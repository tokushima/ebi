import { useState, useMemo, useEffect } from 'react';
import { createRoot } from 'react-dom/client';

// PHPから渡されるグローバル変数
const spec = window.spec || {};
const mailTemplates = window.mailTemplates || [];
const apiUrls = window.apiUrls || {};

const methodColors = { GET: 'method-get', POST: 'method-post', PUT: 'method-put', DELETE: 'method-delete', PATCH: 'method-patch' };

function PropsTable({ items, title }) {
	if (!items || items.length === 0) return null;
	return (
		<div className="mb-3">
			<h6 className="fw-semibold text-secondary">{title}</h6>
			<table className="table table-sm table-bordered">
				<thead className="table-light"><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
				<tbody>
					{items.map((p, i) => (
						<tr key={i}>
							<td><code className="text-primary">{p.name}</code>{p.required && <span className="text-danger ms-1">*</span>}</td>
							<td className="text-muted">{p.schema?.type || p.type || '-'}</td>
							<td className="text-muted small">{p.description || '-'}</td>
						</tr>
					))}
				</tbody>
			</table>
		</div>
	);
}

function SchemaView({ schema, schemas, name }) {
	if (!schema) return <span className="text-muted">-</span>;
	if (schema.$ref) {
		const refName = schema.$ref.replace('#/components/schemas/', '');
		return <SchemaView schema={schemas[refName]} schemas={schemas} name={refName} />;
	}
	if (schema.type === 'array' && schema.items) return <span>Array&lt;<SchemaView schema={schema.items} schemas={schemas} />&gt;</span>;
	if (schema.type === 'object' && schema.properties) {
		const props = Object.entries(schema.properties).map(([k, v]) => ({ name: k, ...v, required: (schema.required || []).includes(k) }));
		return <div>{name && <span className="fw-semibold text-primary">{name}</span>}<PropsTable items={props} title="Properties" /></div>;
	}
	return <span className="text-muted">{schema.type || name || 'object'}</span>;
}

function schemaToTs(schema, schemas, indent = '', visited = new Set()) {
	if (!schema) return 'unknown';
	if (schema.$ref) {
		const refName = schema.$ref.replace('#/components/schemas/', '');
		if (visited.has(refName)) return refName.split('\\').pop();
		visited.add(refName);
		return schemaToTs(schemas[refName], schemas, indent, visited);
	}
	if (schema.type === 'array' && schema.items) return `${schemaToTs(schema.items, schemas, indent, visited)}[]`;
	if (schema.type === 'object' && schema.properties) {
		const props = Object.entries(schema.properties).map(([k, v]) => {
			const req = (schema.required || []).includes(k);
			return `${indent}  ${k}${req ? '' : '?'}: ${schemaToTs(v, schemas, indent + '  ', visited)};`;
		});
		return `{\n${props.join('\n')}\n${indent}}`;
	}
	const typeMap = { integer: 'number', number: 'number', string: 'string', boolean: 'boolean' };
	return typeMap[schema.type] || 'unknown';
}

function ResponsesView({ responses, schemas, operationId }) {
	if (!responses) return null;
	const [copied, setCopied] = useState(null);
	const toPascalCase = (str) => str ? str.replace(/(^|_)(\w)/g, (_, __, c) => c.toUpperCase()) : 'Response';
	const copyTs = (code, schema) => {
		const typeName = toPascalCase(operationId) + (code !== '200' ? code : '');
		const ts = `type ${typeName} = ${schemaToTs(schema, schemas)};`;
		navigator.clipboard.writeText(ts).then(() => { setCopied(code); setTimeout(() => setCopied(null), 2000); });
	};
	return (
		<div className="mb-3">
			<h6 className="fw-semibold text-secondary">Responses</h6>
			{Object.entries(responses).map(([code, resp]) => (
				<div key={code} className="card mb-2">
					<div className="card-header py-2 d-flex align-items-center gap-2">
						<span className={`badge ${code.startsWith('2') ? 'bg-success' : code.startsWith('4') ? 'bg-warning' : 'bg-danger'}`}>{code}</span>
						<span className="small text-muted flex-grow-1">{resp.description}</span>
						{resp.content?.['application/json']?.schema && <button className="btn btn-outline-secondary btn-sm py-0 px-2" onClick={() => copyTs(code, resp.content['application/json'].schema)}>{copied === code ? 'Copied!' : 'Copy TS'}</button>}
					</div>
					{resp.content?.['application/json']?.schema && <div className="card-body py-2"><SchemaView schema={resp.content['application/json'].schema} schemas={schemas} /></div>}
				</div>
			))}
		</div>
	);
}

