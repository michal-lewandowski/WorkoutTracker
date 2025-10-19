import clsx from 'clsx';

// ============================================
// Skeleton Component
// Loading placeholder for content
// ============================================

interface SkeletonProps {
  className?: string;
  variant?: 'text' | 'circular' | 'rectangular';
}

export function Skeleton({
  className,
  variant = 'rectangular',
}: SkeletonProps) {
  const variants = {
    text: 'h-4 rounded',
    circular: 'rounded-full',
    rectangular: 'rounded-lg',
  };

  return (
    <div
      className={clsx(
        'animate-pulse bg-gray-200',
        variants[variant],
        className
      )}
    />
  );
}

// ============================================
// Skeleton List Component
// Multiple skeleton items for lists
// ============================================

interface SkeletonListProps {
  count: number;
  className?: string;
}

export function SkeletonList({ count, className }: SkeletonListProps) {
  return (
    <div className="space-y-4">
      {Array.from({ length: count }).map((_, index) => (
        <div
          key={index}
          className={clsx(
            'bg-white rounded-lg shadow p-4 animate-pulse',
            className
          )}
        >
          <Skeleton className="h-6 w-1/3 mb-2" />
          <Skeleton className="h-4 w-1/2 mb-1" />
          <Skeleton className="h-3 w-1/4" />
        </div>
      ))}
    </div>
  );
}

// ============================================
// Card Skeleton
// Skeleton for card components
// ============================================

export function CardSkeleton() {
  return (
    <div className="bg-white rounded-lg shadow p-4 animate-pulse">
      <Skeleton className="h-6 w-1/3 mb-2" />
      <Skeleton className="h-4 w-1/2 mb-1" />
      <Skeleton className="h-3 w-1/4" />
    </div>
  );
}

// ============================================
// Chart Skeleton
// Skeleton for chart components
// ============================================

export function ChartSkeleton() {
  return (
    <div className="bg-white rounded-lg shadow p-6 animate-pulse">
      <Skeleton className="h-6 w-1/4 mb-6" />
      <div className="space-y-3">
        <Skeleton className="h-48 w-full" />
        <div className="flex justify-between">
          <Skeleton className="h-4 w-16" />
          <Skeleton className="h-4 w-16" />
          <Skeleton className="h-4 w-16" />
          <Skeleton className="h-4 w-16" />
        </div>
      </div>
    </div>
  );
}

