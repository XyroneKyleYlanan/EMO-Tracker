import { useEffect, useRef, useState } from 'react'
import api from '../lib/api'
import { downloadFile } from '../lib/download'
import { isServerUnreachable } from '../lib/errors'
import { useEscapeKey } from '../lib/useEscapeKey'
import { useAuth } from '../contexts/auth'
import { useToast } from '../contexts/toast'
import { ReadinessReason } from './ReadinessBadge'
import EventBadges from './EventBadges'
import ClashWarning from './ClashWarning'
import LoadError from './LoadError'
import TaskRow from './TaskRow'
import TaskFormDialog from './TaskFormDialog'
import { CalendarIcon, ClockIcon, MapPinIcon } from './icons'
import { formatDateCompact, formatDateLong, formatDateRange, formatRescheduledFrom, formatTime, formatTimeRange } from '../lib/format'

const ACCEPTED_TYPES = '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png'
// Matches the server's limit (Document::MAX_UPLOAD_KB).
const MAX_UPLOAD_MB = 10

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export default function EventDetailDrawer({ eventId, onClose, onEdit, onDelete, onChanged, canEdit, canDelete }) {
  const { user } = useAuth()
  const toast = useToast()
  const canManageTasks = ['admin', 'officer'].includes(user.role)
  const canManageDocs = ['admin', 'officer'].includes(user.role)
  // Documents are records: officers add them, only the admin deletes them.
  const canDeleteDocs = user.role === 'admin'
  const fileInputRef = useRef(null)

  const [event, setEvent] = useState(null)
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState(null)
  const [taskFormOpen, setTaskFormOpen] = useState(false)
  const [editingTask, setEditingTask] = useState(null)
  const [uploading, setUploading] = useState(false)
  const [uploadError, setUploadError] = useState(null)
  const [generatingReport, setGeneratingReport] = useState(false)

  // The drawer is keyed by event, so it starts out loading; refreshes show the loader again.
  function loadEvent() {
    api.get(`/events/${eventId}`)
      .then((res) => { setEvent(res.data.event); setLoadError(null) })
      .catch((err) => {
        setLoadError(err)
        // Deleted meanwhile: the page behind refreshes so it disappears there too.
        if (err.response?.status === 404) onChanged?.()
      })
      .finally(() => setLoading(false))
  }

  function fetchEvent() {
    if (!eventId) return
    setLoading(true)
    loadEvent()
  }

  useEffect(() => {
    if (eventId) loadEvent()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [eventId])

  useEscapeKey(onClose, !!eventId)

  function handleTaskChanged() {
    fetchEvent()
    onChanged?.()
  }

  function handleAddTask() {
    setEditingTask(null)
    setTaskFormOpen(true)
  }

  function handleEditTask(task) {
    setEditingTask(task)
    setTaskFormOpen(true)
  }

  async function handleDeleteTask(task) {
    if (!window.confirm(`Delete task "${task.name}"?`)) return
    try {
      await api.delete(`/tasks/${task.id}`)
      toast.success('Task deleted.')
      handleTaskChanged()
    } catch {
      toast.error('Failed to delete task.')
    }
  }

  function handleTaskSaved() {
    setTaskFormOpen(false)
    setEditingTask(null)
    handleTaskChanged()
  }

  async function handleFileChange(e) {
    const file = e.target.files[0]
    if (!file) return
    setUploadError(null)
    // Caught here so a big file isn't sent only to be turned away.
    if (file.size > MAX_UPLOAD_MB * 1024 * 1024) {
      const msg = `"${file.name}" is larger than ${MAX_UPLOAD_MB} MB. Choose a smaller file.`
      setUploadError(msg)
      toast.error(msg)
      e.target.value = ''
      return
    }
    setUploading(true)
    try {
      const formData = new FormData()
      formData.append('file', file)
      await api.post(`/events/${eventId}/documents`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      toast.success(`Uploaded "${file.name}".`)
      fetchEvent()
    } catch (err) {
      const msg = isServerUnreachable(err)
        ? "Can't reach the EMO Tracker server, so the file wasn't uploaded."
        : err.response?.data?.errors?.file?.[0] || err.response?.data?.message || 'Upload failed.'
      setUploadError(msg)
      toast.error(msg)
    } finally {
      setUploading(false)
      if (fileInputRef.current) fileInputRef.current.value = ''
    }
  }

  async function handleDownloadDoc(doc) {
    try {
      await downloadFile(`/documents/${doc.id}/download`, doc.file_name)
    } catch {
      toast.error('Failed to download file.')
    }
  }

  async function handleDeleteDoc(doc) {
    if (!window.confirm(`Delete "${doc.file_name}"?`)) return
    try {
      await api.delete(`/documents/${doc.id}`)
      toast.success('Document deleted.')
      fetchEvent()
    } catch {
      toast.error('Failed to delete document.')
    }
  }

  async function handleGenerateReport() {
    setGeneratingReport(true)
    try {
      await downloadFile(`/events/${eventId}/report`, `event-report-${event.id}.pdf`)
      toast.success('Report downloaded.')
    } catch {
      toast.error('Failed to generate report.')
    } finally {
      setGeneratingReport(false)
    }
  }

  if (!eventId) return null

  // Everyone with a task on this event, in name order.
  const people = Object.values(
    Object.fromEntries((event?.tasks || []).filter((t) => t.assignee).map((t) => [t.assignee.id, t.assignee]))
  ).sort((a, b) => a.name.localeCompare(b.name))

  return (
    <>
      <div className="fixed inset-0 z-30 flex">
        <div className="fixed inset-0 bg-black/30" onClick={onClose} aria-hidden="true"></div>

        <div className="ml-auto h-full w-full md:max-w-xl bg-white shadow-xl flex flex-col z-40 relative">
          <header className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div className="text-sm font-semibold text-gray-500">Event details</div>
            <button onClick={onClose} aria-label="Close" title="Close (Esc)" className="text-gray-500 hover:text-gray-800 p-1 rounded-md text-lg leading-none">
              ✕
            </button>
          </header>

          <div className="flex-1 overflow-y-auto px-6 py-5">
            {loadError && !loading ? (
              loadError.response?.status === 404 ? (
                <div role="alert" className="py-10 text-center">
                  <p className="text-sm font-medium text-gray-900">This event no longer exists.</p>
                  <p className="text-sm text-gray-500 mt-1">Someone may have deleted it.</p>
                </div>
              ) : (
                <LoadError error={loadError} onRetry={fetchEvent} />
              )
            ) : loading || !event ? (
              <div className="space-y-3">
                <div className="h-6 bg-gray-200 rounded-md w-2/3 animate-pulse"></div>
                <div className="h-4 bg-gray-100 rounded-md w-full animate-pulse"></div>
                <div className="h-4 bg-gray-100 rounded-md w-5/6 animate-pulse"></div>
              </div>
            ) : (
              <>
                <div className="flex items-start justify-between gap-3 mb-3">
                  <h2 className="text-xl font-semibold text-gray-900">{event.name}</h2>
                  <EventBadges event={event} />
                </div>
                <ReadinessReason readiness={event.readiness} reason={event.readiness_reason} className="-mt-2 mb-4 text-sm" />

                {event.description && (
                  <p className="text-sm text-gray-600 mb-5 whitespace-pre-wrap">{event.description}</p>
                )}

                <dl className="space-y-2.5 text-sm mb-6">
                  <Row icon={CalendarIcon} label={formatDateRange(event.event_date, event.end_date, formatDateLong)} />
                  <Row icon={ClockIcon} label={formatTimeRange(event.event_time, event.end_time) || 'Time to be announced'} />
                  {event.original_date && (
                    <Row
                      icon={null}
                      label={`${formatRescheduledFrom(event)}${event.original_time && event.original_date !== event.event_date ? `, ${formatTime(event.original_time)}` : ''}`}
                      muted
                    />
                  )}
                  <Row
                    icon={MapPinIcon}
                    label={
                      <span className="inline-flex items-center gap-2">
                        {event.location || 'Venue to be announced'}
                        {event.venue?.building && (
                          <span
                            className="text-[10px] font-semibold px-1.5 py-0.5 rounded-md text-gray-800"
                            style={{ backgroundColor: event.venue.building.color }}
                          >
                            {event.venue.building.name}
                          </span>
                        )}
                      </span>
                    }
                  />
                  <Row icon={null} label={event.event_type === 'external' ? 'External event (outside organizer)' : 'Internal event (NEU)'} />
                  {event.department && <Row icon={null} label={`${event.event_type === 'external' ? 'Organizer' : 'Department'}: ${event.department}`} />}
                  {event.control_number && <Row icon={null} label={`Control #: ${event.control_number}`} />}
                  {event.remarks && <Row icon={null} label={`Remarks: ${event.remarks}`} />}
                  {event.creator && (
                    <Row icon={null} label={`Created by ${event.creator.name}`} muted />
                  )}
                </dl>

                <ClashWarning
                  clashes={event.clashes}
                  intro={(n) => `Overlaps with ${n === 1 ? 'another booking' : `${n} other bookings`} at the same venue:`}
                  className="-mt-2 mb-6"
                />

                <Section title={`People (${people.length})`}>
                  {people.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                      {people.map((p) => (
                        <span key={p.id} className="text-xs px-2.5 py-1 bg-gray-100 rounded-md text-gray-700">
                          {p.name}
                        </span>
                      ))}
                    </div>
                  ) : (
                    <Empty text="No one has a task on this event yet." />
                  )}
                </Section>

                <Section
                  title={`Tasks (${event.tasks?.length || 0})`}
                  action={
                    canManageTasks &&
                    event.status !== 'cancelled' &&
                    (event.status !== 'completed' || user.role === 'admin') && (
                      <button
                        onClick={handleAddTask}
                        className="text-xs font-medium text-neu-green hover:underline"
                      >
                        + Add task
                      </button>
                    )
                  }
                >
                  {!event.needs_preparation && event.status === 'upcoming' && (
                    <div className="mb-3 p-2.5 bg-cyan-50 border border-cyan-100 rounded-lg text-xs text-cyan-900">
                      <span className="font-semibold">Schedule only.</span>{' '}
                      The EMO isn't tracking this event's readiness. Adding a task starts tracking it.
                    </div>
                  )}
                  {event.status === 'cancelled' && (
                    <div className="mb-3 p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-700">
                      <span className="font-semibold">This event is cancelled.</span>{' '}
                      It stays on the schedule, and its tasks are on hold.{' '}
                      {user.role === 'admin' ? 'To restore it, edit the event and untick "cancelled".' : 'Only an administrator can restore it.'}
                    </div>
                  )}
                  {event.status === 'completed' && (
                    <div className="mb-3 p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                      <span className="font-semibold">This event is completed.</span>{' '}
                      {user.role === 'admin'
                        ? 'As an administrator, you can still make corrections to historical records.'
                        : 'Task records are locked. Contact an administrator if a correction is needed.'}
                    </div>
                  )}
                  {event.tasks?.length > 0 ? (
                    <div className="space-y-2">
                      {event.tasks.map((task) => (
                        <TaskRow
                          key={task.id}
                          task={task}
                          currentUser={user}
                          eventStatus={event.status}
                          onChanged={handleTaskChanged}
                          onEdit={handleEditTask}
                          onDelete={handleDeleteTask}
                        />
                      ))}
                    </div>
                  ) : (
                    <Empty text="No tasks yet for this event." />
                  )}
                </Section>

                <Section
                  title={`Documents (${event.documents?.length || 0})`}
                  action={canManageDocs && (
                    <>
                      <input
                        type="file"
                        ref={fileInputRef}
                        accept={ACCEPTED_TYPES}
                        onChange={handleFileChange}
                        disabled={uploading}
                        className="hidden"
                      />
                      <button
                        onClick={() => fileInputRef.current?.click()}
                        disabled={uploading}
                        className="text-xs font-medium text-neu-green hover:underline disabled:opacity-60"
                      >
                        {uploading ? 'Uploading...' : '+ Upload file'}
                      </button>
                    </>
                  )}
                >
                  {uploadError && (
                    <div className="mb-2 p-2 bg-rose-50 border border-rose-200 rounded-md text-xs text-rose-800">
                      {uploadError}
                    </div>
                  )}
                  {event.documents?.length > 0 ? (
                    <div className="space-y-2">
                      {event.documents.map((doc) => (
                        <div key={doc.id} className="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200">
                          <div className="flex-shrink-0 w-9 h-9 rounded-md bg-gray-100 flex items-center justify-center text-gray-500 text-[10px] font-bold uppercase">
                            {doc.file_name.split('.').pop()?.slice(0, 4) || 'FILE'}
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="text-sm font-medium text-gray-900 truncate">{doc.file_name}</div>
                            <div className="text-xs text-gray-500 mt-0.5 truncate">
                              {formatBytes(doc.file_size)} · {doc.uploader?.name || 'Unknown'} · {formatDateCompact(doc.created_at)}
                            </div>
                          </div>
                          <div className="flex items-center gap-1 flex-shrink-0">
                            <button
                              onClick={() => handleDownloadDoc(doc)}
                              className="text-gray-400 hover:text-neu-green p-1 rounded-md hover:bg-gray-100"
                              title="Download"
                            >
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" />
                              </svg>
                            </button>
                            {canDeleteDocs && (
                              <button
                                onClick={() => handleDeleteDoc(doc)}
                                className="text-gray-400 hover:text-rose-600 p-1 rounded-md hover:bg-gray-100"
                                title="Delete"
                              >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                                  <polyline points="3 6 5 6 21 6" />
                                  <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                                </svg>
                              </button>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <Empty text="No documents attached." />
                  )}
                  {canManageDocs && (
                    <div className="text-xs text-gray-500 mt-2">
                      PDF, Word, Excel, JPG or PNG, up to {MAX_UPLOAD_MB} MB.
                      {!canDeleteDocs && ' Only the administrator can delete files.'}
                    </div>
                  )}
                </Section>
              </>
            )}
          </div>

          {event && !loadError && (
            <footer className="px-6 py-3 border-t border-gray-200 flex items-center justify-between gap-2">
              <button
                onClick={handleGenerateReport}
                disabled={generatingReport}
                className="text-sm text-neu-green hover:bg-emerald-50 px-3 py-2 rounded-lg font-medium transition disabled:opacity-60 flex items-center gap-1.5"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                </svg>
                {generatingReport ? 'Preparing report...' : 'Download report'}
              </button>

              <div className="flex items-center gap-2">
                {canDelete && (
                  <button
                    onClick={() => onDelete(event)}
                    className="text-sm text-rose-600 hover:bg-rose-50 px-3 py-2 rounded-lg transition"
                  >
                    Delete
                  </button>
                )}
                {canEdit && (event.status !== 'completed' || user.role === 'admin') && (
                  <button
                    onClick={() => onEdit(event)}
                    className="text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition"
                  >
                    Edit event
                  </button>
                )}
              </div>
            </footer>
          )}
        </div>
      </div>

      {taskFormOpen && (
        <TaskFormDialog
          key={editingTask?.id ?? 'new'}
          open
          eventId={eventId}
          eventLastDay={event?.end_date || event?.event_date}
          task={editingTask}
          onClose={() => { setTaskFormOpen(false); setEditingTask(null); }}
          onSaved={handleTaskSaved}
        />
      )}
    </>
  )
}

function Row({ icon: Icon, label, muted }) {
  return (
    <div className={`flex items-center gap-2 ${muted ? 'text-gray-500' : 'text-gray-700'}`}>
      {Icon ? <Icon width={16} height={16} /> : <span className="w-4"></span>}
      <span>{label}</span>
    </div>
  )
}

function Section({ title, action, children }) {
  return (
    <div className="mb-6">
      <div className="flex items-center justify-between mb-2">
        <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
        {action}
      </div>
      {children}
    </div>
  )
}

function Empty({ text }) {
  return <div className="text-xs text-gray-500 italic">{text}</div>
}
