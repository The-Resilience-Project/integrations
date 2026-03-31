'use client';

import { useState, useMemo } from 'react';
import Link from 'next/link';
import { Workflow, Mail, Globe, RefreshCw, Zap, ChevronDown, ChevronRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useVtigerWorkflows, type VtigerWorkflow } from '@/hooks/use-vtiger-workflows';

type ActionFilter = 'all' | 'Email' | 'Webhook' | 'FieldUpdate';

// ─── Module summary card ─────────────────────────────────────────────────────

function ModuleCard({
  module,
  count,
  enabled,
  active,
  onClick,
}: {
  module: string;
  count: number;
  enabled: number;
  active: boolean;
  onClick: () => void;
}) {
  return (
    <button
      onClick={onClick}
      className={`rounded-lg border px-3 py-2 text-left transition-all ${
        active
          ? 'border-[var(--cyan-glow)]/50 bg-[var(--cyan-glow)]/10'
          : 'border-border/50 bg-card hover:border-border'
      }`}
    >
      <p className="text-sm font-medium">{module}</p>
      <div className="flex items-center gap-2 mt-0.5">
        <span className="text-xs font-mono text-muted-foreground">{count}</span>
        <span className="text-[10px] text-muted-foreground">
          ({enabled} active)
        </span>
      </div>
    </button>
  );
}

// ─── Action type filter chips ────────────────────────────────────────────────

const ACTION_FILTERS: { value: ActionFilter; label: string; icon: typeof Mail }[] = [
  { value: 'Email', label: 'Email', icon: Mail },
  { value: 'Webhook', label: 'Webhook', icon: Globe },
  { value: 'FieldUpdate', label: 'Field Update', icon: RefreshCw },
];

function hasActionType(wf: VtigerWorkflow, actionType: ActionFilter): boolean {
  if (actionType === 'all') return true;
  // Check tasks array (detail data)
  if (wf.tasks?.some((t) => {
    if (actionType === 'Email') return t.taskType === 'VTEmailTask';
    if (actionType === 'Webhook') return t.taskType === 'VTWebhook';
    if (actionType === 'FieldUpdate') return t.taskType === 'VTUpdateFieldsTask';
    return false;
  })) return true;
  // Fallback to actions summary strings
  return wf.actions.some((a) => a.toLowerCase().includes(actionType.toLowerCase()));
}

// ─── Grouped workflow list ───────────────────────────────────────────────────

function WorkflowGroup({
  module,
  workflows,
  defaultOpen,
}: {
  module: string;
  workflows: VtigerWorkflow[];
  defaultOpen: boolean;
}) {
  const [open, setOpen] = useState(defaultOpen);
  const enabled = workflows.filter((w) => w.enabled).length;

  return (
    <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
      <button
        onClick={() => setOpen(!open)}
        className="w-full flex items-center gap-2 px-4 py-3 hover:bg-accent/5 transition-colors"
      >
        {open ? (
          <ChevronDown className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
        ) : (
          <ChevronRight className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
        )}
        <span className="text-sm font-medium">{module}</span>
        <Badge variant="secondary" className="text-[10px] font-mono">
          {workflows.length}
        </Badge>
        <span className="text-[10px] text-muted-foreground">
          {enabled} active
        </span>
      </button>

      {open && (
        <div className="border-t border-border/30">
          {workflows
            .sort((a, b) => a.name.localeCompare(b.name))
            .map((wf) => (
            <WorkflowRow key={wf.id} workflow={wf} />
          ))}
        </div>
      )}
    </div>
  );
}

