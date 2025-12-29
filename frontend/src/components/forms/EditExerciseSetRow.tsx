// ============================================
// Edit Exercise Set Row Component
// Inline editable set row with debounced auto-save
// ============================================

'use client';

import { useState, useEffect } from 'react';
import { ExerciseSet } from '@/lib/types';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { apiClient } from '@/lib/api';
import { toast } from 'react-hot-toast';
import { useDebounce } from '@/hooks/useDebounce';

// ============================================
// Props Interface
// ============================================

interface EditExerciseSetRowProps {
  set: ExerciseSet;
  workoutExerciseId: string;
  allSets: ExerciseSet[];
  onUpdate: () => void;
  onDelete: () => void;
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
}: EditExerciseSetRowProps) {
  const [localValue, setLocalValue] = useState({
    setsCount: set.setsCount,
    reps: set.reps,
    weightKg: set.weightKg,
  });

  const [isSaving, setIsSaving] = useState(false);
  const [hasError, setHasError] = useState(false);

  // Debounce local value changes
  const debouncedValue = useDebounce(localValue, 500);

  // ============================================
  // Auto-save Effect
  // ============================================

  useEffect(() => {
    // Don't save on initial mount
    const isInitialValue =
      debouncedValue.setsCount === set.setsCount &&
      debouncedValue.reps === set.reps &&
      debouncedValue.weightKg === set.weightKg;

    if (isInitialValue) return;

    // Validate values
    if (
      debouncedValue.setsCount < 1 ||
      debouncedValue.reps < 1 ||
      debouncedValue.reps > 100 ||
      debouncedValue.weightKg < 0 ||
      debouncedValue.weightKg > 500
    ) {
      setHasError(true);
      return;
    }

    setHasError(false);

    // Save to API
    const saveChanges = async () => {
      setIsSaving(true);

      try {
        // Update the current set in the array
        const updatedSets = allSets.map((s) =>
          s.id === set.id
            ? {
                setsCount: debouncedValue.setsCount,
                reps: debouncedValue.reps,
                weightKg: debouncedValue.weightKg,
              }
            : {
                setsCount: s.setsCount,
                reps: s.reps,
                weightKg: s.weightKg,
              }
        );

        await apiClient.put(`/workout-exercises/${workoutExerciseId}`, {
          sets: updatedSets,
        });

        onUpdate();
      } catch (error) {
        console.error('Failed to save set changes:', error);
        toast.error('Nie udało się zapisać zmian');
        setHasError(true);
      } finally {
        setIsSaving(false);
      }
    };

    saveChanges();
  }, [debouncedValue]); // Only run when debounced value changes

  // ============================================
  // Handlers
  // ============================================

  const handleChange = (field: keyof typeof localValue, value: number) => {
    setLocalValue((prev) => ({ ...prev, [field]: value }));
    setHasError(false);
  };

  const handleDelete = async () => {
    if (allSets.length === 1) {
      toast.error('Ćwiczenie musi mieć co najmniej jedną serię');
      return;
    }

    const confirmed = confirm('Czy na pewno chcesz usunąć tę serię?');
    if (!confirmed) return;

    try {
      // Remove this set from the array
      const updatedSets = allSets
        .filter((s) => s.id !== set.id)
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
          onChange={(e) => handleChange('setsCount', Number(e.target.value))}
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
          onChange={(e) => handleChange('reps', Number(e.target.value))}
          error={hasError ? ' ' : undefined}
          className="h-10"
        />
      </div>

      {/* Weight */}
      <div className="col-span-4">
        <Input
          type="number"
          min={0}
          max={500}
          step={0.5}
          value={localValue.weightKg}
          onChange={(e) => handleChange('weightKg', Number(e.target.value))}
          error={hasError ? ' ' : undefined}
          className="h-10"
        />
      </div>

      {/* Actions */}
      <div className="col-span-2 flex items-center gap-1">
        {/* Saving indicator */}
        {isSaving && (
          <div className="text-xs text-gray-500" title="Zapisywanie...">
            <svg
              className="animate-spin h-4 w-4 text-blue-500"
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
          </div>
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


