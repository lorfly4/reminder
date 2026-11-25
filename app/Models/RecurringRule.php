<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringRule extends Model
{
    protected $fillable = [
        'task_id', 'frequency', 'interval', 'by_weekday', 'until_at',
        'count', 'next_run_at', 'last_generated_at', 'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'by_weekday' => 'array',
            'until_at' => 'datetime',
            'next_run_at' => 'datetime',
            'last_generated_at' => 'datetime',
            'is_enabled' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
