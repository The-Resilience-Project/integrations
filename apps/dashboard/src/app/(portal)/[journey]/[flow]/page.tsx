'use client';

import { use, useMemo } from 'react';
import Link from 'next/link';
import {
  ArrowLeft,
  ArrowDown,
  Globe,
  FileText,
  ExternalLink,
  AlertTriangle,
  Webhook,
  Server,
  GitBranch,
  Workflow,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { useForms } from '@/hooks/use-forms';
import { useGFFeeds } from '@/hooks/use-gf-feeds';
import { useWebhookErrors } from '@/hooks/use-webhook-errors';
import { EntriesTable } from '@/components/entries-table';
import { getJourney, getPipelineByKey } from '@/lib/pipeline-map';
import type { GravityForm } from '@/lib/types';

export default function FlowDetailPage({
  params,
}: {
  params: Promise<{ journey: string; flow: string }>;
}) {
  const { journey: journeySlug, flow: flowSlug } = use(params);
  const journey = getJourney(journeySlug);
  const pipeline = getPipelineByKey(flowSlug);

  const { data: formsData, isLoading: formsLoading } = useForms();
  const { data: webhookData } = useWebhookErrors();

  const firstFormId = pipeline?.formIds[0] ?? null;
  const { data: feedsData } = useGFFeeds(firstFormId);

  // Look up form objects for this pipeline's formIds
  const matchedForms = useMemo(() => {
    if (!pipeline || !formsData?.forms) return [];
    return pipeline.formIds
      .map((id) => formsData.forms.find((f: GravityForm) => f.id === id))
      .filter((f): f is GravityForm => f != null);
  }, [pipeline, formsData]);

  // Error counts (last 7 days) for this flow's forms
  const { recentErrors, recentErrorCount } = useMemo(() => {
    const cutoff = Date.now() - 7 * 24 * 60 * 60 * 1000;
    const formIdSet = new Set(pipeline?.formIds ?? []);
    const errors = (webhookData?.errors ?? []).filter(
      (e) => formIdSet.has(e.formId) && new Date(e.dateCreated + 'Z').getTime() > cutoff,
    );
    return { recentErrors: errors, recentErrorCount: errors.length };
  }, [webhookData, pipeline]);

  // Find email field for entries table
  const emailFieldId = useMemo(() => {
    if (matchedForms.length === 0) return null;
    const field = matchedForms[0].fields.find((f) => f.type === 'email');
    return field ? String(field.id) : null;
  }, [matchedForms]);

  if (formsLoading) {
    return (
      <div className="max-w-[900px] mx-auto px-6 py-6 space-y-4">
        <div className="skeleton h-8 w-48" />
        <div className="skeleton h-4 w-full" />
        <div className="skeleton h-32 w-full" />
      </div>
    );
  }

  if (!journey || !pipeline) {
    return (
      <div className="max-w-[900px] mx-auto px-6 py-6">
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
          <p className="text-sm text-muted-foreground">
            {!journey ? `Journey not found: ${journeySlug}` : `Flow not found: ${flowSlug}`}
          </p>
          <Link
            href={journey ? `/${journeySlug}` : '/'}
            className="text-sm text-[var(--cyan-glow)] hover:underline mt-2 inline-block"
          >
            {journey ? `Back to ${journey.label}` : 'Back to dashboard'}
          </Link>
        </div>
      </div>
    );
  }

  const webhooks = feedsData?.webhooks ?? [];

  return (
    <div className="max-w-[900px] mx-auto px-6 py-6 space-y-6">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-xs text-muted-foreground">
        <Link
          href={`/${journeySlug}`}
          className="flex items-center gap-1 hover:text-foreground transition-colors"
        >
          <ArrowLeft className="h-3 w-3" />
          {journey.label}
        </Link>
        <span>/</span>
        <span className="text-foreground">{pipeline.label}</span>
      </div>

      {/* Header */}
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5">
          <h1 className="text-lg font-semibold tracking-tight">{pipeline.label}</h1>
          {recentErrorCount > 0 && (
            <Badge
              variant="secondary"
              className="text-[10px] bg-[var(--rose-accent)]/10 text-[var(--rose-accent)]"
            >
              <AlertTriangle className="h-2.5 w-2.5 mr-0.5" />
              {recentErrorCount} recent {recentErrorCount === 1 ? 'error' : 'errors'}
            </Badge>
          )}
        </div>
      </header>

      {/* Vertical timeline of pipeline cards */}
      <div className="space-y-0">
        {/* Card 1: WordPress Page (if any matched form has a wordpressPage) */}
        {matchedForms.some((f) => f.wordpressPage) && (
          <>
            {matchedForms
              .filter((f) => f.wordpressPage)
              .map((f) => (
                <div key={`wp-${f.id}`}>
                  <a
                    href={f.wordpressPage!.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="group flex items-center gap-3 rounded-xl border border-[var(--cyan-glow)]/20 bg-[var(--cyan-glow)]/5 px-4 py-3 hover:bg-[var(--cyan-glow)]/10 transition-colors"
                  >
                    <div
                      className="flex items-center justify-center h-8 w-8 rounded-lg shrink-0"
                      style={{ backgroundColor: 'color-mix(in oklch, var(--cyan-glow) 15%, transparent)' }}
                    >
                      <Globe className="h-4 w-4 text-[var(--cyan-glow)]" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium group-hover:text-[var(--cyan-glow)] transition-colors">
                        {f.wordpressPage!.title}
                      </p>
                      <p className="text-[11px] text-muted-foreground truncate">
                        {f.wordpressPage!.url}
                      </p>
                    </div>
                    <ExternalLink className="h-4 w-4 text-muted-foreground/40 group-hover:text-[var(--cyan-glow)] transition-colors shrink-0" />
                  </a>
                  <ConnectingArrow />
                </div>
              ))}
          </>
        )}

        {/* Card 2: Gravity Forms */}
        {matchedForms.length > 0 ? (
          matchedForms.map((f) => (
            <div key={`form-${f.id}`}>
              <Link
                href={`/forms/${f.id}`}
                className="group block rounded-xl border border-[var(--teal-accent)]/20 bg-[var(--teal-accent)]/5 px-4 py-3 hover:bg-[var(--teal-accent)]/10 transition-colors"
              >
                <div className="flex items-center gap-3">
                  <div
                    className="flex items-center justify-center h-8 w-8 rounded-lg shrink-0"
                    style={{ backgroundColor: 'color-mix(in oklch, var(--teal-accent) 15%, transparent)' }}
                  >
                    <FileText className="h-4 w-4 text-[var(--teal-accent)]" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <p className="text-sm font-medium group-hover:text-[var(--teal-accent)] transition-colors">
                        {f.purpose || f.title}
                      </p>
                      <Badge variant="secondary" className="text-[10px] font-mono">
                        ID {f.id}
                      </Badge>
                    </div>
                    <div className="flex items-center gap-3 mt-1 text-[11px] text-muted-foreground">
                      <span>
                        <span className="font-mono">{(f.entryCount ?? 0).toLocaleString()}</span> entries
                      </span>
                      {f.lastEntryDate && (
                        <span>Last: <span className="font-mono">{formatRelativeDate(f.lastEntryDate)}</span></span>
                      )}
                      <span>
                        <span className="font-mono">{f.fields.length}</span> fields
                      </span>
                    </div>
                  </div>
                </div>
              </Link>
              <ConnectingArrow />
            </div>
          ))
        ) : (
          pipeline.formIds.length === 0 && (
            <div>
              <div className="rounded-xl border border-border/30 bg-card px-4 py-3">
                <p className="text-[11px] text-muted-foreground/50 italic">No Gravity Forms mapped to this flow</p>
              </div>
              <ConnectingArrow />
            </div>
          )
        )}

        {/* Card 3: Webhooks */}
        {webhooks.length > 0 && webhooks.map((wh, i) => (
          <div key={`wh-${i}`}>
            <div className="rounded-xl border border-[var(--violet-accent)]/20 bg-[var(--violet-accent)]/5 px-4 py-3 space-y-1">
              <div className="flex items-center gap-3">
                <div
                  className="flex items-center justify-center h-8 w-8 rounded-lg shrink-0"
                  style={{ backgroundColor: 'color-mix(in oklch, var(--violet-accent) 15%, transparent)' }}
                >
                  <Webhook className="h-4 w-4 text-[var(--violet-accent)]" />
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <p className="text-sm font-medium">Webhook #{wh.feedId}</p>
                    {recentErrorCount > 0 && (
                      <Badge
                        variant="secondary"
                        className="text-[10px] bg-[var(--rose-accent)]/10 text-[var(--rose-accent)]"
                      >
                        {recentErrorCount} errors (7d)
                      </Badge>
                    )}
                  </div>
                  <p className="text-[11px] text-muted-foreground font-mono truncate mt-0.5">
                    POST {wh.url}
                  </p>
                </div>
              </div>
            </div>
            <ConnectingArrow />
          </div>
        ))}

        {/* Card 4: API Endpoints */}
        {pipeline.apiEndpoints.length > 0 && (
          <div>
            <div className="rounded-xl border border-[var(--cyan-glow)]/20 bg-[var(--cyan-glow)]/5 px-4 py-3 space-y-2">
              <div className="flex items-center gap-3">
                <div
                  className="flex items-center justify-center h-8 w-8 rounded-lg shrink-0"
                  style={{ backgroundColor: 'color-mix(in oklch, var(--cyan-glow) 15%, transparent)' }}
                >
                  <Server className="h-4 w-4 text-[var(--cyan-glow)]" />
                </div>
                <p className="text-sm font-medium">API Endpoints</p>
              </div>
              <div className="flex flex-wrap gap-2 pl-11">
                {pipeline.apiEndpoints.map((ep, i) => (
                  <span
                    key={i}
                    className="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[11px] font-mono"
                    style={{
                      backgroundColor: `color-mix(in oklch, ${ep.version === 'v2' ? 'var(--teal-accent)' : 'var(--cyan-glow)'} 15%, transparent)`,
                      color: ep.version === 'v2' ? 'var(--teal-accent)' : 'var(--cyan-glow)',
                    }}
                  >
                    <Badge
                      variant="secondary"
                      className="text-[9px] font-mono px-1 py-0"
                    >
                      {ep.version}
                    </Badge>
                    {ep.method} {ep.path}
                  </span>
                ))}
              </div>
            </div>
            <ConnectingArrow />
          </div>
        )}

        {/* Card 5: VTAP Chain */}
        {pipeline.vtapEndpoints.length > 0 && (
          <div>
            <div className="rounded-xl border border-[var(--violet-accent)]/20 bg-[var(--violet-accent)]/5 px-4 py-3 space-y-2">
              <div className="flex items-center gap-3">
                <div
                  className="flex items-center justify-center h-8 w-8 rounded-lg shrink-0"
                  style={{ backgroundColor: 'color-mix(in oklch, var(--violet-accent) 15%, transparent)' }}
                >
                  <GitBranch className="h-4 w-4 text-[var(--violet-accent)]" />
                </div>
                <p className="text-sm font-medium">VTAP Chain</p>
              </div>
              <div className="flex items-center gap-1.5 flex-wrap pl-11">
                {pipeline.vtapEndpoints.map((ep, i) => (
                  <span key={i} className="inline-flex items-center text-[11px] text-muted-foreground">
                    {i > 0 && <span className="mx-0.5">→</span>}
                    <span
                      className="px-1.5 py-0.5 rounded font-mono"
                      style={{
                        backgroundColor: 'color-mix(in oklch, var(--violet-accent) 10%, transparent)',
                        color: 'var(--violet-accent)',
                      }}
                    >
                      {ep}
                    </span>
                  </span>
                ))}
              </div>
            </div>
            {pipeline.workflowNames.length > 0 && <ConnectingArrow />}
          </div>
        )}

        {/* Card 6: Vtiger Workflows */}
        {pipeline.workflowNames.length > 0 && (
          <div>
            <div className="rounded-xl border border-[var(--amber-accent)]/20 bg-[var(--amber-accent)]/5 px-4 py-3 space-y-2">
              <div className="flex items-center gap-3">
                <div
                  className="flex items-center justify-center h-8 w-8 rounded-lg shrink-0"
                  style={{ backgroundColor: 'color-mix(in oklch, var(--amber-accent) 15%, transparent)' }}
                >
                  <Workflow className="h-4 w-4 text-[var(--amber-accent)]" />
                </div>
                <p className="text-sm font-medium">Vtiger Workflows</p>
              </div>
              <ul className="pl-11 space-y-1">
                {pipeline.workflowNames.map((name, i) => (
                  <li key={i} className="text-[11px] text-muted-foreground">
                    {name}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        )}
      </div>

      {/* Tabs: Entries + Errors */}
      {matchedForms.length > 0 && (
        <Tabs defaultValue="entries">
          <TabsList variant="line">
            <TabsTrigger value="entries">Entries</TabsTrigger>
            <TabsTrigger value="errors" className="relative">
              Errors
              {recentErrors.length > 0 && (
                <span className="ml-1.5 inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full text-[10px] font-mono bg-[var(--rose-accent)]/15 text-[var(--rose-accent)]">
                  {recentErrors.length}
                </span>
              )}
            </TabsTrigger>
          </TabsList>

          <TabsContent value="entries" className="pt-4">
            <EntriesTable
              formId={matchedForms[0].id}
              fields={matchedForms[0].fields}
              emailFieldId={emailFieldId}
            />
          </TabsContent>

          <TabsContent value="errors" className="pt-4">
            {recentErrors.length === 0 ? (
              <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
                <p className="text-sm text-muted-foreground">
                  No recent webhook errors for this flow.
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                <p className="text-xs text-muted-foreground">
                  {recentErrors.length} webhook {recentErrors.length === 1 ? 'error' : 'errors'} in the last 7 days
                </p>
                {recentErrors.map((err, i) => (
                  <div
                    key={`${err.entryId}-${i}`}
                    className="rounded-xl border border-[var(--rose-accent)]/20 bg-[var(--rose-accent)]/5 p-4 space-y-2"
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <AlertTriangle className="h-3.5 w-3.5 text-[var(--rose-accent)]" />
                        <span className="text-sm font-medium">{err.feedName}</span>
                      </div>
                      <span className="text-[10px] font-mono text-muted-foreground">
                        Entry #{err.entryId}
                      </span>
                    </div>
                    <p className="text-xs text-muted-foreground break-words">
                      {err.error}
                    </p>
                    <p className="text-[10px] font-mono text-muted-foreground/60">
                      {new Date(err.dateCreated + 'Z').toLocaleString('en-AU', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </p>
                  </div>
                ))}
              </div>
            )}
          </TabsContent>
        </Tabs>
      )}
    </div>
  );
}

function ConnectingArrow() {
  return (
    <div className="flex justify-center py-2 text-muted-foreground/30">
      <ArrowDown className="h-4 w-4" />
    </div>
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
