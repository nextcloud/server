/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcHeaderMenu.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcHeaderMenu.js ***!
  \*********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

/*! For license information please see NcHeaderMenu.js.LICENSE.txt */
!function(e,t){ true?module.exports=t():0}(self,(()=>(()=>{var e={6730:(e,t,n)=>{"use strict";n.d(t,{default:()=>r});const r={props:{excludeClickOutsideSelectors:{type:[String,Array],default:()=>[]}},computed:{clickOutsideOptions(){return{ignore:Array.isArray(this.excludeClickOutsideSelectors)?this.excludeClickOutsideSelectors:[this.excludeClickOutsideSelectors]}}}}},3351:(e,t,n)=>{"use strict";n.d(t,{BW:()=>r.default});var r=n(6730);n(8136),n(334),n(3132),n(3607),n(768);__webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.js");n(4262)},8136:()=>{"use strict"},334:(e,t,n)=>{"use strict";var r=n(2734);new(n.n(r)())({data:()=>({isMobile:!1}),watch:{isMobile(e){this.$emit("changed",e)}},created(){window.addEventListener("resize",this.handleWindowResize),this.handleWindowResize()},beforeDestroy(){window.removeEventListener("resize",this.handleWindowResize)},methods:{handleWindowResize(){this.isMobile=document.documentElement.clientWidth<1024}}})},3132:(e,t,n)=>{"use strict";n(3330),n(1390);__webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");__webpack_require__(/*! striptags */ "./node_modules/striptags/src/striptags.js");n(2734);const r="(?:^|\\s)",i="(?:[^a-z]|$)";new RegExp("".concat(r,"(@[a-zA-Z0-9_.@\\-']+)(").concat(i,")"),"gi"),new RegExp("".concat(r,"(@&quot;[a-zA-Z0-9 _.@\\-']+&quot;)(").concat(i,")"),"gi")},1390:(e,t,n)=>{"use strict";n.d(t,{Z:()=>a});const r=__webpack_require__(/*! linkify-string */ "./node_modules/linkify-string/dist/linkify-string.es.js");var i=n.n(r);const a=e=>i()(e,{defaultProtocol:"https",target:"_blank",className:"external linkified",attributes:{rel:"nofollow noopener noreferrer"}})},1206:(e,t,n)=>{"use strict";n.d(t,{L:()=>r});n(4505);const r=function(){return Object.assign(window,{_nc_focus_trap:window._nc_focus_trap||[]}),window._nc_focus_trap}},4473:(e,t,n)=>{"use strict";n.d(t,{Z:()=>s});var r=n(7537),i=n.n(r),a=n(3645),o=n.n(a)()(i());o.push([e.id,'.material-design-icon[data-v-8a70222c]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.header-menu[data-v-8a70222c]{position:relative;width:var(--header-height);height:var(--header-height)}.header-menu__trigger[data-v-8a70222c]{display:flex;align-items:center;justify-content:center;width:var(--header-height);height:var(--header-height);margin:0;padding:0;cursor:pointer;opacity:.85;filter:none !important;color:var(--color-primary-text) !important}.header-menu--opened .header-menu__trigger[data-v-8a70222c],.header-menu__trigger[data-v-8a70222c]:hover,.header-menu__trigger[data-v-8a70222c]:focus,.header-menu__trigger[data-v-8a70222c]:active{opacity:1}.header-menu__trigger[data-v-8a70222c]:focus-visible{outline:none}.header-menu__wrapper[data-v-8a70222c]{position:fixed;z-index:2000;top:50px;right:0;box-sizing:border-box;margin:0 8px;padding:8px;border-radius:0 0 var(--border-radius) var(--border-radius);border-radius:var(--border-radius-large);background-color:var(--color-main-background);filter:drop-shadow(0 1px 5px var(--color-box-shadow))}.header-menu__carret[data-v-8a70222c]{position:absolute;z-index:2001;bottom:0;left:calc(50% - 10px);width:0;height:0;content:" ";pointer-events:none;border:10px solid rgba(0,0,0,0);border-bottom-color:var(--color-main-background)}.header-menu__content[data-v-8a70222c]{overflow:auto;width:350px;max-width:calc(100vw - 16px);min-height:66px;max-height:calc(100vh - 100px)}.header-menu__content[data-v-8a70222c] .empty-content{margin:12vh 10px}',"",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcHeaderMenu/NcHeaderMenu.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCFD,8BACC,iBAAA,CACA,0BAAA,CACA,2BAAA,CAEA,uCACC,YAAA,CACA,kBAAA,CACA,sBAAA,CACA,0BAAA,CACA,2BAAA,CACA,QAAA,CACA,SAAA,CACA,cAAA,CACA,WAAA,CAGA,sBAAA,CACA,0CAAA,CAGD,oMAIC,SAAA,CAGD,qDACC,YAAA,CAGD,uCACC,cAAA,CACA,YAAA,CACA,QAAA,CACA,OAAA,CACA,qBAAA,CACA,YAAA,CACA,WAAA,CACA,2DAAA,CACA,wCAAA,CACA,6CAAA,CAEA,qDAAA,CAGD,sCACC,iBAAA,CACA,YAAA,CACA,QAAA,CACA,qBAAA,CACA,OAAA,CACA,QAAA,CACA,WAAA,CACA,mBAAA,CACA,+BAAA,CACA,gDAAA,CAGD,uCACC,aAAA,CACA,WAAA,CACA,4BAAA,CACA,eAAA,CACA,8BAAA,CACA,sDACC,gBAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n// content inner and outer margin\n// Also used for menu top-right positioning\n$externalMargin: 8px;\n\n.header-menu {\n\tposition: relative;\n\twidth: var(--header-height);\n\theight: var(--header-height);\n\n\t&__trigger {\n\t\tdisplay: flex;\n\t\talign-items: center;\n\t\tjustify-content: center;\n\t\twidth: var(--header-height);\n\t\theight: var(--header-height);\n\t\tmargin: 0;\n\t\tpadding: 0;\n\t\tcursor: pointer;\n\t\topacity: .85;\n\n\t\t// header is filled with primary or image background\n\t\tfilter: none !important;\n\t\tcolor: var(--color-primary-text) !important;\n\t}\n\n\t&--opened &__trigger,\n\t&__trigger:hover,\n\t&__trigger:focus,\n\t&__trigger:active {\n\t\topacity: 1;\n\t}\n\n\t&__trigger:focus-visible {\n\t\toutline: none;\n\t}\n\n\t&__wrapper {\n\t\tposition: fixed;\n\t\tz-index: 2000;\n\t\ttop: 50px;\n\t\tright: 0;\n\t\tbox-sizing: border-box;\n\t\tmargin: 0 $externalMargin;\n\t\tpadding: 8px;\n\t\tborder-radius: 0 0 var(--border-radius) var(--border-radius);\n\t\tborder-radius: var(--border-radius-large);\n\t\tbackground-color: var(--color-main-background);\n\n\t\tfilter: drop-shadow(0 1px 5px var(--color-box-shadow));\n\t}\n\n\t&__carret {\n\t\tposition: absolute;\n\t\tz-index: 2001; // Because __wrapper is 2000.\n\t\tbottom: 0;\n\t\tleft: calc(50% - 10px);\n\t\twidth: 0;\n\t\theight: 0;\n\t\tcontent: ' ';\n\t\tpointer-events: none;\n\t\tborder: 10px solid transparent;\n\t\tborder-bottom-color: var(--color-main-background);\n\t}\n\n\t&__content {\n\t\toverflow: auto;\n\t\twidth: 350px;\n\t\tmax-width: calc(100vw - 2 * $externalMargin);\n\t\tmin-height: calc(44px * 1.5);\n\t\tmax-height: calc(100vh - 50px * 2);\n\t\t:deep(.empty-content) {\n\t\t\tmargin: 12vh 10px;\n\t\t}\n\t}\n}\n\n"],sourceRoot:""}]);const s=o},6466:(e,t,n)=>{"use strict";n.d(t,{Z:()=>s});var r=n(7537),i=n.n(r),a=n(3645),o=n.n(a)()(i());o.push([e.id,".material-design-icon[data-v-7dba3f6e]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.mention-bubble--primary .mention-bubble__content[data-v-7dba3f6e]{color:var(--color-primary-element-text);background-color:var(--color-primary-element)}.mention-bubble__wrapper[data-v-7dba3f6e]{max-width:150px;height:18px;vertical-align:text-bottom;display:inline-flex;align-items:center}.mention-bubble__content[data-v-7dba3f6e]{display:inline-flex;overflow:hidden;align-items:center;max-width:100%;height:20px;-webkit-user-select:none;user-select:none;padding-right:6px;padding-left:2px;border-radius:10px;background-color:var(--color-background-dark)}.mention-bubble__icon[data-v-7dba3f6e]{position:relative;width:16px;height:16px;border-radius:8px;background-color:var(--color-background-darker);background-repeat:no-repeat;background-position:center;background-size:12px}.mention-bubble__icon--with-avatar[data-v-7dba3f6e]{color:inherit;background-size:cover}.mention-bubble__title[data-v-7dba3f6e]{overflow:hidden;margin-left:2px;white-space:nowrap;text-overflow:ellipsis}.mention-bubble__title[data-v-7dba3f6e]::before{content:attr(title)}.mention-bubble__select[data-v-7dba3f6e]{position:absolute;z-index:-1;left:-1000px}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcRichContenteditable/NcMentionBubble.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CAAA,mECCC,uCAAA,CACA,6CAAA,CAGD,0CACC,eAXiB,CAajB,WAAA,CACA,0BAAA,CACA,mBAAA,CACA,kBAAA,CAGD,0CACC,mBAAA,CACA,eAAA,CACA,kBAAA,CACA,cAAA,CACA,WAzBc,CA0Bd,wBAAA,CACA,gBAAA,CACA,iBAAA,CACA,gBA3Be,CA4Bf,kBAAA,CACA,6CAAA,CAGD,uCACC,iBAAA,CACA,UAjCmB,CAkCnB,WAlCmB,CAmCnB,iBAAA,CACA,+CAAA,CACA,2BAAA,CACA,0BAAA,CACA,oBAAA,CAEA,oDACC,aAAA,CACA,qBAAA,CAIF,wCACC,eAAA,CACA,eAlDe,CAmDf,kBAAA,CACA,sBAAA,CAEA,gDACC,mBAAA,CAKF,yCACC,iBAAA,CACA,UAAA,CACA,YAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n$bubble-height: 20px;\n$bubble-max-width: 150px;\n$bubble-padding: 2px;\n$bubble-avatar-size: $bubble-height - 2 * $bubble-padding;\n\n.mention-bubble {\n\t&--primary &__content {\n\t\tcolor: var(--color-primary-element-text);\n\t\tbackground-color: var(--color-primary-element);\n\t}\n\n\t&__wrapper {\n\t\tmax-width: $bubble-max-width;\n\t\t// Align with text\n\t\theight: $bubble-height - $bubble-padding;\n\t\tvertical-align: text-bottom;\n\t\tdisplay: inline-flex;\n\t\talign-items: center;\n\t}\n\n\t&__content {\n\t\tdisplay: inline-flex;\n\t\toverflow: hidden;\n\t\talign-items: center;\n\t\tmax-width: 100%;\n\t\theight: $bubble-height ;\n\t\t-webkit-user-select: none;\n\t\tuser-select: none;\n\t\tpadding-right: $bubble-padding * 3;\n\t\tpadding-left: $bubble-padding;\n\t\tborder-radius: math.div($bubble-height, 2);\n\t\tbackground-color: var(--color-background-dark);\n\t}\n\n\t&__icon {\n\t\tposition: relative;\n\t\twidth: $bubble-avatar-size;\n\t\theight: $bubble-avatar-size;\n\t\tborder-radius: math.div($bubble-avatar-size, 2);\n\t\tbackground-color: var(--color-background-darker);\n\t\tbackground-repeat: no-repeat;\n\t\tbackground-position: center;\n\t\tbackground-size: $bubble-avatar-size - 2 * $bubble-padding;\n\n\t\t&--with-avatar {\n\t\t\tcolor: inherit;\n\t\t\tbackground-size: cover;\n\t\t}\n\t}\n\n\t&__title {\n\t\toverflow: hidden;\n\t\tmargin-left: $bubble-padding;\n\t\twhite-space: nowrap;\n\t\ttext-overflow: ellipsis;\n\t\t// Put title in ::before so it is not selectable\n\t\t&::before {\n\t\t\tcontent: attr(title);\n\t\t}\n\t}\n\n\t// Hide the mention id so it is selectable\n\t&__select {\n\t\tposition: absolute;\n\t\tz-index: -1;\n\t\tleft: -1000px;\n\t}\n}\n\n"],sourceRoot:""}]);const s=o},3645:e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var n="",r=void 0!==t[5];return t[4]&&(n+="@supports (".concat(t[4],") {")),t[2]&&(n+="@media ".concat(t[2]," {")),r&&(n+="@layer".concat(t[5].length>0?" ".concat(t[5]):""," {")),n+=e(t),r&&(n+="}"),t[2]&&(n+="}"),t[4]&&(n+="}"),n})).join("")},t.i=function(e,n,r,i,a){"string"==typeof e&&(e=[[null,e,void 0]]);var o={};if(r)for(var s=0;s<this.length;s++){var c=this[s][0];null!=c&&(o[c]=!0)}for(var l=0;l<e.length;l++){var d=[].concat(e[l]);r&&o[d[0]]||(void 0!==a&&(void 0===d[5]||(d[1]="@layer".concat(d[5].length>0?" ".concat(d[5]):""," {").concat(d[1],"}")),d[5]=a),n&&(d[2]?(d[1]="@media ".concat(d[2]," {").concat(d[1],"}"),d[2]=n):d[2]=n),i&&(d[4]?(d[1]="@supports (".concat(d[4],") {").concat(d[1],"}"),d[4]=i):d[4]="".concat(i)),t.push(d))}},t}},7537:e=>{"use strict";e.exports=function(e){var t=e[1],n=e[3];if(!n)return t;if("function"==typeof btoa){var r=btoa(unescape(encodeURIComponent(JSON.stringify(n)))),i="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(r),a="/*# ".concat(i," */");return[t].concat([a]).join("\n")}return[t].join("\n")}},3379:e=>{"use strict";var t=[];function n(e){for(var n=-1,r=0;r<t.length;r++)if(t[r].identifier===e){n=r;break}return n}function r(e,r){for(var a={},o=[],s=0;s<e.length;s++){var c=e[s],l=r.base?c[0]+r.base:c[0],d=a[l]||0,u="".concat(l," ").concat(d);a[l]=d+1;var A=n(u),p={css:c[1],media:c[2],sourceMap:c[3],supports:c[4],layer:c[5]};if(-1!==A)t[A].references++,t[A].updater(p);else{var h=i(p,r);r.byIndex=s,t.splice(s,0,{identifier:u,updater:h,references:1})}o.push(u)}return o}function i(e,t){var n=t.domAPI(t);n.update(e);return function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap&&t.supports===e.supports&&t.layer===e.layer)return;n.update(e=t)}else n.remove()}}e.exports=function(e,i){var a=r(e=e||[],i=i||{});return function(e){e=e||[];for(var o=0;o<a.length;o++){var s=n(a[o]);t[s].references--}for(var c=r(e,i),l=0;l<a.length;l++){var d=n(a[l]);0===t[d].references&&(t[d].updater(),t.splice(d,1))}a=c}}},569:e=>{"use strict";var t={};e.exports=function(e,n){var r=function(e){if(void 0===t[e]){var n=document.querySelector(e);if(window.HTMLIFrameElement&&n instanceof window.HTMLIFrameElement)try{n=n.contentDocument.head}catch(e){n=null}t[e]=n}return t[e]}(e);if(!r)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");r.appendChild(n)}},9216:e=>{"use strict";e.exports=function(e){var t=document.createElement("style");return e.setAttributes(t,e.attributes),e.insert(t,e.options),t}},3565:(e,t,n)=>{"use strict";e.exports=function(e){var t=n.nc;t&&e.setAttribute("nonce",t)}},7795:e=>{"use strict";e.exports=function(e){if("undefined"==typeof document)return{update:function(){},remove:function(){}};var t=e.insertStyleElement(e);return{update:function(n){!function(e,t,n){var r="";n.supports&&(r+="@supports (".concat(n.supports,") {")),n.media&&(r+="@media ".concat(n.media," {"));var i=void 0!==n.layer;i&&(r+="@layer".concat(n.layer.length>0?" ".concat(n.layer):""," {")),r+=n.css,i&&(r+="}"),n.media&&(r+="}"),n.supports&&(r+="}");var a=n.sourceMap;a&&"undefined"!=typeof btoa&&(r+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(a))))," */")),t.styleTagTransform(r,e,t.options)}(t,e,n)},remove:function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(t)}}}},4589:e=>{"use strict";e.exports=function(e,t){if(t.styleSheet)t.styleSheet.cssText=e;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(e))}}},3330:(e,t,n)=>{"use strict";n.d(t,{Z:()=>v});var r=n(4262);const i={name:"NcMentionBubble",props:{id:{type:String,required:!0},title:{type:String,required:!0},icon:{type:String,required:!0},iconUrl:{type:[String,null],default:null},source:{type:String,required:!0},primary:{type:Boolean,default:!1}},computed:{avatarUrl(){return this.iconUrl?this.iconUrl:this.id&&"users"===this.source?this.getAvatarUrl(this.id,44):null},mentionText(){return this.id.includes(" ")||this.id.includes("/")?'@"'.concat(this.id,'"'):"@".concat(this.id)}},methods:{getAvatarUrl:(e,t)=>(0,r.generateUrl)("/avatar/{user}/{size}",{user:e,size:t})}};var a=n(3379),o=n.n(a),s=n(7795),c=n.n(s),l=n(569),d=n.n(l),u=n(3565),A=n.n(u),p=n(9216),h=n.n(p),b=n(4589),C=n.n(b),m=n(6466),f={};f.styleTagTransform=C(),f.setAttributes=A(),f.insert=d().bind(null,"head"),f.domAPI=c(),f.insertStyleElement=h();o()(m.Z,f);m.Z&&m.Z.locals&&m.Z.locals;const v=(0,n(1900).Z)(i,(function(){var e=this,t=e._self._c;return t("span",{staticClass:"mention-bubble",class:{"mention-bubble--primary":e.primary},attrs:{contenteditable:"false"}},[t("span",{staticClass:"mention-bubble__wrapper"},[t("span",{staticClass:"mention-bubble__content"},[t("span",{staticClass:"mention-bubble__icon",class:[e.icon,"mention-bubble__icon--".concat(e.avatarUrl?"with-avatar":"")],style:e.avatarUrl?{backgroundImage:"url(".concat(e.avatarUrl,")")}:null}),e._v(" "),t("span",{staticClass:"mention-bubble__title",attrs:{role:"heading",title:e.title}})]),e._v(" "),t("span",{staticClass:"mention-bubble__select",attrs:{role:"none"}},[e._v(e._s(e.mentionText))])])])}),[],!1,null,"7dba3f6e",null).exports},156:()=>{},1900:(e,t,n)=>{"use strict";function r(e,t,n,r,i,a,o,s){var c,l="function"==typeof e?e.options:e;if(t&&(l.render=t,l.staticRenderFns=n,l._compiled=!0),r&&(l.functional=!0),a&&(l._scopeId="data-v-"+a),o?(c=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),i&&i.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(o)},l._ssrRegister=c):i&&(c=s?function(){i.call(this,(l.functional?this.parent:this).$root.$options.shadowRoot)}:i),c)if(l.functional){l._injectStyles=c;var d=l.render;l.render=function(e,t){return c.call(t),d(e,t)}}else{var u=l.beforeCreate;l.beforeCreate=u?[].concat(u,c):[c]}return{exports:e,options:l}}n.d(t,{Z:()=>r})},3607:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.js")},768:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.cjs")},4262:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js")},4055:e=>{"use strict";e.exports=__webpack_require__(/*! @vueuse/components */ "./node_modules/@vueuse/components/index.cjs")},4505:e=>{"use strict";e.exports=__webpack_require__(/*! focus-trap */ "./node_modules/focus-trap/dist/focus-trap.esm.js")},2734:e=>{"use strict";e.exports=__webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js")}},t={};function n(r){var i=t[r];if(void 0!==i)return i.exports;var a=t[r]={id:r,exports:{}};return e[r](a,a.exports,n),a.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.nc=void 0;var r={};return(()=>{"use strict";n.r(r),n.d(r,{default:()=>k});var e=n(4055),t=n(4505),i=n(3351),a=n(1206);const o={name:"NcHeaderMenu",directives:{ClickOutside:e.vOnClickOutside},mixins:[i.BW],props:{id:{type:String,required:!0},ariaLabel:{type:String,default:""},open:{type:Boolean,default:!1}},emits:["close","closed","open","opened","update:open","cancel"],data(){var e,t,n;return{focusTrap:null,opened:this.open,shortcutsDisabled:null===(e=window.OCP)||void 0===e||null===(t=e.Accessibility)||void 0===t||null===(n=t.disableKeyboardShortcuts)||void 0===n?void 0:n.call(t)}},computed:{clickOutsideConfig(){return[this.closeMenu,this.clickOutsideOptions]}},watch:{open(e){e?this.openMenu():this.closeMenu()}},mounted(){document.addEventListener("keydown",this.onKeyDown)},beforeDestroy(){document.removeEventListener("keydown",this.onKeyDown)},methods:{toggleMenu(){this.opened?this.closeMenu():this.openMenu()},closeMenu(){let e=arguments.length>0&&void 0!==arguments[0]&&arguments[0];this.opened=!1,this.$emit(e?"cancel":"close"),this.$emit("update:open",!1),this.clearFocusTrap(),this.$nextTick((()=>{this.$emit("closed")}))},openMenu(){this.opened=!0,this.$emit("open"),this.$emit("update:open",!0),this.$nextTick((()=>{this.useFocusTrap(),this.$emit("opened")}))},onKeyDown(e){!this.shortcutsDisabled&&this.opened&&"Escape"===e.key&&(e.preventDefault(),this.closeMenu(!0))},async useFocusTrap(){if(this.focusTrap)return;const e=this.$refs.content;this.focusTrap=(0,t.createFocusTrap)(e,{allowOutsideClick:!0,trapStack:(0,a.L)(),fallbackFocus:this.$refs.trigger}),this.focusTrap.activate()},clearFocusTrap(){var e;null===(e=this.focusTrap)||void 0===e||e.deactivate(),this.focusTrap=null}}};var s=n(3379),c=n.n(s),l=n(7795),d=n.n(l),u=n(569),A=n.n(u),p=n(3565),h=n.n(p),b=n(9216),C=n.n(b),m=n(4589),f=n.n(m),v=n(4473),g={};g.styleTagTransform=f(),g.setAttributes=h(),g.insert=A().bind(null,"head"),g.domAPI=d(),g.insertStyleElement=C();c()(v.Z,g);v.Z&&v.Z.locals&&v.Z.locals;var x=n(1900),_=n(156),y=n.n(_),w=(0,x.Z)(o,(function(){var e=this,t=e._self._c;return t("div",{directives:[{name:"click-outside",rawName:"v-click-outside",value:e.clickOutsideConfig,expression:"clickOutsideConfig"}],staticClass:"header-menu",class:{"header-menu--opened":e.opened},attrs:{id:e.id}},[t("a",{ref:"trigger",staticClass:"header-menu__trigger",attrs:{href:"#","aria-label":e.ariaLabel,"aria-controls":"header-menu-".concat(e.id),"aria-expanded":e.opened.toString()},on:{click:function(t){return t.preventDefault(),e.toggleMenu.apply(null,arguments)}}},[e._t("trigger")],2),e._v(" "),t("div",{directives:[{name:"show",rawName:"v-show",value:e.opened,expression:"opened"}],staticClass:"header-menu__carret"}),e._v(" "),t("div",{directives:[{name:"show",rawName:"v-show",value:e.opened,expression:"opened"}],staticClass:"header-menu__wrapper",attrs:{id:"header-menu-".concat(e.id),role:"menu"}},[t("div",{ref:"content",staticClass:"header-menu__content"},[e._t("default")],2)])])}),[],!1,null,"8a70222c",null);"function"==typeof y()&&y()(w);const k=w.exports})(),r})()));
//# sourceMappingURL=NcHeaderMenu.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcHighlight.js":
/*!********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcHighlight.js ***!
  \********************************************************************/
/***/ (function(module) {

/*! For license information please see NcHighlight.js.LICENSE.txt */
!function(t,e){ true?module.exports=e():0}(self,(()=>(()=>{var t={1336:(t,e,n)=>{"use strict";n.d(e,{Z:()=>r});const r=(t,e)=>{const n=[];let r=0,s=t.toLowerCase().indexOf(e.toLowerCase(),r),o=0;for(;s>-1&&o<t.length;)r=s+e.length,n.push({start:s,end:r}),s=t.toLowerCase().indexOf(e.toLowerCase(),r),o++;return n}},6274:()=>{},1900:(t,e,n)=>{"use strict";function r(t,e,n,r,s,o,i,h){var a,l="function"==typeof t?t.options:t;if(e&&(l.render=e,l.staticRenderFns=n,l._compiled=!0),r&&(l.functional=!0),o&&(l._scopeId="data-v-"+o),i?(a=function(t){(t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),s&&s.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(i)},l._ssrRegister=a):s&&(a=h?function(){s.call(this,(l.functional?this.parent:this).$root.$options.shadowRoot)}:s),a)if(l.functional){l._injectStyles=a;var d=l.render;l.render=function(t,e){return a.call(e),d(t,e)}}else{var u=l.beforeCreate;l.beforeCreate=u?[].concat(u,a):[a]}return{exports:t,options:l}}n.d(e,{Z:()=>r})}},e={};function n(r){var s=e[r];if(void 0!==s)return s.exports;var o=e[r]={exports:{}};return t[r](o,o.exports,n),o.exports}n.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return n.d(e,{a:e}),e},n.d=(t,e)=>{for(var r in e)n.o(e,r)&&!n.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:e[r]})},n.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e),n.r=t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})};var r={};return(()=>{"use strict";n.r(r),n.d(r,{default:()=>a});var t=n(1336);const e={name:"NcHighlight",props:{text:{type:String,default:""},search:{type:String,default:""},highlight:{type:Array,default:()=>[]}},computed:{ranges(){let e=[];return this.search||0!==this.highlight.length?(e=this.highlight.length>0?this.highlight:(0,t.Z)(this.text,this.search),e.forEach(((t,n)=>{t.end<t.start&&(e[n]={start:t.end,end:t.start})})),e=e.reduce(((t,e)=>(e.start<this.text.length&&e.end>0&&t.push({start:e.start<0?0:e.start,end:e.end>this.text.length?this.text.length:e.end}),t)),[]),e.sort(((t,e)=>t.start-e.start)),e=e.reduce(((t,e)=>{if(t.length){const n=t.length-1;t[n].end>=e.start?t[n]={start:t[n].start,end:Math.max(t[n].end,e.end)}:t.push(e)}else t.push(e);return t}),[]),e):e},chunks(){if(0===this.ranges.length)return[{start:0,end:this.text.length,highlight:!1,text:this.text}];const t=[];let e=0,n=0;for(;e<this.text.length;){const r=this.ranges[n];r.start!==e?(t.push({start:e,end:r.start,highlight:!1,text:this.text.slice(e,r.start)}),e=r.start):(t.push({...r,highlight:!0,text:this.text.slice(r.start,r.end)}),n++,e=r.end,n>=this.ranges.length&&e<this.text.length&&(t.push({start:e,end:this.text.length,highlight:!1,text:this.text.slice(e)}),e=this.text.length))}return t}},render(t){return this.ranges.length?t("span",{},this.chunks.map((e=>e.highlight?t("strong",{},e.text):e.text))):t("span",{},this.text)}};var s=n(1900),o=n(6274),i=n.n(o),h=(0,s.Z)(e,undefined,undefined,!1,null,null,null);"function"==typeof i()&&i()(h);const a=h.exports})(),r})()));
//# sourceMappingURL=NcHighlight.js.map

/***/ }),

