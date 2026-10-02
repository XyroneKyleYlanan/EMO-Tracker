import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import StatCard from '../components/StatCard'
import DateTimeDisplay from '../components/DateTimeDisplay'
import { CalendarIcon, ClockIcon, MapPinIcon } from '../components/icons'
import { formatDateCompact, formatDateRange, formatTimeRange } from '../lib/format'

const STATUS_LABELS = { pending: 'Pending', in_progress: 'In Progress', done: 'Done' }
const STATUS_COLORS = {
  pending: 'bg-slate-100 text-slate-700',
  in_progress: 'bg-amber-100 text-amber-800',
  done: 'bg-emerald-100 text-emerald-800',
}
const PRIORITY_COLORS = {
  low: 'text-slate-500',
  medium: 'text-amber-600',
  high: 'text-rose-600',
}

export default function StaffDashboard() {
  const { user } = useAuth()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.get('/dashboard/staff')
      .then((res) => setData(res.data))
      .finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="max-w-4xl mx-auto">
        <div className="h-8 bg-gray-200 rounded w-1/3 animate-pulse mb-6"></div>
        <div className="space-y-3">
          {[0, 1, 2].map((i) => (
            <div key={i} className="bg-white rounded-2xl border border-gray-200 p-5 h-20 animate-pulse"></div>
          ))}
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-4xl mx-auto">
      <div className="mb-2 flex flex-col md:flex-row md:items-start md:justify-between gap-3 md:gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Hello, {user.name.split(' ')[0]}</h1>
          <p className="text-sm text-gray-500 mt-1">Here's what's on your plate.</p>
        </div>
        <DateTimeDisplay />
      </div>

      <section className="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard label="Pending" value={data.summary.pendingTasks} accent="slate" />
        <StatCard label="In Progress" value={data.summary.inProgressTasks} accent="amber" />
        <StatCard label="Done" value={data.summary.doneTasks} accent="green" />
        <StatCard label="My Events" value={data.summary.upcomingEvents} accent="blue" />
      </section>

      <section className="mt-10">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-xs font-semibold uppercase tracking-wide text-gray-500">My Tasks</h2>
          <Link to="/staff/tasks" className="text-xs font-medium text-neu-green hover:underline">View all</Link>
        </div>
        {data.myTasks.length === 0 ? (
          <EmptyState message="No tasks assigned to you yet." />
        ) : (
          <div className="space-y-2">
            {data.myTasks.map((task) => (
              <div key={task.id} className="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-3">
                <div className="min-w-0">
                  <div className="font-medium text-gray-900 truncate">{task.name}</div>
                  <div className="text-xs text-gray-500 mt-0.5 truncate">
                    {task.event.name} · Due {formatDateCompact(task.due_date)}
                  </div>
                </div>
                <div className="flex items-center gap-2 flex-shrink-0">
                  <span className={`text-[10px] font-semibold uppercase tracking-wide ${PRIORITY_COLORS[task.priority]}`}>
                    {task.priority}
                  </span>
                  <span className={`text-xs px-2 py-1 rounded-md font-medium ${STATUS_COLORS[task.status]}`}>
                    {STATUS_LABELS[task.status]}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>

      <section className="mt-10">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-xs font-semibold uppercase tracking-wide text-gray-500">My Events</h2>
          <Link to="/staff/events" className="text-xs font-medium text-neu-green hover:underline">View all</Link>
        </div>
        {data.myEvents.length === 0 ? (
          <EmptyState message="You are not assigned to any upcoming events." />
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            {data.myEvents.map((event) => (
              <div key={event.id} className="bg-white rounded-xl border border-gray-200 p-4">
                <div className="font-medium text-gray-900">{event.name}</div>
                <div className="mt-2 space-y-1 text-xs text-gray-500">
                  <div className="flex items-center gap-1.5">
                    <CalendarIcon width={14} height={14} />
                    {formatDateRange(event.event_date, event.end_date)}
                  </div>
                  <div className="flex items-center gap-1.5">
                    <ClockIcon width={14} height={14} />
                    {formatTimeRange(event.event_time, event.end_time) || 'Time TBA'}
                  </div>
                  <div className="flex items-center gap-1.5">
                    <MapPinIcon width={14} height={14} />
                    {event.location || 'Venue TBA'}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </section>
    </div>
  )
}

function EmptyState({ message }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200 border-dashed p-8 text-center text-sm text-gray-500">
      {message}
    </div>
  )
}

