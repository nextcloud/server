import{c as u}from"./index-DUuegQtr.chunk.mjs";import{g as y,e as c}from"./index-6_gsQFyp.chunk.mjs";import{t as o}from"./translation-DoG5ZELJ-2ffMJaM4.chunk.mjs";import{c as f}from"./index-C0kS3IP3.chunk.mjs";import{a as h}from"./createElementId-DhjFt1I9-Bjk2333q.chunk.mjs";import{g as b}from"./dav-BYbKV7ND.chunk.mjs";const r=y().setApp("systemtags").detectUser().build(),V={userVisible:!0,userAssignable:!0,canAssign:!0},x=Object.freeze({"display-name":"displayName","user-visible":"userVisible","user-assignable":"userAssignable","can-assign":"canAssign"});function p(t){return t.map(({props:s})=>Object.fromEntries(Object.entries(s).map(([e,a])=>(e=x[e]??e,a=e==="displayName"?String(a):a,[e,a]))))}function w(t){const s=t.indexOf("?");s>0&&(t=t.substring(0,s));const e=t.split("/");let a;do a=e[e.length-1],e.pop();while(!a&&e.length>0);return Number(a)}function v(t){if("name"in t&&!("displayName"in t))return{...t};const s={...t};return s.name=s.displayName,delete s.displayName,s}function N(t){const s=t.attributes?.["system-tags"]?.["system-tag"];return s===void 0?[]:[s].flat().map(e=>typeof e=="string"?e:e.text)}function P(t,s){t.attributes["system-tags"]={"system-tag":s},c("files:node:updated",t)}const n=b(),l=`<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
	<d:prop>
		<oc:id />
		<oc:display-name />
		<oc:user-visible />
		<oc:user-assignable />
		<oc:can-assign />
		<d:getetag />
		<nc:color />
	</d:prop>
</d:propfind>`;async function D(){const t="/systemtags";try{const{data:s}=await n.getDirectoryContents(t,{data:l,details:!0,glob:"/systemtags/*"});return p(s)}catch(s){throw r.error(o("systemtags","Failed to load tags"),{error:s}),new Error(o("systemtags","Failed to load tags"))}}async function C(t){const s="/systemtags/"+t;try{const{data:e}=await n.stat(s,{data:l,details:!0});return p([e])[0]}catch(e){throw r.error(o("systemtags","Failed to load tag"),{error:e}),new Error(o("systemtags","Failed to load tag"))}}async function R(t){const s="/systemtags",e=v(t);try{const{headers:a}=await n.customRequest(s,{method:"POST",data:e}),i=a.get("content-location");if(i)return c("systemtags:tag:created",t),w(i);throw r.error(o("systemtags",'Missing "Content-Location" header')),new Error(o("systemtags",'Missing "Content-Location" header'))}catch(a){throw a?.response?.status===409?(r.error(o("systemtags","A tag with the same name already exists"),{error:a}),new Error(o("systemtags","A tag with the same name already exists"))):(r.error(o("systemtags","Failed to create tag"),{error:a}),new Error(o("systemtags","Failed to create tag")))}}async function T(t){const s="/systemtags/"+t.id,e=`<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<oc:display-name>${t.displayName}</oc:display-name>
				<oc:user-visible>${t.userVisible}</oc:user-visible>
				<oc:user-assignable>${t.userAssignable}</oc:user-assignable>
				<nc:color>${t?.color||null}</nc:color>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;try{await n.customRequest(s,{method:"PROPPATCH",data:e}),c("systemtags:tag:updated",t)}catch(a){throw r.error(o("systemtags","Failed to update tag"),{error:a}),new Error(o("systemtags","Failed to update tag"))}}async function _(t){const s="/systemtags/"+t.id;try{await n.deleteFile(s),c("systemtags:tag:deleted",t)}catch(e){throw r.error(o("systemtags","Failed to delete tag"),{error:e}),new Error(o("systemtags","Failed to delete tag"))}}async function q(t,s){const e=`/systemtags/${t.id}/${s}`,a=await n.stat(e,{data:`<?xml version="1.0"?>
	<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:prop>
			<nc:object-ids />
			<d:getetag />
		</d:prop>
	</d:propfind>`,details:!0}),i=a?.data?.props?.getetag||'""',d=Object.values(a?.data?.props?.["object-ids"]||[]).flat();return{etag:i,objects:d}}async function k(t,s,e,a=""){const i=`/systemtags/${t.id}/${s}`;let d=`<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<nc:object-ids>${e.map(({id:m,type:g})=>`<nc:object-id><nc:id>${m}</nc:id><nc:type>${g}</nc:type></nc:object-id>`).join("")}</nc:object-ids>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;e.length===0&&(d=`<?xml version="1.0"?>
		<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
			<d:remove>
				<d:prop>
					<nc:object-ids />
				</d:prop>
			</d:remove>
		</d:propertyupdate>`),await n.customRequest(i,{method:"PROPPATCH",data:d,headers:{"if-match":a}})}async function H(t){const s=t?"1":"0",e=h("/apps/provisioning_api/api/v1/config/apps/{appId}/{key}",{appId:"systemtags",key:"restrict_creation_to_admin"});await f();const{data:a}=await u.post(e,{value:s});return a}export{_ as a,H as b,R as c,V as d,N as e,D as f,q as g,P as h,C as i,r as l,k as s,T as u};
//# sourceMappingURL=api-Cia7d5Ij.chunk.mjs.map
