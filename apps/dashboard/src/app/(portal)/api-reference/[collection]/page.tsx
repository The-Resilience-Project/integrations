'use client';

import { use } from 'react';
import Link from 'next/link';
import { ArrowLeft, Code2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { EndpointCard } from '@/components/endpoint-card';
import { usePostmanCollections } from '@/hooks/use-postman';

export default function CollectionPage({
  params,
}: {
  params: Promise<{ collection: string }>;
}) {
  const { collection: collectionSlug } = use(params);
  const { data, isLoading, error } = usePostmanCollections();

  const collection = data?.collections.find(
    (c) => c.slug === collectionSlug,
  );

  return (
    <div className="max-w-[900px] mx-auto px-6 py-6 space-y-6">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-xs text-muted-foreground">
        <Link
          href="/api-reference"
          className="flex items-center gap-1 hover:text-foreground transition-colors"
        >
          <ArrowLeft className="h-3 w-3" />
          API Reference
        </Link>
        {collection && (
          <>
            <span>/</span>
            <span className="text-foreground">{collection.name}</span>
          </>
        )}
      </div>

      {error && (
        <div className="rounded-xl border border-[var(--rose-accent)]/30 bg-[var(--rose-accent)]/5 p-4">
          <p className="text-sm text-[var(--rose-accent)]">Failed to load collections.</p>
        </div>
      )}

      {isLoading ? (
        <div className="space-y-3">
          <div className="skeleton h-8 w-48" />
          {[1, 2, 3].map((i) => (
            <div key={i} className="skeleton h-14 w-full rounded-xl" />
          ))}
        </div>
      ) : collection ? (
        <>
          <header className="pb-4 border-b border-border/50">
            <div className="flex items-center gap-2.5">
              <Code2 className="h-5 w-5 text-[var(--cyan-glow)]" />
              <h1 className="text-lg font-semibold tracking-tight">
                {collection.name}
              </h1>
              <Badge variant="secondary" className="text-[10px] font-mono">
                {collection.version}
              </Badge>
            </div>
            <p className="text-sm text-muted-foreground mt-1">
              {collection.requests.length} endpoints
            </p>
          </header>

          <div className="space-y-2">
            {collection.requests.map((request, i) => (
              <EndpointCard key={i} request={request} />
            ))}
          </div>
        </>
      ) : (
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
          <p className="text-sm text-muted-foreground">
            Collection not found: {collectionSlug}
          </p>
        </div>
      )}
    </div>
  );
}
