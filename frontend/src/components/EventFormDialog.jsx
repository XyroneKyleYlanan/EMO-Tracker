import { useEffect, useState } from 'react'
import api from '../lib/api'

const EMPTY = {
  name: '',
  description: '',
  venue: '',
  event_date: '',
  event_time: '09:00',
  budget: '',
  staff_ids: [],
}

export default function EventFormDialog({ open, event, onClose, onSaved }) {
  const [form, setForm] = useState(EMPTY)
  const [allStaff, setAllStaff] = useState([])
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (!open) return
    api.get('/users').then((res) => setAllStaff(res.data.users || []))
  }, [open])

  useEffect(() => {
    if (event) {
      setForm({
        name: event.name || '',
        description: event.description || '',
        venue: event.venue || '',
        event_date: event.event_date ? event.event_date.slice(0, 10) : '',
        event_time: event.event_time?.slice(0, 5) || '09:00',
        budget: event.budget || '',
        staff_ids: (event.staff || []).map((s) => s.id),
      })
    } else {
      setForm(EMPTY)
    }
    setErrors({})
  }, [event, open])

  if (!open) return null

  function update(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }))
  }

  function toggleStaff(id) {
    setForm((prev) => ({
      ...prev,
      staff_ids: prev.staff_ids.includes(id)
        ? prev.staff_ids.filter((x) => x !== id)
        : [...prev.staff_ids, id],
    }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setErrors({})
    setSubmitting(true)
    try {
      const payload = {
        name: form.name,
        description: form.description || null,
        venue: form.venue,
        event_date: form.event_date,
        event_time: form.event_time,
        budget: form.budget === '' ? null : Number(form.budget),
        staff_ids: form.staff_ids,
      }
      const res = event
        ? await api.put(`/events/${event.id}`, payload)
        : await api.post('/events', payload)
      onSaved(res.data.event)
    } catch (err) {
      setErrors(err.response?.data?.errors || { _: [err.response?.data?.message || 'Failed to save.'] })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="fixed inset-0 z-40 flex items-start md:items-center justify-center p-4 md:p-6">
      <div className="fixed inset-0 bg-black/40" onClick={onClose}></div>

      <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <header className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">
            {event ? 'Edit event' : 'New event'}
          </h2>
        </header>

        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-6 py-5 space-y-4">
          <Field label="Name" error={errors.name?.[0]}>
            <input
              type="text"
              required
              value={form.name}
              onChange={(e) => update('name', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field label="Description" error={errors.description?.[0]}>
            <textarea
              rows={3}
              value={form.description}
              onChange={(e) => update('description', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field label="Venue" error={errors.venue?.[0]}>
            <input
              type="text"
              required
              value={form.venue}
              onChange={(e) => update('venue', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <div className="grid grid-cols-2 gap-3">
            <Field label="Date" error={errors.event_date?.[0]}>
              <input
                type="date"
                required
                value={form.event_date}
                onChange={(e) => update('event_date', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
              />
            </Field>
            <Field label="Time" error={errors.event_time?.[0]}>
              <input
                type="time"
                required
                value={form.event_time}
                onChange={(e) => update('event_time', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
              />
            </Field>
          </div>

          <Field label="Budget (₱)" error={errors.budget?.[0]} optional>
            <input
              type="number"
              min="0"
              step="0.01"
              value={form.budget}
              onChange={(e) => update('budget', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field label="Assigned staff" error={errors.staff_ids?.[0]} optional>
            <div className="border border-gray-300 rounded-lg p-2 max-h-40 overflow-y-auto space-y-1">
              {allStaff.filter((u) => u.role === 'staff').map((u) => (
                <label key={u.id} className="flex items-center gap-2 px-2 py-1 rounded hover:bg-gray-50 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={form.staff_ids.includes(u.id)}
                    onChange={() => toggleStaff(u.id)}
                    className="rounded border-gray-300 text-neu-green focus:ring-neu-green"
                  />
                  <span className="text-sm text-gray-700">{u.name}</span>
                </label>
              ))}
              {allStaff.filter((u) => u.role === 'staff').length === 0 && (
                <div className="text-xs text-gray-400 px-2 py-1">No staff users available.</div>
              )}
            </div>
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
            {submitting ? 'Saving...' : event ? 'Save changes' : 'Create event'}
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
