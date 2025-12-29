// ============================================
// Workout Session Form Component
// Main form for creating/editing workout sessions
// ============================================

'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useForm, FormProvider } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { workoutSessionSchema } from '@/lib/validations';
import { WorkoutSessionFormData } from '@/lib/types';
import { apiClient, ValidationError } from '@/lib/api';
import { Button } from '@/components/ui/Button';
import { Card, CardContent } from '@/components/ui/Card';
import { WorkoutMetadataSection } from './WorkoutMetadataSection';
import { WorkoutExercisesSection } from './WorkoutExercisesSection';
import { toast } from 'react-hot-toast';

// ============================================
// Props Interface
// ============================================

interface WorkoutSessionFormProps {
  initialData?: WorkoutSessionFormData;
  sessionId?: string;
  mode?: 'create' | 'edit';
}

// ============================================
// Draft Storage Key
// ============================================

const DRAFT_KEY = 'workout-session-draft';

// ============================================
// Component
// ============================================

export function WorkoutSessionForm({
  initialData,
  sessionId,
  mode = 'create',
}: WorkoutSessionFormProps) {
  const router = useRouter();

  // Initialize form with React Hook Form
  const form = useForm<WorkoutSessionFormData>({
    resolver: zodResolver(workoutSessionSchema),
    defaultValues: initialData || {
      date: new Date().toISOString().split('T')[0],
      name: '',
      notes: '',
      exercises: [],
    },
  });

  const {
    handleSubmit,
    formState: { errors, isSubmitting, isDirty },
    setError,
    watch,
  } = form;

  // ============================================
  // Draft Auto-save (localStorage)
  // ============================================

  useEffect(() => {
    // Only auto-save in create mode
    if (mode !== 'create') return;

    const subscription = watch((value) => {
      // Debounce save to localStorage
      const timeoutId = setTimeout(() => {
        try {
          localStorage.setItem(DRAFT_KEY, JSON.stringify(value));
        } catch (error) {
          console.error('Failed to save draft:', error);
        }
      }, 1000);

      return () => clearTimeout(timeoutId);
    });

    return () => subscription.unsubscribe();
  }, [watch, mode]);

  // ============================================
  // Restore Draft on Mount
  // ============================================

  useEffect(() => {
    // Only restore in create mode and if no initial data
    if (mode !== 'create' || initialData) return;

    try {
      const draft = localStorage.getItem(DRAFT_KEY);
      if (draft) {
        const parsed = JSON.parse(draft);
        // Check if draft has any meaningful content
        if (parsed.exercises?.length > 0 || parsed.notes || parsed.name) {
          const shouldRestore = confirm(
            'Znaleziono niezapisaną sesję. Czy chcesz ją przywrócić?'
          );

          if (shouldRestore) {
            form.reset(parsed);
          } else {
            localStorage.removeItem(DRAFT_KEY);
          }
        }
      }
    } catch (error) {
      console.error('Failed to restore draft:', error);
      localStorage.removeItem(DRAFT_KEY);
    }
  }, [form, mode, initialData]);

  // ============================================
  // Before Unload Protection
  // ============================================

  useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      if (isDirty) {
        e.preventDefault();
        e.returnValue =
          'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?';
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [isDirty]);

  // ============================================
  // Form Submit Handler
  // ============================================

  const onSubmit = async (data: WorkoutSessionFormData) => {
    try {
      if (mode === 'create') {
        // Step 1: Create workout session
        const session = await apiClient.post<{ id: string }>(
          '/workout-sessions',
          {
            date: data.date,
            name: data.name || null,
            notes: data.notes || null,
          }
        );

        // Step 2: Add exercises with sets
        for (const exercise of data.exercises) {
          await apiClient.post('/workout-exercises', {
            workoutSessionId: session.id,
            exerciseId: exercise.exerciseId,
            sets: exercise.sets,
          });
        }

        // Clear draft from localStorage
        localStorage.removeItem(DRAFT_KEY);

        // Show success message
        toast.success('Sesja treningowa została zapisana');

        // Redirect to session detail page
        router.push(`/dashboard/sessions/${session.id}`);
      } else {
        // Update mode - only update metadata
        await apiClient.put(`/workout-sessions/${sessionId}`, {
          date: data.date,
          name: data.name || null,
          notes: data.notes || null,
        });

        toast.success('Sesja została zaktualizowana');
        router.push(`/dashboard/sessions/${sessionId}`);
      }
    } catch (error) {
      console.error('Failed to save workout session:', error);

      if (error instanceof ValidationError) {
        // Display field-specific validation errors
        Object.entries(error.errors).forEach(([field, messages]) => {
          setError(field as keyof WorkoutSessionFormData, {
            message: messages[0],
          });
        });
        toast.error('Popraw błędy w formularzu');
      } else {
        toast.error('Wystąpił błąd podczas zapisywania sesji');
      }
    }
  };

  // ============================================
  // Cancel Handler
  // ============================================

  const handleCancel = () => {
    if (isDirty) {
      const shouldLeave = confirm(
        'Masz niezapisane zmiany. Czy na pewno chcesz anulować?'
      );
      if (!shouldLeave) return;
    }

    // Clear draft if in create mode
    if (mode === 'create') {
      localStorage.removeItem(DRAFT_KEY);
    }

    router.back();
  };

  // ============================================
  // Render
  // ============================================

  return (
    <FormProvider {...form}>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        {/* Metadata Section */}
        <Card>
          <CardContent className="space-y-4">
            <h2 className="text-lg font-semibold text-gray-900">
              Informacje o sesji
            </h2>
            <WorkoutMetadataSection />
          </CardContent>
        </Card>

        {/* Exercises Section */}
        {mode === 'create' && (
          <Card>
            <CardContent className="space-y-4">
              <h2 className="text-lg font-semibold text-gray-900">
                Ćwiczenia
              </h2>
              <WorkoutExercisesSection />
            </CardContent>
          </Card>
        )}

        {/* Form Errors */}
        {errors.exercises && !Array.isArray(errors.exercises) && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4">
            <p className="text-sm text-red-600">
              {errors.exercises.message as string}
            </p>
          </div>
        )}

        {/* Form Actions */}
        <div className="flex gap-3">
          <Button
            type="submit"
            size="lg"
            isLoading={isSubmitting}
            disabled={isSubmitting}
          >
            {mode === 'create' ? 'Zapisz sesję' : 'Zaktualizuj sesję'}
          </Button>
          <Button
            type="button"
            variant="outline"
            size="lg"
            onClick={handleCancel}
            disabled={isSubmitting}
          >
            Anuluj
          </Button>
        </div>
      </form>
    </FormProvider>
  );
}


