import { useQuery } from '@tanstack/react-query';
import type { MetricsResponse, TimeRange } from '@/lib/types';

export function useMetrics(range: TimeRange) {
  return useQuery<MetricsResponse>({
    queryKey: ['metrics', range],
    queryFn: async () => {
      const res = await fetch(`/api/metrics?range=${range}`);
      if (!res.ok) throw new Error('Failed to fetch metrics');
      return res.json();
    },
    refetchInterval: 60 * 1000,
  });
}
