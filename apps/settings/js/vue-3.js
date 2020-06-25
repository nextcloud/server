(window["webpackJsonpSettings"] = window["webpackJsonpSettings"] || []).push([[3],{

/***/ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationSpacer.js":
/*!****************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/AppNavigationSpacer.js ***!
  \****************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(e,t){ true?module.exports=t():undefined}(window,(function(){return function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}return n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/dist/",n(n.s=179)}({0:function(e,t,n){"use strict";function r(e,t,n,r,o,i,a,s){var c,u="function"==typeof e?e.options:e;if(t&&(u.render=t,u.staticRenderFns=n,u._compiled=!0),r&&(u.functional=!0),i&&(u._scopeId="data-v-"+i),a?(c=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),o&&o.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(a)},u._ssrRegister=c):o&&(c=s?function(){o.call(this,this.$root.$options.shadowRoot)}:o),c)if(u.functional){u._injectStyles=c;var f=u.render;u.render=function(e,t){return c.call(t),f(e,t)}}else{var p=u.beforeCreate;u.beforeCreate=p?[].concat(p,c):[c]}return{exports:e,options:u}}n.d(t,"a",(function(){return r}))},1:function(e,t,n){"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var n=function(e,t){var n=e[1]||"",r=e[3];if(!r)return n;if(t&&"function"==typeof btoa){var o=(a=r,s=btoa(unescape(encodeURIComponent(JSON.stringify(a)))),c="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(s),"/*# ".concat(c," */")),i=r.sources.map((function(e){return"/*# sourceURL=".concat(r.sourceRoot||"").concat(e," */")}));return[n].concat(i).concat([o]).join("\n")}var a,s,c;return[n].join("\n")}(t,e);return t[2]?"@media ".concat(t[2]," {").concat(n,"}"):n})).join("")},t.i=function(e,n,r){"string"==typeof e&&(e=[[null,e,""]]);var o={};if(r)for(var i=0;i<this.length;i++){var a=this[i][0];null!=a&&(o[a]=!0)}for(var s=0;s<e.length;s++){var c=[].concat(e[s]);r&&o[c[0]]||(n&&(c[2]?c[2]="".concat(n," and ").concat(c[2]):c[2]=n),t.push(c))}},t}},104:function(e,t,n){var r=n(227);"string"==typeof r&&(r=[[e.i,r,""]]),r.locals&&(e.exports=r.locals);(0,n(2).default)("1e3ece15",r,!0,{})},179:function(e,t,n){"use strict";n.r(t);var r={name:"AppNavigationSpacer"},o=(n(226),n(0)),i=Object(o.a)(r,(function(){var e=this.$createElement;return(this._self._c||e)("li",{staticClass:"app-navigation-spacer"})}),[],!1,null,"42195cc8",null).exports;t.default=i},2:function(e,t,n){"use strict";function r(e,t){for(var n=[],r={},o=0;o<t.length;o++){var i=t[o],a=i[0],s={id:e+":"+o,css:i[1],media:i[2],sourceMap:i[3]};r[a]?r[a].parts.push(s):n.push(r[a]={id:a,parts:[s]})}return n}n.r(t),n.d(t,"default",(function(){return l}));var o="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!o)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var i={},a=o&&(document.head||document.getElementsByTagName("head")[0]),s=null,c=0,u=!1,f=function(){},p=null,d="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function l(e,t,n,o){u=n,p=o||{};var a=r(e,t);return v(a),function(t){for(var n=[],o=0;o<a.length;o++){var s=a[o];(c=i[s.id]).refs--,n.push(c)}t?v(a=r(e,t)):a=[];for(o=0;o<n.length;o++){var c;if(0===(c=n[o]).refs){for(var u=0;u<c.parts.length;u++)c.parts[u]();delete i[c.id]}}}}function v(e){for(var t=0;t<e.length;t++){var n=e[t],r=i[n.id];if(r){r.refs++;for(var o=0;o<r.parts.length;o++)r.parts[o](n.parts[o]);for(;o<n.parts.length;o++)r.parts.push(g(n.parts[o]));r.parts.length>n.parts.length&&(r.parts.length=n.parts.length)}else{var a=[];for(o=0;o<n.parts.length;o++)a.push(g(n.parts[o]));i[n.id]={id:n.id,refs:1,parts:a}}}}function h(){var e=document.createElement("style");return e.type="text/css",a.appendChild(e),e}function g(e){var t,n,r=document.querySelector('style[data-vue-ssr-id~="'+e.id+'"]');if(r){if(u)return f;r.parentNode.removeChild(r)}if(d){var o=c++;r=s||(s=h()),t=b.bind(null,r,o,!1),n=b.bind(null,r,o,!0)}else r=h(),t=x.bind(null,r),n=function(){r.parentNode.removeChild(r)};return t(e),function(r){if(r){if(r.css===e.css&&r.media===e.media&&r.sourceMap===e.sourceMap)return;t(e=r)}else n()}}var m,y=(m=[],function(e,t){return m[e]=t,m.filter(Boolean).join("\n")});function b(e,t,n,r){var o=n?"":r.css;if(e.styleSheet)e.styleSheet.cssText=y(t,o);else{var i=document.createTextNode(o),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(i,a[t]):e.appendChild(i)}}function x(e,t){var n=t.css,r=t.media,o=t.sourceMap;if(r&&e.setAttribute("media",r),p.ssrId&&e.setAttribute("data-vue-ssr-id",t.id),o&&(n+="\n/*# sourceURL="+o.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}},226:function(e,t,n){"use strict";var r=n(104);n.n(r).a},227:function(e,t,n){(t=n(1)(!1)).push([e.i,"\n.app-navigation-spacer[data-v-42195cc8] {\n\tflex-shrink: 0;\n\torder: 1;\n\theight: 22px;\n}\n\n",""]),e.exports=t}})}));
//# sourceMappingURL=AppNavigationSpacer.js.map

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/AppSidebar.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/AppSidebar.js ***!
  \*******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(e,t){ true?module.exports=t():undefined}(window,(function(){return function(e){var t={};function s(A){if(t[A])return t[A].exports;var n=t[A]={i:A,l:!1,exports:{}};return e[A].call(n.exports,n,n.exports,s),n.l=!0,n.exports}return s.m=e,s.c=t,s.d=function(e,t,A){s.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:A})},s.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},s.t=function(e,t){if(1&t&&(e=s(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var A=Object.create(null);if(s.r(A),Object.defineProperty(A,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var n in e)s.d(A,n,function(t){return e[t]}.bind(null,n));return A},s.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return s.d(t,"a",t),t},s.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},s.p="/dist/",s(s.s=178)}({0:function(e,t,s){"use strict";function A(e,t,s,A,n,a,o,r){var i,c="function"==typeof e?e.options:e;if(t&&(c.render=t,c.staticRenderFns=s,c._compiled=!0),A&&(c.functional=!0),a&&(c._scopeId="data-v-"+a),o?(i=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),n&&n.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(o)},c._ssrRegister=i):n&&(i=r?function(){n.call(this,this.$root.$options.shadowRoot)}:n),i)if(c.functional){c._injectStyles=i;var l=c.render;c.render=function(e,t){return i.call(t),l(e,t)}}else{var m=c.beforeCreate;c.beforeCreate=m?[].concat(m,i):[i]}return{exports:e,options:c}}s.d(t,"a",(function(){return A}))},1:function(e,t,s){"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var s=function(e,t){var s=e[1]||"",A=e[3];if(!A)return s;if(t&&"function"==typeof btoa){var n=(o=A,r=btoa(unescape(encodeURIComponent(JSON.stringify(o)))),i="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(r),"/*# ".concat(i," */")),a=A.sources.map((function(e){return"/*# sourceURL=".concat(A.sourceRoot||"").concat(e," */")}));return[s].concat(a).concat([n]).join("\n")}var o,r,i;return[s].join("\n")}(t,e);return t[2]?"@media ".concat(t[2]," {").concat(s,"}"):s})).join("")},t.i=function(e,s,A){"string"==typeof e&&(e=[[null,e,""]]);var n={};if(A)for(var a=0;a<this.length;a++){var o=this[a][0];null!=o&&(n[o]=!0)}for(var r=0;r<e.length;r++){var i=[].concat(e[r]);A&&n[i[0]]||(s&&(i[2]?i[2]="".concat(s," and ").concat(i[2]):i[2]=s),t.push(i))}},t}},10:function(e,t,s){"use strict";s.r(t),t.default="data:font/ttf;base64,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"},105:function(e,t,s){"use strict";s.r(t),s.d(t,"directive",(function(){return A}));
/**
 * @copyright Copyright (c) 2019 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
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
var A={inserted:function(e){e.focus()}};t.default=A},106:function(e,t,s){var A=s(230);"string"==typeof A&&(A=[[e.i,A,""]]),A.locals&&(e.exports=A.locals);(0,s(2).default)("45aebdd4",A,!0,{})},107:function(e,t,s){var A=s(232);"string"==typeof A&&(A=[[e.i,A,""]]),A.locals&&(e.exports=A.locals);(0,s(2).default)("4dd3631b",A,!0,{})},11:function(e,t,s){"use strict";s.r(t),t.default="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCIgPjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48bWV0YWRhdGE+PC9tZXRhZGF0YT48ZGVmcz48Zm9udCBpZD0iaWNvbmZvbnQtdnVlLWUxZjU1Y2UiIGhvcml6LWFkdi14PSI1MDAwIj48Zm9udC1mYWNlIGZvbnQtZmFtaWx5PSJpY29uZm9udC12dWUtZTFmNTVjZSIgZm9udC13ZWlnaHQ9IjQwMCIgZm9udC1zdHJldGNoPSJub3JtYWwiIHVuaXRzLXBlci1lbT0iNTAwMCIgcGFub3NlLTE9IjIgMCA1IDMgMCAwIDAgMCAwIDAiIGFzY2VudD0iNTAwMCIgZGVzY2VudD0iMCIgeC1oZWlnaHQ9IjAiIGJib3g9Ii0xIDAgNTAxMSA1MDAwIiB1bmRlcmxpbmUtdGhpY2tuZXNzPSIwIiB1bmRlcmxpbmUtcG9zaXRpb249IjUwIiB1bmljb2RlLXJhbmdlPSJVK2VhMDEtZWEwZSIgLz48bWlzc2luZy1nbHlwaCBob3Jpei1hZHYteD0iMCIgIC8+PGdseXBoIGdseXBoLW5hbWU9ImFycm93LWxlZnQtZG91YmxlIiB1bmljb2RlPSImI3hlYTAxOyIgZD0iTTM3NTAgMzkwNiBsLTE0MDYgLTE0MDYgbDE0MDYgLTE0MDYgbDAgMzEyIGwtMTA5NCAxMDk0IGwxMDk0IDEwOTQgbDAgMzEyIFpNMjM0NCAzOTA2IGwtMTQwNiAtMTQwNiBsMTQwNiAtMTQwNiBsMCAzMTIgbC0xMDk0IDEwOTQgbDEwOTQgMTA5NCBsMCAzMTIgWiIgLz48Z2x5cGggZ2x5cGgtbmFtZT0iYXJyb3ctbGVmdCIgdW5pY29kZT0iJiN4ZWEwMjsiIGQ9Ik0xNTYzIDI1MDAgbDE4NzUgLTE4NzUgbDAgLTMxMiBsLTIxODggMjE4NyBsMjE4OCAyMTg4IGwwIC0zMTMgbC0xODc1IC0xODc1IFoiIC8+PGdseXBoIGdseXBoLW5hbWU9ImFycm93LXJpZ2h0LWRvdWJsZSIgdW5pY29kZT0iJiN4ZWEwMzsiIGQ9Ik0xMjUwIDEwOTQgbDE0MDYgMTQwNiBsLTE0MDYgMTQwNiBsMCAtMzEyIGwxMDk0IC0xMDk0IGwtMTA5NCAtMTA5NCBsMCAtMzEyIFpNMjY1NiAxMDk0IGwxNDA3IDE0MDYgbC0xNDA3IDE0MDYgbDAgLTMxMiBsMTA5NCAtMTA5NCBsLTEwOTQgLTEwOTQgbDAgLTMxMiBaIiAvPjxnbHlwaCBnbHlwaC1uYW1lPSJhcnJvdy1yaWdodCIgdW5pY29kZT0iJiN4ZWEwNDsiIGQ9Ik0zNDM4IDI1MDAgbC0xODc1IDE4NzUgbDAgMzEzIGwyMTg3IC0yMTg4IGwtMjE4NyAtMjE4NyBsMCAzMTIgbDE4NzUgMTg3NSBaIiAvPjxnbHlwaCBnbHlwaC1uYW1lPSJicmVhZGNydW1iIiB1bmljb2RlPSImI3hlYTA1OyIgZD0iTTE0OCA1MDAwIGwtMTQ4IC04NSBsMTM5NCAtMjQxNSBsLTEzOTQgLTI0MTUgbDE0OCAtODUgbDE0NDMgMjUwMCBsLTE0NDMgMjUwMCBaIiAvPjxnbHlwaCBnbHlwaC1uYW1lPSJjaGVja21hcmsiIHVuaWNvZGU9IiYjeGVhMDY7IiBkPSJNNDA0NSAzOTcxIGwtMjA2MSAtMjA2MSBsLTEwMjkgMTAyOSBsLTQ0MiAtNDQxIGwxNDcxIC0xNDcxIGwyNTAzIDI1MDIgbC00NDIgNDQyIFoiIC8+PGdseXBoIGdseXBoLW5hbWU9ImNsb3NlIiB1bmljb2RlPSImI3hlYTA3OyIgZD0iTTQzNzUgMTE1NiBsLTUzMSAtNTMxIGwtMTM0NCAxMzQ0IGwtMTM0NCAtMTM0NCBsLTUzMSA1MzEgbDEzNDQgMTM0NCBsLTEzNDQgMTM0NCBsNTMxIDUzMSBsMTM0NCAtMTM0NCBsMTM0NCAxMzQ0IGw1MzEgLTUzMSBsLTEzNDQgLTEzNDQgbDEzNDQgLTEzNDQgWiIgLz48Z2x5cGggZ2x5cGgtbmFtZT0iY29uZmlybSIgdW5pY29kZT0iJiN4ZWEwODsiIGQ9Ik0yNjU2IDQ4NDQgcS0xMDEgMCAtMTgwIC01NyBxLTc0IC01MiAtMTA5IC0xMzggcS0zNSAtODYgLTE5IC0xNzUgcTE4IC05NiA5MCAtMTY3IGwxNDk1IC0xNDk0IGwtMzYxNiAwIHEtNzcgMSAtMTM5IC0yNiBxLTU4IC0yNCAtOTkgLTcwIHEtMzkgLTQ0IC01OSAtMTAxIHEtMjAgLTU2IC0yMCAtMTE2IHEwIC02MCAyMCAtMTE2IHEyMCAtNTcgNTkgLTEwMSBxNDEgLTQ2IDk5IC03MCBxNjIgLTI3IDEzOSAtMjUgbDM2MTYgMCBsLTE0OTUgLTE0OTUgcS01NSAtNTMgLTgxIC0xMTYgcS0yNCAtNTkgLTIxIC0xMjEgcTMgLTU4IDMwIC0xMTMgcTI1IC01NCA2OCAtOTcgcTQzIC00MyA5NiAtNjggcTU1IC0yNiAxMTQgLTI5IHE2MiAtMyAxMjAgMjEgcTYzIDI1IDExNiA4MSBsMjAyOSAyMDI4IHE1OSA2MCA4MCAxNDEgcTIxIDgwIDEgMTU5IHEtMjEgODIgLTgxIDE0MiBsLTIwMjkgMjAyOCBxLTQ0IDQ1IC0xMDIgNzAgcS01OCAyNSAtMTIyIDI1IFoiIC8+PGdseXBoIGdseXBoLW5hbWU9ImluZm8iIHVuaWNvZGU9IiYjeGVhMDk7IiBkPSJNMjQyMiA0Njg4IHEtMTExIDAgLTIxMyAtNDMgcS05OCAtNDIgLTE3NCAtMTE3LjUgcS03NiAtNzUuNSAtMTE3IC0xNzQuNSBxLTQzIC0xMDEgLTQzIC0yMTIuNSBxMCAtMTExLjUgNDMgLTIxMi41IHE0MSAtOTggMTE3IC0xNzQgcTc2IC03NiAxNzQgLTExNyBxMTAyIC00MyAyMTMgLTQzIHExMTEgMCAyMTMgNDMgcTk4IDQxIDE3My41IDExNyBxNzUuNSA3NiAxMTcuNSAxNzQgcTQzIDEwMSA0MyAyMTIuNSBxMCAxMTEuNSAtNDMgMjEyLjUgcS00MiA5OSAtMTE3LjUgMTc0LjUgcS03NS41IDc1LjUgLTE3My41IDExNy41IHEtMTAyIDQzIC0yMTMgNDMgWk0xNTYzIDMxMjUgcS04NiAwIC0xNTggLTQzIHEtNzEgLTQxIC0xMTIgLTExMiBxLTQzIC03MiAtNDMgLTE1Ny41IHEwIC04NS41IDQzIC0xNTcuNSBxNDEgLTcxIDExMiAtMTEyIHE3MiAtNDMgMTU4IC00MyBsNjI1IDAgbDAgLTE1NjIgbC02MjUgMCBxLTg2IDAgLTE1OCAtNDMgcS03MSAtNDEgLTExMiAtMTEyIHEtNDMgLTczIC00MyAtMTU4IHEwIC04NSA0MyAtMTU4IHE0MSAtNzEgMTEyIC0xMTIgcTcyIC00MyAxNTggLTQyIGwxODc1IDAgcTg1IDAgMTU3IDQyIHE3MSA0MSAxMTIgMTEyIHE0MyA3MyA0MyAxNTggcTAgODUgLTQzIDE1OCBxLTQxIDcxIC0xMTIgMTEyIHEtNzIgNDMgLTE1NyA0MyBsLTYyNSAwIGwwIDE4NzUgcTAgODUgLTQzIDE1NyBxLTQxIDcxIC0xMTIgMTEyIHEtNzMgNDMgLTE1OCA0MyBsLTkzNyAwIFoiIC8+PGdseXBoIGdseXBoLW5hbWU9Im1lbnUiIHVuaWNvZGU9IiYjeGVhMGE7IiBkPSJNNjI1IDQzNzUgbDAgLTYyNSBsMzc1MCAwIGwwIDYyNSBsLTM3NTAgMCBaTTYyNSAyODEzIGwwIC02MjUgbDM3NTAgMCBsMCA2MjUgbC0zNzUwIDAgWk02MjUgMTI1MCBsMCAtNjI1IGwzNzUwIDAgbDAgNjI1IGwtMzc1MCAwIFoiIC8+PGdseXBoIGdseXBoLW5hbWU9Im1vcmUiIHVuaWNvZGU9IiYjeGVhMGI7IiBkPSJNNzgxIDMwNDcgcTExMiAwIDIxMyAtNDMgcTk4IC00MiAxNzQgLTExNy41IHE3NiAtNzUuNSAxMTcgLTE3My41IHE0MyAtMTAyIDQzIC0yMTMgcTAgLTExMSAtNDMgLTIxMyBxLTQxIC05OCAtMTE3IC0xNzMuNSBxLTc2IC03NS41IC0xNzQgLTExNy41IHEtMTAxIC00MyAtMjEyLjUgLTQzIHEtMTExLjUgMCAtMjEzLjUgNDMgcS05OCA0MiAtMTczLjUgMTE3LjUgcS03NS41IDc1LjUgLTExNy41IDE3My41IHEtNDMgMTAyIC00MyAyMTMgcTAgMTExIDQzIDIxMyBxNDIgOTggMTE3LjUgMTczLjUgcTc1LjUgNzUuNSAxNzMuNSAxMTcuNSBxMTAyIDQzIDIxMyA0MyBaTTI1MDAgMzA0NyBxMTExIDAgMjEzIC00MyBxOTggLTQyIDE3My41IC0xMTcuNSBxNzUuNSAtNzUuNSAxMTcuNSAtMTczLjUgcTQzIC0xMDIgNDMgLTIxMyBxMCAtMTExIC00MyAtMjEzIHEtNDIgLTk4IC0xMTcuNSAtMTczLjUgcS03NS41IC03NS41IC0xNzMuNSAtMTE3LjUgcS0xMDIgLTQzIC0yMTMgLTQzIHEtMTExIDAgLTIxMyA0MyBxLTk4IDQyIC0xNzMuNSAxMTcuNSBxLTc1LjUgNzUuNSAtMTE3LjUgMTczLjUgcS00MyAxMDIgLTQzIDIxMyBxMCAxMTEgNDMgMjEzIHE0MiA5OCAxMTcuNSAxNzMuNSBxNzUuNSA3NS41IDE3My41IDExNy41IHExMDIgNDMgMjEzIDQzIFpNNDIxOSAzMDQ3IHExMTEgMCAyMTMgLTQzIHE5OCAtNDIgMTczLjUgLTExNy41IHE3NS41IC03NS41IDExNy41IC0xNzMuNSBxNDMgLTEwMiA0MyAtMjEzIHEwIC0xMTEgLTQzIC0yMTMgcS00MiAtOTggLTExNy41IC0xNzMuNSBxLTc1LjUgLTc1LjUgLTE3My41IC0xMTcuNSBxLTEwMiAtNDMgLTIxMy41IC00MyBxLTExMS41IDAgLTIxMi41IDQzIHEtOTggNDIgLTE3NCAxMTcuNSBxLTc2IDc1LjUgLTExNyAxNzMuNSBxLTQzIDEwMiAtNDMgMjEzIHEwIDExMSA0MyAyMTMgcTQxIDk4IDExNyAxNzMuNSBxNzYgNzUuNSAxNzQgMTE3LjUgcTEwMSA0MyAyMTMgNDMgWiIgLz48Z2x5cGggZ2x5cGgtbmFtZT0icGF1c2UiIHVuaWNvZGU9IiYjeGVhMGM7IiBkPSJNOTM4IDQwNjMgbDAgLTMxMjUgbDEyNTAgMCBsMCAzMTI1IGwtMTI1MCAwIFpNMjgxMyA0MDYzIGwwIC0zMTI1IGwxMjUwIDAgbDAgMzEyNSBsLTEyNTAgMCBaIiAvPjxnbHlwaCBnbHlwaC1uYW1lPSJwbGF5IiB1bmljb2RlPSImI3hlYTBkOyIgZD0iTTYyNSA0Mzc1IGwzNzUwIC0xODc1IGwtMzc1MCAtMTg3NSBsMCAzNzUwIFoiIC8+PGdseXBoIGdseXBoLW5hbWU9InRyaWFuZ2xlLXMiIHVuaWNvZGU9IiYjeGVhMGU7IiBkPSJNMTI1MCAzMTI1IGwxMjUwIC0xMjUwIGwxMjUwIDEyNDggbC0yNTAwIDIgWiIgLz48L2ZvbnQ+PC9kZWZzPjwvc3ZnPg=="},12:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.function.name */ "./node_modules/core-js/modules/es.function.name.js")},13:function(e,t,s){"use strict";s.r(t);var A=s(3);s(40);
/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
A.VTooltip.options.defaultTemplate='<div class="vue-tooltip" role="tooltip" data-v-'.concat("e1f55ce",'><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'),A.VTooltip.options.defaultHtml=!1,t.default=A.VTooltip},138:function(e,t){},14:function(e,t){e.exports=__webpack_require__(/*! v-click-outside */ "./node_modules/v-click-outside/dist/v-click-outside.umd.js")},15:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.array.index-of */ "./node_modules/core-js/modules/es.array.index-of.js")},17:function(e,t,s){"use strict";s(4),s(21),s(23),s(30);t.a=function(e){return Math.random().toString(36).replace(/[^a-z]+/g,"").substr(0,e||5)}},178:function(e,t,s){"use strict";s.r(t);s(46),s(228),s(15),s(12),s(28);var A=s(5),n=s.n(A),a=s(38),o=s(105),r=s(32),i=function(e){return e&&"string"==typeof e&&""!==e.trim()&&-1===e.indexOf(" ")},c={name:"AppSidebar",components:{Actions:a.default},directives:{focus:o.default},mixins:[r.a],props:{active:{type:String,default:""},title:{type:String,default:"",required:!0},titleEditable:{type:Boolean,default:!1},titlePlaceholder:{type:String,default:""},subtitle:{type:String,default:""},background:{type:String,default:""},starred:{type:Boolean,default:null},starLoading:{type:Boolean,default:!1},compact:{type:Boolean,default:!1},forceMenu:{type:Boolean,default:!1}},data:function(){return{tabs:[],activeTab:"",isStarred:this.starred,children:[]}},computed:{canStar:function(){return null!==this.isStarred},hasFigure:function(){return this.$slots.header||this.background},hasMultipleTabs:function(){return this.tabs.length>1},hasFigureClickListener:function(){return this.$listeners["figure-click"]},currentTabIndex:function(){var e=this;return this.tabs.findIndex((function(t){return t.id===e.activeTab}))}},watch:{active:function(e){e!==this.activeTab&&this.updateActive()},starred:function(){this.isStarred=this.starred},children:function(){this.updateTabs()}},mounted:function(){this.updateTabs(),this.children=this.$children},methods:{closeSidebar:function(e){this.$emit("close",e)},onFigureClick:function(e){this.$emit("figure-click",e)},setActive:function(e){var t=e.target.closest("a").dataset.id;this.activeTab=t,this.$emit("update:active",t)},focusPreviousTab:function(){this.currentTabIndex>0&&(this.activeTab=this.tabs[this.currentTabIndex-1].id,this.$emit("update:active",this.activeTab)),this.focusActiveTab()},focusNextTab:function(){this.currentTabIndex<this.tabs.length-1&&(this.activeTab=this.tabs[this.currentTabIndex+1].id,this.$emit("update:active",this.activeTab)),this.focusActiveTab()},focusFirstTab:function(){this.activeTab=this.tabs[0].id,this.$emit("update:active",this.activeTab),this.focusActiveTab()},focusLastTab:function(){this.activeTab=this.tabs[this.tabs.length-1].id,this.$emit("update:active",this.activeTab),this.focusActiveTab()},focusActiveTab:function(){this.$el.querySelector("#"+this.activeTab).focus()},focusActiveTabContent:function(){this.$el.querySelector("#tab-"+this.activeTab).focus()},updateActive:function(){var e=this;this.activeTab=this.active&&-1!==this.tabs.findIndex((function(t){return t.id===e.active}))?this.active:this.tabs.length>0?this.tabs[0].id:""},toggleStarred:function(){this.isStarred=!this.isStarred,this.$emit("update:starred",this.isStarred)},updateTabs:function(){var e=this.$children.filter((function(e){return e.name&&"string"==typeof e.name&&i(e.id)&&i(e.icon)}));0!==e.length&&e.length!==this.$children.length&&n.a.util.warn("Mixing tabs and non-tab-content is not possible."),this.tabs=e.sort((function(e,t){var s=e.order||0,A=t.order||0;return s===A?OC.Util.naturalSortCompare(e.name,t.name):s-A})),this.tabs.length>0&&this.updateActive()},onTitleInput:function(e){this.$emit("input-title",e),this.$emit("update:title",e.target.value)},onSubmitTitle:function(e){this.$emit("submit-title",e)},onDismissEditing:function(){this.$emit("dismiss-editing")}}},l=(s(229),s(231),s(0)),m=s(138),g=s.n(m),u=Object(l.a)(c,(function(){var e=this,t=e.$createElement,s=e._self._c||t;return s("transition",{attrs:{name:"slide-right"}},[s("aside",{attrs:{id:"app-sidebar"}},[s("header",{staticClass:"app-sidebar-header",class:{"app-sidebar-header--with-figure":e.hasFigure,"app-sidebar-header--compact":e.compact}},[s("a",{staticClass:"app-sidebar__close icon-close",attrs:{href:"#",title:e.t("close")},on:{click:function(t){return t.preventDefault(),e.closeSidebar(t)}}}),e._v(" "),e.hasFigure?s("div",{staticClass:"app-sidebar-header__figure",class:{"app-sidebar-header__figure--with-action":e.hasFigureClickListener},style:{backgroundImage:"url("+e.background+")"},on:{click:e.onFigureClick}},[e._t("header")],2):e._e(),e._v(" "),s("div",{staticClass:"app-sidebar-header__desc",class:{"app-sidebar-header__desc--with-star":e.canStar,"app-sidebar-header__desc--with-subtitle":e.subtitle&&!e.titleEditable,"app-sidebar-header__desc--editable":e.titleEditable&&!e.subtitle,"app-sidebar-header__desc--with-subtitle--editable":e.titleEditable&&e.subtitle}},[e.canStar?s("a",{staticClass:"app-sidebar-header__star",class:{"icon-starred":e.isStarred&&!e.starLoading,"icon-star":!e.isStarred&&!e.starLoading,"icon-loading-small":e.starLoading},on:{click:function(t){return t.preventDefault(),e.toggleStarred(t)}}}):e._e(),e._v(" "),e.titleEditable?e._e():s("h2",{staticClass:"app-sidebar-header__title"},[e._v("\n\t\t\t\t\t"+e._s(e.title)+"\n\t\t\t\t")]),e._v(" "),e.titleEditable?[s("form",{staticClass:"rename-form",on:{submit:function(t){return t.preventDefault(),e.onSubmitTitle(t)}}},[s("input",{directives:[{name:"focus",rawName:"v-focus"}],staticClass:"app-sidebar-header__title-input",attrs:{type:"text",placeholder:e.titlePlaceholder},domProps:{value:e.title},on:{keydown:function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"esc",27,t.key,["Esc","Escape"])?null:e.onDismissEditing(t)},input:e.onTitleInput}}),e._v(" "),s("button",{staticClass:"icon-confirm",attrs:{type:"submit"}})])]:e._e(),e._v(" "),""!==e.subtitle.trim()?s("p",{staticClass:"app-sidebar-header__subtitle"},[e._v("\n\t\t\t\t\t"+e._s(e.subtitle)+"\n\t\t\t\t")]):e._e(),e._v(" "),e.$slots["secondary-actions"]?s("Actions",{staticClass:"app-sidebar-header__menu",attrs:{"force-menu":e.forceMenu}},[e._t("secondary-actions")],2):e._e()],2),e._v(" "),e.$slots["primary-actions"]?s("div",{staticClass:"app-sidebar-header__action"},[e._t("primary-actions")],2):e._e()]),e._v(" "),e.hasMultipleTabs?s("nav",{staticClass:"app-sidebar-tabs__nav",on:{keydown:[function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"left",37,t.key,["Left","ArrowLeft"])?null:"button"in t&&0!==t.button?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusPreviousTab(t))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"right",39,t.key,["Right","ArrowRight"])?null:"button"in t&&2!==t.button?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusNextTab(t))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"tab",9,t.key,"Tab")?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusActiveTabContent(t))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"page-up",void 0,t.key,void 0)?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusFirstTab(t))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"page-down",void 0,t.key,void 0)?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusLastTab(t))}]}},[s("ul",e._l(e.tabs,(function(t){return s("li",{key:t.id,staticClass:"app-sidebar-tabs__tab"},[s("a",{class:{active:e.activeTab===t.id},attrs:{id:t.id,"aria-controls":"tab-"+t.id,"aria-selected":e.activeTab===t.id,"data-id":t.id,href:"#tab-"+t.id,tabindex:e.activeTab===t.id?null:-1,role:"tab"},on:{click:function(t){return t.preventDefault(),e.setActive(t)}}},[s("span",{staticClass:"app-sidebar-tabs__tab-icon",class:t.icon}),e._v("\n\t\t\t\t\t\t"+e._s(t.name)+"\n\t\t\t\t\t")])])})),0)]):e._e(),e._v(" "),s("div",{staticClass:"app-sidebar-tabs__content",class:{"app-sidebar-tabs__content--multiple":e.hasMultipleTabs}},[e._t("default",null,{activeTab:e.activeTab})],2)])])}),[],!1,null,"1059746a",null);"function"==typeof g.a&&g()(u);var d=u.exports;
/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */t.default=d},2:function(e,t,s){"use strict";function A(e,t){for(var s=[],A={},n=0;n<t.length;n++){var a=t[n],o=a[0],r={id:e+":"+n,css:a[1],media:a[2],sourceMap:a[3]};A[o]?A[o].parts.push(r):s.push(A[o]={id:o,parts:[r]})}return s}s.r(t),s.d(t,"default",(function(){return u}));var n="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!n)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var a={},o=n&&(document.head||document.getElementsByTagName("head")[0]),r=null,i=0,c=!1,l=function(){},m=null,g="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function u(e,t,s,n){c=s,m=n||{};var o=A(e,t);return d(o),function(t){for(var s=[],n=0;n<o.length;n++){var r=o[n];(i=a[r.id]).refs--,s.push(i)}t?d(o=A(e,t)):o=[];for(n=0;n<s.length;n++){var i;if(0===(i=s[n]).refs){for(var c=0;c<i.parts.length;c++)i.parts[c]();delete a[i.id]}}}}function d(e){for(var t=0;t<e.length;t++){var s=e[t],A=a[s.id];if(A){A.refs++;for(var n=0;n<A.parts.length;n++)A.parts[n](s.parts[n]);for(;n<s.parts.length;n++)A.parts.push(f(s.parts[n]));A.parts.length>s.parts.length&&(A.parts.length=s.parts.length)}else{var o=[];for(n=0;n<s.parts.length;n++)o.push(f(s.parts[n]));a[s.id]={id:s.id,refs:1,parts:o}}}}function p(){var e=document.createElement("style");return e.type="text/css",o.appendChild(e),e}function f(e){var t,s,A=document.querySelector('style[data-vue-ssr-id~="'+e.id+'"]');if(A){if(c)return l;A.parentNode.removeChild(A)}if(g){var n=i++;A=r||(r=p()),t=M.bind(null,A,n,!1),s=M.bind(null,A,n,!0)}else A=p(),t=b.bind(null,A),s=function(){A.parentNode.removeChild(A)};return t(e),function(A){if(A){if(A.css===e.css&&A.media===e.media&&A.sourceMap===e.sourceMap)return;t(e=A)}else s()}}var h,v=(h=[],function(e,t){return h[e]=t,h.filter(Boolean).join("\n")});function M(e,t,s,A){var n=s?"":A.css;if(e.styleSheet)e.styleSheet.cssText=v(t,n);else{var a=document.createTextNode(n),o=e.childNodes;o[t]&&e.removeChild(o[t]),o.length?e.insertBefore(a,o[t]):e.appendChild(a)}}function b(e,t){var s=t.css,A=t.media,n=t.sourceMap;if(A&&e.setAttribute("media",A),m.ssrId&&e.setAttribute("data-vue-ssr-id",t.id),n&&(s+="\n/*# sourceURL="+n.sources[0]+" */",s+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(n))))+" */"),e.styleSheet)e.styleSheet.cssText=s;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(s))}}},21:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.regexp.exec */ "./node_modules/core-js/modules/es.regexp.exec.js")},22:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.array.map */ "./node_modules/core-js/modules/es.array.map.js")},228:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.array.find-index */ "./node_modules/core-js/modules/es.array.find-index.js")},229:function(e,t,s){"use strict";var A=s(106);s.n(A).a},23:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.regexp.to-string */ "./node_modules/core-js/modules/es.regexp.to-string.js")},230:function(e,t,s){(t=s(1)(!1)).push([e.i,"#app-sidebar[data-v-1059746a]{z-index:1500;height:calc(100vh - 50px);width:27vw;min-width:300px;max-width:500px;top:50px;right:0;display:flex;flex-shrink:0;flex-direction:column;position:-webkit-sticky;position:sticky;overflow-y:auto;overflow-x:hidden;background:var(--color-main-background);border-left:1px solid var(--color-border)}#app-sidebar .app-sidebar-header>.app-sidebar__close[data-v-1059746a]{position:absolute;width:44px;height:44px;top:6px;right:6px;z-index:100;opacity:.7;border-radius:22px}#app-sidebar .app-sidebar-header>.app-sidebar__close[data-v-1059746a]:hover,#app-sidebar .app-sidebar-header>.app-sidebar__close[data-v-1059746a]:active,#app-sidebar .app-sidebar-header>.app-sidebar__close[data-v-1059746a]:focus{opacity:1;background-color:rgba(127,127,127,0.25)}#app-sidebar .app-sidebar-header__figure[data-v-1059746a]{max-height:250px;height:250px;width:100%;background-size:contain;background-position:center;background-repeat:no-repeat}#app-sidebar .app-sidebar-header__figure--with-action[data-v-1059746a]{cursor:pointer}#app-sidebar .app-sidebar-header__desc[data-v-1059746a]{position:relative;padding:18px 106px 18px 9px;display:flex;flex-direction:column;justify-content:center;box-sizing:content-box}#app-sidebar .app-sidebar-header__desc .app-sidebar-header__title[data-v-1059746a],#app-sidebar .app-sidebar-header__desc .app-sidebar-header__subtitle[data-v-1059746a]{width:100%;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;margin:0}#app-sidebar .app-sidebar-header__desc .app-sidebar-header__title[data-v-1059746a]{padding:0;font-size:20px;line-height:30px}#app-sidebar .app-sidebar-header__desc input.app-sidebar-header__title-input[data-v-1059746a]{font-size:16px;padding:7px;width:100%;margin:0}#app-sidebar .app-sidebar-header__desc .app-sidebar-header__subtitle[data-v-1059746a]{font-size:14px;padding:0;opacity:.7}#app-sidebar .app-sidebar-header__desc .app-sidebar-header__star[data-v-1059746a]{display:block;width:44px;height:44px;padding:14px;position:absolute;left:0}#app-sidebar .app-sidebar-header__desc .app-sidebar-header__menu[data-v-1059746a]{position:absolute;right:22px;background-color:rgba(127,127,127,0.25);border-radius:22px}#app-sidebar .app-sidebar-header__desc--with-star[data-v-1059746a]{padding-left:44px}#app-sidebar .app-sidebar-header__desc--with-subtitle[data-v-1059746a]{justify-content:space-between;height:52px}#app-sidebar .app-sidebar-header__desc--editable[data-v-1059746a]{height:39px}#app-sidebar .app-sidebar-header__desc--with-subtitle--editable[data-v-1059746a]{height:78px}#app-sidebar .app-sidebar-header__desc--with-subtitle--editable .app-sidebar-header__subtitle[data-v-1059746a]{margin-left:7px}#app-sidebar .app-sidebar-header__desc--with-subtitle--editable .app-sidebar-header__title-input[data-v-1059746a]{margin-top:-16px}#app-sidebar .app-sidebar-header--with-figure .app-sidebar-header__desc[data-v-1059746a]{padding-right:88px}#app-sidebar .app-sidebar-header:not(.app-sidebar-header--with-figure) .app-sidebar-header__menu[data-v-1059746a]{top:6px;right:56px}#app-sidebar .app-sidebar-header__action[data-v-1059746a]{display:flex;margin:0 10px;max-height:50px;align-items:center}#app-sidebar .app-sidebar-header--compact .app-sidebar-header__figure[data-v-1059746a]{height:70px;width:70px;margin:9px;border-radius:3px;position:absolute;left:0;top:0;z-index:2}#app-sidebar .app-sidebar-header--compact .app-sidebar-header__desc[data-v-1059746a]{padding-left:44px;margin-left:46px;height:52px}#app-sidebar .app-sidebar-header--compact .app-sidebar-header__desc .app-sidebar-header__star[data-v-1059746a]{margin-top:-9px;z-index:3}#app-sidebar .app-sidebar-header--compact .app-sidebar-header__desc .app-sidebar-header__menu[data-v-1059746a]{right:50px;top:6px;margin:0;background-color:transparent}#app-sidebar .app-sidebar-header--compact .app-sidebar-header__desc--editable[data-v-1059746a]{padding-top:0}#app-sidebar .app-sidebar-header--compact .app-sidebar-header__desc--editable input.app-sidebar-header__title-input[data-v-1059746a]{margin-top:0}#app-sidebar .app-sidebar-tabs__nav[data-v-1059746a]{margin-top:10px}#app-sidebar .app-sidebar-tabs__nav ul[data-v-1059746a]{display:flex;justify-content:stretch}#app-sidebar .app-sidebar-tabs__tab[data-v-1059746a]{display:block;text-align:center;flex:1 1}#app-sidebar .app-sidebar-tabs__tab a[data-v-1059746a]{display:block;padding-top:25px;padding-bottom:5px;position:relative;border-bottom:1px solid var(--color-border);text-align:center;opacity:.7;color:var(--color-main-text);transition:color var(--animation-quick),opacity var(--animation-quick),border-color var(--animation-quick)}#app-sidebar .app-sidebar-tabs__tab a[data-v-1059746a]:hover,#app-sidebar .app-sidebar-tabs__tab a[data-v-1059746a]:focus,#app-sidebar .app-sidebar-tabs__tab a[data-v-1059746a]:active,#app-sidebar .app-sidebar-tabs__tab a.active[data-v-1059746a]{opacity:1}#app-sidebar .app-sidebar-tabs__tab a:hover .app-sidebar-tabs__tab-icon[data-v-1059746a],#app-sidebar .app-sidebar-tabs__tab a:focus .app-sidebar-tabs__tab-icon[data-v-1059746a],#app-sidebar .app-sidebar-tabs__tab a:active .app-sidebar-tabs__tab-icon[data-v-1059746a],#app-sidebar .app-sidebar-tabs__tab a.active .app-sidebar-tabs__tab-icon[data-v-1059746a]{opacity:1}#app-sidebar .app-sidebar-tabs__tab a[data-v-1059746a]:not(.active):hover,#app-sidebar .app-sidebar-tabs__tab a[data-v-1059746a]:not(.active):focus{box-shadow:inset 0 -1px 0 var(--color-background-darker);border-bottom-color:var(--color-background-darker)}#app-sidebar .app-sidebar-tabs__tab a.active[data-v-1059746a]{font-weight:bold;color:var(--color-text-light);border-bottom-color:var(--color-text-light);box-shadow:inset 0 -1px 0 var(--color-text-light)}#app-sidebar .app-sidebar-tabs__tab a[data-v-1059746a]:focus{border-bottom-color:var(--color-primary-element);box-shadow:inset 0 -1px 0 var(--color-primary-element)}#app-sidebar .app-sidebar-tabs__tab-icon[data-v-1059746a]{height:25px;width:100%;position:absolute;top:0;left:0;opacity:.7;background-position:center 8px;background-size:16px;transition:opacity var(--animation-quick)}#app-sidebar .app-sidebar-tabs__content[data-v-1059746a]{position:relative;flex:1 1 100%}#app-sidebar .app-sidebar-tabs__content--multiple[data-v-1059746a]>:not(section){display:none}.slide-right-leave-active[data-v-1059746a],.slide-right-enter-active[data-v-1059746a]{transition-duration:var(--animation-quick);transition-property:max-width, min-width}.slide-right-enter-to[data-v-1059746a],.slide-right-leave[data-v-1059746a]{min-width:300px;max-width:500px}.slide-right-enter[data-v-1059746a],.slide-right-leave-to[data-v-1059746a]{min-width:0 !important;max-width:0 !important}.fade-leave-active[data-v-1059746a],.fade-enter-active[data-v-1059746a]{transition-duration:var(--animation-quick);transition-property:opacity;position:absolute;top:0;left:0;width:100%;opacity:1}.fade-enter[data-v-1059746a],.fade-leave-to[data-v-1059746a]{opacity:0}.rename-form[data-v-1059746a]{display:flex}.rename-form .icon-confirm[data-v-1059746a]{margin:0}\n",""]),e.exports=t},231:function(e,t,s){"use strict";var A=s(107);s.n(A).a},232:function(e,t,s){(t=s(1)(!1)).push([e.i,".app-sidebar-header__action button,.app-sidebar-header__action .button,.app-sidebar-header__action input[type='button'],.app-sidebar-header__action input[type='submit'],.app-sidebar-header__action input[type='reset']{padding:6px 22px}\n",""]),e.exports=t},25:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.array.concat */ "./node_modules/core-js/modules/es.array.concat.js")},27:function(e,t){e.exports=__webpack_require__(/*! @nextcloud/l10n/dist/gettext */ "./node_modules/@nextcloud/l10n/dist/gettext.js")},28:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.string.trim */ "./node_modules/core-js/modules/es.string.trim.js")},29:function(e,t,s){var A=s(67);"string"==typeof A&&(A=[[e.i,A,""]]),A.locals&&(e.exports=A.locals);(0,s(2).default)("640a212a",A,!0,{})},3:function(e,t){e.exports=__webpack_require__(/*! v-tooltip */ "./node_modules/v-tooltip/dist/v-tooltip.esm.js")},30:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.string.replace */ "./node_modules/core-js/modules/es.string.replace.js")},31:function(e,t,s){"use strict";s(25),s(15),s(65),s(12);var A=s(5),n=s.n(A);t.a=function(e,t,s){if(void 0!==e)for(var A=e.length-1;A>=0;A--){var a=e[A],o=!a.componentOptions&&a.tag&&-1===t.indexOf(a.tag),r=!!a.componentOptions&&"string"==typeof a.componentOptions.tag,i=r&&-1===t.indexOf(a.componentOptions.tag);(o||!r||i)&&((o||i)&&n.a.util.warn("".concat(o?a.tag:a.componentOptions.tag," is not allowed inside the ").concat(s.$options.name," component"),s),e.splice(A,1))}}},32:function(e,t,s){"use strict";var A=s(6);t.a={methods:{n:A.a,t:A.b}}},37:function(e,t){},38:function(e,t,s){"use strict";s.r(t);var A=s(43);
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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
 */t.default=A.a},4:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.object.to-string */ "./node_modules/core-js/modules/es.object.to-string.js")},40:function(e,t,s){var A=s(41);"string"==typeof A&&(A=[[e.i,A,""]]),A.locals&&(e.exports=A.locals);(0,s(2).default)("941c791e",A,!0,{})},41:function(e,t,s){(t=s(1)(!1)).push([e.i,".vue-tooltip[data-v-e1f55ce]{position:absolute;z-index:100000;right:auto;left:auto;display:block;margin:0;margin-top:-3px;padding:10px 0;text-align:left;text-align:start;white-space:normal;text-decoration:none;letter-spacing:normal;word-spacing:normal;text-transform:none;word-wrap:normal;word-break:normal;opacity:0;text-shadow:none;font-family:'Nunito', 'Open Sans', Frutiger, Calibri, 'Myriad Pro', Myriad, sans-serif;font-size:12px;font-weight:normal;font-style:normal;line-height:1.6;line-break:auto;filter:drop-shadow(0 1px 10px var(--color-box-shadow))}.vue-tooltip[data-v-e1f55ce][x-placement^='top'] .tooltip-arrow{bottom:0;margin-top:0;margin-bottom:0;border-width:10px 10px 0 10px;border-right-color:transparent;border-bottom-color:transparent;border-left-color:transparent}.vue-tooltip[data-v-e1f55ce][x-placement^='bottom'] .tooltip-arrow{top:0;margin-top:0;margin-bottom:0;border-width:0 10px 10px 10px;border-top-color:transparent;border-right-color:transparent;border-left-color:transparent}.vue-tooltip[data-v-e1f55ce][x-placement^='right'] .tooltip-arrow{right:100%;margin-right:0;margin-left:0;border-width:10px 10px 10px 0;border-top-color:transparent;border-bottom-color:transparent;border-left-color:transparent}.vue-tooltip[data-v-e1f55ce][x-placement^='left'] .tooltip-arrow{left:100%;margin-right:0;margin-left:0;border-width:10px 0 10px 10px;border-top-color:transparent;border-right-color:transparent;border-bottom-color:transparent}.vue-tooltip[data-v-e1f55ce][aria-hidden='true']{visibility:hidden;transition:opacity .15s, visibility .15s;opacity:0}.vue-tooltip[data-v-e1f55ce][aria-hidden='false']{visibility:visible;transition:opacity .15s;opacity:1}.vue-tooltip[data-v-e1f55ce] .tooltip-inner{max-width:350px;padding:5px 8px;text-align:center;color:var(--color-main-text);border-radius:var(--border-radius);background-color:var(--color-main-background)}.vue-tooltip[data-v-e1f55ce] .tooltip-arrow{position:absolute;z-index:1;width:0;height:0;margin:0;border-style:solid;border-color:var(--color-main-background)}\n",""]),e.exports=t},43:function(e,t,s){"use strict";s(25),s(46),s(15);var A=s(14),n=s(13),a=s(17),o=(s(64),function(e){var t=e.getBoundingClientRect(),s=document.documentElement.clientHeight,A=document.documentElement.clientWidth,n=Object.assign({});return n.top=t.top<0,n.left=t.left<0,n.bottom=t.bottom>s,n.right=t.right>A,n.any=n.top||n.left||n.bottom||n.right,n.all=n.top&&n.left&&n.bottom&&n.right,n.offsetY=n.top?t.top:n.bottom?t.bottom-s:0,n.offsetX=n.left?t.left:n.right?t.right-A:0,n}),r=s(31),i=s(6),c=["ActionButton","ActionCheckbox","ActionInput","ActionLink","ActionRadio","ActionRouter","ActionSeparator","ActionText","ActionTextEditable"],l={name:"Actions",directives:{ClickOutside:A.directive,tooltip:n.default},props:{open:{type:Boolean,default:!1},forceMenu:{type:Boolean,default:!1},menuAlign:{type:String,default:"center",validator:function(e){return["left","center","right"].indexOf(e)>-1}},defaultIcon:{type:String,default:"action-item__menutoggle--default-icon"},ariaLabel:{type:String,default:Object(i.b)("Actions")}},data:function(){return{actions:[],opened:this.open,focusIndex:0,randomId:"menu-"+Object(a.a)(),offsetX:0,offsetY:0,offsetYArrow:0,rotateArrow:!1,children:this.$children}},computed:{hasMultipleActions:function(){return this.actions.length>1},isValidSingleAction:function(){return 1===this.actions.length&&null!==this.firstActionElement},firstActionVNode:function(){return this.actions[0]},firstAction:function(){return this.children[0]?this.children[0]:{}},firstActionBinding:function(){if(this.firstActionVNode&&this.firstActionVNode.componentOptions){var e=this.firstActionVNode.componentOptions.tag;if("ActionLink"===e)return{is:"a",href:this.firstAction.href,target:this.firstAction.target,"aria-label":this.firstAction.ariaLabel};if("ActionRouter"===e)return{is:"router-link",to:this.firstAction.to,exact:this.firstAction.exact,"aria-label":this.firstAction.ariaLabel};if("ActionButton"===e)return{is:"button","aria-label":this.firstAction.ariaLabel}}return null},firstActionEvent:function(){return this.firstActionVNode&&this.firstActionVNode.componentOptions&&this.firstActionVNode.componentOptions.listeners&&this.firstActionVNode.componentOptions.listeners.click},firstActionEventBinding:function(){return this.firstActionEvent?"click":null},firstActionClass:function(){var e=this.firstActionVNode&&this.firstActionVNode.data.staticClass,t=this.firstActionVNode&&this.firstActionVNode.data.class;return"".concat(e," ").concat(t)}},watch:{open:function(e){var t=this;this.opened=e,this.opened&&this.$nextTick((function(){t.onOpen()}))}},beforeMount:function(){this.initActions(),Object(r.a)(this.$slots.default,c,this)},beforeUpdate:function(){this.initActions(),Object(r.a)(this.$slots.default,c,this)},methods:{toggleMenu:function(e){var t=this;this.opened=!this.opened,this.opened?(this.$nextTick((function(){t.onOpen(),t.focusFirstAction()})),this.$emit("open",e)):(this.offsetX=0,this.offsetY=0,this.offsetYArrow=0,this.rotateArrow=!1),this.$emit("update:open",this.opened)},closeMenu:function(e){this.opened&&(this.$emit("update:open",!1),this.$emit("close",e)),this.opened=!1,this.offsetX=0,this.offsetY=0,this.offsetYArrow=0,this.rotateArrow=!1},onOpen:function(){if(this.offsetX=0,this.offsetY=0,this.offsetYArrow=0,this.rotateArrow=!1,"center"===this.menuAlign){var e=o(this.$refs.menu);(e.left||e.right)&&(this.offsetX=e.offsetX>0?Math.round(e.offsetX)+5:Math.round(e.offsetX)-5),e.bottom&&(this.offsetY=0-Math.round(this.$refs.menu.clientHeight)-42,this.offsetYArrow=Math.round(this.$refs.menu.clientHeight)+18,this.rotateArrow=!0)}},onMouseFocusAction:function(e){if(document.activeElement!==e.target){var t=e.target.closest("li");if(t){var s=t.querySelector(".focusable");if(s){var A=this.$refs.menu.querySelectorAll(".focusable"),n=Array.prototype.indexOf.call(A,s);n>-1&&(this.focusIndex=n,this.focusAction())}}}},removeCurrentActive:function(){var e=this.$refs.menu.querySelector("li.active");e&&e.classList.remove("active")},focusAction:function(){var e=this.$refs.menu.querySelectorAll(".focusable")[this.focusIndex];if(e){var t=e.closest("li");e.focus(),t&&(this.removeCurrentActive(),t.classList.add("active"))}},focusPreviousAction:function(){this.focusIndex=Math.max(this.focusIndex-1,0),this.focusAction()},focusNextAction:function(){this.focusIndex=Math.min(this.focusIndex+1,this.$refs.menu.querySelectorAll(".focusable").length-1),this.focusAction()},focusFirstAction:function(){this.focusIndex=0,this.focusAction()},focusLastAction:function(){this.focusIndex=this.$el.querySelectorAll(".focusable").length-1,this.focusAction()},execFirstAction:function(e){this.firstActionEvent&&this.firstActionEvent(e)},initActions:function(){this.actions=(this.$slots.default||[]).filter((function(e){return!!e&&!!e.componentOptions}))}}},m=(s(66),s(0)),g=s(37),u=s.n(g),d=Object(m.a)(l,(function(){var e=this,t=e.$createElement,s=e._self._c||t;return e.isValidSingleAction&&!e.forceMenu?s("element",e._b({directives:[{name:"tooltip",rawName:"v-tooltip.auto",value:e.firstAction.text,expression:"firstAction.text",modifiers:{auto:!0}}],staticClass:"action-item action-item--single",class:[e.firstAction.icon,e.firstActionClass],attrs:{rel:"noreferrer noopener"},on:e._d({},[e.firstActionEventBinding,e.execFirstAction])},"element",e.firstActionBinding,!1),[s("span",{attrs:{"aria-hidden":!0,hidden:""}},[e._t("default")],2)]):s("div",{directives:[{name:"show",rawName:"v-show",value:e.hasMultipleActions||e.forceMenu,expression:"hasMultipleActions || forceMenu"},{name:"click-outside",rawName:"v-click-outside",value:e.closeMenu,expression:"closeMenu"}],staticClass:"action-item",class:{"action-item--open":e.opened},on:{keydown:[function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"up",38,t.key,["Up","ArrowUp"])?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusPreviousAction(t))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"down",40,t.key,["Down","ArrowDown"])?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusNextAction(t))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"tab",9,t.key,"Tab")?null:t.shiftKey?(t.preventDefault(),e.focusPreviousAction(t)):null},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"page-up",void 0,t.key,void 0)?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusFirstAction(t))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"page-down",void 0,t.key,void 0)?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.focusLastAction(t))},function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"esc",27,t.key,["Esc","Escape"])?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.closeMenu(t))}]}},[s("a",{staticClass:"icon action-item__menutoggle",class:e.defaultIcon,attrs:{href:"#","aria-label":e.ariaLabel,"aria-haspopup":"true","aria-controls":e.randomId,"aria-expanded":e.opened},on:{click:function(t){return t.preventDefault(),e.toggleMenu(t)},keydown:function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"space",32,t.key,[" ","Spacebar"])?null:t.ctrlKey||t.shiftKey||t.altKey||t.metaKey?null:(t.preventDefault(),e.toggleMenu(t))}}}),e._v(" "),s("div",{directives:[{name:"show",rawName:"v-show",value:e.opened,expression:"opened"}],ref:"menu",staticClass:"action-item__menu",class:["menu-"+e.menuAlign,{open:e.opened}],style:{marginRight:e.offsetX+"px",marginTop:e.offsetY+"px"},attrs:{tabindex:"-1"},on:{mousemove:e.onMouseFocusAction}},[s("div",{staticClass:"action-item__menu_arrow",style:{transform:"translateX("+e.offsetX+"px) translateY("+e.offsetYArrow+"px) "+(e.rotateArrow?" rotate(180deg)":"")}}),e._v(" "),s("ul",{attrs:{id:e.randomId,tabindex:"-1"}},[e.opened?[e._t("default")]:e._e()],2)])])}),[],!1,null,"7b368b0c",null);"function"==typeof u.a&&u()(d);t.a=d.exports},46:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.array.filter */ "./node_modules/core-js/modules/es.array.filter.js")},5:function(e,t){e.exports=__webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js")},6:function(e,t,s){"use strict";s.d(t,"b",(function(){return r})),s.d(t,"a",(function(){return o}));s(22);var A=s(27),n=Object(A.getGettextBuilder)().detectLocale();[{locale:"cs_CZ",json:{charset:"utf-8",headers:{"Last-Translator":"Pavel Borecki <pavel.borecki@gmail.com>, 2020","Language-Team":"Czech (Czech Republic) (https://www.transifex.com/nextcloud/teams/64236/cs_CZ/)","Content-Type":"text/plain; charset=UTF-8",Language:"cs_CZ","Plural-Forms":"nplurals=4; plural=(n == 1 && n % 1 == 0) ? 0 : (n >= 2 && n <= 4 && n % 1 == 0) ? 1: (n % 1 != 0 ) ? 2 : 3;"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nPavel Borecki <pavel.borecki@gmail.com>, 2020\n"},msgstr:["Last-Translator: Pavel Borecki <pavel.borecki@gmail.com>, 2020\nLanguage-Team: Czech (Czech Republic) (https://www.transifex.com/nextcloud/teams/64236/cs_CZ/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: cs_CZ\nPlural-Forms: nplurals=4; plural=(n == 1 && n % 1 == 0) ? 0 : (n >= 2 && n <= 4 && n % 1 == 0) ? 1: (n % 1 != 0 ) ? 2 : 3;\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (neviditelný)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (omezený)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:194"},msgstr:["Akce"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Zvolit"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Zavřít"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Následující"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Žádné výsledky"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Pozastavit prezentaci"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Předchozí"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Vybrat štítek"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Nastavení"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Spustit prezentaci"]}}}}},{locale:"de",json:{charset:"utf-8",headers:{"Last-Translator":"Philipp Fischbeck <pfischbeck@googlemail.com>, 2020","Language-Team":"German (https://www.transifex.com/nextcloud/teams/64236/de/)","Content-Type":"text/plain; charset=UTF-8",Language:"de","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nMark Ziegler <mark.ziegler@rakekniven.de>, 2020\nPhilipp Fischbeck <pfischbeck@googlemail.com>, 2020\n"},msgstr:["Last-Translator: Philipp Fischbeck <pfischbeck@googlemail.com>, 2020\nLanguage-Team: German (https://www.transifex.com/nextcloud/teams/64236/de/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: de\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (unsichtbar)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (eingeschränkt)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:194"},msgstr:["Aktionen"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Auswählen"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Schließen"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Weiter"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Keine Ergebnisse"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Diashow pausieren"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Vorherige"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Schlagwort auswählen"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Einstellungen"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Diashow starten"]}}}}},{locale:"de_DE",json:{charset:"utf-8",headers:{"Last-Translator":"Philipp Fischbeck <pfischbeck@googlemail.com>, 2020","Language-Team":"German (Germany) (https://www.transifex.com/nextcloud/teams/64236/de_DE/)","Content-Type":"text/plain; charset=UTF-8",Language:"de_DE","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nMark Ziegler <mark.ziegler@rakekniven.de>, 2020\nPhilipp Fischbeck <pfischbeck@googlemail.com>, 2020\n"},msgstr:["Last-Translator: Philipp Fischbeck <pfischbeck@googlemail.com>, 2020\nLanguage-Team: German (Germany) (https://www.transifex.com/nextcloud/teams/64236/de_DE/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: de_DE\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (unsichtbar)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (eingeschränkt)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:194"},msgstr:["Aktionen"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Auswählen"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Schließen"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Weiter"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Keine Ergebnisse"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Diashow pausieren"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Vorherige"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Schlagwort auswählen"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Einstellungen"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Diashow starten"]}}}}},{locale:"el",json:{charset:"utf-8",headers:{"Last-Translator":"george k <norhorn@gmail.com>, 2020","Language-Team":"Greek (https://www.transifex.com/nextcloud/teams/64236/el/)","Content-Type":"text/plain; charset=UTF-8",Language:"el","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nEfstathios Iosifidis <iefstathios@gmail.com>, 2020\ngeorge k <norhorn@gmail.com>, 2020\n"},msgstr:["Last-Translator: george k <norhorn@gmail.com>, 2020\nLanguage-Team: Greek (https://www.transifex.com/nextcloud/teams/64236/el/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: el\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (αόρατο)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (περιορισμένο)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:194"},msgstr:["Ενέργειες"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Επιλογή"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Κλείσιμο"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Επόμενο"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Κανένα αποτέλεσμα"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Παύση προβολής διαφανειών"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Προηγούμενο"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Επιλογή ετικέτας"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Ρυθμίσεις"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Έναρξη προβολής διαφανειών"]}}}}},{locale:"eu",json:{charset:"utf-8",headers:{"Last-Translator":"Asier Iturralde Sarasola <asier.iturralde@gmail.com>, 2020","Language-Team":"Basque (https://www.transifex.com/nextcloud/teams/64236/eu/)","Content-Type":"text/plain; charset=UTF-8",Language:"eu","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nAsier Iturralde Sarasola <asier.iturralde@gmail.com>, 2020\n"},msgstr:["Last-Translator: Asier Iturralde Sarasola <asier.iturralde@gmail.com>, 2020\nLanguage-Team: Basque (https://www.transifex.com/nextcloud/teams/64236/eu/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: eu\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (ikusezina)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (mugatua)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Aukeratu"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["Itxi"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["Hurrengoa"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Emaitzarik ez"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Pausatu diaporama"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["Aurrekoa"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Hautatu etiketa bat"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Ezarpenak"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Hasi diaporama"]}}}}},{locale:"fr",json:{charset:"utf-8",headers:{"Last-Translator":"Greg Greg <grena@grenabox.fr>, 2020","Language-Team":"French (https://www.transifex.com/nextcloud/teams/64236/fr/)","Content-Type":"text/plain; charset=UTF-8",Language:"fr","Plural-Forms":"nplurals=2; plural=(n > 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nBrendan Abolivier <transifex@brendanabolivier.com>, 2020\ngud bes <gudbes@protonmail.com>, 2020\nGreg Greg <grena@grenabox.fr>, 2020\n"},msgstr:["Last-Translator: Greg Greg <grena@grenabox.fr>, 2020\nLanguage-Team: French (https://www.transifex.com/nextcloud/teams/64236/fr/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: fr\nPlural-Forms: nplurals=2; plural=(n > 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (invisible)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (restreint)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:196"},msgstr:["Actions"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Choisir"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Fermer"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Suivant"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Aucun résultat"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Mettre le diaporama en pause"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Précédent"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Sélectionnez une balise"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Paramètres"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Démarrer le diaporama"]}}}}},{locale:"gl",json:{charset:"utf-8",headers:{"Last-Translator":"Miguel Anxo Bouzada <mbouzada@gmail.com>, 2020","Language-Team":"Galician (https://www.transifex.com/nextcloud/teams/64236/gl/)","Content-Type":"text/plain; charset=UTF-8",Language:"gl","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nMiguel Anxo Bouzada <mbouzada@gmail.com>, 2020\n"},msgstr:["Last-Translator: Miguel Anxo Bouzada <mbouzada@gmail.com>, 2020\nLanguage-Team: Galician (https://www.transifex.com/nextcloud/teams/64236/gl/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: gl\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (invisíbel)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (restrinxido)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:196"},msgstr:["Accións"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Escoller"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Pechar"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Seguinte"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Sen resultados"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Pausar o diaporama"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Anterir"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Seleccione unha etiqueta"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Axustes"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Iniciar o diaporama"]}}}}},{locale:"he",json:{charset:"utf-8",headers:{"Last-Translator":"Yaron Shahrabani <sh.yaron@gmail.com>, 2020","Language-Team":"Hebrew (https://www.transifex.com/nextcloud/teams/64236/he/)","Content-Type":"text/plain; charset=UTF-8",Language:"he","Plural-Forms":"nplurals=4; plural=(n == 1 && n % 1 == 0) ? 0 : (n == 2 && n % 1 == 0) ? 1: (n % 10 == 0 && n % 1 == 0 && n > 10) ? 2 : 3;"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nYaron Shahrabani <sh.yaron@gmail.com>, 2020\n"},msgstr:["Last-Translator: Yaron Shahrabani <sh.yaron@gmail.com>, 2020\nLanguage-Team: Hebrew (https://www.transifex.com/nextcloud/teams/64236/he/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: he\nPlural-Forms: nplurals=4; plural=(n == 1 && n % 1 == 0) ? 0 : (n == 2 && n % 1 == 0) ? 1: (n % 10 == 0 && n % 1 == 0 && n > 10) ? 2 : 3;\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (נסתר)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (מוגבל)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["בחירה"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["סגירה"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["הבא"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["אין תוצאות"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["השהיית מצגת"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["הקודם"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["בחירת תגית"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["הגדרות"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["התחלת המצגת"]}}}}},{locale:"it",json:{charset:"utf-8",headers:{"Last-Translator":"Random_R, 2020","Language-Team":"Italian (https://www.transifex.com/nextcloud/teams/64236/it/)","Content-Type":"text/plain; charset=UTF-8",Language:"it","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nRandom_R, 2020\n"},msgstr:["Last-Translator: Random_R, 2020\nLanguage-Team: Italian (https://www.transifex.com/nextcloud/teams/64236/it/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: it\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (invisibile)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (limitato)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:196"},msgstr:["Azioni"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Scegli"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Chiudi"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Successivo"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Nessun risultato"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Presentazione in pausa"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Precedente"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Seleziona un'etichetta"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Impostazioni"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Avvia presentazione"]}}}}},{locale:"lt_LT",json:{charset:"utf-8",headers:{"Last-Translator":"Moo, 2020","Language-Team":"Lithuanian (Lithuania) (https://www.transifex.com/nextcloud/teams/64236/lt_LT/)","Content-Type":"text/plain; charset=UTF-8",Language:"lt_LT","Plural-Forms":"nplurals=4; plural=(n % 10 == 1 && (n % 100 > 19 || n % 100 < 11) ? 0 : (n % 10 >= 2 && n % 10 <=9) && (n % 100 > 19 || n % 100 < 11) ? 1 : n % 1 != 0 ? 2: 3);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nMoo, 2020\n"},msgstr:["Last-Translator: Moo, 2020\nLanguage-Team: Lithuanian (Lithuania) (https://www.transifex.com/nextcloud/teams/64236/lt_LT/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: lt_LT\nPlural-Forms: nplurals=4; plural=(n % 10 == 1 && (n % 100 > 19 || n % 100 < 11) ? 0 : (n % 10 >= 2 && n % 10 <=9) && (n % 100 > 19 || n % 100 < 11) ? 1 : n % 1 != 0 ? 2: 3);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (nematoma)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (apribota)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Pasirinkti"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["Užverti"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["Kitas"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Nėra rezultatų"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Pristabdyti skaidrių rodymą"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["Ankstesnis"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Pasirinkti žymę"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Nustatymai"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Pradėti skaidrių rodymą"]}}}}},{locale:"lv",json:{charset:"utf-8",headers:{"Last-Translator":"stendec <stendec@inbox.lv>, 2020","Language-Team":"Latvian (https://www.transifex.com/nextcloud/teams/64236/lv/)","Content-Type":"text/plain; charset=UTF-8",Language:"lv","Plural-Forms":"nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nstendec <stendec@inbox.lv>, 2020\n"},msgstr:["Last-Translator: stendec <stendec@inbox.lv>, 2020\nLanguage-Team: Latvian (https://www.transifex.com/nextcloud/teams/64236/lv/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: lv\nPlural-Forms: nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (neredzams)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (ierobežots)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Izvēlēties"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["Aizvērt"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["Nākamais"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Nav rezultātu"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Pauzēt slaidrādi"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["Iepriekšējais"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Izvēlēties birku"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Iestatījumi"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Sākt slaidrādi"]}}}}},{locale:"mk",json:{charset:"utf-8",headers:{"Last-Translator":"Сашко Тодоров, 2020","Language-Team":"Macedonian (https://www.transifex.com/nextcloud/teams/64236/mk/)","Content-Type":"text/plain; charset=UTF-8",Language:"mk","Plural-Forms":"nplurals=2; plural=(n % 10 == 1 && n % 100 != 11) ? 0 : 1;"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nСашко Тодоров, 2020\n"},msgstr:["Last-Translator: Сашко Тодоров, 2020\nLanguage-Team: Macedonian (https://www.transifex.com/nextcloud/teams/64236/mk/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: mk\nPlural-Forms: nplurals=2; plural=(n % 10 == 1 && n % 100 != 11) ? 0 : 1;\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (невидливо)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (ограничено)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Избери"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["Затвори"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["Следно"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Нема резултати"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Пузирај слајдшоу"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["Предходно"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Избери ознака"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Параметри"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Стартувај слајдшоу"]}}}}},{locale:"nl",json:{charset:"utf-8",headers:{"Last-Translator":"Arjan van S, 2020","Language-Team":"Dutch (https://www.transifex.com/nextcloud/teams/64236/nl/)","Content-Type":"text/plain; charset=UTF-8",Language:"nl","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nRoeland Jago Douma <roeland@famdouma.nl>, 2020\nArjan van S, 2020\n"},msgstr:["Last-Translator: Arjan van S, 2020\nLanguage-Team: Dutch (https://www.transifex.com/nextcloud/teams/64236/nl/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: nl\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (onzichtbaar)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (beperkt)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:196"},msgstr:["Acties"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Kies"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Sluiten"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Volgende"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Geen resultaten"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Pauzeer diavoorstelling"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Vorige"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Selecteer een label"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Instellingen"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Start diavoorstelling"]}}}}},{locale:"oc",json:{charset:"utf-8",headers:{"Last-Translator":"Quentin PAGÈS, 2020","Language-Team":"Occitan (post 1500) (https://www.transifex.com/nextcloud/teams/64236/oc/)","Content-Type":"text/plain; charset=UTF-8",Language:"oc","Plural-Forms":"nplurals=2; plural=(n > 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nQuentin PAGÈS, 2020\n"},msgstr:["Last-Translator: Quentin PAGÈS, 2020\nLanguage-Team: Occitan (post 1500) (https://www.transifex.com/nextcloud/teams/64236/oc/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: oc\nPlural-Forms: nplurals=2; plural=(n > 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (invisible)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (limit)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:194"},msgstr:["Accions"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Causir"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Tampar"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Seguent"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Cap de resultat"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Metre en pausa lo diaporama"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Precedent"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Seleccionar una etiqueta"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Paramètres"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Lançar lo diaporama"]}}}}},{locale:"pl",json:{charset:"utf-8",headers:{"Last-Translator":"Valdnet Valdnet, 2020","Language-Team":"Polish (https://www.transifex.com/nextcloud/teams/64236/pl/)","Content-Type":"text/plain; charset=UTF-8",Language:"pl","Plural-Forms":"nplurals=4; plural=(n==1 ? 0 : (n%10>=2 && n%10<=4) && (n%100<12 || n%100>14) ? 1 : n!=1 && (n%10>=0 && n%10<=1) || (n%10>=5 && n%10<=9) || (n%100>=12 && n%100<=14) ? 2 : 3);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nValdnet Valdnet, 2020\n"},msgstr:["Last-Translator: Valdnet Valdnet, 2020\nLanguage-Team: Polish (https://www.transifex.com/nextcloud/teams/64236/pl/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: pl\nPlural-Forms: nplurals=4; plural=(n==1 ? 0 : (n%10>=2 && n%10<=4) && (n%100<12 || n%100>14) ? 1 : n!=1 && (n%10>=0 && n%10<=1) || (n%10>=5 && n%10<=9) || (n%100>=12 && n%100<=14) ? 2 : 3);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (niewidoczna)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (ograniczona)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:196"},msgstr:["Działania"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Wybierz"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Zamknij"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Następny"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Brak wyników"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Wstrzymaj pokaz slajdów"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Poprzedni"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Wybierz etykietę"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Ustawienia"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Rozpocznij pokaz slajdów"]}}}}},{locale:"pt_BR",json:{charset:"utf-8",headers:{"Last-Translator":"Paulo Schopf, 2020","Language-Team":"Portuguese (Brazil) (https://www.transifex.com/nextcloud/teams/64236/pt_BR/)","Content-Type":"text/plain; charset=UTF-8",Language:"pt_BR","Plural-Forms":"nplurals=2; plural=(n > 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nMaurício Gardini <accounts@mauriciogardini.com>, 2020\nPaulo Schopf, 2020\n"},msgstr:["Last-Translator: Paulo Schopf, 2020\nLanguage-Team: Portuguese (Brazil) (https://www.transifex.com/nextcloud/teams/64236/pt_BR/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: pt_BR\nPlural-Forms: nplurals=2; plural=(n > 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (invisível)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (restrito) "]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:194"},msgstr:["Ações"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Escolher"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Fechar"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Próximo"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Sem resultados"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Pausar apresentação de slides"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Anterior"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Selecionar uma tag"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Configurações"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Iniciar apresentação de slides"]}}}}},{locale:"pt_PT",json:{charset:"utf-8",headers:{"Last-Translator":"fpapoila <fpapoila@gmail.com>, 2020","Language-Team":"Portuguese (Portugal) (https://www.transifex.com/nextcloud/teams/64236/pt_PT/)","Content-Type":"text/plain; charset=UTF-8",Language:"pt_PT","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nfpapoila <fpapoila@gmail.com>, 2020\n"},msgstr:["Last-Translator: fpapoila <fpapoila@gmail.com>, 2020\nLanguage-Team: Portuguese (Portugal) (https://www.transifex.com/nextcloud/teams/64236/pt_PT/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: pt_PT\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (invisivel)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (restrito)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Escolher"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["Fechar"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["Proximo"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Sem resultados"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Pausar apresentação de slides"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["Anterior"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Selecionar etiqueta"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Definições"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Iniciar apresentação de slides"]}}}}},{locale:"ru",json:{charset:"utf-8",headers:{"Last-Translator":"Alex <kekcuha@gmail.com>, 2020","Language-Team":"Russian (https://www.transifex.com/nextcloud/teams/64236/ru/)","Content-Type":"text/plain; charset=UTF-8",Language:"ru","Plural-Forms":"nplurals=4; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : n%10==0 || (n%10>=5 && n%10<=9) || (n%100>=11 && n%100<=14)? 2 : 3);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nAlex <kekcuha@gmail.com>, 2020\n"},msgstr:["Last-Translator: Alex <kekcuha@gmail.com>, 2020\nLanguage-Team: Russian (https://www.transifex.com/nextcloud/teams/64236/ru/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: ru\nPlural-Forms: nplurals=4; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : n%10==0 || (n%10>=5 && n%10<=9) || (n%100>=11 && n%100<=14)? 2 : 3);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (невидимое)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (ограниченное)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Выберите"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["Закрыть"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["Следующее"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Результаты отсуствуют"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Приостановить показ слйдов"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["Предыдущее"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Выберите метку"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Параметры"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Начать показ слайдов"]}}}}},{locale:"sv",json:{charset:"utf-8",headers:{"Last-Translator":"Jonatan Nyberg, 2020","Language-Team":"Swedish (https://www.transifex.com/nextcloud/teams/64236/sv/)","Content-Type":"text/plain; charset=UTF-8",Language:"sv","Plural-Forms":"nplurals=2; plural=(n != 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nGabriel Ekström <gabriel.ekstrom06@gmail.com>, 2020\nErik Lennartsson, 2020\nJonatan Nyberg, 2020\n"},msgstr:["Last-Translator: Jonatan Nyberg, 2020\nLanguage-Team: Swedish (https://www.transifex.com/nextcloud/teams/64236/sv/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: sv\nPlural-Forms: nplurals=2; plural=(n != 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:170"},msgstr:["{tag} (osynlig)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:173"},msgstr:["{tag} (begränsad)"]},Actions:{msgid:"Actions",comments:{reference:"src/components/Actions/Actions.vue:194"},msgstr:["Åtgärder"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Välj"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:117"},msgstr:["Stäng"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:166"},msgstr:["Nästa"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:172\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Inga resultat"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Pausa bildspel"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:144"},msgstr:["Föregående"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Välj en tag"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Inställningar"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:302"},msgstr:["Starta bildspel"]}}}}},{locale:"tr",json:{charset:"utf-8",headers:{"Last-Translator":"Kemal Oktay Aktoğan <oktayaktogan@gmail.com>, 2020","Language-Team":"Turkish (https://www.transifex.com/nextcloud/teams/64236/tr/)","Content-Type":"text/plain; charset=UTF-8",Language:"tr","Plural-Forms":"nplurals=2; plural=(n > 1);"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nKemal Oktay Aktoğan <oktayaktogan@gmail.com>, 2020\n"},msgstr:["Last-Translator: Kemal Oktay Aktoğan <oktayaktogan@gmail.com>, 2020\nLanguage-Team: Turkish (https://www.transifex.com/nextcloud/teams/64236/tr/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: tr\nPlural-Forms: nplurals=2; plural=(n > 1);\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (görünmez)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (kısıtlı)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["Seç"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["Kapat"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["İleri"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["Sonuç yok"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Slayt gösterisini duraklat"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["Önceki"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["Bir etiket seçin"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["Ayarlar"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["Slayt gösterisini başlat"]}}}}},{locale:"zh_TW",json:{charset:"utf-8",headers:{"Last-Translator":"byStarTW (pan93412) <pan93412@gmail.com>, 2020","Language-Team":"Chinese (Taiwan) (https://www.transifex.com/nextcloud/teams/64236/zh_TW/)","Content-Type":"text/plain; charset=UTF-8",Language:"zh_TW","Plural-Forms":"nplurals=1; plural=0;"},translations:{"":{"":{msgid:"",comments:{translator:"Translators:\nbyStarTW (pan93412) <pan93412@gmail.com>, 2020\n"},msgstr:["Last-Translator: byStarTW (pan93412) <pan93412@gmail.com>, 2020\nLanguage-Team: Chinese (Taiwan) (https://www.transifex.com/nextcloud/teams/64236/zh_TW/)\nContent-Type: text/plain; charset=UTF-8\nLanguage: zh_TW\nPlural-Forms: nplurals=1; plural=0;\n"]},"{tag} (invisible)":{msgid:"{tag} (invisible)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:169"},msgstr:["{tag} (隱藏)"]},"{tag} (restricted)":{msgid:"{tag} (restricted)",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:172"},msgstr:["{tag} (受限)"]},Choose:{msgid:"Choose",comments:{reference:"src/components/ColorPicker/ColorPicker.vue:145"},msgstr:["選擇"]},Close:{msgid:"Close",comments:{reference:"src/components/Modal/Modal.vue:109"},msgstr:["關閉"]},Next:{msgid:"Next",comments:{reference:"src/components/Modal/Modal.vue:154"},msgstr:["下一個"]},"No results":{msgid:"No results",comments:{reference:"src/components/Multiselect/Multiselect.vue:169\nsrc/components/MultiselectTags/MultiselectTags.vue:78"},msgstr:["無結果"]},"Pause slideshow":{msgid:"Pause slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["暫停幻燈片"]},Previous:{msgid:"Previous",comments:{reference:"src/components/Modal/Modal.vue:134"},msgstr:["上一個"]},"Select a tag":{msgid:"Select a tag",comments:{reference:"src/components/MultiselectTags/MultiselectTags.vue:100"},msgstr:["選擇標籤"]},Settings:{msgid:"Settings",comments:{reference:"src/components/AppNavigationSettings/AppNavigationSettings.vue:53"},msgstr:["設定"]},"Start slideshow":{msgid:"Start slideshow",comments:{reference:"src/components/Modal/Modal.vue:290"},msgstr:["開始幻燈片"]}}}}}].map((function(e){return n.addTranslation(e.locale,e.json)}));var a=n.build(),o=a.ngettext.bind(a),r=a.gettext.bind(a)},64:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.object.assign */ "./node_modules/core-js/modules/es.object.assign.js")},65:function(e,t){e.exports=__webpack_require__(/*! core-js/modules/es.array.splice */ "./node_modules/core-js/modules/es.array.splice.js")},66:function(e,t,s){"use strict";var A=s(29);s.n(A).a},67:function(e,t,s){var A=s(1),n=s(7),a=s(8),o=s(9),r=s(10),i=s(11);t=A(!1);var c=n(a),l=n(o),m=n(r),g=n(i);t.push([e.i,'@font-face{font-family:"iconfont-vue-e1f55ce";src:url('+c+");src:url("+c+') format("embedded-opentype"),url('+l+') format("woff"),url('+m+') format("truetype"),url('+g+') format("svg")}.icon[data-v-7b368b0c]{font-style:normal;font-weight:400}.icon.arrow-left-double[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.arrow-left[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.arrow-right-double[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.arrow-right[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.breadcrumb[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.checkmark[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.close[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.confirm[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.info[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.menu[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.more[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.pause[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.play[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.icon.triangle-s[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";content:""}.action-item[data-v-7b368b0c]{position:relative;display:inline-block}.action-item--single[data-v-7b368b0c]:hover,.action-item--single[data-v-7b368b0c]:focus,.action-item--single[data-v-7b368b0c]:active,.action-item__menutoggle[data-v-7b368b0c]:hover,.action-item__menutoggle[data-v-7b368b0c]:focus,.action-item__menutoggle[data-v-7b368b0c]:active{border-radius:22px;background-color:rgba(127,127,127,0.25) !important;opacity:1}.action-item.action-item--open .action-item__menutoggle[data-v-7b368b0c]{opacity:1;border-radius:22px;background-color:rgba(127,127,127,0.25)}.action-item--single[data-v-7b368b0c],.action-item__menutoggle[data-v-7b368b0c]{box-sizing:border-box;width:44px;height:44px;margin:0;padding:14px;cursor:pointer;border:none;background-color:transparent}.action-item__menutoggle[data-v-7b368b0c]{display:flex;align-items:center;justify-content:center;opacity:.7}.action-item__menutoggle[data-v-7b368b0c]:before{content:\'\'}.action-item__menutoggle--default-icon[data-v-7b368b0c]{font-size:16px}.action-item__menutoggle--default-icon[data-v-7b368b0c]:before{font-family:"iconfont-vue-e1f55ce";font-style:normal;font-weight:400;content:""}.action-item--single[data-v-7b368b0c]{opacity:.7}.action-item--single[data-v-7b368b0c]:hover,.action-item--single[data-v-7b368b0c]:focus,.action-item--single[data-v-7b368b0c]:active{opacity:1}.action-item--single>[hidden][data-v-7b368b0c]{display:none}.action-item--multiple[data-v-7b368b0c]{position:relative}.action-item__menu[data-v-7b368b0c]{position:absolute;z-index:110;right:50%;display:none;margin-bottom:10px;margin-top:-5px;transform:translateX(50%);color:var(--color-main-text);border-radius:var(--border-radius);background-color:var(--color-main-background);filter:drop-shadow(0 1px 3px var(--color-box-shadow))}.action-item__menu ul[data-v-7b368b0c]>:not(li){display:none}.action-item__menu.open[data-v-7b368b0c]{display:block}.action-item__menu .action-item__menu_arrow[data-v-7b368b0c]{position:absolute;right:50%;bottom:100%;width:0;height:0;margin-right:-9px;content:\' \';pointer-events:none;border:solid transparent;border-width:9px;border-bottom-color:var(--color-main-background)}.action-item__menu.menu-right[data-v-7b368b0c]{right:0;left:auto;transform:none}.action-item__menu.menu-right .action-item__menu_arrow[data-v-7b368b0c]{right:13px;margin-right:0}.action-item__menu.menu-left[data-v-7b368b0c]{right:auto;left:0;transform:none}.action-item__menu.menu-left .action-item__menu_arrow[data-v-7b368b0c]{right:auto;left:13px;margin-right:0}.ie .action-item__menu[data-v-7b368b0c],.ie .action-item__menu .action-item__menu_arrow[data-v-7b368b0c],.edge .action-item__menu[data-v-7b368b0c],.edge .action-item__menu .action-item__menu_arrow[data-v-7b368b0c]{border:1px solid var(--color-border)}\n',""]),e.exports=t},7:function(e,t,s){"use strict";e.exports=function(e,t){return t||(t={}),"string"!=typeof(e=e&&e.__esModule?e.default:e)?e:(/^['"].*['"]$/.test(e)&&(e=e.slice(1,-1)),t.hash&&(e+=t.hash),/["'() \t\n]/.test(e)||t.needQuotes?'"'.concat(e.replace(/"/g,'\\"').replace(/\n/g,"\\n"),'"'):e)}},8:function(e,t,s){"use strict";s.r(t),t.default="data:application/vnd.ms-fontobject;base64,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"},9:function(e,t,s){"use strict";s.r(t),t.default="data:font/woff;base64,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DDUAAgAACQIE4gTiBOIMNfseBOAAAQAAAAEAAEB6caFfDzz1AAsTiAAAAADasyhOAAAAANpiTE///wAAE5MTiAAAAAgAAgAAAAAAAAABAAATiAAAAAATiP////UTkwABAAAAAAAAAAAAAAAAAAAABwAAAAATiAAAE4gAABOIAAATiAAABjYAABOIAAAAAP//AAAAAAAAAAAAAAAAAAAAAAAiADYAWABsAIAAlAC0AQ4BfAGaAhACJgI0AkIAAQAAAA8ASwADAAAAAAACAAAACgAKAAAA/wAAAAAAAAAAABAAxgABAAAAAAABABQAAAABAAAAAAACAAcAFAABAAAAAAADABQAGwABAAAAAAAEABQALwABAAAAAAAFAAsAQwABAAAAAAAGABQATgABAAAAAAAKACsAYgABAAAAAAALABMAjQADAAEECQABACgAoAADAAEECQACAA4AyAADAAEECQADACgA1gADAAEECQAEACgA/gADAAEECQAFABYBJgADAAEECQAGACgBPAADAAEECQAKAFYBZAADAAEECQALACYBumljb25mb250LXZ1ZS1lMWY1NWNlUmVndWxhcmljb25mb250LXZ1ZS1lMWY1NWNlaWNvbmZvbnQtdnVlLWUxZjU1Y2VWZXJzaW9uIDEuMGljb25mb250LXZ1ZS1lMWY1NWNlR2VuZXJhdGVkIGJ5IHN2ZzJ0dGYgZnJvbSBGb250ZWxsbyBwcm9qZWN0Lmh0dHA6Ly9mb250ZWxsby5jb20AaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBlADEAZgA1ADUAYwBlAFIAZQBnAHUAbABhAHIAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBlADEAZgA1ADUAYwBlAGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAC0AZQAxAGYANQA1AGMAZQBWAGUAcgBzAGkAbwBuACAAMQAuADAAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUALQBlADEAZgA1ADUAYwBlAEcAZQBuAGUAcgBhAHQAZQBkACAAYgB5ACAAcwB2AGcAMgB0AHQAZgAgAGYAcgBvAG0AIABGAG8AbgB0AGUAbABsAG8AIABwAHIAbwBqAGUAYwB0AC4AaAB0AHQAcAA6AC8ALwBmAG8AbgB0AGUAbABsAG8ALgBjAG8AbQAAAAIAAAAAAAAAMgAAAAAAAAAAAAAAAAAAAAAAAAAAAA8ADwAAAQIBAwEEAQUBBgEHAQgBCQEKAQsBDAENAQ4BDxFhcnJvdy1sZWZ0LWRvdWJsZQphcnJvdy1sZWZ0EmFycm93LXJpZ2h0LWRvdWJsZQthcnJvdy1yaWdodApicmVhZGNydW1iCWNoZWNrbWFyawVjbG9zZQdjb25maXJtBGluZm8EbWVudQRtb3JlBXBhdXNlBHBsYXkKdHJpYW5nbGUtcw=="}})}));
//# sourceMappingURL=AppSidebar.js.map

/***/ }),

