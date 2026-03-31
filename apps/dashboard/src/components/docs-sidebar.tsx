'use client';

import { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useDocsTree } from '@/hooks/use-docs-tree';
import { cn } from '@/lib/utils';
import type { DocTreeItem } from '@/lib/docs-loader';

export function DocsSidebar() {
  const { data, isLoading } = useDocsTree();
  const pathname = usePathname();

  if (isLoading) {
    return (
      <div className="space-y-2 px-1">
        {[1, 2, 3].map((i) => (
          <div key={i} className="skeleton h-6 w-full rounded" />
        ))}
      </div>
    );
  }

  const tree = data?.tree ?? [];

  return (
    <nav className="space-y-1">
      {tree.map((group) => (
        <DocGroup key={group.slug} group={group} pathname={pathname} />
      ))}
    </nav>
  );
}

function DocGroup({
  group,
  pathname,
}: {
  group: { label: string; slug: string; children: DocTreeItem[] };
  pathname: string;
}) {
  const isGroupActive = pathname.startsWith(`/docs/${group.slug}`);
  const [open, setOpen] = useState(isGroupActive);

  return (
    <div>
      <button
        onClick={() => setOpen(!open)}
        className="flex w-full items-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-medium uppercase tracking-wider text-muted-foreground hover:text-foreground transition-colors"
      >
        {open ? (
          <ChevronDown className="h-3 w-3" />
        ) : (
          <ChevronRight className="h-3 w-3" />
        )}
        {group.label}
        <span className="ml-auto text-[10px] font-mono">
          {group.children.length}
        </span>
      </button>

      {open && (
        <div className="ml-3 space-y-0.5 border-l border-border/30 pl-2 mt-0.5">
          {group.children.map((item) => (
            <DocItem key={item.slug} item={item} pathname={pathname} depth={0} />
          ))}
        </div>
      )}
    </div>
  );
}

function DocItem({
  item,
  pathname,
  depth,
}: {
  item: DocTreeItem;
  pathname: string;
  depth: number;
}) {
  const href = `/docs/${item.slug}`;
  const isActive = pathname === href;
  const hasChildren = item.children && item.children.length > 0;
  const isChildActive = hasChildren && pathname.startsWith(`/docs/${item.slug.replace(/\/index$/, '')}/`);
  const [open, setOpen] = useState(isActive || isChildActive);

  if (!hasChildren) {
    return (
      <Link
        href={href}
        className={cn(
          'block rounded-md px-2 py-1.5 text-sm transition-colors',
          isActive
            ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium'
            : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent/30',
        )}
      >
        {item.title}
      </Link>
    );
  }

  return (
    <div>
      <div className="flex items-center gap-0.5">
        <button
          onClick={() => setOpen(!open)}
          className="shrink-0 p-0.5 rounded hover:bg-sidebar-accent/30 transition-colors"
        >
          {open ? (
            <ChevronDown className="h-3 w-3 text-muted-foreground" />
          ) : (
            <ChevronRight className="h-3 w-3 text-muted-foreground" />
          )}
        </button>
        <Link
          href={href}
          className={cn(
            'flex-1 rounded-md px-1.5 py-1.5 text-sm transition-colors',
            isActive
              ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium'
              : isChildActive
                ? 'text-foreground font-medium'
                : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-accent/30',
          )}
        >
          {item.title}
        </Link>
      </div>

      {open && (
        <div className="ml-3 space-y-0.5 border-l border-border/30 pl-2 mt-0.5">
          {item.children!.map((child) => (
            <DocItem key={child.slug} item={child} pathname={pathname} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}
