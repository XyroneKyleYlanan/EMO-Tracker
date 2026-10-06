import ReadinessBadge from './ReadinessBadge'
import { formatRescheduledFrom } from '../lib/format'

/**
 * The one rule for an event's badges, used on every page:
 * Cancelled or Completed; otherwise Ongoing while it runs, plus its readiness
 * if the EMO prepares it. The Schedule page leaves out "Scheduled" (every row
 * there is a booking) and "Completed" (the date already says so).
 */
export default function EventBadges({ event, size = 'md', showScheduled = true, showCompleted = true }) {
  const badges = []
  if (event.status === 'cancelled' || event.status === 'completed') {
    if (event.status === 'cancelled' || showCompleted) badges.push(event.status)
  } else {
    if (event.ongoing) badges.push('ongoing')
    if (event.needs_preparation) badges.push(event.readiness)
    else if (showScheduled && !event.ongoing) badges.push('scheduled')
  }
  if (badges.length === 0) return null

  return (
    <span className="inline-flex flex-wrap justify-end gap-1">
      {badges.map((badge) => <ReadinessBadge key={badge} readiness={badge} size={size} />)}
    </span>
  )
}

// "Rescheduled from Fri, Oct 3" under an event's name, or nothing.
export function RescheduledNote({ event, className = '' }) {
  const text = formatRescheduledFrom(event)
  return text ? <span className={`block text-[11px] italic ${className}`}>{text}</span> : null
}
