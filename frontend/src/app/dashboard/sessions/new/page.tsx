'use client';

import { Button } from '@/components/ui/Button';
import { useRouter } from 'next/navigation';
import { WorkoutSessionForm } from '@/components/forms/WorkoutSessionForm';

// ============================================
// New Workout Session Page
// Create new workout session with exercises and sets
// ============================================

export default function NewWorkoutSessionPage() {
  const router = useRouter();

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
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
        <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
          Nowa Sesja Treningowa
        </h1>
      </div>

      {/* Workout Session Form */}
      <WorkoutSessionForm mode="create" />
    </div>
  );
}

