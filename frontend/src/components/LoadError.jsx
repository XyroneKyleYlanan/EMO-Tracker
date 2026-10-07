import { isServerUnreachable } from '../lib/errors'

// Shown in place of a page's content when its data couldn't be loaded, so a
// failed request never looks like an empty schedule or a page stuck loading.
export default function LoadError({ error, onRetry, className = '' }) {
  const unreachable = isServerUnreachable(error)
  return (
    <div role="alert" className={`bg-white rounded-xl border border-gray-200 p-8 text-center ${className}`}>
      <p className="text-sm font-medium text-gray-900">
        {unreachable ? "Can't reach the EMO Tracker server." : 'Something went wrong while loading this.'}
      </p>
      <p className="text-sm text-gray-500 mt-1">
        {unreachable
          ? 'Check your connection, and that EMO Tracker is still running, then try again.'
          : 'Please try again. If it keeps happening, let the administrator know.'}
      </p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="mt-4 text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition"
        >
          Try again
        </button>
      )}
    </div>
  )
}
