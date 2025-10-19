'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { RegisterForm } from '@/components/forms/RegisterForm';
import { LoadingScreen } from '@/components/ui/Spinner';

// ============================================
// Register Page
// User registration page
// ============================================

export default function RegisterPage() {
  const router = useRouter();
  const { user, isLoading } = useAuth();

  // Redirect to dashboard if already logged in
  useEffect(() => {
    if (!isLoading && user) {
      router.push('/dashboard');
    }
  }, [user, isLoading, router]);

  if (isLoading) {
    return <LoadingScreen />;
  }

  if (user) {
    return null; // Will redirect
  }

  return (
    <div>
      <h2 className="text-2xl font-bold text-gray-900 mb-6 text-center">
        Zarejestruj się
      </h2>
      <RegisterForm />
    </div>
  );
}

