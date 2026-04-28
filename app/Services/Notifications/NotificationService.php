<?php

namespace App\Services\Notifications;

use App\Models\Claim;
use App\Models\WorkOrder;

class NotificationService
{
    protected EmailService $emailService;
    protected WhatsAppService $whatsAppService;
    protected array $channels = [];

    public function __construct(
        EmailService $emailService,
        WhatsAppService $whatsAppService
    ) {
        $this->emailService = $emailService;
        $this->whatsAppService = $whatsAppService;

        $this->channels = array_filter([
            'email' => config('services.notifications.channels.email', false),
            'whatsapp' => config('services.notifications.channels.whatsapp', false),
        ]);
    }

    public function sendClaimCreatedNotification(Claim $claim): void
    {
        $product = $claim->product;
        $customerName = trim(($claim->customer->customer_name ?? ''));
        $productName = $product->model_no ;
        $productSerial = $product->serial_number;
        $claimDate = $claim->claim_date ? $claim->claim_date->format('Y-m-d') : 'N/A';

        $data = [
            'customerName' => $customerName,
            'claimNumber' => $claim->claim_number,
            'claimStatus' => $claim->status,
            'productName' => $productName,
            'productSerial' => $productSerial,
            'problemDescription' => $claim->problem_description,
            'claimDate' => $claimDate,
            'companyName' => config('settings.company_name', 'SNP Distribution'),
            'companySubtitle' => config('settings.company_subtitle', 'Warranty Service Center'),
        ];

        if ($this->channels['email'] ?? false) {
            $this->sendClaimCreatedEmail($claim->customer->email, $data);
        }

        if ($this->channels['whatsapp'] ?? false && $claim->customer->phone) {
            $this->sendClaimCreatedWhatsApp($claim->customer->phone, $data);
        }
    }

    public function sendWorkOrderCreatedNotification(WorkOrder $workOrder): void
    {
        $claim = $workOrder->claim;
        $warranty = $claim?->warranty;
        $customerName = $claim ? trim(($claim->customer_firstname ?? '') . ' ' . ($claim->customer_lastname ?? '')) : 'Customer';
        $claimNumber = $claim ? ($claim->claim_number ?? 'N/A') : 'N/A';
        $productName = $warranty ? ($warranty->product_name ?? 'N/A') : 'N/A';
        $productSerial = $warranty ? ($warranty->product_serial ?? 'N/A') : 'N/A';
        $createdDate = $workOrder->created_at ? $workOrder->created_at->format('Y-m-d H:i') : 'N/A';

        $data = [
            'customerName' => $customerName,
            'workOrderNumber' => $workOrder->wo_number,
            'workOrderStatus' => $workOrder->status,
            'workOrderFeedbackPreference' => $workOrder->feedback_preference,
            'workOrderFeedbackToken ' => $workOrder->feedback_token,
            'claimNumber' => $claimNumber,
            'claimStatus' => $claim->status,
            'productName' => $productName,
            'productSerial' => $productSerial,
            'status' => $workOrder->status,
            'createdDate' => $createdDate,
        ];

        if ($this->channels['email'] ?? false) {
            $this->sendWorkOrderCreatedEmail($claim?->customer_email, $data);
        }

        if ($this->channels['whatsapp'] ?? false) {
            $this->sendWorkOrderCreatedWhatsApp($claim?->customer_phone, $data);
        }
    }

