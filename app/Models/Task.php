<?php

// app/Models/Task.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'detail',
        'notes',
        'priority',
        'status',
        'assigned_to',
        'category',
        'calendar_event_id',
        'sort_order',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Priority levels for display and sorting.
     */
    public const PRIORITIES = ['high', 'medium', 'low'];

    /**
     * Status options.
     */
    public const STATUSES = ['pending', 'in_progress', 'completed'];

    /**
     * Category options.
     */
    public const CATEGORIES = ['launch', 'admin', 'content', 'marketing', 'technical', 'school', 'personal', 'other'];

    /**
     * Check if the task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Mark the task as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the task as pending (reopen).
     */
    public function markPending(): void
    {
        $this->update([
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }

    /**
     * Scope: only pending/in-progress tasks. Column qualified so the scope
     * stays composition-safe when callers join other tables that have a
     * `status` column. See docs/dev-rules.md "Model scopes" rule.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('tasks.status', ['pending', 'in_progress']);
    }

    /**
     * Scope: order by priority (high first) then sort_order. Columns
     * qualified so the scope stays composition-safe when joined.
     */
    public function scopePriorityOrder($query)
    {
        return $query->orderByRaw("CASE tasks.priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
                     ->orderBy('tasks.created_at', 'desc');
    }
}
