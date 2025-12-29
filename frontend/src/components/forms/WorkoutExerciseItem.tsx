// ============================================
// Workout Exercise Item Component
// Single exercise with dynamic sets list
// ============================================

'use client';

import { useFormContext, useFieldArray } from 'react-hook-form';
import { WorkoutSessionFormData } from '@/lib/types';
import { Button } from '@/components/ui/Button';
import { Card, CardContent } from '@/components/ui/Card';
import { ExerciseSetRow } from './ExerciseSetRow';
import { useExercises } from '@/hooks/useExercises';

// ============================================
// Props Interface
// ============================================

interface WorkoutExerciseItemProps {
  index: number;
  onRemove: () => void;
}

// ============================================
// Component
// ============================================

export function WorkoutExerciseItem({
  index,
  onRemove,
}: WorkoutExerciseItemProps) {
  const { control, watch } = useFormContext<WorkoutSessionFormData>();
  const { exercises } = useExercises();

  const { fields, append, remove } = useFieldArray({
    control,
    name: `exercises.${index}.sets`,
  });

  // Get exercise ID and find exercise details
  const exerciseId = watch(`exercises.${index}.exerciseId`);
  const exercise = exercises.find((ex) => ex.id === exerciseId);

  // ============================================
  // Handlers
  // ============================================

  const handleAddSet = () => {
    // Get last set to pre-fill new set with same values
    const lastSet = fields[fields.length - 1];

    append({
      setsCount: lastSet?.setsCount || 3,
      reps: lastSet?.reps || 10,
      weightKg: lastSet?.weightKg || 0,
    });
  };

  const handleRemoveSet = (setIndex: number) => {
    if (fields.length === 1) {
      alert('Ćwiczenie musi mieć co najmniej jedną serię');
      return;
    }

    remove(setIndex);
  };

  // ============================================
  // Render
  // ============================================

  return (
    <Card>
      <CardContent className="space-y-4">
        {/* Exercise Header */}
        <div className="flex items-center justify-between">
          <div>
            <h3 className="font-semibold text-gray-900">
              {exercise?.name || 'Ładowanie...'}
            </h3>
            <p className="text-sm text-gray-500">
              {exercise?.muscleCategory.namePl}
            </p>
          </div>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={onRemove}
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
          <div className="col-span-4">Powtórzenia</div>
          <div className="col-span-4">Ciężar (kg)</div>
          <div className="col-span-1"></div>
        </div>

        {/* Sets List */}
        <div className="space-y-2">
          {fields.map((field, setIndex) => (
            <ExerciseSetRow
              key={field.id}
              exerciseIndex={index}
              setIndex={setIndex}
              onRemove={() => handleRemoveSet(setIndex)}
            />
          ))}
        </div>

        {/* Add Set Button */}
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={handleAddSet}
          disabled={fields.length >= 20}
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
          Dodaj serię {fields.length >= 20 && '(maksymalnie 20)'}
        </Button>
      </CardContent>
    </Card>
  );
}


