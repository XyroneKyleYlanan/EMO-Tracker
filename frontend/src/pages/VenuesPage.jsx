import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../lib/api'
import { useToast } from '../contexts/toast'

const NO_BUILDING_COLOR = '#E5E7EB'

// Admin-only: the buildings that color the Schedule, and the venues inside them.
export default function VenuesPage() {
  const toast = useToast()
  const [data, setData] = useState(null)
  const [dialog, setDialog] = useState(null) // { type: 'building' | 'venue' | 'merge', item? }

  function fetchVenues() {
    api.get('/venues').then((res) => setData(res.data))
  }

  useEffect(() => {
    fetchVenues()
  }, [])

  function saved(message) {
    setDialog(null)
    toast.success(message)
    fetchVenues()
  }

  async function deleteBuilding(building) {
    if (!window.confirm(`Remove the building "${building.name}"? Its venues stay, but they'll have no building (gray rows).`)) return
    try {
      await api.delete(`/buildings/${building.id}`)
      saved(`Building "${building.name}" removed.`)
    } catch {
      toast.error('Failed to remove the building.')
    }
  }

  async function deleteVenue(venue) {
    if (!window.confirm(`Delete the venue "${venue.name}"?`)) return
    try {
      await api.delete(`/venues/${venue.id}`)
      saved(`Venue "${venue.name}" deleted.`)
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to delete the venue.')
    }
  }

  if (!data) {
    return <div className="max-w-4xl mx-auto"><div className="h-64 bg-white rounded-2xl border border-gray-200 animate-pulse" /></div>
  }

  const groups = [
    ...data.buildings.map((b) => ({ building: b, venues: data.venues.filter((v) => v.building_id === b.id) })),
    { building: null, venues: data.venues.filter((v) => !v.building_id) },
  ]

  return (
    <div className="max-w-4xl mx-auto">
      <Link to="/admin/schedule" className="text-sm text-gray-500 hover:text-neu-green">← Schedule</Link>
      <div className="flex items-start justify-between gap-4 mt-2 mb-6 flex-wrap">
        <div>
          <h1 className="text-2xl md:text-3xl font-semibold text-gray-900">Venues</h1>
          <p className="text-sm text-gray-500 mt-1">Each building&apos;s color is used for its rows on the Schedule.</p>
        </div>
        <div className="flex items-center gap-2">
          <button onClick={() => setDialog({ type: 'building' })} className="text-sm border border-gray-300 bg-white hover:bg-gray-50 px-4 py-2 rounded-lg font-medium">
            + Building
          </button>
          <button onClick={() => setDialog({ type: 'venue' })} className="text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium">
            + Venue
          </button>
        </div>
      </div>

      <div className="space-y-4">
        {groups.map(({ building, venues }) => (
          (building || venues.length > 0) && (
            <section key={building?.id ?? 'none'} className="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <header className="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200" style={{ backgroundColor: building?.color || NO_BUILDING_COLOR }}>
                <div className="font-semibold text-gray-900">
                  {building ? building.name : 'No building'}
                  <span className="ml-2 text-xs font-normal text-gray-700">
                    {venues.length} venue{venues.length === 1 ? '' : 's'}
                  </span>
                </div>
                {building && (
                  <div className="flex items-center gap-1">
                    <RowButton onClick={() => setDialog({ type: 'building', item: building })}>Edit</RowButton>
                    <RowButton dangerOnHover onClick={() => deleteBuilding(building)}>Remove</RowButton>
                  </div>
                )}
              </header>
              {venues.length === 0 ? (
                <div className="px-4 py-3 text-xs text-gray-400 italic">No venues in this building yet.</div>
              ) : (
                <ul className="divide-y divide-gray-100">
                  {venues.map((v) => (
                    <li key={v.id} className="flex items-center justify-between gap-3 px-4 py-2.5">
                      <div className="min-w-0">
                        <div className="text-sm font-medium text-gray-900 truncate">{v.name}</div>
                        <div className="text-xs text-gray-500">{v.events_count} event{v.events_count === 1 ? '' : 's'}</div>
                      </div>
                      <div className="flex items-center gap-1 flex-shrink-0">
                        <RowButton onClick={() => setDialog({ type: 'venue', item: v })}>Edit</RowButton>
                        <RowButton onClick={() => setDialog({ type: 'merge', item: v })}>Merge</RowButton>
                        <RowButton
                          danger
                          disabled={v.events_count > 0}
                          title={v.events_count > 0 ? 'Used by events. Merge it into another venue instead.' : undefined}
                          onClick={() => deleteVenue(v)}
                        >
                          Delete
                        </RowButton>
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </section>
          )
        ))}
      </div>

      {dialog?.type === 'building' && (
        <BuildingDialog building={dialog.item} palette={data.palette} onClose={() => setDialog(null)} onSaved={saved} />
      )}
      {dialog?.type === 'venue' && (
        <VenueDialog venue={dialog.item} buildings={data.buildings} onClose={() => setDialog(null)} onSaved={saved} />
      )}
      {dialog?.type === 'merge' && (
        <MergeDialog venue={dialog.item} venues={data.venues} onClose={() => setDialog(null)} onSaved={saved} />
      )}
    </div>
  )
}

function BuildingDialog({ building, palette, onClose, onSaved }) {
  const [name, setName] = useState(building?.name || '')
  const [color, setColor] = useState(building?.color || palette[0])
  const [errors, setErrors] = useState({})

  async function submit(e) {
    e.preventDefault()
    try {
      if (building) await api.put(`/buildings/${building.id}`, { name, color })
      else await api.post('/buildings', { name, color })
      onSaved(building ? 'Building updated.' : `Building "${name}" added.`)
    } catch (err) {
      setErrors(err.response?.data?.errors || {})
    }
  }

  return (
    <Dialog title={building ? 'Edit building' : 'New building'} onClose={onClose} onSubmit={submit} submitLabel={building ? 'Save' : 'Add building'}>
      <Field label="Name" error={errors.name?.[0]}>
        <input type="text" required value={name} onChange={(e) => setName(e.target.value)} className={inputClass} placeholder="e.g. CEA Building" />
      </Field>
      <Field label="Color" error={errors.color?.[0]}>
        <div className="grid grid-cols-6 gap-2">
          {palette.map((c) => (
            <button
              key={c}
              type="button"
              onClick={() => setColor(c)}
              aria-label={`Color ${c}`}
              aria-pressed={c === color}
              className={`h-9 rounded-md border ${c === color ? 'ring-2 ring-offset-2 ring-neu-green border-transparent' : 'border-black/10'}`}
              style={{ backgroundColor: c }}
            />
          ))}
        </div>
      </Field>
      <div className="rounded-md border border-gray-300 px-3 py-2 text-[13px] text-gray-900" style={{ backgroundColor: color }}>
        Preview: how this building&apos;s rows look on the Schedule
      </div>
    </Dialog>
  )
}

function VenueDialog({ venue, buildings, onClose, onSaved }) {
  const [name, setName] = useState(venue?.name || '')
  const [buildingId, setBuildingId] = useState(venue?.building_id ? String(venue.building_id) : '')
  const [errors, setErrors] = useState({})

  async function submit(e) {
    e.preventDefault()
    const payload = { name, building_id: buildingId ? Number(buildingId) : null }
    try {
      if (venue) await api.put(`/venues/${venue.id}`, payload)
      else await api.post('/venues', payload)
      onSaved(venue ? 'Venue updated.' : `Venue "${name}" added.`)
    } catch (err) {
      setErrors(err.response?.data?.errors || {})
    }
  }

  return (
    <Dialog title={venue ? 'Edit venue' : 'New venue'} onClose={onClose} onSubmit={submit} submitLabel={venue ? 'Save' : 'Add venue'}>
      <Field label="Name" error={errors.name?.[0]}>
        <input type="text" required value={name} onChange={(e) => setName(e.target.value)} className={inputClass} placeholder="e.g. CEA Auditorium" />
      </Field>
      <Field label="Building" error={errors.building_id?.[0]}>
        <select value={buildingId} onChange={(e) => setBuildingId(e.target.value)} className={inputClass}>
          <option value="">No building (shows in gray)</option>
          {buildings.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
        </select>
      </Field>
      {venue?.events_count > 0 && (
        <p className="text-xs text-gray-500">Renaming updates all {venue.events_count} event{venue.events_count === 1 ? '' : 's'} at this venue.</p>
      )}
    </Dialog>
  )
}

function MergeDialog({ venue, venues, onClose, onSaved }) {
  const others = venues.filter((v) => v.id !== venue.id)
  const [intoId, setIntoId] = useState('')
  const [error, setError] = useState(null)
  const target = others.find((v) => String(v.id) === intoId)

  async function submit(e) {
    e.preventDefault()
    try {
      const res = await api.post(`/venues/${venue.id}/merge`, { into_id: Number(intoId) })
      onSaved(`Merged into "${target.name}". ${res.data.moved} event(s) moved.`)
    } catch (err) {
      setError(err.response?.data?.errors?.into_id?.[0] || err.response?.data?.message || 'Failed to merge.')
    }
  }

  return (
    <Dialog title={`Merge "${venue.name}"`} onClose={onClose} onSubmit={submit} submitLabel="Merge" submitDisabled={!intoId}>
      <p className="text-sm text-gray-600">Use this for duplicates, like &ldquo;UHALL&rdquo; and &ldquo;University Hall&rdquo;.</p>
      <Field label="Merge into" error={error}>
        <select required value={intoId} onChange={(e) => setIntoId(e.target.value)} className={inputClass}>
          <option value="">Choose a venue…</option>
          {others.map((v) => <option key={v.id} value={v.id}>{v.name}</option>)}
        </select>
      </Field>
      {target && (
        <p className="text-sm text-gray-700 p-3 bg-amber-50 border border-amber-200 rounded-lg">
          {venue.events_count} event{venue.events_count === 1 ? '' : 's'} will move to &ldquo;{target.name}&rdquo;, and &ldquo;{venue.name}&rdquo; will be removed.
        </p>
      )}
    </Dialog>
  )
}

const inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-neu-green focus:border-transparent'

function Dialog({ title, onClose, onSubmit, submitLabel, submitDisabled, children }) {
  return (
    <div className="fixed inset-0 z-50 flex items-start md:items-center justify-center p-4 md:p-6">
      <div className="fixed inset-0 bg-black/40" onClick={onClose}></div>
      <form onSubmit={onSubmit} className="relative bg-white rounded-2xl shadow-xl w-full max-w-md">
        <header className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">{title}</h2>
        </header>
        <div className="px-6 py-5 space-y-4">{children}</div>
        <footer className="px-6 py-3 border-t border-gray-200 flex items-center justify-end gap-2">
          <button type="button" onClick={onClose} className="text-sm text-gray-600 hover:bg-gray-100 px-3 py-2 rounded-lg transition">Cancel</button>
          <button type="submit" disabled={submitDisabled} className="text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition disabled:opacity-50">
            {submitLabel}
          </button>
        </footer>
      </form>
    </div>
  )
}

function Field({ label, error, children }) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
      {children}
      {error && <div className="text-xs text-rose-600 mt-1">{error}</div>}
    </div>
  )
}

// On colored building headers, red text is hard to read, so "Remove" stays dark until hovered.
function RowButton({ children, danger, dangerOnHover, ...props }) {
  return (
    <button
      type="button"
      {...props}
      className={`text-xs font-medium px-2 py-1 rounded transition disabled:opacity-40 disabled:cursor-not-allowed ${
        danger ? 'text-rose-700 hover:bg-rose-50' : dangerOnHover ? 'text-gray-800 hover:text-rose-700 hover:bg-white/60' : 'text-gray-700 hover:bg-black/5'
      }`}
    >
      {children}
    </button>
  )
}
