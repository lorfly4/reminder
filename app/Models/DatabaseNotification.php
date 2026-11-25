<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseNotification extends Model
{
    protected $table = 'notifications';
    protected $fillable = ['user_id', 'task_id', 'type', 'title', 'message', 'dedupe_key', 'read_at'];
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
