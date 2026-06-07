<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Claim;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Traits\ApiResponse;
use App\Traits\UserAccessFilter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    use ApiResponse, UserAccessFilter;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Product::query()->with(['brand', 'category', 'subCategory', 'creator']);

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

        if ($request->has('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->active();
            } elseif ($status === 'expired') {
                $query->expired();
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
                $q->where('model_no', 'like', "%{$request->search}%")
                    ->orWhere('item_description', 'like', "%{$request->search}%");
            });
        }

        $products = $query->orderBy('id', 'desc')->paginate($request->limit ?? 15);

        return $this->success($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;

            $product = Product::create($data);

            ActivityLog::log(
                $request->user()->id,
                'created',
                'Product',
                $product->model_no,
                $product->id
            );

            DB::commit();

            return $this->created($product->load(['brand', 'category', 'subCategory']), 'Product created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $user = request()->user();
        $product = Product::with(['brand', 'category', 'subCategory', 'creator', 'claims'])
            ->when(! $user->is_admin, fn($q) => $q->whereIn('brand_id', $this->getAccessibleBrandIds($user)))
            ->find($id);

        if (! $product) {
            return $this->notFound('Product not found.');
        }

        return $this->success($product);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $product = Product::find($id);

            if (! $product) {
                return $this->notFound('Product not found.');
            }

            $oldData = $product->toArray();
            $data = $request->validated();

            $product->update($data);

            ActivityLog::log(
                $request->user()->id,
                'updated',
                'Product',
                $product->model_no,
                $product->id,
                ['old' => $oldData, 'new' => $product->toArray()]
            );

            DB::commit();

            return $this->success($product->load(['brand', 'category', 'subCategory']), 'Product updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return $this->notFound('Product not found.');
        }

        if ($product->claims()->count() > 0) {
            return $this->error('Cannot delete product with associated claims.');
        }

        ActivityLog::log(
            request()->user()->id,
            'deleted',
            'Product',
            $product->model_no,
            $product->id
        );

        $product->delete();

        return $this->deleted('Product deleted successfully.');
    }

    public function checkSerial(string $serial): JsonResponse
    {
        $claimQuery = Claim::with(['product.brand', 'product.category', 'product.subCategory'])
            ->where('serial_number', $serial);

        $claimList = $claimQuery->orderBy('id', 'asc')->get();

        $pendingStatuses = [
            'Not Assigned',
            'Assigned',
            'In Progress',
            'Waiting for Part',
        ];

        $claimStatus = !$claimList->contains(function ($claim) use ($pendingStatuses) {
            return in_array($claim->status, $pendingStatuses);
        });

        return $this->success([
            'claim_status' => $claimStatus,
            'claim_list' => $claimList,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
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

                    if (empty($data['model no'])) {
                        continue;
                    }

                    $modelNo = $data['model no'] ?? $data['model no'] ?? null;
                    $itemDescription = $data['item description'] ?? $data['item description'] ?? null;
                    $brandShortName = $data['brand short name'] ?? $data['brand short name'] ?? null;
                    $categoryShortName = $data['category short name'] ?? $data['category short name'] ?? null;
                    $subCategoryShortName = $data['sub-category short name'] ?? $data['sub-category short name'] ?? null;
                    $startDate = $data['start date'] ?? $data['start date'] ?? null;
                    $endDate = $data['end date'] ?? $data['end date'] ?? null;
                    $isCountable = $data['is countable'] ?? $data['is countable'] ?? false;

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

                    $productData = [
                        'model_no' => $modelNo,
                        'item_description' => $itemDescription,
                        'brand_id' => $brandId,
                        'category_id' => $categoryId,
                        'sub_category_id' => $subCategoryId,
                        'is_countable' => filter_var($isCountable, FILTER_VALIDATE_BOOLEAN) ? true : false,
                        'start_date' => $startDate ? Carbon::parse($startDate)->format('Y-m-d') : null,
                        'end_date' => $endDate ? Carbon::parse($endDate)->format('Y-m-d') : null,
                        'created_by' => $createdBy,
                    ];

                    Product::create($productData);
                    $imported++;
                } catch (\Exception $e) {
                    $failed[] = ['row' => $index + 2, 'error' => $e->getMessage()];
                }
            }

            DB::commit();

            return $this->success([
                'imported' => $imported,
                'failed' => $failed,
                'total' => count($dataRows),
            ], 'Product import completed.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    public function importSample(Request $request): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Model No',
            'Item Description',
            'Brand Short Name',
            'Category Short Name',
            'Sub-Category Short Name',
            'Start Date',
            'End Date',
            'Is Countable',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $sampleData = [
            [
                'SMG-S24-ULTRA',
                'Samsung Galaxy S24 Ultra',
                'Samsung',
                'Mobile',
                'Smartphone',
                now()->format('Y-m-d'),
                now()->addYear()->format('Y-m-d'),
                'true',
            ],
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'product_import_sample.xlsx';
        $path = storage_path('app/' . $filename);
        $writer->save($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
