import { useAuth } from '../contexts/AuthContext'

const ROLE_LABELS = {
  admin: 'ADMINISTRATOR',
  officer: 'OFFICER',
  staff: 'STAFF',
}

const ROLE_BADGE_COLORS = {
  admin: 'bg-neu-green text-white',
  officer: 'bg-amber-100 text-amber-800',
  staff: 'bg-gray-100 text-gray-700',
}

export default function DashboardPlaceholder({ role }) {
  const { user, logout } = useAuth()

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-neu-green flex items-center justify-center">
              <span className="text-white font-bold text-sm">EMD</span>
            </div>
            <div>
              <div className="text-sm font-semibold text-gray-900">EMD Tracker</div>
              <div className="text-xs text-gray-500">Events Management Department</div>
            </div>
          </div>
          <button
            onClick={logout}
            className="text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition"
          >
            Logout
          </button>
        </div>
      </header>

      <main className="max-w-6xl mx-auto px-6 py-10">
        <div className="flex items-center gap-3 mb-6">
          <h1 className="text-2xl font-semibold text-gray-900">Hello, {user.name}</h1>
          <span className={`px-2.5 py-1 rounded-md text-xs font-semibold ${ROLE_BADGE_COLORS[role]}`}>
            {ROLE_LABELS[role]}
          </span>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
          <h2 className="text-lg font-semibold text-gray-900 mb-2">Phase 2 placeholder</h2>
          <p className="text-sm text-gray-600 mb-4">
            Authentication is working. You are signed in with role <code className="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{role}</code>.
            The full {ROLE_LABELS[role].toLowerCase()} dashboard will be built in Phase 3.
          </p>
          <div className="text-xs text-gray-500 space-y-1">
            <div>User ID: <span className="font-mono">{user.id}</span></div>
            <div>Email: <span className="font-mono">{user.email}</span></div>
            <div>Active: <span className="font-mono">{user.is_active ? 'yes' : 'no'}</span></div>
          </div>
        </div>
      </main>
    </div>
  )
}
