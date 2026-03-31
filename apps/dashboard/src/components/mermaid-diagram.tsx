'use client';

import { useEffect, useRef, useState, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { Maximize2, X } from 'lucide-react';

interface MermaidDiagramProps {
  chart: string;
}

export function MermaidDiagram({ chart }: MermaidDiagramProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const [error, setError] = useState<string | null>(null);
  const [rendered, setRendered] = useState(false);
  const [expanded, setExpanded] = useState(false);

  useEffect(() => {
    let cancelled = false;

    async function render() {
      try {
        const mermaid = (await import('mermaid')).default;
        const { MERMAID_CONFIG } = await import('@/lib/mermaid-theme');

        mermaid.initialize({
          startOnLoad: false,
          ...MERMAID_CONFIG,
        });

        const id = `mermaid-${Math.random().toString(36).slice(2, 9)}`;
        const { svg } = await mermaid.render(id, chart);

        if (!cancelled && containerRef.current) {
          containerRef.current.innerHTML = svg;
          setRendered(true);
          setError(null);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'Failed to render diagram');
        }
      }
    }

    render();
    return () => {
      cancelled = true;
    };
  }, [chart]);

  const handleExpand = useCallback(() => setExpanded(true), []);

  if (error) {
    return (
      <div className="rounded-lg border border-[var(--amber-accent)]/30 bg-[var(--amber-accent)]/5 p-4">
        <p className="text-xs text-[var(--amber-accent)]">Diagram render error: {error}</p>
        <pre className="mt-2 text-[10px] font-mono text-muted-foreground overflow-x-auto">
          {chart}
        </pre>
      </div>
    );
  }

  return (
    <>
      {/* Inline diagram */}
      <div className="group relative my-4 rounded-lg bg-secondary/30 p-4">
        <div
          ref={containerRef}
          className="flex justify-center overflow-x-auto [&_svg]:max-w-full"
        />
        {rendered && (
          <button
            onClick={handleExpand}
            className="absolute top-2 right-2 rounded-md bg-background/80 border border-border/50 p-1.5 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity hover:text-foreground hover:bg-background"
            title="Expand diagram"
          >
            <Maximize2 className="h-3.5 w-3.5" />
          </button>
        )}
      </div>

      {/* Fullscreen overlay — rendered via portal to escape any stacking context */}
      {expanded && <ExpandedDiagram chart={chart} onClose={() => setExpanded(false)} />}
    </>
  );
}

function ExpandedDiagram({ chart, onClose }: { chart: string; onClose: () => void }) {
  const expandedRef = useRef<HTMLDivElement>(null);
  const [ready, setReady] = useState(false);

  // Re-render mermaid at full size in the expanded view
  useEffect(() => {
    let cancelled = false;

    async function render() {
      try {
        const mermaid = (await import('mermaid')).default;
        const { MERMAID_CONFIG } = await import('@/lib/mermaid-theme');

        mermaid.initialize({
          startOnLoad: false,
          ...MERMAID_CONFIG,
        });

        const id = `mermaid-exp-${Math.random().toString(36).slice(2, 9)}`;
        const { svg } = await mermaid.render(id, chart);

        if (!cancelled && expandedRef.current) {
          // Remove any hardcoded width/height so the SVG scales freely
          const cleaned = svg
            .replace(/width="[^"]*"/, '')
            .replace(/height="[^"]*"/, '')
            .replace(/<svg /, '<svg style="width:100%;height:auto;max-height:80vh" ');
          expandedRef.current.innerHTML = cleaned;
          setReady(true);
        }
      } catch {
        // If re-render fails, just close
        onClose();
      }
    }

    render();
    return () => { cancelled = true; };
  }, [chart, onClose]);

  // Close on Escape
  useEffect(() => {
    function handleKey(e: KeyboardEvent) {
      if (e.key === 'Escape') onClose();
    }
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [onClose]);

  // Lock body scroll
  useEffect(() => {
    document.body.style.overflow = 'hidden';
    return () => { document.body.style.overflow = ''; };
  }, []);

  return createPortal(
    <div
      className="fixed inset-0 flex items-center justify-center"
      style={{ zIndex: 9999 }}
      onClick={onClose}
    >
      {/* Backdrop */}
      <div className="absolute inset-0 bg-black/70 backdrop-blur-sm" />

      {/* Modal */}
      <div
        className="relative mx-4 max-h-[90vh] max-w-[95vw] w-full overflow-auto rounded-xl border border-border/50 bg-card p-8 shadow-2xl"
        style={{ zIndex: 10000 }}
        onClick={(e) => e.stopPropagation()}
      >
        <button
          onClick={onClose}
          className="absolute top-3 right-3 rounded-md bg-background border border-border/50 p-2 text-muted-foreground hover:text-foreground transition-colors"
          title="Close (Esc)"
        >
          <X className="h-4 w-4" />
        </button>

        {!ready && (
          <div className="flex items-center justify-center py-16">
            <div className="skeleton h-8 w-32 rounded" />
          </div>
        )}

        <div
          ref={expandedRef}
          className="flex justify-center"
        />
      </div>
    </div>,
    document.body,
  );
}
