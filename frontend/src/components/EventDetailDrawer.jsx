import { useEffect, useRef, useState } from 'react'
import api from '../lib/api'
import { downloadFile } from '../lib/download'
import { useAuth } from '../contexts/AuthContext'
import { useToast } from '../contexts/ToastContext'
import ReadinessBadge from './ReadinessBadge'
import TaskRow from './TaskRow'
import TaskFormDialog from './TaskFormDialog'
import StaffPickerDialog from './StaffPickerDialog'
import { CalendarIcon, ClockIcon, MapPinIcon } from './icons'
import { formatDateCompact, formatDateLong, formatDateRange, formatTimeRange } from '../lib/format'

const ACCEPTED_TYPES = '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png'

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
  const fileInputRef = useRef(null)

  const [event, setEvent] = useState(null)
  const [loading, setLoading] = useState(true)
  const [taskFormOpen, setTaskFormOpen] = useState(false)
  const [editingTask, setEditingTask] = useState(null)
  const [uploading, setUploading] = useState(false)
  const [uploadError, setUploadError] = useState(null)
  const [generatingReport, setGeneratingReport] = useState(false)
  const [staffPickerOpen, setStaffPickerOpen] = useState(false)

  function fetchEvent() {
    if (!eventId) return
    setLoading(true)
    api.get(`/events/${eventId}`)
      .then((res) => setEvent(res.data.event))
      .finally(() => setLoading(false))
  }

  useEffect(() => {
    fetchEvent()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [eventId])

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
      const msg = err.response?.data?.errors?.file?.[0] || err.response?.data?.message || 'Upload failed.'
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

  return (
    <>
      <div className="fixed inset-0 z-30 flex">
        <div className="fixed inset-0 bg-black/30" onClick={onClose} aria-hidden="true"></div>

        <div className="ml-auto h-full w-full md:max-w-xl bg-white shadow-xl flex flex-col z-40 relative">
          <header className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div className="text-sm font-semibold text-gray-500">Event details</div>
            <button onClick={onClose} className="text-gray-400 hover:text-gray-700 p-1 rounded-md text-lg leading-none">
              ✕
            </button>
          </header>

          <div className="flex-1 overflow-y-auto px-6 py-5">
            {loading || !event ? (
              <div className="space-y-3">
                <div className="h-6 bg-gray-200 rounded w-2/3 animate-pulse"></div>
                <div className="h-4 bg-gray-100 rounded w-full animate-pulse"></div>
                <div className="h-4 bg-gray-100 rounded w-5/6 animate-pulse"></div>
              </div>
            ) : (
              <>
                <div className="flex items-start justify-between gap-3 mb-3">
                  <h2 className="text-xl font-semibold text-gray-900">{event.name}</h2>
                  <ReadinessBadge readiness={event.readiness} />
                </div>

                {event.description && (
                  <p className="text-sm text-gray-600 mb-5 whitespace-pre-wrap">{event.description}</p>
                )}

                <dl className="space-y-2.5 text-sm mb-6">
                  <Row icon={CalendarIcon} label={formatDateRange(event.event_date, event.end_date, formatDateLong)} />
                  <Row icon={ClockIcon} label={formatTimeRange(event.event_time, event.end_time) || 'Time to be announced'} />
                  <Row
                    icon={MapPinIcon}
                    label={
                      <span className="inline-flex items-center gap-2">
                        {event.location || 'Venue to be announced'}
                        {event.venue?.building && (
                          <span
                            className="text-[10px] font-semibold px-1.5 py-0.5 rounded text-gray-800"
                            style={{ backgroundColor: event.venue.building.color }}
                          >
                            {event.venue.building.name}
                          </span>
                        )}
                      </span>
                    }
                  />
                  {event.department && <Row icon={null} label={`Department: ${event.department}`} />}
                  {event.control_number && <Row icon={null} label={`Control #: ${event.control_number}`} />}
                  {event.remarks && <Row icon={null} label={`Remarks: ${event.remarks}`} />}
                  {event.budget && (
                    <Row icon={null} label={`Budget: ₱${Number(event.budget).toLocaleString()}`} />
                  )}
                  {event.creator && (
                    <Row icon={null} label={`Created by ${event.creator.name}`} muted />
                  )}
                </dl>

                <Section
                  title={`Assigned Staff (${event.staff?.length || 0})`}
                  action={
                    canManageTasks &&
                    (event.status !== 'completed' || user.role === 'admin') && (
                      <button
                        onClick={() => setStaffPickerOpen(true)}
                        className="text-xs font-medium text-neu-green hover:underline"
                      >
                        Edit
                      </button>
                    )
                  }
                >
                  {event.staff?.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                      {event.staff.map((s) => (
                        <span key={s.id} className="text-xs px-2.5 py-1 bg-gray-100 rounded-md text-gray-700">
                          {s.name}
                        </span>
                      ))}
                    </div>
                  ) : (
                    <Empty text="No staff assigned at event level." />
                  )}
                </Section>

                <Section
                  title={`Tasks (${event.tasks?.length || 0})`}
                  action={
                    canManageTasks &&
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
                    <div className="mb-2 p-2 bg-rose-50 border border-rose-200 rounded text-xs text-rose-800">
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
                              className="text-gray-400 hover:text-neu-green p-1 rounded hover:bg-gray-100"
                              title="Download"
                            >
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" />
                              </svg>
                            </button>
                            {canManageDocs && (
                              <button
                                onClick={() => handleDeleteDoc(doc)}
                                className="text-gray-400 hover:text-rose-600 p-1 rounded hover:bg-gray-100"
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
                    <div className="text-[10px] text-gray-400 mt-2">
                      Accepted: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG · max 10 MB
                    </div>
                  )}
                </Section>
              </>
            )}
          </div>

          {event && (
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
                {generatingReport ? 'Generating...' : 'Generate Report'}
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

      {staffPickerOpen && event && (
        <StaffPickerDialog
          event={event}
          onClose={() => setStaffPickerOpen(false)}
          onSaved={() => {
            setStaffPickerOpen(false)
            toast.success('Staff updated.')
            handleTaskChanged()
          }}
        />
      )}

      <TaskFormDialog
        open={taskFormOpen}
        eventId={eventId}
        task={editingTask}
        onClose={() => { setTaskFormOpen(false); setEditingTask(null); }}
        onSaved={handleTaskSaved}
      />
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
        <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-500">{title}</h3>
        {action}
      </div>
      {children}
    </div>
  )
}

function Empty({ text }) {
  return <div className="text-xs text-gray-400 italic">{text}</div>
}
