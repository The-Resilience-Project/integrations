'use client';

import { useEffect, useState } from 'react';

interface CodeBlockProps {
  code: string;
  language?: string;
}

export function CodeBlock({ code, language }: CodeBlockProps) {
  const [html, setHtml] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function highlight() {
      try {
        const { codeToHtml } = await import('shiki');
        const highlighted = await codeToHtml(code, {
          lang: language || 'text',
          theme: 'github-dark-default',
        });
        if (!cancelled) setHtml(highlighted);
      } catch {
        // Fallback: render as plain text
        if (!cancelled) setHtml(null);
      }
    }

    highlight();
    return () => {
      cancelled = true;
    };
  }, [code, language]);

  if (html) {
    return (
      <div
        className="rounded-lg overflow-x-auto text-sm [&_pre]:!bg-secondary/50 [&_pre]:p-4 [&_pre]:rounded-lg"
        dangerouslySetInnerHTML={{ __html: html }}
      />
    );
  }

  return (
    <pre className="rounded-lg bg-secondary/50 p-4 overflow-x-auto text-sm">
      <code className="font-mono text-foreground/90">{code}</code>
    </pre>
  );
}
