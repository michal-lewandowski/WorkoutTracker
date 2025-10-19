// ============================================
// Edit Workout Session Page
// Edit session metadata and exercises inline
// ============================================

'use client';

import { useRouter } from 'next/navigation';
import { useWorkoutSession } from '@/hooks/useWorkoutSessions';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';
import { EditWorkoutMetadata } from '@/components/forms/EditWorkoutMetadata';
import { EditExercisesList } from '@/components/forms/EditExercisesList';

// ============================================
// Props Interface
// ============================================

interface EditWorkoutSessionPageProps {
  params: {
    id: string;
  };
}

// ============================================
// Component
// ============================================

export default function EditWorkoutSessionPage({
  params,
}: EditWorkoutSessionPageProps) {
  const router = useRouter();
  const { session, isLoading, error, mutate } = useWorkoutSession(params.id);

  // ============================================
  // Handlers
  // ============================================

  const handleUpdate = () => {
    // Revalidate session data
    mutate();
  };

  const handleCancel = () => {
    router.push(`/dashboard/sessions/${params.id}`);
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
      {/* Header */}
      <div className="flex items-center justify-between">
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
          <div>
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
              Edytuj sesję
            </h1>
            <p className="text-sm text-gray-600 mt-1">
              {new Date(session.date).toLocaleDateString('pl-PL', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}
            </p>
          </div>
        </div>

        <Button variant="outline" onClick={handleCancel}>
          Anuluj edycję
        </Button>
      </div>

      {/* Info Banner */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div className="flex">
          <svg
            className="w-5 h-5 text-blue-600 mt-0.5 mr-3"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <div className="text-sm text-blue-800">
            <p className="font-medium mb-1">Automatyczne zapisywanie</p>
            <p>
              Zmiany w seriach są zapisywane automatycznie po 500ms od ostatniej
              edycji. Metadane sesji wymagają kliknięcia przycisku "Zapisz".
            </p>
          </div>
        </div>
      </div>

      {/* Edit Metadata Section */}
      <EditWorkoutMetadata session={session} onUpdate={handleUpdate} />

      {/* Edit Exercises Section */}
      <EditExercisesList
        exercises={session.workoutExercises}
        sessionId={session.id}
        onUpdate={handleUpdate}
      />

      {/* Bottom Actions */}
      <div className="flex justify-end gap-3 pb-8">
        <Button variant="outline" onClick={handleCancel}>
          Zakończ edycję
        </Button>
        <Button onClick={() => router.push(`/dashboard/sessions/${session.id}`)}>
          Zobacz sesję
        </Button>
      </div>
    </div>
  );
}

