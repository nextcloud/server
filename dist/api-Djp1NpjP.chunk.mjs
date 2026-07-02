import{d as e,o as t}from"./dist-BEXrfIzO.chunk.mjs";import{l as n}from"./dist-DILHJxlx.chunk.mjs";import{t as r}from"./dist-Br80UYjC.chunk.mjs";import{t as i}from"./dist-g-sAiDQH.chunk.mjs";import{r as a}from"./dist-b7ISGQwW.chunk.mjs";import{r as o}from"./dav-DuDWqhEc.chunk.mjs";var s=r().setApp(`systemtags`).detectUser().build(),c={userVisible:!0,userAssignable:!0,canAssign:!0},l=Object.freeze({"display-name":`displayName`,"user-visible":`userVisible`,"user-assignable":`userAssignable`,"can-assign":`canAssign`});function u(e){return e.map(({props:e})=>Object.fromEntries(Object.entries(e).map(([e,t])=>(e=l[e]??e,t=e===`displayName`?String(t):t,[e,t]))))}function d(e){let t=e.indexOf(`?`);t>0&&(e=e.substring(0,t));let n=e.split(`/`),r;do r=n[n.length-1],n.pop();while(!r&&n.length>0);return Number(r)}function f(e){if(`name`in e&&!(`displayName`in e))return{...e};let t={...e};return t.name=t.displayName,delete t.displayName,t}function p(e){let t=e.attributes?.[`system-tags`]?.[`system-tag`];return t===void 0?[]:[t].flat().map(e=>typeof e==`string`?e:e.text)}function m(e,n){e.attributes[`system-tags`]={"system-tag":n},t(`files:node:updated`,e)}var h=o(),g=`<?xml version="1.0"?>
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
</d:propfind>`;async function _(){try{let{data:e}=await h.getDirectoryContents(`/systemtags`,{data:g,details:!0,glob:`/systemtags/*`});return u(e)}catch(e){throw s.error(n(`systemtags`,`Failed to load tags`),{error:e}),Error(n(`systemtags`,`Failed to load tags`),{cause:e})}}async function v(e){let t=`/systemtags/`+e;try{let{data:e}=await h.stat(t,{data:g,details:!0});return u([e])[0]}catch(e){throw s.error(n(`systemtags`,`Failed to load tag`),{error:e}),Error(n(`systemtags`,`Failed to load tag`),{cause:e})}}async function y(e){let r=f(e);try{let{headers:i}=await h.customRequest(`/systemtags`,{method:`POST`,data:r}),a=i.get(`content-location`);if(a)return t(`systemtags:tag:created`,e),d(a);throw s.error(n(`systemtags`,`Missing "Content-Location" header`)),Error(n(`systemtags`,`Missing "Content-Location" header`))}catch(e){throw e?.response?.status===409?(s.error(n(`systemtags`,`A tag with the same name already exists`),{error:e}),Error(n(`systemtags`,`A tag with the same name already exists`),{cause:e})):(s.error(n(`systemtags`,`Failed to create tag`),{error:e}),Error(n(`systemtags`,`Failed to create tag`),{cause:e}))}}async function b(e){let r=`/systemtags/`+e.id,i=`<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<oc:display-name>${e.displayName}</oc:display-name>
				<oc:user-visible>${e.userVisible}</oc:user-visible>
				<oc:user-assignable>${e.userAssignable}</oc:user-assignable>
				<nc:color>${e?.color||null}</nc:color>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;try{await h.customRequest(r,{method:`PROPPATCH`,data:i}),t(`systemtags:tag:updated`,e)}catch(e){throw s.error(n(`systemtags`,`Failed to update tag`),{error:e}),Error(n(`systemtags`,`Failed to update tag`),{cause:e})}}async function x(e){let r=`/systemtags/`+e.id;try{await h.deleteFile(r),t(`systemtags:tag:deleted`,e)}catch(e){throw s.error(n(`systemtags`,`Failed to delete tag`),{error:e}),Error(n(`systemtags`,`Failed to delete tag`),{cause:e})}}async function S(e,t){let n=`/systemtags/${e.id}/${t}`,r=await h.stat(n,{data:`<?xml version="1.0"?>
	<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:prop>
			<nc:object-ids />
			<d:getetag />
		</d:prop>
	</d:propfind>`,details:!0});return{etag:r?.data?.props?.getetag||`""`,objects:Object.values(r?.data?.props?.[`object-ids`]||[]).flat()}}async function C(e,t,n,r=``){let i=`/systemtags/${e.id}/${t}`,a=`<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<nc:object-ids>${n.map(({id:e,type:t})=>`<nc:object-id><nc:id>${e}</nc:id><nc:type>${t}</nc:type></nc:object-id>`).join(``)}</nc:object-ids>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;n.length===0&&(a=`<?xml version="1.0"?>
		<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
			<d:remove>
				<d:prop>
					<nc:object-ids />
				</d:prop>
			</d:remove>
		</d:propertyupdate>`),await h.customRequest(i,{method:`PROPPATCH`,data:a,headers:{"if-match":r}})}async function w(t){let n=t?`1`:`0`,r=e(`/apps/provisioning_api/api/v1/config/apps/{appId}/{key}`,{appId:`systemtags`,key:`restrict_creation_to_admin`});await a();let{data:o}=await i.post(r,{value:n});return o}export{S as a,b as c,m as d,s as f,_ as i,c as l,x as n,C as o,v as r,w as s,y as t,p as u};
//# sourceMappingURL=api-Djp1NpjP.chunk.mjs.map