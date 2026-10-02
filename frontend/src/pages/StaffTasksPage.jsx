import { useEffect, useState } from 'react'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import StatCard from '../components/StatCard'
import TaskRow from '../components/TaskRow'

export default function StaffTasksPage() {
  const { user } = useAuth()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [filter, setFilter] = useState('open')
  
  function fetchTasks() {
    setLoading(true)
    api.get('/my-tasks')
      .then((res) => setData(res.data))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    fetchTasks()
  }, [])

  if (loading || !data) {
    return (
      <div className="max-w-4xl mx-auto">
        <div className="h-8 bg-gray-200 rounded w-1/3 animate-pulse mb-6"></div>
        <div className="space-y-3">
          {[0, 1, 2].map((i) => (
            <div key={i} className="bg-white rounded-2xl border border-gray-200 p-5 h-16 animate-pulse"></div>
          ))}
        </div>
      </div>
    )
  }

  const filtered = filter === 'all'
    ? data.tasks
    : filter === 'open'
      ? data.tasks.filter((t) => t.status !== 'done')
      : data.tasks.filter((t) => t.status === filter)

  return (
    <div className="max-w-4xl mx-auto">
      <div className="mb-6">
        <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">My Tasks</h1>
        <p className="text-sm text-gray-500 mt-1">Everything assigned to you across all events.</p>
      </div>

      <section className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <StatCard label="Total" value={data.summary.total} accent="slate" />
        <StatCard label="Pending" value={data.summary.pending} accent="slate" />
        <StatCard label="In Progress" value={data.summary.in_progress} accent="amber" />
        <StatCard label="Done" value={data.summary.done} accent="green" />
      </section>

      <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1 mb-4 w-fit max-w-full overflow-x-auto">
        <FilterBtn active={filter === 'open'} onClick={() => setFilter('open')} label="Open" />
        <FilterBtn active={filter === 'all'} onClick={() => setFilter('all')} label="All" />
        <FilterBtn active={filter === 'pending'} onClick={() => setFilter('pending')} label="Pending" />
        <FilterBtn active={filter === 'in_progress'} onClick={() => setFilter('in_progress')} label="In Progress" />
        <FilterBtn active={filter === 'done'} onClick={() => setFilter('done')} label="Done" />
      </div>

      {filtered.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 border-dashed p-10 text-center text-sm text-gray-500">
          {filter === 'all' ? 'No tasks assigned to you yet.' : `No ${filter.replace('_', ' ')} tasks.`}
        </div>
      ) : (
        <div className="space-y-2">
          {filtered.map((task) => (
            <TaskRow
              key={task.id}
              task={task}
              currentUser={user}
              showEvent
              onChanged={fetchTasks}
            />
          ))}
        </div>
      )}
    </div>
  )
}

function FilterBtn({ active, onClick, label }) {
  return (
    <button
      onClick={onClick}
      className={`px-3 py-1.5 rounded-md text-sm font-medium whitespace-nowrap transition ${
        active ? 'bg-neu-green text-white' : 'text-gray-600 hover:bg-gray-100'
      }`}
    >
      {label}
    </button>
  )
}
