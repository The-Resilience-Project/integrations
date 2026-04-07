'use client';

import { useState, useCallback } from 'react';
import { useSearchParams } from 'next/navigation';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { TIME_RANGES } from '@/lib/constants';
import type { TimeRange } from '@/lib/types';
import { useMetrics } from '@/hooks/use-metrics';
import { useFunctionFormsMap } from '@/hooks/use-function-forms-map';
import { Overview } from './overview';
import { FunctionsTable } from './functions-table';
import { LogViewer } from './log-viewer';
import { LoadingSkeleton } from './loading-skeleton';
import { MonitorForms } from './monitor-forms';
export function Dashboard() {
  const searchParams = useSearchParams();
  const activeTab = searchParams.get('tab') || 'overview';
  const range = (searchParams.get('range') || '24h') as TimeRange;
  const selectedLogFn = searchParams.get('fn') || null;

  const setParams = useCallback((updates: Record<string, string>) => {
    const params = new URLSearchParams(searchParams.toString());
    for (const [key, value] of Object.entries(updates)) {
      params.set(key, value);
    }
    window.history.replaceState(null, '', `?${params.toString()}`);
  }, [searchParams]);

  const setActiveTab = useCallback((tab: string) => setParams({ tab }), [setParams]);
  const setRange = useCallback((value: string) => setParams({ range: value }), [setParams]);

  const { data, isLoading, error, dataUpdatedAt } = useMetrics(range);
  const functionFormsMap = useFunctionFormsMap();

  function handleSelectFunction(name: string) {
    setParams({ fn: name, tab: 'logs' });
  }

  const lastUpdated = dataUpdatedAt
    ? new Date(dataUpdatedAt).toLocaleTimeString('en-AU', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      })
    : null;

  return (
    <div className="max-w-[1400px] mx-auto px-6 py-6 space-y-6">
      {/* Header */}
      <header className="flex items-end justify-between pb-4 border-b border-border/50">
        <div className="space-y-1">
          <h1 className="text-lg font-semibold tracking-tight">Monitor</h1>
          <div className="flex items-center gap-3 text-xs text-muted-foreground">
            {data && (
              <>
                <span className="flex items-center gap-1.5">
                  <span className="relative flex h-2 w-2">
                    <span className="live-pulse absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                    <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-400" />
                  </span>
                  Live
                </span>
                <span className="text-border">|</span>
                <span>Updated {lastUpdated}</span>
                <span className="text-border">|</span>
                <span className="font-mono">{data.totals.invocations.toLocaleString()} invocations</span>
              </>
            )}
          </div>
        </div>
        <Select value={range} onValueChange={(v) => v && setRange(v)}>
          <SelectTrigger className="w-[160px] h-9 text-xs bg-secondary border-border/50">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {TIME_RANGES.map((tr) => (
              <SelectItem key={tr.value} value={tr.value}>
                {tr.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </header>

      {/* Error */}
      {error && (
        <div className="rounded-lg border border-[var(--rose-accent)]/30 bg-[var(--rose-accent)]/5 p-4">
          <p className="text-sm text-[var(--rose-accent)]">
            Failed to load metrics. Check your AWS credentials (profile: trp-integrations).
          </p>
        </div>
      )}

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="bg-secondary/50 border border-border/30 p-1">
          <TabsTrigger
            value="overview"
            className="text-xs data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm"
          >
            Overview
          </TabsTrigger>
          <TabsTrigger
            value="functions"
            className="text-xs data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm"
          >
            Functions & Forms
            {data && (
              <span className="ml-1.5 text-[10px] font-mono text-muted-foreground">
                {data.functions.length}
              </span>
            )}
          </TabsTrigger>
          <TabsTrigger
            value="logs"
            className="text-xs data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm"
          >
            Logs
          </TabsTrigger>
        </TabsList>
        <TabsContent value="overview" className="mt-4">
          {data ? <Overview data={data} /> : isLoading ? <LoadingSkeleton /> : null}
        </TabsContent>
        <TabsContent value="functions" className="mt-4 space-y-6">
          {data ? (
            <>
              <MonitorForms
                functions={data.functions}
                functionFormsMap={functionFormsMap}
                range={range}
                onSelectFunction={handleSelectFunction}
              />
              <FunctionsTable
                functions={data.functions}
                onSelectFunction={handleSelectFunction}
                functionFormsMap={functionFormsMap}
              />
            </>
          ) : null}
        </TabsContent>
        <TabsContent value="logs" className="mt-4">
          <LogViewer
            range={range}
            initialFunction={selectedLogFn}
            onFunctionChange={(fn) => fn ? setParams({ fn }) : undefined}
            functionFormsMap={functionFormsMap}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
