'use client';

import { useMemo } from 'react';
import { FileText, Code2, Database, Workflow } from 'lucide-react';
import { MermaidDiagram } from '@/components/mermaid-diagram';
import {
  PipelineLayerCard,
  type PipelineLayerItem,
} from '@/components/pipeline-layer-card';
import type { PipelineEntry } from '@/lib/pipeline-map';

interface PipelineViewProps {
  pipelineKey: string;
  pipeline: PipelineEntry;
}

/* ------------------------------------------------------------------ */
/*  Mermaid chart builder                                             */
/* ------------------------------------------------------------------ */

function buildMermaidChart(key: string, p: PipelineEntry): string {
  const lines: string[] = ['flowchart LR'];

  // Forms layer
  if (p.formIds.length > 0) {
    for (const formId of p.formIds) {
      lines.push(`  F${formId}["Form ${formId}"]`);
    }
  } else {
    lines.push(`  F_none["No mapped form"]`);
  }

  // API layer
  for (let i = 0; i < p.apiEndpoints.length; i++) {
    const ep = p.apiEndpoints[i];
    const label = `${ep.version}\\n${ep.method} ${ep.path.replace('/api/', '')}`;
    lines.push(`  API_${i}["${label}"]`);

    // Connect forms → APIs
    if (p.formIds.length > 0) {
      for (const formId of p.formIds) {
        lines.push(`  F${formId} --> API_${i}`);
      }
    } else {
      lines.push(`  F_none --> API_${i}`);
    }
  }

  // VTAP layer — show first, middle, and last to keep diagram readable
  const vtap = p.vtapEndpoints;
  if (vtap.length <= 4) {
    for (let i = 0; i < vtap.length; i++) {
      lines.push(`  VTAP_${i}["${vtap[i]}"]`);
      if (i === 0) {
        for (let j = 0; j < p.apiEndpoints.length; j++) {
          lines.push(`  API_${j} --> VTAP_0`);
        }
      } else {
        lines.push(`  VTAP_${i - 1} --> VTAP_${i}`);
      }
    }
  } else {
    // Abbreviated: first → ... → last
    lines.push(`  VTAP_0["${vtap[0]}"]`);
    lines.push(`  VTAP_1["... ${vtap.length - 2} more ..."]`);
    lines.push(`  VTAP_2["${vtap[vtap.length - 1]}"]`);
    for (let j = 0; j < p.apiEndpoints.length; j++) {
      lines.push(`  API_${j} --> VTAP_0`);
    }
    lines.push(`  VTAP_0 --> VTAP_1`);
    lines.push(`  VTAP_1 --> VTAP_2`);
  }

  // Workflows layer
  if (p.workflowNames.length > 0) {
    for (let i = 0; i < p.workflowNames.length; i++) {
      const wfLabel = p.workflowNames[i].length > 30
        ? p.workflowNames[i].slice(0, 30) + '...'
        : p.workflowNames[i];
      lines.push(`  WF_${i}["${wfLabel}"]`);
      const lastVtap = vtap.length <= 4 ? `VTAP_${vtap.length - 1}` : 'VTAP_2';
      lines.push(`  ${lastVtap} --> WF_${i}`);
    }
  }

  // Styling — explicit color ensures text is readable in both light and dark themes
  if (p.formIds.length > 0) {
    for (const formId of p.formIds) {
      lines.push(`  style F${formId} fill:#0ea5e920,stroke:#0ea5e9,color:#0c4a6e`);
    }
  } else {
    lines.push(`  style F_none fill:#6b728020,stroke:#6b7280,color:#374151`);
  }
  for (let i = 0; i < p.apiEndpoints.length; i++) {
    const colour = p.apiEndpoints[i].version === 'v2' ? '#14b8a6' : '#06b6d4';
    const textColour = p.apiEndpoints[i].version === 'v2' ? '#134e4a' : '#164e63';
    lines.push(`  style API_${i} fill:${colour}20,stroke:${colour},color:${textColour}`);
  }
  const vtapCount = vtap.length <= 4 ? vtap.length : 3;
  for (let i = 0; i < vtapCount; i++) {
    lines.push(`  style VTAP_${i} fill:#8b5cf620,stroke:#8b5cf6,color:#4c1d95`);
  }
  for (let i = 0; i < p.workflowNames.length; i++) {
    lines.push(`  style WF_${i} fill:#f43f5e20,stroke:#f43f5e,color:#881337`);
  }

  return lines.join('\n');
}

/* ------------------------------------------------------------------ */
/*  Component                                                         */
/* ------------------------------------------------------------------ */

export function PipelineView({ pipelineKey, pipeline }: PipelineViewProps) {
  const chart = useMemo(
    () => buildMermaidChart(pipelineKey, pipeline),
    [pipelineKey, pipeline],
  );

  // Build layer card items
  const formItems: PipelineLayerItem[] = pipeline.formIds.map((id) => ({
    label: `Form ${id}`,
    href: `/forms/${id}`,
    badges: [{ text: `ID ${id}`, accent: 'var(--cyan-glow)' }],
  }));

  const apiItems: PipelineLayerItem[] = pipeline.apiEndpoints.map((ep) => ({
    label: `${ep.method} ${ep.path}`,
    href: ep.docSlug ? `/docs/${ep.docSlug}` : undefined,
    badges: [
      {
        text: ep.version,
        accent:
          ep.version === 'v2' ? 'var(--teal-accent)' : 'var(--cyan-glow)',
      },
    ],
  }));

  const vtapItems: PipelineLayerItem[] = pipeline.vtapEndpoints.map((ep) => ({
    label: ep,
    href: '/docs/vtiger/vtap-endpoints',
    badges: [],
  }));

  const workflowItems: PipelineLayerItem[] = pipeline.workflowNames.map(
    (wf) => ({
      label: wf,
      href: '/workflows',
      badges: [],
    }),
  );

  return (
    <div className="space-y-6">
      {/* Mermaid pipeline diagram */}
      <MermaidDiagram chart={chart} />

      {/* Detail cards — four layers */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <PipelineLayerCard
          title="Forms"
          icon={FileText}
          accent="var(--cyan-glow)"
          items={formItems}
        />
        <PipelineLayerCard
          title="API Endpoints"
          icon={Code2}
          accent="var(--teal-accent)"
          items={apiItems}
        />
        <PipelineLayerCard
          title="VTAP Endpoints"
          icon={Database}
          accent="var(--violet-accent)"
          items={vtapItems}
        />
        <PipelineLayerCard
          title="Workflows"
          icon={Workflow}
          accent="var(--rose-accent)"
          items={workflowItems}
        />
      </div>
    </div>
  );
}
