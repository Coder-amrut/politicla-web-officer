import React, { createContext, useState, useEffect, useCallback } from 'react';
import { getToken, setToken, removeToken, hasToken } from '../utils/tokenStorage';
import * as authService from '../services/authService';

export const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    // Check if worker session cached
    try {
      const cached = localStorage.getItem('worker_cached_user');
      return cached ? JSON.parse(cached) : null;
    } catch {
      return null;
    }
  });
  const [loading, setLoading] = useState(true);

  const restoreAuth = useCallback(async () => {
    if (!hasToken()) {
      setUser(null);
      setLoading(false);
      return;
    }

    try {
      const currentUser = await authService.getCurrentUser();
      const userData = currentUser?.user || currentUser;
      setUser(userData);
      localStorage.setItem('worker_cached_user', JSON.stringify(userData));
    } catch (error) {
      console.warn('Auth token expired or backend offline:', error.message);
      // Keep cached user if offline, or clear if 401
      if (error?.status === 401) {
        removeToken();
        localStorage.removeItem('worker_cached_user');
        setUser(null);
      }
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    restoreAuth();

    const handleUnauthorized = () => {
      setUser(null);
      localStorage.removeItem('worker_cached_user');
    };

    window.addEventListener('auth:unauthorized', handleUnauthorized);
    return () => {
      window.removeEventListener('auth:unauthorized', handleUnauthorized);
    };
  }, [restoreAuth]);

  const login = async (credentials) => {
    const data = await authService.loginUser(credentials);
    if (data.token) {
      setToken(data.token);
    }
    if (data.user) {
      setUser(data.user);
      localStorage.setItem('worker_cached_user', JSON.stringify(data.user));
    }
    return data;
  };

  const register = async (userData) => {
    const data = await authService.registerUser(userData);
    if (data.token) {
      setToken(data.token);
    }
    if (data.user) {
      setUser(data.user);
      localStorage.setItem('worker_cached_user', JSON.stringify(data.user));
    }
    return data;
  };

  const setSession = (token, userData) => {
    if (token) {
      setToken(token);
    }
    if (userData) {
      setUser(userData);
      localStorage.setItem('worker_cached_user', JSON.stringify(userData));
    }
  };

  const logout = async () => {
    try {
      await authService.logoutUser();
    } catch {
      // ignore
    } finally {
      removeToken();
      localStorage.removeItem('worker_cached_user');
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        login,
        register,
        logout,
        setUser,
        setSession,
        isAuthenticated: Boolean(user) || hasToken(),
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}
