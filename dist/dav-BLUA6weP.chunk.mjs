import{a as m,b as E,o as q}from"./index-Bndk0DrU.chunk.mjs";import{h as D}from"./createElementId-DhjFt1I9-BQ18DTJF.chunk.mjs";import{l as y}from"./index-Ma7sfat2.chunk.mjs";import{l as P,_ as T}from"./index-B1tM2OGU.chunk.mjs";import{s as i,l as u,N as x,a as R,b as S,P as o}from"./public-Dw51J06r.chunk.mjs";function f(){return y("files_sharing","isPublic",null)??document.querySelector('input#isPublic[type="hidden"][name="isPublic"][value="1"]')!==null}function A(){return y("files_sharing","sharingToken",null)??document.querySelector('input#sharingToken[type="hidden"]')?.value??null}function k(e=""){let r=o.NONE;return e&&(e.includes("G")&&(r|=o.READ),e.includes("W")&&(r|=o.WRITE),e.includes("CK")&&(r|=o.CREATE),e.includes("NV")&&(r|=o.UPDATE),e.includes("D")&&(r|=o.DELETE),e.includes("R")&&(r|=o.SHARE)),r}const w=["d:getcontentlength","d:getcontenttype","d:getetag","d:getlastmodified","d:creationdate","d:displayname","d:quota-available-bytes","d:resourcetype","nc:has-preview","nc:is-encrypted","nc:mount-type","oc:comments-unread","oc:favorite","oc:fileid","oc:owner-display-name","oc:owner-id","oc:permissions","oc:size"],$={d:"DAV:",nc:"http://nextcloud.org/ns",oc:"http://owncloud.org/ns",ocs:"http://open-collaboration-services.org/ns"};function U(e,r={nc:"http://nextcloud.org/ns"}){i.davNamespaces??={...$},i.davProperties??=[...w];const s={...i.davNamespaces,...r};if(i.davProperties.find(t=>t===e))return u.warn(`${e} already registered`,{prop:e}),!1;if(e.startsWith("<")||e.split(":").length!==2)return u.error(`${e} is not valid. See example: 'oc:fileid'`,{prop:e}),!1;const n=e.split(":")[0];return s[n]?(i.davProperties.push(e),i.davNamespaces=s,!0):(u.error(`${e} namespace unknown`,{prop:e,namespaces:s}),!1)}function h(){return i.davProperties??=[...w],i.davProperties.map(e=>`<${e} />`).join(" ")}function g(){return i.davNamespaces??={...$},Object.keys(i.davNamespaces).map(e=>`xmlns:${e}="${i.davNamespaces?.[e]}"`).join(" ")}function _(){return`<?xml version="1.0"?>
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
		</oc:filter-files>`}function X(e){return`<?xml version="1.0" encoding="UTF-8"?>
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
				<d:gt>
					<d:prop>
						<d:getlastmodified/>
					</d:prop>
					<d:literal>${e}</d:literal>
				</d:gt>
			</d:and>
		</d:where>
		<d:orderby>
			<d:order>
				<d:prop>
					<d:getlastmodified/>
				</d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>100</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`}function z(){return f()?`/files/${A()}`:`/files/${m()?.uid}`}const b=z();function W(){const e=D("dav");return f()?e.replace("remote.php","public.php"):e}const N=W();function C(e=N,r={}){const s=P(e,{headers:r});function n(t){s.setHeaders({...r,"X-Requested-With":"XMLHttpRequest",requesttoken:t??""})}return q(n),n(E()),T().patch("fetch",(t,d)=>{const a=d.headers;return a?.method&&(d.method=a.method,delete a.method),fetch(t,d)}),s}async function G(e={}){const r=e.client??C(),s=e.path??"/",n=e.davRoot??b;return(await r.getDirectoryContents(`${n}${s}`,{signal:e.signal,details:!0,data:j(),headers:{method:"REPORT"},includeSelf:!0})).data.filter(t=>t.filename!==s).map(t=>H(t,n))}function H(e,r=b,s=N){let n=m()?.uid;if(f())n=n??"anonymous";else if(!n)throw new Error("No user id found");const t=e.props,d=k(t?.permissions),a=String(t?.["owner-id"]||n),v=t.fileid||0,c=new Date(Date.parse(e.lastmod)),p=new Date(Date.parse(t.creationdate)),l={id:v,source:`${s}${e.filename}`,mtime:!isNaN(c.getTime())&&c.getTime()!==0?c:void 0,crtime:!isNaN(p.getTime())&&p.getTime()!==0?p:void 0,mime:e.mime||"application/octet-stream",displayname:t.displayname!==void 0?String(t.displayname):void 0,size:t?.size||Number.parseInt(t.getcontentlength||"0"),status:v<0?x.FAILED:void 0,permissions:d,owner:a,root:r,attributes:{...e,...t,hasPreview:t?.["has-preview"]}};return delete l.attributes?.props,e.type==="file"?new R(l):new S(l)}export{g as a,h as b,U as c,N as d,W as e,z as f,C as g,G as h,b as i,X as j,_ as k,H as r};
//# sourceMappingURL=dav-BLUA6weP.chunk.mjs.map
