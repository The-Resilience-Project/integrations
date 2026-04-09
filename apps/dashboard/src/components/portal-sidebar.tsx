'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { PanelLeftClose, PanelLeft, X } from 'lucide-react';
import { NAV_CONFIG, isGroup } from '@/lib/nav-config';
import type { NavItem, NavEntry } from '@/lib/nav-config';
import { SearchBar } from '@/components/search-bar';
import { ThemeToggle } from '@/components/theme-toggle';
import { cn } from '@/lib/utils';

interface PortalSidebarProps {
  collapsed: boolean;
  onToggle: () => void;
  mobileOpen: boolean;
  onMobileClose: () => void;
}

export function PortalSidebar({
  collapsed,
  onToggle,
  mobileOpen,
  onMobileClose,
}: PortalSidebarProps) {
  const pathname = usePathname();
  const showLabels = !collapsed || mobileOpen;

  function renderNavItem(item: NavItem) {
    const isActive =
      pathname === item.href || pathname.startsWith(item.href + '/');

    return (
      <Link
        key={item.href}
        href={item.href}
        onClick={onMobileClose}
        className={cn(
          'flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium transition-colors',
          isActive
            ? 'bg-sidebar-accent text-sidebar-accent-foreground'
            : 'text-muted-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-foreground',
        )}
        title={!showLabels ? item.label : undefined}
      >
        <item.icon className="h-4 w-4 shrink-0" />
        {showLabels && <span>{item.label}</span>}
      </Link>
    );
  }

  function renderEntry(entry: NavEntry, index: number) {
    if (!isGroup(entry)) {
      return renderNavItem(entry);
    }

    return (
      <div key={entry.label} className={index > 0 ? 'pt-2' : ''}>
        {showLabels && (
          <p className="px-2.5 pb-1 text-[10px] font-medium uppercase tracking-wider text-muted-foreground/50">
            {entry.label}
          </p>
        )}
        {!showLabels && index > 0 && (
          <div className="mx-2 mb-1 border-t border-sidebar-border" />
        )}
        <div className="space-y-0.5">
          {entry.items.map((item) => renderNavItem(item))}
        </div>
      </div>
    );
  }

  return (
    <aside
      className={cn(
        'fixed inset-y-0 left-0 z-40 flex flex-col border-r border-sidebar-border bg-sidebar transition-all duration-200',
        collapsed ? 'md:w-[52px]' : 'md:w-[220px]',
        mobileOpen ? 'w-[260px] translate-x-0' : '-translate-x-full md:translate-x-0',
      )}
    >
      {/* Header */}
      <div className="flex h-14 items-center gap-2.5 border-b border-sidebar-border px-3">
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[var(--cyan-glow)] to-[var(--teal-accent)]">
          <svg
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            className="text-[var(--primary-foreground)]"
          >
            <path
              d="M2 4h12M2 8h12M2 12h8"
              stroke="currentColor"
              strokeWidth="1.5"
              strokeLinecap="round"
            />
          </svg>
        </div>
        {showLabels && (
          <div className="flex flex-col flex-1">
            <span className="text-sm font-semibold tracking-tight text-sidebar-foreground">
              TRP Dev Portal
            </span>
            <span className="text-[10px] font-mono text-muted-foreground">
              dev
            </span>
          </div>
        )}
        {mobileOpen && (
          <button
            onClick={onMobileClose}
            className="md:hidden p-1.5 rounded-md text-muted-foreground hover:text-foreground hover:bg-sidebar-accent/50"
            aria-label="Close menu"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>

      {/* Search */}
      <div className="px-2 pt-3 pb-1">
        <SearchBar collapsed={collapsed && !mobileOpen} />
      </div>

      {/* Navigation */}
      <nav className="flex-1 space-y-1 px-2 py-2">
        {NAV_CONFIG.map((entry, i) => renderEntry(entry, i))}
      </nav>

      {/* Footer */}
      <div className="border-t border-sidebar-border p-2 space-y-1">
        <ThemeToggle collapsed={collapsed && !mobileOpen} />
        <button
          onClick={onToggle}
          className="hidden md:flex w-full items-center justify-center rounded-md p-2 text-muted-foreground transition-colors hover:bg-sidebar-accent/50 hover:text-sidebar-foreground"
          title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          {collapsed ? (
            <PanelLeft className="h-4 w-4" />
          ) : (
            <PanelLeftClose className="h-4 w-4" />
          )}
        </button>
      </div>
    </aside>
  );
}
