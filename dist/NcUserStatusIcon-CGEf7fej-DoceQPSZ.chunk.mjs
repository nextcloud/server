import{c as T}from"./index-SiCbjwnT.chunk.mjs";import{g as p,l as g}from"./NcNoteCard-CVhtNL04-Bouqxu_R.chunk.mjs";import{r as l,b as q,_ as h,t as s,g as w}from"./createElementId-DhjFt1I9-sbdxHOjK.chunk.mjs";import{b as f,m as x,i as m,l as o,j as b,s as y,v as D,o as B,x as I}from"./preload-helper-BcKx2dRj.chunk.mjs";const S=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-away, var(--color-warning, #C88800))"
		d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,P=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-busy, var(--color-error, #DB0606))"
		d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,X=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-busy, var(--color-error, #DB0606))"
		d="M280-440h400v-80H280v80ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,u=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-offline, var(--color-text-maxcontrast, #6B6B6B))"
		d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
</svg>
`,Z=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-online, var(--color-success, #2D7B41))"
		d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`;l(),l(q);function M(a){switch(a){case"away":return s("away");case"busy":return s("busy");case"dnd":return s("do not disturb");case"online":return s("online");case"invisible":return s("invisible");case"offline":return s("offline");default:return a}}const L=["aria-hidden","aria-label","innerHTML"],_=f({__name:"NcUserStatusIcon",props:x({user:{default:void 0},ariaHidden:{type:[Boolean,String],default:!1}},{status:{},statusModifiers:{}}),emits:["update:status"],setup(a){const e=m(a,"status"),i=a,v=o(()=>e.value&&["invisible","offline"].includes(e.value)),n=o(()=>e.value&&(!i.ariaHidden||i.ariaHidden==="false")?s("User status: {status}",{status:M(e.value)}):void 0);b(()=>i.user,async r=>{if(!e.value&&r&&p()?.user_status?.enabled)try{const{data:t}=await T.get(w("/apps/user_status/api/v1/statuses/{user}",{user:r}));e.value=t.ocs?.data?.status}catch(t){g.debug("Error while fetching user status",{error:t})}},{immediate:!0});const c={online:Z,away:S,busy:P,dnd:X,invisible:u,offline:u},d=o(()=>e.value&&c[e.value]);return(r,t)=>e.value?(B(),y("span",{key:0,class:I(["user-status-icon",{"user-status-icon--invisible":v.value}]),"aria-hidden":!n.value||void 0,"aria-label":n.value,role:"img",innerHTML:d.value},null,10,L)):D("",!0)}}),G=h(_,[["__scopeId","data-v-881a79fb"]]);export{G as N,M as g};
//# sourceMappingURL=NcUserStatusIcon-CGEf7fej-DoceQPSZ.chunk.mjs.map