/***/ "./core/src/services/UnifiedSearchService.js":
/*!***************************************************!*\
  !*** ./core/src/services/UnifiedSearchService.js ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultLimit: function() { return /* binding */ defaultLimit; },
/* harmony export */   enableLiveSearch: function() { return /* binding */ enableLiveSearch; },
/* harmony export */   getTypes: function() { return /* binding */ getTypes; },
/* harmony export */   minSearchLength: function() { return /* binding */ minSearchLength; },
/* harmony export */   regexFilterIn: function() { return /* binding */ regexFilterIn; },
/* harmony export */   regexFilterNot: function() { return /* binding */ regexFilterNot; },
/* harmony export */   search: function() { return /* binding */ search; }
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.es.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2020, John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
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




var defaultLimit = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('unified-search', 'limit-default');
var minSearchLength = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('unified-search', 'min-search-length', 1);
var enableLiveSearch = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('unified-search', 'live-search', true);
var regexFilterIn = /(^|\s)in:([a-z_-]+)/ig;
var regexFilterNot = /(^|\s)-in:([a-z_-]+)/ig;

/**
 * Create a cancel token
 *
 * @return {import('axios').CancelTokenSource}
 */
var createCancelToken = function createCancelToken() {
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].CancelToken.source();
};

/**
 * Get the list of available search providers
 *
 * @return {Promise<Array>}
 */
