<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warranty\StoreWarrantyRequest;
use App\Http\Requests\Warranty\UpdateWarrantyRequest;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\Warranty;
use App\Traits\ApiResponse;
use App\Traits\UserAccessFilter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WarrantyController extends Controller
{
    use ApiResponse, UserAccessFilter;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Warranty::query()->with(['brand', 'category', 'subCategory', 'creator']);

        $this->applyBrandFilter($query, $user);

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->has('is_void')) {
            $query->where('is_void', $request->is_void);
        }

        if ($request->has('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'expired') {
                $query->expired();
            } elseif ($status === 'void') {
                $query->void();
            }
        }

        if ($request->has('date_from')) {
            $query->where('start_date', '>=', Carbon::parse($request->date_from));
        }

        if ($request->has('date_to')) {
            $query->where('end_date', '<=', Carbon::parse($request->date_to));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('product_serial', 'like', "%{$request->search}%")
                    ->orWhere('product_name', 'like', "%{$request->search}%");
            });
        }

        $warranties = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($warranties);
    }

    public function store(StoreWarrantyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $warranty = Warranty::create($data);

        ActivityLog::log(
            $request->user()->id,
            'created',
            'Warranty',
            $warranty->product_serial,
            $warranty->id
        );

        return $this->created($warranty->load(['brand', 'category', 'subCategory']), 'Warranty created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $user = request()->user();
        $warranty = Warranty::with(['brand', 'category', 'subCategory', 'creator'])
            ->when(! $user->is_admin, fn ($q) => $q->whereIn('brand_id', $this->getAccessibleBrandIds($user)))
            ->find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        return $this->success($warranty);
    }

    public function update(UpdateWarrantyRequest $request, int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        $oldData = $warranty->toArray();
        $data = $request->validated();

        $warranty->update($data);

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'Warranty',
            $warranty->product_serial,
            $warranty->id,
            ['old' => $oldData, 'new' => $warranty->toArray()]
        );

        return $this->success($warranty->load(['brand', 'category', 'subCategory']), 'Warranty updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        if ($warranty->claims()->count() > 0) {
            return $this->error('Cannot delete warranty with associated claims.');
        }

        ActivityLog::log(
            request()->user()->id,
            'deleted',
            'Warranty',
            $warranty->product_serial,
            $warranty->id
        );

        $warranty->delete();

        return $this->deleted('Warranty deleted successfully.');
    }

    public function checkSerial(string $serial): JsonResponse
    {
        $warranty = Warranty::with(['brand', 'category', 'subCategory', 'claims'])->where('product_serial', $serial)->first();

        if (! $warranty) {
            return $this->error('Warranty not found for this serial number.', 404);
        }

        return $this->success([
            'warranty' => $warranty,
            'warranty_status' => $warranty->warranty_status,
            'claim_count' => $warranty->claims()->count(),
        ]);
    }

    public function void(Request $request, int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        $data = $request->validate([
            'void_reason' => 'required|string',
        ]);

        $warranty->update([
            'is_void' => 'YES',
            'void_reason' => $data['void_reason'],
        ]);

        ActivityLog::log(
            $request->user()->id,
            'updated',
            'Warranty',
            $warranty->product_serial,
            $warranty->id,
            ['action' => 'voided', 'reason' => $data['void_reason']]
        );

        return $this->success($warranty, 'Warranty voided successfully.');
    }

    public function unvoid(int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        $warranty->update([
            'is_void' => 'NO',
            'void_reason' => null,
        ]);

        ActivityLog::log(
            request()->user()->id,
            'updated',
            'Warranty',
            $warranty->product_serial,
            $warranty->id,
            ['action' => 'unvoided']
        );

        return $this->success($warranty, 'Warranty unvoided successfully.');
    }

    public function claims(int $id): JsonResponse
    {
        $warranty = Warranty::find($id);

        if (! $warranty) {
            return $this->notFound('Warranty not found.');
        }

        $claims = $warranty->claims()->with(['serviceCenter', 'creator'])->orderBy('id', 'desc')->paginate(15);

        return $this->success($claims);
    }

    public function expiringSoon(Request $request): JsonResponse
    {
        $days = $request->days ?? 30;

        $warranties = Warranty::with(['brand', 'category', 'subCategory'])
            ->expiringSoon($days)
            ->orderBy('end_date', 'asc')
            ->paginate($request->limit ?? 15);

        return $this->success($warranties);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return $this->error('File is empty or has no data rows.');
        }

        $headers = array_map('strtolower', array_map('trim', $rows[0]));
        $dataRows = array_slice($rows, 1);

        $imported = 0;
        $failed = [];
        $createdBy = $request->user()->id;

        foreach ($dataRows as $index => $row) {
            try {
                $data = array_combine($headers, $row);

                if (empty($data['serial number']) && empty($data['product_serial'])) {
                    continue;
                }

                $productSerial = $data['serial number'] ?? $data['product_serial'] ?? null;
                $productName = $data['product name'] ?? $data['product_name'] ?? null;
                $productInfo = $data['product description'] ?? $data['product_info'] ?? null;
                $brandShortName = $data['brand short name'] ?? $data['brand_short_name'] ?? null;
                $categoryShortName = $data['category short name'] ?? $data['category_short_name'] ?? null;
                $subCategoryShortName = $data['sub-category short name'] ?? $data['sub_category_short_name'] ?? null;
                $startDate = $data['start date'] ?? $data['start_date'] ?? null;
                $endDate = $data['end date'] ?? $data['end_date'] ?? null;

                $brandId = null;
                if (! empty($brandShortName)) {
                    $brand = Brand::where('short_name', $brandShortName)->first();
                    $brandId = $brand?->id;
                }

                $categoryId = null;
                if (! empty($categoryShortName)) {
                    $category = ProductCategory::where('short_name', $categoryShortName)->first();
                    $categoryId = $category?->id;
                }

                $subCategoryId = null;
                if (! empty($subCategoryShortName)) {
                    $subCategory = ProductCategory::where('short_name', $subCategoryShortName)->first();
                    $subCategoryId = $subCategory?->id;
                }

                $warrantyData = [
                    'product_serial' => $productSerial,
                    'product_name' => $productName,
                    'product_info' => $productInfo,
                    'brand_id' => $brandId,
                    'category_id' => $categoryId,
                    'sub_category_id' => $subCategoryId,
                    'start_date' => $startDate ? Carbon::parse($startDate)->format('Y-m-d') : now()->format('Y-m-d'),
                    'end_date' => $endDate ? Carbon::parse($endDate)->format('Y-m-d') : now()->addYear()->format('Y-m-d'),
                    'created_by' => $createdBy,
                ];

                Warranty::create($warrantyData);
                $imported++;
            } catch (\Exception $e) {
                $failed[] = ['row' => $index + 2, 'error' => $e->getMessage()];
            }
        }

        return $this->success([
            'imported' => $imported,
            'failed' => $failed,
            'total' => count($dataRows),
        ], 'Warranty import completed.');
    }

    public function importSample(Request $request): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Serial Number',
            'Product Name',
            'Product Description',
            'Brand Short Name',
            'Category Short Name',
            'Sub-Category Short Name',
            'Start Date',
            'End Date',
        ];
        $sheet->fromArray($headers, null, 'A1');
                // 'SERIAL123',
                // 'Sample Product',
                // 'Product Description',
                // 'BrandName',
                // 'CategoryName',
                // 'SubCategoryName',
        $sampleData = [
            [
                '',
                '',
                '',
                '',
                '',
                '',
                now()->format('Y-m-d'),
                now()->addYear()->format('Y-m-d'),
            ],
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'warranty_import_sample.xlsx';
        $path = storage_path('app/'.$filename);
        $writer->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
