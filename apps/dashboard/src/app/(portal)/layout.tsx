'use client';

import { useState } from 'react';
import { Menu } from 'lucide-react';
import { PortalSidebar } from '@/components/portal-sidebar';

export default function PortalLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);

  return (
    <div className="flex min-h-screen">
      {/* Mobile menu button */}
      <button
        onClick={() => setMobileOpen(true)}
        className="fixed top-3 left-3 z-40 flex h-9 w-9 items-center justify-center rounded-lg bg-card border border-border/50 text-muted-foreground hover:text-foreground md:hidden"
        aria-label="Open menu"
      >
        <Menu className="h-4 w-4" />
      </button>

      {/* Mobile overlay */}
      {mobileOpen && (
        <div
          className="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm md:hidden"
          onClick={() => setMobileOpen(false)}
        />
      )}

      <PortalSidebar
        collapsed={collapsed}
        onToggle={() => setCollapsed(!collapsed)}
        mobileOpen={mobileOpen}
        onMobileClose={() => setMobileOpen(false)}
      />
      <main
        className="flex-1 transition-[padding] duration-200 pt-14 md:pt-0"
        style={{ paddingLeft: collapsed ? 52 : 220 }}
      >
        {children}
      </main>

      {/* Override padding on mobile — sidebar is an overlay, not pushing content */}
      <style jsx>{`
        @media (max-width: 767px) {
          main {
            padding-left: 0 !important;
          }
        }
      `}</style>
    </div>
  );
}
