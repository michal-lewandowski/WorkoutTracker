// ============================================
// Exercises List Component
// Displays list of exercises with sets in read-only format
// ============================================

'use client';

import { WorkoutExercise } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/Card';

// ============================================
// Props Interface
// ============================================

interface ExercisesListProps {
  exercises: WorkoutExercise[];
}

// ============================================
// Helper Function - Format sets for display
// ============================================

function formatSets(sets: WorkoutExercise['exerciseSets']): string {
  // Group sets by same reps and weight
  // Example: "3x10@70kg, 2x8@80kg"
  
  return sets
    .map((set) => {
      const { setsCount, reps, weightKg } = set;
      return `${setsCount}×${reps}@${weightKg}kg`;
    })
    .join(', ');
}

// ============================================
// Helper Function - Calculate total volume
// ============================================

function calculateVolume(sets: WorkoutExercise['exerciseSets']): number {
  return sets.reduce((total, set) => {
    return total + set.setsCount * set.reps * set.weightKg;
  }, 0);
}

// ============================================
// Component
// ============================================

export function ExercisesList({ exercises }: ExercisesListProps) {
  if (exercises.length === 0) {
    return (
      <Card>
        <CardContent className="text-center py-12">
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
              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
            />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">
            Brak ćwiczeń
          </h3>
          <p className="mt-1 text-sm text-gray-500">
            Ta sesja nie zawiera żadnych ćwiczeń
          </p>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      {exercises.map((workoutExercise, index) => {
        const volume = calculateVolume(workoutExercise.exerciseSets);
        const totalSets = workoutExercise.exerciseSets.reduce(
          (sum, set) => sum + set.setsCount,
          0
        );

        return (
          <Card key={workoutExercise.id}>
            <CardContent>
              {/* Exercise Header */}
              <div className="flex items-start justify-between mb-4">
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full font-semibold text-sm">
                      {exercises.length - index}
                    </span>
                    <div>
                      <h3 className="font-semibold text-gray-900">
                        {workoutExercise.exercise.name}
                      </h3>
                      <p className="text-sm text-gray-500">
                        {totalSets} {totalSets === 1 ? 'seria' : 'serie'}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Volume indicator */}
                <div className="text-right">
                  <p className="text-xs text-gray-500">Objętość</p>
                  <p className="font-semibold text-gray-900">
                    {volume.toFixed(0)} kg
                  </p>
                </div>
              </div>

              {/* Sets Display */}
              <div className="bg-gray-50 rounded-lg p-4">
                <div className="flex flex-wrap gap-2">
                  {workoutExercise.exerciseSets.map((set, setIndex) => (
                    <div
                      key={set.id}
                      className="inline-flex items-center px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm"
                    >
                      <span className="font-mono font-medium text-gray-900">
                        {set.setsCount}×{set.reps}
                      </span>
                      <span className="mx-1 text-gray-400">@</span>
                      <span className="font-semibold text-blue-600">
                        {set.weightKg}kg
                      </span>
                    </div>
                  ))}
                </div>

                {/* Summary line */}
                <div className="mt-3 pt-3 border-t border-gray-200">
                  <p className="text-sm text-gray-600">
                    {formatSets(workoutExercise.exerciseSets)}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        );
      })}

      {/* Session Summary */}
      <Card variant="elevated">
        <CardContent>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 text-center">
            <div>
              <p className="text-2xl font-bold text-gray-900">
                {exercises.length}
              </p>
              <p className="text-sm text-gray-600">
                {exercises.length === 1 ? 'Ćwiczenie' : 'Ćwiczeń'}
              </p>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">
                {exercises.reduce(
                  (total, ex) =>
                    total +
                    ex.exerciseSets.reduce((sum, set) => sum + set.setsCount, 0),
                  0
                )}
              </p>
              <p className="text-sm text-gray-600">Serii</p>
            </div>
            <div className="col-span-2 sm:col-span-1">
              <p className="text-2xl font-bold text-gray-900">
                {exercises
                  .reduce((total, ex) => total + calculateVolume(ex.exerciseSets), 0)
                  .toFixed(0)}
                <span className="text-base text-gray-600"> kg</span>
              </p>
              <p className="text-sm text-gray-600">Całkowita objętość</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