function TryItPanel({ endpoint, op }) {
	const [params, setParams] = useState({});
	const [body, setBody] = useState('');
	const [response, setResponse] = useState(null);
	const [loading, setLoading] = useState(false);
	const allParams = op.parameters || [];

	const execute = async () => {
		setLoading(true); setResponse(null);
		try {
			let url = endpoint.path;
			allParams.filter(p => p.in === 'path').forEach(p => { url = url.replace(`{${p.name}}`, encodeURIComponent(params[p.name] || '')); });
			const qp = allParams.filter(p => p.in === 'query' && params[p.name]).map(p => `${p.name}=${encodeURIComponent(params[p.name])}`);
			if (qp.length) url += '?' + qp.join('&');
			const baseUrl = window.location.pathname.replace(/\/dt\/?$/, '');
			const opts = { method: endpoint.method, headers: {} };
			if (['POST', 'PUT', 'PATCH'].includes(endpoint.method) && body) { opts.body = body; opts.headers['Content-Type'] = 'application/json'; }
			const start = Date.now();
			const res = await fetch(baseUrl + url, opts);
			const time = Date.now() - start;
			const text = await res.text();
			let json = null; try { json = JSON.parse(text); } catch {}
			setResponse({ status: res.status, time, body: json || text });
		} catch (e) { setResponse({ error: e.message }); }
		setLoading(false);
	};

	return (
		<div className="border-top pt-3 mt-3">
			<h6 className="fw-semibold text-secondary mb-3">Try It</h6>
			{allParams.length > 0 && <div className="mb-3">
				{allParams.map(p => (
					<div key={p.name} className="row mb-2 align-items-center">
						<label className="col-3 col-form-label font-monospace small">{p.name} {p.required && <span className="text-danger">*</span>}</label>
						<div className="col-7"><input type="text" className="form-control form-control-sm" value={params[p.name] || ''} onChange={e => setParams(prev => ({ ...prev, [p.name]: e.target.value }))} placeholder={p.schema?.type || 'value'} /></div>
						<div className="col-2 text-muted small">{p.in}</div>
					</div>
				))}
			</div>}
			{['POST', 'PUT', 'PATCH'].includes(endpoint.method) && <div className="mb-3"><label className="form-label small text-muted">Request Body (JSON)</label><textarea className="form-control font-monospace" rows={4} value={body} onChange={e => setBody(e.target.value)} placeholder='{"key": "value"}' /></div>}
			<button className="btn btn-primary" onClick={execute} disabled={loading}>{loading ? 'Sending...' : 'Execute'}</button>
			{response && <div className="mt-3">
				{'error' in response ? <div className="alert alert-danger">Error: {response.error}</div> : <div className="card">
					<div className="card-header py-2 d-flex align-items-center gap-3">
						<span className={`badge ${response.status < 300 ? 'bg-success' : response.status < 400 ? 'bg-warning' : 'bg-danger'}`}>{response.status}</span>
						<span className="small text-muted">{response.time}ms</span>
					</div>
					<pre className="code-block mb-0 p-3">{typeof response.body === 'object' ? JSON.stringify(response.body, null, 2) : response.body}</pre>
				</div>}
			</div>}
		</div>
	);
}

