'use client';

import { useState, useMemo } from 'react';
import Link from 'next/link';
import { ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useGFEntries } from '@/hooks/use-gf-entries';
import { EntryDetailDrawer } from '@/components/entry-detail-drawer';
import type { FormField } from '@/lib/types';

interface EntriesTableProps {
  formId: number;
  fields: FormField[];
  emailFieldId?: string | null;
  functionName?: string | null;
}

// Field types worth showing in the table
const DISPLAY_TYPES = new Set([
  'email', 'text', 'name', 'phone', 'select', 'radio', 'number', 'hidden',
]);

// Fields to prioritise as summary columns (by label keywords)
const PRIORITY_LABELS = [
  'email', 'name', 'school', 'organisation', 'organization', 'service_type',
  'type', 'state', 'phone',
];

function pickSummaryFields(fields: FormField[], maxCols = 3): FormField[] {
  const candidates = fields.filter(
    (f) => DISPLAY_TYPES.has(f.type) && f.label && !f.label.toLowerCase().includes('hidden'),
  );

  // Sort by priority label match
  const scored = candidates.map((f) => {
    const lower = f.label.toLowerCase();
    const idx = PRIORITY_LABELS.findIndex((p) => lower.includes(p));
    return { field: f, score: idx >= 0 ? idx : 999 };
  });
  scored.sort((a, b) => a.score - b.score);

  return scored.slice(0, maxCols).map((s) => s.field);
}

function getEntryFieldValue(entry: Record<string, unknown>, fieldId: number): string {
  // GF entries key fields by ID as strings. Name fields use sub-IDs like "7.3", "7.6"
  const direct = entry[String(fieldId)];
  if (direct && String(direct).trim()) return String(direct);

  // Try name sub-fields: {id}.3 (first), {id}.6 (last)
  const first = entry[`${fieldId}.3`];
  const last = entry[`${fieldId}.6`];
  if (first || last) return [first, last].filter(Boolean).join(' ');

  return '';
}