function getTypes() {
  return _getTypes.apply(this, arguments);
}

/**
 * Get the list of available search providers
 *
 * @param {object} options destructuring object
 * @param {string} options.type the type to search
 * @param {string} options.query the search
 * @param {number|string|undefined} options.cursor the offset for paginated searches
 * @return {object} {request: Promise, cancel: Promise}
 */
function _getTypes() {
  _getTypes = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
    var _yield$axios$get, data;
    return _regeneratorRuntime().wrap(function _callee2$(_context2) {
      while (1) switch (_context2.prev = _context2.next) {
        case 0:
          _context2.prev = 0;
          _context2.next = 3;
          return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('search/providers'), {
            params: {
              // Sending which location we're currently at
              from: window.location.pathname.replace('/index.php', '') + window.location.search
            }
          });
        case 3:
          _yield$axios$get = _context2.sent;
          data = _yield$axios$get.data;
          if (!('ocs' in data && 'data' in data.ocs && Array.isArray(data.ocs.data) && data.ocs.data.length > 0)) {
            _context2.next = 7;
            break;
          }
          return _context2.abrupt("return", data.ocs.data);
        case 7:
          _context2.next = 12;
          break;
        case 9:
          _context2.prev = 9;
          _context2.t0 = _context2["catch"](0);
          console.error(_context2.t0);
        case 12:
          return _context2.abrupt("return", []);
        case 13:
        case "end":
          return _context2.stop();
      }
    }, _callee2, null, [[0, 9]]);
  }));
  return _getTypes.apply(this, arguments);
}
function search(_ref) {
  var type = _ref.type,
    query = _ref.query,
    cursor = _ref.cursor;
  /**
   * Generate an axios cancel token
   */
  var cancelToken = createCancelToken();
  var request = /*#__PURE__*/function () {
    var _ref2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
      return _regeneratorRuntime().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            return _context.abrupt("return", _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('search/providers/{type}/search', {
              type: type
            }), {
              cancelToken: cancelToken.token,
              params: {
                term: query,
                cursor: cursor,
                // Sending which location we're currently at
                from: window.location.pathname.replace('/index.php', '') + window.location.search
              }
            }));
          case 1:
          case "end":
            return _context.stop();
        }
      }, _callee);
    }));
    return function request() {
      return _ref2.apply(this, arguments);
    };
  }();
  return {
    request: request,
    cancel: cancelToken.cancel
  };
}

