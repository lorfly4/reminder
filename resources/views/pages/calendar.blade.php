@extends('layouts.app')
@section('title', 'Kalender - Remindly')
@section('content')
    <section class="card panel calendar-page">
        <div class="panel-header"><h2>Kalender {{ now()->translatedFormat('F Y') }}</h2></div>
        @php
            $start = now()->startOfMonth();
            $daysInMonth = $start->daysInMonth;
            $leading = $start->dayOfWeek;
        @endphp
        <div class="calendar-grid" role="grid" aria-label="Kalender bulanan">
            @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $day)<div class="calendar-weekday">{{ $day }}</div>@endforeach
            @for($i=0;$i<$leading;$i++)<div class="calendar-cell is-empty"></div>@endfor
            @for($day=1;$day<=$daysInMonth;$day++)
                @php
                    $date = $start->copy()->day($day)->toDateString();
                    $dayTasks = $tasks->filter(fn($task) => $task->start_at?->toDateString() === $date || $task->deadline_at?->toDateString() === $date);
                @endphp
                <div class="calendar-cell">
                    <strong>{{ $day }}</strong>
                    @foreach($dayTasks as $task)<span class="calendar-event">{{ $task->title }}</span>@endforeach
                </div>
            @endfor
        </div>
    </section>
@endsection
