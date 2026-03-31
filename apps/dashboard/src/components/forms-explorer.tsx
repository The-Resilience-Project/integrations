'use client';

import { useState, useMemo } from 'react';
import Link from 'next/link';
import { ExternalLink, ArrowUpDown } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useForms } from '@/hooks/use-forms';
import type { GravityForm } from '@/lib/types';

type SortKey = 'id' | 'purpose' | 'entryCount' | 'fields';
type SortDir = 'asc' | 'desc';
type FilterType = 'all' | 'mapped' | 'unmapped' | 'active' | 'inactive';

export function FormsExplorer() {
  const { data, isLoading, error } = useForms();
  const [search, setSearch] = useState('');
  const [sortKey, setSortKey] = useState<SortKey>('entryCount');
  const [sortDir, setSortDir] = useState<SortDir>('desc');
  const [filter, setFilter] = useState<FilterType>('all');

  const forms = data?.forms ?? [];

  // Compute stats
  const stats = useMemo(() => {
    const totalEntries = forms.reduce((sum, f) => sum + (f.entryCount ?? 0), 0);
    const mapped = forms.filter((f) => f.endpoints.length > 0).length;
    const active = forms.filter((f) => f.isActive).length;
    return { total: forms.length, totalEntries, mapped, unmapped: forms.length - mapped, active };
  }, [forms]);

  // Filter
  const filtered = useMemo(() => {
    let result = forms;

    // Text search
    if (search) {
      const q = search.toLowerCase();
      result = result.filter(
        (f) =>
          f.title.toLowerCase().includes(q) ||
          f.purpose.toLowerCase().includes(q) ||
          String(f.id).includes(q) ||
          f.endpoints.some((e) => e.endpoint.toLowerCase().includes(q)),
      );
    }

    // Type filter
    if (filter === 'mapped') result = result.filter((f) => f.endpoints.length > 0);
    if (filter === 'unmapped') result = result.filter((f) => f.endpoints.length === 0);
    if (filter === 'active') result = result.filter((f) => f.isActive);
    if (filter === 'inactive') result = result.filter((f) => !f.isActive);

    return result;
  }, [forms, search, filter]);

  // Sort
  const sorted = useMemo(() => {
    const sorted = [...filtered];
    sorted.sort((a, b) => {
      let cmp = 0;
      switch (sortKey) {
        case 'id':
          cmp = a.id - b.id;
          break;
        case 'purpose':
          cmp = a.purpose.localeCompare(b.purpose);
          break;
        case 'entryCount':
          cmp = (a.entryCount ?? 0) - (b.entryCount ?? 0);
          break;
        case 'fields':
          cmp = a.fields.length - b.fields.length;
          break;
      }
      return sortDir === 'desc' ? -cmp : cmp;
    });
    return sorted;
  }, [filtered, sortKey, sortDir]);

  const toggleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    } else {
      setSortKey(key);
      setSortDir(key === 'purpose' ? 'asc' : 'desc');
    }
  };

  const SortIcon = ({ column }: { column: SortKey }) => (
    <ArrowUpDown
      className={`h-3 w-3 ml-1 inline-block ${
        sortKey === column ? 'text-[var(--cyan-glow)]' : 'opacity-30'
      }`}
    />
  );

  if (isLoading) {
    return (
      <div className="space-y-4">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          {[1, 2, 3, 4].map((i) => <div key={i} className="skeleton h-20 w-full" />)}
        </div>
        <div className="space-y-2">
          {[1, 2, 3, 4, 5].map((i) => (
            <div key={i} className="skeleton h-12 w-full" />
          ))}
        </div>
      </div>
    );
  }

  if (error || (data && !data.configured)) {
    return (
      <div className="rounded-xl border border-border/50 bg-card p-8 text-center space-y-2">
        <p className="text-sm text-muted-foreground">
          Gravity Forms API not configured.
        </p>
        <p className="text-xs text-muted-foreground">
          Set <code className="font-mono text-[var(--cyan-glow)]">GF_CONSUMER_KEY</code> and{' '}
          <code className="font-mono text-[var(--cyan-glow)]">GF_CONSUMER_SECRET</code> in{' '}
          <code className="font-mono">.env.local</code> to browse forms.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      {/* Summary stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Forms</p>
            <p className="text-xl font-semibold font-mono text-[var(--cyan-glow)]">{stats.total}</p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Total Submissions</p>
            <p className="text-xl font-semibold font-mono">{stats.totalEntries.toLocaleString()}</p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Mapped</p>
            <p className="text-xl font-semibold font-mono text-[var(--teal-accent)]">
              {stats.mapped}
              <span className="text-xs text-muted-foreground font-normal ml-1">/ {stats.total}</span>
            </p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Unmapped</p>
            <p className={`text-xl font-semibold font-mono ${stats.unmapped > 0 ? 'text-[var(--amber-accent)]' : 'text-muted-foreground'}`}>
              {stats.unmapped}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Search and filter bar */}
      <div className="flex flex-wrap items-center gap-2">
        <Input
          type="search"
          placeholder="Search forms by name, ID, or endpoint..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="max-w-sm h-8 text-xs bg-secondary border-border/50 placeholder:text-muted-foreground/50"
        />

        <Select
          value={filter}
          onValueChange={(val) => setFilter(val as FilterType)}
        >
          <SelectTrigger size="sm" className="text-xs">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All forms</SelectItem>
            <SelectItem value="mapped">Mapped</SelectItem>
            <SelectItem value="unmapped">Unmapped</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="inactive">Inactive</SelectItem>
          </SelectContent>
        </Select>

        <span className="text-[10px] font-mono text-muted-foreground ml-auto">
          {sorted.length} of {forms.length} forms
        </span>
      </div>

      {/* Table */}
      <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow className="border-border/50 hover:bg-transparent">
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground w-16">
                <button onClick={() => toggleSort('id')} className="flex items-center hover:text-foreground transition-colors">
                  ID<SortIcon column="id" />
                </button>
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                <button onClick={() => toggleSort('purpose')} className="flex items-center hover:text-foreground transition-colors">
                  Purpose<SortIcon column="purpose" />
                </button>
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground text-right w-24">
                <button onClick={() => toggleSort('entryCount')} className="flex items-center justify-end w-full hover:text-foreground transition-colors">
                  Entries<SortIcon column="entryCount" />
                </button>
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground text-center w-20">
                <button onClick={() => toggleSort('fields')} className="flex items-center justify-center w-full hover:text-foreground transition-colors">
                  Fields<SortIcon column="fields" />
                </button>
              </TableHead>
              <TableHead className="text-xs font-medium uppercase tracking-wider text-muted-foreground">Endpoints</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {sorted.map((form) => (
              <FormRow key={form.id} form={form} />
            ))}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}

function FormRow({ form }: { form: GravityForm }) {
  const inbound = form.endpoints.find((e) => e.direction === 'inbound');
  const outbound = form.endpoints.find((e) => e.direction === 'outbound');
  const hasEndpoints = form.endpoints.length > 0;

  return (
    <TableRow className="border-border/30 transition-colors hover:bg-accent/10">
      <TableCell className="font-mono text-sm">
        <Link
          href={`/forms/${form.id}`}
          className="flex items-center gap-1 text-[var(--cyan-glow)] hover:underline"
        >
          {form.id}
          <ExternalLink className="h-3 w-3 opacity-50" />
        </Link>
      </TableCell>
      <TableCell>
        <Link href={`/forms/${form.id}`} className="hover:text-[var(--cyan-glow)] transition-colors">
          <div className="flex items-center gap-2">
            <span className="text-sm font-medium">{form.purpose}</span>
            {!hasEndpoints && (
              <Badge variant="secondary" className="text-[10px] bg-secondary text-muted-foreground">
                unmapped
              </Badge>
            )}
            {!form.isActive && (
              <Badge variant="secondary" className="text-[10px] bg-[var(--rose-accent)]/10 text-[var(--rose-accent)]">
                inactive
              </Badge>
            )}
          </div>
        </Link>
      </TableCell>
      <TableCell className="text-right font-mono text-sm">
        <span className={(form.entryCount ?? 0) > 100 ? 'text-foreground' : 'text-muted-foreground'}>
          {(form.entryCount ?? 0).toLocaleString()}
        </span>
      </TableCell>
      <TableCell className="text-center font-mono text-sm text-muted-foreground">
        {form.fields.length || '—'}
      </TableCell>
      <TableCell>
        <div className="flex flex-wrap gap-1.5">
          {inbound && (
            <Badge variant="secondary" className="text-[10px] font-mono bg-[var(--teal-accent)]/10 text-[var(--teal-accent)] border-[var(--teal-accent)]/20">
              ← {truncateEndpoint(inbound.endpoint)}
            </Badge>
          )}
          {outbound && (
            <Badge variant="secondary" className="text-[10px] font-mono bg-[var(--violet-accent)]/10 text-[var(--violet-accent)] border-[var(--violet-accent)]/20">
              → {truncateEndpoint(outbound.endpoint)}
            </Badge>
          )}
        </div>
      </TableCell>
    </TableRow>
  );
}

function truncateEndpoint(endpoint: string): string {
  const parts = endpoint.split('/');
  const last = parts[parts.length - 1] || parts[parts.length - 2] || endpoint;
  return last.length > 30 ? last.slice(0, 27) + '...' : last;
}
