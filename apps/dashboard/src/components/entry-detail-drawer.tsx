'use client';

import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription,
} from '@/components/ui/sheet';
import { useGFEntry } from '@/hooks/use-gf-entry';
import type { FormField } from '@/lib/types';

interface EntryDetailDrawerProps {
  entryId: string | null;
  formFields: FormField[];
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function EntryDetailDrawer({
  entryId,
  formFields,
  open,
  onOpenChange,
}: EntryDetailDrawerProps) {
  const { data, isLoading } = useGFEntry(open ? entryId : null);
  const [showRaw, setShowRaw] = useState(false);

  const entry = data?.entry;

  // Build field values from entry, matching field IDs to labels
  const fieldValues = entry
    ? formFields
        .map((field) => {
          // Check main field ID
          const mainValue = entry[String(field.id)];

          // Check sub-field IDs (e.g. 3.1, 3.2 for name fields)
          const subValues: { key: string; value: unknown }[] = [];
          for (const key of Object.keys(entry)) {
            if (key.startsWith(`${field.id}.`)) {
              subValues.push({ key, value: entry[key] });
            }
          }

          if (subValues.length > 0) {
            const combined = subValues
              .filter((sv) => sv.value != null && String(sv.value).trim() !== '')
              .map((sv) => String(sv.value))
              .join(' ');
            return combined ? { label: field.label, value: combined, fieldId: field.id } : null;
          }

          if (mainValue != null && String(mainValue).trim() !== '') {
            return { label: field.label, value: String(mainValue), fieldId: field.id };
          }

          return null;
        })
        .filter(Boolean) as { label: string; value: string; fieldId: number }[]
    : [];

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent side="right" className="sm:max-w-md overflow-y-auto">
        <SheetHeader>
          <SheetTitle className="font-mono text-[var(--cyan-glow)]">
            Entry #{entryId}
          </SheetTitle>
          {entry && (
            <SheetDescription className="flex items-center gap-2">
              <span>
                {new Date(entry.date_created).toLocaleDateString('en-AU', {
                  day: '2-digit',
                  month: 'short',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </span>
              <Badge variant="secondary" className="text-[10px]">
                {entry.status}
              </Badge>
            </SheetDescription>
          )}
        </SheetHeader>

        <div className="px-4 pb-4 space-y-4">
          {isLoading && (
            <div className="space-y-3">
              {[1, 2, 3, 4, 5].map((i) => (
                <div key={i} className="space-y-1">
                  <div className="skeleton h-3 w-24" />
                  <div className="skeleton h-5 w-full" />
                </div>
              ))}
            </div>
          )}

          {!isLoading && entry && (
            <>
              {/* Field values */}
              {fieldValues.length > 0 ? (
                <dl className="space-y-3">
                  {fieldValues.map((fv) => (
                    <div key={fv.fieldId} className="border-b border-border/20 pb-2">
                      <dt className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">
                        {fv.label}
                      </dt>
                      <dd className="text-sm break-words">{fv.value}</dd>
                    </div>
                  ))}
                </dl>
              ) : (
                <p className="text-xs text-muted-foreground">No field data available.</p>
              )}

              {/* Raw data toggle */}
              <div className="pt-2">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setShowRaw(!showRaw)}
                  className="text-xs text-muted-foreground"
                >
                  {showRaw ? 'Hide' : 'Show'} raw data
                </Button>
                {showRaw && (
                  <pre className="mt-2 rounded-lg border border-border/30 bg-secondary/30 p-3 text-[11px] font-mono overflow-x-auto max-h-[400px] overflow-y-auto">
                    {JSON.stringify(entry, null, 2)}
                  </pre>
                )}
              </div>
            </>
          )}

          {!isLoading && !entry && (
            <p className="text-xs text-muted-foreground">Entry not found.</p>
          )}
        </div>
      </SheetContent>
    </Sheet>
  );
}
