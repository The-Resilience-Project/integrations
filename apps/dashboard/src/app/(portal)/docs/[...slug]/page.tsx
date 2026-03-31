'use client';

import { use, useMemo } from 'react';
import Link from 'next/link';
import { ArrowLeft, FileText } from 'lucide-react';
import { useDoc } from '@/hooks/use-doc';
import { useFormEndpointMap } from '@/hooks/use-form-endpoint-map';
import { DocsSidebar } from '@/components/docs-sidebar';
import { MarkdownRenderer } from '@/components/markdown-renderer';
import { getEndpointPatternsForDoc } from '@/lib/doc-to-endpoints';

const GROUP_LABELS: Record<string, string> = {
  v1: 'API v1',
  v2: 'API v2',
  flows: 'Business Flows',
  vtiger: 'Vtiger CRM',
  guides: 'Guides',
};

export default function DocPage({
  params,
}: {
  params: Promise<{ slug: string[] }>;
}) {
  const { slug } = use(params);
  const slugPath = slug.join('/');
  const groupSlug = slug[0] ?? '';
  const groupLabel = GROUP_LABELS[groupSlug] ?? groupSlug;
  const { data, isLoading, error } = useDoc(slugPath);
  const formEndpointMap = useFormEndpointMap();

  // Find forms that call endpoints documented on this page
  const calledByForms = useMemo(() => {
    const patterns = getEndpointPatternsForDoc(slugPath);
    if (patterns.length === 0) return [];

    const forms: { id: number; title: string }[] = [];
    const seen = new Set<number>();

    for (const [url, formRefs] of Object.entries(formEndpointMap)) {
      for (const pattern of patterns) {
        if (url.includes(pattern)) {
          for (const ref of formRefs) {
            if (!seen.has(ref.id)) {
              seen.add(ref.id);
              forms.push(ref);
            }
          }
        }
      }
    }

    return forms.sort((a, b) => a.id - b.id);
  }, [slugPath, formEndpointMap]);

  return (
    <div className="flex">
      {/* Docs tree sidebar */}
      <aside className="hidden lg:block w-[220px] shrink-0 border-r border-border/30 px-3 py-6 overflow-y-auto max-h-[calc(100vh-3.5rem)] sticky top-0">
        <DocsSidebar />
      </aside>

      {/* Content */}
      <div className="flex-1 max-w-[800px] mx-auto px-6 py-6">
        {/* Breadcrumb */}
        <div className="flex items-center gap-2 text-xs text-muted-foreground mb-4">
          <Link
            href="/docs"
            className="flex items-center gap-1 hover:text-foreground transition-colors"
          >
            <ArrowLeft className="h-3 w-3" />
            Docs
          </Link>
          <span>/</span>
          <Link
            href={`/docs/${groupSlug}/index`}
            className="hover:text-foreground transition-colors"
          >
            {groupLabel}
          </Link>
          {slug.length > 2 && (
            <>
              <span>/</span>
              <Link
                href={`/docs/${slug.slice(0, -1).join('/')}/index`}
                className="hover:text-foreground transition-colors capitalize"
              >
                {slug[slug.length - 2]?.split('-').join(' ')}
              </Link>
            </>
          )}
          {data && (
            <>
              <span>/</span>
              <span className="text-foreground">{data.title}</span>
            </>
          )}
        </div>

        {isLoading && (
          <div className="space-y-3">
            <div className="skeleton h-8 w-64" />
            <div className="skeleton h-4 w-full" />
            <div className="skeleton h-4 w-3/4" />
            <div className="skeleton h-32 w-full mt-4" />
          </div>
        )}

        {error && (
          <div className="rounded-xl border border-[var(--rose-accent)]/30 bg-[var(--rose-accent)]/5 p-4">
            <p className="text-sm text-[var(--rose-accent)]">
              Failed to load document: {slugPath}
            </p>
          </div>
        )}

        {data && <MarkdownRenderer content={data.content} currentSlug={slugPath} />}

        {/* Called by Forms */}
        {calledByForms.length > 0 && (
          <div className="mt-8 pt-6 border-t border-border/30">
            <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-3">
              Called by Forms
            </h3>
            <div className="flex flex-wrap gap-2">
              {calledByForms.map((form) => (
                <Link
                  key={form.id}
                  href={`/forms/${form.id}`}
                  className="flex items-center gap-1.5 rounded-lg border border-border/50 bg-card px-3 py-2 text-sm hover:border-[var(--cyan-glow)]/30 transition-colors"
                >
                  <FileText className="h-3.5 w-3.5 text-[var(--cyan-glow)]" />
                  <span className="font-mono text-xs text-muted-foreground">#{form.id}</span>
                  <span>{form.title}</span>
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
