const WP_BASE_URL =
  process.env.WP_BASE_URL || 'https://forms.theresilienceproject.com.au';

const CACHE_TTL_MS = 5 * 60 * 1000; // 5 minutes

export interface WordPressPage {
  id: number;
  title: string;
  url: string;
  slug: string;
}

interface WPPageResponse {
  id: number;
  title: { rendered: string };
  link: string;
  slug: string;
  content: { rendered: string };
}

let cached: { map: Map<number, WordPressPage>; expiresAt: number } | null = null;

/**
 * Fetch all published WordPress pages and build a map of form ID → page.
 * Parses `gform_wrapper_XX` from rendered HTML to find embedded Gravity Forms.
 * Uses in-memory cache (WP page responses are too large for Next.js fetch cache).
 */
export async function fetchFormPageMap(): Promise<Map<number, WordPressPage>> {
  if (cached && Date.now() < cached.expiresAt) {
    return cached.map;
  }

  const map = new Map<number, WordPressPage>();
  let page = 1;
  let totalPages = 1;

  do {
    const res = await fetch(
      `${WP_BASE_URL}/wp-json/wp/v2/pages?per_page=100&page=${page}&status=publish`,
      { cache: 'no-store' },
    );

    if (!res.ok) {
      console.error(`WP REST API error: ${res.status} ${res.statusText}`);
      break;
    }

    if (page === 1) {
      totalPages = parseInt(res.headers.get('X-WP-TotalPages') ?? '1', 10);
    }

    const pages: WPPageResponse[] = await res.json();

    for (const wp of pages) {
      const content = wp.content?.rendered ?? '';
      const matches = content.matchAll(/gform_wrapper_(\d+)/g);

      for (const match of matches) {
        const formId = parseInt(match[1], 10);
        if (!map.has(formId)) {
          map.set(formId, {
            id: wp.id,
            title: wp.title.rendered,
            url: wp.link,
            slug: wp.slug,
          });
        }
      }
    }

    page++;
  } while (page <= totalPages);

  cached = { map, expiresAt: Date.now() + CACHE_TTL_MS };
  return map;
}
