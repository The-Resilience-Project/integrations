import { useQuery } from '@tanstack/react-query';
import type { WebhookError } from '@/lib/gravity-forms';

interface WebhookErrorsResponse {
  errors: WebhookError[];
  configured: boolean;
}

export function useWebhookErrors() {
  return useQuery<WebhookErrorsResponse>({
    queryKey: ['webhook-errors'],
    queryFn: async () => {
      const res = await fetch('/api/health/webhook-errors');
      if (res.status === 503) return { errors: [], configured: false };
      if (!res.ok) throw new Error('Failed to fetch webhook errors');
      return res.json();
    },
    staleTime: 5 * 60 * 1000,
  });
}
