(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["settings-apps-view"],{

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationSpacer.js":
/*!******************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationSpacer.js ***!
  \******************************************************************************/
/***/ (function(module) {

/*! For license information please see NcAppNavigationSpacer.js.LICENSE.txt */
!function(e,t){ true?module.exports=t():0}(self,(()=>(()=>{"use strict";var e={5999:(e,t,n)=>{n.d(t,{Z:()=>s});var r=n(7537),o=n.n(r),a=n(3645),i=n.n(a)()(o());i.push([e.id,"\n.app-navigation-spacer[data-v-e9226d86] {\n\tflex-shrink: 0;\n\torder: 1;\n\theight: 22px;\n}\n\n","",{version:3,sources:["webpack://./src/components/NcAppNavigationSpacer/NcAppNavigationSpacer.vue"],names:[],mappings:";AAwCA;CACA,cAAA;CACA,QAAA;CACA,YAAA;AACA",sourcesContent:["\x3c!--\n - @copyright Copyright (c) 2019 Christoph Wurst <christoph@winzerhof-wurst.at>\n -\n - @author Christoph Wurst <christoph@winzerhof-wurst.at>\n -\n - @license GNU AGPL version 3 or any later version\n -\n - This program is free software: you can redistribute it and/or modify\n - it under the terms of the GNU Affero General Public License as\n - published by the Free Software Foundation, either version 3 of the\n - License, or (at your option) any later version.\n -\n - This program is distributed in the hope that it will be useful,\n - but WITHOUT ANY WARRANTY; without even the implied warranty of\n - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n - GNU Affero General Public License for more details.\n -\n - You should have received a copy of the GNU Affero General Public License\n - along with this program. If not, see <http://www.gnu.org/licenses/>.\n -\n --\x3e\n<template>\n\t<li class=\"app-navigation-spacer\" />\n</template>\n\n<script>\nexport default {\n\tname: 'NcAppNavigationSpacer',\n}\n<\/script>\n\n<style scoped>\n\t.app-navigation-spacer {\n\t\tflex-shrink: 0;\n\t\torder: 1;\n\t\theight: 22px;\n\t}\n\n</style>\n"],sourceRoot:""}]);const s=i},3645:e=>{e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var n="",r=void 0!==t[5];return t[4]&&(n+="@supports (".concat(t[4],") {")),t[2]&&(n+="@media ".concat(t[2]," {")),r&&(n+="@layer".concat(t[5].length>0?" ".concat(t[5]):""," {")),n+=e(t),r&&(n+="}"),t[2]&&(n+="}"),t[4]&&(n+="}"),n})).join("")},t.i=function(e,n,r,o,a){"string"==typeof e&&(e=[[null,e,void 0]]);var i={};if(r)for(var s=0;s<this.length;s++){var c=this[s][0];null!=c&&(i[c]=!0)}for(var p=0;p<e.length;p++){var u=[].concat(e[p]);r&&i[u[0]]||(void 0!==a&&(void 0===u[5]||(u[1]="@layer".concat(u[5].length>0?" ".concat(u[5]):""," {").concat(u[1],"}")),u[5]=a),n&&(u[2]?(u[1]="@media ".concat(u[2]," {").concat(u[1],"}"),u[2]=n):u[2]=n),o&&(u[4]?(u[1]="@supports (".concat(u[4],") {").concat(u[1],"}"),u[4]=o):u[4]="".concat(o)),t.push(u))}},t}},7537:e=>{e.exports=function(e){var t=e[1],n=e[3];if(!n)return t;if("function"==typeof btoa){var r=btoa(unescape(encodeURIComponent(JSON.stringify(n)))),o="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(r),a="/*# ".concat(o," */");return[t].concat([a]).join("\n")}return[t].join("\n")}},3379:e=>{var t=[];function n(e){for(var n=-1,r=0;r<t.length;r++)if(t[r].identifier===e){n=r;break}return n}function r(e,r){for(var a={},i=[],s=0;s<e.length;s++){var c=e[s],p=r.base?c[0]+r.base:c[0],u=a[p]||0,l="".concat(p," ").concat(u);a[p]=u+1;var d=n(l),f={css:c[1],media:c[2],sourceMap:c[3],supports:c[4],layer:c[5]};if(-1!==d)t[d].references++,t[d].updater(f);else{var h=o(f,r);r.byIndex=s,t.splice(s,0,{identifier:l,updater:h,references:1})}i.push(l)}return i}function o(e,t){var n=t.domAPI(t);n.update(e);return function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap&&t.supports===e.supports&&t.layer===e.layer)return;n.update(e=t)}else n.remove()}}e.exports=function(e,o){var a=r(e=e||[],o=o||{});return function(e){e=e||[];for(var i=0;i<a.length;i++){var s=n(a[i]);t[s].references--}for(var c=r(e,o),p=0;p<a.length;p++){var u=n(a[p]);0===t[u].references&&(t[u].updater(),t.splice(u,1))}a=c}}},569:e=>{var t={};e.exports=function(e,n){var r=function(e){if(void 0===t[e]){var n=document.querySelector(e);if(window.HTMLIFrameElement&&n instanceof window.HTMLIFrameElement)try{n=n.contentDocument.head}catch(e){n=null}t[e]=n}return t[e]}(e);if(!r)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");r.appendChild(n)}},9216:e=>{e.exports=function(e){var t=document.createElement("style");return e.setAttributes(t,e.attributes),e.insert(t,e.options),t}},3565:(e,t,n)=>{e.exports=function(e){var t=n.nc;t&&e.setAttribute("nonce",t)}},7795:e=>{e.exports=function(e){if("undefined"==typeof document)return{update:function(){},remove:function(){}};var t=e.insertStyleElement(e);return{update:function(n){!function(e,t,n){var r="";n.supports&&(r+="@supports (".concat(n.supports,") {")),n.media&&(r+="@media ".concat(n.media," {"));var o=void 0!==n.layer;o&&(r+="@layer".concat(n.layer.length>0?" ".concat(n.layer):""," {")),r+=n.css,o&&(r+="}"),n.media&&(r+="}"),n.supports&&(r+="}");var a=n.sourceMap;a&&"undefined"!=typeof btoa&&(r+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(a))))," */")),t.styleTagTransform(r,e,t.options)}(t,e,n)},remove:function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(t)}}}},4589:e=>{e.exports=function(e,t){if(t.styleSheet)t.styleSheet.cssText=e;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(e))}}},1900:(e,t,n)=>{function r(e,t,n,r,o,a,i,s){var c,p="function"==typeof e?e.options:e;if(t&&(p.render=t,p.staticRenderFns=n,p._compiled=!0),r&&(p.functional=!0),a&&(p._scopeId="data-v-"+a),i?(c=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),o&&o.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(i)},p._ssrRegister=c):o&&(c=s?function(){o.call(this,(p.functional?this.parent:this).$root.$options.shadowRoot)}:o),c)if(p.functional){p._injectStyles=c;var u=p.render;p.render=function(e,t){return c.call(t),u(e,t)}}else{var l=p.beforeCreate;p.beforeCreate=l?[].concat(l,c):[c]}return{exports:e,options:p}}n.d(t,{Z:()=>r})}},t={};function n(r){var o=t[r];if(void 0!==o)return o.exports;var a=t[r]={id:r,exports:{}};return e[r](a,a.exports,n),a.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.nc=void 0;var r={};return(()=>{n.r(r),n.d(r,{default:()=>y});const e={name:"NcAppNavigationSpacer"};var t=n(3379),o=n.n(t),a=n(7795),i=n.n(a),s=n(569),c=n.n(s),p=n(3565),u=n.n(p),l=n(9216),d=n.n(l),f=n(4589),h=n.n(f),v=n(5999),m={};m.styleTagTransform=h(),m.setAttributes=u(),m.insert=c().bind(null,"head"),m.domAPI=i(),m.insertStyleElement=d();o()(v.Z,m);v.Z&&v.Z.locals&&v.Z.locals;const y=(0,n(1900).Z)(e,(function(){return(0,this._self._c)("li",{staticClass:"app-navigation-spacer"})}),[],!1,null,"e9226d86",null).exports})(),r})()));
//# sourceMappingURL=NcAppNavigationSpacer.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.js ***!
  \*********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/*! For license information please see NcAppSidebar.js.LICENSE.txt */
!function(e,t){ true?module.exports=t():0}(self,(()=>(()=>{var e={8250:(e,t,a)=>{"use strict";a.d(t,{default:()=>T});var i=a(4462),n=a(2297),o=a(1205),r=a(932),s=a(2734),l=a.n(s),c=a(1441),d=a.n(c);const u=".focusable",p={name:"NcActions",components:{NcButton:i.default,DotsHorizontal:d(),NcPopover:n.default},props:{open:{type:Boolean,default:!1},manualOpen:{type:Boolean,default:!1},forceMenu:{type:Boolean,default:!1},forceName:{type:Boolean,default:!1},menuName:{type:String,default:null},primary:{type:Boolean,default:!1},type:{type:String,validator:e=>-1!==["primary","secondary","tertiary","tertiary-no-background","tertiary-on-primary","error","warning","success"].indexOf(e),default:null},defaultIcon:{type:String,default:""},ariaLabel:{type:String,default:(0,r.t)("Actions")},ariaHidden:{type:Boolean,default:null},placement:{type:String,default:"bottom"},boundariesElement:{type:Element,default:()=>document.querySelector("body")},container:{type:[String,Object,Element,Boolean],default:"body"},disabled:{type:Boolean,default:!1},inline:{type:Number,default:0}},emits:["open","update:open","close","focus","blur"],data(){return{opened:this.open,focusIndex:0,randomId:"menu-".concat((0,o.Z)())}},computed:{triggerBtnType(){return this.type||(this.primary?"primary":this.menuName?"secondary":"tertiary")}},watch:{open(e){e!==this.opened&&(this.opened=e)}},methods:{isValidSingleAction(e){var t,a,i,n,o;const r=null!==(t=null==e||null===(a=e.componentOptions)||void 0===a||null===(i=a.Ctor)||void 0===i||null===(n=i.extendOptions)||void 0===n?void 0:n.name)&&void 0!==t?t:null==e||null===(o=e.componentOptions)||void 0===o?void 0:o.tag;return["NcActionButton","NcActionLink","NcActionRouter"].includes(r)},openMenu(e){this.opened||(this.opened=!0,this.$emit("update:open",!0),this.$emit("open"))},closeMenu(){let e=!(arguments.length>0&&void 0!==arguments[0])||arguments[0];this.opened&&(this.opened=!1,this.$refs.popover.clearFocusTrap({returnFocus:e}),this.$emit("update:open",!1),this.$emit("close"),this.focusIndex=0,this.$refs.menuButton.$el.focus())},onOpen(e){this.$nextTick((()=>{this.focusFirstAction(e)}))},onMouseFocusAction(e){if(document.activeElement===e.target)return;const t=e.target.closest("li");if(t){const e=t.querySelector(u);if(e){const t=[...this.$refs.menu.querySelectorAll(u)].indexOf(e);t>-1&&(this.focusIndex=t,this.focusAction())}}},onKeydown(e){(38===e.keyCode||9===e.keyCode&&e.shiftKey)&&this.focusPreviousAction(e),(40===e.keyCode||9===e.keyCode&&!e.shiftKey)&&this.focusNextAction(e),33===e.keyCode&&this.focusFirstAction(e),34===e.keyCode&&this.focusLastAction(e),27===e.keyCode&&(this.closeMenu(),e.preventDefault())},removeCurrentActive(){const e=this.$refs.menu.querySelector("li.active");e&&e.classList.remove("active")},focusAction(){const e=this.$refs.menu.querySelectorAll(u)[this.focusIndex];if(e){this.removeCurrentActive();const t=e.closest("li.action");e.focus(),t&&t.classList.add("active")}},focusPreviousAction(e){this.opened&&(0===this.focusIndex?this.closeMenu():(this.preventIfEvent(e),this.focusIndex=this.focusIndex-1),this.focusAction())},focusNextAction(e){if(this.opened){const t=this.$refs.menu.querySelectorAll(u).length-1;this.focusIndex===t?this.closeMenu():(this.preventIfEvent(e),this.focusIndex=this.focusIndex+1),this.focusAction()}},focusFirstAction(e){this.opened&&(this.preventIfEvent(e),this.focusIndex=0,this.focusAction())},focusLastAction(e){this.opened&&(this.preventIfEvent(e),this.focusIndex=this.$refs.menu.querySelectorAll(u).length-1,this.focusAction())},preventIfEvent(e){e&&(e.preventDefault(),e.stopPropagation())},onFocus(e){this.$emit("focus",e)},onBlur(e){this.$emit("blur",e)}},render(e){const t=(this.$slots.default||[]).filter((e=>{var t,a,i,n;return(null==e||null===(t=e.componentOptions)||void 0===t?void 0:t.tag)||(null==e||null===(a=e.componentOptions)||void 0===a||null===(i=a.Ctor)||void 0===i||null===(n=i.extendOptions)||void 0===n?void 0:n.name)})),a=t.every((e=>{var t,a,i,n,o,r,s,l;return"NcActionLink"===(null!==(t=null==e||null===(a=e.componentOptions)||void 0===a||null===(i=a.Ctor)||void 0===i||null===(n=i.extendOptions)||void 0===n?void 0:n.name)&&void 0!==t?t:null==e||null===(o=e.componentOptions)||void 0===o?void 0:o.tag)&&(null==e||null===(r=e.componentOptions)||void 0===r||null===(s=r.propsData)||void 0===s||null===(l=s.href)||void 0===l?void 0:l.startsWith(window.location.origin))}));let i=t.filter(this.isValidSingleAction);if(this.forceMenu&&i.length>0&&this.inline>0&&(l().util.warn("Specifying forceMenu will ignore any inline actions rendering."),i=[]),0===t.length)return;const n=t=>{var a,i,n,o,r,s,l,c,d,u,p,A,m,h,g,v,b,C,f,y,k,w;const x=(null==t||null===(a=t.data)||void 0===a||null===(i=a.scopedSlots)||void 0===i||null===(n=i.icon())||void 0===n?void 0:n[0])||e("span",{class:["icon",null==t||null===(o=t.componentOptions)||void 0===o||null===(r=o.propsData)||void 0===r?void 0:r.icon]}),_=null==t||null===(s=t.componentOptions)||void 0===s||null===(l=s.listeners)||void 0===l?void 0:l.click,S=null==t||null===(c=t.componentOptions)||void 0===c||null===(d=c.children)||void 0===d||null===(u=d[0])||void 0===u||null===(p=u.text)||void 0===p||null===(A=p.trim)||void 0===A?void 0:A.call(p),N=(null==t||null===(m=t.componentOptions)||void 0===m||null===(h=m.propsData)||void 0===h?void 0:h.ariaLabel)||S,z=this.forceName?S:"";let j=null==t||null===(g=t.componentOptions)||void 0===g||null===(v=g.propsData)||void 0===v?void 0:v.title;return this.forceName||j||(j=S),e("NcButton",{class:["action-item action-item--single",null==t||null===(b=t.data)||void 0===b?void 0:b.staticClass,null==t||null===(C=t.data)||void 0===C?void 0:C.class],attrs:{"aria-label":N,title:j},ref:null==t||null===(f=t.data)||void 0===f?void 0:f.ref,props:{type:this.type||(z?"secondary":"tertiary"),disabled:this.disabled||(null==t||null===(y=t.componentOptions)||void 0===y||null===(k=y.propsData)||void 0===k?void 0:k.disabled),ariaHidden:this.ariaHidden,...null==t||null===(w=t.componentOptions)||void 0===w?void 0:w.propsData},on:{focus:this.onFocus,blur:this.onBlur,...!!_&&{click:e=>{_&&_(e)}}}},[e("template",{slot:"icon"},[x]),z])},o=t=>{var i,n;const o=(null===(i=this.$slots.icon)||void 0===i?void 0:i[0])||(this.defaultIcon?e("span",{class:["icon",this.defaultIcon]}):e("DotsHorizontal",{props:{size:20}}));return e("NcPopover",{ref:"popover",props:{delay:0,handleResize:!0,shown:this.opened,placement:this.placement,boundary:this.boundariesElement,container:this.container,popoverBaseClass:"action-item__popper",setReturnFocus:null===(n=this.$refs.menuButton)||void 0===n?void 0:n.$el},attrs:{delay:0,handleResize:!0,shown:this.opened,placement:this.placement,boundary:this.boundariesElement,container:this.container,...this.manualOpen&&{triggers:[]},popoverBaseClass:"action-item__popper"},on:{show:this.openMenu,"after-show":this.onOpen,hide:this.closeMenu}},[e("NcButton",{class:"action-item__menutoggle",props:{type:this.triggerBtnType,disabled:this.disabled,ariaHidden:this.ariaHidden},slot:"trigger",ref:"menuButton",attrs:{"aria-haspopup":a?null:"menu","aria-label":this.ariaLabel,"aria-controls":this.opened?this.randomId:null,"aria-expanded":this.opened.toString()},on:{focus:this.onFocus,blur:this.onBlur}},[e("template",{slot:"icon"},[o]),this.menuName]),e("div",{class:{open:this.opened},attrs:{tabindex:"-1"},on:{keydown:this.onKeydown,mousemove:this.onMouseFocusAction},ref:"menu"},[e("ul",{attrs:{id:this.randomId,tabindex:"-1",role:a?null:"menu"}},[t])])])};if(1===t.length&&1===i.length&&!this.forceMenu)return n(i[0]);if(i.length>0&&this.inline>0){const a=i.slice(0,this.inline),r=t.filter((e=>!a.includes(e)));return e("div",{class:["action-items","action-item--".concat(this.triggerBtnType)]},[...a.map(n),r.length>0?e("div",{class:["action-item",{"action-item--open":this.opened}]},[o(r)]):null])}return e("div",{class:["action-item action-item--default-popover","action-item--".concat(this.triggerBtnType),{"action-item--open":this.opened}]},[o(t)])}};var A=a(3379),m=a.n(A),h=a(7795),g=a.n(h),v=a(569),b=a.n(v),C=a(3565),f=a.n(C),y=a(9216),k=a.n(y),w=a(4589),x=a.n(w),_=a(4825),S={};S.styleTagTransform=x(),S.setAttributes=f(),S.insert=b().bind(null,"head"),S.domAPI=g(),S.insertStyleElement=k();m()(_.Z,S);_.Z&&_.Z.locals&&_.Z.locals;var N=a(4946),z={};z.styleTagTransform=x(),z.setAttributes=f(),z.insert=b().bind(null,"head"),z.domAPI=g(),z.insertStyleElement=k();m()(N.Z,z);N.Z&&N.Z.locals&&N.Z.locals;var j=a(1900),E=a(5727),P=a.n(E),B=(0,j.Z)(p,undefined,undefined,!1,null,"29452b76",null);"function"==typeof P()&&P()(B);const T=B.exports},4462:(e,t,a)=>{"use strict";a.d(t,{default:()=>k});const i={name:"NcButton",props:{disabled:{type:Boolean,default:!1},type:{type:String,validator:e=>-1!==["primary","secondary","tertiary","tertiary-no-background","tertiary-on-primary","error","warning","success"].indexOf(e),default:"secondary"},nativeType:{type:String,validator:e=>-1!==["submit","reset","button"].indexOf(e),default:"button"},wide:{type:Boolean,default:!1},ariaLabel:{type:String,default:null},href:{type:String,default:null},to:{type:[String,Object],default:null},exact:{type:Boolean,default:!1},ariaHidden:{type:Boolean,default:null}},render(e){var t,a,i,n,o,r=this;const s=null===(t=this.$slots.default)||void 0===t||null===(a=t[0])||void 0===a||null===(i=a.text)||void 0===i||null===(n=i.trim)||void 0===n?void 0:n.call(i),l=!!s,c=null===(o=this.$slots)||void 0===o?void 0:o.icon;s||this.ariaLabel||console.warn("You need to fill either the text or the ariaLabel props in the button component.",{text:s,ariaLabel:this.ariaLabel},this);const d=function(){let{navigate:t,isActive:a,isExactActive:i}=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return e(r.to||!r.href?"button":"a",{class:["button-vue",{"button-vue--icon-only":c&&!l,"button-vue--text-only":l&&!c,"button-vue--icon-and-text":c&&l,["button-vue--vue-".concat(r.type)]:r.type,"button-vue--wide":r.wide,active:a,"router-link-exact-active":i}],attrs:{"aria-label":r.ariaLabel,disabled:r.disabled,type:r.href?null:r.nativeType,role:r.href?"button":null,href:!r.to&&r.href?r.href:null,...r.$attrs},on:{...r.$listeners,click:e=>{var a,i;null===(a=r.$listeners)||void 0===a||null===(i=a.click)||void 0===i||i.call(a,e),null==t||t(e)}}},[e("span",{class:"button-vue__wrapper"},[c?e("span",{class:"button-vue__icon",attrs:{"aria-hidden":r.ariaHidden}},[r.$slots.icon]):null,l?e("span",{class:"button-vue__text"},[s]):null])])};return this.to?e("router-link",{props:{custom:!0,to:this.to,exact:this.exact},scopedSlots:{default:d}}):d()}};var n=a(3379),o=a.n(n),r=a(7795),s=a.n(r),l=a(569),c=a.n(l),d=a(3565),u=a.n(d),p=a(9216),A=a.n(p),m=a(4589),h=a.n(m),g=a(7196),v={};v.styleTagTransform=h(),v.setAttributes=u(),v.insert=c().bind(null,"head"),v.domAPI=s(),v.insertStyleElement=A();o()(g.Z,v);g.Z&&g.Z.locals&&g.Z.locals;var b=a(1900),C=a(2102),f=a.n(C),y=(0,b.Z)(i,undefined,undefined,!1,null,"4d05be2c",null);"function"==typeof f()&&f()(y);const k=y.exports},3116:(e,t,a)=>{"use strict";a.d(t,{default:()=>R});var i=a(6492),n=a(1205),o=a(3648);const r=__webpack_require__(/*! vue-material-design-icons/CheckboxBlankOutline.vue */ "./node_modules/vue-material-design-icons/CheckboxBlankOutline.vue");var s=a.n(r);const l=__webpack_require__(/*! vue-material-design-icons/MinusBox.vue */ "./node_modules/vue-material-design-icons/MinusBox.vue");var c=a.n(l);const d=__webpack_require__(/*! vue-material-design-icons/CheckboxMarked.vue */ "./node_modules/vue-material-design-icons/CheckboxMarked.vue");var u=a.n(d);const p=__webpack_require__(/*! vue-material-design-icons/RadioboxMarked.vue */ "./node_modules/vue-material-design-icons/RadioboxMarked.vue");var A=a.n(p);const m=__webpack_require__(/*! vue-material-design-icons/RadioboxBlank.vue */ "./node_modules/vue-material-design-icons/RadioboxBlank.vue");var h=a.n(m);const g=__webpack_require__(/*! vue-material-design-icons/ToggleSwitchOff.vue */ "./node_modules/vue-material-design-icons/ToggleSwitchOff.vue");var v=a.n(g);const b=__webpack_require__(/*! vue-material-design-icons/ToggleSwitch.vue */ "./node_modules/vue-material-design-icons/ToggleSwitch.vue");var C=a.n(b);const f="checkbox",y="radio",k="switch",w={name:"NcCheckboxRadioSwitch",components:{NcLoadingIcon:i.default},mixins:[o.Z],props:{id:{type:String,default:()=>"checkbox-radio-switch-"+(0,n.Z)(),validator:e=>""!==e.trim()},name:{type:String,default:null},type:{type:String,default:"checkbox",validator:e=>e===f||e===y||e===k},buttonVariant:{type:Boolean,default:!1},buttonVariantGrouped:{type:String,default:"no",validator:e=>["no","vertical","horizontal"].includes(e)},checked:{type:[Boolean,Array,String],default:!1},value:{type:String,default:null},disabled:{type:Boolean,default:!1},indeterminate:{type:Boolean,default:!1},loading:{type:Boolean,default:!1},wrapperElement:{type:String,default:"span"}},emits:["update:checked"],computed:{size(){return this.type===k?36:24},cssVars(){return{"--icon-size":this.size+"px"}},inputType(){return this.type===y?y:f},isChecked(){return null!==this.value?Array.isArray(this.checked)?[...this.checked].indexOf(this.value)>-1:this.checked===this.value:!0===this.checked},checkboxRadioIconElement(){return this.type===y?this.isChecked?A():h():this.type===k?this.isChecked?C():v():this.indeterminate?c():this.isChecked?u():s()}},mounted(){if(this.name&&this.type===f&&!Array.isArray(this.checked))throw new Error("When using groups of checkboxes, the updated value will be an array.");if(this.name&&this.type===k)throw new Error("Switches are not made to be used for data sets. Please use checkboxes instead.");if("boolean"!=typeof this.checked&&this.type===k)throw new Error("Switches can only be used with boolean as checked prop.")},methods:{onToggle(){if(this.disabled)return;if(this.type===y)return void this.$emit("update:checked",this.value);if(this.type===k)return void this.$emit("update:checked",!this.isChecked);if("boolean"==typeof this.checked)return void this.$emit("update:checked",!this.isChecked);const e=this.getInputsSet().filter((e=>e.checked)).map((e=>e.value));this.$emit("update:checked",e)},getInputsSet(){return[...document.getElementsByName(this.name)]}}};var x=a(3379),_=a.n(x),S=a(7795),N=a.n(S),z=a(569),j=a.n(z),E=a(3565),P=a.n(E),B=a(9216),T=a.n(B),D=a(4589),F=a.n(D),O=a(7924),$={};$.styleTagTransform=F(),$.setAttributes=P(),$.insert=j().bind(null,"head"),$.domAPI=N(),$.insertStyleElement=T();_()(O.Z,$);O.Z&&O.Z.locals&&O.Z.locals;var G=a(1900),I=a(3768),M=a.n(I),U=(0,G.Z)(w,(function(){var e=this,t=e._self._c;return t(e.wrapperElement,{tag:"component",staticClass:"checkbox-radio-switch",class:{["checkbox-radio-switch-"+e.type]:e.type,"checkbox-radio-switch--checked":e.isChecked,"checkbox-radio-switch--disabled":e.disabled,"checkbox-radio-switch--indeterminate":e.indeterminate,"checkbox-radio-switch--button-variant":e.buttonVariant,"checkbox-radio-switch--button-variant-v-grouped":e.buttonVariant&&"vertical"===e.buttonVariantGrouped,"checkbox-radio-switch--button-variant-h-grouped":e.buttonVariant&&"horizontal"===e.buttonVariantGrouped},style:e.cssVars},[t("input",{staticClass:"checkbox-radio-switch__input",attrs:{id:e.id,disabled:e.disabled,indeterminate:e.indeterminate,name:e.name,type:e.inputType},domProps:{checked:e.isChecked,value:e.value},on:{change:e.onToggle}}),e._v(" "),t("label",{staticClass:"checkbox-radio-switch__label",attrs:{for:e.id}},[t("div",{staticClass:"checkbox-radio-switch__icon"},[e._t("icon",(function(){return[e.loading?t("NcLoadingIcon"):e.buttonVariant?e._e():t(e.checkboxRadioIconElement,{tag:"component",attrs:{size:e.size}})]}),{checked:e.isChecked,loading:e.loading})],2),e._v(" "),t("span",{staticClass:"checkbox-radio-switch__label-text"},[e._t("default")],2)])])}),[],!1,null,"dec41432",null);"function"==typeof M()&&M()(U);const R=U.exports},4242:(e,t,a)=>{"use strict";a.d(t,{default:()=>k});const i={name:"NcEmptyContent",props:{name:{type:String,default:""},description:{type:String,default:""}},computed:{hasName(){return""!==this.name},hasDescription(){var e;return""!==this.description||(null===(e=this.$slots.description)||void 0===e?void 0:e[0])}}};var n=a(3379),o=a.n(n),r=a(7795),s=a.n(r),l=a(569),c=a.n(l),d=a(3565),u=a.n(d),p=a(9216),A=a.n(p),m=a(4589),h=a.n(m),g=a(6613),v={};v.styleTagTransform=h(),v.setAttributes=u(),v.insert=c().bind(null,"head"),v.domAPI=s(),v.insertStyleElement=A();o()(g.Z,v);g.Z&&g.Z.locals&&g.Z.locals;var b=a(1900),C=a(9258),f=a.n(C),y=(0,b.Z)(i,(function(){var e=this,t=e._self._c;return t("div",{staticClass:"empty-content",attrs:{role:"note"}},[e.$slots.icon?t("div",{staticClass:"empty-content__icon",attrs:{"aria-hidden":"true"}},[e._t("icon")],2):e._e(),e._v(" "),e._t("name",(function(){return[e.hasName?t("h2",{staticClass:"empty-content__name"},[e._v("\n\t\t\t"+e._s(e.name)+"\n\t\t")]):e._e()]})),e._v(" "),e.hasDescription?t("p",[e._t("description",(function(){return[e._v("\n\t\t\t"+e._s(e.description)+"\n\t\t")]}))],2):e._e(),e._v(" "),e.$slots.action?t("div",{staticClass:"empty-content__action"},[e._t("action")],2):e._e()],2)}),[],!1,null,"24368316",null);"function"==typeof f()&&f()(y);const k=y.exports},6492:(e,t,a)=>{"use strict";a.d(t,{default:()=>k});const i={name:"NcLoadingIcon",props:{size:{type:Number,default:20},appearance:{type:String,validator:e=>["auto","light","dark"].includes(e),default:"auto"},name:{type:String,default:""}},computed:{colors(){const e=["#777","#CCC"];return"light"===this.appearance?e:"dark"===this.appearance?e.reverse():["var(--color-loading-light)","var(--color-loading-dark)"]}}};var n=a(3379),o=a.n(n),r=a(7795),s=a.n(r),l=a(569),c=a.n(l),d=a(3565),u=a.n(d),p=a(9216),A=a.n(p),m=a(4589),h=a.n(m),g=a(8502),v={};v.styleTagTransform=h(),v.setAttributes=u(),v.insert=c().bind(null,"head"),v.domAPI=s(),v.insertStyleElement=A();o()(g.Z,v);g.Z&&g.Z.locals&&g.Z.locals;var b=a(1900),C=a(9280),f=a.n(C),y=(0,b.Z)(i,(function(){var e=this,t=e._self._c;return t("span",{staticClass:"material-design-icon loading-icon",attrs:{"aria-label":e.name,role:"img"}},[t("svg",{attrs:{width:e.size,height:e.size,viewBox:"0 0 24 24"}},[t("path",{attrs:{fill:e.colors[0],d:"M12,4V2A10,10 0 1,0 22,12H20A8,8 0 1,1 12,4Z"}}),e._v(" "),t("path",{attrs:{fill:e.colors[1],d:"M12,4V2A10,10 0 0,1 22,12H20A8,8 0 0,0 12,4Z"}},[e.name?t("title",[e._v(e._s(e.name))]):e._e()])])])}),[],!1,null,"27fa1197",null);"function"==typeof f()&&f()(y);const k=y.exports},2297:(e,t,a)=>{"use strict";a.d(t,{default:()=>S});var i=a(9454),n=a(4505),o=a(1206);const r={name:"NcPopover",components:{Dropdown:i.Dropdown},inheritAttrs:!1,props:{popoverBaseClass:{type:String,default:""},focusTrap:{type:Boolean,default:!0},setReturnFocus:{default:void 0,type:[HTMLElement,SVGElement,String,Boolean]}},emits:["after-show","after-hide"],beforeDestroy(){this.clearFocusTrap()},methods:{async useFocusTrap(){var e,t;if(await this.$nextTick(),!this.focusTrap)return;const a=null===(e=this.$refs.popover)||void 0===e||null===(t=e.$refs.popperContent)||void 0===t?void 0:t.$el;a&&(this.$focusTrap=(0,n.createFocusTrap)(a,{escapeDeactivates:!1,allowOutsideClick:!0,setReturnFocus:this.setReturnFocus,trapStack:(0,o.L)()}),this.$focusTrap.activate())},clearFocusTrap(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};try{var t;null===(t=this.$focusTrap)||void 0===t||t.deactivate(e),this.$focusTrap=null}catch(e){console.warn(e)}},afterShow(){this.$nextTick((()=>{this.$emit("after-show"),this.useFocusTrap()}))},afterHide(){this.$emit("after-hide"),this.clearFocusTrap()}}},s=r;var l=a(3379),c=a.n(l),d=a(7795),u=a.n(d),p=a(569),A=a.n(p),m=a(3565),h=a.n(m),g=a(9216),v=a.n(g),b=a(4589),C=a.n(b),f=a(1625),y={};y.styleTagTransform=C(),y.setAttributes=h(),y.insert=A().bind(null,"head"),y.domAPI=u(),y.insertStyleElement=v();c()(f.Z,y);f.Z&&f.Z.locals&&f.Z.locals;var k=a(1900),w=a(2405),x=a.n(w),_=(0,k.Z)(s,(function(){var e=this;return(0,e._self._c)("Dropdown",e._g(e._b({ref:"popover",attrs:{distance:10,"arrow-padding":10,"no-auto-focus":!0,"popper-class":e.popoverBaseClass},on:{"apply-show":e.afterShow,"apply-hide":e.afterHide},scopedSlots:e._u([{key:"popper",fn:function(){return[e._t("default")]},proxy:!0}],null,!0)},"Dropdown",e.$attrs,!1),e.$listeners),[e._t("trigger")],2)}),[],!1,null,null,null);"function"==typeof x()&&x()(_);const S=_.exports},3329:(e,t,a)=>{"use strict";a.d(t,{default:()=>n});const i={name:"NcVNodes",props:{vnodes:{type:[Array,Object],default:null}},render(e){var t,a,i;return this.vnodes||(null===(t=this.$slots)||void 0===t?void 0:t.default)||(null===(a=this.$scopedSlots)||void 0===a||null===(i=a.default)||void 0===i?void 0:i.call(a))}};const n=(0,a(1900).Z)(i,undefined,undefined,!1,null,null,null).exports},8167:(e,t,a)=>{"use strict";a.d(t,{default:()=>i});const i={inserted(e){e.focus()}}},5675:(e,t,a)=>{"use strict";a.d(t,{default:()=>n});var i=a(1390);const n=function(e,t){var a;!0===(null===(a=t.value)||void 0===a?void 0:a.linkify)&&(e.innerHTML=(0,i.Z)(t.value.text))}},336:(e,t,a)=>{"use strict";a.d(t,{default:()=>b});var i=a(9454),n=a(3379),o=a.n(n),r=a(7795),s=a.n(r),l=a(569),c=a.n(l),d=a(3565),u=a.n(d),p=a(9216),A=a.n(p),m=a(4589),h=a.n(m),g=a(8384),v={};v.styleTagTransform=h(),v.setAttributes=u(),v.insert=c().bind(null,"head"),v.domAPI=s(),v.insertStyleElement=A();o()(g.Z,v);g.Z&&g.Z.locals&&g.Z.locals;i.options.themes.tooltip.html=!1,i.options.themes.tooltip.delay={show:500,hide:200},i.options.themes.tooltip.distance=10,i.options.themes.tooltip["arrow-padding"]=3;const b=i.VTooltip},932:(e,t,a)=>{"use strict";a.d(t,{n:()=>r,t:()=>s});var i=a(7931);const n=(0,i.getGettextBuilder)().detectLocale();[{locale:"ar",translations:{"{tag} (invisible)":"{tag} (غير مرئي)","{tag} (restricted)":"{tag} (مقيد)",Actions:"الإجراءات",Activities:"النشاطات","Animals & Nature":"الحيوانات والطبيعة","Anything shared with the same group of people will show up here":"أي مادة تمت مشاركتها مع نفس المجموعة من الأشخاص سيتم عرضها هنا","Avatar of {displayName}":"صورة {displayName} الرمزية","Avatar of {displayName}, {status}":"صورة {displayName} الرمزية، {status}","Cancel changes":"إلغاء التغييرات","Change title":"تغيير العنوان",Choose:"إختيار","Clear text":"مسح النص",Close:"أغلق","Close modal":"قفل الشرط","Close navigation":"إغلاق المتصفح","Close sidebar":"قفل الشريط الجانبي","Confirm changes":"تأكيد التغييرات",Custom:"مخصص","Edit item":"تعديل عنصر","Error getting related resources":"خطأ في تحصيل مصادر ذات صلة","External documentation for {title}":"الوثائق الخارجية لـ{title}",Favorite:"مفضلة",Flags:"الأعلام","Food & Drink":"الطعام والشراب","Frequently used":"كثيرا ما تستخدم",Global:"عالمي","Go back to the list":"العودة إلى القائمة","Hide password":"إخفاء كلمة السر","Message limit of {count} characters reached":"تم الوصول إلى الحد الأقصى لعدد الأحرف في الرسالة: {count} حرف","More items …":"عناصر أخرى ...",Next:"التالي","No emoji found":"لم يتم العثور على أي رمز تعبيري","No results":"ليس هناك أية نتيجة",Objects:"الأشياء",Open:"فتح",'Open link to "{resourceTitle}"':'فتح رابط إلى "{resourceTitle}"',"Open navigation":"فتح المتصفح","Password is secure":"كلمة السر مُؤمّنة","Pause slideshow":"إيقاف العرض مؤقتًا","People & Body":"الناس والجسم","Pick an emoji":"اختر رمزًا تعبيريًا","Please select a time zone:":"الرجاء تحديد المنطقة الزمنية:",Previous:"السابق","Related resources":"مصادر ذات صلة",Search:"بحث","Search results":"نتائج البحث","Select a tag":"اختر علامة",Settings:"الإعدادات","Settings navigation":"إعدادات المتصفح","Show password":"أعرض كلمة السر","Smileys & Emotion":"الوجوه و الرموز التعبيرية","Start slideshow":"بدء العرض",Submit:"إرسال",Symbols:"الرموز","Travel & Places":"السفر والأماكن","Type to search time zone":"اكتب للبحث عن منطقة زمنية","Unable to search the group":"تعذر البحث في المجموعة","Undo changes":"التراجع عن التغييرات","Write message, @ to mention someone, : for emoji autocompletion …":"اكتب رسالة، @ للإشارة إلى شخص ما، : للإكمال التلقائي للرموز التعبيرية ..."}},{locale:"br",translations:{"{tag} (invisible)":"{tag} (diwelus)","{tag} (restricted)":"{tag} (bevennet)",Actions:"Oberioù",Activities:"Oberiantizoù","Animals & Nature":"Loened & Natur",Choose:"Dibab",Close:"Serriñ",Custom:"Personelañ",Flags:"Bannieloù","Food & Drink":"Boued & Evajoù","Frequently used":"Implijet alies",Next:"Da heul","No emoji found":"Emoji ebet kavet","No results":"Disoc'h ebet",Objects:"Traoù","Pause slideshow":"Arsav an diaporama","People & Body":"Tud & Korf","Pick an emoji":"Choaz un emoji",Previous:"A-raok",Search:"Klask","Search results":"Disoc'hoù an enklask","Select a tag":"Choaz ur c'hlav",Settings:"Arventennoù","Smileys & Emotion":"Smileyioù & Fromoù","Start slideshow":"Kregiñ an diaporama",Symbols:"Arouezioù","Travel & Places":"Beaj & Lec'hioù","Unable to search the group":"Dibosupl eo klask ar strollad"}},{locale:"ca",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (restringit)",Actions:"Accions",Activities:"Activitats","Animals & Nature":"Animals i natura","Anything shared with the same group of people will show up here":"Qualsevol cosa compartida amb el mateix grup de persones es mostrarà aquí","Avatar of {displayName}":"Avatar de {displayName}","Avatar of {displayName}, {status}":"Avatar de {displayName}, {status}","Cancel changes":"Cancel·la els canvis","Change title":"Canviar títol",Choose:"Tria","Clear text":"Netejar text",Close:"Tanca","Close modal":"Tancar el mode","Close navigation":"Tanca la navegació","Close sidebar":"Tancar la barra lateral","Confirm changes":"Confirmeu els canvis",Custom:"Personalitzat","Edit item":"Edita l'element","Error getting related resources":"Error obtenint els recursos relacionats","Error parsing svg":"Error en l'anàlisi del svg","External documentation for {title}":"Documentació externa per a {title}",Favorite:"Preferit",Flags:"Marques","Food & Drink":"Menjar i begudes","Frequently used":"Utilitzats recentment",Global:"Global","Go back to the list":"Torna a la llista","Hide password":"Amagar contrasenya","Message limit of {count} characters reached":"S'ha arribat al límit de {count} caràcters per missatge","More items …":"Més artícles...",Next:"Següent","No emoji found":"No s'ha trobat cap emoji","No results":"Sense resultats",Objects:"Objectes",Open:"Obrir",'Open link to "{resourceTitle}"':'Obrir enllaç a "{resourceTitle}"',"Open navigation":"Obre la navegació","Password is secure":"Contrasenya segura<br>","Pause slideshow":"Atura la presentació","People & Body":"Persones i cos","Pick an emoji":"Trieu un emoji","Please select a time zone:":"Seleccioneu una zona horària:",Previous:"Anterior","Related resources":"Recursos relacionats",Search:"Cerca","Search results":"Resultats de cerca","Select a tag":"Seleccioneu una etiqueta",Settings:"Paràmetres","Settings navigation":"Navegació d'opcions","Show password":"Mostrar contrasenya","Smileys & Emotion":"Cares i emocions","Start slideshow":"Inicia la presentació",Submit:"Envia",Symbols:"Símbols","Travel & Places":"Viatges i llocs","Type to search time zone":"Escriviu per cercar la zona horària","Unable to search the group":"No es pot cercar el grup","Undo changes":"Desfés els canvis",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Escriu missatge, fes servir "@" per esmentar algú, fes servir ":" per autocompletar emojis...'}},{locale:"cs_CZ",translations:{"{tag} (invisible)":"{tag} (neviditelné)","{tag} (restricted)":"{tag} (omezené)",Actions:"Akce",Activities:"Aktivity","Animals & Nature":"Zvířata a příroda","Anything shared with the same group of people will show up here":"Cokoli nasdíleného stejné skupině lidí se zobrazí zde","Avatar of {displayName}":"Zástupný obrázek uživatele {displayName}","Avatar of {displayName}, {status}":"Zástupný obrázek uživatele {displayName}, {status}","Cancel changes":"Zrušit změny","Change title":"Změnit nadpis",Choose:"Zvolit","Clear text":"Čitelný text",Close:"Zavřít","Close modal":"Zavřít dialogové okno","Close navigation":"Zavřít navigaci","Close sidebar":"Zavřít postranní panel","Confirm changes":"Potvrdit změny",Custom:"Uživatelsky určené","Edit item":"Upravit položku","Error getting related resources":"Chyba při získávání souvisejících prostředků","Error parsing svg":"Chyba při zpracovávání svg","External documentation for {title}":"Externí dokumentace k {title}",Favorite:"Oblíbené",Flags:"Příznaky","Food & Drink":"Jídlo a pití","Frequently used":"Často používané",Global:"Globální","Go back to the list":"Jít zpět na seznam","Hide password":"Skrýt heslo","Message limit of {count} characters reached":"Dosaženo limitu počtu ({count}) znaků zprávy","More items …":"Další položky…",Next:"Následující","No emoji found":"Nenalezeno žádné emoji","No results":"Nic nenalezeno",Objects:"Objekty",Open:"Otevřít",'Open link to "{resourceTitle}"':"Otevřít odkaz na „{resourceTitle}“","Open navigation":"Otevřít navigaci","Password is secure":"Heslo je bezpečné","Pause slideshow":"Pozastavit prezentaci","People & Body":"Lidé a tělo","Pick an emoji":"Vybrat emoji","Please select a time zone:":"Vyberte časovou zónu:",Previous:"Předchozí","Related resources":"Související prostředky",Search:"Hledat","Search results":"Výsledky hledání","Select a tag":"Vybrat štítek",Settings:"Nastavení","Settings navigation":"Pohyb po nastavení","Show password":"Zobrazit heslo","Smileys & Emotion":"Úsměvy a emoce","Start slideshow":"Spustit prezentaci",Submit:"Odeslat",Symbols:"Symboly","Travel & Places":"Cestování a místa","Type to search time zone":"Psaním vyhledejte časovou zónu","Unable to search the group":"Nedaří se hledat skupinu","Undo changes":"Vzít změny zpět",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':"Napište zprávu – pokud chcete někoho zmínit, napište před jeho uživatelským jménem „@“ (zavináč); automatické doplňování emotikonů zahájíte napsáním „:“ (dvojtečky)…"}},{locale:"da",translations:{"{tag} (invisible)":"{tag} (usynlig)","{tag} (restricted)":"{tag} (begrænset)",Actions:"Handlinger",Activities:"Aktiviteter","Animals & Nature":"Dyr & Natur","Anything shared with the same group of people will show up here":"Alt der deles med samme gruppe af personer vil vises her","Avatar of {displayName}":"Avatar af {displayName}","Avatar of {displayName}, {status}":"Avatar af {displayName}, {status}","Cancel changes":"Annuller ændringer","Change title":"Ret titel",Choose:"Vælg","Clear text":"Ryd tekst",Close:"Luk","Close modal":"Luk vindue","Close navigation":"Luk navigation","Close sidebar":"Luk sidepanel","Confirm changes":"Bekræft ændringer",Custom:"Brugerdefineret","Edit item":"Rediger emne","Error getting related resources":"Kunne ikke hente tilknyttede data","Error parsing svg":"Fejl ved analysering af svg","External documentation for {title}":"Ekstern dokumentation for {title}",Favorite:"Favorit",Flags:"Flag","Food & Drink":"Mad & Drikke","Frequently used":"Ofte brugt",Global:"Global","Go back to the list":"Tilbage til listen","Hide password":"Skjul kodeord","Message limit of {count} characters reached":"Begrænsning på {count} tegn er nået","More items …":"Mere ...",Next:"Videre","No emoji found":"Ingen emoji fundet","No results":"Ingen resultater",Objects:"Objekter",Open:"Åbn",'Open link to "{resourceTitle}"':'Åbn link til "{resourceTitle}"',"Open navigation":"Åbn navigation","Password is secure":"Kodeordet er sikkert","Pause slideshow":"Suspender fremvisning","People & Body":"Mennesker & Menneskekroppen","Pick an emoji":"Vælg en emoji","Please select a time zone:":"Vælg venligst en tidszone:",Previous:"Forrige","Related resources":"Relaterede emner",Search:"Søg","Search results":"Søgeresultater","Select a tag":"Vælg et mærke",Settings:"Indstillinger","Settings navigation":"Naviger i indstillinger","Show password":"Vis kodeord","Smileys & Emotion":"Smileys & Emotion","Start slideshow":"Start fremvisning",Submit:"Send",Symbols:"Symboler","Travel & Places":"Rejser & Rejsemål","Type to search time zone":"Indtast for at søge efter tidszone","Unable to search the group":"Kan ikke søge på denne gruppe","Undo changes":"Fortryd ændringer",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Skriv besked, brug "@" for at nævne nogen, brug ":" til emoji-autofuldførelse ...'}},{locale:"de",translations:{"{tag} (invisible)":"{tag} (unsichtbar)","{tag} (restricted)":"{tag} (eingeschränkt)",Actions:"Aktionen",Activities:"Aktivitäten","Animals & Nature":"Tiere & Natur","Anything shared with the same group of people will show up here":"Alles, das mit derselben Gruppe von Personen geteilt wird, wird hier angezeigt","Avatar of {displayName}":"Avatar von {displayName}","Avatar of {displayName}, {status}":"Avatar von {displayName}, {status}","Cancel changes":"Änderungen verwerfen","Change title":"Titel ändern",Choose:"Auswählen","Clear text":"Klartext",Close:"Schließen","Close modal":"Modal schließen","Close navigation":"Navigation schließen","Close sidebar":"Seitenleiste schließen","Confirm changes":"Änderungen bestätigen",Custom:"Benutzerdefiniert","Edit item":"Objekt bearbeiten","Error getting related resources":"Fehler beim Abrufen verwandter Ressourcen","Error parsing svg":"Fehler beim Einlesen der SVG","External documentation for {title}":"Externe Dokumentation für {title}",Favorite:"Favorit",Flags:"Flaggen","Food & Drink":"Essen & Trinken","Frequently used":"Häufig verwendet",Global:"Global","Go back to the list":"Zurück zur Liste","Hide password":"Passwort verbergen","Message limit of {count} characters reached":"Nachrichtenlimit von {count} Zeichen erreicht","More items …":"Weitere Elemente …",Next:"Weiter","No emoji found":"Kein Emoji gefunden","No results":"Keine Ergebnisse",Objects:"Gegenstände",Open:"Öffnen",'Open link to "{resourceTitle}"':'Link zu "{resourceTitle}" öffnen',"Open navigation":"Navigation öffnen","Password is secure":"Passwort ist sicher","Pause slideshow":"Diashow pausieren","People & Body":"Menschen & Körper","Pick an emoji":"Ein Emoji auswählen","Please select a time zone:":"Bitte wählen Sie eine Zeitzone:",Previous:"Vorherige","Related resources":"Verwandte Ressourcen",Search:"Suche","Search results":"Suchergebnisse","Select a tag":"Schlagwort auswählen",Settings:"Einstellungen","Settings navigation":"Einstellungen für die Navigation","Show password":"Passwort anzeigen","Smileys & Emotion":"Smileys & Emotionen","Start slideshow":"Diashow starten",Submit:"Einreichen",Symbols:"Symbole","Travel & Places":"Reisen & Orte","Type to search time zone":"Tippen, um Zeitzone zu suchen","Unable to search the group":"Die Gruppe konnte nicht durchsucht werden","Undo changes":"Änderungen rückgängig machen",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Nachricht schreiben, "@" um jemanden zu erwähnen, ":" für die automatische Vervollständigung von Emojis …'}},{locale:"de_DE",translations:{"{tag} (invisible)":"{tag} (unsichtbar)","{tag} (restricted)":"{tag} (eingeschränkt)",Actions:"Aktionen",Activities:"Aktivitäten","Animals & Nature":"Tiere & Natur","Anything shared with the same group of people will show up here":"Alles, das mit derselben Gruppe von Personen geteilt wird, wird hier angezeigt","Avatar of {displayName}":"Avatar von {displayName}","Avatar of {displayName}, {status}":"Avatar von {displayName}, {status}","Cancel changes":"Änderungen verwerfen","Change title":"Titel ändern",Choose:"Auswählen","Clear text":"Klartext",Close:"Schließen","Close modal":"Modal schließen","Close navigation":"Navigation schließen","Close sidebar":"Seitenleiste schließen","Confirm changes":"Änderungen bestätigen",Custom:"Benutzerdefiniert","Edit item":"Objekt bearbeiten","Error getting related resources":"Fehler beim Abrufen verwandter Ressourcen","Error parsing svg":"Fehler beim Einlesen der SVG","External documentation for {title}":"Externe Dokumentation für {title}",Favorite:"Favorit",Flags:"Flaggen","Food & Drink":"Essen & Trinken","Frequently used":"Häufig verwendet",Global:"Global","Go back to the list":"Zurück zur Liste","Hide password":"Passwort verbergen","Message limit of {count} characters reached":"Nachrichtenlimit von {count} Zeichen erreicht","More items …":"Weitere Elemente …",Next:"Weiter","No emoji found":"Kein Emoji gefunden","No results":"Keine Ergebnisse",Objects:"Objekte",Open:"Öffnen",'Open link to "{resourceTitle}"':'Link zu "{resourceTitle}" öffnen',"Open navigation":"Navigation öffnen","Password is secure":"Passwort ist sicher","Pause slideshow":"Diashow pausieren","People & Body":"Menschen & Körper","Pick an emoji":"Ein Emoji auswählen","Please select a time zone:":"Bitte eine Zeitzone auswählen:",Previous:"Vorherige","Related resources":"Verwandte Ressourcen",Search:"Suche","Search results":"Suchergebnisse","Select a tag":"Schlagwort auswählen",Settings:"Einstellungen","Settings navigation":"Einstellungen für die Navigation","Show password":"Passwort anzeigen","Smileys & Emotion":"Smileys & Emotionen","Start slideshow":"Diashow starten",Submit:"Einreichen",Symbols:"Symbole","Travel & Places":"Reisen & Orte","Type to search time zone":"Tippen, um eine Zeitzone zu suchen","Unable to search the group":"Die Gruppe kann nicht durchsucht werden","Undo changes":"Änderungen rückgängig machen",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Nachricht schreiben, "@" um jemanden zu erwähnen, ":" für die automatische Vervollständigung von Emojis …'}},{locale:"el",translations:{"{tag} (invisible)":"{tag} (αόρατο)","{tag} (restricted)":"{tag} (περιορισμένο)",Actions:"Ενέργειες",Activities:"Δραστηριότητες","Animals & Nature":"Ζώα & Φύση","Anything shared with the same group of people will show up here":"Οτιδήποτε μοιράζεται με την ίδια ομάδα ατόμων θα εμφανίζεται εδώ","Avatar of {displayName}":"Άβαταρ του {displayName}","Avatar of {displayName}, {status}":"Άβαταρ του {displayName}, {status}","Cancel changes":"Ακύρωση αλλαγών","Change title":"Αλλαγή τίτλου",Choose:"Επιλογή","Clear text":"Εκκαθάριση κειμένου",Close:"Κλείσιμο","Close modal":"Βοηθητικό κλείσιμο","Close navigation":"Κλείσιμο πλοήγησης","Close sidebar":"Κλείσιμο πλευρικής μπάρας","Confirm changes":"Επιβεβαίωση αλλαγών",Custom:"Προσαρμογή","Edit item":"Επεξεργασία","Error getting related resources":"Σφάλμα λήψης σχετικών πόρων","Error parsing svg":"Σφάλμα ανάλυσης svg","External documentation for {title}":"Εξωτερική τεκμηρίωση για {title}",Favorite:"Αγαπημένα",Flags:"Σημαίες","Food & Drink":"Φαγητό & Ποτό","Frequently used":"Συχνά χρησιμοποιούμενο",Global:"Καθολικό","Go back to the list":"Επιστροφή στην αρχική λίστα ","Hide password":"Απόκρυψη κωδικού πρόσβασης","Message limit of {count} characters reached":"Συμπληρώθηκε το όριο των {count} χαρακτήρων του μηνύματος","More items …":"Περισσότερα στοιχεία …",Next:"Επόμενο","No emoji found":"Δεν βρέθηκε emoji","No results":"Κανένα αποτέλεσμα",Objects:"Αντικείμενα",Open:"Άνοιγμα",'Open link to "{resourceTitle}"':'Άνοιγμα συνδέσμου στο "{resourceTitle}"',"Open navigation":"Άνοιγμα πλοήγησης","Password is secure":"Ο κωδικός πρόσβασης είναι ασφαλής","Pause slideshow":"Παύση προβολής διαφανειών","People & Body":"Άνθρωποι & Σώμα","Pick an emoji":"Επιλέξτε ένα emoji","Please select a time zone:":"Παρακαλούμε επιλέξτε μια ζώνη ώρας:",Previous:"Προηγούμενο","Related resources":"Σχετικοί πόροι",Search:"Αναζήτηση","Search results":"Αποτελέσματα αναζήτησης","Select a tag":"Επιλογή ετικέτας",Settings:"Ρυθμίσεις","Settings navigation":"Πλοήγηση ρυθμίσεων","Show password":"Εμφάνιση κωδικού πρόσβασης","Smileys & Emotion":"Φατσούλες & Συναίσθημα","Start slideshow":"Έναρξη προβολής διαφανειών",Submit:"Υποβολή",Symbols:"Σύμβολα","Travel & Places":"Ταξίδια & Τοποθεσίες","Type to search time zone":"Πληκτρολογήστε για αναζήτηση ζώνης ώρας","Unable to search the group":"Δεν είναι δυνατή η αναζήτηση της ομάδας","Undo changes":"Αναίρεση Αλλαγών",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Γράψτε μήνυμα, χρησιμοποιείστε "@" για να αναφέρετε κάποιον, χρησιμοποιείστε ":" για αυτόματη συμπλήρωση emoji …'}},{locale:"en_GB",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (restricted)",Actions:"Actions",Activities:"Activities","Animals & Nature":"Animals & Nature","Anything shared with the same group of people will show up here":"Anything shared with the same group of people will show up here","Avatar of {displayName}":"Avatar of {displayName}","Avatar of {displayName}, {status}":"Avatar of {displayName}, {status}","Cancel changes":"Cancel changes","Change title":"Change title",Choose:"Choose","Clear text":"Clear text",Close:"Close","Close modal":"Close modal","Close navigation":"Close navigation","Close sidebar":"Close sidebar","Confirm changes":"Confirm changes",Custom:"Custom","Edit item":"Edit item","Error getting related resources":"Error getting related resources","Error parsing svg":"Error parsing svg","External documentation for {title}":"External documentation for {title}",Favorite:"Favourite",Flags:"Flags","Food & Drink":"Food & Drink","Frequently used":"Frequently used",Global:"Global","Go back to the list":"Go back to the list","Hide password":"Hide password","Message limit of {count} characters reached":"Message limit of {count} characters reached","More items …":"More items …",Next:"Next","No emoji found":"No emoji found","No results":"No results",Objects:"Objects",Open:"Open",'Open link to "{resourceTitle}"':'Open link to "{resourceTitle}"',"Open navigation":"Open navigation","Password is secure":"Password is secure","Pause slideshow":"Pause slideshow","People & Body":"People & Body","Pick an emoji":"Pick an emoji","Please select a time zone:":"Please select a time zone:",Previous:"Previous","Related resources":"Related resources",Search:"Search","Search results":"Search results","Select a tag":"Select a tag",Settings:"Settings","Settings navigation":"Settings navigation","Show password":"Show password","Smileys & Emotion":"Smileys & Emotion","Start slideshow":"Start slideshow",Submit:"Submit",Symbols:"Symbols","Travel & Places":"Travel & Places","Type to search time zone":"Type to search time zone","Unable to search the group":"Unable to search the group","Undo changes":"Undo changes",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Write message, use "@" to mention someone, use ":" for emoji autocompletion …'}},{locale:"eo",translations:{"{tag} (invisible)":"{tag} (kaŝita)","{tag} (restricted)":"{tag} (limigita)",Actions:"Agoj",Activities:"Aktiveco","Animals & Nature":"Bestoj & Naturo",Choose:"Elektu",Close:"Fermu",Custom:"Propra",Flags:"Flagoj","Food & Drink":"Manĝaĵo & Trinkaĵo","Frequently used":"Ofte uzataj","Message limit of {count} characters reached":"La limo je {count} da literoj atingita",Next:"Sekva","No emoji found":"La emoĝio forestas","No results":"La rezulto forestas",Objects:"Objektoj","Pause slideshow":"Payzi bildprezenton","People & Body":"Homoj & Korpo","Pick an emoji":"Elekti emoĝion ",Previous:"Antaŭa",Search:"Serĉi","Search results":"Serĉrezultoj","Select a tag":"Elektu etikedon",Settings:"Agordo","Settings navigation":"Agorda navigado","Smileys & Emotion":"Ridoj kaj Emocioj","Start slideshow":"Komenci bildprezenton",Symbols:"Signoj","Travel & Places":"Vojaĵoj & Lokoj","Unable to search the group":"Ne eblas serĉi en la grupo","Write message, @ to mention someone …":"Mesaĝi, uzu @ por mencii iun ..."}},{locale:"es",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (restringido)",Actions:"Acciones",Activities:"Actividades","Animals & Nature":"Animales y naturaleza","Anything shared with the same group of people will show up here":"Cualquier cosa que sea compartida con el mismo grupo de personas se mostrará aquí","Avatar of {displayName}":"Avatar de {displayName}","Avatar of {displayName}, {status}":"Avatar de {displayName}, {status}","Cancel changes":"Cancelar cambios","Change title":"Cambiar título",Choose:"Elegir","Clear text":"Limpiar texto",Close:"Cerrar","Close modal":"Cerrar modal","Close navigation":"Cerrar navegación","Close sidebar":"Cerrar barra lateral","Confirm changes":"Confirmar cambios",Custom:"Personalizado","Edit item":"Editar elemento","Error getting related resources":"Se encontró un error al obtener los recursos relacionados","Error parsing svg":"Error procesando svg","External documentation for {title}":"Documentacion externa de {title}",Favorite:"Favorito",Flags:"Banderas","Food & Drink":"Comida y bebida","Frequently used":"Usado con frecuenca",Global:"Global","Go back to the list":"Volver a la lista","Hide password":"Ocultar contraseña","Message limit of {count} characters reached":"El mensaje ha alcanzado el límite de {count} caracteres","More items …":"Más ítems...",Next:"Siguiente","No emoji found":"No hay ningún emoji","No results":" Ningún resultado",Objects:"Objetos",Open:"Abrir",'Open link to "{resourceTitle}"':'Abrir enlace a "{resourceTitle}"',"Open navigation":"Abrir navegación","Password is secure":"La contraseña es segura","Pause slideshow":"Pausar la presentación ","People & Body":"Personas y cuerpos","Pick an emoji":"Elegir un emoji","Please select a time zone:":"Por favor elige un huso de horario:",Previous:"Anterior","Related resources":"Recursos relacionados",Search:"Buscar","Search results":"Resultados de la búsqueda","Select a tag":"Seleccione una etiqueta",Settings:"Ajustes","Settings navigation":"Navegación por ajustes","Show password":"Mostrar contraseña","Smileys & Emotion":"Smileys y emoticonos","Start slideshow":"Iniciar la presentación",Submit:"Enviar",Symbols:"Símbolos","Travel & Places":"Viajes y lugares","Type to search time zone":"Escribe para buscar un huso de horario","Unable to search the group":"No es posible buscar en el grupo","Undo changes":"Deshacer cambios",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Escribir mensaje, utilice "@" para mencionar a alguien, utilice ":" para autocompletado de emojis ...'}},{locale:"eu",translations:{"{tag} (invisible)":"{tag} (ikusezina)","{tag} (restricted)":"{tag} (mugatua)",Actions:"Ekintzak",Activities:"Jarduerak","Animals & Nature":"Animaliak eta Natura","Anything shared with the same group of people will show up here":"Pertsona-talde berarekin partekatutako edozer agertuko da hemen","Avatar of {displayName}":"{displayName}-(e)n irudia","Avatar of {displayName}, {status}":"{displayName} -(e)n irudia, {status}","Cancel changes":"Ezeztatu aldaketak","Change title":"Aldatu titulua",Choose:"Aukeratu","Clear text":"Garbitu testua",Close:"Itxi","Close modal":"Itxi modala","Close navigation":"Itxi nabigazioa","Close sidebar":"Itxi albo-barra","Confirm changes":"Baieztatu aldaketak",Custom:"Pertsonalizatua","Edit item":"Editatu elementua","Error getting related resources":"Errorea erlazionatutako baliabideak lortzerakoan","Error parsing svg":"Errore bat gertatu da svg-a analizatzean","External documentation for {title}":"Kanpoko dokumentazioa {title}(r)entzat",Favorite:"Gogokoa",Flags:"Banderak","Food & Drink":"Janaria eta edariak","Frequently used":"Askotan erabilia",Global:"Globala","Go back to the list":"Bueltatu zerrendara","Hide password":"Ezkutatu pasahitza","Message limit of {count} characters reached":"Mezuaren {count} karaketere-limitera heldu zara","More items …":"Elementu gehiago …",Next:"Hurrengoa","No emoji found":"Ez da emojirik aurkitu","No results":"Emaitzarik ez",Objects:"Objektuak",Open:"Ireki",'Open link to "{resourceTitle}"':'Ireki esteka: "{resourceTitle}"',"Open navigation":"Ireki nabigazioa","Password is secure":"Pasahitza segurua da","Pause slideshow":"Pausatu diaporama","People & Body":"Jendea eta gorputza","Pick an emoji":"Hautatu emoji bat","Please select a time zone:":"Mesedez hautatu ordu-zona bat:",Previous:"Aurrekoa","Related resources":"Erlazionatutako baliabideak",Search:"Bilatu","Search results":"Bilaketa emaitzak","Select a tag":"Hautatu etiketa bat",Settings:"Ezarpenak","Settings navigation":"Nabigazio ezarpenak","Show password":"Erakutsi pasahitza","Smileys & Emotion":"Smileyak eta emozioa","Start slideshow":"Hasi diaporama",Submit:"Bidali",Symbols:"Sinboloak","Travel & Places":"Bidaiak eta lekuak","Type to search time zone":"Idatzi ordu-zona bat bilatzeko","Unable to search the group":"Ezin izan da taldea bilatu","Undo changes":"Aldaketak desegin",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Idatzi mezua, erabili "@" norbait aipatzeko, erabili ":" emojiak automatikoki osatzeko...'}},{locale:"fi_FI",translations:{"{tag} (invisible)":"{tag} (näkymätön)","{tag} (restricted)":"{tag} (rajoitettu)",Actions:"Toiminnot",Activities:"Aktiviteetit","Animals & Nature":"Eläimet & luonto","Avatar of {displayName}":"Käyttäjän {displayName} avatar","Avatar of {displayName}, {status}":"Käyttäjän {displayName} avatar, {status}","Cancel changes":"Peruuta muutokset",Choose:"Valitse",Close:"Sulje","Close navigation":"Sulje navigaatio","Confirm changes":"Vahvista muutokset",Custom:"Mukautettu","Edit item":"Muokkaa kohdetta","External documentation for {title}":"Ulkoinen dokumentaatio kohteelle {title}",Flags:"Liput","Food & Drink":"Ruoka & juoma","Frequently used":"Usein käytetyt",Global:"Yleinen","Go back to the list":"Siirry takaisin listaan","Message limit of {count} characters reached":"Viestin merkken enimmäisimäärä {count} täynnä ",Next:"Seuraava","No emoji found":"Emojia ei löytynyt","No results":"Ei tuloksia",Objects:"Esineet & asiat","Open navigation":"Avaa navigaatio","Pause slideshow":"Keskeytä diaesitys","People & Body":"Ihmiset & keho","Pick an emoji":"Valitse emoji","Please select a time zone:":"Valitse aikavyöhyke:",Previous:"Edellinen",Search:"Etsi","Search results":"Hakutulokset","Select a tag":"Valitse tagi",Settings:"Asetukset","Settings navigation":"Asetusnavigaatio","Smileys & Emotion":"Hymiöt & tunteet","Start slideshow":"Aloita diaesitys",Submit:"Lähetä",Symbols:"Symbolit","Travel & Places":"Matkustus & kohteet","Type to search time zone":"Kirjoita etsiäksesi aikavyöhyke","Unable to search the group":"Ryhmää ei voi hakea","Undo changes":"Kumoa muutokset","Write message, @ to mention someone, : for emoji autocompletion …":"Kirjoita viesti, @ mainitaksesi käyttäjän, : emojin automaattitäydennykseen…"}},{locale:"fr",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (restreint)",Actions:"Actions",Activities:"Activités","Animals & Nature":"Animaux & Nature","Anything shared with the same group of people will show up here":"Tout ce qui est partagé avec le même groupe de personnes apparaîtra ici","Avatar of {displayName}":"Avatar de {displayName}","Avatar of {displayName}, {status}":"Avatar de {displayName}, {status}","Cancel changes":"Annuler les modifications","Change title":"Modifier le titre",Choose:"Choisir","Clear text":"Effacer le texte",Close:"Fermer","Close modal":"Fermer la fenêtre","Close navigation":"Fermer la navigation","Close sidebar":"Fermer la barre latérale","Confirm changes":"Confirmer les modifications",Custom:"Personnalisé","Edit item":"Éditer l'élément","Error getting related resources":"Erreur à la récupération des ressources liées","Error parsing svg":"Erreur d'analyse SVG","External documentation for {title}":"Documentation externe pour {title}",Favorite:"Favori",Flags:"Drapeaux","Food & Drink":"Nourriture & Boissons","Frequently used":"Utilisés fréquemment",Global:"Global","Go back to the list":"Retourner à la liste","Hide password":"Cacher le mot de passe","Message limit of {count} characters reached":"Limite de messages de {count} caractères atteinte","More items …":"Plus d'éléments...",Next:"Suivant","No emoji found":"Pas d’émoji trouvé","No results":"Aucun résultat",Objects:"Objets",Open:"Ouvrir",'Open link to "{resourceTitle}"':'Ouvrir le lien vers "{resourceTitle}"',"Open navigation":"Ouvrir la navigation","Password is secure":"Le mot de passe est sécurisé","Pause slideshow":"Mettre le diaporama en pause","People & Body":"Personnes & Corps","Pick an emoji":"Choisissez un émoji","Please select a time zone:":"Sélectionnez un fuseau horaire : ",Previous:"Précédent","Related resources":"Ressources liées",Search:"Chercher","Search results":"Résultats de recherche","Select a tag":"Sélectionnez une balise",Settings:"Paramètres","Settings navigation":"Navigation dans les paramètres","Show password":"Afficher le mot de passe","Smileys & Emotion":"Smileys & Émotions","Start slideshow":"Démarrer le diaporama",Submit:"Valider",Symbols:"Symboles","Travel & Places":"Voyage & Lieux","Type to search time zone":"Saisissez les premiers lettres pour rechercher un fuseau horaire","Unable to search the group":"Impossible de chercher le groupe","Undo changes":"Annuler les changements",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Écrire un message, utiliser "@" pour mentionner une personne, ":" pour l\'autocomplétion des émojis...'}},{locale:"gl",translations:{"{tag} (invisible)":"{tag} (invisíbel)","{tag} (restricted)":"{tag} (restrinxido)",Actions:"Accións",Activities:"Actividades","Animals & Nature":"Animais e natureza","Cancel changes":"Cancelar os cambios",Choose:"Escoller",Close:"Pechar","Confirm changes":"Confirma os cambios",Custom:"Personalizado","External documentation for {title}":"Documentación externa para {title}",Flags:"Bandeiras","Food & Drink":"Comida e bebida","Frequently used":"Usado con frecuencia","Message limit of {count} characters reached":"Acadouse o límite de {count} caracteres por mensaxe",Next:"Seguinte","No emoji found":"Non se atopou ningún «emoji»","No results":"Sen resultados",Objects:"Obxectos","Pause slideshow":"Pausar o diaporama","People & Body":"Persoas e corpo","Pick an emoji":"Escolla un «emoji»",Previous:"Anterir",Search:"Buscar","Search results":"Resultados da busca","Select a tag":"Seleccione unha etiqueta",Settings:"Axustes","Settings navigation":"Navegación polos axustes","Smileys & Emotion":"Sorrisos e emocións","Start slideshow":"Iniciar o diaporama",Submit:"Enviar",Symbols:"Símbolos","Travel & Places":"Viaxes e lugares","Unable to search the group":"Non foi posíbel buscar o grupo","Write message, @ to mention someone …":"Escriba a mensaxe, @ para mencionar a alguén…"}},{locale:"he",translations:{"{tag} (invisible)":"{tag} (נסתר)","{tag} (restricted)":"{tag} (מוגבל)",Actions:"פעולות",Activities:"פעילויות","Animals & Nature":"חיות וטבע",Choose:"בחירה",Close:"סגירה",Custom:"בהתאמה אישית",Flags:"דגלים","Food & Drink":"מזון ומשקאות","Frequently used":"בשימוש תדיר",Next:"הבא","No emoji found":"לא נמצא אמוג׳י","No results":"אין תוצאות",Objects:"חפצים","Pause slideshow":"השהיית מצגת","People & Body":"אנשים וגוף","Pick an emoji":"נא לבחור אמוג׳י",Previous:"הקודם",Search:"חיפוש","Search results":"תוצאות חיפוש","Select a tag":"בחירת תגית",Settings:"הגדרות","Smileys & Emotion":"חייכנים ורגשונים","Start slideshow":"התחלת המצגת",Symbols:"סמלים","Travel & Places":"טיולים ומקומות","Unable to search the group":"לא ניתן לחפש בקבוצה"}},{locale:"hu_HU",translations:{"{tag} (invisible)":"{tag} (láthatatlan)","{tag} (restricted)":"{tag} (korlátozott)",Actions:"Műveletek",Activities:"Tevékenységek","Animals & Nature":"Állatok és természet","Anything shared with the same group of people will show up here":"Minden, amit ugyanazzal a csoporttal oszt meg, itt fog megjelenni","Avatar of {displayName}":"{displayName} profilképe","Avatar of {displayName}, {status}":"{displayName} profilképe, {status}","Cancel changes":"Változtatások elvetése","Change title":"Cím megváltoztatása",Choose:"Válassszon","Clear text":"Szöveg törlése",Close:"Bezárás","Close modal":"Ablak bezárása","Close navigation":"Navigáció bezárása","Close sidebar":"Oldalsáv bezárása","Confirm changes":"Változtatások megerősítése",Custom:"Egyéni","Edit item":"Elem szerkesztése","Error getting related resources":"Hiba a kapcsolódó erőforrások lekérésekor","Error parsing svg":"Hiba az SVG feldolgozásakor","External documentation for {title}":"Külső dokumentáció ehhez: {title}",Favorite:"Kedvenc",Flags:"Zászlók","Food & Drink":"Étel és ital","Frequently used":"Gyakran használt",Global:"Globális","Go back to the list":"Ugrás vissza a listához","Hide password":"Jelszó elrejtése","Message limit of {count} characters reached":"{count} karakteres üzenetkorlát elérve","More items …":"További elemek...",Next:"Következő","No emoji found":"Nem található emodzsi","No results":"Nincs találat",Objects:"Tárgyak",Open:"Megnyitás",'Open link to "{resourceTitle}"':"A(z) „{resourceTitle}” hivatkozásának megnyitása","Open navigation":"Navigáció megnyitása","Password is secure":"A jelszó biztonságos","Pause slideshow":"Diavetítés szüneteltetése","People & Body":"Emberek és test","Pick an emoji":"Válasszon egy emodzsit","Please select a time zone:":"Válasszon időzónát:",Previous:"Előző","Related resources":"Kapcsolódó erőforrások",Search:"Keresés","Search results":"Találatok","Select a tag":"Válasszon címkét",Settings:"Beállítások","Settings navigation":"Navigáció a beállításokban","Show password":"Jelszó megjelenítése","Smileys & Emotion":"Mosolyok és érzelmek","Start slideshow":"Diavetítés indítása",Submit:"Beküldés",Symbols:"Szimbólumok","Travel & Places":"Utazás és helyek","Type to search time zone":"Gépeljen az időzóna kereséséhez","Unable to search the group":"A csoport nem kereshető","Undo changes":"Változtatások visszavonása",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':"Írjon egy üzenetet, használja a „@”-ot valaki megemlítéséhet, illetve a „:”-ot az emodzsik automatikus kiegészítéséhez…"}},{locale:"is",translations:{"{tag} (invisible)":"{tag} (ósýnilegt)","{tag} (restricted)":"{tag} (takmarkað)",Actions:"Aðgerðir",Activities:"Aðgerðir","Animals & Nature":"Dýr og náttúra",Choose:"Velja",Close:"Loka",Custom:"Sérsniðið",Flags:"Flögg","Food & Drink":"Matur og drykkur","Frequently used":"Oftast notað",Next:"Næsta","No emoji found":"Ekkert tjáningartákn fannst","No results":"Engar niðurstöður",Objects:"Hlutir","Pause slideshow":"Gera hlé á skyggnusýningu","People & Body":"Fólk og líkami","Pick an emoji":"Veldu tjáningartákn",Previous:"Fyrri",Search:"Leita","Search results":"Leitarniðurstöður","Select a tag":"Veldu merki",Settings:"Stillingar","Smileys & Emotion":"Broskallar og tilfinningar","Start slideshow":"Byrja skyggnusýningu",Symbols:"Tákn","Travel & Places":"Staðir og ferðalög","Unable to search the group":"Get ekki leitað í hópnum"}},{locale:"it",translations:{"{tag} (invisible)":"{tag} (invisibile)","{tag} (restricted)":"{tag} (limitato)",Actions:"Azioni",Activities:"Attività","Animals & Nature":"Animali e natura","Anything shared with the same group of people will show up here":"Tutto ciò che è stato condiviso con lo stesso gruppo di persone viene visualizzato qui","Avatar of {displayName}":"Avatar di {displayName}","Avatar of {displayName}, {status}":"Avatar di {displayName}, {status}","Cancel changes":"Annulla modifiche","Change title":"Modifica il titolo",Choose:"Scegli","Clear text":"Cancella il testo",Close:"Chiudi","Close modal":"Chiudi il messaggio modale","Close navigation":"Chiudi la navigazione","Close sidebar":"Chiudi la barra laterale","Confirm changes":"Conferma modifiche",Custom:"Personalizzato","Edit item":"Modifica l'elemento","Error getting related resources":"Errore nell'ottenere risorse correlate","Error parsing svg":"Errore nell'analizzare l'svg","External documentation for {title}":"Documentazione esterna per {title}",Favorite:"Preferito",Flags:"Bandiere","Food & Drink":"Cibo e bevande","Frequently used":"Usati di frequente",Global:"Globale","Go back to the list":"Torna all'elenco","Hide password":"Nascondi la password","Message limit of {count} characters reached":"Limite dei messaggi di {count} caratteri raggiunto","More items …":"Più elementi ...",Next:"Successivo","No emoji found":"Nessun emoji trovato","No results":"Nessun risultato",Objects:"Oggetti",Open:"Apri",'Open link to "{resourceTitle}"':'Apri il link a "{resourceTitle}"',"Open navigation":"Apri la navigazione","Password is secure":"La password è sicura","Pause slideshow":"Presentazione in pausa","People & Body":"Persone e corpo","Pick an emoji":"Scegli un emoji","Please select a time zone:":"Si prega di selezionare un fuso orario:",Previous:"Precedente","Related resources":"Risorse correlate",Search:"Cerca","Search results":"Risultati di ricerca","Select a tag":"Seleziona un'etichetta",Settings:"Impostazioni","Settings navigation":"Navigazione delle impostazioni","Show password":"Mostra la password","Smileys & Emotion":"Faccine ed emozioni","Start slideshow":"Avvia presentazione",Submit:"Invia",Symbols:"Simboli","Travel & Places":"Viaggi e luoghi","Type to search time zone":"Digita per cercare un fuso orario","Unable to search the group":"Impossibile cercare il gruppo","Undo changes":"Cancella i cambiamenti",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Scrivi un messaggio, "@" per menzionare qualcuno, ":" per il completamento automatico delle emoji ...'}},{locale:"ja_JP",translations:{"{tag} (invisible)":"{タグ} (不可視)","{tag} (restricted)":"{タグ} (制限付)",Actions:"操作",Activities:"アクティビティ","Animals & Nature":"動物と自然","Anything shared with the same group of people will show up here":"同じグループで共有しているものは、全てここに表示されます","Avatar of {displayName}":"{displayName} のアバター","Avatar of {displayName}, {status}":"{displayName}, {status} のアバター","Cancel changes":"変更をキャンセル","Change title":"タイトルを変更",Choose:"選択","Clear text":"テキストをクリア",Close:"閉じる","Close modal":"モーダルを閉じる","Close navigation":"ナビゲーションを閉じる","Close sidebar":"サイドバーを閉じる","Confirm changes":"変更を承認",Custom:"カスタム","Edit item":"編集","Error getting related resources":"関連リソースの取得エラー","Error parsing svg":"svgの解析エラー","External documentation for {title}":"{title} のための添付文書",Favorite:"お気に入り",Flags:"国旗","Food & Drink":"食べ物と飲み物","Frequently used":"よく使うもの",Global:"全体","Go back to the list":"リストに戻る","Hide password":"パスワードを非表示","Message limit of {count} characters reached":"{count} 文字のメッセージ上限に達しています","More items …":"他のアイテム",Next:"次","No emoji found":"絵文字が見つかりません","No results":"なし",Objects:"物",Open:"開く",'Open link to "{resourceTitle}"':'"{resourceTitle}"のリンクを開く',"Open navigation":"ナビゲーションを開く","Password is secure":"パスワードは保護されています","Pause slideshow":"スライドショーを一時停止","People & Body":"様々な人と体の部位","Pick an emoji":"絵文字を選択","Please select a time zone:":"タイムゾーンを選んで下さい：",Previous:"前","Related resources":"関連リソース",Search:"検索","Search results":"検索結果","Select a tag":"タグを選択",Settings:"設定","Settings navigation":"ナビゲーション設定","Show password":"パスワードを表示","Smileys & Emotion":"感情表現","Start slideshow":"スライドショーを開始",Submit:"提出",Symbols:"記号","Travel & Places":"旅行と場所","Type to search time zone":"タイムゾーン検索のため入力してください","Unable to search the group":"グループを検索できません","Undo changes":"変更を取り消し",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'メッセージを記入、"@"でメンション、"："で絵文字の自動補完 ...'}},{locale:"lt_LT",translations:{"{tag} (invisible)":"{tag} (nematoma)","{tag} (restricted)":"{tag} (apribota)",Actions:"Veiksmai",Activities:"Veiklos","Animals & Nature":"Gyvūnai ir gamta",Choose:"Pasirinkti",Close:"Užverti",Custom:"Tinkinti","External documentation for {title}":"Išorinė {title} dokumentacija",Flags:"Vėliavos","Food & Drink":"Maistas ir gėrimai","Frequently used":"Dažniausiai naudoti","Message limit of {count} characters reached":"Pasiekta {count} simbolių žinutės riba",Next:"Kitas","No emoji found":"Nerasta jaustukų","No results":"Nėra rezultatų",Objects:"Objektai","Pause slideshow":"Pristabdyti skaidrių rodymą","People & Body":"Žmonės ir kūnas","Pick an emoji":"Pasirinkti jaustuką",Previous:"Ankstesnis",Search:"Ieškoti","Search results":"Paieškos rezultatai","Select a tag":"Pasirinkti žymę",Settings:"Nustatymai","Settings navigation":"Naršymas nustatymuose","Smileys & Emotion":"Šypsenos ir emocijos","Start slideshow":"Pradėti skaidrių rodymą",Submit:"Pateikti",Symbols:"Simboliai","Travel & Places":"Kelionės ir vietos","Unable to search the group":"Nepavyko atlikti paiešką grupėje","Write message, @ to mention someone …":"Rašykite žinutę, naudokite @ norėdami kažką paminėti…"}},{locale:"lv",translations:{"{tag} (invisible)":"{tag} (neredzams)","{tag} (restricted)":"{tag} (ierobežots)",Choose:"Izvēlēties",Close:"Aizvērt",Next:"Nākamais","No results":"Nav rezultātu","Pause slideshow":"Pauzēt slaidrādi",Previous:"Iepriekšējais","Select a tag":"Izvēlēties birku",Settings:"Iestatījumi","Start slideshow":"Sākt slaidrādi"}},{locale:"mk",translations:{"{tag} (invisible)":"{tag} (невидливо)","{tag} (restricted)":"{tag} (ограничено)",Actions:"Акции",Activities:"Активности","Animals & Nature":"Животни & Природа","Avatar of {displayName}":"Аватар на {displayName}","Avatar of {displayName}, {status}":"Аватар на {displayName}, {status}","Cancel changes":"Откажи ги промените","Change title":"Промени наслов",Choose:"Избери",Close:"Затвори","Close modal":"Затвори модал","Close navigation":"Затвори навигација","Confirm changes":"Потврди ги промените",Custom:"Прилагодени","Edit item":"Уреди","External documentation for {title}":"Надворешна документација за {title}",Favorite:"Фаворити",Flags:"Знамиња","Food & Drink":"Храна & Пијалоци","Frequently used":"Најчесто користени",Global:"Глобално","Go back to the list":"Врати се на листата",items:"ставки","Message limit of {count} characters reached":"Ограничувањето на должината на пораката од {count} карактери е надминато","More {dashboardItemType} …":"Повеќе {dashboardItemType} …",Next:"Следно","No emoji found":"Не се пронајдени емотикони","No results":"Нема резултати",Objects:"Објекти",Open:"Отвори","Open navigation":"Отвори навигација","Pause slideshow":"Пузирај слајдшоу","People & Body":"Луѓе & Тело","Pick an emoji":"Избери емотикон","Please select a time zone:":"Изберете временска зона:",Previous:"Предходно",Search:"Барај","Search results":"Резултати од барувањето","Select a tag":"Избери ознака",Settings:"Параметри","Settings navigation":"Параметри за навигација","Smileys & Emotion":"Смешковци & Емотикони","Start slideshow":"Стартувај слајдшоу",Submit:"Испрати",Symbols:"Симболи","Travel & Places":"Патувања & Места","Type to search time zone":"Напишете за да пребарате временска зона","Unable to search the group":"Неможе да се принајде групата","Undo changes":"Врати ги промените","Write message, @ to mention someone, : for emoji autocompletion …":"Напиши порака, @ за да спомнете некого, : за емотинони автоатско комплетирање ..."}},{locale:"my",translations:{"{tag} (invisible)":"{tag} (ကွယ်ဝှက်ထား)","{tag} (restricted)":"{tag} (ကန့်သတ်)",Actions:"လုပ်ဆောင်ချက်များ",Activities:"ပြုလုပ်ဆောင်တာများ","Animals & Nature":"တိရစ္ဆာန်များနှင့် သဘာဝ","Avatar of {displayName}":"{displayName} ၏ ကိုယ်ပွား","Cancel changes":"ပြောင်းလဲမှုများ ပယ်ဖျက်ရန်",Choose:"ရွေးချယ်ရန်",Close:"ပိတ်ရန်","Confirm changes":"ပြောင်းလဲမှုများ အတည်ပြုရန်",Custom:"အလိုကျချိန်ညှိမှု","External documentation for {title}":"{title} အတွက် ပြင်ပ စာရွက်စာတမ်း",Flags:"အလံများ","Food & Drink":"အစားအသောက်","Frequently used":"မကြာခဏအသုံးပြုသော",Global:"ကမ္ဘာလုံးဆိုင်ရာ","Message limit of {count} characters reached":"ကန့်သတ် စာလုံးရေ {count} လုံး ပြည့်ပါပြီ",Next:"နောက်သို့ဆက်ရန်","No emoji found":"အီမိုဂျီ ရှာဖွေမတွေ့နိုင်ပါ","No results":"ရလဒ်မရှိပါ",Objects:"အရာဝတ္ထုများ","Pause slideshow":"စလိုက်ရှိုး ခေတ္တရပ်ရန်","People & Body":"လူပုဂ္ဂိုလ်များနှင့် ခန္ဓာကိုယ်","Pick an emoji":"အီမိုဂျီရွေးရန်","Please select a time zone:":"ဒေသစံတော်ချိန် ရွေးချယ်ပေးပါ",Previous:"ယခင်",Search:"ရှာဖွေရန်","Search results":"ရှာဖွေမှု ရလဒ်များ","Select a tag":"tag ရွေးချယ်ရန်",Settings:"ချိန်ညှိချက်များ","Settings navigation":"ချိန်ညှိချက်အညွှန်း","Smileys & Emotion":"စမိုင်လီများနှင့် အီမိုရှင်း","Start slideshow":"စလိုက်ရှိုးအား စတင်ရန်",Submit:"တင်သွင်းရန်",Symbols:"သင်္ကေတများ","Travel & Places":"ခရီးသွားလာခြင်းနှင့် နေရာများ","Type to search time zone":"ဒေသစံတော်ချိန်များ ရှာဖွေရန် စာရိုက်ပါ","Unable to search the group":"အဖွဲ့အား ရှာဖွေ၍ မရနိုင်ပါ","Write message, @ to mention someone …":"စာရေးသားရန်၊ တစ်စုံတစ်ဦးအား @ အသုံးပြု ရည်ညွှန်းရန်..."}},{locale:"nb_NO",translations:{"{tag} (invisible)":"{tag} (usynlig)","{tag} (restricted)":"{tag} (beskyttet)",Actions:"Handlinger",Activities:"Aktiviteter","Animals & Nature":"Dyr og natur","Anything shared with the same group of people will show up here":"Alt som er delt med den samme gruppen vil vises her","Avatar of {displayName}":"Avataren til {displayName}","Avatar of {displayName}, {status}":"{displayName}'s avatar, {status}","Cancel changes":"Avbryt endringer","Change title":"Endre tittel",Choose:"Velg","Clear text":"Fjern tekst",Close:"Lukk","Close modal":"Lukk modal","Close navigation":"Lukk navigasjon","Close sidebar":"Lukk sidepanel","Confirm changes":"Bekreft endringer",Custom:"Tilpasset","Edit item":"Rediger","Error getting related resources":"Feil ved henting av relaterte ressurser","Error parsing svg":"Feil ved parsing av svg","External documentation for {title}":"Ekstern dokumentasjon for {title}",Favorite:"Favoritt",Flags:"Flagg","Food & Drink":"Mat og drikke","Frequently used":"Ofte brukt",Global:"Global","Go back to the list":"Gå tilbake til listen","Hide password":"Skjul passord","Message limit of {count} characters reached":"Karakter begrensing {count} nådd i melding","More items …":"Flere gjenstander...",Next:"Neste","No emoji found":"Fant ingen emoji","No results":"Ingen resultater",Objects:"Objekter",Open:"Åpne",'Open link to "{resourceTitle}"':'Åpne link til "{resourceTitle}"',"Open navigation":"Åpne navigasjon","Password is secure":"Passordet er sikkert","Pause slideshow":"Pause lysbildefremvisning","People & Body":"Mennesker og kropp","Pick an emoji":"Velg en emoji","Please select a time zone:":"Vennligst velg tidssone",Previous:"Forrige","Related resources":"Relaterte ressurser",Search:"Søk","Search results":"Søkeresultater","Select a tag":"Velg en merkelapp",Settings:"Innstillinger","Settings navigation":"Navigasjonsinstillinger","Show password":"Vis passord","Smileys & Emotion":"Smilefjes og følelser","Start slideshow":"Start lysbildefremvisning",Submit:"Send",Symbols:"Symboler","Travel & Places":"Reise og steder","Type to search time zone":"Tast for å søke etter tidssone","Unable to search the group":"Kunne ikke søke i gruppen","Undo changes":"Tilbakestill endringer",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Skriv melding, bruk "@" for å nevne noen, bruk ":" for autofullføring av emoji...'}},{locale:"nl",translations:{"{tag} (invisible)":"{tag} (onzichtbaar)","{tag} (restricted)":"{tag} (beperkt)",Actions:"Acties",Activities:"Activiteiten","Animals & Nature":"Dieren & Natuur","Avatar of {displayName}":"Avatar van {displayName}","Avatar of {displayName}, {status}":"Avatar van {displayName}, {status}","Cancel changes":"Wijzigingen annuleren",Choose:"Kies",Close:"Sluiten","Close navigation":"Navigatie sluiten","Confirm changes":"Wijzigingen bevestigen",Custom:"Aangepast","Edit item":"Item bewerken","External documentation for {title}":"Externe documentatie voor {title}",Flags:"Vlaggen","Food & Drink":"Eten & Drinken","Frequently used":"Vaak gebruikt",Global:"Globaal","Go back to the list":"Ga terug naar de lijst","Message limit of {count} characters reached":"Berichtlimiet van {count} karakters bereikt",Next:"Volgende","No emoji found":"Geen emoji gevonden","No results":"Geen resultaten",Objects:"Objecten","Open navigation":"Navigatie openen","Pause slideshow":"Pauzeer diavoorstelling","People & Body":"Mensen & Lichaam","Pick an emoji":"Kies een emoji","Please select a time zone:":"Selecteer een tijdzone:",Previous:"Vorige",Search:"Zoeken","Search results":"Zoekresultaten","Select a tag":"Selecteer een label",Settings:"Instellingen","Settings navigation":"Instellingen navigatie","Smileys & Emotion":"Smileys & Emotie","Start slideshow":"Start diavoorstelling",Submit:"Verwerken",Symbols:"Symbolen","Travel & Places":"Reizen & Plaatsen","Type to search time zone":"Type om de tijdzone te zoeken","Unable to search the group":"Kan niet in de groep zoeken","Undo changes":"Wijzigingen ongedaan maken","Write message, @ to mention someone, : for emoji autocompletion …":"Schrijf bericht, @ om iemand te noemen, : voor emoji auto-aanvullen ..."}},{locale:"oc",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (limit)",Actions:"Accions",Choose:"Causir",Close:"Tampar",Next:"Seguent","No results":"Cap de resultat","Pause slideshow":"Metre en pausa lo diaporama",Previous:"Precedent","Select a tag":"Seleccionar una etiqueta",Settings:"Paramètres","Start slideshow":"Lançar lo diaporama"}},{locale:"pl",translations:{"{tag} (invisible)":"{tag} (niewidoczna)","{tag} (restricted)":"{tag} (ograniczona)",Actions:"Działania",Activities:"Aktywność","Animals & Nature":"Zwierzęta i natura","Anything shared with the same group of people will show up here":"Tutaj pojawi się wszystko, co zostało udostępnione tej samej grupie osób","Avatar of {displayName}":"Awatar {displayName}","Avatar of {displayName}, {status}":"Awatar {displayName}, {status}","Cancel changes":"Anuluj zmiany","Change title":"Zmień tytuł",Choose:"Wybierz","Clear text":"Wyczyść tekst",Close:"Zamknij","Close modal":"Zamknij modal","Close navigation":"Zamknij nawigację","Close sidebar":"Zamknij pasek boczny","Confirm changes":"Potwierdź zmiany",Custom:"Zwyczajne","Edit item":"Edytuj element","Error getting related resources":"Błąd podczas pobierania powiązanych zasobów","Error parsing svg":"Błąd podczas analizowania svg","External documentation for {title}":"Dokumentacja zewnętrzna dla {title}",Favorite:"Ulubiony",Flags:"Flagi","Food & Drink":"Jedzenie i picie","Frequently used":"Często używane",Global:"Globalnie","Go back to the list":"Powrót do listy","Hide password":"Ukryj hasło","Message limit of {count} characters reached":"Przekroczono limit wiadomości wynoszący {count} znaków","More items …":"Więcej pozycji…",Next:"Następny","No emoji found":"Nie znaleziono emoji","No results":"Brak wyników",Objects:"Obiekty",Open:"Otwórz",'Open link to "{resourceTitle}"':'Otwórz link do "{resourceTitle}"',"Open navigation":"Otwórz nawigację","Password is secure":"Hasło jest bezpieczne","Pause slideshow":"Wstrzymaj pokaz slajdów","People & Body":"Ludzie i ciało","Pick an emoji":"Wybierz emoji","Please select a time zone:":"Wybierz strefę czasową:",Previous:"Poprzedni","Related resources":"Powiązane zasoby",Search:"Szukaj","Search results":"Wyniki wyszukiwania","Select a tag":"Wybierz etykietę",Settings:"Ustawienia","Settings navigation":"Ustawienia nawigacji","Show password":"Pokaż hasło","Smileys & Emotion":"Buźki i emotikony","Start slideshow":"Rozpocznij pokaz slajdów",Submit:"Wyślij",Symbols:"Symbole","Travel & Places":"Podróże i miejsca","Type to search time zone":"Wpisz, aby wyszukać strefę czasową","Unable to search the group":"Nie można przeszukać grupy","Undo changes":"Cofnij zmiany",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Napisz wiadomość, "@" aby o kimś wspomnieć, ":" dla autouzupełniania emoji…'}},{locale:"pt_BR",translations:{"{tag} (invisible)":"{tag} (invisível)","{tag} (restricted)":"{tag} (restrito) ",Actions:"Ações",Activities:"Atividades","Animals & Nature":"Animais & Natureza","Anything shared with the same group of people will show up here":"Qualquer coisa compartilhada com o mesmo grupo de pessoas aparecerá aqui","Avatar of {displayName}":"Avatar de {displayName}","Avatar of {displayName}, {status}":"Avatar de {displayName}, {status}","Cancel changes":"Cancelar alterações","Change title":"Alterar título",Choose:"Escolher","Clear text":"Limpar texto",Close:"Fechar","Close modal":"Fechar modal","Close navigation":"Fechar navegação","Close sidebar":"Fechar barra lateral","Confirm changes":"Confirmar alterações",Custom:"Personalizado","Edit item":"Editar item","Error getting related resources":"Erro ao obter recursos relacionados","Error parsing svg":"Erro ao analisar svg","External documentation for {title}":"Documentação externa para {title}",Favorite:"Favorito",Flags:"Bandeiras","Food & Drink":"Comida & Bebida","Frequently used":"Mais usados",Global:"Global","Go back to the list":"Volte para a lista","Hide password":"Ocultar a senha","Message limit of {count} characters reached":"Limite de mensagem de {count} caracteres atingido","More items …":"Mais itens …",Next:"Próximo","No emoji found":"Nenhum emoji encontrado","No results":"Sem resultados",Objects:"Objetos",Open:"Aberto",'Open link to "{resourceTitle}"':'Abrir link para "{resourceTitle}"',"Open navigation":"Abrir navegação","Password is secure":"A senha é segura","Pause slideshow":"Pausar apresentação de slides","People & Body":"Pessoas & Corpo","Pick an emoji":"Escolha um emoji","Please select a time zone:":"Selecione um fuso horário: ",Previous:"Anterior","Related resources":"Recursos relacionados",Search:"Pesquisar","Search results":"Resultados da pesquisa","Select a tag":"Selecionar uma tag",Settings:"Configurações","Settings navigation":"Navegação de configurações","Show password":"Mostrar senha","Smileys & Emotion":"Smiles & Emoções","Start slideshow":"Iniciar apresentação de slides",Submit:"Enviar",Symbols:"Símbolo","Travel & Places":"Viagem & Lugares","Type to search time zone":"Digite para pesquisar o fuso horário ","Unable to search the group":"Não foi possível pesquisar o grupo","Undo changes":"Desfazer modificações",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Escreva mensagens, use "@" para mencionar algum, use ":" for autocompletar emoji …'}},{locale:"pt_PT",translations:{"{tag} (invisible)":"{tag} (invisivel)","{tag} (restricted)":"{tag} (restrito)",Actions:"Ações",Choose:"Escolher",Close:"Fechar",Next:"Seguinte","No results":"Sem resultados","Pause slideshow":"Pausar diaporama",Previous:"Anterior","Select a tag":"Selecionar uma etiqueta",Settings:"Definições","Start slideshow":"Iniciar diaporama","Unable to search the group":"Não é possível pesquisar o grupo"}},{locale:"ro",translations:{"{tag} (invisible)":"{tag} (invizibil)","{tag} (restricted)":"{tag} (restricționat)",Actions:"Acțiuni",Activities:"Activități","Animals & Nature":"Animale și natură","Anything shared with the same group of people will show up here":"Tot ceea ce este partajat cu același grup de persoane va fi afișat aici","Avatar of {displayName}":"Avatarul lui {displayName}","Avatar of {displayName}, {status}":"Avatarul lui {displayName}, {status}","Cancel changes":"Anulează modificările","Change title":"Modificați titlul",Choose:"Alegeți","Clear text":"Șterge textul",Close:"Închideți","Close modal":"Închideți modulul","Close navigation":"Închideți navigarea","Close sidebar":"Închide bara laterală","Confirm changes":"Confirmați modificările",Custom:"Personalizat","Edit item":"Editați elementul","Error getting related resources":" Eroare la returnarea resurselor legate","Error parsing svg":"Eroare de analizare a svg","External documentation for {title}":"Documentație externă pentru {title}",Favorite:"Favorit",Flags:"Marcaje","Food & Drink":"Alimente și băuturi","Frequently used":"Utilizate frecvent",Global:"Global","Go back to the list":"Întoarceți-vă la listă","Hide password":"Ascunde parola","Message limit of {count} characters reached":"Limita mesajului de {count} caractere a fost atinsă","More items …":"Mai multe articole ...",Next:"Următorul","No emoji found":"Nu s-a găsit niciun emoji","No results":"Nu există rezultate",Objects:"Obiecte",Open:"Deschideți",'Open link to "{resourceTitle}"':'Deschide legătura la "{resourceTitle}"',"Open navigation":"Deschideți navigația","Password is secure":"Parola este sigură","Pause slideshow":"Pauză prezentare de diapozitive","People & Body":"Oameni și corp","Pick an emoji":"Alege un emoji","Please select a time zone:":"Vă rugăm să selectați un fus orar:",Previous:"Anterior","Related resources":"Resurse legate",Search:"Căutare","Search results":"Rezultatele căutării","Select a tag":"Selectați o etichetă",Settings:"Setări","Settings navigation":"Navigare setări","Show password":"Arată parola","Smileys & Emotion":"Zâmbete și emoții","Start slideshow":"Începeți prezentarea de diapozitive",Submit:"Trimiteți",Symbols:"Simboluri","Travel & Places":"Călătorii și locuri","Type to search time zone":"Tastați pentru a căuta fusul orar","Unable to search the group":"Imposibilitatea de a căuta în grup","Undo changes":"Anularea modificărilor",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Scrie un mesaj, folosește "@" pentru a menționa pe cineva, folosește ":" pentru autocompletarea cu emoji ...'}},{locale:"ru",translations:{"{tag} (invisible)":"{tag} (невидимое)","{tag} (restricted)":"{tag} (ограниченное)",Actions:"Действия ",Activities:"События","Animals & Nature":"Животные и природа ","Avatar of {displayName}":"Аватар {displayName}","Avatar of {displayName}, {status}":"Фотография {displayName}, {status}","Cancel changes":"Отменить изменения",Choose:"Выберите",Close:"Закрыть","Close modal":"Закрыть модальное окно","Close navigation":"Закрыть навигацию","Confirm changes":"Подтвердить изменения",Custom:"Пользовательское","Edit item":"Изменить элемент","External documentation for {title}":"Внешняя документация для {title}",Flags:"Флаги","Food & Drink":"Еда, напиток","Frequently used":"Часто используемый",Global:"Глобальный","Go back to the list":"Вернуться к списку",items:"элементов","Message limit of {count} characters reached":"Достигнуто ограничение на количество символов в {count}","More {dashboardItemType} …":"Больше {dashboardItemType} …",Next:"Следующее","No emoji found":"Эмодзи не найдено","No results":"Результаты отсуствуют",Objects:"Объекты",Open:"Открыть","Open navigation":"Открыть навигацию","Pause slideshow":"Приостановить показ слйдов","People & Body":"Люди и тело","Pick an emoji":"Выберите эмодзи","Please select a time zone:":"Пожалуйста, выберите часовой пояс:",Previous:"Предыдущее",Search:"Поиск","Search results":"Результаты поиска","Select a tag":"Выберите метку",Settings:"Параметры","Settings navigation":"Навигация по настройкам","Smileys & Emotion":"Смайлики и эмоции","Start slideshow":"Начать показ слайдов",Submit:"Утвердить",Symbols:"Символы","Travel & Places":"Путешествия и места","Type to search time zone":"Введите для поиска часового пояса","Unable to search the group":"Невозможно найти группу","Undo changes":"Отменить изменения","Write message, @ to mention someone, : for emoji autocompletion …":"Напишите сообщение, @ - чтобы упомянуть кого-то, : - для автозаполнения эмодзи …"}},{locale:"sk_SK",translations:{"{tag} (invisible)":"{tag} (neviditeľný)","{tag} (restricted)":"{tag} (obmedzený)",Actions:"Akcie",Activities:"Aktivity","Animals & Nature":"Zvieratá a príroda","Avatar of {displayName}":"Avatar {displayName}","Avatar of {displayName}, {status}":"Avatar {displayName}, {status}","Cancel changes":"Zrušiť zmeny",Choose:"Vybrať",Close:"Zatvoriť","Close navigation":"Zavrieť navigáciu","Confirm changes":"Potvrdiť zmeny",Custom:"Zvyk","Edit item":"Upraviť položku","External documentation for {title}":"Externá dokumentácia pre {title}",Flags:"Vlajky","Food & Drink":"Jedlo a nápoje","Frequently used":"Často používané",Global:"Globálne","Go back to the list":"Naspäť na zoznam","Message limit of {count} characters reached":"Limit správy na {count} znakov dosiahnutý",Next:"Ďalší","No emoji found":"Nenašli sa žiadne emodži","No results":"Žiadne výsledky",Objects:"Objekty","Open navigation":"Otvoriť navigáciu","Pause slideshow":"Pozastaviť prezentáciu","People & Body":"Ľudia a telo","Pick an emoji":"Vyberte si emodži","Please select a time zone:":"Prosím vyberte časovú zónu:",Previous:"Predchádzajúci",Search:"Hľadať","Search results":"Výsledky vyhľadávania","Select a tag":"Vybrať štítok",Settings:"Nastavenia","Settings navigation":"Navigácia v nastaveniach","Smileys & Emotion":"Smajlíky a emócie","Start slideshow":"Začať prezentáciu",Submit:"Odoslať",Symbols:"Symboly","Travel & Places":"Cestovanie a miesta","Type to search time zone":"Začníte písať pre vyhľadávanie časovej zóny","Unable to search the group":"Skupinu sa nepodarilo nájsť","Undo changes":"Vrátiť zmeny","Write message, @ to mention someone, : for emoji autocompletion …":"Napíšte správu, @ ak chcete niekoho spomenúť, : pre automatické dopĺňanie emotikonov…"}},{locale:"sl",translations:{"{tag} (invisible)":"{tag} (nevidno)","{tag} (restricted)":"{tag} (omejeno)",Actions:"Dejanja",Activities:"Dejavnosti","Animals & Nature":"Živali in Narava","Avatar of {displayName}":"Podoba {displayName}","Avatar of {displayName}, {status}":"Prikazna slika {displayName}, {status}","Cancel changes":"Prekliči spremembe","Change title":"Spremeni naziv",Choose:"Izbor","Clear text":"Počisti besedilo",Close:"Zapri","Close modal":"Zapri pojavno okno","Close navigation":"Zapri krmarjenje","Close sidebar":"Zapri stransko vrstico","Confirm changes":"Potrdi spremembe",Custom:"Po meri","Edit item":"Uredi predmet","Error getting related resources":"Napaka pridobivanja povezanih virov","External documentation for {title}":"Zunanja dokumentacija za {title}",Favorite:"Priljubljeno",Flags:"Zastavice","Food & Drink":"Hrana in Pijača","Frequently used":"Pogostost uporabe",Global:"Splošno","Go back to the list":"Vrni se na seznam","Hide password":"Skrij geslo","Message limit of {count} characters reached":"Dosežena omejitev {count} znakov na sporočilo.","More items …":"Več predmetov ...",Next:"Naslednji","No emoji found":"Ni najdenih izraznih ikon","No results":"Ni zadetkov",Objects:"Predmeti",Open:"Odpri",'Open link to "{resourceTitle}"':"Odpri povezavo do »{resourceTitle}«","Open navigation":"Odpri krmarjenje","Password is secure":"Geslo je varno","Pause slideshow":"Ustavi predstavitev","People & Body":"Ljudje in Telo","Pick a date":"Izbor datuma","Pick a date and a time":"Izbor datuma in časa","Pick a month":"Izbor meseca","Pick a time":"Izbor časa","Pick a week":"Izbor tedna","Pick a year":"Izbor leta","Pick an emoji":"Izbor izrazne ikone","Please select a time zone:":"Izbor časovnega pasu:",Previous:"Predhodni","Related resources":"Povezani viri",Search:"Iskanje","Search results":"Zadetki iskanja","Select a tag":"Izbor oznake",Settings:"Nastavitve","Settings navigation":"Krmarjenje nastavitev","Show password":"Pokaži geslo","Smileys & Emotion":"Izrazne ikone","Start slideshow":"Začni predstavitev",Submit:"Pošlji",Symbols:"Simboli","Travel & Places":"Potovanja in Kraji","Type to search time zone":"Vpišite niz za iskanje časovnega pasu","Unable to search the group":"Ni mogoče iskati po skupini","Undo changes":"Razveljavi spremembe","Write message, @ to mention someone, : for emoji autocompletion …":"Napišite sporočilo, za omembo pred ime postavite@, začnite z : za vstavljanje izraznih ikon …"}},{locale:"sr",translations:{"{tag} (invisible)":"{tag} (nevidljivo)","{tag} (restricted)":"{tag} (ograničeno)",Actions:"Radnje",Activities:"Aktivnosti","Animals & Nature":"Životinje i Priroda","Avatar of {displayName}":"Avatar za {displayName}","Avatar of {displayName}, {status}":"Avatar za {displayName}, {status}","Cancel changes":"Otkaži izmene","Change title":"Izmeni naziv",Choose:"Изаберите",Close:"Затвори","Close modal":"Zatvori modal","Close navigation":"Zatvori navigaciju","Close sidebar":"Zatvori bočnu traku","Confirm changes":"Potvrdite promene",Custom:"Po meri","Edit item":"Uredi stavku","External documentation for {title}":"Eksterna dokumentacija za {title}",Favorite:"Omiljeni",Flags:"Zastave","Food & Drink":"Hrana i Piće","Frequently used":"Često korišćeno",Global:"Globalno","Go back to the list":"Natrag na listu",items:"stavke","Message limit of {count} characters reached":"Dostignuto je ograničenje za poruke od {count} znakova","More {dashboardItemType} …":"Više  {dashboardItemType} …",Next:"Следеће","No emoji found":"Nije pronađen nijedan emodži","No results":"Нема резултата",Objects:"Objekti",Open:"Otvori","Open navigation":"Otvori navigaciju","Pause slideshow":"Паузирај слајд шоу","People & Body":"Ljudi i Telo","Pick an emoji":"Izaberi emodži","Please select a time zone:":"Molimo izaberite vremensku zonu:",Previous:"Претходно",Search:"Pretraži","Search results":"Rezultati pretrage","Select a tag":"Изаберите ознаку",Settings:"Поставке","Settings navigation":"Navigacija u podešavanjima","Smileys & Emotion":"Smajli i Emocije","Start slideshow":"Покрени слајд шоу",Submit:"Prihvati",Symbols:"Simboli","Travel & Places":"Putovanja i Mesta","Type to search time zone":"Ukucaj da pretražiš vremenske zone","Unable to search the group":"Nije moguće pretražiti grupu","Undo changes":"Poništi promene","Write message, @ to mention someone, : for emoji autocompletion …":"Napišite poruku, @ da pomenete nekoga, : za automatsko dovršavanje emodžija…"}},{locale:"sv",translations:{"{tag} (invisible)":"{tag} (osynlig)","{tag} (restricted)":"{tag} (begränsad)",Actions:"Åtgärder",Activities:"Aktiviteter","Animals & Nature":"Djur & Natur","Anything shared with the same group of people will show up here":"Något som delats med samma grupp av personer kommer att visas här","Avatar of {displayName}":"{displayName}s avatar","Avatar of {displayName}, {status}":"{displayName}s avatar, {status}","Cancel changes":"Avbryt ändringar","Change title":"Ändra titel",Choose:"Välj","Clear text":"Ta bort text",Close:"Stäng","Close modal":"Stäng modal","Close navigation":"Stäng navigering","Close sidebar":"Stäng sidopanel","Confirm changes":"Bekräfta ändringar",Custom:"Anpassad","Edit item":"Ändra","Error getting related resources":"Problem att hämta relaterade resurser","Error parsing svg":"Fel vid inläsning av svg","External documentation for {title}":"Extern dokumentation för {title}",Favorite:"Favorit",Flags:"Flaggor","Food & Drink":"Mat & Dryck","Frequently used":"Används ofta",Global:"Global","Go back to the list":"Gå tillbaka till listan","Hide password":"Göm lössenordet","Message limit of {count} characters reached":"Meddelandegräns {count} tecken används","More items …":"Fler objekt",Next:"Nästa","No emoji found":"Hittade inga emojis","No results":"Inga resultat",Objects:"Objekt",Open:"Öppna",'Open link to "{resourceTitle}"':'Öppna länk till "{resourceTitle}"',"Open navigation":"Öppna navigering","Password is secure":"Lössenordet är säkert","Pause slideshow":"Pausa bildspelet","People & Body":"Kropp & Själ","Pick an emoji":"Välj en emoji","Please select a time zone:":"Välj tidszon:",Previous:"Föregående","Related resources":"Relaterade resurser",Search:"Sök","Search results":"Sökresultat","Select a tag":"Välj en tag",Settings:"Inställningar","Settings navigation":"Inställningsmeny","Show password":"Visa lössenordet","Smileys & Emotion":"Selfies & Känslor","Start slideshow":"Starta bildspelet",Submit:"Skicka",Symbols:"Symboler","Travel & Places":"Resor & Sevärdigheter","Type to search time zone":"Skriv för att välja tidszon","Unable to search the group":"Kunde inte söka i gruppen","Undo changes":"Ångra ändringar",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Skriv meddelande, använd "@" för att nämna någon, använd ":" för automatiska emojiförslag ...'}},{locale:"tr",translations:{"{tag} (invisible)":"{tag} (görünmez)","{tag} (restricted)":"{tag} (kısıtlı)",Actions:"İşlemler",Activities:"Etkinlikler","Animals & Nature":"Hayvanlar ve Doğa","Anything shared with the same group of people will show up here":"Aynı kişi grubu ile paylaşılan herşey burada görüntülenir","Avatar of {displayName}":"{displayName} avatarı","Avatar of {displayName}, {status}":"{displayName}, {status} avatarı","Cancel changes":"Değişiklikleri iptal et","Change title":"Başlığı değiştir",Choose:"Seçin","Clear text":"Metni temizle",Close:"Kapat","Close modal":"Üste açılan pencereyi kapat","Close navigation":"Gezinmeyi kapat","Close sidebar":"Yan çubuğu kapat","Confirm changes":"Değişiklikleri onayla",Custom:"Özel","Edit item":"Ögeyi düzenle","Error getting related resources":"İlgili kaynaklar alınırken sorun çıktı","Error parsing svg":"svg işlenirken sorun çıktı","External documentation for {title}":"{title} için dış belgeler",Favorite:"Sık kullanılanlara ekle",Flags:"Bayraklar","Food & Drink":"Yeme ve İçme","Frequently used":"Sık kullanılanlar",Global:"Evrensel","Go back to the list":"Listeye dön","Hide password":"Parolayı gizle","Message limit of {count} characters reached":"{count} karakter ileti sınırına ulaşıldı","More items …":"Diğer ögeler…",Next:"Sonraki","No emoji found":"Herhangi bir emoji bulunamadı","No results":"Herhangi bir sonuç bulunamadı",Objects:"Nesneler",Open:"Aç",'Open link to "{resourceTitle}"':'"{resourceTitle}" bağlantısını aç',"Open navigation":"Gezinmeyi aç","Password is secure":"Parola güvenli","Pause slideshow":"Slayt sunumunu duraklat","People & Body":"İnsanlar ve Beden","Pick an emoji":"Bir emoji seçin","Please select a time zone:":"Lütfen bir saat dilimi seçin:",Previous:"Önceki","Related resources":"İlgili kaynaklar",Search:"Arama","Search results":"Arama sonuçları","Select a tag":"Bir etiket seçin",Settings:"Ayarlar","Settings navigation":"Gezinme ayarları","Show password":"Parolayı görüntüle","Smileys & Emotion":"İfadeler ve Duygular","Start slideshow":"Slayt sunumunu başlat",Submit:"Gönder",Symbols:"Simgeler","Travel & Places":"Gezi ve Yerler","Type to search time zone":"Saat dilimi aramak için yazmaya başlayın","Unable to search the group":"Grupta arama yapılamadı","Undo changes":"Değişiklikleri geri al",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'İleti yazın, birini anmak için @, otomatik emoji tamamlamak için ":" kullanın…'}},{locale:"uk",translations:{"{tag} (invisible)":"{tag} (невидимий)","{tag} (restricted)":"{tag} (обмежений)",Actions:"Дії",Activities:"Діяльність","Animals & Nature":"Тварини та природа","Avatar of {displayName}":"Аватар {displayName}","Avatar of {displayName}, {status}":"Аватар {displayName}, {status}","Cancel changes":"Скасувати зміни","Change title":"Змінити назву",Choose:"ВиберітьВиберіть","Clear text":"Очистити текст",Close:"Закрити","Close modal":"Закрити модаль","Close navigation":"Закрити навігацію","Close sidebar":"Закрити бічну панель","Confirm changes":"Підтвердити зміни",Custom:"Власне","Edit item":"Редагувати елемент","External documentation for {title}":"Зовнішня документація для {title}",Favorite:"Улюблений",Flags:"Прапори","Food & Drink":"Їжа та напої","Frequently used":"Найчастіші",Global:"Глобальний","Go back to the list":"Повернутися до списку","Hide password":"Приховати пароль",items:"елементи","Message limit of {count} characters reached":"Вичерпано ліміт у {count} символів для повідомлення","More {dashboardItemType} …":"Більше {dashboardItemType}…",Next:"Вперед","No emoji found":"Емоційки відсутні","No results":"Відсутні результати",Objects:"Об'єкти",Open:"Відкрити","Open navigation":"Відкрити навігацію","Password is secure":"Пароль безпечний","Pause slideshow":"Пауза у показі слайдів","People & Body":"Люди та жести","Pick an emoji":"Виберіть емоційку","Please select a time zone:":"Виберіть часовий пояс:",Previous:"Назад",Search:"Пошук","Search results":"Результати пошуку","Select a tag":"Виберіть позначку",Settings:"Налаштування","Settings navigation":"Навігація у налаштуваннях","Show password":"Показати пароль","Smileys & Emotion":"Смайли та емоції","Start slideshow":"Почати показ слайдів",Submit:"Надіслати",Symbols:"Символи","Travel & Places":"Поїздки та місця","Type to search time zone":"Введіть для пошуку часовий пояс","Unable to search the group":"Неможливо шукати в групі","Undo changes":"Скасувати зміни","Write message, @ to mention someone, : for emoji autocompletion …":"Напишіть повідомлення, @, щоб згадати когось, : для автозаповнення емодзі…"}},{locale:"zh_CN",translations:{"{tag} (invisible)":"{tag} （不可见）","{tag} (restricted)":"{tag} （受限）",Actions:"行为",Activities:"活动","Animals & Nature":"动物 & 自然","Anything shared with the same group of people will show up here":"与同组用户分享的所有内容都会显示于此","Avatar of {displayName}":"{displayName}的头像","Avatar of {displayName}, {status}":"{displayName}的头像，{status}","Cancel changes":"取消更改","Change title":"更改标题",Choose:"选择","Clear text":"清除文本",Close:"关闭","Close modal":"关闭窗口","Close navigation":"关闭导航","Close sidebar":"关闭侧边栏","Confirm changes":"确认更改",Custom:"自定义","Edit item":"编辑项目","Error getting related resources":"获取相关资源时出错","Error parsing svg":"解析 svg 时出错","External documentation for {title}":"{title}的外部文档",Favorite:"喜爱",Flags:"旗帜","Food & Drink":"食物 & 饮品","Frequently used":"经常使用",Global:"全局","Go back to the list":"返回至列表","Hide password":"隐藏密码","Message limit of {count} characters reached":"已达到 {count} 个字符的消息限制","More items …":"更多项目…",Next:"下一个","No emoji found":"表情未找到","No results":"无结果",Objects:"物体",Open:"打开",'Open link to "{resourceTitle}"':'打开"{resourceTitle}"的连接',"Open navigation":"开启导航","Password is secure":"密码安全","Pause slideshow":"暂停幻灯片","People & Body":"人 & 身体","Pick an emoji":"选择一个表情","Please select a time zone:":"请选择一个时区：",Previous:"上一个","Related resources":"相关资源",Search:"搜索","Search results":"搜索结果","Select a tag":"选择一个标签",Settings:"设置","Settings navigation":"设置向导","Show password":"显示密码","Smileys & Emotion":"笑脸 & 情感","Start slideshow":"开始幻灯片",Submit:"提交",Symbols:"符号","Travel & Places":"旅游 & 地点","Type to search time zone":"打字以搜索时区","Unable to search the group":"无法搜索分组","Undo changes":"撤销更改",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'写信息，使用"@"来提及某人，使用":"进行表情符号自动完成 ...'}},{locale:"zh_HK",translations:{"{tag} (invisible)":"{tag} (隱藏)","{tag} (restricted)":"{tag} (受限)",Actions:"動作",Activities:"活動","Animals & Nature":"動物與自然","Anything shared with the same group of people will show up here":"與同一組人共享的任何內容都會顯示在此處","Avatar of {displayName}":"{displayName} 的頭像","Avatar of {displayName}, {status}":"{displayName} 的頭像，{status}","Cancel changes":"取消更改","Change title":"更改標題",Choose:"選擇","Clear text":"清除文本",Close:"關閉","Close modal":"關閉模態","Close navigation":"關閉導航","Close sidebar":"關閉側邊欄","Confirm changes":"確認更改",Custom:"自定義","Edit item":"編輯項目","Error getting related resources":"獲取相關資源出錯","Error parsing svg":"解析 svg 時出錯","External documentation for {title}":"{title} 的外部文檔",Favorite:"喜愛",Flags:"旗幟","Food & Drink":"食物與飲料","Frequently used":"經常使用",Global:"全球的","Go back to the list":"返回清單","Hide password":"隱藏密碼","Message limit of {count} characters reached":"已達到訊息最多 {count} 字元限制","More items …":"更多項目 …",Next:"下一個","No emoji found":"未找到表情符號","No results":"無結果",Objects:"物件",Open:"打開",'Open link to "{resourceTitle}"':"打開指向 “{resourceTitle}” 的鏈結","Open navigation":"開啟導航","Password is secure":"密碼是安全的","Pause slideshow":"暫停幻燈片","People & Body":"人物","Pick an emoji":"選擇表情符號","Please select a time zone:":"請選擇時區：",Previous:"上一個","Related resources":"相關資源",Search:"搜尋","Search results":"搜尋結果","Select a tag":"選擇標籤",Settings:"設定","Settings navigation":"設定值導覽","Show password":"顯示密碼","Smileys & Emotion":"表情","Start slideshow":"開始幻燈片",Submit:"提交",Symbols:"標誌","Travel & Places":"旅遊與景點","Type to search time zone":"鍵入以搜索時區","Unable to search the group":"無法搜尋群組","Undo changes":"取消更改",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'寫訊息，使用 "@" 來指代某人，使用 ":" 用於表情符號自動填充 ...'}},{locale:"zh_TW",translations:{"{tag} (invisible)":"{tag} (隱藏)","{tag} (restricted)":"{tag} (受限)",Actions:"動作",Activities:"活動","Animals & Nature":"動物與自然",Choose:"選擇",Close:"關閉",Custom:"自定義",Flags:"旗幟","Food & Drink":"食物與飲料","Frequently used":"最近使用","Message limit of {count} characters reached":"已達到訊息最多 {count} 字元限制",Next:"下一個","No emoji found":"未找到表情符號","No results":"無結果",Objects:"物件","Pause slideshow":"暫停幻燈片","People & Body":"人物","Pick an emoji":"選擇表情符號",Previous:"上一個",Search:"搜尋","Search results":"搜尋結果","Select a tag":"選擇標籤",Settings:"設定","Settings navigation":"設定值導覽","Smileys & Emotion":"表情","Start slideshow":"開始幻燈片",Symbols:"標誌","Travel & Places":"旅遊與景點","Unable to search the group":"無法搜尋群組","Write message, @ to mention someone …":"輸入訊息時可使用 @ 來標示某人..."}}].forEach((e=>{const t={};for(const a in e.translations)e.translations[a].pluralId?t[a]={msgid:a,msgid_plural:e.translations[a].pluralId,msgstr:e.translations[a].msgstr}:t[a]={msgid:a,msgstr:[e.translations[a]]};n.addTranslation(e.locale,{translations:{"":t}})}));const o=n.build(),r=o.ngettext.bind(o),s=o.gettext.bind(o)},3648:(e,t,a)=>{"use strict";a.d(t,{Z:()=>n});var i=a(932);const n={methods:{n:i.n,t:i.t}}},1205:(e,t,a)=>{"use strict";a.d(t,{Z:()=>i});const i=e=>Math.random().toString(36).replace(/[^a-z]+/g,"").slice(0,e||5)},1390:(e,t,a)=>{"use strict";a.d(t,{Z:()=>o});const i=__webpack_require__(/*! linkify-string */ "./node_modules/linkify-string/dist/linkify-string.es.js");var n=a.n(i);const o=e=>n()(e,{defaultProtocol:"https",target:"_blank",className:"external linkified",attributes:{rel:"nofollow noopener noreferrer"}})},1206:(e,t,a)=>{"use strict";a.d(t,{L:()=>i});a(4505);const i=function(){return Object.assign(window,{_nc_focus_trap:window._nc_focus_trap||[]}),window._nc_focus_trap}},8384:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.v-popper--theme-tooltip.v-popper__popper{position:absolute;z-index:100000;top:0;right:auto;left:auto;display:block;margin:0;padding:0;text-align:left;text-align:start;opacity:0;line-height:1.6;line-break:auto;filter:drop-shadow(0 1px 10px var(--color-box-shadow))}.v-popper--theme-tooltip.v-popper__popper[data-popper-placement^=top] .v-popper__arrow-container{bottom:-10px;border-bottom-width:0;border-top-color:var(--color-main-background)}.v-popper--theme-tooltip.v-popper__popper[data-popper-placement^=bottom] .v-popper__arrow-container{top:-10px;border-top-width:0;border-bottom-color:var(--color-main-background)}.v-popper--theme-tooltip.v-popper__popper[data-popper-placement^=right] .v-popper__arrow-container{right:100%;border-left-width:0;border-right-color:var(--color-main-background)}.v-popper--theme-tooltip.v-popper__popper[data-popper-placement^=left] .v-popper__arrow-container{left:100%;border-right-width:0;border-left-color:var(--color-main-background)}.v-popper--theme-tooltip.v-popper__popper[aria-hidden=true]{visibility:hidden;transition:opacity .15s,visibility .15s;opacity:0}.v-popper--theme-tooltip.v-popper__popper[aria-hidden=false]{visibility:visible;transition:opacity .15s;opacity:1}.v-popper--theme-tooltip .v-popper__inner{max-width:350px;padding:5px 8px;text-align:center;color:var(--color-main-text);border-radius:var(--border-radius);background-color:var(--color-main-background)}.v-popper--theme-tooltip .v-popper__arrow-container{position:absolute;z-index:1;width:0;height:0;margin:0;border-style:solid;border-color:rgba(0,0,0,0);border-width:10px}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/directives/Tooltip/index.scss"],names:[],mappings:"AAGA,sBACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCSA,0CACC,iBAAA,CACA,cAAA,CACA,KAAA,CACA,UAAA,CACA,SAAA,CACA,aAAA,CACA,QAAA,CACA,SAAA,CACA,eAAA,CACA,gBAAA,CACA,SAAA,CACA,eAAA,CAEA,eAAA,CACA,sDAAA,CAGA,iGACC,YAAA,CACA,qBAAA,CACA,6CAAA,CAID,oGACC,SAAA,CACA,kBAAA,CACA,gDAAA,CAID,mGACC,UAAA,CACA,mBAAA,CACA,+CAAA,CAID,kGACC,SAAA,CACA,oBAAA,CACA,8CAAA,CAID,4DACC,iBAAA,CACA,uCAAA,CACA,SAAA,CAED,6DACC,kBAAA,CACA,uBAAA,CACA,SAAA,CAKF,0CACC,eAAA,CACA,eAAA,CACA,iBAAA,CACA,4BAAA,CACA,kCAAA,CACA,6CAAA,CAID,oDACC,iBAAA,CACA,SAAA,CACA,OAAA,CACA,QAAA,CACA,QAAA,CACA,kBAAA,CACA,0BAAA,CACA,iBAhFY",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n/**\n* @copyright Copyright (c) 2016, John Molakvoæ <skjnldsv@protonmail.com>\n* @copyright Copyright (c) 2016, Robin Appelman <robin@icewind.nl>\n* @copyright Copyright (c) 2016, Jan-Christoph Borchardt <hey@jancborchardt.net>\n* @copyright Copyright (c) 2016, Erik Pellikka <erik@pellikka.org>\n* @copyright Copyright (c) 2015, Vincent Petry <pvince81@owncloud.com>\n*\n* Bootstrap (http://getbootstrap.com)\n* SCSS copied from version 3.3.5\n* Copyright 2011-2015 Twitter, Inc.\n* Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)\n*/\n\n$arrow-width: 10px;\n\n.v-popper--theme-tooltip {\n\t&.v-popper__popper {\n\t\tposition: absolute;\n\t\tz-index: 100000;\n\t\ttop: 0;\n\t\tright: auto;\n\t\tleft: auto;\n\t\tdisplay: block;\n\t\tmargin: 0;\n\t\tpadding: 0;\n\t\ttext-align: left;\n\t\ttext-align: start;\n\t\topacity: 0;\n\t\tline-height: 1.6;\n\n\t\tline-break: auto;\n\t\tfilter: drop-shadow(0 1px 10px var(--color-box-shadow));\n\n\t\t// TOP\n\t\t&[data-popper-placement^='top'] .v-popper__arrow-container {\n\t\t\tbottom: -$arrow-width;\n\t\t\tborder-bottom-width: 0;\n\t\t\tborder-top-color: var(--color-main-background);\n\t\t}\n\n\t\t// BOTTOM\n\t\t&[data-popper-placement^='bottom'] .v-popper__arrow-container {\n\t\t\ttop: -$arrow-width;\n\t\t\tborder-top-width: 0;\n\t\t\tborder-bottom-color: var(--color-main-background);\n\t\t}\n\n\t\t// RIGHT\n\t\t&[data-popper-placement^='right'] .v-popper__arrow-container {\n\t\t\tright: 100%;\n\t\t\tborder-left-width: 0;\n\t\t\tborder-right-color: var(--color-main-background);\n\t\t}\n\n\t\t// LEFT\n\t\t&[data-popper-placement^='left'] .v-popper__arrow-container {\n\t\t\tleft: 100%;\n\t\t\tborder-right-width: 0;\n\t\t\tborder-left-color: var(--color-main-background);\n\t\t}\n\n\t\t// HIDDEN / SHOWN\n\t\t&[aria-hidden='true'] {\n\t\t\tvisibility: hidden;\n\t\t\ttransition: opacity .15s, visibility .15s;\n\t\t\topacity: 0;\n\t\t}\n\t\t&[aria-hidden='false'] {\n\t\t\tvisibility: visible;\n\t\t\ttransition: opacity .15s;\n\t\t\topacity: 1;\n\t\t}\n\t}\n\n\t// CONTENT\n\t.v-popper__inner {\n\t\tmax-width: 350px;\n\t\tpadding: 5px 8px;\n\t\ttext-align: center;\n\t\tcolor: var(--color-main-text);\n\t\tborder-radius: var(--border-radius);\n\t\tbackground-color: var(--color-main-background);\n\t}\n\n\t// ARROW\n\t.v-popper__arrow-container {\n\t\tposition: absolute;\n\t\tz-index: 1;\n\t\twidth: 0;\n\t\theight: 0;\n\t\tmargin: 0;\n\t\tborder-style: solid;\n\t\tborder-color: transparent;\n\t\tborder-width: $arrow-width;\n\t}\n}\n"],sourceRoot:""}]);const s=r},4825:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon[data-v-29452b76]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.action-items[data-v-29452b76]{display:flex;align-items:center}.action-items>button[data-v-29452b76]{margin-right:7px}.action-item[data-v-29452b76]{--open-background-color: var(--color-background-hover, $action-background-hover);position:relative;display:inline-block}.action-item.action-item--primary[data-v-29452b76]{--open-background-color: var(--color-primary-element-hover)}.action-item.action-item--secondary[data-v-29452b76]{--open-background-color: var(--color-primary-element-light-hover)}.action-item.action-item--error[data-v-29452b76]{--open-background-color: var(--color-error-hover)}.action-item.action-item--warning[data-v-29452b76]{--open-background-color: var(--color-warning-hover)}.action-item.action-item--success[data-v-29452b76]{--open-background-color: var(--color-success-hover)}.action-item.action-item--tertiary-no-background[data-v-29452b76]{--open-background-color: transparent}.action-item.action-item--open .action-item__menutoggle[data-v-29452b76]{background-color:var(--open-background-color)}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcActions/NcActions.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCLD,+BACC,YAAA,CACA,kBAAA,CAGA,sCACC,gBAAA,CAIF,8BACC,gFAAA,CACA,iBAAA,CACA,oBAAA,CAEA,mDACC,2DAAA,CAGD,qDACC,iEAAA,CAGD,iDACC,iDAAA,CAGD,mDACC,mDAAA,CAGD,mDACC,mDAAA,CAGD,kEACC,oCAAA,CAGD,yEACC,6CAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n// Inline buttons\n.action-items {\n\tdisplay: flex;\n\talign-items: center;\n\n\t// Spacing between buttons\n\t& > button {\n\t\tmargin-right: math.div($icon-margin, 2);\n\t}\n}\n\n.action-item {\n\t--open-background-color: var(--color-background-hover, $action-background-hover);\n\tposition: relative;\n\tdisplay: inline-block;\n\n\t&.action-item--primary {\n\t\t--open-background-color: var(--color-primary-element-hover);\n\t}\n\n\t&.action-item--secondary {\n\t\t--open-background-color: var(--color-primary-element-light-hover);\n\t}\n\n\t&.action-item--error {\n\t\t--open-background-color: var(--color-error-hover);\n\t}\n\n\t&.action-item--warning {\n\t\t--open-background-color: var(--color-warning-hover);\n\t}\n\n\t&.action-item--success {\n\t\t--open-background-color: var(--color-success-hover);\n\t}\n\n\t&.action-item--tertiary-no-background {\n\t\t--open-background-color: transparent;\n\t}\n\n\t&.action-item--open .action-item__menutoggle {\n\t\tbackground-color: var(--open-background-color);\n\t}\n}\n"],sourceRoot:""}]);const s=r},4946:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.v-popper--theme-dropdown.v-popper__popper.action-item__popper .v-popper__wrapper{border-radius:var(--border-radius-large);overflow:hidden}.v-popper--theme-dropdown.v-popper__popper.action-item__popper .v-popper__wrapper .v-popper__inner{border-radius:var(--border-radius-large);padding:4px;max-height:calc(50vh - 16px);overflow:auto}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcActions/NcActions.vue"],names:[],mappings:"AAGA,sBACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCJD,kFACC,wCAAA,CACA,eAAA,CAEA,mGACC,wCAAA,CACA,WAAA,CACA,4BAAA,CACA,aAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n// We overwrote the popover base class, so we can style\n// the popover__inner for actions only.\n.v-popper--theme-dropdown.v-popper__popper.action-item__popper .v-popper__wrapper {\n\tborder-radius: var(--border-radius-large);\n\toverflow:hidden;\n\n\t.v-popper__inner {\n\t\tborder-radius: var(--border-radius-large);\n\t\tpadding: 4px;\n\t\tmax-height: calc(50vh - 16px);\n\t\toverflow: auto;\n\t}\n}\n"],sourceRoot:""}]);const s=r},6184:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon[data-v-fc71d00e]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.app-sidebar[data-v-fc71d00e]{z-index:1500;top:0;right:0;display:flex;overflow-x:hidden;overflow-y:auto;flex-direction:column;flex-shrink:0;width:27vw;min-width:300px;max-width:500px;height:100%;border-left:1px solid var(--color-border);background:var(--color-main-background)}.app-sidebar .app-sidebar-header>.app-sidebar__close[data-v-fc71d00e]{position:absolute;z-index:100;top:6px;right:6px;width:44px;height:44px;opacity:.7;border-radius:22px}.app-sidebar .app-sidebar-header>.app-sidebar__close[data-v-fc71d00e]:hover,.app-sidebar .app-sidebar-header>.app-sidebar__close[data-v-fc71d00e]:active,.app-sidebar .app-sidebar-header>.app-sidebar__close[data-v-fc71d00e]:focus{opacity:1;background-color:rgba(127,127,127,.25)}.app-sidebar .app-sidebar-header--compact.app-sidebar-header--with-figure .app-sidebar-header__info[data-v-fc71d00e]{flex-direction:row}.app-sidebar .app-sidebar-header--compact.app-sidebar-header--with-figure .app-sidebar-header__info .app-sidebar-header__figure[data-v-fc71d00e]{z-index:2;width:70px;height:70px;margin:9px;border-radius:3px;flex:0 0 auto}.app-sidebar .app-sidebar-header--compact.app-sidebar-header--with-figure .app-sidebar-header__info .app-sidebar-header__desc[data-v-fc71d00e]{padding-left:0;flex:1 1 auto;min-width:0;padding-right:94px;padding-top:10px}.app-sidebar .app-sidebar-header--compact.app-sidebar-header--with-figure .app-sidebar-header__info .app-sidebar-header__desc.app-sidebar-header__desc--without-actions[data-v-fc71d00e]{padding-right:50px}.app-sidebar .app-sidebar-header--compact.app-sidebar-header--with-figure .app-sidebar-header__info .app-sidebar-header__desc .app-sidebar-header__tertiary-actions[data-v-fc71d00e]{z-index:3;position:absolute;top:9px;left:-44px;gap:0}.app-sidebar .app-sidebar-header--compact.app-sidebar-header--with-figure .app-sidebar-header__info .app-sidebar-header__desc .app-sidebar-header__menu[data-v-fc71d00e]{top:6px;right:50px;background-color:rgba(0,0,0,0);position:absolute}.app-sidebar .app-sidebar-header:not(.app-sidebar-header--with-figure) .app-sidebar-header__menu[data-v-fc71d00e]{position:absolute;top:6px;right:50px}.app-sidebar .app-sidebar-header:not(.app-sidebar-header--with-figure) .app-sidebar-header__desc[data-v-fc71d00e]{padding-right:94px}.app-sidebar .app-sidebar-header:not(.app-sidebar-header--with-figure) .app-sidebar-header__desc.app-sidebar-header__desc--without-actions[data-v-fc71d00e]{padding-right:50px}.app-sidebar .app-sidebar-header .app-sidebar-header__info[data-v-fc71d00e]{display:flex;flex-direction:column}.app-sidebar .app-sidebar-header__figure[data-v-fc71d00e]{width:100%;height:250px;max-height:250px;background-repeat:no-repeat;background-position:center;background-size:contain}.app-sidebar .app-sidebar-header__figure--with-action[data-v-fc71d00e]{cursor:pointer}.app-sidebar .app-sidebar-header__desc[data-v-fc71d00e]{position:relative;display:flex;flex-direction:row;justify-content:center;align-items:center;padding:18px 6px 18px 9px;gap:0 4px}.app-sidebar .app-sidebar-header__desc--with-tertiary-action[data-v-fc71d00e]{padding-left:6px}.app-sidebar .app-sidebar-header__desc--editable .app-sidebar-header__mainname-form[data-v-fc71d00e],.app-sidebar .app-sidebar-header__desc--with-subname--editable .app-sidebar-header__mainname-form[data-v-fc71d00e]{margin-top:-2px;margin-bottom:-2px}.app-sidebar .app-sidebar-header__desc--with-subname--editable .app-sidebar-header__subname[data-v-fc71d00e]{margin-top:-2px}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__tertiary-actions[data-v-fc71d00e]{display:flex;height:44px;width:44px;justify-content:center;flex:0 0 auto}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__tertiary-actions .app-sidebar-header__star[data-v-fc71d00e]{box-shadow:none}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__tertiary-actions .app-sidebar-header__star[data-v-fc71d00e]:hover{box-shadow:none;background-color:var(--color-background-hover)}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container[data-v-fc71d00e]{flex:1 1 auto;display:flex;flex-direction:column;justify-content:center;min-width:0}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__mainname-container[data-v-fc71d00e]{display:flex;align-items:center;min-height:44px}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__mainname-container .app-sidebar-header__mainname[data-v-fc71d00e]{padding:0;min-height:30px;font-size:20px;line-height:30px}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__mainname-container .app-sidebar-header__mainname[data-v-fc71d00e] .linkified{cursor:pointer;text-decoration:underline;margin:0}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__mainname-container .app-sidebar-header__mainname-form[data-v-fc71d00e]{display:flex;flex:1 1 auto;align-items:center}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__mainname-container .app-sidebar-header__mainname-form input.app-sidebar-header__mainname-input[data-v-fc71d00e]{flex:1 1 auto;margin:0;padding:7px;font-size:20px;font-weight:bold}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__mainname-container .app-sidebar-header__menu[data-v-fc71d00e]{height:44px;width:44px;border-radius:22px;background-color:rgba(127,127,127,.25);margin-left:5px}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__mainname[data-v-fc71d00e],.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__subname[data-v-fc71d00e]{overflow:hidden;width:100%;margin:0;white-space:nowrap;text-overflow:ellipsis}.app-sidebar .app-sidebar-header__desc .app-sidebar-header__name-container .app-sidebar-header__subname[data-v-fc71d00e]{padding:0;opacity:.7;font-size:var(--default-font-size)}.app-sidebar .app-sidebar-header__description[data-v-fc71d00e]{display:flex;align-items:center;margin:0 10px}@media only screen and (max-width: 768px){.app-sidebar[data-v-fc71d00e]{width:100vw;max-width:100vw}}.slide-right-leave-active[data-v-fc71d00e],.slide-right-enter-active[data-v-fc71d00e]{transition-duration:var(--animation-quick);transition-property:max-width,min-width}.slide-right-enter-to[data-v-fc71d00e],.slide-right-leave[data-v-fc71d00e]{min-width:300px;max-width:500px}.slide-right-enter[data-v-fc71d00e],.slide-right-leave-to[data-v-fc71d00e]{min-width:0 !important;max-width:0 !important}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcAppSidebar/NcAppSidebar.vue","webpack://./src/assets/variables.scss"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCYD,8BACC,YAAA,CACA,KAAA,CACA,OAAA,CACA,YAAA,CACA,iBAAA,CACA,eAAA,CACA,qBAAA,CACA,aAAA,CACA,UAAA,CACA,eA5BmB,CA6BnB,eA5BmB,CA6BnB,WAAA,CACA,yCAAA,CACA,uCAAA,CAGC,sEACC,iBAAA,CACA,WAAA,CACA,OA1BmB,CA2BnB,SA3BmB,CA4BnB,UCjBc,CDkBd,WClBc,CDmBd,UCDc,CDEd,kBAAA,CACA,qOAGC,SCLW,CDMX,sCCFsB,CDQvB,qHACC,kBAAA,CAEA,iJACC,SAAA,CACA,UAAA,CACA,WAAA,CACA,UAAA,CACA,iBAAA,CACA,aAAA,CAED,+IACC,cAAA,CACA,aAAA,CACA,WAAA,CACA,kBAAA,CACA,gBAlE2B,CAoE3B,yLACC,kBAAA,CAGD,qLACC,SAAA,CACA,iBAAA,CACA,OAAA,CACA,UAAA,CACA,KAAA,CAED,yKACC,OAxEgB,CAyEhB,UAAA,CACA,8BAAA,CACA,iBAAA,CASH,kHACC,iBAAA,CACA,OAtFkB,CAuFlB,UAAA,CAGD,kHACC,kBAAA,CAEA,4JACC,kBAAA,CAMH,4EACC,YAAA,CACA,qBAAA,CAID,0DACC,UAAA,CACA,YAAA,CACA,gBAAA,CACA,2BAAA,CACA,0BAAA,CACA,uBAAA,CACA,uEACC,cAAA,CAKF,wDACC,iBAAA,CACA,YAAA,CACA,kBAAA,CACA,sBAAA,CACA,kBAAA,CACA,yBAAA,CACA,SAAA,CAGA,8EACC,gBAAA,CAGD,wNAEC,eAAA,CACA,kBAAA,CAGD,6GACC,eAAA,CAGD,8FACC,YAAA,CACA,WCtIa,CDuIb,UCvIa,CDwIb,sBAAA,CACA,aAAA,CAEA,wHAEC,eAAA,CACA,8HACC,eAAA,CACA,8CAAA,CAMH,4FACC,aAAA,CACA,YAAA,CACA,qBAAA,CACA,sBAAA,CACA,WAAA,CAEA,oIACC,YAAA,CACA,kBAAA,CACA,eChKY,CDmKZ,kKACC,SAAA,CACA,eAAA,CACA,cAAA,CACA,gBAtLa,CAyLb,6KACC,cAAA,CACA,yBAAA,CACA,QAAA,CAIF,uKACC,YAAA,CACA,aAAA,CACA,kBAAA,CAEA,gNACC,aAAA,CACA,QAAA,CACA,WA3Mc,CA4Md,cAAA,CACA,gBAAA,CAKF,8JACC,WCjMW,CDkMX,UClMW,CDmMX,kBAAA,CACA,sCC7KoB,CD8KpB,eAAA,CAKF,mPAEC,eAAA,CACA,UAAA,CACA,QAAA,CACA,kBAAA,CACA,sBAAA,CAID,yHACC,SAAA,CACA,UCpMY,CDqMZ,kCAAA,CAMH,+DACC,YAAA,CACA,kBAAA,CACA,aAAA,CAMH,0CACC,8BACC,WAAA,CACA,eAAA,CAAA,CAIF,sFAEC,0CAAA,CACA,uCAAA,CAGD,2EAEC,eA5QmB,CA6QnB,eA5QmB,CA+QpB,2EAEC,sBAAA,CACA,sBAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n$sidebar-min-width: 300px;\n$sidebar-max-width: 500px;\n\n$desc-vertical-padding: 18px;\n$desc-vertical-padding-compact: 10px;\n$desc-input-padding: 7px;\n\n// name and subname\n$desc-name-height: 30px;\n$desc-subname-height: 22px;\n$desc-height: $desc-name-height + $desc-subname-height;\n\n$top-buttons-spacing: 6px;\n\n/*\n\tSidebar: to be used within #content\n\tapp-content will be shrinked properly\n*/\n.app-sidebar {\n\tz-index: 1500;\n\ttop: 0;\n\tright: 0;\n\tdisplay: flex;\n\toverflow-x: hidden;\n\toverflow-y: auto;\n\tflex-direction: column;\n\tflex-shrink: 0;\n\twidth: 27vw;\n\tmin-width: $sidebar-min-width;\n\tmax-width: $sidebar-max-width;\n\theight: 100%;\n\tborder-left: 1px solid var(--color-border);\n\tbackground: var(--color-main-background);\n\n\t.app-sidebar-header {\n\t\t> .app-sidebar__close {\n\t\t\tposition: absolute;\n\t\t\tz-index: 100;\n\t\t\ttop: $top-buttons-spacing;\n\t\t\tright: $top-buttons-spacing;\n\t\t\twidth: $clickable-area;\n\t\t\theight: $clickable-area;\n\t\t\topacity: $opacity_normal;\n\t\t\tborder-radius: math.div($clickable-area, 2);\n\t\t\t&:hover,\n\t\t\t&:active,\n\t\t\t&:focus {\n\t\t\t\topacity: $opacity_full;\n\t\t\t\tbackground-color: $action-background-hover;\n\t\t\t}\n\t\t}\n\n\t\t// Compact mode only affects a sidebar with a figure\n\t\t&--compact.app-sidebar-header--with-figure {\n\t\t\t.app-sidebar-header__info {\n\t\t\t\tflex-direction: row;\n\n\t\t\t\t.app-sidebar-header__figure {\n\t\t\t\t\tz-index: 2;\n\t\t\t\t\twidth: $desc-height + $desc-vertical-padding;\n\t\t\t\t\theight: $desc-height + $desc-vertical-padding;\n\t\t\t\t\tmargin: math.div($desc-vertical-padding, 2);\n\t\t\t\t\tborder-radius: 3px;\n\t\t\t\t\tflex: 0 0 auto;\n\t\t\t\t}\n\t\t\t\t.app-sidebar-header__desc {\n\t\t\t\t\tpadding-left: 0;\n\t\t\t\t\tflex: 1 1 auto;\n\t\t\t\t\tmin-width: 0;\n\t\t\t\t\tpadding-right: 2 * $clickable-area + $top-buttons-spacing;\n\t\t\t\t\tpadding-top: $desc-vertical-padding-compact;\n\n\t\t\t\t\t&.app-sidebar-header__desc--without-actions {\n\t\t\t\t\t\tpadding-right: #{$clickable-area + $top-buttons-spacing};\n\t\t\t\t\t}\n\n\t\t\t\t\t.app-sidebar-header__tertiary-actions {\n\t\t\t\t\t\tz-index: 3; // above star\n\t\t\t\t\t\tposition: absolute;\n\t\t\t\t\t\ttop: math.div($desc-vertical-padding, 2);\n\t\t\t\t\t\tleft: -1 * $clickable-area;\n\t\t\t\t\t\tgap: 0; // override gap\n\t\t\t\t\t}\n\t\t\t\t\t.app-sidebar-header__menu {\n\t\t\t\t\t\ttop: $top-buttons-spacing;\n\t\t\t\t\t\tright: $clickable-area + $top-buttons-spacing; // left of the close button\n\t\t\t\t\t\tbackground-color: transparent;\n\t\t\t\t\t\tposition: absolute;\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\n\t\t// sidebar without figure\n\t\t&:not(.app-sidebar-header--with-figure) {\n\t\t\t// align the menu with the close button\n\t\t\t.app-sidebar-header__menu {\n\t\t\t\tposition: absolute;\n\t\t\t\ttop: $top-buttons-spacing;\n\t\t\t\tright: $top-buttons-spacing + $clickable-area;\n\t\t\t}\n\t\t\t// increase the padding to not overlap the menu\n\t\t\t.app-sidebar-header__desc {\n\t\t\t\tpadding-right: #{$clickable-area * 2 + $top-buttons-spacing};\n\n\t\t\t\t&.app-sidebar-header__desc--without-actions {\n\t\t\t\t\tpadding-right: #{$clickable-area + $top-buttons-spacing};\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\n\t\t// the container with the figure and the description\n\t\t.app-sidebar-header__info {\n\t\t\tdisplay: flex;\n\t\t\tflex-direction: column;\n\t\t}\n\n\t\t// header background\n\t\t&__figure {\n\t\t\twidth: 100%;\n\t\t\theight: 250px;\n\t\t\tmax-height: 250px;\n\t\t\tbackground-repeat: no-repeat;\n\t\t\tbackground-position: center;\n\t\t\tbackground-size: contain;\n\t\t\t&--with-action {\n\t\t\t\tcursor: pointer;\n\t\t\t}\n\t\t}\n\n\t\t// description\n\t\t&__desc {\n\t\t\tposition: relative;\n\t\t\tdisplay: flex;\n\t\t\tflex-direction: row;\n\t\t\tjustify-content: center;\n\t\t\talign-items: center;\n\t\t\tpadding: #{$desc-vertical-padding} #{$top-buttons-spacing} #{$desc-vertical-padding} #{math.div($desc-vertical-padding, 2)};\n\t\t\tgap: 0 4px;\n\n\t\t\t// custom overrides\n\t\t\t&--with-tertiary-action {\n\t\t\t\tpadding-left: 6px;\n\t\t\t}\n\n\t\t\t&--editable .app-sidebar-header__mainname-form,\n\t\t\t&--with-subname--editable .app-sidebar-header__mainname-form {\n\t\t\t\tmargin-top: -2px;\n\t\t\t\tmargin-bottom: -2px;\n\t\t\t}\n\n\t\t\t&--with-subname--editable .app-sidebar-header__subname {\n\t\t\t\tmargin-top: -2px;\n\t\t\t}\n\n\t\t\t.app-sidebar-header__tertiary-actions {\n\t\t\t\tdisplay: flex;\n\t\t\t\theight: $clickable-area;\n\t\t\t\twidth: $clickable-area;\n\t\t\t\tjustify-content: center;\n\t\t\t\tflex: 0 0 auto;\n\n\t\t\t\t.app-sidebar-header__star {\n\t\t\t\t\t// Override default Button component styles\n\t\t\t\t\tbox-shadow: none;\n\t\t\t\t\t&:hover {\n\t\t\t\t\t\tbox-shadow: none;\n\t\t\t\t\t\tbackground-color: var(--color-background-hover);\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n\n\t\t\t// names\n\t\t\t.app-sidebar-header__name-container {\n\t\t\t\tflex: 1 1 auto;\n\t\t\t\tdisplay: flex;\n\t\t\t\tflex-direction: column;\n\t\t\t\tjustify-content: center;\n\t\t\t\tmin-width: 0;\n\n\t\t\t\t.app-sidebar-header__mainname-container {\n\t\t\t\t\tdisplay: flex;\n\t\t\t\t\talign-items: center;\n\t\t\t\t\tmin-height: $clickable-area;\n\n\t\t\t\t\t// main name\n\t\t\t\t\t.app-sidebar-header__mainname {\n\t\t\t\t\t\tpadding: 0;\n\t\t\t\t\t\tmin-height: 30px;\n\t\t\t\t\t\tfont-size: 20px;\n\t\t\t\t\t\tline-height: $desc-name-height;\n\n\t\t\t\t\t\t// Needs 'deep' as the link is generated by the linkify directive\n\t\t\t\t\t\t&:deep(.linkified) {\n\t\t\t\t\t\t\tcursor: pointer;\n\t\t\t\t\t\t\ttext-decoration: underline;\n\t\t\t\t\t\t\tmargin: 0;\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\n\t\t\t\t\t.app-sidebar-header__mainname-form {\n\t\t\t\t\t\tdisplay: flex;\n\t\t\t\t\t\tflex: 1 1 auto;\n\t\t\t\t\t\talign-items: center;\n\n\t\t\t\t\t\tinput.app-sidebar-header__mainname-input {\n\t\t\t\t\t\t\tflex: 1 1 auto;\n\t\t\t\t\t\t\tmargin: 0;\n\t\t\t\t\t\t\tpadding: $desc-input-padding;\n\t\t\t\t\t\t\tfont-size: 20px;\n\t\t\t\t\t\t\tfont-weight: bold;\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\n\t\t\t\t\t// main menu\n\t\t\t\t\t.app-sidebar-header__menu {\n\t\t\t\t\t\theight: $clickable-area;\n\t\t\t\t\t\twidth: $clickable-area;\n\t\t\t\t\t\tborder-radius: math.div($clickable-area, 2);\n\t\t\t\t\t\tbackground-color: $action-background-hover;\n\t\t\t\t\t\tmargin-left: 5px;\n\t\t\t\t\t}\n\t\t\t\t}\n\n\t\t\t\t// shared between main and subname\n\t\t\t\t.app-sidebar-header__mainname,\n\t\t\t\t.app-sidebar-header__subname {\n\t\t\t\t\toverflow: hidden;\n\t\t\t\t\twidth: 100%;\n\t\t\t\t\tmargin: 0;\n\t\t\t\t\twhite-space: nowrap;\n\t\t\t\t\ttext-overflow: ellipsis;\n\t\t\t\t}\n\n\t\t\t\t// subname\n\t\t\t\t.app-sidebar-header__subname {\n\t\t\t\t\tpadding: 0;\n\t\t\t\t\topacity: $opacity_normal;\n\t\t\t\t\tfont-size: var(--default-font-size);\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\n\t\t// sidebar description slot\n\t\t&__description {\n\t\t\tdisplay: flex;\n\t\t\talign-items: center;\n\t\t\tmargin: 0 10px;\n\t\t}\n\t}\n}\n\n// Make the sidebar full-width on small screens\n@media only screen and (max-width: 768px) {\n\t.app-sidebar {\n\t\twidth: 100vw;\n\t\tmax-width: 100vw;\n\t}\n}\n\n.slide-right-leave-active,\n.slide-right-enter-active {\n\ttransition-duration: var(--animation-quick);\n\ttransition-property: max-width, min-width;\n}\n\n.slide-right-enter-to,\n.slide-right-leave {\n\tmin-width: $sidebar-min-width;\n\tmax-width: $sidebar-max-width;\n}\n\n.slide-right-enter,\n.slide-right-leave-to {\n\tmin-width: 0 !important;\n\tmax-width: 0 !important;\n}\n","/**\n * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @author John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n\n// https://uxplanet.org/7-rules-for-mobile-ui-button-design-e9cf2ea54556\n// recommended is 48px\n// 44px is what we choose and have very good visual-to-usability ratio\n$clickable-area: 44px;\n\n// background icon size\n// also used for the scss icon font\n$icon-size: 16px;\n\n// icon padding for a $clickable-area width and a $icon-size icon\n// ( 44px - 16px ) / 2\n$icon-margin: math.div($clickable-area - $icon-size, 2);\n\n// transparency background for icons\n$icon-focus-bg: rgba(127, 127, 127, .25);\n\n// popovermenu arrow width from the triangle center\n$arrow-width: 9px;\n\n// opacities\n$opacity_disabled: .5;\n$opacity_normal: .7;\n$opacity_full: 1;\n\n// menu round background hover feedback\n// good looking on dark AND white bg\n$action-background-hover: rgba(127, 127, 127, .25);\n\n// various structure data used in the \n// `AppNavigation` component\n$header-height: 50px;\n$navigation-width: 300px;\n\n// mobile breakpoint\n$breakpoint-mobile: 1024px;\n\n// top-bar spacing\n$topbar-margin: 4px;\n\n// navigation spacing\n$app-navigation-settings-margin: 3px;\n"],sourceRoot:""}]);const s=r},2030:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.app-sidebar-header__description button,.app-sidebar-header__description .button,.app-sidebar-header__description input[type=button],.app-sidebar-header__description input[type=submit],.app-sidebar-header__description input[type=reset]{padding:6px 22px}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcAppSidebar/NcAppSidebar.vue"],names:[],mappings:"AAGA,sBACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCHA,4OAIC,gBAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n// ! slots specific designs, cannot be scoped\n// if any button inside the description slot, increase visual padding\n.app-sidebar-header__description {\n\tbutton, .button,\n\tinput[type='button'],\n\tinput[type='submit'],\n\tinput[type='reset'] {\n\t\tpadding: 6px 22px;\n\t}\n}\n\n"],sourceRoot:""}]);const s=r},2789:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon[data-v-d6d35ae8]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.app-sidebar-tabs[data-v-d6d35ae8]{display:flex;flex-direction:column;min-height:0;flex:1 1 100%}.app-sidebar-tabs__nav[data-v-d6d35ae8]{display:flex;justify-content:stretch;margin-top:10px;padding:0 4px}.app-sidebar-tabs__tab[data-v-d6d35ae8]{flex:1 1}.app-sidebar-tabs__tab.active[data-v-d6d35ae8]{color:var(--color-primary-element)}.app-sidebar-tabs__tab-caption[data-v-d6d35ae8]{flex:0 1 100%;width:100%;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;text-align:center}.app-sidebar-tabs__tab-icon[data-v-d6d35ae8]{display:flex;align-items:center;justify-content:center;background-size:20px}.app-sidebar-tabs__tab[data-v-d6d35ae8] .checkbox-radio-switch__label{max-width:unset}.app-sidebar-tabs__content[data-v-d6d35ae8]{position:relative;min-height:0;height:100%}.app-sidebar-tabs__content--multiple[data-v-d6d35ae8]>:not(section){display:none}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcAppSidebar/NcAppSidebarTabs.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,mCACC,YAAA,CACA,qBAAA,CACA,YAAA,CACA,aAAA,CAEA,wCACC,YAAA,CACA,uBAAA,CACA,eAAA,CACA,aAAA,CAGD,wCACC,QAAA,CACA,+CACC,kCAAA,CAGD,gDACC,aAAA,CACA,UAAA,CACA,eAAA,CACA,kBAAA,CACA,sBAAA,CACA,iBAAA,CAGD,6CACC,YAAA,CACA,kBAAA,CACA,sBAAA,CACA,oBAAA,CAID,sEACC,eAAA,CAIF,4CACC,iBAAA,CAEA,YAAA,CACA,WAAA,CAGA,oEACC,YAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.app-sidebar-tabs {\n\tdisplay: flex;\n\tflex-direction: column;\n\tmin-height: 0;\n\tflex: 1 1 100%;\n\n\t&__nav {\n\t\tdisplay: flex;\n\t\tjustify-content: stretch;\n\t\tmargin-top: 10px;\n\t\tpadding: 0 4px;\n\t}\n\n\t&__tab {\n\t\tflex: 1 1;\n\t\t&.active {\n\t\t\tcolor: var(--color-primary-element);\n\t\t}\n\n\t\t&-caption {\n\t\t\tflex: 0 1 100%;\n\t\t\twidth: 100%;\n\t\t\toverflow: hidden;\n\t\t\twhite-space: nowrap;\n\t\t\ttext-overflow: ellipsis;\n\t\t\ttext-align: center;\n\t\t}\n\n\t\t&-icon {\n\t\t\tdisplay: flex;\n\t\t\talign-items: center;\n\t\t\tjustify-content: center;\n\t\t\tbackground-size: 20px;\n\t\t}\n\n\t\t// Override max-width to use all available space\n\t\t:deep(.checkbox-radio-switch__label) {\n\t\t\tmax-width: unset;\n\t\t}\n\t}\n\n\t&__content {\n\t\tposition: relative;\n\t\t// take full available height\n\t\tmin-height: 0;\n\t\theight: 100%;\n\t\t// force the use of the tab component if more than one tab\n\t\t// you can just put raw content if you don't use tabs\n\t\t&--multiple > :not(section) {\n\t\t\tdisplay: none;\n\t\t}\n\t}\n}\n"],sourceRoot:""}]);const s=r},7196:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon[data-v-4d05be2c]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.button-vue[data-v-4d05be2c]{position:relative;width:fit-content;overflow:hidden;border:0;padding:0;font-size:var(--default-font-size);font-weight:bold;min-height:44px;min-width:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:22px;transition-property:color,border-color,background-color;transition-duration:.1s;transition-timing-function:linear;color:var(--color-primary-element-light-text);background-color:var(--color-primary-element-light)}.button-vue *[data-v-4d05be2c],.button-vue span[data-v-4d05be2c]{cursor:pointer}.button-vue[data-v-4d05be2c]:focus{outline:none}.button-vue[data-v-4d05be2c]:disabled{cursor:default;opacity:.5;filter:saturate(0.7)}.button-vue:disabled *[data-v-4d05be2c]{cursor:default}.button-vue[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-primary-element-light-hover)}.button-vue[data-v-4d05be2c]:active{background-color:var(--color-primary-element-light)}.button-vue__wrapper[data-v-4d05be2c]{display:inline-flex;align-items:center;justify-content:center;width:100%}.button-vue__icon[data-v-4d05be2c]{height:44px;width:44px;min-height:44px;min-width:44px;display:flex;justify-content:center;align-items:center}.button-vue__text[data-v-4d05be2c]{font-weight:bold;margin-bottom:1px;padding:2px 0;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}.button-vue--icon-only[data-v-4d05be2c]{width:44px !important}.button-vue--text-only[data-v-4d05be2c]{padding:0 12px}.button-vue--text-only .button-vue__text[data-v-4d05be2c]{margin-left:4px;margin-right:4px}.button-vue--icon-and-text[data-v-4d05be2c]{padding:0 16px 0 4px}.button-vue--wide[data-v-4d05be2c]{width:100%}.button-vue[data-v-4d05be2c]:focus-visible{outline:2px solid var(--color-main-text) !important}.button-vue:focus-visible.button-vue--vue-tertiary-on-primary[data-v-4d05be2c]{outline:2px solid var(--color-primary-element-text);border-radius:var(--border-radius);background-color:rgba(0,0,0,0)}.button-vue--vue-primary[data-v-4d05be2c]{background-color:var(--color-primary-element);color:var(--color-primary-element-text)}.button-vue--vue-primary[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-primary-element-hover)}.button-vue--vue-primary[data-v-4d05be2c]:active{background-color:var(--color-primary-element)}.button-vue--vue-secondary[data-v-4d05be2c]{color:var(--color-primary-element-light-text);background-color:var(--color-primary-element-light)}.button-vue--vue-secondary[data-v-4d05be2c]:hover:not(:disabled){color:var(--color-primary-element-light-text);background-color:var(--color-primary-element-light-hover)}.button-vue--vue-tertiary[data-v-4d05be2c]{color:var(--color-main-text);background-color:rgba(0,0,0,0)}.button-vue--vue-tertiary[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color);background-color:var(--color-background-hover)}.button-vue--vue-tertiary-no-background[data-v-4d05be2c]{color:var(--color-main-text);background-color:rgba(0,0,0,0)}.button-vue--vue-tertiary-no-background[data-v-4d05be2c]:hover:not(:disabled){background-color:rgba(0,0,0,0)}.button-vue--vue-tertiary-on-primary[data-v-4d05be2c]{color:var(--color-primary-element-text);background-color:rgba(0,0,0,0)}.button-vue--vue-tertiary-on-primary[data-v-4d05be2c]:hover:not(:disabled){background-color:rgba(0,0,0,0)}.button-vue--vue-success[data-v-4d05be2c]{background-color:var(--color-success);color:#fff}.button-vue--vue-success[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-success-hover)}.button-vue--vue-success[data-v-4d05be2c]:active{background-color:var(--color-success)}.button-vue--vue-warning[data-v-4d05be2c]{background-color:var(--color-warning);color:#fff}.button-vue--vue-warning[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-warning-hover)}.button-vue--vue-warning[data-v-4d05be2c]:active{background-color:var(--color-warning)}.button-vue--vue-error[data-v-4d05be2c]{background-color:var(--color-error);color:#fff}.button-vue--vue-error[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-error-hover)}.button-vue--vue-error[data-v-4d05be2c]:active{background-color:var(--color-error)}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcButton/NcButton.vue","webpack://./src/assets/variables.scss"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCLD,6BACC,iBAAA,CACA,iBAAA,CACA,eAAA,CACA,QAAA,CACA,SAAA,CACA,kCAAA,CACA,gBAAA,CACA,eCcgB,CDbhB,cCagB,CDZhB,YAAA,CACA,kBAAA,CACA,sBAAA,CAGA,cAAA,CAKA,kBAAA,CACA,uDAAA,CACA,uBAAA,CACA,iCAAA,CAkBA,6CAAA,CACA,mDAAA,CA1BA,iEAEC,cAAA,CAQD,mCACC,YAAA,CAGD,sCACC,cAAA,CAIA,UCIiB,CDFjB,oBAAA,CALA,wCACC,cAAA,CAUF,kDACC,yDAAA,CAKD,oCACC,mDAAA,CAGD,sCACC,mBAAA,CACA,kBAAA,CACA,sBAAA,CACA,UAAA,CAGD,mCACC,WCvCe,CDwCf,UCxCe,CDyCf,eCzCe,CD0Cf,cC1Ce,CD2Cf,YAAA,CACA,sBAAA,CACA,kBAAA,CAGD,mCACC,gBAAA,CACA,iBAAA,CACA,aAAA,CACA,kBAAA,CACA,sBAAA,CACA,eAAA,CAID,wCACC,qBAAA,CAID,wCACC,cAAA,CACA,0DACC,eAAA,CACA,gBAAA,CAKF,4CACC,oBAAA,CAID,mCACC,UAAA,CAGD,2CACC,mDAAA,CACA,+EACC,mDAAA,CACA,kCAAA,CACA,8BAAA,CAOF,0CACC,6CAAA,CACA,uCAAA,CACA,+DACC,mDAAA,CAID,iDACC,6CAAA,CAKF,4CACC,6CAAA,CACA,mDAAA,CACA,iEACC,6CAAA,CACA,yDAAA,CAKF,2CACC,4BAAA,CACA,8BAAA,CACA,gEACC,6BAAA,CACA,8CAAA,CAKF,yDACC,4BAAA,CACA,8BAAA,CACA,8EACC,8BAAA,CAKF,sDACC,uCAAA,CACA,8BAAA,CAEA,2EACC,8BAAA,CAKF,0CACC,qCAAA,CACA,UAAA,CACA,+DACC,2CAAA,CAID,iDACC,qCAAA,CAKF,0CACC,qCAAA,CACA,UAAA,CACA,+DACC,2CAAA,CAID,iDACC,qCAAA,CAKF,wCACC,mCAAA,CACA,UAAA,CACA,6DACC,yCAAA,CAID,+CACC,mCAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n\n.button-vue {\n\tposition: relative;\n\twidth: fit-content;\n\toverflow: hidden;\n\tborder: 0;\n\tpadding: 0;\n\tfont-size: var(--default-font-size);\n\tfont-weight: bold;\n\tmin-height: $clickable-area;\n\tmin-width: $clickable-area;\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: center;\n\n\t// Cursor pointer on element and all children\n\tcursor: pointer;\n\t& *,\n\tspan {\n\t\tcursor: pointer;\n\t}\n\tborder-radius: math.div($clickable-area, 2);\n\ttransition-property: color, border-color, background-color;\n\ttransition-duration: 0.1s;\n\ttransition-timing-function: linear;\n\n\t// No outline feedback for focus. Handled with a toggled class in js (see data)\n\t&:focus {\n\t\toutline: none;\n\t}\n\n\t&:disabled {\n\t\tcursor: default;\n\t\t& * {\n\t\t\tcursor: default;\n\t\t}\n\t\topacity: $opacity_disabled;\n\t\t// Gives a wash out effect\n\t\tfilter: saturate($opacity_normal);\n\t}\n\n\t// Default button type\n\tcolor: var(--color-primary-element-light-text);\n\tbackground-color: var(--color-primary-element-light);\n\t&:hover:not(:disabled) {\n\t\tbackground-color: var(--color-primary-element-light-hover);\n\t}\n\n\t// Back to the default color for this button when active\n\t// TODO: add ripple effect\n\t&:active {\n\t\tbackground-color: var(--color-primary-element-light);\n\t}\n\n\t&__wrapper {\n\t\tdisplay: inline-flex;\n\t\talign-items: center;\n\t\tjustify-content: center;\n\t\twidth: 100%;\n\t}\n\n\t&__icon {\n\t\theight: $clickable-area;\n\t\twidth: $clickable-area;\n\t\tmin-height: $clickable-area;\n\t\tmin-width: $clickable-area;\n\t\tdisplay: flex;\n\t\tjustify-content: center;\n\t\talign-items: center;\n\t}\n\n\t&__text {\n\t\tfont-weight: bold;\n\t\tmargin-bottom: 1px;\n\t\tpadding: 2px 0;\n\t\twhite-space: nowrap;\n\t\ttext-overflow: ellipsis;\n\t\toverflow: hidden;\n\t}\n\n\t// Icon-only button\n\t&--icon-only {\n\t\twidth: $clickable-area !important;\n\t}\n\n\t// Text-only button\n\t&--text-only {\n\t\tpadding: 0 12px;\n\t\t& .button-vue__text {\n\t\t\tmargin-left: 4px;\n\t\t\tmargin-right: 4px;\n\t\t}\n\t}\n\n\t// Icon and text button\n\t&--icon-and-text {\n\t\tpadding: 0 16px 0 4px;\n\t}\n\n\t// Wide button spans the whole width of the container\n\t&--wide {\n\t\twidth: 100%;\n\t}\n\n\t&:focus-visible {\n\t\toutline: 2px solid var(--color-main-text) !important;\n\t\t&.button-vue--vue-tertiary-on-primary {\n\t\t\toutline: 2px solid var(--color-primary-element-text);\n\t\t\tborder-radius: var(--border-radius);\n\t\t\tbackground-color: transparent;\n\t\t}\n\t}\n\n\t// Button types\n\n\t// Primary\n\t&--vue-primary {\n\t\tbackground-color: var(--color-primary-element);\n\t\tcolor: var(--color-primary-element-text);\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color-primary-element-hover);\n\t\t}\n\t\t// Back to the default color for this button when active\n\t\t// TODO: add ripple effect\n\t\t&:active {\n\t\t\tbackground-color: var(--color-primary-element);\n\t\t}\n\t}\n\n\t// Secondary\n\t&--vue-secondary {\n\t\tcolor: var(--color-primary-element-light-text);\n\t\tbackground-color: var(--color-primary-element-light);\n\t\t&:hover:not(:disabled) {\n\t\t\tcolor: var(--color-primary-element-light-text);\n\t\t\tbackground-color: var(--color-primary-element-light-hover);\n\t\t}\n\t}\n\n\t// Tertiary\n\t&--vue-tertiary {\n\t\tcolor: var(--color-main-text);\n\t\tbackground-color: transparent;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color);\n\t\t\tbackground-color: var(--color-background-hover);\n\t\t}\n\t}\n\n\t// Tertiary, no background\n\t&--vue-tertiary-no-background {\n\t\tcolor: var(--color-main-text);\n\t\tbackground-color: transparent;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: transparent;\n\t\t}\n\t}\n\n\t// Tertiary on primary color (like the header)\n\t&--vue-tertiary-on-primary {\n\t\tcolor: var(--color-primary-element-text);\n\t\tbackground-color: transparent;\n\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: transparent;\n\t\t}\n\t}\n\n\t// Success\n\t&--vue-success {\n\t\tbackground-color: var(--color-success);\n\t\tcolor: white;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color-success-hover);\n\t\t}\n\t\t// Back to the default color for this button when active\n\t\t// : add ripple effect\n\t\t&:active {\n\t\t\tbackground-color: var(--color-success);\n\t\t}\n\t}\n\n\t// Warning\n\t&--vue-warning {\n\t\tbackground-color: var(--color-warning);\n\t\tcolor: white;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color-warning-hover);\n\t\t}\n\t\t// Back to the default color for this button when active\n\t\t// TODO: add ripple effect\n\t\t&:active {\n\t\t\tbackground-color: var(--color-warning);\n\t\t}\n\t}\n\n\t// Error\n\t&--vue-error {\n\t\tbackground-color: var(--color-error);\n\t\tcolor: white;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color-error-hover);\n\t\t}\n\t\t// Back to the default color for this button when active\n\t\t// TODO: add ripple effect\n\t\t&:active {\n\t\t\tbackground-color: var(--color-error);\n\t\t}\n\t}\n}\n\n","/**\n * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @author John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n\n// https://uxplanet.org/7-rules-for-mobile-ui-button-design-e9cf2ea54556\n// recommended is 48px\n// 44px is what we choose and have very good visual-to-usability ratio\n$clickable-area: 44px;\n\n// background icon size\n// also used for the scss icon font\n$icon-size: 16px;\n\n// icon padding for a $clickable-area width and a $icon-size icon\n// ( 44px - 16px ) / 2\n$icon-margin: math.div($clickable-area - $icon-size, 2);\n\n// transparency background for icons\n$icon-focus-bg: rgba(127, 127, 127, .25);\n\n// popovermenu arrow width from the triangle center\n$arrow-width: 9px;\n\n// opacities\n$opacity_disabled: .5;\n$opacity_normal: .7;\n$opacity_full: 1;\n\n// menu round background hover feedback\n// good looking on dark AND white bg\n$action-background-hover: rgba(127, 127, 127, .25);\n\n// various structure data used in the \n// `AppNavigation` component\n$header-height: 50px;\n$navigation-width: 300px;\n\n// mobile breakpoint\n$breakpoint-mobile: 1024px;\n\n// top-bar spacing\n$topbar-margin: 4px;\n\n// navigation spacing\n$app-navigation-settings-margin: 3px;\n"],sourceRoot:""}]);const s=r},7924:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon[data-v-dec41432]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.checkbox-radio-switch[data-v-dec41432]{display:flex}.checkbox-radio-switch__input[data-v-dec41432]{position:absolute;z-index:-1;opacity:0 !important;width:var(--icon-size);height:var(--icon-size)}.checkbox-radio-switch__input:focus-visible+label[data-v-dec41432]{outline:2px solid var(--color-primary-element) !important}.checkbox-radio-switch__label[data-v-dec41432]{display:flex;align-items:center;flex-direction:row;gap:4px;user-select:none;min-height:44px;border-radius:44px;padding:4px 14px;width:100%;max-width:fit-content}.checkbox-radio-switch__label[data-v-dec41432],.checkbox-radio-switch__label *[data-v-dec41432]{cursor:pointer}.checkbox-radio-switch__icon>*[data-v-dec41432]{color:var(--color-primary-element);width:var(--icon-size);height:var(--icon-size)}.checkbox-radio-switch--disabled .checkbox-radio-switch__label[data-v-dec41432]{opacity:.5}.checkbox-radio-switch--disabled .checkbox-radio-switch__label .checkbox-radio-switch__icon>*[data-v-dec41432]{color:var(--color-main-text)}.checkbox-radio-switch:not(.checkbox-radio-switch--disabled,.checkbox-radio-switch--checked):focus-within .checkbox-radio-switch__label[data-v-dec41432],.checkbox-radio-switch:not(.checkbox-radio-switch--disabled,.checkbox-radio-switch--checked) .checkbox-radio-switch__label[data-v-dec41432]:hover{background-color:var(--color-background-hover)}.checkbox-radio-switch--checked:not(.checkbox-radio-switch--disabled):focus-within .checkbox-radio-switch__label[data-v-dec41432],.checkbox-radio-switch--checked:not(.checkbox-radio-switch--disabled) .checkbox-radio-switch__label[data-v-dec41432]:hover{background-color:var(--color-primary-element-light-hover)}.checkbox-radio-switch-switch:not(.checkbox-radio-switch--checked) .checkbox-radio-switch__icon>*[data-v-dec41432]{color:var(--color-text-maxcontrast)}.checkbox-radio-switch-switch.checkbox-radio-switch--disabled.checkbox-radio-switch--checked .checkbox-radio-switch__icon>*[data-v-dec41432]{color:var(--color-primary-element-light)}.checkbox-radio-switch--button-variant.checkbox-radio-switch[data-v-dec41432]{border:2px solid var(--color-border-dark);overflow:hidden}.checkbox-radio-switch--button-variant.checkbox-radio-switch--checked[data-v-dec41432]{font-weight:bold}.checkbox-radio-switch--button-variant.checkbox-radio-switch--checked label[data-v-dec41432]{background-color:var(--color-primary-element-light)}.checkbox-radio-switch--button-variant .checkbox-radio-switch__label-text[data-v-dec41432]{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;width:100%}.checkbox-radio-switch--button-variant:not(.checkbox-radio-switch--checked) .checkbox-radio-switch__icon>*[data-v-dec41432]{color:var(--color-main-text)}.checkbox-radio-switch--button-variant .checkbox-radio-switch__icon[data-v-dec41432]:empty{display:none}.checkbox-radio-switch--button-variant[data-v-dec41432]:not(.checkbox-radio-switch--button-variant-v-grouped):not(.checkbox-radio-switch--button-variant-h-grouped),.checkbox-radio-switch--button-variant .checkbox-radio-switch__label[data-v-dec41432]{border-radius:calc(var(--default-clickable-area)/2)}.checkbox-radio-switch--button-variant-v-grouped .checkbox-radio-switch__label[data-v-dec41432]{flex-basis:100%;max-width:unset}.checkbox-radio-switch--button-variant-v-grouped[data-v-dec41432]:first-of-type{border-top-left-radius:calc(var(--default-clickable-area)/2 + 2px);border-top-right-radius:calc(var(--default-clickable-area)/2 + 2px)}.checkbox-radio-switch--button-variant-v-grouped[data-v-dec41432]:last-of-type{border-bottom-left-radius:calc(var(--default-clickable-area)/2 + 2px);border-bottom-right-radius:calc(var(--default-clickable-area)/2 + 2px)}.checkbox-radio-switch--button-variant-v-grouped[data-v-dec41432]:not(:last-of-type){border-bottom:0 !important}.checkbox-radio-switch--button-variant-v-grouped:not(:last-of-type) .checkbox-radio-switch__label[data-v-dec41432]{margin-bottom:2px}.checkbox-radio-switch--button-variant-v-grouped[data-v-dec41432]:not(:first-of-type){border-top:0 !important}.checkbox-radio-switch--button-variant-h-grouped[data-v-dec41432]:first-of-type{border-top-left-radius:calc(var(--default-clickable-area)/2 + 2px);border-bottom-left-radius:calc(var(--default-clickable-area)/2 + 2px)}.checkbox-radio-switch--button-variant-h-grouped[data-v-dec41432]:last-of-type{border-top-right-radius:calc(var(--default-clickable-area)/2 + 2px);border-bottom-right-radius:calc(var(--default-clickable-area)/2 + 2px)}.checkbox-radio-switch--button-variant-h-grouped[data-v-dec41432]:not(:last-of-type){border-right:0 !important}.checkbox-radio-switch--button-variant-h-grouped:not(:last-of-type) .checkbox-radio-switch__label[data-v-dec41432]{margin-right:2px}.checkbox-radio-switch--button-variant-h-grouped[data-v-dec41432]:not(:first-of-type){border-left:0 !important}.checkbox-radio-switch--button-variant-h-grouped .checkbox-radio-switch__label-text[data-v-dec41432]{text-align:center}.checkbox-radio-switch--button-variant-h-grouped .checkbox-radio-switch__label[data-v-dec41432]{flex-direction:column;justify-content:center;width:100%;margin:0;gap:0}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcCheckboxRadioSwitch/NcCheckboxRadioSwitch.vue","webpack://./src/assets/variables.scss"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,wCACC,YAAA,CAEA,+CACC,iBAAA,CACA,UAAA,CACA,oBAAA,CACA,sBAAA,CACA,uBAAA,CAGD,mEACC,yDAAA,CAGD,+CACC,YAAA,CACA,kBAAA,CACA,kBAAA,CACA,OAAA,CACA,gBAAA,CACA,eCEe,CDDf,kBCCe,CAAA,gBAAA,CDEf,UAAA,CAEA,qBAAA,CAEA,gGACC,cAAA,CAIF,gDACC,kCAAA,CACA,sBAAA,CACA,uBAAA,CAGD,gFACC,UCDiB,CDEjB,+GACC,4BAAA,CAIF,2SAEC,8CAAA,CAGD,6PAEC,yDAAA,CAID,mHACC,mCAAA,CAID,6IACC,wCAAA,CAOD,8EACC,yCAAA,CACA,eAAA,CAEA,uFACC,gBAAA,CAEA,6FACC,mDAAA,CAMH,2FACC,eAAA,CACA,sBAAA,CACA,kBAAA,CACA,UAAA,CAID,4HACC,4BAAA,CAID,2FACC,YAAA,CAGD,0PAEC,mDArCe,CAyChB,gGACC,eAAA,CAEA,eAAA,CAGA,gFACC,kEA9CoB,CA+CpB,mEA/CoB,CAiDrB,+EACC,qEAlDoB,CAmDpB,sEAnDoB,CAuDrB,qFACC,0BAAA,CACA,mHACC,iBAAA,CAGF,sFACC,uBAAA,CAMD,gFACC,kEArEoB,CAsEpB,qEAtEoB,CAwErB,+EACC,mEAzEoB,CA0EpB,sEA1EoB,CA8ErB,qFACC,yBAAA,CACA,mHACC,gBAAA,CAGF,sFACC,wBAAA,CAGF,qGACC,iBAAA,CAED,gGACC,qBAAA,CACA,sBAAA,CACA,UAAA,CACA,QAAA,CACA,KAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.checkbox-radio-switch {\n\tdisplay: flex;\n\n\t&__input {\n\t\tposition: absolute;\n\t\tz-index: -1;\n\t\topacity: 0 !important; // We need !important, or it gets overwritten by server style\n\t\twidth: var(--icon-size);\n\t\theight: var(--icon-size);\n\t}\n\n\t&__input:focus-visible + label {\n\t\toutline: 2px solid var(--color-primary-element) !important;\n\t}\n\n\t&__label {\n\t\tdisplay: flex;\n\t\talign-items: center;\n\t\tflex-direction: row;\n\t\tgap: 4px;\n\t\tuser-select: none;\n\t\tmin-height: $clickable-area;\n\t\tborder-radius: $clickable-area;\n\t\tpadding: 4px $icon-margin;\n\t\t// Set to 100% to make text overflow work on button style\n\t\twidth: 100%;\n\t\t// but restrict to content so plain checkboxes / radio switches do not expand\n\t\tmax-width: fit-content;\n\n\t\t&, * {\n\t\t\tcursor: pointer;\n\t\t}\n\t}\n\n\t&__icon > * {\n\t\tcolor: var(--color-primary-element);\n\t\twidth: var(--icon-size);\n\t\theight: var(--icon-size);\n\t}\n\n\t&--disabled &__label {\n\t\topacity: $opacity_disabled;\n\t\t.checkbox-radio-switch__icon > * {\n\t\t\tcolor: var(--color-main-text)\n\t\t}\n\t}\n\n\t&:not(&--disabled, &--checked):focus-within &__label,\n\t&:not(&--disabled, &--checked) &__label:hover {\n\t\tbackground-color: var(--color-background-hover);\n\t}\n\n\t&--checked:not(&--disabled):focus-within &__label,\n\t&--checked:not(&--disabled) &__label:hover {\n\t\tbackground-color: var(--color-primary-element-light-hover);\n\t}\n\n\t// Switch specific rules\n\t&-switch:not(&--checked) &__icon > * {\n\t\tcolor: var(--color-text-maxcontrast);\n\t}\n\n\t// If switch is checked AND disabled, use the fade primary colour\n\t&-switch.checkbox-radio-switch--disabled.checkbox-radio-switch--checked &__icon > * {\n\t\tcolor: var(--color-primary-element-light);\n\t}\n\n\t$border-radius: calc(var(--default-clickable-area) / 2);\n\t// keep inner border width in mind\n\t$border-radius-outer: calc($border-radius + 2px);\n\n\t&--button-variant.checkbox-radio-switch {\n\t\tborder: 2px solid var(--color-border-dark);\n\t\toverflow: hidden;\n\n\t\t&--checked {\n\t\t\tfont-weight: bold;\n\n\t\t\tlabel {\n\t\t\t\tbackground-color: var(--color-primary-element-light);\n\t\t\t}\n\t\t}\n\t}\n\n\t// Text overflow of button style\n\t&--button-variant &__label-text {\n\t\toverflow: hidden;\n\t\ttext-overflow: ellipsis;\n\t\twhite-space: nowrap;\n\t\twidth: 100%;\n\t}\n\n\t// Set icon color for non active elements to main text color\n\t&--button-variant:not(&--checked) &__icon > * {\n\t\tcolor: var(--color-main-text);\n\t}\n\n\t// Hide icon container if empty to remove virtual padding\n\t&--button-variant &__icon:empty {\n\t\tdisplay: none;\n\t}\n\n\t&--button-variant:not(&--button-variant-v-grouped):not(&--button-variant-h-grouped),\n\t&--button-variant &__label {\n\t\tborder-radius: $border-radius;\n\t}\n\n\t/* Special rules for vertical button groups */\n\t&--button-variant-v-grouped &__label {\n\t\tflex-basis: 100%;\n\t\t// vertically grouped buttons should all have the same width\n\t\tmax-width: unset;\n\t}\n\t&--button-variant-v-grouped {\n\t\t&:first-of-type {\n\t\t\tborder-top-left-radius: $border-radius-outer;\n\t\t\tborder-top-right-radius: $border-radius-outer;\n\t\t}\n\t\t&:last-of-type {\n\t\t\tborder-bottom-left-radius: $border-radius-outer;\n\t\t\tborder-bottom-right-radius: $border-radius-outer;\n\t\t}\n\n\t\t// remove borders between elements\n\t\t&:not(:last-of-type) {\n\t\t\tborder-bottom: 0!important;\n\t\t\t.checkbox-radio-switch__label {\n\t\t\t\tmargin-bottom: 2px;\n\t\t\t}\n\t\t}\n\t\t&:not(:first-of-type) {\n\t\t\tborder-top: 0!important;\n\t\t}\n\t}\n\n\t/* Special rules for horizontal button groups */\n\t&--button-variant-h-grouped {\n\t\t&:first-of-type {\n\t\t\tborder-top-left-radius: $border-radius-outer;\n\t\t\tborder-bottom-left-radius: $border-radius-outer;\n\t\t}\n\t\t&:last-of-type {\n\t\t\tborder-top-right-radius: $border-radius-outer;\n\t\t\tborder-bottom-right-radius: $border-radius-outer;\n\t\t}\n\n\t\t// remove borders between elements\n\t\t&:not(:last-of-type) {\n\t\t\tborder-right: 0!important;\n\t\t\t.checkbox-radio-switch__label {\n\t\t\t\tmargin-right: 2px;\n\t\t\t}\n\t\t}\n\t\t&:not(:first-of-type) {\n\t\t\tborder-left: 0!important;\n\t\t}\n\t}\n\t&--button-variant-h-grouped &__label-text {\n\t\ttext-align: center;\n\t}\n\t&--button-variant-h-grouped &__label {\n\t\tflex-direction: column;\n\t\tjustify-content: center;\n\t\twidth: 100%;\n\t\tmargin: 0;\n\t\tgap: 0;\n\t}\n}\n","/**\n * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @author John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n\n// https://uxplanet.org/7-rules-for-mobile-ui-button-design-e9cf2ea54556\n// recommended is 48px\n// 44px is what we choose and have very good visual-to-usability ratio\n$clickable-area: 44px;\n\n// background icon size\n// also used for the scss icon font\n$icon-size: 16px;\n\n// icon padding for a $clickable-area width and a $icon-size icon\n// ( 44px - 16px ) / 2\n$icon-margin: math.div($clickable-area - $icon-size, 2);\n\n// transparency background for icons\n$icon-focus-bg: rgba(127, 127, 127, .25);\n\n// popovermenu arrow width from the triangle center\n$arrow-width: 9px;\n\n// opacities\n$opacity_disabled: .5;\n$opacity_normal: .7;\n$opacity_full: 1;\n\n// menu round background hover feedback\n// good looking on dark AND white bg\n$action-background-hover: rgba(127, 127, 127, .25);\n\n// various structure data used in the \n// `AppNavigation` component\n$header-height: 50px;\n$navigation-width: 300px;\n\n// mobile breakpoint\n$breakpoint-mobile: 1024px;\n\n// top-bar spacing\n$topbar-margin: 4px;\n\n// navigation spacing\n$app-navigation-settings-margin: 3px;\n"],sourceRoot:""}]);const s=r},6613:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon[data-v-24368316]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.empty-content[data-v-24368316]{display:flex;align-items:center;flex-direction:column;margin-top:20vh}.modal-wrapper .empty-content[data-v-24368316]{margin-top:5vh;margin-bottom:5vh}.empty-content__icon[data-v-24368316]{display:flex;align-items:center;justify-content:center;width:64px;height:64px;margin:0 auto 15px;opacity:.4;background-repeat:no-repeat;background-position:center;background-size:64px}.empty-content__icon[data-v-24368316] svg{width:64px;height:64px}.empty-content__name[data-v-24368316]{margin-bottom:10px;text-align:center}.empty-content__action[data-v-24368316]{margin-top:8px}.modal-wrapper .empty-content__action[data-v-24368316]{margin-top:20px;display:flex}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcEmptyContent/NcEmptyContent.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,gCACC,YAAA,CACA,kBAAA,CACA,qBAAA,CACA,eAAA,CAEA,+CACC,cAAA,CACA,iBAAA,CAGD,sCACC,YAAA,CACA,kBAAA,CACA,sBAAA,CACA,UAAA,CACA,WAAA,CACA,kBAAA,CACA,UAAA,CACA,2BAAA,CACA,0BAAA,CACA,oBAAA,CAEA,0CACC,UAAA,CACA,WAAA,CAIF,sCACC,kBAAA,CACA,iBAAA,CAGD,wCACC,cAAA,CAEA,uDACC,eAAA,CACA,YAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.empty-content {\n\tdisplay: flex;\n\talign-items: center;\n\tflex-direction: column;\n\tmargin-top: 20vh;\n\n\t.modal-wrapper & {\n\t\tmargin-top: 5vh;\n\t\tmargin-bottom: 5vh;\n\t}\n\n\t&__icon {\n\t\tdisplay: flex;\n\t\talign-items: center;\n\t\tjustify-content: center;\n\t\twidth: 64px;\n\t\theight: 64px;\n\t\tmargin: 0 auto 15px;\n\t\topacity: .4;\n\t\tbackground-repeat: no-repeat;\n\t\tbackground-position: center;\n\t\tbackground-size: 64px;\n\n\t\t:deep(svg) {\n\t\t\twidth: 64px;\n\t\t\theight: 64px;\n\t\t}\n\t}\n\n\t&__name {\n\t\tmargin-bottom: 10px;\n\t\ttext-align: center;\n\t}\n\n\t&__action {\n\t\tmargin-top: 8px;\n\n\t\t.modal-wrapper & {\n\t\t\tmargin-top: 20px;\n\t\t\tdisplay: flex;\n\t\t}\n\t}\n}\n"],sourceRoot:""}]);const s=r},8502:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon[data-v-27fa1197]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.loading-icon svg[data-v-27fa1197]{animation:rotate var(--animation-duration, 0.8s) linear infinite}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcLoadingIcon/NcLoadingIcon.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,mCACC,gEAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.loading-icon svg{\n\tanimation: rotate var(--animation-duration, 0.8s) linear infinite;\n}\n"],sourceRoot:""}]);const s=r},1625:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var i=a(7537),n=a.n(i),o=a(3645),r=a.n(o)()(n());r.push([e.id,".material-design-icon{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.resize-observer{position:absolute;top:0;left:0;z-index:-1;width:100%;height:100%;border:none;background-color:rgba(0,0,0,0);pointer-events:none;display:block;overflow:hidden;opacity:0}.resize-observer object{display:block;position:absolute;top:0;left:0;height:100%;width:100%;overflow:hidden;pointer-events:none;z-index:-1}.v-popper--theme-dropdown.v-popper__popper{z-index:100000;top:0;left:0;display:block !important;filter:drop-shadow(0 1px 10px var(--color-box-shadow))}.v-popper--theme-dropdown.v-popper__popper .v-popper__inner{padding:0;color:var(--color-main-text);border-radius:var(--border-radius-large);overflow:hidden;background:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper .v-popper__arrow-container{position:absolute;z-index:1;width:0;height:0;border-style:solid;border-color:rgba(0,0,0,0);border-width:10px}.v-popper--theme-dropdown.v-popper__popper[data-popper-placement^=top] .v-popper__arrow-container{bottom:-10px;border-bottom-width:0;border-top-color:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper[data-popper-placement^=bottom] .v-popper__arrow-container{top:-10px;border-top-width:0;border-bottom-color:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper[data-popper-placement^=right] .v-popper__arrow-container{left:-10px;border-left-width:0;border-right-color:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper[data-popper-placement^=left] .v-popper__arrow-container{right:-10px;border-right-width:0;border-left-color:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper[aria-hidden=true]{visibility:hidden;transition:opacity var(--animation-quick),visibility var(--animation-quick);opacity:0}.v-popper--theme-dropdown.v-popper__popper[aria-hidden=false]{visibility:visible;transition:opacity var(--animation-quick);opacity:1}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcPopover/NcPopover.vue"],names:[],mappings:"AAGA,sBACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCLD,iBACC,iBAAA,CACA,KAAA,CACA,MAAA,CACA,UAAA,CACA,UAAA,CACA,WAAA,CACA,WAAA,CACA,8BAAA,CACA,mBAAA,CACA,aAAA,CACA,eAAA,CACA,SAAA,CAGD,wBACC,aAAA,CACA,iBAAA,CACA,KAAA,CACA,MAAA,CACA,WAAA,CACA,UAAA,CACA,eAAA,CACA,mBAAA,CACA,UAAA,CAMA,2CACC,cAAA,CACA,KAAA,CACA,MAAA,CACA,wBAAA,CAEA,sDAAA,CAEA,4DACC,SAAA,CACA,4BAAA,CACA,wCAAA,CACA,eAAA,CACA,uCAAA,CAGD,sEACC,iBAAA,CACA,SAAA,CACA,OAAA,CACA,QAAA,CACA,kBAAA,CACA,0BAAA,CACA,iBA1BW,CA6BZ,kGACC,YAAA,CACA,qBAAA,CACA,6CAAA,CAGD,qGACC,SAAA,CACA,kBAAA,CACA,gDAAA,CAGD,oGACC,UAAA,CACA,mBAAA,CACA,+CAAA,CAGD,mGACC,WAAA,CACA,oBAAA,CACA,8CAAA,CAGD,6DACC,iBAAA,CACA,2EAAA,CACA,SAAA,CAGD,8DACC,kBAAA,CACA,yCAAA,CACA,SAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n\n.resize-observer {\n\tposition:absolute;\n\ttop:0;\n\tleft:0;\n\tz-index:-1;\n\twidth:100%;\n\theight:100%;\n\tborder:none;\n\tbackground-color:transparent;\n\tpointer-events:none;\n\tdisplay:block;\n\toverflow:hidden;\n\topacity:0\n}\n\n.resize-observer object {\n\tdisplay:block;\n\tposition:absolute;\n\ttop:0;\n\tleft:0;\n\theight:100%;\n\twidth:100%;\n\toverflow:hidden;\n\tpointer-events:none;\n\tz-index:-1\n}\n\n$arrow-width: 10px;\n\n.v-popper--theme-dropdown {\n\t&.v-popper__popper {\n\t\tz-index: 100000;\n\t\ttop: 0;\n\t\tleft: 0;\n\t\tdisplay: block !important;\n\n\t\tfilter: drop-shadow(0 1px 10px var(--color-box-shadow));\n\n\t\t.v-popper__inner {\n\t\t\tpadding: 0;\n\t\t\tcolor: var(--color-main-text);\n\t\t\tborder-radius: var(--border-radius-large);\n\t\t\toverflow: hidden;\n\t\t\tbackground: var(--color-main-background);\n\t\t}\n\n\t\t.v-popper__arrow-container {\n\t\t\tposition: absolute;\n\t\t\tz-index: 1;\n\t\t\twidth: 0;\n\t\t\theight: 0;\n\t\t\tborder-style: solid;\n\t\t\tborder-color: transparent;\n\t\t\tborder-width: $arrow-width;\n\t\t}\n\n\t\t&[data-popper-placement^='top'] .v-popper__arrow-container {\n\t\t\tbottom: -$arrow-width;\n\t\t\tborder-bottom-width: 0;\n\t\t\tborder-top-color: var(--color-main-background);\n\t\t}\n\n\t\t&[data-popper-placement^='bottom'] .v-popper__arrow-container {\n\t\t\ttop: -$arrow-width;\n\t\t\tborder-top-width: 0;\n\t\t\tborder-bottom-color: var(--color-main-background);\n\t\t}\n\n\t\t&[data-popper-placement^='right'] .v-popper__arrow-container {\n\t\t\tleft: -$arrow-width;\n\t\t\tborder-left-width: 0;\n\t\t\tborder-right-color: var(--color-main-background);\n\t\t}\n\n\t\t&[data-popper-placement^='left'] .v-popper__arrow-container {\n\t\t\tright: -$arrow-width;\n\t\t\tborder-right-width: 0;\n\t\t\tborder-left-color: var(--color-main-background);\n\t\t}\n\n\t\t&[aria-hidden='true'] {\n\t\t\tvisibility: hidden;\n\t\t\ttransition: opacity var(--animation-quick), visibility var(--animation-quick);\n\t\t\topacity: 0;\n\t\t}\n\n\t\t&[aria-hidden='false'] {\n\t\t\tvisibility: visible;\n\t\t\ttransition: opacity var(--animation-quick);\n\t\t\topacity: 1;\n\t\t}\n\t}\n}\n\n"],sourceRoot:""}]);const s=r},3645:e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var a="",i=void 0!==t[5];return t[4]&&(a+="@supports (".concat(t[4],") {")),t[2]&&(a+="@media ".concat(t[2]," {")),i&&(a+="@layer".concat(t[5].length>0?" ".concat(t[5]):""," {")),a+=e(t),i&&(a+="}"),t[2]&&(a+="}"),t[4]&&(a+="}"),a})).join("")},t.i=function(e,a,i,n,o){"string"==typeof e&&(e=[[null,e,void 0]]);var r={};if(i)for(var s=0;s<this.length;s++){var l=this[s][0];null!=l&&(r[l]=!0)}for(var c=0;c<e.length;c++){var d=[].concat(e[c]);i&&r[d[0]]||(void 0!==o&&(void 0===d[5]||(d[1]="@layer".concat(d[5].length>0?" ".concat(d[5]):""," {").concat(d[1],"}")),d[5]=o),a&&(d[2]?(d[1]="@media ".concat(d[2]," {").concat(d[1],"}"),d[2]=a):d[2]=a),n&&(d[4]?(d[1]="@supports (".concat(d[4],") {").concat(d[1],"}"),d[4]=n):d[4]="".concat(n)),t.push(d))}},t}},7537:e=>{"use strict";e.exports=function(e){var t=e[1],a=e[3];if(!a)return t;if("function"==typeof btoa){var i=btoa(unescape(encodeURIComponent(JSON.stringify(a)))),n="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(i),o="/*# ".concat(n," */");return[t].concat([o]).join("\n")}return[t].join("\n")}},3379:e=>{"use strict";var t=[];function a(e){for(var a=-1,i=0;i<t.length;i++)if(t[i].identifier===e){a=i;break}return a}function i(e,i){for(var o={},r=[],s=0;s<e.length;s++){var l=e[s],c=i.base?l[0]+i.base:l[0],d=o[c]||0,u="".concat(c," ").concat(d);o[c]=d+1;var p=a(u),A={css:l[1],media:l[2],sourceMap:l[3],supports:l[4],layer:l[5]};if(-1!==p)t[p].references++,t[p].updater(A);else{var m=n(A,i);i.byIndex=s,t.splice(s,0,{identifier:u,updater:m,references:1})}r.push(u)}return r}function n(e,t){var a=t.domAPI(t);a.update(e);return function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap&&t.supports===e.supports&&t.layer===e.layer)return;a.update(e=t)}else a.remove()}}e.exports=function(e,n){var o=i(e=e||[],n=n||{});return function(e){e=e||[];for(var r=0;r<o.length;r++){var s=a(o[r]);t[s].references--}for(var l=i(e,n),c=0;c<o.length;c++){var d=a(o[c]);0===t[d].references&&(t[d].updater(),t.splice(d,1))}o=l}}},569:e=>{"use strict";var t={};e.exports=function(e,a){var i=function(e){if(void 0===t[e]){var a=document.querySelector(e);if(window.HTMLIFrameElement&&a instanceof window.HTMLIFrameElement)try{a=a.contentDocument.head}catch(e){a=null}t[e]=a}return t[e]}(e);if(!i)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");i.appendChild(a)}},9216:e=>{"use strict";e.exports=function(e){var t=document.createElement("style");return e.setAttributes(t,e.attributes),e.insert(t,e.options),t}},3565:(e,t,a)=>{"use strict";e.exports=function(e){var t=a.nc;t&&e.setAttribute("nonce",t)}},7795:e=>{"use strict";e.exports=function(e){if("undefined"==typeof document)return{update:function(){},remove:function(){}};var t=e.insertStyleElement(e);return{update:function(a){!function(e,t,a){var i="";a.supports&&(i+="@supports (".concat(a.supports,") {")),a.media&&(i+="@media ".concat(a.media," {"));var n=void 0!==a.layer;n&&(i+="@layer".concat(a.layer.length>0?" ".concat(a.layer):""," {")),i+=a.css,n&&(i+="}"),a.media&&(i+="}"),a.supports&&(i+="}");var o=a.sourceMap;o&&"undefined"!=typeof btoa&&(i+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(o))))," */")),t.styleTagTransform(i,e,t.options)}(t,e,a)},remove:function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(t)}}}},4589:e=>{"use strict";e.exports=function(e,t){if(t.styleSheet)t.styleSheet.cssText=e;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(e))}}},5727:()=>{},2112:()=>{},2102:()=>{},3768:()=>{},9258:()=>{},9280:()=>{},2405:()=>{},1900:(e,t,a)=>{"use strict";function i(e,t,a,i,n,o,r,s){var l,c="function"==typeof e?e.options:e;if(t&&(c.render=t,c.staticRenderFns=a,c._compiled=!0),i&&(c.functional=!0),o&&(c._scopeId="data-v-"+o),r?(l=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),n&&n.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(r)},c._ssrRegister=l):n&&(l=s?function(){n.call(this,(c.functional?this.parent:this).$root.$options.shadowRoot)}:n),l)if(c.functional){c._injectStyles=l;var d=c.render;c.render=function(e,t){return l.call(t),d(e,t)}}else{var u=c.beforeCreate;c.beforeCreate=u?[].concat(u,l):[l]}return{exports:e,options:c}}a.d(t,{Z:()=>i})},7931:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/l10n/gettext */ "./node_modules/@nextcloud/l10n/dist/gettext.js")},4055:e=>{"use strict";e.exports=__webpack_require__(/*! @vueuse/components */ "./node_modules/@vueuse/components/index.cjs")},9454:e=>{"use strict";e.exports=__webpack_require__(/*! floating-vue */ "./node_modules/floating-vue/dist/floating-vue.es.js")},4505:e=>{"use strict";e.exports=__webpack_require__(/*! focus-trap */ "./node_modules/focus-trap/dist/focus-trap.esm.js")},2734:e=>{"use strict";e.exports=__webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js")},3875:e=>{"use strict";e.exports=__webpack_require__(/*! vue-material-design-icons/ArrowRight.vue */ "./node_modules/vue-material-design-icons/ArrowRight.vue")},8618:e=>{"use strict";e.exports=__webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue")},1441:e=>{"use strict";e.exports=__webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue")}},t={};function a(i){var n=t[i];if(void 0!==n)return n.exports;var o=t[i]={id:i,exports:{}};return e[i](o,o.exports,a),o.exports}a.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return a.d(t,{a:t}),t},a.d=(e,t)=>{for(var i in t)a.o(t,i)&&!a.o(e,i)&&Object.defineProperty(e,i,{enumerable:!0,get:t[i]})},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),a.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},a.nc=void 0;var i={};return(()=>{"use strict";a.r(i),a.d(i,{default:()=>L});var e=a(3329);const t={name:"NcAppSidebarTabs",components:{NcCheckboxRadioSwitch:a(3116).default,NcVNodes:e.default},provide(){return{registerTab:this.registerTab,unregisterTab:this.unregisterTab,getActiveTab:()=>this.activeTab}},props:{active:{type:String,default:""}},emits:["update:active"],data:()=>({tabs:[],activeTab:""}),computed:{hasMultipleTabs(){return this.tabs.length>1},currentTabIndex(){return this.tabs.findIndex((e=>e.id===this.activeTab))}},watch:{active(e){e!==this.activeTab&&this.updateActive()}},methods:{setActive(e){this.activeTab=e,this.$emit("update:active",this.activeTab)},focusPreviousTab(){this.currentTabIndex>0&&this.setActive(this.tabs[this.currentTabIndex-1].id),this.focusActiveTab()},focusNextTab(){this.currentTabIndex<this.tabs.length-1&&this.setActive(this.tabs[this.currentTabIndex+1].id),this.focusActiveTab()},focusFirstTab(){this.setActive(this.tabs[0].id),this.focusActiveTab()},focusLastTab(){this.setActive(this.tabs[this.tabs.length-1].id),this.focusActiveTab()},focusActiveTab(){this.$el.querySelector('[data-id="'.concat(this.activeTab,'"]')).focus()},focusActiveTabContent(){this.$el.querySelector("#tab-"+this.activeTab).focus()},updateActive(){this.activeTab=this.active&&this.tabs.some((e=>e.id===this.active))?this.active:this.tabs.length>0?this.tabs[0].id:""},registerTab(e){this.tabs.push(e),this.tabs.sort(((e,t)=>e.order===t.order?OC.Util.naturalSortCompare(e.name,t.name):e.order-t.order)),this.updateActive()},unregisterTab(e){const t=this.tabs.findIndex((t=>t.id===e));-1!==t&&this.tabs.splice(t,1),this.activeTab===e&&this.updateActive()}}};var n=a(3379),o=a.n(n),r=a(7795),s=a.n(r),l=a(569),c=a.n(l),d=a(3565),u=a.n(d),p=a(9216),A=a.n(p),m=a(4589),h=a.n(m),g=a(2789),v={};v.styleTagTransform=h(),v.setAttributes=u(),v.insert=c().bind(null,"head"),v.domAPI=s(),v.insertStyleElement=A();o()(g.Z,v);g.Z&&g.Z.locals&&g.Z.locals;var b=a(1900);const C=(0,b.Z)(t,(function(){var e=this,t=e._self._c;return t("div",{staticClass:"app-sidebar-tabs"},[e.hasMultipleTabs?t("nav",{staticClass:"app-sidebar-tabs__nav",attrs:{role:"tablist"},on:{keydown:[function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"left",37,t.key,["Left","ArrowLeft"])||"button"in t&&0!==t.button||t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusPreviousTab.apply(null,arguments))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"right",39,t.key,["Right","ArrowRight"])||"button"in t&&2!==t.button||t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusNextTab.apply(null,arguments))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"tab",9,t.key,"Tab")||t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusActiveTabContent.apply(null,arguments))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"home",void 0,t.key,void 0)||t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusFirstTab.apply(null,arguments))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"end",void 0,t.key,void 0)||t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusLastTab.apply(null,arguments))},function(t){return t.type.indexOf("key")||33===t.keyCode?t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusFirstTab.apply(null,arguments)):null},function(t){return t.type.indexOf("key")||34===t.keyCode?t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusLastTab.apply(null,arguments)):null}]}},e._l(e.tabs,(function(a){return t("NcCheckboxRadioSwitch",{key:a.id,staticClass:"app-sidebar-tabs__tab",class:{active:a.id===e.activeTab},attrs:{"aria-controls":"tab-".concat(a.id),"aria-selected":e.activeTab===a.id,"button-variant":!0,checked:e.activeTab===a.id,"data-id":a.id,tabindex:e.activeTab===a.id?0:-1,"button-variant-grouped":"horizontal",role:"tab",type:"radio"},on:{"update:checked":function(t){return e.setActive(a.id)}},scopedSlots:e._u([{key:"icon",fn:function(){return[t("NcVNodes",{attrs:{vnodes:a.renderIcon()}},[t("span",{staticClass:"app-sidebar-tabs__tab-icon",class:a.icon})])]},proxy:!0}],null,!0)},[t("span",{staticClass:"app-sidebar-tabs__tab-caption"},[e._v("\n\t\t\t\t"+e._s(a.name)+"\n\t\t\t")])])})),1):e._e(),e._v(" "),t("div",{staticClass:"app-sidebar-tabs__content",class:{"app-sidebar-tabs__content--multiple":e.hasMultipleTabs}},[e._t("default")],2)])}),[],!1,null,"d6d35ae8",null).exports;var f=a(8250),y=a(6492),k=a(4462),w=a(4242),x=a(8167),_=a(5675),S=a(336),N=a(932),z=a(3875),j=a.n(z),E=a(8618),P=a.n(E);const B=__webpack_require__(/*! vue-material-design-icons/Star.vue */ "./node_modules/vue-material-design-icons/Star.vue");var T=a.n(B);const D=__webpack_require__(/*! vue-material-design-icons/StarOutline.vue */ "./node_modules/vue-material-design-icons/StarOutline.vue");var F=a.n(D),O=a(4055);const $={name:"NcAppSidebar",components:{NcActions:f.default,NcAppSidebarTabs:C,ArrowRight:j(),NcButton:k.default,NcLoadingIcon:y.default,NcEmptyContent:w.default,Close:P(),Star:T(),StarOutline:F()},directives:{focus:x.default,linkify:_.default,ClickOutside:O.vOnClickOutside,Tooltip:S.default},props:{active:{type:String,default:""},name:{type:String,default:"",required:!0},nameEditable:{type:Boolean,default:!1},namePlaceholder:{type:String,default:""},subname:{type:String,default:""},subtitle:{type:String,default:""},background:{type:String,default:""},starred:{type:Boolean,default:null},starLoading:{type:Boolean,default:!1},loading:{type:Boolean,default:!1},compact:{type:Boolean,default:!1},empty:{type:Boolean,default:!1},forceMenu:{type:Boolean,default:!1},linkifyName:{type:Boolean,default:!1},title:{type:String,default:""}},emits:["close","closing","closed","opening","opened","figure-click","update:starred","update:nameEditable","update:name","update:active","submit-name","dismiss-editing"],data(){return{changeNameTranslated:(0,N.t)("Change name"),closeTranslated:(0,N.t)("Close sidebar"),favoriteTranslated:(0,N.t)("Favorite"),isStarred:this.starred}},computed:{canStar(){return null!==this.isStarred},hasFigure(){return this.$slots.header||this.background},hasFigureClickListener(){return this.$listeners["figure-click"]}},watch:{starred(){this.isStarred=this.starred}},beforeDestroy(){this.$emit("closed")},methods:{onBeforeEnter(e){this.$emit("opening",e)},onAfterEnter(e){this.$emit("opened",e)},onBeforeLeave(e){this.$emit("closing",e)},onAfterLeave(e){this.$emit("closed",e)},closeSidebar(e){this.$emit("close",e)},onFigureClick(e){this.$emit("figure-click",e)},toggleStarred(){this.isStarred=!this.isStarred,this.$emit("update:starred",this.isStarred)},editName(){this.$emit("update:nameEditable",!0),this.nameEditable&&this.$nextTick((()=>this.$refs.nameInput.focus()))},onNameInput(e){this.$emit("update:name",e.target.value)},onSubmitName(e){this.$emit("update:nameEditable",!1),this.$emit("submit-name",e)},onDismissEditing(){this.$emit("update:nameEditable",!1),this.$emit("dismiss-editing")},onUpdateActive(e){this.$emit("update:active",e)}}};var G=a(6184),I={};I.styleTagTransform=h(),I.setAttributes=u(),I.insert=c().bind(null,"head"),I.domAPI=s(),I.insertStyleElement=A();o()(G.Z,I);G.Z&&G.Z.locals&&G.Z.locals;var M=a(2030),U={};U.styleTagTransform=h(),U.setAttributes=u(),U.insert=c().bind(null,"head"),U.domAPI=s(),U.insertStyleElement=A();o()(M.Z,U);M.Z&&M.Z.locals&&M.Z.locals;var R=a(2112),Z=a.n(R),q=(0,b.Z)($,(function(){var e=this,t=e._self._c;return t("transition",{attrs:{appear:"",name:"slide-right"},on:{"before-enter":e.onBeforeEnter,"after-enter":e.onAfterEnter,"before-leave":e.onBeforeLeave,"after-leave":e.onAfterLeave}},[t("aside",{staticClass:"app-sidebar",attrs:{id:"app-sidebar-vue"}},[t("header",{staticClass:"app-sidebar-header",class:{"app-sidebar-header--with-figure":e.hasFigure,"app-sidebar-header--compact":e.compact}},[t("div",{staticClass:"app-sidebar-header__info"},[e.hasFigure&&!e.empty?t("div",{staticClass:"app-sidebar-header__figure",class:{"app-sidebar-header__figure--with-action":e.hasFigureClickListener},style:{backgroundImage:"url(".concat(e.background,")")},attrs:{tabindex:"0"},on:{click:e.onFigureClick,keydown:function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"enter",13,t.key,"Enter")?null:e.onFigureClick.apply(null,arguments)}}},[e._t("header")],2):e._e(),e._v(" "),e.empty?e._e():t("div",{staticClass:"app-sidebar-header__desc",class:{"app-sidebar-header__desc--with-tertiary-action":e.canStar||e.$slots["tertiary-actions"],"app-sidebar-header__desc--editable":e.nameEditable&&!e.subname,"app-sidebar-header__desc--with-subname--editable":e.nameEditable&&e.subname,"app-sidebar-header__desc--without-actions":!e.$slots["secondary-actions"]}},[e.canStar||e.$slots["tertiary-actions"]?t("div",{staticClass:"app-sidebar-header__tertiary-actions"},[e._t("tertiary-actions",(function(){return[e.canStar?t("NcButton",{staticClass:"app-sidebar-header__star",attrs:{"aria-label":e.favoriteTranslated,type:"secondary"},on:{click:function(t){return t.preventDefault(),e.toggleStarred.apply(null,arguments)}},scopedSlots:e._u([{key:"icon",fn:function(){return[e.starLoading?t("NcLoadingIcon"):e.isStarred?t("Star",{attrs:{size:20}}):t("StarOutline",{attrs:{size:20}})]},proxy:!0}],null,!1,2575459756)}):e._e()]}))],2):e._e(),e._v(" "),t("div",{staticClass:"app-sidebar-header__name-container"},[t("div",{staticClass:"app-sidebar-header__mainname-container"},[t("h2",{directives:[{name:"show",rawName:"v-show",value:!e.nameEditable,expression:"!nameEditable"},{name:"linkify",rawName:"v-linkify",value:{text:e.name,linkify:e.linkifyName},expression:"{text: name, linkify: linkifyName}"}],staticClass:"app-sidebar-header__mainname",attrs:{"aria-label":e.title,title:e.title,tabindex:e.nameEditable?0:void 0},on:{click:function(t){return t.target!==t.currentTarget?null:e.editName.apply(null,arguments)}}},[e._v("\n\t\t\t\t\t\t\t\t"+e._s(e.name)+"\n\t\t\t\t\t\t\t")]),e._v(" "),e.nameEditable?[t("form",{directives:[{name:"click-outside",rawName:"v-click-outside",value:()=>e.onSubmitName(),expression:"() => onSubmitName()"}],staticClass:"app-sidebar-header__mainname-form",on:{submit:function(t){return t.preventDefault(),e.onSubmitName.apply(null,arguments)}}},[t("input",{directives:[{name:"focus",rawName:"v-focus"}],ref:"nameInput",staticClass:"app-sidebar-header__mainname-input",attrs:{type:"text",placeholder:e.namePlaceholder},domProps:{value:e.name},on:{keydown:function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"esc",27,t.key,["Esc","Escape"])?null:e.onDismissEditing.apply(null,arguments)},input:e.onNameInput}}),e._v(" "),t("NcButton",{attrs:{type:"tertiary-no-background","aria-label":e.changeNameTranslated,"native-type":"submit"},scopedSlots:e._u([{key:"icon",fn:function(){return[t("ArrowRight",{attrs:{size:20}})]},proxy:!0}],null,!1,1252225425)})],1)]:e._e(),e._v(" "),e.$slots["secondary-actions"]?t("NcActions",{staticClass:"app-sidebar-header__menu",attrs:{"force-menu":e.forceMenu}},[e._t("secondary-actions")],2):e._e()],2),e._v(" "),""!==e.subname.trim()?t("p",{staticClass:"app-sidebar-header__subname",attrs:{"aria-label":e.subtitle,title:e.subtitle}},[e._v("\n\t\t\t\t\t\t\t"+e._s(e.subname)+"\n\t\t\t\t\t\t")]):e._e()])])]),e._v(" "),t("NcButton",{staticClass:"app-sidebar__close",attrs:{title:e.closeTranslated,"aria-label":e.closeTranslated,type:"tertiary"},on:{click:function(t){return t.preventDefault(),e.closeSidebar.apply(null,arguments)}},scopedSlots:e._u([{key:"icon",fn:function(){return[t("Close",{attrs:{size:20}})]},proxy:!0}])}),e._v(" "),e.$slots.description&&!e.empty?t("div",{staticClass:"app-sidebar-header__description"},[e._t("description")],2):e._e()],1),e._v(" "),t("NcAppSidebarTabs",{directives:[{name:"show",rawName:"v-show",value:!e.loading,expression:"!loading"}],ref:"tabs",attrs:{active:e.active},on:{"update:active":e.onUpdateActive}},[e._t("default")],2),e._v(" "),e.loading?t("NcEmptyContent",{scopedSlots:e._u([{key:"icon",fn:function(){return[t("NcLoadingIcon",{attrs:{size:64}})]},proxy:!0}],null,!1,826850984)}):e._e()],1)])}),[],!1,null,"fc71d00e",null);"function"==typeof Z()&&Z()(q);const L=q.exports})(),i})()));
//# sourceMappingURL=NcAppSidebar.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.js":
/*!************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.js ***!
  \************************************************************************/
/***/ (function(module) {

/*! For license information please see NcAppSidebarTab.js.LICENSE.txt */
!function(e,t){ true?module.exports=t():0}(self,(()=>(()=>{"use strict";var e={4909:(e,t,n)=>{n.d(t,{Z:()=>s});var r=n(7537),o=n.n(r),a=n(3645),i=n.n(a)()(o());i.push([e.id,".material-design-icon[data-v-4c850128]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.app-sidebar__tab[data-v-4c850128]{display:none;padding:10px;min-height:100%;max-height:100%;height:100%;overflow:auto}.app-sidebar__tab[data-v-4c850128]:focus{border-color:var(--color-primary-element);box-shadow:0 0 .2em var(--color-primary-element);outline:0}.app-sidebar__tab--active[data-v-4c850128]{display:block}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcAppSidebarTab/NcAppSidebarTab.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,mCACC,YAAA,CACA,YAAA,CACA,eAAA,CACA,eAAA,CACA,WAAA,CACA,aAAA,CAEA,yCACC,yCAAA,CACA,gDAAA,CACA,SAAA,CAGD,2CACC,aAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.app-sidebar__tab {\n\tdisplay: none;\n\tpadding: 10px;\n\tmin-height: 100%; // fill available height\n\tmax-height: 100%; // scroll inside\n\theight: 100%;\n\toverflow: auto;\n\n\t&:focus {\n\t\tborder-color: var(--color-primary-element);\n\t\tbox-shadow: 0 0 0.2em var(--color-primary-element);\n\t\toutline: 0;\n\t}\n\n\t&--active {\n\t\tdisplay: block;\n\t}\n}\n"],sourceRoot:""}]);const s=i},3645:e=>{e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var n="",r=void 0!==t[5];return t[4]&&(n+="@supports (".concat(t[4],") {")),t[2]&&(n+="@media ".concat(t[2]," {")),r&&(n+="@layer".concat(t[5].length>0?" ".concat(t[5]):""," {")),n+=e(t),r&&(n+="}"),t[2]&&(n+="}"),t[4]&&(n+="}"),n})).join("")},t.i=function(e,n,r,o,a){"string"==typeof e&&(e=[[null,e,void 0]]);var i={};if(r)for(var s=0;s<this.length;s++){var c=this[s][0];null!=c&&(i[c]=!0)}for(var l=0;l<e.length;l++){var d=[].concat(e[l]);r&&i[d[0]]||(void 0!==a&&(void 0===d[5]||(d[1]="@layer".concat(d[5].length>0?" ".concat(d[5]):""," {").concat(d[1],"}")),d[5]=a),n&&(d[2]?(d[1]="@media ".concat(d[2]," {").concat(d[1],"}"),d[2]=n):d[2]=n),o&&(d[4]?(d[1]="@supports (".concat(d[4],") {").concat(d[1],"}"),d[4]=o):d[4]="".concat(o)),t.push(d))}},t}},7537:e=>{e.exports=function(e){var t=e[1],n=e[3];if(!n)return t;if("function"==typeof btoa){var r=btoa(unescape(encodeURIComponent(JSON.stringify(n)))),o="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(r),a="/*# ".concat(o," */");return[t].concat([a]).join("\n")}return[t].join("\n")}},3379:e=>{var t=[];function n(e){for(var n=-1,r=0;r<t.length;r++)if(t[r].identifier===e){n=r;break}return n}function r(e,r){for(var a={},i=[],s=0;s<e.length;s++){var c=e[s],l=r.base?c[0]+r.base:c[0],d=a[l]||0,u="".concat(l," ").concat(d);a[l]=d+1;var p=n(u),f={css:c[1],media:c[2],sourceMap:c[3],supports:c[4],layer:c[5]};if(-1!==p)t[p].references++,t[p].updater(f);else{var v=o(f,r);r.byIndex=s,t.splice(s,0,{identifier:u,updater:v,references:1})}i.push(u)}return i}function o(e,t){var n=t.domAPI(t);n.update(e);return function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap&&t.supports===e.supports&&t.layer===e.layer)return;n.update(e=t)}else n.remove()}}e.exports=function(e,o){var a=r(e=e||[],o=o||{});return function(e){e=e||[];for(var i=0;i<a.length;i++){var s=n(a[i]);t[s].references--}for(var c=r(e,o),l=0;l<a.length;l++){var d=n(a[l]);0===t[d].references&&(t[d].updater(),t.splice(d,1))}a=c}}},569:e=>{var t={};e.exports=function(e,n){var r=function(e){if(void 0===t[e]){var n=document.querySelector(e);if(window.HTMLIFrameElement&&n instanceof window.HTMLIFrameElement)try{n=n.contentDocument.head}catch(e){n=null}t[e]=n}return t[e]}(e);if(!r)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");r.appendChild(n)}},9216:e=>{e.exports=function(e){var t=document.createElement("style");return e.setAttributes(t,e.attributes),e.insert(t,e.options),t}},3565:(e,t,n)=>{e.exports=function(e){var t=n.nc;t&&e.setAttribute("nonce",t)}},7795:e=>{e.exports=function(e){if("undefined"==typeof document)return{update:function(){},remove:function(){}};var t=e.insertStyleElement(e);return{update:function(n){!function(e,t,n){var r="";n.supports&&(r+="@supports (".concat(n.supports,") {")),n.media&&(r+="@media ".concat(n.media," {"));var o=void 0!==n.layer;o&&(r+="@layer".concat(n.layer.length>0?" ".concat(n.layer):""," {")),r+=n.css,o&&(r+="}"),n.media&&(r+="}"),n.supports&&(r+="}");var a=n.sourceMap;a&&"undefined"!=typeof btoa&&(r+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(a))))," */")),t.styleTagTransform(r,e,t.options)}(t,e,n)},remove:function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(t)}}}},4589:e=>{e.exports=function(e,t){if(t.styleSheet)t.styleSheet.cssText=e;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(e))}}},1900:(e,t,n)=>{function r(e,t,n,r,o,a,i,s){var c,l="function"==typeof e?e.options:e;if(t&&(l.render=t,l.staticRenderFns=n,l._compiled=!0),r&&(l.functional=!0),a&&(l._scopeId="data-v-"+a),i?(c=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),o&&o.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(i)},l._ssrRegister=c):o&&(c=s?function(){o.call(this,(l.functional?this.parent:this).$root.$options.shadowRoot)}:o),c)if(l.functional){l._injectStyles=c;var d=l.render;l.render=function(e,t){return c.call(t),d(e,t)}}else{var u=l.beforeCreate;l.beforeCreate=u?[].concat(u,c):[c]}return{exports:e,options:l}}n.d(t,{Z:()=>r})}},t={};function n(r){var o=t[r];if(void 0!==o)return o.exports;var a=t[r]={id:r,exports:{}};return e[r](a,a.exports,n),a.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.nc=void 0;var r={};return(()=>{n.r(r),n.d(r,{default:()=>h});const e={name:"NcAppSidebarTab",inject:["registerTab","unregisterTab","getActiveTab"],props:{id:{type:String,required:!0},name:{type:String,required:!0},icon:{type:String,default:""},order:{type:Number,default:0}},emits:["bottom-reached","scroll"],expose:["id","name","icon","order","renderIcon"],computed:{isActive(){return this.getActiveTab()===this.id}},created(){this.registerTab(this)},beforeDestroy(){this.unregisterTab(this.id)},methods:{onScroll(e){this.$el.scrollHeight-this.$el.scrollTop===this.$el.clientHeight&&this.$emit("bottom-reached",e),this.$emit("scroll",e)},renderIcon(){var e,t;return null===(e=(t=this.$scopedSlots).icon)||void 0===e?void 0:e.call(t)}}};var t=n(3379),o=n.n(t),a=n(7795),i=n.n(a),s=n(569),c=n.n(s),l=n(3565),d=n.n(l),u=n(9216),p=n.n(u),f=n(4589),v=n.n(f),A=n(4909),m={};m.styleTagTransform=v(),m.setAttributes=d(),m.insert=c().bind(null,"head"),m.domAPI=i(),m.insertStyleElement=p();o()(A.Z,m);A.Z&&A.Z.locals&&A.Z.locals;const h=(0,n(1900).Z)(e,(function(){var e=this,t=e._self._c;return t("section",{staticClass:"app-sidebar__tab",class:{"app-sidebar__tab--active":e.isActive},attrs:{id:"tab-".concat(e.id),"aria-hidden":!e.isActive,"aria-labelledby":e.id,tabindex:"0",role:"tabpanel"},on:{scroll:e.onScroll}},[t("h3",{staticClass:"hidden-visually"},[e._v("\n\t\t"+e._s(e.name)+"\n\t")]),e._v(" "),e._t("default")],2)}),[],!1,null,"4c850128",null).exports})(),r})()));
//# sourceMappingURL=NcAppSidebarTab.js.map

/***/ }),

/***/ "./apps/settings/src/mixins/AppManagement.js":
/*!***************************************************!*\
  !*** ./apps/settings/src/mixins/AppManagement.js ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../service/rebuild-navigation.js */ "./apps/settings/src/service/rebuild-navigation.js");
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */



/* harmony default export */ __webpack_exports__["default"] = ({
  computed: {
    appGroups: function appGroups() {
      return this.app.groups.map(function (group) {
        return {
          id: group,
          name: group
        };
      });
    },
    installing: function installing() {
      return this.$store.getters.loading('install');
    },
    isLoading: function isLoading() {
      return this.app && this.$store.getters.loading(this.app.id);
    },
    enableButtonText: function enableButtonText() {
      if (this.app.needsDownload) {
        return t('settings', 'Download and enable');
      }
      return t('settings', 'Enable');
    },
    forceEnableButtonText: function forceEnableButtonText() {
      if (this.app.needsDownload) {
        return t('settings', 'Allow untested app');
      }
      return t('settings', 'Allow untested app');
    },
    enableButtonTooltip: function enableButtonTooltip() {
      if (this.app.needsDownload) {
        return t('settings', 'The app will be downloaded from the App Store');
      }
      return false;
    },
    forceEnableButtonTooltip: function forceEnableButtonTooltip() {
      var base = t('settings', 'This app is not marked as compatible with your Nextcloud version. If you continue you will still be able to install the app. Note that the app might not work as expected.');
      if (this.app.needsDownload) {
        return base + ' ' + t('settings', 'The app will be downloaded from the App Store');
      }
      return base;
    }
  },
  data: function data() {
    return {
      groupCheckedAppsData: false
    };
  },
  mounted: function mounted() {
    if (this.app && this.app.groups && this.app.groups.length > 0) {
      this.groupCheckedAppsData = true;
    }
  },
  methods: {
    asyncFindGroup: function asyncFindGroup(query) {
      return this.$store.dispatch('getGroups', {
        search: query,
        limit: 5,
        offset: 0
      });
    },
    isLimitedToGroups: function isLimitedToGroups(app) {
      if (this.app.groups.length || this.groupCheckedAppsData) {
        return true;
      }
      return false;
    },
    setGroupLimit: function setGroupLimit() {
      if (!this.groupCheckedAppsData) {
        this.$store.dispatch('enableApp', {
          appId: this.app.id,
          groups: []
        });
      }
    },
    canLimitToGroups: function canLimitToGroups(app) {
      if (app.types && app.types.includes('filesystem') || app.types.includes('prelogin') || app.types.includes('authentication') || app.types.includes('logging') || app.types.includes('prevent_group_restriction')) {
        return false;
      }
      return true;
    },
    addGroupLimitation: function addGroupLimitation(groupArray) {
      var group = groupArray.pop();
      var groups = this.app.groups.concat([]).concat([group.id]);
      this.$store.dispatch('enableApp', {
        appId: this.app.id,
        groups: groups
      });
    },
    removeGroupLimitation: function removeGroupLimitation(group) {
      var currentGroups = this.app.groups.concat([]);
      var index = currentGroups.indexOf(group.id);
      if (index > -1) {
        currentGroups.splice(index, 1);
      }
      this.$store.dispatch('enableApp', {
        appId: this.app.id,
        groups: currentGroups
      });
    },
    forceEnable: function forceEnable(appId) {
      this.$store.dispatch('forceEnableApp', {
        appId: appId,
        groups: []
      }).then(function (response) {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(function (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    enable: function enable(appId) {
      this.$store.dispatch('enableApp', {
        appId: appId,
        groups: []
      }).then(function (response) {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(function (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    disable: function disable(appId) {
      this.$store.dispatch('disableApp', {
        appId: appId
      }).then(function (response) {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(function (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    remove: function remove(appId) {
      this.$store.dispatch('uninstallApp', {
        appId: appId
      }).then(function (response) {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(function (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    install: function install(appId) {
      this.$store.dispatch('enableApp', {
        appId: appId
      }).then(function (response) {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(function (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    },
    update: function update(appId) {
      this.$store.dispatch('updateApp', {
        appId: appId
      }).then(function (response) {
        (0,_service_rebuild_navigation_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
      }).catch(function (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(error);
      });
    }
  }
});

/***/ }),

/***/ "./apps/settings/src/service/rebuild-navigation.js":
/*!*********************************************************!*\
  !*** ./apps/settings/src/service/rebuild-navigation.js ***!
  \*********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");



/* harmony default export */ __webpack_exports__["default"] = (function () {
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('core/navigation', 2) + '/apps?format=json').then(function (_ref) {
    var data = _ref.data;
    if (data.ocs.meta.statuscode !== 200) {
      return;
    }
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('nextcloud:app-menu.refresh', {
      apps: data.ocs.data
    });
    window.dispatchEvent(new Event('resize'));
  });
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mixins/AppManagement.js */ "./apps/settings/src/mixins/AppManagement.js");
/* harmony import */ var _PrefixMixin_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PrefixMixin.vue */ "./apps/settings/src/components/PrefixMixin.vue");
/* harmony import */ var _Markdown_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Markdown.vue */ "./apps/settings/src/components/Markdown.vue");




/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AppDetails',
  components: {
    NcSelect: (_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_0___default()),
    Markdown: _Markdown_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  mixins: [_mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_1__["default"], _PrefixMixin_vue__WEBPACK_IMPORTED_MODULE_2__["default"]],
  props: {
    app: {
      type: Object,
      required: true
    }
  },
  data: function data() {
    return {
      groupCheckedAppsData: false
    };
  },
  computed: {
    appstoreUrl: function appstoreUrl() {
      return "https://apps.nextcloud.com/apps/".concat(this.app.id);
    },
    licence: function licence() {
      if (this.app.licence) {
        return t('settings', '{license}-licensed', {
          license: ('' + this.app.licence).toUpperCase()
        });
      }
      return null;
    },
    author: function author() {
      if (typeof this.app.author === 'string') {
        return [{
          '@value': this.app.author
        }];
      }
      if (this.app.author['@value']) {
        return [this.app.author];
      }
      return this.app.author;
    },
    appGroups: function appGroups() {
      return this.app.groups.map(function (group) {
        return {
          id: group,
          name: group
        };
      });
    },
    groups: function groups() {
      return this.$store.getters.getGroups.filter(function (group) {
        return group.id !== 'disabled';
      }).sort(function (a, b) {
        return a.name.localeCompare(b.name);
      });
    }
  },
  mounted: function mounted() {
    if (this.app.groups.length > 0) {
      this.groupCheckedAppsData = true;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AppList_AppItem_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppList/AppItem.vue */ "./apps/settings/src/components/AppList/AppItem.vue");
/* harmony import */ var _PrefixMixin_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PrefixMixin.vue */ "./apps/settings/src/components/PrefixMixin.vue");
/* harmony import */ var p_limit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! p-limit */ "./node_modules/p-limit/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__);
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");




/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AppList',
  components: {
    AppItem: _AppList_AppItem_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default())
  },
  mixins: [_PrefixMixin_vue__WEBPACK_IMPORTED_MODULE_1__["default"]],
  props: ['category', 'app', 'search'],
  computed: {
    counter: function counter() {
      return this.apps.filter(function (app) {
        return app.update;
      }).length;
    },
    loading: function loading() {
      return this.$store.getters.loading('list');
    },
    hasPendingUpdate: function hasPendingUpdate() {
      return this.apps.filter(function (app) {
        return app.update;
      }).length > 0;
    },
    showUpdateAll: function showUpdateAll() {
      return this.hasPendingUpdate && this.useListView;
    },
    apps: function apps() {
      var _this = this;
      var apps = this.$store.getters.getAllApps.filter(function (app) {
        return app.name.toLowerCase().search(_this.search.toLowerCase()) !== -1;
      }).sort(function (a, b) {
        var sortStringA = '' + (a.active ? 0 : 1) + (a.update ? 0 : 1) + a.name;
        var sortStringB = '' + (b.active ? 0 : 1) + (b.update ? 0 : 1) + b.name;
        return OC.Util.naturalSortCompare(sortStringA, sortStringB);
      });
      if (this.category === 'installed') {
        return apps.filter(function (app) {
          return app.installed;
        });
      }
      if (this.category === 'enabled') {
        return apps.filter(function (app) {
          return app.active && app.installed;
        });
      }
      if (this.category === 'disabled') {
        return apps.filter(function (app) {
          return !app.active && app.installed;
        });
      }
      if (this.category === 'app-bundles') {
        return apps.filter(function (app) {
          return app.bundles;
        });
      }
      if (this.category === 'updates') {
        return apps.filter(function (app) {
          return app.update;
        });
      }
      if (this.category === 'supported') {
        // For customers of the Nextcloud GmbH the app level will be set to `300` for apps that are supported in their subscription
        return apps.filter(function (app) {
          return app.level === 300;
        });
      }
      if (this.category === 'featured') {
        // An app level of `200` will be set for apps featured on the app store
        return apps.filter(function (app) {
          return app.level === 200;
        });
      }
      // filter app store categories
      return apps.filter(function (app) {
        return app.appstore && app.category !== undefined && (app.category === _this.category || app.category.indexOf(_this.category) > -1);
      });
    },
    bundles: function bundles() {
      var _this2 = this;
      return this.$store.getters.getServerData.bundles.filter(function (bundle) {
        return _this2.bundleApps(bundle.id).length > 0;
      });
    },
    bundleApps: function bundleApps() {
      return function (bundle) {
        return this.$store.getters.getAllApps.filter(function (app) {
          return app.bundleIds !== undefined && app.bundleIds.includes(bundle);
        });
      };
    },
    searchApps: function searchApps() {
      var _this3 = this;
      if (this.search === '') {
        return [];
      }
      return this.$store.getters.getAllApps.filter(function (app) {
        if (app.name.toLowerCase().search(_this3.search.toLowerCase()) !== -1) {
          return !_this3.apps.find(function (_app) {
            return _app.id === app.id;
          });
        }
        return false;
      });
    },
    useAppStoreView: function useAppStoreView() {
      return !this.useListView && !this.useBundleView;
    },
    useListView: function useListView() {
      return this.category === 'installed' || this.category === 'enabled' || this.category === 'disabled' || this.category === 'updates' || this.category === 'featured' || this.category === 'supported';
    },
    useBundleView: function useBundleView() {
      return this.category === 'app-bundles';
    },
    allBundlesEnabled: function allBundlesEnabled() {
      var _this4 = this;
      return function (id) {
        return _this4.bundleApps(id).filter(function (app) {
          return !app.active;
        }).length === 0;
      };
    },
    bundleToggleText: function bundleToggleText() {
      var _this5 = this;
      return function (id) {
        if (_this5.allBundlesEnabled(id)) {
          return t('settings', 'Disable all');
        }
        return t('settings', 'Download and enable all');
      };
    }
  },
  methods: {
    toggleBundle: function toggleBundle(id) {
      if (this.allBundlesEnabled(id)) {
        return this.disableBundle(id);
      }
      return this.enableBundle(id);
    },
    enableBundle: function enableBundle(id) {
      var apps = this.bundleApps(id).map(function (app) {
        return app.id;
      });
      this.$store.dispatch('enableApp', {
        appId: apps,
        groups: []
      }).catch(function (error) {
        console.error(error);
        OC.Notification.show(error);
      });
    },
    disableBundle: function disableBundle(id) {
      var apps = this.bundleApps(id).map(function (app) {
        return app.id;
      });
      this.$store.dispatch('disableApp', {
        appId: apps,
        groups: []
      }).catch(function (error) {
        OC.Notification.show(error);
      });
    },
    updateAll: function updateAll() {
      var _this6 = this;
      var limit = (0,p_limit__WEBPACK_IMPORTED_MODULE_2__["default"])(1);
      this.apps.filter(function (app) {
        return app.update;
      }).map(function (app) {
        return limit(function () {
          return _this6.$store.dispatch('updateApp', {
            appId: app.id
          });
        });
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AppScore_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppScore.vue */ "./apps/settings/src/components/AppList/AppScore.vue");
/* harmony import */ var _mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../mixins/AppManagement.js */ "./apps/settings/src/mixins/AppManagement.js");
/* harmony import */ var _SvgFilterMixin_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../SvgFilterMixin.vue */ "./apps/settings/src/components/SvgFilterMixin.vue");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__);
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }




/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AppItem',
  components: {
    AppScore: _AppScore_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default())
  },
  mixins: [_mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_1__["default"], _SvgFilterMixin_vue__WEBPACK_IMPORTED_MODULE_2__["default"]],
  props: {
    app: {},
    category: {},
    listView: {
      type: Boolean,
      default: true
    }
  },
  data: function data() {
    return {
      isSelected: false,
      scrolled: false,
      screenshotLoaded: false
    };
  },
  computed: {
    hasRating: function hasRating() {
      return this.app.appstoreData && this.app.appstoreData.ratingNumOverall > 5;
    }
  },
  watch: {
    '$route.params.id': function $routeParamsId(id) {
      this.isSelected = this.app.id === id;
    }
  },
  mounted: function mounted() {
    var _this = this;
    this.isSelected = this.app.id === this.$route.params.id;
    if (this.app.releases && this.app.screenshot) {
      var image = new Image();
      image.onload = function (e) {
        _this.screenshotLoaded = true;
      };
      image.src = this.app.screenshot;
    }
  },
  watchers: {},
  methods: {
    showAppDetails: function showAppDetails(event) {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              if (!(event.currentTarget.tagName === 'INPUT' || event.currentTarget.tagName === 'A')) {
                _context.next = 2;
                break;
              }
              return _context.abrupt("return");
            case 2:
              _context.prev = 2;
              _context.next = 5;
              return _this2.$router.push({
                name: 'apps-details',
                params: {
                  category: _this2.category,
                  id: _this2.app.id
                }
              });
            case 5:
              _context.next = 9;
              break;
            case 7:
              _context.prev = 7;
              _context.t0 = _context["catch"](2);
            case 9:
            case "end":
              return _context.stop();
          }
        }, _callee, null, [[2, 7]]);
      }))();
    },
    prefix: function prefix(_prefix, content) {
      return _prefix + '_' + content;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");

/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AppScore',
  props: ['score'],
  computed: {
    scoreImage: function scoreImage() {
      var score = Math.round(this.score * 10);
      var imageName = 'rating/s' + score + '.svg';
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.imagePath)('core', imageName);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var marked__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! marked */ "./node_modules/marked/lib/marked.esm.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(dompurify__WEBPACK_IMPORTED_MODULE_1__);


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Markdown',
  props: {
    text: {
      type: String,
      default: ''
    }
  },
  computed: {
    renderMarkdown: function renderMarkdown() {
      var renderer = new marked__WEBPACK_IMPORTED_MODULE_0__.marked.Renderer();
      renderer.link = function (href, title, text) {
        var prot;
        try {
          prot = decodeURIComponent(unescape(href)).replace(/[^\w:]/g, '').toLowerCase();
        } catch (e) {
          return '';
        }
        if (prot.indexOf('http:') !== 0 && prot.indexOf('https:') !== 0) {
          return '';
        }
        var out = '<a href="' + href + '" rel="noreferrer noopener"';
        if (title) {
          out += ' title="' + title + '"';
        }
        out += '>' + text + '</a>';
        return out;
      };
      renderer.image = function (href, title, text) {
        if (text) {
          return text;
        }
        return title;
      };
      renderer.blockquote = function (quote) {
        return quote;
      };
      return dompurify__WEBPACK_IMPORTED_MODULE_1___default().sanitize((0,marked__WEBPACK_IMPORTED_MODULE_0__.marked)(this.text.trim(), {
        renderer: renderer,
        gfm: false,
        highlight: false,
        tables: false,
        breaks: false,
        pedantic: false,
        sanitize: true,
        smartLists: true,
        smartypants: false
      }), {
        SAFE_FOR_JQUERY: true,
        ALLOWED_TAGS: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'p', 'a', 'ul', 'ol', 'li', 'em', 'del', 'blockquote']
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'PrefixMixin',
  methods: {
    prefix: function prefix(_prefix, content) {
      return _prefix + '_' + content;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'SvgFilterMixin',
  data: function data() {
    return {
      filterId: ''
    };
  },
  computed: {
    filterUrl: function filterUrl() {
      return "url(#".concat(this.filterId, ")");
    }
  },
  mounted: function mounted() {
    this.filterId = 'invertIconApps-' + Math.random().toString(36).substring(2);
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-localstorage */ "./node_modules/vue-localstorage/dist/vue-local-storage.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(vue_localstorage__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigation.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigation.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationSpacer.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationSpacer.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebarTab.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCounterBubble.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var vue_material_design_icons_StarShooting_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/StarShooting.vue */ "./node_modules/vue-material-design-icons/StarShooting.vue");
/* harmony import */ var _components_AppList_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../components/AppList.vue */ "./apps/settings/src/components/AppList.vue");
/* harmony import */ var _components_AppDetails_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../components/AppDetails.vue */ "./apps/settings/src/components/AppDetails.vue");
/* harmony import */ var _mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../mixins/AppManagement.js */ "./apps/settings/src/mixins/AppManagement.js");
/* harmony import */ var _components_AppList_AppScore_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../components/AppList/AppScore.vue */ "./apps/settings/src/components/AppList/AppScore.vue");
/* harmony import */ var _components_Markdown_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../components/Markdown.vue */ "./apps/settings/src/components/Markdown.vue");
/* harmony import */ var _constants_AppsConstants_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./../constants/AppsConstants.js */ "./apps/settings/src/constants/AppsConstants.js");


















vue__WEBPACK_IMPORTED_MODULE_17__["default"].use((vue_localstorage__WEBPACK_IMPORTED_MODULE_1___default()));
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Apps',
  APPS_SECTION_ENUM: _constants_AppsConstants_js__WEBPACK_IMPORTED_MODULE_16__.APPS_SECTION_ENUM,
  components: {
    NcAppContent: (_nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2___default()),
    AppDetails: _components_AppDetails_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    AppList: _components_AppList_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    IconStarShooting: vue_material_design_icons_StarShooting_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcAppNavigation: (_nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcAppNavigationItem: (_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcAppNavigationSpacer: (_nextcloud_vue_dist_Components_NcAppNavigationSpacer_js__WEBPACK_IMPORTED_MODULE_5___default()),
    NcCounterBubble: (_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_8___default()),
    AppScore: _components_AppList_AppScore_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    NcAppSidebar: (_nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_6___default()),
    NcAppSidebarTab: (_nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_7___default()),
    NcContent: (_nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_9___default()),
    Markdown: _components_Markdown_vue__WEBPACK_IMPORTED_MODULE_15__["default"]
  },
  mixins: [_mixins_AppManagement_js__WEBPACK_IMPORTED_MODULE_13__["default"]],
  props: {
    category: {
      type: String,
      default: 'installed'
    },
    id: {
      type: String,
      default: ''
    }
  },
  data: function data() {
    return {
      searchQuery: '',
      screenshotLoaded: false
    };
  },
  computed: {
    loading: function loading() {
      return this.$store.getters.loading('categories');
    },
    loadingList: function loadingList() {
      return this.$store.getters.loading('list');
    },
    app: function app() {
      var _this = this;
      return this.apps.find(function (app) {
        return app.id === _this.id;
      });
    },
    categories: function categories() {
      return this.$store.getters.getCategories;
    },
    apps: function apps() {
      return this.$store.getters.getAllApps;
    },
    updateCount: function updateCount() {
      return this.$store.getters.getUpdateCount;
    },
    settings: function settings() {
      return this.$store.getters.getServerData;
    },
    hasRating: function hasRating() {
      return this.app.appstoreData && this.app.appstoreData.ratingNumOverall > 5;
    },
    // sidebar app binding
    appSidebar: function appSidebar() {
      var authorName = function authorName(xmlNode) {
        if (xmlNode['@value']) {
          // Complex node (with email or homepage attribute)
          return xmlNode['@value'];
        }

        // Simple text node
        return xmlNode;
      };
      var author = Array.isArray(this.app.author) ? this.app.author.map(authorName).join(', ') : authorName(this.app.author);
      var license = t('settings', '{license}-licensed', {
        license: ('' + this.app.licence).toUpperCase()
      });
      var subname = t('settings', 'by {author}\n{license}', {
        author: author,
        license: license
      });
      return {
        background: this.app.screenshot && this.screenshotLoaded ? this.app.screenshot : this.app.preview,
        compact: !(this.app.screenshot && this.screenshotLoaded),
        name: this.app.name,
        subname: subname
      };
    },
    changelog: function changelog() {
      return function (release) {
        return release.translations.en.changelog;
      };
    },
    /**
     * Check if the current instance has a support subscription from the Nextcloud GmbH
     */
    isSubscribed: function isSubscribed() {
      // For customers of the Nextcloud GmbH the app level will be set to `300` for apps that are supported in their subscription
      return this.apps.some(function (app) {
        return app.level === 300;
      });
    }
  },
  watch: {
    category: function category() {
      this.searchQuery = '';
    },
    app: function app() {
      var _this$app,
        _this$app2,
        _this2 = this;
      this.screenshotLoaded = false;
      if ((_this$app = this.app) !== null && _this$app !== void 0 && _this$app.releases && (_this$app2 = this.app) !== null && _this$app2 !== void 0 && _this$app2.screenshot) {
        var image = new Image();
        image.onload = function (e) {
          _this2.screenshotLoaded = true;
        };
        image.src = this.app.screenshot;
      }
    }
  },
  beforeMount: function beforeMount() {
    this.$store.dispatch('getCategories', {
      shouldRefetchCategories: true
    });
    this.$store.dispatch('getAllApps');
    this.$store.dispatch('getGroups', {
      offset: 0,
      limit: 5
    });
    this.$store.commit('setUpdateCount', this.$store.getters.getServerData.updateCount);
  },
  mounted: function mounted() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.search', this.setSearch);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  beforeDestroy: function beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.search', this.setSearch);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  methods: {
    setSearch: function setSearch(_ref) {
      var query = _ref.query;
      this.searchQuery = query;
    },
    resetSearch: function resetSearch() {
      this.searchQuery = '';
    },
    hideAppDetails: function hideAppDetails() {
      this.$router.push({
        name: 'apps-category',
        params: {
          category: this.category
        }
      });
    },
    openDeveloperDocumentation: function openDeveloperDocumentation() {
      window.open(this.settings.developerDocumentation);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "app-details"
  }, [_c("div", {
    staticClass: "app-details__actions"
  }, [_vm.app.active && _vm.canLimitToGroups(_vm.app) ? _c("div", {
    staticClass: "app-details__actions-groups"
  }, [_c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.groupCheckedAppsData,
      expression: "groupCheckedAppsData"
    }],
    staticClass: "groups-enable__checkbox checkbox",
    attrs: {
      id: _vm.prefix("groups_enable", _vm.app.id),
      type: "checkbox"
    },
    domProps: {
      value: _vm.app.id,
      checked: Array.isArray(_vm.groupCheckedAppsData) ? _vm._i(_vm.groupCheckedAppsData, _vm.app.id) > -1 : _vm.groupCheckedAppsData
    },
    on: {
      change: [function ($event) {
        var $$a = _vm.groupCheckedAppsData,
          $$el = $event.target,
          $$c = $$el.checked ? true : false;
        if (Array.isArray($$a)) {
          var $$v = _vm.app.id,
            $$i = _vm._i($$a, $$v);
          if ($$el.checked) {
            $$i < 0 && (_vm.groupCheckedAppsData = $$a.concat([$$v]));
          } else {
            $$i > -1 && (_vm.groupCheckedAppsData = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
          }
        } else {
          _vm.groupCheckedAppsData = $$c;
        }
      }, _vm.setGroupLimit]
    }
  }), _vm._v(" "), _c("label", {
    attrs: {
      for: _vm.prefix("groups_enable", _vm.app.id)
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Limit to groups")))]), _vm._v(" "), _c("input", {
    staticClass: "group_select",
    attrs: {
      type: "hidden",
      title: _vm.t("settings", "All"),
      value: ""
    }
  }), _vm._v(" "), _c("br"), _vm._v(" "), _c("label", {
    attrs: {
      for: "limitToGroups"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Limit app usage to groups")))])]), _vm._v(" "), _vm.isLimitedToGroups(_vm.app) ? _c("NcSelect", {
    attrs: {
      "input-id": "limitToGroups",
      options: _vm.groups,
      value: _vm.appGroups,
      limit: 5,
      label: "name",
      multiple: true,
      "close-on-select": false
    },
    on: {
      "option:selected": _vm.addGroupLimitation,
      "option:deselected": _vm.removeGroupLimitation,
      search: _vm.asyncFindGroup
    }
  }, [_c("span", {
    attrs: {
      slot: "noResult"
    },
    slot: "noResult"
  }, [_vm._v(_vm._s(_vm.t("settings", "No results")))])]) : _vm._e()], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "app-details__actions-manage"
  }, [_vm.app.update ? _c("input", {
    staticClass: "update primary",
    attrs: {
      type: "button",
      value: _vm.t("settings", "Update to {version}", {
        version: _vm.app.update
      }),
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        return _vm.update(_vm.app.id);
      }
    }
  }) : _vm._e(), _vm._v(" "), _vm.app.canUnInstall ? _c("input", {
    staticClass: "uninstall",
    attrs: {
      type: "button",
      value: _vm.t("settings", "Remove"),
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        return _vm.remove(_vm.app.id);
      }
    }
  }) : _vm._e(), _vm._v(" "), _vm.app.active ? _c("input", {
    staticClass: "enable",
    attrs: {
      type: "button",
      value: _vm.t("settings", "Disable"),
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        return _vm.disable(_vm.app.id);
      }
    }
  }) : _vm._e(), _vm._v(" "), !_vm.app.active && (_vm.app.canInstall || _vm.app.isCompatible) ? _c("input", {
    staticClass: "enable primary",
    attrs: {
      title: _vm.enableButtonTooltip,
      "aria-label": _vm.enableButtonTooltip,
      type: "button",
      value: _vm.enableButtonText,
      disabled: !_vm.app.canInstall || _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        return _vm.enable(_vm.app.id);
      }
    }
  }) : !_vm.app.active && !_vm.app.canInstall ? _c("input", {
    staticClass: "enable force",
    attrs: {
      title: _vm.forceEnableButtonTooltip,
      "aria-label": _vm.forceEnableButtonTooltip,
      type: "button",
      value: _vm.forceEnableButtonText,
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        return _vm.forceEnable(_vm.app.id);
      }
    }
  }) : _vm._e()])]), _vm._v(" "), _c("ul", {
    staticClass: "app-details__dependencies"
  }, [_vm.app.missingMinOwnCloudVersion ? _c("li", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "This app has no minimum Nextcloud version assigned. This will be an error in the future.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.app.missingMaxOwnCloudVersion ? _c("li", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "This app has no maximum Nextcloud version assigned. This will be an error in the future.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), !_vm.app.canInstall ? _c("li", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "This app cannot be installed because the following dependencies are not fulfilled:")) + "\n\t\t\t"), _c("ul", {
    staticClass: "missing-dependencies"
  }, _vm._l(_vm.app.missingDependencies, function (dep, index) {
    return _c("li", {
      key: index
    }, [_vm._v("\n\t\t\t\t\t" + _vm._s(dep) + "\n\t\t\t\t")]);
  }), 0)]) : _vm._e()]), _vm._v(" "), _c("p", {
    staticClass: "app-details__documentation"
  }, [!_vm.app.internal ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.appstoreUrl,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "View in store")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.website ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.website,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Visit website")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.bugs ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.bugs,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Report a bug")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.documentation && _vm.app.documentation.user ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.documentation.user,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "User documentation")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.documentation && _vm.app.documentation.admin ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.documentation.admin,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Admin documentation")) + " ↗")]) : _vm._e(), _vm._v(" "), _vm.app.documentation && _vm.app.documentation.developer ? _c("a", {
    staticClass: "appslink",
    attrs: {
      href: _vm.app.documentation.developer,
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Developer documentation")) + " ↗")]) : _vm._e()]), _vm._v(" "), _c("Markdown", {
    staticClass: "app-details__description",
    attrs: {
      text: _vm.app.description
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&":
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4& ***!
  \******************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    attrs: {
      id: "app-content-inner"
    }
  }, [_c("div", {
    staticClass: "apps-list",
    class: {
      installed: _vm.useBundleView || _vm.useListView,
      store: _vm.useAppStoreView
    },
    attrs: {
      id: "apps-list"
    }
  }, [_vm.useListView ? [_vm.showUpdateAll ? _c("div", {
    staticClass: "toolbar"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.n("settings", "%n app has an update available", "%n apps have an update available", _vm.counter)) + "\n\t\t\t\t"), _vm.showUpdateAll ? _c("NcButton", {
    attrs: {
      id: "app-list-update-all",
      type: "primary"
    },
    on: {
      click: _vm.updateAll
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.n("settings", "Update", "Update all", _vm.counter)) + "\n\t\t\t\t")]) : _vm._e()], 1) : _vm._e(), _vm._v(" "), !_vm.showUpdateAll ? _c("div", {
    staticClass: "toolbar"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "All apps are up-to-date.")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _c("transition-group", {
    staticClass: "apps-list-container",
    attrs: {
      name: "app-list",
      tag: "div"
    }
  }, _vm._l(_vm.apps, function (app) {
    return _c("AppItem", {
      key: app.id,
      attrs: {
        app: app,
        category: _vm.category
      }
    });
  }), 1)] : _vm._e(), _vm._v(" "), _vm.useBundleView ? _c("transition-group", {
    staticClass: "apps-list-container",
    attrs: {
      name: "app-list",
      tag: "div"
    }
  }, [_vm._l(_vm.bundles, function (bundle) {
    return [_c("div", {
      key: bundle.id,
      staticClass: "apps-header"
    }, [_c("div", {
      staticClass: "app-image"
    }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(bundle.name) + " "), _c("input", {
      attrs: {
        type: "button",
        value: _vm.bundleToggleText(bundle.id)
      },
      on: {
        click: function click($event) {
          return _vm.toggleBundle(bundle.id);
        }
      }
    })]), _vm._v(" "), _c("div", {
      staticClass: "app-version"
    }), _vm._v(" "), _c("div", {
      staticClass: "app-level"
    }), _vm._v(" "), _c("div", {
      staticClass: "app-groups"
    }), _vm._v(" "), _c("div", {
      staticClass: "actions"
    }, [_vm._v("\n\t\t\t\t\t\t \n\t\t\t\t\t")])]), _vm._v(" "), _vm._l(_vm.bundleApps(bundle.id), function (app) {
      return _c("AppItem", {
        key: bundle.id + app.id,
        attrs: {
          app: app,
          category: _vm.category
        }
      });
    })];
  })], 2) : _vm._e(), _vm._v(" "), _vm.useAppStoreView ? _vm._l(_vm.apps, function (app) {
    return _c("AppItem", {
      key: app.id,
      attrs: {
        app: app,
        category: _vm.category,
        "list-view": false
      }
    });
  }) : _vm._e()], 2), _vm._v(" "), _c("div", {
    staticClass: "apps-list installed",
    attrs: {
      id: "apps-list-search"
    }
  }, [_c("div", {
    staticClass: "apps-list-container"
  }, [_vm.search !== "" && _vm.searchApps.length > 0 ? [_c("div", {
    staticClass: "section"
  }, [_c("div"), _vm._v(" "), _c("td", {
    attrs: {
      colspan: "5"
    }
  }, [_c("h2", [_vm._v(_vm._s(_vm.t("settings", "Results from other categories")))])])]), _vm._v(" "), _vm._l(_vm.searchApps, function (app) {
    return _c("AppItem", {
      key: app.id,
      attrs: {
        app: app,
        category: _vm.category,
        "list-view": true
      }
    });
  })] : _vm._e()], 2)]), _vm._v(" "), _vm.search !== "" && !_vm.loading && _vm.searchApps.length === 0 && _vm.apps.length === 0 ? _c("div", {
    staticClass: "emptycontent emptycontent-search",
    attrs: {
      id: "apps-list-empty"
    }
  }, [_c("div", {
    staticClass: "icon-settings-dark",
    attrs: {
      id: "app-list-empty-icon"
    }
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.t("settings", "No apps found for your version")))])]) : _vm._e(), _vm._v(" "), _c("div", {
    attrs: {
      id: "searchresults"
    }
  })]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "section",
    class: {
      selected: _vm.isSelected
    },
    on: {
      click: _vm.showAppDetails
    }
  }, [_c("div", {
    staticClass: "app-image app-image-icon",
    on: {
      click: _vm.showAppDetails
    }
  }, [_vm.listView && !_vm.app.preview || !_vm.listView && !_vm.screenshotLoaded ? _c("div", {
    staticClass: "icon-settings-dark"
  }) : _vm.listView && _vm.app.preview ? _c("svg", {
    attrs: {
      width: "32",
      height: "32",
      viewBox: "0 0 32 32"
    }
  }, [_c("image", {
    staticClass: "app-icon",
    attrs: {
      x: "0",
      y: "0",
      width: "32",
      height: "32",
      preserveAspectRatio: "xMinYMin meet",
      "xlink:href": _vm.app.preview
    }
  })]) : _vm._e(), _vm._v(" "), !_vm.listView && _vm.app.screenshot && _vm.screenshotLoaded ? _c("img", {
    attrs: {
      src: _vm.app.screenshot,
      width: "100%"
    }
  }) : _vm._e()]), _vm._v(" "), _c("div", {
    staticClass: "app-name",
    on: {
      click: _vm.showAppDetails
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.app.name) + "\n\t")]), _vm._v(" "), !_vm.listView ? _c("div", {
    staticClass: "app-summary"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.app.summary) + "\n\t")]) : _vm._e(), _vm._v(" "), _vm.listView ? _c("div", {
    staticClass: "app-version"
  }, [_vm.app.version ? _c("span", [_vm._v(_vm._s(_vm.app.version))]) : _vm.app.appstoreData.releases[0].version ? _c("span", [_vm._v(_vm._s(_vm.app.appstoreData.releases[0].version))]) : _vm._e()]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "app-level"
  }, [_vm.app.level === 300 ? _c("span", {
    staticClass: "supported icon-checkmark-color",
    attrs: {
      title: _vm.t("settings", "This app is supported via your current Nextcloud subscription."),
      "aria-label": _vm.t("settings", "This app is supported via your current Nextcloud subscription.")
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Supported")))]) : _vm._e(), _vm._v(" "), _vm.app.level === 200 ? _c("span", {
    staticClass: "official icon-checkmark",
    attrs: {
      title: _vm.t("settings", "Featured apps are developed by and within the community. They offer central functionality and are ready for production use."),
      "aria-label": _vm.t("settings", "Featured apps are developed by and within the community. They offer central functionality and are ready for production use.")
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Featured")))]) : _vm._e(), _vm._v(" "), _vm.hasRating && !_vm.listView ? _c("AppScore", {
    attrs: {
      score: _vm.app.score
    }
  }) : _vm._e()], 1), _vm._v(" "), _c("div", {
    staticClass: "actions"
  }, [_vm.app.error ? _c("div", {
    staticClass: "warning"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.app.error) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.isLoading ? _c("div", {
    staticClass: "icon icon-loading-small"
  }) : _vm._e(), _vm._v(" "), _vm.app.update ? _c("NcButton", {
    attrs: {
      type: "primary",
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        return _vm.update(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Update to {update}", {
    update: _vm.app.update
  })) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.app.canUnInstall ? _c("NcButton", {
    staticClass: "uninstall",
    attrs: {
      type: "tertiary",
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        return _vm.remove(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Remove")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.app.active ? _c("NcButton", {
    attrs: {
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        return _vm.disable(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Disable")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), !_vm.app.active && (_vm.app.canInstall || _vm.app.isCompatible) ? _c("NcButton", {
    attrs: {
      title: _vm.enableButtonTooltip,
      "aria-label": _vm.enableButtonTooltip,
      type: "primary",
      disabled: !_vm.app.canInstall || _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        return _vm.enable(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.enableButtonText) + "\n\t\t")]) : !_vm.app.active ? _c("NcButton", {
    attrs: {
      title: _vm.forceEnableButtonTooltip,
      "aria-label": _vm.forceEnableButtonTooltip,
      type: "secondary",
      disabled: _vm.installing || _vm.isLoading
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        return _vm.forceEnable(_vm.app.id);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.forceEnableButtonText) + "\n\t\t")]) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&":
/*!***************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("img", {
    staticClass: "app-score-image",
    attrs: {
      src: _vm.scoreImage
    }
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "settings-markdown",
    domProps: {
      innerHTML: _vm._s(_vm.renderMarkdown)
    }
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcContent", {
    class: {
      "with-app-sidebar": _vm.app
    },
    attrs: {
      "app-name": "settings",
      "content-class": {
        "icon-loading": _vm.loadingList
      },
      "navigation-class": {
        "icon-loading": _vm.loading
      }
    }
  }, [_c("NcAppNavigation", {
    scopedSlots: _vm._u([{
      key: "list",
      fn: function fn() {
        return [_c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-your-apps",
            to: {
              name: "apps"
            },
            exact: true,
            icon: "icon-category-installed",
            name: _vm.t("settings", "Your apps")
          }
        }), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-enabled",
            to: {
              name: "apps-category",
              params: {
                category: "enabled"
              }
            },
            icon: "icon-category-enabled",
            name: _vm.$options.APPS_SECTION_ENUM.enabled
          }
        }), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-disabled",
            to: {
              name: "apps-category",
              params: {
                category: "disabled"
              }
            },
            icon: "icon-category-disabled",
            name: _vm.$options.APPS_SECTION_ENUM.disabled
          }
        }), _vm._v(" "), _vm.updateCount > 0 ? _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-updates",
            to: {
              name: "apps-category",
              params: {
                category: "updates"
              }
            },
            icon: "icon-download",
            name: _vm.$options.APPS_SECTION_ENUM.updates
          },
          scopedSlots: _vm._u([{
            key: "counter",
            fn: function fn() {
              return [_c("NcCounterBubble", [_vm._v(_vm._s(_vm.updateCount))])];
            },
            proxy: true
          }], null, false, 54487302)
        }) : _vm._e(), _vm._v(" "), _vm.isSubscribed ? _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-supported",
            to: {
              name: "apps-category",
              params: {
                category: "supported"
              }
            },
            name: _vm.$options.APPS_SECTION_ENUM.supported
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function fn() {
              return [_c("IconStarShooting", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }], null, false, 704374136)
        }) : _vm._e(), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-your-bundles",
            to: {
              name: "apps-category",
              params: {
                category: "app-bundles"
              }
            },
            icon: "icon-category-app-bundles",
            name: _vm.$options.APPS_SECTION_ENUM["app-bundles"]
          }
        }), _vm._v(" "), _c("NcAppNavigationSpacer"), _vm._v(" "), _vm.settings.appstoreEnabled ? [_c("NcAppNavigationItem", {
          attrs: {
            id: "app-category-featured",
            to: {
              name: "apps-category",
              params: {
                category: "featured"
              }
            },
            icon: "icon-favorite",
            name: _vm.$options.APPS_SECTION_ENUM.featured
          }
        }), _vm._v(" "), _vm._l(_vm.categories, function (cat) {
          return _c("NcAppNavigationItem", {
            key: "icon-category-" + cat.ident,
            attrs: {
              icon: "icon-category-" + cat.ident,
              to: {
                name: "apps-category",
                params: {
                  category: cat.ident
                }
              },
              name: cat.displayName
            }
          });
        })] : _vm._e(), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "app-developer-docs",
            name: _vm.t("settings", "Developer documentation") + " ↗"
          },
          on: {
            click: _vm.openDeveloperDocumentation
          }
        })];
      },
      proxy: true
    }])
  }), _vm._v(" "), _c("NcAppContent", {
    staticClass: "app-settings-content",
    class: {
      "icon-loading": _vm.loadingList
    }
  }, [_c("AppList", {
    attrs: {
      category: _vm.category,
      app: _vm.app,
      search: _vm.searchQuery
    }
  })], 1), _vm._v(" "), _vm.id && _vm.app ? _c("NcAppSidebar", _vm._b({
    class: {
      "app-sidebar--without-background": !_vm.appSidebar.background
    },
    on: {
      close: _vm.hideAppDetails
    },
    scopedSlots: _vm._u([!_vm.appSidebar.background ? {
      key: "header",
      fn: function fn() {
        return [_c("div", {
          staticClass: "app-sidebar-header__figure--default-app-icon icon-settings-dark"
        })];
      },
      proxy: true
    } : null, {
      key: "description",
      fn: function fn() {
        return [_vm.app.level === 300 || _vm.app.level === 200 || _vm.hasRating ? _c("div", {
          staticClass: "app-level"
        }, [_vm.app.level === 300 ? _c("span", {
          staticClass: "supported icon-checkmark-color",
          attrs: {
            title: _vm.t("settings", "This app is supported via your current Nextcloud subscription.")
          }
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Supported")))]) : _vm._e(), _vm._v(" "), _vm.app.level === 200 ? _c("span", {
          staticClass: "official icon-checkmark",
          attrs: {
            title: _vm.t("settings", "Featured apps are developed by and within the community. They offer central functionality and are ready for production use.")
          }
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Featured")))]) : _vm._e(), _vm._v(" "), _vm.hasRating ? _c("AppScore", {
          attrs: {
            score: _vm.app.appstoreData.ratingOverall
          }
        }) : _vm._e()], 1) : _vm._e(), _vm._v(" "), _c("div", {
          staticClass: "app-version"
        }, [_c("p", [_vm._v(_vm._s(_vm.app.version))])])];
      },
      proxy: true
    }], null, true)
  }, "NcAppSidebar", _vm.appSidebar, false), [_vm._v(" "), _vm._v(" "), _c("NcAppSidebarTab", {
    attrs: {
      id: "desc",
      icon: "icon-category-office",
      name: _vm.t("settings", "Details"),
      order: 0
    }
  }, [_c("AppDetails", {
    attrs: {
      app: _vm.app
    }
  })], 1), _vm._v(" "), _vm.app.appstoreData && _vm.app.releases[0].translations.en.changelog ? _c("NcAppSidebarTab", {
    attrs: {
      id: "desca",
      icon: "icon-category-organization",
      name: _vm.t("settings", "Changelog"),
      order: 1
    }
  }, _vm._l(_vm.app.releases, function (release) {
    return _c("div", {
      key: release.version,
      staticClass: "app-sidebar-tabs__release"
    }, [_c("h2", [_vm._v(_vm._s(release.version))]), _vm._v(" "), _vm.changelog(release) ? _c("Markdown", {
      attrs: {
        text: _vm.changelog(release)
      }
    }) : _vm._e()], 1);
  }), 0) : _vm._e()], 1) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".app-details[data-v-59a92e62] {\n  padding: 20px;\n}\n.app-details__actions-manage[data-v-59a92e62] {\n  display: flex;\n}\n.app-details__actions-manage input[data-v-59a92e62] {\n  flex: 0 1 auto;\n  min-width: 0;\n  text-overflow: ellipsis;\n  white-space: nowrap;\n  overflow: hidden;\n}\n.app-details__dependencies[data-v-59a92e62] {\n  opacity: 0.7;\n}\n.app-details__documentation[data-v-59a92e62] {\n  padding-top: 20px;\n}\n.app-details__documentation a.appslink[data-v-59a92e62] {\n  display: block;\n}\n.app-details__description[data-v-59a92e62] {\n  padding-top: 20px;\n}\n.force[data-v-59a92e62] {\n  color: var(--color-error);\n  border-color: var(--color-error);\n  background: var(--color-main-background);\n}\n.force[data-v-59a92e62]:hover,\n.force[data-v-59a92e62]:active {\n  color: var(--color-main-background);\n  border-color: var(--color-error) !important;\n  background: var(--color-error);\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".settings-markdown[data-v-11f4a1b0] h1,\n.settings-markdown[data-v-11f4a1b0] h2,\n.settings-markdown[data-v-11f4a1b0] h3,\n.settings-markdown[data-v-11f4a1b0] h4,\n.settings-markdown[data-v-11f4a1b0] h5,\n.settings-markdown[data-v-11f4a1b0] h6 {\n  font-weight: 600;\n  line-height: 120%;\n  margin-top: 24px;\n  margin-bottom: 12px;\n  color: var(--color-main-text);\n}\n.settings-markdown[data-v-11f4a1b0] h1 {\n  font-size: 36px;\n  margin-top: 48px;\n}\n.settings-markdown[data-v-11f4a1b0] h2 {\n  font-size: 28px;\n  margin-top: 48px;\n}\n.settings-markdown[data-v-11f4a1b0] h3 {\n  font-size: 24px;\n}\n.settings-markdown[data-v-11f4a1b0] h4 {\n  font-size: 21px;\n}\n.settings-markdown[data-v-11f4a1b0] h5 {\n  font-size: 17px;\n}\n.settings-markdown[data-v-11f4a1b0] h6 {\n  font-size: var(--default-font-size);\n}\n.settings-markdown[data-v-11f4a1b0] pre {\n  white-space: pre;\n  overflow-x: auto;\n  background-color: var(--color-background-dark);\n  border-radius: var(--border-radius);\n  padding: 1em 1.3em;\n  margin-bottom: 1em;\n}\n.settings-markdown[data-v-11f4a1b0] p code {\n  background-color: var(--color-background-dark);\n  border-radius: var(--border-radius);\n  padding: 0.1em 0.3em;\n}\n.settings-markdown[data-v-11f4a1b0] li {\n  position: relative;\n}\n.settings-markdown[data-v-11f4a1b0] ul, .settings-markdown[data-v-11f4a1b0] ol {\n  padding-left: 10px;\n  margin-left: 10px;\n}\n.settings-markdown[data-v-11f4a1b0] ul li {\n  list-style-type: disc;\n}\n.settings-markdown[data-v-11f4a1b0] ul > li > ul > li {\n  list-style-type: circle;\n}\n.settings-markdown[data-v-11f4a1b0] ul > li > ul > li ul li {\n  list-style-type: square;\n}\n.settings-markdown[data-v-11f4a1b0] blockquote {\n  padding-left: 1em;\n  border-left: 4px solid var(--color-primary-element);\n  color: var(--color-text-maxcontrast);\n  margin-left: 0;\n  margin-right: 0;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".app-sidebar[data-v-d3714d0a]:not(.app-sidebar--without-background) :not(.app-sidebar-header--compact) .app-sidebar-header__figure {\n  background-size: cover;\n}\n.app-sidebar[data-v-d3714d0a]:not(.app-sidebar--without-background) .app-sidebar-header--compact .app-sidebar-header__figure {\n  background-size: 32px;\n  filter: var(--background-invert-if-bright);\n}\n.app-sidebar[data-v-d3714d0a] .app-sidebar-header__description .app-version {\n  padding-left: 10px;\n}\n.app-sidebar[data-v-d3714d0a].app-sidebar--without-background .app-sidebar-header__figure {\n  display: flex;\n  align-items: center;\n  justify-content: center;\n}\n.app-sidebar[data-v-d3714d0a].app-sidebar--without-background .app-sidebar-header__figure--default-app-icon {\n  width: 32px;\n  height: 32px;\n  background-size: 32px;\n}\n.app-sidebar[data-v-d3714d0a] .app-sidebar-header__desc .app-sidebar-header__subtitle {\n  overflow: visible !important;\n  height: auto;\n  white-space: normal !important;\n  line-height: 16px;\n}\n.app-sidebar[data-v-d3714d0a] .app-sidebar-header__action {\n  margin: 0 20px;\n}\n.app-sidebar[data-v-d3714d0a] .app-sidebar-header__action input {\n  margin: 3px;\n}\n.app-navigation[data-v-d3714d0a] button.app-navigation-toggle {\n  top: 8px;\n  right: -8px;\n}\n.app-sidebar-tabs__release h2[data-v-d3714d0a] {\n  border-bottom: 1px solid var(--color-border);\n}\n.app-sidebar-tabs__release[data-v-d3714d0a] h3 {\n  font-size: 20px;\n}\n.app-sidebar-tabs__release[data-v-d3714d0a] h4 {\n  font-size: 17px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "\n.app-icon[data-v-429da85a] {\n\tfilter: var(--background-invert-if-bright);\n}\n.actions[data-v-429da85a] {\n\tdisplay: flex !important;\n\tgap: 8px;\n\tflex-wrap: wrap;\n\tjustify-content: end;\n}\n", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/settings/src/components/AppDetails.vue":
/*!*****************************************************!*\
  !*** ./apps/settings/src/components/AppDetails.vue ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppDetails.vue?vue&type=template&id=59a92e62&scoped=true& */ "./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true&");
/* harmony import */ var _AppDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppDetails.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js&");
/* harmony import */ var _AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& */ "./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "59a92e62",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppDetails.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppList.vue":
/*!**************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AppList_vue_vue_type_template_id_6d1e92a4___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppList.vue?vue&type=template&id=6d1e92a4& */ "./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&");
/* harmony import */ var _AppList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppList.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AppList.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AppList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppList_vue_vue_type_template_id_6d1e92a4___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppList_vue_vue_type_template_id_6d1e92a4___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppList.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue":
/*!**********************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue ***!
  \**********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppItem.vue?vue&type=template&id=429da85a&scoped=true& */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true&");
/* harmony import */ var _AppItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppItem.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js&");
/* harmony import */ var _AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& */ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AppItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "429da85a",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppList/AppItem.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue":
/*!***********************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue ***!
  \***********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppScore.vue?vue&type=template&id=0ecce4fc& */ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&");
/* harmony import */ var _AppScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppScore.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AppScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__.render,
  _AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppList/AppScore.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue":
/*!***************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true& */ "./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true&");
/* harmony import */ var _Markdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Markdown.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js&");
/* harmony import */ var _Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& */ "./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Markdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "11f4a1b0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Markdown.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PrefixMixin.vue":
/*!******************************************************!*\
  !*** ./apps/settings/src/components/PrefixMixin.vue ***!
  \******************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _PrefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PrefixMixin.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
var render, staticRenderFns
;



/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__["default"])(
  _PrefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"],
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PrefixMixin.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/SvgFilterMixin.vue":
/*!*********************************************************!*\
  !*** ./apps/settings/src/components/SvgFilterMixin.vue ***!
  \*********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SvgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SvgFilterMixin.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
var render, staticRenderFns
;



/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_1__["default"])(
  _SvgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"],
  render,
  staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/SvgFilterMixin.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/views/Apps.vue":
/*!******************************************!*\
  !*** ./apps/settings/src/views/Apps.vue ***!
  \******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Apps.vue?vue&type=template&id=d3714d0a&scoped=true& */ "./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true&");
/* harmony import */ var _Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Apps.vue?vue&type=script&lang=js& */ "./apps/settings/src/views/Apps.vue?vue&type=script&lang=js&");
/* harmony import */ var _Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& */ "./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "d3714d0a",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/Apps.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./node_modules/vue-material-design-icons/StarShooting.vue":
/*!*****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/StarShooting.vue ***!
  \*****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _StarShooting_vue_vue_type_template_id_1f4b38fe___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./StarShooting.vue?vue&type=template&id=1f4b38fe& */ "./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=template&id=1f4b38fe&");
/* harmony import */ var _StarShooting_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./StarShooting.vue?vue&type=script&lang=js& */ "./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=script&lang=js&");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _StarShooting_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _StarShooting_vue_vue_type_template_id_1f4b38fe___WEBPACK_IMPORTED_MODULE_0__.render,
  _StarShooting_vue_vue_type_template_id_1f4b38fe___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/StarShooting.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

/* harmony default export */ __webpack_exports__["default"] = ({
  name: "StarShootingIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js&":
/*!******************************************************************************!*\
  !*** ./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js& ***!
  \******************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetails.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList.vue?vue&type=script&lang=js&":
/*!***************************************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue?vue&type=script&lang=js& ***!
  \***************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js&":
/*!***********************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppScore.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js&":
/*!****************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js& ***!
  \****************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js&":
/*!*******************************************************************************!*\
  !*** ./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PrefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PrefixMixin.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PrefixMixin.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PrefixMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js&":
/*!**********************************************************************************!*\
  !*** ./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SvgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SvgFilterMixin.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/SvgFilterMixin.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SvgFilterMixin_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/Apps.vue?vue&type=script&lang=js&":
/*!*******************************************************************!*\
  !*** ./apps/settings/src/views/Apps.vue?vue&type=script&lang=js& ***!
  \*******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Apps.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true&":
/*!************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true& ***!
  \************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_template_id_59a92e62_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetails.vue?vue&type=template&id=59a92e62&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=template&id=59a92e62&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&":
/*!*********************************************************************************!*\
  !*** ./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4& ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppList_vue_vue_type_template_id_6d1e92a4___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppList.vue?vue&type=template&id=6d1e92a4& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList.vue?vue&type=template&id=6d1e92a4&");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true&":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true& ***!
  \*****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_template_id_429da85a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=template&id=429da85a&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=template&id=429da85a&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&":
/*!******************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc& ***!
  \******************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppScore_vue_vue_type_template_id_0ecce4fc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppScore.vue?vue&type=template&id=0ecce4fc& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppScore.vue?vue&type=template&id=0ecce4fc&");


/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true&":
/*!**********************************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true& ***!
  \**********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_template_id_11f4a1b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=template&id=11f4a1b0&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true&":
/*!*************************************************************************************!*\
  !*** ./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_template_id_d3714d0a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Apps.vue?vue&type=template&id=d3714d0a&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=template&id=d3714d0a&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&":
/*!***************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& ***!
  \***************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppDetails_vue_vue_type_style_index_0_id_59a92e62_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppDetails.vue?vue&type=style&index=0&id=59a92e62&scoped=true&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&":
/*!*************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& ***!
  \*************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Markdown_vue_vue_type_style_index_0_id_11f4a1b0_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Markdown.vue?vue&type=style&index=0&id=11f4a1b0&scoped=true&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&":
/*!****************************************************************************************************!*\
  !*** ./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& ***!
  \****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Apps_vue_vue_type_style_index_0_id_d3714d0a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Apps.vue?vue&type=style&index=0&id=d3714d0a&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&":
/*!*******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& ***!
  \*******************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AppItem_vue_vue_type_style_index_0_id_429da85a_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppList/AppItem.vue?vue&type=style&index=0&id=429da85a&scoped=true&lang=css&");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=script&lang=js&":
/*!******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_StarShooting_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./StarShooting.vue?vue&type=script&lang=js& */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_vue_loader_lib_index_js_vue_loader_options_StarShooting_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=template&id=1f4b38fe&":
/*!************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=template&id=1f4b38fe& ***!
  \************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_StarShooting_vue_vue_type_template_id_1f4b38fe___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_StarShooting_vue_vue_type_template_id_1f4b38fe___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_StarShooting_vue_vue_type_template_id_1f4b38fe___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./StarShooting.vue?vue&type=template&id=1f4b38fe& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=template&id=1f4b38fe&");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=template&id=1f4b38fe&":
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/StarShooting.vue?vue&type=template&id=1f4b38fe& ***!
  \****************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon star-shooting-icon",
        attrs: {
          "aria-hidden": !_vm.title,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M18.09 11.77L19.56 18.1L14 14.74L8.44 18.1L9.9 11.77L5 7.5L11.47 6.96L14 1L16.53 6.96L23 7.5L18.09 11.77M2 12.43C2.19 12.43 2.38 12.37 2.55 12.26L5.75 10.15L4.18 8.79L1.45 10.59C.989 10.89 .861 11.5 1.16 12C1.36 12.27 1.68 12.43 2 12.43M1.16 21.55C1.36 21.84 1.68 22 2 22C2.19 22 2.38 21.95 2.55 21.84L6.66 19.13L7 17.76L7.31 16.31L1.45 20.16C.989 20.47 .861 21.09 1.16 21.55M1.45 15.38C.989 15.68 .861 16.3 1.16 16.76C1.36 17.06 1.68 17.21 2 17.21C2.19 17.21 2.38 17.16 2.55 17.05L7.97 13.5L8.24 12.31L7.32 11.5L1.45 15.38Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/p-limit/index.js":
/*!***************************************!*\
  !*** ./node_modules/p-limit/index.js ***!
  \***************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ pLimit; }
/* harmony export */ });
/* harmony import */ var yocto_queue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! yocto-queue */ "./node_modules/yocto-queue/index.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function pLimit(concurrency) {
  if (!((Number.isInteger(concurrency) || concurrency === Number.POSITIVE_INFINITY) && concurrency > 0)) {
    throw new TypeError('Expected `concurrency` to be a number from 1 and up');
  }
  var queue = new yocto_queue__WEBPACK_IMPORTED_MODULE_0__["default"]();
  var activeCount = 0;
  var next = function next() {
    activeCount--;
    if (queue.size > 0) {
      queue.dequeue()();
    }
  };
  var run = /*#__PURE__*/function () {
    var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2(fn, resolve, args) {
      var result;
      return _regeneratorRuntime().wrap(function _callee2$(_context2) {
        while (1) switch (_context2.prev = _context2.next) {
          case 0:
            activeCount++;
            result = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
              return _regeneratorRuntime().wrap(function _callee$(_context) {
                while (1) switch (_context.prev = _context.next) {
                  case 0:
                    return _context.abrupt("return", fn.apply(void 0, _toConsumableArray(args)));
                  case 1:
                  case "end":
                    return _context.stop();
                }
              }, _callee);
            }))();
            resolve(result);
            _context2.prev = 3;
            _context2.next = 6;
            return result;
          case 6:
            _context2.next = 10;
            break;
          case 8:
            _context2.prev = 8;
            _context2.t0 = _context2["catch"](3);
          case 10:
            next();
          case 11:
          case "end":
            return _context2.stop();
        }
      }, _callee2, null, [[3, 8]]);
    }));
    return function run(_x, _x2, _x3) {
      return _ref.apply(this, arguments);
    };
  }();
  var enqueue = function enqueue(fn, resolve, args) {
    queue.enqueue(run.bind(undefined, fn, resolve, args));
    _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
      return _regeneratorRuntime().wrap(function _callee3$(_context3) {
        while (1) switch (_context3.prev = _context3.next) {
          case 0:
            _context3.next = 2;
            return Promise.resolve();
          case 2:
            if (activeCount < concurrency && queue.size > 0) {
              queue.dequeue()();
            }
          case 3:
          case "end":
            return _context3.stop();
        }
      }, _callee3);
    }))();
  };
  var generator = function generator(fn) {
    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }
    return new Promise(function (resolve) {
      enqueue(fn, resolve, args);
    });
  };
  Object.defineProperties(generator, {
    activeCount: {
      get: function get() {
        return activeCount;
      }
    },
    pendingCount: {
      get: function get() {
        return queue.size;
      }
    },
    clearQueue: {
      value: function value() {
        queue.clear();
      }
    }
  });
  return generator;
}

/***/ }),

/***/ "./node_modules/yocto-queue/index.js":
/*!*******************************************!*\
  !*** ./node_modules/yocto-queue/index.js ***!
  \*******************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Queue; }
/* harmony export */ });
var _Symbol$iterator;
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
function _classPrivateFieldSet(receiver, privateMap, value) { var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "set"); _classApplyDescriptorSet(receiver, descriptor, value); return value; }
function _classApplyDescriptorSet(receiver, descriptor, value) { if (descriptor.set) { descriptor.set.call(receiver, value); } else { if (!descriptor.writable) { throw new TypeError("attempted to set read only private field"); } descriptor.value = value; } }
function _classPrivateFieldGet(receiver, privateMap) { var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
function _classExtractFieldDescriptor(receiver, privateMap, action) { if (!privateMap.has(receiver)) { throw new TypeError("attempted to " + action + " private field on non-instance"); } return privateMap.get(receiver); }
function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
/*
How it works:
`this.#head` is an instance of `Node` which keeps track of its current value and nests another instance of `Node` that keeps the value that comes after it. When a value is provided to `.enqueue()`, the code needs to iterate through `this.#head`, going deeper and deeper to find the last value. However, iterating through every single item is slow. This problem is solved by saving a reference to the last value as `this.#tail` so that it can reference it to add a new value.
*/
var Node = /*#__PURE__*/_createClass(function Node(value) {
  _classCallCheck(this, Node);
  _defineProperty(this, "value", void 0);
  _defineProperty(this, "next", void 0);
  this.value = value;
});
var _head = /*#__PURE__*/new WeakMap();
var _tail = /*#__PURE__*/new WeakMap();
var _size = /*#__PURE__*/new WeakMap();
_Symbol$iterator = Symbol.iterator;
var Queue = /*#__PURE__*/function () {
  function Queue() {
    _classCallCheck(this, Queue);
    _classPrivateFieldInitSpec(this, _head, {
      writable: true,
      value: void 0
    });
    _classPrivateFieldInitSpec(this, _tail, {
      writable: true,
      value: void 0
    });
    _classPrivateFieldInitSpec(this, _size, {
      writable: true,
      value: void 0
    });
    this.clear();
  }
  _createClass(Queue, [{
    key: "enqueue",
    value: function enqueue(value) {
      var _this$size, _this$size2;
      var node = new Node(value);
      if (_classPrivateFieldGet(this, _head)) {
        _classPrivateFieldGet(this, _tail).next = node;
        _classPrivateFieldSet(this, _tail, node);
      } else {
        _classPrivateFieldSet(this, _head, node);
        _classPrivateFieldSet(this, _tail, node);
      }
      _classPrivateFieldSet(this, _size, (_this$size = _classPrivateFieldGet(this, _size), _this$size2 = _this$size++, _this$size)), _this$size2;
    }
  }, {
    key: "dequeue",
    value: function dequeue() {
      var _this$size3, _this$size4;
      var current = _classPrivateFieldGet(this, _head);
      if (!current) {
        return;
      }
      _classPrivateFieldSet(this, _head, _classPrivateFieldGet(this, _head).next);
      _classPrivateFieldSet(this, _size, (_this$size3 = _classPrivateFieldGet(this, _size), _this$size4 = _this$size3--, _this$size3)), _this$size4;
      return current.value;
    }
  }, {
    key: "clear",
    value: function clear() {
      _classPrivateFieldSet(this, _head, undefined);
      _classPrivateFieldSet(this, _tail, undefined);
      _classPrivateFieldSet(this, _size, 0);
    }
  }, {
    key: "size",
    get: function get() {
      return _classPrivateFieldGet(this, _size);
    }
  }, {
    key: _Symbol$iterator,
    value: /*#__PURE__*/_regeneratorRuntime().mark(function value() {
      var current;
      return _regeneratorRuntime().wrap(function value$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            current = _classPrivateFieldGet(this, _head);
          case 1:
            if (!current) {
              _context.next = 7;
              break;
            }
            _context.next = 4;
            return current.value;
          case 4:
            current = current.next;
            _context.next = 1;
            break;
          case 7:
          case "end":
            return _context.stop();
        }
      }, value, this);
    })
  }]);
  return Queue;
}();


/***/ }),

/***/ "./node_modules/marked/lib/marked.esm.js":
/*!***********************************************!*\
  !*** ./node_modules/marked/lib/marked.esm.js ***!
  \***********************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Hooks: function() { return /* binding */ _Hooks; },
/* harmony export */   Lexer: function() { return /* binding */ _Lexer; },
/* harmony export */   Marked: function() { return /* binding */ Marked; },
/* harmony export */   Parser: function() { return /* binding */ _Parser; },
/* harmony export */   Renderer: function() { return /* binding */ _Renderer; },
/* harmony export */   Slugger: function() { return /* binding */ _Slugger; },
/* harmony export */   TextRenderer: function() { return /* binding */ _TextRenderer; },
/* harmony export */   Tokenizer: function() { return /* binding */ _Tokenizer; },
/* harmony export */   defaults: function() { return /* binding */ _defaults; },
/* harmony export */   getDefaults: function() { return /* binding */ _getDefaults; },
/* harmony export */   lexer: function() { return /* binding */ lexer; },
/* harmony export */   marked: function() { return /* binding */ marked; },
/* harmony export */   options: function() { return /* binding */ options; },
/* harmony export */   parse: function() { return /* binding */ parse; },
/* harmony export */   parseInline: function() { return /* binding */ parseInline; },
/* harmony export */   parser: function() { return /* binding */ parser; },
/* harmony export */   setOptions: function() { return /* binding */ setOptions; },
/* harmony export */   use: function() { return /* binding */ use; },
/* harmony export */   walkTokens: function() { return /* binding */ walkTokens; }
/* harmony export */ });
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * marked v7.0.3 - a markdown parser
 * Copyright (c) 2011-2023, Christopher Jeffrey. (MIT Licensed)
 * https://github.com/markedjs/marked
 */

/**
 * DO NOT EDIT THIS FILE
 * The code in this file is generated from files in ./src/
 */

/**
 * Gets the original marked default options.
 */
function _getDefaults() {
    return {
        async: false,
        baseUrl: null,
        breaks: false,
        extensions: null,
        gfm: true,
        headerIds: false,
        headerPrefix: '',
        highlight: null,
        hooks: null,
        langPrefix: 'language-',
        mangle: false,
        pedantic: false,
        renderer: null,
        sanitize: false,
        sanitizer: null,
        silent: false,
        smartypants: false,
        tokenizer: null,
        walkTokens: null,
        xhtml: false
    };
}
let _defaults = _getDefaults();
function changeDefaults(newDefaults) {
    _defaults = newDefaults;
}

/**
 * Helpers
 */
const escapeTest = /[&<>"']/;
const escapeReplace = new RegExp(escapeTest.source, 'g');
const escapeTestNoEncode = /[<>"']|&(?!(#\d{1,7}|#[Xx][a-fA-F0-9]{1,6}|\w+);)/;
const escapeReplaceNoEncode = new RegExp(escapeTestNoEncode.source, 'g');
const escapeReplacements = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
};
const getEscapeReplacement = (ch) => escapeReplacements[ch];
function escape(html, encode) {
    if (encode) {
        if (escapeTest.test(html)) {
            return html.replace(escapeReplace, getEscapeReplacement);
        }
    }
    else {
        if (escapeTestNoEncode.test(html)) {
            return html.replace(escapeReplaceNoEncode, getEscapeReplacement);
        }
    }
    return html;
}
const unescapeTest = /&(#(?:\d+)|(?:#x[0-9A-Fa-f]+)|(?:\w+));?/ig;
function unescape(html) {
    // explicitly match decimal, hex, and named HTML entities
    return html.replace(unescapeTest, (_, n) => {
        n = n.toLowerCase();
        if (n === 'colon')
            return ':';
        if (n.charAt(0) === '#') {
            return n.charAt(1) === 'x'
                ? String.fromCharCode(parseInt(n.substring(2), 16))
                : String.fromCharCode(+n.substring(1));
        }
        return '';
    });
}
const caret = /(^|[^\[])\^/g;
function edit(regex, opt) {
    regex = typeof regex === 'string' ? regex : regex.source;
    opt = opt || '';
    const obj = {
        replace: (name, val) => {
            val = typeof val === 'object' && 'source' in val ? val.source : val;
            val = val.replace(caret, '$1');
            regex = regex.replace(name, val);
            return obj;
        },
        getRegex: () => {
            return new RegExp(regex, opt);
        }
    };
    return obj;
}
const nonWordAndColonTest = /[^\w:]/g;
const originIndependentUrl = /^$|^[a-z][a-z0-9+.-]*:|^[?#]/i;
function cleanUrl(sanitize, base, href) {
    if (sanitize) {
        let prot;
        try {
            prot = decodeURIComponent(unescape(href))
                .replace(nonWordAndColonTest, '')
                .toLowerCase();
        }
        catch (e) {
            return null;
        }
        if (prot.indexOf('javascript:') === 0 || prot.indexOf('vbscript:') === 0 || prot.indexOf('data:') === 0) {
            return null;
        }
    }
    if (base && !originIndependentUrl.test(href)) {
        href = resolveUrl(base, href);
    }
    try {
        href = encodeURI(href).replace(/%25/g, '%');
    }
    catch (e) {
        return null;
    }
    return href;
}
const baseUrls = {};
const justDomain = /^[^:]+:\/*[^/]*$/;
const protocol = /^([^:]+:)[\s\S]*$/;
const domain = /^([^:]+:\/*[^/]*)[\s\S]*$/;
function resolveUrl(base, href) {
    if (!baseUrls[' ' + base]) {
        // we can ignore everything in base after the last slash of its path component,
        // but we might need to add _that_
        // https://tools.ietf.org/html/rfc3986#section-3
        if (justDomain.test(base)) {
            baseUrls[' ' + base] = base + '/';
        }
        else {
            baseUrls[' ' + base] = rtrim(base, '/', true);
        }
    }
    base = baseUrls[' ' + base];
    const relativeBase = base.indexOf(':') === -1;
    if (href.substring(0, 2) === '//') {
        if (relativeBase) {
            return href;
        }
        return base.replace(protocol, '$1') + href;
    }
    else if (href.charAt(0) === '/') {
        if (relativeBase) {
            return href;
        }
        return base.replace(domain, '$1') + href;
    }
    else {
        return base + href;
    }
}
const noopTest = { exec: () => null };
function splitCells(tableRow, count) {
    // ensure that every cell-delimiting pipe has a space
    // before it to distinguish it from an escaped pipe
    const row = tableRow.replace(/\|/g, (match, offset, str) => {
        let escaped = false, curr = offset;
        while (--curr >= 0 && str[curr] === '\\')
            escaped = !escaped;
        if (escaped) {
            // odd number of slashes means | is escaped
            // so we leave it alone
            return '|';
        }
        else {
            // add space before unescaped |
            return ' |';
        }
    }), cells = row.split(/ \|/);
    let i = 0;
    // First/last cell in a row cannot be empty if it has no leading/trailing pipe
    if (!cells[0].trim()) {
        cells.shift();
    }
    if (cells.length > 0 && !cells[cells.length - 1].trim()) {
        cells.pop();
    }
    if (count) {
        if (cells.length > count) {
            cells.splice(count);
        }
        else {
            while (cells.length < count)
                cells.push('');
        }
    }
    for (; i < cells.length; i++) {
        // leading or trailing whitespace is ignored per the gfm spec
        cells[i] = cells[i].trim().replace(/\\\|/g, '|');
    }
    return cells;
}
/**
 * Remove trailing 'c's. Equivalent to str.replace(/c*$/, '').
 * /c*$/ is vulnerable to REDOS.
 *
 * @param str
 * @param c
 * @param invert Remove suffix of non-c chars instead. Default falsey.
 */
function rtrim(str, c, invert) {
    const l = str.length;
    if (l === 0) {
        return '';
    }
    // Length of suffix matching the invert condition.
    let suffLen = 0;
    // Step left until we fail to match the invert condition.
    while (suffLen < l) {
        const currChar = str.charAt(l - suffLen - 1);
        if (currChar === c && !invert) {
            suffLen++;
        }
        else if (currChar !== c && invert) {
            suffLen++;
        }
        else {
            break;
        }
    }
    return str.slice(0, l - suffLen);
}
function findClosingBracket(str, b) {
    if (str.indexOf(b[1]) === -1) {
        return -1;
    }
    const l = str.length;
    let level = 0, i = 0;
    for (; i < l; i++) {
        if (str[i] === '\\') {
            i++;
        }
        else if (str[i] === b[0]) {
            level++;
        }
        else if (str[i] === b[1]) {
            level--;
            if (level < 0) {
                return i;
            }
        }
    }
    return -1;
}
function checkDeprecations(opt, callback) {
    if (!opt || opt.silent) {
        return;
    }
    if (callback) {
        console.warn('marked(): callback is deprecated since version 5.0.0, should not be used and will be removed in the future. Read more here: https://marked.js.org/using_pro#async');
    }
    if (opt.sanitize || opt.sanitizer) {
        console.warn('marked(): sanitize and sanitizer parameters are deprecated since version 0.7.0, should not be used and will be removed in the future. Read more here: https://marked.js.org/#/USING_ADVANCED.md#options');
    }
    if (opt.highlight || opt.langPrefix !== 'language-') {
        console.warn('marked(): highlight and langPrefix parameters are deprecated since version 5.0.0, should not be used and will be removed in the future. Instead use https://www.npmjs.com/package/marked-highlight.');
    }
    if (opt.mangle) {
        console.warn('marked(): mangle parameter is enabled by default, but is deprecated since version 5.0.0, and will be removed in the future. To clear this warning, install https://www.npmjs.com/package/marked-mangle, or disable by setting `{mangle: false}`.');
    }
    if (opt.baseUrl) {
        console.warn('marked(): baseUrl parameter is deprecated since version 5.0.0, should not be used and will be removed in the future. Instead use https://www.npmjs.com/package/marked-base-url.');
    }
    if (opt.smartypants) {
        console.warn('marked(): smartypants parameter is deprecated since version 5.0.0, should not be used and will be removed in the future. Instead use https://www.npmjs.com/package/marked-smartypants.');
    }
    if (opt.xhtml) {
        console.warn('marked(): xhtml parameter is deprecated since version 5.0.0, should not be used and will be removed in the future. Instead use https://www.npmjs.com/package/marked-xhtml.');
    }
    if (opt.headerIds || opt.headerPrefix) {
        console.warn('marked(): headerIds and headerPrefix parameters enabled by default, but are deprecated since version 5.0.0, and will be removed in the future. To clear this warning, install  https://www.npmjs.com/package/marked-gfm-heading-id, or disable by setting `{headerIds: false}`.');
    }
}

function outputLink(cap, link, raw, lexer) {
    const href = link.href;
    const title = link.title ? escape(link.title) : null;
    const text = cap[1].replace(/\\([\[\]])/g, '$1');
    if (cap[0].charAt(0) !== '!') {
        lexer.state.inLink = true;
        const token = {
            type: 'link',
            raw,
            href,
            title,
            text,
            tokens: lexer.inlineTokens(text)
        };
        lexer.state.inLink = false;
        return token;
    }
    return {
        type: 'image',
        raw,
        href,
        title,
        text: escape(text)
    };
}
function indentCodeCompensation(raw, text) {
    const matchIndentToCode = raw.match(/^(\s+)(?:```)/);
    if (matchIndentToCode === null) {
        return text;
    }
    const indentToCode = matchIndentToCode[1];
    return text
        .split('\n')
        .map(node => {
        const matchIndentInNode = node.match(/^\s+/);
        if (matchIndentInNode === null) {
            return node;
        }
        const [indentInNode] = matchIndentInNode;
        if (indentInNode.length >= indentToCode.length) {
            return node.slice(indentToCode.length);
        }
        return node;
    })
        .join('\n');
}
/**
 * Tokenizer
 */
class _Tokenizer {
    options;
    rules;
    lexer;
    constructor(options) {
        this.options = options || _defaults;
    }
    space(src) {
        const cap = this.rules.block.newline.exec(src);
        if (cap && cap[0].length > 0) {
            return {
                type: 'space',
                raw: cap[0]
            };
        }
    }
    code(src) {
        const cap = this.rules.block.code.exec(src);
        if (cap) {
            const text = cap[0].replace(/^ {1,4}/gm, '');
            return {
                type: 'code',
                raw: cap[0],
                codeBlockStyle: 'indented',
                text: !this.options.pedantic
                    ? rtrim(text, '\n')
                    : text
            };
        }
    }
    fences(src) {
        const cap = this.rules.block.fences.exec(src);
        if (cap) {
            const raw = cap[0];
            const text = indentCodeCompensation(raw, cap[3] || '');
            return {
                type: 'code',
                raw,
                lang: cap[2] ? cap[2].trim().replace(this.rules.inline._escapes, '$1') : cap[2],
                text
            };
        }
    }
    heading(src) {
        const cap = this.rules.block.heading.exec(src);
        if (cap) {
            let text = cap[2].trim();
            // remove trailing #s
            if (/#$/.test(text)) {
                const trimmed = rtrim(text, '#');
                if (this.options.pedantic) {
                    text = trimmed.trim();
                }
                else if (!trimmed || / $/.test(trimmed)) {
                    // CommonMark requires space before trailing #s
                    text = trimmed.trim();
                }
            }
            return {
                type: 'heading',
                raw: cap[0],
                depth: cap[1].length,
                text,
                tokens: this.lexer.inline(text)
            };
        }
    }
    hr(src) {
        const cap = this.rules.block.hr.exec(src);
        if (cap) {
            return {
                type: 'hr',
                raw: cap[0]
            };
        }
    }
    blockquote(src) {
        const cap = this.rules.block.blockquote.exec(src);
        if (cap) {
            const text = cap[0].replace(/^ *>[ \t]?/gm, '');
            const top = this.lexer.state.top;
            this.lexer.state.top = true;
            const tokens = this.lexer.blockTokens(text);
            this.lexer.state.top = top;
            return {
                type: 'blockquote',
                raw: cap[0],
                tokens,
                text
            };
        }
    }
    list(src) {
        let cap = this.rules.block.list.exec(src);
        if (cap) {
            let raw, istask, ischecked, indent, i, blankLine, endsWithBlankLine, line, nextLine, rawLine, itemContents, endEarly;
            let bull = cap[1].trim();
            const isordered = bull.length > 1;
            const list = {
                type: 'list',
                raw: '',
                ordered: isordered,
                start: isordered ? +bull.slice(0, -1) : '',
                loose: false,
                items: []
            };
            bull = isordered ? `\\d{1,9}\\${bull.slice(-1)}` : `\\${bull}`;
            if (this.options.pedantic) {
                bull = isordered ? bull : '[*+-]';
            }
            // Get next list item
            const itemRegex = new RegExp(`^( {0,3}${bull})((?:[\t ][^\\n]*)?(?:\\n|$))`);
            // Check if current bullet point can start a new List Item
            while (src) {
                endEarly = false;
                if (!(cap = itemRegex.exec(src))) {
                    break;
                }
                if (this.rules.block.hr.test(src)) { // End list if bullet was actually HR (possibly move into itemRegex?)
                    break;
                }
                raw = cap[0];
                src = src.substring(raw.length);
                line = cap[2].split('\n', 1)[0].replace(/^\t+/, (t) => ' '.repeat(3 * t.length));
                nextLine = src.split('\n', 1)[0];
                if (this.options.pedantic) {
                    indent = 2;
                    itemContents = line.trimLeft();
                }
                else {
                    indent = cap[2].search(/[^ ]/); // Find first non-space char
                    indent = indent > 4 ? 1 : indent; // Treat indented code blocks (> 4 spaces) as having only 1 indent
                    itemContents = line.slice(indent);
                    indent += cap[1].length;
                }
                blankLine = false;
                if (!line && /^ *$/.test(nextLine)) { // Items begin with at most one blank line
                    raw += nextLine + '\n';
                    src = src.substring(nextLine.length + 1);
                    endEarly = true;
                }
                if (!endEarly) {
                    const nextBulletRegex = new RegExp(`^ {0,${Math.min(3, indent - 1)}}(?:[*+-]|\\d{1,9}[.)])((?:[ \t][^\\n]*)?(?:\\n|$))`);
                    const hrRegex = new RegExp(`^ {0,${Math.min(3, indent - 1)}}((?:- *){3,}|(?:_ *){3,}|(?:\\* *){3,})(?:\\n+|$)`);
                    const fencesBeginRegex = new RegExp(`^ {0,${Math.min(3, indent - 1)}}(?:\`\`\`|~~~)`);
                    const headingBeginRegex = new RegExp(`^ {0,${Math.min(3, indent - 1)}}#`);
                    // Check if following lines should be included in List Item
                    while (src) {
                        rawLine = src.split('\n', 1)[0];
                        nextLine = rawLine;
                        // Re-align to follow commonmark nesting rules
                        if (this.options.pedantic) {
                            nextLine = nextLine.replace(/^ {1,4}(?=( {4})*[^ ])/g, '  ');
                        }
                        // End list item if found code fences
                        if (fencesBeginRegex.test(nextLine)) {
                            break;
                        }
                        // End list item if found start of new heading
                        if (headingBeginRegex.test(nextLine)) {
                            break;
                        }
                        // End list item if found start of new bullet
                        if (nextBulletRegex.test(nextLine)) {
                            break;
                        }
                        // Horizontal rule found
                        if (hrRegex.test(src)) {
                            break;
                        }
                        if (nextLine.search(/[^ ]/) >= indent || !nextLine.trim()) { // Dedent if possible
                            itemContents += '\n' + nextLine.slice(indent);
                        }
                        else {
                            // not enough indentation
                            if (blankLine) {
                                break;
                            }
                            // paragraph continuation unless last line was a different block level element
                            if (line.search(/[^ ]/) >= 4) { // indented code block
                                break;
                            }
                            if (fencesBeginRegex.test(line)) {
                                break;
                            }
                            if (headingBeginRegex.test(line)) {
                                break;
                            }
                            if (hrRegex.test(line)) {
                                break;
                            }
                            itemContents += '\n' + nextLine;
                        }
                        if (!blankLine && !nextLine.trim()) { // Check if current line is blank
                            blankLine = true;
                        }
                        raw += rawLine + '\n';
                        src = src.substring(rawLine.length + 1);
                        line = nextLine.slice(indent);
                    }
                }
                if (!list.loose) {
                    // If the previous item ended with a blank line, the list is loose
                    if (endsWithBlankLine) {
                        list.loose = true;
                    }
                    else if (/\n *\n *$/.test(raw)) {
                        endsWithBlankLine = true;
                    }
                }
                // Check for task list items
                if (this.options.gfm) {
                    istask = /^\[[ xX]\] /.exec(itemContents);
                    if (istask) {
                        ischecked = istask[0] !== '[ ] ';
                        itemContents = itemContents.replace(/^\[[ xX]\] +/, '');
                    }
                }
                list.items.push({
                    type: 'list_item',
                    raw,
                    task: !!istask,
                    checked: ischecked,
                    loose: false,
                    text: itemContents
                });
                list.raw += raw;
            }
            // Do not consume newlines at end of final item. Alternatively, make itemRegex *start* with any newlines to simplify/speed up endsWithBlankLine logic
            list.items[list.items.length - 1].raw = raw.trimRight();
            list.items[list.items.length - 1].text = itemContents.trimRight();
            list.raw = list.raw.trimRight();
            const l = list.items.length;
            // Item child tokens handled here at end because we needed to have the final item to trim it first
            for (i = 0; i < l; i++) {
                this.lexer.state.top = false;
                list.items[i].tokens = this.lexer.blockTokens(list.items[i].text, []);
                if (!list.loose) {
                    // Check if list should be loose
                    const spacers = list.items[i].tokens.filter(t => t.type === 'space');
                    const hasMultipleLineBreaks = spacers.length > 0 && spacers.some(t => /\n.*\n/.test(t.raw));
                    list.loose = hasMultipleLineBreaks;
                }
            }
            // Set all items to loose if list is loose
            if (list.loose) {
                for (i = 0; i < l; i++) {
                    list.items[i].loose = true;
                }
            }
            return list;
        }
    }
    html(src) {
        const cap = this.rules.block.html.exec(src);
        if (cap) {
            const token = {
                type: 'html',
                block: true,
                raw: cap[0],
                pre: !this.options.sanitizer
                    && (cap[1] === 'pre' || cap[1] === 'script' || cap[1] === 'style'),
                text: cap[0]
            };
            if (this.options.sanitize) {
                const text = this.options.sanitizer ? this.options.sanitizer(cap[0]) : escape(cap[0]);
                const paragraph = token;
                paragraph.type = 'paragraph';
                paragraph.text = text;
                paragraph.tokens = this.lexer.inline(text);
            }
            return token;
        }
    }
    def(src) {
        const cap = this.rules.block.def.exec(src);
        if (cap) {
            const tag = cap[1].toLowerCase().replace(/\s+/g, ' ');
            const href = cap[2] ? cap[2].replace(/^<(.*)>$/, '$1').replace(this.rules.inline._escapes, '$1') : '';
            const title = cap[3] ? cap[3].substring(1, cap[3].length - 1).replace(this.rules.inline._escapes, '$1') : cap[3];
            return {
                type: 'def',
                tag,
                raw: cap[0],
                href,
                title
            };
        }
    }
    table(src) {
        const cap = this.rules.block.table.exec(src);
        if (cap) {
            const item = {
                type: 'table',
                raw: cap[0],
                header: splitCells(cap[1]).map(c => {
                    return { text: c };
                }),
                align: cap[2].replace(/^ *|\| *$/g, '').split(/ *\| */),
                rows: cap[3] && cap[3].trim() ? cap[3].replace(/\n[ \t]*$/, '').split('\n') : []
            };
            if (item.header.length === item.align.length) {
                let l = item.align.length;
                let i, j, k, row;
                for (i = 0; i < l; i++) {
                    if (/^ *-+: *$/.test(item.align[i])) {
                        item.align[i] = 'right';
                    }
                    else if (/^ *:-+: *$/.test(item.align[i])) {
                        item.align[i] = 'center';
                    }
                    else if (/^ *:-+ *$/.test(item.align[i])) {
                        item.align[i] = 'left';
                    }
                    else {
                        item.align[i] = null;
                    }
                }
                l = item.rows.length;
                for (i = 0; i < l; i++) {
                    item.rows[i] = splitCells(item.rows[i], item.header.length).map(c => {
                        return { text: c };
                    });
                }
                // parse child tokens inside headers and cells
                // header child tokens
                l = item.header.length;
                for (j = 0; j < l; j++) {
                    item.header[j].tokens = this.lexer.inline(item.header[j].text);
                }
                // cell child tokens
                l = item.rows.length;
                for (j = 0; j < l; j++) {
                    row = item.rows[j];
                    for (k = 0; k < row.length; k++) {
                        row[k].tokens = this.lexer.inline(row[k].text);
                    }
                }
                return item;
            }
        }
    }
    lheading(src) {
        const cap = this.rules.block.lheading.exec(src);
        if (cap) {
            return {
                type: 'heading',
                raw: cap[0],
                depth: cap[2].charAt(0) === '=' ? 1 : 2,
                text: cap[1],
                tokens: this.lexer.inline(cap[1])
            };
        }
    }
    paragraph(src) {
        const cap = this.rules.block.paragraph.exec(src);
        if (cap) {
            const text = cap[1].charAt(cap[1].length - 1) === '\n'
                ? cap[1].slice(0, -1)
                : cap[1];
            return {
                type: 'paragraph',
                raw: cap[0],
                text,
                tokens: this.lexer.inline(text)
            };
        }
    }
    text(src) {
        const cap = this.rules.block.text.exec(src);
        if (cap) {
            return {
                type: 'text',
                raw: cap[0],
                text: cap[0],
                tokens: this.lexer.inline(cap[0])
            };
        }
    }
    escape(src) {
        const cap = this.rules.inline.escape.exec(src);
        if (cap) {
            return {
                type: 'escape',
                raw: cap[0],
                text: escape(cap[1])
            };
        }
    }
    tag(src) {
        const cap = this.rules.inline.tag.exec(src);
        if (cap) {
            if (!this.lexer.state.inLink && /^<a /i.test(cap[0])) {
                this.lexer.state.inLink = true;
            }
            else if (this.lexer.state.inLink && /^<\/a>/i.test(cap[0])) {
                this.lexer.state.inLink = false;
            }
            if (!this.lexer.state.inRawBlock && /^<(pre|code|kbd|script)(\s|>)/i.test(cap[0])) {
                this.lexer.state.inRawBlock = true;
            }
            else if (this.lexer.state.inRawBlock && /^<\/(pre|code|kbd|script)(\s|>)/i.test(cap[0])) {
                this.lexer.state.inRawBlock = false;
            }
            return {
                type: this.options.sanitize
                    ? 'text'
                    : 'html',
                raw: cap[0],
                inLink: this.lexer.state.inLink,
                inRawBlock: this.lexer.state.inRawBlock,
                block: false,
                text: this.options.sanitize
                    ? (this.options.sanitizer
                        ? this.options.sanitizer(cap[0])
                        : escape(cap[0]))
                    : cap[0]
            };
        }
    }
    link(src) {
        const cap = this.rules.inline.link.exec(src);
        if (cap) {
            const trimmedUrl = cap[2].trim();
            if (!this.options.pedantic && /^</.test(trimmedUrl)) {
                // commonmark requires matching angle brackets
                if (!(/>$/.test(trimmedUrl))) {
                    return;
                }
                // ending angle bracket cannot be escaped
                const rtrimSlash = rtrim(trimmedUrl.slice(0, -1), '\\');
                if ((trimmedUrl.length - rtrimSlash.length) % 2 === 0) {
                    return;
                }
            }
            else {
                // find closing parenthesis
                const lastParenIndex = findClosingBracket(cap[2], '()');
                if (lastParenIndex > -1) {
                    const start = cap[0].indexOf('!') === 0 ? 5 : 4;
                    const linkLen = start + cap[1].length + lastParenIndex;
                    cap[2] = cap[2].substring(0, lastParenIndex);
                    cap[0] = cap[0].substring(0, linkLen).trim();
                    cap[3] = '';
                }
            }
            let href = cap[2];
            let title = '';
            if (this.options.pedantic) {
                // split pedantic href and title
                const link = /^([^'"]*[^\s])\s+(['"])(.*)\2/.exec(href);
                if (link) {
                    href = link[1];
                    title = link[3];
                }
            }
            else {
                title = cap[3] ? cap[3].slice(1, -1) : '';
            }
            href = href.trim();
            if (/^</.test(href)) {
                if (this.options.pedantic && !(/>$/.test(trimmedUrl))) {
                    // pedantic allows starting angle bracket without ending angle bracket
                    href = href.slice(1);
                }
                else {
                    href = href.slice(1, -1);
                }
            }
            return outputLink(cap, {
                href: href ? href.replace(this.rules.inline._escapes, '$1') : href,
                title: title ? title.replace(this.rules.inline._escapes, '$1') : title
            }, cap[0], this.lexer);
        }
    }
    reflink(src, links) {
        let cap;
        if ((cap = this.rules.inline.reflink.exec(src))
            || (cap = this.rules.inline.nolink.exec(src))) {
            let link = (cap[2] || cap[1]).replace(/\s+/g, ' ');
            link = links[link.toLowerCase()];
            if (!link) {
                const text = cap[0].charAt(0);
                return {
                    type: 'text',
                    raw: text,
                    text
                };
            }
            return outputLink(cap, link, cap[0], this.lexer);
        }
    }
    emStrong(src, maskedSrc, prevChar = '') {
        let match = this.rules.inline.emStrong.lDelim.exec(src);
        if (!match)
            return;
        // _ can't be between two alphanumerics. \p{L}\p{N} includes non-english alphabet/numbers as well
        if (match[3] && prevChar.match(/[\p{L}\p{N}]/u))
            return;
        const nextChar = match[1] || match[2] || '';
        if (!nextChar || !prevChar || this.rules.inline.punctuation.exec(prevChar)) {
            // unicode Regex counts emoji as 1 char; spread into array for proper count (used multiple times below)
            const lLength = [...match[0]].length - 1;
            let rDelim, rLength, delimTotal = lLength, midDelimTotal = 0;
            const endReg = match[0][0] === '*' ? this.rules.inline.emStrong.rDelimAst : this.rules.inline.emStrong.rDelimUnd;
            endReg.lastIndex = 0;
            // Clip maskedSrc to same section of string as src (move to lexer?)
            maskedSrc = maskedSrc.slice(-1 * src.length + lLength);
            while ((match = endReg.exec(maskedSrc)) != null) {
                rDelim = match[1] || match[2] || match[3] || match[4] || match[5] || match[6];
                if (!rDelim)
                    continue; // skip single * in __abc*abc__
                rLength = [...rDelim].length;
                if (match[3] || match[4]) { // found another Left Delim
                    delimTotal += rLength;
                    continue;
                }
                else if (match[5] || match[6]) { // either Left or Right Delim
                    if (lLength % 3 && !((lLength + rLength) % 3)) {
                        midDelimTotal += rLength;
                        continue; // CommonMark Emphasis Rules 9-10
                    }
                }
                delimTotal -= rLength;
                if (delimTotal > 0)
                    continue; // Haven't found enough closing delimiters
                // Remove extra characters. *a*** -> *a*
                rLength = Math.min(rLength, rLength + delimTotal + midDelimTotal);
                const raw = [...src].slice(0, lLength + match.index + rLength + 1).join('');
                // Create `em` if smallest delimiter has odd char count. *a***
                if (Math.min(lLength, rLength) % 2) {
                    const text = raw.slice(1, -1);
                    return {
                        type: 'em',
                        raw,
                        text,
                        tokens: this.lexer.inlineTokens(text)
                    };
                }
                // Create 'strong' if smallest delimiter has even char count. **a***
                const text = raw.slice(2, -2);
                return {
                    type: 'strong',
                    raw,
                    text,
                    tokens: this.lexer.inlineTokens(text)
                };
            }
        }
    }
    codespan(src) {
        const cap = this.rules.inline.code.exec(src);
        if (cap) {
            let text = cap[2].replace(/\n/g, ' ');
            const hasNonSpaceChars = /[^ ]/.test(text);
            const hasSpaceCharsOnBothEnds = /^ /.test(text) && / $/.test(text);
            if (hasNonSpaceChars && hasSpaceCharsOnBothEnds) {
                text = text.substring(1, text.length - 1);
            }
            text = escape(text, true);
            return {
                type: 'codespan',
                raw: cap[0],
                text
            };
        }
    }
    br(src) {
        const cap = this.rules.inline.br.exec(src);
        if (cap) {
            return {
                type: 'br',
                raw: cap[0]
            };
        }
    }
    del(src) {
        const cap = this.rules.inline.del.exec(src);
        if (cap) {
            return {
                type: 'del',
                raw: cap[0],
                text: cap[2],
                tokens: this.lexer.inlineTokens(cap[2])
            };
        }
    }
    autolink(src, mangle) {
        const cap = this.rules.inline.autolink.exec(src);
        if (cap) {
            let text, href;
            if (cap[2] === '@') {
                text = escape(this.options.mangle ? mangle(cap[1]) : cap[1]);
                href = 'mailto:' + text;
            }
            else {
                text = escape(cap[1]);
                href = text;
            }
            return {
                type: 'link',
                raw: cap[0],
                text,
                href,
                tokens: [
                    {
                        type: 'text',
                        raw: text,
                        text
                    }
                ]
            };
        }
    }
    url(src, mangle) {
        let cap;
        if (cap = this.rules.inline.url.exec(src)) {
            let text, href;
            if (cap[2] === '@') {
                text = escape(this.options.mangle ? mangle(cap[0]) : cap[0]);
                href = 'mailto:' + text;
            }
            else {
                // do extended autolink path validation
                let prevCapZero;
                do {
                    prevCapZero = cap[0];
                    cap[0] = this.rules.inline._backpedal.exec(cap[0])[0];
                } while (prevCapZero !== cap[0]);
                text = escape(cap[0]);
                if (cap[1] === 'www.') {
                    href = 'http://' + cap[0];
                }
                else {
                    href = cap[0];
                }
            }
            return {
                type: 'link',
                raw: cap[0],
                text,
                href,
                tokens: [
                    {
                        type: 'text',
                        raw: text,
                        text
                    }
                ]
            };
        }
    }
    inlineText(src, smartypants) {
        const cap = this.rules.inline.text.exec(src);
        if (cap) {
            let text;
            if (this.lexer.state.inRawBlock) {
                text = this.options.sanitize ? (this.options.sanitizer ? this.options.sanitizer(cap[0]) : escape(cap[0])) : cap[0];
            }
            else {
                text = escape(this.options.smartypants ? smartypants(cap[0]) : cap[0]);
            }
            return {
                type: 'text',
                raw: cap[0],
                text
            };
        }
    }
}

/**
 * Block-Level Grammar
 */
// Not all rules are defined in the object literal
// @ts-expect-error
const block = {
    newline: /^(?: *(?:\n|$))+/,
    code: /^( {4}[^\n]+(?:\n(?: *(?:\n|$))*)?)+/,
    fences: /^ {0,3}(`{3,}(?=[^`\n]*(?:\n|$))|~{3,})([^\n]*)(?:\n|$)(?:|([\s\S]*?)(?:\n|$))(?: {0,3}\1[~`]* *(?=\n|$)|$)/,
    hr: /^ {0,3}((?:-[\t ]*){3,}|(?:_[ \t]*){3,}|(?:\*[ \t]*){3,})(?:\n+|$)/,
    heading: /^ {0,3}(#{1,6})(?=\s|$)(.*)(?:\n+|$)/,
    blockquote: /^( {0,3}> ?(paragraph|[^\n]*)(?:\n|$))+/,
    list: /^( {0,3}bull)([ \t][^\n]+?)?(?:\n|$)/,
    html: '^ {0,3}(?:' // optional indentation
        + '<(script|pre|style|textarea)[\\s>][\\s\\S]*?(?:</\\1>[^\\n]*\\n+|$)' // (1)
        + '|comment[^\\n]*(\\n+|$)' // (2)
        + '|<\\?[\\s\\S]*?(?:\\?>\\n*|$)' // (3)
        + '|<![A-Z][\\s\\S]*?(?:>\\n*|$)' // (4)
        + '|<!\\[CDATA\\[[\\s\\S]*?(?:\\]\\]>\\n*|$)' // (5)
        + '|</?(tag)(?: +|\\n|/?>)[\\s\\S]*?(?:(?:\\n *)+\\n|$)' // (6)
        + '|<(?!script|pre|style|textarea)([a-z][\\w-]*)(?:attribute)*? */?>(?=[ \\t]*(?:\\n|$))[\\s\\S]*?(?:(?:\\n *)+\\n|$)' // (7) open tag
        + '|</(?!script|pre|style|textarea)[a-z][\\w-]*\\s*>(?=[ \\t]*(?:\\n|$))[\\s\\S]*?(?:(?:\\n *)+\\n|$)' // (7) closing tag
        + ')',
    def: /^ {0,3}\[(label)\]: *(?:\n *)?([^<\s][^\s]*|<.*?>)(?:(?: +(?:\n *)?| *\n *)(title))? *(?:\n+|$)/,
    table: noopTest,
    lheading: /^((?:(?!^bull ).|\n(?!\n|bull ))+?)\n {0,3}(=+|-+) *(?:\n+|$)/,
    // regex template, placeholders will be replaced according to different paragraph
    // interruption rules of commonmark and the original markdown spec:
    _paragraph: /^([^\n]+(?:\n(?!hr|heading|lheading|blockquote|fences|list|html|table| +\n)[^\n]+)*)/,
    text: /^[^\n]+/
};
block._label = /(?!\s*\])(?:\\.|[^\[\]\\])+/;
block._title = /(?:"(?:\\"?|[^"\\])*"|'[^'\n]*(?:\n[^'\n]+)*\n?'|\([^()]*\))/;
block.def = edit(block.def)
    .replace('label', block._label)
    .replace('title', block._title)
    .getRegex();
block.bullet = /(?:[*+-]|\d{1,9}[.)])/;
block.listItemStart = edit(/^( *)(bull) */)
    .replace('bull', block.bullet)
    .getRegex();
block.list = edit(block.list)
    .replace(/bull/g, block.bullet)
    .replace('hr', '\\n+(?=\\1?(?:(?:- *){3,}|(?:_ *){3,}|(?:\\* *){3,})(?:\\n+|$))')
    .replace('def', '\\n+(?=' + block.def.source + ')')
    .getRegex();
block._tag = 'address|article|aside|base|basefont|blockquote|body|caption'
    + '|center|col|colgroup|dd|details|dialog|dir|div|dl|dt|fieldset|figcaption'
    + '|figure|footer|form|frame|frameset|h[1-6]|head|header|hr|html|iframe'
    + '|legend|li|link|main|menu|menuitem|meta|nav|noframes|ol|optgroup|option'
    + '|p|param|section|source|summary|table|tbody|td|tfoot|th|thead|title|tr'
    + '|track|ul';
block._comment = /<!--(?!-?>)[\s\S]*?(?:-->|$)/;
block.html = edit(block.html, 'i')
    .replace('comment', block._comment)
    .replace('tag', block._tag)
    .replace('attribute', / +[a-zA-Z:_][\w.:-]*(?: *= *"[^"\n]*"| *= *'[^'\n]*'| *= *[^\s"'=<>`]+)?/)
    .getRegex();
block.lheading = edit(block.lheading)
    .replace(/bull/g, block.bullet) // lists can interrupt
    .getRegex();
block.paragraph = edit(block._paragraph)
    .replace('hr', block.hr)
    .replace('heading', ' {0,3}#{1,6} ')
    .replace('|lheading', '') // setex headings don't interrupt commonmark paragraphs
    .replace('|table', '')
    .replace('blockquote', ' {0,3}>')
    .replace('fences', ' {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n')
    .replace('list', ' {0,3}(?:[*+-]|1[.)]) ') // only lists starting from 1 can interrupt
    .replace('html', '</?(?:tag)(?: +|\\n|/?>)|<(?:script|pre|style|textarea|!--)')
    .replace('tag', block._tag) // pars can be interrupted by type (6) html blocks
    .getRegex();
block.blockquote = edit(block.blockquote)
    .replace('paragraph', block.paragraph)
    .getRegex();
/**
 * Normal Block Grammar
 */
block.normal = { ...block };
/**
 * GFM Block Grammar
 */
block.gfm = {
    ...block.normal,
    table: '^ *([^\\n ].*\\|.*)\\n' // Header
        + ' {0,3}(?:\\| *)?(:?-+:? *(?:\\| *:?-+:? *)*)(?:\\| *)?' // Align
        + '(?:\\n((?:(?! *\\n|hr|heading|blockquote|code|fences|list|html).*(?:\\n|$))*)\\n*|$)' // Cells
};
block.gfm.table = edit(block.gfm.table)
    .replace('hr', block.hr)
    .replace('heading', ' {0,3}#{1,6} ')
    .replace('blockquote', ' {0,3}>')
    .replace('code', ' {4}[^\\n]')
    .replace('fences', ' {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n')
    .replace('list', ' {0,3}(?:[*+-]|1[.)]) ') // only lists starting from 1 can interrupt
    .replace('html', '</?(?:tag)(?: +|\\n|/?>)|<(?:script|pre|style|textarea|!--)')
    .replace('tag', block._tag) // tables can be interrupted by type (6) html blocks
    .getRegex();
block.gfm.paragraph = edit(block._paragraph)
    .replace('hr', block.hr)
    .replace('heading', ' {0,3}#{1,6} ')
    .replace('|lheading', '') // setex headings don't interrupt commonmark paragraphs
    .replace('table', block.gfm.table) // interrupt paragraphs with table
    .replace('blockquote', ' {0,3}>')
    .replace('fences', ' {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n')
    .replace('list', ' {0,3}(?:[*+-]|1[.)]) ') // only lists starting from 1 can interrupt
    .replace('html', '</?(?:tag)(?: +|\\n|/?>)|<(?:script|pre|style|textarea|!--)')
    .replace('tag', block._tag) // pars can be interrupted by type (6) html blocks
    .getRegex();
/**
 * Pedantic grammar (original John Gruber's loose markdown specification)
 */
block.pedantic = {
    ...block.normal,
    html: edit('^ *(?:comment *(?:\\n|\\s*$)'
        + '|<(tag)[\\s\\S]+?</\\1> *(?:\\n{2,}|\\s*$)' // closed tag
        + '|<tag(?:"[^"]*"|\'[^\']*\'|\\s[^\'"/>\\s]*)*?/?> *(?:\\n{2,}|\\s*$))')
        .replace('comment', block._comment)
        .replace(/tag/g, '(?!(?:'
        + 'a|em|strong|small|s|cite|q|dfn|abbr|data|time|code|var|samp|kbd|sub'
        + '|sup|i|b|u|mark|ruby|rt|rp|bdi|bdo|span|br|wbr|ins|del|img)'
        + '\\b)\\w+(?!:|[^\\w\\s@]*@)\\b')
        .getRegex(),
    def: /^ *\[([^\]]+)\]: *<?([^\s>]+)>?(?: +(["(][^\n]+[")]))? *(?:\n+|$)/,
    heading: /^(#{1,6})(.*)(?:\n+|$)/,
    fences: noopTest,
    lheading: /^(.+?)\n {0,3}(=+|-+) *(?:\n+|$)/,
    paragraph: edit(block.normal._paragraph)
        .replace('hr', block.hr)
        .replace('heading', ' *#{1,6} *[^\n]')
        .replace('lheading', block.lheading)
        .replace('blockquote', ' {0,3}>')
        .replace('|fences', '')
        .replace('|list', '')
        .replace('|html', '')
        .getRegex()
};
/**
 * Inline-Level Grammar
 */
// Not all rules are defined in the object literal
// @ts-expect-error
const inline = {
    escape: /^\\([!"#$%&'()*+,\-./:;<=>?@\[\]\\^_`{|}~])/,
    autolink: /^<(scheme:[^\s\x00-\x1f<>]*|email)>/,
    url: noopTest,
    tag: '^comment'
        + '|^</[a-zA-Z][\\w:-]*\\s*>' // self-closing tag
        + '|^<[a-zA-Z][\\w-]*(?:attribute)*?\\s*/?>' // open tag
        + '|^<\\?[\\s\\S]*?\\?>' // processing instruction, e.g. <?php ?>
        + '|^<![a-zA-Z]+\\s[\\s\\S]*?>' // declaration, e.g. <!DOCTYPE html>
        + '|^<!\\[CDATA\\[[\\s\\S]*?\\]\\]>',
    link: /^!?\[(label)\]\(\s*(href)(?:\s+(title))?\s*\)/,
    reflink: /^!?\[(label)\]\[(ref)\]/,
    nolink: /^!?\[(ref)\](?:\[\])?/,
    reflinkSearch: 'reflink|nolink(?!\\()',
    emStrong: {
        lDelim: /^(?:\*+(?:((?!\*)[punct])|[^\s*]))|^_+(?:((?!_)[punct])|([^\s_]))/,
        //         (1) and (2) can only be a Right Delimiter. (3) and (4) can only be Left.  (5) and (6) can be either Left or Right.
        //         | Skip orphan inside strong      | Consume to delim | (1) #***              | (2) a***#, a***                    | (3) #***a, ***a                  | (4) ***#                 | (5) #***#                         | (6) a***a
        rDelimAst: /^[^_*]*?__[^_*]*?\*[^_*]*?(?=__)|[^*]+(?=[^*])|(?!\*)[punct](\*+)(?=[\s]|$)|[^punct\s](\*+)(?!\*)(?=[punct\s]|$)|(?!\*)[punct\s](\*+)(?=[^punct\s])|[\s](\*+)(?!\*)(?=[punct])|(?!\*)[punct](\*+)(?!\*)(?=[punct])|[^punct\s](\*+)(?=[^punct\s])/,
        rDelimUnd: /^[^_*]*?\*\*[^_*]*?_[^_*]*?(?=\*\*)|[^_]+(?=[^_])|(?!_)[punct](_+)(?=[\s]|$)|[^punct\s](_+)(?!_)(?=[punct\s]|$)|(?!_)[punct\s](_+)(?=[^punct\s])|[\s](_+)(?!_)(?=[punct])|(?!_)[punct](_+)(?!_)(?=[punct])/ // ^- Not allowed for _
    },
    code: /^(`+)([^`]|[^`][\s\S]*?[^`])\1(?!`)/,
    br: /^( {2,}|\\)\n(?!\s*$)/,
    del: noopTest,
    text: /^(`+|[^`])(?:(?= {2,}\n)|[\s\S]*?(?:(?=[\\<!\[`*_]|\b_|$)|[^ ](?= {2,}\n)))/,
    punctuation: /^((?![*_])[\spunctuation])/
};
// list of unicode punctuation marks, plus any missing characters from CommonMark spec
inline._punctuation = '\\p{P}$+<=>`^|~';
inline.punctuation = edit(inline.punctuation, 'u').replace(/punctuation/g, inline._punctuation).getRegex();
// sequences em should skip over [title](link), `code`, <html>
inline.blockSkip = /\[[^[\]]*?\]\([^\(\)]*?\)|`[^`]*?`|<[^<>]*?>/g;
inline.anyPunctuation = /\\[punct]/g;
inline._escapes = /\\([punct])/g;
inline._comment = edit(block._comment).replace('(?:-->|$)', '-->').getRegex();
inline.emStrong.lDelim = edit(inline.emStrong.lDelim, 'u')
    .replace(/punct/g, inline._punctuation)
    .getRegex();
inline.emStrong.rDelimAst = edit(inline.emStrong.rDelimAst, 'gu')
    .replace(/punct/g, inline._punctuation)
    .getRegex();
inline.emStrong.rDelimUnd = edit(inline.emStrong.rDelimUnd, 'gu')
    .replace(/punct/g, inline._punctuation)
    .getRegex();
inline.anyPunctuation = edit(inline.anyPunctuation, 'gu')
    .replace(/punct/g, inline._punctuation)
    .getRegex();
inline._escapes = edit(inline._escapes, 'gu')
    .replace(/punct/g, inline._punctuation)
    .getRegex();
inline._scheme = /[a-zA-Z][a-zA-Z0-9+.-]{1,31}/;
inline._email = /[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+(@)[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+(?![-_])/;
inline.autolink = edit(inline.autolink)
    .replace('scheme', inline._scheme)
    .replace('email', inline._email)
    .getRegex();
inline._attribute = /\s+[a-zA-Z:_][\w.:-]*(?:\s*=\s*"[^"]*"|\s*=\s*'[^']*'|\s*=\s*[^\s"'=<>`]+)?/;
inline.tag = edit(inline.tag)
    .replace('comment', inline._comment)
    .replace('attribute', inline._attribute)
    .getRegex();
inline._label = /(?:\[(?:\\.|[^\[\]\\])*\]|\\.|`[^`]*`|[^\[\]\\`])*?/;
inline._href = /<(?:\\.|[^\n<>\\])+>|[^\s\x00-\x1f]*/;
inline._title = /"(?:\\"?|[^"\\])*"|'(?:\\'?|[^'\\])*'|\((?:\\\)?|[^)\\])*\)/;
inline.link = edit(inline.link)
    .replace('label', inline._label)
    .replace('href', inline._href)
    .replace('title', inline._title)
    .getRegex();
inline.reflink = edit(inline.reflink)
    .replace('label', inline._label)
    .replace('ref', block._label)
    .getRegex();
inline.nolink = edit(inline.nolink)
    .replace('ref', block._label)
    .getRegex();
inline.reflinkSearch = edit(inline.reflinkSearch, 'g')
    .replace('reflink', inline.reflink)
    .replace('nolink', inline.nolink)
    .getRegex();
/**
 * Normal Inline Grammar
 */
inline.normal = { ...inline };
/**
 * Pedantic Inline Grammar
 */
inline.pedantic = {
    ...inline.normal,
    strong: {
        start: /^__|\*\*/,
        middle: /^__(?=\S)([\s\S]*?\S)__(?!_)|^\*\*(?=\S)([\s\S]*?\S)\*\*(?!\*)/,
        endAst: /\*\*(?!\*)/g,
        endUnd: /__(?!_)/g
    },
    em: {
        start: /^_|\*/,
        middle: /^()\*(?=\S)([\s\S]*?\S)\*(?!\*)|^_(?=\S)([\s\S]*?\S)_(?!_)/,
        endAst: /\*(?!\*)/g,
        endUnd: /_(?!_)/g
    },
    link: edit(/^!?\[(label)\]\((.*?)\)/)
        .replace('label', inline._label)
        .getRegex(),
    reflink: edit(/^!?\[(label)\]\s*\[([^\]]*)\]/)
        .replace('label', inline._label)
        .getRegex()
};
/**
 * GFM Inline Grammar
 */
inline.gfm = {
    ...inline.normal,
    escape: edit(inline.escape).replace('])', '~|])').getRegex(),
    _extended_email: /[A-Za-z0-9._+-]+(@)[a-zA-Z0-9-_]+(?:\.[a-zA-Z0-9-_]*[a-zA-Z0-9])+(?![-_])/,
    url: /^((?:ftp|https?):\/\/|www\.)(?:[a-zA-Z0-9\-]+\.?)+[^\s<]*|^email/,
    _backpedal: /(?:[^?!.,:;*_'"~()&]+|\([^)]*\)|&(?![a-zA-Z0-9]+;$)|[?!.,:;*_'"~)]+(?!$))+/,
    del: /^(~~?)(?=[^\s~])([\s\S]*?[^\s~])\1(?=[^~]|$)/,
    text: /^([`~]+|[^`~])(?:(?= {2,}\n)|(?=[a-zA-Z0-9.!#$%&'*+\/=?_`{\|}~-]+@)|[\s\S]*?(?:(?=[\\<!\[`*~_]|\b_|https?:\/\/|ftp:\/\/|www\.|$)|[^ ](?= {2,}\n)|[^a-zA-Z0-9.!#$%&'*+\/=?_`{\|}~-](?=[a-zA-Z0-9.!#$%&'*+\/=?_`{\|}~-]+@)))/
};
inline.gfm.url = edit(inline.gfm.url, 'i')
    .replace('email', inline.gfm._extended_email)
    .getRegex();
/**
 * GFM + Line Breaks Inline Grammar
 */
inline.breaks = {
    ...inline.gfm,
    br: edit(inline.br).replace('{2,}', '*').getRegex(),
    text: edit(inline.gfm.text)
        .replace('\\b_', '\\b_| {2,}\\n')
        .replace(/\{2,\}/g, '*')
        .getRegex()
};

/**
 * smartypants text replacement
 */
function smartypants(text) {
    return text
        // em-dashes
        .replace(/---/g, '\u2014')
        // en-dashes
        .replace(/--/g, '\u2013')
        // opening singles
        .replace(/(^|[-\u2014/(\[{"\s])'/g, '$1\u2018')
        // closing singles & apostrophes
        .replace(/'/g, '\u2019')
        // opening doubles
        .replace(/(^|[-\u2014/(\[{\u2018\s])"/g, '$1\u201c')
        // closing doubles
        .replace(/"/g, '\u201d')
        // ellipses
        .replace(/\.{3}/g, '\u2026');
}
/**
 * mangle email addresses
 */
function mangle(text) {
    let out = '', i, ch;
    const l = text.length;
    for (i = 0; i < l; i++) {
        ch = text.charCodeAt(i);
        if (Math.random() > 0.5) {
            ch = 'x' + ch.toString(16);
        }
        out += '&#' + ch + ';';
    }
    return out;
}
/**
 * Block Lexer
 */
class _Lexer {
    tokens;
    options;
    state;
    tokenizer;
    inlineQueue;
    constructor(options) {
        // TokenList cannot be created in one go
        // @ts-expect-error
        this.tokens = [];
        this.tokens.links = Object.create(null);
        this.options = options || _defaults;
        this.options.tokenizer = this.options.tokenizer || new _Tokenizer();
        this.tokenizer = this.options.tokenizer;
        this.tokenizer.options = this.options;
        this.tokenizer.lexer = this;
        this.inlineQueue = [];
        this.state = {
            inLink: false,
            inRawBlock: false,
            top: true
        };
        const rules = {
            block: block.normal,
            inline: inline.normal
        };
        if (this.options.pedantic) {
            rules.block = block.pedantic;
            rules.inline = inline.pedantic;
        }
        else if (this.options.gfm) {
            rules.block = block.gfm;
            if (this.options.breaks) {
                rules.inline = inline.breaks;
            }
            else {
                rules.inline = inline.gfm;
            }
        }
        this.tokenizer.rules = rules;
    }
    /**
     * Expose Rules
     */
    static get rules() {
        return {
            block,
            inline
        };
    }
    /**
     * Static Lex Method
     */
    static lex(src, options) {
        const lexer = new _Lexer(options);
        return lexer.lex(src);
    }
    /**
     * Static Lex Inline Method
     */
    static lexInline(src, options) {
        const lexer = new _Lexer(options);
        return lexer.inlineTokens(src);
    }
    /**
     * Preprocessing
     */
    lex(src) {
        src = src
            .replace(/\r\n|\r/g, '\n');
        this.blockTokens(src, this.tokens);
        let next;
        while (next = this.inlineQueue.shift()) {
            this.inlineTokens(next.src, next.tokens);
        }
        return this.tokens;
    }
    blockTokens(src, tokens = []) {
        if (this.options.pedantic) {
            src = src.replace(/\t/g, '    ').replace(/^ +$/gm, '');
        }
        else {
            src = src.replace(/^( *)(\t+)/gm, (_, leading, tabs) => {
                return leading + '    '.repeat(tabs.length);
            });
        }
        let token;
        let lastToken;
        let cutSrc;
        let lastParagraphClipped;
        while (src) {
            if (this.options.extensions
                && this.options.extensions.block
                && this.options.extensions.block.some((extTokenizer) => {
                    if (token = extTokenizer.call({ lexer: this }, src, tokens)) {
                        src = src.substring(token.raw.length);
                        tokens.push(token);
                        return true;
                    }
                    return false;
                })) {
                continue;
            }
            // newline
            if (token = this.tokenizer.space(src)) {
                src = src.substring(token.raw.length);
                if (token.raw.length === 1 && tokens.length > 0) {
                    // if there's a single \n as a spacer, it's terminating the last line,
                    // so move it there so that we don't get unecessary paragraph tags
                    tokens[tokens.length - 1].raw += '\n';
                }
                else {
                    tokens.push(token);
                }
                continue;
            }
            // code
            if (token = this.tokenizer.code(src)) {
                src = src.substring(token.raw.length);
                lastToken = tokens[tokens.length - 1];
                // An indented code block cannot interrupt a paragraph.
                if (lastToken && (lastToken.type === 'paragraph' || lastToken.type === 'text')) {
                    lastToken.raw += '\n' + token.raw;
                    lastToken.text += '\n' + token.text;
                    this.inlineQueue[this.inlineQueue.length - 1].src = lastToken.text;
                }
                else {
                    tokens.push(token);
                }
                continue;
            }
            // fences
            if (token = this.tokenizer.fences(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // heading
            if (token = this.tokenizer.heading(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // hr
            if (token = this.tokenizer.hr(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // blockquote
            if (token = this.tokenizer.blockquote(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // list
            if (token = this.tokenizer.list(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // html
            if (token = this.tokenizer.html(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // def
            if (token = this.tokenizer.def(src)) {
                src = src.substring(token.raw.length);
                lastToken = tokens[tokens.length - 1];
                if (lastToken && (lastToken.type === 'paragraph' || lastToken.type === 'text')) {
                    lastToken.raw += '\n' + token.raw;
                    lastToken.text += '\n' + token.raw;
                    this.inlineQueue[this.inlineQueue.length - 1].src = lastToken.text;
                }
                else if (!this.tokens.links[token.tag]) {
                    this.tokens.links[token.tag] = {
                        href: token.href,
                        title: token.title
                    };
                }
                continue;
            }
            // table (gfm)
            if (token = this.tokenizer.table(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // lheading
            if (token = this.tokenizer.lheading(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // top-level paragraph
            // prevent paragraph consuming extensions by clipping 'src' to extension start
            cutSrc = src;
            if (this.options.extensions && this.options.extensions.startBlock) {
                let startIndex = Infinity;
                const tempSrc = src.slice(1);
                let tempStart;
                this.options.extensions.startBlock.forEach((getStartIndex) => {
                    tempStart = getStartIndex.call({ lexer: this }, tempSrc);
                    if (typeof tempStart === 'number' && tempStart >= 0) {
                        startIndex = Math.min(startIndex, tempStart);
                    }
                });
                if (startIndex < Infinity && startIndex >= 0) {
                    cutSrc = src.substring(0, startIndex + 1);
                }
            }
            if (this.state.top && (token = this.tokenizer.paragraph(cutSrc))) {
                lastToken = tokens[tokens.length - 1];
                if (lastParagraphClipped && lastToken.type === 'paragraph') {
                    lastToken.raw += '\n' + token.raw;
                    lastToken.text += '\n' + token.text;
                    this.inlineQueue.pop();
                    this.inlineQueue[this.inlineQueue.length - 1].src = lastToken.text;
                }
                else {
                    tokens.push(token);
                }
                lastParagraphClipped = (cutSrc.length !== src.length);
                src = src.substring(token.raw.length);
                continue;
            }
            // text
            if (token = this.tokenizer.text(src)) {
                src = src.substring(token.raw.length);
                lastToken = tokens[tokens.length - 1];
                if (lastToken && lastToken.type === 'text') {
                    lastToken.raw += '\n' + token.raw;
                    lastToken.text += '\n' + token.text;
                    this.inlineQueue.pop();
                    this.inlineQueue[this.inlineQueue.length - 1].src = lastToken.text;
                }
                else {
                    tokens.push(token);
                }
                continue;
            }
            if (src) {
                const errMsg = 'Infinite loop on byte: ' + src.charCodeAt(0);
                if (this.options.silent) {
                    console.error(errMsg);
                    break;
                }
                else {
                    throw new Error(errMsg);
                }
            }
        }
        this.state.top = true;
        return tokens;
    }
    inline(src, tokens = []) {
        this.inlineQueue.push({ src, tokens });
        return tokens;
    }
    /**
     * Lexing/Compiling
     */
    inlineTokens(src, tokens = []) {
        let token, lastToken, cutSrc;
        // String with links masked to avoid interference with em and strong
        let maskedSrc = src;
        let match;
        let keepPrevChar, prevChar;
        // Mask out reflinks
        if (this.tokens.links) {
            const links = Object.keys(this.tokens.links);
            if (links.length > 0) {
                while ((match = this.tokenizer.rules.inline.reflinkSearch.exec(maskedSrc)) != null) {
                    if (links.includes(match[0].slice(match[0].lastIndexOf('[') + 1, -1))) {
                        maskedSrc = maskedSrc.slice(0, match.index) + '[' + 'a'.repeat(match[0].length - 2) + ']' + maskedSrc.slice(this.tokenizer.rules.inline.reflinkSearch.lastIndex);
                    }
                }
            }
        }
        // Mask out other blocks
        while ((match = this.tokenizer.rules.inline.blockSkip.exec(maskedSrc)) != null) {
            maskedSrc = maskedSrc.slice(0, match.index) + '[' + 'a'.repeat(match[0].length - 2) + ']' + maskedSrc.slice(this.tokenizer.rules.inline.blockSkip.lastIndex);
        }
        // Mask out escaped characters
        while ((match = this.tokenizer.rules.inline.anyPunctuation.exec(maskedSrc)) != null) {
            maskedSrc = maskedSrc.slice(0, match.index) + '++' + maskedSrc.slice(this.tokenizer.rules.inline.anyPunctuation.lastIndex);
        }
        while (src) {
            if (!keepPrevChar) {
                prevChar = '';
            }
            keepPrevChar = false;
            // extensions
            if (this.options.extensions
                && this.options.extensions.inline
                && this.options.extensions.inline.some((extTokenizer) => {
                    if (token = extTokenizer.call({ lexer: this }, src, tokens)) {
                        src = src.substring(token.raw.length);
                        tokens.push(token);
                        return true;
                    }
                    return false;
                })) {
                continue;
            }
            // escape
            if (token = this.tokenizer.escape(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // tag
            if (token = this.tokenizer.tag(src)) {
                src = src.substring(token.raw.length);
                lastToken = tokens[tokens.length - 1];
                if (lastToken && token.type === 'text' && lastToken.type === 'text') {
                    lastToken.raw += token.raw;
                    lastToken.text += token.text;
                }
                else {
                    tokens.push(token);
                }
                continue;
            }
            // link
            if (token = this.tokenizer.link(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // reflink, nolink
            if (token = this.tokenizer.reflink(src, this.tokens.links)) {
                src = src.substring(token.raw.length);
                lastToken = tokens[tokens.length - 1];
                if (lastToken && token.type === 'text' && lastToken.type === 'text') {
                    lastToken.raw += token.raw;
                    lastToken.text += token.text;
                }
                else {
                    tokens.push(token);
                }
                continue;
            }
            // em & strong
            if (token = this.tokenizer.emStrong(src, maskedSrc, prevChar)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // code
            if (token = this.tokenizer.codespan(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // br
            if (token = this.tokenizer.br(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // del (gfm)
            if (token = this.tokenizer.del(src)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // autolink
            if (token = this.tokenizer.autolink(src, mangle)) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // url (gfm)
            if (!this.state.inLink && (token = this.tokenizer.url(src, mangle))) {
                src = src.substring(token.raw.length);
                tokens.push(token);
                continue;
            }
            // text
            // prevent inlineText consuming extensions by clipping 'src' to extension start
            cutSrc = src;
            if (this.options.extensions && this.options.extensions.startInline) {
                let startIndex = Infinity;
                const tempSrc = src.slice(1);
                let tempStart;
                this.options.extensions.startInline.forEach((getStartIndex) => {
                    tempStart = getStartIndex.call({ lexer: this }, tempSrc);
                    if (typeof tempStart === 'number' && tempStart >= 0) {
                        startIndex = Math.min(startIndex, tempStart);
                    }
                });
                if (startIndex < Infinity && startIndex >= 0) {
                    cutSrc = src.substring(0, startIndex + 1);
                }
            }
            if (token = this.tokenizer.inlineText(cutSrc, smartypants)) {
                src = src.substring(token.raw.length);
                if (token.raw.slice(-1) !== '_') { // Track prevChar before string of ____ started
                    prevChar = token.raw.slice(-1);
                }
                keepPrevChar = true;
                lastToken = tokens[tokens.length - 1];
                if (lastToken && lastToken.type === 'text') {
                    lastToken.raw += token.raw;
                    lastToken.text += token.text;
                }
                else {
                    tokens.push(token);
                }
                continue;
            }
            if (src) {
                const errMsg = 'Infinite loop on byte: ' + src.charCodeAt(0);
                if (this.options.silent) {
                    console.error(errMsg);
                    break;
                }
                else {
                    throw new Error(errMsg);
                }
            }
        }
        return tokens;
    }
}

/**
 * Renderer
 */
class _Renderer {
    options;
    constructor(options) {
        this.options = options || _defaults;
    }
    code(code, infostring, escaped) {
        const lang = (infostring || '').match(/\S*/)[0];
        if (this.options.highlight) {
            const out = this.options.highlight(code, lang);
            if (out != null && out !== code) {
                escaped = true;
                code = out;
            }
        }
        code = code.replace(/\n$/, '') + '\n';
        if (!lang) {
            return '<pre><code>'
                + (escaped ? code : escape(code, true))
                + '</code></pre>\n';
        }
        return '<pre><code class="'
            + this.options.langPrefix
            + escape(lang)
            + '">'
            + (escaped ? code : escape(code, true))
            + '</code></pre>\n';
    }
    blockquote(quote) {
        return `<blockquote>\n${quote}</blockquote>\n`;
    }
    html(html, block) {
        return html;
    }
    heading(text, level, raw, slugger) {
        if (this.options.headerIds) {
            const id = this.options.headerPrefix + slugger.slug(raw);
            return `<h${level} id="${id}">${text}</h${level}>\n`;
        }
        // ignore IDs
        return `<h${level}>${text}</h${level}>\n`;
    }
    hr() {
        return this.options.xhtml ? '<hr/>\n' : '<hr>\n';
    }
    list(body, ordered, start) {
        const type = ordered ? 'ol' : 'ul', startatt = (ordered && start !== 1) ? (' start="' + start + '"') : '';
        return '<' + type + startatt + '>\n' + body + '</' + type + '>\n';
    }
    listitem(text, task, checked) {
        return `<li>${text}</li>\n`;
    }
    checkbox(checked) {
        return '<input '
            + (checked ? 'checked="" ' : '')
            + 'disabled="" type="checkbox"'
            + (this.options.xhtml ? ' /' : '')
            + '> ';
    }
    paragraph(text) {
        return `<p>${text}</p>\n`;
    }
    table(header, body) {
        if (body)
            body = `<tbody>${body}</tbody>`;
        return '<table>\n'
            + '<thead>\n'
            + header
            + '</thead>\n'
            + body
            + '</table>\n';
    }
    tablerow(content) {
        return `<tr>\n${content}</tr>\n`;
    }
    tablecell(content, flags) {
        const type = flags.header ? 'th' : 'td';
        const tag = flags.align
            ? `<${type} align="${flags.align}">`
            : `<${type}>`;
        return tag + content + `</${type}>\n`;
    }
    /**
     * span level renderer
     */
    strong(text) {
        return `<strong>${text}</strong>`;
    }
    em(text) {
        return `<em>${text}</em>`;
    }
    codespan(text) {
        return `<code>${text}</code>`;
    }
    br() {
        return this.options.xhtml ? '<br/>' : '<br>';
    }
    del(text) {
        return `<del>${text}</del>`;
    }
    link(href, title, text) {
        href = cleanUrl(this.options.sanitize, this.options.baseUrl, href);
        if (href === null) {
            return text;
        }
        let out = '<a href="' + href + '"';
        if (title) {
            out += ' title="' + title + '"';
        }
        out += '>' + text + '</a>';
        return out;
    }
    image(href, title, text) {
        href = cleanUrl(this.options.sanitize, this.options.baseUrl, href);
        if (href === null) {
            return text;
        }
        let out = `<img src="${href}" alt="${text}"`;
        if (title) {
            out += ` title="${title}"`;
        }
        out += this.options.xhtml ? '/>' : '>';
        return out;
    }
    text(text) {
        return text;
    }
}

/**
 * TextRenderer
 * returns only the textual part of the token
 */
class _TextRenderer {
    // no need for block level renderers
    strong(text) {
        return text;
    }
    em(text) {
        return text;
    }
    codespan(text) {
        return text;
    }
    del(text) {
        return text;
    }
    html(text) {
        return text;
    }
    text(text) {
        return text;
    }
    link(href, title, text) {
        return '' + text;
    }
    image(href, title, text) {
        return '' + text;
    }
    br() {
        return '';
    }
}

/**
 * Slugger generates header id
 */
class _Slugger {
    seen;
    constructor() {
        this.seen = {};
    }
    serialize(value) {
        return value
            .toLowerCase()
            .trim()
            // remove html tags
            .replace(/<[!\/a-z].*?>/ig, '')
            // remove unwanted chars
            .replace(/[\u2000-\u206F\u2E00-\u2E7F\\'!"#$%&()*+,./:;<=>?@[\]^`{|}~]/g, '')
            .replace(/\s/g, '-');
    }
    /**
     * Finds the next safe (unique) slug to use
     */
    getNextSafeSlug(originalSlug, isDryRun) {
        let slug = originalSlug;
        let occurenceAccumulator = 0;
        if (this.seen.hasOwnProperty(slug)) {
            occurenceAccumulator = this.seen[originalSlug];
            do {
                occurenceAccumulator++;
                slug = originalSlug + '-' + occurenceAccumulator;
            } while (this.seen.hasOwnProperty(slug));
        }
        if (!isDryRun) {
            this.seen[originalSlug] = occurenceAccumulator;
            this.seen[slug] = 0;
        }
        return slug;
    }
    /**
     * Convert string to unique id
     */
    slug(value, options = {}) {
        const slug = this.serialize(value);
        return this.getNextSafeSlug(slug, options.dryrun);
    }
}

/**
 * Parsing & Compiling
 */
class _Parser {
    options;
    renderer;
    textRenderer;
    slugger;
    constructor(options) {
        this.options = options || _defaults;
        this.options.renderer = this.options.renderer || new _Renderer();
        this.renderer = this.options.renderer;
        this.renderer.options = this.options;
        this.textRenderer = new _TextRenderer();
        this.slugger = new _Slugger();
    }
    /**
     * Static Parse Method
     */
    static parse(tokens, options) {
        const parser = new _Parser(options);
        return parser.parse(tokens);
    }
    /**
     * Static Parse Inline Method
     */
    static parseInline(tokens, options) {
        const parser = new _Parser(options);
        return parser.parseInline(tokens);
    }
    /**
     * Parse Loop
     */
    parse(tokens, top = true) {
        let out = '', i, j, k, l2, l3, row, cell, header, body, token, ordered, start, loose, itemBody, item, checked, task, checkbox, ret;
        const l = tokens.length;
        for (i = 0; i < l; i++) {
            token = tokens[i];
            // Run any renderer extensions
            if (this.options.extensions && this.options.extensions.renderers && this.options.extensions.renderers[token.type]) {
                ret = this.options.extensions.renderers[token.type].call({ parser: this }, token);
                if (ret !== false || !['space', 'hr', 'heading', 'code', 'table', 'blockquote', 'list', 'html', 'paragraph', 'text'].includes(token.type)) {
                    out += ret || '';
                    continue;
                }
            }
            switch (token.type) {
                case 'space': {
                    continue;
                }
                case 'hr': {
                    out += this.renderer.hr();
                    continue;
                }
                case 'heading': {
                    out += this.renderer.heading(this.parseInline(token.tokens), token.depth, unescape(this.parseInline(token.tokens, this.textRenderer)), this.slugger);
                    continue;
                }
                case 'code': {
                    out += this.renderer.code(token.text, token.lang, !!token.escaped);
                    continue;
                }
                case 'table': {
                    header = '';
                    // header
                    cell = '';
                    l2 = token.header.length;
                    for (j = 0; j < l2; j++) {
                        cell += this.renderer.tablecell(this.parseInline(token.header[j].tokens), { header: true, align: token.align[j] });
                    }
                    header += this.renderer.tablerow(cell);
                    body = '';
                    l2 = token.rows.length;
                    for (j = 0; j < l2; j++) {
                        row = token.rows[j];
                        cell = '';
                        l3 = row.length;
                        for (k = 0; k < l3; k++) {
                            cell += this.renderer.tablecell(this.parseInline(row[k].tokens), { header: false, align: token.align[k] });
                        }
                        body += this.renderer.tablerow(cell);
                    }
                    out += this.renderer.table(header, body);
                    continue;
                }
                case 'blockquote': {
                    body = this.parse(token.tokens);
                    out += this.renderer.blockquote(body);
                    continue;
                }
                case 'list': {
                    ordered = token.ordered;
                    start = token.start;
                    loose = token.loose;
                    l2 = token.items.length;
                    body = '';
                    for (j = 0; j < l2; j++) {
                        item = token.items[j];
                        checked = item.checked;
                        task = item.task;
                        itemBody = '';
                        if (item.task) {
                            checkbox = this.renderer.checkbox(!!checked);
                            if (loose) {
                                if (item.tokens.length > 0 && item.tokens[0].type === 'paragraph') {
                                    item.tokens[0].text = checkbox + ' ' + item.tokens[0].text;
                                    if (item.tokens[0].tokens && item.tokens[0].tokens.length > 0 && item.tokens[0].tokens[0].type === 'text') {
                                        item.tokens[0].tokens[0].text = checkbox + ' ' + item.tokens[0].tokens[0].text;
                                    }
                                }
                                else {
                                    item.tokens.unshift({
                                        type: 'text',
                                        text: checkbox
                                    });
                                }
                            }
                            else {
                                itemBody += checkbox;
                            }
                        }
                        itemBody += this.parse(item.tokens, loose);
                        body += this.renderer.listitem(itemBody, task, !!checked);
                    }
                    out += this.renderer.list(body, ordered, start);
                    continue;
                }
                case 'html': {
                    out += this.renderer.html(token.text, token.block);
                    continue;
                }
                case 'paragraph': {
                    out += this.renderer.paragraph(this.parseInline(token.tokens));
                    continue;
                }
                case 'text': {
                    body = token.tokens ? this.parseInline(token.tokens) : token.text;
                    while (i + 1 < l && tokens[i + 1].type === 'text') {
                        token = tokens[++i];
                        body += '\n' + (token.tokens ? this.parseInline(token.tokens) : token.text);
                    }
                    out += top ? this.renderer.paragraph(body) : body;
                    continue;
                }
                default: {
                    const errMsg = 'Token with "' + token.type + '" type was not found.';
                    if (this.options.silent) {
                        console.error(errMsg);
                        return '';
                    }
                    else {
                        throw new Error(errMsg);
                    }
                }
            }
        }
        return out;
    }
    /**
     * Parse Inline Tokens
     */
    parseInline(tokens, renderer) {
        renderer = renderer || this.renderer;
        let out = '', i, token, ret;
        const l = tokens.length;
        for (i = 0; i < l; i++) {
            token = tokens[i];
            // Run any renderer extensions
            if (this.options.extensions && this.options.extensions.renderers && this.options.extensions.renderers[token.type]) {
                ret = this.options.extensions.renderers[token.type].call({ parser: this }, token);
                if (ret !== false || !['escape', 'html', 'link', 'image', 'strong', 'em', 'codespan', 'br', 'del', 'text'].includes(token.type)) {
                    out += ret || '';
                    continue;
                }
            }
            switch (token.type) {
                case 'escape': {
                    out += renderer.text(token.text);
                    break;
                }
                case 'html': {
                    out += renderer.html(token.text);
                    break;
                }
                case 'link': {
                    out += renderer.link(token.href, token.title, this.parseInline(token.tokens, renderer));
                    break;
                }
                case 'image': {
                    out += renderer.image(token.href, token.title, token.text);
                    break;
                }
                case 'strong': {
                    out += renderer.strong(this.parseInline(token.tokens, renderer));
                    break;
                }
                case 'em': {
                    out += renderer.em(this.parseInline(token.tokens, renderer));
                    break;
                }
                case 'codespan': {
                    out += renderer.codespan(token.text);
                    break;
                }
                case 'br': {
                    out += renderer.br();
                    break;
                }
                case 'del': {
                    out += renderer.del(this.parseInline(token.tokens, renderer));
                    break;
                }
                case 'text': {
                    out += renderer.text(token.text);
                    break;
                }
                default: {
                    const errMsg = 'Token with "' + token.type + '" type was not found.';
                    if (this.options.silent) {
                        console.error(errMsg);
                        return '';
                    }
                    else {
                        throw new Error(errMsg);
                    }
                }
            }
        }
        return out;
    }
}

class _Hooks {
    options;
    constructor(options) {
        this.options = options || _defaults;
    }
    static passThroughHooks = new Set([
        'preprocess',
        'postprocess'
    ]);
    /**
     * Process markdown before marked
     */
    preprocess(markdown) {
        return markdown;
    }
    /**
     * Process HTML after marked is finished
     */
    postprocess(html) {
        return html;
    }
}

class Marked {
    defaults = _getDefaults();
    options = this.setOptions;
    parse = this.#parseMarkdown(_Lexer.lex, _Parser.parse);
    parseInline = this.#parseMarkdown(_Lexer.lexInline, _Parser.parseInline);
    Parser = _Parser;
    parser = _Parser.parse;
    Renderer = _Renderer;
    TextRenderer = _TextRenderer;
    Lexer = _Lexer;
    lexer = _Lexer.lex;
    Tokenizer = _Tokenizer;
    Slugger = _Slugger;
    Hooks = _Hooks;
    constructor(...args) {
        this.use(...args);
    }
    /**
     * Run callback for every token
     */
    walkTokens(tokens, callback) {
        let values = [];
        for (const token of tokens) {
            values = values.concat(callback.call(this, token));
            switch (token.type) {
                case 'table': {
                    for (const cell of token.header) {
                        values = values.concat(this.walkTokens(cell.tokens, callback));
                    }
                    for (const row of token.rows) {
                        for (const cell of row) {
                            values = values.concat(this.walkTokens(cell.tokens, callback));
                        }
                    }
                    break;
                }
                case 'list': {
                    values = values.concat(this.walkTokens(token.items, callback));
                    break;
                }
                default: {
                    if (this.defaults.extensions && this.defaults.extensions.childTokens && this.defaults.extensions.childTokens[token.type]) { // Walk any extensions
                        this.defaults.extensions.childTokens[token.type].forEach((childTokens) => {
                            // @ts-expect-error we assume token[childToken] is an array of tokens but we can't be sure
                            values = values.concat(this.walkTokens(token[childTokens], callback));
                        });
                    }
                    else if (token.tokens) {
                        values = values.concat(this.walkTokens(token.tokens, callback));
                    }
                }
            }
        }
        return values;
    }
    use(...args) {
        const extensions = this.defaults.extensions || { renderers: {}, childTokens: {} };
        args.forEach((pack) => {
            // copy options to new object
            const opts = { ...pack };
            // set async to true if it was set to true before
            opts.async = this.defaults.async || opts.async || false;
            // ==-- Parse "addon" extensions --== //
            if (pack.extensions) {
                pack.extensions.forEach((ext) => {
                    if (!ext.name) {
                        throw new Error('extension name required');
                    }
                    if ('renderer' in ext) { // Renderer extensions
                        const prevRenderer = extensions.renderers[ext.name];
                        if (prevRenderer) {
                            // Replace extension with func to run new extension but fall back if false
                            extensions.renderers[ext.name] = function (...args) {
                                let ret = ext.renderer.apply(this, args);
                                if (ret === false) {
                                    ret = prevRenderer.apply(this, args);
                                }
                                return ret;
                            };
                        }
                        else {
                            extensions.renderers[ext.name] = ext.renderer;
                        }
                    }
                    if ('tokenizer' in ext) { // Tokenizer Extensions
                        if (!ext.level || (ext.level !== 'block' && ext.level !== 'inline')) {
                            throw new Error("extension level must be 'block' or 'inline'");
                        }
                        if (extensions[ext.level]) {
                            extensions[ext.level].unshift(ext.tokenizer);
                        }
                        else {
                            extensions[ext.level] = [ext.tokenizer];
                        }
                        if (ext.start) { // Function to check for start of token
                            if (ext.level === 'block') {
                                if (extensions.startBlock) {
                                    extensions.startBlock.push(ext.start);
                                }
                                else {
                                    extensions.startBlock = [ext.start];
                                }
                            }
                            else if (ext.level === 'inline') {
                                if (extensions.startInline) {
                                    extensions.startInline.push(ext.start);
                                }
                                else {
                                    extensions.startInline = [ext.start];
                                }
                            }
                        }
                    }
                    if ('childTokens' in ext && ext.childTokens) { // Child tokens to be visited by walkTokens
                        extensions.childTokens[ext.name] = ext.childTokens;
                    }
                });
                opts.extensions = extensions;
            }
            // ==-- Parse "overwrite" extensions --== //
            if (pack.renderer) {
                const renderer = this.defaults.renderer || new _Renderer(this.defaults);
                for (const prop in pack.renderer) {
                    const rendererFunc = pack.renderer[prop];
                    const rendererKey = prop;
                    const prevRenderer = renderer[rendererKey];
                    // Replace renderer with func to run extension, but fall back if false
                    renderer[rendererKey] = (...args) => {
                        let ret = rendererFunc.apply(renderer, args);
                        if (ret === false) {
                            ret = prevRenderer.apply(renderer, args);
                        }
                        return ret || '';
                    };
                }
                opts.renderer = renderer;
            }
            if (pack.tokenizer) {
                const tokenizer = this.defaults.tokenizer || new _Tokenizer(this.defaults);
                for (const prop in pack.tokenizer) {
                    const tokenizerFunc = pack.tokenizer[prop];
                    const tokenizerKey = prop;
                    const prevTokenizer = tokenizer[tokenizerKey];
                    // Replace tokenizer with func to run extension, but fall back if false
                    tokenizer[tokenizerKey] = (...args) => {
                        let ret = tokenizerFunc.apply(tokenizer, args);
                        if (ret === false) {
                            ret = prevTokenizer.apply(tokenizer, args);
                        }
                        return ret;
                    };
                }
                opts.tokenizer = tokenizer;
            }
            // ==-- Parse Hooks extensions --== //
            if (pack.hooks) {
                const hooks = this.defaults.hooks || new _Hooks();
                for (const prop in pack.hooks) {
                    const hooksFunc = pack.hooks[prop];
                    const hooksKey = prop;
                    const prevHook = hooks[hooksKey];
                    if (_Hooks.passThroughHooks.has(prop)) {
                        hooks[hooksKey] = (arg) => {
                            if (this.defaults.async) {
                                return Promise.resolve(hooksFunc.call(hooks, arg)).then(ret => {
                                    return prevHook.call(hooks, ret);
                                });
                            }
                            const ret = hooksFunc.call(hooks, arg);
                            return prevHook.call(hooks, ret);
                        };
                    }
                    else {
                        hooks[hooksKey] = (...args) => {
                            let ret = hooksFunc.apply(hooks, args);
                            if (ret === false) {
                                ret = prevHook.apply(hooks, args);
                            }
                            return ret;
                        };
                    }
                }
                opts.hooks = hooks;
            }
            // ==-- Parse WalkTokens extensions --== //
            if (pack.walkTokens) {
                const walkTokens = this.defaults.walkTokens;
                opts.walkTokens = function (token) {
                    let values = [];
                    values.push(pack.walkTokens.call(this, token));
                    if (walkTokens) {
                        values = values.concat(walkTokens.call(this, token));
                    }
                    return values;
                };
            }
            this.defaults = { ...this.defaults, ...opts };
        });
        return this;
    }
    setOptions(opt) {
        this.defaults = { ...this.defaults, ...opt };
        return this;
    }
    #parseMarkdown(lexer, parser) {
        return (src, optOrCallback, callback) => {
            if (typeof optOrCallback === 'function') {
                callback = optOrCallback;
                optOrCallback = null;
            }
            const origOpt = { ...optOrCallback };
            const opt = { ...this.defaults, ...origOpt };
            const throwError = this.#onError(!!opt.silent, !!opt.async, callback);
            // throw error in case of non string input
            if (typeof src === 'undefined' || src === null) {
                return throwError(new Error('marked(): input parameter is undefined or null'));
            }
            if (typeof src !== 'string') {
                return throwError(new Error('marked(): input parameter is of type '
                    + Object.prototype.toString.call(src) + ', string expected'));
            }
            checkDeprecations(opt, callback);
            if (opt.hooks) {
                opt.hooks.options = opt;
            }
            if (callback) {
                const highlight = opt.highlight;
                let tokens;
                try {
                    if (opt.hooks) {
                        src = opt.hooks.preprocess(src);
                    }
                    tokens = lexer(src, opt);
                }
                catch (e) {
                    return throwError(e);
                }
                const done = (err) => {
                    let out;
                    if (!err) {
                        try {
                            if (opt.walkTokens) {
                                this.walkTokens(tokens, opt.walkTokens);
                            }
                            out = parser(tokens, opt);
                            if (opt.hooks) {
                                out = opt.hooks.postprocess(out);
                            }
                        }
                        catch (e) {
                            err = e;
                        }
                    }
                    opt.highlight = highlight;
                    return err
                        ? throwError(err)
                        : callback(null, out);
                };
                if (!highlight || highlight.length < 3) {
                    return done();
                }
                delete opt.highlight;
                if (!tokens.length)
                    return done();
                let pending = 0;
                this.walkTokens(tokens, (token) => {
                    if (token.type === 'code') {
                        pending++;
                        setTimeout(() => {
                            highlight(token.text, token.lang, (err, code) => {
                                if (err) {
                                    return done(err);
                                }
                                if (code != null && code !== token.text) {
                                    token.text = code;
                                    token.escaped = true;
                                }
                                pending--;
                                if (pending === 0) {
                                    done();
                                }
                            });
                        }, 0);
                    }
                });
                if (pending === 0) {
                    done();
                }
                return;
            }
            if (opt.async) {
                return Promise.resolve(opt.hooks ? opt.hooks.preprocess(src) : src)
                    .then(src => lexer(src, opt))
                    .then(tokens => opt.walkTokens ? Promise.all(this.walkTokens(tokens, opt.walkTokens)).then(() => tokens) : tokens)
                    .then(tokens => parser(tokens, opt))
                    .then(html => opt.hooks ? opt.hooks.postprocess(html) : html)
                    .catch(throwError);
            }
            try {
                if (opt.hooks) {
                    src = opt.hooks.preprocess(src);
                }
                const tokens = lexer(src, opt);
                if (opt.walkTokens) {
                    this.walkTokens(tokens, opt.walkTokens);
                }
                let html = parser(tokens, opt);
                if (opt.hooks) {
                    html = opt.hooks.postprocess(html);
                }
                return html;
            }
            catch (e) {
                return throwError(e);
            }
        };
    }
    #onError(silent, async, callback) {
        return (e) => {
            e.message += '\nPlease report this to https://github.com/markedjs/marked.';
            if (silent) {
                const msg = '<p>An error occurred:</p><pre>'
                    + escape(e.message + '', true)
                    + '</pre>';
                if (async) {
                    return Promise.resolve(msg);
                }
                if (callback) {
                    callback(null, msg);
                    return;
                }
                return msg;
            }
            if (async) {
                return Promise.reject(e);
            }
            if (callback) {
                callback(e);
                return;
            }
            throw e;
        };
    }
}

const markedInstance = new Marked();
function marked(src, opt, callback) {
    return markedInstance.parse(src, opt, callback);
}
/**
 * Sets the default options.
 *
 * @param options Hash of options
 */
marked.options =
    marked.setOptions = function (options) {
        markedInstance.setOptions(options);
        marked.defaults = markedInstance.defaults;
        changeDefaults(marked.defaults);
        return marked;
    };
/**
 * Gets the original marked default options.
 */
marked.getDefaults = _getDefaults;
marked.defaults = _defaults;
/**
 * Use Extension
 */
marked.use = function (...args) {
    markedInstance.use(...args);
    marked.defaults = markedInstance.defaults;
    changeDefaults(marked.defaults);
    return marked;
};
/**
 * Run callback for every token
 */
marked.walkTokens = function (tokens, callback) {
    return markedInstance.walkTokens(tokens, callback);
};
/**
 * Compiles markdown to HTML without enclosing `p` tag.
 *
 * @param src String of markdown source to be compiled
 * @param options Hash of options
 * @return String of compiled HTML
 */
marked.parseInline = markedInstance.parseInline;
/**
 * Expose
 */
marked.Parser = _Parser;
marked.parser = _Parser.parse;
marked.Renderer = _Renderer;
marked.TextRenderer = _TextRenderer;
marked.Lexer = _Lexer;
marked.lexer = _Lexer.lex;
marked.Tokenizer = _Tokenizer;
marked.Slugger = _Slugger;
marked.Hooks = _Hooks;
marked.parse = marked;
const options = marked.options;
const setOptions = marked.setOptions;
const use = marked.use;
const walkTokens = marked.walkTokens;
const parseInline = marked.parseInline;
const parse = marked;
const parser = _Parser.parse;
const lexer = _Lexer.lex;


//# sourceMappingURL=marked.esm.js.map


/***/ })

}]);
//# sourceMappingURL=settings-apps-view-settings-apps-view.js.map?v=74cd667f477fb2ddc0cc