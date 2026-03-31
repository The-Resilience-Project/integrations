import { useQuery } from '@tanstack/react-query';
import type { LogEntry, TimeRange } from '@/lib/types';

export function useLogs(
  functionName: string | null,
  range: TimeRange,
  filter: string,
) {
  return useQuery<{ logs: LogEntry[] }>({
    queryKey: ['logs', functionName, range, filter],
    queryFn: async () => {
      const params = new URLSearchParams({ range });
      if (filter) params.set('filter', filter);
      const res = await fetch(`/api/logs/${functionName}?${params}`);
      if (!res.ok) throw new Error('Failed to fetch logs');
      return res.json();
    },
    enabled: !!functionName,
    refetchInterval: 60 * 1000,
  });
}
