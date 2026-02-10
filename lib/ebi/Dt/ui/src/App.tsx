// @ts-nocheck
import { useState, useMemo, useEffect, useRef, useCallback } from 'react';
import './index.css';

// PHPから渡されるグローバル変数
const spec = window.spec || {};
const webhooks = window.webhooks || [];
const allTagDefs = window.allTags || [];
const mailTemplates = window.mailTemplates || [];
const apiUrls = window.apiUrls || {};
const hasSmtpBlackhole = !!window.hasSmtpBlackhole;
const appmode = window.appmode || '';

const methodColors = { GET: 'method-get', POST: 'method-post', PUT: 'method-put', DELETE: 'method-delete', PATCH: 'method-patch' };

const LockIcon = ({ size = 14 }) => (
	<svg xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="#f59e0b" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0 }} title="Login required">
		<rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" />
	</svg>
);

function resolveTypeName(v) {
	if (!v) return '-';
	const refToName = (r) => r.replace('#/components/schemas/', '').split('\\').pop();
	if (v.$ref) return refToName(v.$ref);
	if (v.allOf?.[0]?.$ref) return refToName(v.allOf[0].$ref);
	if (v.type === 'array' && v.items) {
		if (v.items.$ref) return refToName(v.items.$ref) + '[]';
		if (v.items.allOf?.[0]?.$ref) return refToName(v.items.allOf[0].$ref) + '[]';
		return (v.items.type || 'any') + '[]';
	}
	return v.type || '-';
}

function resolveRefSchema(v, schemas) {
	if (!v || !schemas) return null;
	const getRef = (r) => r.replace('#/components/schemas/', '');
	let refKey = null;
	if (v.$ref) refKey = getRef(v.$ref);
	else if (v.allOf?.[0]?.$ref) refKey = getRef(v.allOf[0].$ref);
	else if (v.type === 'array' && v.items) {
		if (v.items.$ref) refKey = getRef(v.items.$ref);
		else if (v.items.allOf?.[0]?.$ref) refKey = getRef(v.items.allOf[0].$ref);
		else if (v.items.properties) return v.items;
	}
	if (refKey && schemas[refKey]?.properties) return schemas[refKey];
	if (v.type === 'object' && v.properties) return v;
	return null;
}

function renderNestedProps(items, parentKey, schemas, expanded, depth = 1) {
	const rows = [];
	items.forEach((p, pi) => {
		const key = `${parentKey}-${pi}`;
		const nested = resolveRefSchema(p, schemas);
		const hasChildren = !!(nested?.properties) && depth < 3;
		const isOpen = expanded.has(key);
		rows.push(
			{ key, name: p.name, type: resolveTypeName(p), desc: p.description || '-', depth, hasChildren, isOpen }
		);
		if (hasChildren && isOpen) {
			const children = Object.entries(nested.properties).map(([k, v]) => ({ name: k, ...v }));
			rows.push(...renderNestedProps(children, key, schemas, expanded, depth + 1));
		}
	});
	return rows;
}

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

function refToShortName(refName) {
	return refName.replace('#/components/schemas/', '').split('\\').pop();
}

function schemaToTs(schema, schemas, indent = '\t', refs = new Set()) {
	if (!schema) return 'unknown';
	if (schema.type === 'array' && schema.items) return schemaToTs(schema.items, schemas, indent, refs) + '[]';
	if (schema.$ref) {
		const name = refToShortName(schema.$ref);
		refs.add(schema.$ref.replace('#/components/schemas/', ''));
		return name;
	}
	if (schema.allOf?.[0]?.$ref) {
		const name = refToShortName(schema.allOf[0].$ref);
		refs.add(schema.allOf[0].$ref.replace('#/components/schemas/', ''));
		return name;
	}
	if (schema.properties) {
		const required = schema.required || [];
		const lines = Object.entries(schema.properties).map(([key, prop]) => {
			const opt = required.includes(key) ? '' : '?';
			const type = schemaToTs(prop, schemas, indent + '\t', refs);
			return `${indent}${key}${opt}: ${type};`;
		});
		return `{\n${lines.join('\n')}\n${indent.slice(1)}}`;
	}
	if (schema.additionalProperties) return `Record<string, ${schemaToTs(schema.additionalProperties, schemas, indent, refs)}>`;
	if (schema.enum) return schema.enum.map(e => typeof e === 'string' ? `'${e}'` : e).join(' | ');
	switch (schema.type) {
		case 'string': return 'string';
		case 'integer': case 'number': return 'number';
		case 'boolean': return 'boolean';
		default: return 'unknown';
	}
}

