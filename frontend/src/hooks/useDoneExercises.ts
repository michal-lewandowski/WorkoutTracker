// ============================================
// useDoneExercises Hook
// Fetch exercises performed by user
// ============================================

'use client';

import useSWR from 'swr';
import { DoneExercise } from '@/lib/types';
import { swrFetcher } from '@/lib/api';

// ============================================
// Query Parameters Interface
// ============================================

interface DoneExercisesParams {
  dateFrom?: string;
  dateTo?: string;
  sortBy?: 'name' | 'createdAt' | 'lastUsed';
  sortOrder?: 'asc' | 'desc';
}

// ============================================
// Hook
// ============================================

/**
 * Hook to fetch exercises performed by user
 * 
 * Features:
 * - Fetches only exercises that user has performed at least once
 * - Includes workoutsCount for each exercise
 * - Supports date filtering and sorting
 * - No localStorage cache (data changes frequently)
 * - Auto-revalidates on component mount and tab focus
 * - Ensures fresh data after adding new workout exercises
 * 
 * @param params - Optional query parameters
 * @returns done exercises array, loading state, and error
 */
export function useDoneExercises(params?: DoneExercisesParams) {
  // Build query string
  const queryString = params
    ? `?${new URLSearchParams(params as Record<string, string>).toString()}`
    : '';

  const { data, error, isLoading, mutate } = useSWR<DoneExercise[]>(
    `/statistics/done-exercises${queryString}`,
    (url: string) => swrFetcher<DoneExercise[]>(url),
    {
      revalidateOnMount: true, // Odśwież przy każdym montowaniu komponentu
      revalidateOnFocus: true, // Odśwież przy powrocie do zakładki
      revalidateOnReconnect: true, // Odśwież przy powrocie połączenia
      dedupingInterval: 2000, // 2 sekundy - krótszy interval dla szybszej aktualizacji
    }
  );

  return {
    exercises: data ?? [],
    isLoading,
    error,
    mutate, // For manual cache invalidation (np. po dodaniu workout exercise)
  };
}

