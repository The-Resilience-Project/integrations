'use client';

import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeRaw from 'rehype-raw';
import type { Components } from 'react-markdown';
import { MermaidDiagram } from './mermaid-diagram';
import { CodeBlock } from './code-block';

interface MarkdownRendererProps {
  content: string;
  currentSlug?: string;
}

function buildComponents(currentSlug?: string): Components {
  return {
  h1: ({ children }) => (
    <h1 className="text-2xl font-bold tracking-tight mb-4 mt-8 first:mt-0 text-foreground">
      {children}
    </h1>
  ),
  h2: ({ children }) => (
    <h2 className="text-xl font-semibold tracking-tight mb-3 mt-8 text-foreground border-b border-border/30 pb-2">
      {children}
    </h2>
  ),
  h3: ({ children }) => (
    <h3 className="text-lg font-semibold mb-2 mt-6 text-foreground">
      {children}
    </h3>
  ),
  h4: ({ children }) => (
    <h4 className="text-base font-medium mb-2 mt-4 text-foreground">
      {children}
    </h4>
  ),
  p: ({ children }) => (
    <p className="text-sm leading-relaxed mb-4 text-foreground/85">
      {children}
    </p>
  ),
  a: ({ href, children }) => {
    // Rewrite relative .md links to portal routes
    let resolvedHref = href ?? '';
    if (resolvedHref.endsWith('.md') && !resolvedHref.startsWith('http')) {
      const clean = resolvedHref.replace(/\.md$/, '');
      if (currentSlug && (clean.startsWith('./') || (!clean.startsWith('../') && !clean.includes('/')))) {
        // Resolve relative to current document's directory
        const currentDir = currentSlug.substring(0, currentSlug.lastIndexOf('/'));
        const relative = clean.replace(/^\.\//, '');
        resolvedHref = `/docs/${currentDir}/${relative}`;
      } else {
        // Absolute-ish paths: strip ../ and ./ prefixes
        const stripped = clean.replace(/^\.\.\//, '').replace(/^\.\//, '');
        resolvedHref = `/docs/${stripped}`;
      }
    }

    const isExternal = resolvedHref.startsWith('http');
    return (
      <a
        href={resolvedHref}
        className="text-[var(--cyan-glow)] hover:underline underline-offset-2"
        target={isExternal ? '_blank' : undefined}
        rel={isExternal ? 'noopener noreferrer' : undefined}
      >
        {children}
      </a>
    );
  },
  ul: ({ children }) => (
    <ul className="list-disc list-inside space-y-1 mb-4 text-sm text-foreground/85 ml-2">
      {children}
    </ul>
  ),
  ol: ({ children }) => (
    <ol className="list-decimal list-inside space-y-1 mb-4 text-sm text-foreground/85 ml-2">
      {children}
    </ol>
  ),
  li: ({ children }) => <li className="leading-relaxed">{children}</li>,
  blockquote: ({ children }) => (
    <blockquote className="border-l-2 border-[var(--cyan-glow)]/40 pl-4 my-4 text-sm text-muted-foreground italic">
      {children}
    </blockquote>
  ),
  table: ({ children }) => (
    <div className="my-4 overflow-x-auto rounded-lg border border-border/50">
      <table className="w-full text-sm">{children}</table>
    </div>
  ),
  thead: ({ children }) => (
    <thead className="bg-secondary/50 border-b border-border/50">
      {children}
    </thead>
  ),
  th: ({ children }) => (
    <th className="text-left px-3 py-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
      {children}
    </th>
  ),
  td: ({ children }) => (
    <td className="px-3 py-2 text-sm border-b border-border/20">
      {children}
    </td>
  ),
  tr: ({ children }) => (
    <tr className="hover:bg-accent/10">{children}</tr>
  ),
  hr: () => <hr className="my-6 border-border/30" />,
  strong: ({ children }) => (
    <strong className="font-semibold text-foreground">{children}</strong>
  ),
  em: ({ children }) => <em className="italic">{children}</em>,
  code: ({ className, children }) => {
    const match = className?.match(/language-(\w+)/);
    const lang = match ? match[1] : undefined;
    const codeStr = String(children).replace(/\n$/, '');

    // Inline code (no language class, single line)
    if (!className) {
      return (
        <code className="rounded bg-secondary px-1.5 py-0.5 text-xs font-mono text-[var(--cyan-glow)]">
          {children}
        </code>
      );
    }

    // Mermaid diagram
    if (lang === 'mermaid') {
      return <MermaidDiagram chart={codeStr} />;
    }

    // Code block with syntax highlighting
    return <CodeBlock code={codeStr} language={lang} />;
  },
  pre: ({ children }) => {
    // The pre tag wraps code blocks — pass through to let code component handle rendering
    return <>{children}</>;
  },
};
}

export function MarkdownRenderer({ content, currentSlug }: MarkdownRendererProps) {
  return (
    <div className="prose-dark max-w-none">
      <ReactMarkdown
        remarkPlugins={[remarkGfm]}
        rehypePlugins={[rehypeRaw]}
        components={buildComponents(currentSlug)}
      >
        {content}
      </ReactMarkdown>
    </div>
  );
}
