import { useEffect, useState } from 'react'
import api from '../lib/api'
import ClashWarning from './ClashWarning'
import { AlertIcon } from './icons'
import { formatDayMonth, formatTime } from '../lib/format'
import { useEscapeKey } from '../lib/useEscapeKey'

const NEW_VENUE = '__new'

const EVENT_TYPES = [
  { value: 'internal', label: 'Internal', hint: 'An NEU event' },
  { value: 'external', label: 'External', hint: 'Organized by an outside group' },
]

function dayAfter(isoDate) {
  const [y, m, d] = isoDate.split('-').map(Number)
  const next = new Date(y, m - 1, d + 1)
  return `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-${String(next.getDate()).padStart(2, '0')}`
}

function initialForm(event) {
  return {
    name: event?.name || '',
    description: event?.description || '',
    event_type: event?.event_type || 'internal',
    department: event?.department || '',
    event_date: event?.event_date || '',
    end_date: event?.end_date || '',
    event_time: event?.event_time?.slice(0, 5) || '',
    end_time: event?.end_time?.slice(0, 5) || '',
    venue_id: event?.venue_id ? String(event.venue_id) : '',
    venue_details: event?.venue_details || '',
    control_number: event?.control_number || '',
    remarks: event?.remarks || '',
    needs_preparation: event?.needs_preparation ?? false,
    cancelled: event?.status === 'cancelled',
    rescheduled: null,
  }
}

