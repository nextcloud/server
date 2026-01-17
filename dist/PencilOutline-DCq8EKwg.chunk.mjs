import{c as T}from"./index-BfylblLb.chunk.mjs";import{g as q,a as f}from"./index-xFugdZPW.chunk.mjs";import{r as p,m as w,_ as m,t as s,a as x}from"./createElementId-DhjFt1I9--Zqj3wLs.chunk.mjs";import{l as y}from"./NcNoteCard-CVhtNL04-hwuc093N.chunk.mjs";import{b,p as L,q as S,x as c,s as B,j as r,l as h,o as l,n as C,k as D,t as I,z as P}from"./runtime-dom.esm-bundler-BrYCUcZF.chunk.mjs";const M=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-away, var(--color-warning, #C88800))"
		d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,Z=`<!--
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
`,g=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-offline, var(--color-text-maxcontrast, #6B6B6B))"
		d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
</svg>
`,_=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-online, var(--color-success, #2D7B41))"
		d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`;p(),p(w);function H(a){switch(a){case"away":return s("away");case"busy":return s("busy");case"dnd":return s("do not disturb");case"online":return s("online");case"invisible":return s("invisible");case"offline":return s("offline");default:return a}}const k=["aria-hidden","aria-label","innerHTML"],A=b({__name:"NcUserStatusIcon",props:L({user:{default:void 0},ariaHidden:{type:[Boolean,String],default:!1}},{status:{},statusModifiers:{}}),emits:["update:status"],setup(a){const e=S(a,"status"),t=a,d=c(()=>e.value&&["invisible","offline"].includes(e.value)),o=c(()=>e.value&&(!t.ariaHidden||t.ariaHidden==="false")?s("User status: {status}",{status:H(e.value)}):void 0);B(()=>t.user,async u=>{if(!e.value&&u&&q()?.user_status?.enabled)try{const{data:i}=await T.get(x("/apps/user_status/api/v1/statuses/{user}",{user:u}));e.value=i.ocs?.data?.status}catch(i){y.debug("Error while fetching user status",{error:i})}},{immediate:!0});const v={online:_,away:M,busy:Z,dnd:X,invisible:g,offline:g},n=c(()=>e.value&&v[e.value]);return(u,i)=>e.value?(l(),r("span",{key:0,class:C(["user-status-icon",{"user-status-icon--invisible":d.value}]),"aria-hidden":!o.value||void 0,"aria-label":o.value,role:"img",innerHTML:n.value},null,10,k)):h("",!0)}}),K=m(A,[["__scopeId","data-v-881a79fb"]]),F={name:"PencilOutlineIcon",emits:["click"],props:{title:{type:String},fillColor:{type:String,default:"currentColor"},size:{type:Number,default:24}}},G=["aria-hidden","aria-label"],z=["fill","width","height"],N={d:"M14.06,9L15,9.94L5.92,19H5V18.08L14.06,9M17.66,3C17.41,3 17.15,3.1 16.96,3.29L15.13,5.12L18.88,8.87L20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18.17,3.09 17.92,3 17.66,3M14.06,6.19L3,17.25V21H6.75L17.81,9.94L14.06,6.19Z"},U={key:0};function V(a,e,t,d,o,v){return l(),r("span",P(a.$attrs,{"aria-hidden":t.title?null:"true","aria-label":t.title,class:"material-design-icon pencil-outline-icon",role:"img",onClick:e[0]||(e[0]=n=>a.$emit("click",n))}),[(l(),r("svg",{fill:t.fillColor,class:"material-design-icon__svg",width:t.size,height:t.size,viewBox:"0 0 24 24"},[D("path",N,[t.title?(l(),r("title",U,I(t.title),1)):h("",!0)])],8,z))],16,G)}const Q=f(F,[["render",V]]);export{K as N,Q as P,H as g};
//# sourceMappingURL=PencilOutline-DCq8EKwg.chunk.mjs.map
