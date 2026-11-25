import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

export type CalendarEvent = {
    id: string;
    title: string;
    start?: string;
    end?: string;
    allDay?: boolean;
    backgroundColor?: string;
    borderColor?: string;
};

export function CalendarPanel({ events }: { events: CalendarEvent[] }) {
    return (
        <FullCalendar
            plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
            initialView="dayGridMonth"
            headerToolbar={{ left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' }}
            events={events}
            height="480px"
        />
    );
}
