export interface OpenApiSpec {
  openapi: string;
  info: {
    title: string;
    version: string;
    description?: string;
  };
  paths: Record<string, Record<string, Operation>>;
  tags?: Tag[];
  components?: {
    schemas?: Record<string, Schema>;
    securitySchemes?: Record<string, SecurityScheme>;
  };
}

export interface Operation {
  operationId?: string;
  summary?: string;
  description?: string;
  tags?: string[];
  deprecated?: boolean;
  parameters?: Parameter[];
  requestBody?: RequestBody;
  responses?: Record<string, Response>;
  security?: Record<string, string[]>[];
}

export interface Tag {
  name: string;
  description?: string;
  'x-displayName'?: string;
}

export interface Parameter {
  name: string;
  in: 'path' | 'query' | 'header' | 'cookie';
  description?: string;
  required?: boolean;
  deprecated?: boolean;
  schema?: Schema;
}

export interface RequestBody {
  description?: string;
  required?: boolean;
  content?: Record<string, { schema?: Schema }>;
}

export interface Response {
  description: string;
  content?: Record<string, { schema?: Schema }>;
}

export interface Schema {
  type?: string;
  format?: string;
  description?: string;
  properties?: Record<string, Schema>;
  required?: string[];
  items?: Schema;
  $ref?: string;
  allOf?: Schema[];
  enum?: (string | number)[];
}

export interface SecurityScheme {
  type: string;
  in?: string;
  name?: string;
  description?: string;
}

export interface Endpoint {
  method: string;
  path: string;
  op: Operation;
}

export interface MailTemplate {
  name: string;
  code: string;
  subject: string;
}

export interface Mail {
  id: number;
  from: string;
  to: string;
  subject: string;
  message: string;
  tcode: string;
  create_date: string;
}

export interface Pagination {
  current: number;
  pages: number;
  total: number;
  limit: number;
}

declare global {
  interface Window {
    __DT_CONFIG__?: {
      baseUrl: string;
      apiUrl: string;
    };
  }
}
