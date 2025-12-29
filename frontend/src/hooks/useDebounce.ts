// ============================================
// useDebounce Hook
// Debounce any value with configurable delay
// ============================================

'use client';

import { useEffect, useState } from 'react';

/**
 * Hook to debounce a value
 * Useful for search inputs and auto-save features
 * 
 * @param value - Value to debounce
 * @param delay - Delay in milliseconds (default: 500ms)
 * @returns debounced value
 * 
 * @example
 * const [search, setSearch] = useState('');
 * const debouncedSearch = useDebounce(search, 300);
 * 
 * useEffect(() => {
 *   // This runs only after user stops typing for 300ms
 *   performSearch(debouncedSearch);
 * }, [debouncedSearch]);
 */
export function useDebounce<T>(value: T, delay: number = 500): T {
  const [debouncedValue, setDebouncedValue] = useState<T>(value);

  useEffect(() => {
    // Set timeout to update debounced value
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    // Cleanup timeout on value change or unmount
    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
}

/**
 * Hook to create a debounced callback function
 * Useful for form auto-save and API calls
 * 
 * @param callback - Function to debounce
 * @param delay - Delay in milliseconds (default: 500ms)
 * @returns debounced callback function
 * 
 * @example
 * const debouncedSave = useDebouncedCallback((data) => {
 *   apiClient.post('/save', data);
 * }, 1000);
 * 
 * // Call this on every change, but it will only execute after 1s of inactivity
 * debouncedSave(formData);
 */
export function useDebouncedCallback<T extends (...args: unknown[]) => unknown>(
  callback: T,
  delay: number = 500
): (...args: Parameters<T>) => void {
  useEffect(() => {
    // No cleanup needed for callback
  }, [callback]);

  return (...args: Parameters<T>) => {
    const handler = setTimeout(() => {
      callback(...args);
    }, delay);

    // Store timeout ID for potential cleanup
    return () => clearTimeout(handler);
  };
}


