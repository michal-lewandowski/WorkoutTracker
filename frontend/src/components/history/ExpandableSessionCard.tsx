// ============================================
// Expandable Session Card Component
// Session card that expands to show exercises on click
// ============================================

'use client';

import { useState } from 'react';
import Link from 'next/link';
import { useWorkoutSession } from '@/hooks/useWorkoutSessions';
import { WorkoutSessionSummary } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';

// ============================================
// Props Interface
// ============================================

interface ExpandableSessionCardProps {
  session: WorkoutSessionSummary;
}

// ============================================
// Component
// ============================================

export function ExpandableSessionCard({
  session,
}: ExpandableSessionCardProps) {
  const [isExpanded, setIsExpanded] = useState(false);

  // Only fetch details when expanded
  const { session: details, isLoading } = useWorkoutSession(
    isExpanded ? session.id : null
  );

  // Format date
  const formattedDate = new Date(session.date).toLocaleDateString('pl-PL', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });

  // ============================================
  // Toggle Handler
  // ============================================

  const handleToggle = () => {
    setIsExpanded(!isExpanded);
  };

  // ============================================
  // Render
  // ============================================

  return (
    <Card>
      <CardContent>
        {/* Collapsed View - Clickable */}
        <div
          className="flex justify-between items-center cursor-pointer"
          onClick={handleToggle}
        >
          <div className="flex items-center gap-4 flex-1">
            {/* Date Badge */}
            <div className="flex flex-col items-center justify-center w-16 h-16 bg-blue-50 rounded-lg flex-shrink-0">
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
            <div className="flex-1 min-w-0">
              <h3 className="font-semibold text-gray-900 truncate">
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

          {/* Expand/Collapse Icon */}
          <div className="ml-4">
            <svg
              className={`w-5 h-5 text-gray-400 transition-transform ${
                isExpanded ? 'rotate-180' : ''
              }`}
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </div>
        </div>

        {/* Expanded View - Details */}
        {isExpanded && (
          <div className="mt-4 pt-4 border-t border-gray-200">
            {isLoading ? (
              <div className="flex items-center justify-center py-8">
                <Spinner size="md" />
              </div>
            ) : details ? (
              <div className="space-y-4">
                {/* Notes */}
                {details.notes && (
                  <div>
                    <p className="text-sm font-medium text-gray-700 mb-1">
                      Notatki
                    </p>
                    <p className="text-sm text-gray-600 whitespace-pre-wrap">
                      {details.notes}
                    </p>
                  </div>
                )}

                {/* Exercises List */}
                {details.workoutExercises.length > 0 ? (
                  <div>
                    <p className="text-sm font-medium text-gray-700 mb-2">
                      Ćwiczenia
                    </p>
                    <div className="space-y-3">
                      {details.workoutExercises.map((workoutExercise, index) => (
                        <div
                          key={workoutExercise.id}
                          className="bg-gray-50 rounded-lg p-3"
                        >
                          <div className="flex items-start gap-2">
                            <span className="flex items-center justify-center w-6 h-6 bg-blue-100 text-blue-600 rounded-full font-semibold text-xs flex-shrink-0 mt-0.5">
                              {index + 1}
                            </span>
                            <div className="flex-1 min-w-0">
                              <p className="font-medium text-gray-900 text-sm">
                                {workoutExercise.exercise.name}
                              </p>
                              <div className="flex flex-wrap gap-2 mt-2">
                                {workoutExercise.exerciseSets.map((set) => (
                                  <span
                                    key={set.id}
                                    className="inline-flex items-center px-2 py-1 bg-white border border-gray-200 rounded text-xs"
                                  >
                                    <span className="font-mono font-medium text-gray-900">
                                      {set.setsCount}×{set.reps}
                                    </span>
                                    <span className="mx-1 text-gray-400">@</span>
                                    <span className="font-semibold text-blue-600">
                                      {set.weightKg}kg
                                    </span>
                                  </span>
                                ))}
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ) : (
                  <p className="text-sm text-gray-500 text-center py-4">
                    Brak ćwiczeń w tej sesji
                  </p>
                )}

                {/* Action Buttons */}
                <div className="flex gap-2 pt-2">
                  <Link href={`/dashboard/sessions/${session.id}`} className="flex-1">
                    <Button variant="outline" size="sm" className="w-full">
                      <svg
                        className="w-4 h-4 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />
                      </svg>
                      Zobacz
                    </Button>
                  </Link>
                  <Link href={`/dashboard/sessions/${session.id}/edit`} className="flex-1">
                    <Button variant="outline" size="sm" className="w-full">
                      <svg
                        className="w-4 h-4 mr-2"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                      Edytuj
                    </Button>
                  </Link>
                </div>
              </div>
            ) : (
              <p className="text-sm text-red-600 text-center py-4">
                Nie udało się załadować szczegółów sesji
              </p>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

