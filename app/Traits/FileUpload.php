<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

trait FileUpload
{
    public function uploadFile($file, string $folder): string
    {
        if (! $file) {
            return '';
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs("uploads/{$folder}", $filename);
    }

    public function uploadFiles(array $files, string $folder): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file && is_object($file)) {
                $paths[] = $this->uploadFile($file, $folder);
            }
        }

        return $paths;
    }

    public function uploadBase64File(string $base64Data, string $folder, string $extension = 'jpg'): string
    {
        if (empty($base64Data)) {
            return '';
        }

        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        $base64Data = base64_decode($base64Data);

        if ($base64Data === false) {
            return '';
        }

        $filename = Str::uuid() . '.' . $extension;
        $path = "uploads/{$folder}/{$filename}";

        $fullPath = storage_path($path);
        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, $base64Data);

        return $path;
    }

    public function deleteFile(?string $path): void
    {
        if ($path) {
            $fullPath = storage_path($path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteFile($path);
        }
    }

    public function handleAttachments(?string $attachments, string $folder = 'work-orders'): ?string
    {
        if (empty($attachments)) {
            return null;
        }

        if (is_array($attachments)) {
            $uploadedPaths = [];
            foreach ($attachments as $attachment) {
                if (is_string($attachment)) {
                    if (str_starts_with($attachment, 'data:')) {
                        $ext = 'jpg';
                        if (preg_match('/data:image\/(\w+);/', $attachment, $matches)) {
                            $ext = $matches[1];
                        }
                        $uploadedPaths[] = $this->uploadBase64File($attachment, $folder, $ext);
                    } else {
                        $uploadedPaths[] = $attachment;
                    }
                } elseif (is_object($attachment) && method_exists($attachment, 'getClientOriginalName')) {
                    $uploadedPaths[] = $this->uploadFile($attachment, $folder);
                }
            }

            return json_encode($uploadedPaths);
        }

        if (str_starts_with($attachments, 'data:')) {
            $ext = 'jpg';
            if (preg_match('/data:image\/(\w+);/', $attachments, $matches)) {
                $ext = $matches[1];
            }

            return $this->uploadBase64File($attachments, $folder, $ext);
        }

        return $attachments;
    }

    public function handleImageUpload(string $base64Data, string $folder): string
    {
        if (empty($base64Data)) {
            return '';
        }

        if (str_starts_with($base64Data, 'data:')) {
            /* $ext = 'jpg';
            if (preg_match('/data:image\/(\w+);/', $base64Data, $matches)) {
                $ext = $matches[1];
            }

            return $this->uploadBase64File($base64Data, $folder, $ext); */
            $ext = 'jpg';
            if (preg_match('/data:image\/(\w+);/', $base64Data, $matches)) {
                $ext = $matches[1];
            }

            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
            $base64Data = base64_decode($base64Data);

            if ($base64Data === false) {
                return '';
            }

            $filename = Str::uuid() . '.' . $ext;
            $path = "uploads/{$folder}/{$filename}";
            Storage::disk('public')->put($path, $base64Data);

            return $path;
        }

        return $base64Data;
    }
}