function WorkflowRow({ workflow }: { workflow: VtigerWorkflow }) {
  return (
    <Link
      href={`/workflows/${workflow.id}`}
      className="flex items-center gap-3 px-4 py-2.5 border-b border-border/20 last:border-b-0 hover:bg-accent/5 transition-colors"
    >
      <span
        className={`inline-block h-2 w-2 rounded-full shrink-0 ${
          workflow.enabled ? 'bg-emerald-400' : 'bg-muted-foreground/30'
        }`}
        title={workflow.enabled ? 'Enabled' : 'Disabled'}
      />

      <div className="flex-1 min-w-0">
        <p className="text-sm text-[var(--cyan-glow)]">{workflow.name}</p>
        {workflow.description && (
          <p className="text-[11px] text-muted-foreground truncate">{workflow.description}</p>
        )}
      </div>

      <span className="text-[11px] text-muted-foreground shrink-0 hidden sm:block w-[180px]">
        {workflow.trigger}
      </span>

      <div className="flex gap-1 shrink-0">
        {workflow.actions.map((action, i) => (
          <Badge key={i} variant="secondary" className="text-[10px]">
            {action}
          </Badge>
        ))}
      </div>
    </Link>
  );
}

// ─── Sort options ────────────────────────────────────────────────────────────

type SortOption = 'name' | 'module' | 'trigger' | 'actions';

function sortWorkflows(workflows: VtigerWorkflow[], sort: SortOption): VtigerWorkflow[] {
  return [...workflows].sort((a, b) => {
    switch (sort) {
      case 'name': return a.name.localeCompare(b.name);
      case 'module': return a.module.localeCompare(b.module) || a.name.localeCompare(b.name);
      case 'trigger': return a.trigger.localeCompare(b.trigger) || a.name.localeCompare(b.name);
      case 'actions': return b.actions.length - a.actions.length || a.name.localeCompare(b.name);
      default: return 0;
    }
  });
}

// ─── Page ────────────────────────────────────────────────────────────────────

