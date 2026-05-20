import { useEffect, useState } from 'react'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import StatCard from '../components/StatCard'
import QuickNavCard from '../components/QuickNavCard'
import DateTimeDisplay from '../components/DateTimeDisplay'
import { CalendarIcon, ChartIcon, UsersIcon, ChecklistIcon } from '../components/icons'

export default function AdminDashboard() {
  const { user } = useAuth()
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.get('/dashboard/admin')
      .then((res) => setStats(res.data.stats))
      .finally(() => setLoading(false))
  }, [])

  return (
    <div className="max-w-6xl mx-auto">
      <div className="mb-2 flex flex-col md:flex-row md:items-start md:justify-between gap-3 md:gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Hello, {user.name.split(' ')[0]}</h1>
          <p className="text-sm text-gray-500 mt-1">Welcome back to your administrator dashboard.</p>
        </div>
        <DateTimeDisplay />
      </div>

      <section className="mt-8">
        <h2 className="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Overview</h2>
        {loading ? (
          <StatGridSkeleton />
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <StatCard
              label="Total Events"
              value={stats.totalEvents}
              sublabel={`${stats.upcomingEvents} upcoming`}
              accent="blue"
            />
            <StatCard label="Total Tasks" value={stats.totalTasks} accent="slate" />
            <StatCard
              label="Tasks Done"
              value={stats.tasksDone}
              sublabel={`${Math.round((stats.tasksDone / stats.totalTasks) * 100) || 0}%`}
              accent="green"
            />
            <StatCard label="Active Staff" value={stats.activeStaff} accent="amber" />
          </div>
        )}
      </section>

      <section className="mt-10">
        <h2 className="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Quick actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <QuickNavCard to="/admin/staff" Icon={UsersIcon} label="Manage Staff" description="Add and manage user accounts" />
          <QuickNavCard to="/admin/events" Icon={CalendarIcon} label="Events" description="Plan and schedule events" />
          <QuickNavCard to="/admin/analytics" Icon={ChartIcon} label="Analytics" description="Event readiness overview" />
          <QuickNavCard to="/admin/events" Icon={ChecklistIcon} label="Reports" description="Generate and view reports" />
        </div>
      </section>
    </div>
  )
}

function StatGridSkeleton() {
  return (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
      {[0, 1, 2, 3].map((i) => (
        <div key={i} className="bg-white rounded-2xl border border-gray-200 p-5 h-28 animate-pulse">
          <div className="h-3 bg-gray-200 rounded w-2/3 mb-3"></div>
          <div className="h-8 bg-gray-100 rounded w-1/3"></div>
        </div>
      ))}
    </div>
  )
}
