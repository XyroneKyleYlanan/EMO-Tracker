import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import { ToastProvider } from './contexts/ToastContext'
import ProtectedRoute from './components/ProtectedRoute'
import AppLayout from './components/AppLayout'
import LoginPage from './pages/LoginPage'
import AdminDashboard from './pages/AdminDashboard'
import OfficerDashboard from './pages/OfficerDashboard'
import StaffDashboard from './pages/StaffDashboard'
import EventsPage from './pages/EventsPage'
import StaffTasksPage from './pages/StaffTasksPage'
import AnalyticsPage from './pages/AnalyticsPage'
import StaffManagementPage from './pages/StaffManagementPage'
import ComingSoon from './pages/ComingSoon'

function App() {
  return (
    <ToastProvider>
      <AuthProvider>
        <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />

          <Route
            path="/admin"
            element={
              <ProtectedRoute allowedRoles={['admin']}>
                <AppLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<AdminDashboard />} />
            <Route path="staff" element={<StaffManagementPage />} />
            <Route path="events" element={<EventsPage />} />
            <Route path="analytics" element={<AnalyticsPage />} />
          </Route>

          <Route
            path="/officer"
            element={
              <ProtectedRoute allowedRoles={['officer']}>
                <AppLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<OfficerDashboard />} />
            <Route path="events" element={<EventsPage />} />
            <Route path="analytics" element={<AnalyticsPage />} />
          </Route>

          <Route
            path="/staff"
            element={
              <ProtectedRoute allowedRoles={['staff']}>
                <AppLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<StaffDashboard />} />
            <Route path="tasks" element={<StaffTasksPage />} />
            <Route path="events" element={<EventsPage />} />
          </Route>

          <Route path="/" element={<Navigate to="/login" replace />} />
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
    </ToastProvider>
  )
}

export default App