export default function WorkflowsPage() {
  const { data, isLoading } = useVtigerWorkflows();
  const [search, setSearch] = useState('');
  const [moduleFilter, setModuleFilter] = useState('');
  const [actionFilter, setActionFilter] = useState<ActionFilter>('all');
  const [showDisabled, setShowDisabled] = useState(true);
  const [sort, setSort] = useState<SortOption>('module');

  const workflows = data?.workflows ?? [];

  // Module stats for summary cards
  const moduleStats = useMemo(() => {
    const stats: Record<string, { count: number; enabled: number }> = {};
    for (const wf of workflows) {
      if (!stats[wf.module]) stats[wf.module] = { count: 0, enabled: 0 };
      stats[wf.module].count++;
      if (wf.enabled) stats[wf.module].enabled++;
    }
    return Object.entries(stats).sort((a, b) => b[1].count - a[1].count);
  }, [workflows]);

  // Filter and search
  const filtered = useMemo(() => {
    let result = workflows;

    if (search) {
      const q = search.toLowerCase();
      result = result.filter(
        (w) =>
          w.name.toLowerCase().includes(q) ||
          w.conditions.toLowerCase().includes(q) ||
          (w.description ?? '').toLowerCase().includes(q) ||
          w.actions.some((a) => a.toLowerCase().includes(q)),
      );
    }

    if (moduleFilter) {
      result = result.filter((w) => w.module === moduleFilter);
    }

    if (actionFilter !== 'all') {
      result = result.filter((w) => hasActionType(w, actionFilter));
    }

    if (!showDisabled) {
      result = result.filter((w) => w.enabled);
    }

    return sortWorkflows(result, sort);
  }, [workflows, search, moduleFilter, actionFilter, showDisabled, sort]);

  // Group filtered results by module
  const grouped = useMemo(() => {
    const groups: Record<string, VtigerWorkflow[]> = {};
    for (const wf of filtered) {
      if (!groups[wf.module]) groups[wf.module] = [];
      groups[wf.module].push(wf);
    }
    return Object.entries(groups).sort((a, b) => a[0].localeCompare(b[0]));
  }, [filtered]);

  return (
    <div className="mx-auto px-6 py-6 space-y-5">
      <header className="pb-4 border-b border-border/50">
        <div className="flex items-center gap-2.5">
          <Workflow className="h-5 w-5 text-[var(--cyan-glow)]" />
          <h1 className="text-lg font-semibold tracking-tight">Vtiger Workflows</h1>
          {data?.count != null && (
            <Badge variant="secondary" className="text-[10px] font-mono">
              {data.count} total
            </Badge>
          )}
        </div>
        {data?.generated && (
          <p className="text-xs text-muted-foreground mt-1">
            Last scraped: {new Date(data.generated).toLocaleDateString('en-AU', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
            })}
          </p>
        )}
      </header>

      {/* Module summary cards */}
      {!isLoading && moduleStats.length > 0 && (
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setModuleFilter('')}
            className={`rounded-lg border px-3 py-2 text-left transition-all ${
              !moduleFilter
                ? 'border-[var(--cyan-glow)]/50 bg-[var(--cyan-glow)]/10'
                : 'border-border/50 bg-card hover:border-border'
            }`}
          >
            <p className="text-sm font-medium">All</p>
            <span className="text-xs font-mono text-muted-foreground">{workflows.length}</span>
          </button>
          {moduleStats.map(([mod, stats]) => (
            <ModuleCard
              key={mod}
              module={mod}
              count={stats.count}
              enabled={stats.enabled}
              active={moduleFilter === mod}
              onClick={() => setModuleFilter(moduleFilter === mod ? '' : mod)}
            />
          ))}
        </div>
      )}

      {/* Filters row */}
      <div className="flex flex-wrap items-center gap-2">
        <Input
          type="search"
          placeholder="Search workflows..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="max-w-[250px] h-8 text-xs bg-secondary border-border/50 placeholder:text-muted-foreground/50"
        />

        {/* Action type filter chips */}
        <div className="flex items-center gap-1">
          {ACTION_FILTERS.map(({ value, label, icon: Icon }) => (
            <button
              key={value}
              onClick={() => setActionFilter(actionFilter === value ? 'all' : value)}
              className={`flex items-center gap-1 rounded-md px-2 py-1 text-[11px] border transition-colors ${
                actionFilter === value
                  ? 'border-[var(--cyan-glow)]/50 bg-[var(--cyan-glow)]/10 text-foreground'
                  : 'border-border/50 text-muted-foreground hover:text-foreground hover:border-border'
              }`}
            >
              <Icon className="h-3 w-3" />
              {label}
            </button>
          ))}
        </div>

        <Select value={sort} onValueChange={(v) => setSort(v as SortOption)}>
          <SelectTrigger size="sm" className="text-xs w-[120px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="module">By module</SelectItem>
            <SelectItem value="name">By name</SelectItem>
            <SelectItem value="trigger">By trigger</SelectItem>
            <SelectItem value="actions">By actions</SelectItem>
          </SelectContent>
        </Select>

        <label className="flex items-center gap-1.5 text-xs text-muted-foreground cursor-pointer">
          <input
            type="checkbox"
            checked={showDisabled}
            onChange={(e) => setShowDisabled(e.target.checked)}
            className="rounded"
          />
          Show disabled
        </label>

        <span className="text-[10px] font-mono text-muted-foreground ml-auto">
          {filtered.length} shown
        </span>
      </div>

      {/* Grouped workflow list */}
      {isLoading ? (
        <div className="space-y-2">
          {[1, 2, 3, 4, 5].map((i) => (
            <div key={i} className="skeleton h-12 w-full" />
          ))}
        </div>
      ) : workflows.length === 0 ? (
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center space-y-2">
          <p className="text-sm text-muted-foreground">No workflow data found.</p>
          <p className="text-xs text-muted-foreground">
            Run the scraper: <code className="font-mono text-[var(--cyan-glow)]">VT_SESSION=&lt;cookie&gt; npx tsx scripts/scrape-vtiger-workflows.ts</code>
          </p>
        </div>
      ) : filtered.length === 0 ? (
        <div className="rounded-xl border border-border/50 bg-card p-8 text-center">
          <p className="text-sm text-muted-foreground">No workflows match your filters.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {grouped.map(([module, wfs]) => (
            <WorkflowGroup
              key={module}
              module={module}
              workflows={wfs}
              defaultOpen={!!moduleFilter || grouped.length <= 3}
            />
          ))}
        </div>
      )}
    </div>
  );
}
