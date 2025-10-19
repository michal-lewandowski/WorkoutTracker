// ============================================
// Stats Panel Component
// Exercise selector + progress chart
// ============================================

'use client';

import { useState } from 'react';
import { useExercises } from '@/hooks/useExercises';
import { useExerciseStatistics } from '@/hooks/useExerciseStatistics';
import { Card, CardContent } from '@/components/ui/Card';
import { Spinner } from '@/components/ui/Spinner';
import { ExerciseProgressChart } from './ExerciseProgressChart';

// ============================================
// Component
// ============================================

export function StatsPanel() {
  const { exercises, isLoading: exercisesLoading } = useExercises();
  const [selectedExerciseId, setSelectedExerciseId] = useState<string | null>(
    null
  );

  const { statistics, isLoading: statsLoading } = useExerciseStatistics(
    selectedExerciseId
  );

  // ============================================
  // Handlers
  // ============================================

  const handleExerciseSelect = (exerciseId: string) => {
    setSelectedExerciseId(exerciseId);
  };

  // ============================================
  // Render
  // ============================================

  return (
    <Card>
      <CardContent className="space-y-6">
        <div>
          <h2 className="text-xl font-semibold text-gray-900 mb-4">
            Postęp w ćwiczeniach
          </h2>

          {/* Exercise Selector */}
          {exercisesLoading ? (
            <div className="flex items-center justify-center py-4">
              <Spinner size="sm" />
            </div>
          ) : (
            <div>
              <label
                htmlFor="exercise-select"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                Wybierz ćwiczenie
              </label>
              <select
                id="exercise-select"
                value={selectedExerciseId || ''}
                onChange={(e) => handleExerciseSelect(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">-- Wybierz ćwiczenie --</option>
                {exercises.map((exercise) => (
                  <option key={exercise.id} value={exercise.id}>
                    {exercise.name}
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>

        {/* Chart Area */}
        <div className="min-h-[300px]">
          {!selectedExerciseId ? (
            // Empty state - no exercise selected
            <div className="flex items-center justify-center h-64 bg-gray-50 rounded-lg">
              <div className="text-center">
                <svg
                  className="mx-auto h-16 w-16 text-gray-400 mb-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                  />
                </svg>
                <p className="text-sm text-gray-600">
                  Wybierz ćwiczenie, aby zobaczyć postęp
                </p>
              </div>
            </div>
          ) : statsLoading ? (
            // Loading state
            <div className="flex items-center justify-center h-64">
              <Spinner size="lg" />
            </div>
          ) : statistics ? (
            // Chart with data
            <ExerciseProgressChart statistics={statistics} />
          ) : (
            // No data for selected exercise
            <div className="flex items-center justify-center h-64 bg-gray-50 rounded-lg">
              <div className="text-center">
                <svg
                  className="mx-auto h-12 w-12 text-gray-400 mb-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                  />
                </svg>
                <p className="text-sm text-gray-600">
                  Nie wykonano jeszcze tego ćwiczenia
                </p>
              </div>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

