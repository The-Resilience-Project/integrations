import {
  Activity,
  BookOpen,
  Code2,
  FileText,
  Layers,
  Workflow,
  type LucideIcon,
} from 'lucide-react';

export interface NavSection {
  label: string;
  href: string;
  icon: LucideIcon;
}

export const NAV_SECTIONS: NavSection[] = [
  { label: 'Monitor', href: '/monitor', icon: Activity },
  { label: 'Docs', href: '/docs', icon: BookOpen },
  { label: 'API Reference', href: '/api-reference', icon: Code2 },
  { label: 'Pipeline', href: '/pipeline', icon: Layers },
  { label: 'Forms', href: '/forms', icon: FileText },
  { label: 'Workflows', href: '/workflows', icon: Workflow },
];
