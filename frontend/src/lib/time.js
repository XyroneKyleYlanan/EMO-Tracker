// Reads a time the way people type it: "1:00 PM", "1pm", "1 p.m.", "13:00", "1300", "0100P".
// Returns "HH:MM" (24-hour), "" for an empty box, or null when it isn't a time.
// A 1-to-12 hour needs AM or PM, so "1:00" is never quietly read as 1:00 AM.
export function parseTime(text) {
  const s = (text || '').toLowerCase().replace(/[\s.]/g, '')
  if (s === '') return ''
  const m = s.match(/^(\d{1,2})(?::(\d{2}))?(am|pm|a|p)?$/) || s.match(/^(\d{1,2})(\d{2})(am|pm|a|p)?$/)
  if (!m) return null
  let h = parseInt(m[1], 10)
  const min = m[2] ? parseInt(m[2], 10) : 0
  const period = m[3] ? m[3][0] : null
  if (min > 59) return null
  if (period) {
    if (h < 1 || h > 12) return null
    if (period === 'p' && h !== 12) h += 12
    if (period === 'a' && h === 12) h = 0
  } else if (h >= 1 && h <= 12) {
    return null
  } else if (h > 23) {
    return null
  }
  return `${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}`
}
