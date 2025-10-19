'use client';

import { useState } from 'react';
import { useWorkoutSessionsInfinite } from '@/hooks/useWorkoutSessions';
import { useIntersectionObserver } from '@/hooks/useIntersectionObserver';
import { Card, CardContent } from '@/components/ui/Card';
import { Spinner } from '@/components/ui/Spinner';
import { HistoryFilters, DateFilter } from '@/components/history/HistoryFilters';
import { ExpandableSessionCard } from '@/components/history/ExpandableSessionCard';

// ============================================
// History Page
// Browse all workout sessions with infinite scroll
// ============================================

export default function HistoryPage() {
  const [dateFilter, setDateFilter] = useState<DateFilter | null>(null);

  // Fetch sessions with infinite scroll
  const {
    sessions,
    isLoading,
    isLoadingMore,
    error,
    hasMore,
    loadMore,
  } = useWorkoutSessionsInfinite(
    dateFilter
      ? {
          dateFrom: dateFilter.from,
          dateTo: dateFilter.to,
        }
      : undefined
  );

  // Intersection observer for infinite scroll
  const loadMoreRef = useIntersectionObserver(
    () => {
      if (hasMore && !isLoadingMore) {
        loadMore();
      }
    },
    { threshold: 0.8 }
  );

  // ============================================
  // Loading State (Initial)
  // ============================================

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
            Historia treningów
          </h1>
          <p className="text-gray-600 mt-1">
            Przeglądaj wszystkie swoje sesje treningowe
          </p>
        </div>

        <Card>
          <CardContent>
            <HistoryFilters
              selectedFilter={dateFilter}
              onFilterChange={setDateFilter}
            />
          </CardContent>
        </Card>

        <div className="flex items-center justify-center py-12">
          <Spinner size="lg" />
        </div>
      </div>
    );
  }

  // ============================================
  // Error State
  // ============================================

  if (error) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
            Historia treningów
          </h1>
          <p className="text-gray-600 mt-1">
            Przeglądaj wszystkie swoje sesje treningowe
          </p>
        </div>

        <Card>
          <CardContent className="text-center py-12">
            <svg
              className="mx-auto h-12 w-12 text-red-500 mb-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
            <p className="text-sm text-red-600">
              Nie udało się załadować historii sesji
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  // ============================================
  // Success State
  // ============================================

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
          Historia treningów
        </h1>
        <p className="text-gray-600 mt-1">
          Przeglądaj wszystkie swoje sesje treningowe
        </p>
      </div>

      {/* Filters */}
      <Card>
        <CardContent>
          <HistoryFilters
            selectedFilter={dateFilter}
            onFilterChange={setDateFilter}
          />
        </CardContent>
      </Card>

      {/* Sessions List */}
      {sessions.length === 0 ? (
        // Empty State
        <Card>
          <CardContent className="text-center py-12">
            <svg
              className="mx-auto h-16 w-16 text-gray-400 mb-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
              />
            </svg>
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              {dateFilter
                ? 'Brak sesji w wybranym okresie'
                : 'Brak sesji treningowych'}
            </h3>
            <p className="text-sm text-gray-600">
              {dateFilter
                ? 'Zmień zakres dat lub usuń filtr aby zobaczyć więcej sesji'
                : 'Nie masz jeszcze żadnych sesji. Zacznij od dodania pierwszej!'}
            </p>
          </CardContent>
        </Card>
      ) : (
        <>
          {/* Sessions Count */}
          <div className="text-sm text-gray-600">
            Znaleziono <strong>{sessions.length}</strong>{' '}
            {sessions.length === 1
              ? 'sesję'
              : sessions.length < 5
              ? 'sesje'
              : 'sesji'}
            {hasMore && ' (ładowanie kolejnych...)'}
          </div>

          {/* Expandable Cards */}
          <div className="space-y-3">
            {sessions.map((session) => (
              <ExpandableSessionCard key={session.id} session={session} />
            ))}
          </div>

          {/* Load More Trigger */}
          {hasMore && (
            <div ref={loadMoreRef} className="py-8">
              {isLoadingMore ? (
                <div className="flex items-center justify-center">
                  <Spinner size="md" />
                  <span className="ml-3 text-sm text-gray-600">
                    Ładowanie kolejnych sesji...
                  </span>
                </div>
              ) : (
                <div className="text-center text-sm text-gray-500">
                  Przewiń w dół aby załadować więcej
                </div>
              )}
            </div>
          )}

          {/* End of list */}
          {!hasMore && sessions.length > 0 && (
            <div className="text-center py-8">
              <p className="text-sm text-gray-500">
                Wszystkie sesje zostały załadowane
              </p>
            </div>
          )}
        </>
      )}
    </div>
  );
}

