import { useState } from 'react'
import EventCard from './EventCard'

// Today on this device, e.g. "2026-10-07".
function todayIso() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// What's coming up comes first, soonest at the top. Past events (most recent
// first) stay one click away, so a long history never buries what's next.
export default function EventListView({ events, onSelect }) {
  const [showPast, setShowPast] = useState(false)

  if (events.length === 0) {
    return <Empty>No events yet.</Empty>
  }

  const today = todayIso()
  const isPast = (e) => e.status === 'completed' || (e.status === 'cancelled' && (e.end_date || e.event_date) < today)
  const upcoming = events.filter((e) => !isPast(e))
  const past = events.filter(isPast).reverse()

  return (
    <div className="space-y-6">
      {upcoming.length > 0
        ? <Cards events={upcoming} onSelect={onSelect} />
        : <Empty>No upcoming events.</Empty>}

      {past.length > 0 && (
        <div>
          <button
            onClick={() => setShowPast((v) => !v)}
            aria-expanded={showPast}
            className="text-sm font-medium text-neu-green hover:underline"
          >
            {showPast ? 'Hide past events' : `Show past events (${past.length})`}
          </button>
          {showPast && <Cards events={past} onSelect={onSelect} className="mt-4" />}
        </div>
      )}
    </div>
  )
}

function Cards({ events, onSelect, className = '' }) {
  return (
    <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 ${className}`}>
      {events.map((event) => (
        <EventCard key={event.id} event={event} onClick={() => onSelect(event)} />
      ))}
    </div>
  )
}

function Empty({ children }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200 border-dashed p-10 text-center text-sm text-gray-500">
      {children}
    </div>
  )
}
