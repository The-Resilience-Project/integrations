'use client';

import Link from 'next/link';
import { FileText, ArrowRight } from 'lucide-react';
import { useForms } from '@/hooks/use-forms';
import {
  getAllPipelineEntries,
  type PipelineEntry,
} from '@/lib/pipeline-map';
import type { GravityForm } from '@/lib/types';

/* ------------------------------------------------------------------ */
/*  Group pipelines by journey for display                            */
/* ------------------------------------------------------------------ */

const JOURNEY_LABELS: Record<string, string> = {
  enquiries: 'Enquiries',
  conference: 'Conference',
  schools: 'School Operations',
};

const JOURNEY_ORDER = ['enquiries', 'conference', 'schools'];

interface FormWithPipeline {
  form: GravityForm;
  pipeline: PipelineEntry;
  pipelineKey: string;
}

/* ------------------------------------------------------------------ */
/*  Component                                                         */
/* ------------------------------------------------------------------ */

export function FormsCatalogueSection() {
  const { data: formsData, isLoading } = useForms();

  if (isLoading) {
    return (
      <section>
        <div className="flex items-center gap-2.5 mb-4">
          <div
            className="flex items-center justify-center h-7 w-7 rounded-md"
            style={{
              backgroundColor:
                'color-mix(in oklch, var(--cyan-glow) 15%, transparent)',
            }}
          >
            <FileText
              className="h-3.5 w-3.5"
              style={{ color: 'var(--cyan-glow)' }}
            />
          </div>
          <h2 className="text-sm font-semibold tracking-tight">
            Forms Catalogue
          </h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          {[1, 2, 3].map((i) => (
            <div key={i} className="skeleton h-24 rounded-xl" />
          ))}
        </div>
      </section>
    );
  }

  const forms = formsData?.forms ?? [];
  const pipelineEntries = getAllPipelineEntries();

  // Build list of forms that have pipeline mappings
  const mappedForms: FormWithPipeline[] = [];
  for (const [key, pipeline] of pipelineEntries) {
    for (const formId of pipeline.formIds) {
      const form = forms.find((f: GravityForm) => f.id === formId);
      if (form) {
        mappedForms.push({ form, pipeline, pipelineKey: key });
      }
    }
  }

  // Group by journey
  const byJourney = JOURNEY_ORDER.map((journey) => ({
    journey,
    label: JOURNEY_LABELS[journey] ?? journey,
    items: mappedForms.filter((mf) => mf.pipeline.journey === journey),
  })).filter((group) => group.items.length > 0);

  if (mappedForms.length === 0) return null;

  return (
    <section>
      <div className="flex items-center justify-between mb-5">
        <div className="flex items-center gap-2.5">
          <div
            className="flex items-center justify-center h-7 w-7 rounded-md"
            style={{
              backgroundColor:
                'color-mix(in oklch, var(--cyan-glow) 15%, transparent)',
            }}
          >
            <FileText
              className="h-3.5 w-3.5"
              style={{ color: 'var(--cyan-glow)' }}
            />
          </div>
          <h2 className="text-sm font-semibold tracking-tight">
            Forms Catalogue
          </h2>
        </div>
        <Link
          href="/forms"
          className="text-xs text-muted-foreground hover:text-foreground transition-colors flex items-center gap-1"
        >
          All forms
          <ArrowRight className="h-3 w-3" />
        </Link>
      </div>

      <div className="space-y-5">
        {byJourney.map(({ journey, label, items }) => (
          <div key={journey}>
            <div className="flex items-center gap-2.5 mb-2.5">
              <h3 className="text-[11px] font-medium uppercase tracking-widest text-muted-foreground/70">
                {label}
              </h3>
              <div className="flex-1 h-px bg-border/30" />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              {items.map(({ form, pipeline }) => (
                <Link
                  key={form.id}
                  href={`/forms/${form.id}`}
                  className="group relative flex flex-col rounded-xl bg-card ring-1 ring-border/60 hover:ring-[var(--cyan-glow)]/40 transition-all duration-200 overflow-hidden"
                >
                  <div
                    className="h-0.5 w-full"
                    style={{ backgroundColor: 'var(--cyan-glow)' }}
                  />
                  <div className="px-4 pt-3.5 pb-3">
                    <div className="flex items-center justify-between gap-2 mb-2.5">
                      <h4 className="text-[13px] font-semibold tracking-tight group-hover:text-[var(--cyan-glow)] transition-colors truncate">
                        {form.purpose || form.title}
                      </h4>
                      <ArrowRight className="h-3.5 w-3.5 text-muted-foreground/40 group-hover:text-[var(--cyan-glow)] group-hover:translate-x-0.5 transition-all shrink-0" />
                    </div>
                    <div className="flex items-center gap-1.5 flex-wrap">
                      <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium font-mono bg-muted text-muted-foreground">
                        ID {form.id}
                      </span>
                      <span
                        className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                        style={{
                          backgroundColor: `color-mix(in oklch, ${
                            pipeline.apiEndpoints.some(
                              (e) => e.version === 'v2',
                            )
                              ? 'var(--teal-accent)'
                              : 'var(--cyan-glow)'
                          } 15%, transparent)`,
                          color: pipeline.apiEndpoints.some(
                            (e) => e.version === 'v2',
                          )
                            ? 'var(--teal-accent)'
                            : 'var(--cyan-glow)',
                        }}
                      >
                        {pipeline.apiEndpoints
                          .map((e) => e.version)
                          .filter((v, i, a) => a.indexOf(v) === i)
                          .join(' + ')}
                      </span>
                      <span
                        className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                        style={{
                          backgroundColor:
                            'color-mix(in oklch, var(--violet-accent) 15%, transparent)',
                          color: 'var(--violet-accent)',
                        }}
                      >
                        {pipeline.vtapEndpoints.length} VTAP
                      </span>
                      <span
                        className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                        style={{
                          backgroundColor:
                            'color-mix(in oklch, var(--amber-accent) 15%, transparent)',
                          color: 'var(--amber-accent)',
                        }}
                      >
                        {pipeline.label}
                      </span>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
