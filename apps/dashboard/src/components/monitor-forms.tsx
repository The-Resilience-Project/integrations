'use client';

import { useMemo, useState } from 'react';
import Link from 'next/link';
import { FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import type { FunctionMetrics, TimeRange } from '@/lib/types';
import type { FunctionFormsMap } from '@/hooks/use-function-forms-map';
import { useEntryCounts } from '@/hooks/use-entry-counts';

interface MonitorFormsProps {
  functions: FunctionMetrics[];
  functionFormsMap: FunctionFormsMap;
  range: TimeRange;
  onSelectFunction: (name: string) => void;
}

interface FormRow {
  formId: number;
  formTitle: string;
  formPurpose: string;
  entryCount: number | null;
  functionName: string;
  invocations: number;
  errors: number;
  delta: number | null;
}

export function MonitorForms({ functions, functionFormsMap, range, onSelectFunction }: MonitorFormsProps) {
  const [showAll, setShowAll] = useState(true);

  // Collect all form IDs that map to functions
  const formIds = useMemo(() => {
    const ids = new Set<number>();
    for (const forms of Object.values(functionFormsMap)) {
      for (const f of forms) ids.add(f.id);
    }
    return [...ids];
  }, [functionFormsMap]);

  // Fetch entry counts filtered by the same time range as CloudWatch metrics
  const { data: entryCountsData } = useEntryCounts(formIds, range);
  const entryCounts = entryCountsData?.counts ?? {};

  const rows = useMemo(() => {
    const result: FormRow[] = [];
    const metricsMap = new Map(functions.map((f) => [f.name, f]));

    for (const [fnName, forms] of Object.entries(functionFormsMap)) {
      const metrics = metricsMap.get(fnName);
      const invocations = metrics?.invocations ?? 0;
      const errors = metrics?.errors ?? 0;

      for (const form of forms) {
        const rawCount = entryCounts[form.id];
        // -1 means the API call failed for this form; undefined means still loading
        const entryCount = rawCount != null && rawCount >= 0 ? rawCount : null;
        result.push({
          formId: form.id,
          formTitle: form.title,
          formPurpose: form.purpose,
          entryCount,
          functionName: fnName,
          invocations,
          errors,
          delta: entryCount != null ? invocations - entryCount : null,
        });
      }
    }

    return result.sort((a, b) => (b.entryCount ?? 0) - (a.entryCount ?? 0));
  }, [functions, functionFormsMap, entryCounts]);

  // Functions with no forms (internal / non-form triggered)
  const unmappedFunctions = useMemo(() => {
    const mapped = new Set(Object.keys(functionFormsMap));
    return functions
      .filter((f) => !mapped.has(f.name) && f.invocations > 0)
      .sort((a, b) => b.invocations - a.invocations);
  }, [functions, functionFormsMap]);

  const filtered = showAll ? rows : rows.filter((r) => r.delta != null && r.delta !== 0);

  const totalEntries = rows.reduce((sum, r) => sum + (r.entryCount ?? 0), 0);
  const totalInvocations = rows.reduce((sum, r) => sum + r.invocations, 0);

  return (
    <div className="space-y-6">
      {/* Summary cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <SummaryCard label="Forms with functions" value={rows.length} />
        <SummaryCard label="Total entries" value={totalEntries.toLocaleString()} />
        <SummaryCard label="Total invocations" value={totalInvocations.toLocaleString()} />
        <SummaryCard
          label="Internal functions"
          value={unmappedFunctions.length}
          sublabel="no form trigger"
        />
      </div>

      {/* Filter toggle */}
      <div className="flex items-center gap-2">
        <label className="flex items-center gap-1.5 text-xs text-muted-foreground cursor-pointer">
          <input
            type="checkbox"
            checked={!showAll}
            onChange={(e) => setShowAll(!e.target.checked)}
            className="rounded"
          />
          Only show discrepancies
        </label>
        <span className="text-[10px] font-mono text-muted-foreground ml-auto">
          {filtered.length} rows
        </span>
      </div>

      {/* Main table */}
      <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow className="border-border/50 hover:bg-transparent">
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                Form
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground text-right">
                Entries
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground text-center w-10">
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                Function
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground text-right">
                Invocations
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground text-right">
                Delta
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {filtered.map((row) => (
              <TableRow key={`${row.formId}-${row.functionName}`} className="border-border/30">
                <TableCell>
                  <Link
                    href={`/forms/${row.formId}`}
                    className="inline-flex items-center gap-1.5 text-sm text-[var(--cyan-glow)] hover:underline"
                  >
                    <FileText className="h-3 w-3 shrink-0" />
                    <span className="font-mono text-xs text-muted-foreground">#{row.formId}</span>
                    <span className="truncate max-w-[200px]">{row.formPurpose || row.formTitle}</span>
                  </Link>
                </TableCell>
                <TableCell className="text-right font-mono text-sm tabular-nums">
                  {row.entryCount != null ? row.entryCount.toLocaleString() : '—'}
                </TableCell>
                <TableCell className="text-center text-muted-foreground/40">
                  →
                </TableCell>
                <TableCell>
                  <button
                    className="font-mono text-sm text-[var(--cyan-glow)] hover:text-[var(--cyan-glow)]/80 transition-colors"
                    onClick={() => onSelectFunction(row.functionName)}
                  >
                    {row.functionName}
                  </button>
                </TableCell>
                <TableCell className="text-right font-mono text-sm tabular-nums">
                  {row.invocations.toLocaleString()}
                </TableCell>
                <TableCell className="text-right font-mono text-sm tabular-nums">
                  {row.delta != null ? <DeltaBadge delta={row.delta} /> : <span className="text-muted-foreground/40">—</span>}
                </TableCell>
              </TableRow>
            ))}
            {filtered.length === 0 && (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-sm text-muted-foreground py-8">
                  {showAll ? 'No form→function mappings found' : 'No discrepancies found'}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {/* Unmapped functions */}
      {unmappedFunctions.length > 0 && (
        <div>
          <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-2">
            Internal functions (no form trigger)
          </h3>
          <div className="flex flex-wrap gap-1.5">
            {unmappedFunctions.map((fn) => (
              <button
                key={fn.name}
                onClick={() => onSelectFunction(fn.name)}
                className="inline-flex items-center gap-1.5 rounded-md border border-border/40 bg-secondary/30 px-2 py-1 text-[11px] hover:border-border transition-colors"
              >
                <span className="font-mono text-[var(--cyan-glow)]">{fn.name}</span>
                <span className="text-muted-foreground">{fn.invocations.toLocaleString()}</span>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

function SummaryCard({ label, value, sublabel }: { label: string; value: string | number; sublabel?: string }) {
  return (
    <div className="rounded-lg border border-border/50 bg-card px-4 py-3">
      <p className="text-[10px] uppercase tracking-wider text-muted-foreground">{label}</p>
      <p className="text-xl font-semibold font-mono mt-0.5">{value}</p>
      {sublabel && <p className="text-[10px] text-muted-foreground/60">{sublabel}</p>}
    </div>
  );
}

function DeltaBadge({ delta }: { delta: number }) {
  if (delta === 0) {
    return <span className="text-muted-foreground/40">—</span>;
  }

  const isPositive = delta > 0;
  return (
    <Badge
      variant="secondary"
      className={`text-[10px] font-mono ${
        isPositive
          ? 'text-[var(--amber-accent)] bg-[var(--amber-accent)]/10'
          : 'text-[var(--teal-accent)] bg-[var(--teal-accent)]/10'
      }`}
    >
      {isPositive ? '+' : ''}{delta.toLocaleString()}
    </Badge>
  );
}