function EndpointModal({ endpoint, schemas, onClose }) {
	const [showTry, setShowTry] = useState(false);
	const op = endpoint.op;
	return (
		<div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} onClick={onClose}>
			<div className="modal-dialog modal-xl modal-dialog-scrollable" onClick={e => e.stopPropagation()}>
				<div className="modal-content">
					<div className="modal-header">
						<div className="d-flex align-items-center gap-2">
							<span className={`method-badge ${methodColors[endpoint.method]}`}>{endpoint.method}</span>
							<code className="fs-5">{endpoint.path}</code>
						</div>
						<button type="button" className="btn-close" onClick={onClose}></button>
					</div>
					<div className="modal-body">
						{op.summary && <p className="lead">{op.summary}</p>}
						{op.description && <p className="text-muted" style={{ whiteSpace: 'pre-wrap' }}>{op.description}</p>}
						<div className="d-flex gap-2 mb-3">
							{op.tags?.map(t => <span key={t} className="badge bg-secondary">{t}</span>)}
							{op.deprecated && <span className="badge bg-danger">deprecated</span>}
						</div>
						{op.operationId && <p className="small mb-3"><span className="text-muted">Operation ID:</span><code className="ms-2 bg-light px-2 py-1 rounded">{op.operationId}</code></p>}
						<PropsTable items={op.parameters} title="Parameters" />
						{op.requestBody?.content && <div className="mb-3"><h6 className="fw-semibold text-secondary">Request Body</h6><div className="card"><div className="card-body py-2">{Object.entries(op.requestBody.content).map(([ct, c]) => <div key={ct}>{c.schema && <SchemaView schema={c.schema} schemas={schemas} />}</div>)}</div></div></div>}
						<ResponsesView responses={op.responses} schemas={schemas} operationId={op.operationId} />
						<div className="mt-4"><button className="btn btn-outline-secondary" onClick={() => setShowTry(!showTry)}>{showTry ? 'Hide Try It' : 'Try It'}</button>{showTry && <TryItPanel endpoint={endpoint} op={op} />}</div>
					</div>
				</div>
			</div>
		</div>
	);
}

