'use client';

import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { Card, CardContent, CardHeader } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { formatDate } from '@/lib/utils';

// ============================================
// Profile Page
// User profile and logout
// ============================================

export default function ProfilePage() {
  const router = useRouter();
  const { user, logout } = useAuth();

  const handleLogout = () => {
    logout();
    router.push('/login');
  };

  if (!user) {
    return null;
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">
        Profil Użytkownika
      </h1>

      <Card>
        <CardHeader>
          <h2 className="text-xl font-semibold">Informacje o koncie</h2>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <label className="text-sm text-gray-600 block mb-1">Email</label>
            <p className="font-medium text-gray-900">{user.email || 'N/A'}</p>
          </div>

          <div>
            <label className="text-sm text-gray-600 block mb-1">
              Data rejestracji
            </label>
            <p className="font-medium text-gray-900">
              {formatDate(user.createdAt)}
            </p>
          </div>

          <div className="pt-4 border-t border-gray-200">
            <Button
              variant="destructive"
              fullWidth
              onClick={handleLogout}
            >
              Wyloguj się
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

