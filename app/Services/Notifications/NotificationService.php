<?php

namespace App\Services\Notifications;

use App\Models\Claim;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    protected EmailService $emailService;

    protected SmsService $smsService;

    protected WhatsAppService $whatsAppService;

    protected array $channels = [];

    public function __construct(
        EmailService $emailService,
        SmsService $smsService,
        WhatsAppService $whatsAppService
    ) {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->whatsAppService = $whatsAppService;

        $this->channels = array_filter([
            'email' => config('services.notifications.channels.email', false),
            'sms' => config('services.notifications.channels.sms', false),
            'whatsapp' => config('services.notifications.channels.whatsapp', false),
        ]);
    }

    public function sendClaimCreatedNotification(Claim $claim): void
    {
        $warranty = $claim->warranty;
        $customerName = trim(($claim->customer_firstname ?? '').' '.($claim->customer_lastname ?? ''));
        $productName = $warranty ? ($warranty->product_name ?? 'N/A') : 'N/A';
        $productSerial = $warranty ? ($warranty->product_serial ?? 'N/A') : 'N/A';
        $claimDate = $claim->claim_date ? $claim->claim_date->format('Y-m-d') : 'N/A';

        $data = [
            'customerName' => $customerName,
            'claimNumber' => $claim->claim_number,
            'productName' => $productName,
            'productSerial' => $productSerial,
            'problemDescription' => $claim->problem_description,
            'claimDate' => $claimDate,
        ];

        if ($this->channels['email'] ?? false) {
            $this->sendClaimCreatedEmail($claim->customer_email, $data);
        }

        if ($this->channels['sms'] ?? false) {
            $this->sendClaimCreatedSms($claim->customer_phone, $data);
        }

        if ($this->channels['whatsapp'] ?? false) {
            $this->sendClaimCreatedWhatsApp($claim->customer_phone, $data);
        }
    }

    public function sendWorkOrderCreatedNotification(WorkOrder $workOrder): void
    {
        $claim = $workOrder->claim;
        $warranty = $claim?->warranty;
        $customerName = $claim ? trim(($claim->customer_firstname ?? '').' '.($claim->customer_lastname ?? '')) : 'Customer';
        $claimNumber = $claim ? ($claim->claim_number ?? 'N/A') : 'N/A';
        $productName = $warranty ? ($warranty->product_name ?? 'N/A') : 'N/A';
        $createdDate = $workOrder->created_at ? $workOrder->created_at->format('Y-m-d H:i') : 'N/A';

        $data = [
            'customerName' => $customerName,
            'workOrderNumber' => $workOrder->wo_number,
            'claimNumber' => $claimNumber,
            'productName' => $productName,
            'status' => $workOrder->status,
            'createdDate' => $createdDate,
        ];

        if ($this->channels['email'] ?? false) {
            $this->sendWorkOrderCreatedEmail($claim?->customer_email, $data);
        }

        if ($this->channels['sms'] ?? false) {
            $this->sendWorkOrderCreatedSms($claim?->customer_phone, $data);
        }

        if ($this->channels['whatsapp'] ?? false) {
            $this->sendWorkOrderCreatedWhatsApp($claim?->customer_phone, $data);
        }
    }

    public function sendWorkOrderStatusNotification(WorkOrder $workOrder, string $previousStatus): void
    {
        $claim = $workOrder->claim;
        $warranty = $claim?->warranty;
        $customerName = $claim ? trim(($claim->customer_firstname ?? '').' '.($claim->customer_lastname ?? '')) : 'Customer';
        $productName = $warranty ? ($warranty->product_name ?? 'N/A') : 'N/A';
        $updatedDate = now()->format('Y-m-d H:i');

        $statusMessages = [
            'Pending' => 'Your work order is pending. Our team will assign it to a service center soon.',
            'In Progress' => 'Your product is now being serviced. Our technicians are working on it.',
            'Completed' => 'Your product has been serviced successfully. We will arrange for delivery.',
            'Delivered' => 'Your product has been delivered. Thank you for your patience!',
        ];

        $data = [
            'customerName' => $customerName,
            'workOrderNumber' => $workOrder->wo_number,
            'previousStatus' => $previousStatus,
            'currentStatus' => $workOrder->status,
            'productName' => $productName,
            'updatedDate' => $updatedDate,
            'statusMessage' => $statusMessages[$workOrder->status] ?? "Your work order status has been updated to: {$workOrder->status}",
        ];

        if ($this->channels['email'] ?? false) {
            $this->sendWorkOrderStatusEmail($claim?->customer_email, $data);
        }

        if ($this->channels['sms'] ?? false) {
            $this->sendWorkOrderStatusSms($claim?->customer_phone, $data);
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

        try {
            Mail::send('emails.claim.created', $data, function ($message) use ($email, $data) {
                $message->to($email)
                    ->subject('Claim Created Successfully - '.$data['claimNumber']);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Claim Created Email failed: '.$e->getMessage());

            return false;
        }
    }

    protected function sendClaimCreatedSms(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "Dear {$data['customerName']}, your claim {$data['claimNumber']} has been created successfully. "
            ."Product: {$data['productName']}. Issue: {$data['problemDescription']}. "
            .'We will keep you updated on the progress. Thank you!';

        return $this->smsService->send($phone, 'Claim Created', $message);
    }

    protected function sendClaimCreatedWhatsApp(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "✅ *Claim Created Successfully*\n\n"
            ."Dear {$data['customerName']},\n\n"
            ."Your claim has been created.\n\n"
            ."📋 *Claim Number:* {$data['claimNumber']}\n"
            ."📦 *Product:* {$data['productName']}\n"
            ."🔢 *Serial:* {$data['productSerial']}\n"
            ."📝 *Issue:* {$data['problemDescription']}\n\n"
            .'We will keep you updated. Thank you!';

        return $this->whatsAppService->send($phone, 'Claim Created', $message);
    }

    protected function sendWorkOrderCreatedEmail(string $email, array $data): bool
    {
        if (empty($email)) {
            return false;
        }

        try {
            Mail::send('emails.work-order.created', $data, function ($message) use ($email, $data) {
                $message->to($email)
                    ->subject('Work Order Created - '.$data['workOrderNumber']);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Work Order Created Email failed: '.$e->getMessage());

            return false;
        }
    }

    protected function sendWorkOrderCreatedSms(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "Dear {$data['customerName']}, your work order {$data['workOrderNumber']} has been created. "
            ."Status: {$data['status']}. "
            .'Our service team will contact you soon. Thank you!';

        return $this->smsService->send($phone, 'Work Order Created', $message);
    }

    protected function sendWorkOrderCreatedWhatsApp(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "🔧 *Work Order Created*\n\n"
            ."Dear {$data['customerName']},\n\n"
            ."Your work order has been created.\n\n"
            ."📋 *WO Number:* {$data['workOrderNumber']}\n"
            ."📎 *Claim:* {$data['claimNumber']}\n"
            ."📦 *Product:* {$data['productName']}\n"
            ."⏳ *Status:* {$data['status']}\n\n"
            .'Our service team will contact you soon.';

        return $this->whatsAppService->send($phone, 'Work Order Created', $message);
    }

    protected function sendWorkOrderStatusEmail(string $email, array $data): bool
    {
        if (empty($email)) {
            return false;
        }

        try {
            Mail::send('emails.work-order.status-updated', $data, function ($message) use ($email, $data) {
                $message->to($email)
                    ->subject('Work Order Status Update - '.$data['workOrderNumber']);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Work Order Status Email failed: '.$e->getMessage());

            return false;
        }
    }

    protected function sendWorkOrderStatusSms(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "Dear {$data['customerName']}, your work order {$data['workOrderNumber']} status changed from "
            ."{$data['previousStatus']} to {$data['currentStatus']}. "
            ."{$data['statusMessage']} Thank you!";

        return $this->smsService->send($phone, 'WO Status Update', $message);
    }

    protected function sendWorkOrderStatusWhatsApp(string $phone, array $data): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = "📋 *Work Order Status Update*\n\n"
            ."Dear {$data['customerName']},\n\n"
            ."Your work order status has been updated.\n\n"
            ."📋 *WO Number:* {$data['workOrderNumber']}\n"
            ."🔄 *Previous Status:* {$data['previousStatus']}\n"
            ."✅ *Current Status:* {$data['currentStatus']}\n\n"
            ."{$data['statusMessage']}\n\n"
            .'Thank you for your patience!';

        return $this->whatsAppService->send($phone, 'WO Status Update', $message);
    }
}