export function EntriesTable({
  formId,
  fields,
  emailFieldId,
  functionName,
}: EntriesTableProps) {
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(20);
  const [status, setStatus] = useState<string>('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [searchValue, setSearchValue] = useState('');
  const [selectedEntryId, setSelectedEntryId] = useState<string | null>(null);

  const { data, isLoading } = useGFEntries({
    formId,
    page,
    pageSize,
    status: status || undefined,
    startDate: startDate || undefined,
    endDate: endDate || undefined,
    searchValue: searchValue || undefined,
    searchKey: searchValue ? '0' : undefined,
  });

  const totalPages = data ? Math.ceil(data.total_count / pageSize) : 0;
  const canTrace = !!(functionName && emailFieldId);

  const summaryFields = useMemo(() => pickSummaryFields(fields), [fields]);

  const resetPage = () => setPage(1);

  if (!data?.configured && !isLoading) {
    return (
      <div className="rounded-xl border border-border/30 bg-secondary/20 p-4 text-center">
        <p className="text-xs text-muted-foreground">
          GF API not configured — set <code className="font-mono text-[var(--cyan-glow)]">GF_CONSUMER_KEY</code> and{' '}
          <code className="font-mono text-[var(--cyan-glow)]">GF_CONSUMER_SECRET</code> in <code className="font-mono">.env.local</code> to see entries.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
        Entries
      </h3>

      {/* Filter bar */}
      <div className="flex flex-wrap items-center gap-2">
        <Input
          type="search"
          placeholder="Search entries..."
          value={searchValue}
          onChange={(e) => {
            setSearchValue(e.target.value);
            resetPage();
          }}
          className="max-w-[200px] h-8 text-xs bg-secondary border-border/50 placeholder:text-muted-foreground/50"
        />

        <Select
          value={status}
          onValueChange={(val) => {
            setStatus(val as string);
            resetPage();
          }}
        >
          <SelectTrigger size="sm" className="text-xs">
            <SelectValue placeholder="All statuses" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="">All statuses</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="spam">Spam</SelectItem>
            <SelectItem value="trash">Trash</SelectItem>
          </SelectContent>
        </Select>

        <Input
          type="date"
          value={startDate}
          onChange={(e) => {
            setStartDate(e.target.value);
            resetPage();
          }}
          className="w-[140px] h-8 text-xs bg-secondary border-border/50"
        />
        <span className="text-xs text-muted-foreground">to</span>
        <Input
          type="date"
          value={endDate}
          onChange={(e) => {
            setEndDate(e.target.value);
            resetPage();
          }}
          className="w-[140px] h-8 text-xs bg-secondary border-border/50"
        />

        <span className="text-[10px] font-mono text-muted-foreground ml-auto">
          {data?.total_count ?? 0} total
        </span>
      </div>

      {/* Table */}
      <div className="rounded-xl border border-border/50 bg-card overflow-hidden overflow-x-auto">
        {isLoading ? (
          <div className="space-y-2 p-4">
            {[1, 2, 3].map((i) => (
              <div key={i} className="skeleton h-8 w-full" />
            ))}
          </div>
        ) : data && data.entries.length > 0 ? (
          <Table>
            <TableHeader>
              <TableRow className="border-border/50 hover:bg-transparent">
                <TableHead className="text-xs w-16">ID</TableHead>
                <TableHead className="text-xs w-[140px]">Created</TableHead>
                {summaryFields.map((f) => (
                  <TableHead key={f.id} className="text-xs">
                    {f.label}
                  </TableHead>
                ))}
                <TableHead className="text-xs w-16">Status</TableHead>
                {canTrace && <TableHead className="text-xs w-16" />}
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.entries.map((entry) => {
                return (
                  <TableRow
                    key={entry.id}
                    className="border-border/30 hover:bg-accent/10"
                  >
                    <TableCell className="font-mono text-xs">
                      <button
                        onClick={() => setSelectedEntryId(entry.id)}
                        className="text-[var(--cyan-glow)] hover:underline"
                      >
                        {entry.id}
                      </button>
                    </TableCell>
                    <TableCell className="text-xs text-muted-foreground whitespace-nowrap">
                      {new Date(entry.date_created).toLocaleDateString('en-AU', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </TableCell>
                    {summaryFields.map((f) => (
                      <TableCell key={f.id} className="text-xs max-w-[200px] truncate">
                        {getEntryFieldValue(entry, f.id)}
                      </TableCell>
                    ))}
                    <TableCell>
                      <Badge variant="secondary" className="text-[10px]">
                        {entry.status}
                      </Badge>
                    </TableCell>
                    {canTrace && (
                      <TableCell>
                        <Link
                          href={`/forms/${formId}/trace/${entry.id}`}
                          className="flex items-center gap-1 text-[11px] text-muted-foreground hover:text-[var(--cyan-glow)] transition-colors"
                          title="Trace this entry through CloudWatch"
                        >
                          <Search className="h-3 w-3" />
                          Trace
                        </Link>
                      </TableCell>
                    )}
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        ) : (
          <div className="p-6 text-center">
            <p className="text-xs text-muted-foreground">No entries found</p>
          </div>
        )}
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Select
              value={String(pageSize)}
              onValueChange={(val) => {
                setPageSize(parseInt(val as string, 10));
                setPage(1);
              }}
            >
              <SelectTrigger size="sm" className="text-xs">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="10">10 per page</SelectItem>
                <SelectItem value="20">20 per page</SelectItem>
                <SelectItem value="50">50 per page</SelectItem>
                <SelectItem value="100">100 per page</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="flex items-center gap-2">
            <span className="text-xs text-muted-foreground">
              Page {page} of {totalPages}
            </span>
            <Button
              variant="outline"
              size="icon"
              className="h-7 w-7"
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              <ChevronLeft className="h-3.5 w-3.5" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              className="h-7 w-7"
              disabled={page >= totalPages}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
            >
              <ChevronRight className="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>
      )}

      {/* Entry detail drawer */}
      <EntryDetailDrawer
        entryId={selectedEntryId}
        formFields={fields}
        open={selectedEntryId !== null}
        onOpenChange={(open) => {
          if (!open) setSelectedEntryId(null);
        }}
      />
    </div>
  );
}
