import { useQuery } from '@tanstack/react-query';
import type { EntryNote } from '@/lib/gravity-forms';

interface EntryNotesResponse {
  notes: EntryNote[];
  configured: boolean;
}

export function useEntryNotes(entryId: string | null) {
  return useQuery<EntryNotesResponse | null>({
    queryKey: ['gf-entry-notes', entryId],
    queryFn: async () => {
      const res = await fetch(`/api/gf/entries/${entryId}/notes`);
      if (res.status === 503) return null;
      if (!res.ok) throw new Error(`Failed to fetch notes for entry ${entryId}`);
      return res.json();
    },
    enabled: entryId != null,
    staleTime: 2 * 60 * 1000,
  });
}
