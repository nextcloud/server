import{c as T}from"./index-CeZOua3E.chunk.mjs";import{g as q,a as w}from"./index-xFugdZPW.chunk.mjs";import{r as p,v as f,w as m,_ as x,b as s,a as y}from"./createElementId-DhjFt1I9-CbtAsEAv.chunk.mjs";import{l as b}from"./NcNoteCard-Cok_4Fld-CEiA7MRo.chunk.mjs";import{b as L,p as S,q as B,s as C,j as r,l as h,o as l,z as c,n as D,k as I,t as P,y as M}from"./runtime-dom.esm-bundler-CBTFVsZ1.chunk.mjs";const Z=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-away, var(--color-warning, #C88800))"
		d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,X=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-busy, var(--color-error, #DB0606))"
		d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`,_=`<!--
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
`,H=`<!--
  - SPDX-FileCopyrightText: 2020 Google Inc.
  - SPDX-License-Identifier: Apache-2.0
-->
<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">
	<path
		fill="var(--user-status-color-online, var(--color-success, #2D7B41))"
		d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>
</svg>
`;p(f),p(m);function k(a){switch(a){case"away":return s("away");case"busy":return s("busy");case"dnd":return s("do not disturb");case"online":return s("online");case"invisible":return s("invisible");case"offline":return s("offline");default:return a}}const A=["aria-hidden","aria-label","innerHTML"],F=L({__name:"NcUserStatusIcon",props:S({user:{default:void 0},ariaHidden:{type:[Boolean,String],default:!1}},{status:{},statusModifiers:{}}),emits:["update:status"],setup(a){const e=B(a,"status"),t=a,v=c(()=>e.value&&["invisible","offline"].includes(e.value)),o=c(()=>e.value&&(!t.ariaHidden||t.ariaHidden==="false")?s("User status: {status}",{status:k(e.value)}):void 0);C(()=>t.user,async u=>{if(!e.value&&u&&q()?.user_status?.enabled)try{const{data:i}=await T.get(y("/apps/user_status/api/v1/statuses/{user}",{user:u}));e.value=i.ocs?.data?.status}catch(i){b.debug("Error while fetching user status",{error:i})}},{immediate:!0});const d={online:H,away:Z,busy:X,dnd:_,invisible:g,offline:g},n=c(()=>e.value&&d[e.value]);return(u,i)=>e.value?(l(),r("span",{key:0,class:D(["user-status-icon",{"user-status-icon--invisible":v.value}]),"aria-hidden":!o.value||void 0,"aria-label":o.value,role:"img",innerHTML:n.value},null,10,A)):h("",!0)}}),Q=x(F,[["__scopeId","data-v-881a79fb"]]),G={name:"PencilOutlineIcon",emits:["click"],props:{title:{type:String},fillColor:{type:String,default:"currentColor"},size:{type:Number,default:24}}},z=["aria-hidden","aria-label"],N=["fill","width","height"],U={d:"M14.06,9L15,9.94L5.92,19H5V18.08L14.06,9M17.66,3C17.41,3 17.15,3.1 16.96,3.29L15.13,5.12L18.88,8.87L20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18.17,3.09 17.92,3 17.66,3M14.06,6.19L3,17.25V21H6.75L17.81,9.94L14.06,6.19Z"},V={key:0};function $(a,e,t,v,o,d){return l(),r("span",M(a.$attrs,{"aria-hidden":t.title?null:"true","aria-label":t.title,class:"material-design-icon pencil-outline-icon",role:"img",onClick:e[0]||(e[0]=n=>a.$emit("click",n))}),[(l(),r("svg",{fill:t.fillColor,class:"material-design-icon__svg",width:t.size,height:t.size,viewBox:"0 0 24 24"},[I("path",U,[t.title?(l(),r("title",V,P(t.title),1)):h("",!0)])],8,N))],16,z)}const R=w(G,[["render",$]]);export{Q as N,R as P,k as g};
//# sourceMappingURL=PencilOutline-DoqPbti1.chunk.mjs.map
