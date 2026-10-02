import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import StatCard from '../components/StatCard'
import ReadinessBadge from '../components/ReadinessBadge'
import ReadinessDonut from '../components/ReadinessDonut'
import { formatDateCompact, formatTimeRange } from '../lib/format'

const PERIODS = [
  { value: 'week', label: 'This Week' },
  { value: 'month', label: 'This Month' },
  { value: 'all', label: 'All Time' },
]

export default function AnalyticsPage() {
  const { user } = useAuth()
  const [period, setPeriod] = useState('all')
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    // Ignore answers for a period the user has already switched away from.
    let current = true
    setLoading(true)
    api.get(`/analytics?period=${period}`)
      .then((res) => { if (current) setData(res.data) })
      .finally(() => { if (current) setLoading(false) })
    return () => { current = false }
  }, [period])

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Analytics</h1>
          <p className="text-sm text-gray-500 mt-1">Event readiness overview and key metrics.</p>
        </div>

        <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1">
          {PERIODS.map((p) => (
            <button
              key={p.value}
              onClick={() => setPeriod(p.value)}
              className={`px-3 py-1.5 rounded-md text-sm font-medium transition ${
                period === p.value ? 'bg-neu-green text-white' : 'text-gray-600 hover:bg-gray-100'
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>
      </div>

      {loading || !data ? (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          {[0, 1, 2, 3].map((i) => (
            <div key={i} className="bg-white rounded-2xl border border-gray-200 p-5 h-28 animate-pulse">
              <div className="h-3 bg-gray-200 rounded w-2/3 mb-3"></div>
              <div className="h-8 bg-gray-100 rounded w-1/3"></div>
            </div>
          ))}
        </div>
      ) : (
        <>
          <section className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <StatCard label="Total Events" value={data.stats.totalEvents} accent="blue" />
            <StatCard label="Total Tasks" value={data.stats.totalTasks} accent="slate" />
            <StatCard
              label="Tasks Done"
              value={data.stats.tasksDone}
              sublabel={`${data.stats.tasksDonePercent}%`}
              accent="green"
            />
            <StatCard label="Active Staff" value={data.stats.activeStaff} accent="amber" />
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
                      <div key={event.id} className="flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-lg">
                        <div className="min-w-0 flex-1">
                          <div className="text-sm font-medium text-gray-900 truncate">{event.name}</div>
                          <div className="text-xs text-gray-500 mt-0.5">
                            {formatDateCompact(event.event_date)}{event.event_time ? ` · ${formatTimeRange(event.event_time)}` : ''} · {event.task_summary.done}/{event.task_summary.total} tasks ({pct}%)
                          </div>
                        </div>
                        <ReadinessBadge readiness={event.readiness} size="sm" />
                      </div>
                    )
                  })}
                </div>
              )}
            </div>
          </section>
        </>
      )}
    </div>
  )
}
