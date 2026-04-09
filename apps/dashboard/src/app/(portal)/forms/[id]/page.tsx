'use client';

import { use, useMemo } from 'react';
import Link from 'next/link';
import { ArrowLeft, FileText, GitBranch, ArrowRight, Globe } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useForms } from '@/hooks/use-forms';
import { useGFFeeds } from '@/hooks/use-gf-feeds';
import { FormFlowDiagram } from '@/components/form-flow-diagram';
import { EntriesTable } from '@/components/entries-table';
import { FormResults } from '@/components/form-results';
import { urlToFunctionName } from '@/lib/url-to-function';
import { getFlowForForm } from '@/lib/form-to-flow';
import { getPipelineForForm } from '@/lib/pipeline-map';
import type { GravityForm, FormEndpoint, FormFieldMapping } from '@/lib/types';
import type { GFWebhookFeed } from '@/lib/gravity-forms';

export default function FormDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = use(params);
  const formId = parseInt(id, 10);
  const { data: formsData, isLoading } = useForms();
  const { data: feedsData } = useGFFeeds(formId);

  const form = formsData?.forms?.find((f: GravityForm) => f.id === formId);

  // Resolve which Lambda function this form's webhook calls
  const functionName = useMemo(() => {
    if (!form) return null;
    const outbound = form.endpoints.find((e: FormEndpoint) => e.direction === 'outbound');
    if (outbound) return urlToFunctionName(outbound.endpoint);
    if (feedsData?.webhooks?.[0]) return urlToFunctionName(feedsData.webhooks[0].url);
    return null;
  }, [form, feedsData]);

  // Find the email field ID from the form definition
  const emailFieldId = useMemo(() => {
    if (!form) return null;
    const emailField = form.fields.find((f) => f.type === 'email');
    return emailField ? String(emailField.id) : null;
  }, [form]);

  if (isLoading) {
    return (
      <div className="max-w-[900px] mx-auto px-6 py-6 space-y-4">
        <div className="skeleton h-8 w-48" />
        <div className="skeleton h-4 w-full" />
        <div className="skeleton h-32 w-full" />
      </div>
    );
  }

  if (!form) {
    return (
      <div className="max-w-[900px] mx-auto px-6 py-6">
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
          <p className="text-sm text-muted-foreground">Form not found: {id}</p>
          <Link
            href="/forms"
            className="text-sm text-[var(--cyan-glow)] hover:underline mt-2 inline-block"
          >
            Back to forms
          </Link>
        </div>
      </div>
    );
  }

  const inbound = form.endpoints.find((e: FormEndpoint) => e.direction === 'inbound');
  const outbound = form.endpoints.find((e: FormEndpoint) => e.direction === 'outbound');
  const relatedFlow = getFlowForForm(formId);

  return (
    <div className="max-w-[900px] mx-auto px-6 py-6 space-y-6">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-xs text-muted-foreground">
        <Link
          href="/forms"
          className="flex items-center gap-1 hover:text-foreground transition-colors"
        >
          <ArrowLeft className="h-3 w-3" />
          Forms
        </Link>
        <span>/</span>
        <span className="text-foreground">Form {form.id}</span>
      </div>

      {/* Header */}
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5">
          <FileText className="h-5 w-5 text-[var(--cyan-glow)]" />
          <h1 className="text-lg font-semibold tracking-tight">
            {form.purpose}
          </h1>
          <Badge variant="secondary" className="text-[10px] font-mono">
            ID {form.id}
          </Badge>
        </div>
        {form.title !== form.purpose && (
          <p className="text-sm text-muted-foreground mt-1">{form.title}</p>
        )}
      </header>

      {/* WordPress page link */}
      {form.wordpressPage && (
        <a
          href={form.wordpressPage.url}
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
              {form.wordpressPage.title}
            </p>
            <p className="text-[11px] text-muted-foreground truncate">
              {form.wordpressPage.url}
            </p>
          </div>
          <ArrowRight className="h-4 w-4 text-muted-foreground/40 group-hover:text-[var(--cyan-glow)] group-hover:translate-x-0.5 transition-all shrink-0" />
        </a>
      )}

      {/* Overview cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Pages</p>
            <p className="text-xl font-semibold font-mono">{form.pageCount}</p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Fields</p>
            <p className="text-xl font-semibold font-mono">{form.fields.length}</p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Inbound</p>
            <p className="text-xl font-semibold font-mono">{inbound ? '✓' : '—'}</p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Outbound</p>
            <p className="text-xl font-semibold font-mono">{outbound ? '✓' : '—'}</p>
          </CardContent>
        </Card>
      </div>

      {/* Related business flow + pipeline context */}
      {relatedFlow && (() => {
        const pipeline = getPipelineForForm(formId);
        return (
          <div className="rounded-xl border border-[var(--amber-accent)]/20 bg-[var(--amber-accent)]/5 overflow-hidden">
            <Link
              href={`/docs/flows/${relatedFlow.slug}`}
              className="group flex items-center gap-3 px-4 py-3 hover:bg-[var(--amber-accent)]/10 transition-colors"
            >
              <div
                className="flex items-center justify-center h-8 w-8 rounded-lg shrink-0"
                style={{ backgroundColor: 'color-mix(in oklch, var(--amber-accent) 15%, transparent)' }}
              >
                <GitBranch className="h-4 w-4 text-[var(--amber-accent)]" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium group-hover:text-[var(--amber-accent)] transition-colors">
                  {relatedFlow.title}
                </p>
                <p className="text-[11px] text-muted-foreground truncate">
                  {relatedFlow.description}
                </p>
              </div>
              <ArrowRight className="h-4 w-4 text-muted-foreground/40 group-hover:text-[var(--amber-accent)] group-hover:translate-x-0.5 transition-all shrink-0" />
            </Link>

            {pipeline && (
              <div className="px-4 pb-3 pt-0 space-y-2 border-t border-[var(--amber-accent)]/10">
                {/* API endpoints */}
                <div className="flex items-center gap-1.5 flex-wrap pt-2">
                  <span className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground/60 mr-1">
                    API
                  </span>
                  {pipeline.apiEndpoints.map((ep, i) => (
                    <span
                      key={i}
                      className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium font-mono"
                      style={{
                        backgroundColor: `color-mix(in oklch, ${ep.version === 'v2' ? 'var(--teal-accent)' : 'var(--cyan-glow)'} 15%, transparent)`,
                        color: ep.version === 'v2' ? 'var(--teal-accent)' : 'var(--cyan-glow)',
                      }}
                    >
                      {ep.version} {ep.path.replace('/api/', '')}
                    </span>
                  ))}
                </div>

                {/* VTAP chain */}
                <div className="flex items-center gap-1.5 flex-wrap">
                  <span className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground/60 mr-1">
                    VTAP
                  </span>
                  {pipeline.vtapEndpoints.map((ep, i) => (
                    <span key={i} className="inline-flex items-center text-[10px] text-muted-foreground">
                      {i > 0 && <span className="mx-0.5">→</span>}
                      <span
                        className="px-1 py-0.5 rounded font-mono"
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

                {/* Link to pipeline view */}
                <Link
                  href="/pipeline"
                  className="inline-flex items-center gap-1 text-[10px] text-muted-foreground/60 hover:text-[var(--teal-accent)] transition-colors pt-1"
                >
                  View full pipeline
                  <ArrowRight className="h-2.5 w-2.5" />
                </Link>
              </div>
            )}
          </div>
        );
      })()}

      {/* Tabbed content */}
      <Tabs defaultValue="overview">
        <TabsList variant="line">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
          <TabsTrigger value="entries">Entries</TabsTrigger>
        </TabsList>

        {/* Overview tab */}
        <TabsContent value="overview" className="space-y-6 pt-4">
          {/* Flow diagram */}
          <FormFlowDiagram
            formId={form.id}
            formTitle={form.purpose}
            endpoints={form.endpoints}
          />

          {/* Live webhook data — shown when static endpoints are missing */}
          {form.endpoints.length === 0 && feedsData?.webhooks && feedsData.webhooks.length > 0 && (
            <div className="space-y-4">
              <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                Webhooks <Badge variant="secondary" className="text-[10px] ml-2">live from GF API</Badge>
              </h3>
              <div className="space-y-3">
                {feedsData.webhooks.map((wh: GFWebhookFeed) => (
                  <div
                    key={wh.feedId}
                    className="rounded-xl border border-[var(--violet-accent)]/20 bg-[var(--violet-accent)]/5 p-4 space-y-2"
                  >
                    <div className="flex items-center gap-2">
                      <span className="text-[var(--violet-accent)] text-sm font-medium">→</span>
                      <span className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Outbound (Form → API)
                      </span>
                    </div>
                    <p className="font-mono text-xs text-foreground break-all">
                      {wh.method} {wh.url}
                    </p>
                    {wh.fieldMappings.length > 0 && (
                      <div className="rounded border border-border/20 overflow-hidden">
                        <table className="w-full text-[11px]">
                          <thead>
                            <tr className="bg-secondary/30 border-b border-border/20">
                              <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">Form Field</th>
                              <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">API Key</th>
                            </tr>
                          </thead>
                          <tbody>
                            {wh.fieldMappings.map((m, i) => (
                              <tr key={i} className="border-b border-border/10">
                                <td className="px-2 py-1 font-mono">{m.value}</td>
                                <td className="px-2 py-1 font-mono">{m.custom_key || m.key}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Static endpoint details */}
          {form.endpoints.length > 0 && (
            <div className="space-y-4">
              <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                Endpoints
              </h3>
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {form.endpoints.map((ep: FormEndpoint, i: number) => (
                  <EndpointDetail key={i} endpoint={ep} />
                ))}
              </div>
            </div>
          )}

          {/* Fields table */}
          {form.fields.length > 0 && (
            <div>
              <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-2">
                Fields ({form.fields.length})
              </h3>
              <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow className="border-border/50 hover:bg-transparent">
                      <TableHead className="text-xs w-12">#</TableHead>
                      <TableHead className="text-xs">Label</TableHead>
                      <TableHead className="text-xs">Type</TableHead>
                      <TableHead className="text-xs text-center w-20">Required</TableHead>
                      <TableHead className="text-xs text-center w-16">Page</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {form.fields.map((field) => (
                      <TableRow key={field.id} className="border-border/30">
                        <TableCell className="font-mono text-xs text-muted-foreground">
                          {field.id}
                        </TableCell>
                        <TableCell className="text-sm">{field.label}</TableCell>
                        <TableCell className="font-mono text-xs text-muted-foreground">
                          {field.type}
                        </TableCell>
                        <TableCell className="text-center">
                          {field.isRequired && (
                            <span className="text-[var(--amber-accent)] text-xs">Yes</span>
                          )}
                        </TableCell>
                        <TableCell className="text-center font-mono text-xs text-muted-foreground">
                          {field.page}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </div>
          )}
        </TabsContent>

        {/* Analytics tab */}
        <TabsContent value="analytics" className="pt-4">
          <FormResults formId={formId} fields={form.fields} />
        </TabsContent>

        {/* Entries tab */}
        <TabsContent value="entries" className="pt-4">
          <EntriesTable
            formId={formId}
            fields={form.fields}
            emailFieldId={emailFieldId}
            functionName={functionName}
          />
        </TabsContent>
      </Tabs>

    </div>
  );
}

function EndpointDetail({ endpoint }: { endpoint: FormEndpoint }) {
  const isInbound = endpoint.direction === 'inbound';
  const colour = isInbound ? 'teal' : 'violet';
  const label = isInbound ? 'Inbound (API → Form)' : 'Outbound (Form → API)';
  const arrow = isInbound ? '←' : '→';

  return (
    <div
      className={`rounded-xl border border-[var(--${colour}-accent)]/20 bg-[var(--${colour}-accent)]/5 p-4 space-y-3`}
    >
      <div className="flex items-center gap-2">
        <span className={`text-[var(--${colour}-accent)] text-sm font-medium`}>
          {arrow}
        </span>
        <span className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
          {label}
        </span>
      </div>
      <div>
        <p className="font-mono text-xs text-foreground break-all">
          {endpoint.method} {endpoint.endpoint}
        </p>
        <p className="text-[10px] text-muted-foreground mt-1">
          Trigger: {endpoint.trigger}
        </p>
      </div>

      {endpoint.fieldMappings.length > 0 && (
        <div className="rounded border border-border/20 overflow-hidden">
          <table className="w-full text-[11px]">
            <thead>
              <tr className="bg-secondary/30 border-b border-border/20">
                <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">
                  {isInbound ? 'API Field' : 'Form Field'}
                </th>
                <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">
                  {isInbound ? 'Form Field' : 'API Param'}
                </th>
                <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">Notes</th>
              </tr>
            </thead>
            <tbody>
              {endpoint.fieldMappings.map((m: FormFieldMapping, i: number) => (
                <tr key={i} className="border-b border-border/10">
                  <td className="px-2 py-1 font-mono">
                    {isInbound ? m.apiParam : m.formFieldLabel}
                  </td>
                  <td className="px-2 py-1 font-mono">
                    {isInbound ? m.formInput : m.apiParam}
                  </td>
                  <td className="px-2 py-1 text-muted-foreground">
                    {m.note ?? ''}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
