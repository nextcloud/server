import{d as e}from"./dist-BEXrfIzO.chunk.mjs";import{O as t,P as n,S as r,U as i,at as a,kt as o,tt as s,v as c,x as l}from"./vue.runtime.esm-bundler-R-TRboFG.chunk.mjs";import{t as u}from"./logger-D3RVzcfQ-D5Bg2La4.chunk.mjs";import{t as d}from"./dist-CjlPnIS8.chunk.mjs";import{a as f,et as p,o as m,r as h,u as g}from"./createElementId-DhjFt1I9-nqaih5Pu.chunk.mjs";import{t as _}from"./dist-g-sAiDQH.chunk.mjs";var v=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-away, var(--color-warning, #C88800))"
		d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,y=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-busy, var(--color-error, #DB0606))"
		d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,b=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-busy, var(--color-error, #DB0606))"
		d="M280-440h400v-80H280v80ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,x=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-offline, var(--color-text-maxcontrast, #6B6B6B))"
		d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
</svg>
`,S=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-online, var(--color-success, #2D7B41))"
		d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`;f(p),f(g);function C(e){switch(e){case`away`:return m(`away`);case`busy`:return m(`busy`);case`dnd`:return m(`do not disturb`);case`online`:return m(`online`);case`invisible`:return m(`invisible`);case`offline`:return m(`offline`);default:return e}}var w=[`aria-hidden`,`aria-label`,`innerHTML`],T=h(t({__name:`NcUserStatusIcon`,props:n({user:{default:void 0},ariaHidden:{type:[Boolean,String],default:!1}},{status:{},statusModifiers:{}}),emits:[`update:status`],setup(t){let n=s(t,`status`),f=t,p=c(()=>n.value&&[`invisible`,`offline`].includes(n.value)),h=c(()=>n.value&&(!f.ariaHidden||f.ariaHidden===`false`)?m(`User status: {status}`,{status:C(n.value)}):void 0);a(()=>f.user,async t=>{if(!n.value&&t&&d()?.user_status?.enabled)try{let{data:r}=await _.get(e(`/apps/user_status/api/v1/statuses/{user}`,{user:t}));n.value=r.ocs?.data?.status}catch(e){u.debug(`Error while fetching user status`,{error:e})}},{immediate:!0});let g={online:S,away:v,busy:y,dnd:b,invisible:x,offline:x},T=c(()=>n.value&&g[n.value]);return(e,t)=>n.value?(i(),r(`span`,{key:0,class:o([`user-status-icon`,{"user-status-icon--invisible":p.value}]),"aria-hidden":!h.value||void 0,"aria-label":h.value,role:`img`,innerHTML:T.value},null,10,w)):l(``,!0)}}),[[`__scopeId`,`data-v-881a79fb`]]);export{C as n,T as t};
//# sourceMappingURL=NcUserStatusIcon-DsviB2Cr-CBUQMkzS.chunk.mjs.map