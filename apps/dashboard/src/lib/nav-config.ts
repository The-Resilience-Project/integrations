import {
  Activity,
  BookOpen,
  Code2,
  FileText,
  GraduationCap,
  Heart,
  Layers,
  Building2,
  Share2,
  Baby,
  type LucideIcon,
} from 'lucide-react';

export interface NavItem {
  label: string;
  href: string;
  icon: LucideIcon;
}

export interface NavGroup {
  label: string;
  items: NavItem[];
}

export type NavEntry = NavItem | NavGroup;

function isGroup(entry: NavEntry): entry is NavGroup {
  return 'items' in entry;
}

export { isGroup };

export const NAV_CONFIG: NavEntry[] = [
  { label: 'Health', href: '/health', icon: Activity },

  // Journeys
  {
    label: 'Journeys',
    items: [
      { label: 'Schools', href: '/schools', icon: GraduationCap },
      { label: 'Early Years', href: '/early-years', icon: Baby },
      { label: 'Workplaces', href: '/workplaces', icon: Building2 },
      { label: 'Shared', href: '/shared', icon: Share2 },
    ],
  },

  // Reference
  {
    label: 'Reference',
    items: [
      { label: 'Pipeline', href: '/pipeline', icon: Layers },
      { label: 'Forms', href: '/forms', icon: FileText },
      { label: 'Docs', href: '/docs', icon: BookOpen },
      { label: 'API Reference', href: '/api-reference', icon: Code2 },
    ],
  },
];

// Legacy flat export for backwards compatibility
export type NavSection = NavItem;
export const NAV_SECTIONS: NavSection[] = NAV_CONFIG.flatMap((entry) =>
  isGroup(entry) ? entry.items : [entry],
);