function generateRefType(refKey, schemas, generated = new Set()) {
	if (generated.has(refKey)) return '';
	generated.add(refKey);
	const schema = schemas?.[refKey];
	if (!schema?.properties) return '';
	const name = refKey.split('\\').pop();
	const refs = new Set();
	const required = schema.required || [];
	const lines = Object.entries(schema.properties).map(([key, prop]) => {
		const opt = required.includes(key) ? '' : '?';
		const type = schemaToTs(prop, schemas, '\t', refs);
		return `\t${key}${opt}: ${type};`;
	});
	let result = `type ${name} = {\n${lines.join('\n')}\n};`;
	for (const dep of refs) {
		const depType = generateRefType(dep, schemas, generated);
		if (depType) result += '\n\n' + depType;
	}
	return result;
}

function generateResponseTypes(responses, schemas, operationId) {
	const lines = [];
	const typeName = operationId ? operationId.charAt(0).toUpperCase() + operationId.slice(1) : 'Response';
	const allRefs = new Set();
	Object.entries(responses).forEach(([code, resp]) => {
		const schema = resp.content?.['application/json']?.schema;
		if (!schema) return;
		const suffix = code.startsWith('2') ? '' : `_${code}`;
		const refs = new Set();
		const ts = schemaToTs(schema, schemas, '\t', refs);
		lines.push(`type ${typeName}${suffix} = ${ts};`);
		refs.forEach(r => allRefs.add(r));
	});
	const generated = new Set();
	for (const ref of allRefs) {
		const refType = generateRefType(ref, schemas, generated);
		if (refType) lines.push(refType);
	}
	return lines.join('\n\n');
}

function ResponsesView({ responses, schemas, operationId }) {
	if (!responses) return null;
	const [expanded, setExpanded] = useState(new Set());
	const [showTs, setShowTs] = useState(false);
	const [copied, setCopied] = useState(false);
	const statusColor = (code) => code.startsWith('2') ? '#22c55e' : code.startsWith('4') ? '#f59e0b' : '#ef4444';
	const tsCode = useMemo(() => generateResponseTypes(responses, schemas, operationId), [responses, schemas, operationId]);

	const handleCopy = () => {
		navigator.clipboard.writeText(tsCode).then(() => { setCopied(true); setTimeout(() => setCopied(false), 1500); });
	};

	return (
		<section>
			<div className="d-flex align-items-center gap-2">
				<div className="section-label" style={{ marginBottom: 0 }}>Responses</div>
				{tsCode && <button className="btn btn-link btn-sm p-0" style={{ fontSize: '0.6875rem' }} onClick={() => setShowTs(!showTs)}>{showTs ? 'Schema' : 'TypeScript'}</button>}
			</div>
			{showTs ? (
				<div className="mt-2" style={{ position: 'relative' }}>
					<button className="btn btn-sm" style={{ position: 'absolute', top: 8, right: 8, fontSize: '0.6875rem', color: '#94a3b8', background: 'transparent', border: '1px solid #475569', borderRadius: 4, padding: '2px 8px' }} onClick={handleCopy}>{copied ? 'Copied!' : 'Copy'}</button>
					<pre className="code-block p-3 mb-0" style={{ borderRadius: '0.5rem' }}>{tsCode}</pre>
				</div>
			) : (
				<div className="param-grid mt-2" style={{ border: '1px solid #e2e8f0', borderRadius: '0.5rem', overflow: 'hidden' }}>
					{Object.entries(responses).flatMap(([code, resp], idx) => {
						const hasSchema = !!resp.content?.['application/json']?.schema;
						const props = hasSchema ? resp.content['application/json'].schema : null;
						const properties = props?.properties ? Object.entries(props.properties).map(([k, v]) => ({ name: k, ...v, required: (props.required || []).includes(k) })) : null;
						const items = [];
						items.push(
							<div key={`h-${code}`} className="resp-header" style={{ gridColumn: '1 / -1', borderTop: idx > 0 ? '1px solid #e2e8f0' : 'none' }}>
								<span style={{ width: 8, height: 8, borderRadius: '50%', background: statusColor(code), flexShrink: 0 }} />
								<span className="param-name" style={{ minWidth: 'auto' }}>{code}</span>
								<span className="param-desc" style={{ flex: 1 }}>{resp.description}</span>
							</div>
						);
						if (properties) {
							const toggle = (key) => setExpanded(prev => { const next = new Set(prev); next.has(key) ? next.delete(key) : next.add(key); return next; });
							const allRows = renderNestedProps(properties, `p-${code}`, schemas, expanded);
							allRows.forEach(r => {
								const indent = r.depth * 1.25;
								const isNested = r.depth > 1;
								items.push(
									<div key={r.key} className="param-row" style={r.hasChildren ? { cursor: 'pointer' } : {}} onClick={r.hasChildren ? () => toggle(r.key) : undefined}>
										<span className="param-name" style={{ paddingLeft: `${indent}rem`, ...(isNested ? { color: '#64748b', fontWeight: 400 } : {}) }}>
											{r.hasChildren && <span style={{ display: 'inline-block', width: 12, fontSize: '0.625rem', color: '#94a3b8' }}>{r.isOpen ? '▼' : '▶'}</span>}
											{r.name}
										</span>
										<span className="param-type">{r.type}</span>
										<span className="param-desc" style={isNested ? { color: '#94a3b8' } : {}}>{r.desc}</span>
									</div>
								);
							});
						}
						return items;
					})}
				</div>
			)}
		</section>
	);
}

