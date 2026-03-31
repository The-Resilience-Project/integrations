'use client';

import { useState } from 'react';
import Link from 'next/link';
import { FileText } from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import type { FunctionMetrics } from '@/lib/types';
import type { FunctionFormsMap } from '@/hooks/use-function-forms-map';

type SortKey = 'name' | 'invocations' | 'errors' | 'errorRate' | 'avgDuration' | 'p95Duration';
type SortDir = 'asc' | 'desc';

interface FunctionsTableProps {
  functions: FunctionMetrics[];
  onSelectFunction: (name: string) => void;
  functionFormsMap?: FunctionFormsMap;
}

export function FunctionsTable({ functions, onSelectFunction, functionFormsMap }: FunctionsTableProps) {
  const [sortKey, setSortKey] = useState<SortKey>('invocations');
  const [sortDir, setSortDir] = useState<SortDir>('desc');

  function handleSort(key: SortKey) {
    if (sortKey === key) {
      setSortDir(sortDir === 'asc' ? 'desc' : 'asc');
    } else {
      setSortKey(key);
      setSortDir('desc');
    }
  }

  const sorted = [...functions].sort((a, b) => {
    let aVal: number | string;
    let bVal: number | string;

    if (sortKey === 'errorRate') {
      aVal = a.invocations > 0 ? a.errors / a.invocations : 0;
      bVal = b.invocations > 0 ? b.errors / b.invocations : 0;
    } else {
      aVal = a[sortKey];
      bVal = b[sortKey];
    }

    if (typeof aVal === 'string') {
      return sortDir === 'asc'
        ? aVal.localeCompare(bVal as string)
        : (bVal as string).localeCompare(aVal);
    }
    return sortDir === 'asc' ? (aVal as number) - (bVal as number) : (bVal as number) - (aVal as number);
  });

  const SortIcon = ({ columnKey }: { columnKey: SortKey }) => {
    if (sortKey !== columnKey) {
      return <span className="ml-1 text-muted-foreground/30">↕</span>;
    }
    return (
      <span className="ml-1 text-[var(--cyan-glow)]">
        {sortDir === 'asc' ? '↑' : '↓'}
      </span>
    );
  };

  return (
    <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow className="border-border/50 hover:bg-transparent">
            <TableHead
              className="cursor-pointer text-xs font-medium uppercase tracking-wider text-muted-foreground select-none"
              onClick={() => handleSort('name')}
            >
              Function <SortIcon columnKey="name" />
            </TableHead>
            <TableHead
              className="cursor-pointer text-xs font-medium uppercase tracking-wider text-muted-foreground text-right select-none"
              onClick={() => handleSort('invocations')}
            >
              Invocations <SortIcon columnKey="invocations" />
            </TableHead>
            <TableHead
              className="cursor-pointer text-xs font-medium uppercase tracking-wider text-muted-foreground text-right select-none"
              onClick={() => handleSort('errors')}
            >
              Errors <SortIcon columnKey="errors" />
            </TableHead>
            <TableHead
              className="cursor-pointer text-xs font-medium uppercase tracking-wider text-muted-foreground text-right select-none"
              onClick={() => handleSort('errorRate')}
            >
              Error Rate <SortIcon columnKey="errorRate" />
            </TableHead>
            <TableHead
              className="cursor-pointer text-xs font-medium uppercase tracking-wider text-muted-foreground text-right select-none"
              onClick={() => handleSort('avgDuration')}
            >
              Avg <SortIcon columnKey="avgDuration" />
            </TableHead>
            <TableHead
              className="cursor-pointer text-xs font-medium uppercase tracking-wider text-muted-foreground text-right select-none"
              onClick={() => handleSort('p95Duration')}
            >
              p95 <SortIcon columnKey="p95Duration" />
            </TableHead>
            {functionFormsMap && (
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground select-none">
                Triggered by
              </TableHead>
            )}
          </TableRow>
        </TableHeader>
        <TableBody>
          {sorted.map((fn) => {
            const errorRate =
              fn.invocations > 0 ? ((fn.errors / fn.invocations) * 100).toFixed(1) : '0.0';
            const hasErrors = fn.errors > 0;
            const hasActivity = fn.invocations > 0;

            return (
              <TableRow
                key={fn.name}
                className="border-border/30 transition-colors hover:bg-accent/20"
              >
                <TableCell>
                  <div className="flex items-center gap-2.5">
                    <span
                      className={`h-1.5 w-1.5 rounded-full ${
                        hasErrors
                          ? 'bg-[var(--rose-accent)]'
                          : hasActivity
                            ? 'bg-[var(--teal-accent)]'
                            : 'bg-muted-foreground/30'
                      }`}
                    />
                    <button
                      className="font-mono text-sm text-[var(--cyan-glow)] hover:text-[var(--cyan-glow)]/80 transition-colors"
                      onClick={() => onSelectFunction(fn.name)}
                    >
                      {fn.name}
                    </button>
                  </div>
                </TableCell>
                <TableCell className="text-right font-mono text-sm tabular-nums">
                  {fn.invocations.toLocaleString()}
                </TableCell>
                <TableCell className="text-right font-mono text-sm tabular-nums">
                  <span className={hasErrors ? 'text-[var(--rose-accent)]' : 'text-muted-foreground/50'}>
                    {fn.errors.toLocaleString()}
                  </span>
                </TableCell>
                <TableCell className="text-right font-mono text-sm tabular-nums">
                  <span className={hasErrors ? 'text-[var(--amber-accent)]' : 'text-muted-foreground/50'}>
                    {errorRate}%
                  </span>
                </TableCell>
                <TableCell className="text-right font-mono text-sm tabular-nums text-muted-foreground">
                  {hasActivity ? `${Math.round(fn.avgDuration).toLocaleString()}ms` : '—'}
                </TableCell>
                <TableCell className="text-right font-mono text-sm tabular-nums text-muted-foreground">
                  {hasActivity ? `${Math.round(fn.p95Duration).toLocaleString()}ms` : '—'}
                </TableCell>
                {functionFormsMap && (
                  <TableCell>
                    <TriggeredByForms forms={functionFormsMap[fn.name]} />
                  </TableCell>
                )}
              </TableRow>
            );
          })}
        </TableBody>
      </Table>
    </div>
  );
}

function TriggeredByForms({ forms }: { forms?: { id: number; purpose: string }[] }) {
  if (!forms || forms.length === 0) {
    return <span className="text-[11px] text-muted-foreground/40">—</span>;
  }

  const show = forms.slice(0, 2);
  const extra = forms.length - show.length;

  return (
    <div className="flex flex-wrap gap-1">
      {show.map((f) => (
        <Link
          key={f.id}
          href={`/forms/${f.id}`}
          className="inline-flex items-center gap-1 rounded-md border border-border/40 bg-secondary/30 px-1.5 py-0.5 text-[10px] text-muted-foreground hover:text-foreground hover:border-[var(--cyan-glow)]/30 transition-colors"
          onClick={(e) => e.stopPropagation()}
        >
          <FileText className="h-2.5 w-2.5" />
          <span className="font-mono">#{f.id}</span>
          <span className="max-w-[100px] truncate">{f.purpose}</span>
        </Link>
      ))}
      {extra > 0 && (
        <span className="text-[10px] text-muted-foreground/60">+{extra} more</span>
      )}
    </div>
  );
}
