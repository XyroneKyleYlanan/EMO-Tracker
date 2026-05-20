import { useEffect, useState } from 'react'

export default function DateTimeDisplay() {
  const [now, setNow] = useState(new Date())

  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 60000)
    return () => clearInterval(id)
  }, [])

  const dateStr = now.toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  })

  const timeStr = now.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  })

  return (
    <div className="text-sm md:text-right">
      <div className="text-gray-900 font-medium">{dateStr}</div>
      <div className="text-gray-500 mt-0.5">{timeStr}</div>
    </div>
  )
}
