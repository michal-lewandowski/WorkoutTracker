'use client';

import { AuthProvider } from '@/context/AuthContext';
import { SWRProvider } from '@/lib/swr-config';
import { Toaster } from 'react-hot-toast';

/**
 * Client-side providers wrapper
 * Separates client components from server layout
 */
export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <SWRProvider>
      <AuthProvider>
        {children}
        <Toaster
          position="top-right"
          toastOptions={{
            duration: 4000,
            style: {
              background: '#363636',
              color: '#fff',
            },
            success: {
              duration: 3000,
              iconTheme: {
                primary: '#10b981',
                secondary: '#fff',
              },
            },
            error: {
              duration: 5000,
              iconTheme: {
                primary: '#ef4444',
                secondary: '#fff',
              },
            },
          }}
        />
      </AuthProvider>
    </SWRProvider>
  );
}

