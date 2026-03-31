'use client';

import { useState } from 'react';
import { Layers, GitBranch } from 'lucide-react';
import { PipelineView } from '@/components/pipeline-view';
import {
  getAllPipelineEntries,
  type PipelineEntry,
} from '@/lib/pipeline-map';

/* ------------------------------------------------------------------ */
/*  Group entries by journey for the selector                         */
/* ------------------------------------------------------------------ */

const JOURNEY_LABELS: Record<string, string> = {
  enquiries: 'Enquiries',
  conference: 'Conference',
  schools: 'School Operations',
};

const JOURNEY_ORDER = ['enquiries', 'conference', 'schools'];

interface GroupedPipeline {
  journey: string;
  label: string;
  entries: [string, PipelineEntry][];
}

function groupByJourney(): GroupedPipeline[] {
  const all = getAllPipelineEntries();
  return JOURNEY_ORDER.map((journey) => ({
    journey,
    label: JOURNEY_LABELS[journey] ?? journey,
    entries: all.filter(([, p]) => p.journey === journey),
  })).filter((g) => g.entries.length > 0);
}

/* ------------------------------------------------------------------ */
/*  Page                                                              */
/* ------------------------------------------------------------------ */

export default function PipelinePage() {
  const groups = groupByJourney();
  const [selectedKey, setSelectedKey] = useState<string>(
    groups[0]?.entries[0]?.[0] ?? '',
  );

  const allEntries = getAllPipelineEntries();
  const selected = allEntries.find(([key]) => key === selectedKey);

  return (
    <div className="max-w-[1100px] mx-auto px-6 py-6 space-y-6">
      {/* Header */}
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5">
          <Layers className="h-5 w-5 text-[var(--teal-accent)]" />
          <h1 className="text-lg font-semibold tracking-tight">Pipeline</h1>
        </div>
        <p className="text-sm text-muted-foreground mt-1">
          Trace the full journey from form submission through API, VTAP
          endpoints, and CRM workflows.
        </p>
      </header>

      {/* Flow selector */}
      <div className="flex items-center gap-3">
        <div className="flex items-center gap-2">
          <GitBranch
            className="h-3.5 w-3.5"
            style={{ color: 'var(--amber-accent)' }}
          />
          <label
            htmlFor="flow-selector"
            className="text-xs font-medium text-muted-foreground"
          >
            Flow
          </label>
        </div>
        <select
          id="flow-selector"
          value={selectedKey}
          onChange={(e) => setSelectedKey(e.target.value)}
          className="flex-1 max-w-sm rounded-lg border border-border/50 bg-card px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--teal-accent)]/30"
        >
          {groups.map((group) => (
            <optgroup key={group.journey} label={group.label}>
              {group.entries.map(([key, pipeline]) => (
                <option key={key} value={key}>
                  {pipeline.label}
                </option>
              ))}
            </optgroup>
          ))}
        </select>
      </div>

      {/* Pipeline visualisation */}
      {selected ? (
        <PipelineView pipelineKey={selected[0]} pipeline={selected[1]} />
      ) : (
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
          <p className="text-sm text-muted-foreground">
            Select a flow to view its pipeline.
          </p>
        </div>
      )}
    </div>
  );
}
