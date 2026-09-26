import{a as m,h as N,o as E,e as D}from"./index-DgEyzq8A.chunk.mjs";import{i as f,b as x,g as R}from"./index-DU6aLNdu.chunk.mjs";import{$ as q,R as T}from"./fileSize-2LkQn4td.chunk.mjs";import{s as o,l as u,N as P,a as _,b as A,P as i}from"./externalStorageUtils-DlAO49Rz.chunk.mjs";function S(e=""){let r=i.NONE;return e&&(e.includes("G")&&(r|=i.READ),e.includes("W")&&(r|=i.WRITE),e.includes("CK")&&(r|=i.CREATE),e.includes("NV")&&(r|=i.UPDATE),e.includes("D")&&(r|=i.DELETE),e.includes("R")&&(r|=i.SHARE)),r}const $=["d:getcontentlength","d:getcontenttype","d:getetag","d:getlastmodified","d:creationdate","d:displayname","d:quota-available-bytes","d:resourcetype","nc:has-preview","nc:is-encrypted","nc:mount-type","oc:comments-unread","oc:favorite","oc:fileid","oc:owner-display-name","oc:owner-id","oc:permissions","oc:size","nc:upload_time"],y={d:"DAV:",nc:"http://nextcloud.org/ns",oc:"http://owncloud.org/ns",ocs:"http://open-collaboration-services.org/ns"};function F(e,r={nc:"http://nextcloud.org/ns"}){o.davNamespaces??={...y},o.davProperties??=[...$];const s={...o.davNamespaces,...r};if(o.davProperties.find(t=>t===e))return u.warn(`${e} already registered`,{prop:e}),!1;if(e.startsWith("<")||e.split(":").length!==2)return u.error(`${e} is not valid. See example: 'oc:fileid'`,{prop:e}),!1;const d=e.split(":")[0];return s[d]?(o.davProperties.push(e),o.davNamespaces=s,!0):(u.error(`${e} namespace unknown`,{prop:e,namespaces:s}),!1)}function h(){return o.davProperties??=[...$],o.davProperties.map(e=>`<${e} />`).join(" ")}function g(){return o.davNamespaces??={...y},Object.keys(o.davNamespaces).map(e=>`xmlns:${e}="${o.davNamespaces?.[e]}"`).join(" ")}function O(){return`<?xml version="1.0"?>
		<d:propfind ${g()}>
			<d:prop>
				${h()}
			</d:prop>
		</d:propfind>`}function j(){return`<?xml version="1.0"?>
		<oc:filter-files ${g()}>
			<d:prop>
				${h()}
			</d:prop>
			<oc:filter-rules>
				<oc:favorite>1</oc:favorite>
			</oc:filter-rules>
		</oc:filter-files>`}function U(e,r=100){const s=R(),d=s.dav?.search_supports_upload_time,t=s.dav?.search_supports_last_activity?"<nc:last_activity/>":"<d:getlastmodified/>";return`<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest ${g()}
	xmlns:ns="https://github.com/icewind1991/SearchDAV/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>
				${h()}
			</d:prop>
		</d:select>
		<d:from>
			<d:scope>
				<d:href>/files/${m()?.uid}/</d:href>
				<d:depth>infinity</d:depth>
			</d:scope>
		</d:from>
		<d:where>
			<d:and>
				<d:or>
					<d:not>
						<d:eq>
							<d:prop>
								<d:getcontenttype/>
							</d:prop>
							<d:literal>httpd/unix-directory</d:literal>
						</d:eq>
					</d:not>
					<d:eq>
						<d:prop>
							<oc:size/>
						</d:prop>
						<d:literal>0</d:literal>
					</d:eq>
				</d:or>
				${d?`
						<d:or>
							<d:gt>
								<d:prop>
									<d:getlastmodified/>
								</d:prop>
								<d:literal>${e}</d:literal>
							</d:gt>
							<d:gt>
								<d:prop>
									<nc:upload_time/>
								</d:prop>
								<d:literal>${e}</d:literal>
							</d:gt>
						</d:or>
				`:`
					<d:gt>
						<d:prop>
							<d:getlastmodified/>
						</d:prop>
						<d:literal>${e}</d:literal>
					</d:gt>
				`}
			</d:and>
		</d:where>
		<d:orderby>
			<d:order>
				<d:prop>
					${t}
				</d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>${r}</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`}function k(){return f()?`/files/${x()}`:`/files/${m()?.uid}`}const w=k();function z(){const e=N("dav");return f()?e.replace("remote.php","public.php"):e}const b=z();function W(e=b,r={}){const s=q(e,{headers:r});function d(t){s.setHeaders({...r,"X-Requested-With":"XMLHttpRequest",requesttoken:t??""})}return E(d),d(D()),T().patch("fetch",(t,a)=>{const n=a.headers;return n?.method&&(a.method=n.method,delete n.method),fetch(t,a)}),s}async function X(e={}){const r=e.client??W(),s=e.path??"/",d=e.davRoot??w;return(await r.getDirectoryContents(`${d}${s}`,{signal:e.signal,details:!0,data:j(),headers:{method:"REPORT"},includeSelf:!0})).data.filter(t=>t.filename!==s).map(t=>C(t,d))}function C(e,r=w,s=b){let d=m()?.uid;if(f())d=d??"anonymous";else if(!d)throw new Error("No user id found");const t=e.props,a=S(t?.permissions),n=String(t?.["owner-id"]||d),v=t.fileid||0,p=new Date(Date.parse(e.lastmod)),c=new Date(Date.parse(t.creationdate)),l={id:v,source:`${s}${e.filename}`,mtime:!isNaN(p.getTime())&&p.getTime()!==0?p:void 0,crtime:!isNaN(c.getTime())&&c.getTime()!==0?c:void 0,mime:e.mime||"application/octet-stream",displayname:t.displayname!==void 0?String(t.displayname):void 0,size:t?.size||Number.parseInt(t.getcontentlength||"0"),status:v<0?P.FAILED:void 0,permissions:a,owner:n,root:r,attributes:{...e,...t,hasPreview:t?.["has-preview"]}};return delete l.attributes?.props,e.type==="file"?new _(l):new A(l)}export{g as a,h as b,F as c,b as d,z as e,k as f,W as g,X as h,w as i,U as j,O as k,C as r};
//# sourceMappingURL=dav-C6K-3S9c.chunk.mjs.map
