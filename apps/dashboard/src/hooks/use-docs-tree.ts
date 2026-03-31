import { useQuery } from '@tanstack/react-query';
import type { DocTreeGroup } from '@/lib/docs-loader';

interface DocsTreeResponse {
  tree: DocTreeGroup[];
}

export function useDocsTree() {
  return useQuery<DocsTreeResponse>({
    queryKey: ['docs-tree'],
    queryFn: async () => {
      const res = await fetch('/api/docs/tree');
      if (!res.ok) throw new Error('Failed to load docs tree');
      return res.json();
    },
    staleTime: Infinity,
  });
}
