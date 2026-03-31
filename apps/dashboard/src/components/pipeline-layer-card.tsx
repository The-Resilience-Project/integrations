'use client';

import Link from 'next/link';
import { ArrowRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export interface PipelineLayerItem {
  label: string;
  href?: string;
  badges?: { text: string; accent: string }[];
}

interface PipelineLayerCardProps {
  title: string;
  icon: LucideIcon;
  accent: string;
  items: PipelineLayerItem[];
}

export function PipelineLayerCard({
  title,
  icon: Icon,
  accent,
  items,
}: PipelineLayerCardProps) {
  if (items.length === 0) return null;

  return (
    <div className="rounded-xl bg-card ring-1 ring-border/60 overflow-hidden">
      <div className="h-0.5 w-full" style={{ backgroundColor: accent }} />
      <div className="px-4 pt-3 pb-3">
        <div className="flex items-center gap-2 mb-3">
          <Icon className="h-3.5 w-3.5" style={{ color: accent }} />
          <h3 className="text-[11px] font-medium uppercase tracking-widest text-muted-foreground/70">
            {title}
          </h3>
        </div>

        <div className="space-y-1.5">
          {items.map((item, i) => {
            const content = (
              <div className="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg hover:bg-secondary/50 transition-colors">
                <div className="flex items-center gap-2 min-w-0 flex-1">
                  <span className="text-[10px] font-mono text-muted-foreground/50 w-4 shrink-0">
                    {i + 1}
                  </span>
                  <span className="text-xs font-medium truncate">
                    {item.label}
                  </span>
                </div>
                <div className="flex items-center gap-1.5 shrink-0">
                  {item.badges?.map((badge, j) => (
                    <span
                      key={j}
                      className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                      style={{
                        backgroundColor: `color-mix(in oklch, ${badge.accent} 15%, transparent)`,
                        color: badge.accent,
                      }}
                    >
                      {badge.text}
                    </span>
                  ))}
                  {item.href && (
                    <ArrowRight className="h-3 w-3 text-muted-foreground/30" />
                  )}
                </div>
              </div>
            );

            if (item.href) {
              return (
                <Link key={i} href={item.href} className="block group">
                  {content}
                </Link>
              );
            }
            return <div key={i}>{content}</div>;
          })}
        </div>
      </div>
    </div>
  );
}
