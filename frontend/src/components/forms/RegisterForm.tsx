'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { registerSchema } from '@/lib/validations';
import { ApiError, ValidationError } from '@/lib/api';
import { formatValidationErrors } from '@/lib/validations';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { PasswordStrengthIndicator } from './PasswordStrengthIndicator';
import type { RegisterFormData } from '@/lib/types';

// ============================================
// Register Form Component
// With real-time password validation
// ============================================

export function RegisterForm() {
  const router = useRouter();
  const { register: registerUser } = useAuth();
  const [globalError, setGlobalError] = useState<string | null>(null);
  const [passwordValue, setPasswordValue] = useState('');

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors, isSubmitting },
    setError,
  } = useForm<RegisterFormData>({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      email: '',
      password: '',
      passwordConfirmation: '',
    },
    mode: 'onChange', // Enable real-time validation
  });

  // Watch password for real-time strength indicator
  const password = watch('password', '');

  const onSubmit = async (data: RegisterFormData) => {
    try {
      setGlobalError(null);
      await registerUser(data.email, data.password, data.passwordConfirmation);
      router.push('/dashboard');
    } catch (error) {
      if (error instanceof ValidationError) {
        // Handle field-specific validation errors from API
        const formattedErrors = formatValidationErrors(error.errors);
        Object.entries(formattedErrors).forEach(([field, message]) => {
          setError(field as keyof RegisterFormData, {
            type: 'manual',
            message,
          });
        });
        
        // If there's a general message, show it
        if (error.message && !Object.keys(error.errors).length) {
          setGlobalError(error.message);
        }
      } else if (error instanceof ApiError) {
        // Handle general API errors
        setGlobalError(error.message || 'Wystąpił błąd podczas rejestracji');
      } else {
        // Handle unknown errors
        setGlobalError('Wystąpił nieoczekiwany błąd');
        console.error('Registration error:', error);
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
        {...register('email')}
        type="email"
        label="Email"
        placeholder="twoj@email.com"
        error={errors.email?.message}
        autoComplete="email"
        autoFocus
      />

      {/* Password field with real-time validation */}
      <div>
        <Input
          {...register('password', {
            onChange: (e) => setPasswordValue(e.target.value),
          })}
          type="password"
          label="Hasło"
          placeholder="••••••••"
          error={errors.password?.message}
          autoComplete="new-password"
        />
        {/* Password strength indicator */}
        <PasswordStrengthIndicator 
          password={password} 
          show={password.length > 0}
        />
      </div>

      {/* Password confirmation field */}
      <Input
        {...register('passwordConfirmation')}
        type="password"
        label="Potwierdź hasło"
        placeholder="••••••••"
        error={errors.passwordConfirmation?.message}
        autoComplete="new-password"
      />

      {/* Submit button */}
      <Button
        type="submit"
        fullWidth
        isLoading={isSubmitting}
        disabled={isSubmitting}
      >
        {isSubmitting ? 'Rejestracja...' : 'Zarejestruj się'}
      </Button>

      {/* Login link */}
      <p className="text-center text-sm text-gray-600">
        Masz już konto?{' '}
        <Link
          href="/login"
          className="font-medium text-primary-600 hover:text-primary-700 transition-colors"
        >
          Zaloguj się
        </Link>
      </p>
    </form>
  );
}

