import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuth } from '../contexts/auth'
import { useToast } from '../contexts/toast'
import DateTimeDisplay from '../components/DateTimeDisplay'
import { ReadinessReason } from '../components/ReadinessBadge'
import EventBadges from '../components/EventBadges'
import EventDetailDrawer from '../components/EventDetailDrawer'
import EventFormDialog from '../components/EventFormDialog'
import LoadError from '../components/LoadError'
import TaskRow from '../components/TaskRow'
import { formatDateRange, formatDateTime, formatDayMonth, formatRescheduledFrom, formatTimeRange } from '../lib/format'

const WEEK_ROWS = 10
const NO_BUILDING_COLOR = '#E5E7EB'

// Home for admins and officers: what needs attention, and what's on this week.
export default function ManagerDashboard() {
  const { user } = useAuth()
  const toast = useToast()
  const isAdmin = user.role === 'admin'
  const [data, setData] = useState(null)
  const [loadError, setLoadError] = useState(null)
  const [selectedId, setSelectedId] = useState(null)
  const [editingEvent, setEditingEvent] = useState(null)

  function fetchHome() {
    api.get(`/dashboard/${isAdmin ? 'admin' : 'officer'}`)
      .then((res) => { setData(res.data); setLoadError(null) })
      .catch(setLoadError)
  }

  function retry() {
    setLoadError(null)
    fetchHome()
  }

  useEffect(() => {
    fetchHome()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function handleEdit(event) {
    setSelectedId(null)
    setEditingEvent(event)
  }

  async function handleDelete(event) {
    if (!window.confirm(`Delete event "${event.name}"? This cannot be undone.`)) return
    try {
      await api.delete(`/events/${event.id}`)
      toast.success(`Event "${event.name}" deleted.`)
      setSelectedId(null)
      fetchHome()
    } catch {
      toast.error('Failed to delete event.')
    }
  }

  return (
    <div className="max-w-6xl mx-auto">
      <div className="mb-2 flex flex-col md:flex-row md:items-start md:justify-between gap-3 md:gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Hello, {user.name}</h1>
          {data && !loadError
            ? <HomeSummary stats={data.stats} />
            : <p className="text-sm text-gray-500 mt-1">Here&apos;s what needs your attention.</p>}
        </div>
        <DateTimeDisplay />
      </div>

      {loadError ? (
        <LoadError error={loadError} onRetry={retry} className="mt-8" />
      ) : !data ? (
        <div className="mt-8 grid grid-cols-1 lg:grid-cols-5 gap-6">
          <div className="lg:col-span-3 bg-white rounded-xl border border-gray-200 h-64 animate-pulse" />
          <div className="lg:col-span-2 bg-white rounded-xl border border-gray-200 h-64 animate-pulse" />
        </div>
      ) : (
        <>
          {/* Needs attention is the reason to open Home, so it gets the most room. */}
          <div className="mt-8 grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
            <Panel className="lg:col-span-3" title="Needs attention" link={{ to: `/${user.role}/events`, label: 'All events' }}>
              {data.needsAttention.length === 0 ? (
                <Empty>
                  {data.stats.preparedUpcoming === 0
                    ? 'No events are marked "EMO prepares" yet. Open an event and add tasks to start tracking its readiness.'
                    : 'Nothing needs attention. Every EMO-prepared event is on track.'}
                </Empty>
              ) : (
                <ul className="divide-y divide-gray-100">
                  {data.needsAttention.map((e) => (
                    <li key={e.id}>
                      <button onClick={() => setSelectedId(e.id)} className="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-start justify-between gap-3">
                        <div className="min-w-0">
                          <div className="text-sm font-medium text-gray-900 truncate">{e.name}</div>
                          <div className="text-xs text-gray-500 mt-0.5 truncate">
                            {formatDateRange(e.event_date, e.end_date, formatDayMonth)}
                            {e.event_time ? ` · ${formatTimeRange(e.event_time, e.end_time)}` : ''}
                            {e.location ? ` · ${e.location}` : ''}
                          </div>
                          <ReadinessReason readiness={e.readiness} reason={e.readiness_reason} className="text-xs mt-0.5 truncate" />
                        </div>
                        <div className="flex flex-col items-end gap-1 flex-shrink-0">
                          <EventBadges event={e} size="sm" showScheduled={false} />
                          <span className="text-[11px] text-gray-500">
                            {e.task_summary.total ? `${e.task_summary.done}/${e.task_summary.total} tasks done` : 'No tasks yet'}
                          </span>
                        </div>
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </Panel>

            <Panel className="lg:col-span-2" title="Next 7 days" link={{ to: `/${user.role}/schedule`, label: 'Open schedule' }}>
              {data.thisWeek.length === 0 ? (
                <Empty>Nothing on the schedule for the next 7 days.</Empty>
              ) : (
                <WeekList events={data.thisWeek} today={data.today} onOpen={setSelectedId} scheduleLink={`/${user.role}/schedule`} />
              )}
            </Panel>
          </div>

          {/* In a small office the head and officers take tasks too. */}
          {data.myOpenTasks.length > 0 && (
            <section className="mt-8">
              <div className="flex items-center justify-between mb-3">
                <h2 className="text-sm font-semibold text-gray-900">My open tasks</h2>
                <Link to={`/${user.role}/tasks`} className="text-xs font-medium text-neu-green hover:underline">View all</Link>
              </div>
              <div className="space-y-2">
                {data.myOpenTasks.map((task) => (
                  <TaskRow key={task.id} task={task} currentUser={user} showEvent onChanged={fetchHome} />
                ))}
              </div>
            </section>
          )}

          {data.backup && <BackupNote backup={data.backup} />}
        </>
      )}

      <EventDetailDrawer
        key={selectedId}
        eventId={selectedId}
        onClose={() => setSelectedId(null)}
        onEdit={handleEdit}
        onDelete={handleDelete}
        onChanged={fetchHome}
        canEdit={isAdmin}
        canDelete={isAdmin}
      />

      {editingEvent && (
        <EventFormDialog
          event={editingEvent}
          onClose={() => setEditingEvent(null)}
          onSaved={() => { setEditingEvent(null); toast.success('Event updated.'); fetchHome() }}
        />
      )}
    </div>
  )
}

// The day in one plain paragraph, in place of a row of number cards.
function HomeSummary({ stats }) {
  const n = (count) => <strong className="font-semibold text-gray-900">{count}</strong>
  const plural = (count, one, many) => (count === 1 ? one : many)
  const {
    preparedUpcoming: prepared, needAttention, openTasks, overdueTasks, tasksDone, tasksTotal, thisWeek,
  } = stats

  return (
    <p className="mt-2 max-w-3xl text-[15px] leading-relaxed text-gray-600">
      {prepared === 0 ? (
        <>No upcoming events are marked &ldquo;EMO prepares&rdquo; yet. </>
      ) : prepared === 1 ? (
        <>The EMO-prepared event {needAttention ? 'needs attention' : 'is on track'}. </>
      ) : needAttention === 0 ? (
        <>All {n(prepared)} EMO-prepared events are on track. </>
      ) : (
        <>Of the {n(prepared)} EMO-prepared events, {n(needAttention)} {plural(needAttention, 'needs', 'need')} attention. </>
      )}
      {tasksTotal > 0 && (openTasks === 0 ? (
        <>All {n(tasksTotal)} of their tasks are done. </>
      ) : (
        <>
          Across them, {n(openTasks)} {plural(openTasks, 'task is', 'tasks are')} still open
          {overdueTasks > 0 && <> (<strong className="font-semibold text-rose-700">{overdueTasks} overdue</strong>)</>}
          {' '}and {n(tasksDone)} of {n(tasksTotal)} are done.{' '}
        </>
      ))}
      {thisWeek === 0
        ? <>Nothing is on the schedule in the next 7 days.</>
        : <>The schedule has {n(thisWeek)} {plural(thisWeek, 'event', 'events')} in the next 7 days.</>}
    </p>
  )
}

// Grouped by day; an event that started earlier and is still running shows under Today.
// "Today" comes from the server (Manila time), not the viewer's device.
function WeekList({ events, today, onOpen, scheduleLink }) {
  const tomorrow = dayAfter(today)
  const shown = events.slice(0, WEEK_ROWS)
  const days = []
  for (const e of shown) {
    const day = e.event_date < today ? today : e.event_date
    if (days.at(-1)?.day !== day) days.push({ day, events: [] })
    days.at(-1).events.push(e)
  }

  return (
    <div>
      {days.map(({ day, events: list }) => (
        <div key={day}>
          <div className="px-4 py-1.5 bg-gray-50 text-xs font-semibold text-gray-600 border-y border-gray-100">
            {day === today ? 'Today' : day === tomorrow ? 'Tomorrow' : formatDayMonth(day)}
          </div>
          <ul className="divide-y divide-gray-100">
            {list.map((e) => (
              <li key={e.id}>
                <button onClick={() => onOpen(e.id)} className="w-full text-left px-4 py-2.5 hover:bg-gray-50 flex items-start gap-3">
                  <span className="mt-1.5 h-2.5 w-2.5 rounded-sm flex-shrink-0 border border-black/10" style={{ backgroundColor: e.building?.color || NO_BUILDING_COLOR }} />
                  <div className="min-w-0 flex-1">
                    <div className={`text-sm truncate ${e.status === 'cancelled' ? 'text-gray-500 line-through' : 'text-gray-900'}`}>{e.name}</div>
                    <div className="text-xs text-gray-500 truncate">
                      {formatTimeRange(e.event_time, e.end_time) || 'Time TBA'}
                      {e.location ? ` · ${e.location}` : ''}
                      {e.end_date && e.end_date !== e.event_date ? ` · until ${formatDayMonth(e.end_date)}` : ''}
                      {e.original_date ? ` · ${formatRescheduledFrom(e)}` : ''}
                    </div>
                  </div>
                  <EventBadges event={e} size="sm" showScheduled={false} />
                </button>
              </li>
            ))}
          </ul>
        </div>
      ))}
      {events.length > WEEK_ROWS && (
        <Link to={scheduleLink} className="block px-4 py-2.5 text-xs font-medium text-neu-green hover:underline border-t border-gray-100">
          +{events.length - WEEK_ROWS} more in the next 7 days on the Schedule
        </Link>
      )}
    </div>
  )
}

// Admin only: whether the automatic daily backups are working.
function BackupNote({ backup }) {
  if (backup.failed) {
    return (
      <div role="alert" className="mt-8 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-900">
        <span className="font-semibold">The automatic backup isn&apos;t working.</span>{' '}
        The last try ({formatDateTime(backup.failed.at)}) failed: {backup.failed.message.replace(/\.$/, '')}. It tries again every hour while the app is in use.
      </div>
    )
  }

  return (
    <p className="mt-8 text-xs text-gray-500">
      {!backup.daily
        ? 'Automatic backups are turned off.'
        : backup.last_at
          ? `Last backup: ${formatDateTime(backup.last_at)}`
          : 'No backup yet. One is made automatically each day the app is used.'}
    </p>
  )
}

function dayAfter(isoDate) {
  const [y, m, d] = isoDate.split('-').map(Number)
  const next = new Date(y, m - 1, d + 1)
  return `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-${String(next.getDate()).padStart(2, '0')}`
}

function Panel({ title, link, className = '', children }) {
  return (
    <section className={`bg-white rounded-xl border border-gray-200 overflow-hidden ${className}`}>
      <header className="flex items-center justify-between px-4 py-3 border-b border-gray-200">
        <h2 className="text-sm font-semibold text-gray-900">{title}</h2>
        <Link to={link.to} className="text-xs font-medium text-neu-green hover:underline">{link.label}</Link>
      </header>
      {children}
    </section>
  )
}

function Empty({ children }) {
  return <div className="px-4 py-8 text-center text-sm text-gray-500">{children}</div>
}
