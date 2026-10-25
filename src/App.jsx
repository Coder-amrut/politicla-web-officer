import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './hooks/useAuth';
import WorkerLogin from './pages/auth/WorkerLogin';
import WorkerDashboard from './pages/worker_Karyakarta/WorkerDashboard';
import AdminPhoneDashboard from './pages/admin/AdminPhoneDashboard';

export default function App() {
  const { isAuthenticated, loading, user } = useAuth();

  if (loading) {
    return (
      <div style={{
        height: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: '#f8fafc',
        fontFamily: "'Inter', sans-serif",
        color: '#334155',
        flexDirection: 'column',
        gap: '12px'
      }}>
        <div style={{
          width: '38px',
          height: '38px',
          border: '3.5px solid #e2e8f0',
          borderTopColor: '#2563eb',
          borderRadius: '50%',
          animation: 'spin 0.8s linear infinite'
        }} />
        <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
        <span style={{ fontSize: '14px', fontWeight: '600' }}>लोड होत आहे... / Loading...</span>
      </div>
    );
  }

  // Check if current logged-in user is an admin
  const isAdmin = Boolean(
    user?.roles?.includes('ADMIN') ||
    user?.roles?.includes('SUPER_ADMIN') ||
    user?.role === 'ADMIN' ||
    user?.role === 'SUPER_ADMIN' ||
    user?.email?.toLowerCase().includes('admin')
  );

  return (
    <Routes>
      <Route
        path="/login"
        element={
          isAuthenticated ? (
            <Navigate to={isAdmin ? "/admin" : "/"} replace />
          ) : (
            <WorkerLogin initialMode="login" />
          )
        }
      />
      <Route
        path="/register"
        element={isAuthenticated ? <Navigate to="/" replace /> : <WorkerLogin initialMode="register" />}
      />
      <Route
        path="/otp"
        element={isAuthenticated ? <Navigate to="/" replace /> : <WorkerLogin initialMode="otp" />}
      />
      <Route
        path="/"
        element={
          isAuthenticated ? (
            isAdmin ? <AdminPhoneDashboard /> : <WorkerDashboard />
          ) : (
            <Navigate to="/login" replace />
          )
        }
      />
      <Route
        path="/admin"
        element={
          isAuthenticated ? (
            <AdminPhoneDashboard />
          ) : (
            <Navigate to="/login" replace />
          )
        }
      />
      <Route
        path="*"
        element={<Navigate to="/" replace />}
      />
    </Routes>
  );
}
