import { useQuery } from '@tanstack/react-query';
import type { PostmanCollection } from '@/lib/postman-parser';

interface PostmanResponse {
  collections: PostmanCollection[];
  environments: Record<string, string>[];
}

export function usePostmanCollections() {
  return useQuery<PostmanResponse>({
    queryKey: ['postman-collections'],
    queryFn: async () => {
      const res = await fetch('/api/postman');
      if (!res.ok) throw new Error('Failed to load Postman collections');
      return res.json();
    },
    staleTime: Infinity,
  });
}
