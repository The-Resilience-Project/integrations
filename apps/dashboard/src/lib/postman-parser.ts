import fs from 'fs/promises';
import path from 'path';
import { parse as parseYaml } from 'yaml';

const POSTMAN_ROOT = path.resolve(process.cwd(), '../../postman');

export interface PostmanHeader {
  key: string;
  value: string;
}

export interface PostmanBody {
  type: string;
  content: string;
}

export interface PostmanRequest {
  name: string;
  url: string;
  method: string;
  headers: PostmanHeader[];
  body?: PostmanBody;
  order?: number;
}

export interface PostmanCollection {
  name: string;
  slug: string;
  version: string;
  requests: PostmanRequest[];
}

async function readYamlFile<T>(filePath: string): Promise<T> {
  const content = await fs.readFile(filePath, 'utf-8');
  return parseYaml(content) as T;
}

async function listDirectories(dir: string): Promise<string[]> {
  try {
    const entries = await fs.readdir(dir, { withFileTypes: true });
    return entries
      .filter((e) => e.isDirectory() && !e.name.startsWith('.'))
      .map((e) => e.name)
      .sort();
  } catch {
    return [];
  }
}

async function listRequestFiles(dir: string): Promise<string[]> {
  try {
    const entries = await fs.readdir(dir);
    return entries
      .filter((e) => e.endsWith('.request.yaml'))
      .sort();
  } catch {
    return [];
  }
}

function slugify(name: string): string {
  return name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');
}

async function parseCollection(
  collectionDir: string,
  name: string,
  version: string,
): Promise<PostmanCollection> {
  const files = await listRequestFiles(collectionDir);
  const requests: PostmanRequest[] = [];

  for (const file of files) {
    try {
      const filePath = path.join(collectionDir, file);
      const data = await readYamlFile<Record<string, unknown>>(filePath);

      if (data.$kind !== 'http-request') continue;

      requests.push({
        name: (data.name as string) || file.replace('.request.yaml', ''),
        url: (data.url as string) || '',
        method: (data.method as string) || 'GET',
        headers: (data.headers as PostmanHeader[]) || [],
        body: data.body as PostmanBody | undefined,
        order: data.order as number | undefined,
      });
    } catch {
      // Skip malformed files
    }
  }

  // Sort by order field if present, then by name
  requests.sort((a, b) => {
    if (a.order != null && b.order != null) return a.order - b.order;
    if (a.order != null) return -1;
    if (b.order != null) return 1;
    return a.name.localeCompare(b.name);
  });

  return {
    name,
    slug: slugify(name),
    version,
    requests,
  };
}

export async function getPostmanCollections(): Promise<PostmanCollection[]> {
  const collections: PostmanCollection[] = [];
  const collectionsDir = path.join(POSTMAN_ROOT, 'collections');

  // Parse v1 collections
  const v1Dir = path.join(collectionsDir, 'v1');
  const v1Dirs = await listDirectories(v1Dir);
  for (const dir of v1Dirs) {
    const collection = await parseCollection(
      path.join(v1Dir, dir),
      dir,
      'v1',
    );
    if (collection.requests.length > 0) {
      collections.push(collection);
    }
  }

  // Parse v2 collections
  const v2Dir = path.join(collectionsDir, 'v2');
  const v2Dirs = await listDirectories(v2Dir);
  for (const dir of v2Dirs) {
    const collection = await parseCollection(
      path.join(v2Dir, dir),
      dir,
      'v2',
    );
    if (collection.requests.length > 0) {
      collections.push(collection);
    }
  }

  return collections;
}

export async function getPostmanEnvironments(): Promise<
  Record<string, string>[]
> {
  const envDir = path.join(POSTMAN_ROOT, 'environments');
  try {
    const files = await fs.readdir(envDir);
    const envs: Record<string, string>[] = [];

    for (const file of files.filter((f) => f.endsWith('.yaml'))) {
      const data = await readYamlFile<{
        name: string;
        values: { key: string; value: string }[];
      }>(path.join(envDir, file));

      const env: Record<string, string> = { _name: data.name };
      for (const v of data.values || []) {
        env[v.key] = v.value;
      }
      envs.push(env);
    }

    return envs;
  } catch {
    return [];
  }
}
