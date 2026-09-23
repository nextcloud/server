import{a as m,f as E,o as q,d as D}from"./public-CbgMFeCH.chunk.mjs";import{l as y}from"./_plugin-vue_export-helper-CqVUm19z.chunk.mjs";import{z as P,L as T}from"./index-BEngW5-6.chunk.mjs";import{s as d,l as u,N as x,a as _,b as R,P as o}from"./externalStorageUtils-BPgoNaNo.chunk.mjs";import{g as S}from"./fileSize-YTgV-6aa.chunk.mjs";function f(){return y("files_sharing","isPublic",null)??document.querySelector('input#isPublic[type="hidden"][name="isPublic"][value="1"]')!==null}function A(){return y("files_sharing","sharingToken",null)??document.querySelector('input#sharingToken[type="hidden"]')?.value??null}function k(e=""){let r=o.NONE;return e&&(e.includes("G")&&(r|=o.READ),e.includes("W")&&(r|=o.WRITE),e.includes("CK")&&(r|=o.CREATE),e.includes("NV")&&(r|=o.UPDATE),e.includes("D")&&(r|=o.DELETE),e.includes("R")&&(r|=o.SHARE)),r}const $=["d:getcontentlength","d:getcontenttype","d:getetag","d:getlastmodified","d:creationdate","d:displayname","d:quota-available-bytes","d:resourcetype","nc:has-preview","nc:is-encrypted","nc:mount-type","oc:comments-unread","oc:favorite","oc:fileid","oc:owner-display-name","oc:owner-id","oc:permissions","oc:size","nc:upload_time"],w={d:"DAV:",nc:"http://nextcloud.org/ns",oc:"http://owncloud.org/ns",ocs:"http://open-collaboration-services.org/ns"};function U(e,r={nc:"http://nextcloud.org/ns"}){d.davNamespaces??={...w},d.davProperties??=[...$];const i={...d.davNamespaces,...r};if(d.davProperties.find(t=>t===e))return u.warn(`${e} already registered`,{prop:e}),!1;if(e.startsWith("<")||e.split(":").length!==2)return u.error(`${e} is not valid. See example: 'oc:fileid'`,{prop:e}),!1;const s=e.split(":")[0];return i[s]?(d.davProperties.push(e),d.davNamespaces=i,!0):(u.error(`${e} namespace unknown`,{prop:e,namespaces:i}),!1)}function h(){return d.davProperties??=[...$],d.davProperties.map(e=>`<${e} />`).join(" ")}function g(){return d.davNamespaces??={...w},Object.keys(d.davNamespaces).map(e=>`xmlns:${e}="${d.davNamespaces?.[e]}"`).join(" ")}function X(){return`<?xml version="1.0"?>
		<d:propfind ${g()}>
			<d:prop>
				${h()}
			</d:prop>
		</d:propfind>`}function z(){return`<?xml version="1.0"?>
		<oc:filter-files ${g()}>
			<d:prop>
				${h()}
			</d:prop>
			<oc:filter-rules>
				<oc:favorite>1</oc:favorite>
			</oc:filter-rules>
		</oc:filter-files>`}function G(e,r=100){const i=S(),s=i.dav?.search_supports_upload_time,t=i.dav?.search_supports_last_activity?"<nc:last_activity/>":"<d:getlastmodified/>";return`<?xml version="1.0" encoding="UTF-8"?>
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
				${s?`
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
</d:searchrequest>`}function j(){return f()?`/files/${A()}`:`/files/${m()?.uid}`}const b=j();function L(){const e=E("dav");return f()?e.replace("remote.php","public.php"):e}const N=L();function W(e=N,r={}){const i=P(e,{headers:r});function s(t){i.setHeaders({...r,"X-Requested-With":"XMLHttpRequest",requesttoken:t??""})}return q(s),s(D()),T().patch("fetch",(t,n)=>{const a=n.headers;return a?.method&&(n.method=a.method,delete a.method),fetch(t,n)}),i}async function K(e={}){const r=e.client??W(),i=e.path??"/",s=e.davRoot??b;return(await r.getDirectoryContents(`${s}${i}`,{signal:e.signal,details:!0,data:z(),headers:{method:"REPORT"},includeSelf:!0})).data.filter(t=>t.filename!==i).map(t=>C(t,s))}function C(e,r=b,i=N){let s=m()?.uid;if(f())s=s??"anonymous";else if(!s)throw new Error("No user id found");const t=e.props,n=k(t?.permissions),a=String(t?.["owner-id"]||s),v=t.fileid||0,p=new Date(Date.parse(e.lastmod)),c=new Date(Date.parse(t.creationdate)),l={id:v,source:`${i}${e.filename}`,mtime:!isNaN(p.getTime())&&p.getTime()!==0?p:void 0,crtime:!isNaN(c.getTime())&&c.getTime()!==0?c:void 0,mime:e.mime||"application/octet-stream",displayname:t.displayname!==void 0?String(t.displayname):void 0,size:t?.size||Number.parseInt(t.getcontentlength||"0"),status:v<0?x.FAILED:void 0,permissions:n,owner:a,root:r,attributes:{...e,...t,hasPreview:t?.["has-preview"]}};return delete l.attributes?.props,e.type==="file"?new _(l):new R(l)}export{g as a,h as b,U as c,N as d,L as e,j as f,W as g,K as h,b as i,G as j,X as k,C as r};
//# sourceMappingURL=dav-C9tbwQva.chunk.mjs.map
