import { HTMLAttributes } from 'react';
import clsx from 'clsx';

// ============================================
// Card Component
// Container component for content sections
// ============================================

interface CardProps extends HTMLAttributes<HTMLDivElement> {
  variant?: 'default' | 'bordered' | 'elevated';
}

export function Card({
  children,
  variant = 'default',
  className,
  ...props
}: CardProps) {
  const variants = {
    default: 'bg-white rounded-lg shadow',
    bordered: 'bg-white rounded-lg border-2 border-gray-200',
    elevated: 'bg-white rounded-lg shadow-lg',
  };

  return (
    <div className={clsx(variants[variant], className)} {...props}>
      {children}
    </div>
  );
}

// ============================================
// Card Sub-components
// ============================================

export function CardHeader({
  children,
  className,
  ...props
}: HTMLAttributes<HTMLDivElement>) {
  return (
    <div className={clsx('px-4 py-3 border-b border-gray-200', className)} {...props}>
      {children}
    </div>
  );
}

export function CardContent({
  children,
  className,
  ...props
}: HTMLAttributes<HTMLDivElement>) {
  return (
    <div className={clsx('px-4 py-4', className)} {...props}>
      {children}
    </div>
  );
}

export function CardFooter({
  children,
  className,
  ...props
}: HTMLAttributes<HTMLDivElement>) {
  return (
    <div className={clsx('px-4 py-3 border-t border-gray-200', className)} {...props}>
      {children}
    </div>
  );
}