    public function sendWorkOrderStatusNotification(WorkOrder $workOrder, string $previousStatus): void
    {
        $claim = $workOrder->claim;
        $warranty = $claim?->warranty;
        $customerName = $claim ? trim(($claim->customer_firstname ?? '') . ' ' . ($claim->customer_lastname ?? '')) : 'Customer';
        $productName = $warranty ? ($warranty->product_name ?? 'N/A') : 'N/A';
        $claimNumber = $claim ? ($claim->claim_number ?? 'N/A') : 'N/A';
        $productSerial = $warranty ? ($warranty->product_serial ?? 'N/A') : 'N/A';
        $createdDate = $workOrder->created_at ? $workOrder->created_at->format('Y-m-d H:i') : 'N/A';
        $updatedDate = now()->format('Y-m-d H:i');

        $statusMessages = [
            'Progress' => 'Your work order is in progress. Our team is working on it.',
            'Closed' => 'Your product has been serviced. We will arrange for delivery.',
            'Delivered' => 'Your product has been delivered. Thank you for your patience!',
        ];

        $data = [
            'customerName' => $customerName,
            'workOrderNumber' => $workOrder->wo_number,
            'workOrderStatus' => $workOrder->status,
            'workOrderFeedbackPreference' => $workOrder->feedback_preference,
            'workOrderFeedbackToken ' => $workOrder->feedback_token,
            'previousStatus' => $previousStatus,
            'currentStatus' => $workOrder->status,
            'claimNumber' => $claimNumber,
            'claimStatus' => $claim->status,
            'productName' => $productName,
            'productSerial' => $productSerial,
            'createdDate' => $createdDate,
            'updatedDate' => $updatedDate,
            'statusMessage' => $statusMessages[$workOrder->status] ?? "Your work order status has been updated to: {$workOrder->status}",
        ];

        if ($this->channels['email'] ?? false) {
            $this->sendWorkOrderStatusEmail($claim?->customer_email, $data);
        }

        if ($this->channels['whatsapp'] ?? false) {
            $this->sendWorkOrderStatusWhatsApp($claim?->customer_phone, $data);
        }
    }

    protected function sendClaimCreatedEmail(string $email, array $data): bool
    {
        if (empty($email)) {
            return false;
        }

        return $this->emailService->send(
            $email,
            'Claim Created Successfully - ' . $data['claimNumber'],
            view('emails.claim.created', $data)->render()
        );
    }

    protected function sendClaimCreatedWhatsApp(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "✅ *Claim Created Successfully*\n\n"
            . "Dear {$data['customerName']},\n\n"
            . "Your claim has been created.\n\n"
            . "📋 *Claim Number:* {$data['claimNumber']}\n"
            . "📦 *Product:* {$data['productName']}\n"
            . "🔢 *Serial:* {$data['productSerial']}\n"
            . "📝 *Issue:* {$data['problemDescription']}\n\n"
            . 'We will keep you updated. Thank you!';

        return $this->whatsAppService->send($phone, 'Claim Created', $message);
    }

    protected function sendWorkOrderCreatedEmail(string $email, array $data): bool
    {
        if (empty($email)) {
            return false;
        }

        return $this->emailService->send(
            $email,
            'Work Order Created - ' . $data['workOrderNumber'],
            view('emails.work-order.created', $data)->render()
        );
    }

    protected function sendWorkOrderCreatedWhatsApp(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "🔧 *Work Order Created*\n\n"
            . "Dear {$data['customerName']},\n\n"
            . "Your work order has been created.\n\n"
            . "📋 *WO Number:* {$data['workOrderNumber']}\n"
            . "📎 *Claim:* {$data['claimNumber']}\n"
            . "📦 *Product:* {$data['productName']}\n"
            . "⏳ *Status:* {$data['status']}\n\n"
            . 'Our service team will contact you soon.';

        return $this->whatsAppService->send($phone, 'Work Order Created', $message);
    }

    protected function sendWorkOrderStatusEmail(string $email, array $data): bool
    {
        if (empty($email)) {
            return false;
        }

        return $this->emailService->send(
            $email,
            'Work Order Status Update - ' . $data['workOrderNumber'],
            view('emails.work-order.status-updated', $data)->render()
        );
    }

    protected function sendWorkOrderStatusWhatsApp(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "📋 *Work Order Status Update*\n\n"
            . "Dear {$data['customerName']},\n\n"
            . "Your work order status has been updated.\n\n"
            . "📋 *WO Number:* {$data['workOrderNumber']}\n"
            . "🔄 *Previous Status:* {$data['previousStatus']}\n"
            . "✅ *Current Status:* {$data['currentStatus']}\n\n"
            . "{$data['statusMessage']}\n\n"
            . 'Thank you for your patience!';

        return $this->whatsAppService->send($phone, 'WO Status Update', $message);
    }
}
