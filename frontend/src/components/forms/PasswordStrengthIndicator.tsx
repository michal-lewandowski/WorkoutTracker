'use client';

import { checkPasswordStrength } from '@/lib/validations';
import clsx from 'clsx';

// ============================================
// Password Strength Indicator Component
// Visual feedback for password requirements
// ============================================

interface PasswordStrengthIndicatorProps {
  password: string;
  show?: boolean;
}

export function PasswordStrengthIndicator({
  password,
  show = true,
}: PasswordStrengthIndicatorProps) {
  if (!show) return null;

  const strength = checkPasswordStrength(password);
  const requirements = [
    {
      label: 'Minimum 8 znaków',
      met: strength.minLength,
    },
    {
      label: 'Co najmniej jedna wielka litera',
      met: strength.hasUppercase,
    },
    {
      label: 'Co najmniej jedna cyfra',
      met: strength.hasDigit,
    },
  ];

  return (
    <div className="mt-2 space-y-2">
      <p className="text-sm font-medium text-gray-700">Wymagania hasła:</p>
      <ul className="space-y-1">
        {requirements.map((req, index) => (
          <li key={index} className="flex items-center space-x-2 text-sm">
            <div
              className={clsx(
                'w-5 h-5 rounded-full flex items-center justify-center transition-colors',
                req.met
                  ? 'bg-green-500'
                  : password.length > 0
                  ? 'bg-red-500'
                  : 'bg-gray-300'
              )}
            >
              {req.met && (
                <svg
                  className="w-3 h-3 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={3}
                    d="M5 13l4 4L19 7"
                  />
                </svg>
              )}
              {!req.met && password.length > 0 && (
                <svg
                  className="w-3 h-3 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={3}
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              )}
            </div>
            <span
              className={clsx(
                req.met
                  ? 'text-green-700'
                  : password.length > 0
                  ? 'text-red-700'
                  : 'text-gray-600'
              )}
            >
              {req.label}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}

