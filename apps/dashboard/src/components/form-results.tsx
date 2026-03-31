'use client';

import { useMemo } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  AreaChart,
  Area,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Cell,
} from 'recharts';
import { useGFResults } from '@/hooks/use-gf-results';
import { useGFEntries } from '@/hooks/use-gf-entries';
import type { FormField } from '@/lib/types';

const CHART_COLOURS = {
  cyan: 'oklch(0.75 0.15 195)',
  teal: 'oklch(0.7 0.12 170)',
  rose: 'oklch(0.65 0.2 25)',
  violet: 'oklch(0.68 0.15 290)',
  amber: 'oklch(0.8 0.16 80)',
  grid: 'oklch(0.22 0.02 260)',
  axis: 'oklch(0.45 0.015 260)',
};

const BAR_COLOURS = [
  CHART_COLOURS.cyan,
  CHART_COLOURS.teal,
  CHART_COLOURS.violet,
  CHART_COLOURS.amber,
  CHART_COLOURS.rose,
];

interface FormResultsProps {
  formId: number;
  fields: FormField[];
}

export function FormResults({ formId, fields }: FormResultsProps) {
  const { data: results, isLoading: resultsLoading } = useGFResults(formId);

  // Fetch entries for the last 90 days for the time series chart
  const ninetyDaysAgo = useMemo(() => {
    const d = new Date();
    d.setDate(d.getDate() - 90);
    return d.toISOString().split('T')[0];
  }, []);

  const { data: timeSeriesEntries } = useGFEntries({
    formId,
    pageSize: 500,
    startDate: ninetyDaysAgo,
  });

  // Build weekly time series from entries
  const weeklyData = useMemo(() => {
    if (!timeSeriesEntries?.entries?.length) return [];

    const buckets = new Map<string, number>();
    for (const entry of timeSeriesEntries.entries) {
      const date = new Date(entry.date_created);
      // Round to start of week (Monday)
      const day = date.getDay();
      const diff = date.getDate() - day + (day === 0 ? -6 : 1);
      const weekStart = new Date(date.setDate(diff));
      const key = weekStart.toISOString().split('T')[0];
      buckets.set(key, (buckets.get(key) ?? 0) + 1);
    }

    return Array.from(buckets.entries())
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([week, count]) => ({
        week: new Date(week).toLocaleDateString('en-AU', { day: '2-digit', month: 'short' }),
        submissions: count,
      }));
  }, [timeSeriesEntries]);

  // Build field label map
  const fieldLabelMap = useMemo(() => {
    const map = new Map<string, string>();
    for (const f of fields) {
      map.set(String(f.id), f.label);
    }
    return map;
  }, [fields]);

  // Process choice distribution data
  const choiceCharts = useMemo(() => {
    if (!results?.field_data) return [];

    return Object.entries(results.field_data)
      .map(([fieldId, choices]) => {
        const label = fieldLabelMap.get(fieldId) ?? `Field ${fieldId}`;
        const data = Object.entries(choices)
          .map(([name, count]) => ({ name, count }))
          .sort((a, b) => b.count - a.count);
        return { fieldId, label, data };
      })
      .filter((chart) => chart.data.length > 0);
  }, [results, fieldLabelMap]);

  // Find most popular choice across all fields
  const mostPopular = useMemo(() => {
    if (!choiceCharts.length) return null;
    let best = { field: '', choice: '', count: 0 };
    for (const chart of choiceCharts) {
      for (const item of chart.data) {
        if (item.count > best.count) {
          best = { field: chart.label, choice: item.name, count: item.count };
        }
      }
    }
    return best.count > 0 ? best : null;
  }, [choiceCharts]);

  if (resultsLoading) {
    return (
      <div className="space-y-3">
        <div className="skeleton h-6 w-32" />
        <div className="grid grid-cols-3 gap-3">
          {[1, 2, 3].map((i) => <div key={i} className="skeleton h-20 w-full" />)}
        </div>
      </div>
    );
  }

  if (!results) return null;

  return (
    <div className="space-y-4">
      <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
        Analytics
      </h3>

      {/* Summary cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Total Entries</p>
            <p className="text-xl font-semibold font-mono text-[var(--cyan-glow)]">
              {results.entry_count.toLocaleString()}
            </p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Choice Fields</p>
            <p className="text-xl font-semibold font-mono">
              {choiceCharts.length}
            </p>
          </CardContent>
        </Card>
        <Card className="bg-card border-border/50">
          <CardContent className="pt-4 pb-3 px-4">
            <p className="text-[10px] uppercase tracking-wider text-muted-foreground">Most Popular</p>
            {mostPopular ? (
              <p className="text-sm font-medium truncate" title={`${mostPopular.choice} (${mostPopular.field})`}>
                {mostPopular.choice}
                <span className="text-xs text-muted-foreground ml-1">({mostPopular.count})</span>
              </p>
            ) : (
              <p className="text-sm text-muted-foreground">—</p>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Submissions over time */}
      {weeklyData.length > 1 && (
        <Card className="bg-card border-border/50">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Submissions Over Time
            </CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={200}>
              <AreaChart data={weeklyData} margin={{ top: 8, right: 8, left: -12, bottom: 0 }}>
                <defs>
                  <linearGradient id="submGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={CHART_COLOURS.cyan} stopOpacity={0.25} />
                    <stop offset="100%" stopColor={CHART_COLOURS.cyan} stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={CHART_COLOURS.grid} vertical={false} />
                <XAxis
                  dataKey="week"
                  fontSize={11}
                  fontFamily="var(--font-geist-mono)"
                  stroke={CHART_COLOURS.axis}
                  tickLine={false}
                  axisLine={false}
                />
                <YAxis
                  fontSize={11}
                  fontFamily="var(--font-geist-mono)"
                  stroke={CHART_COLOURS.axis}
                  tickLine={false}
                  axisLine={false}
                  allowDecimals={false}
                />
                <Tooltip
                  contentStyle={{
                    background: 'oklch(0.16 0.015 260)',
                    border: '1px solid oklch(0.25 0.02 260)',
                    borderRadius: '8px',
                    fontSize: '12px',
                    fontFamily: 'var(--font-geist-mono)',
                  }}
                />
                <Area
                  type="monotone"
                  dataKey="submissions"
                  stroke={CHART_COLOURS.cyan}
                  fill="url(#submGrad)"
                  strokeWidth={2}
                  name="Submissions"
                />
              </AreaChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      )}

      {/* Choice distribution charts */}
      {choiceCharts.length > 0 && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          {choiceCharts.map((chart) => (
            <Card key={chart.fieldId} className="bg-card border-border/50">
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground truncate">
                  {chart.label}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={Math.max(120, chart.data.length * 32)}>
                  <BarChart
                    data={chart.data}
                    layout="vertical"
                    margin={{ top: 0, right: 8, left: 0, bottom: 0 }}
                  >
                    <CartesianGrid strokeDasharray="3 3" stroke={CHART_COLOURS.grid} horizontal={false} />
                    <XAxis
                      type="number"
                      fontSize={11}
                      fontFamily="var(--font-geist-mono)"
                      stroke={CHART_COLOURS.axis}
                      tickLine={false}
                      axisLine={false}
                      allowDecimals={false}
                    />
                    <YAxis
                      type="category"
                      dataKey="name"
                      fontSize={11}
                      stroke={CHART_COLOURS.axis}
                      tickLine={false}
                      axisLine={false}
                      width={120}
                      tick={{ fontSize: 10 }}
                    />
                    <Tooltip
                      contentStyle={{
                        background: 'oklch(0.16 0.015 260)',
                        border: '1px solid oklch(0.25 0.02 260)',
                        borderRadius: '8px',
                        fontSize: '12px',
                        fontFamily: 'var(--font-geist-mono)',
                      }}
                    />
                    <Bar dataKey="count" radius={[0, 4, 4, 0]} name="Responses">
                      {chart.data.map((_, index) => (
                        <Cell key={index} fill={BAR_COLOURS[index % BAR_COLOURS.length]} />
                      ))}
                    </Bar>
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
