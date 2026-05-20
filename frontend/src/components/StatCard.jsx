export default function StatCard({ label, value, sublabel, accent = 'green' }) {
  const accentClasses = {
    green: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    blue: 'bg-blue-50 text-blue-700',
    rose: 'bg-rose-50 text-rose-700',
    slate: 'bg-slate-50 text-slate-700',
  }

  return (
    <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
      <div className="text-xs font-semibold uppercase tracking-wide text-gray-500">{label}</div>
      <div className="mt-2 flex items-baseline gap-2">
        <div className="text-3xl font-semibold text-gray-900">{value}</div>
        {sublabel && (
          <div className={`text-xs font-medium px-1.5 py-0.5 rounded-md ${accentClasses[accent]}`}>
            {sublabel}
          </div>
        )}
      </div>
    </div>
  )
}
