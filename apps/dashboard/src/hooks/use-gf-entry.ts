import { useQuery } from '@tanstack/react-query';
import type { GFEntry } from '@/lib/gravity-forms';

interface GFEntryResponse {
  entry: GFEntry;
  configured: boolean;
}

export function useGFEntry(entryId: string | null) {
  return useQuery<GFEntryResponse | null>({
    queryKey: ['gf-entry', entryId],
    queryFn: async () => {
      const res = await fetch(`/api/gf/entries/detail/${entryId}`);
      if (res.status === 503) return null;
      if (!res.ok) throw new Error(`Failed to fetch entry ${entryId}`);
      return res.json();
    },
    enabled: entryId != null,
    staleTime: 2 * 60 * 1000,
  });
}
