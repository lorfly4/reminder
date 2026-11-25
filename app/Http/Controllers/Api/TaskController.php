<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\ActivityLog;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = $request->user()->tasks()
            ->with('category')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$request->search.'%')->orWhere('description', 'like', '%'.$request->search.'%')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->boolean('archived'), fn ($q) => $q->whereNotNull('archived_at'), fn ($q) => $q->whereNull('archived_at'))
            ->when($request->filled('sort'), fn ($q) => match ($request->string('sort')->toString()) {
                'deadline' => $q->orderByRaw('deadline_at IS NULL')->orderBy('deadline_at'),
                'priority' => $q->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')"),
                default => $q->latest(),
            }, fn ($q) => $q->latest())
            ->paginate($request->integer('per_page', 15))->withQueryString();

        return $this->success($tasks->items(), $tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->validated());
        $this->activity($request->user()->id, $task->id, 'created', 'Task dibuat');
        return $this->success($task->load('category'), null, 'Task created', 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorizeForUser($request->user(), 'view', $task);
        return $this->success($task->load('category'));
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorizeForUser($request->user(), 'update', $task);
        $task->update($request->validated());
        $this->activity($request->user()->id, $task->id, 'updated', 'Task diperbarui');
        return $this->success($task->fresh('category'), null, 'Task updated');
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorizeForUser($request->user(), 'delete', $task);
        $task->delete();
        $this->activity($request->user()->id, null, 'deleted', 'Task dihapus');
        return $this->success(null, null, 'Task deleted');
    }

    public function complete(Request $request, Task $task): JsonResponse
    {
        $this->authorizeForUser($request->user(), 'update', $task);
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        $this->activity($request->user()->id, $task->id, 'completed', 'Task diselesaikan');
        return $this->success($task->fresh());
    }

    public function reopen(Request $request, Task $task): JsonResponse
    {
        $this->authorizeForUser($request->user(), 'update', $task);
        $task->update(['status' => 'todo', 'completed_at' => null]);
        $this->activity($request->user()->id, $task->id, 'reopened', 'Task dibuka kembali');
        return $this->success($task->fresh());
    }

    public function archive(Request $request, Task $task): JsonResponse
    {
        $this->authorizeForUser($request->user(), 'update', $task);
        $task->update(['archived_at' => now()]);
        $this->activity($request->user()->id, $task->id, 'archived', 'Task diarsipkan');
        return $this->success($task->fresh());
    }

    public function unarchive(Request $request, Task $task): JsonResponse
    {
        $this->authorizeForUser($request->user(), 'update', $task);
        $task->update(['archived_at' => null]);
        $this->activity($request->user()->id, $task->id, 'unarchived', 'Task dikembalikan');
        return $this->success($task->fresh());
    }

    public function reschedule(Request $request, Task $task): JsonResponse
    {
        $this->authorizeForUser($request->user(), 'update', $task);
        $validated = $request->validate([
            'start_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);
        $task->update($validated);
        $this->activity($request->user()->id, $task->id, 'rescheduled', 'Task dijadwalkan ulang');
        return $this->success($task->fresh());
    }

    private function activity(int $userId, ?int $taskId, string $action, string $description): void
    {
        ActivityLog::create(['user_id' => $userId, 'task_id' => $taskId, 'action' => $action, 'description' => $description]);
    }

    private function authorizeForUser($user, string $ability, Task $task): void
    {
        if (! app(\Illuminate\Contracts\Auth\Access\Gate::class)->forUser($user)->allows($ability, $task)) {
            abort(403, 'Akses ditolak.');
        }
    }

    private function success(mixed $data, mixed $paginator = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $meta = $paginator ? [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ] : null;
        return response()->json(['data' => $data, 'meta' => $meta, 'message' => $message], $status);
    }
}
