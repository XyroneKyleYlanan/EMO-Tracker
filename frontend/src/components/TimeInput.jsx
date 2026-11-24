import { useEffect, useRef, useState } from 'react'
import { parseTime } from '../lib/time'
import { formatTime } from '../lib/format'

// A plain text box for a time, used instead of the browser's time field: Safari showed gray
// placeholder digits that looked like a typed time and then said "Invalid value" (Nov 20 usability test).
// It accepts "1:00 PM", "1pm", "13:00" and similar, keeps "HH:MM" in the form, and shows "1:00 PM" when you leave it.
export default function TimeInput({ id, value, onChange, className = '' }) {
  const [text, setText] = useState(() => formatTime(value))
  const [shownValue, setShownValue] = useState(value)
  const [touched, setTouched] = useState(false)
  const ref = useRef(null)

  // Follow changes made outside the box, such as the Clear button.
  if (value !== shownValue) {
    setShownValue(value)
    if (parseTime(text) !== value) setText(formatTime(value))
  }
  const parsed = parseTime(text)

  // A time the box can't read stops the form from saving, with a message that says how to type it.
  useEffect(() => {
    ref.current?.setCustomValidity(parsed === null ? 'Type a time like 1:00 PM.' : '')
  }, [parsed])

  function handleChange(e) {
    setText(e.target.value)
    const next = parseTime(e.target.value)
    if (next !== null) onChange(next)
  }

  function handleBlur() {
    setTouched(true)
    if (parsed) setText(formatTime(parsed))
  }

  return (
    <>
      <input
        ref={ref}
        id={id}
        type="text"
        inputMode="text"
        autoComplete="off"
        placeholder="e.g. 1:00 PM"
        value={text}
        onChange={handleChange}
        onBlur={handleBlur}
        aria-invalid={parsed === null}
        aria-describedby={parsed === null && touched ? `${id}-hint` : undefined}
        className={className}
      />
      {parsed === null && touched && (
        <div id={`${id}-hint`} className="text-xs text-rose-600 mt-1">Type a time like 1:00 PM (with AM or PM).</div>
      )}
    </>
  )
}