/***/ "./node_modules/dompurify/dist/purify.js":
/*!***********************************************!*\
  !*** ./node_modules/dompurify/dist/purify.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

(function (global, factory) {
	 true ? module.exports = factory() :
	undefined;
}(this, (function () { 'use strict';

function _toConsumableArray$1(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var hasOwnProperty = Object.hasOwnProperty;
var setPrototypeOf = Object.setPrototypeOf;
var isFrozen = Object.isFrozen;
var objectKeys = Object.keys;
var freeze = Object.freeze;
var seal = Object.seal; // eslint-disable-line import/no-mutable-exports

var _ref = typeof Reflect !== 'undefined' && Reflect;
var apply = _ref.apply;
var construct = _ref.construct;

if (!apply) {
  apply = function apply(fun, thisValue, args) {
    return fun.apply(thisValue, args);
  };
}

if (!freeze) {
  freeze = function freeze(x) {
    return x;
  };
}

if (!seal) {
  seal = function seal(x) {
    return x;
  };
}

if (!construct) {
  construct = function construct(Func, args) {
    return new (Function.prototype.bind.apply(Func, [null].concat(_toConsumableArray$1(args))))();
  };
}

var arrayForEach = unapply(Array.prototype.forEach);
var arrayIndexOf = unapply(Array.prototype.indexOf);
var arrayJoin = unapply(Array.prototype.join);
var arrayPop = unapply(Array.prototype.pop);
var arrayPush = unapply(Array.prototype.push);
var arraySlice = unapply(Array.prototype.slice);

var stringToLowerCase = unapply(String.prototype.toLowerCase);
var stringMatch = unapply(String.prototype.match);
var stringReplace = unapply(String.prototype.replace);
var stringIndexOf = unapply(String.prototype.indexOf);
var stringTrim = unapply(String.prototype.trim);

var regExpTest = unapply(RegExp.prototype.test);
var regExpCreate = unconstruct(RegExp);

var typeErrorCreate = unconstruct(TypeError);

function unapply(func) {
  return function (thisArg) {
    for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }

    return apply(func, thisArg, args);
  };
}

function unconstruct(func) {
  return function () {
    for (var _len2 = arguments.length, args = Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      args[_key2] = arguments[_key2];
    }

    return construct(func, args);
  };
}

/* Add properties to a lookup table */
function addToSet(set, array) {
  if (setPrototypeOf) {
    // Make 'in' and truthy checks like Boolean(set.constructor)
    // independent of any properties defined on Object.prototype.
    // Prevent prototype setters from intercepting set as a this value.
    setPrototypeOf(set, null);
  }

  var l = array.length;
  while (l--) {
    var element = array[l];
    if (typeof element === 'string') {
      var lcElement = stringToLowerCase(element);
      if (lcElement !== element) {
        // Config presets (e.g. tags.js, attrs.js) are immutable.
        if (!isFrozen(array)) {
          array[l] = lcElement;
        }

        element = lcElement;
      }
    }

    set[element] = true;
  }

  return set;
}

