// ============================================
// Edit Workout Metadata Component
// Standalone form for editing session metadata
// ============================================

'use client';

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { updateWorkoutSessionSchema } from '@/lib/validations';
import { WorkoutSessionDetail, UpdateWorkoutSessionRequest } from '@/lib/types';
import { apiClient, ValidationError } from '@/lib/api';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Card, CardContent } from '@/components/ui/Card';
import { toast } from 'react-hot-toast';

// ============================================
// Props Interface
// ============================================

interface EditWorkoutMetadataProps {
  session: WorkoutSessionDetail;
  onUpdate: () => void;
}

// ============================================
// Component
// ============================================

export function EditWorkoutMetadata({
  session,
  onUpdate,
}: EditWorkoutMetadataProps) {
  const [isEditing, setIsEditing] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    setError,
    reset,
  } = useForm<UpdateWorkoutSessionRequest>({
    resolver: zodResolver(updateWorkoutSessionSchema),
    defaultValues: {
      date: session.date,
      name: session.name,
      notes: session.notes,
    },
  });

  // ============================================
  // Submit Handler
  // ============================================

  const onSubmit = async (data: UpdateWorkoutSessionRequest) => {
    try {
      await apiClient.put(`/workout-sessions/${session.id}`, data);
      toast.success('Metadane sesji zaktualizowane');
      setIsEditing(false);
      onUpdate();
    } catch (error) {
      console.error('Failed to update session metadata:', error);

      if (error instanceof ValidationError) {
        Object.entries(error.errors).forEach(([field, messages]) => {
          setError(field as keyof UpdateWorkoutSessionRequest, {
            message: messages[0],
          });
        });
        toast.error('Popraw błędy w formularzu');
      } else {
        toast.error('Nie udało się zaktualizować metadanych');
      }
    }
  };

  // ============================================
  // Cancel Handler
  // ============================================

  const handleCancel = () => {
    reset({
      date: session.date,
      name: session.name,
      notes: session.notes,
    });
    setIsEditing(false);
  };

  // ============================================
  // Render - Display Mode
  // ============================================

  if (!isEditing) {
    return (
      <Card>
        <CardContent>
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900">
              Informacje o sesji
            </h2>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setIsEditing(true)}
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
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              Edytuj
            </Button>
          </div>

          <div className="space-y-3">
            <div>
              <label className="text-sm font-medium text-gray-700">Data</label>
              <p className="text-gray-900">
                {new Date(session.date).toLocaleDateString('pl-PL', {
                  weekday: 'long',
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                })}
              </p>
            </div>

            <div>
              <label className="text-sm font-medium text-gray-700">Nazwa</label>
              <p className="text-gray-900">{session.name || '-'}</p>
            </div>

            <div>
              <label className="text-sm font-medium text-gray-700">
                Notatki
              </label>
              <p className="text-gray-900 whitespace-pre-wrap">
                {session.notes || '-'}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }

  // ============================================
  // Render - Edit Mode
  // ============================================

  return (
    <Card>
      <CardContent>
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          Edytuj informacje o sesji
        </h2>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
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
          </div>

          {/* Name Input */}
          <div>
            <label
              htmlFor="name"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Nazwa sesji{' '}
              <span className="text-gray-500 text-xs">(opcjonalne)</span>
            </label>
            <Input
              id="name"
              type="text"
              placeholder="np. Trening A - FBW"
              {...register('name')}
              error={errors.name?.message}
              maxLength={255}
            />
          </div>

          {/* Notes Textarea */}
          <div>
            <label
              htmlFor="notes"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Notatki{' '}
              <span className="text-gray-500 text-xs">(opcjonalne)</span>
            </label>
            <textarea
              id="notes"
              {...register('notes')}
              placeholder="Dodaj notatki o treningu..."
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              rows={3}
            />
          </div>

          {/* Form Actions */}
          <div className="flex gap-3 pt-2">
            <Button
              type="submit"
              size="sm"
              isLoading={isSubmitting}
              disabled={isSubmitting}
            >
              Zapisz zmiany
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={handleCancel}
              disabled={isSubmitting}
            >
              Anuluj
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}


