import { Component } from 'react'

// If a page crashes while drawing, show a message with a way out instead of
// leaving a blank white screen. Anything already saved is unaffected.
export default class ErrorBoundary extends Component {
  state = { crashed: false }

  static getDerivedStateFromError() {
    return { crashed: true }
  }

  componentDidCatch(error, info) {
    console.error(error, info.componentStack)
  }

  render() {
    if (!this.state.crashed) return this.props.children

    return (
      <div role="alert" className="max-w-md mx-auto mt-16 bg-white rounded-xl border border-gray-200 p-8 text-center">
        <h1 className="text-lg font-semibold text-gray-900">Something went wrong on this page.</h1>
        <p className="text-sm text-gray-500 mt-1">Reloading usually fixes it. Anything you already saved is safe.</p>
        <button
          onClick={() => window.location.reload()}
          className="mt-5 text-sm bg-neu-green hover:bg-neu-green-dark text-white px-4 py-2 rounded-lg font-medium transition"
        >
          Reload page
        </button>
      </div>
    )
  }
}
