// @ts-nocheck
import { useState, useMemo, useEffect, useRef, useCallback } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import remarkBreaks from 'remark-breaks';
import './index.css';

// PHPDoc を markdown に正規化する。
//   1. 行頭の "* " (PHPDoc 残り) を剥がす
//   2. 2 スペース以上で字下げされた連続行は code block にまとめる
//      (factory.php docblock のように "URL 体系:\n  service/* ... " 風の構造を
//      コードブロックで等幅整形して表示するため)
//   3. プレーン文中の URL を markdown リンク化
//   4. (描画側で remark-breaks により単一改行はハードブレーク扱い)
function phpdocToMarkdown(src) {
	const lines = String(src).split('\n').map(l => l.replace(/^\s*\*\s?/, ''));
	const out = [];
	let buf = []; // 連続する字下げ行

	const flushCode = () => {
		if (buf.length === 0) return;
		const minIndent = Math.min(...buf.map(l => l.match(/^ */)[0].length));
		out.push('```');
		for (const l of buf) out.push(l.slice(minIndent));
		out.push('```');
		buf = [];
	};
	const linkify = (s) => s.replace(/(https?:\/\/[^\s]+)/g, '[$1]($1)');

	for (const line of lines) {
		if (/^ {2,}\S/.test(line)) {        // 2 スペース以上で始まる非空行 → code 候補
			buf.push(line);
		} else {
			flushCode();
			out.push(linkify(line));
		}
	}
	flushCode();
	return out.join('\n');
}

function MarkdownText({ children, className = '', style }) {
	if (!children) return null;
	const text = phpdocToMarkdown(children);
	return (
		<div className={`md-body ${className}`} style={style}>
			<ReactMarkdown remarkPlugins={[remarkGfm, remarkBreaks]}>{text}</ReactMarkdown>
		</div>
	);
}

