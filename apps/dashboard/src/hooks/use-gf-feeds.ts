import { useQuery } from '@tanstack/react-query';
import type { GFFeed, GFWebhookFeed } from '@/lib/gravity-forms';

interface GFFeedsResponse {
  feeds: GFFeed[];
  webhooks: GFWebhookFeed[];
  configured: boolean;
}

export function useGFFeeds(formId: number | null) {
  return useQuery<GFFeedsResponse>({
    queryKey: ['gf-feeds', formId],
    queryFn: async () => {
      const res = await fetch(`/api/gf/feeds/${formId}`);
      if (res.status === 503) {
        return { feeds: [], webhooks: [], configured: false };
      }
      if (!res.ok) throw new Error(`Failed to fetch feeds for form ${formId}`);
      return res.json();
    },
    enabled: formId != null,
    staleTime: 5 * 60 * 1000,
  });
}
