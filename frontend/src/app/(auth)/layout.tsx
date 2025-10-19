import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Autentykacja - WorkoutTracker',
};

// ============================================
// Auth Layout
// Layout for unauthenticated pages (login, register)
// ============================================

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 px-4 py-12">
      <div className="w-full max-w-md">
        {/* Logo */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-2xl mb-4">
            <span className="text-white font-bold text-3xl">W</span>
          </div>
          <h1 className="text-3xl font-bold text-gray-900">WorkoutTracker</h1>
          <p className="text-gray-600 mt-2">Śledź swoje postępy treningowe</p>
        </div>

        {/* Form container */}
        <div className="bg-white rounded-2xl shadow-xl p-8">
          {children}
        </div>

        {/* Footer */}
        <p className="text-center text-sm text-gray-600 mt-8">
          © 2025 WorkoutTracker. Wszystkie prawa zastrzeżone.
        </p>
      </div>
    </div>
  );
}

