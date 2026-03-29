<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends BaseModel
{
    protected $table = 'wms_activity_logs';

    public $timestamps = true;

    protected $fillable = [
        'created_by',
        'action',
        'log_type',
        'log_type_title',
        'log_type_id',
        'changes',
        'log_for',
        'log_for_id',
    ];

    protected $casts = [
        'changes' => 'array',
        'log_type_id' => 'integer',
        'log_for_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function log(
        int $userId,
        string $action,
        string $logType,
        string $logTypeTitle,
        int $logTypeId,
        ?array $changes = null,
        ?string $logFor = null,
        ?int $logForId = null
    ): self {
        return static::create([
            'created_by' => $userId,
            'action' => $action,
            'log_type' => $logType,
            'log_type_title' => $logTypeTitle,
            'log_type_id' => $logTypeId,
            'changes' => $changes,
            'log_for' => $logFor,
            'log_for_id' => $logForId,
        ]);
    }
}
