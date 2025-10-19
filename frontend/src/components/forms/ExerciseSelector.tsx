// ============================================
// Exercise Selector Component
// Search and filter exercises with client-side filtering
// ============================================

'use client';

import { useState, useMemo } from 'react';
import { useExercises } from '@/hooks/useExercises';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { Spinner } from '@/components/ui/Spinner';

// ============================================
// Props Interface
// ============================================

interface ExerciseSelectorProps {
  onSelectExercise: (exerciseId: string) => void;
}

// ============================================
// Component
// ============================================

export function ExerciseSelector({ onSelectExercise }: ExerciseSelectorProps) {
  const { exercises, isLoading, error } = useExercises();
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState<string | null>(null);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);

  // ============================================
  // Filtered Exercises (Client-side)
  // ============================================

  const filteredExercises = useMemo(() => {
    return exercises.filter((exercise) => {
      const matchesSearch = exercise.name
        .toLowerCase()
        .includes(search.toLowerCase());

      const matchesCategory =
        !categoryFilter || exercise.muscleCategoryId === categoryFilter;

      return matchesSearch && matchesCategory;
    });
  }, [exercises, search, categoryFilter]);

  // Get unique muscle categories
  const categories = useMemo(() => {
    const uniqueCategories = new Map();
    exercises.forEach((exercise) => {
      if (!uniqueCategories.has(exercise.muscleCategoryId)) {
        uniqueCategories.set(exercise.muscleCategoryId, exercise.muscleCategory);
      }
    });
    return Array.from(uniqueCategories.values());
  }, [exercises]);

  // ============================================
  // Handlers
  // ============================================

  const handleSelectExercise = (exerciseId: string) => {
    onSelectExercise(exerciseId);
    setSearch('');
    setIsDropdownOpen(false);
  };

  // ============================================
  // Render
  // ============================================

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-4">
        <p className="text-sm text-red-600">
          Nie udało się załadować listy ćwiczeń. Spróbuj odświeżyć stronę.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Search Input */}
      <div className="relative">
        <Input
          type="text"
          placeholder="Szukaj ćwiczenia..."
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setIsDropdownOpen(true);
          }}
          onFocus={() => setIsDropdownOpen(true)}
          disabled={isLoading}
        />
        {isLoading && (
          <div className="absolute right-3 top-1/2 -translate-y-1/2">
            <Spinner size="sm" />
          </div>
        )}
      </div>

      {/* Category Filter */}
      {categories.length > 0 && (
        <div className="flex flex-wrap gap-2">
          <Button
            type="button"
            variant={categoryFilter === null ? 'primary' : 'outline'}
            size="sm"
            onClick={() => setCategoryFilter(null)}
          >
            Wszystkie
          </Button>
          {categories.map((category) => (
            <Button
              key={category.id}
              type="button"
              variant={categoryFilter === category.id ? 'primary' : 'outline'}
              size="sm"
              onClick={() => setCategoryFilter(category.id)}
            >
              {category.namePl}
            </Button>
          ))}
        </div>
      )}

      {/* Dropdown with filtered exercises */}
      {isDropdownOpen && search.length > 0 && (
        <div className="relative">
          <div className="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto">
            {filteredExercises.length === 0 ? (
              <div className="p-4 text-center text-gray-500">
                <p className="text-sm">Nie znaleziono ćwiczeń</p>
              </div>
            ) : (
              <ul>
                {filteredExercises.map((exercise) => (
                  <li key={exercise.id}>
                    <button
                      type="button"
                      onClick={() => handleSelectExercise(exercise.id)}
                      className="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0"
                    >
                      <div className="font-medium text-gray-900">
                        {exercise.name}
                      </div>
                      <div className="text-sm text-gray-500">
                        {exercise.muscleCategory.namePl}
                      </div>
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      )}

      {/* Quick Add Buttons (when no search) */}
      {!search && filteredExercises.length > 0 && (
        <div>
          <p className="text-sm text-gray-600 mb-2">Popularne ćwiczenia:</p>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
            {filteredExercises.slice(0, 6).map((exercise) => (
              <Button
                key={exercise.id}
                type="button"
                variant="outline"
                size="sm"
                onClick={() => handleSelectExercise(exercise.id)}
                className="justify-start text-left"
              >
                <span className="truncate">{exercise.name}</span>
              </Button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

