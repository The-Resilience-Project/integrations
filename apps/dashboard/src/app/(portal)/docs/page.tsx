'use client';

import Link from 'next/link';
import {
  BookOpen,
  FileText,
  Code2,
  Database,
  GitBranch,
  ArrowRight,
  Zap,
  Route,
} from 'lucide-react';
import { useDocsTree } from '@/hooks/use-docs-tree';
import { FormsCatalogueSection } from '@/components/forms-catalogue-section';
import type { DocTreeItem } from '@/lib/docs-loader';

/* ------------------------------------------------------------------ */
/*  Flow metadata — stack info + service-type grouping                */
/* ------------------------------------------------------------------ */

interface FlowInfo {
  form: string;
  api: string;
  vtapCount: number;
  workflow?: boolean;
  group: string;
}

const flowMeta: Record<string, FlowInfo> = {
  enquiry: {
    group: 'schools',
    form: 'GF Webhooks',
    api: 'v1 + v2',
    vtapCount: 7,
    workflow: true,
  },
  'more-info': {
    group: 'schools',
    form: 'GF Webhooks',
    api: 'v2',
    vtapCount: 9,
  },
  registration: {
    group: 'schools',
    form: 'GF Webhooks',
    api: 'v1 + v2',
    vtapCount: 10,
  },
  'event-confirmation': {
    group: 'schools',
    form: 'Form 72',
    api: 'v1 + v2',
    vtapCount: 7,
  },
  'program-confirmation': {
    group: 'schools',
    form: 'Form 76/80',
    api: 'v1',
    vtapCount: 9,
  },
  'order-resources': {
    group: 'schools',
    form: 'Form 63/89',
    api: 'v1',
    vtapCount: 8,
  },
  'date-acceptance': {
    group: 'schools',
    form: 'Form 70',
    api: 'v1',
    vtapCount: 2,
  },
  assessment: {
    group: 'schools',
    form: 'Form 86',
    api: 'v1',
    vtapCount: 3,
  },
  'workplace-enquiry': {
    group: 'workplaces',
    form: 'GF Webhooks',
    api: 'v1',
    vtapCount: 7,
    workflow: true,
  },
  'early-years-enquiry': {
    group: 'early-years',
    form: 'GF Webhooks',
    api: 'v1',
    vtapCount: 7,
    workflow: true,
  },
  'early-years-confirmation': {
    group: 'early-years',
    form: 'Form 29',
    api: 'v1',
    vtapCount: 9,
  },
  'general-enquiry': {
    group: 'general',
    form: 'GF Webhooks',
    api: 'v1',
    vtapCount: 2,
    workflow: true,
  },
  'conference-import': {
    group: 'operations',
    form: 'TSV / Sheets',
    api: 'v1',
    vtapCount: 7,
    workflow: true,
  },
};

const serviceGroups = [
  { key: 'schools', label: 'Schools' },
  { key: 'workplaces', label: 'Workplaces' },
  { key: 'early-years', label: 'Early Years' },
  { key: 'general', label: 'General / Imperfects' },
  { key: 'operations', label: 'Bulk Operations' },
];

/* ------------------------------------------------------------------ */
/*  Reference group metadata                                         */
/* ------------------------------------------------------------------ */

const refMeta: Record<
  string,
  { icon: typeof BookOpen; accent: string; description: string }
> = {
  v1: {
    icon: Code2,
    accent: 'var(--cyan-glow)',
    description: 'Controller-based endpoints for all service types',
  },
  v2: {
    icon: Code2,
    accent: 'var(--teal-accent)',
    description: 'DDD-lite school endpoints',
  },
  vtiger: {
    icon: Database,
    accent: 'var(--violet-accent)',
    description: 'VTAP endpoints, CRM integration, and workflows',
  },
  guides: {
    icon: FileText,
    accent: 'var(--rose-accent)',
    description: 'Design system and development guides',
  },
};

const defaultRef = {
  icon: BookOpen,
  accent: 'var(--cyan-glow)',
  description: '',
};

/* ------------------------------------------------------------------ */
/*  Journey metadata                                                  */
/* ------------------------------------------------------------------ */

interface JourneyInfo {
  slug: string;
  title: string;
  subtitle: string;
  stages: number;
  accent: string;
}

const JOURNEYS: JourneyInfo[] = [
  {
    slug: 'flows/journeys/schools-journey',
    title: 'Schools Journey',
    subtitle: 'Enquiry → Registration → Confirmation → Resources → Assessment',
    stages: 7,
    accent: 'var(--teal-accent)',
  },
  {
    slug: 'flows/journeys/conference-journey',
    title: 'Conference Journey',
    subtitle: 'Enquiry → Import → Delegate Registration → Prize Pack',
    stages: 4,
    accent: 'var(--violet-accent)',
  },
  {
    slug: 'flows/journeys/enquiries-overview',
    title: 'Enquiries Overview',
    subtitle: 'How school, workplace, early years, and general enquiries relate',
    stages: 4,
    accent: 'var(--cyan-glow)',
  },
];

