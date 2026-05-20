import EventCard from './EventCard'

export default function EventListView({ events, onSelect }) {
  if (events.length === 0) {
    return (
      <div className="bg-white rounded-xl border border-gray-200 border-dashed p-10 text-center text-sm text-gray-500">
        No events yet.
      </div>
    )
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {events.map((event) => (
        <EventCard key={event.id} event={event} onClick={() => onSelect(event)} />
      ))}
    </div>
  )
}