/* Shallow clone an object */
function clone(object) {
  var newObject = {};

  var property = void 0;
  for (property in object) {
    if (apply(hasOwnProperty, object, [property])) {
      newObject[property] = object[property];
    }
  }

  return newObject;
}

var html = freeze(['a', 'abbr', 'acronym', 'address', 'area', 'article', 'aside', 'audio', 'b', 'bdi', 'bdo', 'big', 'blink', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'content', 'data', 'datalist', 'dd', 'decorator', 'del', 'details', 'dfn', 'dir', 'div', 'dl', 'dt', 'element', 'em', 'fieldset', 'figcaption', 'figure', 'font', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'i', 'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'main', 'map', 'mark', 'marquee', 'menu', 'menuitem', 'meter', 'nav', 'nobr', 'ol', 'optgroup', 'option', 'output', 'p', 'picture', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'select', 'shadow', 'small', 'source', 'spacer', 'span', 'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead', 'time', 'tr', 'track', 'tt', 'u', 'ul', 'var', 'video', 'wbr']);

// SVG
var svg = freeze(['svg', 'a', 'altglyph', 'altglyphdef', 'altglyphitem', 'animatecolor', 'animatemotion', 'animatetransform', 'audio', 'canvas', 'circle', 'clippath', 'defs', 'desc', 'ellipse', 'filter', 'font', 'g', 'glyph', 'glyphref', 'hkern', 'image', 'line', 'lineargradient', 'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon', 'polyline', 'radialgradient', 'rect', 'stop', 'style', 'switch', 'symbol', 'text', 'textpath', 'title', 'tref', 'tspan', 'video', 'view', 'vkern']);

var svgFilters = freeze(['feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence']);

var mathMl = freeze(['math', 'menclose', 'merror', 'mfenced', 'mfrac', 'mglyph', 'mi', 'mlabeledtr', 'mmultiscripts', 'mn', 'mo', 'mover', 'mpadded', 'mphantom', 'mroot', 'mrow', 'ms', 'mspace', 'msqrt', 'mstyle', 'msub', 'msup', 'msubsup', 'mtable', 'mtd', 'mtext', 'mtr', 'munder', 'munderover']);

var text = freeze(['#text']);

var html$1 = freeze(['accept', 'action', 'align', 'alt', 'autocomplete', 'background', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'checked', 'cite', 'class', 'clear', 'color', 'cols', 'colspan', 'controls', 'coords', 'crossorigin', 'datetime', 'default', 'dir', 'disabled', 'download', 'enctype', 'face', 'for', 'headers', 'height', 'hidden', 'high', 'href', 'hreflang', 'id', 'integrity', 'ismap', 'label', 'lang', 'list', 'loop', 'low', 'max', 'maxlength', 'media', 'method', 'min', 'minlength', 'multiple', 'name', 'noshade', 'novalidate', 'nowrap', 'open', 'optimum', 'pattern', 'placeholder', 'poster', 'preload', 'pubdate', 'radiogroup', 'readonly', 'rel', 'required', 'rev', 'reversed', 'role', 'rows', 'rowspan', 'spellcheck', 'scope', 'selected', 'shape', 'size', 'sizes', 'span', 'srclang', 'start', 'src', 'srcset', 'step', 'style', 'summary', 'tabindex', 'title', 'type', 'usemap', 'valign', 'value', 'width', 'xmlns']);

var svg$1 = freeze(['accent-height', 'accumulate', 'additive', 'alignment-baseline', 'ascent', 'attributename', 'attributetype', 'azimuth', 'basefrequency', 'baseline-shift', 'begin', 'bias', 'by', 'class', 'clip', 'clip-path', 'clip-rule', 'color', 'color-interpolation', 'color-interpolation-filters', 'color-profile', 'color-rendering', 'cx', 'cy', 'd', 'dx', 'dy', 'diffuseconstant', 'direction', 'display', 'divisor', 'dur', 'edgemode', 'elevation', 'end', 'fill', 'fill-opacity', 'fill-rule', 'filter', 'filterunits', 'flood-color', 'flood-opacity', 'font-family', 'font-size', 'font-size-adjust', 'font-stretch', 'font-style', 'font-variant', 'font-weight', 'fx', 'fy', 'g1', 'g2', 'glyph-name', 'glyphref', 'gradientunits', 'gradienttransform', 'height', 'href', 'id', 'image-rendering', 'in', 'in2', 'k', 'k1', 'k2', 'k3', 'k4', 'kerning', 'keypoints', 'keysplines', 'keytimes', 'lang', 'lengthadjust', 'letter-spacing', 'kernelmatrix', 'kernelunitlength', 'lighting-color', 'local', 'marker-end', 'marker-mid', 'marker-start', 'markerheight', 'markerunits', 'markerwidth', 'maskcontentunits', 'maskunits', 'max', 'mask', 'media', 'method', 'mode', 'min', 'name', 'numoctaves', 'offset', 'operator', 'opacity', 'order', 'orient', 'orientation', 'origin', 'overflow', 'paint-order', 'path', 'pathlength', 'patterncontentunits', 'patterntransform', 'patternunits', 'points', 'preservealpha', 'preserveaspectratio', 'primitiveunits', 'r', 'rx', 'ry', 'radius', 'refx', 'refy', 'repeatcount', 'repeatdur', 'restart', 'result', 'rotate', 'scale', 'seed', 'shape-rendering', 'specularconstant', 'specularexponent', 'spreadmethod', 'stddeviation', 'stitchtiles', 'stop-color', 'stop-opacity', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'stroke', 'stroke-width', 'style', 'surfacescale', 'tabindex', 'targetx', 'targety', 'transform', 'text-anchor', 'text-decoration', 'text-rendering', 'textlength', 'type', 'u1', 'u2', 'unicode', 'values', 'viewbox', 'visibility', 'version', 'vert-adv-y', 'vert-origin-x', 'vert-origin-y', 'width', 'word-spacing', 'wrap', 'writing-mode', 'xchannelselector', 'ychannelselector', 'x', 'x1', 'x2', 'xmlns', 'y', 'y1', 'y2', 'z', 'zoomandpan']);

var mathMl$1 = freeze(['accent', 'accentunder', 'align', 'bevelled', 'close', 'columnsalign', 'columnlines', 'columnspan', 'denomalign', 'depth', 'dir', 'display', 'displaystyle', 'encoding', 'fence', 'frame', 'height', 'href', 'id', 'largeop', 'length', 'linethickness', 'lspace', 'lquote', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant', 'maxsize', 'minsize', 'movablelimits', 'notation', 'numalign', 'open', 'rowalign', 'rowlines', 'rowspacing', 'rowspan', 'rspace', 'rquote', 'scriptlevel', 'scriptminsize', 'scriptsizemultiplier', 'selection', 'separator', 'separators', 'stretchy', 'subscriptshift', 'supscriptshift', 'symmetric', 'voffset', 'width', 'xmlns']);

var xml = freeze(['xlink:href', 'xml:id', 'xlink:title', 'xml:space', 'xmlns:xlink']);

var MUSTACHE_EXPR = seal(/\{\{[\s\S]*|[\s\S]*\}\}/gm); // Specify template detection regex for SAFE_FOR_TEMPLATES mode
var ERB_EXPR = seal(/<%[\s\S]*|[\s\S]*%>/gm);
var DATA_ATTR = seal(/^data-[\-\w.\u00B7-\uFFFF]/); // eslint-disable-line no-useless-escape
var ARIA_ATTR = seal(/^aria-[\-\w]+$/); // eslint-disable-line no-useless-escape
var IS_ALLOWED_URI = seal(/^(?:(?:(?:f|ht)tps?|mailto|tel|callto|cid|xmpp):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i // eslint-disable-line no-useless-escape
);
var IS_SCRIPT_OR_DATA = seal(/^(?:\w+script|data):/i);
var ATTR_WHITESPACE = seal(/[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205f\u3000]/g // eslint-disable-line no-control-regex
);

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var getGlobal = function getGlobal() {
  return typeof window === 'undefined' ? null : window;
};

/**
 * Creates a no-op policy for internal use only.
 * Don't export this function outside this module!
 * @param {?TrustedTypePolicyFactory} trustedTypes The policy factory.
 * @param {Document} document The document object (to determine policy name suffix)
 * @return {?TrustedTypePolicy} The policy created (or null, if Trusted Types
 * are not supported).
 */
var _createTrustedTypesPolicy = function _createTrustedTypesPolicy(trustedTypes, document) {
  if ((typeof trustedTypes === 'undefined' ? 'undefined' : _typeof(trustedTypes)) !== 'object' || typeof trustedTypes.createPolicy !== 'function') {
    return null;
  }

  // Allow the callers to control the unique policy name
  // by adding a data-tt-policy-suffix to the script element with the DOMPurify.
  // Policy creation with duplicate names throws in Trusted Types.
  var suffix = null;
  var ATTR_NAME = 'data-tt-policy-suffix';
  if (document.currentScript && document.currentScript.hasAttribute(ATTR_NAME)) {
    suffix = document.currentScript.getAttribute(ATTR_NAME);
  }

  var policyName = 'dompurify' + (suffix ? '#' + suffix : '');

  try {
    return trustedTypes.createPolicy(policyName, {
      createHTML: function createHTML(html$$1) {
        return html$$1;
      }
    });
  } catch (error) {
    // Policy creation failed (most likely another DOMPurify script has
    // already run). Skip creating the policy, as this will only cause errors
    // if TT are enforced.
    console.warn('TrustedTypes policy ' + policyName + ' could not be created.');
    return null;
  }
};

function createDOMPurify() {
  var window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getGlobal();

  var DOMPurify = function DOMPurify(root) {
    return createDOMPurify(root);
  };

  /**
   * Version label, exposed for easier checks
   * if DOMPurify is up to date or not
   */
  DOMPurify.version = '2.0.8';

  /**
   * Array of elements that DOMPurify removed during sanitation.
   * Empty if nothing was removed.
   */
  DOMPurify.removed = [];

  if (!window || !window.document || window.document.nodeType !== 9) {
    // Not running in a browser, provide a factory function
    // so that you can pass your own Window
    DOMPurify.isSupported = false;

    return DOMPurify;
  }

  var originalDocument = window.document;
  var useDOMParser = false;
  var removeTitle = false;

  var document = window.document;
  var DocumentFragment = window.DocumentFragment,
      HTMLTemplateElement = window.HTMLTemplateElement,
      Node = window.Node,
      NodeFilter = window.NodeFilter,
      _window$NamedNodeMap = window.NamedNodeMap,
      NamedNodeMap = _window$NamedNodeMap === undefined ? window.NamedNodeMap || window.MozNamedAttrMap : _window$NamedNodeMap,
      Text = window.Text,
      Comment = window.Comment,
      DOMParser = window.DOMParser,
      trustedTypes = window.trustedTypes;

  // As per issue #47, the web-components registry is inherited by a
  // new document created via createHTMLDocument. As per the spec
  // (http://w3c.github.io/webcomponents/spec/custom/#creating-and-passing-registries)
  // a new empty registry is used when creating a template contents owner
  // document, so we use that as our parent document to ensure nothing
  // is inherited.

  if (typeof HTMLTemplateElement === 'function') {
    var template = document.createElement('template');
    if (template.content && template.content.ownerDocument) {
      document = template.content.ownerDocument;
    }
  }

  var trustedTypesPolicy = _createTrustedTypesPolicy(trustedTypes, originalDocument);
  var emptyHTML = trustedTypesPolicy ? trustedTypesPolicy.createHTML('') : '';

  var _document = document,
      implementation = _document.implementation,
      createNodeIterator = _document.createNodeIterator,
      getElementsByTagName = _document.getElementsByTagName,
      createDocumentFragment = _document.createDocumentFragment;
  var importNode = originalDocument.importNode;


  var hooks = {};

  /**
   * Expose whether this browser supports running the full DOMPurify.
   */
  DOMPurify.isSupported = implementation && typeof implementation.createHTMLDocument !== 'undefined' && document.documentMode !== 9;

  var MUSTACHE_EXPR$$1 = MUSTACHE_EXPR,
      ERB_EXPR$$1 = ERB_EXPR,
      DATA_ATTR$$1 = DATA_ATTR,
      ARIA_ATTR$$1 = ARIA_ATTR,
      IS_SCRIPT_OR_DATA$$1 = IS_SCRIPT_OR_DATA,
      ATTR_WHITESPACE$$1 = ATTR_WHITESPACE;
  var IS_ALLOWED_URI$$1 = IS_ALLOWED_URI;

  /**
   * We consider the elements and attributes below to be safe. Ideally
   * don't add any new ones but feel free to remove unwanted ones.
   */

  /* allowed element names */

  var ALLOWED_TAGS = null;
  var DEFAULT_ALLOWED_TAGS = addToSet({}, [].concat(_toConsumableArray(html), _toConsumableArray(svg), _toConsumableArray(svgFilters), _toConsumableArray(mathMl), _toConsumableArray(text)));

  /* Allowed attribute names */
  var ALLOWED_ATTR = null;
  var DEFAULT_ALLOWED_ATTR = addToSet({}, [].concat(_toConsumableArray(html$1), _toConsumableArray(svg$1), _toConsumableArray(mathMl$1), _toConsumableArray(xml)));

  /* Explicitly forbidden tags (overrides ALLOWED_TAGS/ADD_TAGS) */
  var FORBID_TAGS = null;

  /* Explicitly forbidden attributes (overrides ALLOWED_ATTR/ADD_ATTR) */
  var FORBID_ATTR = null;

  /* Decide if ARIA attributes are okay */
  var ALLOW_ARIA_ATTR = true;

  /* Decide if custom data attributes are okay */
  var ALLOW_DATA_ATTR = true;

  /* Decide if unknown protocols are okay */
  var ALLOW_UNKNOWN_PROTOCOLS = false;

  /* Output should be safe for jQuery's $() factory? */
  var SAFE_FOR_JQUERY = false;

  /* Output should be safe for common template engines.
   * This means, DOMPurify removes data attributes, mustaches and ERB
   */
  var SAFE_FOR_TEMPLATES = false;

  /* Decide if document with <html>... should be returned */
  var WHOLE_DOCUMENT = false;

  /* Track whether config is already set on this instance of DOMPurify. */
  var SET_CONFIG = false;

  /* Decide if all elements (e.g. style, script) must be children of
   * document.body. By default, browsers might move them to document.head */
  var FORCE_BODY = false;

  /* Decide if a DOM `HTMLBodyElement` should be returned, instead of a html
   * string (or a TrustedHTML object if Trusted Types are supported).
   * If `WHOLE_DOCUMENT` is enabled a `HTMLHtmlElement` will be returned instead
   */
  var RETURN_DOM = false;

  /* Decide if a DOM `DocumentFragment` should be returned, instead of a html
   * string  (or a TrustedHTML object if Trusted Types are supported) */
  var RETURN_DOM_FRAGMENT = false;

  /* If `RETURN_DOM` or `RETURN_DOM_FRAGMENT` is enabled, decide if the returned DOM
   * `Node` is imported into the current `Document`. If this flag is not enabled the
   * `Node` will belong (its ownerDocument) to a fresh `HTMLDocument`, created by
   * DOMPurify. */
  var RETURN_DOM_IMPORT = false;

  /* Try to return a Trusted Type object instead of a string, retrun a string in
   * case Trusted Types are not supported  */
  var RETURN_TRUSTED_TYPE = false;

  /* Output should be free from DOM clobbering attacks? */
  var SANITIZE_DOM = true;

  /* Keep element content when removing element? */
  var KEEP_CONTENT = true;

  /* If a `Node` is passed to sanitize(), then performs sanitization in-place instead
   * of importing it into a new Document and returning a sanitized copy */
  var IN_PLACE = false;

  /* Allow usage of profiles like html, svg and mathMl */
  var USE_PROFILES = {};

  /* Tags to ignore content of when KEEP_CONTENT is true */
  var FORBID_CONTENTS = addToSet({}, ['annotation-xml', 'audio', 'colgroup', 'desc', 'foreignobject', 'head', 'iframe', 'math', 'mi', 'mn', 'mo', 'ms', 'mtext', 'noembed', 'noframes', 'plaintext', 'script', 'style', 'svg', 'template', 'thead', 'title', 'video', 'xmp']);

  /* Tags that are safe for data: URIs */
  var DATA_URI_TAGS = addToSet({}, ['audio', 'video', 'img', 'source', 'image']);

  /* Attributes safe for values like "javascript:" */
  var URI_SAFE_ATTRIBUTES = null;
  var DEFAULT_URI_SAFE_ATTRIBUTES = addToSet({}, ['alt', 'class', 'for', 'id', 'label', 'name', 'pattern', 'placeholder', 'summary', 'title', 'value', 'style', 'xmlns']);

  /* Keep a reference to config to pass to hooks */
  var CONFIG = null;

  /* Ideally, do not touch anything below this line */
  /* ______________________________________________ */

  var formElement = document.createElement('form');

  /**
   * _parseConfig
   *
   * @param  {Object} cfg optional config literal
   */
  // eslint-disable-next-line complexity
  var _parseConfig = function _parseConfig(cfg) {
    if (CONFIG && CONFIG === cfg) {
      return;
    }

    /* Shield configuration object from tampering */
    if (!cfg || (typeof cfg === 'undefined' ? 'undefined' : _typeof(cfg)) !== 'object') {
      cfg = {};
    }

    /* Set configuration parameters */
    ALLOWED_TAGS = 'ALLOWED_TAGS' in cfg ? addToSet({}, cfg.ALLOWED_TAGS) : DEFAULT_ALLOWED_TAGS;
    ALLOWED_ATTR = 'ALLOWED_ATTR' in cfg ? addToSet({}, cfg.ALLOWED_ATTR) : DEFAULT_ALLOWED_ATTR;
    URI_SAFE_ATTRIBUTES = 'ADD_URI_SAFE_ATTR' in cfg ? addToSet(clone(DEFAULT_URI_SAFE_ATTRIBUTES), cfg.ADD_URI_SAFE_ATTR) : DEFAULT_URI_SAFE_ATTRIBUTES;
    FORBID_TAGS = 'FORBID_TAGS' in cfg ? addToSet({}, cfg.FORBID_TAGS) : {};
    FORBID_ATTR = 'FORBID_ATTR' in cfg ? addToSet({}, cfg.FORBID_ATTR) : {};
    USE_PROFILES = 'USE_PROFILES' in cfg ? cfg.USE_PROFILES : false;
    ALLOW_ARIA_ATTR = cfg.ALLOW_ARIA_ATTR !== false; // Default true
    ALLOW_DATA_ATTR = cfg.ALLOW_DATA_ATTR !== false; // Default true
    ALLOW_UNKNOWN_PROTOCOLS = cfg.ALLOW_UNKNOWN_PROTOCOLS || false; // Default false
    SAFE_FOR_JQUERY = cfg.SAFE_FOR_JQUERY || false; // Default false
    SAFE_FOR_TEMPLATES = cfg.SAFE_FOR_TEMPLATES || false; // Default false
    WHOLE_DOCUMENT = cfg.WHOLE_DOCUMENT || false; // Default false
    RETURN_DOM = cfg.RETURN_DOM || false; // Default false
    RETURN_DOM_FRAGMENT = cfg.RETURN_DOM_FRAGMENT || false; // Default false
    RETURN_DOM_IMPORT = cfg.RETURN_DOM_IMPORT || false; // Default false
    RETURN_TRUSTED_TYPE = cfg.RETURN_TRUSTED_TYPE || false; // Default false
    FORCE_BODY = cfg.FORCE_BODY || false; // Default false
    SANITIZE_DOM = cfg.SANITIZE_DOM !== false; // Default true
    KEEP_CONTENT = cfg.KEEP_CONTENT !== false; // Default true
    IN_PLACE = cfg.IN_PLACE || false; // Default false
    IS_ALLOWED_URI$$1 = cfg.ALLOWED_URI_REGEXP || IS_ALLOWED_URI$$1;
    if (SAFE_FOR_TEMPLATES) {
      ALLOW_DATA_ATTR = false;
    }

    if (RETURN_DOM_FRAGMENT) {
      RETURN_DOM = true;
    }

    /* Parse profile info */
    if (USE_PROFILES) {
      ALLOWED_TAGS = addToSet({}, [].concat(_toConsumableArray(text)));
      ALLOWED_ATTR = [];
      if (USE_PROFILES.html === true) {
        addToSet(ALLOWED_TAGS, html);
        addToSet(ALLOWED_ATTR, html$1);
      }

      if (USE_PROFILES.svg === true) {
        addToSet(ALLOWED_TAGS, svg);
        addToSet(ALLOWED_ATTR, svg$1);
        addToSet(ALLOWED_ATTR, xml);
      }

      if (USE_PROFILES.svgFilters === true) {
        addToSet(ALLOWED_TAGS, svgFilters);
        addToSet(ALLOWED_ATTR, svg$1);
        addToSet(ALLOWED_ATTR, xml);
      }

      if (USE_PROFILES.mathMl === true) {
        addToSet(ALLOWED_TAGS, mathMl);
        addToSet(ALLOWED_ATTR, mathMl$1);
        addToSet(ALLOWED_ATTR, xml);
      }
    }

    /* Merge configuration parameters */
    if (cfg.ADD_TAGS) {
      if (ALLOWED_TAGS === DEFAULT_ALLOWED_TAGS) {
        ALLOWED_TAGS = clone(ALLOWED_TAGS);
      }

      addToSet(ALLOWED_TAGS, cfg.ADD_TAGS);
    }

    if (cfg.ADD_ATTR) {
      if (ALLOWED_ATTR === DEFAULT_ALLOWED_ATTR) {
        ALLOWED_ATTR = clone(ALLOWED_ATTR);
      }

      addToSet(ALLOWED_ATTR, cfg.ADD_ATTR);
    }

    if (cfg.ADD_URI_SAFE_ATTR) {
      addToSet(URI_SAFE_ATTRIBUTES, cfg.ADD_URI_SAFE_ATTR);
    }

    /* Add #text in case KEEP_CONTENT is set to true */
    if (KEEP_CONTENT) {
      ALLOWED_TAGS['#text'] = true;
    }

    /* Add html, head and body to ALLOWED_TAGS in case WHOLE_DOCUMENT is true */
    if (WHOLE_DOCUMENT) {
      addToSet(ALLOWED_TAGS, ['html', 'head', 'body']);
    }

    /* Add tbody to ALLOWED_TAGS in case tables are permitted, see #286, #365 */
    if (ALLOWED_TAGS.table) {
      addToSet(ALLOWED_TAGS, ['tbody']);
      delete FORBID_TAGS.tbody;
    }

    // Prevent further manipulation of configuration.
    // Not available in IE8, Safari 5, etc.
    if (freeze) {
      freeze(cfg);
    }

    CONFIG = cfg;
  };

  /**
   * _forceRemove
   *
   * @param  {Node} node a DOM node
   */
  var _forceRemove = function _forceRemove(node) {
    arrayPush(DOMPurify.removed, { element: node });
    try {
      node.parentNode.removeChild(node);
    } catch (error) {
      node.outerHTML = emptyHTML;
    }
  };

  /**
   * _removeAttribute
   *
   * @param  {String} name an Attribute name
   * @param  {Node} node a DOM node
   */
  var _removeAttribute = function _removeAttribute(name, node) {
    try {
      arrayPush(DOMPurify.removed, {
        attribute: node.getAttributeNode(name),
        from: node
      });
    } catch (error) {
      arrayPush(DOMPurify.removed, {
        attribute: null,
        from: node
      });
    }

    node.removeAttribute(name);
  };

  /**
   * _initDocument
   *
   * @param  {String} dirty a string of dirty markup
   * @return {Document} a DOM, filled with the dirty markup
   */
  var _initDocument = function _initDocument(dirty) {
    /* Create a HTML document */
    var doc = void 0;
    var leadingWhitespace = void 0;

    if (FORCE_BODY) {
      dirty = '<remove></remove>' + dirty;
    } else {
      /* If FORCE_BODY isn't used, leading whitespace needs to be preserved manually */
      var matches = stringMatch(dirty, /^[\s]+/);
      leadingWhitespace = matches && matches[0];
    }

    var dirtyPayload = trustedTypesPolicy ? trustedTypesPolicy.createHTML(dirty) : dirty;
    /* Use DOMParser to workaround Firefox bug (see comment below) */
    if (useDOMParser) {
      try {
        doc = new DOMParser().parseFromString(dirtyPayload, 'text/html');
      } catch (error) {}
    }

    /* Remove title to fix a mXSS bug in older MS Edge */
    if (removeTitle) {
      addToSet(FORBID_TAGS, ['title']);
    }

    /* Otherwise use createHTMLDocument, because DOMParser is unsafe in
    Safari (see comment below) */
    if (!doc || !doc.documentElement) {
      doc = implementation.createHTMLDocument('');
      var _doc = doc,
          body = _doc.body;

      body.parentNode.removeChild(body.parentNode.firstElementChild);
      body.outerHTML = dirtyPayload;
    }

    if (dirty && leadingWhitespace) {
      doc.body.insertBefore(document.createTextNode(leadingWhitespace), doc.body.childNodes[0] || null);
    }

    /* Work on whole document or just its body */
    return getElementsByTagName.call(doc, WHOLE_DOCUMENT ? 'html' : 'body')[0];
  };

  // Firefox uses a different parser for innerHTML rather than
  // DOMParser (see https://bugzilla.mozilla.org/show_bug.cgi?id=1205631)
  // which means that you *must* use DOMParser, otherwise the output may
  // not be safe if used in a document.write context later.
  //
  // So we feature detect the Firefox bug and use the DOMParser if necessary.
  //
  // Chrome 77 and other versions ship an mXSS bug that caused a bypass to
  // happen. We now check for the mXSS trigger and react accordingly.
  if (DOMPurify.isSupported) {
    (function () {
      try {
        var doc = _initDocument('<svg><p><textarea><img src="</textarea><img src=x abc=1//">');
        if (doc.querySelector('svg img')) {
          useDOMParser = true;
        }
      } catch (error) {}
    })();

    (function () {
      try {
        var doc = _initDocument('<x/><title>&lt;/title&gt;&lt;img&gt;');
        if (regExpTest(/<\/title/, doc.querySelector('title').innerHTML)) {
          removeTitle = true;
        }
      } catch (error) {}
    })();
  }

  /**
   * _createIterator
   *
   * @param  {Document} root document/fragment to create iterator for
   * @return {Iterator} iterator instance
   */
  var _createIterator = function _createIterator(root) {
    return createNodeIterator.call(root.ownerDocument || root, root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_COMMENT | NodeFilter.SHOW_TEXT, function () {
      return NodeFilter.FILTER_ACCEPT;
    }, false);
  };

  /**
   * _isClobbered
   *
   * @param  {Node} elm element to check for clobbering attacks
   * @return {Boolean} true if clobbered, false if safe
   */
  var _isClobbered = function _isClobbered(elm) {
    if (elm instanceof Text || elm instanceof Comment) {
      return false;
    }

    if (typeof elm.nodeName !== 'string' || typeof elm.textContent !== 'string' || typeof elm.removeChild !== 'function' || !(elm.attributes instanceof NamedNodeMap) || typeof elm.removeAttribute !== 'function' || typeof elm.setAttribute !== 'function' || typeof elm.namespaceURI !== 'string') {
      return true;
    }

    return false;
  };

  /**
   * _isNode
   *
   * @param  {Node} obj object to check whether it's a DOM node
   * @return {Boolean} true is object is a DOM node
   */
  var _isNode = function _isNode(obj) {
    return (typeof Node === 'undefined' ? 'undefined' : _typeof(Node)) === 'object' ? obj instanceof Node : obj && (typeof obj === 'undefined' ? 'undefined' : _typeof(obj)) === 'object' && typeof obj.nodeType === 'number' && typeof obj.nodeName === 'string';
  };

  /**
   * _executeHook
   * Execute user configurable hooks
   *
   * @param  {String} entryPoint  Name of the hook's entry point
   * @param  {Node} currentNode node to work on with the hook
   * @param  {Object} data additional hook parameters
   */
  var _executeHook = function _executeHook(entryPoint, currentNode, data) {
    if (!hooks[entryPoint]) {
      return;
    }

    arrayForEach(hooks[entryPoint], function (hook) {
      hook.call(DOMPurify, currentNode, data, CONFIG);
    });
  };

  /**
   * _sanitizeElements
   *
   * @protect nodeName
   * @protect textContent
   * @protect removeChild
   *
   * @param   {Node} currentNode to check for permission to exist
   * @return  {Boolean} true if node was killed, false if left alive
   */
  // eslint-disable-next-line complexity
  var _sanitizeElements = function _sanitizeElements(currentNode) {
    var content = void 0;

    /* Execute a hook if present */
    _executeHook('beforeSanitizeElements', currentNode, null);

    /* Check if element is clobbered or can clobber */
    if (_isClobbered(currentNode)) {
      _forceRemove(currentNode);
      return true;
    }

    /* Now let's check the element's type and name */
    var tagName = stringToLowerCase(currentNode.nodeName);

    /* Execute a hook if present */
    _executeHook('uponSanitizeElement', currentNode, {
      tagName: tagName,
      allowedTags: ALLOWED_TAGS
    });

    /* Take care of an mXSS pattern using p, br inside svg, math */
    if ((tagName === 'svg' || tagName === 'math') && currentNode.querySelectorAll('p, br').length !== 0) {
      _forceRemove(currentNode);
      return true;
    }

    /* Remove element if anything forbids its presence */
    if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
      /* Keep content except for black-listed elements */
      if (KEEP_CONTENT && !FORBID_CONTENTS[tagName] && typeof currentNode.insertAdjacentHTML === 'function') {
        try {
          var htmlToInsert = currentNode.innerHTML;
          currentNode.insertAdjacentHTML('AfterEnd', trustedTypesPolicy ? trustedTypesPolicy.createHTML(htmlToInsert) : htmlToInsert);
        } catch (error) {}
      }

      _forceRemove(currentNode);
      return true;
    }

    /* Remove in case a noscript/noembed XSS is suspected */
    if (tagName === 'noscript' && regExpTest(/<\/noscript/i, currentNode.innerHTML)) {
      _forceRemove(currentNode);
      return true;
    }

    if (tagName === 'noembed' && regExpTest(/<\/noembed/i, currentNode.innerHTML)) {
      _forceRemove(currentNode);
      return true;
    }

    /* Convert markup to cover jQuery behavior */
    if (SAFE_FOR_JQUERY && !currentNode.firstElementChild && (!currentNode.content || !currentNode.content.firstElementChild) && regExpTest(/</g, currentNode.textContent)) {
      arrayPush(DOMPurify.removed, { element: currentNode.cloneNode() });
      if (currentNode.innerHTML) {
        currentNode.innerHTML = stringReplace(currentNode.innerHTML, /</g, '&lt;');
      } else {
        currentNode.innerHTML = stringReplace(currentNode.textContent, /</g, '&lt;');
      }
    }

    /* Sanitize element content to be template-safe */
    if (SAFE_FOR_TEMPLATES && currentNode.nodeType === 3) {
      /* Get the element's text content */
      content = currentNode.textContent;
      content = stringReplace(content, MUSTACHE_EXPR$$1, ' ');
      content = stringReplace(content, ERB_EXPR$$1, ' ');
      if (currentNode.textContent !== content) {
        arrayPush(DOMPurify.removed, { element: currentNode.cloneNode() });
        currentNode.textContent = content;
      }
    }

    /* Execute a hook if present */
    _executeHook('afterSanitizeElements', currentNode, null);

    return false;
  };

  /**
   * _isValidAttribute
   *
   * @param  {string} lcTag Lowercase tag name of containing element.
   * @param  {string} lcName Lowercase attribute name.
   * @param  {string} value Attribute value.
   * @return {Boolean} Returns true if `value` is valid, otherwise false.
   */
  // eslint-disable-next-line complexity
  var _isValidAttribute = function _isValidAttribute(lcTag, lcName, value) {
    /* Make sure attribute cannot clobber */
    if (SANITIZE_DOM && (lcName === 'id' || lcName === 'name') && (value in document || value in formElement)) {
      return false;
    }

    /* Allow valid data-* attributes: At least one character after "-"
        (https://html.spec.whatwg.org/multipage/dom.html#embedding-custom-non-visible-data-with-the-data-*-attributes)
        XML-compatible (https://html.spec.whatwg.org/multipage/infrastructure.html#xml-compatible and http://www.w3.org/TR/xml/#d0e804)
        We don't need to check the value; it's always URI safe. */
    if (ALLOW_DATA_ATTR && regExpTest(DATA_ATTR$$1, lcName)) {
      // This attribute is safe
    } else if (ALLOW_ARIA_ATTR && regExpTest(ARIA_ATTR$$1, lcName)) {
      // This attribute is safe
      /* Otherwise, check the name is permitted */
    } else if (!ALLOWED_ATTR[lcName] || FORBID_ATTR[lcName]) {
      return false;

      /* Check value is safe. First, is attr inert? If so, is safe */
    } else if (URI_SAFE_ATTRIBUTES[lcName]) {
      // This attribute is safe
      /* Check no script, data or unknown possibly unsafe URI
        unless we know URI values are safe for that attribute */
    } else if (regExpTest(IS_ALLOWED_URI$$1, stringReplace(value, ATTR_WHITESPACE$$1, ''))) {
      // This attribute is safe
      /* Keep image data URIs alive if src/xlink:href is allowed */
      /* Further prevent gadget XSS for dynamically built script tags */
    } else if ((lcName === 'src' || lcName === 'xlink:href' || lcName === 'href') && lcTag !== 'script' && stringIndexOf(value, 'data:') === 0 && DATA_URI_TAGS[lcTag]) {
      // This attribute is safe
      /* Allow unknown protocols: This provides support for links that
        are handled by protocol handlers which may be unknown ahead of
        time, e.g. fb:, spotify: */
    } else if (ALLOW_UNKNOWN_PROTOCOLS && !regExpTest(IS_SCRIPT_OR_DATA$$1, stringReplace(value, ATTR_WHITESPACE$$1, ''))) {
      // This attribute is safe
      /* Check for binary attributes */
      // eslint-disable-next-line no-negated-condition
    } else if (!value) {
      // Binary attributes are safe at this point
      /* Anything else, presume unsafe, do not add it back */
    } else {
      return false;
    }

    return true;
  };

  /**
   * _sanitizeAttributes
   *
   * @protect attributes
   * @protect nodeName
   * @protect removeAttribute
   * @protect setAttribute
   *
   * @param  {Node} currentNode to sanitize
   */
  // eslint-disable-next-line complexity
  var _sanitizeAttributes = function _sanitizeAttributes(currentNode) {
    var attr = void 0;
    var value = void 0;
    var lcName = void 0;
    var idAttr = void 0;
    var l = void 0;
    /* Execute a hook if present */
    _executeHook('beforeSanitizeAttributes', currentNode, null);

    var attributes = currentNode.attributes;

    /* Check if we have attributes; if not we might have a text node */

    if (!attributes) {
      return;
    }

    var hookEvent = {
      attrName: '',
      attrValue: '',
      keepAttr: true,
      allowedAttributes: ALLOWED_ATTR
    };
    l = attributes.length;

    /* Go backwards over all attributes; safely remove bad ones */
    while (l--) {
      attr = attributes[l];
      var _attr = attr,
          name = _attr.name,
          namespaceURI = _attr.namespaceURI;

      value = stringTrim(attr.value);
      lcName = stringToLowerCase(name);

      /* Execute a hook if present */
      hookEvent.attrName = lcName;
      hookEvent.attrValue = value;
      hookEvent.keepAttr = true;
      hookEvent.forceKeepAttr = undefined; // Allows developers to see this is a property they can set
      _executeHook('uponSanitizeAttribute', currentNode, hookEvent);
      value = hookEvent.attrValue;
      /* Did the hooks approve of the attribute? */
      if (hookEvent.forceKeepAttr) {
        continue;
      }

      /* Remove attribute */
      // Safari (iOS + Mac), last tested v8.0.5, crashes if you try to
      // remove a "name" attribute from an <img> tag that has an "id"
      // attribute at the time.
      if (lcName === 'name' && currentNode.nodeName === 'IMG' && attributes.id) {
        idAttr = attributes.id;
        attributes = arraySlice(attributes, []);
        _removeAttribute('id', currentNode);
        _removeAttribute(name, currentNode);
        if (arrayIndexOf(attributes, idAttr) > l) {
          currentNode.setAttribute('id', idAttr.value);
        }
      } else if (
      // This works around a bug in Safari, where input[type=file]
      // cannot be dynamically set after type has been removed
      currentNode.nodeName === 'INPUT' && lcName === 'type' && value === 'file' && hookEvent.keepAttr && (ALLOWED_ATTR[lcName] || !FORBID_ATTR[lcName])) {
        continue;
      } else {
        // This avoids a crash in Safari v9.0 with double-ids.
        // The trick is to first set the id to be empty and then to
        // remove the attribute
        if (name === 'id') {
          currentNode.setAttribute(name, '');
        }

        _removeAttribute(name, currentNode);
      }

      /* Did the hooks approve of the attribute? */
      if (!hookEvent.keepAttr) {
        continue;
      }

      /* Work around a security issue in jQuery 3.0 */
      if (SAFE_FOR_JQUERY && regExpTest(/\/>/i, value)) {
        _removeAttribute(name, currentNode);
        continue;
      }

      /* Take care of an mXSS pattern using namespace switches */
      if (regExpTest(/svg|math/i, currentNode.namespaceURI) && regExpTest(regExpCreate('</(' + arrayJoin(objectKeys(FORBID_CONTENTS), '|') + ')', 'i'), value)) {
        _removeAttribute(name, currentNode);
        continue;
      }

      /* Sanitize attribute content to be template-safe */
      if (SAFE_FOR_TEMPLATES) {
        value = stringReplace(value, MUSTACHE_EXPR$$1, ' ');
        value = stringReplace(value, ERB_EXPR$$1, ' ');
      }

      /* Is `value` valid for this attribute? */
      var lcTag = currentNode.nodeName.toLowerCase();
      if (!_isValidAttribute(lcTag, lcName, value)) {
        continue;
      }

      /* Handle invalid data-* attribute set by try-catching it */
      try {
        if (namespaceURI) {
          currentNode.setAttributeNS(namespaceURI, name, value);
        } else {
          /* Fallback to setAttribute() for browser-unrecognized namespaces e.g. "x-schema". */
          currentNode.setAttribute(name, value);
        }

        arrayPop(DOMPurify.removed);
      } catch (error) {}
    }

    /* Execute a hook if present */
    _executeHook('afterSanitizeAttributes', currentNode, null);
  };

  /**
   * _sanitizeShadowDOM
   *
   * @param  {DocumentFragment} fragment to iterate over recursively
   */
  var _sanitizeShadowDOM = function _sanitizeShadowDOM(fragment) {
    var shadowNode = void 0;
    var shadowIterator = _createIterator(fragment);

    /* Execute a hook if present */
    _executeHook('beforeSanitizeShadowDOM', fragment, null);

    while (shadowNode = shadowIterator.nextNode()) {
      /* Execute a hook if present */
      _executeHook('uponSanitizeShadowNode', shadowNode, null);

      /* Sanitize tags and elements */
      if (_sanitizeElements(shadowNode)) {
        continue;
      }

      /* Deep shadow DOM detected */
      if (shadowNode.content instanceof DocumentFragment) {
        _sanitizeShadowDOM(shadowNode.content);
      }

      /* Check attributes, sanitize if necessary */
      _sanitizeAttributes(shadowNode);
    }

    /* Execute a hook if present */
    _executeHook('afterSanitizeShadowDOM', fragment, null);
  };

  /**
   * Sanitize
   * Public method providing core sanitation functionality
   *
   * @param {String|Node} dirty string or DOM node
   * @param {Object} configuration object
   */
  // eslint-disable-next-line complexity
  DOMPurify.sanitize = function (dirty, cfg) {
    var body = void 0;
    var importedNode = void 0;
    var currentNode = void 0;
    var oldNode = void 0;
    var returnNode = void 0;
    /* Make sure we have a string to sanitize.
      DO NOT return early, as this will return the wrong type if
      the user has requested a DOM object rather than a string */
    if (!dirty) {
      dirty = '<!-->';
    }

    /* Stringify, in case dirty is an object */
    if (typeof dirty !== 'string' && !_isNode(dirty)) {
      // eslint-disable-next-line no-negated-condition
      if (typeof dirty.toString !== 'function') {
        throw typeErrorCreate('toString is not a function');
      } else {
        dirty = dirty.toString();
        if (typeof dirty !== 'string') {
          throw typeErrorCreate('dirty is not a string, aborting');
        }
      }
    }

    /* Check we can run. Otherwise fall back or ignore */
    if (!DOMPurify.isSupported) {
      if (_typeof(window.toStaticHTML) === 'object' || typeof window.toStaticHTML === 'function') {
        if (typeof dirty === 'string') {
          return window.toStaticHTML(dirty);
        }

        if (_isNode(dirty)) {
          return window.toStaticHTML(dirty.outerHTML);
        }
      }

      return dirty;
    }

    /* Assign config vars */
    if (!SET_CONFIG) {
      _parseConfig(cfg);
    }

    /* Clean up removed elements */
    DOMPurify.removed = [];

    /* Check if dirty is correctly typed for IN_PLACE */
    if (typeof dirty === 'string') {
      IN_PLACE = false;
    }

    if (IN_PLACE) {
      /* No special handling necessary for in-place sanitization */
    } else if (dirty instanceof Node) {
      /* If dirty is a DOM element, append to an empty document to avoid
         elements being stripped by the parser */
      body = _initDocument('<!-->');
      importedNode = body.ownerDocument.importNode(dirty, true);
      if (importedNode.nodeType === 1 && importedNode.nodeName === 'BODY') {
        /* Node is already a body, use as is */
        body = importedNode;
      } else if (importedNode.nodeName === 'HTML') {
        body = importedNode;
      } else {
        // eslint-disable-next-line unicorn/prefer-node-append
        body.appendChild(importedNode);
      }
    } else {
      /* Exit directly if we have nothing to do */
      if (!RETURN_DOM && !SAFE_FOR_TEMPLATES && !WHOLE_DOCUMENT && RETURN_TRUSTED_TYPE && dirty.indexOf('<') === -1) {
        return trustedTypesPolicy ? trustedTypesPolicy.createHTML(dirty) : dirty;
      }

      /* Initialize the document to work on */
      body = _initDocument(dirty);

      /* Check we have a DOM node from the data */
      if (!body) {
        return RETURN_DOM ? null : emptyHTML;
      }
    }

    /* Remove first element node (ours) if FORCE_BODY is set */
    if (body && FORCE_BODY) {
      _forceRemove(body.firstChild);
    }

    /* Get node iterator */
    var nodeIterator = _createIterator(IN_PLACE ? dirty : body);

    /* Now start iterating over the created document */
    while (currentNode = nodeIterator.nextNode()) {
      /* Fix IE's strange behavior with manipulated textNodes #89 */
      if (currentNode.nodeType === 3 && currentNode === oldNode) {
        continue;
      }

      /* Sanitize tags and elements */
      if (_sanitizeElements(currentNode)) {
        continue;
      }

      /* Shadow DOM detected, sanitize it */
      if (currentNode.content instanceof DocumentFragment) {
        _sanitizeShadowDOM(currentNode.content);
      }

      /* Check attributes, sanitize if necessary */
      _sanitizeAttributes(currentNode);

      oldNode = currentNode;
    }

    oldNode = null;

    /* If we sanitized `dirty` in-place, return it. */
    if (IN_PLACE) {
      return dirty;
    }

    /* Return sanitized string or DOM */
    if (RETURN_DOM) {
      if (RETURN_DOM_FRAGMENT) {
        returnNode = createDocumentFragment.call(body.ownerDocument);

        while (body.firstChild) {
          // eslint-disable-next-line unicorn/prefer-node-append
          returnNode.appendChild(body.firstChild);
        }
      } else {
        returnNode = body;
      }

      if (RETURN_DOM_IMPORT) {
        /* AdoptNode() is not used because internal state is not reset
               (e.g. the past names map of a HTMLFormElement), this is safe
               in theory but we would rather not risk another attack vector.
               The state that is cloned by importNode() is explicitly defined
               by the specs. */
        returnNode = importNode.call(originalDocument, returnNode, true);
      }

      return returnNode;
    }

    var serializedHTML = WHOLE_DOCUMENT ? body.outerHTML : body.innerHTML;

    /* Sanitize final string template-safe */
    if (SAFE_FOR_TEMPLATES) {
      serializedHTML = stringReplace(serializedHTML, MUSTACHE_EXPR$$1, ' ');
      serializedHTML = stringReplace(serializedHTML, ERB_EXPR$$1, ' ');
    }

    return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(serializedHTML) : serializedHTML;
  };

  /**
   * Public method to set the configuration once
   * setConfig
   *
   * @param {Object} cfg configuration object
   */
  DOMPurify.setConfig = function (cfg) {
    _parseConfig(cfg);
    SET_CONFIG = true;
  };

  /**
   * Public method to remove the configuration
   * clearConfig
   *
   */
  DOMPurify.clearConfig = function () {
    CONFIG = null;
    SET_CONFIG = false;
  };

  /**
   * Public method to check if an attribute value is valid.
   * Uses last set config, if any. Otherwise, uses config defaults.
   * isValidAttribute
   *
   * @param  {string} tag Tag name of containing element.
   * @param  {string} attr Attribute name.
   * @param  {string} value Attribute value.
   * @return {Boolean} Returns true if `value` is valid. Otherwise, returns false.
   */
  DOMPurify.isValidAttribute = function (tag, attr, value) {
    /* Initialize shared config vars if necessary. */
    if (!CONFIG) {
      _parseConfig({});
    }

    var lcTag = stringToLowerCase(tag);
    var lcName = stringToLowerCase(attr);
    return _isValidAttribute(lcTag, lcName, value);
  };

  /**
   * AddHook
   * Public method to add DOMPurify hooks
   *
   * @param {String} entryPoint entry point for the hook to add
   * @param {Function} hookFunction function to execute
   */
  DOMPurify.addHook = function (entryPoint, hookFunction) {
    if (typeof hookFunction !== 'function') {
      return;
    }

    hooks[entryPoint] = hooks[entryPoint] || [];
    arrayPush(hooks[entryPoint], hookFunction);
  };

  /**
   * RemoveHook
   * Public method to remove a DOMPurify hook at a given entryPoint
   * (pops it from the stack of hooks if more are present)
   *
   * @param {String} entryPoint entry point for the hook to remove
   */
  DOMPurify.removeHook = function (entryPoint) {
    if (hooks[entryPoint]) {
      arrayPop(hooks[entryPoint]);
    }
  };

  /**
   * RemoveHooks
   * Public method to remove all DOMPurify hooks at a given entryPoint
   *
   * @param  {String} entryPoint entry point for the hooks to remove
   */
  DOMPurify.removeHooks = function (entryPoint) {
    if (hooks[entryPoint]) {
      hooks[entryPoint] = [];
    }
  };

  /**
   * RemoveAllHooks
   * Public method to remove all DOMPurify hooks
   *
   */
  DOMPurify.removeAllHooks = function () {
    hooks = {};
  };

  return DOMPurify;
}

var purify = createDOMPurify();

return purify;

})));
//# sourceMappingURL=purify.js.map