/* ------------------------------------------------------------------ */
/*  Helpers                                                           */
/* ------------------------------------------------------------------ */

function getFlowKey(item: DocTreeItem): string {
  return item.slug.split('/').pop() ?? '';
}

function groupHref(children: DocTreeItem[]): string {
  const idx = children.find((c) => c.slug.endsWith('/index'));
  return idx ? `/docs/${idx.slug}` : `/docs/${children[0]?.slug}`;
}

/* ------------------------------------------------------------------ */
/*  Flow card component                                              */
/* ------------------------------------------------------------------ */

function FlowCard({ item, info }: { item: DocTreeItem; info?: FlowInfo }) {
  return (
    <Link
      href={`/docs/${item.slug}`}
      className="group relative flex flex-col rounded-xl bg-card ring-1 ring-border/60 hover:ring-[var(--amber-accent)]/40 transition-all duration-200 overflow-hidden"
    >
      <div
        className="h-0.5 w-full"
        style={{ backgroundColor: 'var(--amber-accent)' }}
      />
      <div className="px-4 pt-3.5 pb-3">
        <div className="flex items-center justify-between gap-2 mb-2.5">
          <h3 className="text-[13px] font-semibold tracking-tight group-hover:text-[var(--amber-accent)] transition-colors truncate">
            {item.title}
          </h3>
          <ArrowRight className="h-3.5 w-3.5 text-muted-foreground/40 group-hover:text-[var(--amber-accent)] group-hover:translate-x-0.5 transition-all shrink-0" />
        </div>
        {info && (
          <div className="flex items-center gap-1.5 flex-wrap">
            <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-muted text-muted-foreground">
              {info.form}
            </span>
            <span
              className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
              style={{
                backgroundColor: `color-mix(in oklch, ${info.api.includes('v2') ? 'var(--teal-accent)' : 'var(--cyan-glow)'} 15%, transparent)`,
                color: info.api.includes('v2')
                  ? 'var(--teal-accent)'
                  : 'var(--cyan-glow)',
              }}
            >
              {info.api}
            </span>
            <span
              className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
              style={{
                backgroundColor:
                  'color-mix(in oklch, var(--violet-accent) 15%, transparent)',
                color: 'var(--violet-accent)',
              }}
            >
              {info.vtapCount} VTAP
            </span>
            {info.workflow && (
              <span
                className="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium"
                style={{
                  backgroundColor:
                    'color-mix(in oklch, var(--rose-accent) 15%, transparent)',
                  color: 'var(--rose-accent)',
                }}
              >
                <Zap className="h-2.5 w-2.5" />
                WF
              </span>
            )}
          </div>
        )}
      </div>
    </Link>
  );
}

/* ------------------------------------------------------------------ */
/*  Page component                                                   */
/* ------------------------------------------------------------------ */

