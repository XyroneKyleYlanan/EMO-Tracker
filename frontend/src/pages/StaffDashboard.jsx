import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import StatCard from '../components/StatCard'
import DateTimeDisplay from '../components/DateTimeDisplay'
import TaskRow from '../components/TaskRow'
import EventDetailDrawer from '../components/EventDetailDrawer'
import { CalendarIcon, ClockIcon, MapPinIcon } from '../components/icons'
import EventBadges, { RescheduledNote } from '../components/EventBadges'
import { formatDateRange, formatTimeRange } from '../lib/format'

export default function StaffDashboard() {
  const { user } = useAuth()
  const [data, setData] = useState(null)
  const [selectedId, setSelectedId] = useState(null)

  function fetchHome() {
    api.get('/dashboard/staff').then((res) => setData(res.data))
  }

  useEffect(() => {
    fetchHome()
  }, [])

  if (!data) {
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
          <p className="text-sm text-gray-500 mt-1">Here&apos;s what&apos;s on your plate.</p>
        </div>
        <DateTimeDisplay />
      </div>

      <section className="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard label="Pending" value={data.summary.pendingTasks} accent="slate" />
        <StatCard label="In Progress" value={data.summary.inProgressTasks} accent="amber" />
        <StatCard label="Done" value={data.summary.doneTasks} accent="green" />
        <StatCard label="Upcoming events" value={data.summary.upcomingEvents} accent="blue" />
      </section>

      <section className="mt-10">
        <SectionHeader title="My open tasks" to="/staff/tasks" />
        {data.openTasks.length === 0 ? (
          <EmptyState message="No open tasks. You're all caught up." />
        ) : (
          <div className="space-y-2">
            {data.openTasks.map((task) => (
              <TaskRow key={task.id} task={task} currentUser={user} showEvent onChanged={fetchHome} />
            ))}
          </div>
        )}
      </section>

      <section className="mt-10">
        <SectionHeader title="My upcoming events" to="/staff/events" />
        {data.myEvents.length === 0 ? (
          <EmptyState message="You are not assigned to any upcoming events." />
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            {data.myEvents.map((event) => (
              <button
                key={event.id}
                onClick={() => setSelectedId(event.id)}
                className="text-left bg-white rounded-xl border border-gray-200 p-4 hover:border-neu-green hover:shadow-md transition"
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <div className="font-medium text-gray-900">{event.name}</div>
                    <RescheduledNote event={event} className="text-gray-500" />
                  </div>
                  <EventBadges event={event} size="sm" showScheduled={false} />
                </div>
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
              </button>
            ))}
          </div>
        )}
      </section>

      <EventDetailDrawer
        key={selectedId}
        eventId={selectedId}
        onClose={() => setSelectedId(null)}
        onChanged={fetchHome}
        canEdit={false}
        canDelete={false}
      />
    </div>
  )
}

function SectionHeader({ title, to }) {
  return (
    <div className="flex items-center justify-between mb-3">
      <h2 className="text-xs font-semibold uppercase tracking-wide text-gray-500">{title}</h2>
      <Link to={to} className="text-xs font-medium text-neu-green hover:underline">View all</Link>
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