/***/ }),

/***/ "./node_modules/marked/src/InlineLexer.js":
/*!************************************************!*\
  !*** ./node_modules/marked/src/InlineLexer.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

const Renderer = __webpack_require__(/*! ./Renderer.js */ "./node_modules/marked/src/Renderer.js");
const { defaults } = __webpack_require__(/*! ./defaults.js */ "./node_modules/marked/src/defaults.js");
const { inline } = __webpack_require__(/*! ./rules.js */ "./node_modules/marked/src/rules.js");
const {
  findClosingBracket,
  escape
} = __webpack_require__(/*! ./helpers.js */ "./node_modules/marked/src/helpers.js");

/**
 * Inline Lexer & Compiler
 */
module.exports = class InlineLexer {
  constructor(links, options) {
    this.options = options || defaults;
    this.links = links;
    this.rules = inline.normal;
    this.options.renderer = this.options.renderer || new Renderer();
    this.renderer = this.options.renderer;
    this.renderer.options = this.options;

    if (!this.links) {
      throw new Error('Tokens array requires a `links` property.');
    }

    if (this.options.pedantic) {
      this.rules = inline.pedantic;
    } else if (this.options.gfm) {
      if (this.options.breaks) {
        this.rules = inline.breaks;
      } else {
        this.rules = inline.gfm;
      }
    }
  }

  /**
   * Expose Inline Rules
   */
  static get rules() {
    return inline;
  }

  /**
   * Static Lexing/Compiling Method
   */
  static output(src, links, options) {
    const inline = new InlineLexer(links, options);
    return inline.output(src);
  }

  /**
   * Lexing/Compiling
   */
  output(src) {
    let out = '',
      link,
      text,
      href,
      title,
      cap,
      prevCapZero;

    while (src) {
      // escape
      if (cap = this.rules.escape.exec(src)) {
        src = src.substring(cap[0].length);
        out += escape(cap[1]);
        continue;
      }

      // tag
      if (cap = this.rules.tag.exec(src)) {
        if (!this.inLink && /^<a /i.test(cap[0])) {
          this.inLink = true;
        } else if (this.inLink && /^<\/a>/i.test(cap[0])) {
          this.inLink = false;
        }
        if (!this.inRawBlock && /^<(pre|code|kbd|script)(\s|>)/i.test(cap[0])) {
          this.inRawBlock = true;
        } else if (this.inRawBlock && /^<\/(pre|code|kbd|script)(\s|>)/i.test(cap[0])) {
          this.inRawBlock = false;
        }

        src = src.substring(cap[0].length);
        out += this.renderer.html(this.options.sanitize
          ? (this.options.sanitizer
            ? this.options.sanitizer(cap[0])
            : escape(cap[0]))
          : cap[0]);
        continue;
      }

      // link
      if (cap = this.rules.link.exec(src)) {
        const lastParenIndex = findClosingBracket(cap[2], '()');
        if (lastParenIndex > -1) {
          const start = cap[0].indexOf('!') === 0 ? 5 : 4;
          const linkLen = start + cap[1].length + lastParenIndex;
          cap[2] = cap[2].substring(0, lastParenIndex);
          cap[0] = cap[0].substring(0, linkLen).trim();
          cap[3] = '';
        }
        src = src.substring(cap[0].length);
        this.inLink = true;
        href = cap[2];
        if (this.options.pedantic) {
          link = /^([^'"]*[^\s])\s+(['"])(.*)\2/.exec(href);

          if (link) {
            href = link[1];
            title = link[3];
          } else {
            title = '';
          }
        } else {
          title = cap[3] ? cap[3].slice(1, -1) : '';
        }
        href = href.trim().replace(/^<([\s\S]*)>$/, '$1');
        out += this.outputLink(cap, {
          href: InlineLexer.escapes(href),
          title: InlineLexer.escapes(title)
        });
        this.inLink = false;
        continue;
      }

      // reflink, nolink
      if ((cap = this.rules.reflink.exec(src))
          || (cap = this.rules.nolink.exec(src))) {
        src = src.substring(cap[0].length);
        link = (cap[2] || cap[1]).replace(/\s+/g, ' ');
        link = this.links[link.toLowerCase()];
        if (!link || !link.href) {
          out += cap[0].charAt(0);
          src = cap[0].substring(1) + src;
          continue;
        }
        this.inLink = true;
        out += this.outputLink(cap, link);
        this.inLink = false;
        continue;
      }

      // strong
      if (cap = this.rules.strong.exec(src)) {
        src = src.substring(cap[0].length);
        out += this.renderer.strong(this.output(cap[4] || cap[3] || cap[2] || cap[1]));
        continue;
      }

      // em
      if (cap = this.rules.em.exec(src)) {
        src = src.substring(cap[0].length);
        out += this.renderer.em(this.output(cap[6] || cap[5] || cap[4] || cap[3] || cap[2] || cap[1]));
        continue;
      }

      // code
      if (cap = this.rules.code.exec(src)) {
        src = src.substring(cap[0].length);
        out += this.renderer.codespan(escape(cap[2].trim(), true));
        continue;
      }

      // br
      if (cap = this.rules.br.exec(src)) {
        src = src.substring(cap[0].length);
        out += this.renderer.br();
        continue;
      }

      // del (gfm)
      if (cap = this.rules.del.exec(src)) {
        src = src.substring(cap[0].length);
        out += this.renderer.del(this.output(cap[1]));
        continue;
      }

      // autolink
      if (cap = this.rules.autolink.exec(src)) {
        src = src.substring(cap[0].length);
        if (cap[2] === '@') {
          text = escape(this.mangle(cap[1]));
          href = 'mailto:' + text;
        } else {
          text = escape(cap[1]);
          href = text;
        }
        out += this.renderer.link(href, null, text);
        continue;
      }

      // url (gfm)
      if (!this.inLink && (cap = this.rules.url.exec(src))) {
        if (cap[2] === '@') {
          text = escape(cap[0]);
          href = 'mailto:' + text;
        } else {
          // do extended autolink path validation
          do {
            prevCapZero = cap[0];
            cap[0] = this.rules._backpedal.exec(cap[0])[0];
          } while (prevCapZero !== cap[0]);
          text = escape(cap[0]);
          if (cap[1] === 'www.') {
            href = 'http://' + text;
          } else {
            href = text;
          }
        }
        src = src.substring(cap[0].length);
        out += this.renderer.link(href, null, text);
        continue;
      }

      // text
      if (cap = this.rules.text.exec(src)) {
        src = src.substring(cap[0].length);
        if (this.inRawBlock) {
          out += this.renderer.text(this.options.sanitize ? (this.options.sanitizer ? this.options.sanitizer(cap[0]) : escape(cap[0])) : cap[0]);
        } else {
          out += this.renderer.text(escape(this.smartypants(cap[0])));
        }
        continue;
      }

      if (src) {
        throw new Error('Infinite loop on byte: ' + src.charCodeAt(0));
      }
    }

    return out;
  }

  static escapes(text) {
    return text ? text.replace(InlineLexer.rules._escapes, '$1') : text;
  }

  /**
   * Compile Link
   */
  outputLink(cap, link) {
    const href = link.href,
      title = link.title ? escape(link.title) : null;

    return cap[0].charAt(0) !== '!'
      ? this.renderer.link(href, title, this.output(cap[1]))
      : this.renderer.image(href, title, escape(cap[1]));
  }

  /**
   * Smartypants Transformations
   */
  smartypants(text) {
    if (!this.options.smartypants) return text;
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
   * Mangle Links
   */
  mangle(text) {
    if (!this.options.mangle) return text;
    const l = text.length;
    let out = '',
      i = 0,
      ch;

    for (; i < l; i++) {
      ch = text.charCodeAt(i);
      if (Math.random() > 0.5) {
        ch = 'x' + ch.toString(16);
      }
      out += '&#' + ch + ';';
    }

    return out;
  }
};


/***/ }),

/***/ "./node_modules/marked/src/Lexer.js":
/*!******************************************!*\
  !*** ./node_modules/marked/src/Lexer.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

const { defaults } = __webpack_require__(/*! ./defaults.js */ "./node_modules/marked/src/defaults.js");
const { block } = __webpack_require__(/*! ./rules.js */ "./node_modules/marked/src/rules.js");
const {
  rtrim,
  splitCells,
  escape
} = __webpack_require__(/*! ./helpers.js */ "./node_modules/marked/src/helpers.js");

