@extends('layouts.app')
@section('title', 'Pengingat - Remindly')
@section('content')
    <section class="card panel page-panel">
        <div class="panel-header"><h2>Pengingat</h2></div>
        @if($errors->any())<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="task-create" method="POST" action="{{ route('reminders.store') }}">
            @csrf
            <label>Tugas
                <select name="task_id" required>
                    <option value="">Pilih tugas</option>
                    @foreach($tasks as $task)<option value="{{ $task->id }}">{{ $task->title }}</option>@endforeach
                </select>
            </label>
            <label>Waktu<input type="datetime-local" name="remind_at" required></label>
            <button class="primary-button" type="submit">Tambah</button>
        </form>
        @if($reminders->isEmpty())
            <div class="empty-state"><div><strong class="empty-icon">♢</strong><p>Belum ada pengingat.</p></div></div>
        @else
            <div class="task-table">
                @foreach($reminders as $reminder)
                    <article class="task-row task-row-expanded">
                        <div class="task-main">
                            <strong>{{ $reminder->task->title ?? 'Task' }}</strong>
                            <span>{{ $reminder->effectiveTime()->timezone(auth()->user()->timezone)->format('d M Y H:i') }}</span>
                        </div>
                        <div class="task-actions">
                            <form method="POST" action="{{ route('reminders.toggle', $reminder) }}">@csrf @method('PATCH')<button class="secondary-button" type="submit">{{ $reminder->is_enabled ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                            <form method="POST" action="{{ route('reminders.snooze', $reminder) }}">@csrf<button class="secondary-button" type="submit">Snooze 10m</button></form>
                            <form method="POST" action="{{ route('reminders.destroy', $reminder) }}" onsubmit="return confirm('Batalkan pengingat?')">@csrf @method('DELETE')<button class="danger-button" type="submit">Hapus</button></form>
                        </div>
                    </article>
                @endforeach
            </div>
            {{ $reminders->links() }}
        @endif
    </section>
@endsection
