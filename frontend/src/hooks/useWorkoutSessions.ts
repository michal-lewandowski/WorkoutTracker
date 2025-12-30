// ============================================
// useWorkoutSessions Hooks
// Fetch and manage workout sessions data
// ============================================

'use client';

import useSWR from 'swr';
import useSWRInfinite from 'swr/infinite';
import { WorkoutSessionList, WorkoutSessionDetail } from '@/lib/types';
import { apiClient } from '@/lib/api';

// ============================================
// Query Parameters Interface
// ============================================

interface WorkoutSessionsParams {
  limit?: number;
  offset?: number;
  dateFrom?: string;
  dateTo?: string;
  sortBy?: 'date' | 'createdAt';
  sortOrder?: 'asc' | 'desc';
}

// ============================================
// Basic Hook - Get paginated workout sessions
// ============================================

/**
 * Hook to fetch paginated workout sessions
 * 
 * @param params - Query parameters for filtering and pagination
 * @returns sessions array, pagination metadata, loading state, error
 */
export function useWorkoutSessions(params?: WorkoutSessionsParams) {
  const queryString = params
    ? `?${new URLSearchParams(params as Record<string, string>).toString()}`
    : '';

  const { data, error, isLoading, mutate } = useSWR<WorkoutSessionList>(
    `/workout-sessions${queryString}`,
    (url: string) => apiClient.get<WorkoutSessionList>(url)
  );

  return {
    sessions: data?.items ?? [],
    meta: data ? { total: data.total, limit: data.limit, offset: data.offset } : undefined,
    isLoading,
    error,
    mutate,
  };
}

// ============================================
// Infinite Scroll Hook - For history page
// ============================================

interface InfiniteScrollFilters {
  dateFrom?: string;
  dateTo?: string;
}

/**
 * Hook to fetch workout sessions with infinite scroll
 * Used in history page with pagination
 * 
 * @param filters - Date range filters
 * @returns sessions array, loading states, load more function
 */
export function useWorkoutSessionsInfinite(filters?: InfiniteScrollFilters) {
  const getKey = (
    pageIndex: number,
    previousPageData: WorkoutSessionList | null
  ) => {
    // Stop if we've reached the end
    if (previousPageData && !previousPageData.items.length) return null;

    const params = new URLSearchParams({
      limit: '20',
      offset: String(pageIndex * 20),
      sortBy: 'date',
      sortOrder: 'desc',
    });

    // Add filters if provided
    if (filters?.dateFrom) params.set('dateFrom', filters.dateFrom);
    if (filters?.dateTo) params.set('dateTo', filters.dateTo);

    return `/workout-sessions?${params}`;
  };

  const { data, error, size, setSize, isLoading, isValidating, mutate } =
    useSWRInfinite<WorkoutSessionList>(
      getKey,
      (url: string) => apiClient.get<WorkoutSessionList>(url)
    );

  // Flatten data from all pages
  const sessions = data ? data.flatMap((page) => page.items) : [];

  // Check if there are more pages to load
  const hasMore =
    data && data[data.length - 1]
      ? data[data.length - 1].total > sessions.length
      : false;

  return {
    sessions,
    isLoading,
    isLoadingMore: isValidating,
    error,
    hasMore,
    loadMore: () => setSize(size + 1),
    mutate,
  };
}

// ============================================
// Single Session Hook - Get session by ID
// ============================================

/**
 * Hook to fetch a single workout session by ID
 * 
 * @param id - Workout session ID (null to disable fetching)
 * @returns session data, loading state, error
 */
export function useWorkoutSession(id: string | null) {
  const { data, error, isLoading, mutate } = useSWR<WorkoutSessionDetail>(
    id ? `/workout-sessions/${id}` : null,
    (url: string) => apiClient.get<WorkoutSessionDetail>(url)
  );

  return {
    session: data,
    isLoading,
    error,
    mutate,
  };
}

