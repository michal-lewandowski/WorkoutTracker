// ============================================
// Workout Session Detail Page (Read-Only)
// Displays complete session with exercises and sets
// ============================================

'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useWorkoutSession } from '@/hooks/useWorkoutSessions';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { SessionHeader } from '@/components/sessions/SessionHeader';
import { SessionMetadata } from '@/components/sessions/SessionMetadata';
import { ExercisesList } from '@/components/sessions/ExercisesList';
import { apiClient } from '@/lib/api';
import { toast } from 'react-hot-toast';

// ============================================
// Props Interface
// ============================================

interface WorkoutSessionPageProps {
  params: {
    id: string;
  };
}

// ============================================
// Component
// ============================================

export default function WorkoutSessionPage({
  params,
}: WorkoutSessionPageProps) {
  const router = useRouter();
  const { session, isLoading, error, mutate } = useWorkoutSession(params.id);

  // ============================================
  // Delete Session Handler
  // ============================================

  const handleDelete = async () => {
    if (!session) return;

    const confirmed = confirm(
      'Czy na pewno chcesz usunąć całą sesję treningową? Ta operacja jest nieodwracalna.'
    );

    if (!confirmed) return;

    try {
      await apiClient.delete(`/workout-sessions/${session.id}`);
      toast.success('Sesja została usunięta');
      router.push('/dashboard');
    } catch (error) {
      console.error('Failed to delete session:', error);
      toast.error('Nie udało się usunąć sesji');
    }
  };

  // ============================================
  // Loading State
  // ============================================

  if (isLoading) {
    return (
      <div className="max-w-4xl mx-auto">
        <div className="flex items-center space-x-4 mb-6">
          <Button variant="ghost" onClick={() => router.back()}>
            <svg
              className="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </Button>
          <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
            Ładowanie...
          </h1>
        </div>

        <div className="flex items-center justify-center py-12">
          <Spinner size="lg" />
        </div>
      </div>
    );
  }

  // ============================================
  // Error State
  // ============================================

  if (error || !session) {
    return (
      <div className="max-w-4xl mx-auto">
        <div className="flex items-center space-x-4 mb-6">
          <Button variant="ghost" onClick={() => router.back()}>
            <svg
              className="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </Button>
          <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
            Błąd
          </h1>
        </div>

        <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
          <svg
            className="mx-auto h-12 w-12 text-red-600 mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
          <h2 className="text-lg font-semibold text-red-900 mb-2">
            Nie znaleziono sesji
          </h2>
          <p className="text-red-700 mb-4">
            Sesja treningowa o podanym ID nie istnieje lub została usunięta.
          </p>
          <Button onClick={() => router.push('/dashboard')}>
            Wróć do Dashboard
          </Button>
        </div>
      </div>
    );
  }

  // ============================================
  // Success State
  // ============================================

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Back Button + Header */}
      <div className="flex items-center space-x-4">
        <Button variant="ghost" onClick={() => router.back()}>
          <svg
            className="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M15 19l-7-7 7-7"
            />
          </svg>
        </Button>
        <div className="flex-1">
          <SessionHeader session={session} onDelete={handleDelete} />
        </div>
      </div>

      {/* Session Metadata (Notes, timestamps) */}
      <SessionMetadata session={session} />

      {/* Exercises List */}
      <div>
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Ćwiczenia</h2>
        <ExercisesList exercises={session.workoutExercises} />
      </div>
    </div>
  );
}

