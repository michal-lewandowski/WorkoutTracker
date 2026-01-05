// ============================================
// Edit Exercise Set Row Component
// Inline editable set row with manual save
// ============================================

'use client';

import { useState } from 'react';
import { ExerciseSet } from '@/lib/types';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { apiClient } from '@/lib/api';
import { toast } from 'react-hot-toast';

// ============================================
// Props Interface
// ============================================

interface EditExerciseSetRowProps {
  set: ExerciseSet;
  workoutExerciseId: string;
  allSets: ExerciseSet[];
  onUpdate: () => void;
  onDelete: () => void;
  isPending?: boolean;
}

// ============================================
// Component
// ============================================

export function EditExerciseSetRow({
  set,
  workoutExerciseId,
  allSets,
  onUpdate,
  onDelete,
  isPending = false,
}: EditExerciseSetRowProps) {
  const [localValue, setLocalValue] = useState({
    setsCount: set.setsCount.toString(),
    reps: set.reps.toString(),
    weightKg: set.weightKg.toString(),
  });

  const [isSaving, setIsSaving] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [hasChanges, setHasChanges] = useState(isPending);

  // ============================================
  // Save Handler
  // ============================================

  const handleSave = async () => {
    // Parse values
    const setsCount = Number(localValue.setsCount);
    const reps = Number(localValue.reps);
    const weightKg = Number(localValue.weightKg || 0);

    // Validate values
    if (
      isNaN(setsCount) ||
      isNaN(reps) ||
      isNaN(weightKg) ||
      setsCount < 1 ||
      reps < 1 ||
      reps > 100 ||
      weightKg < 0 ||
      weightKg > 500
    ) {
      setHasError(true);
      toast.error('Nieprawidłowe wartości');
      return;
    }

    setHasError(false);
    setIsSaving(true);

    try {
      // Prepare the updated sets array
      let updatedSets;

      if (isPending) {
        // For pending sets, add to the array (exclude temporary IDs)
        updatedSets = [
          ...allSets
            .filter((s) => !s.id.startsWith('temp-'))
            .map((s) => ({
              setsCount: s.setsCount,
              reps: s.reps,
              weightKg: s.weightKg,
            })),
          {
            setsCount: setsCount,
            reps: reps,
            weightKg: weightKg,
          },
        ];
      } else {
        // For existing sets, update in place
        updatedSets = allSets
          .filter((s) => !s.id.startsWith('temp-'))
          .map((s) =>
            s.id === set.id
              ? {
                  setsCount: setsCount,
                  reps: reps,
                  weightKg: weightKg,
                }
              : {
                  setsCount: s.setsCount,
                  reps: s.reps,
                  weightKg: s.weightKg,
                }
          );
      }

      await apiClient.put(`/workout-exercises/${workoutExerciseId}`, {
        sets: updatedSets,
      });

      toast.success(isPending ? 'Seria dodana' : 'Zmiany zapisane');
      setHasChanges(false);
      onUpdate();
    } catch (error) {
      console.error('Failed to save set changes:', error);
      toast.error('Nie udało się zapisać zmian');
      setHasError(true);
    } finally {
      setIsSaving(false);
    }
  };

  // ============================================
  // Handlers
  // ============================================

  const handleChange = (field: keyof typeof localValue, value: string) => {
    setLocalValue((prev) => ({ ...prev, [field]: value }));
    setHasError(false);
    setHasChanges(true);
  };

  const handleDelete = async () => {
    // For pending sets, just remove from local state
    if (isPending) {
      onDelete();
      return;
    }

    if (allSets.filter((s) => !s.id.startsWith('temp-')).length === 1) {
      toast.error('Ćwiczenie musi mieć co najmniej jedną serię');
      return;
    }

    const confirmed = confirm('Czy na pewno chcesz usunąć tę serię?');
    if (!confirmed) return;

    try {
      // Remove this set from the array (exclude temporary sets)
      const updatedSets = allSets
        .filter((s) => !s.id.startsWith('temp-') && s.id !== set.id)
        .map((s) => ({
          setsCount: s.setsCount,
          reps: s.reps,
          weightKg: s.weightKg,
        }));

      await apiClient.put(`/workout-exercises/${workoutExerciseId}`, {
        sets: updatedSets,
      });

      toast.success('Seria usunięta');
      onDelete();
    } catch (error) {
      console.error('Failed to delete set:', error);
      toast.error('Nie udało się usunąć serii');
    }
  };

  // ============================================
  // Render
  // ============================================

  return (
    <div className="grid grid-cols-12 gap-2 items-start">
      {/* Sets Count */}
      <div className="col-span-3">
        <Input
          type="number"
          min={1}
          step={1}
          value={localValue.setsCount}
          onChange={(e) => handleChange('setsCount', e.target.value)}
          error={hasError ? ' ' : undefined}
          className="h-10"
        />
      </div>

      {/* Reps */}
      <div className="col-span-3">
        <Input
          type="number"
          min={1}
          max={100}
          step={1}
          value={localValue.reps}
          onChange={(e) => handleChange('reps', e.target.value)}
          error={hasError ? ' ' : undefined}
          className="h-10"
        />
      </div>

      {/* Weight */}
      <div className="col-span-3">
        <Input
          type="number"
          min={0}
          max={500}
          step={0.5}
          value={localValue.weightKg}
          onChange={(e) => handleChange('weightKg', e.target.value)}
          error={hasError ? ' ' : undefined}
          className="h-10"
        />
      </div>

      {/* Actions */}
      <div className="col-span-2 flex items-center gap-1">
        {/* Save Button */}
        {hasChanges && (
          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={handleSave}
            className="text-blue-600 hover:text-blue-700 h-10 w-10 p-0"
            disabled={isSaving}
            title="Zapisz zmiany"
          >
            {isSaving ? (
              <svg
                className="animate-spin h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  className="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  strokeWidth="4"
                ></circle>
                <path
                  className="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
            ) : (
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
                  d="M5 13l4 4L19 7"
                />
              </svg>
            )}
          </Button>
        )}

        {/* Delete Button */}
        <Button
          type="button"
          variant="ghost"
          size="sm"
          onClick={handleDelete}
          className="text-red-600 hover:text-red-700 h-10 w-10 p-0"
          disabled={isSaving}
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
    </div>
  );
}


