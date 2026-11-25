<?php

namespace App\Http\Controllers\Web;

use App\Models\Reminder;
use App\Models\Task;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageController
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $base = $user->tasks()->whereNull('archived_at');
        return view('pages.dashboard', [
            'stats' => [
                'total' => (clone $base)->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'pending' => (clone $base)->where('status', '!=', 'completed')->count(),
                'overdue' => (clone $base)->overdue()->count(),
            ],
            'todayTasks' => (clone $base)->where(function ($query): void {
                $query->whereDate('start_at', now()->toDateString())
                    ->orWhereDate('deadline_at', now()->toDateString());
            })->where('status', '!=', 'completed')->orderBy('deadline_at')->limit(10)->get(),
            'recentActivity' => $user->activityLogs()->latest()->limit(8)->get(),
            'unreadCount' => $user->databaseNotifications()->whereNull('read_at')->count(),
        ]);
    }

    public function tasks(Request $request): View
    {
        $tasks = $request->user()->tasks()->with('category')->latest()->paginate(20)->withQueryString();
        return view('pages.tasks', [
            'tasks' => $tasks,
            'categories' => $request->user()->categories()->orderBy('name')->get(),
            'unreadCount' => $request->user()->databaseNotifications()->whereNull('read_at')->count(),
        ]);
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'start_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);
        if (! empty($validated['category_id']) && ! $request->user()->categories()->whereKey($validated['category_id'])->exists()) {
            return back()->withErrors(['category_id' => 'Kategori tidak valid.']);
        }
        $request->user()->tasks()->create([...$validated, 'status' => 'todo']);
        return back()->with('status', 'Tugas berhasil ditambahkan.');
    }

    public function updateTask(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'deadline_at' => ['nullable', 'date'],
        ]);
        $task->update($validated);
        return back()->with('status', 'Tugas berhasil diperbarui.');
    }

    public function completeTask(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        return back()->with('status', 'Tugas selesai.');
    }

    public function reopenTask(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);
        $task->update(['status' => 'todo', 'completed_at' => null]);
        return back()->with('status', 'Tugas dibuka kembali.');
    }

    public function archiveTask(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);
        $task->update(['archived_at' => now()]);
        return back()->with('status', 'Tugas diarsipkan.');
    }

    public function deleteTask(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);
        $task->delete();
        return back()->with('status', 'Tugas dihapus.');
    }

    public function calendar(Request $request): View
    {
        $start = now()->startOfMonth();
        $end = now()->copy()->endOfMonth();
        $tasks = $request->user()->tasks()->where(function ($query) use ($start, $end): void {
            $query->whereBetween('start_at', [$start, $end])
                ->orWhereBetween('deadline_at', [$start, $end]);
        })->get();
        return view('pages.calendar', [
            'tasks' => $tasks,
            'unreadCount' => $request->user()->databaseNotifications()->whereNull('read_at')->count(),
        ]);
    }

    public function reminders(Request $request): View
    {
        $reminders = Reminder::whereHas('task', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('task')->latest()->paginate(20);
        return view('pages.reminders', [
            'reminders' => $reminders,
            'tasks' => $request->user()->tasks()->where('status', '!=', 'completed')->orderBy('title')->get(),
            'unreadCount' => $request->user()->databaseNotifications()->whereNull('read_at')->count(),
        ]);
    }

    public function storeReminder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
            'remind_at' => ['required', 'date', 'after:now'],
            'channels' => ['nullable', 'array'],
        ]);
        $task = $request->user()->tasks()->whereKey($validated['task_id'])->first();
        abort_unless($task, 403);
        $task->reminders()->create([
            'remind_at' => $validated['remind_at'],
            'channels' => $validated['channels'] ?? ['in_app'],
            'status' => 'pending',
            'is_enabled' => true,
            'dedupe_key' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        return back()->with('status', 'Pengingat berhasil ditambahkan.');
    }

    public function toggleReminder(Request $request, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->task->user_id === $request->user()->id, 403);
        $reminder->update(['is_enabled' => ! $reminder->is_enabled]);
        return back();
    }

    public function snoozeReminder(Request $request, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->task->user_id === $request->user()->id, 403);
        $reminder->update(['status' => 'pending', 'snoozed_until' => now()->addMinutes(10)]);
        return back()->with('status', 'Pengingat ditunda 10 menit.');
    }

    public function deleteReminder(Request $request, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->task->user_id === $request->user()->id, 403);
        $reminder->update(['status' => 'cancelled', 'is_enabled' => false]);
        return back()->with('status', 'Pengingat dibatalkan.');
    }

    public function settings(Request $request): View
    {
        $settings = $request->user()->notificationSetting()->firstOrCreate([
            'user_id' => $request->user()->id,
        ], ['in_app' => true, 'email' => true, 'web_push' => true, 'fcm' => true]);
        return view('pages.settings', [
            'user' => $request->user(),
            'notificationSettings' => $settings,
            'categories' => $request->user()->categories()->orderBy('name')->get(),
            'unreadCount' => $request->user()->databaseNotifications()->whereNull('read_at')->count(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'timezone:all'],
            'language' => ['required', 'in:id,en'],
            'theme' => ['required', 'in:light,dark,system'],
        ]);
        $request->user()->update($validated);
        return back()->with('status', 'Pengaturan berhasil disimpan.');
    }

    public function exportSettings(Request $request): StreamedResponse
    {
        $user = $request->user()->load([
            'categories', 'tasks.category', 'tasks.reminders.deliveries',
            'tasks.recurringRule', 'activityLogs', 'databaseNotifications',
            'pushSubscriptions', 'notificationSetting',
        ]);
        $payload = [
            'profile' => $user->only(['id', 'name', 'email', 'timezone', 'language', 'theme', 'created_at']),
            'categories' => $user->categories,
            'tasks' => $user->tasks,
            'activity_logs' => $user->activityLogs,
            'notifications' => $user->databaseNotifications,
        ];
        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            'remindly-export.json',
            ['Content-Type' => 'application/json'],
        );
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $request->user()->categories()->create($validated);
        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
        $category->update($validated);
        return back()->with('status', 'Kategori berhasil diperbarui.');
    }

    public function deleteCategory(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        $category->tasks()->update(['category_id' => null]);
        $category->delete();
        return back()->with('status', 'Kategori dihapus.');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:web']]);
        $user = $request->user();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $user->delete();
        return redirect()->route('login')->with('status', 'Akun berhasil dihapus.');
    }
}
