// ============================================
// useExercises Hook
// Client-side exercise dictionary with localStorage cache
// ============================================

'use client';

import useSWR from 'swr';
import { useEffect } from 'react';
import { Exercise } from '@/lib/types';
import { swrFetcher } from '@/lib/api';

const CACHE_KEY = 'exercises_cache';
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours

interface CachedExercises {
  data: Exercise[];
  timestamp: number;
}

/**
 * Load exercises from localStorage cache
 */
function loadFromCache(): Exercise[] | undefined {
  if (typeof window === 'undefined') return undefined;

  try {
    const cached = localStorage.getItem(CACHE_KEY);
    if (!cached) return undefined;

    const { data, timestamp }: CachedExercises = JSON.parse(cached);
    const age = Date.now() - timestamp;

    // Cache expired - remove it
    if (age > CACHE_DURATION) {
      localStorage.removeItem(CACHE_KEY);
      return undefined;
    }

    return data;
  } catch (error) {
    console.error('Failed to load exercises from cache:', error);
    return undefined;
  }
}

/**
 * Save exercises to localStorage cache
 */
function saveToCache(data: Exercise[]): void {
  if (typeof window === 'undefined') return;

  try {
    const cached: CachedExercises = {
      data,
      timestamp: Date.now(),
    };
    localStorage.setItem(CACHE_KEY, JSON.stringify(cached));
  } catch (error) {
    console.error('Failed to save exercises to cache:', error);
  }
}

/**
 * Hook to fetch and cache exercises
 * 
 * Features:
 * - Fetches from API on first load
 * - Caches in localStorage for 24 hours
 * - Revalidates on focus and reconnect disabled
 * 
 * @returns exercises array, loading state, and error
 */
export function useExercises() {
  const { data, error, isLoading, mutate } = useSWR<Exercise[]>(
    '/exercises',
    (url: string) => swrFetcher<Exercise[]>(url),
    {
      // Try to load from cache first
      fallbackData: loadFromCache(),
      revalidateOnFocus: false,
      revalidateOnReconnect: false,
      dedupingInterval: 60000, // 1 minute
    }
  );

  // Save to cache when data changes
  useEffect(() => {
    if (data) {
      saveToCache(data);
    }
  }, [data]);

  return {
    exercises: data ?? [],
    isLoading,
    error,
    mutate, // For manual cache invalidation
  };
}

/**
 * Hook to get exercises by muscle category
 * 
 * @param muscleCategoryId - Filter by muscle category ID
 * @returns filtered exercises array
 */
export function useExercisesByCategory(muscleCategoryId: string | null) {
  const { exercises, isLoading, error } = useExercises();

  const filteredExercises = muscleCategoryId
    ? exercises.filter((ex) => ex.muscleCategoryId === muscleCategoryId)
    : exercises;

  return {
    exercises: filteredExercises,
    isLoading,
    error,
  };
}

