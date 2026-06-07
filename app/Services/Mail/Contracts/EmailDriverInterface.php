<?php

namespace App\Services\Mail\Contracts;

interface EmailDriverInterface
{
    public function send(string $to, string $subject, string $body): bool;
}