export default function DocsPage() {
  const { data, isLoading } = useDocsTree();

  const flowsGroup = data?.tree.find((g) => g.slug === 'flows');
  const refGroups = data?.tree.filter((g) => g.slug !== 'flows') ?? [];

  // Flow items — filter out index, then group by service type
  const flowItems =
    flowsGroup?.children.filter((c) => !c.slug.endsWith('/index')) ?? [];
  const flowsHref = flowsGroup ? groupHref(flowsGroup.children) : '/docs';

  // Build grouped flows: known groups in order, then any ungrouped items
  const groupedFlows = serviceGroups
    .map(({ key, label }) => {
      const items = flowItems.filter((item) => {
        const info = flowMeta[getFlowKey(item)];
        return info?.group === key;
      });
      return { key, label, items };
    })
    .filter((g) => g.items.length > 0);

  // Catch any flows not in flowMeta (new files without metadata yet)
  const knownSlugs = new Set(
    groupedFlows.flatMap((g) => g.items.map((i) => i.slug)),
  );
  const ungrouped = flowItems.filter((i) => !knownSlugs.has(i.slug));
  if (ungrouped.length > 0) {
    groupedFlows.push({ key: 'other', label: 'Other', items: ungrouped });
  }

  return (
    <div className="max-w-[1100px] mx-auto px-6 py-6 space-y-8">
      {/* ── Page header ─────────────────────────────────────── */}
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5">
          <BookOpen className="h-5 w-5 text-[var(--cyan-glow)]" />
          <h1 className="text-lg font-semibold tracking-tight">
            Documentation
          </h1>
        </div>
        <p className="text-sm text-muted-foreground mt-1">
          Start with a business flow to see the full journey, then drill into
          API and CRM reference docs.
        </p>
      </header>

      {isLoading ? (
        <div className="space-y-8">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="skeleton h-28 rounded-xl" />
            ))}
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="skeleton h-24 rounded-xl" />
            ))}
          </div>
        </div>
      ) : (
        <>
          {/* ── Business Journeys — High-level maps ────────────── */}
          <section>
            <div className="flex items-center gap-2.5 mb-5">
              <div
                className="flex items-center justify-center h-7 w-7 rounded-md"
                style={{
                  backgroundColor:
                    'color-mix(in oklch, var(--teal-accent) 15%, transparent)',
                }}
              >
                <Route
                  className="h-3.5 w-3.5"
                  style={{ color: 'var(--teal-accent)' }}
                />
              </div>
              <h2 className="text-sm font-semibold tracking-tight">
                Business Journeys
              </h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              {JOURNEYS.map((journey) => (
                <Link
                  key={journey.slug}
                  href={`/docs/${journey.slug}`}
                  className="group relative flex flex-col rounded-xl bg-card ring-1 ring-border/60 hover:ring-[var(--teal-accent)]/40 transition-all duration-200 overflow-hidden"
                >
                  <div
                    className="h-0.5 w-full"
                    style={{ backgroundColor: journey.accent }}
                  />
                  <div className="px-4 pt-3.5 pb-3">
                    <div className="flex items-center justify-between gap-2 mb-1.5">
                      <h3 className="text-[13px] font-semibold tracking-tight group-hover:text-[var(--teal-accent)] transition-colors">
                        {journey.title}
                      </h3>
                      <ArrowRight className="h-3.5 w-3.5 text-muted-foreground/40 group-hover:text-[var(--teal-accent)] group-hover:translate-x-0.5 transition-all shrink-0" />
                    </div>
                    <p className="text-[11px] text-muted-foreground/70 leading-snug mb-2">
                      {journey.subtitle}
                    </p>
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-muted text-muted-foreground">
                      {journey.stages} stages
                    </span>
                  </div>
                </Link>
              ))}
            </div>
          </section>

          {/* ── Business Flows — Hero section ─────────────────── */}
          <section>
            <div className="flex items-center justify-between mb-5">
              <div className="flex items-center gap-2.5">
                <div
                  className="flex items-center justify-center h-7 w-7 rounded-md"
                  style={{
                    backgroundColor:
                      'color-mix(in oklch, var(--amber-accent) 15%, transparent)',
                  }}
                >
                  <GitBranch
                    className="h-3.5 w-3.5"
                    style={{ color: 'var(--amber-accent)' }}
                  />
                </div>
                <h2 className="text-sm font-semibold tracking-tight">
                  Business Flows
                </h2>
              </div>
              <Link
                href={flowsHref}
                className="text-xs text-muted-foreground hover:text-foreground transition-colors flex items-center gap-1"
              >
                View all
                <ArrowRight className="h-3 w-3" />
              </Link>
            </div>

            <div className="space-y-5">
              {groupedFlows.map(({ key, label, items }) => (
                <div key={key}>
                  {/* Service type label */}
                  <div className="flex items-center gap-2.5 mb-2.5">
                    <h3 className="text-[11px] font-medium uppercase tracking-widest text-muted-foreground/70">
                      {label}
                    </h3>
                    <div className="flex-1 h-px bg-border/30" />
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    {items.map((item) => (
                      <FlowCard
                        key={item.slug}
                        item={item}
                        info={flowMeta[getFlowKey(item)]}
                      />
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </section>

          {/* ── Forms Catalogue ─────────────────────────────────── */}
          <FormsCatalogueSection />

          {/* ── Reference Documentation ───────────────────────── */}
          <section>
            <div className="flex items-center gap-2.5 mb-4">
              <h2 className="text-xs font-medium uppercase tracking-widest text-muted-foreground">
                Reference
              </h2>
              <div className="flex-1 h-px bg-border/50" />
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
              {refGroups.map((group) => {
                const meta = refMeta[group.slug] ?? defaultRef;
                const Icon = meta.icon;
                const href = groupHref(group.children);
                const visibleDocs = group.children.filter(
                  (c) => !c.slug.endsWith('/index'),
                );
                const docCount = visibleDocs.length;

                return (
                  <Link
                    key={group.slug}
                    href={href}
                    className="group relative flex flex-col rounded-xl bg-card ring-1 ring-border/60 hover:ring-border transition-all duration-200 overflow-hidden"
                  >
                    <div
                      className="h-0.5 w-full"
                      style={{ backgroundColor: meta.accent }}
                    />
                    <div className="px-3.5 pt-3 pb-3">
                      <div className="flex items-center justify-between gap-2 mb-1">
                        <div className="flex items-center gap-2 min-w-0">
                          <Icon
                            className="h-3.5 w-3.5 shrink-0"
                            style={{ color: meta.accent }}
                          />
                          <h3 className="text-[13px] font-semibold tracking-tight truncate group-hover:text-[var(--cyan-glow)] transition-colors">
                            {group.label}
                          </h3>
                        </div>
                        <ArrowRight className="h-3 w-3 text-muted-foreground/40 group-hover:text-[var(--cyan-glow)] group-hover:translate-x-0.5 transition-all shrink-0" />
                      </div>
                      <p className="text-[11px] text-muted-foreground/70 leading-snug">
                        {meta.description}
                      </p>
                      <p className="text-[10px] text-muted-foreground/50 mt-1.5">
                        {docCount} {docCount === 1 ? 'document' : 'documents'}
                      </p>
                    </div>
                  </Link>
                );
              })}
            </div>
          </section>
        </>
      )}
    </div>
  );
}
