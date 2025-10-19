'use client';

import { createContext, useContext, useState, useEffect } from 'react';
import { apiClient } from '@/lib/api';
import type { User, LoginRequest, RegisterRequest, AuthResponse } from '@/lib/types';

// ============================================
// Auth Context Type
// ============================================

interface AuthContextType {
  user: User | null;
  token: string | null;
  login: (username: string, password: string) => Promise<void>;
  register: (email: string, password: string, passwordConfirmation: string) => Promise<void>;
  logout: () => void;
  isLoading: boolean;
}

// ============================================
// Create Context
// ============================================

const AuthContext = createContext<AuthContextType | null>(null);

// ============================================
// Auth Provider Component
// ============================================

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Initialize auth state from localStorage on mount
  useEffect(() => {
    const storedToken = localStorage.getItem('auth_token');
    if (storedToken) {
      setToken(storedToken);
      // Fetch current user data
      apiClient
        .get<User>('/auth/me')
        .then((userData) => {
          setUser(userData);
        })
        .catch(() => {
          // Token invalid or expired, clear it
          localStorage.removeItem('auth_token');
          setToken(null);
        })
        .finally(() => {
          setIsLoading(false);
        });
    } else {
      setIsLoading(false);
    }
  }, []);

  /**
   * Login user
   */
  const login = async (username: string, password: string) => {
    // Step 1: Get token
    const response = await apiClient.post<{ token: string }>('/auth/login', {
      username,
      password,
    } as LoginRequest);

    // Step 2: Save token
    setToken(response.token);
    localStorage.setItem('auth_token', response.token);

    // Step 3: Fetch user data
    const userData = await apiClient.get<User>('/auth/me');
    setUser(userData);
  };

  /**
   * Register new user
   */
  const register = async (
    email: string,
    password: string,
    passwordConfirmation: string
  ) => {
    // Step 1: Register and get token
    const response = await apiClient.post<{ token: string }>('/auth/register', {
      email,
      password,
      passwordConfirmation,
    } as RegisterRequest);

    // Step 2: Save token
    setToken(response.token);
    localStorage.setItem('auth_token', response.token);

    // Step 3: Fetch user data
    const userData = await apiClient.get<User>('/auth/me');
    setUser(userData);
  };

  /**
   * Logout user
   */
  const logout = () => {
    setUser(null);
    setToken(null);
    localStorage.removeItem('auth_token');
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        login,
        register,
        logout,
        isLoading,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

// ============================================
// Custom Hook
// ============================================

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}