function syntaxHighlight(json) {
	if (typeof json !== 'string') json = JSON.stringify(json, null, 2);
	return json.replace(/("(\\u[\da-fA-F]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+-]?\d+)?)/g, (match) => {
		let cls = 'json-number';
		if (/^"/.test(match)) { cls = /:$/.test(match) ? 'json-key' : 'json-string'; }
		else if (/true|false/.test(match)) cls = 'json-bool';
		else if (/null/.test(match)) cls = 'json-null';
		return `<span class="${cls}">${match}</span>`;
	});
}

function TryItPanel({ endpoint, op, envelope }) {
	const [params, setParams] = useState({});
	const [response, setResponse] = useState(null);
	const [loading, setLoading] = useState(false);
	const allParams = op.parameters || [];

	const execute = async () => {
		setLoading(true); setResponse(null);
		try {
			let url = endpoint.path;
			const method = endpoint.method.toUpperCase();
			const isBodyMethod = ['POST', 'PUT', 'PATCH'].includes(method);
			allParams.filter(p => p.in === 'path').forEach(p => { url = url.replace(`{${p.name}}`, encodeURIComponent(params[p.name] || '')); });
			const nonPathParams = allParams.filter(p => p.in !== 'path' && params[p.name]);
			if (!isBodyMethod) {
				const qp = nonPathParams.map(p => `${p.name}=${encodeURIComponent(params[p.name])}`);
				if (qp.length) url += '?' + qp.join('&');
			}
			const baseUrl = window.location.pathname.replace(/\/dt\/?$/, '');
			const accept = envelope ? 'application/json' : 'application/json; envelope=false';
			const opts = { method, headers: { 'Accept': accept } };
			if (isBodyMethod && nonPathParams.length) {
				const jsonBody = {};
				nonPathParams.forEach(p => { jsonBody[p.name] = params[p.name]; });
				opts.body = JSON.stringify(jsonBody);
				opts.headers['Content-Type'] = 'application/json';
			}
			const start = Date.now();
			const res = await fetch(baseUrl + url, opts);
			const time = Date.now() - start;
			const text = await res.text();
			let json = null; try { json = JSON.parse(text); } catch {}
			setResponse({ status: res.status, time, body: json || text });
		} catch (e) { setResponse({ error: e.message }); }
		setLoading(false);
	};

	const dotClass = response && !('error' in response) ? (response.status < 300 ? 'status-dot-ok' : response.status < 400 ? 'status-dot-warn' : 'status-dot-err') : '';

	return (
		<div className="try-it-section">
			<div className="section-label">Try It</div>
			{allParams.length > 0 && <div className="mb-3">
				{allParams.map(p => (
					<div key={p.name} className="d-flex align-items-center gap-2 mb-2">
						<label className="param-name" style={{ minWidth: 120 }}>{p.name}{p.required && <span className="text-danger ms-1">*</span>}</label>
						{p.schema?.type === 'boolean' ? (
							<select className="try-input" value={params[p.name] || ''} onChange={e => setParams(prev => ({ ...prev, [p.name]: e.target.value }))}>
								<option value="">-</option>
								<option value="true">true</option>
								<option value="false">false</option>
							</select>
						) : (
							<input type="text" className="try-input" value={params[p.name] || ''} onChange={e => setParams(prev => ({ ...prev, [p.name]: e.target.value }))} placeholder={p.schema?.type || 'value'} />
						)}
					</div>
				))}
			</div>}
			<button className="btn btn-primary btn-sm px-4" onClick={execute} disabled={loading}>{loading ? 'Sending...' : 'Execute'}</button>
			{response && <div className="mt-3">
				{'error' in response ? <div className="alert alert-danger mb-0 py-2 small">Error: {response.error}</div> : <>
					<div className="response-header">
						<span className={`status-dot ${dotClass}`} />
						<span style={{ color: '#e2e8f0', fontSize: '0.8125rem', fontWeight: 600 }}>{response.status}</span>
						<span style={{ color: '#94a3b8', fontSize: '0.75rem' }}>{response.time}ms</span>
					</div>
					<pre className="code-block mb-0 p-3" dangerouslySetInnerHTML={{ __html: typeof response.body === 'object' ? syntaxHighlight(response.body) : (response.body || '') }} />
				</>}
			</div>}
		</div>
	);
}

function findEndpointByPath(seePath) {
	const paths = spec.paths || {};
	const normalized = '/' + seePath.replace(/^\//, '');
	// パス完全一致
	for (const [path, methods] of Object.entries(paths)) {
		if (path === normalized) {
			const method = Object.keys(methods)[0];
			return { path, method: method.toUpperCase(), op: methods[method] };
		}
	}
	// operationId（name）一致
	const name = seePath.replace(/^\//, '');
	for (const [path, methods] of Object.entries(paths)) {
		for (const [method, op] of Object.entries(methods)) {
			if (op.operationId === name) {
				return { path, method: method.toUpperCase(), op };
			}
		}
	}
	// 末尾セグメント一致 (例: "auth_token" → "/member/auth_token")
	for (const [path, methods] of Object.entries(paths)) {
		if (path.endsWith('/' + name)) {
			const method = Object.keys(methods)[0];
			return { path, method: method.toUpperCase(), op: methods[method] };
		}
	}
	return null;
}

function SeeLinks({ seeList, onNavigate, label = 'See:' }) {
	if (!seeList || seeList.length === 0) return null;
	return (
		<div className="d-flex align-items-center gap-2 mt-2 flex-wrap">
			<span style={{ fontSize: '0.75rem', color: '#94a3b8', fontWeight: 500 }}>{label}</span>
			{seeList.map((see, i) => {
				if (see.type === 'url') {
					return <a key={i} href={see.url} target="_blank" rel="noopener noreferrer" style={{ fontSize: '0.75rem' }}>{see.url}</a>;
				}
				if (see.type === 'endpoint') {
					const target = findEndpointByPath(see.path);
					if (target && onNavigate) {
						return <a key={i} href="#" onClick={e => { e.preventDefault(); onNavigate(target); }} style={{ fontSize: '0.75rem', color: '#3b82f6', cursor: 'pointer', textDecoration: 'underline' }}>{see.path}</a>;
					}
					return <span key={i} style={{ fontSize: '0.75rem', color: '#64748b' }}>{see.path}</span>;
				}
				if (see.type === 'method') {
					return <span key={i} style={{ fontSize: '0.75rem', color: '#64748b' }}>{see.class}::{see.method}</span>;
				}
				if (see.type === 'class') {
					const target = findEndpointByPath(see.class);
					if (target && onNavigate) {
						return <a key={i} href="#" onClick={e => { e.preventDefault(); onNavigate(target); }} style={{ fontSize: '0.75rem', color: '#3b82f6', cursor: 'pointer', textDecoration: 'underline' }}>{see.class}</a>;
					}
					return <span key={i} style={{ fontSize: '0.75rem', color: '#64748b' }}>{see.class}</span>;
				}
				return null;
			})}
		</div>
	);
}

function EndpointModal({ endpoint, schemas, envelope, onClose, onNavigate = null }) {
	const [showTry, setShowTry] = useState(false);
	const op = endpoint.op;
	const method = endpoint.method.toUpperCase();
	const methodBg = { GET: '#0d6efd', POST: '#198754', PUT: '#fd7e14', DELETE: '#dc3545', PATCH: '#20c997' }[method] || '#6c757d';

	return (
		<div className="modal-backdrop-custom" onClick={onClose}>
			<div className="modal-panel" onClick={e => e.stopPropagation()}>
				<div className="modal-panel-header">
					<div className="d-flex align-items-center justify-content-between">
						<div className="d-flex align-items-center gap-3">
							<span className={`method-badge ${methodColors[method] || methodColors[endpoint.method]}`}>{method}</span>
							<code style={{ fontSize: '1.1rem', color: '#1e293b' }}>{endpoint.path}</code>
						</div>
						<button type="button" className="btn-close" onClick={onClose} />
					</div>
					{(op.summary || op.description) && <div className="mt-2">
						{op.summary && <div style={{ fontSize: '0.9375rem', color: '#334155', fontWeight: 500 }}>{op.summary}</div>}
						{op.description && <div style={{ fontSize: '0.8125rem', color: '#64748b', whiteSpace: 'pre-wrap', marginTop: 4 }}>{op.description}</div>}
					</div>}
					<div className="d-flex align-items-center gap-2 mt-2">
						{op.tags?.map(t => <span key={t} className="badge" style={{ background: '#e2e8f0', color: '#475569', fontWeight: 500 }}>{t}</span>)}
						{op.security?.length > 0 && <LockIcon />}
						{op['x-mode'] === '@dev' && <span className="badge bg-warning text-dark" style={{ fontSize: '0.6rem' }}>DEV</span>}
						{op.deprecated && <span className="badge bg-danger">deprecated</span>}
						{op.operationId && <code style={{ fontSize: '0.6875rem', color: '#94a3b8', marginLeft: 'auto' }}>{op.operationId}</code>}
					</div>
					<SeeLinks seeList={op['x-see']} onNavigate={onNavigate} />
					{op['x-deprecated-see'] && <SeeLinks seeList={[op['x-deprecated-see']]} onNavigate={onNavigate} label="Deprecated, see:" />}
				</div>
				<div className="modal-panel-body">
					{op.parameters && op.parameters.length > 0 && <section>
						<div className="section-label">Parameters</div>
						<div className="param-grid" style={{ border: '1px solid #e2e8f0', borderRadius: '0.5rem', overflow: 'hidden' }}>
							{op.parameters.map((p, i) => (
								<div key={i} className="param-row">
									<span className="param-name">{p.name}{p.required && <span className="text-danger ms-1">*</span>}</span>
									<span className="param-type">{p.schema?.type || p.type || '-'}</span>
									<span className="param-desc">{p.description || '-'}</span>
								</div>
							))}
						</div>
					</section>}
					{op.requestBody?.content && <section>
						<div className="section-label">Request Body</div>
						<div className="card border-0" style={{ background: '#f8fafc' }}>
							<div className="card-body py-2">{Object.entries(op.requestBody.content).map(([ct, c]) => <div key={ct}>{c.schema && <SchemaView schema={c.schema} schemas={schemas} />}</div>)}</div>
						</div>
					</section>}
					<ResponsesView responses={op.responses} schemas={schemas} operationId={op.operationId} />
					<section>
						<button className={`btn btn-sm px-4 ${showTry ? 'btn-outline-danger' : 'btn-dark'}`} onClick={() => setShowTry(!showTry)}>{showTry ? 'Close' : 'Try It'}</button>
						{showTry && <div className="mt-3"><TryItPanel endpoint={endpoint} op={op} envelope={envelope} /></div>}
					</section>
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
			for (const [method, op] of Object.entries(methods)) {
				if ((op.tags || []).includes('Dt')) continue;
				result.push({ method: method.toUpperCase(), path, op });
			}
		}
		return result;
	}, []);
	const tags = useMemo(() => (spec.tags || []).filter(t => t.name !== 'Dt').map(t => ({ name: t.name, label: t['x-displayName'] || t.name })), []);
	const filtered = endpoints.filter(e => {
		const s = search.toLowerCase();
		return (!s || e.path.toLowerCase().includes(s) || (e.op.summary || '').toLowerCase().includes(s)) && (!tagFilter || (e.op.tags || []).includes(tagFilter));
	});
	const normalFiltered = filtered.filter(e => e.op['x-mode'] !== '@dev');
	const devFiltered = filtered.filter(e => e.op['x-mode'] === '@dev');
	const grouped = normalFiltered.reduce((acc, e) => { const tag = e.op.tags?.[0] || 'Other'; if (!acc[tag]) acc[tag] = []; acc[tag].push(e); return acc; }, {});
	const devGrouped = devFiltered.reduce((acc, e) => { const tag = e.op.tags?.[0] || 'Other'; if (!acc[tag]) acc[tag] = []; acc[tag].push(e); return acc; }, {});
	const methodOrder = (e) => { if (e.op.deprecated) return 3; if (e.method === 'GET') return 0; if (e.method === 'POST') return 1; return 2; };
	Object.values(grouped).forEach(items => items.sort((a, b) => methodOrder(a) - methodOrder(b)));
	Object.values(devGrouped).forEach(items => items.sort((a, b) => methodOrder(a) - methodOrder(b)));

	const EndpointRow = ({ e }) => (
		<div className={`list-group-item endpoint-row d-flex align-items-center gap-3 ${e.op.deprecated ? 'opacity-50' : ''}`} onClick={() => onSelect(e)}>
			<span className={`method-badge ${methodColors[e.method]}`}>{e.method}</span>
			<code className="flex-grow-1">{e.path}</code>
			<span className="text-muted small" style={{ whiteSpace: 'normal' }}>{e.op.summary}</span>
			{e.op.security?.length > 0 && <LockIcon />}
			{e.op.deprecated && <span className="badge bg-danger">deprecated</span>}
		</div>
	);

	return (
		<div>
			<div className="mb-4">
				<h1 className="h3">{spec.info?.title || 'API'} <span className="text-muted fw-normal fs-6">({filtered.length}/{endpoints.length})</span></h1>
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
						{items.map((e, i) => <EndpointRow key={i} e={e} />)}
					</div>
				</div>
			))}
			{Object.keys(devGrouped).length > 0 && <>
				<h2 className="h5 mt-5 mb-3 d-flex align-items-center gap-2">
					<span className="badge bg-warning text-dark">DEV</span> Development Endpoints
					<span className="text-muted fw-normal fs-6">({devFiltered.length})</span>
				</h2>
				{Object.entries(devGrouped).map(([tag, items]) => (
					<div key={`dev-${tag}`} className="card mb-4" style={{ borderColor: '#ffc107' }}>
						<div className="card-header fw-semibold" style={{ background: '#fff8e1' }}>{tags.find(t => t.name === tag)?.label || tag}</div>
						<div className="list-group list-group-flush">
							{items.map((e, i) => <EndpointRow key={i} e={e} />)}
						</div>
					</div>
				))}
			</>}
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
				<div className="modal-backdrop-custom" onClick={onClose}>
					<div className="modal-panel" onClick={e => e.stopPropagation()}>
						<div className="modal-panel-header">
							<div className="d-flex align-items-center justify-content-between">
								<div className="d-flex align-items-center gap-3">
									<code style={{ fontSize: '1.1rem', color: '#1e293b' }}>{selected.name}</code>
									{selected.schema['x-dao'] ? <span className="badge bg-info">Dao</span> : <span className="badge bg-secondary">Obj</span>}
								</div>
								<button type="button" className="btn-close" onClick={onClose} />
							</div>
							{selected.schema.description && <div className="mt-2" style={{ fontSize: '0.8125rem', color: '#64748b', whiteSpace: 'pre-wrap' }}>{selected.schema.description}</div>}
						</div>
						<div className="modal-panel-body">
							{selected.schema['x-table'] && <section>
								<div className="section-label">Table</div>
								<code style={{ fontSize: '0.875rem' }}>{selected.schema['x-table']}</code>
								{selected.schema['x-joins']?.length > 0 && <div className="mt-1" style={{ fontSize: '0.75rem', color: '#64748b' }}>Joins: {selected.schema['x-joins'].map((t, i) => <code key={i} style={{ fontSize: '0.75rem', marginRight: 4 }}>{t}</code>)}</div>}
							</section>}
							{selected.schema.properties && <section>
								<div className="section-label">Properties</div>
								<div className="param-grid" style={{ border: '1px solid #e2e8f0', borderRadius: '0.5rem', overflow: 'hidden' }}>
									{Object.entries(selected.schema.properties).map(([k, v], i) => {
										const isRequired = (selected.schema.required || []).includes(k);
										return (
											<div key={k} className="param-row">
												<span className="param-name">{k}{isRequired && <span className="text-danger ms-1">*</span>}</span>
												<span className="param-type">{resolveTypeName(v)}</span>
												<span className="param-desc">{v['x-join'] && <span className="badge" style={{ background: '#ede9fe', color: '#7c3aed', fontWeight: 500, fontSize: '0.625rem', marginRight: 4 }}>{v['x-join']}</span>}{v.description || (v['x-join'] ? '' : '-')}</span>
											</div>
										);
									})}
								</div>
							</section>}
						</div>
					</div>
				</div>
			)}
		</div>
	);
}

