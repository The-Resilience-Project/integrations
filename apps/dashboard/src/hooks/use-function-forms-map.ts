import { useMemo } from 'react';
import { useForms } from './use-forms';
import { urlToFunctionName } from '@/lib/url-to-function';
import type { GravityForm } from '@/lib/types';

export interface FormRef {
  id: number;
  title: string;
  purpose: string;
  entryCount: number;
}

export type FunctionFormsMap = Record<string, FormRef[]>;

/**
 * Builds a map of Lambda function name → Gravity Forms that trigger it.
 * Uses outbound webhook endpoints from each form to determine the mapping.
 */
export function useFunctionFormsMap(): FunctionFormsMap {
  const { data } = useForms();

  return useMemo(() => {
    const map: FunctionFormsMap = {};
    if (!data?.forms) return map;

    for (const form of data.forms) {
      const outbound = form.endpoints.filter((e) => e.direction === 'outbound');
      const seenFunctions = new Set<string>();

      for (const ep of outbound) {
        const fn = urlToFunctionName(ep.endpoint);
        if (fn && !seenFunctions.has(fn)) {
          seenFunctions.add(fn);
          if (!map[fn]) map[fn] = [];
          map[fn].push({
            id: form.id,
            title: form.title,
            purpose: form.purpose,
            entryCount: form.entryCount,
          });
        }
      }
    }

    return map;
  }, [data]);
}
