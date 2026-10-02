import { createContext, useContext, useEffect, useState } from 'react'
import api from '../lib/api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('emo_user')
    return stored ? JSON.parse(stored) : null
  })
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('emo_token')
    if (!token) {
      setLoading(false)
      return
    }

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

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider')
  return ctx
}
