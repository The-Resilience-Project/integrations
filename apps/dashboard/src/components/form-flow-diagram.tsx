'use client';

import type { FormEndpoint } from '@/lib/types';
import { MermaidDiagram } from './mermaid-diagram';

interface FormFlowDiagramProps {
  formId: number;
  formTitle: string;
  endpoints: FormEndpoint[];
}

function extractLambdaName(endpoint: string): string {
  // Extract the PHP filename or path segment as the Lambda function name
  const parts = endpoint.split('/');
  const last = parts[parts.length - 1] || endpoint;
  return last.replace('.php', '');
}

export function FormFlowDiagram({
  formId,
  formTitle,
  endpoints,
}: FormFlowDiagramProps) {
  const inbound = endpoints.find((e) => e.direction === 'inbound');
  const outbound = endpoints.find((e) => e.direction === 'outbound');

  if (!inbound && !outbound) {
    return null;
  }

  // Build Mermaid flowchart
  const lines: string[] = ['flowchart LR'];

  // Form node
  const shortTitle =
    formTitle.length > 30 ? formTitle.slice(0, 27) + '...' : formTitle;
  lines.push(`  GF["🔲 Form ${formId}\\n${shortTitle}"]`);

  if (outbound) {
    const lambdaName = extractLambdaName(outbound.endpoint);
    lines.push(`  WH["⚡ GF Webhooks"]`);
    lines.push(`  Lambda["λ ${lambdaName}"]`);
    lines.push(`  CRM["📋 Vtiger CRM"]`);
    lines.push(`  GF --> WH`);
    lines.push(`  WH -->|"${outbound.method}"| Lambda`);
    lines.push(`  Lambda --> CRM`);
  }

  if (inbound) {
    const lambdaName = extractLambdaName(inbound.endpoint);
    if (!outbound) {
      // If no outbound, still need Lambda and CRM nodes
      lines.push(`  Lambda["λ ${lambdaName}"]`);
      lines.push(`  CRM["📋 Vtiger CRM"]`);
    } else {
      // Add separate inbound Lambda node if it's a different endpoint
      const outboundLambda = extractLambdaName(outbound.endpoint);
      if (lambdaName !== outboundLambda) {
        lines.push(`  InLambda["λ ${lambdaName}"]`);
        lines.push(`  CRM -->|data| InLambda`);
        lines.push(`  InLambda -->|"${inbound.method}"| GF`);
      } else {
        lines.push(`  CRM -->|data| Lambda`);
        lines.push(`  Lambda -->|"${inbound.method}"| GF`);
      }
    }

    if (!outbound) {
      lines.push(`  CRM -->|data| Lambda`);
      lines.push(`  Lambda -->|"${inbound.method}"| GF`);
    }
  }

  const chart = lines.join('\n');

  return (
    <div>
      <h3 className="text-xs font-medium uppercase tracking-wider text-muted-foreground mb-2">
        Data Flow
      </h3>
      <MermaidDiagram chart={chart} />
    </div>
  );
}
