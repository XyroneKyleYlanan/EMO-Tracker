import { useState } from 'react'
import api from '../lib/api'
import { useToast } from '../contexts/toast'
import { formatDateCompact, formatDateLong } from '../lib/format'
import { useEscapeKey } from '../lib/useEscapeKey'

const STATUS_OPTIONS = [
  { value: 'pending', label: 'Pending', color: 'bg-slate-100 text-slate-700 border-slate-200' },
  { value: 'in_progress', label: 'In Progress', color: 'bg-amber-100 text-amber-800 border-amber-200' },
  { value: 'done', label: 'Done', color: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
]

const PRIORITY_COLORS = {
  low: 'text-slate-500',
  medium: 'text-amber-600',
  high: 'text-rose-600',
}

export default function TaskRow({ task, currentUser, onChanged, onEdit, onDelete, showEvent = false, eventStatus }) {
  const toast = useToast()
  const [updating, setUpdating] = useState(false)
  const [selectKey, setSelectKey] = useState(0)
  const [detailOpen, setDetailOpen] = useState(false)
  const isOwner = task.assigned_to === currentUser.id
  const isAdmin = currentUser.role === 'admin'
  const canManageRole = ['admin', 'officer'].includes(currentUser.role)
  const isEventCompleted = (eventStatus ?? task.event?.status) === 'completed'
  const isEventCancelled = (eventStatus ?? task.event?.status) === 'cancelled'
  const lockedByCompletion = isEventCompleted && !isAdmin
  const canChangeStatus = (isOwner || canManageRole) && !lockedByCompletion
  const canManageTask = canManageRole && !lockedByCompletion
  const statusCfg = STATUS_OPTIONS.find((s) => s.value === task.status) || STATUS_OPTIONS[0]

  async function handleStatusChange(newStatus) {
    if (newStatus === task.status) return

    if (isEventCompleted && isAdmin) {
      const currentLabel = STATUS_OPTIONS.find((s) => s.value === task.status)?.label || task.status
      const newLabel = STATUS_OPTIONS.find((s) => s.value === newStatus)?.label || newStatus
      const confirmed = window.confirm(
        `This task belongs to a completed event. Changing its status will alter historical records.\n\nChange "${task.name}" from ${currentLabel} to ${newLabel}?`
      )
      if (!confirmed) {
        setSelectKey((k) => k + 1)
        return
      }
    }

    setUpdating(true)
    try {
      await api.patch(`/tasks/${task.id}/status`, { status: newStatus })
      toast.success(`Task marked as ${STATUS_OPTIONS.find((s) => s.value === newStatus).label}.`)
      onChanged?.()
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to update status.')
    } finally {
      setUpdating(false)
    }
  }

  return (
    <>
      <div className={`flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition ${isEventCompleted ? 'opacity-90' : isEventCancelled ? 'opacity-70' : ''}`}>
        <button
          type="button"
          onClick={() => setDetailOpen(true)}
          className="group flex-1 min-w-0 text-left"
          title="View task details"
        >
          <div className="text-sm font-medium text-gray-900 truncate group-hover:text-neu-green group-hover:underline">
            {task.name}
          </div>
          <div className="text-xs text-gray-500 mt-0.5 flex items-center gap-2 flex-wrap">
            {showEvent && task.event && (
              <span className="truncate">{task.event.name} ·</span>
            )}
            <span>{task.assignee ? task.assignee.name : 'Unassigned'}</span>
            <span>·</span>
            <span>Due {formatDateCompact(task.due_date)}</span>
            <span className={`text-[10px] font-semibold uppercase tracking-wide ${PRIORITY_COLORS[task.priority]}`}>
              {task.priority}
            </span>
            {isEventCompleted && (
              <span className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                · Locked
              </span>
            )}
            {isEventCancelled && (
              <span className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                · Event cancelled
              </span>
            )}
          </div>
        </button>

        <div className="flex items-center gap-1 flex-shrink-0">
          {canChangeStatus ? (
            <select
              key={selectKey}
              value={task.status}
              onChange={(e) => handleStatusChange(e.target.value)}
              disabled={updating}
              className={`text-xs font-medium rounded-md border px-2 py-1 cursor-pointer focus:outline-none focus:ring-2 focus:ring-neu-green ${statusCfg.color}`}
            >
              {STATUS_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
          ) : (
            <span
              className={`text-xs font-medium px-2 py-1 rounded-md border ${statusCfg.color}`}
              title={lockedByCompletion ? 'This event is completed. Only an administrator can modify tasks.' : undefined}
            >
              {statusCfg.label}
            </span>
          )}

          {canManageTask && onEdit && (
            <button
              onClick={() => onEdit(task)}
              className="text-gray-400 hover:text-neu-green p-1 rounded-md hover:bg-gray-100"
              title="Edit task"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
              </svg>
            </button>
          )}

          {canManageTask && onDelete && (
            <button
              onClick={() => onDelete(task)}
              className="text-gray-400 hover:text-rose-600 p-1 rounded-md hover:bg-gray-100"
              title="Delete task"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                <polyline points="3 6 5 6 21 6" />
                <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
              </svg>
            </button>
          )}
        </div>
      </div>

      {detailOpen && (
        <TaskDetailDialog
          task={task}
          statusCfg={statusCfg}
          locked={lockedByCompletion}
          onClose={() => setDetailOpen(false)}
          onEdit={canManageTask && onEdit ? () => { setDetailOpen(false); onEdit(task) } : null}
        />
      )}
    </>
  )
}

function TaskDetailDialog({ task, statusCfg, locked, onClose, onEdit }) {
  useEscapeKey(onClose)

  return (
    <div className="fixed inset-0 z-50 flex items-start md:items-center justify-center p-4 md:p-6">
      <div className="fixed inset-0 bg-black/40" onClick={onClose}></div>

      <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col">
        <header className="px-6 py-4 border-b border-gray-200 flex items-start justify-between gap-3">
          <h2 className="text-lg font-semibold text-gray-900 break-words min-w-0">{task.name}</h2>
          <span className={`text-xs font-medium px-2 py-1 rounded-md border flex-shrink-0 ${statusCfg.color}`}>
            {statusCfg.label}
          </span>
        </header>

        <div className="flex-1 overflow-y-auto px-6 py-5 space-y-5">
          <dl className="grid grid-cols-[110px_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm">
            {task.event && (
              <>
                <dt className="text-gray-500">Event</dt>
                <dd className="text-gray-900">{task.event.name}</dd>
              </>
            )}
            <dt className="text-gray-500">Assigned to</dt>
            <dd className="text-gray-900">{task.assignee ? task.assignee.name : 'Unassigned'}</dd>
            <dt className="text-gray-500">Due</dt>
            <dd className="text-gray-900">{formatDateLong(task.due_date)}</dd>
            <dt className="text-gray-500">Priority</dt>
            <dd className={`font-semibold uppercase text-xs tracking-wide self-center ${PRIORITY_COLORS[task.priority]}`}>
              {task.priority}
            </dd>
          </dl>

          <div>
            <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Description</h3>
            {task.description ? (
              <p className="text-sm text-gray-700 whitespace-pre-wrap break-words">{task.description}</p>
            ) : (
              <p className="text-xs text-gray-500 italic">No description.</p>
            )}
          </div>

          {locked && (
            <div className="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
              This event is completed, so this task is locked. Contact an administrator if a correction is needed.
            </div>
          )}
        </div>

        <footer className="px-6 py-3 border-t border-gray-200 flex items-center justify-end gap-2">
          <button
            type="button"
            onClick={onClose}
            className="text-sm text-gray-600 hover:bg-gray-100 px-3 py-2 rounded-lg transition"
          >
            Close
          </button>
          {onEdit && (
            <button
              type="button"
              onClick={onEdit}
              className="text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition"
            >
              Edit task
            </button>
          )}
        </footer>
      </div>
    </div>
  )
}
