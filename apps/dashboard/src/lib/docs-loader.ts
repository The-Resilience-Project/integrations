import fs from 'fs/promises';
import path from 'path';

const DOCS_ROOT = path.resolve(process.cwd(), '../../docs');

export interface DocTreeItem {
  slug: string;
  title: string;
  children?: DocTreeItem[];
}

export interface DocTreeGroup {
  label: string;
  slug: string;
  children: DocTreeItem[];
}

function titleFromFilename(filename: string): string {
  const name = filename.replace(/\.md$/, '');
  if (name === 'index') return 'Overview';
  if (name === 'DESIGN_SYSTEM_GUIDE') return 'Design System Guide';
  if (name.startsWith('form-')) return `Form ${name.replace('form-', '')}`;
  return name
    .split('-')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

async function extractTitle(filePath: string): Promise<string> {
  try {
    const content = await fs.readFile(filePath, 'utf-8');
    const match = content.match(/^#\s+(.+)$/m);
    return match ? match[1] : titleFromFilename(path.basename(filePath));
  } catch {
    return titleFromFilename(path.basename(filePath));
  }
}

async function buildTreeItems(
  dirPath: string,
  slugPrefix: string,
): Promise<DocTreeItem[]> {
  try {
    const entries = await fs.readdir(dirPath, { withFileTypes: true });

    const mdFiles = entries
      .filter((e) => e.isFile() && e.name.endsWith('.md'))
      .sort((a, b) => {
        if (a.name === 'index.md') return -1;
        if (b.name === 'index.md') return 1;
        return a.name.localeCompare(b.name, undefined, { numeric: true });
      });

    const subDirs = entries
      .filter((e) => e.isDirectory() && !e.name.startsWith('.'))
      .sort((a, b) => a.name.localeCompare(b.name));

    const items: DocTreeItem[] = [];

    // Add markdown files (skip index.md as it becomes the parent in sub-folders)
    for (const file of mdFiles) {
      const name = file.name.replace(/\.md$/, '');
      const title = await extractTitle(path.join(dirPath, file.name));
      items.push({
        slug: `${slugPrefix}/${name}`,
        title,
      });
    }

    // Add sub-directories that contain an index.md
    for (const dir of subDirs) {
      const subDirPath = path.join(dirPath, dir.name);
      const indexPath = path.join(subDirPath, 'index.md');
      try {
        await fs.access(indexPath);
      } catch {
        continue; // Skip directories without index.md
      }

      const title = await extractTitle(indexPath);
      const childItems = await buildTreeItems(subDirPath, `${slugPrefix}/${dir.name}`);
      // Remove index from children (it becomes the parent node)
      const children = childItems.filter(
        (c) => c.slug !== `${slugPrefix}/${dir.name}/index`,
      );

      items.push({
        slug: `${slugPrefix}/${dir.name}/index`,
        title,
        children: children.length > 0 ? children : undefined,
      });
    }

    return items;
  } catch {
    return [];
  }
}

export async function getDocsTree(): Promise<DocTreeGroup[]> {
  const groups: { label: string; dir: string; slug: string }[] = [
    { label: 'API v1', dir: 'v1', slug: 'v1' },
    { label: 'API v2', dir: 'v2', slug: 'v2' },
    { label: 'Business Flows', dir: 'flows', slug: 'flows' },
    { label: 'Vtiger CRM', dir: 'vtiger', slug: 'vtiger' },
    { label: 'Guides', dir: 'guides', slug: 'guides' },
  ];

  const tree: DocTreeGroup[] = [];

  for (const group of groups) {
    const dirPath = path.join(DOCS_ROOT, group.dir);
    const children = await buildTreeItems(dirPath, group.slug);

    if (children.length > 0) {
      tree.push({
        label: group.label,
        slug: group.slug,
        children,
      });
    }
  }

  return tree;
}

export async function getDocContent(
  slug: string,
): Promise<{ content: string; title: string } | null> {
  // Try direct .md file first, then fall back to directory with index.md
  let filePath = path.join(DOCS_ROOT, `${slug}.md`);
  try {
    await fs.access(filePath);
  } catch {
    filePath = path.join(DOCS_ROOT, slug, 'index.md');
  }

  try {
    const content = await fs.readFile(filePath, 'utf-8');
    const match = content.match(/^#\s+(.+)$/m);
    const title = match
      ? match[1]
      : titleFromFilename(path.basename(filePath));
    return { content, title };
  } catch {
    return null;
  }
}
