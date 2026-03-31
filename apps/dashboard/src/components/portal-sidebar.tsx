'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { PanelLeftClose, PanelLeft, X } from 'lucide-react';
import { NAV_SECTIONS } from '@/lib/nav-config';
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

  return (
    <aside
      className={cn(
        'fixed inset-y-0 left-0 z-40 flex flex-col border-r border-sidebar-border bg-sidebar transition-all duration-200',
        // Desktop: respect collapsed state
        collapsed ? 'md:w-[52px]' : 'md:w-[220px]',
        // Mobile: always full width when open, hidden when closed
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
        {(!collapsed || mobileOpen) && (
          <div className="flex flex-col flex-1">
            <span className="text-sm font-semibold tracking-tight text-sidebar-foreground">
              TRP Dev Portal
            </span>
            <span className="text-[10px] font-mono text-muted-foreground">
              dev
            </span>
          </div>
        )}
        {/* Mobile close button */}
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
        {NAV_SECTIONS.map((section) => {
          const isActive =
            pathname === section.href ||
            pathname.startsWith(section.href + '/');

          return (
            <Link
              key={section.href}
              href={section.href}
              onClick={onMobileClose}
              className={cn(
                'flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium transition-colors',
                isActive
                  ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                  : 'text-muted-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-foreground',
              )}
              title={collapsed && !mobileOpen ? section.label : undefined}
            >
              <section.icon className="h-4 w-4 shrink-0" />
              {(!collapsed || mobileOpen) && <span>{section.label}</span>}
            </Link>
          );
        })}
      </nav>

      {/* Footer — theme toggle + collapse */}
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
