'use client';

import { use, useMemo } from 'react';
import Link from 'next/link';
import { ArrowLeft, FileText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useForms } from '@/hooks/use-forms';
import { useGFFeeds } from '@/hooks/use-gf-feeds';
import { useTrace } from '@/hooks/use-trace';
import { TraceTimeline } from '@/components/trace-timeline';
import { urlToFunctionName } from '@/lib/url-to-function';
import type { GravityForm, FormEndpoint } from '@/lib/types';
import { useQuery } from '@tanstack/react-query';
import type { GFEntry } from '@/lib/gravity-forms';

export default function TracePage({
  params,
}: {
  params: Promise<{ id: string; entryId: string }>;
}) {
  const { id, entryId } = use(params);
  const formId = parseInt(id, 10);
  const { data: formsData } = useForms();
  const { data: feedsData } = useGFFeeds(formId);

  const form = formsData?.forms?.find((f: GravityForm) => f.id === formId);

  // Fetch the specific entry
  const { data: entryData, isLoading: entryLoading } = useQuery<GFEntry>({
    queryKey: ['gf-entry', entryId],
    queryFn: async () => {
      const res = await fetch(`/api/gf/entries/${formId}?entry_id=${entryId}`);
      if (!res.ok) throw new Error('Failed to fetch entry');
      const data = await res.json();
      // Find this specific entry
      const entry = data.entries?.find((e: GFEntry) => e.id === entryId);
      if (!entry) throw new Error('Entry not found');
      return entry;
    },
    staleTime: 60 * 1000,
  });

  // Resolve function name
  const functionName = useMemo(() => {
    if (!form) return null;
    const outbound = form.endpoints.find((e: FormEndpoint) => e.direction === 'outbound');
    if (outbound) return urlToFunctionName(outbound.endpoint);
    if (feedsData?.webhooks?.[0]) return urlToFunctionName(feedsData.webhooks[0].url);
    return null;
  }, [form, feedsData]);

  // Find email from the entry
  const emailFieldId = useMemo(() => {
    if (!form) return null;
    const emailField = form.fields.find((f) => f.type === 'email');
    return emailField ? String(emailField.id) : null;
  }, [form]);

  const email = entryData && emailFieldId
    ? (entryData[emailFieldId] as string) ?? null
    : null;

  // Trace with wider window (5 minutes each side)
  const traceParams = useMemo(() => {
    if (!entryData || !functionName || !email) return null;
    return {
      email,
      fn: functionName,
      timestamp: entryData.date_created,
    };
  }, [entryData, functionName, email]);

  const { data: traceData, isLoading: traceLoading } = useTrace(traceParams);

  return (
    <div className="max-w-[900px] mx-auto px-6 py-6 space-y-6">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-xs text-muted-foreground">
        <Link
          href={`/forms/${id}`}
          className="flex items-center gap-1 hover:text-foreground transition-colors"
        >
          <ArrowLeft className="h-3 w-3" />
          Form {id}
        </Link>
        <span>/</span>
        <span className="text-foreground">Trace Entry #{entryId}</span>
      </div>

      {/* Header */}
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5">
          <FileText className="h-5 w-5 text-[var(--cyan-glow)]" />
          <h1 className="text-lg font-semibold tracking-tight">
            Entry Trace
          </h1>
          <Badge variant="secondary" className="text-[10px] font-mono">
            #{entryId}
          </Badge>
        </div>
        {form && (
          <p className="text-sm text-muted-foreground mt-1">
            {form.purpose || form.title} &rarr; {functionName ?? 'unknown function'}
          </p>
        )}
      </header>

      {/* Entry details */}
      {entryLoading && (
        <div className="space-y-2">
          <div className="skeleton h-6 w-48" />
          <div className="skeleton h-40 w-full" />
        </div>
      )}

      {entryData && (
        <div className="rounded-xl border border-border/50 bg-card p-4">
          <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-3">
            Entry Data
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
            {form?.fields
              .filter((f) => {
                const val = entryData[String(f.id)];
                return val && String(val).trim() !== '' && f.type !== 'html' && f.type !== 'section';
              })
              .map((field) => (
                <div key={field.id} className="flex gap-2">
                  <span className="text-muted-foreground shrink-0 w-[140px] truncate text-xs">
                    {field.label}
                  </span>
                  <span className="text-xs font-mono truncate">
                    {String(entryData[String(field.id)])}
                  </span>
                </div>
              ))}
          </div>
        </div>
      )}

      {/* Trace search details */}
      {entryData && email && functionName && (
        <div className="rounded-lg bg-secondary/30 px-4 py-2 text-[11px] font-mono text-muted-foreground flex flex-wrap gap-x-4 gap-y-1">
          <span>Email: {email}</span>
          <span>Function: {functionName}</span>
          <span>Window: ±10 min from {new Date(entryData.date_created.replace(' ', 'T') + 'Z').toLocaleTimeString('en-AU')}</span>
        </div>
      )}

      {/* No email warning */}
      {entryData && !email && (
        <div className="rounded-xl border border-[var(--amber-accent)]/30 bg-[var(--amber-accent)]/5 p-4">
          <p className="text-sm text-[var(--amber-accent)]">
            Unable to trace — no email field found in this entry.
          </p>
        </div>
      )}

      {/* Trace timeline */}
      {entryData && email && functionName && (
        <TraceTimeline
          entry={entryData}
          email={email}
          formTitle={form?.purpose || form?.title || `Form ${id}`}
          functionName={functionName}
          logs={traceData?.logs ?? []}
          isLoading={traceLoading}
        />
      )}
    </div>
  );
}
