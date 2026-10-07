export function formatTime(time24) {
  if (!time24) return ''
  const [hStr, mStr] = time24.split(':')
  let h = parseInt(hStr, 10)
  const m = mStr || '00'
  const period = h >= 12 ? 'PM' : 'AM'
  h = h % 12
  if (h === 0) h = 12
  return `${h}:${m} ${period}`
}

// Plain dates ("2026-10-01") are calendar days, not instants. Parse them in
// local time so they never shift by a day depending on the browser's timezone.
function parseDate(value) {
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    const [y, m, d] = value.split('-').map(Number)
    return new Date(y, m - 1, d)
  }
  return new Date(value)
}

export function formatDateShort(iso) {
  if (!iso) return ''
  return parseDate(iso).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

export function formatDateLong(iso) {
  if (!iso) return ''
  return parseDate(iso).toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  })
}

export function formatDateCompact(iso) {
  if (!iso) return ''
  return parseDate(iso).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

// "8:00 AM – 5:00 PM", just the start time, or '' when no time is set.
export function formatTimeRange(start, end) {
  if (!start) return ''
  return end ? `${formatTime(start)} – ${formatTime(end)}` : formatTime(start)
}

// "Oct 26 – 28, 2026" for multi-day events, otherwise the single date.
export function formatDateRange(start, end, format = formatDateCompact) {
  if (!end || end === start) return format(start)
  return `${format(start)} – ${format(end)}`
}

// "Mon, Oct 5": compact date for table rows.
export function formatDayMonth(iso) {
  if (!iso) return ''
  return parseDate(iso).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })
}

// "Wed, Oct 7, 8:15 AM": an exact moment (with its time zone), e.g. a backup.
export function formatDateTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

export function formatMonthYear(iso) {
  return parseDate(iso).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
}

// "Rescheduled from Fri, Oct 3", or "Rescheduled from 9:00 AM" when only the time moved.
export function formatRescheduledFrom(event) {
  if (!event?.original_date) return null
  return `Rescheduled from ${event.original_date === event.event_date ? formatTime(event.original_time) || 'another time' : formatDayMonth(event.original_date)}`
}
