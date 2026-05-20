import { useEffect, useState } from 'react'
import api from '../lib/api'
import { useToast } from '../contexts/ToastContext'

export default function ChangePasswordDialog({ open, onClose }) {
  const toast = useToast()
  const [currentPassword, setCurrentPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (open) {
      setCurrentPassword('')
      setNewPassword('')
      setConfirm('')
      setErrors({})
    }
  }, [open])

  if (!open) return null

  async function handleSubmit(e) {
    e.preventDefault()
    setErrors({})

    if (newPassword !== confirm) {
      setErrors({ new_password: ['Passwords do not match.'] })
      return
    }

    setSubmitting(true)
    try {
      await api.post('/change-password', {
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirm,
      })
      toast.success('Password changed successfully.')
      onClose()
    } catch (err) {
      setErrors(err.response?.data?.errors || { _: [err.response?.data?.message || 'Failed to change password.'] })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-start md:items-center justify-center p-4 md:p-6">
      <div className="fixed inset-0 bg-black/40" onClick={onClose}></div>

      <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-md">
        <header className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">Change password</h2>
          <p className="text-xs text-gray-500 mt-0.5">Use a strong password you don't reuse elsewhere.</p>
        </header>

        <form onSubmit={handleSubmit} className="px-6 py-5 space-y-4">
          <Field label="Current password" error={errors.current_password?.[0]}>
            <input
              type="password"
              required
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
              autoFocus
            />
          </Field>

          <Field label="New password" error={errors.new_password?.[0]} hint="Minimum 8 characters.">
            <input
              type="password"
              required
              minLength={8}
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          <Field label="Confirm new password" error={errors.new_password_confirmation?.[0]}>
            <input
              type="password"
              required
              minLength={8}
              value={confirm}
              onChange={(e) => setConfirm(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
            />
          </Field>

          {errors._ && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-800">
              {errors._[0]}
            </div>
          )}

          <footer className="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="text-sm text-gray-600 hover:bg-gray-100 px-3 py-2 rounded-lg transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={submitting}
              className="text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition disabled:opacity-60"
            >
              {submitting ? 'Changing...' : 'Change password'}
            </button>
          </footer>
        </form>
      </div>
    </div>
  )
}

function Field({ label, error, hint, children }) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
      {children}
      {hint && !error && <div className="text-xs text-gray-400 mt-1">{hint}</div>}
      {error && <div className="text-xs text-rose-600 mt-1">{error}</div>}
    </div>
  )
}
