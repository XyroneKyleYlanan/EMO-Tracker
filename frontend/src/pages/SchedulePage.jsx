import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { downloadFile } from '../lib/download'
import { useAuth } from '../contexts/auth'
import { useToast } from '../contexts/toast'
import EventDetailDrawer from '../components/EventDetailDrawer'
import EventFormDialog from '../components/EventFormDialog'
import EventBadges, { RescheduledNote } from '../components/EventBadges'
import LoadError from '../components/LoadError'
import ReadinessBadge from '../components/ReadinessBadge'
import { ChevronDownIcon, SearchIcon } from '../components/icons'
import { formatDateRange, formatDayMonth, formatMonthYear, formatTimeRange } from '../lib/format'

const OTHER_COLOR = '#E5E7EB'

// The EMO's schedule sheet: every NEU event, one tab per year, colored by
// building. Everyone can view it; only the admin adds and edits events.
export default function SchedulePage() {
  const { user } = useAuth()
  const toast = useToast()
  const isAdmin = user.role === 'admin'
  const canExport = isAdmin || user.role === 'officer'

  const [year, setYear] = useState(new Date().getFullYear())
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState(null)
  const [search, setSearch] = useState('')
  const [selectedId, setSelectedId] = useState(null)
  const [formOpen, setFormOpen] = useState(false)
  const [editingEvent, setEditingEvent] = useState(null)

  // Only the latest request may update the page, so switching years quickly
  // can't leave one year's events showing under another year's tab.
  const latestRequest = useRef(0)
  function loadSchedule() {
    const request = ++latestRequest.current
    api.get('/schedule', { params: { year } })
      .then((res) => { if (request === latestRequest.current) { setData(res.data); setLoadError(null) } })
      .catch((err) => { if (request === latestRequest.current) setLoadError(err) })
      .finally(() => { if (request === latestRequest.current) setLoading(false) })
  }

  function fetchSchedule() {
    setLoading(true)
    loadSchedule()
  }

  function chooseYear(value) {
    if (value === year) return
    setLoading(true)
    setYear(value)
  }

  useEffect(() => {
    loadSchedule()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [year])

  async function handleExport() {
    try {
      await downloadFile(`/schedule/export?year=${year}`, `emo-schedule-${year}.xlsx`)
    } catch {
      toast.error('Failed to export the schedule.')
    }
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
    !query || [e.name, e.event_type, e.department, e.location, e.control_number, e.remarks, e.clashes?.length ? 'overlap' : null]
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
        {canExport && (
          <div className="flex flex-wrap items-center gap-2">
            <button
              onClick={handleExport}
              className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition"
            >
              Export to Excel
            </button>
            {isAdmin && (
              <Link
                to="/admin/venues"
                className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition"
              >
                Manage venues
              </Link>
            )}
            {isAdmin && (
              <button
                onClick={() => { setEditingEvent(null); setFormOpen(true) }}
                className="bg-neu-green hover:bg-neu-green-dark text-white text-sm font-medium px-4 py-2 rounded-lg transition"
              >
                + Add event
              </button>
            )}
          </div>
        )}
      </div>

      {/* Years in time order (past, this year, upcoming) on the left; search on the right. */}
      <div className="flex flex-wrap items-center gap-3 mb-4">
        <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1">
          {olderYears.length > 0 && (
            // Our own arrow instead of the browser's, so the spacing is even in every browser.
            <div className="relative">
              <select
                value={olderYears.includes(year) ? year : ''}
                onChange={(e) => e.target.value && chooseYear(Number(e.target.value))}
                aria-label="Past years"
                className={`appearance-none pl-3 pr-8 py-1.5 rounded-md text-sm font-medium border-0 cursor-pointer focus:outline-none focus:ring-2 focus:ring-neu-green ${
                  olderYears.includes(year) ? 'bg-neu-green text-white' : 'bg-transparent text-gray-600 hover:bg-gray-100'
                }`}
              >
                <option value="">Past years</option>
                {olderYears.map((y) => <option key={y} value={y}>{y}</option>)}
              </select>
              <ChevronDownIcon
                width={14}
                height={14}
                className={`absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none ${olderYears.includes(year) ? 'text-white' : 'text-gray-500'}`}
              />
            </div>
          )}
          {recentYears.map((y) => (
            <button
              key={y}
              onClick={() => chooseYear(y)}
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

      {loadError ? (
        <LoadError error={loadError} onRetry={() => { setLoadError(null); fetchSchedule() }} />
      ) : loading && !data ? (
        <div className="bg-white rounded-xl border border-gray-200 p-10 animate-pulse">
          <div className="h-6 bg-gray-200 rounded-md w-1/4 mb-4"></div>
          <div className="h-64 bg-gray-100 rounded-md"></div>
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

          <div className="hidden md:block bg-white rounded-xl border border-gray-300 overflow-x-auto">
            <table className="w-full min-w-[1080px] table-fixed text-[13px] border-collapse">
              <colgroup>
                <col className="w-32" />
                <col className="w-36" />
                <col />
                <col className="w-24" />
                <col className="w-32" />
                <col className="w-36" />
                <col className="w-28" />
                <col className="w-36" />
              </colgroup>
              <thead className="text-gray-700">
                <tr>
                  <th colSpan={8} className="border-b border-gray-300 px-3 py-2 text-center text-lg font-semibold text-gray-900">
                    {data?.year ?? year}
                  </th>
                </tr>
                <tr className="bg-gray-50 text-[11px] uppercase tracking-wide">
                  <Th>Date</Th>
                  <Th>Time</Th>
                  <Th>Event</Th>
                  <Th>Type</Th>
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
        <td colSpan={8} className="border border-gray-300 px-2.5 py-1.5 bg-gray-100 text-xs font-semibold text-gray-700">
          {month.label} · {month.rows.length} event{month.rows.length === 1 ? '' : 's'}
        </td>
      </tr>
      {month.rows.map((row) => (
        <tr
          key={row.id}
          onClick={() => onOpen(row.id)}
          title="Open event details"
          className="text-gray-900 cursor-pointer hover:brightness-95"
          style={{ backgroundColor: row.building?.color || OTHER_COLOR }}
        >
          <Td className="font-semibold">{formatDateRange(row.event_date, row.end_date, formatDayMonth)}</Td>
          <Td className="whitespace-nowrap">{formatTimeRange(row.event_time, row.end_time) || <Muted>TBA</Muted>}</Td>
          <Td><EventName row={row} /></Td>
          <Td><EventType type={row.event_type} /></Td>
          <Td>{row.department}</Td>
          <Td>{row.location || <Muted>TBA</Muted>}</Td>
          <Td className="break-words">{row.control_number}</Td>
          <Td>{row.remarks}</Td>
        </tr>
      ))}
    </>
  )
}

// Name plus its status badges, the same badges used everywhere else in the app.
function EventName({ row }) {
  return (
    <span className="flex items-start justify-between gap-2">
      <span className="min-w-0">
        <span className={row.status === 'cancelled' ? 'line-through decoration-gray-700' : ''}>{row.name}</span>
        <RescheduledNote event={row} className="text-gray-800" />
      </span>
      <span className="flex-shrink-0 pt-px inline-flex flex-wrap justify-end gap-1">
        <EventBadges event={row} size="sm" showScheduled={false} showCompleted={false} />
        {/* Another upcoming booking at the same venue and time; the event panel has the details. */}
        {row.clashes?.length > 0 && (
          <span title={`Overlaps with ${row.clashes.join(', ')}`} className="inline-flex">
            <ReadinessBadge readiness="overlap" size="sm" />
          </span>
        )}
      </span>
    </span>
  )
}

// Every row says its type; outside bookings stand out so they can be spotted at a glance.
function EventType({ type }) {
  return type === 'external' ? <ReadinessBadge readiness="external" size="sm" /> : <span>Internal</span>
}

// Small notes on a building-colored row stay gray-800 so they're readable on every color.
function Muted({ children }) {
  return <span className="text-gray-800 italic">{children}</span>
}

function MonthCards({ month, onOpen }) {
  return (
    <section>
      <h2 className="text-sm font-semibold text-gray-900 mb-2">
        {month.label} · {month.rows.length} event{month.rows.length === 1 ? '' : 's'}
      </h2>
      <div className="space-y-2">
        {month.rows.map((row) => {
          return (
            <button
              key={row.id}
              type="button"
              onClick={() => onOpen(row.id)}
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
              <div className="text-xs mt-1"><EventType type={row.event_type} /></div>
              {row.remarks && <div className="text-xs mt-1 italic">{row.remarks}</div>}
            </button>
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
