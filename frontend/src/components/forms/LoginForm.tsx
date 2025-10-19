'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { loginSchema } from '@/lib/validations';
import { ApiError, ValidationError } from '@/lib/api';
import { formatValidationErrors } from '@/lib/validations';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import type { LoginFormData } from '@/lib/types';

// ============================================
// Login Form Component
// ============================================

export function LoginForm() {
  const router = useRouter();
  const { login } = useAuth();
  const [globalError, setGlobalError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    setError,
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      username: '',
      password: '',
    },
  });

  const onSubmit = async (data: LoginFormData) => {
    try {
      setGlobalError(null);
      await login(data.username, data.password);
      router.push('/dashboard');
    } catch (error) {
      if (error instanceof ValidationError) {
        // Handle field-specific validation errors from API
        const formattedErrors = formatValidationErrors(error.errors);
        Object.entries(formattedErrors).forEach(([field, message]) => {
          setError(field as keyof LoginFormData, {
            type: 'manual',
            message,
          });
        });
      } else if (error instanceof ApiError) {
        // Handle general API errors
        if (error.status === 401) {
          setGlobalError('Nieprawidłowy email lub hasło');
        } else {
          setGlobalError(error.message || 'Wystąpił błąd podczas logowania');
        }
      } else {
        // Handle unknown errors
        setGlobalError('Wystąpił nieoczekiwany błąd');
        console.error('Login error:', error);
      }
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {/* Global error message */}
      {globalError && (
        <div
          className="p-4 bg-red-50 border border-red-200 rounded-lg"
          role="alert"
        >
          <p className="text-sm text-red-600">{globalError}</p>
        </div>
      )}

      {/* Email field */}
      <Input
        {...register('username')}
        type="email"
        label="Email"
        placeholder="twoj@email.com"
        error={errors.username?.message}
        autoComplete="email"
        autoFocus
      />

      {/* Password field */}
      <Input
        {...register('password')}
        type="password"
        label="Hasło"
        placeholder="••••••••"
        error={errors.password?.message}
        autoComplete="current-password"
      />

      {/* Submit button */}
      <Button
        type="submit"
        fullWidth
        isLoading={isSubmitting}
        disabled={isSubmitting}
      >
        {isSubmitting ? 'Logowanie...' : 'Zaloguj się'}
      </Button>

      {/* Register link */}
      <p className="text-center text-sm text-gray-600">
        Nie masz konta?{' '}
        <Link
          href="/register"
          className="font-medium text-primary-600 hover:text-primary-700 transition-colors"
        >
          Zarejestruj się
        </Link>
      </p>
    </form>
  );
}

