import { AlertIcon } from './icons'
import { formatDateRange, formatDayMonth, formatTimeRange } from '../lib/format'

// Other bookings at the same venue and time. A warning, never a block:
// some overlaps are on purpose, like a rehearsal right before its event.
export default function ClashWarning({ clashes, intro, className = '' }) {
  if (!clashes?.length) return null

  return (
    <div role="status" className={`p-3 bg-orange-50 border border-orange-200 rounded-lg text-orange-950 ${className}`}>
      <div className="flex items-start gap-2">
        <AlertIcon width={16} height={16} className="mt-0.5 flex-shrink-0 text-orange-600" />
        <div className="min-w-0 text-sm">
          <div className="font-medium">{intro(clashes.length)}</div>
          <ul className="mt-1 space-y-0.5 text-xs">
            {clashes.map((c) => (
              <li key={c.id}>
                <span className="font-semibold">{c.name}</span>
                {' · '}
                {formatDateRange(c.event_date, c.end_date, formatDayMonth)}
                {' · '}
                {formatTimeRange(c.event_time, c.end_time) || 'all day'}
                {c.location ? ` · ${c.location}` : ''}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  )
}