/**
 * Block Lexer
 */
module.exports = class Lexer {
  constructor(options) {
    this.tokens = [];
    this.tokens.links = Object.create(null);
    this.options = options || defaults;
    this.rules = block.normal;

    if (this.options.pedantic) {
      this.rules = block.pedantic;
    } else if (this.options.gfm) {
      this.rules = block.gfm;
    }
  }

  /**
   * Expose Block Rules
   */
  static get rules() {
    return block;
  }

  /**
   * Static Lex Method
   */
  static lex(src, options) {
    const lexer = new Lexer(options);
    return lexer.lex(src);
  };

  /**
   * Preprocessing
   */
  lex(src) {
    src = src
      .replace(/\r\n|\r/g, '\n')
      .replace(/\t/g, '    ');

    return this.token(src, true);
  };

  /**
   * Lexing
   */
  token(src, top) {
    src = src.replace(/^ +$/gm, '');
    let next,
      loose,
      cap,
      bull,
      b,
      item,
      listStart,
      listItems,
      t,
      space,
      i,
      tag,
      l,
      isordered,
      istask,
      ischecked;

    while (src) {
      // newline
      if (cap = this.rules.newline.exec(src)) {
        src = src.substring(cap[0].length);
        if (cap[0].length > 1) {
          this.tokens.push({
            type: 'space'
          });
        }
      }

      // code
      if (cap = this.rules.code.exec(src)) {
        const lastToken = this.tokens[this.tokens.length - 1];
        src = src.substring(cap[0].length);
        // An indented code block cannot interrupt a paragraph.
        if (lastToken && lastToken.type === 'paragraph') {
          lastToken.text += '\n' + cap[0].trimRight();
        } else {
          cap = cap[0].replace(/^ {4}/gm, '');
          this.tokens.push({
            type: 'code',
            codeBlockStyle: 'indented',
            text: !this.options.pedantic
              ? rtrim(cap, '\n')
              : cap
          });
        }
        continue;
      }

      // fences
      if (cap = this.rules.fences.exec(src)) {
        src = src.substring(cap[0].length);
        this.tokens.push({
          type: 'code',
          lang: cap[2] ? cap[2].trim() : cap[2],
          text: cap[3] || ''
        });
        continue;
      }

      // heading
      if (cap = this.rules.heading.exec(src)) {
        src = src.substring(cap[0].length);
        this.tokens.push({
          type: 'heading',
          depth: cap[1].length,
          text: cap[2]
        });
        continue;
      }

      // table no leading pipe (gfm)
      if (cap = this.rules.nptable.exec(src)) {
        item = {
          type: 'table',
          header: splitCells(cap[1].replace(/^ *| *\| *$/g, '')),
          align: cap[2].replace(/^ *|\| *$/g, '').split(/ *\| */),
          cells: cap[3] ? cap[3].replace(/\n$/, '').split('\n') : []
        };

        if (item.header.length === item.align.length) {
          src = src.substring(cap[0].length);

          for (i = 0; i < item.align.length; i++) {
            if (/^ *-+: *$/.test(item.align[i])) {
              item.align[i] = 'right';
            } else if (/^ *:-+: *$/.test(item.align[i])) {
              item.align[i] = 'center';
            } else if (/^ *:-+ *$/.test(item.align[i])) {
              item.align[i] = 'left';
            } else {
              item.align[i] = null;
            }
          }

          for (i = 0; i < item.cells.length; i++) {
            item.cells[i] = splitCells(item.cells[i], item.header.length);
          }

          this.tokens.push(item);

          continue;
        }
      }

      // hr
      if (cap = this.rules.hr.exec(src)) {
        src = src.substring(cap[0].length);
        this.tokens.push({
          type: 'hr'
        });
        continue;
      }

      // blockquote
      if (cap = this.rules.blockquote.exec(src)) {
        src = src.substring(cap[0].length);

        this.tokens.push({
          type: 'blockquote_start'
        });

        cap = cap[0].replace(/^ *> ?/gm, '');

        // Pass `top` to keep the current
        // "toplevel" state. This is exactly
        // how markdown.pl works.
        this.token(cap, top);

        this.tokens.push({
          type: 'blockquote_end'
        });

        continue;
      }

      // list
      if (cap = this.rules.list.exec(src)) {
        src = src.substring(cap[0].length);
        bull = cap[2];
        isordered = bull.length > 1;

        listStart = {
          type: 'list_start',
          ordered: isordered,
          start: isordered ? +bull : '',
          loose: false
        };

        this.tokens.push(listStart);

        // Get each top-level item.
        cap = cap[0].match(this.rules.item);

        listItems = [];
        next = false;
        l = cap.length;
        i = 0;

        for (; i < l; i++) {
          item = cap[i];

          // Remove the list item's bullet
          // so it is seen as the next token.
          space = item.length;
          item = item.replace(/^ *([*+-]|\d+\.) */, '');

          // Outdent whatever the
          // list item contains. Hacky.
          if (~item.indexOf('\n ')) {
            space -= item.length;
            item = !this.options.pedantic
              ? item.replace(new RegExp('^ {1,' + space + '}', 'gm'), '')
              : item.replace(/^ {1,4}/gm, '');
          }

          // Determine whether the next list item belongs here.
          // Backpedal if it does not belong in this list.
          if (i !== l - 1) {
            b = block.bullet.exec(cap[i + 1])[0];
            if (bull.length > 1 ? b.length === 1
              : (b.length > 1 || (this.options.smartLists && b !== bull))) {
              src = cap.slice(i + 1).join('\n') + src;
              i = l - 1;
            }
          }

          // Determine whether item is loose or not.
          // Use: /(^|\n)(?! )[^\n]+\n\n(?!\s*$)/
          // for discount behavior.
          loose = next || /\n\n(?!\s*$)/.test(item);
          if (i !== l - 1) {
            next = item.charAt(item.length - 1) === '\n';
            if (!loose) loose = next;
          }

          if (loose) {
            listStart.loose = true;
          }

          // Check for task list items
          istask = /^\[[ xX]\] /.test(item);
          ischecked = undefined;
          if (istask) {
            ischecked = item[1] !== ' ';
            item = item.replace(/^\[[ xX]\] +/, '');
          }

          t = {
            type: 'list_item_start',
            task: istask,
            checked: ischecked,
            loose: loose
          };

          listItems.push(t);
          this.tokens.push(t);

          // Recurse.
          this.token(item, false);

          this.tokens.push({
            type: 'list_item_end'
          });
        }

        if (listStart.loose) {
          l = listItems.length;
          i = 0;
          for (; i < l; i++) {
            listItems[i].loose = true;
          }
        }

        this.tokens.push({
          type: 'list_end'
        });

        continue;
      }

      // html
      if (cap = this.rules.html.exec(src)) {
        src = src.substring(cap[0].length);
        this.tokens.push({
          type: this.options.sanitize
            ? 'paragraph'
            : 'html',
          pre: !this.options.sanitizer
            && (cap[1] === 'pre' || cap[1] === 'script' || cap[1] === 'style'),
          text: this.options.sanitize ? (this.options.sanitizer ? this.options.sanitizer(cap[0]) : escape(cap[0])) : cap[0]
        });
        continue;
      }

      // def
      if (top && (cap = this.rules.def.exec(src))) {
        src = src.substring(cap[0].length);
        if (cap[3]) cap[3] = cap[3].substring(1, cap[3].length - 1);
        tag = cap[1].toLowerCase().replace(/\s+/g, ' ');
        if (!this.tokens.links[tag]) {
          this.tokens.links[tag] = {
            href: cap[2],
            title: cap[3]
          };
        }
        continue;
      }

      // table (gfm)
      if (cap = this.rules.table.exec(src)) {
        item = {
          type: 'table',
          header: splitCells(cap[1].replace(/^ *| *\| *$/g, '')),
          align: cap[2].replace(/^ *|\| *$/g, '').split(/ *\| */),
          cells: cap[3] ? cap[3].replace(/\n$/, '').split('\n') : []
        };

        if (item.header.length === item.align.length) {
          src = src.substring(cap[0].length);

          for (i = 0; i < item.align.length; i++) {
            if (/^ *-+: *$/.test(item.align[i])) {
              item.align[i] = 'right';
            } else if (/^ *:-+: *$/.test(item.align[i])) {
              item.align[i] = 'center';
            } else if (/^ *:-+ *$/.test(item.align[i])) {
              item.align[i] = 'left';
            } else {
              item.align[i] = null;
            }
          }

          for (i = 0; i < item.cells.length; i++) {
            item.cells[i] = splitCells(
              item.cells[i].replace(/^ *\| *| *\| *$/g, ''),
              item.header.length);
          }

          this.tokens.push(item);

          continue;
        }
      }

      // lheading
      if (cap = this.rules.lheading.exec(src)) {
        src = src.substring(cap[0].length);
        this.tokens.push({
          type: 'heading',
          depth: cap[2].charAt(0) === '=' ? 1 : 2,
          text: cap[1]
        });
        continue;
      }

      // top-level paragraph
      if (top && (cap = this.rules.paragraph.exec(src))) {
        src = src.substring(cap[0].length);
        this.tokens.push({
          type: 'paragraph',
          text: cap[1].charAt(cap[1].length - 1) === '\n'
            ? cap[1].slice(0, -1)
            : cap[1]
        });
        continue;
      }

      // text
      if (cap = this.rules.text.exec(src)) {
        // Top-level should never reach here.
        src = src.substring(cap[0].length);
        this.tokens.push({
          type: 'text',
          text: cap[0]
        });
        continue;
      }

      if (src) {
        throw new Error('Infinite loop on byte: ' + src.charCodeAt(0));
      }
    }

    return this.tokens;
  };
};


