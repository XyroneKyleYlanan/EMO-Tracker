import { useEffect, useState } from 'react'
import api from '../lib/api'
import { useAuth } from '../contexts/auth'
import { useToast } from '../contexts/toast'
import EventCalendarView from '../components/EventCalendarView'
import EventListView from '../components/EventListView'
import EventDetailDrawer from '../components/EventDetailDrawer'
import EventFormDialog from '../components/EventFormDialog'
import LoadError from '../components/LoadError'
import { CalendarIcon, ChecklistIcon } from '../components/icons'

export default function EventsPage() {
  const { user } = useAuth()
  const toast = useToast()
  // The admin owns event details; officers prepare events from the drawer.
  const canEdit = user.role === 'admin'
  const canDelete = user.role === 'admin'
  const canFilter = user.role === 'admin' || user.role === 'officer'

  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState(null)
  const [view, setView] = useState(() =>
    typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches
      ? 'calendar'
      : 'list'
  )
  const [selectedId, setSelectedId] = useState(null)
  const [formOpen, setFormOpen] = useState(false)
  const [editingEvent, setEditingEvent] = useState(null)
  // Most schedule entries are routine bookings, so the Events page starts on
  // the events the EMO prepares. The Schedule page lists everything.
  const [preparedOnly, setPreparedOnly] = useState(true)

  useEffect(() => {
    const mq = window.matchMedia('(min-width: 768px)')
    const handler = (e) => setView(e.matches ? 'calendar' : 'list')
    mq.addEventListener('change', handler)
    return () => mq.removeEventListener('change', handler)
  }, [])

  function loadEvents() {
    const params = !canFilter ? { mine: 1 } : preparedOnly ? { prepared: 1 } : {}
    api.get('/events', { params })
      .then((res) => { setEvents(res.data.events || []); setLoadError(null) })
      .catch(setLoadError)
      .finally(() => setLoading(false))
  }

  function fetchEvents() {
    setLoading(true)
    loadEvents()
  }

  function choosePrepared(value) {
    if (value === preparedOnly) return
    setLoading(true)
    setPreparedOnly(value)
  }

  useEffect(() => {
    loadEvents()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [preparedOnly])

  function handleSelect(event) {
    setSelectedId(event.id)
  }

  function handleNew() {
    setEditingEvent(null)
    setFormOpen(true)
  }

  function handleEdit(event) {
    setSelectedId(null)
    setEditingEvent(event)
    setFormOpen(true)
  }

  async function handleDelete(event) {
    if (!window.confirm(`Delete event "${event.name}"? This cannot be undone.`)) return
    try {
      await api.delete(`/events/${event.id}`)
      toast.success(`Event "${event.name}" deleted.`)
      setSelectedId(null)
      fetchEvents()
    } catch {
      toast.error('Failed to delete event.')
    }
  }

  function handleSaved(savedEvent) {
    const wasEditing = !!editingEvent
    setFormOpen(false)
    setEditingEvent(null)
    fetchEvents()
    toast.success(wasEditing ? 'Event updated.' : `Event "${savedEvent?.name || ''}" created.`)
  }

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex items-start justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Events</h1>
          <p className="text-sm text-gray-500 mt-1">
            {!canFilter
              ? 'Events where you have tasks. The Schedule shows every event.'
              : preparedOnly ? 'Events the EMO prepares, with their readiness.' : 'Plan and track EMO events.'}
          </p>
        </div>
        {canEdit && (
          <button
            onClick={handleNew}
            className="bg-neu-green hover:bg-neu-green-dark text-white text-sm font-medium px-4 py-2 rounded-lg transition flex-shrink-0"
          >
            + New event
          </button>
        )}
      </div>

      <div className="flex flex-wrap items-center gap-2 mb-6">
        <div className="hidden md:flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1 w-fit">
          <ViewToggle active={view === 'calendar'} onClick={() => setView('calendar')} Icon={CalendarIcon} label="Calendar" />
          <ViewToggle active={view === 'list'} onClick={() => setView('list')} Icon={ChecklistIcon} label="List" />
        </div>
        {canFilter && (
          <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1 w-fit">
            <FilterToggle active={preparedOnly} onClick={() => choosePrepared(true)} label="EMO-prepared" />
            <FilterToggle active={!preparedOnly} onClick={() => choosePrepared(false)} label="All events" />
          </div>
        )}
      </div>

      {loading ? (
        <div className="bg-white rounded-2xl border border-gray-200 p-10 animate-pulse">
          <div className="h-6 bg-gray-200 rounded w-1/4 mb-4"></div>
          <div className="h-64 bg-gray-100 rounded"></div>
        </div>
      ) : loadError ? (
        <LoadError error={loadError} onRetry={fetchEvents} />
      ) : view === 'calendar' ? (
        <EventCalendarView events={events} onSelect={handleSelect} />
      ) : (
        <EventListView events={events} onSelect={handleSelect} />
      )}

      {/* key gives each event a fresh drawer, so the previous event never flashes */}
      <EventDetailDrawer
        key={selectedId}
        eventId={selectedId}
        onClose={() => setSelectedId(null)}
        onEdit={handleEdit}
        onDelete={handleDelete}
        onChanged={fetchEvents}
        canEdit={canEdit}
        canDelete={canDelete}
      />

      {formOpen && (
        <EventFormDialog
          event={editingEvent}
          onClose={() => { setFormOpen(false); setEditingEvent(null); }}
          onSaved={handleSaved}
        />
      )}
    </div>
  )
}

function FilterToggle({ active, onClick, label }) {
  return (
    <button
      onClick={onClick}
      className={`px-3 py-1.5 rounded-md text-sm font-medium transition ${
        active ? 'bg-neu-green text-white' : 'text-gray-600 hover:bg-gray-100'
      }`}
    >
      {label}
    </button>
  )
}

function ViewToggle({ active, onClick, Icon, label }) {
  return (
    <button
      onClick={onClick}
      className={`flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition ${
        active ? 'bg-neu-green text-white' : 'text-gray-600 hover:bg-gray-100'
      }`}
    >
      <Icon width={16} height={16} />
      {label}
    </button>
  )
}
