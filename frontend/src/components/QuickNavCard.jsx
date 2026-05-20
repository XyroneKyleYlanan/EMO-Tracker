import { Link } from 'react-router-dom'
import { ChevronRightIcon } from './icons'

export default function QuickNavCard({ to, Icon, label, description }) {
  return (
    <Link
      to={to}
      className="group bg-white rounded-2xl border border-gray-200 p-5 shadow-sm hover:border-neu-green hover:shadow-md transition"
    >
      <div className="flex items-start justify-between gap-3">
        <div className="flex-1">
          <div className="w-10 h-10 rounded-lg bg-neu-green/10 text-neu-green flex items-center justify-center mb-3">
            <Icon />
          </div>
          <div className="font-semibold text-gray-900">{label}</div>
          <div className="text-sm text-gray-500 mt-1">{description}</div>
        </div>
        <ChevronRightIcon className="text-gray-300 group-hover:text-neu-green transition" />
      </div>
    </Link>
  )
}