function Endpoints({ onSelect }) {
	const [search, setSearch] = useState('');
	const [tagFilter, setTagFilter] = useState('');
	const endpoints = useMemo(() => {
		const result = [];
		for (const [path, methods] of Object.entries(spec.paths || {})) {
			for (const [method, op] of Object.entries(methods)) result.push({ method: method.toUpperCase(), path, op });
		}
		return result;
	}, []);
	const tags = useMemo(() => (spec.tags || []).map(t => ({ name: t.name, label: t['x-displayName'] || t.name })), []);
	const filtered = endpoints.filter(e => {
		const s = search.toLowerCase();
		return (!s || e.path.toLowerCase().includes(s) || (e.op.summary || '').toLowerCase().includes(s)) && (!tagFilter || (e.op.tags || []).includes(tagFilter));
	});
	const grouped = filtered.reduce((acc, e) => { const tag = e.op.tags?.[0] || 'Other'; if (!acc[tag]) acc[tag] = []; acc[tag].push(e); return acc; }, {});

	return (
		<div>
			<div className="mb-4">
				<h1 className="h3">{spec.info?.title || 'API'}</h1>
				{spec.info?.description && <p className="text-muted">{spec.info.description}</p>}
				<span className="badge bg-primary">v{spec.info?.version}</span>
			</div>
			<div className="row g-3 mb-4">
				<div className="col-md-8"><input type="text" className="form-control" placeholder="Search endpoints..." value={search} onChange={e => setSearch(e.target.value)} /></div>
				<div className="col-md-4"><select className="form-select" value={tagFilter} onChange={e => setTagFilter(e.target.value)}><option value="">All Tags</option>{tags.map(t => <option key={t.name} value={t.name}>{t.label}</option>)}</select></div>
			</div>
			{Object.entries(grouped).map(([tag, items]) => (
				<div key={tag} className="card mb-4">
					<div className="card-header fw-semibold">{tags.find(t => t.name === tag)?.label || tag}</div>
					<div className="list-group list-group-flush">
						{items.map((e, i) => (
							<div key={i} className={`list-group-item endpoint-row d-flex align-items-center gap-3 ${e.op.deprecated ? 'opacity-50' : ''}`} onClick={() => onSelect(e)}>
								<span className={`method-badge ${methodColors[e.method]}`}>{e.method}</span>
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

function Schemas({ selected, onSelect, onClose }) {
	const schemas = spec.components?.schemas || {};
	const items = Object.entries(schemas);
	const [search, setSearch] = useState('');
	const [typeFilter, setTypeFilter] = useState('');

	if (items.length === 0) return <div className="text-muted">No schemas defined.</div>;

	const filtered = items.filter(([name, schema]) => {
		const s = search.toLowerCase();
		const matchSearch = !s || name.toLowerCase().includes(s) || (schema.description || '').toLowerCase().includes(s);
		const matchType = !typeFilter || (typeFilter === 'dao' && schema['x-dao']) || (typeFilter === 'other' && !schema['x-dao']);
		return matchSearch && matchType;
	});

	const daoCount = items.filter(([_, s]) => s['x-dao']).length;

	return (
		<div>
			<h1 className="h3 mb-4">Schemas <span className="text-muted fw-normal fs-6">({filtered.length}/{items.length})</span></h1>
			<div className="row g-3 mb-4">
				<div className="col-md-8"><input type="text" className="form-control" placeholder="Search schemas..." value={search} onChange={e => setSearch(e.target.value)} /></div>
				<div className="col-md-4">
					<select className="form-select" value={typeFilter} onChange={e => setTypeFilter(e.target.value)}>
						<option value="">All ({items.length})</option>
						<option value="dao">Dao ({daoCount})</option>
						<option value="other">Other ({items.length - daoCount})</option>
					</select>
				</div>
			</div>
			<div className="card">
				<table className="table table-hover mb-0">
					<thead className="table-light"><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
					<tbody>{filtered.map(([name, schema]) => (
						<tr key={name} className="endpoint-row" onClick={() => onSelect(name, schema)}>
							<td><code className="text-primary fw-medium">{name}</code></td>
							<td>{schema['x-dao'] ? <span className="badge bg-info">Dao</span> : <span className="badge bg-secondary">Obj</span>}</td>
							<td className="text-muted small text-truncate" style={{maxWidth:'400px'}}>{schema.description || '-'}</td>
						</tr>
					))}</tbody>
				</table>
			</div>
			{selected && (
				<div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} onClick={onClose}>
					<div className="modal-dialog modal-xl" onClick={e => e.stopPropagation()}>
						<div className="modal-content">
							<div className="modal-header">
								<h5 className="modal-title"><code className="text-primary">{selected.name}</code>{selected.schema['x-dao'] && <span className="badge bg-info ms-2">Dao</span>}</h5>
								<button type="button" className="btn-close" onClick={onClose}></button>
							</div>
							<div className="modal-body">
								{selected.schema.description && <p className="text-muted mb-3" style={{ whiteSpace: 'pre-wrap' }}>{selected.schema.description}</p>}
								<SchemaView schema={selected.schema} schemas={schemas} />
							</div>
						</div>
					</div>
				</div>
			)}
		</div>
	);
}

function MailPage() {
	const [tab, setTab] = useState('sent');
	const [sentMails, setSentMails] = useState([]);
	const [loadingSent, setLoadingSent] = useState(true);
	const [selectedMail, setSelectedMail] = useState(null);
	const [filterEmail, setFilterEmail] = useState('');
	const [filterTemplate, setFilterTemplate] = useState('');

	useEffect(() => {
		fetch(apiUrls.sent_mails)
			.then(res => res.json())
			.then(data => { setSentMails(data.mails || []); setLoadingSent(false); })
			.catch(() => setLoadingSent(false));
	}, []);

	const templateCodes = useMemo(() => [...new Set(sentMails.map(m => m.tcode).filter(Boolean))].sort(), [sentMails]);
	const filteredMails = sentMails.filter(m => {
		const emailMatch = !filterEmail || (m.to && m.to.toLowerCase().includes(filterEmail.toLowerCase())) || (m.from && m.from.toLowerCase().includes(filterEmail.toLowerCase()));
		const templateMatch = !filterTemplate || m.tcode === filterTemplate;
		return emailMatch && templateMatch;
	});

	return (
		<div>
			<h1 className="h3 mb-4">Mail</h1>
			<ul className="nav nav-tabs mb-4">
				<li className="nav-item"><button className={`nav-link ${tab === 'sent' ? 'active' : ''}`} onClick={() => setTab('sent')}>Sent Mails</button></li>
				<li className="nav-item"><button className={`nav-link ${tab === 'templates' ? 'active' : ''}`} onClick={() => setTab('templates')}>Templates</button></li>
			</ul>

			{tab === 'sent' && (
				<div>
					{loadingSent ? <div className="text-center py-4"><div className="spinner-border text-primary" /></div> : sentMails.length === 0 ? <div className="alert alert-info">No sent mails found. (SmtpBlackholeDao)</div> : (
						<>
							<div className="row mb-3 g-2">
								<div className="col-md-6"><input type="text" className="form-control" placeholder="Filter by email address..." value={filterEmail} onChange={e => setFilterEmail(e.target.value)} /></div>
								<div className="col-md-4">
									<select className="form-select" value={filterTemplate} onChange={e => setFilterTemplate(e.target.value)}>
										<option value="">All Templates</option>
										{templateCodes.map(code => <option key={code} value={code}>{code}</option>)}
									</select>
								</div>
								<div className="col-md-2 text-muted small d-flex align-items-center">{filteredMails.length} / {sentMails.length}</div>
							</div>
							<div className="card">
								<table className="table table-hover mb-0">
									<thead className="table-light"><tr><th style={{width:'160px'}}>Date</th><th>To</th><th>Subject</th><th style={{width:'100px'}}>Code</th></tr></thead>
									<tbody>{filteredMails.map((m, i) => (
										<tr key={i} className="endpoint-row" onClick={() => setSelectedMail(m)}>
											<td className="text-muted small">{m.create_date}</td>
											<td><code className="text-primary">{m.to}</code></td>
											<td>{m.subject}</td>
											<td>{m.tcode && <span className="badge bg-secondary">{m.tcode}</span>}</td>
										</tr>
									))}</tbody>
								</table>
							</div>
						</>
					)}
				</div>
			)}

			{tab === 'templates' && (
				mailTemplates.length === 0 ? <div className="text-muted">No mail templates.</div> : (
					<div className="card">
						<table className="table table-hover mb-0">
							<thead className="table-light"><tr><th>Name</th><th>Code</th><th>Summary</th><th style={{width:'80px'}}>Sent</th></tr></thead>
							<tbody>{mailTemplates.map((t, i) => {
								const sentCount = sentMails.filter(m => m.tcode === t.code).length;
								return <tr key={i} className={sentCount > 0 ? 'endpoint-row' : ''} onClick={() => { if(sentCount > 0){ setFilterTemplate(t.code); setTab('sent'); } }}>
									<td className="fw-medium">{t.name}</td>
									<td><code className="bg-light px-2 py-1 rounded">{t.code}</code></td>
									<td className="text-muted">{t.summary || t.subject}</td>
									<td>{sentCount > 0 ? <span className="badge bg-primary">{sentCount}</span> : <span className="text-muted">-</span>}</td>
								</tr>;
							})}</tbody>
						</table>
					</div>
				)
			)}

			{selectedMail && (
				<div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} onClick={() => setSelectedMail(null)}>
					<div className="modal-dialog modal-lg" onClick={e => e.stopPropagation()}>
						<div className="modal-content">
							<div className="modal-header">
								<h5 className="modal-title">{selectedMail.subject}</h5>
								<button type="button" className="btn-close" onClick={() => setSelectedMail(null)}></button>
							</div>
							<div className="modal-body">
								<div className="mb-3">
									<div className="row mb-2"><div className="col-2 text-muted">From:</div><div className="col-10"><code>{selectedMail.from}</code></div></div>
									<div className="row mb-2"><div className="col-2 text-muted">To:</div><div className="col-10"><code>{selectedMail.to}</code></div></div>
									<div className="row mb-2"><div className="col-2 text-muted">Date:</div><div className="col-10">{selectedMail.create_date}</div></div>
									{selectedMail.tcode && <div className="row mb-2"><div className="col-2 text-muted">Code:</div><div className="col-10"><span className="badge bg-secondary">{selectedMail.tcode}</span></div></div>}
								</div>
								<hr />
								<pre className="bg-light p-3 rounded" style={{whiteSpace:'pre-wrap',maxHeight:'400px',overflow:'auto'}}>{selectedMail.message}</pre>
							</div>
						</div>
					</div>
				</div>
			)}
		</div>
	);
}

function ConfigPage() {
	const [configs, setConfigs] = useState([]);
	const [loading, setLoading] = useState(true);
	const [search, setSearch] = useState('');
	const [filterDefined, setFilterDefined] = useState('');

	useEffect(() => {
		fetch(apiUrls.configs)
			.then(res => res.json())
			.then(data => { setConfigs(data.configs || []); setLoading(false); })
			.catch(() => setLoading(false));
	}, []);

	const filtered = configs.filter(c => {
		const s = search.toLowerCase();
		const matchSearch = !s || c.class.toLowerCase().includes(s) || c.name.toLowerCase().includes(s) || (c.summary || '').toLowerCase().includes(s);
		const matchDefined = filterDefined === '' || (filterDefined === 'defined' ? c.defined : !c.defined);
		return matchSearch && matchDefined;
	});

	const grouped = filtered.reduce((acc, c) => { if (!acc[c.class]) acc[c.class] = []; acc[c.class].push(c); return acc; }, {});

	return (
		<div>
			<h1 className="h3 mb-4">Configurations</h1>
			<div className="row g-3 mb-4">
				<div className="col-md-8"><input type="text" className="form-control" placeholder="Search configs..." value={search} onChange={e => setSearch(e.target.value)} /></div>
				<div className="col-md-4"><select className="form-select" value={filterDefined} onChange={e => setFilterDefined(e.target.value)}><option value="">All</option><option value="defined">Defined</option><option value="undefined">Undefined</option></select></div>
			</div>
			{loading ? <div className="text-center py-4"><div className="spinner-border text-primary" /></div> : Object.keys(grouped).length === 0 ? <div className="alert alert-info">No configs found.</div> : (
				Object.entries(grouped).map(([className, items]) => (
					<div key={className} className="card mb-4">
						<div className="card-header fw-semibold"><code>{className}</code></div>
						<table className="table table-hover mb-0">
							<thead className="table-light"><tr><th style={{width:'250px'}}>Name</th><th>Type</th><th>Description</th><th style={{width:'80px'}}>Status</th></tr></thead>
							<tbody>{items.map((c, i) => (
								<tr key={i}>
									<td><code className="text-primary">{c.name}</code></td>
									<td className="text-muted small">{c.params.map(p => p.type).join(', ') || '-'}</td>
									<td className="small">{c.summary || '-'}</td>
									<td>{c.defined ? <span className="badge bg-success">Defined</span> : <span className="badge bg-secondary">-</span>}</td>
								</tr>
							))}</tbody>
						</table>
					</div>
				))
			)}
		</div>
	);
}

function parseHash() {
	const hash = window.location.hash.slice(1);
	if (!hash) return { page: 'endpoints', detail: null };
	const [page, ...rest] = hash.split('=');
	const detail = rest.join('=') || null;
	return { page: page || 'endpoints', detail: detail ? decodeURIComponent(detail) : null };
}

function App() {
	const initial = parseHash();
	const [page, setPage] = useState(initial.page);
	const [selected, setSelected] = useState(null);
	const [selectedSchema, setSelectedSchema] = useState(null);

	const updateHash = (p, detail = null) => {
		const hash = detail ? `${p}=${encodeURIComponent(detail)}` : p;
		window.history.replaceState(null, '', '#' + hash);
	};

	const handlePageChange = (p) => { setPage(p); setSelected(null); setSelectedSchema(null); updateHash(p); };

	const handleSelectEndpoint = (e) => { setSelected(e); updateHash('endpoints', e.path); };
	const handleCloseEndpoint = () => { setSelected(null); updateHash('endpoints'); };

	const handleSelectSchema = (name, schema) => { setSelectedSchema({ name, schema }); updateHash('schemas', name); };
	const handleCloseSchema = () => { setSelectedSchema(null); updateHash('schemas'); };

	useEffect(() => {
		const { page: initPage, detail } = parseHash();
		setPage(initPage);
		if (initPage === 'endpoints' && detail) {
			const paths = spec.paths || {};
			for (const [path, methods] of Object.entries(paths)) {
				if (path === detail) {
					const method = Object.keys(methods)[0];
					setSelected({ path, method, op: methods[method] });
					break;
				}
			}
		} else if (initPage === 'schemas' && detail) {
			const schemas = spec.components?.schemas || {};
			if (schemas[detail]) setSelectedSchema({ name: detail, schema: schemas[detail] });
		}
		const onHashChange = () => {
			const { page: p, detail: d } = parseHash();
			setPage(p);
			if (p === 'endpoints' && d) {
				const paths = spec.paths || {};
				for (const [path, methods] of Object.entries(paths)) {
					if (path === d) { setSelected({ path, method: Object.keys(methods)[0], op: methods[Object.keys(methods)[0]] }); break; }
				}
			} else if (p === 'schemas' && d) {
				const schemas = spec.components?.schemas || {};
				if (schemas[d]) setSelectedSchema({ name: d, schema: schemas[d] });
			}
		};
		window.addEventListener('hashchange', onHashChange);
		return () => window.removeEventListener('hashchange', onHashChange);
	}, []);

	return (
		<div className="min-vh-100">
			<nav className="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
				<div className="container">
					<span className="navbar-brand fw-bold">DevTools</span>
					<div className="navbar-nav me-auto flex-row gap-2">
						<button className={`nav-link btn btn-link ${page === 'endpoints' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('endpoints')}>Endpoints</button>
						<button className={`nav-link btn btn-link ${page === 'schemas' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('schemas')}>Schemas</button>
						<button className={`nav-link btn btn-link ${page === 'config' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('config')}>Config</button>
						<button className={`nav-link btn btn-link ${page === 'mail' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('mail')}>Mail</button>
					</div>
					<a href={apiUrls.redoc} className="btn btn-outline-secondary btn-sm me-2">Redoc</a><a href={apiUrls.openapi} className="btn btn-outline-primary btn-sm">OpenAPI JSON</a>
				</div>
			</nav>
			<main className="container py-4">
				{page === 'endpoints' && <Endpoints onSelect={handleSelectEndpoint} />}
				{page === 'schemas' && <Schemas selected={selectedSchema} onSelect={handleSelectSchema} onClose={handleCloseSchema} />}
				{page === 'config' && <ConfigPage />}
				{page === 'mail' && <MailPage />}
			</main>
			{selected && <EndpointModal endpoint={selected} schemas={spec.components?.schemas || {}} onClose={handleCloseEndpoint} />}
		</div>
	);
}

createRoot(document.getElementById('root')).render(<App />);
