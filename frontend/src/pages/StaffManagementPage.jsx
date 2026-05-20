import { useEffect, useState } from 'react'
import api from '../lib/api'
import { useAuth } from '../contexts/AuthContext'
import { useToast } from '../contexts/ToastContext'
import UserFormDialog from '../components/UserFormDialog'
import { formatDateCompact } from '../lib/format'

const ROLE_LABELS = {
  admin: 'Administrator',
  officer: 'Officer',
  staff: 'Staff',
}

const ROLE_COLORS = {
  admin: 'bg-emerald-100 text-emerald-800',
  officer: 'bg-amber-100 text-amber-800',
  staff: 'bg-slate-100 text-slate-700',
}

export default function StaffManagementPage() {
  const { user: currentUser } = useAuth()
  const toast = useToast()

  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(true)
  const [roleFilter, setRoleFilter] = useState('all')
  const [statusFilter, setStatusFilter] = useState('active')
  const [formOpen, setFormOpen] = useState(false)
  const [editingUser, setEditingUser] = useState(null)

  function fetchUsers() {
    setLoading(true)
    api.get('/users')
      .then((res) => setUsers(res.data.users || []))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    fetchUsers()
  }, [])

  const filtered = users.filter((u) => {
    if (roleFilter !== 'all' && u.role !== roleFilter) return false
    if (statusFilter === 'active' && !u.is_active) return false
    if (statusFilter === 'inactive' && u.is_active) return false
    return true
  })

  function handleNew() {
    setEditingUser(null)
    setFormOpen(true)
  }

  function handleEdit(u) {
    setEditingUser(u)
    setFormOpen(true)
  }

  async function handleDeactivate(u) {
    if (u.id === currentUser.id) {
      toast.error('You cannot deactivate your own account.')
      return
    }
    if (!window.confirm(`Deactivate ${u.name}? They will no longer be able to log in.`)) return
    try {
      await api.delete(`/users/${u.id}`)
      toast.success(`${u.name} deactivated.`)
      fetchUsers()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to deactivate user.')
    }
  }

  async function handleReactivate(u) {
    try {
      await api.put(`/users/${u.id}`, { is_active: true })
      toast.success(`${u.name} reactivated.`)
      fetchUsers()
    } catch {
      toast.error('Failed to reactivate user.')
    }
  }

  function handleSaved() {
    setFormOpen(false)
    setEditingUser(null)
    fetchUsers()
  }

  const counts = {
    total: users.length,
    admin: users.filter((u) => u.role === 'admin').length,
    officer: users.filter((u) => u.role === 'officer').length,
    staff: users.filter((u) => u.role === 'staff').length,
  }

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Staff Management</h1>
          <p className="text-sm text-gray-500 mt-1">
            {counts.total} total · {counts.admin} admin · {counts.officer} officer · {counts.staff} staff
          </p>
        </div>
        <button
          onClick={handleNew}
          className="bg-neu-green hover:bg-neu-green-dark text-white text-sm font-medium px-4 py-2 rounded-lg transition flex-shrink-0"
        >
          + New user
        </button>
      </div>

      <div className="flex flex-wrap items-center gap-2 mb-4">
        <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1">
          <FilterBtn active={roleFilter === 'all'} onClick={() => setRoleFilter('all')} label="All roles" />
          <FilterBtn active={roleFilter === 'admin'} onClick={() => setRoleFilter('admin')} label="Admin" />
          <FilterBtn active={roleFilter === 'officer'} onClick={() => setRoleFilter('officer')} label="Officer" />
          <FilterBtn active={roleFilter === 'staff'} onClick={() => setRoleFilter('staff')} label="Staff" />
        </div>

        <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1">
          <FilterBtn active={statusFilter === 'active'} onClick={() => setStatusFilter('active')} label="Active" />
          <FilterBtn active={statusFilter === 'inactive'} onClick={() => setStatusFilter('inactive')} label="Deactivated" />
          <FilterBtn active={statusFilter === 'all'} onClick={() => setStatusFilter('all')} label="All" />
        </div>
      </div>

      {loading ? (
        <div className="space-y-2">
          {[0, 1, 2, 3, 4].map((i) => (
            <div key={i} className="bg-white rounded-xl border border-gray-200 p-4 h-16 animate-pulse"></div>
          ))}
        </div>
      ) : filtered.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 border-dashed p-10 text-center text-sm text-gray-500">
          No users match these filters.
        </div>
      ) : (
        <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
          <div className="hidden md:grid grid-cols-[minmax(0,2fr)_minmax(0,2fr)_140px_120px_180px] gap-4 px-5 py-3 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 border-b border-gray-200">
            <div>Name</div>
            <div>Email</div>
            <div>Role</div>
            <div>Status</div>
            <div></div>
          </div>

          {filtered.map((u) => (
            <div
              key={u.id}
              className="grid grid-cols-1 md:grid-cols-[minmax(0,2fr)_minmax(0,2fr)_140px_120px_180px] gap-2 md:gap-4 px-5 py-4 border-b border-gray-100 last:border-0 items-center"
            >
              <div className="font-medium text-gray-900 flex items-center gap-2">
                {u.name}
                {u.id === currentUser.id && (
                  <span className="text-[10px] uppercase tracking-wide bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">You</span>
                )}
              </div>
              <div className="text-sm text-gray-600 truncate">{u.email}</div>
              <div>
                <span className={`inline-block text-xs font-medium px-2 py-0.5 rounded-md ${ROLE_COLORS[u.role]}`}>
                  {ROLE_LABELS[u.role]}
                </span>
              </div>
              <div className="text-xs">
                {u.is_active ? (
                  <span className="inline-flex items-center gap-1.5 text-emerald-700">
                    <span className="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                    Active
                  </span>
                ) : (
                  <span className="inline-flex items-center gap-1.5 text-gray-500">
                    <span className="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                    Deactivated
                  </span>
                )}
              </div>
              <div className="flex items-center gap-1 justify-end">
                <button
                  onClick={() => handleEdit(u)}
                  className="text-sm text-gray-600 hover:bg-gray-100 px-2.5 py-1 rounded"
                >
                  Edit
                </button>
                {u.id !== currentUser.id && (
                  u.is_active ? (
                    <button
                      onClick={() => handleDeactivate(u)}
                      className="text-sm text-rose-600 hover:bg-rose-50 px-2.5 py-1 rounded"
                    >
                      Deactivate
                    </button>
                  ) : (
                    <button
                      onClick={() => handleReactivate(u)}
                      className="text-sm text-emerald-700 hover:bg-emerald-50 px-2.5 py-1 rounded"
                    >
                      Reactivate
                    </button>
                  )
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      <UserFormDialog
        open={formOpen}
        user={editingUser}
        onClose={() => { setFormOpen(false); setEditingUser(null); }}
        onSaved={handleSaved}
      />
    </div>
  )
}

function FilterBtn({ active, onClick, label }) {
  return (
    <button
      onClick={onClick}
      className={`px-3 py-1 rounded-md text-sm font-medium transition ${
        active ? 'bg-neu-green text-white' : 'text-gray-600 hover:bg-gray-100'
      }`}
    >
      {label}
    </button>
  )
}