/***/ }),

/***/ "./core/src/unified-search.js":
/*!************************************!*\
  !*** ./core/src/unified-search.js ***!
  \************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _views_UnifiedSearch_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./views/UnifiedSearch.vue */ "./core/src/views/UnifiedSearch.vue");
/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
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







// eslint-disable-next-line camelcase
__webpack_require__.nc = btoa((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getRequestToken)());
var logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('unified-search').detectUser().build();
vue__WEBPACK_IMPORTED_MODULE_4__["default"].mixin({
  data: function data() {
    return {
      logger: logger
    };
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate,
    n: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translatePlural
  }
});
/* harmony default export */ __webpack_exports__["default"] = (new vue__WEBPACK_IMPORTED_MODULE_4__["default"]({
  el: '#unified-search',
  // eslint-disable-next-line vue/match-component-file-name
  name: 'UnifiedSearchRoot',
  render: function render(h) {
    return h(_views_UnifiedSearch_vue__WEBPACK_IMPORTED_MODULE_3__["default"]);
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcHighlight_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcHighlight.js */ "./node_modules/@nextcloud/vue/dist/Components/NcHighlight.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcHighlight_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcHighlight_js__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'SearchResult',
  components: {
    NcHighlight: (_nextcloud_vue_dist_Components_NcHighlight_js__WEBPACK_IMPORTED_MODULE_0___default())
  },
  props: {
    thumbnailUrl: {
      type: String,
      default: null
    },
    title: {
      type: String,
      required: true
    },
    subline: {
      type: String,
      default: null
    },
    resourceUrl: {
      type: String,
      default: null
    },
    icon: {
      type: String,
      default: ''
    },
    rounded: {
      type: Boolean,
      default: false
    },
    query: {
      type: String,
      default: ''
    },
    /**
     * Only used for the first result as a visual feedback
     * so we can keep the search input focused but pressing
     * enter still opens the first result
     */
    focused: {
      type: Boolean,
      default: false
    }
  },
  data: function data() {
    return {
      hasValidThumbnail: this.thumbnailUrl && this.thumbnailUrl.trim() !== '',
      loaded: false
    };
  },
  computed: {
    isIconUrl: function isIconUrl() {
      // If we're facing an absolute url
      if (this.icon.startsWith('/')) {
        return true;
      }

      // Otherwise, let's check if this is a valid url
      try {
        // eslint-disable-next-line no-new
        new URL(this.icon);
      } catch (_unused) {
        return false;
      }
      return true;
    }
  },
  watch: {
    // Make sure to reset state on change even when vue recycle the component
    thumbnailUrl: function thumbnailUrl() {
      this.hasValidThumbnail = this.thumbnailUrl && this.thumbnailUrl.trim() !== '';
      this.loaded = false;
    }
  },
  methods: {
    reEmitEvent: function reEmitEvent(e) {
      this.$emit(e.type, e);
    },
    /**
     * If the image fails to load, fallback to iconClass
     */
    onError: function onError() {
      this.hasValidThumbnail = false;
    },
    onLoad: function onLoad() {
      this.loaded = true;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'SearchResultPlaceholders',
  data: function data() {
    return {
      light: null,
      dark: null
    };
  },
  mounted: function mounted() {
    var styles = getComputedStyle(document.documentElement);
    this.dark = styles.getPropertyValue('--color-placeholder-dark');
    this.light = styles.getPropertyValue('--color-placeholder-light');
  },
  methods: {
    randWidth: function randWidth() {
      return Math.floor(Math.random() * 20) + 30;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcHeaderMenu_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcHeaderMenu.js */ "./node_modules/@nextcloud/vue/dist/Components/NcHeaderMenu.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcHeaderMenu_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcHeaderMenu_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var vue_material_design_icons_Magnify_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/Magnify.vue */ "./node_modules/vue-material-design-icons/Magnify.vue");
/* harmony import */ var _components_UnifiedSearch_SearchResult_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../components/UnifiedSearch/SearchResult.vue */ "./core/src/components/UnifiedSearch/SearchResult.vue");
/* harmony import */ var _components_UnifiedSearch_SearchResultPlaceholders_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../components/UnifiedSearch/SearchResultPlaceholders.vue */ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue");
/* harmony import */ var _services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../services/UnifiedSearchService.js */ "./core/src/services/UnifiedSearchService.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }











var REQUEST_FAILED = 0;
var REQUEST_OK = 1;
var REQUEST_CANCELED = 2;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'UnifiedSearch',
  components: {
    Magnify: vue_material_design_icons_Magnify_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcActions: (_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcEmptyContent: (_nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_5___default()),
    NcHeaderMenu: (_nextcloud_vue_dist_Components_NcHeaderMenu_js__WEBPACK_IMPORTED_MODULE_6___default()),
    SearchResult: _components_UnifiedSearch_SearchResult_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    SearchResultPlaceholders: _components_UnifiedSearch_SearchResultPlaceholders_vue__WEBPACK_IMPORTED_MODULE_9__["default"]
  },
  data: function data() {
    return {
      types: [],
      // Cursors per types
      cursors: {},
      // Various search limits per types
      limits: {},
      // Loading types
      loading: {},
      // Reached search types
      reached: {},
      // Pending cancellable requests
      requests: [],
      // List of all results
      results: {},
      query: '',
      focused: null,
      triggered: false,
      defaultLimit: _services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.defaultLimit,
      minSearchLength: _services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.minSearchLength,
      enableLiveSearch: _services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.enableLiveSearch,
      open: false
    };
  },
  computed: {
    typesIDs: function typesIDs() {
      return this.types.map(function (type) {
        return type.id;
      });
    },
    typesNames: function typesNames() {
      return this.types.map(function (type) {
        return type.name;
      });
    },
    typesMap: function typesMap() {
      return this.types.reduce(function (prev, curr) {
        prev[curr.id] = curr.name;
        return prev;
      }, {});
    },
    ariaLabel: function ariaLabel() {
      return t('core', 'Search');
    },
    /**
     * Is there any result to display
     *
     * @return {boolean}
     */
    hasResults: function hasResults() {
      return Object.keys(this.results).length !== 0;
    },
    /**
     * Return ordered results
     *
     * @return {Array}
     */
    orderedResults: function orderedResults() {
      var _this = this;
      return this.typesIDs.filter(function (type) {
        return type in _this.results;
      }).map(function (type) {
        return {
          type: type,
          list: _this.results[type]
        };
      });
    },
    /**
     * Available filters
     * We only show filters that are available on the results
     *
     * @return {string[]}
     */
    availableFilters: function availableFilters() {
      return Object.keys(this.results);
    },
    /**
     * Applied filters
     *
     * @return {string[]}
     */
    usedFiltersIn: function usedFiltersIn() {
      var match;
      var filters = [];
      while ((match = _services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.regexFilterIn.exec(this.query)) !== null) {
        filters.push(match[2]);
      }
      return filters;
    },
    /**
     * Applied anti filters
     *
     * @return {string[]}
     */
    usedFiltersNot: function usedFiltersNot() {
      var match;
      var filters = [];
      while ((match = _services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.regexFilterNot.exec(this.query)) !== null) {
        filters.push(match[2]);
      }
      return filters;
    },
    /**
     * Valid query empty content title
     *
     * @return {string}
     */
    validQueryTitle: function validQueryTitle() {
      return this.triggered ? t('core', 'No results for {query}', {
        query: this.query
      }) : t('core', 'Press Enter to start searching');
    },
    /**
     * Short query empty content description
     *
     * @return {string}
     */
    shortQueryDescription: function shortQueryDescription() {
      if (!this.isShortQuery) {
        return '';
      }
      return n('core', 'Please enter {minSearchLength} character or more to search', 'Please enter {minSearchLength} characters  or more to search', this.minSearchLength, {
        minSearchLength: this.minSearchLength
      });
    },
    /**
     * Is the current search too short
     *
     * @return {boolean}
     */
    isShortQuery: function isShortQuery() {
      return this.query && this.query.trim().length < _services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.minSearchLength;
    },
    /**
     * Is the current search valid
     *
     * @return {boolean}
     */
    isValidQuery: function isValidQuery() {
      return this.query && this.query.trim() !== '' && !this.isShortQuery;
    },
    /**
     * Have we reached the end of all types searches
     *
     * @return {boolean}
     */
    isDoneSearching: function isDoneSearching() {
      return Object.values(this.reached).every(function (state) {
        return state === false;
      });
    },
    /**
     * Is there any search in progress
     *
     * @return {boolean}
     */
    isLoading: function isLoading() {
      return Object.values(this.loading).some(function (state) {
        return state === true;
      });
    }
  },
  created: function created() {
    var _this2 = this;
    return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
      return _regeneratorRuntime().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:navigation:changed', _this2.onNavigationChange);
            _context.next = 3;
            return (0,_services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.getTypes)();
          case 3:
            _this2.types = _context.sent;
            _this2.logger.debug('Unified Search initialized with the following providers', _this2.types);
          case 5:
          case "end":
            return _context.stop();
        }
      }, _callee);
    }))();
  },
  beforeDestroy: function beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.unsubscribe)('files:navigation:changed', this.onNavigationChange);
  },
  mounted: function mounted() {
    var _this3 = this;
    if (OCP.Accessibility.disableKeyboardShortcuts()) {
      return;
    }
    document.addEventListener('keydown', function (event) {
      // if not already opened, allows us to trigger default browser on second keydown
      if (event.ctrlKey && event.key === 'f' && !_this3.open) {
        event.preventDefault();
        _this3.open = true;
      }

      // https://www.w3.org/WAI/GL/wiki/Using_ARIA_menus
      if (_this3.open) {
        // If arrow down, focus next result
        if (event.key === 'ArrowDown') {
          _this3.focusNext(event);
        }

        // If arrow up, focus prev result
        if (event.key === 'ArrowUp') {
          _this3.focusPrev(event);
        }
      }
    });
  },
  methods: {
    onOpen: function onOpen() {
      var _this4 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
          while (1) switch (_context2.prev = _context2.next) {
            case 0:
              _context2.next = 2;
              return (0,_services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.getTypes)();
            case 2:
              _this4.types = _context2.sent;
            case 3:
            case "end":
              return _context2.stop();
          }
        }, _callee2);
      }))();
    },
    onClose: function onClose() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('nextcloud:unified-search.close');
    },
    onNavigationChange: function onNavigationChange() {
      this.$el.querySelector('form[role="search"]').reset();
    },
    /**
     * Reset the search state
     */
    onReset: function onReset() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('nextcloud:unified-search.reset');
      this.logger.debug('Search reset');
      this.query = '';
      this.resetState();
      this.focusInput();
    },
    resetState: function resetState() {
      var _this5 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
        return _regeneratorRuntime().wrap(function _callee3$(_context3) {
          while (1) switch (_context3.prev = _context3.next) {
            case 0:
              _this5.cursors = {};
              _this5.limits = {};
              _this5.reached = {};
              _this5.results = {};
              _this5.focused = null;
              _this5.triggered = false;
              _context3.next = 8;
              return _this5.cancelPendingRequests();
            case 8:
            case "end":
              return _context3.stop();
          }
        }, _callee3);
      }))();
    },
    /**
     * Cancel any ongoing searches
     */
    cancelPendingRequests: function cancelPendingRequests() {
      var _this6 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4() {
        var requests;
        return _regeneratorRuntime().wrap(function _callee4$(_context4) {
          while (1) switch (_context4.prev = _context4.next) {
            case 0:
              // Cloning so we can keep processing other requests
              requests = _this6.requests.slice(0);
              _this6.requests = [];

              // Cancel all pending requests
              _context4.next = 4;
              return Promise.all(requests.map(function (cancel) {
                return cancel();
              }));
            case 4:
            case "end":
              return _context4.stop();
          }
        }, _callee4);
      }))();
    },
    /**
     * Focus the search input on next tick
     */
    focusInput: function focusInput() {
      var _this7 = this;
      this.$nextTick(function () {
        _this7.$refs.input.focus();
        _this7.$refs.input.select();
      });
    },
    /**
     * If we have results already, open first one
     * If not, trigger the search again
     */
    onInputEnter: function onInputEnter() {
      if (this.hasResults) {
        var results = this.getResultsList();
        results[0].click();
        return;
      }
      this.onInput();
    },
    /**
     * Start searching on input
     */
    onInput: function onInput() {
      var _this8 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6() {
        var _iterator, _step, type, types, query;
        return _regeneratorRuntime().wrap(function _callee6$(_context6) {
          while (1) switch (_context6.prev = _context6.next) {
            case 0:
              // emit the search query
              (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('nextcloud:unified-search.search', {
                query: _this8.query
              });

              // Do not search if not long enough
              if (!(_this8.query.trim() === '' || _this8.isShortQuery)) {
                _context6.next = 5;
                break;
              }
              _iterator = _createForOfIteratorHelper(_this8.typesIDs);
              try {
                for (_iterator.s(); !(_step = _iterator.n()).done;) {
                  type = _step.value;
                  _this8.$delete(_this8.results, type);
                }
              } catch (err) {
                _iterator.e(err);
              } finally {
                _iterator.f();
              }
              return _context6.abrupt("return");
            case 5:
              types = _this8.typesIDs;
              query = _this8.query; // Filter out types
              if (_this8.usedFiltersNot.length > 0) {
                types = _this8.typesIDs.filter(function (type) {
                  return _this8.usedFiltersNot.indexOf(type) === -1;
                });
              }

              // Only use those filters if any and check if they are valid
              if (_this8.usedFiltersIn.length > 0) {
                types = _this8.typesIDs.filter(function (type) {
                  return _this8.usedFiltersIn.indexOf(type) > -1;
                });
              }

              // Remove any filters from the query
              query = query.replace(_services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.regexFilterIn, '').replace(_services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.regexFilterNot, '');

              // Reset search if the query changed
              _context6.next = 12;
              return _this8.resetState();
            case 12:
              _this8.triggered = true;
              if (types.length) {
                _context6.next = 16;
                break;
              }
              // no results since no types were selected
              _this8.logger.error('No types to search in');
              return _context6.abrupt("return");
            case 16:
              _this8.$set(_this8.loading, 'all', true);
              _this8.logger.debug("Searching ".concat(query, " in"), types);
              Promise.all(types.map( /*#__PURE__*/function () {
                var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5(type) {
                  var _search, request, cancel, _yield$request, data;
                  return _regeneratorRuntime().wrap(function _callee5$(_context5) {
                    while (1) switch (_context5.prev = _context5.next) {
                      case 0:
                        _context5.prev = 0;
                        // Init cancellable request
                        _search = (0,_services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.search)({
                          type: type,
                          query: query
                        }), request = _search.request, cancel = _search.cancel;
                        _this8.requests.push(cancel);

                        // Fetch results
                        _context5.next = 5;
                        return request();
                      case 5:
                        _yield$request = _context5.sent;
                        data = _yield$request.data;
                        // Process results
                        if (data.ocs.data.entries.length > 0) {
                          _this8.$set(_this8.results, type, data.ocs.data.entries);
                        } else {
                          _this8.$delete(_this8.results, type);
                        }

                        // Save cursor if any
                        if (data.ocs.data.cursor) {
                          _this8.$set(_this8.cursors, type, data.ocs.data.cursor);
                        } else if (!data.ocs.data.isPaginated) {
                          // If no cursor and no pagination, we save the default amount
                          // provided by server's initial state `defaultLimit`
                          _this8.$set(_this8.limits, type, _this8.defaultLimit);
                        }

                        // Check if we reached end of pagination
                        if (data.ocs.data.entries.length < _this8.defaultLimit) {
                          _this8.$set(_this8.reached, type, true);
                        }

                        // If none already focused, focus the first rendered result
                        if (_this8.focused === null) {
                          _this8.focused = 0;
                        }
                        return _context5.abrupt("return", REQUEST_OK);
                      case 14:
                        _context5.prev = 14;
                        _context5.t0 = _context5["catch"](0);
                        _this8.$delete(_this8.results, type);

                        // If this is not a cancelled throw
                        if (!(_context5.t0.response && _context5.t0.response.status)) {
                          _context5.next = 21;
                          break;
                        }
                        _this8.logger.error("Error searching for ".concat(_this8.typesMap[type]), _context5.t0);
                        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(_this8.t('core', 'An error occurred while searching for {type}', {
                          type: _this8.typesMap[type]
                        }));
                        return _context5.abrupt("return", REQUEST_FAILED);
                      case 21:
                        return _context5.abrupt("return", REQUEST_CANCELED);
                      case 22:
                      case "end":
                        return _context5.stop();
                    }
                  }, _callee5, null, [[0, 14]]);
                }));
                return function (_x) {
                  return _ref.apply(this, arguments);
                };
              }())).then(function (results) {
                // Do not declare loading finished if the request have been cancelled
                // This means another search was triggered and we're therefore still loading
                if (results.some(function (result) {
                  return result === REQUEST_CANCELED;
                })) {
                  return;
                }
                // We finished all searches
                _this8.loading = {};
              });
            case 19:
            case "end":
              return _context6.stop();
          }
        }, _callee6);
      }))();
    },
    onInputDebounced: _services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.enableLiveSearch ? debounce__WEBPACK_IMPORTED_MODULE_0___default()(function (e) {
      this.onInput(e);
    }, 500) : function () {
      this.triggered = false;
    },
    /**
     * Load more results for the provided type
     *
     * @param {string} type type
     */
    loadMore: function loadMore(type) {
      var _this9 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee7() {
        var _search2, request, cancel, _yield$request2, data, _this9$results$type;
        return _regeneratorRuntime().wrap(function _callee7$(_context7) {
          while (1) switch (_context7.prev = _context7.next) {
            case 0:
              if (!_this9.loading[type]) {
                _context7.next = 2;
                break;
              }
              return _context7.abrupt("return");
            case 2:
              if (!_this9.cursors[type]) {
                _context7.next = 14;
                break;
              }
              // Init cancellable request
              _search2 = (0,_services_UnifiedSearchService_js__WEBPACK_IMPORTED_MODULE_10__.search)({
                type: type,
                query: _this9.query,
                cursor: _this9.cursors[type]
              }), request = _search2.request, cancel = _search2.cancel;
              _this9.requests.push(cancel);

              // Fetch results
              _context7.next = 7;
              return request();
            case 7:
              _yield$request2 = _context7.sent;
              data = _yield$request2.data;
              // Save cursor if any
              if (data.ocs.data.cursor) {
                _this9.$set(_this9.cursors, type, data.ocs.data.cursor);
              }

              // Process results
              if (data.ocs.data.entries.length > 0) {
                (_this9$results$type = _this9.results[type]).push.apply(_this9$results$type, _toConsumableArray(data.ocs.data.entries));
              }

              // Check if we reached end of pagination
              if (data.ocs.data.entries.length < _this9.defaultLimit) {
                _this9.$set(_this9.reached, type, true);
              }
              _context7.next = 15;
              break;
            case 14:
              // If no cursor, we might have all the results already,
              // let's fake pagination and show the next xxx entries
              if (_this9.limits[type] && _this9.limits[type] >= 0) {
                _this9.limits[type] += _this9.defaultLimit;

                // Check if we reached end of pagination
                if (_this9.limits[type] >= _this9.results[type].length) {
                  _this9.$set(_this9.reached, type, true);
                }
              }
            case 15:
              // Focus result after render
              if (_this9.focused !== null) {
                _this9.$nextTick(function () {
                  _this9.focusIndex(_this9.focused);
                });
              }
            case 16:
            case "end":
              return _context7.stop();
          }
        }, _callee7);
      }))();
    },
    /**
     * Return a subset of the array if the search provider
     * doesn't supports pagination
     *
     * @param {Array} list the results
     * @param {string} type the type
     * @return {Array}
     */
    limitIfAny: function limitIfAny(list, type) {
      if (type in this.limits) {
        return list.slice(0, this.limits[type]);
      }
      return list;
    },
    getResultsList: function getResultsList() {
      return this.$el.querySelectorAll('.unified-search__results .unified-search__result');
    },
    /**
     * Focus the first result if any
     *
     * @param {Event} event the keydown event
     */
    focusFirst: function focusFirst(event) {
      var results = this.getResultsList();
      if (results && results.length > 0) {
        if (event) {
          event.preventDefault();
        }
        this.focused = 0;
        this.focusIndex(this.focused);
      }
    },
    /**
     * Focus the next result if any
     *
     * @param {Event} event the keydown event
     */
    focusNext: function focusNext(event) {
      if (this.focused === null) {
        this.focusFirst(event);
        return;
      }
      var results = this.getResultsList();
      // If we're not focusing the last, focus the next one
      if (results && results.length > 0 && this.focused + 1 < results.length) {
        event.preventDefault();
        this.focused++;
        this.focusIndex(this.focused);
      }
    },
    /**
     * Focus the previous result if any
     *
     * @param {Event} event the keydown event
     */
    focusPrev: function focusPrev(event) {
      if (this.focused === null) {
        this.focusFirst(event);
        return;
      }
      var results = this.getResultsList();
      // If we're not focusing the first, focus the previous one
      if (results && results.length > 0 && this.focused > 0) {
        event.preventDefault();
        this.focused--;
        this.focusIndex(this.focused);
      }
    },
    /**
     * Focus the specified result index if it exists
     *
     * @param {number} index the result index
     */
    focusIndex: function focusIndex(index) {
      var results = this.getResultsList();
      if (results && results[index]) {
        results[index].focus();
      }
    },
    /**
     * Set the current focused element based on the target
     *
     * @param {Event} event the focus event
     */
    setFocusedIndex: function setFocusedIndex(event) {
      var entry = event.target;
      var results = this.getResultsList();
      var index = _toConsumableArray(results).findIndex(function (search) {
        return search === entry;
      });
      if (index > -1) {
        // let's not use focusIndex as the entry is already focused
        this.focused = index;
      }
    },
    onClickFilter: function onClickFilter(filter) {
      this.query = "".concat(this.query, " ").concat(filter).replace(/ {2}/g, ' ').trim();
      this.onInput();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("a", {
    staticClass: "unified-search__result",
    class: {
      "unified-search__result--focused": _vm.focused
    },
    attrs: {
      href: _vm.resourceUrl || "#"
    },
    on: {
      click: _vm.reEmitEvent,
      focus: _vm.reEmitEvent
    }
  }, [_c("div", {
    staticClass: "unified-search__result-icon",
    class: _defineProperty({
      "unified-search__result-icon--rounded": _vm.rounded,
      "unified-search__result-icon--no-preview": !_vm.hasValidThumbnail && !_vm.loaded,
      "unified-search__result-icon--with-thumbnail": _vm.hasValidThumbnail && _vm.loaded
    }, _vm.icon, !_vm.loaded && !_vm.isIconUrl),
    style: {
      backgroundImage: _vm.isIconUrl ? "url(".concat(_vm.icon, ")") : ""
    }
  }, [_vm.hasValidThumbnail ? _c("img", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.loaded,
      expression: "loaded"
    }],
    attrs: {
      src: _vm.thumbnailUrl,
      alt: ""
    },
    on: {
      error: _vm.onError,
      load: _vm.onLoad
    }
  }) : _vm._e()]), _vm._v(" "), _c("span", {
    staticClass: "unified-search__result-content"
  }, [_c("span", {
    staticClass: "unified-search__result-line-one",
    attrs: {
      title: _vm.title
    }
  }, [_c("NcHighlight", {
    attrs: {
      text: _vm.title,
      search: _vm.query
    }
  })], 1), _vm._v(" "), _vm.subline ? _c("span", {
    staticClass: "unified-search__result-line-two",
    attrs: {
      title: _vm.subline
    }
  }, [_vm._v(_vm._s(_vm.subline))]) : _vm._e()])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("ul", [_c("svg", {
    staticClass: "unified-search__result-placeholder-gradient"
  }, [_c("defs", [_c("linearGradient", {
    attrs: {
      id: "unified-search__result-placeholder-gradient"
    }
  }, [_c("stop", {
    attrs: {
      offset: "0%",
      "stop-color": _vm.light
    }
  }, [_c("animate", {
    attrs: {
      attributeName: "stop-color",
      values: "".concat(_vm.light, "; ").concat(_vm.light, "; ").concat(_vm.dark, "; ").concat(_vm.dark, "; ").concat(_vm.light),
      dur: "2s",
      repeatCount: "indefinite"
    }
  })]), _vm._v(" "), _c("stop", {
    attrs: {
      offset: "100%",
      "stop-color": _vm.dark
    }
  }, [_c("animate", {
    attrs: {
      attributeName: "stop-color",
      values: "".concat(_vm.dark, "; ").concat(_vm.light, "; ").concat(_vm.light, "; ").concat(_vm.dark, "; ").concat(_vm.dark),
      dur: "2s",
      repeatCount: "indefinite"
    }
  })])], 1)], 1)]), _vm._v(" "), _vm._l([1, 2, 3], function (placeholder) {
    return _c("li", {
      key: placeholder
    }, [_c("svg", {
      staticClass: "unified-search__result-placeholder",
      attrs: {
        xmlns: "http://www.w3.org/2000/svg",
        fill: "url(#unified-search__result-placeholder-gradient)"
      }
    }, [_c("rect", {
      staticClass: "unified-search__result-placeholder-icon"
    }), _vm._v(" "), _c("rect", {
      staticClass: "unified-search__result-placeholder-line-one"
    }), _vm._v(" "), _c("rect", {
      staticClass: "unified-search__result-placeholder-line-two",
      style: {
        width: "calc(".concat(_vm.randWidth(), "%)")
      }
    })])]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true& ***!
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
  return _c("NcHeaderMenu", {
    staticClass: "unified-search",
    attrs: {
      id: "unified-search",
      "exclude-click-outside-selectors": [".popover"],
      open: _vm.open,
      "aria-label": _vm.ariaLabel
    },
    on: {
      "update:open": function updateOpen($event) {
        _vm.open = $event;
      },
      open: _vm.onOpen,
      close: _vm.onClose
    },
    scopedSlots: _vm._u([{
      key: "trigger",
      fn: function fn() {
        return [_c("Magnify", {
          staticClass: "unified-search__trigger",
          attrs: {
            size: 22 /* fit better next to other 20px icons */
          }
        })];
      },

      proxy: true
    }])
  }, [_vm._v(" "), _c("div", {
    staticClass: "unified-search__input-wrapper"
  }, [_c("label", {
    attrs: {
      for: "unified-search__input"
    }
  }, [_vm._v(_vm._s(_vm.ariaLabel))]), _vm._v(" "), _c("div", {
    staticClass: "unified-search__input-row"
  }, [_c("form", {
    staticClass: "unified-search__form",
    class: {
      "icon-loading-small": _vm.isLoading
    },
    attrs: {
      role: "search"
    },
    on: {
      submit: function submit($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onInputEnter.apply(null, arguments);
      },
      reset: function reset($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onReset.apply(null, arguments);
      }
    }
  }, [_c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.query,
      expression: "query"
    }],
    ref: "input",
    staticClass: "unified-search__form-input",
    class: {
      "unified-search__form-input--with-reset": !!_vm.query
    },
    attrs: {
      id: "unified-search__input",
      type: "search",
      placeholder: _vm.t("core", "Search {types} …", {
        types: _vm.typesNames.join(", ")
      }),
      "aria-describedby": "unified-search-desc"
    },
    domProps: {
      value: _vm.query
    },
    on: {
      input: [function ($event) {
        if ($event.target.composing) return;
        _vm.query = $event.target.value;
      }, _vm.onInputDebounced],
      keypress: function keypress($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")) return null;
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onInputEnter.apply(null, arguments);
      }
    }
  }), _vm._v(" "), _c("p", {
    staticClass: "hidden-visually",
    attrs: {
      id: "unified-search-desc"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Search starts once you start typing and results may be reached with the arrow keys")) + "\n\t\t\t\t")]), _vm._v(" "), !!_vm.query && !_vm.isLoading ? _c("input", {
    staticClass: "unified-search__form-reset icon-close",
    attrs: {
      type: "reset",
      "aria-label": _vm.t("core", "Reset search"),
      value: ""
    }
  }) : _vm._e(), _vm._v(" "), !!_vm.query && !_vm.isLoading && !_vm.enableLiveSearch ? _c("input", {
    staticClass: "unified-search__form-submit icon-confirm",
    attrs: {
      type: "submit",
      "aria-label": _vm.t("core", "Start search"),
      value: ""
    }
  }) : _vm._e()]), _vm._v(" "), _vm.availableFilters.length > 1 ? _c("NcActions", {
    staticClass: "unified-search__filters",
    attrs: {
      placement: "bottom",
      container: ".unified-search__input-wrapper"
    }
  }, _vm._l(_vm.availableFilters, function (filter) {
    return _c("NcActionButton", {
      key: filter,
      attrs: {
        icon: "icon-filter",
        title: _vm.t("core", "Search for {name} only", {
          name: _vm.typesMap[filter]
        })
      },
      on: {
        click: function click($event) {
          $event.stopPropagation();
          return _vm.onClickFilter("in:".concat(filter));
        }
      }
    }, [_vm._v("\n\t\t\t\t\t" + _vm._s("in:".concat(filter)) + "\n\t\t\t\t")]);
  }), 1) : _vm._e()], 1)]), _vm._v(" "), !_vm.hasResults ? [_vm.isLoading ? _c("SearchResultPlaceholders") : _vm.isValidQuery ? _c("NcEmptyContent", {
    attrs: {
      title: _vm.validQueryTitle
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Magnify")];
      },
      proxy: true
    }], null, false, 931131664)
  }) : !_vm.isLoading || _vm.isShortQuery ? _c("NcEmptyContent", {
    attrs: {
      title: _vm.t("core", "Start typing to search"),
      description: _vm.shortQueryDescription
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Magnify")];
      },
      proxy: true
    }], null, false, 931131664)
  }) : _vm._e()] : _vm._l(_vm.orderedResults, function (_ref, typesIndex) {
    var list = _ref.list,
      type = _ref.type;
    return _c("ul", {
      key: type,
      staticClass: "unified-search__results",
      class: "unified-search__results-".concat(type),
      attrs: {
        "aria-label": _vm.typesMap[type]
      }
    }, [_c("h2", {
      staticClass: "unified-search__results-header"
    }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.typesMap[type]) + "\n\t\t\t")]), _vm._v(" "), _vm._l(_vm.limitIfAny(list, type), function (result, index) {
      return _c("li", {
        key: result.resourceUrl
      }, [_c("SearchResult", _vm._b({
        attrs: {
          query: _vm.query,
          focused: _vm.focused === 0 && typesIndex === 0 && index === 0
        },
        on: {
          focus: _vm.setFocusedIndex
        }
      }, "SearchResult", result, false))], 1);
    }), _vm._v(" "), _c("li", [!_vm.reached[type] ? _c("SearchResult", {
      staticClass: "unified-search__result-more",
      attrs: {
        title: _vm.loading[type] ? _vm.t("core", "Loading more results …") : _vm.t("core", "Load more results"),
        "icon-class": _vm.loading[type] ? "icon-loading-small" : ""
      },
      on: {
        click: function click($event) {
          $event.preventDefault();
          $event.stopPropagation();
          return _vm.loadMore(type);
        },
        focus: _vm.setFocusedIndex
      }
    }) : _vm._e()], 1)], 2);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".unified-search__result[data-v-69f8eb86] {\n  display: flex;\n  align-items: center;\n  height: 44px;\n  padding: 10px;\n  border: 2px solid transparent;\n  border-radius: var(--border-radius-large) !important;\n}\n.unified-search__result--focused[data-v-69f8eb86] {\n  background-color: var(--color-background-hover);\n}\n.unified-search__result[data-v-69f8eb86]:active, .unified-search__result[data-v-69f8eb86]:hover, .unified-search__result[data-v-69f8eb86]:focus {\n  background-color: var(--color-background-hover);\n  border: 2px solid var(--color-border-maxcontrast);\n}\n.unified-search__result *[data-v-69f8eb86] {\n  cursor: pointer;\n}\n.unified-search__result-icon[data-v-69f8eb86] {\n  overflow: hidden;\n  width: 44px;\n  height: 44px;\n  border-radius: var(--border-radius);\n  background-repeat: no-repeat;\n  background-position: center center;\n  background-size: 32px;\n}\n.unified-search__result-icon--rounded[data-v-69f8eb86] {\n  border-radius: 22px;\n}\n.unified-search__result-icon--no-preview[data-v-69f8eb86] {\n  background-size: 32px;\n}\n.unified-search__result-icon--with-thumbnail[data-v-69f8eb86] {\n  background-size: cover;\n}\n.unified-search__result-icon--with-thumbnail[data-v-69f8eb86]:not(.unified-search__result-icon--rounded) {\n  max-width: 42px;\n  max-height: 42px;\n  border: 1px solid var(--color-border);\n}\n.unified-search__result-icon img[data-v-69f8eb86] {\n  width: 100%;\n  height: 100%;\n  object-fit: cover;\n  object-position: center;\n}\n.unified-search__result-icon[data-v-69f8eb86], .unified-search__result-actions[data-v-69f8eb86] {\n  flex: 0 0 44px;\n}\n.unified-search__result-content[data-v-69f8eb86] {\n  display: flex;\n  align-items: center;\n  flex: 1 1 100%;\n  flex-wrap: wrap;\n  min-width: 0;\n  padding-left: 10px;\n}\n.unified-search__result-line-one[data-v-69f8eb86], .unified-search__result-line-two[data-v-69f8eb86] {\n  overflow: hidden;\n  flex: 1 1 100%;\n  margin: 1px 0;\n  white-space: nowrap;\n  text-overflow: ellipsis;\n  color: inherit;\n  font-size: inherit;\n}\n.unified-search__result-line-two[data-v-69f8eb86] {\n  opacity: 0.7;\n  font-size: var(--default-font-size);\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".unified-search__result-placeholder-gradient[data-v-ff2497f4] {\n  position: fixed;\n  height: 0;\n  width: 0;\n  z-index: -1;\n}\n.unified-search__result-placeholder[data-v-ff2497f4] {\n  width: calc(100% - 2 * 10px);\n  height: 44px;\n  margin: 10px;\n}\n.unified-search__result-placeholder-icon[data-v-ff2497f4] {\n  width: 44px;\n  height: 44px;\n  rx: var(--border-radius);\n  ry: var(--border-radius);\n}\n.unified-search__result-placeholder-line-one[data-v-ff2497f4], .unified-search__result-placeholder-line-two[data-v-ff2497f4] {\n  width: calc(100% - 54px);\n  height: 1em;\n  x: 54px;\n}\n.unified-search__result-placeholder-line-one[data-v-ff2497f4] {\n  y: 5px;\n}\n.unified-search__result-placeholder-line-two[data-v-ff2497f4] {\n  y: 25px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".unified-search__input-wrapper[data-v-d79c2f68] {\n  position: sticky;\n  z-index: 2;\n  top: 0;\n  display: inline-flex;\n  flex-direction: column;\n  align-items: center;\n  width: 100%;\n  background-color: var(--color-main-background);\n}\n.unified-search__input-wrapper label[for=unified-search__input][data-v-d79c2f68] {\n  align-self: flex-start;\n  font-weight: bold;\n  font-size: 19px;\n  margin-left: 13px;\n}\n.unified-search__form-input[data-v-d79c2f68] {\n  margin: 0 !important;\n}\n.unified-search__input-row[data-v-d79c2f68] {\n  display: flex;\n  width: 100%;\n  align-items: center;\n}\n.unified-search__filters[data-v-d79c2f68] {\n  margin: 10px 0 10px 5px;\n}\n.unified-search__filters ul[data-v-d79c2f68] {\n  display: inline-flex;\n  justify-content: space-between;\n}\n.unified-search__form[data-v-d79c2f68] {\n  position: relative;\n  width: 100%;\n  margin: 10px 0;\n}\n.unified-search__form[data-v-d79c2f68]::after {\n  right: 6px;\n  left: auto;\n}\n.unified-search__form-input[data-v-d79c2f68], .unified-search__form-reset[data-v-d79c2f68] {\n  margin: 3px;\n}\n.unified-search__form-input[data-v-d79c2f68] {\n  width: 100%;\n  height: 34px;\n  padding: 6px;\n}\n.unified-search__form-input[data-v-d79c2f68], .unified-search__form-input[placeholder][data-v-d79c2f68], .unified-search__form-input[data-v-d79c2f68]::placeholder {\n  overflow: hidden;\n  white-space: nowrap;\n  text-overflow: ellipsis;\n}\n.unified-search__form-input[data-v-d79c2f68]::-webkit-search-decoration, .unified-search__form-input[data-v-d79c2f68]::-webkit-search-cancel-button, .unified-search__form-input[data-v-d79c2f68]::-webkit-search-results-button, .unified-search__form-input[data-v-d79c2f68]::-webkit-search-results-decoration {\n  -webkit-appearance: none;\n}\n.icon-loading-small .unified-search__form-input[data-v-d79c2f68], .unified-search__form-input--with-reset[data-v-d79c2f68] {\n  padding-right: 34px;\n}\n.unified-search__form-reset[data-v-d79c2f68], .unified-search__form-submit[data-v-d79c2f68] {\n  position: absolute;\n  top: 0;\n  right: 4px;\n  width: 28px;\n  height: 28px;\n  min-height: 30px;\n  padding: 0;\n  opacity: 0.5;\n  border: none;\n  background-color: transparent;\n  margin-right: 0;\n}\n.unified-search__form-reset[data-v-d79c2f68]:hover, .unified-search__form-reset[data-v-d79c2f68]:focus, .unified-search__form-reset[data-v-d79c2f68]:active, .unified-search__form-submit[data-v-d79c2f68]:hover, .unified-search__form-submit[data-v-d79c2f68]:focus, .unified-search__form-submit[data-v-d79c2f68]:active {\n  opacity: 1;\n}\n.unified-search__form-submit[data-v-d79c2f68] {\n  right: 28px;\n}\n.unified-search__results[data-v-d79c2f68] {\n  display: flex;\n  flex-direction: column;\n  gap: 4px;\n}\n.unified-search__results-header[data-v-d79c2f68] {\n  display: block;\n  margin: 10px;\n  margin-bottom: 6px;\n  margin-left: 13px;\n  color: var(--color-primary-element);\n  font-size: 19px;\n  font-weight: bold;\n}\n.unified-search .unified-search__result-more[data-v-d79c2f68] {\n  color: var(--color-text-maxcontrast);\n}\n.unified-search .empty-content[data-v-d79c2f68] {\n  margin: 10vh 0;\n}\n.unified-search .empty-content[data-v-d79c2f68] .empty-content__title {\n  font-weight: normal;\n  font-size: var(--default-font-size);\n  padding: 0 15px;\n  text-align: center;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResult.vue":
/*!************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResult.vue ***!
  \************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true& */ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true&");
/* harmony import */ var _SearchResult_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SearchResult.vue?vue&type=script&lang=js& */ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js&");
/* harmony import */ var _SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& */ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SearchResult_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "69f8eb86",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/UnifiedSearch/SearchResult.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue":
/*!************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue ***!
  \************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true& */ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true&");
/* harmony import */ var _SearchResultPlaceholders_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SearchResultPlaceholders.vue?vue&type=script&lang=js& */ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js&");
/* harmony import */ var _SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& */ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SearchResultPlaceholders_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "ff2497f4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/components/UnifiedSearch/SearchResultPlaceholders.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./core/src/views/UnifiedSearch.vue":
/*!******************************************!*\
  !*** ./core/src/views/UnifiedSearch.vue ***!
  \******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true& */ "./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true&");
