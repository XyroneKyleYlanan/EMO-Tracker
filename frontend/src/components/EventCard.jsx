import ReadinessBadge from './ReadinessBadge'
import { CalendarIcon, ClockIcon, MapPinIcon, UsersIcon } from './icons'
import { formatDateShort, formatTime } from '../lib/format'

export default function EventCard({ event, onClick }) {
  const { total, done } = event.task_summary || { total: 0, done: 0 }
  const pct = total > 0 ? Math.round((done / total) * 100) : 0

  return (
    <button
      onClick={onClick}
      className="w-full text-left bg-white rounded-2xl border border-gray-200 p-5 shadow-sm hover:border-neu-green hover:shadow-md transition"
    >
      <div className="flex items-start justify-between gap-3 mb-3">
        <div className="min-w-0 flex-1">
          <div className="font-semibold text-gray-900 truncate">{event.name}</div>
          <div className="text-xs text-gray-500 mt-0.5">
            {event.description?.slice(0, 80) || 'No description.'}
          </div>
        </div>
        <ReadinessBadge readiness={event.readiness} />
      </div>

      <div className="grid grid-cols-2 gap-2 text-xs text-gray-600">
        <div className="flex items-center gap-1.5">
          <CalendarIcon width={14} height={14} />
          {formatDateShort(event.event_date)}
        </div>
        <div className="flex items-center gap-1.5">
          <ClockIcon width={14} height={14} />
          {formatTime(event.event_time)}
        </div>
        <div className="flex items-center gap-1.5 col-span-2 truncate">
          <MapPinIcon width={14} height={14} />
          <span className="truncate">{event.venue}</span>
        </div>
        <div className="flex items-center gap-1.5 col-span-2">
          <UsersIcon width={14} height={14} />
          <span>{event.staff?.length || 0} staff · {done}/{total} tasks done ({pct}%)</span>
        </div>
      </div>
    </button>
  )
}
