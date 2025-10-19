// ============================================
// Workout Exercises Section Component
// Dynamic list of exercises with sets
// ============================================

'use client';

import { useFormContext, useFieldArray } from 'react-hook-form';
import { WorkoutSessionFormData } from '@/lib/types';
import { Button } from '@/components/ui/Button';
import { ExerciseSelector } from './ExerciseSelector';
import { WorkoutExerciseItem } from './WorkoutExerciseItem';

// ============================================
// Component
// ============================================

export function WorkoutExercisesSection() {
  const { control } = useFormContext<WorkoutSessionFormData>();

  const { fields, append, remove } = useFieldArray({
    control,
    name: 'exercises',
  });

  // ============================================
  // Handlers
  // ============================================

  const handleAddExercise = (exerciseId: string) => {
    append({
      exerciseId,
      sets: [
        {
          setsCount: 3,
          reps: 10,
          weightKg: 0,
        },
      ],
    });
  };

  const handleRemoveExercise = (index: number) => {
    const shouldRemove = confirm('Czy na pewno chcesz usunąć to ćwiczenie?');
    if (shouldRemove) {
      remove(index);
    }
  };

  // ============================================
  // Render
  // ============================================

  return (
    <div className="space-y-6">
      {/* Exercise Selector */}
      <ExerciseSelector onSelectExercise={handleAddExercise} />

      {/* Exercise List */}
      {fields.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
            />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">
            Brak ćwiczeń
          </h3>
          <p className="mt-1 text-sm text-gray-500">
            Dodaj pierwsze ćwiczenie używając wyszukiwarki powyżej
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {fields.map((field, index) => (
            <WorkoutExerciseItem
              key={field.id}
              index={index}
              onRemove={() => handleRemoveExercise(index)}
            />
          ))}
        </div>
      )}

      {/* Summary */}
      {fields.length > 0 && (
        <div className="text-sm text-gray-600">
          <p>
            Dodano <strong>{fields.length}</strong> ćwiczeń{' '}
            {fields.length >= 15 && '(maksymalnie 15)'}
          </p>
        </div>
      )}
    </div>
  );
}

