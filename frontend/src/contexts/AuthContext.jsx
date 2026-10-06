import { useEffect, useState } from 'react'
import api from '../lib/api'
import { AuthContext } from './auth'

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('emo_user')
    return stored ? JSON.parse(stored) : null
  })
  // With a saved session, check it with the server before showing pages.
  const [loading, setLoading] = useState(() => !!localStorage.getItem('emo_token'))

  useEffect(() => {
    if (!localStorage.getItem('emo_token')) return

    api.get('/me')
      .then((res) => {
        setUser(res.data.user)
        localStorage.setItem('emo_user', JSON.stringify(res.data.user))
      })
      .catch((err) => {
        // Only drop the session if the server rejected it, not when it's unreachable.
        if (!err.response) return
        localStorage.removeItem('emo_token')
        localStorage.removeItem('emo_user')
        setUser(null)
      })
      .finally(() => setLoading(false))
  }, [])

  async function login(email, password) {
    const res = await api.post('/login', { email, password })
    localStorage.setItem('emo_token', res.data.token)
    localStorage.setItem('emo_user', JSON.stringify(res.data.user))
    setUser(res.data.user)
    return res.data.user
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch {
      // ignore — token may already be invalid
    }
    localStorage.removeItem('emo_token')
    localStorage.removeItem('emo_user')
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}
