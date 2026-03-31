import { useQuery } from '@tanstack/react-query';
import type { GFFormResults } from '@/lib/gravity-forms';

interface GFResultsResponse extends GFFormResults {
  configured: boolean;
}

export function useGFResults(formId: number | null) {
  return useQuery<GFResultsResponse | null>({
    queryKey: ['gf-results', formId],
    queryFn: async () => {
      const res = await fetch(`/api/gf/results/${formId}`);
      if (res.status === 503) return null;
      if (res.status === 404) return null;
      if (!res.ok) throw new Error(`Failed to fetch results for form ${formId}`);
      return res.json();
    },
    enabled: formId != null,
    staleTime: 5 * 60 * 1000,
  });
}