/***/ }),

/***/ "./node_modules/marked/src/Parser.js":
/*!*******************************************!*\
  !*** ./node_modules/marked/src/Parser.js ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

const Renderer = __webpack_require__(/*! ./Renderer.js */ "./node_modules/marked/src/Renderer.js");
const Slugger = __webpack_require__(/*! ./Slugger.js */ "./node_modules/marked/src/Slugger.js");
const InlineLexer = __webpack_require__(/*! ./InlineLexer.js */ "./node_modules/marked/src/InlineLexer.js");
const TextRenderer = __webpack_require__(/*! ./TextRenderer.js */ "./node_modules/marked/src/TextRenderer.js");
const { defaults } = __webpack_require__(/*! ./defaults.js */ "./node_modules/marked/src/defaults.js");
const {
  merge,
  unescape
} = __webpack_require__(/*! ./helpers.js */ "./node_modules/marked/src/helpers.js");

/**
 * Parsing & Compiling
 */
module.exports = class Parser {
  constructor(options) {
    this.tokens = [];
    this.token = null;
    this.options = options || defaults;
    this.options.renderer = this.options.renderer || new Renderer();
    this.renderer = this.options.renderer;
    this.renderer.options = this.options;
    this.slugger = new Slugger();
  }

  /**
   * Static Parse Method
   */
  static parse(tokens, options) {
    const parser = new Parser(options);
    return parser.parse(tokens);
  };

  /**
   * Parse Loop
   */
  parse(tokens) {
    this.inline = new InlineLexer(tokens.links, this.options);
    // use an InlineLexer with a TextRenderer to extract pure text
    this.inlineText = new InlineLexer(
      tokens.links,
      merge({}, this.options, { renderer: new TextRenderer() })
    );
    this.tokens = tokens.reverse();

    let out = '';
    while (this.next()) {
      out += this.tok();
    }

    return out;
  };

  /**
   * Next Token
   */
  next() {
    this.token = this.tokens.pop();
    return this.token;
  };

  /**
   * Preview Next Token
   */
  peek() {
    return this.tokens[this.tokens.length - 1] || 0;
  };

  /**
   * Parse Text Tokens
   */
  parseText() {
    let body = this.token.text;

    while (this.peek().type === 'text') {
      body += '\n' + this.next().text;
    }

    return this.inline.output(body);
  };

  /**
   * Parse Current Token
   */
  tok() {
    let body = '';
    switch (this.token.type) {
      case 'space': {
        return '';
      }
      case 'hr': {
        return this.renderer.hr();
      }
      case 'heading': {
        return this.renderer.heading(
          this.inline.output(this.token.text),
          this.token.depth,
          unescape(this.inlineText.output(this.token.text)),
          this.slugger);
      }
      case 'code': {
        return this.renderer.code(this.token.text,
          this.token.lang,
          this.token.escaped);
      }
      case 'table': {
        let header = '',
          i,
          row,
          cell,
          j;

        // header
        cell = '';
        for (i = 0; i < this.token.header.length; i++) {
          cell += this.renderer.tablecell(
            this.inline.output(this.token.header[i]),
            { header: true, align: this.token.align[i] }
          );
        }
        header += this.renderer.tablerow(cell);

        for (i = 0; i < this.token.cells.length; i++) {
          row = this.token.cells[i];

          cell = '';
          for (j = 0; j < row.length; j++) {
            cell += this.renderer.tablecell(
              this.inline.output(row[j]),
              { header: false, align: this.token.align[j] }
            );
          }

          body += this.renderer.tablerow(cell);
        }
        return this.renderer.table(header, body);
      }
      case 'blockquote_start': {
        body = '';

        while (this.next().type !== 'blockquote_end') {
          body += this.tok();
        }

        return this.renderer.blockquote(body);
      }
      case 'list_start': {
        body = '';
        const ordered = this.token.ordered,
          start = this.token.start;

        while (this.next().type !== 'list_end') {
          body += this.tok();
        }

        return this.renderer.list(body, ordered, start);
      }
      case 'list_item_start': {
        body = '';
        const loose = this.token.loose;
        const checked = this.token.checked;
        const task = this.token.task;

        if (this.token.task) {
          if (loose) {
            if (this.peek().type === 'text') {
              const nextToken = this.peek();
              nextToken.text = this.renderer.checkbox(checked) + ' ' + nextToken.text;
            } else {
              this.tokens.push({
                type: 'text',
                text: this.renderer.checkbox(checked)
              });
            }
          } else {
            body += this.renderer.checkbox(checked);
          }
        }

        while (this.next().type !== 'list_item_end') {
          body += !loose && this.token.type === 'text'
            ? this.parseText()
            : this.tok();
        }
        return this.renderer.listitem(body, task, checked);
      }
      case 'html': {
        // TODO parse inline content if parameter markdown=1
        return this.renderer.html(this.token.text);
      }
      case 'paragraph': {
        return this.renderer.paragraph(this.inline.output(this.token.text));
      }
      case 'text': {
        return this.renderer.paragraph(this.parseText());
      }
      default: {
        const errMsg = 'Token with "' + this.token.type + '" type was not found.';
        if (this.options.silent) {
          console.log(errMsg);
        } else {
          throw new Error(errMsg);
        }
      }
    }
  };
};


