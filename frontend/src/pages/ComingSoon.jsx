export default function ComingSoon({ feature, phase }) {
  return (
    <div className="max-w-2xl mx-auto">
      <div className="bg-white rounded-2xl border border-gray-200 border-dashed p-10 text-center">
        <div className="text-sm font-semibold uppercase tracking-wide text-neu-green mb-2">
          Coming soon
        </div>
        <h1 className="text-2xl font-semibold text-gray-900 mb-2">{feature}</h1>
        <p className="text-sm text-gray-500">
          This page is part of {phase}. Authentication and dashboards are done — feature pages are next.
        </p>
      </div>
    </div>
  )
}
