'use client';

import { useState, useRef, useCallback } from 'react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { FUNCTION_NAMES } from '@/lib/constants';
import type { TimeRange } from '@/lib/types';
import type { FunctionFormsMap } from '@/hooks/use-function-forms-map';
import { useLogs } from '@/hooks/use-logs';
import { FunctionEntries } from './function-entries';

interface LogViewerProps {
  range: TimeRange;
  initialFunction?: string | null;
  functionFormsMap?: FunctionFormsMap;
}

export function LogViewer({ range, initialFunction = null, functionFormsMap }: LogViewerProps) {
  const [selectedFn, setSelectedFn] = useState<string | null>(initialFunction);
  const [filter, setFilter] = useState('');
  const [debouncedFilter, setDebouncedFilter] = useState('');
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const { data, isLoading, error } = useLogs(selectedFn, range, debouncedFilter);

  const handleFilterChange = useCallback((value: string) => {
    setFilter(value);
    if (timeoutRef.current) clearTimeout(timeoutRef.current);
    timeoutRef.current = setTimeout(() => setDebouncedFilter(value), 500);
  }, []);

  return (
    <div className="space-y-4">
      {/* Controls */}
      <div className="flex gap-3">
        <Select
          value={selectedFn ?? ''}
          onValueChange={(v) => setSelectedFn(v || null)}
        >
          <SelectTrigger className="w-[260px] h-9 text-xs font-mono bg-secondary border-border/50">
            <SelectValue placeholder="Select function..." />
          </SelectTrigger>
          <SelectContent>
            {FUNCTION_NAMES.map((fn) => (
              <SelectItem key={fn} value={fn} className="text-xs font-mono">
                {fn}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Input
          placeholder="Filter (e.g. ERROR, timeout)..."
          value={filter}
          onChange={(e) => handleFilterChange(e.target.value)}
          className="max-w-xs h-9 text-xs bg-secondary border-border/50 placeholder:text-muted-foreground/50"
        />
        {data && data.logs.length > 0 && (
          <span className="flex items-center text-[10px] font-mono text-muted-foreground px-2">
            {data.logs.length} entries
          </span>
        )}
      </div>

      {/* Empty state */}
      {!selectedFn && (
        <div className="rounded-xl border border-border/30 bg-card/50 p-12 text-center">
          <div className="text-muted-foreground/30 text-4xl mb-3">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="mx-auto">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <polyline points="14,2 14,8 20,8" />
              <line x1="16" y1="13" x2="8" y2="13" />
              <line x1="16" y1="17" x2="8" y2="17" />
              <line x1="10" y1="9" x2="8" y2="9" />
            </svg>
          </div>
          <p className="text-sm text-muted-foreground">Select a function to view its logs</p>
        </div>
      )}

      {/* Loading */}
      {isLoading && (
        <div className="rounded-xl border border-border/30 bg-[oklch(0.08_0.01_260)] p-6">
          <div className="space-y-2">
            {[...Array(5)].map((_, i) => (
              <div key={i} className="skeleton h-4 w-full" style={{ opacity: 1 - i * 0.15 }} />
            ))}
          </div>
        </div>
      )}

      {/* Error */}
      {error && (
        <div className="rounded-xl border border-[var(--rose-accent)]/30 bg-[var(--rose-accent)]/5 p-4">
          <p className="text-sm text-[var(--rose-accent)]">
            Failed to load logs. Check AWS credentials.
          </p>
        </div>
      )}

      {/* No results */}
      {data && data.logs.length === 0 && (
        <div className="rounded-xl border border-border/30 bg-card/50 p-8 text-center">
          <p className="text-sm text-muted-foreground">No log entries found for this time range.</p>
        </div>
      )}

      {/* Log output */}
      {data && data.logs.length > 0 && (
        <div className="rounded-xl border border-border/30 bg-[oklch(0.06_0.01_260)] overflow-hidden">
          {/* Terminal header */}
          <div className="flex items-center gap-2 px-4 py-2.5 bg-[oklch(0.08_0.01_260)] border-b border-border/20">
            <div className="flex gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-[var(--rose-accent)]/60" />
              <span className="w-2.5 h-2.5 rounded-full bg-[var(--amber-accent)]/60" />
              <span className="w-2.5 h-2.5 rounded-full bg-[var(--teal-accent)]/60" />
            </div>
            <span className="text-[10px] font-mono text-muted-foreground/50 ml-2">
              /aws/lambda/trp-api-dev-{selectedFn}
            </span>
          </div>
          {/* Log content */}
          <div className="max-h-[560px] overflow-auto p-4">
            <div className="space-y-px">
              {data.logs.map((entry, i) => {
                const time = new Date(entry.timestamp).toLocaleTimeString('en-AU', {
                  hour: '2-digit',
                  minute: '2-digit',
                  second: '2-digit',
                });
                const isError = /ERROR|Exception|Fatal/i.test(entry.message);
                const isWarning = /WARNING|WARN/i.test(entry.message);
                const isStart = /^START RequestId/i.test(entry.message);
                const isEnd = /^END RequestId/i.test(entry.message);
                const isReport = /^REPORT RequestId/i.test(entry.message);

                let lineClass = 'text-[oklch(0.65_0.015_260)]';
                let bgClass = '';

                if (isError) {
                  lineClass = 'text-[var(--rose-accent)]';
                  bgClass = 'bg-[var(--rose-accent)]/5';
                } else if (isWarning) {
                  lineClass = 'text-[var(--amber-accent)]';
                  bgClass = 'bg-[var(--amber-accent)]/5';
                } else if (isStart || isEnd || isReport) {
                  lineClass = 'text-muted-foreground/40';
                }

                return (
                  <div
                    key={i}
                    className={`flex gap-3 px-2 py-0.5 rounded text-xs font-mono leading-5 ${bgClass} hover:bg-accent/10 transition-colors`}
                  >
                    <span className="text-muted-foreground/40 shrink-0 select-none tabular-nums">
                      {time}
                    </span>
                    <span className={`${lineClass} whitespace-pre-wrap break-all`}>
                      {entry.message.trimEnd()}
                    </span>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      )}

      {/* Related form entries */}
      {selectedFn && functionFormsMap && (
        <FunctionEntries
          functionName={selectedFn}
          functionFormsMap={functionFormsMap}
          range={range}
        />
      )}
    </div>
  );
}
