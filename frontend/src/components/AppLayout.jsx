import { useEffect, useRef, useState } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../contexts/auth'
import ChangePasswordDialog from './ChangePasswordDialog'
import ErrorBoundary from './ErrorBoundary'
import {
  CalendarIcon,
  ChartIcon,
  ChecklistIcon,
  HomeIcon,
  LogOutIcon,
  TableIcon,
  UsersIcon,
} from './icons'

const NAV_BY_ROLE = {
  admin: [
    { to: '/admin', label: 'Home', Icon: HomeIcon, end: true },
    { to: '/admin/tasks', label: 'My tasks', Icon: ChecklistIcon },
    { to: '/admin/events', label: 'Events', Icon: CalendarIcon },
    { to: '/admin/schedule', label: 'Schedule', Icon: TableIcon },
    { to: '/admin/analytics', label: 'Analytics', Icon: ChartIcon },
    { to: '/admin/accounts', label: 'Accounts', Icon: UsersIcon },
  ],
  officer: [
    { to: '/officer', label: 'Home', Icon: HomeIcon, end: true },
    { to: '/officer/tasks', label: 'My tasks', Icon: ChecklistIcon },
    { to: '/officer/events', label: 'Events', Icon: CalendarIcon },
    { to: '/officer/schedule', label: 'Schedule', Icon: TableIcon },
    { to: '/officer/analytics', label: 'Analytics', Icon: ChartIcon },
  ],
  staff: [
    { to: '/staff', label: 'Home', Icon: HomeIcon, end: true },
    { to: '/staff/tasks', label: 'My tasks', Icon: ChecklistIcon },
    { to: '/staff/events', label: 'My events', Icon: CalendarIcon },
    { to: '/staff/schedule', label: 'Schedule', Icon: TableIcon },
  ],
}

const ROLE_LABELS = {
  admin: 'Administrator',
  officer: 'Officer',
  staff: 'Staff',
}

const ROLE_BADGE = {
  admin: 'bg-emerald-100 text-emerald-800',
  officer: 'bg-amber-100 text-amber-800',
  staff: 'bg-slate-100 text-slate-700',
}

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').filter(Boolean).slice(0, 2).map((s) => s[0]).join('').toUpperCase()
}

export default function AppLayout() {
  const { user, logout } = useAuth()
  const { pathname } = useLocation()
  const items = NAV_BY_ROLE[user.role] || []
  const [menuOpen, setMenuOpen] = useState(false)
  const [pwOpen, setPwOpen] = useState(false)
  const menuRef = useRef(null)

  useEffect(() => {
    if (!menuOpen) return
    function handleClickOutside(e) {
      if (menuRef.current && !menuRef.current.contains(e.target)) {
        setMenuOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [menuOpen])

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <header className="bg-white border-b border-gray-200 sticky top-0 z-20">
        <div className="px-4 md:px-6 h-16 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <img src="/neu-logo.png" alt="NEU" className="w-10 h-10 object-contain" />
            <div className="hidden sm:block">
              <div className="text-sm font-semibold text-gray-900 leading-tight">EMO Tracker</div>
              <div className="text-xs text-gray-500 leading-tight">Events Management Office · New Era University</div>
            </div>
          </div>

          <div className="relative" ref={menuRef}>
            <button
              onClick={() => setMenuOpen((v) => !v)}
              className="flex items-center gap-3 p-1 pl-2 pr-1 rounded-lg hover:bg-gray-100 transition"
            >
              <div className="text-right hidden sm:block">
                <div className="text-sm font-medium text-gray-900 leading-tight">{user.name}</div>
                <div className={`inline-block px-1.5 py-0.5 rounded-md text-[10px] font-semibold ${ROLE_BADGE[user.role]}`}>
                  {ROLE_LABELS[user.role]}
                </div>
              </div>
              <div className="w-9 h-9 rounded-full bg-neu-green text-white font-semibold text-sm flex items-center justify-center">
                {getInitials(user.name)}
              </div>
            </button>

            {menuOpen && (
              <div className="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden z-30">
                <div className="px-4 py-3 border-b border-gray-100">
                  <div className="text-sm font-medium text-gray-900 truncate">{user.name}</div>
                  <div className="text-xs text-gray-500 truncate">{user.email}</div>
                </div>
                <button
                  onClick={() => { setMenuOpen(false); setPwOpen(true); }}
                  className="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" />
                    <path d="M7 11V7a5 5 0 0110 0v4" />
                  </svg>
                  Change password
                </button>
                <button
                  onClick={logout}
                  className="w-full text-left px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 flex items-center gap-2 border-t border-gray-100"
                >
                  <LogOutIcon width={16} height={16} />
                  Log out
                </button>
              </div>
            )}
          </div>
        </div>
      </header>

      <div className="flex flex-1">
        <aside className="hidden md:flex flex-col w-60 bg-white border-r border-gray-200 sticky top-16 self-start h-[calc(100vh-4rem)]">
          <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
            {items.map(({ to, label, Icon, end }) => (
              <NavLink
                key={to}
                to={to}
                end={end}
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition ${
                    isActive
                      ? 'bg-neu-green text-white'
                      : 'text-gray-700 hover:bg-gray-100'
                  }`
                }
              >
                <Icon />
                {label}
              </NavLink>
            ))}
          </nav>
        </aside>

        <main className="flex-1 p-4 md:p-8 pb-24 md:pb-8 min-w-0">
          {/* A crashed page keeps the menu usable; going to another page starts fresh. */}
          <ErrorBoundary key={pathname}>
            <Outlet />
          </ErrorBoundary>
        </main>
      </div>

      <footer className="hidden md:block bg-white border-t border-gray-200 py-3 px-6">
        <div className="text-center text-xs text-gray-500">
          © 2026 EMO Tracker · A Capstone Project at New Era University
        </div>
      </footer>

      <nav className="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-20">
        <div
          className="grid px-2 py-1.5"
          style={{ gridTemplateColumns: `repeat(${items.length}, minmax(0, 1fr))` }}
        >
          {items.map(({ to, label, Icon, end }) => (
            <NavLink
              key={to}
              to={to}
              end={end}
              className={({ isActive }) =>
                `flex flex-col items-center gap-0.5 py-1.5 rounded-lg text-[11px] font-medium ${
                  isActive ? 'text-neu-green' : 'text-gray-500'
                }`
              }
            >
              <Icon />
              {label}
            </NavLink>
          ))}
        </div>
      </nav>

      {pwOpen && <ChangePasswordDialog open onClose={() => setPwOpen(false)} />}
    </div>
  )
}
