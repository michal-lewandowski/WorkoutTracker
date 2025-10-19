// ============================================
// useExerciseStatistics Hook
// Fetch statistics for specific exercise
// ============================================

'use client';

import useSWR from 'swr';
import { ExerciseStatistics } from '@/lib/types';
import { apiClient } from '@/lib/api';

// ============================================
// Query Parameters Interface
// ============================================

interface StatisticsParams {
  dateFrom?: string;
  dateTo?: string;
  limit?: number;
}

// ============================================
// Hook
// ============================================

/**
 * Hook to fetch exercise progress statistics
 * 
 * @param exerciseId - Exercise ID (null to disable fetching)
 * @param params - Optional query parameters
 * @returns statistics data, loading state, error
 */
export function useExerciseStatistics(
  exerciseId: string | null,
  params?: StatisticsParams
) {
  const queryString = params
    ? `?${new URLSearchParams(params as Record<string, string>).toString()}`
    : '';

  const { data, error, isLoading } = useSWR<ExerciseStatistics>(
    exerciseId ? `/statistics/exercise/${exerciseId}${queryString}` : null,
    (url: string) => apiClient.get<ExerciseStatistics>(url)
  );

  return {
    statistics: data,
    isLoading,
    error,
  };
}

