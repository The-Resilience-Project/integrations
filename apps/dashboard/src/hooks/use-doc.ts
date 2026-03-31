import { useQuery } from '@tanstack/react-query';

interface DocResponse {
  content: string;
  title: string;
}

export function useDoc(slug: string | null) {
  return useQuery<DocResponse>({
    queryKey: ['doc', slug],
    queryFn: async () => {
      const res = await fetch(`/api/docs/${slug}`);
      if (!res.ok) throw new Error(`Failed to load doc: ${slug}`);
      return res.json();
    },
    enabled: !!slug,
    staleTime: 5 * 60 * 1000,
  });
}
