'use client';

import { use, useMemo } from 'react';
import Link from 'next/link';
import { AlertTriangle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useForms } from '@/hooks/use-forms';
import { useWebhookErrors } from '@/hooks/use-webhook-errors';
import {
  getJourney,
  getFlowsForJourney,
} from '@/lib/pipeline-map';
import type { JourneySlug, PipelineEntry } from '@/lib/pipeline-map';
import type { GravityForm } from '@/lib/types';

export default function JourneyPage({
  params,
}: {
  params: Promise<{ journey: string }>;
}) {
  const { journey: slug } = use(params);
  const journey = getJourney(slug);
  const { data: formsData, isLoading: formsLoading } = useForms();
  const { data: webhookData } = useWebhookErrors();

  const flows = useMemo(() => {
    if (!journey) return [];
    return getFlowsForJourney(journey.slug);
  }, [journey]);

  // Map form IDs to form data for quick lookup
  const formsById = useMemo(() => {
    const map = new Map<number, GravityForm>();
    for (const form of formsData?.forms ?? []) {
      map.set(form.id, form);
    }
    return map;
  }, [formsData]);

  // Webhook error counts per form (last 7 days)
  const errorCountsByForm = useMemo(() => {
    const counts = new Map<number, number>();
    const cutoff = Date.now() - 7 * 24 * 60 * 60 * 1000;
    for (const err of webhookData?.errors ?? []) {
      if (new Date(err.dateCreated + 'Z').getTime() > cutoff) {
        counts.set(err.formId, (counts.get(err.formId) ?? 0) + 1);
      }
    }
    return counts;
  }, [webhookData]);

  if (!journey) {
    return (
      <div className="max-w-[900px] mx-auto px-6 py-6">
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
          <p className="text-sm text-muted-foreground">Journey not found: {slug}</p>
          <Link
            href="/"
            className="text-sm text-[var(--cyan-glow)] hover:underline mt-2 inline-block"
          >
            Back to dashboard
          </Link>
        </div>
      </div>
    );
  }

  if (formsLoading) {
    return (
      <div className="max-w-[900px] mx-auto px-6 py-6 space-y-4">
        <div className="skeleton h-8 w-48" />
        <div className="skeleton h-4 w-64" />
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="skeleton h-36 w-full" />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-[900px] mx-auto px-6 py-6 space-y-6">
      {/* Header */}
      <header className="pb-4 border-b border-border/50">
        <h1 className="text-lg font-semibold tracking-tight">{journey.label}</h1>
        <p className="text-sm text-muted-foreground mt-1">{journey.description}</p>
      </header>

      {/* Flow cards grid */}
      {flows.length === 0 ? (
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
          <p className="text-sm text-muted-foreground">
            No flows mapped to this journey yet.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {flows.map(([key, entry]) => (
            <FlowCard
              key={key}
              flowKey={key}
              entry={entry}
              journeySlug={journey.slug}
              formsById={formsById}
              errorCountsByForm={errorCountsByForm}
            />
          ))}
        </div>
      )}
    </div>
  );
}

function FlowCard({
  flowKey,
  entry,
  journeySlug,
  formsById,
  errorCountsByForm,
}: {
  flowKey: string;
  entry: PipelineEntry;
  journeySlug: JourneySlug;
  formsById: Map<number, GravityForm>;
  errorCountsByForm: Map<number, number>;
}) {
  // Aggregate stats across all forms in this flow
  const { totalEntries, lastEntryDate, formDetails, totalErrors } = useMemo(() => {
    let entries = 0;
    let latest: string | null = null;
    let errors = 0;
    const details: { id: number; title: string }[] = [];

    for (const formId of entry.formIds) {
      const form = formsById.get(formId);
      if (form) {
        entries += form.entryCount ?? 0;
        details.push({ id: form.id, title: form.purpose || form.title });
        if (form.lastEntryDate) {
          if (!latest || form.lastEntryDate > latest) {
            latest = form.lastEntryDate;
          }
        }
      }
      errors += errorCountsByForm.get(formId) ?? 0;
    }

    return { totalEntries: entries, lastEntryDate: latest, formDetails: details, totalErrors: errors };
  }, [entry.formIds, formsById, errorCountsByForm]);

  return (
    <Link href={`/${journeySlug}/${flowKey}`}>
      <Card className="bg-card border-border/50 hover:border-[var(--cyan-glow)]/30 transition-colors cursor-pointer h-full">
        <CardContent className="pt-4 pb-4 px-4 space-y-3">
          {/* Flow label + error badge */}
          <div className="flex items-center gap-2">
            <h3 className="text-sm font-medium">{entry.label}</h3>
            {totalErrors > 0 && (
              <Badge
                variant="secondary"
                className="text-[10px] bg-[var(--rose-accent)]/10 text-[var(--rose-accent)]"
              >
                <AlertTriangle className="h-2.5 w-2.5 mr-0.5" />
                {totalErrors}
              </Badge>
            )}
          </div>

          {/* Form IDs and titles */}
          {entry.formIds.length === 0 ? (
            <p className="text-[11px] text-muted-foreground/50 italic">No forms mapped</p>
          ) : (
            <div className="space-y-1">
              {formDetails.map((f) => (
                <div key={f.id} className="flex items-center gap-1.5">
                  <Badge variant="secondary" className="text-[10px] font-mono">
                    #{f.id}
                  </Badge>
                  <span className="text-[11px] text-muted-foreground truncate">
                    {f.title}
                  </span>
                </div>
              ))}
            </div>
          )}

          {/* Stats row */}
          <div className="flex items-center gap-4 text-[11px] text-muted-foreground pt-1 border-t border-border/30">
            <span>
              <span className="font-mono font-medium text-foreground">
                {totalEntries.toLocaleString()}
              </span>{' '}
              entries
            </span>
            {lastEntryDate && (
              <span>
                Last:{' '}
                <span className="font-mono">
                  {formatRelativeDate(lastEntryDate)}
                </span>
              </span>
            )}
          </div>
        </CardContent>
      </Card>
    </Link>
  );
}

function formatRelativeDate(dateStr: string): string {
  const date = new Date(dateStr + 'Z');
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays === 0) return 'today';
  if (diffDays === 1) return 'yesterday';
  if (diffDays < 7) return `${diffDays}d ago`;
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`;
  if (diffDays < 365) return `${Math.floor(diffDays / 30)}mo ago`;
  return `${Math.floor(diffDays / 365)}y ago`;
}
