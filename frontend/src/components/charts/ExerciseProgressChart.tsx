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
  Legend,
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
      <p className="text-sm font-medium text-gray-900 mb-2">
        {new Date(data.date).toLocaleDateString('pl-PL', {
          day: 'numeric',
          month: 'short',
          year: 'numeric',
        })}
      </p>
      <div className="space-y-1">
        <p className="text-sm">
          <span className="font-medium text-blue-600">Max ciężar:</span>{' '}
          <span className="font-bold">{data.maxWeightKg} kg</span>
        </p>
        <p className="text-sm">
          <span className="font-medium text-green-600">Objętość:</span>{' '}
          <span className="font-bold">{data.totalVolume} kg</span>
        </p>
      </div>
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
          margin={{ top: 5, right: 30, left: 0, bottom: 5 }}
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
          {/* Left Y-Axis - Max Weight */}
          <YAxis
            yAxisId="left"
            tickFormatter={(value) => `${value}kg`}
            tick={{ fontSize: 12, fill: '#2563eb' }}
            stroke="#2563eb"
            label={{
              value: 'Max Ciężar (kg)',
              angle: -90,
              position: 'insideLeft',
              style: { fontSize: 12, fill: '#2563eb' },
            }}
          />
          {/* Right Y-Axis - Total Volume */}
          <YAxis
            yAxisId="right"
            orientation="right"
            tickFormatter={(value) => `${value}kg`}
            tick={{ fontSize: 12, fill: '#10b981' }}
            stroke="#10b981"
            label={{
              value: 'Objętość (kg)',
              angle: 90,
              position: 'insideRight',
              style: { fontSize: 12, fill: '#10b981' },
            }}
          />
          <Tooltip content={<CustomTooltip />} />
          <Legend
            wrapperStyle={{ fontSize: '14px', paddingTop: '10px' }}
            iconType="line"
          />
          {/* Line for Max Weight */}
          <Line
            yAxisId="left"
            type="monotone"
            dataKey="maxWeightKg"
            stroke="#2563eb"
            strokeWidth={3}
            dot={{ fill: '#2563eb', r: 4 }}
            activeDot={{ r: 6 }}
            name="Max Ciężar (kg)"
          />
          {/* Line for Total Volume */}
          <Line
            yAxisId="right"
            type="monotone"
            dataKey="totalVolume"
            stroke="#10b981"
            strokeWidth={3}
            dot={{ fill: '#10b981', r: 4 }}
            activeDot={{ r: 6 }}
            name="Objętość (kg)"
          />
        </LineChart>
      </ResponsiveContainer>

      {/* Summary Stats */}
      {statistics.summary && (
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-2 pt-6 border-t border-gray-200">
          <div className="text-center">
            <p className="text-2xl font-bold text-gray-900">
              {statistics.summary.maxWeightRecord}
              <span className="text-sm text-gray-600"> kg</span>
            </p>
            <p className="text-xs text-gray-600 mt-1">Rekordowy ciężar</p>
          </div>
            <div className="text-center">
                <p className="text-2xl font-bold text-gray-900">
                    {statistics.summary.maxVolumeRecord}
                    <span className="text-sm text-gray-600"> kg</span>
                </p>
                <p className="text-xs text-gray-600 mt-1">Rekord objętości</p>
            </div>
          <div className="text-center">
            <p className="text-2xl font-bold text-gray-900">
              {statistics.summary.totalSessions}
            </p>
            <p className="text-xs text-gray-600 mt-1">Sesje treningowe</p>
          </div>
          <div className="text-center">
            <p className="text-2xl font-bold text-gray-900">
              {statistics.summary.latestWeight}
              <span className="text-sm text-gray-600"> kg</span>
            </p>
            <p className="text-xs text-gray-600 mt-1">Ostatni max ciężar</p>
          </div>
        </div>
      )}
    </div>
  );
}


