@extends('layouts.app')
@section('title', 'Dashboard - Remindly')
@section('content')
    <section class="dashboard-grid" aria-label="Ringkasan">
        <article class="card stat-card"><div class="label">Total tugas</div><div class="value">{{ $stats['total'] }}</div></article>
        <article class="card stat-card"><div class="label">Selesai</div><div class="value">{{ $stats['completed'] }}</div></article>
        <article class="card stat-card"><div class="label">Menunggu</div><div class="value">{{ $stats['pending'] }}</div></article>
        <article class="card stat-card"><div class="label">Terlewat</div><div class="value">{{ $stats['overdue'] }}</div></article>
    </section>
    <section class="content-grid">
        <article class="card panel">
            <div class="panel-header"><h2>Tugas hari ini</h2><a class="icon-button" href="{{ route('tasks.index') }}" aria-label="Tambah tugas">+</a></div>
            @if($todayTasks->isEmpty())
                <div class="empty-state"><div><strong class="empty-icon">☑</strong><p>Belum ada tugas hari ini.</p></div></div>
            @else
                <div class="task-list">
                    @foreach($todayTasks as $task)
                        <div class="task-row"><span>☑</span><span>{{ $task->title }}</span></div>
                    @endforeach
                </div>
            @endif
        </article>
        <article class="card panel">
            <div class="panel-header"><h2>Aktivitas terbaru</h2></div>
            @if($recentActivity->isEmpty())
                <div class="empty-state"><p>Belum ada aktivitas.</p></div>
            @else
                <div class="task-list">
                    @foreach($recentActivity as $activity)
                        <div class="task-row"><span>↗</span><div><strong>{{ $activity->description }}</strong><small>{{ $activity->created_at->diffForHumans() }}</small></div></div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
@endsection
