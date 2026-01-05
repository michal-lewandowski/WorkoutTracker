// ============================================
// Edit Exercises List Component
// List of exercises with inline editing and add/remove functionality
// ============================================

'use client';

import { useState } from 'react';
import { WorkoutExercise, ExerciseSet, Exercise } from '@/lib/types';
import { Button } from '@/components/ui/Button';
import { Card, CardContent } from '@/components/ui/Card';
import { EditExerciseSetRow } from './EditExerciseSetRow';
import { ExerciseSelector } from './ExerciseSelector';
import { apiClient } from '@/lib/api';
import { toast } from 'react-hot-toast';
import { useExercises } from '@/hooks/useExercises';

// ============================================
// Props Interface
// ============================================

interface EditExercisesListProps {
  exercises: WorkoutExercise[];
  sessionId: string;
  onUpdate: () => void;
}

// ============================================
// Component
// ============================================

export function EditExercisesList({
  exercises,
  sessionId,
  onUpdate,
}: EditExercisesListProps) {
  const { exercises: allExercises } = useExercises();
  const [isAddingExercise, setIsAddingExercise] = useState(false);
  const [pendingSets, setPendingSets] = useState<
    Record<string, ExerciseSet[]>
  >({});

  // ============================================
  // Add Exercise Handler
  // ============================================

  const handleAddExercise = async (exerciseId: string) => {
    try {
      await apiClient.post('/workout-exercises', {
        workoutSessionId: sessionId,
        exerciseId: exerciseId,
        sets: [
          {
            setsCount: 3,
            reps: 10,
            weightKg: 0,
          },
        ],
      });

      toast.success('Ćwiczenie dodane');
      setIsAddingExercise(false);
      onUpdate();
    } catch (error) {
      console.error('Failed to add exercise:', error);
      toast.error('Nie udało się dodać ćwiczenia');
    }
  };

  // ============================================
  // Delete Exercise Handler
  // ============================================

  const handleDeleteExercise = async (workoutExerciseId: string) => {
    const confirmed = confirm(
      'Czy na pewno chcesz usunąć to ćwiczenie wraz ze wszystkimi seriami?'
    );
    if (!confirmed) return;

    try {
      await apiClient.delete(`/workout-exercises/${workoutExerciseId}`);
      toast.success('Ćwiczenie usunięte');
      onUpdate();
    } catch (error) {
      console.error('Failed to delete exercise:', error);
      toast.error('Nie udało się usunąć ćwiczenia');
    }
  };

  // ============================================
  // Add Set Handler
  // ============================================

  const handleAddSet = (workoutExercise: WorkoutExercise) => {
    // Create a temporary set with a unique temporary ID
    const tempSet: ExerciseSet = {
      id: `temp-${Date.now()}`,
      workoutExerciseId: workoutExercise.id,
      setsCount: 1,
      reps: 1,
      weightKg: 0,
      createdAt: new Date().toISOString(),
    };

    // Add to pending sets for this workout exercise
    setPendingSets((prev) => ({
      ...prev,
      [workoutExercise.id]: [
        ...(prev[workoutExercise.id] || []),
        tempSet,
      ],
    }));
  };

  // ============================================
  // Handle Pending Set Update/Delete
  // ============================================

  const handlePendingSetUpdate = (workoutExerciseId: string) => {
    // Clear pending sets for this exercise after successful save
    setPendingSets((prev: Record<string, ExerciseSet[]>) => {
      const newPending = { ...prev };
      delete newPending[workoutExerciseId];
      return newPending;
    });
    onUpdate();
  };

  const handlePendingSetDelete = (
    workoutExerciseId: string,
    setId: string
  ) => {
    if (setId.startsWith('temp-')) {
      // Remove only this pending set
      setPendingSets((prev: Record<string, ExerciseSet[]>) => ({
        ...prev,
        [workoutExerciseId]: (prev[workoutExerciseId] || []).filter(
          (s: ExerciseSet) => s.id !== setId
        ),
      }));
    } else {
      // For real sets, trigger update from backend
      onUpdate();
    }
  };

  // ============================================
  // Render
  // ============================================

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-gray-900">Ćwiczenia</h2>
        <Button
          variant="outline"
          size="sm"
          onClick={() => setIsAddingExercise(!isAddingExercise)}
        >
          <svg
            className="w-4 h-4 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 4v16m8-8H4"
            />
          </svg>
          Dodaj ćwiczenie
        </Button>
      </div>

      {/* Add Exercise Selector */}
      {isAddingExercise && (
        <Card>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="font-medium text-gray-900">Wybierz ćwiczenie</h3>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setIsAddingExercise(false)}
              >
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
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </Button>
            </div>
            <ExerciseSelector onSelectExercise={handleAddExercise} />
          </CardContent>
        </Card>
      )}

      {/* Exercises List */}
      {exercises.length === 0 ? (
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
              Dodaj pierwsze ćwiczenie klikając przycisk powyżej
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {exercises.map((workoutExercise, index) => {
            const exercise = allExercises.find(
              (ex: Exercise) => ex.id === workoutExercise.exerciseId
            );

            // Combine real sets with pending sets
            const allSetsForExercise = [
              ...workoutExercise.exerciseSets,
              ...(pendingSets[workoutExercise.id] || []),
            ];

            return (
              <Card key={workoutExercise.id}>
                <CardContent className="space-y-4">
                  {/* Exercise Header */}
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <span className="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full font-semibold text-sm">
                        {exercises.length - index}
                      </span>
                      <div>
                        <h3 className="font-semibold text-gray-900">
                          {workoutExercise.exercise.name}
                        </h3>
                        <p className="text-sm text-gray-500">
                          {exercise?.muscleCategory.namePl}
                        </p>
                      </div>
                    </div>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() => handleDeleteExercise(workoutExercise.id)}
                      className="text-red-600 hover:text-red-700 hover:bg-red-50"
                    >
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
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </Button>
                  </div>

                  {/* Sets Header */}
                  <div className="grid grid-cols-12 gap-2 text-sm font-medium text-gray-700 px-2">
                    <div className="col-span-3">Serie</div>
                    <div className="col-span-3">Powtórzenia</div>
                    <div className="col-span-3">Ciężar (kg)</div>
                    <div className="col-span-2"></div>
                  </div>

                  {/* Sets List */}
                  <div className="space-y-2">
                    {allSetsForExercise.map((set) => (
                      <EditExerciseSetRow
                        key={set.id}
                        set={set}
                        workoutExerciseId={workoutExercise.id}
                        allSets={allSetsForExercise}
                        onUpdate={() =>
                          handlePendingSetUpdate(workoutExercise.id)
                        }
                        onDelete={() =>
                          handlePendingSetDelete(workoutExercise.id, set.id)
                        }
                        isPending={set.id.startsWith('temp-')}
                      />
                    ))}
                  </div>

                  {/* Add Set Button */}
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => handleAddSet(workoutExercise)}
                    disabled={allSetsForExercise.length >= 20}
                    className="w-full"
                  >
                    <svg
                      className="w-4 h-4 mr-2"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M12 4v16m8-8H4"
                      />
                    </svg>
                    Dodaj serię{' '}
                    {allSetsForExercise.length >= 20 && '(maksymalnie 20)'}
                  </Button>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}


