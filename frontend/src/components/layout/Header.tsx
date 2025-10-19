'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import clsx from 'clsx';

// ============================================
// Header Component
// Top navigation bar with desktop menu
// ============================================

export function Header() {
  const { user } = useAuth();
  const pathname = usePathname();

  const navItems = [
    { href: '/dashboard', label: 'Dashboard' },
    { href: '/dashboard/history', label: 'Historia' },
    { href: '/dashboard/profile', label: 'Profil' },
  ];

  return (
    <header className="bg-white border-b border-gray-200 sticky top-0 z-40">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          {/* Logo */}
          <Link href="/dashboard" className="flex items-center space-x-2">
            <div className="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
              <span className="text-white font-bold text-xl">W</span>
            </div>
            <span className="font-bold text-xl text-gray-900 hidden sm:block">
              WorkoutTracker
            </span>
          </Link>

          {/* Desktop Navigation */}
          {user && (
            <nav className="hidden lg:flex items-center space-x-8">
              {navItems.map((item) => {
                const isActive = pathname === item.href;
                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    className={clsx(
                      'text-sm font-medium transition-colors',
                      isActive
                        ? 'text-primary-600'
                        : 'text-gray-600 hover:text-gray-900'
                    )}
                  >
                    {item.label}
                  </Link>
                );
              })}
              
              {/* User Avatar */}
              <div className="flex items-center space-x-2 ml-4 pl-4 border-l border-gray-200">
                <span className="text-sm text-gray-600">
                  {user.email || 'User'}
                </span>
                <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                  <span className="text-primary-600 font-semibold">
                    {user.email?.charAt(0).toUpperCase() || 'U'}
                  </span>
                </div>
              </div>
            </nav>
          )}
        </div>
      </div>
    </header>
  );
}

