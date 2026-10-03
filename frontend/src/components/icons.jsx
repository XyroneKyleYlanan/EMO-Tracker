const baseProps = {
  width: 20,
  height: 20,
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  strokeWidth: 1.75,
  strokeLinecap: 'round',
  strokeLinejoin: 'round',
}

export const HomeIcon = (props) => (
  <svg {...baseProps} {...props}>
    <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1V9.5z" />
  </svg>
)

export const UsersIcon = (props) => (
  <svg {...baseProps} {...props}>
    <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
    <circle cx="9" cy="7" r="4" />
    <path d="M22 21v-2a4 4 0 00-3-3.87" />
    <path d="M16 3.13a4 4 0 010 7.75" />
  </svg>
)

export const CalendarIcon = (props) => (
  <svg {...baseProps} {...props}>
    <rect x="3" y="4" width="18" height="18" rx="2" />
    <path d="M16 2v4M8 2v4M3 10h18" />
  </svg>
)

export const ChartIcon = (props) => (
  <svg {...baseProps} {...props}>
    <path d="M3 3v18h18" />
    <path d="M7 14l4-4 4 4 5-6" />
  </svg>
)

export const ChecklistIcon = (props) => (
  <svg {...baseProps} {...props}>
    <path d="M9 11l3 3L22 4" />
    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
  </svg>
)

export const LogOutIcon = (props) => (
  <svg {...baseProps} {...props}>
    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
    <path d="M16 17l5-5-5-5M21 12H9" />
  </svg>
)

export const ChevronRightIcon = (props) => (
  <svg {...baseProps} {...props}>
    <path d="M9 18l6-6-6-6" />
  </svg>
)

export const ChevronDownIcon = (props) => (
  <svg {...baseProps} {...props}>
    <path d="M6 9l6 6 6-6" />
  </svg>
)

export const ClockIcon = (props) => (
  <svg {...baseProps} {...props}>
    <circle cx="12" cy="12" r="10" />
    <path d="M12 6v6l4 2" />
  </svg>
)

export const MapPinIcon = (props) => (
  <svg {...baseProps} {...props}>
    <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" />
    <circle cx="12" cy="10" r="3" />
  </svg>
)

export const TableIcon = (props) => (
  <svg {...baseProps} {...props}>
    <rect x="3" y="4" width="18" height="16" rx="2" />
    <path d="M3 10h18M3 15h18M9 4v16" />
  </svg>
)

export const SearchIcon = (props) => (
  <svg {...baseProps} {...props}>
    <circle cx="11" cy="11" r="7" />
    <path d="M20 20l-3.5-3.5" />
  </svg>
)
