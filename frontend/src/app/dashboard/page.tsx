'use client';

import Link from 'next/link';
import { Button } from '@/components/ui/Button';
import { Card, CardContent } from '@/components/ui/Card';
import { RecentSessionsList } from '@/components/sessions/RecentSessionsList';
import { StatsPanel } from '@/components/charts/StatsPanel';

// ============================================
// Dashboard Page
// Main dashboard with recent sessions and stats
// ============================================

export default function DashboardPage() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
            Dashboard
          </h1>
          <p className="text-gray-600 mt-1">
            Witaj z powrotem! Gotowy na dzisiejszy trening?
          </p>
        </div>

        {/* Quick Action Button */}
        <Link href="/dashboard/sessions/new">
          <Button size="lg" className="w-full sm:w-auto">
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
            Nowa sesja
          </Button>
        </Link>
      </div>

      {/* Main Content - Two Column Layout on Desktop */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Left Column - Recent Sessions */}
        <div>
          <RecentSessionsList />
        </div>

        {/* Right Column - Stats Panel */}
        <div>
          <StatsPanel />
        </div>
      </div>
    </div>
  );
}

