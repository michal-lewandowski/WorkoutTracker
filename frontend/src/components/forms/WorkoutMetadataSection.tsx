// ============================================
// Workout Metadata Section Component
// Date, Name, and Notes inputs
// ============================================

'use client';

import { useFormContext } from 'react-hook-form';
import { WorkoutSessionFormData } from '@/lib/types';
import { Input } from '@/components/ui/Input';

// ============================================
// Component
// ============================================

export function WorkoutMetadataSection() {
  const {
    register,
    formState: { errors },
  } = useFormContext<WorkoutSessionFormData>();

  return (
    <div className="space-y-4">
      {/* Date Input */}
      <div>
        <label
          htmlFor="date"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Data treningu <span className="text-red-500">*</span>
        </label>
        <Input
          id="date"
          type="date"
          {...register('date')}
          error={errors.date?.message}
          max={new Date().toISOString().split('T')[0]}
        />
        {errors.date && (
          <p className="mt-1 text-sm text-red-600">{errors.date.message}</p>
        )}
      </div>

      {/* Name Input (Optional) */}
      <div>
        <label
          htmlFor="name"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Nazwa sesji <span className="text-gray-500 text-xs">(opcjonalne)</span>
        </label>
        <Input
          id="name"
          type="text"
          placeholder="np. Trening A - FBW"
          {...register('name')}
          error={errors.name?.message}
          maxLength={255}
        />
        {errors.name && (
          <p className="mt-1 text-sm text-red-600">{errors.name.message}</p>
        )}
      </div>

      {/* Notes Textarea (Optional) */}
      <div>
        <label
          htmlFor="notes"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Notatki <span className="text-gray-500 text-xs">(opcjonalne)</span>
        </label>
        <textarea
          id="notes"
          {...register('notes')}
          placeholder="Dodaj notatki o treningu..."
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          rows={3}
        />
        {errors.notes && (
          <p className="mt-1 text-sm text-red-600">{errors.notes.message}</p>
        )}
      </div>
    </div>
  );
}


