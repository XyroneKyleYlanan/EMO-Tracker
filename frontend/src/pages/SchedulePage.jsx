import { useEffect, useRef, useState } from 'react'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import { useToast } from '../contexts/ToastContext'
import EventDetailDrawer from '../components/EventDetailDrawer'
import EventFormDialog from '../components/EventFormDialog'
import ReadinessBadge from '../components/ReadinessBadge'
import { SearchIcon } from '../components/icons'
import { formatDateRange, formatDayMonth, formatMonthYear, formatTimeRange } from '../lib/format'

const OTHER_COLOR = '#E5E7EB'

// The EMO's schedule sheet: every NEU event, one tab per year, colored by
// building. Everyone can view it; only the admin adds and edits events.
export default function SchedulePage() {
  const { user } = useAuth()
  const toast = useToast()
  const isAdmin = user.role === 'admin'

  const [year, setYear] = useState(new Date().getFullYear())
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [selectedId, setSelectedId] = useState(null)
  const [formOpen, setFormOpen] = useState(false)
  const [editingEvent, setEditingEvent] = useState(null)

  // Only the latest request may update the page, so switching years quickly
  // can't leave one year's events showing under another year's tab.
  const latestRequest = useRef(0)
  function fetchSchedule() {
    const request = ++latestRequest.current
    setLoading(true)
    api.get('/schedule', { params: { year } })
      .then((res) => { if (request === latestRequest.current) setData(res.data) })
      .finally(() => { if (request === latestRequest.current) setLoading(false) })
  }

  useEffect(() => {
    fetchSchedule()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [year])

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
      fetchSchedule()
    } catch {
      toast.error('Failed to delete event.')
    }
  }

  function handleSaved(saved) {
    const wasEditing = !!editingEvent
    setFormOpen(false)
    setEditingEvent(null)
    fetchSchedule()
    toast.success(wasEditing ? 'Event updated.' : `Event "${saved?.name || ''}" added to the schedule.`)
  }

  // The current year and upcoming years get tabs, since upcoming events are the
  // EMO's focus. Past years move into a dropdown; nothing is ever deleted.
  const allYears = data?.years || [year]
  const currentYear = data?.current_year ?? new Date().getFullYear()
  const recentYears = allYears.filter((y) => y >= currentYear)
  const olderYears = allYears.filter((y) => y < currentYear).reverse()

  const query = search.trim().toLowerCase()
  const rows = (data?.events || []).filter((e) =>
    !query || [e.name, e.department, e.location, e.control_number, e.remarks]
      .some((v) => v && v.toLowerCase().includes(query))
  )
  const months = []
  for (const row of rows) {
    const label = formatMonthYear(row.event_date)
    if (months.at(-1)?.label !== label) months.push({ label, rows: [] })
    months.at(-1).rows.push(row)
  }

  return (
    <div className="max-w-7xl mx-auto">
      <div className="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Schedule</h1>
          <p className="text-sm text-gray-500 mt-1">
            Every NEU event and venue booking{!isAdmin && ' · View only'}
          </p>
        </div>
        {isAdmin && (
          <button
            onClick={() => { setEditingEvent(null); setFormOpen(true) }}
            className="bg-neu-green hover:bg-neu-green-dark text-white text-sm font-medium px-4 py-2 rounded-lg transition flex-shrink-0"
          >
            + Add event
          </button>
        )}
      </div>

      {/* Years in time order (past, this year, upcoming) on the left; search on the right. */}
      <div className="flex flex-wrap items-center gap-3 mb-4">
        <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1">
          {olderYears.length > 0 && (
            <select
              value={olderYears.includes(year) ? year : ''}
              onChange={(e) => e.target.value && setYear(Number(e.target.value))}
              aria-label="Past years"
              className={`pl-3 pr-2 py-1.5 rounded-md text-sm font-medium border-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-neu-green ${
                olderYears.includes(year) ? 'bg-neu-green text-white' : 'bg-transparent text-gray-600 hover:bg-gray-100'
              }`}
            >
              <option value="">Past years</option>
              {olderYears.map((y) => <option key={y} value={y}>{y}</option>)}
            </select>
          )}
          {recentYears.map((y) => (
            <button
              key={y}
              onClick={() => setYear(y)}
              className={`px-3 py-1.5 rounded-md text-sm font-medium transition ${
                y === year ? 'bg-neu-green text-white' : 'text-gray-600 hover:bg-gray-100'
              }`}
            >
              {y}
            </button>
          ))}
        </div>

        <div className="relative w-full sm:w-72 sm:ml-auto">
          <SearchIcon width={16} height={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
          <input
            type="search"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search schedule"
            aria-label="Search schedule"
            className="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
          />
        </div>
      </div>

      {loading && !data ? (
        <div className="bg-white rounded-2xl border border-gray-200 p-10 animate-pulse">
          <div className="h-6 bg-gray-200 rounded w-1/4 mb-4"></div>
          <div className="h-64 bg-gray-100 rounded"></div>
        </div>
      ) : rows.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 border-dashed p-10 text-center text-sm text-gray-500">
          {query ? 'No events match your search.' : `No events scheduled for ${year}.`}
        </div>
      ) : (
        <div className={loading ? 'opacity-60' : ''}>
          {/* Phones get cards so the event name is visible without sideways scrolling. */}
          <div className="md:hidden space-y-5">
            {months.map((month) => (
              <MonthCards key={month.label} month={month} onOpen={setSelectedId} />
            ))}
          </div>

          <div className="hidden md:block bg-white rounded-xl border border-gray-300 shadow-sm overflow-x-auto">
            <table className="w-full min-w-[1000px] table-fixed text-[13px] border-collapse">
              <colgroup>
                <col className="w-32" />
                <col className="w-36" />
                <col />
                <col className="w-32" />
                <col className="w-36" />
                <col className="w-28" />
                <col className="w-36" />
              </colgroup>
              <thead className="text-gray-700">
                <tr>
                  <th colSpan={7} className="border-b border-gray-300 px-3 py-2 text-center text-lg font-semibold text-gray-900">
                    {data?.year ?? year}
                  </th>
                </tr>
                <tr className="bg-gray-50 text-[11px] uppercase tracking-wide">
                  <Th>Date</Th>
                  <Th>Time</Th>
                  <Th>Event</Th>
                  <Th>Department</Th>
                  <Th>Venue</Th>
                  <Th>Control #</Th>
                  <Th>Remarks</Th>
                </tr>
              </thead>
              <tbody>
                {months.map((month) => (
                  <MonthRows key={month.label} month={month} onOpen={setSelectedId} />
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      <EventDetailDrawer
        key={selectedId}
        eventId={selectedId}
        onClose={() => setSelectedId(null)}
        onEdit={handleEdit}
        onDelete={handleDelete}
        onChanged={fetchSchedule}
        canEdit={isAdmin}
        canDelete={isAdmin}
      />

      {formOpen && (
        <EventFormDialog
          event={editingEvent}
          onClose={() => { setFormOpen(false); setEditingEvent(null) }}
          onSaved={handleSaved}
        />
      )}
    </div>
  )
}

function MonthRows({ month, onOpen }) {
  return (
    <>
      <tr>
        <td colSpan={7} className="border border-gray-300 px-2.5 py-1.5 bg-gray-100 text-[11px] font-semibold uppercase tracking-wide text-gray-600">
          {month.label} · {month.rows.length} event{month.rows.length === 1 ? '' : 's'}
        </td>
      </tr>
      {month.rows.map((row) => (
        <tr
          key={row.id}
          onClick={row.can_open ? () => onOpen(row.id) : undefined}
          title={row.can_open ? 'Open event details' : undefined}
          className={`text-gray-900 ${row.can_open ? 'cursor-pointer hover:brightness-95' : ''}`}
          style={{ backgroundColor: row.building?.color || OTHER_COLOR }}
        >
          <Td className="font-semibold">{formatDateRange(row.event_date, row.end_date, formatDayMonth)}</Td>
          <Td className="whitespace-nowrap">{formatTimeRange(row.event_time, row.end_time) || <Muted>TBA</Muted>}</Td>
          <Td><EventName row={row} /></Td>
          <Td>{row.department}</Td>
          <Td>{row.location || <Muted>TBA</Muted>}</Td>
          <Td className="break-words">{row.control_number}</Td>
          <Td>{row.remarks}</Td>
        </tr>
      ))}
    </>
  )
}

// Name plus one status badge, the same badges used everywhere else in the app.
function EventName({ row }) {
  const badge = row.status === 'cancelled' ? 'cancelled' : row.needs_preparation && row.status === 'upcoming' ? row.readiness : null
  return (
    <span className="flex items-start justify-between gap-2">
      <span className={row.status === 'cancelled' ? 'line-through decoration-gray-700' : ''}>{row.name}</span>
      {badge && <span className="flex-shrink-0 pt-px"><ReadinessBadge readiness={badge} size="sm" /></span>}
    </span>
  )
}

function Muted({ children }) {
  return <span className="text-gray-600 italic">{children}</span>
}

function MonthCards({ month, onOpen }) {
  return (
    <section>
      <h2 className="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
        {month.label} · {month.rows.length} event{month.rows.length === 1 ? '' : 's'}
      </h2>
      <div className="space-y-2">
        {month.rows.map((row) => {
          const Tag = row.can_open ? 'button' : 'div'
          return (
            <Tag
              key={row.id}
              type={row.can_open ? 'button' : undefined}
              onClick={row.can_open ? () => onOpen(row.id) : undefined}
              className="block w-full text-left rounded-lg border border-black/10 px-3 py-2.5 text-sm text-gray-900"
              style={{ backgroundColor: row.building?.color || OTHER_COLOR }}
            >
              <div className="font-medium"><EventName row={row} /></div>
              <div className="text-xs mt-1 font-semibold">
                {formatDateRange(row.event_date, row.end_date, formatDayMonth)}
                {' · '}
                {formatTimeRange(row.event_time, row.end_time) || 'Time TBA'}
              </div>
              <div className="text-xs mt-0.5">
                {row.location || 'Venue TBA'}{row.department ? ` · ${row.department}` : ''}
              </div>
              {row.remarks && <div className="text-xs mt-1 italic">{row.remarks}</div>}
            </Tag>
          )
        })}
      </div>
    </section>
  )
}

function Th({ children }) {
  return <th className="border border-gray-300 px-2.5 py-2 text-left font-semibold whitespace-nowrap">{children}</th>
}

function Td({ children, className = '' }) {
  return <td className={`border border-gray-300 px-2.5 py-1.5 align-top leading-snug ${className}`}>{children}</td>
}
