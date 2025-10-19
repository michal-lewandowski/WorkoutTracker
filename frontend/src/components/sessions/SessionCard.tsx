// ============================================
// Session Card Component
// Compact card for displaying session summary in lists
// ============================================

'use client';

import Link from 'next/link';
import { WorkoutSessionSummary } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/Card';

// ============================================
// Props Interface
// ============================================

interface SessionCardProps {
  session: WorkoutSessionSummary;
}

// ============================================
// Component
// ============================================

export function SessionCard({ session }: SessionCardProps) {
  // Format date
  const formattedDate = new Date(session.date).toLocaleDateString('pl-PL', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  });

  return (
    <Link href={`/dashboard/sessions/${session.id}`}>
      <Card className="hover:shadow-md transition-shadow cursor-pointer">
        <CardContent className="flex items-center justify-between">
          {/* Left side - Date badge + Info */}
          <div className="flex items-center gap-4">
            {/* Date Badge */}
            <div className="flex flex-col items-center justify-center w-16 h-16 bg-blue-50 rounded-lg">
              <span className="text-2xl font-bold text-blue-600">
                {new Date(session.date).getDate()}
              </span>
              <span className="text-xs text-blue-600 uppercase">
                {new Date(session.date).toLocaleDateString('pl-PL', {
                  month: 'short',
                })}
              </span>
            </div>

            {/* Session Info */}
            <div className="flex-1">
              <h3 className="font-semibold text-gray-900">
                {session.name || 'Sesja treningowa'}
              </h3>
              <p className="text-sm text-gray-600 capitalize">{formattedDate}</p>
              <div className="flex items-center gap-3 mt-1">
                <span className="inline-flex items-center text-xs text-gray-500">
                  <svg
                    className="w-4 h-4 mr-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                    />
                  </svg>
                  {session.exerciseCount}{' '}
                  {session.exerciseCount === 1 ? 'ćwiczenie' : 'ćwiczeń'}
                </span>
              </div>
            </div>
          </div>

          {/* Right side - Arrow */}
          <div>
            <svg
              className="w-5 h-5 text-gray-400"
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
          </div>
        </CardContent>
      </Card>
    </Link>
  );
}

