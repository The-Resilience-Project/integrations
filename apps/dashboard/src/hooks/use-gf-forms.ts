import { useQuery } from '@tanstack/react-query';
import type { GFFormSummary } from '@/lib/gravity-forms';

interface GFFormsResponse {
  forms: GFFormSummary[];
  configured: boolean;
}

export function useGFForms() {
  return useQuery<GFFormsResponse>({
    queryKey: ['gf-forms'],
    queryFn: async () => {
      const res = await fetch('/api/gf/forms');
      if (res.status === 503) {
        return { forms: [], configured: false };
      }
      if (!res.ok) throw new Error('Failed to fetch GF forms');
      return res.json();
    },
    staleTime: 5 * 60 * 1000,
  });
}
