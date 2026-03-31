'use client';

import { use, useMemo } from 'react';
import Link from 'next/link';
import {
  ArrowLeft,
  Workflow,
  ExternalLink,
  Mail,
  Globe,
  RefreshCw,
  ListChecks,
  PlusCircle,
  Bell,
  Zap,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { MermaidDiagram } from '@/components/mermaid-diagram';
import {
  useVtigerWorkflows,
  type VtigerWorkflow,
  type WorkflowCondition,
  type WorkflowTask,
} from '@/hooks/use-vtiger-workflows';

// ─── Task type config ────────────────────────────────────────────────────────

const TASK_CONFIG: Record<string, { label: string; icon: typeof Mail; colour: string }> = {
  VTEmailTask: { label: 'Send Email', icon: Mail, colour: 'violet' },
  VTWebhook: { label: 'Webhook', icon: Globe, colour: 'cyan' },
  VTUpdateFieldsTask: { label: 'Update Fields', icon: RefreshCw, colour: 'amber' },
  VTCreateTodoTask: { label: 'Create To-do', icon: ListChecks, colour: 'teal' },
  VTCreateEventTask: { label: 'Create Event', icon: Bell, colour: 'teal' },
  VTCreateEntityTask: { label: 'Create Record', icon: PlusCircle, colour: 'teal' },
  VTEntityMethodTask: { label: 'Custom Function', icon: Zap, colour: 'amber' },
  VTPushNotificationTask: { label: 'Push Notification', icon: Bell, colour: 'violet' },
};

function getTaskConfig(taskType: string) {
  return TASK_CONFIG[taskType] || { label: taskType, icon: Zap, colour: 'muted' };
}

// ─── Mermaid diagram builder ─────────────────────────────────────────────────

function buildWorkflowDiagram(wf: VtigerWorkflow): string {
  const lines: string[] = ['flowchart TD'];
  const conditions = wf.conditionsParsed ?? [];
  const tasks = wf.tasks ?? [];

  // Trigger node
  const triggerLabel = wf.trigger.replace(/"/g, "'");
  lines.push(`    trigger(["${wf.module}\\n${triggerLabel}"])`);

  // Conditions
  if (conditions.length > 0) {
    // Group conditions by groupid
    const groups = new Map<number, WorkflowCondition[]>();
    for (const c of conditions) {
      const list = groups.get(c.groupid) || [];
      list.push(c);
      groups.set(c.groupid, list);
    }

    // Build concise condition summary
    const condLines: string[] = [];
    for (const [, groupConds] of groups) {
      const join = groupConds[0]?.groupjoin === 'or' ? 'Any' : 'All';
      const fieldSummaries = groupConds
        .slice(0, 3)
        .map((c) => {
          const field = c.fieldname.length > 25
            ? c.fieldname.slice(0, 22) + '...'
            : c.fieldname;
          const val = c.value != null
            ? c.value.length > 20 ? c.value.slice(0, 17) + '...' : c.value
            : '';
          return `${field} ${c.operation}${val ? ' ' + val : ''}`;
        });
      if (groupConds.length > 3) {
        fieldSummaries.push(`+${groupConds.length - 3} more`);
      }
      condLines.push(`${join}: ${fieldSummaries.join(', ')}`);
    }

    const condLabel = condLines.join('\\n').replace(/"/g, "'");
    lines.push(`    trigger --> cond{"${condLabel}"}`);

    if (tasks.length > 0) {
      lines.push(`    cond -->|"Match"| task0`);
      lines.push(`    cond -->|"No match"| skip(["Skip"])`);
    }
  }

  // Task nodes
  for (let i = 0; i < tasks.length; i++) {
    const task = tasks[i];
    const config = getTaskConfig(task.taskType);
    const d = task.details;

    let detail = '';
    if (d.type === 'Email' && d.subject) {
      const subj = String(d.subject).length > 35
        ? String(d.subject).slice(0, 32) + '...'
        : String(d.subject);
      detail = `\\nSubject: ${subj.replace(/"/g, "'")}`;
    } else if (d.type === 'Webhook' && d.url) {
      const url = String(d.url);
      // Show just the path
      try {
        const parsed = new URL(url);
        detail = `\\n${d.method || 'POST'} ${parsed.pathname}`;
      } catch {
        detail = `\\n${d.method || 'POST'} ${url.slice(0, 40)}`;
      }
    } else if (d.type === 'FieldUpdate' && Array.isArray(d.fieldUpdates)) {
      const updates = d.fieldUpdates as Array<{ fieldname: string; value: string }>;
      const first = updates[0];
      if (first) {
        const fn = first.fieldname.length > 20
          ? first.fieldname.slice(0, 17) + '...'
          : first.fieldname;
        detail = `\\n${fn} = ${String(first.value).slice(0, 20).replace(/"/g, "'")}`;
        if (updates.length > 1) detail += `\\n+${updates.length - 1} more`;
      }
    } else if (d.type === 'CustomFunction' && d.methodName) {
      detail = `\\n${String(d.methodName).replace(/"/g, "'")}`;
    }

    const title = task.title.length > 30
      ? task.title.slice(0, 27).replace(/"/g, "'") + '...'
      : task.title.replace(/"/g, "'");

    lines.push(`    task${i}["${config.label}\\n${title}${detail}"]`);

    // Chain tasks sequentially
    if (i > 0) {
      lines.push(`    task${i - 1} --> task${i}`);
    }
  }

  // If no conditions, connect trigger directly to first task
  if (conditions.length === 0 && tasks.length > 0) {
    lines.push(`    trigger --> task0`);
  }

  // Style the nodes
  lines.push('');
  lines.push('    style trigger fill:#1a3038,stroke:#4db8b8,color:#eeedf2');
  if (conditions.length > 0) {
    lines.push('    style cond fill:#1e1e28,stroke:#8b8b3a,color:#eeedf2');
    if (tasks.length === 0) {
      // No tasks case
    } else {
      lines.push('    style skip fill:#1e1e28,stroke:#555,color:#888');
    }
  }
  for (let i = 0; i < tasks.length; i++) {
    const config = getTaskConfig(tasks[i].taskType);
    const strokeColour =
      config.colour === 'violet' ? '#8b5cf6'
        : config.colour === 'cyan' ? '#06b6d4'
          : config.colour === 'amber' ? '#f59e0b'
            : config.colour === 'teal' ? '#14b8a6'
              : '#6b7280';
    lines.push(`    style task${i} fill:#1a2838,stroke:${strokeColour},color:#eeedf2`);
  }

  return lines.join('\n');
}

// ─── Page component ──────────────────────────────────────────────────────────

export default function WorkflowDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = use(params);
  const workflowId = parseInt(id, 10);
  const { data, isLoading } = useVtigerWorkflows();

  const workflow = data?.workflows?.find((w: VtigerWorkflow) => w.id === workflowId);

  const chart = useMemo(() => {
    if (!workflow) return '';
    return buildWorkflowDiagram(workflow);
  }, [workflow]);

  // Related workflows in the same module
  const relatedWorkflows = useMemo(() => {
    if (!workflow || !data?.workflows) return [];
    return data.workflows
      .filter((w: VtigerWorkflow) => w.module === workflow.module && w.id !== workflow.id)
      .sort((a: VtigerWorkflow, b: VtigerWorkflow) => a.name.localeCompare(b.name));
  }, [workflow, data]);

  if (isLoading) {
    return (
      <div className="max-w-[900px] mx-auto px-6 py-6 space-y-4">
        <div className="skeleton h-8 w-48" />
        <div className="skeleton h-4 w-full" />
        <div className="skeleton h-32 w-full" />
      </div>
    );
  }

  if (!workflow) {
    return (
      <div className="max-w-[900px] mx-auto px-6 py-6">
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
          <p className="text-sm text-muted-foreground">Workflow not found: {id}</p>
          <Link
            href="/workflows"
            className="text-sm text-[var(--cyan-glow)] hover:underline mt-2 inline-block"
          >
            Back to workflows
          </Link>
        </div>
      </div>
    );
  }

  const conditions = workflow.conditionsParsed ?? [];
  const tasks = workflow.tasks ?? [];

  return (
    <div className="max-w-[900px] mx-auto px-6 py-6 space-y-6">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-xs text-muted-foreground">
        <Link
          href="/workflows"
          className="flex items-center gap-1 hover:text-foreground transition-colors"
        >
          <ArrowLeft className="h-3 w-3" />
          Workflows
        </Link>
        <span>/</span>
        <span className="text-foreground truncate">{workflow.name}</span>
      </div>

      {/* Header */}
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5 flex-wrap">
          <Workflow className="h-5 w-5 text-[var(--cyan-glow)]" />
          <h1 className="text-lg font-semibold tracking-tight">{workflow.name}</h1>
          <Badge variant="secondary" className="text-[10px] font-mono">
            ID {workflow.id}
          </Badge>
          <Badge
            variant="secondary"
            className={`text-[10px] ${workflow.enabled ? 'text-emerald-400' : 'text-muted-foreground'}`}
          >
            {workflow.enabled ? 'Enabled' : 'Disabled'}
          </Badge>
        </div>
        {workflow.description && (
          <p className="text-sm text-muted-foreground mt-1">{workflow.description}</p>
        )}
      </header>

      {/* Overview cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Module</p>
            <p className="text-base font-semibold">{workflow.module}</p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Trigger</p>
            <p className="text-sm font-medium">{workflow.trigger}</p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Type</p>
            <p className="text-base font-semibold capitalize">{workflow.workflowType}</p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Actions</p>
            <p className="text-xl font-semibold font-mono">{tasks.length || workflow.actions.length}</p>
          </CardContent>
        </Card>
      </div>

      {/* Vtiger link */}
      <a
        href={workflow.editUrl}
        target="_blank"
        rel="noopener noreferrer"
        className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-[var(--cyan-glow)] transition-colors"
      >
        <ExternalLink className="h-3 w-3" />
        Open in Vtiger
      </a>

      {/* Workflow flow diagram */}
      {chart && (tasks.length > 0 || conditions.length > 0) && (
        <div>
          <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-2">
            Workflow Flow
          </h3>
          <MermaidDiagram chart={chart} />
        </div>
      )}

      {/* Conditions */}
      {conditions.length > 0 && (
        <div>
          <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-2">
            Conditions ({conditions.length})
          </h3>
          <ConditionsTable conditions={conditions} />
        </div>
      )}

      {/* Tasks / Actions */}
      {tasks.length > 0 && (
        <div className="space-y-4">
          <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
            Actions ({tasks.length})
          </h3>
          {tasks.map((task, i) => (
            <TaskCard key={task.id} task={task} index={i} />
          ))}
        </div>
      )}

      {/* Related workflows in same module */}
      {relatedWorkflows.length > 0 && (
        <div>
          <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-2">
            Other {workflow.module} workflows ({relatedWorkflows.length})
          </h3>
          <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
            {relatedWorkflows.map((rw: VtigerWorkflow) => (
              <Link
                key={rw.id}
                href={`/workflows/${rw.id}`}
                className="flex items-center gap-3 px-4 py-2.5 border-b border-border/20 last:border-b-0 hover:bg-accent/5 transition-colors"
              >
                <span
                  className={`inline-block h-2 w-2 rounded-full shrink-0 ${
                    rw.enabled ? 'bg-emerald-400' : 'bg-muted-foreground/30'
                  }`}
                />
                <div className="flex-1 min-w-0">
                  <p className="text-sm text-[var(--cyan-glow)]">{rw.name}</p>
                  {rw.description && (
                    <p className="text-[11px] text-muted-foreground truncate">{rw.description}</p>
                  )}
                </div>
                <div className="flex gap-1 shrink-0">
                  {rw.actions.map((action, i) => (
                    <Badge key={i} variant="secondary" className="text-[10px]">
                      {action}
                    </Badge>
                  ))}
                </div>
              </Link>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Conditions table ────────────────────────────────────────────────────────

function ConditionsTable({ conditions }: { conditions: WorkflowCondition[] }) {
  // Group by groupid
  const groups = new Map<number, WorkflowCondition[]>();
  for (const c of conditions) {
    const list = groups.get(c.groupid) || [];
    list.push(c);
    groups.set(c.groupid, list);
  }

  return (
    <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow className="border-border/50 hover:bg-transparent">
            <TableHead className="text-xs w-16">Group</TableHead>
            <TableHead className="text-xs">Field</TableHead>
            <TableHead className="text-xs w-[140px]">Operator</TableHead>
            <TableHead className="text-xs">Value</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {[...groups.entries()].map(([groupId, groupConds]) => {
            const joinLabel = groupConds[0]?.groupjoin === 'or' ? 'Any' : 'All';
            return groupConds.map((cond, i) => (
              <TableRow key={`${groupId}-${i}`} className="border-border/30">
                {i === 0 && (
                  <TableCell
                    rowSpan={groupConds.length}
                    className="text-xs font-medium align-top"
                  >
                    <Badge variant="secondary" className="text-[10px]">
                      {joinLabel}
                    </Badge>
                  </TableCell>
                )}
                <TableCell className="font-mono text-xs">{cond.fieldname}</TableCell>
                <TableCell className="text-xs text-muted-foreground">{cond.operation}</TableCell>
                <TableCell className="font-mono text-xs text-muted-foreground">
                  {cond.value ?? '—'}
                </TableCell>
              </TableRow>
            ));
          })}
        </TableBody>
      </Table>
    </div>
  );
}

// ─── Task card ───────────────────────────────────────────────────────────────

function TaskCard({ task, index }: { task: WorkflowTask; index: number }) {
  const config = getTaskConfig(task.taskType);
  const Icon = config.icon;
  const d = task.details;
  const colourVar = `var(--${config.colour === 'cyan' ? 'cyan-glow' : config.colour + '-accent'})`;

  return (
    <div
      className="rounded-xl border bg-card p-4 space-y-3"
      style={{ borderColor: `color-mix(in oklch, ${colourVar} 25%, transparent)` }}
    >
      {/* Task header */}
      <div className="flex items-center gap-2">
        <div
          className="flex items-center justify-center h-7 w-7 rounded-lg shrink-0"
          style={{ backgroundColor: `color-mix(in oklch, ${colourVar} 15%, transparent)` }}
        >
          <Icon className="h-3.5 w-3.5" style={{ color: colourVar }} />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <span className="text-xs font-mono text-muted-foreground">{index + 1}.</span>
            <span className="text-sm font-medium">{config.label}</span>
            {!task.active && (
              <Badge variant="secondary" className="text-[10px] text-muted-foreground">
                Inactive
              </Badge>
            )}
          </div>
          <p className="text-xs text-muted-foreground truncate">{task.title}</p>
        </div>
      </div>

      {/* Email details */}
      {d.type === 'Email' && (
        <div className="space-y-2 text-xs">
          {!!d.to && <DetailRow label="To" value={String(d.to)} mono />}
          {!!d.cc && <DetailRow label="CC" value={String(d.cc)} mono />}
          {!!d.fromEmail && <DetailRow label="From" value={String(d.fromEmail)} mono />}
          {!!d.subject && <DetailRow label="Subject" value={String(d.subject)} />}
          {!!d.bodyExcerpt && (
            <div className="rounded-lg bg-secondary/30 p-3">
              <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-1">Body preview</p>
              <p className="text-xs text-muted-foreground leading-relaxed">
                {String(d.bodyExcerpt).substring(0, 400)}
                {String(d.bodyExcerpt).length > 400 ? '...' : ''}
              </p>
            </div>
          )}
        </div>
      )}

      {/* Webhook details */}
      {d.type === 'Webhook' && (
        <div className="space-y-2 text-xs">
          {!!d.url && <DetailRow label="URL" value={String(d.url)} mono />}
          {!!d.method && <DetailRow label="Method" value={String(d.method)} />}
          {!!d.contentType && <DetailRow label="Content-Type" value={String(d.contentType)} />}
          {Array.isArray(d.parameterMapping) && (d.parameterMapping as Array<{ fieldname: string; value: string }>).length > 0 && (
            <div className="rounded border border-border/20 overflow-hidden">
              <table className="w-full text-[11px]">
                <thead>
                  <tr className="bg-secondary/30 border-b border-border/20">
                    <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">Parameter</th>
                    <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">CRM Field</th>
                  </tr>
                </thead>
                <tbody>
                  {(d.parameterMapping as Array<{ fieldname: string; value: string }>).map((p, i) => (
                    <tr key={i} className="border-b border-border/10">
                      <td className="px-2 py-1 font-mono">{p.fieldname}</td>
                      <td className="px-2 py-1 font-mono text-muted-foreground">{p.value}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* Field Update details */}
      {d.type === 'FieldUpdate' && Array.isArray(d.fieldUpdates) && (
        <div className="rounded border border-border/20 overflow-hidden">
          <table className="w-full text-[11px]">
            <thead>
              <tr className="bg-secondary/30 border-b border-border/20">
                <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">Field</th>
                <th className="text-left px-2 py-1.5 font-medium text-muted-foreground">New Value</th>
                <th className="text-left px-2 py-1.5 font-medium text-muted-foreground w-24">Type</th>
              </tr>
            </thead>
            <tbody>
              {(d.fieldUpdates as Array<{ fieldname: string; value: string; valuetype: string }>).map((u, i) => (
                <tr key={i} className="border-b border-border/10">
                  <td className="px-2 py-1 font-mono">{u.fieldname}</td>
                  <td className="px-2 py-1 font-mono text-muted-foreground">{u.value}</td>
                  <td className="px-2 py-1 text-muted-foreground">{u.valuetype}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Custom Function details */}
      {d.type === 'CustomFunction' && !!d.methodName && (
        <DetailRow label="Method" value={String(d.methodName)} mono />
      )}
    </div>
  );
}

function DetailRow({ label, value, mono }: { label: string; value: string; mono?: boolean }) {
  return (
    <div className="flex gap-2">
      <span className="text-muted-foreground w-16 shrink-0">{label}</span>
      <span className={`break-all ${mono ? 'font-mono' : ''}`}>{value}</span>
    </div>
  );
}
