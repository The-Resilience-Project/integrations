import {
  CloudWatchClient,
  GetMetricDataCommand,
  type MetricDataQuery,
} from '@aws-sdk/client-cloudwatch';
import {
  CloudWatchLogsClient,
  FilterLogEventsCommand,
} from '@aws-sdk/client-cloudwatch-logs';
import { fromIni } from '@aws-sdk/credential-providers';
import {
  AWS_REGION,
  AWS_PROFILE,
  SERVICE_PREFIX,
  FUNCTION_NAMES,
  getTimeRangeMs,
  getPeriodSeconds,
} from './constants';
import type { FunctionMetrics, MetricsResponse, LogEntry } from './types';

const credentials = fromIni({ profile: AWS_PROFILE });

const cwClient = new CloudWatchClient({ region: AWS_REGION, credentials });
const cwLogsClient = new CloudWatchLogsClient({ region: AWS_REGION, credentials });

function sanitiseId(name: string): string {
  return name.replace(/[^a-zA-Z0-9_]/g, '_').replace(/^[^a-z]/, 'm$&');
}

export async function getMetrics(range: string): Promise<MetricsResponse> {
  const now = Date.now();
  const startTime = new Date(now - getTimeRangeMs(range));
  const endTime = new Date(now);
  const period = getPeriodSeconds(range);

  const queries: MetricDataQuery[] = [];

  for (const fn of FUNCTION_NAMES) {
    const id = sanitiseId(fn);
    const dimensions = [{ Name: 'FunctionName', Value: `${SERVICE_PREFIX}${fn}` }];

    queries.push({
      Id: `inv_${id}`,
      MetricStat: {
        Metric: { Namespace: 'AWS/Lambda', MetricName: 'Invocations', Dimensions: dimensions },
        Period: period,
        Stat: 'Sum',
      },
    });
    queries.push({
      Id: `err_${id}`,
      MetricStat: {
        Metric: { Namespace: 'AWS/Lambda', MetricName: 'Errors', Dimensions: dimensions },
        Period: period,
        Stat: 'Sum',
      },
    });
    queries.push({
      Id: `avg_${id}`,
      MetricStat: {
        Metric: { Namespace: 'AWS/Lambda', MetricName: 'Duration', Dimensions: dimensions },
        Period: period,
        Stat: 'Average',
      },
    });
    queries.push({
      Id: `p95_${id}`,
      MetricStat: {
        Metric: { Namespace: 'AWS/Lambda', MetricName: 'Duration', Dimensions: dimensions },
        Period: period,
        Stat: 'p95',
      },
    });
  }

  const command = new GetMetricDataCommand({
    MetricDataQueries: queries,
    StartTime: startTime,
    EndTime: endTime,
  });

  const response = await cwClient.send(command);
  const results = response.MetricDataResults ?? [];

  const resultMap = new Map<string, { timestamps: Date[]; values: number[] }>();
  for (const r of results) {
    if (r.Id) {
      resultMap.set(r.Id, {
        timestamps: (r.Timestamps ?? []) as Date[],
        values: (r.Values ?? []) as number[],
      });
    }
  }

  let totalInvocations = 0;
  let totalErrors = 0;

  const functions: FunctionMetrics[] = FUNCTION_NAMES.map((fn) => {
    const id = sanitiseId(fn);
    const inv = resultMap.get(`inv_${id}`);
    const err = resultMap.get(`err_${id}`);
    const avg = resultMap.get(`avg_${id}`);
    const p95 = resultMap.get(`p95_${id}`);

    const invocations = (inv?.values ?? []).reduce((a, b) => a + b, 0);
    const errors = (err?.values ?? []).reduce((a, b) => a + b, 0);
    const avgDuration = mean(avg?.values ?? []);
    const p95Duration = mean(p95?.values ?? []);

    totalInvocations += invocations;
    totalErrors += errors;

    // Build time series by merging invocation and error timestamps
    const tsMap = new Map<string, { invocations: number; errors: number }>();
    if (inv) {
      for (let i = 0; i < inv.timestamps.length; i++) {
        const ts = new Date(inv.timestamps[i]).toISOString();
        const entry = tsMap.get(ts) ?? { invocations: 0, errors: 0 };
        entry.invocations = inv.values[i] ?? 0;
        tsMap.set(ts, entry);
      }
    }
    if (err) {
      for (let i = 0; i < err.timestamps.length; i++) {
        const ts = new Date(err.timestamps[i]).toISOString();
        const entry = tsMap.get(ts) ?? { invocations: 0, errors: 0 };
        entry.errors = err.values[i] ?? 0;
        tsMap.set(ts, entry);
      }
    }

    const timeSeries = Array.from(tsMap.entries())
      .map(([timestamp, data]) => ({ timestamp, ...data }))
      .sort((a, b) => a.timestamp.localeCompare(b.timestamp));

    return { name: fn, invocations, errors, avgDuration, p95Duration, timeSeries };
  });

  return {
    functions,
    totals: {
      invocations: totalInvocations,
      errors: totalErrors,
      errorRate: totalInvocations > 0 ? (totalErrors / totalInvocations) * 100 : 0,
    },
  };
}

export async function getLogs(
  functionName: string,
  range: string,
  filterPattern?: string,
): Promise<LogEntry[]> {
  const now = Date.now();
  const startTime = now - getTimeRangeMs(range);
  const logGroupName = `/aws/lambda/${SERVICE_PREFIX}${functionName}`;

  try {
    const command = new FilterLogEventsCommand({
      logGroupName,
      startTime,
      endTime: now,
      filterPattern: filterPattern || undefined,
      limit: 100,
    });

    const response = await cwLogsClient.send(command);

    return (response.events ?? []).map((event) => ({
      timestamp: event.timestamp ?? 0,
      message: event.message ?? '',
      logStream: event.logStreamName ?? '',
    }));
  } catch (error: unknown) {
    if (
      error &&
      typeof error === 'object' &&
      'name' in error &&
      error.name === 'ResourceNotFoundException'
    ) {
      return [];
    }
    throw error;
  }
}

export async function traceLogs(
  functionName: string,
  email: string,
  timestamp: number,
  windowSeconds = 60,
): Promise<LogEntry[]> {
  const startTime = timestamp - windowSeconds * 1000;
  const endTime = timestamp + windowSeconds * 1000;
  const logGroupName = `/aws/lambda/${SERVICE_PREFIX}${functionName}`;

  try {
    const command = new FilterLogEventsCommand({
      logGroupName,
      startTime,
      endTime,
      filterPattern: `"${email}"`,
      limit: 50,
    });

    const response = await cwLogsClient.send(command);

    return (response.events ?? []).map((event) => ({
      timestamp: event.timestamp ?? 0,
      message: event.message ?? '',
      logStream: event.logStreamName ?? '',
    }));
  } catch (error: unknown) {
    if (
      error &&
      typeof error === 'object' &&
      'name' in error &&
      error.name === 'ResourceNotFoundException'
    ) {
      return [];
    }
    throw error;
  }
}

function mean(values: number[]): number {
  if (values.length === 0) return 0;
  return values.reduce((a, b) => a + b, 0) / values.length;
}
