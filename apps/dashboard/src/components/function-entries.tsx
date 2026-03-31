'use client';

import { useMemo } from 'react';
import Link from 'next/link';
import { FileText, Search } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useGFEntries } from '@/hooks/use-gf-entries';
import { useForms } from '@/hooks/use-forms';
import { getTimeRangeMs } from '@/lib/constants';
import type { TimeRange } from '@/lib/types';
import type { FunctionFormsMap } from '@/hooks/use-function-forms-map';
import type { GFEntry } from '@/lib/gravity-forms';

interface FunctionEntriesProps {
  functionName: string;
  functionFormsMap: FunctionFormsMap;
  range: TimeRange;
}

interface EntryRow {
  entryId: string;
  formId: number;
  formPurpose: string;
  email: string;
  name: string;
  date: string;
  status: string;
  emailFieldId: string | null;
}

/**
 * Shows recent form entries that triggered a given Lambda function.
 * Displayed below the log viewer on the Logs tab.
 */
export function FunctionEntries({ functionName, functionFormsMap, range }: FunctionEntriesProps) {
  const forms = functionFormsMap[functionName];
  const { data: formsData } = useForms();

  // Pick the first mapped form to fetch entries for
  const primaryForm = forms?.[0];
  const startDate = new Date(Date.now() - getTimeRangeMs(range))
    .toISOString()
    .split('T')[0];

  const { data: entriesData, isLoading } = useGFEntries({
    formId: primaryForm?.id ?? null,
    pageSize: 15,
    startDate,
  });

  // Find the email field for the primary form
  const emailFieldId = useMemo(() => {
    if (!primaryForm || !formsData?.forms) return null;
    const form = formsData.forms.find((f) => f.id === primaryForm.id);
    if (!form) return null;
    const emailField = form.fields.find((f) => f.type === 'email');
    return emailField ? String(emailField.id) : null;
  }, [primaryForm, formsData]);

  // Find name field for display
  const nameFieldId = useMemo(() => {
    if (!primaryForm || !formsData?.forms) return null;
    const form = formsData.forms.find((f) => f.id === primaryForm.id);
    if (!form) return null;
    const nameField = form.fields.find((f) => f.type === 'name');
    return nameField ? nameField.id : null;
  }, [primaryForm, formsData]);

  const rows: EntryRow[] = useMemo(() => {
    if (!entriesData?.entries || !primaryForm) return [];
    return entriesData.entries.map((entry: GFEntry) => {
      const email = emailFieldId ? String(entry[emailFieldId] ?? '') : '';
      let name = '';
      if (nameFieldId) {
        const first = entry[`${nameFieldId}.3`] ?? '';
        const last = entry[`${nameFieldId}.6`] ?? '';
        name = [first, last].filter(Boolean).join(' ');
      }
      return {
        entryId: entry.id,
        formId: primaryForm.id,
        formPurpose: primaryForm.purpose || primaryForm.title,
        email,
        name,
        date: entry.date_created,
        status: entry.status ?? 'active',
        emailFieldId,
      };
    });
  }, [entriesData, primaryForm, emailFieldId, nameFieldId]);

  if (!forms || forms.length === 0) return null;

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
          Recent entries
          {primaryForm && (
            <Link
              href={`/forms/${primaryForm.id}`}
              className="ml-2 text-[var(--cyan-glow)] normal-case tracking-normal hover:underline"
            >
              #{primaryForm.id} {primaryForm.purpose || primaryForm.title}
            </Link>
          )}
        </h3>
        {forms.length > 1 && (
          <span className="text-[10px] text-muted-foreground">
            +{forms.length - 1} more form{forms.length > 2 ? 's' : ''}
          </span>
        )}
      </div>

      {isLoading ? (
        <div className="space-y-1">
          {[1, 2, 3].map((i) => (
            <div key={i} className="skeleton h-8 w-full" />
          ))}
        </div>
      ) : rows.length === 0 ? (
        <p className="text-xs text-muted-foreground">No entries in this time range.</p>
      ) : (
        <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow className="border-border/50 hover:bg-transparent">
                <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground w-16">ID</TableHead>
                <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Submitted</TableHead>
                <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Name</TableHead>
                <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Email</TableHead>
                <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground w-16">Status</TableHead>
                <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground w-16">Trace</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((row) => (
                <TableRow key={row.entryId} className="border-border/30">
                  <TableCell className="font-mono text-xs text-muted-foreground">
                    {row.entryId}
                  </TableCell>
                  <TableCell className="text-xs text-muted-foreground tabular-nums">
                    {new Date(row.date + ' UTC').toLocaleString('en-AU', {
                      day: '2-digit',
                      month: 'short',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </TableCell>
                  <TableCell className="text-sm">{row.name || '—'}</TableCell>
                  <TableCell className="text-xs font-mono text-muted-foreground truncate max-w-[200px]">
                    {row.email || '—'}
                  </TableCell>
                  <TableCell>
                    <Badge
                      variant="secondary"
                      className={`text-[10px] ${
                        row.status === 'active' ? 'text-emerald-400' : 'text-muted-foreground'
                      }`}
                    >
                      {row.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    {row.email && row.emailFieldId ? (
                      <Link
                        href={`/forms/${row.formId}/trace/${row.entryId}`}
                        className="inline-flex items-center justify-center h-7 w-7 rounded-md hover:bg-accent/20 text-muted-foreground hover:text-[var(--cyan-glow)] transition-colors"
                        title="Trace this entry in Lambda logs"
                      >
                        <Search className="h-3.5 w-3.5" />
                      </Link>
                    ) : (
                      <span className="text-muted-foreground/30">—</span>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {entriesData && entriesData.total_count > 15 && (
            <div className="px-4 py-2 border-t border-border/30 text-[10px] text-muted-foreground">
              Showing 15 of {entriesData.total_count} entries ·{' '}
              <Link href={`/forms/${primaryForm!.id}`} className="text-[var(--cyan-glow)] hover:underline">
                View all
              </Link>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
