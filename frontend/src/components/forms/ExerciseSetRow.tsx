// ============================================
// Exercise Set Row Component
// Single set input row with validation
// ============================================

'use client';

import { useFormContext } from 'react-hook-form';
import { WorkoutSessionFormData } from '@/lib/types';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';

// ============================================
// Props Interface
// ============================================

interface ExerciseSetRowProps {
  exerciseIndex: number;
  setIndex: number;
  onRemove: () => void;
}

// ============================================
// Component
// ============================================

export function ExerciseSetRow({
  exerciseIndex,
  setIndex,
  onRemove,
}: ExerciseSetRowProps) {
  const {
    register,
    formState: { errors },
  } = useFormContext<WorkoutSessionFormData>();

  // Get errors for this set
  const setErrors = errors.exercises?.[exerciseIndex]?.sets?.[setIndex];

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
          {...register(
            `exercises.${exerciseIndex}.sets.${setIndex}.setsCount` as const,
            {
              valueAsNumber: true,
            }
          )}
          error={setErrors?.setsCount?.message}
          className="h-10"
        />
      </div>

      {/* Reps */}
      <div className="col-span-4">
        <Input
          type="number"
          min={1}
          max={100}
          step={1}
          {...register(
            `exercises.${exerciseIndex}.sets.${setIndex}.reps` as const,
            {
              valueAsNumber: true,
            }
          )}
          error={setErrors?.reps?.message}
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
          {...register(
            `exercises.${exerciseIndex}.sets.${setIndex}.weightKg` as const,
            {
              valueAsNumber: true,
            }
          )}
          error={setErrors?.weightKg?.message}
          className="h-10"
        />
      </div>

      {/* Remove Button */}
      <div className="col-span-1 flex items-center">
        <Button
          type="button"
          variant="ghost"
          size="sm"
          onClick={onRemove}
          className="text-red-600 hover:text-red-700 h-10 w-10 p-0"
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

