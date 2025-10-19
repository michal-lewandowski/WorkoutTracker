// ============================================
// History Filters Component
// Date range filter buttons (7/30/90 days + All)
// ============================================

'use client';

import { Button } from '@/components/ui/Button';

// ============================================
// Types
// ============================================

export interface DateFilter {
  from: string;
  to: string;
  label: string;
}

interface HistoryFiltersProps {
  selectedFilter: DateFilter | null;
  onFilterChange: (filter: DateFilter | null) => void;
}

// ============================================
// Helper - Calculate date range
// ============================================

function getDateRange(days: number): { from: string; to: string } {
  const today = new Date();
  const from = new Date(today);
  from.setDate(today.getDate() - days);

  return {
    from: from.toISOString().split('T')[0],
    to: today.toISOString().split('T')[0],
  };
}

// ============================================
// Component
// ============================================

export function HistoryFilters({
  selectedFilter,
  onFilterChange,
}: HistoryFiltersProps) {
  const filters = [
    { label: 'Ostatnie 7 dni', days: 7 },
    { label: 'Ostatnie 30 dni', days: 30 },
    { label: 'Ostatnie 90 dni', days: 90 },
  ];

  const handlePresetFilter = (days: number, label: string) => {
    const range = getDateRange(days);
    onFilterChange({
      from: range.from,
      to: range.to,
      label,
    });
  };

  return (
    <div className="space-y-3">
      <h3 className="text-sm font-medium text-gray-700">Filtruj po dacie</h3>
      
      <div className="flex flex-wrap gap-2">
        {/* All button */}
        <Button
          variant={selectedFilter === null ? 'primary' : 'outline'}
          size="sm"
          onClick={() => onFilterChange(null)}
        >
          Wszystkie
        </Button>

        {/* Preset filters */}
        {filters.map((filter) => (
          <Button
            key={filter.days}
            variant={
              selectedFilter?.label === filter.label ? 'primary' : 'outline'
            }
            size="sm"
            onClick={() => handlePresetFilter(filter.days, filter.label)}
          >
            {filter.label}
          </Button>
        ))}
      </div>

      {/* Active filter info */}
      {selectedFilter && (
        <p className="text-xs text-gray-600">
          Pokazuję sesje od{' '}
          {new Date(selectedFilter.from).toLocaleDateString('pl-PL')} do{' '}
          {new Date(selectedFilter.to).toLocaleDateString('pl-PL')}
        </p>
      )}
    </div>
  );
}

