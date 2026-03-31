import { useQuery } from '@tanstack/react-query';

export interface WorkflowCondition {
  fieldname: string;
  operation: string;
  value: string | null;
  valuetype: string;
  joincondition: string;
  groupjoin: string;
  groupid: number;
}

export interface WorkflowTask {
  id: number;
  title: string;
  taskType: string;
  active: boolean;
  details: Record<string, unknown>;
}

export interface VtigerWorkflow {
  id: number;
  name: string;
  module: string;
  trigger: string;
  conditions: string;
  actions: string[];
  enabled: boolean;
  workflowType: string;
  editUrl: string;
  // Detail fields (present when scraped with detail scraper)
  internalModule?: string;
  description?: string;
  conditionsParsed?: WorkflowCondition[];
  tasks?: WorkflowTask[];
  scrapeErrors?: string[];
}

interface WorkflowsResponse {
  generated: string;
  count: number;
  workflows: VtigerWorkflow[];
}

export function useVtigerWorkflows() {
  return useQuery<WorkflowsResponse>({
    queryKey: ['vtiger-workflows'],
    queryFn: async () => {
      const res = await fetch('/api/vtiger/workflows');
      if (!res.ok && res.status !== 404) throw new Error('Failed to fetch workflows');
      return res.json();
    },
    staleTime: Infinity,
  });
}