/***/ }),

/***/ "./node_modules/marked/src/Renderer.js":
/*!*********************************************!*\
  !*** ./node_modules/marked/src/Renderer.js ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

const { defaults } = __webpack_require__(/*! ./defaults.js */ "./node_modules/marked/src/defaults.js");
const {
  cleanUrl,
  escape
} = __webpack_require__(/*! ./helpers.js */ "./node_modules/marked/src/helpers.js");

/**
 * Renderer
 */
module.exports = class Renderer {
  constructor(options) {
    this.options = options || defaults;
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

    if (!lang) {
      return '<pre><code>'
        + (escaped ? code : escape(code, true))
        + '</code></pre>';
    }

    return '<pre><code class="'
      + this.options.langPrefix
      + escape(lang, true)
      + '">'
      + (escaped ? code : escape(code, true))
      + '</code></pre>\n';
  };

  blockquote(quote) {
    return '<blockquote>\n' + quote + '</blockquote>\n';
  };

  html(html) {
    return html;
  };

  heading(text, level, raw, slugger) {
    if (this.options.headerIds) {
      return '<h'
        + level
        + ' id="'
        + this.options.headerPrefix
        + slugger.slug(raw)
        + '">'
        + text
        + '</h'
        + level
        + '>\n';
    }
    // ignore IDs
    return '<h' + level + '>' + text + '</h' + level + '>\n';
  };

  hr() {
    return this.options.xhtml ? '<hr/>\n' : '<hr>\n';
  };

  list(body, ordered, start) {
    const type = ordered ? 'ol' : 'ul',
      startatt = (ordered && start !== 1) ? (' start="' + start + '"') : '';
    return '<' + type + startatt + '>\n' + body + '</' + type + '>\n';
  };

  listitem(text) {
    return '<li>' + text + '</li>\n';
  };

  checkbox(checked) {
    return '<input '
      + (checked ? 'checked="" ' : '')
      + 'disabled="" type="checkbox"'
      + (this.options.xhtml ? ' /' : '')
      + '> ';
  };

  paragraph(text) {
    return '<p>' + text + '</p>\n';
  };

  table(header, body) {
    if (body) body = '<tbody>' + body + '</tbody>';

    return '<table>\n'
      + '<thead>\n'
      + header
      + '</thead>\n'
      + body
      + '</table>\n';
  };

  tablerow(content) {
    return '<tr>\n' + content + '</tr>\n';
  };

  tablecell(content, flags) {
    const type = flags.header ? 'th' : 'td';
    const tag = flags.align
      ? '<' + type + ' align="' + flags.align + '">'
      : '<' + type + '>';
    return tag + content + '</' + type + '>\n';
  };

  // span level renderer
  strong(text) {
    return '<strong>' + text + '</strong>';
  };

  em(text) {
    return '<em>' + text + '</em>';
  };

  codespan(text) {
    return '<code>' + text + '</code>';
  };

  br() {
    return this.options.xhtml ? '<br/>' : '<br>';
  };

  del(text) {
    return '<del>' + text + '</del>';
  };

  link(href, title, text) {
    href = cleanUrl(this.options.sanitize, this.options.baseUrl, href);
    if (href === null) {
      return text;
    }
    let out = '<a href="' + escape(href) + '"';
    if (title) {
      out += ' title="' + title + '"';
    }
    out += '>' + text + '</a>';
    return out;
  };

  image(href, title, text) {
    href = cleanUrl(this.options.sanitize, this.options.baseUrl, href);
    if (href === null) {
      return text;
    }

    let out = '<img src="' + href + '" alt="' + text + '"';
    if (title) {
      out += ' title="' + title + '"';
    }
    out += this.options.xhtml ? '/>' : '>';
    return out;
  };

  text(text) {
    return text;
  };
};


/***/ }),

/***/ "./node_modules/marked/src/Slugger.js":
/*!********************************************!*\
  !*** ./node_modules/marked/src/Slugger.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Slugger generates header id
 */
module.exports = class Slugger {
  constructor() {
    this.seen = {};
  }

  /**
   * Convert string to unique id
   */
  slug(value) {
    let slug = value
      .toLowerCase()
      .trim()
      // remove html tags
      .replace(/<[!\/a-z].*?>/ig, '')
      // remove unwanted chars
      .replace(/[\u2000-\u206F\u2E00-\u2E7F\\'!"#$%&()*+,./:;<=>?@[\]^`{|}~]/g, '')
      .replace(/\s/g, '-');

    if (this.seen.hasOwnProperty(slug)) {
      const originalSlug = slug;
      do {
        this.seen[originalSlug]++;
        slug = originalSlug + '-' + this.seen[originalSlug];
      } while (this.seen.hasOwnProperty(slug));
    }
    this.seen[slug] = 0;

    return slug;
  };
};


/***/ }),

/***/ "./node_modules/marked/src/TextRenderer.js":
/*!*************************************************!*\
  !*** ./node_modules/marked/src/TextRenderer.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * TextRenderer
 * returns only the textual part of the token
 */
module.exports = class TextRenderer {
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
};


/***/ }),

/***/ "./node_modules/marked/src/defaults.js":
/*!*********************************************!*\
  !*** ./node_modules/marked/src/defaults.js ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function getDefaults() {
  return {
    baseUrl: null,
    breaks: false,
    gfm: true,
    headerIds: true,
    headerPrefix: '',
    highlight: null,
    langPrefix: 'language-',
    mangle: true,
    pedantic: false,
    renderer: null,
    sanitize: false,
    sanitizer: null,
    silent: false,
    smartLists: false,
    smartypants: false,
    xhtml: false
  };
}

function changeDefaults(newDefaults) {
  module.exports.defaults = newDefaults;
}

module.exports = {
  defaults: getDefaults(),
  getDefaults,
  changeDefaults
};


/***/ }),

/***/ "./node_modules/marked/src/helpers.js":
/*!********************************************!*\
  !*** ./node_modules/marked/src/helpers.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Helpers
 */