/* harmony import */ var _UnifiedSearch_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UnifiedSearch.vue?vue&type=script&lang=js& */ "./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js&");
/* harmony import */ var _UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& */ "./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UnifiedSearch_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "d79c2f68",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "core/src/views/UnifiedSearch.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Magnify.vue":
/*!************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Magnify.vue ***!
  \************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Magnify_vue_vue_type_template_id_1d382cb6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Magnify.vue?vue&type=template&id=1d382cb6& */ "./node_modules/vue-material-design-icons/Magnify.vue?vue&type=template&id=1d382cb6&");
/* harmony import */ var _Magnify_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Magnify.vue?vue&type=script&lang=js& */ "./node_modules/vue-material-design-icons/Magnify.vue?vue&type=script&lang=js&");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Magnify_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Magnify_vue_vue_type_template_id_1d382cb6___WEBPACK_IMPORTED_MODULE_0__.render,
  _Magnify_vue_vue_type_template_id_1d382cb6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Magnify.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Magnify.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Magnify.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

/* harmony default export */ __webpack_exports__["default"] = ({
  name: "MagnifyIcon",
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

/***/ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js&":
/*!*************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResult.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResultPlaceholders.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js&":
/*!*******************************************************************!*\
  !*** ./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js& ***!
  \*******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UnifiedSearch.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true&":
/*!*******************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true& ***!
  \*******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_template_id_69f8eb86_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=template&id=69f8eb86&scoped=true&");


/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true&":
/*!*******************************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true& ***!
  \*******************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_template_id_ff2497f4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=template&id=ff2497f4&scoped=true&");


/***/ }),

