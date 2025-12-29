// ============================================
// Session Metadata Component
// Displays session notes and metadata
// ============================================

'use client';

import { WorkoutSessionDetail } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/Card';

// ============================================
// Props Interface
// ============================================

interface SessionMetadataProps {
  session: WorkoutSessionDetail;
}

// ============================================
// Component
// ============================================

export function SessionMetadata({ session }: SessionMetadataProps) {
  // Format timestamps
  const createdAt = new Date(session.createdAt).toLocaleString('pl-PL', {
    dateStyle: 'short',
    timeStyle: 'short',
  });

  const updatedAt = new Date(session.updatedAt).toLocaleString('pl-PL', {
    dateStyle: 'short',
    timeStyle: 'short',
  });

  const hasMetadata = session.notes || session.createdAt !== session.updatedAt;

  if (!hasMetadata) {
    return null;
  }

  return (
    <Card>
      <CardContent className="space-y-4">
        {/* Notes */}
        {session.notes && (
          <div>
            <h3 className="text-sm font-medium text-gray-700 mb-2">Notatki</h3>
            <p className="text-gray-900 whitespace-pre-wrap">{session.notes}</p>
          </div>
        )}

        {/* Timestamps */}
        <div className="text-xs text-gray-500 space-y-1">
          <p>Utworzono: {createdAt}</p>
          {session.createdAt !== session.updatedAt && (
            <p>Zaktualizowano: {updatedAt}</p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}