function PaginationNav({ pagination, onPageChange }) {
	if (!pagination || pagination.pages <= 1) return null;
	const current = pagination.current;
	const last = pagination.pages;
	const delta = 3;
	let start = Math.max(1, current - delta);
	let end = Math.min(last, current + delta);
	if (current - delta < 1) end = Math.min(last, end + (delta - current + 1));
	if (current + delta > last) start = Math.max(1, start - (current + delta - last));
	const range = [];
	for (let i = start; i <= end; i++) range.push(i);

	return (
		<nav>
			<ul className="pagination pagination-sm mb-0">
				<li className={`page-item ${current <= 1 ? 'disabled' : ''}`}><button className="page-link" onClick={() => onPageChange(current - 1)}>Prev</button></li>
				{start > 1 && <><li className="page-item"><button className="page-link" onClick={() => onPageChange(1)}>1</button></li>{start > 2 && <li className="page-item disabled"><span className="page-link">...</span></li>}</>}
				{range.map(p => <li key={p} className={`page-item ${p === current ? 'active' : ''}`}><button className="page-link" onClick={() => onPageChange(p)}>{p}</button></li>)}
				{end < last && <>{end < last - 1 && <li className="page-item disabled"><span className="page-link">...</span></li>}<li className="page-item"><button className="page-link" onClick={() => onPageChange(last)}>{last}</button></li></>}
				<li className={`page-item ${current >= last ? 'disabled' : ''}`}><button className="page-link" onClick={() => onPageChange(current + 1)}>Next</button></li>
			</ul>
		</nav>
	);
}

