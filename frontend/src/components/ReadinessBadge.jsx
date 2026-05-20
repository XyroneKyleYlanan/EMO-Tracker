const CONFIG = {
  green: { label: 'On Track', classes: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
  yellow: { label: 'At Risk', classes: 'bg-amber-100 text-amber-800 border-amber-200' },
  red: { label: 'Critical', classes: 'bg-rose-100 text-rose-800 border-rose-200' },
  completed: { label: 'Completed', classes: 'bg-slate-100 text-slate-700 border-slate-200' },
}

export default function ReadinessBadge({ readiness, size = 'md' }) {
  const cfg = CONFIG[readiness] || CONFIG.yellow
  const sizeClasses = size === 'sm'
    ? 'text-[10px] px-1.5 py-0.5'
    : 'text-xs px-2 py-0.5'

  return (
    <span className={`inline-flex items-center font-semibold rounded-md border ${cfg.classes} ${sizeClasses}`}>
      {cfg.label}
    </span>
  )
}

export const READINESS_HEX = {
  green: '#059669',
  yellow: '#d97706',
  red: '#dc2626',
  completed: '#64748b',
}
