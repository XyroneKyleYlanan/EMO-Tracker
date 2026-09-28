import { useEffect, useState } from 'react'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import { useToast } from '../contexts/ToastContext'

const EMPTY = {
  name: '',
  email: '',
  password: '',
  role: 'staff',
  is_active: true,
}

export default function UserFormDialog({ open, user, onClose, onSaved }) {
  const { user: currentUser } = useAuth()
  const toast = useToast()
  const isSelf = !!user && user.id === currentUser.id
  const [form, setForm] = useState(EMPTY)
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (user) {
      setForm({
        name: user.name || '',
        email: user.email || '',
        password: '',
        role: user.role || 'staff',
        is_active: user.is_active ?? true,
      })
    } else {
      setForm(EMPTY)
    }
    setErrors({})
  }, [user, open])

  if (!open) return null

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
        email: form.email,
        role: form.role,
      }
      if (user) {
        payload.is_active = form.is_active
        if (form.password) payload.password = form.password
        await api.put(`/users/${user.id}`, payload)
        toast.success('User updated.')
      } else {
        payload.password = form.password
        await api.post('/users', payload)
        toast.success(`User "${form.name}" created.`)
      }
      onSaved()
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
          <h2 className="text-lg font-semibold text-gray-900">{user ? 'Edit user' : 'New user'}</h2>
        </header>

        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-6 py-5 space-y-4">
          <Field label="Full name" error={errors.name?.[0]}>
            <input
              type="text"
              required
              value={form.name}
              onChange={(e) => update('name', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field label="Email" error={errors.email?.[0]}>
            <input
              type="email"
              required
              value={form.email}
              onChange={(e) => update('email', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field
            label={user ? 'New password' : 'Password'}
            optional={!!user}
            error={errors.password?.[0]}
            hint={user ? 'Leave blank to keep current password.' : 'Minimum 8 characters.'}
          >
            <input
              type="password"
              required={!user}
              minLength={8}
              value={form.password}
              onChange={(e) => update('password', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field
            label="Role"
            error={errors.role?.[0]}
            hint={isSelf ? 'You cannot change your own role.' : undefined}
          >
            <select
              value={form.role}
              onChange={(e) => update('role', e.target.value)}
              disabled={isSelf}
              className="disabled:bg-gray-100 disabled:text-gray-500 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            >
              <option value="staff">Staff</option>
              <option value="officer">Officer</option>
              <option value="admin">Administrator</option>
            </select>
          </Field>

          {user && (
            <Field
              label="Active"
              error={errors.is_active?.[0]}
              hint={isSelf ? 'You cannot deactivate your own account.' : undefined}
            >
              <label className={`flex items-center gap-2 ${isSelf ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'}`}>
                <input
                  type="checkbox"
                  checked={form.is_active}
                  onChange={(e) => update('is_active', e.target.checked)}
                  disabled={isSelf}
                  className="rounded border-gray-300 text-neu-green focus:ring-neu-green"
                />
                <span className="text-sm text-gray-700">
                  {form.is_active ? 'Account is active' : 'Account is deactivated'}
                </span>
              </label>
            </Field>
          )}

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
            {submitting ? 'Saving...' : user ? 'Save changes' : 'Create user'}
          </button>
        </footer>
      </div>
    </div>
  )
}

function Field({ label, error, optional, hint, children }) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">
        {label} {optional && <span className="text-gray-400 font-normal">(optional)</span>}
      </label>
      {children}
      {hint && !error && <div className="text-xs text-gray-400 mt-1">{hint}</div>}
      {error && <div className="text-xs text-rose-600 mt-1">{error}</div>}
    </div>
  )
}
