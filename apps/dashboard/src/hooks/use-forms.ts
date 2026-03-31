import { useQuery } from '@tanstack/react-query';
import type { GravityForm } from '@/lib/types';

interface FormsResponse {
  forms: GravityForm[];
  configured: boolean;
  error?: string;
}

export function useForms() {
  return useQuery<FormsResponse>({
    queryKey: ['forms'],
    queryFn: async () => {
      const res = await fetch('/api/forms');
      if (res.status === 503) {
        const data = await res.json();
        return { forms: [], configured: false, error: data.error };
      }
      if (!res.ok) throw new Error('Failed to fetch forms');
      return res.json();
    },
    staleTime: 5 * 60 * 1000,
  });
}
