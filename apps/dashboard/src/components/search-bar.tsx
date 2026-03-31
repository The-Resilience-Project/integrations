'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { Search, FileText, Code2, BookOpen, X } from 'lucide-react';
import { useDocsTree } from '@/hooks/use-docs-tree';
import { usePostmanCollections } from '@/hooks/use-postman';
import { useForms } from '@/hooks/use-forms';
import { cn } from '@/lib/utils';

interface SearchResult {
  type: 'doc' | 'endpoint' | 'form';
  title: string;
  subtitle: string;
  href: string;
}

export function SearchBar({ collapsed }: { collapsed: boolean }) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [selectedIndex, setSelectedIndex] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);
  const router = useRouter();

  const { data: docsData } = useDocsTree();
  const { data: postmanData } = usePostmanCollections();
  const { data: formsData } = useForms();

  // Cmd+K shortcut
  useEffect(() => {
    function handleKeyDown(e: KeyboardEvent) {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        setOpen(true);
      }
      if (e.key === 'Escape') {
        setOpen(false);
        setQuery('');
      }
    }
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, []);

  // Focus input when opened
  useEffect(() => {
    if (open) {
      setTimeout(() => inputRef.current?.focus(), 50);
    }
  }, [open]);

  const results = useSearchResults(query, docsData, postmanData, formsData);

  // Reset selection when results change
  useEffect(() => {
    setSelectedIndex(0);
  }, [results.length]);

  const navigate = useCallback(
    (href: string) => {
      router.push(href);
      setOpen(false);
      setQuery('');
    },
    [router],
  );

  function handleKeyDown(e: React.KeyboardEvent) {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setSelectedIndex((i) => Math.min(i + 1, results.length - 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setSelectedIndex((i) => Math.max(i - 1, 0));
    } else if (e.key === 'Enter' && results[selectedIndex]) {
      navigate(results[selectedIndex].href);
    }
  }

  const typeIcon = {
    doc: BookOpen,
    endpoint: Code2,
    form: FileText,
  };

  return (
    <>
      {/* Trigger button */}
      <button
        onClick={() => setOpen(true)}
        className={cn(
          'flex items-center gap-2 rounded-md border border-border/50 bg-secondary/50 text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground',
          collapsed ? 'p-2 justify-center' : 'px-3 py-1.5 w-full',
        )}
        title="Search (⌘K)"
      >
        <Search className="h-3.5 w-3.5 shrink-0" />
        {!collapsed && (
          <>
            <span className="text-xs flex-1 text-left">Search...</span>
            <kbd className="hidden sm:inline text-[10px] font-mono bg-background/50 px-1.5 py-0.5 rounded border border-border/30">
              ⌘K
            </kbd>
          </>
        )}
      </button>

      {/* Modal overlay */}
      {open && (
        <div
          className="fixed inset-0 z-50 flex items-start justify-center pt-[15vh] bg-black/60 backdrop-blur-sm"
          onClick={() => {
            setOpen(false);
            setQuery('');
          }}
        >
          <div
            className="w-full max-w-lg mx-4 rounded-xl border border-border/50 bg-card shadow-2xl overflow-hidden"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Search input */}
            <div className="flex items-center gap-3 border-b border-border/30 px-4 py-3">
              <Search className="h-4 w-4 text-muted-foreground shrink-0" />
              <input
                ref={inputRef}
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Search docs, endpoints, forms..."
                className="flex-1 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground/50"
              />
              {query && (
                <button
                  onClick={() => setQuery('')}
                  className="text-muted-foreground hover:text-foreground"
                >
                  <X className="h-3.5 w-3.5" />
                </button>
              )}
            </div>

            {/* Results */}
            <div className="max-h-[50vh] overflow-y-auto">
              {query.length < 2 ? (
                <div className="px-4 py-6 text-center text-xs text-muted-foreground">
                  Type to search across documentation, API endpoints, and forms.
                </div>
              ) : results.length === 0 ? (
                <div className="px-4 py-6 text-center text-xs text-muted-foreground">
                  No results for &ldquo;{query}&rdquo;
                </div>
              ) : (
                <ul className="py-1">
                  {results.map((result, i) => {
                    const Icon = typeIcon[result.type];
                    return (
                      <li key={`${result.type}-${result.href}`}>
                        <button
                          className={cn(
                            'flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors',
                            i === selectedIndex
                              ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                              : 'hover:bg-accent/10',
                          )}
                          onClick={() => navigate(result.href)}
                          onMouseEnter={() => setSelectedIndex(i)}
                        >
                          <Icon className="h-4 w-4 text-muted-foreground shrink-0" />
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium truncate">
                              {result.title}
                            </p>
                            <p className="text-[11px] text-muted-foreground truncate">
                              {result.subtitle}
                            </p>
                          </div>
                          <span className="text-[10px] font-mono text-muted-foreground shrink-0">
                            {result.type}
                          </span>
                        </button>
                      </li>
                    );
                  })}
                </ul>
              )}
            </div>

            {/* Footer */}
            <div className="flex items-center gap-4 border-t border-border/30 px-4 py-2 text-[10px] text-muted-foreground">
              <span>
                <kbd className="font-mono bg-background/50 px-1 py-0.5 rounded border border-border/30">↑↓</kbd> navigate
              </span>
              <span>
                <kbd className="font-mono bg-background/50 px-1 py-0.5 rounded border border-border/30">↵</kbd> open
              </span>
              <span>
                <kbd className="font-mono bg-background/50 px-1 py-0.5 rounded border border-border/30">esc</kbd> close
              </span>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

function useSearchResults(
  query: string,
  docsData: ReturnType<typeof useDocsTree>['data'],
  postmanData: ReturnType<typeof usePostmanCollections>['data'],
  formsData: ReturnType<typeof useForms>['data'],
): SearchResult[] {
  if (!query || query.length < 2) return [];

  const q = query.toLowerCase();
  const results: SearchResult[] = [];

  // Search docs
  if (docsData?.tree) {
    for (const group of docsData.tree) {
      for (const item of group.children) {
        if (
          item.title.toLowerCase().includes(q) ||
          item.slug.toLowerCase().includes(q)
        ) {
          results.push({
            type: 'doc',
            title: item.title,
            subtitle: `${group.label} — /docs/${item.slug}`,
            href: `/docs/${item.slug}`,
          });
        }
      }
    }
  }

  // Search postman collections and requests
  if (postmanData?.collections) {
    for (const collection of postmanData.collections) {
      if (collection.name.toLowerCase().includes(q)) {
        results.push({
          type: 'endpoint',
          title: collection.name,
          subtitle: `${collection.version} — ${collection.requests.length} endpoints`,
          href: `/api-reference/${collection.slug}`,
        });
      }
      for (const req of collection.requests) {
        if (
          req.name.toLowerCase().includes(q) ||
          req.url.toLowerCase().includes(q)
        ) {
          results.push({
            type: 'endpoint',
            title: req.name,
            subtitle: `${req.method} ${req.url}`,
            href: `/api-reference/${collection.slug}`,
          });
        }
      }
    }
  }

  // Search forms
  if (formsData?.forms) {
    for (const form of formsData.forms) {
      if (
        form.title.toLowerCase().includes(q) ||
        form.purpose.toLowerCase().includes(q) ||
        String(form.id).includes(q) ||
        form.endpoints.some((e) => e.endpoint.toLowerCase().includes(q))
      ) {
        results.push({
          type: 'form',
          title: `Form ${form.id} — ${form.purpose}`,
          subtitle: `${form.fields.length} fields, ${form.pageCount} pages`,
          href: `/forms/${form.id}`,
        });
      }
    }
  }

  // Deduplicate by href and cap at 15 results
  const seen = new Set<string>();
  return results.filter((r) => {
    if (seen.has(r.href)) return false;
    seen.add(r.href);
    return true;
  }).slice(0, 15);
}
