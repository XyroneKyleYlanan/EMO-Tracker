import { useState } from 'react'
import { EyeIcon, EyeOffIcon } from './icons'

// A password box with an eye button that shows or hides what was typed,
// so a password is easy to check on a phone keyboard too.
export default function PasswordInput({ className = '', ...props }) {
  const [shown, setShown] = useState(false)
  const label = shown ? 'Hide password' : 'Show password'

  return (
    <div className="relative">
      <input {...props} type={shown ? 'text' : 'password'} className={`${className} pr-10`} />
      <button
        type="button"
        onClick={() => setShown((s) => !s)}
        aria-label={label}
        aria-pressed={shown}
        title={label}
        className="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 focus:outline-none focus-visible:text-neu-green"
      >
        {shown ? <EyeOffIcon width={18} height={18} /> : <EyeIcon width={18} height={18} />}
      </button>
    </div>
  )
}
