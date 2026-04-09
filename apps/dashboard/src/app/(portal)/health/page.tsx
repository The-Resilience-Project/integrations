'use client';

import { useMemo } from 'react';
import Link from 'next/link';
import { Activity, AlertTriangle, CheckCircle, FileText, Zap, Server } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { useForms } from '@/hooks/use-forms';
import { useWebhookErrors } from '@/hooks/use-webhook-errors';
import { useMetrics } from '@/hooks/use-metrics';
import type { WebhookError } from '@/lib/gravity-forms';

function formatRelativeDate(dateStr: string): string {
  const date = new Date(dateStr + 'Z'); // GF dates are UTC
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / (1000 * 60));
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffMins < 1) return 'just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays === 1) return 'yesterday';
  if (diffDays < 7) return `${diffDays}d ago`;
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`;
  return `${Math.floor(diffDays / 30)}mo ago`;
}

export default function HealthPage() {
  const { data: formsData, isLoading: formsLoading } = useForms();
  const { data: webhookData, isLoading: webhooksLoading } = useWebhookErrors();
  const { data: metricsData, isLoading: metricsLoading } = useMetrics('24h');

  const now = new Date().toLocaleString('en-AU', {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

  // Webhook errors from the last 7 days
  const recentErrors = useMemo(() => {
    const cutoff = Date.now() - 7 * 24 * 60 * 60 * 1000;
    return (webhookData?.errors ?? []).filter(
      (e) => new Date(e.dateCreated + 'Z').getTime() > cutoff,
    );
  }, [webhookData]);

  // Unique entries in last 24h (proxy for submissions)
  const submissionsLast24h = useMemo(() => {
    const cutoff = Date.now() - 24 * 60 * 60 * 1000;
    const recent = (webhookData?.errors ?? []).filter(
      (e) => new Date(e.dateCreated + 'Z').getTime() > cutoff,
    );
    const uniqueEntries = new Set(recent.map((e) => `${e.formId}-${e.entryId}`));
    return uniqueEntries.size;
  }, [webhookData]);

  // Top Lambda functions by error count
  const topErrorFunctions = useMemo(() => {
    if (!metricsData?.functions) return [];
    return [...metricsData.functions]
      .filter((f) => f.errors > 0)
      .sort((a, b) => b.errors - a.errors)
      .slice(0, 10);
  }, [metricsData]);

  const isLoading = formsLoading || webhooksLoading || metricsLoading;

  if (isLoading) {
    return (
      <div className="max-w-[1400px] mx-auto px-6 py-6 space-y-6">
        <div className="skeleton h-8 w-48" />
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="skeleton h-24 rounded-xl" />
          ))}
        </div>
        <div className="skeleton h-64 rounded-xl" />
      </div>
    );
  }

  const totalForms = formsData?.forms?.length ?? 0;
  const errorRate = metricsData?.totals?.errorRate ?? 0;
  const hasErrors = recentErrors.length > 0;

  return (
    <div className="max-w-[1400px] mx-auto px-6 py-6 space-y-6">
      {/* Header */}
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5">
          <Activity className="h-5 w-5 text-[var(--cyan-glow)]" />
          <h1 className="text-lg font-semibold tracking-tight">Health</h1>
        </div>
        <p className="text-sm text-muted-foreground mt-1">{now}</p>
      </header>

      {/* Status cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <div className="flex items-center gap-1.5 mb-1">
              <FileText className="h-3 w-3 text-muted-foreground" />
              <p className="text-[10px] uppercase tracking-wider text-muted-foreground">
                Total Forms
              </p>
            </div>
            <p className="text-xl font-semibold font-mono">{totalForms}</p>
          </CardContent>
        </Card>

        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <div className="flex items-center gap-1.5 mb-1">
              <Zap className="h-3 w-3 text-muted-foreground" />
              <p className="text-[10px] uppercase tracking-wider text-muted-foreground">
                Submissions 24h
              </p>
            </div>
            <p className="text-xl font-semibold font-mono">{submissionsLast24h}</p>
          </CardContent>
        </Card>

        <Card className={`bg-card border-border/50 ${hasErrors ? 'border-[var(--rose-accent)]/30' : ''}`}>
          <CardContent className="pt-4 pb-3 px-4">
            <div className="flex items-center gap-1.5 mb-1">
              <AlertTriangle className={`h-3 w-3 ${hasErrors ? 'text-[var(--rose-accent)]' : 'text-muted-foreground'}`} />
              <p className="text-[10px] uppercase tracking-wider text-muted-foreground">
                Webhook Errors 7d
              </p>
            </div>
            <p className={`text-xl font-semibold font-mono ${hasErrors ? 'text-[var(--rose-accent)]' : ''}`}>
              {recentErrors.length}
            </p>
          </CardContent>
        </Card>

        <Card className={`bg-card border-border/50 ${errorRate > 5 ? 'border-[var(--rose-accent)]/30' : ''}`}>
          <CardContent className="pt-4 pb-3 px-4">
            <div className="flex items-center gap-1.5 mb-1">
              <Server className={`h-3 w-3 ${errorRate > 5 ? 'text-[var(--rose-accent)]' : 'text-muted-foreground'}`} />
              <p className="text-[10px] uppercase tracking-wider text-muted-foreground">
                Lambda Error Rate
              </p>
            </div>
            <p className={`text-xl font-semibold font-mono ${errorRate > 5 ? 'text-[var(--rose-accent)]' : ''}`}>
              {errorRate.toFixed(1)}%
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Recent Webhook Errors */}
      <section className="space-y-3">
        <h2 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
          Recent Webhook Errors
        </h2>
        {recentErrors.length === 0 ? (
          <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-6 text-center">
            <CheckCircle className="h-5 w-5 text-emerald-500 mx-auto mb-2" />
            <p className="text-sm text-emerald-500 font-medium">All webhooks healthy</p>
            <p className="text-[11px] text-muted-foreground mt-1">No errors in the last 7 days</p>
          </div>
        ) : (
          <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow className="border-border/50 hover:bg-transparent">
                  <TableHead className="text-xs">Form</TableHead>
                  <TableHead className="text-xs">Feed</TableHead>
                  <TableHead className="text-xs">Error</TableHead>
                  <TableHead className="text-xs w-24 text-right">When</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {recentErrors.map((err: WebhookError, i: number) => (
                  <TableRow key={`${err.entryId}-${i}`} className="border-border/30">
                    <TableCell className="text-sm">
                      <Link
                        href={`/forms/${err.formId}`}
                        className="text-[var(--cyan-glow)] hover:underline"
                      >
                        {err.formTitle}
                      </Link>
                    </TableCell>
                    <TableCell className="text-sm text-muted-foreground">
                      {err.feedName}
                    </TableCell>
                    <TableCell className="text-xs text-muted-foreground max-w-[400px] truncate">
                      {err.error}
                    </TableCell>
                    <TableCell className="text-xs font-mono text-muted-foreground text-right whitespace-nowrap">
                      {formatRelativeDate(err.dateCreated)}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </section>

      {/* Lambda Overview */}
      <section className="space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            Lambda Overview (24h)
          </h2>
          {metricsData?.totals && (
            <Badge variant="secondary" className="text-[10px] font-mono">
              {metricsData.totals.invocations.toLocaleString()} total invocations
            </Badge>
          )}
        </div>
        {topErrorFunctions.length === 0 ? (
          <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-6 text-center">
            <CheckCircle className="h-5 w-5 text-emerald-500 mx-auto mb-2" />
            <p className="text-sm text-emerald-500 font-medium">All Lambda functions healthy</p>
            <p className="text-[11px] text-muted-foreground mt-1">No errors in the last 24 hours</p>
          </div>
        ) : (
          <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow className="border-border/50 hover:bg-transparent">
                  <TableHead className="text-xs">Function</TableHead>
                  <TableHead className="text-xs text-right">Invocations</TableHead>
                  <TableHead className="text-xs text-right">Errors</TableHead>
                  <TableHead className="text-xs text-right">P95 (ms)</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {topErrorFunctions.map((fn) => (
                  <TableRow key={fn.name} className="border-border/30">
                    <TableCell className="font-mono text-sm">{fn.name}</TableCell>
                    <TableCell className="font-mono text-sm text-right text-muted-foreground">
                      {fn.invocations.toLocaleString()}
                    </TableCell>
                    <TableCell className="font-mono text-sm text-right text-[var(--rose-accent)]">
                      {fn.errors.toLocaleString()}
                    </TableCell>
                    <TableCell className="font-mono text-sm text-right text-muted-foreground">
                      {fn.p95Duration.toFixed(0)}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </section>
    </div>
  );
}