// Mount this only while it's open (parents render it conditionally), so every
// opening starts from the event it was given.
export default function EventFormDialog({ event, onClose, onSaved }) {
  const [form, setForm] = useState(() => initialForm(event))
  // Most events are one day, so "Until" only shows when it's needed, and
  // unticking it really clears the end date (date inputs have no clear button).
  const [multiDay, setMultiDay] = useState(() => !!event?.end_date && event.end_date !== event.event_date)
  const [venues, setVenues] = useState([])
  const [buildings, setBuildings] = useState([])
  const [departments, setDepartments] = useState([])
  const [newVenue, setNewVenue] = useState({ name: '', building_id: '' })
  const [venueError, setVenueError] = useState(null)
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  useEscapeKey(onClose)

  useEffect(() => {
    api.get('/venues').then((res) => {
      setVenues(res.data.venues || [])
      setBuildings(res.data.buildings || [])
    })
    api.get('/departments').then((res) => setDepartments(res.data.departments || []))
  }, [])

  // Double-booking check while the venue, date and time are being chosen.
  // Only answers for the current inputs count, so an old reply can't show up late.
  const venueChosen = form.venue_id && form.venue_id !== NEW_VENUE
  const clashQuery = venueChosen && form.event_date && !form.cancelled
    ? new URLSearchParams(Object.entries({
        venue_id: form.venue_id,
        venue_details: form.venue_details.trim(),
        event_date: form.event_date,
        end_date: multiDay ? form.end_date : '',
        event_time: form.event_time,
        end_time: form.end_time,
        ignore: event?.id ?? '',
      }).filter(([, value]) => value !== '')).toString()
    : null
  const [clashResult, setClashResult] = useState({ query: null, clashes: [] })
  useEffect(() => {
    if (!clashQuery) return
    let current = true
    const timer = setTimeout(() => {
      api.get(`/event-clashes?${clashQuery}`)
        .then((res) => { if (current) setClashResult({ query: clashQuery, clashes: res.data.clashes || [] }) })
        .catch(() => {})
    }, 400)
    return () => { current = false; clearTimeout(timer) }
  }, [clashQuery])
  const clashes = clashResult.query === clashQuery ? clashResult.clashes : []

  function update(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }))
  }

  function toggleMultiDay(checked) {
    setMultiDay(checked)
    update('end_date', checked ? form.end_date || (form.event_date ? dayAfter(form.event_date) : '') : '')
  }

  async function addVenue() {
    setVenueError(null)
    try {
      const res = await api.post('/venues', {
        name: newVenue.name.trim(),
        building_id: newVenue.building_id ? Number(newVenue.building_id) : null,
      })
      const venue = res.data.venue
      setVenues((prev) => [...prev, venue].sort((a, b) => a.name.localeCompare(b.name)))
      update('venue_id', String(venue.id))
      setNewVenue({ name: '', building_id: '' })
    } catch (err) {
      setVenueError(err.response?.data?.errors?.name?.[0] || 'Could not add the venue.')
    }
  }

  // Changing when an existing event starts is either a reschedule or a fix.
  // Already-rescheduled events keep their original date either way.
  const moved = !!event && (form.event_date !== event.event_date || form.event_time !== (event.event_time?.slice(0, 5) || ''))
  const askReschedule = moved && !event.original_date

  async function handleSubmit(e) {
    e.preventDefault()
    if (askReschedule && form.rescheduled === null) {
      setErrors({ rescheduled: ['Choose whether the event was moved or the date was entered wrong.'] })
      return
    }
    setErrors({})
    setSubmitting(true)
    try {
      const payload = {
        name: form.name,
        description: form.description || null,
        event_type: form.event_type,
        department: form.department.trim() || null,
        event_date: form.event_date,
        end_date: multiDay && form.end_date ? form.end_date : null,
        event_time: form.event_time || null,
        end_time: form.end_time || null,
        venue_id: form.venue_id && form.venue_id !== NEW_VENUE ? Number(form.venue_id) : null,
        venue_details: form.venue_details.trim() || null,
        control_number: form.control_number.trim() || null,
        remarks: form.remarks.trim() || null,
        needs_preparation: form.needs_preparation,
      }
      if (event) payload.cancelled = form.cancelled
      if (moved) payload.rescheduled = askReschedule ? form.rescheduled : true
      const res = event
        ? await api.put(`/events/${event.id}`, payload)
        : await api.post('/events', payload)
      onSaved(res.data.event)
    } catch (err) {
      setErrors(err.response?.data?.errors || { _: [err.response?.data?.message || 'Failed to save.'] })
    } finally {
      setSubmitting(false)
    }
  }

  // Venues grouped under their building, like the colors in the schedule.
  const grouped = buildings
    .map((b) => ({ label: b.name, venues: venues.filter((v) => v.building_id === b.id) }))
    .filter((g) => g.venues.length > 0)
  const unassigned = venues.filter((v) => !v.building_id)
  const input = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent'
  // Safari fills empty date/time boxes with today's date and the current time,
  // which look like real values. Gray marks them as "not set yet".
  const dateTime = (value) => `${input} ${value ? 'text-gray-900' : 'text-gray-500'}`

  return (
    <div className="fixed inset-0 z-40 flex items-start md:items-center justify-center p-4 md:p-6">
      <div className="fixed inset-0 bg-black/40" onClick={onClose}></div>

      <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <header className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">
            {event ? 'Edit event' : 'New event'}
          </h2>
        </header>

        <form id="event-form" onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-6 py-5 space-y-4">
          <Field label="Event" htmlFor="event-name" error={errors.name?.[0]}>
            <input id="event-name" type="text" required value={form.name} onChange={(e) => update('name', e.target.value)} className={input} />
          </Field>

          <Field label="Type" error={errors.event_type?.[0]}>
            <div className="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Event type">
              {EVENT_TYPES.map((t) => (
                <button
                  key={t.value}
                  type="button"
                  role="radio"
                  aria-checked={form.event_type === t.value}
                  onClick={() => update('event_type', t.value)}
                  className={`text-left px-3 py-2 rounded-lg border transition ${
                    form.event_type === t.value ? 'border-neu-green bg-emerald-50/60 ring-1 ring-neu-green' : 'border-gray-300 hover:bg-gray-50'
                  }`}
                >
                  <span className="block text-sm font-medium text-gray-900">{t.label}</span>
                  <span className="block text-xs text-gray-500">{t.hint}</span>
                </button>
              ))}
            </div>
          </Field>

          <Field label={form.event_type === 'external' ? 'Organizer' : 'Department'} htmlFor="event-department" error={errors.department?.[0]} optional>
            <input
              id="event-department"
              type="text"
              list="department-suggestions"
              value={form.department}
              onChange={(e) => update('department', e.target.value)}
              placeholder={form.event_type === 'external' ? "e.g. the organization's name" : 'e.g. CAS, College of Nursing'}
              className={input}
            />
            <datalist id="department-suggestions">
              {departments.map((d) => <option key={d} value={d} />)}
            </datalist>
          </Field>

          <div>
            <div className="grid grid-cols-2 gap-3">
              <Field label="Date" htmlFor="event-date" error={errors.event_date?.[0]}>
                <input id="event-date" type="date" required value={form.event_date} onChange={(e) => update('event_date', e.target.value)} className={dateTime(form.event_date)} />
              </Field>
              {multiDay && (
                <Field label="Until" htmlFor="event-until" error={errors.end_date?.[0]}>
                  <input id="event-until" type="date" required value={form.end_date} min={form.event_date || undefined} onChange={(e) => update('end_date', e.target.value)} className={dateTime(form.end_date)} />
                </Field>
              )}
            </div>
            <label className="mt-2 inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
              <input
                type="checkbox"
                checked={multiDay}
                onChange={(e) => toggleMultiDay(e.target.checked)}
                className="rounded border-gray-300 text-neu-green focus:ring-neu-green"
              />
              Runs for several days
            </label>
          </div>

          {askReschedule && (
            <div className="p-3 bg-blue-50/60 border border-blue-100 rounded-lg">
              <div className="text-sm font-medium text-gray-900">The start date or time changed. Was the event rescheduled?</div>
              <div className="mt-2 space-y-1.5">
                <Choice
                  checked={form.rescheduled === true}
                  onChange={() => update('rescheduled', true)}
                  label="Yes, it was moved"
                  hint={`It will show "Rescheduled from ${formatDayMonth(event.event_date)}${form.event_date === event.event_date && event.event_time ? `, ${formatTime(event.event_time)}` : ''}".`}
                />
                <Choice
                  checked={form.rescheduled === false}
                  onChange={() => update('rescheduled', false)}
                  label="No, I'm correcting a mistake"
                  hint="The date or time was entered wrong."
                />
              </div>
              {errors.rescheduled && <div className="text-xs text-rose-600 mt-1.5">{errors.rescheduled[0]}</div>}
            </div>
          )}

          <div className="grid grid-cols-2 gap-3">
            <Field
              label="Start time"
              htmlFor="event-start"
              error={errors.event_time?.[0]}
              optional
              action={form.event_time && <ClearButton onClick={() => update('event_time', '')} />}
            >
              <input id="event-start" type="time" value={form.event_time} onChange={(e) => update('event_time', e.target.value)} className={dateTime(form.event_time)} />
            </Field>
            <Field
              label="End time"
              htmlFor="event-end"
              error={errors.end_time?.[0]}
              optional
              action={form.end_time && <ClearButton onClick={() => update('end_time', '')} />}
            >
              <input id="event-end" type="time" value={form.end_time} onChange={(e) => update('end_time', e.target.value)} className={dateTime(form.end_time)} />
            </Field>
          </div>

          <Field label="Venue" htmlFor="event-venue" error={errors.venue_id?.[0]}>
            <select id="event-venue" value={form.venue_id} onChange={(e) => update('venue_id', e.target.value)} className={input}>
              <option value="">Other / not listed (type it below)</option>
              {grouped.map((g) => (
                <optgroup key={g.label} label={g.label}>
                  {g.venues.map((v) => <option key={v.id} value={v.id}>{v.name}</option>)}
                </optgroup>
              ))}
              {unassigned.length > 0 && (
                <optgroup label="Other venues">
                  {unassigned.map((v) => <option key={v.id} value={v.id}>{v.name}</option>)}
                </optgroup>
              )}
              <option value={NEW_VENUE}>+ Add a new venue…</option>
            </select>
          </Field>

          {form.venue_id === NEW_VENUE && (
            <div className="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
              <div className="text-xs font-medium text-gray-600">New venue</div>
              <input
                type="text"
                value={newVenue.name}
                onChange={(e) => setNewVenue((v) => ({ ...v, name: e.target.value }))}
                placeholder="Venue name, e.g. CEA Building"
                aria-label="New venue name"
                className={input}
              />
              <select
                value={newVenue.building_id}
                onChange={(e) => setNewVenue((v) => ({ ...v, building_id: e.target.value }))}
                aria-label="New venue's building"
                className={input}
              >
                <option value="">No building (shows in gray)</option>
                {buildings.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
              </select>
              {venueError && <div className="text-xs text-rose-600">{venueError}</div>}
              <button
                type="button"
                onClick={addVenue}
                disabled={!newVenue.name.trim()}
                className="text-sm bg-white border border-gray-300 hover:bg-gray-100 px-3 py-1.5 rounded-lg font-medium disabled:opacity-50"
              >
                Add venue
              </button>
            </div>
          )}

          <Field
            label={form.venue_id ? 'Room / details' : 'Where'}
            htmlFor="event-where"
            error={errors.venue_details?.[0]}
            optional={!!form.venue_id && form.venue_id !== NEW_VENUE}
          >
            <input
              id="event-where"
              type="text"
              value={form.venue_details}
              onChange={(e) => update('venue_details', e.target.value)}
              placeholder={form.venue_id ? 'e.g. 504–507, 2nd floor lobby' : 'e.g. School corridors'}
              className={input}
            />
          </Field>

          <ClashWarning
            clashes={clashes}
            intro={(n) => `This overlaps with ${n === 1 ? 'another booking' : `${n} other bookings`} at this venue. You can still save it.`}
          />

          <label className="flex items-start gap-2.5 p-3 bg-emerald-50/60 border border-emerald-100 rounded-lg cursor-pointer">
            <input
              type="checkbox"
              checked={form.needs_preparation}
              onChange={(e) => update('needs_preparation', e.target.checked)}
              className="mt-0.5 rounded border-gray-300 text-neu-green focus:ring-neu-green"
            />
            <span className="text-sm">
              <span className="font-medium text-gray-900">The EMO prepares this event</span>
              <span className="block text-xs text-gray-500 mt-0.5">
                Tracks its readiness (On Track / At Risk / Critical). Leave off for bookings the EMO only schedules.
              </span>
            </span>
          </label>

          <Field label="Description" htmlFor="event-description" error={errors.description?.[0]} optional>
            <textarea id="event-description" rows={2} value={form.description} onChange={(e) => update('description', e.target.value)} className={input} />
          </Field>

          <Field label="Control #" htmlFor="event-control" error={errors.control_number?.[0]} optional>
            <input id="event-control" type="text" value={form.control_number} onChange={(e) => update('control_number', e.target.value)} className={input} />
          </Field>

          <Field label="Remarks" htmlFor="event-remarks" error={errors.remarks?.[0]} optional>
            <textarea id="event-remarks" rows={2} value={form.remarks} onChange={(e) => update('remarks', e.target.value)} className={input} />
          </Field>

          {event && (
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={form.cancelled}
                onChange={(e) => update('cancelled', e.target.checked)}
                className="rounded border-gray-300 text-rose-600 focus:ring-rose-500"
              />
              <span className="text-sm text-gray-700">This event is cancelled (it stays on the schedule, marked cancelled)</span>
            </label>
          )}

          {errors._ && (
            <div className="p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-800">
              {errors._[0]}
            </div>
          )}
        </form>

        <footer className="px-6 py-3 border-t border-gray-200 flex items-center justify-end gap-2">
          {/* Always in view, even when the full warning is scrolled out of sight. */}
          {clashes.length > 0 && (
            <span className="mr-auto inline-flex items-center gap-1.5 text-xs font-medium text-orange-700">
              <AlertIcon width={14} height={14} />
              Overlaps with {clashes.length} booking{clashes.length === 1 ? '' : 's'}
            </span>
          )}
          <button
            type="button"
            onClick={onClose}
            className="text-sm text-gray-600 hover:bg-gray-100 px-3 py-2 rounded-lg transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            form="event-form"
            disabled={submitting || form.venue_id === NEW_VENUE}
            className="text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition disabled:opacity-60"
          >
            {submitting ? 'Saving...' : event ? 'Save changes' : 'Create event'}
          </button>
        </footer>
      </div>
    </div>
  )
}

function Choice({ checked, onChange, label, hint }) {
  return (
    <label className="flex items-start gap-2 cursor-pointer">
      <input type="radio" name="rescheduled" checked={checked} onChange={onChange} className="mt-0.5 text-neu-green focus:ring-neu-green" />
      <span className="text-sm">
        <span className="text-gray-900">{label}</span>
        <span className="block text-xs text-gray-500">{hint}</span>
      </span>
    </label>
  )
}

function ClearButton({ onClick }) {
  return (
    <button type="button" onClick={onClick} className="text-xs font-medium text-gray-500 hover:text-rose-600">
      Clear
    </button>
  )
}

function Field({ label, htmlFor, error, optional, action, children }) {
  return (
    <div>
      <div className="flex items-baseline justify-between gap-2 mb-1">
        <label htmlFor={htmlFor} className="block text-sm font-medium text-gray-700">
          {label} {optional && <span className="text-gray-500 font-normal">(optional)</span>}
        </label>
        {action}
      </div>
      {children}
      {error && <div className="text-xs text-rose-600 mt-1">{error}</div>}
    </div>
  )
}
