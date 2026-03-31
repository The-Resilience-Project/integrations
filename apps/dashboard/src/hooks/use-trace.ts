import { useQuery } from '@tanstack/react-query';
import type { LogEntry } from '@/lib/types';

interface TraceResponse {
  logs: LogEntry[];
}

interface TraceParams {
  email: string;
  fn: string;
  timestamp: string;
}

export function useTrace(params: TraceParams | null) {
  return useQuery<TraceResponse>({
    queryKey: ['trace', params?.email, params?.fn, params?.timestamp],
    queryFn: async () => {
      const sp = new URLSearchParams({
        email: params!.email,
        fn: params!.fn,
        timestamp: params!.timestamp,
        window: '600',
      });
      const res = await fetch(`/api/trace?${sp}`);
      if (!res.ok) throw new Error('Trace failed');
      return res.json();
    },
    enabled: params != null,
    staleTime: 30 * 1000,
  });
}
