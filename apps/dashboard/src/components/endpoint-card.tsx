'use client';

import { useState } from 'react';
import Link from 'next/link';
import { ChevronDown, ChevronRight, BookOpen } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { PostmanRequest } from '@/lib/postman-parser';

// Map endpoint paths to their documentation pages
const ENDPOINT_DOC_MAP: Record<string, string> = {
  'enquiry.php': '/docs/v1/enquiries',
  'confirm.php': '/docs/v1/confirmations',
  'confirm_existing_schools.php': '/docs/v1/confirmations',
  'register.php': '/docs/v1/registrations',
  'seminar_registration.php': '/docs/v1/registrations',
  'accept_dates.php': '/docs/v1/school-operations',
  'submit_ca.php': '/docs/v1/school-operations',
  'order_resources.php': '/docs/v1/school-operations',
  'order_resources_2026.php': '/docs/v1/school-operations',
  'qualify.php': '/docs/v1/workplace',
  'calendly_event.php': '/docs/v1/workplace',
  'calculate_shipping.php': '/docs/v1/shipping',
  'prize_pack.php': '/docs/v1/prize-pack',
  'school_confirmation_form_details.php': '/docs/v1/form-details',
  'ey_confirmation_form_details.php': '/docs/v1/form-details',
  'school_ltrp_details.php': '/docs/v1/form-details',
  'school_curric_ordering_details.php': '/docs/v1/form-details',
  'createInvoice': '/docs/v1/invoices',
  'createShipment': '/docs/v1/invoices',
  'sendInvitation': '/docs/v1/events',
  '/api/v2/schools/enquiry': '/docs/v2/schools',
  '/api/v2/schools/more-info': '/docs/v2/schools',
  '/api/v2/schools/register': '/docs/v2/schools',
  '/api/v2/schools/prize-pack': '/docs/v2/schools',
};

function getDocLink(url: string): string | null {
  // Check full path matches first
  for (const [pattern, doc] of Object.entries(ENDPOINT_DOC_MAP)) {
    if (url.includes(pattern)) return doc;
  }
  return null;
}

const METHOD_COLOURS: Record<string, string> = {
  GET: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/25',
  POST: 'bg-blue-500/15 text-blue-400 border-blue-500/25',
  PUT: 'bg-amber-500/15 text-amber-400 border-amber-500/25',
  PATCH: 'bg-orange-500/15 text-orange-400 border-orange-500/25',
  DELETE: 'bg-red-500/15 text-red-400 border-red-500/25',
};

export function EndpointCard({ request }: { request: PostmanRequest }) {
  const [expanded, setExpanded] = useState(false);
  const methodColour = METHOD_COLOURS[request.method] ?? METHOD_COLOURS.GET;
  const docLink = getDocLink(request.url);

  return (
    <div className="rounded-xl border border-border/50 bg-card overflow-hidden">
      <button
        onClick={() => setExpanded(!expanded)}
        className="flex w-full items-center gap-3 px-4 py-3 hover:bg-accent/10 transition-colors text-left"
      >
        <Badge
          variant="secondary"
          className={cn('text-[11px] font-mono font-semibold px-2 py-0.5 shrink-0', methodColour)}
        >
          {request.method}
        </Badge>
        <span className="font-mono text-sm text-foreground/90 truncate flex-1">
          {request.url}
        </span>
        <span className="text-xs text-muted-foreground shrink-0 hidden sm:block max-w-[200px] truncate">
          {request.name}
        </span>
        {request.body ? (
          expanded ? (
            <ChevronDown className="h-4 w-4 text-muted-foreground shrink-0" />
          ) : (
            <ChevronRight className="h-4 w-4 text-muted-foreground shrink-0" />
          )
        ) : null}
      </button>

      {expanded && (
        <div className="border-t border-border/30 px-4 py-3 space-y-3">
          {/* Request name + doc link */}
          <div className="flex items-center justify-between">
            <p className="text-xs text-muted-foreground">{request.name}</p>
            {docLink && (
              <Link
                href={docLink}
                className="flex items-center gap-1 text-[11px] text-[var(--cyan-glow)] hover:underline"
              >
                <BookOpen className="h-3 w-3" />
                View docs
              </Link>
            )}
          </div>

          {/* Headers */}
          {request.headers.length > 0 && (
            <div>
              <h4 className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground mb-1">
                Headers
              </h4>
              <div className="rounded-lg bg-secondary/30 p-2 space-y-0.5">
                {request.headers.map((h, i) => (
                  <div key={i} className="flex gap-2 text-xs font-mono">
                    <span className="text-muted-foreground">{h.key}:</span>
                    <span className="text-foreground/80">{h.value}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Body */}
          {request.body?.content && (
            <div>
              <h4 className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground mb-1">
                Body ({request.body.type})
              </h4>
              <pre className="rounded-lg bg-secondary/30 p-3 overflow-x-auto text-xs font-mono text-foreground/80 max-h-[400px] overflow-y-auto">
                {request.body.content}
              </pre>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
