// ============================================
// Recent Sessions List Component
// Displays last 5 workout sessions
// ============================================

'use client';

import Link from 'next/link';
import { useWorkoutSessions } from '@/hooks/useWorkoutSessions';
import { Button } from '@/components/ui/Button';
import { Card, CardContent } from '@/components/ui/Card';
import { Spinner } from '@/components/ui/Spinner';
import { SessionCard } from './SessionCard';

// ============================================
// Component
// ============================================

export function RecentSessionsList() {
  const { sessions, isLoading, error } = useWorkoutSessions({
    limit: 7,
    offset: 0,
    sortBy: 'createdAt',
    sortOrder: 'desc',
  });

  // ============================================
  // Loading State
  // ============================================

  if (isLoading) {
    return (
      <Card>
        <CardContent className="flex items-center justify-center py-12">
          <Spinner size="lg" />
        </CardContent>
      </Card>
    );
  }

  // ============================================
  // Error State
  // ============================================

  if (error) {
    return (
      <Card>
        <CardContent className="text-center py-8">
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
            Nie udało się załadować ostatnich sesji
          </p>
        </CardContent>
      </Card>
    );
  }

  // ============================================
  // Empty State
  // ============================================

  if (sessions.length === 0) {
    return (
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
            Brak sesji treningowych
          </h3>
          <p className="text-sm text-gray-600 mb-6">
            Nie masz jeszcze żadnych sesji. Zacznij od dodania pierwszej!
          </p>
          <Link href="/dashboard/sessions/new">
            <Button>
              <svg
                className="w-5 h-5 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 4v16m8-8H4"
                />
              </svg>
              Dodaj pierwszą sesję
            </Button>
          </Link>
        </CardContent>
      </Card>
    );
  }

  // ============================================
  // Success State - List of Sessions
  // ============================================

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold text-gray-900">
          Ostatnie sesje
        </h2>
        <Link href="/dashboard/history">
          <Button variant="ghost" size="sm">
            Zobacz wszystkie
            <svg
              className="w-4 h-4 ml-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9 5l7 7-7 7"
              />
            </svg>
          </Button>
        </Link>
      </div>

      {/* Sessions List */}
      <div className="space-y-3">
        {sessions.map((session) => (
          <SessionCard key={session.id} session={session} />
        ))}
      </div>
    </div>
  );
}

