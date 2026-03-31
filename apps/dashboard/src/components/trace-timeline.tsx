'use client';

import { Badge } from '@/components/ui/badge';
import type { LogEntry } from '@/lib/types';
import type { GFEntry } from '@/lib/gravity-forms';

interface TraceTimelineProps {
  entry: GFEntry;
  email: string;
  formTitle: string;
  functionName: string;
  logs: LogEntry[];
  isLoading: boolean;
}

function getLogLevel(message: string): 'info' | 'warning' | 'error' | 'system' {
  if (/\[ERROR\]|\[EXCEPTION\]/i.test(message)) return 'error';
  if (/\[WARNING\]/i.test(message)) return 'warning';
  if (/\[INFO\]/i.test(message)) return 'info';
  return 'system';
}

const LEVEL_STYLES = {
  info: 'border-emerald-500/30 bg-emerald-500/5',
  warning: 'border-[var(--amber-accent)]/30 bg-[var(--amber-accent)]/5',
  error: 'border-[var(--rose-accent)]/30 bg-[var(--rose-accent)]/5',
  system: 'border-border/30 bg-secondary/30',
};

const DOT_STYLES = {
  info: 'bg-emerald-400',
  warning: 'bg-[var(--amber-accent)]',
  error: 'bg-[var(--rose-accent)]',
  system: 'bg-muted-foreground',
};

function formatTime(ts: number | string): string {
  // GF date_created is UTC without timezone indicator — force UTC parsing for strings
  const raw = typeof ts === 'string' && !String(ts).includes('T') && !String(ts).includes('Z')
    ? ts.replace(' ', 'T') + 'Z'
    : ts;
  const date = new Date(raw);
  return date.toLocaleTimeString('en-AU', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

function cleanLogMessage(message: string): string {
  // Strip Lambda preamble (timestamp, request ID, log level prefix)
  return message
    .replace(/^\d{4}-\d{2}-\d{2}T[\d:.Z]+\s+[\w-]+\s+(INFO|WARNING|ERROR|DEBUG)\s+/, '')
    .replace(/^\[[\w]+\]\s*/, '')
    .trim();
}

// Vtiger record ID format: {modulePrefix}x{numericId}
const VT_BASE = 'https://theresilienceproject.od2.vtiger.com/view/detail';
const VT_MODULE_MAP: Record<string, string> = {
  '2': 'Groups',
  '3': 'Accounts',
  '4': 'Contacts',
  '5': 'Potentials',
  '6': 'Products',
  '7': 'Quotes',
  '8': 'SalesOrder',
  '14': 'Invoice',
  '19': 'Users',
  '20': 'Groups',
};

function vtUrl(prefix: string, id: string): string | null {
  const module = VT_MODULE_MAP[prefix];
  if (!module) return null;
  return `${VT_BASE}?module=${module}&id=${id}`;
}

// Regex to find Vtiger IDs like "3x802914", "4x123456"
const VT_ID_REGEX = /\b(\d{1,2})x(\d{4,})\b/g;

function LogMessageWithLinks({ message }: { message: string }) {
  const parts: (string | { text: string; href: string; module: string })[] = [];
  let lastIndex = 0;

  for (const match of message.matchAll(VT_ID_REGEX)) {
    const [full, prefix, id] = match;
    const href = vtUrl(prefix, id);
    if (match.index! > lastIndex) {
      parts.push(message.slice(lastIndex, match.index));
    }
    if (href) {
      parts.push({
        text: full,
        href,
        module: VT_MODULE_MAP[prefix] ?? 'Record',
      });
    } else {
      parts.push(full);
    }
    lastIndex = match.index! + full.length;
  }
  if (lastIndex < message.length) {
    parts.push(message.slice(lastIndex));
  }

  if (parts.length <= 1 && typeof parts[0] === 'string') {
    return <>{message}</>;
  }

  return (
    <>
      {parts.map((part, i) =>
        typeof part === 'string' ? (
          <span key={i}>{part}</span>
        ) : (
          <a
            key={i}
            href={part.href}
            target="_blank"
            rel="noopener noreferrer"
            className="text-[var(--cyan-glow)] hover:underline underline-offset-2"
            title={`Open ${part.module} in Vtiger`}
          >
            {part.text}
          </a>
        ),
      )}
    </>
  );
}

export function TraceTimeline({
  entry,
  email,
  formTitle,
  functionName,
  logs,
  isLoading,
}: TraceTimelineProps) {
  return (
    <div className="rounded-xl border border-border/50 bg-card p-5">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-sm font-semibold">
          Trace: {email}
        </h3>
        <Badge variant="secondary" className="text-[10px] font-mono">
          Entry #{entry.id}
        </Badge>
      </div>

      <div className="relative pl-6">
        {/* Vertical line */}
        <div className="absolute left-[9px] top-2 bottom-2 w-px bg-border/50" />

        {/* GF Entry node */}
        <TimelineNode
          dotColour="bg-[var(--cyan-glow)]"
          time={formatTime(entry.date_created)}
          title="Form Submitted"
          borderClass="border-[var(--cyan-glow)]/30 bg-[var(--cyan-glow)]/5"
        >
          <div className="space-y-1 text-xs">
            <p><span className="text-muted-foreground">Form:</span> {formTitle}</p>
            <p><span className="text-muted-foreground">Email:</span> {email}</p>
            <p><span className="text-muted-foreground">Status:</span> {entry.status}</p>
          </div>
        </TimelineNode>

        {/* Loading */}
        {isLoading && (
          <div className="relative mb-3 ml-3">
            <div className="skeleton h-16 w-full rounded-lg" />
          </div>
        )}

        {/* No logs found */}
        {!isLoading && logs.length === 0 && (
          <TimelineNode
            dotColour="bg-muted-foreground"
            time="—"
            title="No matching logs found"
            borderClass="border-border/30 bg-secondary/20"
          >
            <p className="text-xs text-muted-foreground">
              No CloudWatch logs matched &ldquo;{email}&rdquo; in {functionName} within the time window.
            </p>
          </TimelineNode>
        )}

        {/* Log entries */}
        {logs.map((log, i) => {
          const level = getLogLevel(log.message);
          const cleaned = cleanLogMessage(log.message);

          return (
            <TimelineNode
              key={`${log.timestamp}-${i}`}
              dotColour={DOT_STYLES[level]}
              time={formatTime(log.timestamp)}
              title={`Lambda: ${functionName}`}
              borderClass={LEVEL_STYLES[level]}
            >
              <pre className="text-[11px] font-mono text-foreground/80 whitespace-pre-wrap break-all max-h-[200px] overflow-y-auto">
                <LogMessageWithLinks message={cleaned} />
              </pre>
            </TimelineNode>
          );
        })}
      </div>
    </div>
  );
}

function TimelineNode({
  dotColour,
  time,
  title,
  borderClass,
  children,
}: {
  dotColour: string;
  time: string;
  title: string;
  borderClass: string;
  children: React.ReactNode;
}) {
  return (
    <div className="relative mb-3">
      {/* Dot */}
      <div
        className={`absolute -left-6 top-3 h-[10px] w-[10px] rounded-full border-2 border-card ${dotColour}`}
      />

      <div className={`rounded-lg border p-3 ${borderClass}`}>
        <div className="flex items-center justify-between mb-1.5">
          <span className="text-xs font-medium">{title}</span>
          <span className="text-[10px] font-mono text-muted-foreground">{time}</span>
        </div>
        {children}
      </div>
    </div>
  );
}
