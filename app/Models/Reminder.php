<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id', 'remind_at', 'channels', 'status', 'is_enabled',
        'snoozed_until', 'attempts', 'sent_at', 'last_error', 'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'snoozed_until' => 'datetime',
            'sent_at' => 'datetime',
            'channels' => 'array',
            'is_enabled' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('is_enabled', true)
            ->where(function (Builder $query): void {
                $query->where(function (Builder $q): void {
                    $q->whereNull('snoozed_until')->where('remind_at', '<=', now());
                })->orWhere('snoozed_until', '<=', now());
            });
    }

    public function effectiveTime(): \Illuminate\Support\Carbon
    {
        return $this->snoozed_until ?? $this->remind_at;
    }
}
