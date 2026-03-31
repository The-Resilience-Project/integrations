'use client';

import Link from 'next/link';
import { Code2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { usePostmanCollections } from '@/hooks/use-postman';

export default function ApiReferencePage() {
  const { data, isLoading, error } = usePostmanCollections();

  return (
    <div className="max-w-[1000px] mx-auto px-6 py-6 space-y-6">
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5">
          <Code2 className="h-5 w-5 text-[var(--cyan-glow)]" />
          <h1 className="text-lg font-semibold tracking-tight">API Reference</h1>
        </div>
        <p className="text-sm text-muted-foreground mt-1">
          Browseable endpoint reference from Postman collections.
        </p>
      </header>

      {error && (
        <div className="rounded-xl border border-[var(--rose-accent)]/30 bg-[var(--rose-accent)]/5 p-4">
          <p className="text-sm text-[var(--rose-accent)]">Failed to load Postman collections.</p>
        </div>
      )}

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <div key={i} className="skeleton h-32 rounded-xl" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {data?.collections.map((collection) => (
            <Link key={collection.slug} href={`/api-reference/${collection.slug}`}>
              <Card className="bg-card border-border/50 hover:border-[var(--cyan-glow)]/30 transition-colors h-full">
                <CardHeader className="pb-2">
                  <div className="flex items-center justify-between">
                    <CardTitle className="text-sm font-medium">
                      {collection.name}
                    </CardTitle>
                    <Badge
                      variant="secondary"
                      className="text-[10px] font-mono"
                    >
                      {collection.version}
                    </Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-muted-foreground">
                      {collection.requests.length} endpoints
                    </span>
                    <div className="flex gap-1">
                      {[...new Set(collection.requests.map((r) => r.method))].map(
                        (method) => (
                          <Badge
                            key={method}
                            variant="secondary"
                            className="text-[9px] font-mono px-1 py-0"
                          >
                            {method}
                          </Badge>
                        ),
                      )}
                    </div>
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
