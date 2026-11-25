<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'category_id', 'start_at', 'deadline_at',
        'priority', 'status', 'completed_at', 'archived_at',
        'recurring_template_id', 'occurrence_at', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'deadline_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function recurringRule(): HasOne
    {
        return $this->hasOne(RecurringRule::class);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('archived_at')
            ->where('status', '!=', 'completed')
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', now());
    }
}
