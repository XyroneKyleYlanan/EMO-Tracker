import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import { READINESS_HEX } from './ReadinessBadge'
import { formatTimeRange } from '../lib/format'

const READINESS_LABEL = {
  green: 'On Track',
  yellow: 'At Risk',
  red: 'Critical',
  completed: 'Completed',
  scheduled: 'Scheduled',
  cancelled: 'Cancelled',
}

// FullCalendar treats an all-day event's end as exclusive, so a multi-day
// event ending Oct 28 needs end = Oct 29 to cover the 28th.
function dayAfter(isoDate) {
  const [y, m, d] = isoDate.split('-').map(Number)
  const next = new Date(y, m - 1, d + 1)
  return `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-${String(next.getDate()).padStart(2, '0')}`
}

export default function EventCalendarView({ events, onSelect }) {
  const calendarEvents = events.map((event) => ({
    id: String(event.id),
    title: event.name,
    start: event.event_date,
    end: event.end_date && event.end_date !== event.event_date ? dayAfter(event.end_date) : undefined,
    allDay: true,
    backgroundColor: READINESS_HEX[event.readiness] || READINESS_HEX.yellow,
    borderColor: READINESS_HEX[event.readiness] || READINESS_HEX.yellow,
    extendedProps: { event },
  }))

  return (
    <div className="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm fc-emo">
      <FullCalendar
        plugins={[dayGridPlugin, interactionPlugin]}
        initialView="dayGridMonth"
        events={calendarEvents}
        eventClick={(info) => onSelect(info.event.extendedProps.event)}
        height="auto"
        headerToolbar={{
          left: 'prev,next today',
          center: 'title',
          right: '',
        }}
        dayMaxEvents={3}
        eventDisplay="block"
        contentHeight={620}
        eventContent={(arg) => {
          const ev = arg.event.extendedProps.event
          const tooltip = `${ev.name}\n${formatTimeRange(ev.event_time, ev.end_time) || 'Time TBA'} · ${ev.location || 'Venue TBA'}\n${[ev.ongoing && 'Ongoing', READINESS_LABEL[ev.readiness]].filter(Boolean).join(' · ')}`
          return (
            <div
              title={tooltip}
              className="w-full px-1.5 py-0.5 text-[11px] font-medium text-white leading-tight truncate cursor-pointer"
            >
              {ev.name}
            </div>
          )
        }}
      />
    </div>
  )
}
