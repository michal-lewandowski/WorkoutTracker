// ============================================
// Exercise Progress Chart Component
// Line chart showing max weight progress over time
// ============================================

'use client';

import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  TooltipProps,
} from 'recharts';
import { ExerciseStatistics } from '@/lib/types';

// ============================================
// Props Interface
// ============================================

interface ExerciseProgressChartProps {
  statistics: ExerciseStatistics;
}

// ============================================
// Custom Tooltip Component
// ============================================

function CustomTooltip({ active, payload }: TooltipProps<number, string>) {
  if (!active || !payload || !payload.length) {
    return null;
  }

  const data = payload[0].payload;

  return (
    <div className="bg-white border border-gray-200 rounded-lg shadow-lg p-3">
      <p className="text-sm font-medium text-gray-900 mb-1">
        {new Date(data.date).toLocaleDateString('pl-PL', {
          day: 'numeric',
          month: 'short',
          year: 'numeric',
        })}
      </p>
      <p className="text-lg font-bold text-blue-600">
        {data.maxWeightKg} kg
      </p>
    </div>
  );
}

// ============================================
// Component
// ============================================

export function ExerciseProgressChart({
  statistics,
}: ExerciseProgressChartProps) {
  const { dataPoints } = statistics;

  if (dataPoints.length === 0) {
    return (
      <div className="flex items-center justify-center h-64 bg-gray-50 rounded-lg">
        <div className="text-center">
          <svg
            className="mx-auto h-12 w-12 text-gray-400 mb-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
            />
          </svg>
          <p className="text-sm text-gray-600">Brak danych do wyświetlenia</p>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full">
      {/* Chart */}
      <ResponsiveContainer width="100%" height={300}>
        <LineChart
          data={dataPoints}
          margin={{ top: 5, right: 10, left: 0, bottom: 5 }}
        >
          <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
          <XAxis
            dataKey="date"
            tickFormatter={(date) =>
              new Date(date).toLocaleDateString('pl-PL', {
                day: 'numeric',
                month: 'short',
              })
            }
            tick={{ fontSize: 12, fill: '#6b7280' }}
            stroke="#9ca3af"
          />
          <YAxis
            tickFormatter={(value) => `${value}kg`}
            tick={{ fontSize: 12, fill: '#6b7280' }}
            stroke="#9ca3af"
          />
          <Tooltip content={<CustomTooltip />} />
          <Line
            type="monotone"
            dataKey="maxWeightKg"
            stroke="#2563eb"
            strokeWidth={3}
            dot={{ fill: '#2563eb', r: 4 }}
            activeDot={{ r: 6 }}
          />
        </LineChart>
      </ResponsiveContainer>

      {/* Summary Stats */}
      {statistics.summary && (
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-200">
          <div className="text-center">
            <p className="text-2xl font-bold text-gray-900">
              {statistics.summary.personalRecord}
              <span className="text-sm text-gray-600"> kg</span>
            </p>
            <p className="text-xs text-gray-600 mt-1">Rekord</p>
          </div>
          <div className="text-center">
            <p className="text-2xl font-bold text-gray-900">
              {statistics.summary.totalSessions}
            </p>
            <p className="text-xs text-gray-600 mt-1">Sesji</p>
          </div>
          <div className="text-center">
            <p className="text-2xl font-bold text-gray-900">
              {statistics.summary.latestWeight}
              <span className="text-sm text-gray-600"> kg</span>
            </p>
            <p className="text-xs text-gray-600 mt-1">Ostatnio</p>
          </div>
          <div className="text-center">
            <p
              className={`text-2xl font-bold ${
                statistics.summary.progressPercentage >= 0
                  ? 'text-green-600'
                  : 'text-red-600'
              }`}
            >
              {statistics.summary.progressPercentage >= 0 ? '+' : ''}
              {statistics.summary.progressPercentage.toFixed(1)}%
            </p>
            <p className="text-xs text-gray-600 mt-1">Postęp</p>
          </div>
        </div>
      )}
    </div>
  );
}


