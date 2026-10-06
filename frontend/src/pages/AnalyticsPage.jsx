import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import StatCard from '../components/StatCard'
import EventBadges from '../components/EventBadges'
import ReadinessDonut from '../components/ReadinessDonut'
import EventDetailDrawer from '../components/EventDetailDrawer'
import { formatDateCompact, formatTimeRange } from '../lib/format'

// Calendar periods, like a report. Events count by the day they start.
const PERIODS = [
  { value: 'week', label: 'This Week' },
  { value: 'month', label: 'This Month' },
  { value: 'year', label: 'This Year' },
  { value: 'all', label: 'All Time' },
]

const STATUSES = [
  { key: 'upcoming', label: 'Upcoming' },
  { key: 'ongoing', label: 'Ongoing' },
  { key: 'completed', label: 'Completed' },
  { key: 'cancelled', label: 'Cancelled' },
]

export default function AnalyticsPage() {
  const { user } = useAuth()
  const [period, setPeriod] = useState('year')
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [selectedId, setSelectedId] = useState(null)
  const [refresh, setRefresh] = useState(0)

  useEffect(() => {
    // Ignore answers for a period the user has already switched away from.
    let current = true
    setLoading(true)
    api.get(`/analytics?period=${period}`)
      .then((res) => { if (current) setData(res.data) })
      .finally(() => { if (current) setLoading(false) })
    return () => { current = false }
  }, [period, refresh])

  const periodLabel = PERIODS.find((p) => p.value === period).label.toLowerCase()

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Analytics</h1>
          <p className="text-sm text-gray-500 mt-1">How the schedule and the EMO&apos;s preparation are going.</p>
        </div>

        <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1 max-w-full overflow-x-auto">
          {PERIODS.map((p) => (
            <button
              key={p.value}
              onClick={() => setPeriod(p.value)}
              className={`px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition ${
                period === p.value ? 'bg-neu-green text-white' : 'text-gray-600 hover:bg-gray-100'
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>
      </div>

      {loading && !data ? (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          {[0, 1, 2, 3].map((i) => (
            <div key={i} className="bg-white rounded-2xl border border-gray-200 p-5 h-28 animate-pulse">
              <div className="h-3 bg-gray-200 rounded w-2/3 mb-3"></div>
              <div className="h-8 bg-gray-100 rounded w-1/3"></div>
            </div>
          ))}
        </div>
      ) : (
        <div className={loading ? 'opacity-60' : ''}>
          <SectionHeading
            title="Events"
            subtitle={`${data.schedule.total} event${data.schedule.total === 1 ? '' : 's'} on the schedule ${period === 'all' ? 'in total' : periodLabel}`}
          />
          <section className="grid grid-cols-2 md:grid-cols-5 gap-4">
            {STATUSES.map((s) => (
              <StatCard
                key={s.key}
                label={s.label}
                value={data.schedule.status[s.key]}
                sublabel={data.schedule.total ? `${percent(data.schedule.status[s.key], data.schedule.total)}%` : null}
                accent="slate"
              />
            ))}
            <StatCard label="Rescheduled" value={data.schedule.rescheduled} sublabel="moved" accent="slate" />
          </section>
          <p className="mt-2 text-xs text-gray-500">
            Upcoming, ongoing, completed and cancelled add up to all events. Rescheduled events moved to a new date and are also counted in one of those.
          </p>

          <section className="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <EventTypeSplit type={data.schedule.type} />
            <BusiestVenues venues={data.schedule.venues} />
          </section>

          <SectionHeading title="EMO preparation" subtitle="Events the EMO prepares, and how ready they are." className="mt-10" />
          <section className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <StatCard label="Prepared events" value={data.stats.totalEvents} accent="blue" />
            <StatCard label="Total Tasks" value={data.stats.totalTasks} accent="slate" />
            <StatCard
              label="Tasks Done"
              value={data.stats.tasksDone}
              sublabel={`${data.stats.tasksDonePercent}%`}
              accent="green"
            />
            <StatCard label="People with tasks" value={data.stats.activeMembers} accent="amber" />
          </section>

          <section className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <ReadinessDonut distribution={data.distribution} />

            <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
              <div className="flex items-center justify-between mb-3">
                <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                  Top 5 most urgent events
                </h3>
                <Link to={`/${user.role}/events`} className="text-xs font-medium text-neu-green hover:underline">
                  View all events
                </Link>
              </div>

              {data.topUrgent.length === 0 ? (
                <div className="text-sm text-gray-500 py-8 text-center">
                  No upcoming events in this period.
                </div>
              ) : (
                <div className="space-y-2">
                  {data.topUrgent.map((event) => {
                    const pct = event.task_summary.total > 0
                      ? Math.round((event.task_summary.done / event.task_summary.total) * 100)
                      : 0
                    return (
                      <button
                        key={event.id}
                        onClick={() => setSelectedId(event.id)}
                        className="w-full text-left flex items-center justify-between gap-3 p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition"
                      >
                        <div className="min-w-0 flex-1">
                          <div className="text-sm font-medium text-gray-900 truncate">{event.name}</div>
                          <div className="text-xs text-gray-500 mt-0.5">
                            {formatDateCompact(event.event_date)}{event.event_time ? ` · ${formatTimeRange(event.event_time)}` : ''} · {event.task_summary.done}/{event.task_summary.total} tasks ({pct}%)
                          </div>
                        </div>
                        <EventBadges event={event} size="sm" showScheduled={false} />
                      </button>
                    )
                  })}
                </div>
              )}
            </div>
          </section>
        </div>
      )}

      <EventDetailDrawer
        key={selectedId}
        eventId={selectedId}
        onClose={() => setSelectedId(null)}
        onChanged={() => setRefresh((n) => n + 1)}
        canEdit={false}
        canDelete={false}
      />
    </div>
  )
}

function percent(part, whole) {
  return whole ? Math.round((part / whole) * 100) : 0
}

function SectionHeading({ title, subtitle, className = '' }) {
  return (
    <div className={`mb-3 ${className}`}>
      <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-700">{title}</h2>
      <p className="text-xs text-gray-500 mt-0.5">{subtitle}</p>
    </div>
  )
}

// One ratio: how much of the schedule is booked by outside organizers.
function EventTypeSplit({ type }) {
  const total = type.internal + type.external
  return (
    <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
      <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-4">Internal vs external</h3>
      {total === 0 ? (
        <div className="text-sm text-gray-500 py-4 text-center">No events in this period.</div>
      ) : (
        <>
          <div className="grid grid-cols-2 gap-4">
            <TypeCount label="Internal" hint="NEU events" value={type.internal} total={total} />
            <TypeCount label="External" hint="Outside organizers" value={type.external} total={total} />
          </div>
          <div
            className="mt-4 h-2.5 rounded bg-neu-green/15 overflow-hidden"
            role="img"
            aria-label={`${type.external} of ${total} events are external`}
            title={`External: ${type.external} of ${total} events (${percent(type.external, total)}%)`}
          >
            <div className="h-full bg-neu-green rounded-r" style={{ width: `${percent(type.external, total)}%` }} />
          </div>
          <div className="mt-1.5 text-xs text-gray-500">The filled part is external.</div>
        </>
      )}
    </div>
  )
}

function TypeCount({ label, hint, value, total }) {
  return (
    <div>
      <div className="text-sm font-medium text-gray-900">{label}</div>
      <div className="mt-1 flex items-baseline gap-2">
        <span className="text-2xl font-semibold text-gray-900">{value}</span>
        <span className="text-xs text-gray-500">{percent(value, total)}%</span>
      </div>
      <div className="text-xs text-gray-500">{hint}</div>
    </div>
  )
}

// The venues booked most in this period (cancelled bookings don't count).
function BusiestVenues({ venues }) {
  const most = venues[0]?.events || 0
  return (
    <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
      <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-500">Busiest venues</h3>
      <p className="text-xs text-gray-500 mt-0.5 mb-4">Events per venue, not counting cancelled ones.</p>
      {venues.length === 0 ? (
        <div className="text-sm text-gray-500 py-4 text-center">No venue bookings in this period.</div>
      ) : (
        <ul className="space-y-3">
          {venues.map((venue) => (
            <li key={venue.name} title={`${venue.name}: ${venue.events} event${venue.events === 1 ? '' : 's'}`}>
              <div className="flex items-baseline justify-between gap-3 text-sm">
                <span className="text-gray-900 truncate">{venue.name}</span>
                <span className="text-gray-700 font-medium tabular-nums">{venue.events}</span>
              </div>
              <div className="mt-1 h-2 rounded-r bg-neu-green" style={{ width: `${Math.max(4, (venue.events / most) * 100)}%` }} />
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
