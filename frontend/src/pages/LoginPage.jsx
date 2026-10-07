import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/auth'
import { isServerUnreachable } from '../lib/errors'

export default function LoginPage() {
  const { user, login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  if (user) {
    return <Navigate to={`/${user.role}`} replace />
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setError(null)
    setSubmitting(true)
    try {
      const u = await login(email, password)
      navigate(`/${u.role}`, { replace: true })
    } catch (err) {
      const msg = isServerUnreachable(err)
        ? "Can't reach the EMO Tracker server. Make sure the app is running, then try again."
        : err.response?.data?.errors?.email?.[0]
          || err.response?.data?.message
          || 'Login failed. Please try again.'
      setError(msg)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center p-6">
      <div className="w-full max-w-md">
        <div className="flex flex-col items-center gap-3 mb-8">
          <img src="/neu-logo.png" alt="New Era University" className="w-20 h-20 object-contain" />
          <div className="text-center">
            <h1 className="text-2xl font-semibold text-gray-900">EMO Tracker</h1>
            <p className="text-sm text-gray-500">Events Management Office</p>
            <p className="text-xs text-gray-500 mt-0.5">New Era University</p>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-md border border-gray-200 p-8">
          <h2 className="text-lg font-semibold text-gray-900 mb-1">Sign in</h2>
          <p className="text-sm text-gray-500 mb-6">Welcome back. Use your EMO credentials.</p>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                Email
              </label>
              <input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                autoComplete="email"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
                placeholder="you@neu.edu.ph"
              />
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
                Password
              </label>
              <input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                autoComplete="current-password"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent"
              />
            </div>

            {error && (
              <div className="p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-800">
                {error}
              </div>
            )}

            <button
              type="submit"
              disabled={submitting}
              className="w-full py-2.5 px-4 bg-neu-green hover:bg-neu-green-dark text-white font-medium rounded-lg transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {submitting ? 'Signing in...' : 'Sign in'}
            </button>
          </form>
        </div>

        <div className="text-center mt-6 space-y-1">
          <p className="text-xs text-gray-500">Internal use only</p>
          <p className="text-[11px] text-gray-500">
            © 2026 EMO Tracker · A Capstone Project at New Era University
          </p>
        </div>
      </div>
    </div>
  )
}
