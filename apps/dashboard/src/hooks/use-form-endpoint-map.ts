import { useMemo } from 'react';
import { useForms } from './use-forms';

interface FormRef {
  id: number;
  title: string;
}

/**
 * Builds a reverse map: endpoint URL path → forms that call it.
 * Uses the outbound webhook endpoints from the forms data.
 */
export function useFormEndpointMap(): Record<string, FormRef[]> {
  const { data } = useForms();

  return useMemo(() => {
    const map: Record<string, FormRef[]> = {};
    if (!data?.forms) return map;

    for (const form of data.forms) {
      for (const ep of form.endpoints) {
        if (ep.direction === 'outbound') {
          // Extract the path portion (strip method prefix)
          const urlPath = ep.endpoint.replace(/^(GET|POST|PUT|DELETE)\s+/, '');
          const existing = map[urlPath] ?? [];
          existing.push({ id: form.id, title: form.purpose || form.title });
          map[urlPath] = existing;
        }
      }
    }

    return map;
  }, [data]);
}
