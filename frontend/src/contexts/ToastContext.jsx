import { createContext, useCallback, useContext, useState } from 'react'

const ToastContext = createContext(null)
let idCounter = 0

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])

  const dismiss = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id))
  }, [])

  const push = useCallback((toast) => {
    const id = ++idCounter
    const next = { id, type: 'info', duration: 4000, ...toast }
    setToasts((prev) => [...prev, next])
    if (next.duration > 0) {
      setTimeout(() => dismiss(id), next.duration)
    }
    return id
  }, [dismiss])

  const toast = {
    success: (message, opts = {}) => push({ type: 'success', message, ...opts }),
    error: (message, opts = {}) => push({ type: 'error', message, ...opts }),
    info: (message, opts = {}) => push({ type: 'info', message, ...opts }),
  }

  return (
    <ToastContext.Provider value={toast}>
      {children}
      <ToastViewport toasts={toasts} onDismiss={dismiss} />
    </ToastContext.Provider>
  )
}

export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast must be used inside ToastProvider')
  return ctx
}

function ToastViewport({ toasts, onDismiss }) {
  return (
    <div className="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
      {toasts.map((t) => (
        <ToastItem key={t.id} toast={t} onDismiss={() => onDismiss(t.id)} />
      ))}
    </div>
  )
}

const TYPE_STYLES = {
  success: 'bg-white border-emerald-200 text-gray-900',
  error: 'bg-white border-rose-200 text-gray-900',
  info: 'bg-white border-gray-200 text-gray-900',
}

const ICON_STYLES = {
  success: 'bg-emerald-100 text-emerald-700',
  error: 'bg-rose-100 text-rose-700',
  info: 'bg-blue-100 text-blue-700',
}

const ICONS = {
  success: '✓',
  error: '✕',
  info: 'i',
}

function ToastItem({ toast, onDismiss }) {
  return (
    <div
      className={`pointer-events-auto rounded-xl border shadow-lg px-3 py-3 flex items-start gap-3 animate-slide-in ${TYPE_STYLES[toast.type]}`}
    >
      <div className={`w-7 h-7 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0 ${ICON_STYLES[toast.type]}`}>
        {ICONS[toast.type]}
      </div>
      <div className="flex-1 text-sm pt-0.5">
        {toast.message}
      </div>
      <button
        onClick={onDismiss}
        className="text-gray-400 hover:text-gray-700 p-0.5 flex-shrink-0"
      >
        ✕
      </button>
    </div>
  )
}
