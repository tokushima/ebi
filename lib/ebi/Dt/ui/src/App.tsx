import { useState, useMemo, useEffect, useCallback } from 'react';
import type { OpenApiSpec, Endpoint, Schema, Parameter, MailTemplate, Mail, Pagination } from './types/api';
import './index.css';

// スキーマ参照を解決
function resolveSchema(schema: Schema | undefined, schemas: Record<string, Schema>): Schema | null {
  if (!schema) return null;
  if (schema.$ref) {
    const name = schema.$ref.replace('#/components/schemas/', '');
    return schemas[name] || null;
  }
  return schema;
}

// パラメータテーブル
function ParamsTable({ params, title }: { params: Parameter[]; title: string }) {
  if (!params || params.length === 0) return null;
  return (
    <div className="mb-3">
      <h6 className="fw-semibold text-secondary">{title}</h6>
      <table className="table table-sm table-bordered">
        <thead className="table-light">
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Required</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          {params.map((p, i) => (
            <tr key={i}>
              <td><code className="text-primary">{p.name}</code></td>
              <td className="text-muted">{p.schema?.type || '-'}</td>
              <td>{p.required ? <span className="text-danger">*</span> : '-'}</td>
              <td className="text-muted small">{p.description || '-'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// スキーマプロパティ表示
function SchemaProps({ schema, schemas }: { schema: Schema; schemas: Record<string, Schema> }) {
  const resolved = resolveSchema(schema, schemas);
  if (!resolved) return <span className="text-muted">-</span>;

  if (resolved.type === 'array' && resolved.items) {
    return <span>Array&lt;<SchemaProps schema={resolved.items} schemas={schemas} />&gt;</span>;
  }

  if (resolved.type === 'object' && resolved.properties) {
    const props = Object.entries(resolved.properties);
    return (
      <table className="table table-sm table-bordered mb-0">
        <thead className="table-light">
          <tr>
            <th>Property</th>
            <th>Type</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          {props.map(([name, prop]) => (
            <tr key={name}>
              <td>
                <code className="text-primary">{name}</code>
                {resolved.required?.includes(name) && <span className="text-danger ms-1">*</span>}
              </td>
              <td className="text-muted">{prop.type || (prop.$ref ? prop.$ref.split('/').pop() : '-')}</td>
              <td className="text-muted small">{prop.description || '-'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    );
  }

  return <span className="text-muted">{resolved.type || 'object'}</span>;
}

// レスポンス表示
function ResponsesView({ responses, schemas }: { responses: Record<string, { description: string; content?: Record<string, { schema?: Schema }> }>; schemas: Record<string, Schema> }) {
  return (
    <div className="mb-3">
      <h6 className="fw-semibold text-secondary">Responses</h6>
      {Object.entries(responses).map(([code, resp]) => {
        const content = resp.content?.['application/json'];
        return (
          <div key={code} className="card mb-2">
            <div className="card-header py-2 d-flex align-items-center gap-2">
              <span className={`badge ${code.startsWith('2') ? 'bg-success' : code.startsWith('4') ? 'bg-warning' : 'bg-danger'}`}>{code}</span>
              <span className="small text-muted">{resp.description}</span>
            </div>
            {content?.schema && (
              <div className="card-body py-2">
                <SchemaProps schema={content.schema} schemas={schemas} />
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

// Try It パネル
function TryItPanel({ endpoint }: { endpoint: Endpoint }) {
  const [params, setParams] = useState<Record<string, string>>({});
  const [body, setBody] = useState('');
  const [response, setResponse] = useState<{ status: number; time: number; body: unknown } | { error: string } | null>(null);
  const [loading, setLoading] = useState(false);

  const allParams = endpoint.op.parameters || [];

  const updateParam = (name: string, value: string) => {
    setParams(prev => ({ ...prev, [name]: value }));
  };

  const execute = async () => {
    setLoading(true);
    setResponse(null);
    try {
      let url = endpoint.path;
      allParams.filter(p => p.in === 'path').forEach(p => {
        url = url.replace(`{${p.name}}`, encodeURIComponent(params[p.name] || ''));
      });

      const qp = allParams
        .filter(p => p.in === 'query' && params[p.name])
        .map(p => `${p.name}=${encodeURIComponent(params[p.name])}`);
      if (qp.length) url += '?' + qp.join('&');

      const baseUrl = window.location.pathname.replace(/\/dt\/?$/, '');
      const fullUrl = baseUrl + url;

      const opts: RequestInit = { method: endpoint.method, headers: {} };
      if (['POST', 'PUT', 'PATCH'].includes(endpoint.method) && body) {
        opts.body = body;
        (opts.headers as Record<string, string>)['Content-Type'] = 'application/json';
      }

      const start = Date.now();
      const res = await fetch(fullUrl, opts);
      const time = Date.now() - start;
      const text = await res.text();
      let json = null;
      try { json = JSON.parse(text); } catch { /* ignore */ }

      setResponse({ status: res.status, time, body: json || text });
    } catch (e) {
      setResponse({ error: (e as Error).message });
    }
    setLoading(false);
  };

  return (
    <div className="border-top pt-3 mt-3">
      <h6 className="fw-semibold text-secondary mb-3">Try It</h6>

      {allParams.length > 0 && (
        <div className="mb-3">
          {allParams.map(p => (
            <div key={p.name} className="row mb-2 align-items-center">
              <label className="col-3 col-form-label font-monospace small">
                {p.name} {p.required && <span className="text-danger">*</span>}
              </label>
              <div className="col-7">
                <input
                  type="text"
                  className="form-control form-control-sm"
                  value={params[p.name] || ''}
                  onChange={e => updateParam(p.name, e.target.value)}
                  placeholder={p.schema?.type || 'value'}
                />
              </div>
              <div className="col-2 text-muted small">{p.in}</div>
            </div>
          ))}
        </div>
      )}

      {['POST', 'PUT', 'PATCH'].includes(endpoint.method) && (
        <div className="mb-3">
          <label className="form-label small text-muted">Request Body (JSON)</label>
          <textarea
            className="form-control font-monospace"
            rows={4}
            value={body}
            onChange={e => setBody(e.target.value)}
            placeholder='{"key": "value"}'
          />
        </div>
      )}

      <button className="btn btn-primary" onClick={execute} disabled={loading}>
        {loading ? 'Sending...' : 'Execute'}
      </button>

      {response && (
        <div className="mt-3">
          {'error' in response ? (
            <div className="alert alert-danger">Error: {response.error}</div>
          ) : (
            <div className="card">
              <div className="card-header py-2 d-flex align-items-center gap-3">
                <span className={`badge ${response.status < 300 ? 'bg-success' : response.status < 400 ? 'bg-warning' : 'bg-danger'}`}>
                  {response.status}
                </span>
                <span className="small text-muted">{response.time}ms</span>
              </div>
              <pre className="code-block mb-0 p-3">
                {typeof response.body === 'object' ? JSON.stringify(response.body, null, 2) : String(response.body)}
              </pre>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// エンドポイント詳細モーダル
function EndpointModal({ endpoint, schemas, onClose }: { endpoint: Endpoint; schemas: Record<string, Schema>; onClose: () => void }) {
  const [showTry, setShowTry] = useState(false);
  const op = endpoint.op;

  return (
    <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} onClick={onClose}>
      <div className="modal-dialog modal-lg modal-dialog-scrollable" onClick={e => e.stopPropagation()}>
        <div className="modal-content">
          <div className="modal-header">
            <div className="d-flex align-items-center gap-2">
              <span className={`method-badge method-${endpoint.method.toLowerCase()}`}>{endpoint.method}</span>
              <code className="fs-5">{endpoint.path}</code>
            </div>
            <button type="button" className="btn-close" onClick={onClose}></button>
          </div>
          <div className="modal-body">
            {op.summary && <p className="lead">{op.summary}</p>}
            {op.description && <p className="text-muted">{op.description}</p>}

            <div className="d-flex gap-2 mb-3">
              {op.tags?.map(t => <span key={t} className="badge bg-secondary">{t}</span>)}
              {op.deprecated && <span className="badge bg-danger">deprecated</span>}
            </div>

            {op.operationId && (
              <p className="small mb-3">
                <span className="text-muted">Operation ID:</span>
                <code className="ms-2 bg-light px-2 py-1 rounded">{op.operationId}</code>
              </p>
            )}

            <ParamsTable params={op.parameters || []} title="Parameters" />

            {op.requestBody?.content && (
              <div className="mb-3">
                <h6 className="fw-semibold text-secondary">Request Body</h6>
                <div className="card">
                  <div className="card-body py-2">
                    {Object.entries(op.requestBody.content).map(([contentType, c]) => (
                      <div key={contentType}>
                        {c.schema && <SchemaProps schema={c.schema} schemas={schemas} />}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}

            {op.responses && <ResponsesView responses={op.responses} schemas={schemas} />}

            <div className="mt-4">
              <button className="btn btn-outline-secondary" onClick={() => setShowTry(!showTry)}>
                {showTry ? 'Hide Try It' : 'Try It'}
              </button>
              {showTry && <TryItPanel endpoint={endpoint} />}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// ダッシュボード
function Dashboard({ spec, onSelect }: { spec: OpenApiSpec; onSelect: (e: Endpoint) => void }) {
  const [search, setSearch] = useState('');
  const [tagFilter, setTagFilter] = useState('');

  const endpoints = useMemo(() => {
    const result: Endpoint[] = [];
    for (const [path, methods] of Object.entries(spec.paths || {})) {
      for (const [method, op] of Object.entries(methods)) {
        result.push({ method: method.toUpperCase(), path, op });
      }
    }
    return result;
  }, [spec]);

  const tags = useMemo(() => (spec.tags || []).map(t => ({ name: t.name, label: t['x-displayName'] || t.name })), [spec]);

  const filtered = endpoints.filter(e => {
    const s = search.toLowerCase();
    const matchSearch = !s || e.path.toLowerCase().includes(s) || (e.op.summary || '').toLowerCase().includes(s);
    const matchTag = !tagFilter || (e.op.tags || []).includes(tagFilter);
    return matchSearch && matchTag;
  });

  const grouped = filtered.reduce<Record<string, Endpoint[]>>((acc, e) => {
    const tag = e.op.tags?.[0] || 'Other';
    if (!acc[tag]) acc[tag] = [];
    acc[tag].push(e);
    return acc;
  }, {});

  const stats = {
    total: endpoints.length,
    get: endpoints.filter(e => e.method === 'GET').length,
    post: endpoints.filter(e => e.method === 'POST').length,
    deprecated: endpoints.filter(e => e.op.deprecated).length,
  };

  return (
    <div>
      <div className="mb-4">
        <h1 className="h3">{spec.info?.title || 'API'}</h1>
        {spec.info?.description && <p className="text-muted">{spec.info.description}</p>}
        <span className="badge bg-primary">v{spec.info?.version}</span>
      </div>

      <div className="row g-3 mb-4">
        <div className="col-6 col-md-3">
          <div className="card text-center">
            <div className="card-body py-3">
              <div className="fs-4 fw-bold">{stats.total}</div>
              <div className="small text-muted">Total</div>
            </div>
          </div>
        </div>
        <div className="col-6 col-md-3">
          <div className="card text-center">
            <div className="card-body py-3">
              <div className="fs-4 fw-bold text-primary">{stats.get}</div>
              <div className="small text-muted">GET</div>
            </div>
          </div>
        </div>
        <div className="col-6 col-md-3">
          <div className="card text-center">
            <div className="card-body py-3">
              <div className="fs-4 fw-bold text-success">{stats.post}</div>
              <div className="small text-muted">POST</div>
            </div>
          </div>
        </div>
        <div className="col-6 col-md-3">
          <div className="card text-center">
            <div className="card-body py-3">
              <div className="fs-4 fw-bold text-danger">{stats.deprecated}</div>
              <div className="small text-muted">Deprecated</div>
            </div>
          </div>
        </div>
      </div>

      <div className="row g-3 mb-4">
        <div className="col-md-8">
          <input
            type="text"
            className="form-control"
            placeholder="Search endpoints..."
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
        <div className="col-md-4">
          <select className="form-select" value={tagFilter} onChange={e => setTagFilter(e.target.value)}>
            <option value="">All Tags</option>
            {tags.map(t => <option key={t.name} value={t.name}>{t.label}</option>)}
          </select>
        </div>
      </div>

      {Object.entries(grouped).map(([tag, items]) => (
        <div key={tag} className="card mb-4">
          <div className="card-header fw-semibold">{tags.find(t => t.name === tag)?.label || tag}</div>
          <div className="list-group list-group-flush">
            {items.map((e, i) => (
              <div
                key={i}
                className={`list-group-item endpoint-row d-flex align-items-center gap-3 ${e.op.deprecated ? 'opacity-50' : ''}`}
                onClick={() => onSelect(e)}
              >
                <span className={`method-badge method-${e.method.toLowerCase()}`}>{e.method}</span>
                <code className="flex-grow-1">{e.path}</code>
                <span className="text-muted small text-truncate" style={{ maxWidth: '200px' }}>{e.op.summary}</span>
                {e.op.deprecated && <span className="badge bg-danger">deprecated</span>}
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}

// スキーマ一覧
function Schemas({ spec }: { spec: OpenApiSpec }) {
  const schemas = spec.components?.schemas || {};
  const items = Object.entries(schemas);

  if (items.length === 0) {
    return <div className="text-muted">No schemas defined.</div>;
  }

  return (
    <div>
      <h1 className="h3 mb-4">Schemas</h1>
      {items.map(([name, schema]) => (
        <div key={name} className="card mb-3">
          <div className="card-header">
            <span className="fw-semibold text-primary">{name}</span>
          </div>
          <div className="card-body">
            {schema.description && <p className="text-muted small mb-2">{schema.description}</p>}
            <SchemaProps schema={schema} schemas={schemas} />
          </div>
        </div>
      ))}
    </div>
  );
}

// ページネーション
function PaginationNav({ pagination, onPageChange }: { pagination: Pagination; onPageChange: (page: number) => void }) {
  if (pagination.pages <= 1) return null;

  const range: number[] = [];
  const current = pagination.current;
  const last = pagination.pages;
  const delta = 3;
  let start = Math.max(1, current - delta);
  let end = Math.min(last, current + delta);
  if (current - delta < 1) end = Math.min(last, end + (delta - current + 1));
  if (current + delta > last) start = Math.max(1, start - (current + delta - last));
  for (let i = start; i <= end; i++) range.push(i);

  return (
    <nav>
      <ul className="pagination pagination-sm mb-0">
        <li className={`page-item ${current <= 1 ? 'disabled' : ''}`}>
          <button className="page-link" onClick={() => onPageChange(current - 1)}>Prev</button>
        </li>
        {start > 1 && <>
          <li className="page-item"><button className="page-link" onClick={() => onPageChange(1)}>1</button></li>
          {start > 2 && <li className="page-item disabled"><span className="page-link">...</span></li>}
        </>}
        {range.map(p => (
          <li key={p} className={`page-item ${p === current ? 'active' : ''}`}>
            <button className="page-link" onClick={() => onPageChange(p)}>{p}</button>
          </li>
        ))}
        {end < last && <>
          {end < last - 1 && <li className="page-item disabled"><span className="page-link">...</span></li>}
          <li className="page-item"><button className="page-link" onClick={() => onPageChange(last)}>{last}</button></li>
        </>}
        <li className={`page-item ${current >= last ? 'disabled' : ''}`}>
          <button className="page-link" onClick={() => onPageChange(current + 1)}>Next</button>
        </li>
      </ul>
    </nav>
  );
}

// Sent Mails 一覧
function SentMails({ apiUrl }: { apiUrl: string }) {
  const [mails, setMails] = useState<Mail[]>([]);
  const [pagination, setPagination] = useState<Pagination | null>(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [selected, setSelected] = useState<Mail | null>(null);

  const fetchMails = useCallback((p: number) => {
    setLoading(true);
    fetch(`${apiUrl}?page=${p}&paginate_by=20`)
      .then(r => r.json())
      .then(data => {
        setMails(data.mails || []);
        setPagination(data.pagination || null);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, [apiUrl]);

  useEffect(() => { fetchMails(page); }, [page, fetchMails]);

  const filtered = mails.filter(m => {
    if (!search) return true;
    const s = search.toLowerCase();
    return (m.to || '').toLowerCase().includes(s)
      || (m.from || '').toLowerCase().includes(s)
      || (m.subject || '').toLowerCase().includes(s)
      || (m.tcode || '').toLowerCase().includes(s);
  });

  const handlePageChange = (p: number) => {
    setPage(p);
    setSearch('');
  };

  if (loading && mails.length === 0) {
    return <div className="text-center py-4"><div className="spinner-border text-primary"></div></div>;
  }

  return (
    <div>
      {mails.length === 0 ? (
        <div className="alert alert-info">No sent mails found. (SmtpBlackholeDao)</div>
      ) : (
        <>
          <div className="row mb-3 g-2 align-items-center">
            <div className="col-md-6">
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="Filter by to, from, subject, tcode..."
                value={search}
                onChange={e => setSearch(e.target.value)}
              />
            </div>
            <div className="col-md-6 d-flex justify-content-end align-items-center gap-3">
              {pagination && (
                <span className="text-muted small">
                  {pagination.total} mails
                </span>
              )}
              {pagination && <PaginationNav pagination={pagination} onPageChange={handlePageChange} />}
            </div>
          </div>

          <div className="card">
            <table className="table table-hover mb-0">
              <thead className="table-light">
                <tr>
                  <th style={{ width: '160px' }}>Date</th>
                  <th>To</th>
                  <th>Subject</th>
                  <th style={{ width: '100px' }}>Code</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((m, i) => (
                  <tr key={i} className="endpoint-row" onClick={() => setSelected(m)}>
                    <td className="text-muted small">{m.create_date}</td>
                    <td><code className="text-primary">{m.to}</code></td>
                    <td>{m.subject}</td>
                    <td>{m.tcode && <span className="badge bg-secondary">{m.tcode}</span>}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {pagination && pagination.pages > 1 && (
            <div className="d-flex justify-content-center mt-3">
              <PaginationNav pagination={pagination} onPageChange={handlePageChange} />
            </div>
          )}
        </>
      )}

      {selected && (
        <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} onClick={() => setSelected(null)}>
          <div className="modal-dialog modal-lg modal-dialog-scrollable" onClick={e => e.stopPropagation()}>
            <div className="modal-content">
              <div className="modal-header">
                <h5 className="modal-title">{selected.subject}</h5>
                <button type="button" className="btn-close" onClick={() => setSelected(null)}></button>
              </div>
              <div className="modal-body">
                <div className="row mb-2">
                  <div className="col-2 text-muted">From:</div>
                  <div className="col-10">{selected.from}</div>
                </div>
                <div className="row mb-2">
                  <div className="col-2 text-muted">To:</div>
                  <div className="col-10">{selected.to}</div>
                </div>
                <div className="row mb-2">
                  <div className="col-2 text-muted">Date:</div>
                  <div className="col-10">{selected.create_date}</div>
                </div>
                {selected.tcode && (
                  <div className="row mb-2">
                    <div className="col-2 text-muted">Code:</div>
                    <div className="col-10"><span className="badge bg-secondary">{selected.tcode}</span></div>
                  </div>
                )}
                <hr />
                <pre className="bg-light p-3 rounded" style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>{selected.message}</pre>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// メールテンプレート
function MailTemplates({ templates }: { templates: MailTemplate[] }) {
  if (templates.length === 0) {
    return <div className="text-muted">No mail templates.</div>;
  }

  return (
    <div className="card">
      <table className="table table-hover mb-0">
        <thead className="table-light">
          <tr>
            <th>Name</th>
            <th>Code</th>
            <th>Summary</th>
          </tr>
        </thead>
        <tbody>
          {templates.map((t, i) => (
            <tr key={i}>
              <td className="fw-medium">{t.name}</td>
              <td><code className="bg-light px-2 py-1 rounded">{t.code}</code></td>
              <td className="text-muted">{t.summary || t.subject}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// Mail ページ（Sent Mails + Templates タブ）
function MailPage({ templates, sentMailsUrl }: { templates: MailTemplate[]; sentMailsUrl: string }) {
  const [tab, setTab] = useState<'sent' | 'templates'>('sent');

  return (
    <div>
      <h1 className="h3 mb-4">Mail</h1>
      <ul className="nav nav-tabs mb-4">
        <li className="nav-item">
          <button className={`nav-link ${tab === 'sent' ? 'active' : ''}`} onClick={() => setTab('sent')}>Sent Mails</button>
        </li>
        <li className="nav-item">
          <button className={`nav-link ${tab === 'templates' ? 'active' : ''}`} onClick={() => setTab('templates')}>Templates</button>
        </li>
      </ul>
      {tab === 'sent' && <SentMails apiUrl={sentMailsUrl} />}
      {tab === 'templates' && <MailTemplates templates={templates} />}
    </div>
  );
}

// メインApp
export default function App() {
  const [spec, setSpec] = useState<OpenApiSpec | null>(null);
  const [mailTemplates, setMailTemplates] = useState<MailTemplate[]>([]);
  const [page, setPage] = useState<'dashboard' | 'schemas' | 'mail'>('dashboard');
  const [selected, setSelected] = useState<Endpoint | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [specRes, mailRes] = await Promise.all([
          fetch('openapi.json'),
          fetch('mail_templates.json'),
        ]);
        const specData = await specRes.json();
        const mailData = await mailRes.json();
        setSpec(specData);
        setMailTemplates(mailData.templates || []);
      } catch (e) {
        console.error('Failed to load data:', e);
      }
      setLoading(false);
    };
    fetchData();
  }, []);

  if (loading) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Loading...</span>
        </div>
      </div>
    );
  }

  if (!spec) {
    return (
      <div className="container py-5">
        <div className="alert alert-danger">Failed to load OpenAPI specification.</div>
      </div>
    );
  }

  return (
    <div className="min-vh-100 bg-light">
      <nav className="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div className="container">
          <span className="navbar-brand fw-bold">DevTools</span>
          <div className="navbar-nav me-auto flex-row gap-2">
            <button
              className={`nav-link btn btn-link ${page === 'dashboard' ? 'active' : ''}`}
              onClick={() => setPage('dashboard')}
            >
              Dashboard
            </button>
            <button
              className={`nav-link btn btn-link ${page === 'schemas' ? 'active' : ''}`}
              onClick={() => setPage('schemas')}
            >
              Schemas
            </button>
            <button
              className={`nav-link btn btn-link ${page === 'mail' ? 'active' : ''}`}
              onClick={() => setPage('mail')}
            >
              Mail
            </button>
          </div>
          <div className="d-flex gap-3">
            <a href="redoc" className="nav-link text-muted">ReDoc</a>
            <a href="swagger" className="nav-link text-muted">Swagger</a>
            <a href="openapi.json" target="_blank" className="nav-link text-primary">OpenAPI JSON</a>
          </div>
        </div>
      </nav>

      <main className="container py-4">
        {page === 'dashboard' && <Dashboard spec={spec} onSelect={setSelected} />}
        {page === 'schemas' && <Schemas spec={spec} />}
        {page === 'mail' && <MailPage templates={mailTemplates} sentMailsUrl="sent_mails.json" />}
      </main>

      {selected && (
        <EndpointModal
          endpoint={selected}
          schemas={spec.components?.schemas || {}}
          onClose={() => setSelected(null)}
        />
      )}
    </div>
  );
}