// PHPから渡されるグローバル変数
let spec = window.spec || {};
let webhooks = window.webhooks || [];
let allTagDefs = window.allTags || [];
let mailTemplates = window.mailTemplates || [];
const apiUrls = window.apiUrls || {};
const hasSmtpBlackhole = !!window.hasSmtpBlackhole;
const appmode = window.appmode || '';
const initialAuthenticated = !!window.authenticated;
const requiresPassword = !!window.requiresPassword;
const initialLoginError = !!window.loginError;
const mcpEnabled = !!window.mcpEnabled;

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
	if (v.type === 'object' && v.additionalProperties) {
		const valType = v.additionalProperties.$ref ? refToName(v.additionalProperties.$ref) : (v.additionalProperties.type || 'any');
		return `array<string, ${valType}>`;
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
			{ key, name: p.name, type: resolveTypeName(p), desc: p.description || '-', deprecated: !!p.deprecated, depth, hasChildren, isOpen }
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

function ResponsesView({ responses, schemas, operationId, envelope = false }) {
	if (!responses) return null;
	const [expanded, setExpanded] = useState(new Set());
	const [showTs, setShowTs] = useState(false);
	const [copied, setCopied] = useState(false);
	const statusColor = (code) => code.startsWith('2') ? '#22c55e' : code.startsWith('4') ? '#f59e0b' : '#ef4444';
	const tsCode = useMemo(() => generateResponseTypes(responses, schemas, operationId), [responses, schemas, operationId]);

	// envelope時は401以外のエラーステータスを非表示
	const filteredResponses = envelope
		? Object.fromEntries(Object.entries(responses).filter(([code]) => code.startsWith('2') || code === '401'))
		: responses;

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
					{Object.entries(filteredResponses).flatMap(([code, resp], idx) => {
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
									<div key={r.key} className="param-row" style={{ ...(r.hasChildren ? { cursor: 'pointer' } : {}), ...(r.deprecated ? { opacity: 0.5 } : {}) }} onClick={r.hasChildren ? () => toggle(r.key) : undefined}>
										<span className="param-name" style={{ paddingLeft: `${indent}rem`, ...(isNested ? { color: '#64748b', fontWeight: 400 } : {}), ...(r.deprecated ? { textDecoration: 'line-through' } : {}) }}>
											{r.hasChildren && <span style={{ display: 'inline-block', width: 12, fontSize: '0.625rem', color: '#94a3b8' }}>{r.isOpen ? '▼' : '▶'}</span>}
											{r.name}
										</span>
										<span className="param-type" style={r.deprecated ? { textDecoration: 'line-through' } : {}}>{r.type}</span>
										<span className="param-desc" style={{ ...(isNested ? { color: '#94a3b8' } : {}), ...(r.deprecated ? { textDecoration: 'line-through' } : {}) }}>{r.desc}</span>
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
	const itemStyle = { fontSize: '0.75rem' };
	return (
		<div className="mt-2">
			<div style={{ fontSize: '0.75rem', color: '#94a3b8', fontWeight: 500, marginBottom: 2 }}>{label}</div>
			<ul className="see-list" style={{ listStyle: 'none', paddingLeft: 0, margin: 0 }}>
				{seeList.map((see, i) => {
					let inner = null;
					if (see.type === 'url') {
						inner = <a href={see.url} target="_blank" rel="noopener noreferrer" style={itemStyle}>{see.url}</a>;
					} else if (see.type === 'endpoint') {
						const target = findEndpointByPath(see.path);
						inner = (target && onNavigate)
							? <a href="#" onClick={e => { e.preventDefault(); onNavigate(target); }} style={{ ...itemStyle, color: '#3b82f6', cursor: 'pointer', textDecoration: 'underline' }}>{see.path}</a>
							: <span style={{ ...itemStyle, color: '#64748b' }}>{see.path}</span>;
					} else if (see.type === 'method') {
						inner = <span style={{ ...itemStyle, color: '#64748b' }}>{see.class}::{see.method}</span>;
					} else if (see.type === 'class') {
						const target = findEndpointByPath(see.class);
						inner = (target && onNavigate)
							? <a href="#" onClick={e => { e.preventDefault(); onNavigate(target); }} style={{ ...itemStyle, color: '#3b82f6', cursor: 'pointer', textDecoration: 'underline' }}>{see.class}</a>
							: <span style={{ ...itemStyle, color: '#64748b' }}>{see.class}</span>;
					}
					return inner !== null ? <li key={i} style={{ margin: '2px 0' }}>{inner}</li> : null;
				})}
			</ul>
		</div>
	);
}

function EndpointContent({ endpoint, schemas, envelope, onClose, onNavigate = null }) {
	const [showTry, setShowTry] = useState(false);
	// 詳細を開く/隣接エンドポイントへ移動したら先頭へスクロール（全画面詳細のため）
	useEffect(() => { window.scrollTo({ top: 0 }); }, [endpoint]);
	const op = endpoint.op;
	// x-flow の生産者/消費者索引（直前=前提を作る / 直後=効果を使う endpoint 導出用）
	const flowNeighbors = useMemo(() => {
		const producers = {}, consumers = {}, byId = {};
		for (const [path, methods] of Object.entries(spec.paths || {})) {
			for (const [m, o] of Object.entries(methods)) {
				if (!o || !o.operationId) continue;
				byId[o.operationId] = { path, method: m.toUpperCase(), op: o };
				const fl = o['x-flow']; if (!fl) continue;
				for (const p of (fl.produces || [])) if (p.token) (producers[p.token] = producers[p.token] || []).push(o.operationId);
				for (const r of (fl.requires || [])) if (r.token) (consumers[r.token] = consumers[r.token] || []).push(o.operationId);
			}
		}
		return { producers, consumers, byId };
	}, [endpoint]);
	const method = endpoint.method.toUpperCase();
	const methodBg = { GET: '#0d6efd', POST: '#198754', PUT: '#fd7e14', DELETE: '#dc3545', PATCH: '#20c997' }[method] || '#6c757d';

	return (
		<>
				<div className="modal-panel-header">
					<div className="d-flex align-items-center justify-content-between">
						<div className="d-flex align-items-center gap-3">
							<span className={`method-badge ${methodColors[method] || methodColors[endpoint.method]}`}>{method}</span>
							<code style={{ fontSize: '1.1rem', color: '#1e293b' }}>{endpoint.path}</code>
						</div>
					</div>
					{(op.summary || op.description) && <div className="mt-2">
						{op.summary && <div style={{ fontSize: '0.9375rem', color: '#334155', fontWeight: 500 }}>{op.summary}</div>}
						{op.description && <MarkdownText style={{ fontSize: '0.8125rem', color: '#64748b', marginTop: 4 }}>{op.description}</MarkdownText>}
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
					{op.requestBody?.content && (() => {
						const bodyProps = Object.entries(op.requestBody.content).flatMap(([, c]) => {
							const s = c.schema;
							if (!s || !s.properties) return [];
							return Object.entries(s.properties).map(([k, v]) => ({ name: k, ...v, required: (s.required || []).includes(k) }));
						});
						return bodyProps.length > 0 ? <section>
							<div className="section-label">Request Body</div>
							<div className="param-grid" style={{ border: '1px solid #e2e8f0', borderRadius: '0.5rem', overflow: 'hidden' }}>
								{bodyProps.map((p, i) => (
									<div key={i} className="param-row">
										<span className="param-name">{p.name}{p.required && <span className="text-danger ms-1">*</span>}</span>
										<span className="param-type">{p.type || '-'}</span>
										<span className="param-desc">{p.description || '-'}</span>
									</div>
								))}
							</div>
						</section> : null;
					})()}
					<ResponsesView responses={op.responses} schemas={schemas} operationId={op.operationId} envelope={envelope} />
					{envelope && op['x-throws'] && op['x-throws'].length > 0 && <section>
						<div className="section-label">Throws <span style={{ fontSize: '0.6875rem', color: '#94a3b8', fontWeight: 400 }}>(HTTP 200)</span></div>
						<div className="mt-2" style={{ border: '1px solid #e2e8f0', borderRadius: '0.5rem', overflow: 'hidden' }}>
							{op['x-throws'].map((t, i) => (
								<div key={i} style={{ display: 'flex', alignItems: 'center', padding: '0.5rem 0.75rem', fontSize: '0.8125rem', gap: '0.75rem', borderTop: i > 0 ? '1px solid #e2e8f0' : 'none', background: i % 2 === 0 ? '#f8fafc' : 'transparent' }}>
									<span style={{ display: 'inline-flex', alignItems: 'center', whiteSpace: 'nowrap', fontFamily: "'SF Mono',SFMono-Regular,Menlo,Monaco,Consolas,monospace", fontWeight: 500, color: '#1e293b' }}>
										<span style={{ display: 'inline-block', minWidth: 28, padding: '1px 6px', borderRadius: 4, fontSize: '0.625rem', fontWeight: 600, textAlign: 'center', background: '#fee2e2', color: '#991b1b', marginRight: 6 }}>200</span>
										{t.exception || ''}
									</span>
									<span style={{ color: '#64748b', flex: 1 }}>{t.description}</span>
								</div>
							))}
						</div>
					</section>}
					<section>
						<button className={`btn btn-sm px-4 ${showTry ? 'btn-outline-danger' : 'btn-dark'}`} onClick={() => setShowTry(!showTry)}>{showTry ? 'Close' : 'Try It'}</button>
						{showTry && <div className="mt-3"><TryItPanel endpoint={endpoint} op={op} envelope={envelope} /></div>}
					</section>
					{op['x-flow'] && ((op['x-flow'].requires || []).length > 0 || (op['x-flow'].produces || []).length > 0) && (() => {
						const reg = spec['x-flow-registry'] || {};
						const fl = op['x-flow'];
						const kindLabel = t => reg[t]?.kind === 'state' ? '状態' : reg[t]?.kind === 'value' ? '値' : reg[t]?.kind === 'ambient' ? '外部由来' : '';
						const selfOid = op.operationId;
						const uniq = a => [...new Set(a)];
						const prev = uniq((fl.requires || []).flatMap(r => flowNeighbors.producers[r.token] || [])).filter(o => o !== selfOid && !flowNeighbors.byId[o]?.op.deprecated);
						const next = uniq((fl.produces || []).flatMap(p => flowNeighbors.consumers[p.token] || [])).filter(o => o !== selfOid && !flowNeighbors.byId[o]?.op.deprecated);
						return <section style={{ borderTop: '1px solid #e2e8f0', marginTop: '1.75rem', paddingTop: '1.25rem' }}>
                        <div className="section-label">Flow <span className="fw-normal text-muted" style={{ fontSize: '0.75rem' }}>— このAPIの前後関係（呼び出しの文脈）</span></div>
                        <div className="row g-4 mt-0" style={{ fontSize: '0.8125rem' }}>
                          <div className="col-lg-6">
                            <div className="text-muted fw-semibold mb-2" style={{ fontSize: '0.75rem' }}>前提 <span className="fw-normal">— 呼ぶ前に成立していること</span></div>
                            {(fl.requires || []).length > 0 ? (
                              <div className="d-flex flex-column gap-2">
                                {fl.requires.map((r, i) => (
                                  <div key={i} className={`flow-io-item${r.optional ? ' flow-io-optional' : ''}`}>
                                    <span className="flow-io-dot" />
                                    <div className="flow-io-body">
                                      <div className="flow-io-summary">{reg[r.token]?.summary || r.token}</div>
                                      <div className="flow-io-meta"><code>{r.token}</code>{kindLabel(r.token) && <span className="flow-io-kind">{kindLabel(r.token)}</span>}{r.optional && <span className="flow-io-tag">任意</span>}{r.bind && <span className="flow-io-tag">bind:{r.bind}</span>}</div>
                                    </div>
                                  </div>
                                ))}
                              </div>
                            ) : <div className="text-muted">なし</div>}
                          </div>
                          <div className="col-lg-6">
                            <div className="text-muted fw-semibold mb-2" style={{ fontSize: '0.75rem' }}>効果 <span className="fw-normal">— このAPIが成立させること</span></div>
                            {(fl.produces || []).length > 0 ? (
                              <div className="d-flex flex-column gap-2">
                                {fl.produces.map((p, i) => (
                                  <div key={i} className="flow-io-item flow-io-produce">
                                    <span className="flow-io-dot" />
                                    <div className="flow-io-body">
                                      <div className="flow-io-summary">{reg[p.token]?.summary || p.token} <a href={`#flow=${encodeURIComponent(p.token)}`} className="flow-io-goto" title="このゴールへの全手順を Flow で見る">→ 手順</a></div>
                                      <div className="flow-io-meta"><code>{p.token}</code>{kindLabel(p.token) && <span className="flow-io-kind">{kindLabel(p.token)}</span>}</div>
                                    </div>
                                  </div>
                                ))}
                              </div>
                            ) : <div className="text-muted">なし</div>}
                          </div>
                        </div>
                        {(prev.length > 0 || next.length > 0) && (
                          <div className="row g-4 mt-1" style={{ fontSize: '0.8125rem' }}>
                            <div className="col-lg-6">
                              <div className="text-muted fw-semibold mb-2" style={{ fontSize: '0.75rem' }} title="この前提を作るAPI（先に呼ぶ）">← 直前に呼べるAPI</div>
                              {prev.length > 0 ? <div className="list-group">{prev.map((oid, i) => { const ep = flowNeighbors.byId[oid]; return (
                            <button key={i} className="list-group-item list-group-item-action d-flex align-items-center gap-2 flow-neighbor" onClick={() => onNavigate && onNavigate(ep)} disabled={!onNavigate}>
                              <span className={`method-badge ${methodColors[ep?.method] || ''}`}>{ep?.method}</span>
                              <code className="flow-neighbor-oid">{oid}</code>
                              {ep?.op.summary && <span className="text-muted text-truncate ms-1" style={{ fontSize: '0.75rem' }}>{ep.op.summary}</span>}
                            </button>); })}</div> : <div className="text-muted">—</div>}
                            </div>
                            <div className="col-lg-6">
                              <div className="text-muted fw-semibold mb-2" style={{ fontSize: '0.75rem' }} title="この効果を使うAPI（後に呼べる）">直後に呼べるAPI →</div>
                              {next.length > 0 ? <div className="list-group">{next.map((oid, i) => { const ep = flowNeighbors.byId[oid]; return (
                            <button key={i} className="list-group-item list-group-item-action d-flex align-items-center gap-2 flow-neighbor" onClick={() => onNavigate && onNavigate(ep)} disabled={!onNavigate}>
                              <span className={`method-badge ${methodColors[ep?.method] || ''}`}>{ep?.method}</span>
                              <code className="flow-neighbor-oid">{oid}</code>
                              {ep?.op.summary && <span className="text-muted text-truncate ms-1" style={{ fontSize: '0.75rem' }}>{ep.op.summary}</span>}
                            </button>); })}</div> : <div className="text-muted">—</div>}
                            </div>
                          </div>
                        )}
                      </section>;
					})()}
				</div>
		</>
	);
}

function EndpointDetail(props) {
	return (
		<div>
			<button className="btn btn-sm btn-link px-0 mb-3 text-decoration-none" onClick={props.onClose}>← 一覧に戻る</button>
			<div className="card">
				<EndpointContent {...props} />
			</div>
		</div>
	);
}

function Endpoints({ onSelect }) {
	const initialQuery = parseHash().query;
	const [search, setSearch] = useState(initialQuery.q || '');
	const [tagFilter, setTagFilter] = useState(initialQuery.tag || '');

	useEffect(() => {
		const { page, detail } = parseHash();
		if (page !== 'endpoints') return;
		const hash = buildHash('endpoints', detail, { q: search, tag: tagFilter });
		window.history.replaceState(null, '', '#' + hash);
	}, [search, tagFilter]);
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
		return (!s || e.path.toLowerCase().includes(s) || (e.op.summary || '').toLowerCase().includes(s) || (e.op.operationId || '').toLowerCase().includes(s)) && (!tagFilter || (e.op.tags || []).includes(tagFilter));
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
				{spec.info?.description && <MarkdownText className="text-muted">{spec.info.description}</MarkdownText>}
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
	const initialQuery = parseHash().query;
	const [search, setSearch] = useState(initialQuery.q || '');
	const [typeFilter, setTypeFilter] = useState(initialQuery.type || '');

	useEffect(() => {
		const { page, detail } = parseHash();
		if (page !== 'schemas') return;
		const hash = buildHash('schemas', detail, { q: search, type: typeFilter });
		window.history.replaceState(null, '', '#' + hash);
	}, [search, typeFilter]);

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

function WebhooksPage({ onSelect }) {
	const initialQuery = parseHash().query;
	const [search, setSearch] = useState(initialQuery.q || '');
	const [tagFilter, setTagFilter] = useState(initialQuery.tag || '');

	useEffect(() => {
		const { page, detail } = parseHash();
		if (page !== 'webhooks') return;
		const hash = buildHash('webhooks', detail, { q: search, tag: tagFilter });
		window.history.replaceState(null, '', '#' + hash);
	}, [search, tagFilter]);

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
							<div key={i} className={`list-group-item endpoint-row d-flex align-items-center gap-3 ${w.op?.deprecated ? 'opacity-50' : ''}`} onClick={() => onSelect(w)}>
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
		</div>
	);
}

function ScannedClassesModal({ onClose }) {
	const [classes, setClasses] = useState([]);
	const [loading, setLoading] = useState(true);
	const [search, setSearch] = useState('');

	useEffect(() => {
		fetch(apiUrls.scanned_classes)
			.then(res => res.json())
			.then(data => { setClasses(data.classes || []); setLoading(false); })
			.catch(() => setLoading(false));
	}, []);

	const filtered = classes.filter(c => {
		const s = search.toLowerCase();
		return !s || c.class.toLowerCase().includes(s) || c.filename.toLowerCase().includes(s);
	});
	const grouped = filtered.reduce((acc, c) => {
		const ns = c.class.substring(0, c.class.lastIndexOf('\\')) || '(root)';
		if (!acc[ns]) acc[ns] = [];
		acc[ns].push(c);
		return acc;
	}, {});

	return (
		<div className="modal-backdrop-custom" onClick={onClose}>
			<div className="modal-panel" style={{ maxWidth: 1000 }} onClick={e => e.stopPropagation()}>
				<div className="modal-panel-header">
					<div className="d-flex align-items-center justify-content-between">
						<div className="d-flex align-items-center gap-2">
							<h5 className="mb-0">Scanned Classes</h5>
							{!loading && <span className="text-muted" style={{ fontSize: '0.8125rem' }}>({filtered.length}/{classes.length})</span>}
						</div>
						<button type="button" className="btn-close" onClick={onClose} />
					</div>
					<div className="mt-2"><input type="text" className="form-control form-control-sm" placeholder="Search classes..." value={search} onChange={e => setSearch(e.target.value)} /></div>
				</div>
				<div className="modal-panel-body" style={{ maxHeight: '70vh', overflowY: 'auto' }}>
					{loading ? <div className="text-center py-4"><div className="spinner-border text-primary" /></div> : Object.keys(grouped).length === 0 ? <div className="text-muted">No classes found.</div> : (
						Object.entries(grouped).map(([ns, items]) => (
							<div key={ns} className="mb-3">
								<div className="fw-semibold small text-muted mb-1">{ns} <span style={{ fontSize: '0.6875rem', color: '#94a3b8' }}>({items.length})</span></div>
								{items.map((c, i) => (
									<div key={i} className="d-flex align-items-center gap-2 py-1 px-2" style={{ fontSize: '0.8125rem', background: i % 2 === 0 ? '#f8fafc' : 'transparent', borderRadius: 4 }}>
										<code className="text-primary" style={{ flex: '0 0 auto' }}>{c.class.substring(c.class.lastIndexOf('\\') + 1)}</code>
										<span className="text-muted text-truncate" style={{ fontSize: '0.6875rem' }} title={c.filename}>{c.filename}</span>
									</div>
								))}
							</div>
						))
					)}
				</div>
			</div>
		</div>
	);
}

function MocksModal({ onClose }) {
	const [mocks, setMocks] = useState([]);
	const [loading, setLoading] = useState(true);

	useEffect(() => {
		fetch(apiUrls.mocks)
			.then(res => res.json())
			.then(data => { setMocks(data.mocks || []); setLoading(false); })
			.catch(() => setLoading(false));
	}, []);

	return (
		<div className="modal-backdrop-custom" onClick={onClose}>
			<div className="modal-panel" style={{ maxWidth: 800 }} onClick={e => e.stopPropagation()}>
				<div className="modal-panel-header">
					<div className="d-flex align-items-center justify-content-between">
						<div className="d-flex align-items-center gap-2">
							<h5 className="mb-0">Registered Mocks</h5>
							{!loading && <span className="text-muted" style={{ fontSize: '0.8125rem' }}>({mocks.length})</span>}
						</div>
						<button type="button" className="btn-close" onClick={onClose} />
					</div>
				</div>
				<div className="modal-panel-body" style={{ maxHeight: '70vh', overflowY: 'auto' }}>
					{loading ? <div className="text-center py-4"><div className="spinner-border text-primary" /></div> : mocks.length === 0 ? <div className="text-muted">No mocks registered.</div> : (
						mocks.map((m, i) => (
							<div key={i} className="mb-3">
								<div className="fw-semibold small mb-1"><code className="text-primary">{m.class}</code></div>
								{Object.keys(m.rewrite_map || {}).length > 0 ? (
									<div style={{ border: '1px solid #e2e8f0', borderRadius: '0.5rem', overflow: 'hidden' }}>
										{Object.entries(m.rewrite_map).map(([pattern, replacement], j) => (
											<div key={j} className="d-flex align-items-center gap-2 py-1 px-2" style={{ fontSize: '0.8125rem', background: j % 2 === 0 ? '#f8fafc' : 'transparent' }}>
												<code style={{ color: '#64748b', flex: '0 0 auto' }}>{pattern}</code>
												<span style={{ color: '#94a3b8' }}>&rarr;</span>
												<code style={{ color: '#059669' }}>{replacement}</code>
											</div>
										))}
									</div>
								) : <div className="text-muted small" style={{ fontSize: '0.75rem' }}>No rewrite rules</div>}
							</div>
						))
					)}
				</div>
			</div>
		</div>
	);
}

function ConfigPage({ initialClass = '' }) {
	const [configs, setConfigs] = useState([]);
	const [loading, setLoading] = useState(true);
	const [search, setSearch] = useState(initialClass);
	const [filterDefined, setFilterDefined] = useState('');
	const [showScanned, setShowScanned] = useState(false);
	const [showMocks, setShowMocks] = useState(false);

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
			<div className="d-flex align-items-center gap-3 mb-4">
				<h1 className="h3 mb-0">Configurations</h1>
				<button className="btn btn-link btn-sm p-0" style={{ fontSize: '0.6875rem', color: '#94a3b8' }} onClick={() => setShowScanned(true)}>Scanned Classes</button>
				<button className="btn btn-link btn-sm p-0" style={{ fontSize: '0.6875rem', color: '#94a3b8' }} onClick={() => setShowMocks(true)}>Mocks</button>
				<a href={apiUrls.phpinfo} target="_blank" rel="noopener noreferrer" className="btn btn-link btn-sm p-0" style={{ fontSize: '0.6875rem', color: '#94a3b8', textDecoration: 'none' }}>phpinfo</a>
			</div>
			{showScanned && <ScannedClassesModal onClose={() => setShowScanned(false)} />}
			{showMocks && <MocksModal onClose={() => setShowMocks(false)} />}
			<div className="row g-3 mb-4">
				<div className="col-md-8"><input type="text" className="form-control" placeholder="Search configs..." value={search} onChange={e => setSearch(e.target.value)} /></div>
				<div className="col-md-4"><select className="form-select" value={filterDefined} onChange={e => setFilterDefined(e.target.value)}><option value="">All</option><option value="defined">Defined</option><option value="undefined">Undefined</option></select></div>
			</div>
			{loading ? <div className="text-center py-4"><div className="spinner-border text-primary" /></div> : Object.keys(grouped).length === 0 ? <div className="alert alert-info">No configs found.</div> : (
				Object.entries(grouped).map(([className, items]) => (
					<div key={className} className="card mb-4">
						<div className="card-header fw-semibold"><code>{className}</code> <a href={`#config=${encodeURIComponent(className)}`} style={{ color: '#94a3b8', fontSize: '0.75rem', textDecoration: 'none' }} title={className}>#</a></div>
						<table className="table table-hover mb-0">
							<thead className="table-light"><tr><th style={{width:'250px'}}>Name</th><th style={{width:'100px'}}>Type</th><th>Description</th><th style={{width:'80px'}}>Status</th></tr></thead>
							<tbody>{items.map((c, i) => (
								<tr key={i}>
									<td><code className="text-primary">{c.name}</code></td>
									<td className="text-muted small">{c.params.map(p => p.type).join(', ') || '-'}</td>
									<td className="small" style={{whiteSpace:'pre-wrap'}}>{(() => {
									const doc = c.document || c.summary || '';
									if (!doc) return '-';
									const lines = doc.split('\n');
									return <>{lines[0]}{lines.length > 1 && <span className="text-muted" style={{fontSize:'0.75em'}}>{'\n' + lines.slice(1).join('\n')}</span>}</>;
								})()}</td>
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

function CopyButton({ text, className = '' }) {
	const [copied, setCopied] = useState(false);
	const handleCopy = () => {
		navigator.clipboard.writeText(text).then(() => { setCopied(true); setTimeout(() => setCopied(false), 1500); });
	};
	return (
		<button type="button" className={`btn btn-sm ${className}`} style={{ fontSize: '0.6875rem', color: '#94a3b8', background: 'transparent', border: '1px solid #cbd5e1', borderRadius: 4, padding: '2px 8px', whiteSpace: 'nowrap' }} onClick={handleCopy}>{copied ? 'Copied!' : 'Copy'}</button>
	);
}

function CodeBlock({ code }) {
	return (
		<div style={{ position: 'relative' }}>
			<div style={{ position: 'absolute', top: 8, right: 8, zIndex: 1 }}><CopyButton text={code} /></div>
			<pre className="code-block p-3 mb-0" style={{ borderRadius: '0.5rem' }}>{code}</pre>
		</div>
	);
}

// 取得失敗時のフォールバック（通常は MCP の tools/list を動的表示）。Mcp.php の tool_defs() と対応。
const MCP_TOOLS = [
	{ name: 'api_info', desc: 'API 概要（info / servers=base URL / securitySchemes=認証方式）。クライアント実装の起点' },
	{ name: 'search_endpoints', desc: 'エンドポイントをキーワードで検索（path / summary / description / tag / operationId が対象）' },
	{ name: 'get_endpoint', desc: 'operationId を指定してエンドポイント詳細（parameters / requestBody / responses と参照スキーマ）を取得' },
	{ name: 'list_tags', desc: 'API のタグ（グループ）一覧を取得' },
	{ name: 'get_schema', desc: 'components schema（モデル定義）を名前で取得' },
	{ name: 'list_flows', desc: '達成できるゴール（状態/値トークン）の一覧＝ユースケース発見。goal を選び get_flow へ' },
	{ name: 'get_flow', desc: 'goal（operationId か 状態トークン）への呼び出し順(plan)を Requires/Produces から導出' },
];

function McpPage() {
	const serverName = 'endpoints-mcp';
	const mcpUrl = useMemo(() => {
		try { return apiUrls.mcp ? new URL(apiUrls.mcp, window.location.href).href : ''; }
		catch { return apiUrls.mcp || ''; }
	}, []);

	// MCP の tools/list を動的取得（ドリフト防止）。失敗時は静的 MCP_TOOLS にフォールバック。
	const [tools, setTools] = useState(null);
	useEffect(() => {
		if (!mcpUrl) return;
		fetch(mcpUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'tools/list' }) })
			.then(r => r.json())
			.then(j => { const t = j?.result?.tools; if (Array.isArray(t) && t.length) setTools(t.map(x => ({ name: x.name, desc: x.description || '' }))); })
			.catch(() => {});
	}, [mcpUrl]);
	const toolList = tools || MCP_TOOLS;

	const claudeCodeCmd = `claude mcp add --transport http ${serverName} ${mcpUrl}`;
	const jsonConfig = JSON.stringify({ mcpServers: { [serverName]: { type: 'http', url: mcpUrl } } }, null, 2);
	const geminiCmd = `gemini mcp add --transport http ${serverName} ${mcpUrl}`;
	const geminiConfig = JSON.stringify({ mcpServers: { [serverName]: { httpUrl: mcpUrl } } }, null, 2);
	const desktopBridgeConfig = JSON.stringify({ mcpServers: { [serverName]: { command: 'npx', args: ['-y', 'mcp-remote', mcpUrl] } } }, null, 2);

	return (
		<div>
			<div className="mb-4">
				<h1 className="h3 mb-1">MCP <span className="badge bg-success" style={{ fontSize: '0.6rem', verticalAlign: 'middle' }}>enabled</span></h1>
				<p className="text-muted mb-0" style={{ fontSize: '0.875rem' }}>
					このアプリの API ドキュメントを MCP (Model Context Protocol) 経由で検索・参照できます。読み取り専用で、API を実行するものではありません。<br />
					トランスポートは Streamable HTTP (JSON-RPC 2.0)。ebi 自身が MCP を喋るため、npx 等のブリッジは不要です。
				</p>
			</div>

			<div className="card mb-4">
				<div className="card-header fw-semibold">エンドポイント</div>
				<div className="card-body">
					<div className="d-flex align-items-center gap-2">
						<code style={{ fontSize: '0.875rem', flex: 1, wordBreak: 'break-all' }}>{mcpUrl || '(URL を取得できませんでした)'}</code>
						{mcpUrl && <CopyButton text={mcpUrl} />}
					</div>
				</div>
			</div>

			<div className="card mb-4">
				<div className="card-header fw-semibold">Claude Code (CLI)</div>
				<div className="card-body">
					<p className="text-muted small mb-2">ターミナルで以下を実行して登録します。</p>
					<CodeBlock code={claudeCodeCmd} />
					<p className="text-muted small mb-0 mt-2">Bearer 認証が必要な場合は <code>{'--header "Authorization: Bearer <token>"'}</code> を付けて登録します。</p>
				</div>
			</div>

			<div className="card mb-4">
				<div className="card-header fw-semibold">設定ファイル (JSON)</div>
				<div className="card-body">
					<p className="text-muted small mb-2"><code>type: http</code> のリモート MCP を <code>mcpServers</code> に直接書けるクライアント（Cursor など）向け。</p>
					<CodeBlock code={jsonConfig} />
					<p className="text-muted small mb-0 mt-2">Bearer 認証が必要な場合は同ブロックに <code>{'"headers": { "Authorization": "Bearer <token>" }'}</code> を追加します。</p>
				</div>
			</div>

			<div className="card mb-4">
				<div className="card-header fw-semibold">Claude Desktop</div>
				<div className="card-body">
					<p className="text-muted small mb-2">Claude Desktop の <code>claude_desktop_config.json</code> は stdio サーバのみ対応で、<code>url</code> を直接書くと無視・起動失敗の原因になります。リモート MCP へは <code>mcp-remote</code> ブリッジ経由で繋ぎます。</p>
					<CodeBlock code={desktopBridgeConfig} />
					<p className="text-muted small mb-0 mt-2">Bearer 認証が必要な場合は <code>args</code> の末尾に <code>{'"--header", "Authorization: Bearer <token>"'}</code> を追加します（URL には埋め込まないこと）。</p>
					<p className="text-muted small mb-0 mt-2">GUI から繋ぐ場合は 設定 → Connectors の Custom Connector も使えますが、こちらは OAuth 前提で静的 Bearer トークンは渡せません。</p>
				</div>
			</div>

			<div className="card mb-4">
				<div className="card-header fw-semibold">Gemini CLI</div>
				<div className="card-body">
					<p className="text-muted small mb-2">ターミナルで以下を実行して登録します。</p>
					<CodeBlock code={geminiCmd} />
					<p className="text-muted small mb-2 mt-3">または <code>~/.gemini/settings.json</code>（プロジェクト単位なら <code>.gemini/settings.json</code>）に直接記述します。Streamable HTTP のキーは <code>httpUrl</code> です。</p>
					<CodeBlock code={geminiConfig} />
					<p className="text-muted small mb-0 mt-2">Bearer 認証が必要な場合は同ブロックに <code>{'"headers": { "Authorization": "Bearer <token>" }'}</code> を追加します。</p>
				</div>
			</div>

			<div className="card mb-4">
				<div className="card-header fw-semibold">ChatGPT <span className="badge bg-secondary" style={{ fontSize: '0.6rem', verticalAlign: 'middle' }}>認証なし構成のみ</span></div>
				<div className="card-body">
					<p className="text-muted small mb-2">設定ファイルではなく Web アプリの GUI から登録します（Pro / Plus / Business / Enterprise / Edu、Web 版のみ）。</p>
					<ol className="text-muted small mb-2" style={{ paddingLeft: '1.2rem' }}>
						<li>設定 → Apps（コネクタ）→ Advanced settings で <strong>Developer mode</strong> を ON</li>
						<li><strong>Add custom connector</strong> を選び、上記のエンドポイント URL を貼り付け</li>
						<li>チャットで有効化</li>
					</ol>
					<div className="alert alert-warning small mb-0 py-2">
						ChatGPT のコネクタは <strong>OAuth または認証なし</strong>のみ対応です。ebi の静的 Bearer トークンや Cookie ログインは使えないため、<strong><code>bearer_token</code> を設定しない（認証なし）エンドポイントのときだけ</strong>接続できます。保護された API を見せる場合はネットワーク層（VPN / IP 制限）で守ってください。
					</div>
				</div>
			</div>

			<div className="card mb-4">
				<div className="card-header fw-semibold">利用できるツール</div>
				<table className="table table-hover mb-0">
					<thead className="table-light"><tr><th style={{ width: 200 }}>Tool</th><th>説明</th></tr></thead>
					<tbody>{toolList.map(t => (
						<tr key={t.name}>
							<td><code className="text-primary">{t.name}</code></td>
							<td className="text-muted small">{t.desc}</td>
						</tr>
					))}</tbody>
				</table>
			</div>

			<div className="card mb-4">
				<div className="card-header fw-semibold">envelope（レスポンス形式）</div>
				<div className="card-body">
					<p className="text-muted small mb-2">
						返すドキュメントを envelope 形式にするかは、API 本体と同じく <code>Accept</code> ヘッダで決まります。登録時に <code>--header</code> で指定します。
					</p>
					<table className="table table-sm mb-2">
						<thead className="table-light"><tr><th style={{ width: 320 }}>Accept ヘッダ</th><th>ドキュメントの表現</th></tr></thead>
						<tbody>
							<tr>
								<td><code>application/json; envelope=true</code></td>
								<td className="text-muted small">成功・失敗とも HTTP 200 前提。各エンドポイントの <code>200</code> が <code>oneOf[成功, Error]</code>、エラーは <code>x-throws</code> に列挙。</td>
							</tr>
							<tr>
								<td><code>application/json; envelope=false</code></td>
								<td className="text-muted small">エラーを 4xx/5xx としてステータス別に列挙し、各エラーに <code>Error</code> スキーマを付与。</td>
							</tr>
							<tr>
								<td className="text-muted">（未指定）</td>
								<td className="text-muted small">アプリの既定（<code>Conf 'envelope'</code>、既定 true）に従う。</td>
							</tr>
						</tbody>
					</table>
					<p className="text-muted small mb-0">
						例: <code>{'claude mcp add --transport http endpoints-mcp <URL> --header "Accept: application/json; envelope=false"'}</code>
					</p>
					<div className="alert alert-warning small mb-0 mt-2 py-2">
						一部のクライアントは Streamable HTTP のために <code>Accept</code> を自前で設定します。<code>--header</code> での上書きが効かない／SSE と競合する場合は、既定（<code>Conf 'envelope'</code>）で揃えてください。
					</div>
				</div>
			</div>
		</div>
	);
}

// spec に x-flow 情報が含まれているか（nav 出し分け用）。spec は非同期ロードで差し替わるため呼び出し時に評価する。
function specHasFlow() {
	if (spec && spec['x-flow-registry'] && Object.keys(spec['x-flow-registry']).length > 0) return true;
	for (const methods of Object.values(spec.paths || {})) {
		for (const op of Object.values(methods)) {
			if (op && op['x-flow']) return true;
		}
	}
	return false;
}

// ---- Flow 派生ロジック（Mcp.php の get_flow() / flow_topo_order() の TS 移植）----

// x-flow を持つ operation を収集し、生産者索引 token=>{oid:true} を作る。
function buildFlowIndex() {
	const ops = {};
	const producers = {};
	for (const [path, methods] of Object.entries(spec.paths || {})) {
		for (const [method, op] of Object.entries(methods)) {
			const flow = op['x-flow'];
			if (!flow || Object.keys(flow).length === 0) continue;
			const oid = op.operationId || (method.toUpperCase() + ' ' + path);
			const req = [];
			for (const r of (flow.requires || [])) {
				if (r && r.token != null) req.push(r.token);
			}
			const pro = [];
			for (const p of (flow.produces || [])) {
				if (p && p.token != null) {
					pro.push({ token: p.token, when: p.when || 'success' });
					if (!producers[p.token]) producers[p.token] = {};
					producers[p.token][oid] = true;
				}
			}
			ops[oid] = {
				method: method.toUpperCase(),
				path,
				summary: op.summary || '',
				requires: req,
				produces: pro,
				requiresRaw: flow.requires || [],
				after: flow.after || [],
				deprecated: !!op.deprecated,
			};
		}
	}
	return { ops, producers };
}

// 生産者→消費者の依存で安定トポロジカル整列（Kahn法）。循環時は残りを後置。
function flowTopoOrder(oids, ops, producers) {
	const set = {};
	for (const o of oids) set[o] = true;
	const indeg = {};
	const edges = {};
	for (const oid of oids) indeg[oid] = 0;
	for (const oid of oids) {
		for (const r of (ops[oid].requiresRaw || [])) {
			const t = r.token == null ? null : r.token;
			if (t === null || r.optional) continue;
			for (const p of Object.keys(producers[t] || {})) {
				if (p === oid || !set[p]) continue;
				(edges[p] || (edges[p] = [])).push(oid);
				indeg[oid]++;
			}
		}
	}
	const queue = [];
	for (const oid of oids) if (indeg[oid] === 0) queue.push(oid);
	queue.sort();
	const order = [];
	const seen = {};
	while (queue.length) {
		const oid = queue.shift();
		if (seen[oid]) continue;
		seen[oid] = true;
		order.push(oid);
		const next = [];
		for (const c of (edges[oid] || [])) {
			if (--indeg[c] === 0) next.push(c);
		}
		next.sort();
		for (const n of next) queue.push(n);
	}
	for (const oid of oids) if (!seen[oid]) order.push(oid);
	return order;
}

// token を作る op 連鎖を後ろ向き閉包（hard requires を辿る）→ topo整列して operationId 列を返す（entryOptions多段用）。
function flowProducerChain(token, ops, producers, registry, activeProducers) {
	const needed = {};
	const stack = [...activeProducers(token)];
	let guard = 0;
	while (stack.length && guard++ < 1000) {
		const oid = stack.pop();
		if (oid == null || needed[oid] || !ops[oid]) continue;
		needed[oid] = true;
		for (const r of (ops[oid].requiresRaw || [])) {
			const t = r.token == null ? null : r.token;
			if (t === null || r.optional) continue;
			if (registry[t]?.ambient || (registry[t]?.kind || '') === 'ambient') continue;
			for (const p of activeProducers(t)) { if (!needed[p]) stack.push(p); }
		}
	}
	return flowTopoOrder(Object.keys(needed), ops, producers);
}

// hard plan（spine）に差し込める任意の中間段を導出する。不動点反復で、採用した任意段の産物に依存する
// 任意段も次passで拾う（多段接続）。soft requires が既知産物で満たされる or after が既知 op を指すものを列挙（Mcp.php の移植）。
function flowOptionalSteps(needed, ops, planOut, inputs, registry) {
	const tokenStep = {};
	const oidStep = {};
	for (const po of planOut) {
		oidStep[po.operationId] = po.step;
		for (const t of po.produces) {
			if (tokenStep[t] == null || po.step < tokenStep[t]) tokenStep[t] = po.step;
		}
	}
	const available = {};
	for (const t of Object.keys(tokenStep)) available[t] = true;
	for (const t of Object.keys(inputs)) available[t] = true;
	const isAmbient = t => registry[t]?.ambient || (registry[t]?.kind || '') === 'ambient';

	const steps = [];
	const chosen = {};
	let guard = 0;
	let added;
	do {
		added = false;
		for (const oid of Object.keys(ops)) {
			if (needed[oid] || chosen[oid]) continue; // spine か採用済み
			if (ops[oid].deprecated) continue;
			let hardOk = true;
			const softLink = [];
			let afterStep = 0;
			for (const r of (ops[oid].requiresRaw || [])) {
				const t = r.token == null ? null : r.token;
				if (t === null) continue;
				if (!r.optional) {
					if (available[t] == null && !isAmbient(t)) { hardOk = false; break; }
					if (tokenStep[t] != null) afterStep = Math.max(afterStep, tokenStep[t]);
				} else if (tokenStep[t] != null) {
					softLink.push(t);
					afterStep = Math.max(afterStep, tokenStep[t]);
				}
			}
			if (!hardOk) continue;
			const afterHits = [];
			for (const a of (ops[oid].after || [])) {
				const ep = a.endpoint == null ? null : a.endpoint;
				if (ep !== null && oidStep[ep] != null) { afterHits.push(ep); afterStep = Math.max(afterStep, oidStep[ep]); }
			}
			if (softLink.length === 0 && afterHits.length === 0) continue; // この flow に非接続
			chosen[oid] = true;
			added = true;
			const produced = ops[oid].produces.map(p => p.token);
			steps.push({
				operationId: oid,
				method: ops[oid].method,
				path: ops[oid].path,
				summary: ops[oid].summary,
				requires: ops[oid].requires,
				produces: produced,
				afterStep,
				linkedBy: Array.from(new Set([...softLink.map(t => 'requires:' + t), ...afterHits.map(e => 'after:' + e)])),
			});
			const pos = afterStep + 1;
			oidStep[oid] = pos;
			for (const t of produced) {
				if (tokenStep[t] == null || pos < tokenStep[t]) tokenStep[t] = pos;
				available[t] = true;
			}
		}
	} while (added && guard++ < 100);

	steps.sort((a, b) => (a.afterStep - b.afterStep) || (a.operationId < b.operationId ? -1 : a.operationId > b.operationId ? 1 : 0));
	return steps;
}

// goal（operationId か token）へ到達する呼び出し順を導出する。
function computeFlow(goal, index) {
	const { ops, producers } = index;
	const registry = spec['x-flow-registry'] || {};
	if (!goal) return { error: 'goal is required (operationId or token)' };

	// token の生産者は active（非 deprecated）を優先。active が無い時だけ deprecated にフォールバック。
	const activeProducers = t => {
		const all = Object.keys(producers[t] || {});
		const active = all.filter(p => !ops[p]?.deprecated);
		return active.length ? active : all;
	};

	let goalOps, resolvedAs;
	if (ops[goal]) { goalOps = [goal]; resolvedAs = 'operationId'; }
	else if (producers[goal]) { goalOps = activeProducers(goal); resolvedAs = 'token'; }
	else return { error: `goal '${goal}' が operationId としても produces token としても解決できません` };

	// 後ろ向き閉包: hard requires の token を辿り生産者を集める。ambient/未生産は inputs へ。
	const needed = {};
	const inputs = {};
	const alternatives = {};
	const stack = [...goalOps];
	let guard = 0;
	while (stack.length && guard++ < 1000) {
		const oid = stack.pop();
		if (needed[oid]) continue;
		needed[oid] = true;
		for (const r of (ops[oid].requiresRaw || [])) {
			const t = r.token == null ? null : r.token;
			if (t === null || r.optional) continue;
			if (registry[t]?.ambient || (registry[t]?.kind || '') === 'ambient') { inputs[t] = 'ambient'; continue; }
			const prod = activeProducers(t);
			if (prod.length === 0) { inputs[t] = 'no-producer'; continue; }
			if (prod.length > 1) alternatives[t] = prod;
			for (const p of prod) { if (!needed[p]) stack.push(p); }
		}
	}

	const plan = flowTopoOrder(Object.keys(needed), ops, producers);

	const planOut = [];
	const branches = [];
	plan.forEach((oid, i) => {
		planOut.push({
			step: i + 1,
			operationId: oid,
			method: ops[oid].method,
			path: ops[oid].path,
			summary: ops[oid].summary,
			requires: ops[oid].requires,
			produces: ops[oid].produces.map(p => p.token),
		});
		for (const p of ops[oid].produces) {
			if ((p.when || 'success') !== 'success') branches.push({ at: i + 1, operationId: oid, when: p.when, token: p.token });
		}
	});

	const inputList = Object.entries(inputs).map(([t, reason]) => ({ token: t, kind: registry[t]?.kind || 'unknown', reason }));

	// spine op の optional requires（one-of の値トークン等。後ろ向き閉包は optional を辿らない）の
	// token を作る生産者を「上流の入口候補」として列挙する。plan 内生産/ambient/生産者なし/spine内 は除外。
	const planProduced = {};
	const oidStep = {};
	planOut.forEach(po => { oidStep[po.operationId] = po.step; po.produces.forEach(t => { planProduced[t] = true; }); });
	const isAmbient = t => registry[t]?.ambient || (registry[t]?.kind || '') === 'ambient';
	const entryOptions = [];
	const eoSeen = {};
	for (const oid of Object.keys(needed)) {
		for (const r of (ops[oid].requiresRaw || [])) {
			if (!r.optional) continue;
			const t = r.token == null ? null : r.token;
			if (t === null || isAmbient(t) || planProduced[t]) continue;
			const rest = activeProducers(t).filter(p => !needed[p]);
			if (rest.length === 0) continue;
			const key = oid + '|' + t;
			if (eoSeen[key]) continue;
			eoSeen[key] = true;
			const chain = flowProducerChain(t, ops, producers, registry, activeProducers);
			entryOptions.push({
				token: t,
				forOperationId: oid,
				forStep: oidStep[oid] != null ? oidStep[oid] : null,
				chain: chain.map(p => ({ operationId: p, method: ops[p].method, path: ops[p].path, summary: ops[p].summary, produces: ops[p].produces.map(x => x.token) })),
			});
		}
	}
	entryOptions.sort((a, b) => (a.forStep || 0) - (b.forStep || 0) || (a.token < b.token ? -1 : a.token > b.token ? 1 : 0));

	const optionalSteps = flowOptionalSteps(needed, ops, planOut, inputs, registry);

	const relIssues = (spec['x-flow-issues'] || []).filter(iss => needed[(iss && iss.operationId) || '']);

	return { goal, resolvedAs, inputs: inputList, entryOptions, plan: planOut, optionalSteps, branches, alternatives, issues: relIssues, needed };
}

function FlowPage() {
	const index = useMemo(() => buildFlowIndex(), []);
	const { ops, producers } = index;
	const registry = spec['x-flow-registry'] || {};
	const issuesAll = spec['x-flow-issues'] || [];

	const tokenOptions = useMemo(() => Object.keys(producers).sort(), [producers]);
	const opOptions = useMemo(() => Object.keys(ops).sort(), [ops]);

	const initial = parseHash();
	const [search, setSearch] = useState(initial.query.q || '');
	const [goal, setGoal] = useState(initial.detail || '');
	const schemas = spec.components?.schemas || {};
	// operationId → {path, method, op}（ノードクリックで endpoint 詳細を開く用）
	const opEndpoints = useMemo(() => {
		const m = {};
		for (const [path, methods] of Object.entries(spec.paths || {})) {
			for (const [method, op] of Object.entries(methods)) {
				if (op && op.operationId) m[op.operationId] = { path, method: method.toUpperCase(), op };
			}
		}
		return m;
	}, []);
	const openEp = oid => { const e = opEndpoints[oid]; if (e) window.location.hash = buildHash('endpoints', e.path, {}); };

	useEffect(() => {
		const { page } = parseHash();
		if (page !== 'flow') return;
		window.history.replaceState(null, '', '#' + buildHash('flow', goal, { q: search }));
	}, [goal, search]);

	// hash（戻る/進む・外部遷移）に goal を追従。goal 空＝ユースケース一覧。
	useEffect(() => {
		const h = () => { const p = parseHash(); if (p.page === 'flow') setGoal(p.detail || ''); };
		window.addEventListener('hashchange', h);
		return () => window.removeEventListener('hashchange', h);
	}, []);
	const gotoGoal = t => { window.location.hash = buildHash('flow', t, { q: search }); };
	const backToList = () => { window.location.hash = buildHash('flow', null, { q: search }); };

	const flow = useMemo(() => (goal ? computeFlow(goal, index) : null), [goal, index]);

	const layout = useMemo(() => {
		if (!flow || flow.error) return null;
		const plan = flow.plan.map(p => p.operationId);
		const planSet = {};
		plan.forEach(o => { planSet[o] = true; });
		const layerOf = {};
		for (const oid of plan) {
			let L = 0;
			for (const r of (ops[oid].requiresRaw || [])) {
				const t = r.token == null ? null : r.token;
				if (t === null || r.optional) continue;
				for (const p of Object.keys(producers[t] || {})) {
					if (p === oid || !planSet[p]) continue;
					if (layerOf[p] != null) L = Math.max(L, layerOf[p] + 1);
				}
			}
			layerOf[oid] = L;
		}
		const layers = [];
		for (const oid of plan) { const L = layerOf[oid]; (layers[L] || (layers[L] = [])).push(oid); }
		// ノード幅を最長 operationId に合わせて動的化（名前が切れないように）
		const maxLen = Math.max(10, ...plan.map(o => o.length));
		// chrome 分（番号バッジ＋HTTPメソッドバッジ＋左右padding/gap）を見込んで operationId が省略されない幅にする
		const nodeW = Math.min(560, Math.max(210, maxLen * 8 + 132)), nodeH = 46, gapX = 32, gapY = 92, padX = 14, padY = 14;
		const colW = nodeW + gapX;
		const maxRow = Math.max(1, ...layers.map(l => (l ? l.length : 0)));
		const width = maxRow * colW + padX * 2;
		const pos = {};
		layers.forEach((l, L) => {
			if (!l) return;
			const rowWidth = l.length * colW;
			const x0 = (width - rowWidth) / 2;
			l.forEach((oid, i) => {
				const x = x0 + i * colW + gapX / 2;
				const y = padY + L * (nodeH + gapY);
				pos[oid] = { x, y, cx: x + nodeW / 2 };
			});
		});
		const layerCount = layers.length;
		const height = padY * 2 + layerCount * nodeH + Math.max(0, layerCount - 1) * gapY;
		const edges = [];
		for (const oid of plan) {
			for (const r of (ops[oid].requiresRaw || [])) {
				const t = r.token == null ? null : r.token;
				if (t === null || r.optional) continue;
				for (const p of Object.keys(producers[t] || {})) {
					if (p === oid || !planSet[p]) continue;
					edges.push({ from: p, to: oid, token: t });
				}
			}
		}
		return { plan, layerOf, pos, width, height, nodeW, nodeH, edges };
	}, [flow, ops, producers]);

	// フローデータそのものが無い場合
	if (tokenOptions.length === 0 && opOptions.length === 0) {
		return (
			<div>
				<h1 className="h3 mb-4">Flow</h1>
				<div className="text-muted">No flow tokens defined.</div>
			</div>
		);
	}

	const s = search.toLowerCase();
	const filteredTokens = tokenOptions.filter(t => !s || t.toLowerCase().includes(s) || (registry[t]?.summary || '').toLowerCase().includes(s));
	const filteredOps = opOptions.filter(o => !s || o.toLowerCase().includes(s) || (ops[o]?.summary || '').toLowerCase().includes(s));

	const branchesByOid = {};
	if (flow && !flow.error) {
		for (const b of flow.branches) { (branchesByOid[b.operationId] || (branchesByOid[b.operationId] = [])).push(b); }
	}
	const alternatives = (flow && !flow.error) ? flow.alternatives : {};
	const altTokens = Object.keys(alternatives || {});

	// 冗長な summary を短い見出しに。末尾の注釈括弧だけ除去（インラインの「（再送）」等は残す）。
	const shortLabel = t => {
		let s = (registry[t] && registry[t].summary) || t;
		s = s.replace(/[（(][^（）()]*[）)]\s*$/, '').trim(); // 末尾の (…) 注釈を除去
		return s || t;
	};

	return (
		<div>
			<div className="mb-4">
				<h1 className="h3">Flow</h1>
			</div>

			{!goal ? (
              <>
                <div className="mb-4">
                  <input type="text" className="form-control" placeholder="ユースケースを絞り込む…" value={search} onChange={e => setSearch(e.target.value)} />
                </div>
                {filteredTokens.length === 0 ? <div className="text-muted">該当するユースケースがありません。</div> : (() => {
                  const tagList = (spec.tags || []).filter(x => x.name !== 'Dt');
                  const tagLabel = name => tagList.find(x => x.name === name)?.['x-displayName'] || name;
                  const tagOrder = name => { const i = tagList.findIndex(x => x.name === name); return i < 0 ? 999 : i; };
                  const tagOf = t => { const ps = Object.keys(producers[t] || {}); const p = ps.find(o => !opEndpoints[o]?.op.deprecated) || ps[0]; return (p && opEndpoints[p]?.op.tags?.[0]) || 'Other'; };
                  const grouped = {};
                  filteredTokens.forEach(t => { const g = tagOf(t); (grouped[g] || (grouped[g] = [])).push(t); });
                  return Object.keys(grouped).sort((a, b) => tagOrder(a) - tagOrder(b) || (a < b ? -1 : 1)).map(g => (
                    <div key={g} className="card mb-4">
                      <div className="card-header fw-semibold">{tagLabel(g)}</div>
                      <div className="list-group list-group-flush">
                        {grouped[g].map(t => (
                          <button key={t} className="list-group-item list-group-item-action d-flex align-items-center gap-3 flow-usecase-row" onClick={() => gotoGoal(t)}>
                            <span className={`flow-uc-kind flow-uc-${registry[t]?.kind || 'value'}`}>{registry[t]?.kind === 'state' ? '状態' : '値'}</span>
                            <span className="flex-grow-1 fw-medium" style={{ fontSize: '0.875rem' }}>{shortLabel(t)}</span>
                            <code className="text-muted small">{t}</code>
                          </button>
                        ))}
                      </div>
                    </div>
                  ));
                })()}
              </>
            ) : flow && flow.error ? (
              <div className="alert alert-warning">
                <a href="#" onClick={e => { e.preventDefault(); backToList(); }} className="d-inline-block mb-2 small text-decoration-none">← ユースケース一覧</a>
                <div>{flow.error}</div>
              </div>
            ) : flow ? (
              <>
              <a href="#" onClick={e => { e.preventDefault(); backToList(); }} className="d-inline-block mb-2 small text-decoration-none">← ユースケース一覧</a>
              <div className="card">
                <div className="modal-panel-header">
                  <div className="d-flex align-items-center gap-3">
                    <span className="badge" style={{ background: '#e2e8f0', color: '#475569' }}>{flow.resolvedAs === 'token' ? '状態/値' : 'API'}</span>
                    <span className="fw-semibold" style={{ fontSize: '1.1rem', color: '#1e293b' }}>{shortLabel(flow.goal)}</span>
                  </div>
                  {registry[flow.goal]?.summary && registry[flow.goal].summary !== shortLabel(flow.goal) && <div className="text-muted" style={{ fontSize: '0.8125rem', marginTop: 4 }}>{registry[flow.goal].summary}</div>}
                  <div className="d-flex align-items-center gap-2 mt-2">
                    <code style={{ color: '#3b82f6', fontSize: '0.8125rem' }}>{flow.goal}</code>
                    <span className="text-muted small ms-auto">全 {flow.plan.length} ステップ</span>
                  </div>
                </div>
                <div className="modal-panel-body">
                  {flow.inputs.length > 0 && <section>
                    <div className="section-label">前提</div>
                    <div className="d-flex flex-column gap-2 mt-2">
                      {flow.inputs.map((inp, i) => (
                        <div key={i} className={`flow-io-item${inp.reason === 'no-producer' ? ' flow-io-warn' : ''}`}>
                          <span className="flow-io-dot" />
                          <div className="flow-io-body">
                            <div className="flow-io-summary">{registry[inp.token]?.summary || inp.token}</div>
                            <div className="flow-io-meta"><code>{inp.token}</code><span className={inp.reason === 'no-producer' ? 'flow-io-tag' : 'flow-io-kind'}>{inp.reason === 'no-producer' ? '生産者なし' : (inp.kind === 'state' ? '状態' : inp.kind === 'value' ? '値' : '外部由来')}</span></div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </section>}
                  {flow.entryOptions && flow.entryOptions.length > 0 && <section>
                    <div className="section-label">始め方（入口）<span className="text-muted fw-normal small"> — いずれか1つ</span></div>
                    <div className="list-group mt-2">
                      {flow.entryOptions.map((eo, i) => (
                        <div key={i} className="list-group-item">
                          <div className="mb-2" style={{ fontSize: '0.8125rem' }}><span className="fw-medium">{registry[eo.token]?.summary || eo.token}</span> <code className="text-muted" style={{ fontSize: '0.6875rem' }}>{eo.token}</code></div>
                          <div className="d-flex flex-column gap-1">
                            {(eo.chain || []).map((p, pi) => (
                              <button key={pi} className="d-flex align-items-center gap-2 flow-neighbor border rounded px-2 py-1 bg-white text-start" onClick={() => openEp(p.operationId)} title={p.summary || ''}>
                                <span className="flow-opt-after">{pi + 1}</span>
                                <span className={`method-badge ${methodColors[p.method] || ''}`}>{p.method}</span>
                                <code className="flow-neighbor-oid">{p.operationId}</code>
                                {p.summary && <span className="text-muted small text-truncate ms-1">{p.summary}</span>}
                              </button>
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  </section>}
                  <section>
                    <div className="section-label">手順 <span className="text-muted fw-normal small">— ノードをクリックでAPI詳細</span></div>
                    <div className="mt-2">
                    {layout && layout.plan.length > 0 ? (
                      <div className="flow-graph">
                        <svg viewBox={`0 0 ${layout.width} ${layout.height}`} width="100%" style={{ maxWidth: layout.width, height: 'auto', display: 'block' }}>
                          <defs>
                            <marker id="flow-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                              <path d="M 0 0 L 10 5 L 0 10 z" fill="#cbd5e1" />
                            </marker>
                          </defs>
                          {layout.edges.map((e, i) => {
                            const a = layout.pos[e.from], b = layout.pos[e.to];
                            if (!a || !b) return null;
                            const x1 = a.cx, y1 = a.y + layout.nodeH;
                            const x2 = b.cx, y2 = b.y;
                            const dy = Math.max(20, (y2 - y1) / 2);
                            const mx = (x1 + x2) / 2, my = (y1 + y2) / 2;
                            const isAlt = !!(alternatives && alternatives[e.token]);
                            const full = registry[e.token]?.summary || e.token;
                            const labelTxt = full + (isAlt ? ` ↔${alternatives[e.token].length}` : '');
                            const perLine = 20, maxW = 260;
                            const lines = Math.max(1, Math.ceil(labelTxt.length / perLine));
                            const chipW = labelTxt.length <= perLine ? (18 + labelTxt.length * 12) : maxW;
                            const chipH = lines * 16 + 8;
                            return (
                              <g key={i}>
                                <path className="flow-edge" d={`M ${x1} ${y1} C ${x1} ${y1 + dy} ${x2} ${y2 - dy} ${x2} ${y2}`} markerEnd="url(#flow-arrow)" />
                                <foreignObject x={mx - chipW / 2} y={my - chipH / 2} width={chipW} height={chipH}>
                                  <div className={`flow-edge-label ${isAlt ? 'flow-edge-label-alt' : ''}`} title={full}>{labelTxt}</div>
                                </foreignObject>
                              </g>
                            );
                          })}
                          {layout.plan.map((oid, i) => {
                            const p = layout.pos[oid];
                            if (!p) return null;
                            const step = flow.plan[i];
                            const brs = branchesByOid[oid] || [];
                            return (
                              <foreignObject key={oid} x={p.x} y={p.y} width={layout.nodeW} height={layout.nodeH}>
                                <div className="flow-node" onClick={() => openEp(oid)} title={step.summary || oid}>
                                  <span className="flow-node-num">{step.step}</span>
                                  <span className={`method-badge ${methodColors[step.method] || ''}`}>{step.method}</span>
                                  <span className="flow-node-oid">{oid}</span>
                                  {brs.map((b, bi) => <span key={bi} className="flow-branch-badge">分岐:{b.when}</span>)}
                                </div>
                              </foreignObject>
                            );
                          })}
                        </svg>
                      </div>
                    ) : (
                      <div className="text-muted">追加の呼び出しは不要（直接満たせます）。</div>
                    )}
                    </div>
                  </section>
                  {flow.optionalSteps && flow.optionalSteps.length > 0 && <section>
                    <div className="section-label">任意で挟める操作<span className="text-muted fw-normal small"> — クリックで詳細</span></div>
                    <div className="list-group mt-2">
                      {flow.optionalSteps.map((os, i) => (
                        <button key={i} className="list-group-item list-group-item-action d-flex align-items-center gap-2 flow-opt-row" onClick={() => openEp(os.operationId)}>
                          <span className="flow-opt-after">手順{os.afterStep}後</span>
                          <span className={`method-badge ${methodColors[os.method] || ''}`}>{os.method}</span>
                          <code className="flow-opt-oid">{os.operationId}</code>
                          {os.summary && <span className="text-muted small text-truncate ms-1">{os.summary}</span>}
                        </button>
                      ))}
                    </div>
                  </section>}
                  {flow.branches.length > 0 && <section>
                    <div className="section-label">分岐</div>
                    <div className="list-group mt-2">
                      {flow.branches.map((b, i) => (
                        <div key={i} className="list-group-item d-flex align-items-center gap-2" style={{ fontSize: '0.8125rem' }}>
                          <span className="flow-branch-badge">{b.when}</span>
                          <span className="text-muted">手順{b.at} で分岐 →</span>
                          <code style={{ color: '#3b82f6' }}>{registry[b.token]?.summary || b.token}</code>
                        </div>
                      ))}
                    </div>
                  </section>}
                  {altTokens.length > 0 && <section>
                    <div className="section-label">代替経路</div>
                    <div className="list-group mt-2">
                      {altTokens.map((t, i) => (
                        <div key={i} className="list-group-item">
                          <div className="mb-2" style={{ fontSize: '0.8125rem' }}><span className="fw-medium" style={{ color: '#3b82f6' }}>{registry[t]?.summary || t}</span> <span className="text-muted small">を成立させる別の手段</span></div>
                          <div className="d-flex flex-column gap-1">
                            {alternatives[t].map((o, oi) => { const ep = opEndpoints[o]; return (
                              <button key={oi} className="d-flex align-items-center gap-2 flow-neighbor border rounded px-2 py-1 bg-white text-start" onClick={() => openEp(o)} title={ep?.op.summary || ''}>
                                <span className={`method-badge ${methodColors[ep?.method || 'GET'] || ''}`}>{ep?.method || ''}</span>
                                <code className="flow-neighbor-oid">{o}</code>
                                {ep?.op.summary && <span className="text-muted small text-truncate ms-1">{ep.op.summary}</span>}
                              </button>); })}
                          </div>
                        </div>
                      ))}
                    </div>
                  </section>}
                  {flow.resolvedAs === 'token' && (() => {
                    const consumers = Object.keys(ops).filter(oid => !flow.needed[oid] && !opEndpoints[oid]?.op.deprecated && (ops[oid].requiresRaw || []).some(r => r.token === flow.goal)).sort();
                    if (!consumers.length) return null;
                    return <section>
                      <div className="section-label">この後に呼べる操作 <span className="text-muted fw-normal small">— この状態/値を前提にするAPI</span></div>
                      <div className="list-group mt-2">
                        {consumers.map(oid => { const ep = opEndpoints[oid]; return (
                          <button key={oid} className="list-group-item list-group-item-action d-flex align-items-center gap-2 flow-neighbor" onClick={() => openEp(oid)} title={ep?.op.summary || ''}>
                            <span className={`method-badge ${methodColors[ep?.method] || ''}`}>{ep?.method}</span>
                            <code className="flow-neighbor-oid">{oid}</code>
                            {ep?.op.summary && <span className="text-muted small text-truncate ms-1">{ep.op.summary}</span>}
                          </button>); })}
                      </div>
                    </section>;
                  })()}
                  {flow.issues.length > 0 && <div className="alert alert-warning mt-3">
                    <div className="fw-semibold mb-1" style={{ fontSize: '0.8125rem' }}>この経路に関係する宣言の不整合（{flow.issues.length}）</div>
                    {flow.issues.map((iss, i) => (
                      <div key={i} style={{ fontSize: '0.8125rem' }}>
                        {iss.gate && <span className="badge bg-danger me-2">{iss.gate}</span>}
                        {iss.operationId && <code className="me-2" style={{ fontSize: '0.6875rem' }}>{iss.operationId}</code>}
                        {iss.message || ''}
                      </div>
                    ))}
                  </div>}
                </div>
              </div>
            </>
			) : null}

			{issuesAll.length > 0 && <div className="alert alert-danger mt-4">
              <div className="fw-semibold mb-2" style={{ fontSize: '0.875rem' }}>宣言の整合性チェック — {issuesAll.length} 件の不整合（#[Requires]/#[Produces] が語彙と不一致。G1..G6）</div>
              {issuesAll.map((iss, i) => (
                <div key={i} className="d-flex align-items-center gap-2 py-1" style={{ fontSize: '0.8125rem' }}>
                  {iss.gate && <span className="badge bg-danger">{iss.gate}</span>}
                  {iss.operationId && <code style={{ fontSize: '0.6875rem', color: '#94a3b8' }}>{iss.operationId}</code>}
                  <span>{iss.message || ''}</span>
                </div>
              ))}
            </div>}
		</div>
	);
}

function parseHash() {
	const hash = window.location.hash.slice(1);
	if (!hash) return { page: 'endpoints', detail: null, query: {} };
	const [main, queryStr] = hash.split('?');
	const [page, ...rest] = main.split('=');
	const detail = rest.join('=') || null;
	const query = {};
	if (queryStr) {
		for (const pair of queryStr.split('&')) {
			const [k, v] = pair.split('=');
			if (k) query[decodeURIComponent(k)] = v ? decodeURIComponent(v) : '';
		}
	}
	return { page: page || 'endpoints', detail: detail ? decodeURIComponent(detail) : null, query };
}

function buildHash(page, detail, query) {
	let hash = detail ? `${page}=${encodeURIComponent(detail)}` : page;
	const entries = Object.entries(query || {}).filter(([, v]) => v !== '' && v != null);
	if (entries.length > 0) {
		hash += '?' + entries.map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
	}
	return hash;
}

function LoginPage() {
	return (
		<div className="min-vh-100 bg-light d-flex align-items-start justify-content-center">
			<div style={{ width: '100%', maxWidth: 360, marginTop: '15vh' }}>
				<div className="card shadow-sm">
					<div className="card-body p-4">
						<form method="post" action={apiUrls.login}>
							<div className="mb-3">
								<label className="form-label small text-muted">Username</label>
								<input type="text" name="username" className="form-control" autoComplete="username" />
							</div>
							<div className="mb-3">
								<label className="form-label small text-muted">Password</label>
								<input type="password" name="password" className="form-control" autoComplete="current-password" autoFocus required />
							</div>
							<button type="submit" className="btn btn-primary w-100">Login</button>
						</form>
						{initialLoginError && <div className="text-danger small mt-3 text-center">Authentication failed</div>}
					</div>
				</div>
			</div>
		</div>
	);
}

function App() {
	if (requiresPassword && !initialAuthenticated) {
		return <LoginPage />;
	}
	return <MainApp />;
}

function MainApp() {
	const initial = parseHash();
	const [page, setPage] = useState(initial.page);
	const [selected, setSelected] = useState(null);
	const [selectedSchema, setSelectedSchema] = useState(null);
	const [envelope, setEnvelope] = useState(true);
	const [configClass, setConfigClass] = useState(initial.page === 'config' ? (initial.detail || '') : '');
	const [specLoading, setSpecLoading] = useState(!spec.paths);
	const hasFlow = specHasFlow();

	// 全ナビゲーションは location.hash を単一の真実の源にする（hashchange → applyHashState が状態へ反映）。
	// これでブラウザの戻る/進むが 一覧↔詳細↔隣接↔フロー で一貫して機能する。
	const navHash = (p, detail = null, preserveQuery = true) => {
		const query = preserveQuery ? parseHash().query : {};
		window.location.hash = buildHash(p, detail, query);
	};

	const handlePageChange = (p) => { navHash(p, null, false); };

	const handleSelectEndpoint = (e) => { navHash('endpoints', e.path); };
	const handleCloseEndpoint = () => { navHash('endpoints'); };

	const handleSelectWebhook = (w) => { navHash('webhooks', w.path); };
	const handleCloseWebhook = () => { navHash('webhooks'); };

	const handleSelectSchema = (name) => { navHash('schemas', name); };
	const handleCloseSchema = () => { navHash('schemas'); };

	const applyHashState = useCallback(() => {
		const { page: p, detail: d } = parseHash();
		setPage(p);
		if (p === 'endpoints' && d) {
			const paths = spec.paths || {};
			for (const [path, methods] of Object.entries(paths)) {
				if (path === d) { setSelected({ path, method: Object.keys(methods)[0], op: methods[Object.keys(methods)[0]] }); break; }
			}
		} else if (p === 'webhooks' && d) {
			for (const w of webhooks) {
				if (w.path === d) { setSelected(w); break; }
			}
		} else if (p === 'schemas' && d) {
			const schemas = spec.components?.schemas || {};
			if (schemas[d]) setSelectedSchema({ name: d, schema: schemas[d] });
		} else if (p === 'config') {
			setConfigClass(d || '');
		}
		// 詳細を持たないページ状態では選択をクリア（全画面詳細→一覧の戻りを hash と同期）
		if (!((p === 'endpoints' || p === 'webhooks') && d)) setSelected(null);
		if (!(p === 'schemas' && d)) setSelectedSchema(null);
	}, []);

	useEffect(() => {
		if (!spec.paths && apiUrls.openapi) {
			const sep = apiUrls.openapi.includes('?') ? '&' : '?';
			// UIは全4xx+x-throwsを持つ envelope=false の spec を取得し、client側トグルで表示を切替える。
			// サーバー既定(envelope_default)に依存しないよう明示的に envelope=false を付ける。
			fetch(apiUrls.openapi + sep + 'include_dev=true&envelope=false')
				.then(res => res.json())
				.then(data => {
					spec = data.spec || data;
					webhooks = data.webhooks || webhooks;
					allTagDefs = data.allTags || allTagDefs;
					mailTemplates = data.mailTemplates || mailTemplates;
					setSpecLoading(false);
					applyHashState();
				})
				.catch(() => setSpecLoading(false));
		} else {
			applyHashState();
		}
		window.addEventListener('hashchange', applyHashState);
		return () => window.removeEventListener('hashchange', applyHashState);
	}, []);

	return (
		<div className="min-vh-100">
			<nav className="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
				<div className="container">
					<span className="navbar-brand fw-bold">DevTools</span>{appmode && <span className="badge bg-info text-dark ms-2">{appmode}</span>}
					<div className="navbar-nav me-auto flex-row gap-2">
						<button className={`nav-link btn btn-link ${page === 'endpoints' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('endpoints')}>Endpoints</button>
						{webhooks.length > 0 && <button className={`nav-link btn btn-link ${page === 'webhooks' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('webhooks')}>Webhooks</button>}
						{hasFlow && <button className={`nav-link btn btn-link ${page === 'flow' ? 'active fw-semibold' : ''}`} onClick={() => handlePageChange('flow')}>Flow</button>}
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
					{mcpEnabled && <button className={`btn btn-outline-secondary btn-sm me-2 ${page === 'mcp' ? 'active' : ''}`} onClick={() => handlePageChange('mcp')}>MCP</button>}<a href={apiUrls.redoc + '?envelope=' + (envelope ? 'true' : 'false')} className="btn btn-outline-secondary btn-sm me-2">Redoc</a><a href={apiUrls.openapi + '?envelope=' + (envelope ? 'true' : 'false')} download="openapi.json" className="btn btn-outline-primary btn-sm">OpenAPI JSON</a>
				</div>
			</nav>
			<main className="container py-4">
				{specLoading ? (
					<div className="text-center py-5"><div className="spinner-border text-primary" /><div className="text-muted mt-2">Loading...</div></div>
				) : (<>
					{page === 'endpoints' && (selected
						? <EndpointDetail endpoint={selected} schemas={spec.components?.schemas || {}} envelope={envelope} onClose={handleCloseEndpoint} onNavigate={handleSelectEndpoint} />
						: <Endpoints onSelect={handleSelectEndpoint} />)}
					{page === 'webhooks' && (selected
						? <EndpointDetail endpoint={selected} schemas={spec.components?.schemas || {}} envelope={false} onClose={handleCloseWebhook} onNavigate={handleSelectWebhook} />
						: <WebhooksPage onSelect={handleSelectWebhook} />)}
					{page === 'schemas' && <Schemas selected={selectedSchema} onSelect={handleSelectSchema} onClose={handleCloseSchema} />}
						{page === 'flow' && <FlowPage />}
					{page === 'config' && <ConfigPage key={configClass} initialClass={configClass} />}
					{page === 'mail' && <MailPage />}
					{page === 'mcp' && <McpPage />}
				</>)}
			</main>
		</div>
	);
}

export default App;
