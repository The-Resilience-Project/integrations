import { useQuery } from '@tanstack/react-query';
import { getTimeRangeMs } from '@/lib/constants';
import type { TimeRange } from '@/lib/types';

interface EntryCountsResponse {
  counts: Record<number, number>;
  configured: boolean;
}

/**
 * Fetches GF entry counts for a set of form IDs within a time range.
 * Only fetches when formIds is non-empty.
 */
export function useEntryCounts(formIds: number[], range: TimeRange) {
  const startDate = new Date(Date.now() - getTimeRangeMs(range))
    .toISOString()
    .split('T')[0]; // YYYY-MM-DD

  return useQuery<EntryCountsResponse>({
    queryKey: ['entry-counts', formIds.sort().join(','), range],
    queryFn: async () => {
      const params = new URLSearchParams({
        form_ids: formIds.join(','),
        start_date: startDate,
      });
      const res = await fetch(`/api/gf/entry-counts?${params}`);
      if (!res.ok) throw new Error('Failed to fetch entry counts');
      return res.json();
    },
    enabled: formIds.length > 0,
    staleTime: 60 * 1000, // 1 min — matches metrics refetch
  });
}
