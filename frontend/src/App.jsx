import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import { ToastProvider } from './contexts/ToastContext'
import ProtectedRoute from './components/ProtectedRoute'
import AppLayout from './components/AppLayout'
import ErrorBoundary from './components/ErrorBoundary'
import LoginPage from './pages/LoginPage'
import ManagerDashboard from './pages/ManagerDashboard'
import StaffDashboard from './pages/StaffDashboard'
import EventsPage from './pages/EventsPage'
import StaffTasksPage from './pages/StaffTasksPage'
import AnalyticsPage from './pages/AnalyticsPage'
import StaffManagementPage from './pages/StaffManagementPage'
import SchedulePage from './pages/SchedulePage'
import VenuesPage from './pages/VenuesPage'

function App() {
  return (
    <ToastProvider>
      <AuthProvider>
        <BrowserRouter>
        <ErrorBoundary>
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
            <Route index element={<ManagerDashboard />} />
            <Route path="tasks" element={<StaffTasksPage />} />
            <Route path="accounts" element={<StaffManagementPage />} />
            {/* Old address of the Accounts page, kept so saved links still work. */}
            <Route path="staff" element={<Navigate to="/admin/accounts" replace />} />
            <Route path="events" element={<EventsPage />} />
            <Route path="schedule" element={<SchedulePage />} />
            <Route path="venues" element={<VenuesPage />} />
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
            <Route index element={<ManagerDashboard />} />
            <Route path="tasks" element={<StaffTasksPage />} />
            <Route path="events" element={<EventsPage />} />
            <Route path="schedule" element={<SchedulePage />} />
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
            <Route path="schedule" element={<SchedulePage />} />
          </Route>

          <Route path="/" element={<Navigate to="/login" replace />} />
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
        </ErrorBoundary>
      </BrowserRouter>
    </AuthProvider>
    </ToastProvider>
  )
}

export default App
