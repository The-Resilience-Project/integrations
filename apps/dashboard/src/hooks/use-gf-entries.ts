import { useQuery } from '@tanstack/react-query';
import type { GFEntry } from '@/lib/gravity-forms';

interface GFEntriesResponse {
  entries: GFEntry[];
  total_count: number;
  configured: boolean;
}

export interface UseGFEntriesParams {
  formId: number | null;
  page?: number;
  pageSize?: number;
  status?: string;
  startDate?: string;
  endDate?: string;
  searchKey?: string;
  searchValue?: string;
}

export function useGFEntries(params: UseGFEntriesParams) {
  const { formId, page = 1, pageSize = 20, status, startDate, endDate, searchKey, searchValue } = params;

  return useQuery<GFEntriesResponse>({
    queryKey: ['gf-entries', formId, page, pageSize, status, startDate, endDate, searchKey, searchValue],
    queryFn: async () => {
      const qs = new URLSearchParams();
      qs.set('page', String(page));
      qs.set('page_size', String(pageSize));
      if (status) qs.set('status', status);
      if (startDate) qs.set('start_date', startDate);
      if (endDate) qs.set('end_date', endDate);
      if (searchKey && searchValue) {
        qs.set('search_key', searchKey);
        qs.set('search_value', searchValue);
      }

      const res = await fetch(`/api/gf/entries/${formId}?${qs.toString()}`);
      if (res.status === 503) {
        return { entries: [], total_count: 0, configured: false };
      }
      if (!res.ok) throw new Error(`Failed to fetch entries for form ${formId}`);
      return res.json();
    },
    enabled: formId != null,
    staleTime: 2 * 60 * 1000,
  });
}
