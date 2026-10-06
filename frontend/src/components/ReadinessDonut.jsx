import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend } from 'recharts'
import { READINESS_HEX } from './ReadinessBadge'

const LABELS = {
  green: 'On Track',
  yellow: 'At Risk',
  red: 'Critical',
  completed: 'Completed',
}

export default function ReadinessDonut({ distribution }) {
  const data = Object.entries(distribution)
    .filter(([, count]) => count > 0)
    .map(([key, value]) => ({
      key,
      name: LABELS[key],
      value,
      color: READINESS_HEX[key],
    }))

  const total = data.reduce((sum, d) => sum + d.value, 0)

  if (total === 0) {
    return (
      <div className="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm flex items-center justify-center min-h-[280px]">
        <div className="text-sm text-gray-500">No events to display.</div>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
      <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">
        Readiness distribution
      </h3>
      <div className="relative">
        <ResponsiveContainer width="100%" height={280}>
          <PieChart>
            <Pie
              data={data}
              dataKey="value"
              nameKey="name"
              cx="50%"
              cy="50%"
              innerRadius={70}
              outerRadius={105}
              paddingAngle={2}
              stroke="#ffffff"
              strokeWidth={3}
            >
              {data.map((entry) => (
                <Cell key={entry.key} fill={entry.color} />
              ))}
            </Pie>
            <Tooltip
              position={{ y: 0 }}
              cursor={false}
              contentStyle={{
                background: 'white',
                border: '1px solid #e5e7eb',
                borderRadius: 8,
                fontSize: 12,
                padding: '6px 10px',
                boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)',
              }}
              formatter={(value, name) => [`${value} event${value === 1 ? '' : 's'}`, name]}
              separator=": "
            />
            <Legend
              verticalAlign="bottom"
              iconType="circle"
              wrapperStyle={{ fontSize: 12, paddingTop: 8 }}
            />
          </PieChart>
        </ResponsiveContainer>

        <div
          className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none"
          style={{ paddingBottom: 38 }}
        >
          <div className="text-3xl font-semibold text-gray-900">{total}</div>
          <div className="text-xs text-gray-500 uppercase tracking-wide">Prepared events</div>
        </div>
      </div>
    </div>
  )
}
