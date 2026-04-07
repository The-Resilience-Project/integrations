'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { MetricsResponse } from '@/lib/types';
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
  Legend,
} from 'recharts';

interface OverviewProps {
  data: MetricsResponse;
}

const CHART_COLOURS = {
  cyan: 'oklch(0.75 0.15 195)',
  teal: 'oklch(0.7 0.12 170)',
  rose: 'oklch(0.65 0.2 25)',
  violet: 'oklch(0.68 0.15 290)',
  amber: 'oklch(0.8 0.16 80)',
  grid: 'oklch(0.22 0.02 260)',
  axis: 'oklch(0.45 0.015 260)',
};

export function Overview({ data }: OverviewProps) {
  const { totals, functions } = data;

  // Aggregate time series across all functions
  const timeSeriesMap = new Map<string, { timestamp: string; invocations: number; errors: number }>();
  for (const fn of functions) {
    for (const point of fn.timeSeries) {
      const existing = timeSeriesMap.get(point.timestamp) ?? {
        timestamp: point.timestamp,
        invocations: 0,
        errors: 0,
      };
      existing.invocations += point.invocations;
      existing.errors += point.errors;
      timeSeriesMap.set(point.timestamp, existing);
    }
  }
  const timeSeries = Array.from(timeSeriesMap.values())
    .sort((a, b) => a.timestamp.localeCompare(b.timestamp))
    .map((p) => ({
      ...p,
      time: new Date(p.timestamp).toLocaleTimeString('en-AU', {
        hour: '2-digit',
        minute: '2-digit',
      }),
    }));

  // Top functions by errors
  const topErrors = functions
    .filter((f) => f.errors > 0)
    .sort((a, b) => b.errors - a.errors)
    .slice(0, 10);

  // Top functions by invocations
  const topInvocations = functions
    .filter((f) => f.invocations > 0)
    .sort((a, b) => b.invocations - a.invocations)
    .slice(0, 10);

  // Top functions by duration
  const topDuration = functions
    .filter((f) => f.avgDuration > 0)
    .sort((a, b) => b.p95Duration - a.p95Duration)
    .slice(0, 10)
    .map((f) => ({
      name: f.name,
      avg: Math.round(f.avgDuration),
      p95: Math.round(f.p95Duration),
    }));

  const errorRateColour = totals.errorRate > 5
    ? 'text-[var(--rose-accent)]'
    : totals.errorRate > 1
      ? 'text-[var(--amber-accent)]'
      : 'text-[var(--teal-accent)]';

  return (
    <div className="space-y-4">
      {/* Metric cards */}
      <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
        <MetricCard
          label="Total Invocations"
          value={totals.invocations.toLocaleString()}
          colour="text-[var(--cyan-glow)]"
          border="border-[var(--cyan-glow)]/20"
        />
        <MetricCard
          label="Total Errors"
          value={totals.errors.toLocaleString()}
          colour="text-[var(--rose-accent)]"
          border="border-[var(--rose-accent)]/20"
        />
        <MetricCard
          label="Error Rate"
          value={`${totals.errorRate.toFixed(1)}%`}
          colour={errorRateColour}
          border={totals.errorRate > 5 ? 'border-[var(--rose-accent)]/20' : 'border-border/50'}
        />
      </div>

      {/* Invocations over time */}
      {timeSeries.length > 0 && (
        <Card className="bg-card border-border/50">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Invocations Over Time
            </CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={280}>
              <AreaChart data={timeSeries} margin={{ top: 8, right: 8, left: -12, bottom: 0 }}>
                <defs>
                  <linearGradient id="invGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={CHART_COLOURS.cyan} stopOpacity={0.25} />
                    <stop offset="100%" stopColor={CHART_COLOURS.cyan} stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="errGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={CHART_COLOURS.rose} stopOpacity={0.25} />
                    <stop offset="100%" stopColor={CHART_COLOURS.rose} stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={CHART_COLOURS.grid} vertical={false} />
                <XAxis
                  dataKey="time"
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
                <Legend
                  iconType="circle"
                  iconSize={8}
                  wrapperStyle={{ fontSize: '12px', paddingTop: '8px' }}
                />
                <Area
                  type="monotone"
                  dataKey="invocations"
                  stroke={CHART_COLOURS.cyan}
                  strokeWidth={2}
                  fill="url(#invGrad)"
                  name="Invocations"
                />
                <Area
                  type="monotone"
                  dataKey="errors"
                  stroke={CHART_COLOURS.rose}
                  strokeWidth={2}
                  fill="url(#errGrad)"
                  name="Errors"
                />
              </AreaChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      )}

      <div className="grid grid-cols-1 gap-3 lg:grid-cols-3">
        {/* Errors by function */}
        {topErrors.length > 0 && (
          <Card className="bg-card border-border/50">
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Errors by Function
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={Math.max(200, topErrors.length * 32)}>
                <BarChart data={topErrors} layout="vertical" margin={{ top: 0, right: 8, left: 0, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke={CHART_COLOURS.grid} horizontal={false} />
                  <XAxis
                    type="number"
                    fontSize={11}
                    fontFamily="var(--font-geist-mono)"
                    stroke={CHART_COLOURS.axis}
                    tickLine={false}
                    axisLine={false}
                  />
                  <YAxis
                    dataKey="name"
                    type="category"
                    fontSize={11}
                    fontFamily="var(--font-geist-mono)"
                    stroke={CHART_COLOURS.axis}
                    width={150}
                    tickLine={false}
                    axisLine={false}
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
                  <Bar dataKey="errors" fill={CHART_COLOURS.rose} radius={[0, 4, 4, 0]} name="Errors" />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        )}

        {/* Duration by function */}
        {topDuration.length > 0 && (
          <Card className="bg-card border-border/50">
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Duration — Top 10 Slowest
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={Math.max(200, topDuration.length * 32)}>
                <BarChart data={topDuration} layout="vertical" margin={{ top: 0, right: 8, left: 0, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke={CHART_COLOURS.grid} horizontal={false} />
                  <XAxis
                    type="number"
                    fontSize={11}
                    fontFamily="var(--font-geist-mono)"
                    stroke={CHART_COLOURS.axis}
                    tickLine={false}
                    axisLine={false}
                    tickFormatter={(v: number) => `${v}ms`}
                  />
                  <YAxis
                    dataKey="name"
                    type="category"
                    fontSize={11}
                    fontFamily="var(--font-geist-mono)"
                    stroke={CHART_COLOURS.axis}
                    width={150}
                    tickLine={false}
                    axisLine={false}
                  />
                  <Tooltip
                    contentStyle={{
                      background: 'oklch(0.16 0.015 260)',
                      border: '1px solid oklch(0.25 0.02 260)',
                      borderRadius: '8px',
                      fontSize: '12px',
                      fontFamily: 'var(--font-geist-mono)',
                    }}
                    formatter={(value) => [`${value}ms`]}
                  />
                  <Legend
                    iconType="circle"
                    iconSize={8}
                    wrapperStyle={{ fontSize: '12px', paddingTop: '8px' }}
                  />
                  <Bar dataKey="avg" fill={CHART_COLOURS.cyan} radius={[0, 4, 4, 0]} name="Avg" />
                  <Bar dataKey="p95" fill={CHART_COLOURS.violet} radius={[0, 4, 4, 0]} name="p95" />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        )}

        {/* Most executed functions */}
        {topInvocations.length > 0 && (
          <Card className="bg-card border-border/50">
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Most Executed
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={Math.max(200, topInvocations.length * 32)}>
                <BarChart data={topInvocations} layout="vertical" margin={{ top: 0, right: 8, left: 0, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke={CHART_COLOURS.grid} horizontal={false} />
                  <XAxis
                    type="number"
                    fontSize={11}
                    fontFamily="var(--font-geist-mono)"
                    stroke={CHART_COLOURS.axis}
                    tickLine={false}
                    axisLine={false}
                  />
                  <YAxis
                    dataKey="name"
                    type="category"
                    fontSize={11}
                    fontFamily="var(--font-geist-mono)"
                    stroke={CHART_COLOURS.axis}
                    width={150}
                    tickLine={false}
                    axisLine={false}
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
                  <Bar dataKey="invocations" fill={CHART_COLOURS.teal} radius={[0, 4, 4, 0]} name="Invocations" />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}

function MetricCard({
  label,
  value,
  colour,
  border,
}: {
  label: string;
  value: string;
  colour: string;
  border: string;
}) {
  return (
    <div className={`metric-glow rounded-xl border ${border} bg-card p-5 transition-colors hover:bg-accent/30`}>
      <p className="text-xs font-medium text-muted-foreground tracking-wide uppercase mb-3">
        {label}
      </p>
      <p className={`text-3xl font-semibold font-mono tracking-tight ${colour}`}>
        {value}
      </p>
    </div>
  );
}
