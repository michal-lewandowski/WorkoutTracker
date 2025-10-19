// ============================================
// useIntersectionObserver Hook
// Detect when element enters viewport for infinite scroll
// ============================================

'use client';

import { useEffect, useRef, RefObject } from 'react';

// ============================================
// Hook
// ============================================

/**
 * Hook to observe when an element enters the viewport
 * Useful for implementing infinite scroll
 * 
 * @param callback - Function to call when element is visible
 * @param options - IntersectionObserver options
 * @returns ref to attach to the element
 * 
 * @example
 * const loadMoreRef = useIntersectionObserver(() => {
 *   loadMore();
 * }, { threshold: 0.8 });
 * 
 * return <div ref={loadMoreRef}>Loading...</div>
 */
export function useIntersectionObserver(
  callback: () => void,
  options?: IntersectionObserverInit
): RefObject<HTMLDivElement> {
  const elementRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const element = elementRef.current;
    if (!element) return;

    const observer = new IntersectionObserver(
      (entries) => {
        // Check if element is intersecting (visible in viewport)
        if (entries[0].isIntersecting) {
          callback();
        }
      },
      {
        threshold: 0.5, // Trigger when 50% of element is visible
        ...options,
      }
    );

    observer.observe(element);

    // Cleanup
    return () => {
      if (element) {
        observer.unobserve(element);
      }
    };
  }, [callback, options]);

  return elementRef;
}