const escapeTest = /[&<>"']/;
const escapeReplace = /[&<>"']/g;
const escapeTestNoEncode = /[<>"']|&(?!#?\w+;)/;
const escapeReplaceNoEncode = /[<>"']|&(?!#?\w+;)/g;
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
  } else {
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
    if (n === 'colon') return ':';
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
  regex = regex.source || regex;
  opt = opt || '';
  const obj = {
    replace: (name, val) => {
      val = val.source || val;
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
    } catch (e) {
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
  } catch (e) {
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
    } else {
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
  } else if (href.charAt(0) === '/') {
    if (relativeBase) {
      return href;
    }
    return base.replace(domain, '$1') + href;
  } else {
    return base + href;
  }
}

const noopTest = { exec: function noopTest() {} };

function merge(obj) {
  let i = 1,
    target,
    key;

  for (; i < arguments.length; i++) {
    target = arguments[i];
    for (key in target) {
      if (Object.prototype.hasOwnProperty.call(target, key)) {
        obj[key] = target[key];
      }
    }
  }

  return obj;
}

function splitCells(tableRow, count) {
  // ensure that every cell-delimiting pipe has a space
  // before it to distinguish it from an escaped pipe
  const row = tableRow.replace(/\|/g, (match, offset, str) => {
      let escaped = false,
        curr = offset;
      while (--curr >= 0 && str[curr] === '\\') escaped = !escaped;
      if (escaped) {
        // odd number of slashes means | is escaped
        // so we leave it alone
        return '|';
      } else {
        // add space before unescaped |
        return ' |';
      }
    }),
    cells = row.split(/ \|/);
  let i = 0;

  if (cells.length > count) {
    cells.splice(count);
  } else {
    while (cells.length < count) cells.push('');
  }

  for (; i < cells.length; i++) {
    // leading or trailing whitespace is ignored per the gfm spec
    cells[i] = cells[i].trim().replace(/\\\|/g, '|');
  }
  return cells;
}

// Remove trailing 'c's. Equivalent to str.replace(/c*$/, '').
// /c*$/ is vulnerable to REDOS.
// invert: Remove suffix of non-c chars instead. Default falsey.
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
    } else if (currChar !== c && invert) {
      suffLen++;
    } else {
      break;
    }
  }

  return str.substr(0, l - suffLen);
}

function findClosingBracket(str, b) {
  if (str.indexOf(b[1]) === -1) {
    return -1;
  }
  const l = str.length;
  let level = 0,
    i = 0;
  for (; i < l; i++) {
    if (str[i] === '\\') {
      i++;
    } else if (str[i] === b[0]) {
      level++;
    } else if (str[i] === b[1]) {
      level--;
      if (level < 0) {
        return i;
      }
    }
  }
  return -1;
}

function checkSanitizeDeprecation(opt) {
  if (opt && opt.sanitize && !opt.silent) {
    console.warn('marked(): sanitize and sanitizer parameters are deprecated since version 0.7.0, should not be used and will be removed in the future. Read more here: https://marked.js.org/#/USING_ADVANCED.md#options');
  }
}

module.exports = {
  escape,
  unescape,
  edit,
  cleanUrl,
  resolveUrl,
  noopTest,
  merge,
  splitCells,
  rtrim,
  findClosingBracket,
  checkSanitizeDeprecation
};


/***/ }),

/***/ "./node_modules/marked/src/marked.js":
/*!*******************************************!*\
  !*** ./node_modules/marked/src/marked.js ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

const Lexer = __webpack_require__(/*! ./Lexer.js */ "./node_modules/marked/src/Lexer.js");
const Parser = __webpack_require__(/*! ./Parser.js */ "./node_modules/marked/src/Parser.js");
const Renderer = __webpack_require__(/*! ./Renderer.js */ "./node_modules/marked/src/Renderer.js");
const TextRenderer = __webpack_require__(/*! ./TextRenderer.js */ "./node_modules/marked/src/TextRenderer.js");
const InlineLexer = __webpack_require__(/*! ./InlineLexer.js */ "./node_modules/marked/src/InlineLexer.js");
const Slugger = __webpack_require__(/*! ./Slugger.js */ "./node_modules/marked/src/Slugger.js");
const {
  merge,
  checkSanitizeDeprecation,
  escape
} = __webpack_require__(/*! ./helpers.js */ "./node_modules/marked/src/helpers.js");
const {
  getDefaults,
  changeDefaults,
  defaults
} = __webpack_require__(/*! ./defaults.js */ "./node_modules/marked/src/defaults.js");

/**
 * Marked
 */
function marked(src, opt, callback) {
  // throw error in case of non string input
  if (typeof src === 'undefined' || src === null) {
    throw new Error('marked(): input parameter is undefined or null');
  }
  if (typeof src !== 'string') {
    throw new Error('marked(): input parameter is of type '
      + Object.prototype.toString.call(src) + ', string expected');
  }

  if (callback || typeof opt === 'function') {
    if (!callback) {
      callback = opt;
      opt = null;
    }

    opt = merge({}, marked.defaults, opt || {});
    checkSanitizeDeprecation(opt);
    const highlight = opt.highlight;
    let tokens,
      pending,
      i = 0;

    try {
      tokens = Lexer.lex(src, opt);
    } catch (e) {
      return callback(e);
    }

    pending = tokens.length;

    const done = function(err) {
      if (err) {
        opt.highlight = highlight;
        return callback(err);
      }

      let out;

      try {
        out = Parser.parse(tokens, opt);
      } catch (e) {
        err = e;
      }

      opt.highlight = highlight;

      return err
        ? callback(err)
        : callback(null, out);
    };

    if (!highlight || highlight.length < 3) {
      return done();
    }

    delete opt.highlight;

    if (!pending) return done();

    for (; i < tokens.length; i++) {
      (function(token) {
        if (token.type !== 'code') {
          return --pending || done();
        }
        return highlight(token.text, token.lang, function(err, code) {
          if (err) return done(err);
          if (code == null || code === token.text) {
            return --pending || done();
          }
          token.text = code;
          token.escaped = true;
          --pending || done();
        });
      })(tokens[i]);
    }

    return;
  }
  try {
    opt = merge({}, marked.defaults, opt || {});
    checkSanitizeDeprecation(opt);
    return Parser.parse(Lexer.lex(src, opt), opt);
  } catch (e) {
    e.message += '\nPlease report this to https://github.com/markedjs/marked.';
    if ((opt || marked.defaults).silent) {
      return '<p>An error occurred:</p><pre>'
        + escape(e.message + '', true)
        + '</pre>';
    }
    throw e;
  }
}

/**
 * Options
 */

marked.options =
marked.setOptions = function(opt) {
  merge(marked.defaults, opt);
  changeDefaults(marked.defaults);
  return marked;
};

marked.getDefaults = getDefaults;

marked.defaults = defaults;

/**
 * Expose
 */

marked.Parser = Parser;
marked.parser = Parser.parse;

marked.Renderer = Renderer;
marked.TextRenderer = TextRenderer;

marked.Lexer = Lexer;
marked.lexer = Lexer.lex;

marked.InlineLexer = InlineLexer;
marked.inlineLexer = InlineLexer.output;

marked.Slugger = Slugger;

marked.parse = marked;

module.exports = marked;


/***/ }),

/***/ "./node_modules/marked/src/rules.js":
/*!******************************************!*\
  !*** ./node_modules/marked/src/rules.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

const {
  noopTest,
  edit,
  merge
} = __webpack_require__(/*! ./helpers.js */ "./node_modules/marked/src/helpers.js");

/**
 * Block-Level Grammar
 */
const block = {
  newline: /^\n+/,
  code: /^( {4}[^\n]+\n*)+/,
  fences: /^ {0,3}(`{3,}(?=[^`\n]*\n)|~{3,})([^\n]*)\n(?:|([\s\S]*?)\n)(?: {0,3}\1[~`]* *(?:\n+|$)|$)/,
  hr: /^ {0,3}((?:- *){3,}|(?:_ *){3,}|(?:\* *){3,})(?:\n+|$)/,
  heading: /^ {0,3}(#{1,6}) +([^\n]*?)(?: +#+)? *(?:\n+|$)/,
  blockquote: /^( {0,3}> ?(paragraph|[^\n]*)(?:\n|$))+/,
  list: /^( {0,3})(bull) [\s\S]+?(?:hr|def|\n{2,}(?! )(?!\1bull )\n*|\s*$)/,
  html: '^ {0,3}(?:' // optional indentation
    + '<(script|pre|style)[\\s>][\\s\\S]*?(?:</\\1>[^\\n]*\\n+|$)' // (1)
    + '|comment[^\\n]*(\\n+|$)' // (2)
    + '|<\\?[\\s\\S]*?\\?>\\n*' // (3)
    + '|<![A-Z][\\s\\S]*?>\\n*' // (4)
    + '|<!\\[CDATA\\[[\\s\\S]*?\\]\\]>\\n*' // (5)
    + '|</?(tag)(?: +|\\n|/?>)[\\s\\S]*?(?:\\n{2,}|$)' // (6)
    + '|<(?!script|pre|style)([a-z][\\w-]*)(?:attribute)*? */?>(?=[ \\t]*(?:\\n|$))[\\s\\S]*?(?:\\n{2,}|$)' // (7) open tag
    + '|</(?!script|pre|style)[a-z][\\w-]*\\s*>(?=[ \\t]*(?:\\n|$))[\\s\\S]*?(?:\\n{2,}|$)' // (7) closing tag
    + ')',
  def: /^ {0,3}\[(label)\]: *\n? *<?([^\s>]+)>?(?:(?: +\n? *| *\n *)(title))? *(?:\n+|$)/,
  nptable: noopTest,
  table: noopTest,
  lheading: /^([^\n]+)\n {0,3}(=+|-+) *(?:\n+|$)/,
  // regex template, placeholders will be replaced according to different paragraph
  // interruption rules of commonmark and the original markdown spec:
  _paragraph: /^([^\n]+(?:\n(?!hr|heading|lheading|blockquote|fences|list|html)[^\n]+)*)/,
  text: /^[^\n]+/
};

block._label = /(?!\s*\])(?:\\[\[\]]|[^\[\]])+/;
block._title = /(?:"(?:\\"?|[^"\\])*"|'[^'\n]*(?:\n[^'\n]+)*\n?'|\([^()]*\))/;
block.def = edit(block.def)
  .replace('label', block._label)
  .replace('title', block._title)
  .getRegex();

block.bullet = /(?:[*+-]|\d{1,9}\.)/;
block.item = /^( *)(bull) ?[^\n]*(?:\n(?!\1bull ?)[^\n]*)*/;
block.item = edit(block.item, 'gm')
  .replace(/bull/g, block.bullet)
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
block._comment = /<!--(?!-?>)[\s\S]*?-->/;
block.html = edit(block.html, 'i')
  .replace('comment', block._comment)
  .replace('tag', block._tag)
  .replace('attribute', / +[a-zA-Z:_][\w.:-]*(?: *= *"[^"\n]*"| *= *'[^'\n]*'| *= *[^\s"'=<>`]+)?/)
  .getRegex();

block.paragraph = edit(block._paragraph)
  .replace('hr', block.hr)
  .replace('heading', ' {0,3}#{1,6} ')
  .replace('|lheading', '') // setex headings don't interrupt commonmark paragraphs
  .replace('blockquote', ' {0,3}>')
  .replace('fences', ' {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n')
  .replace('list', ' {0,3}(?:[*+-]|1[.)]) ') // only lists starting from 1 can interrupt
  .replace('html', '</?(?:tag)(?: +|\\n|/?>)|<(?:script|pre|style|!--)')
  .replace('tag', block._tag) // pars can be interrupted by type (6) html blocks
  .getRegex();

block.blockquote = edit(block.blockquote)
  .replace('paragraph', block.paragraph)
  .getRegex();

/**
 * Normal Block Grammar
 */

block.normal = merge({}, block);

/**
 * GFM Block Grammar
 */

block.gfm = merge({}, block.normal, {
  nptable: '^ *([^|\\n ].*\\|.*)\\n' // Header
    + ' *([-:]+ *\\|[-| :]*)' // Align
    + '(?:\\n((?:(?!\\n|hr|heading|blockquote|code|fences|list|html).*(?:\\n|$))*)\\n*|$)', // Cells
  table: '^ *\\|(.+)\\n' // Header
    + ' *\\|?( *[-:]+[-| :]*)' // Align
    + '(?:\\n *((?:(?!\\n|hr|heading|blockquote|code|fences|list|html).*(?:\\n|$))*)\\n*|$)' // Cells
});

block.gfm.nptable = edit(block.gfm.nptable)
  .replace('hr', block.hr)
  .replace('heading', ' {0,3}#{1,6} ')
  .replace('blockquote', ' {0,3}>')
  .replace('code', ' {4}[^\\n]')
  .replace('fences', ' {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n')
  .replace('list', ' {0,3}(?:[*+-]|1[.)]) ') // only lists starting from 1 can interrupt
  .replace('html', '</?(?:tag)(?: +|\\n|/?>)|<(?:script|pre|style|!--)')
  .replace('tag', block._tag) // tables can be interrupted by type (6) html blocks
  .getRegex();

block.gfm.table = edit(block.gfm.table)
  .replace('hr', block.hr)
  .replace('heading', ' {0,3}#{1,6} ')
  .replace('blockquote', ' {0,3}>')
  .replace('code', ' {4}[^\\n]')
  .replace('fences', ' {0,3}(?:`{3,}(?=[^`\\n]*\\n)|~{3,})[^\\n]*\\n')
  .replace('list', ' {0,3}(?:[*+-]|1[.)]) ') // only lists starting from 1 can interrupt
  .replace('html', '</?(?:tag)(?: +|\\n|/?>)|<(?:script|pre|style|!--)')
  .replace('tag', block._tag) // tables can be interrupted by type (6) html blocks
  .getRegex();

/**
 * Pedantic grammar (original John Gruber's loose markdown specification)
 */

block.pedantic = merge({}, block.normal, {
  html: edit(
    '^ *(?:comment *(?:\\n|\\s*$)'
    + '|<(tag)[\\s\\S]+?</\\1> *(?:\\n{2,}|\\s*$)' // closed tag
    + '|<tag(?:"[^"]*"|\'[^\']*\'|\\s[^\'"/>\\s]*)*?/?> *(?:\\n{2,}|\\s*$))')
    .replace('comment', block._comment)
    .replace(/tag/g, '(?!(?:'
      + 'a|em|strong|small|s|cite|q|dfn|abbr|data|time|code|var|samp|kbd|sub'
      + '|sup|i|b|u|mark|ruby|rt|rp|bdi|bdo|span|br|wbr|ins|del|img)'
      + '\\b)\\w+(?!:|[^\\w\\s@]*@)\\b')
    .getRegex(),
  def: /^ *\[([^\]]+)\]: *<?([^\s>]+)>?(?: +(["(][^\n]+[")]))? *(?:\n+|$)/,
  heading: /^ *(#{1,6}) *([^\n]+?) *(?:#+ *)?(?:\n+|$)/,
  fences: noopTest, // fences not supported
  paragraph: edit(block.normal._paragraph)
    .replace('hr', block.hr)
    .replace('heading', ' *#{1,6} *[^\n]')
    .replace('lheading', block.lheading)
    .replace('blockquote', ' {0,3}>')
    .replace('|fences', '')
    .replace('|list', '')
    .replace('|html', '')
    .getRegex()
});

/**
 * Inline-Level Grammar
 */
const inline = {
  escape: /^\\([!"#$%&'()*+,\-./:;<=>?@\[\]\\^_`{|}~])/,
  autolink: /^<(scheme:[^\s\x00-\x1f<>]*|email)>/,
  url: noopTest,
  tag: '^comment'
    + '|^</[a-zA-Z][\\w:-]*\\s*>' // self-closing tag
    + '|^<[a-zA-Z][\\w-]*(?:attribute)*?\\s*/?>' // open tag
    + '|^<\\?[\\s\\S]*?\\?>' // processing instruction, e.g. <?php ?>
    + '|^<![a-zA-Z]+\\s[\\s\\S]*?>' // declaration, e.g. <!DOCTYPE html>
    + '|^<!\\[CDATA\\[[\\s\\S]*?\\]\\]>', // CDATA section
  link: /^!?\[(label)\]\(\s*(href)(?:\s+(title))?\s*\)/,
  reflink: /^!?\[(label)\]\[(?!\s*\])((?:\\[\[\]]?|[^\[\]\\])+)\]/,
  nolink: /^!?\[(?!\s*\])((?:\[[^\[\]]*\]|\\[\[\]]|[^\[\]])*)\](?:\[\])?/,
  strong: /^__([^\s_])__(?!_)|^\*\*([^\s*])\*\*(?!\*)|^__([^\s][\s\S]*?[^\s])__(?!_)|^\*\*([^\s][\s\S]*?[^\s])\*\*(?!\*)/,
  em: /^_([^\s_])_(?!_)|^\*([^\s*<\[])\*(?!\*)|^_([^\s<][\s\S]*?[^\s_])_(?!_|[^\spunctuation])|^_([^\s_<][\s\S]*?[^\s])_(?!_|[^\spunctuation])|^\*([^\s<"][\s\S]*?[^\s\*])\*(?!\*|[^\spunctuation])|^\*([^\s*"<\[][\s\S]*?[^\s])\*(?!\*)/,
  code: /^(`+)([^`]|[^`][\s\S]*?[^`])\1(?!`)/,
  br: /^( {2,}|\\)\n(?!\s*$)/,
  del: noopTest,
  text: /^(`+|[^`])(?:[\s\S]*?(?:(?=[\\<!\[`*]|\b_|$)|[^ ](?= {2,}\n))|(?= {2,}\n))/
};

// list of punctuation marks from common mark spec
// without ` and ] to workaround Rule 17 (inline code blocks/links)
inline._punctuation = '!"#$%&\'()*+,\\-./:;<=>?@\\[^_{|}~';
inline.em = edit(inline.em).replace(/punctuation/g, inline._punctuation).getRegex();

inline._escapes = /\\([!"#$%&'()*+,\-./:;<=>?@\[\]\\^_`{|}~])/g;

inline._scheme = /[a-zA-Z][a-zA-Z0-9+.-]{1,31}/;
inline._email = /[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+(@)[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+(?![-_])/;
inline.autolink = edit(inline.autolink)
  .replace('scheme', inline._scheme)
  .replace('email', inline._email)
  .getRegex();

inline._attribute = /\s+[a-zA-Z:_][\w.:-]*(?:\s*=\s*"[^"]*"|\s*=\s*'[^']*'|\s*=\s*[^\s"'=<>`]+)?/;

inline.tag = edit(inline.tag)
  .replace('comment', block._comment)
  .replace('attribute', inline._attribute)
  .getRegex();

inline._label = /(?:\[[^\[\]]*\]|\\.|`[^`]*`|[^\[\]\\`])*?/;
inline._href = /<(?:\\[<>]?|[^\s<>\\])*>|[^\s\x00-\x1f]*/;
inline._title = /"(?:\\"?|[^"\\])*"|'(?:\\'?|[^'\\])*'|\((?:\\\)?|[^)\\])*\)/;

inline.link = edit(inline.link)
  .replace('label', inline._label)
  .replace('href', inline._href)
  .replace('title', inline._title)
  .getRegex();

inline.reflink = edit(inline.reflink)
  .replace('label', inline._label)
  .getRegex();

/**
 * Normal Inline Grammar
 */

inline.normal = merge({}, inline);

/**
 * Pedantic Inline Grammar
 */

inline.pedantic = merge({}, inline.normal, {
  strong: /^__(?=\S)([\s\S]*?\S)__(?!_)|^\*\*(?=\S)([\s\S]*?\S)\*\*(?!\*)/,
  em: /^_(?=\S)([\s\S]*?\S)_(?!_)|^\*(?=\S)([\s\S]*?\S)\*(?!\*)/,
  link: edit(/^!?\[(label)\]\((.*?)\)/)
    .replace('label', inline._label)
    .getRegex(),
  reflink: edit(/^!?\[(label)\]\s*\[([^\]]*)\]/)
    .replace('label', inline._label)
    .getRegex()
});

/**
 * GFM Inline Grammar
 */

inline.gfm = merge({}, inline.normal, {
  escape: edit(inline.escape).replace('])', '~|])').getRegex(),
  _extended_email: /[A-Za-z0-9._+-]+(@)[a-zA-Z0-9-_]+(?:\.[a-zA-Z0-9-_]*[a-zA-Z0-9])+(?![-_])/,
  url: /^((?:ftp|https?):\/\/|www\.)(?:[a-zA-Z0-9\-]+\.?)+[^\s<]*|^email/,
  _backpedal: /(?:[^?!.,:;*_~()&]+|\([^)]*\)|&(?![a-zA-Z0-9]+;$)|[?!.,:;*_~)]+(?!$))+/,
  del: /^~+(?=\S)([\s\S]*?\S)~+/,
  text: /^(`+|[^`])(?:[\s\S]*?(?:(?=[\\<!\[`*~]|\b_|https?:\/\/|ftp:\/\/|www\.|$)|[^ ](?= {2,}\n)|[^a-zA-Z0-9.!#$%&'*+\/=?_`{\|}~-](?=[a-zA-Z0-9.!#$%&'*+\/=?_`{\|}~-]+@))|(?= {2,}\n|[a-zA-Z0-9.!#$%&'*+\/=?_`{\|}~-]+@))/
});

inline.gfm.url = edit(inline.gfm.url, 'i')
  .replace('email', inline.gfm._extended_email)
  .getRegex();
/**
 * GFM + Line Breaks Inline Grammar
 */

inline.breaks = merge({}, inline.gfm, {
  br: edit(inline.br).replace('{2,}', '*').getRegex(),
  text: edit(inline.gfm.text)
    .replace('\\b_', '\\b_| {2,}\\n')
    .replace(/\{2,\}/g, '*')
    .getRegex()
});

module.exports = {
  block,
  inline
};


/***/ }),

/***/ "./node_modules/p-limit/index.js":
/*!***************************************!*\
  !*** ./node_modules/p-limit/index.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var pTry = __webpack_require__(/*! p-try */ "./node_modules/p-try/index.js");

var pLimit = function pLimit(concurrency) {
  if (!((Number.isInteger(concurrency) || concurrency === Infinity) && concurrency > 0)) {
    return Promise.reject(new TypeError('Expected `concurrency` to be a number from 1 and up'));
  }

  var queue = [];
  var activeCount = 0;

  var next = function next() {
    activeCount--;

    if (queue.length > 0) {
      queue.shift()();
    }
  };

  var run = function run(fn, resolve) {
    activeCount++;

    for (var _len = arguments.length, args = new Array(_len > 2 ? _len - 2 : 0), _key = 2; _key < _len; _key++) {
      args[_key - 2] = arguments[_key];
    }

    var result = pTry.apply(void 0, [fn].concat(args));
    resolve(result);
    result.then(next, next);
  };

  var enqueue = function enqueue(fn, resolve) {
    for (var _len2 = arguments.length, args = new Array(_len2 > 2 ? _len2 - 2 : 0), _key2 = 2; _key2 < _len2; _key2++) {
      args[_key2 - 2] = arguments[_key2];
    }

    if (activeCount < concurrency) {
      run.apply(void 0, [fn, resolve].concat(args));
    } else {
      queue.push(run.bind.apply(run, [null, fn, resolve].concat(args)));
    }
  };

  var generator = function generator(fn) {
    for (var _len3 = arguments.length, args = new Array(_len3 > 1 ? _len3 - 1 : 0), _key3 = 1; _key3 < _len3; _key3++) {
      args[_key3 - 1] = arguments[_key3];
    }

    return new Promise(function (resolve) {
      return enqueue.apply(void 0, [fn, resolve].concat(args));
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
        return queue.length;
      }
    },
    clearQueue: {
      value: function value() {
        queue.length = 0;
      }
    }
  });
  return generator;
};

module.exports = pLimit;
module.exports.default = pLimit;

/***/ }),

/***/ "./node_modules/p-try/index.js":
/*!*************************************!*\
  !*** ./node_modules/p-try/index.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var pTry = function pTry(fn) {
  for (var _len = arguments.length, arguments_ = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    arguments_[_key - 1] = arguments[_key];
  }

  return new Promise(function (resolve) {
    resolve(fn.apply(void 0, arguments_));
  });
};

module.exports = pTry; // TODO: remove this in the next major version

module.exports.default = pTry;

/***/ })

}]);
//# sourceMappingURL=vue-3.js.map?v=49bee3c8683e8ce01ca3