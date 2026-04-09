export type TimeRange = '1h' | '6h' | '24h' | '7d' | '30d' | '90d' | '6mo' | '1y';

export interface TimeSeriesPoint {
  timestamp: string;
  invocations: number;
  errors: number;
}

export interface FunctionMetrics {
  name: string;
  invocations: number;
  errors: number;
  avgDuration: number;
  p95Duration: number;
  timeSeries: TimeSeriesPoint[];
}

export interface MetricsResponse {
  functions: FunctionMetrics[];
  totals: {
    invocations: number;
    errors: number;
    errorRate: number;
  };
}

export interface LogEntry {
  timestamp: number;
  message: string;
  logStream: string;
}

// Gravity Forms types

export interface FormFieldMapping {
  fieldId?: number;
  formFieldLabel: string;
  formInput: string;
  apiParam: string;
  note?: string;
}

export interface FormEndpoint {
  direction: 'inbound' | 'outbound';
  endpoint: string;
  method: 'GET' | 'POST';
  trigger: string;
  fieldMappings: FormFieldMapping[];
}

export interface FormField {
  id: number;
  label: string;
  type: string;
  inputName: string;
  isRequired: boolean;
  page: number;
  choices?: { text: string; value: string }[];
}

export interface GravityForm {
  id: number;
  title: string;
  description: string;
  purpose: string;
  pageCount: number;
  entryCount: number;
  isActive: boolean;
  fields: FormField[];
  endpoints: FormEndpoint[];
  wordpressPage?: { id: number; title: string; url: string; slug: string };
}
