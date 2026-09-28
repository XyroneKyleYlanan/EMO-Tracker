import { useEffect, useState } from 'react'
import api from '../lib/api'

const EMPTY = {
  name: '',
  description: '',
  due_date: '',
  status: 'pending',
  priority: 'medium',
  assigned_to: '',
}

export default function TaskFormDialog({ open, eventId, task, onClose, onSaved }) {
  const [form, setForm] = useState(EMPTY)
  const [staff, setStaff] = useState([])
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (!open) return
    api.get('/users').then((res) => {
      setStaff((res.data.users || []).filter((u) => u.role === 'staff'))
    })
  }, [open])

  useEffect(() => {
    if (task) {
      setForm({
        name: task.name || '',
        description: task.description || '',
        due_date: task.due_date ? task.due_date.slice(0, 10) : '',
        status: task.status || 'pending',
        priority: task.priority || 'medium',
        assigned_to: task.assigned_to || '',
      })
    } else {
      setForm(EMPTY)
    }
    setErrors({})
  }, [task, open])

  if (!open) return null

  // Active staff, plus the current assignee even if they were deactivated since.
  const staffOptions = staff.filter((u) => u.is_active || u.id === task?.assigned_to)

  function update(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setErrors({})
    setSubmitting(true)
    try {
      const payload = {
        name: form.name,
        description: form.description || null,
        due_date: form.due_date,
        status: form.status,
        priority: form.priority,
        assigned_to: form.assigned_to === '' ? null : Number(form.assigned_to),
      }
      const res = task
        ? await api.put(`/tasks/${task.id}`, payload)
        : await api.post(`/events/${eventId}/tasks`, payload)
      onSaved(res.data.task)
    } catch (err) {
      setErrors(err.response?.data?.errors || { _: [err.response?.data?.message || 'Failed to save.'] })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-start md:items-center justify-center p-4 md:p-6">
      <div className="fixed inset-0 bg-black/40" onClick={onClose}></div>

      <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col">
        <header className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">{task ? 'Edit task' : 'New task'}</h2>
        </header>

        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-6 py-5 space-y-4">
          <Field label="Task name" error={errors.name?.[0]}>
            <input
              type="text"
              required
              value={form.name}
              onChange={(e) => update('name', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field label="Description" optional error={errors.description?.[0]}>
            <textarea
              rows={2}
              value={form.description}
              onChange={(e) => update('description', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field label="Due date" error={errors.due_date?.[0]}>
            <input
              type="date"
              required
              value={form.due_date}
              onChange={(e) => update('due_date', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <div className="grid grid-cols-2 gap-3">
            <Field label="Priority" error={errors.priority?.[0]}>
              <select
                value={form.priority}
                onChange={(e) => update('priority', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </Field>
            <Field label="Status" error={errors.status?.[0]}>
              <select
                value={form.status}
                onChange={(e) => update('status', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
              >
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="done">Done</option>
              </select>
            </Field>
          </div>

          <Field label="Assigned staff" optional error={errors.assigned_to?.[0]}>
            <select
              value={form.assigned_to}
              onChange={(e) => update('assigned_to', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            >
              <option value="">— Unassigned —</option>
              {staffOptions.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.name}{u.is_active ? '' : ' (deactivated)'}
                </option>
              ))}
            </select>
          </Field>

          {errors._ && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-800">
              {errors._[0]}
            </div>
          )}
        </form>

        <footer className="px-6 py-3 border-t border-gray-200 flex items-center justify-end gap-2">
          <button
            type="button"
            onClick={onClose}
            className="text-sm text-gray-600 hover:bg-gray-100 px-3 py-2 rounded-lg transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            onClick={handleSubmit}
            disabled={submitting}
            className="text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition disabled:opacity-60"
          >
            {submitting ? 'Saving...' : task ? 'Save changes' : 'Create task'}
          </button>
        </footer>
      </div>
    </div>
  )
}

function Field({ label, error, optional, children }) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">
        {label} {optional && <span className="text-gray-400 font-normal">(optional)</span>}
      </label>
      {children}
      {error && <div className="text-xs text-rose-600 mt-1">{error}</div>}
    </div>
  )
}
