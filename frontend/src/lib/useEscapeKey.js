import { useEffect, useRef } from 'react'

// Panels and dialogs that are open now, the one on top last.
const open = []

function handleKeyDown(e) {
  if (e.key === 'Escape' && !e.isComposing && open.length > 0) {
    open.at(-1).current()
  }
}

/**
 * Closes a panel or dialog with the Escape key. When one is open on top of
 * another (a task form over the event panel), only the top one closes.
 */
export function useEscapeKey(onEscape, active = true) {
  const handler = useRef(onEscape)

  useEffect(() => {
    handler.current = onEscape
  })

  useEffect(() => {
    if (!active) return
    open.push(handler)
    if (open.length === 1) window.addEventListener('keydown', handleKeyDown)
    return () => {
      open.splice(open.indexOf(handler), 1)
      if (open.length === 0) window.removeEventListener('keydown', handleKeyDown)
    }
  }, [active])
}
