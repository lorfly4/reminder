@extends('layouts.app')
@section('title', 'Tugas - Remindly')
@section('content')
    <section class="card panel page-panel">
        <div class="panel-header"><h2>Daftar Tugas</h2></div>
        @if($errors->any())<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="task-create" method="POST" action="{{ route('tasks.store') }}">
            @csrf
            <label>Judul tugas<input type="text" name="title" value="{{ old('title') }}" required></label>
            <label>Prioritas
                <select name="priority">
                    <option value="low">Rendah</option><option value="medium" selected>Sedang</option><option value="high">Tinggi</option><option value="urgent">Urgent</option>
                </select>
            </label>
            <label>Kategori
                <select name="category_id"><option value="">Tanpa kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
            </label>
            <button class="primary-button" type="submit">Tambah</button>
        </form>
        @if($tasks->isEmpty())
            <div class="empty-state"><div><strong class="empty-icon">☑</strong><p>Belum ada tugas.</p></div></div>
        @else
            <div class="task-table">
                @foreach($tasks as $task)
                    <article class="task-row task-row-expanded">
                        <div class="task-main">
                            <strong>{{ $task->title }}</strong>
                            <span class="priority priority-{{ $task->priority }}">{{ $task->priority }}</span>
                            @if($task->deadline_at)<small>Deadline {{ $task->deadline_at->timezone(auth()->user()->timezone)->format('d M Y H:i') }}</small>@endif
                        </div>
                        <div class="task-actions">
                            <details class="inline-edit">
                                <summary class="secondary-button">Edit</summary>
                                <form class="inline-edit-form" method="POST" action="{{ route('tasks.update', $task) }}">
                                    @csrf @method('PUT')
                                    <input type="text" name="title" value="{{ $task->title }}" required>
                                    <select name="priority">
                                        @foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected($task->priority === $priority)>{{ $priority }}</option>@endforeach
                                    </select>
                                    <button class="secondary-button" type="submit">Simpan</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route($task->status === 'completed' ? 'tasks.reopen' : 'tasks.complete', $task) }}">@csrf @method('PATCH')<button class="secondary-button" type="submit">{{ $task->status === 'completed' ? 'Buka lagi' : 'Selesai' }}</button></form>
                            <form method="POST" action="{{ route('tasks.archive', $task) }}">@csrf @method('PATCH')<button class="secondary-button" type="submit">Arsip</button></form>
                            <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Hapus tugas ini?')">@csrf @method('DELETE')<button class="danger-button" type="submit">Hapus</button></form>
                        </div>
                    </article>
                @endforeach
            </div>
            {{ $tasks->links() }}
        @endif
    </section>
@endsection