function MailPage() {
	const [tab, setTab] = useState(hasSmtpBlackhole ? 'sent' : 'templates');
	const [sentMails, setSentMails] = useState([]);
	const [pagination, setPagination] = useState(null);
	const [loadingSent, setLoadingSent] = useState(true);
	const [selectedMail, setSelectedMail] = useState(null);
	const [filterTcode, setFilterTcode] = useState('');
	const [filterText, setFilterText] = useState('');
	const [debouncedText, setDebouncedText] = useState('');
	const [page, setPage] = useState(1);
	const debounceRef = useRef(null);

	const handleFilterText = (v) => {
		setFilterText(v);
		if (debounceRef.current) clearTimeout(debounceRef.current);
		debounceRef.current = setTimeout(() => { setDebouncedText(v); setPage(1); }, 300);
	};

	const fetchMails = (p, tcode, search) => {
		setLoadingSent(true);
		const params = new URLSearchParams({ page: p, paginate_by: 20 });
		if (tcode) params.set('tcode', tcode);
		if (search) params.set('search', search);
		fetch(`${apiUrls.sent_mails}?${params}`)
			.then(res => res.json())
			.then(data => { setSentMails(data.mails || []); setPagination(data.pagination || null); setLoadingSent(false); })
			.catch(() => setLoadingSent(false));
	};

	useEffect(() => { if (hasSmtpBlackhole) fetchMails(page, filterTcode, debouncedText); }, [page, filterTcode, debouncedText]);

	const templateOptions = useMemo(() => {
		return mailTemplates.filter(t => t.code).map(t => ({ code: t.code, label: `${t.code} - ${t.summary || t.subject || t.name}` }));
	}, []);

	const handlePageChange = (p) => { setPage(p); };
	const handleFilterTcode = (v) => { setFilterTcode(v); setPage(1); };
	const openSentWithTcode = (tcode) => { setFilterTcode(tcode); setFilterText(''); setDebouncedText(''); setPage(1); setTab('sent'); };

	return (
		<div>
			<h1 className="h3 mb-4">Mail</h1>
			{hasSmtpBlackhole && <ul className="nav nav-tabs mb-4">
				<li className="nav-item"><button className={`nav-link ${tab === 'sent' ? 'active' : ''}`} onClick={() => setTab('sent')}>Sent Mails</button></li>
				<li className="nav-item"><button className={`nav-link ${tab === 'templates' ? 'active' : ''}`} onClick={() => setTab('templates')}>Templates</button></li>
			</ul>}

			{tab === 'sent' && (
				<div>
					{loadingSent && sentMails.length === 0 ? <div className="text-center py-4"><div className="spinner-border text-primary" /></div> : sentMails.length === 0 && !loadingSent ? <div className="alert alert-info">No sent mails found. (SmtpBlackholeDao)</div> : (
						<>
							<div className="row mb-3 g-2 align-items-center">
								<div className="col-md-4">
									<select className="form-select form-select-sm" value={filterTcode} onChange={e => handleFilterTcode(e.target.value)}>
										<option value="">All Templates</option>
										{templateOptions.map(t => <option key={t.code} value={t.code}>{t.label}</option>)}
									</select>
								</div>
								<div className="col-md-5">
									<input type="text" className="form-control form-control-sm" placeholder="Search to, from, subject..." value={filterText} onChange={e => handleFilterText(e.target.value)} />
								</div>
								<div className="col-md-3 d-flex justify-content-end align-items-center">
									{pagination && <span className="text-muted small">{pagination.total} mails</span>}
								</div>
							</div>
							<div className="card">
								<table className="table table-hover mb-0" style={{fontSize:'0.875rem'}}>
									<thead className="table-light"><tr><th style={{width:'140px'}}>Date</th><th style={{width:'180px'}}>To</th><th>Subject</th><th style={{width:'90px'}}>Code</th></tr></thead>
									<tbody>{sentMails.map((m, i) => (
										<tr key={i} className="endpoint-row" onClick={() => setSelectedMail(m)}>
											<td className="text-muted text-nowrap">{m.create_date}</td>
											<td className="text-truncate text-nowrap" style={{maxWidth:'180px'}} title={m.to}><code className="text-primary small">{m.to}</code></td>
											<td>{m.subject}</td>
											<td>{m.tcode && <span className="badge bg-secondary">{m.tcode}</span>}</td>
										</tr>
									))}</tbody>
								</table>
							</div>
							{pagination && pagination.pages > 1 && (
								<div className="d-flex justify-content-center mt-3">
									<PaginationNav pagination={pagination} onPageChange={handlePageChange} />
								</div>
							)}
						</>
					)}
				</div>
			)}

			{tab === 'templates' && (
				mailTemplates.length === 0 ? <div className="text-muted">No mail templates.</div> : (
					<div className="card">
						<table className="table table-hover mb-0" style={{fontSize:'0.875rem'}}>
							<thead className="table-light"><tr><th>Name</th><th>Code</th><th>Summary</th></tr></thead>
							<tbody>{mailTemplates.map((t, i) => (
								<tr key={i} className={hasSmtpBlackhole ? 'endpoint-row' : ''} onClick={hasSmtpBlackhole ? () => openSentWithTcode(t.code) : undefined}>
									<td className="fw-medium">{t.name}</td>
									<td><code className="bg-light px-2 py-1 rounded">{t.code}</code></td>
									<td className="text-muted">{t.summary || t.subject}</td>
								</tr>
							))}</tbody>
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

function WebhooksPage() {
	const [search, setSearch] = useState('');
	const [tagFilter, setTagFilter] = useState('');
	const [selected, setSelected] = useState(null);

	const allTags = useMemo(() => allTagDefs.map(t => ({ name: t.name, label: t['x-displayName'] || t.name })), []);
	const tags = useMemo(() => {
		const tagSet = new Set();
		webhooks.forEach(w => { const tag = w.op?.tags?.[0]; if (tag) tagSet.add(tag); });
		return allTags.filter(t => tagSet.has(t.name));
	}, [allTags]);

	const filtered = webhooks.filter(w => {
		const s = search.toLowerCase();
		const tag = w.op?.tags?.[0] || '';
		return (!s || w.path.toLowerCase().includes(s) || (w.op?.summary || '').toLowerCase().includes(s)) && (!tagFilter || tag === tagFilter);
	});

	const grouped = filtered.reduce((acc, w) => { const tag = w.op?.tags?.[0] || 'Other'; if (!acc[tag]) acc[tag] = []; acc[tag].push(w); return acc; }, {});

	return (
		<div>
			<h1 className="h3 mb-4">Webhooks <span className="badge bg-secondary fw-normal" style={{ fontSize: '0.65rem', verticalAlign: 'middle' }}>S2S</span> <span className="text-muted fw-normal fs-6">({filtered.length}/{webhooks.length})</span></h1>
			<div className="row g-3 mb-4">
				<div className="col-md-8"><input type="text" className="form-control" placeholder="Search webhooks..." value={search} onChange={e => setSearch(e.target.value)} /></div>
				<div className="col-md-4"><select className="form-select" value={tagFilter} onChange={e => setTagFilter(e.target.value)}><option value="">All Tags</option>{tags.map(t => <option key={t.name} value={t.name}>{t.label}</option>)}</select></div>
			</div>
			{Object.entries(grouped).map(([tag, items]) => (
				<div key={tag} className="card mb-4">
					<div className="card-header fw-semibold">{allTags.find(t => t.name === tag)?.label || tag}</div>
					<div className="list-group list-group-flush">
						{items.map((w, i) => (
							<div key={i} className={`list-group-item endpoint-row d-flex align-items-center gap-3 ${w.op?.deprecated ? 'opacity-50' : ''}`} onClick={() => setSelected(w)}>
								<span className={`method-badge ${methodColors[w.method] || ''}`}>{w.method}</span>
								<code className="flex-grow-1">{w.path}</code>
								<span className="text-muted small" style={{ whiteSpace: 'normal' }}>{w.op?.summary}</span>
								{w.op?.security?.length > 0 && <LockIcon />}
								{w.op?.deprecated && <span className="badge bg-danger">deprecated</span>}
							</div>
						))}
					</div>
				</div>
			))}
			{filtered.length === 0 && <div className="alert alert-info">No webhooks found.</div>}
			{selected && <EndpointModal endpoint={selected} schemas={spec.components?.schemas || {}} envelope={false} onClose={() => setSelected(null)} onNavigate={setSelected} />}
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
	const [envelope, setEnvelope] = useState(true);

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
					<span className="navbar-brand fw-bold">DevTools</span>{appmode && <span className="badge bg-info text-dark ms-2">{appmode}</span>}
					<div className="navbar-nav me-auto flex-row gap-2">
						<button className={`nav-link btn btn-link ${page === 'endpoints' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('endpoints')}>Endpoints</button>
						{webhooks.length > 0 && <button className={`nav-link btn btn-link ${page === 'webhooks' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('webhooks')}>Webhooks</button>}
						<button className={`nav-link btn btn-link ${page === 'schemas' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('schemas')}>Schemas</button>
						<button className={`nav-link btn btn-link ${page === 'config' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('config')}>Config</button>
						{mailTemplates.length > 0 && <button className={`nav-link btn btn-link ${page === 'mail' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('mail')}>Mail</button>}
					</div>
					<label className="d-flex align-items-center gap-1 me-3" style={{ fontSize: '0.75rem', color: '#64748b', cursor: 'pointer', userSelect: 'none' }}>
						<input type="checkbox" checked={envelope} onChange={e => setEnvelope(e.target.checked)} style={{ accentColor: '#3b82f6' }} />
						envelope
					</label>
					<span className="hint-wrap me-3">
						<span className="hint-icon">?</span>
						<span className="hint-popup">
							ON: レスポンスを {"{ result: {...} }"} でラップ<br />
							OFF: フラットなJSONを返す<br />
							<span style={{ color: '#94a3b8', fontSize: '0.625rem' }}>Accept: application/json; envelope=false と同等</span>
						</span>
					</span>
					<a href={apiUrls.redoc + (envelope ? '?envelope=true' : '')} className="btn btn-outline-secondary btn-sm me-2">Redoc</a><a href={apiUrls.openapi + (envelope ? '?envelope=true' : '')} download="openapi.json" className="btn btn-outline-primary btn-sm">OpenAPI JSON</a>
				</div>
			</nav>
			<main className="container py-4">
				{page === 'endpoints' && <Endpoints onSelect={handleSelectEndpoint} />}
				{page === 'webhooks' && <WebhooksPage />}
				{page === 'schemas' && <Schemas selected={selectedSchema} onSelect={handleSelectSchema} onClose={handleCloseSchema} />}
				{page === 'config' && <ConfigPage />}
				{page === 'mail' && <MailPage />}
			</main>
			{selected && <EndpointModal endpoint={selected} schemas={spec.components?.schemas || {}} envelope={envelope} onClose={handleCloseEndpoint} onNavigate={handleSelectEndpoint} />}
		</div>
	);
}

export default App;