/***/ "./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true&":
/*!*************************************************************************************!*\
  !*** ./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_template_id_d79c2f68_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=template&id=d79c2f68&scoped=true&");


/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&":
/*!**********************************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResult_vue_vue_type_style_index_0_id_69f8eb86_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResult.vue?vue&type=style&index=0&id=69f8eb86&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************!*\
  !*** ./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SearchResultPlaceholders_vue_vue_type_style_index_0_id_ff2497f4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/UnifiedSearch/SearchResultPlaceholders.vue?vue&type=style&index=0&id=ff2497f4&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&":
/*!****************************************************************************************************!*\
  !*** ./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& ***!
  \****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UnifiedSearch_vue_vue_type_style_index_0_id_d79c2f68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/UnifiedSearch.vue?vue&type=style&index=0&id=d79c2f68&lang=scss&scoped=true&");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Magnify.vue?vue&type=script&lang=js&":
/*!*************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Magnify.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Magnify_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Magnify.vue?vue&type=script&lang=js& */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Magnify.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_vue_loader_lib_index_js_vue_loader_options_Magnify_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Magnify.vue?vue&type=template&id=1d382cb6&":
/*!*******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Magnify.vue?vue&type=template&id=1d382cb6& ***!
  \*******************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Magnify_vue_vue_type_template_id_1d382cb6___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Magnify_vue_vue_type_template_id_1d382cb6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Magnify_vue_vue_type_template_id_1d382cb6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Magnify.vue?vue&type=template&id=1d382cb6& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Magnify.vue?vue&type=template&id=1d382cb6&");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Magnify.vue?vue&type=template&id=1d382cb6&":
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Magnify.vue?vue&type=template&id=1d382cb6& ***!
  \***********************************************************************************************************************************************************************************************************************************/
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
        staticClass: "material-design-icon magnify-icon",
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
                d: "M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z",
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



/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	!function() {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = function(result, chunkIds, fn, priority) {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every(function(key) { return __webpack_require__.O[key](chunkIds[j]); })) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	!function() {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	!function() {
/******/ 		__webpack_require__.nmd = function(module) {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	!function() {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"core-unified-search": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = function(chunkId) { return installedChunks[chunkId] === 0; };
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = function(parentChunkLoadingFunction, data) {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some(function(id) { return installedChunks[id] !== 0; })) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	!function() {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./core/src/unified-search.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=core-unified-search.js.map?v=c48fd34c9c89f01b99de