import { useEffect, useState } from 'react'
import api from '../lib/api'

// Assigning staff is preparation work, so officers can do it even though
// event details (date, time, venue) are admin-only. Mount it only while open.
export default function StaffPickerDialog({ event, onClose, onSaved }) {
  const currentIds = (event.staff || []).map((s) => s.id)
  const [selected, setSelected] = useState(currentIds)
  const [staff, setStaff] = useState([])
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    api.get('/users').then((res) => setStaff((res.data.users || []).filter((u) => u.role === 'staff')))
  }, [])

  // Active staff, plus anyone already assigned so they aren't silently dropped.
  const options = staff.filter((u) => u.is_active || currentIds.includes(u.id))

  function toggle(id) {
    setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]))
  }

  async function save() {
    setError(null)
    setSubmitting(true)
    try {
      await api.put(`/events/${event.id}/staff`, { staff_ids: selected })
      onSaved()
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to save staff.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-start md:items-center justify-center p-4 md:p-6">
      <div className="fixed inset-0 bg-black/40" onClick={onClose}></div>

      <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-sm max-h-[90vh] flex flex-col">
        <header className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">Assigned staff</h2>
          <p className="text-xs text-gray-500 mt-0.5 truncate">{event.name}</p>
        </header>

        <div className="flex-1 overflow-y-auto px-4 py-3 space-y-1">
          {options.map((u) => (
            <label key={u.id} className="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-50 cursor-pointer">
              <input
                type="checkbox"
                checked={selected.includes(u.id)}
                onChange={() => toggle(u.id)}
                className="rounded border-gray-300 text-neu-green focus:ring-neu-green"
              />
              <span className="text-sm text-gray-700">
                {u.name}
                {!u.is_active && <span className="text-gray-400"> (deactivated)</span>}
              </span>
            </label>
          ))}
          {options.length === 0 && <div className="text-xs text-gray-400 px-2 py-2">No staff users available.</div>}
          {error && <div className="p-2 bg-rose-50 border border-rose-200 rounded text-xs text-rose-800">{error}</div>}
        </div>

        <footer className="px-6 py-3 border-t border-gray-200 flex items-center justify-end gap-2">
          <button type="button" onClick={onClose} className="text-sm text-gray-600 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
            Cancel
          </button>
          <button
            type="button"
            onClick={save}
            disabled={submitting}
            className="text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition disabled:opacity-60"
          >
            {submitting ? 'Saving...' : 'Save'}
          </button>
        </footer>
      </div>
    </div>
  )
}
