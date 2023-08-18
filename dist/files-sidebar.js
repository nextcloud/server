/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

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

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcSelectTags.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcSelectTags.js ***!
  \*********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/*! For license information please see NcSelectTags.js.LICENSE.txt */
!function(e,t){ true?module.exports=t():0}(self,(()=>(()=>{var e={6969:(e,t,a)=>{"use strict";a.d(t,{default:()=>k});const n={name:"NcActionLink",mixins:[a(1139).Z],props:{href:{type:String,default:"#",required:!0,validator:e=>{try{return new URL(e)}catch(t){return e.startsWith("#")||e.startsWith("/")}}},download:{type:String,default:null},target:{type:String,default:"_self",validator:e=>e&&(!e.startsWith("_")||["_blank","_self","_parent","_top"].indexOf(e)>-1)},title:{type:String,default:null},ariaHidden:{type:Boolean,default:null}}};var o=a(3379),i=a.n(o),r=a(7795),s=a.n(r),l=a(569),c=a.n(l),d=a(3565),u=a.n(d),p=a(9216),A=a.n(p),m=a(4589),g=a.n(m),h=a(3490),v={};v.styleTagTransform=g(),v.setAttributes=u(),v.insert=c().bind(null,"head"),v.domAPI=s(),v.insertStyleElement=A();i()(h.Z,v);h.Z&&h.Z.locals&&h.Z.locals;var b=a(1900),C=a(9158),f=a.n(C),y=(0,b.Z)(n,(function(){var e=this,t=e._self._c;return t("li",{staticClass:"action"},[t("a",{staticClass:"action-link focusable",attrs:{download:e.download,href:e.href,"aria-label":e.ariaLabel,target:e.target,title:e.title,rel:"nofollow noreferrer noopener"},on:{click:e.onClick}},[e._t("icon",(function(){return[t("span",{staticClass:"action-link__icon",class:[e.isIconUrl?"action-link__icon--url":e.icon],style:{backgroundImage:e.isIconUrl?"url(".concat(e.icon,")"):null},attrs:{"aria-hidden":e.ariaHidden}})]})),e._v(" "),e.name?t("p",[t("strong",{staticClass:"action-link__name"},[e._v("\n\t\t\t\t"+e._s(e.name)+"\n\t\t\t")]),e._v(" "),t("br"),e._v(" "),t("span",{staticClass:"action-link__longtext",domProps:{textContent:e._s(e.text)}})]):e.isLongText?t("p",{staticClass:"action-link__longtext",domProps:{textContent:e._s(e.text)}}):t("span",{staticClass:"action-link__text"},[e._v(e._s(e.text))]),e._v(" "),e._e()],2)])}),[],!1,null,"63ee0e66",null);"function"==typeof f()&&f()(y);const k=y.exports},8250:(e,t,a)=>{"use strict";a.d(t,{default:()=>D});var n=a(4462),o=a(2297),i=a(1205),r=a(932),s=a(2734),l=a.n(s),c=a(1441),d=a.n(c);const u=".focusable",p={name:"NcActions",components:{NcButton:n.default,DotsHorizontal:d(),NcPopover:o.default},props:{open:{type:Boolean,default:!1},manualOpen:{type:Boolean,default:!1},forceMenu:{type:Boolean,default:!1},forceName:{type:Boolean,default:!1},menuName:{type:String,default:null},primary:{type:Boolean,default:!1},type:{type:String,validator:e=>-1!==["primary","secondary","tertiary","tertiary-no-background","tertiary-on-primary","error","warning","success"].indexOf(e),default:null},defaultIcon:{type:String,default:""},ariaLabel:{type:String,default:(0,r.t)("Actions")},ariaHidden:{type:Boolean,default:null},placement:{type:String,default:"bottom"},boundariesElement:{type:Element,default:()=>document.querySelector("body")},container:{type:[String,Object,Element,Boolean],default:"body"},disabled:{type:Boolean,default:!1},inline:{type:Number,default:0}},emits:["open","update:open","close","focus","blur"],data(){return{opened:this.open,focusIndex:0,randomId:"menu-".concat((0,i.Z)())}},computed:{triggerBtnType(){return this.type||(this.primary?"primary":this.menuName?"secondary":"tertiary")}},watch:{open(e){e!==this.opened&&(this.opened=e)}},methods:{isValidSingleAction(e){var t,a,n,o,i;const r=null!==(t=null==e||null===(a=e.componentOptions)||void 0===a||null===(n=a.Ctor)||void 0===n||null===(o=n.extendOptions)||void 0===o?void 0:o.name)&&void 0!==t?t:null==e||null===(i=e.componentOptions)||void 0===i?void 0:i.tag;return["NcActionButton","NcActionLink","NcActionRouter"].includes(r)},openMenu(e){this.opened||(this.opened=!0,this.$emit("update:open",!0),this.$emit("open"))},closeMenu(){let e=!(arguments.length>0&&void 0!==arguments[0])||arguments[0];this.opened&&(this.opened=!1,this.$refs.popover.clearFocusTrap({returnFocus:e}),this.$emit("update:open",!1),this.$emit("close"),this.focusIndex=0,this.$refs.menuButton.$el.focus())},onOpen(e){this.$nextTick((()=>{this.focusFirstAction(e)}))},onMouseFocusAction(e){if(document.activeElement===e.target)return;const t=e.target.closest("li");if(t){const e=t.querySelector(u);if(e){const t=[...this.$refs.menu.querySelectorAll(u)].indexOf(e);t>-1&&(this.focusIndex=t,this.focusAction())}}},onKeydown(e){(38===e.keyCode||9===e.keyCode&&e.shiftKey)&&this.focusPreviousAction(e),(40===e.keyCode||9===e.keyCode&&!e.shiftKey)&&this.focusNextAction(e),33===e.keyCode&&this.focusFirstAction(e),34===e.keyCode&&this.focusLastAction(e),27===e.keyCode&&(this.closeMenu(),e.preventDefault())},removeCurrentActive(){const e=this.$refs.menu.querySelector("li.active");e&&e.classList.remove("active")},focusAction(){const e=this.$refs.menu.querySelectorAll(u)[this.focusIndex];if(e){this.removeCurrentActive();const t=e.closest("li.action");e.focus(),t&&t.classList.add("active")}},focusPreviousAction(e){this.opened&&(0===this.focusIndex?this.closeMenu():(this.preventIfEvent(e),this.focusIndex=this.focusIndex-1),this.focusAction())},focusNextAction(e){if(this.opened){const t=this.$refs.menu.querySelectorAll(u).length-1;this.focusIndex===t?this.closeMenu():(this.preventIfEvent(e),this.focusIndex=this.focusIndex+1),this.focusAction()}},focusFirstAction(e){this.opened&&(this.preventIfEvent(e),this.focusIndex=0,this.focusAction())},focusLastAction(e){this.opened&&(this.preventIfEvent(e),this.focusIndex=this.$refs.menu.querySelectorAll(u).length-1,this.focusAction())},preventIfEvent(e){e&&(e.preventDefault(),e.stopPropagation())},onFocus(e){this.$emit("focus",e)},onBlur(e){this.$emit("blur",e)}},render(e){const t=(this.$slots.default||[]).filter((e=>{var t,a,n,o;return(null==e||null===(t=e.componentOptions)||void 0===t?void 0:t.tag)||(null==e||null===(a=e.componentOptions)||void 0===a||null===(n=a.Ctor)||void 0===n||null===(o=n.extendOptions)||void 0===o?void 0:o.name)})),a=t.every((e=>{var t,a,n,o,i,r,s,l;return"NcActionLink"===(null!==(t=null==e||null===(a=e.componentOptions)||void 0===a||null===(n=a.Ctor)||void 0===n||null===(o=n.extendOptions)||void 0===o?void 0:o.name)&&void 0!==t?t:null==e||null===(i=e.componentOptions)||void 0===i?void 0:i.tag)&&(null==e||null===(r=e.componentOptions)||void 0===r||null===(s=r.propsData)||void 0===s||null===(l=s.href)||void 0===l?void 0:l.startsWith(window.location.origin))}));let n=t.filter(this.isValidSingleAction);if(this.forceMenu&&n.length>0&&this.inline>0&&(l().util.warn("Specifying forceMenu will ignore any inline actions rendering."),n=[]),0===t.length)return;const o=t=>{var a,n,o,i,r,s,l,c,d,u,p,A,m,g,h,v,b,C,f,y,k,w;const S=(null==t||null===(a=t.data)||void 0===a||null===(n=a.scopedSlots)||void 0===n||null===(o=n.icon())||void 0===o?void 0:o[0])||e("span",{class:["icon",null==t||null===(i=t.componentOptions)||void 0===i||null===(r=i.propsData)||void 0===r?void 0:r.icon]}),x=null==t||null===(s=t.componentOptions)||void 0===s||null===(l=s.listeners)||void 0===l?void 0:l.click,N=null==t||null===(c=t.componentOptions)||void 0===c||null===(d=c.children)||void 0===d||null===(u=d[0])||void 0===u||null===(p=u.text)||void 0===p||null===(A=p.trim)||void 0===A?void 0:A.call(p),_=(null==t||null===(m=t.componentOptions)||void 0===m||null===(g=m.propsData)||void 0===g?void 0:g.ariaLabel)||N,z=this.forceName?N:"";let j=null==t||null===(h=t.componentOptions)||void 0===h||null===(v=h.propsData)||void 0===v?void 0:v.title;return this.forceName||j||(j=N),e("NcButton",{class:["action-item action-item--single",null==t||null===(b=t.data)||void 0===b?void 0:b.staticClass,null==t||null===(C=t.data)||void 0===C?void 0:C.class],attrs:{"aria-label":_,title:j},ref:null==t||null===(f=t.data)||void 0===f?void 0:f.ref,props:{type:this.type||(z?"secondary":"tertiary"),disabled:this.disabled||(null==t||null===(y=t.componentOptions)||void 0===y||null===(k=y.propsData)||void 0===k?void 0:k.disabled),ariaHidden:this.ariaHidden,...null==t||null===(w=t.componentOptions)||void 0===w?void 0:w.propsData},on:{focus:this.onFocus,blur:this.onBlur,...!!x&&{click:e=>{x&&x(e)}}}},[e("template",{slot:"icon"},[S]),z])},i=t=>{var n,o;const i=(null===(n=this.$slots.icon)||void 0===n?void 0:n[0])||(this.defaultIcon?e("span",{class:["icon",this.defaultIcon]}):e("DotsHorizontal",{props:{size:20}}));return e("NcPopover",{ref:"popover",props:{delay:0,handleResize:!0,shown:this.opened,placement:this.placement,boundary:this.boundariesElement,container:this.container,popoverBaseClass:"action-item__popper",setReturnFocus:null===(o=this.$refs.menuButton)||void 0===o?void 0:o.$el},attrs:{delay:0,handleResize:!0,shown:this.opened,placement:this.placement,boundary:this.boundariesElement,container:this.container,...this.manualOpen&&{triggers:[]},popoverBaseClass:"action-item__popper"},on:{show:this.openMenu,"after-show":this.onOpen,hide:this.closeMenu}},[e("NcButton",{class:"action-item__menutoggle",props:{type:this.triggerBtnType,disabled:this.disabled,ariaHidden:this.ariaHidden},slot:"trigger",ref:"menuButton",attrs:{"aria-haspopup":a?null:"menu","aria-label":this.ariaLabel,"aria-controls":this.opened?this.randomId:null,"aria-expanded":this.opened.toString()},on:{focus:this.onFocus,blur:this.onBlur}},[e("template",{slot:"icon"},[i]),this.menuName]),e("div",{class:{open:this.opened},attrs:{tabindex:"-1"},on:{keydown:this.onKeydown,mousemove:this.onMouseFocusAction},ref:"menu"},[e("ul",{attrs:{id:this.randomId,tabindex:"-1",role:a?null:"menu"}},[t])])])};if(1===t.length&&1===n.length&&!this.forceMenu)return o(n[0]);if(n.length>0&&this.inline>0){const a=n.slice(0,this.inline),r=t.filter((e=>!a.includes(e)));return e("div",{class:["action-items","action-item--".concat(this.triggerBtnType)]},[...a.map(o),r.length>0?e("div",{class:["action-item",{"action-item--open":this.opened}]},[i(r)]):null])}return e("div",{class:["action-item action-item--default-popover","action-item--".concat(this.triggerBtnType),{"action-item--open":this.opened}]},[i(t)])}};var A=a(3379),m=a.n(A),g=a(7795),h=a.n(g),v=a(569),b=a.n(v),C=a(3565),f=a.n(C),y=a(9216),k=a.n(y),w=a(4589),S=a.n(w),x=a(4825),N={};N.styleTagTransform=S(),N.setAttributes=f(),N.insert=b().bind(null,"head"),N.domAPI=h(),N.insertStyleElement=k();m()(x.Z,N);x.Z&&x.Z.locals&&x.Z.locals;var _=a(4946),z={};z.styleTagTransform=S(),z.setAttributes=f(),z.insert=b().bind(null,"head"),z.domAPI=h(),z.insertStyleElement=k();m()(_.Z,z);_.Z&&_.Z.locals&&_.Z.locals;var j=a(1900),P=a(5727),B=a.n(P),E=(0,j.Z)(p,undefined,undefined,!1,null,"29452b76",null);"function"==typeof B()&&B()(E);const D=E.exports},7262:(e,t,a)=>{"use strict";a.d(t,{default:()=>G});var n=a(8250),o=a(6969),i=a(4462),r=a(6492),s=a(7993),l=a(3351),c=a(932),d=a(768),u=a.n(d),p=a(1441),A=a.n(p),m=a(3607),g=a(542),h=a(7672),v=a(4262),b=a(4055);const C=(0,h.getBuilder)("nextcloud").persist().build();function f(e,t){e&&C.setItem("user-has-avatar."+e,t)}const y={name:"NcAvatar",directives:{ClickOutside:b.vOnClickOutside},components:{DotsHorizontal:A(),NcActions:n.default,NcActionLink:o.default,NcButton:i.default,NcLoadingIcon:r.default},mixins:[l.iQ],props:{url:{type:String,default:void 0},iconClass:{type:String,default:void 0},user:{type:String,default:void 0},showUserStatus:{type:Boolean,default:!0},showUserStatusCompact:{type:Boolean,default:!0},preloadedUserStatus:{type:Object,default:void 0},isGuest:{type:Boolean,default:!1},displayName:{type:String,default:void 0},size:{type:Number,default:32},allowPlaceholder:{type:Boolean,default:!0},disableTooltip:{type:Boolean,default:!1},disableMenu:{type:Boolean,default:!1},tooltipMessage:{type:String,default:null},isNoUser:{type:Boolean,default:!1},menuContainer:{type:[String,Object,Element,Boolean],default:"body"}},data:()=>({avatarUrlLoaded:null,avatarSrcSetLoaded:null,userDoesNotExist:!1,isAvatarLoaded:!1,isMenuLoaded:!1,contactsMenuLoading:!1,contactsMenuActions:[],contactsMenuOpenState:!1}),computed:{avatarAriaLabel(){var e,t;if(this.hasMenu)return this.hasStatus&&this.showUserStatus&&this.showUserStatusCompact?(0,c.t)("Avatar of {displayName}, {status}",{displayName:null!==(t=this.displayName)&&void 0!==t?t:this.user,status:this.userStatus.status}):(0,c.t)("Avatar of {displayName}",{displayName:null!==(e=this.displayName)&&void 0!==e?e:this.user})},canDisplayUserStatus(){return this.showUserStatus&&this.hasStatus&&["online","away","dnd"].includes(this.userStatus.status)},showUserStatusIconOnAvatar(){return this.showUserStatus&&this.showUserStatusCompact&&this.hasStatus&&"dnd"!==this.userStatus.status&&this.userStatus.icon},getUserIdentifier(){return this.isDisplayNameDefined?this.displayName:this.isUserDefined?this.user:""},isUserDefined(){return void 0!==this.user},isDisplayNameDefined(){return void 0!==this.displayName},isUrlDefined(){return void 0!==this.url},hasMenu(){var e;return!this.disableMenu&&(this.isMenuLoaded?this.menu.length>0:!(this.user===(null===(e=(0,m.getCurrentUser)())||void 0===e?void 0:e.uid)||this.userDoesNotExist||this.url))},shouldShowPlaceholder(){return this.allowPlaceholder&&this.userDoesNotExist},avatarStyle(){return{"--size":this.size+"px",lineHeight:this.size+"px",fontSize:Math.round(.45*this.size)+"px"}},initialsWrapperStyle(){const{r:e,g:t,b:a}=(0,s.default)(this.getUserIdentifier);return{backgroundColor:"rgba(".concat(e,", ").concat(t,", ").concat(a,", 0.1)")}},initialsStyle(){const{r:e,g:t,b:a}=(0,s.default)(this.getUserIdentifier);return{color:"rgb(".concat(e,", ").concat(t,", ").concat(a,")")}},tooltip(){return!this.disableTooltip&&(this.tooltipMessage?this.tooltipMessage:this.displayName)},initials(){let e;if(this.shouldShowPlaceholder){const t=this.getUserIdentifier,a=t.indexOf(" ");""===t?e="?":(e=String.fromCodePoint(t.codePointAt(0)),-1!==a&&(e=e.concat(String.fromCodePoint(t.codePointAt(a+1)))))}return e.toUpperCase()},menu(){const e=this.contactsMenuActions.map((e=>({href:e.hyperlink,icon:e.icon,text:e.title})));return this.showUserStatus&&(this.userStatus.icon||this.userStatus.message)?[{href:"#",icon:"data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg'><text x='0' y='14' font-size='14'>".concat(function(e){const t=document.createTextNode(e),a=document.createElement("p");return a.appendChild(t),a.innerHTML}(this.userStatus.icon),"</text></svg>"),text:"".concat(this.userStatus.message)}].concat(e):e}},watch:{url(){this.userDoesNotExist=!1,this.loadAvatarUrl()},user(){this.userDoesNotExist=!1,this.isMenuLoaded=!1,this.loadAvatarUrl()}},mounted(){this.loadAvatarUrl(),(0,g.subscribe)("settings:avatar:updated",this.loadAvatarUrl),(0,g.subscribe)("settings:display-name:updated",this.loadAvatarUrl),this.showUserStatus&&this.user&&!this.isNoUser&&(this.preloadedUserStatus?(this.userStatus.status=this.preloadedUserStatus.status||"",this.userStatus.message=this.preloadedUserStatus.message||"",this.userStatus.icon=this.preloadedUserStatus.icon||"",this.hasStatus=null!==this.preloadedUserStatus.status):this.fetchUserStatus(this.user),(0,g.subscribe)("user_status:status.updated",this.handleUserStatusUpdated))},beforeDestroy(){(0,g.unsubscribe)("settings:avatar:updated",this.loadAvatarUrl),(0,g.unsubscribe)("settings:display-name:updated",this.loadAvatarUrl),this.showUserStatus&&this.user&&!this.isNoUser&&(0,g.unsubscribe)("user_status:status.updated",this.handleUserStatusUpdated)},methods:{t:c.t,handleUserStatusUpdated(e){this.user===e.userId&&(this.userStatus={status:e.status,icon:e.icon,message:e.message})},async toggleMenu(){this.hasMenu&&(this.contactsMenuOpenState||await this.fetchContactsMenu(),this.contactsMenuOpenState=!this.contactsMenuOpenState)},closeMenu(){this.contactsMenuOpenState=!1},async fetchContactsMenu(){this.contactsMenuLoading=!0;try{const e=encodeURIComponent(this.user),{data:t}=await u().post((0,v.generateUrl)("contactsmenu/findOne"),"shareType=0&shareWith=".concat(e));this.contactsMenuActions=t.topAction?[t.topAction].concat(t.actions):t.actions}catch(e){this.contactsMenuOpenState=!1}this.contactsMenuLoading=!1,this.isMenuLoaded=!0},loadAvatarUrl(){if(this.isAvatarLoaded=!1,!this.isUrlDefined&&(!this.isUserDefined||this.isNoUser))return this.isAvatarLoaded=!0,void(this.userDoesNotExist=!0);if(this.isUrlDefined)this.updateImageIfValid(this.url);else if(this.size<=64){const e=this.avatarUrlGenerator(this.user,64),t=[e+" 1x",this.avatarUrlGenerator(this.user,512)+" 8x"].join(", ");this.updateImageIfValid(e,t)}else{const e=this.avatarUrlGenerator(this.user,512);this.updateImageIfValid(e)}},avatarUrlGenerator(e,t){var a;const n="invert(100%)"===window.getComputedStyle(document.body).getPropertyValue("--background-invert-if-dark");let o="/avatar/{user}/{size}"+(n?"/dark":"");this.isGuest&&(o="/avatar/guest/{user}/{size}"+(n?"/dark":""));let i=(0,v.generateUrl)(o,{user:e,size:t});return e===(null===(a=(0,m.getCurrentUser)())||void 0===a?void 0:a.uid)&&"undefined"!=typeof oc_userconfig&&(i+="?v="+oc_userconfig.avatar.version),i},updateImageIfValid(e){let t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null;const a=function(e){const t=C.getItem("user-has-avatar."+e);return"string"==typeof t?Boolean(t):null}(this.user);if(this.isUserDefined&&"boolean"==typeof a)return this.isAvatarLoaded=!0,this.avatarUrlLoaded=e,t&&(this.avatarSrcSetLoaded=t),void(!1===a&&(this.userDoesNotExist=!0));const n=new Image;n.onload=()=>{this.avatarUrlLoaded=e,t&&(this.avatarSrcSetLoaded=t),this.isAvatarLoaded=!0,f(this.user,!0)},n.onerror=()=>{console.debug("Invalid avatar url",e),this.avatarUrlLoaded=null,this.avatarSrcSetLoaded=null,this.userDoesNotExist=!0,this.isAvatarLoaded=!1,f(this.user,!1)},t&&(n.srcset=t),n.src=e}}};var k=a(3379),w=a.n(k),S=a(7795),x=a.n(S),N=a(569),_=a.n(N),z=a(3565),j=a.n(z),P=a(9216),B=a.n(P),E=a(4589),D=a.n(E),T=a(6222),O={};O.styleTagTransform=D(),O.setAttributes=j(),O.insert=_().bind(null,"head"),O.domAPI=x(),O.insertStyleElement=B();w()(T.Z,O);T.Z&&T.Z.locals&&T.Z.locals;var F=a(1900),I=a(3051),M=a.n(I),U=(0,F.Z)(y,(function(){var e=this,t=e._self._c;return t("div",{directives:[{name:"click-outside",rawName:"v-click-outside",value:e.closeMenu,expression:"closeMenu"}],ref:"main",staticClass:"avatardiv popovermenu-wrapper",class:{"avatardiv--unknown":e.userDoesNotExist,"avatardiv--with-menu":e.hasMenu,"avatardiv--with-menu-loading":e.contactsMenuLoading},style:e.avatarStyle,attrs:{title:e.tooltip,tabindex:e.hasMenu?"0":void 0,"aria-label":e.avatarAriaLabel,role:e.hasMenu?"button":void 0},on:{click:e.toggleMenu,keydown:function(t){return!t.type.indexOf("key")&&e._k(t.keyCode,"enter",13,t.key,"Enter")?null:e.toggleMenu.apply(null,arguments)}}},[e._t("icon",(function(){return[e.iconClass?t("div",{staticClass:"avatar-class-icon",class:e.iconClass}):e.isAvatarLoaded&&!e.userDoesNotExist?t("img",{attrs:{src:e.avatarUrlLoaded,srcset:e.avatarSrcSetLoaded,alt:""}}):e._e()]})),e._v(" "),e.hasMenu&&!e.menu.length?t("NcButton",{staticClass:"action-item action-item__menutoggle",attrs:{"aria-label":e.t("Open contact menu"),type:"tertiary-no-background"},scopedSlots:e._u([{key:"icon",fn:function(){return[e.contactsMenuLoading?t("NcLoadingIcon"):t("DotsHorizontal",{attrs:{size:20}})]},proxy:!0}],null,!1,2617833509)}):e.hasMenu?t("NcActions",{attrs:{"force-menu":"","manual-open":"",type:"tertiary-no-background",container:e.menuContainer,open:e.contactsMenuOpenState},scopedSlots:e._u([e.contactsMenuLoading?{key:"icon",fn:function(){return[t("NcLoadingIcon")]},proxy:!0}:null],null,!0)},e._l(e.menu,(function(a,n){return t("NcActionLink",{key:n,attrs:{href:a.href,icon:a.icon}},[e._v("\n\t\t\t"+e._s(a.text)+"\n\t\t")])})),1):e._e(),e._v(" "),e.showUserStatusIconOnAvatar?t("div",{staticClass:"avatardiv__user-status avatardiv__user-status--icon"},[e._v("\n\t\t"+e._s(e.userStatus.icon)+"\n\t")]):e.canDisplayUserStatus?t("div",{staticClass:"avatardiv__user-status",class:"avatardiv__user-status--"+e.userStatus.status}):e._e(),e._v(" "),!e.userDoesNotExist||e.iconClass||e.$slots.icon?e._e():t("div",{staticClass:"avatardiv__initials-wrapper",style:e.initialsWrapperStyle},[t("div",{staticClass:"unknown",style:e.initialsStyle},[e._v("\n\t\t\t"+e._s(e.initials)+"\n\t\t")])])],2)}),[],!1,null,"7de2f7ff",null);"function"==typeof M()&&M()(U);const G=U.exports},4462:(e,t,a)=>{"use strict";a.d(t,{default:()=>k});const n={name:"NcButton",props:{disabled:{type:Boolean,default:!1},type:{type:String,validator:e=>-1!==["primary","secondary","tertiary","tertiary-no-background","tertiary-on-primary","error","warning","success"].indexOf(e),default:"secondary"},nativeType:{type:String,validator:e=>-1!==["submit","reset","button"].indexOf(e),default:"button"},wide:{type:Boolean,default:!1},ariaLabel:{type:String,default:null},href:{type:String,default:null},to:{type:[String,Object],default:null},exact:{type:Boolean,default:!1},ariaHidden:{type:Boolean,default:null}},render(e){var t,a,n,o,i,r=this;const s=null===(t=this.$slots.default)||void 0===t||null===(a=t[0])||void 0===a||null===(n=a.text)||void 0===n||null===(o=n.trim)||void 0===o?void 0:o.call(n),l=!!s,c=null===(i=this.$slots)||void 0===i?void 0:i.icon;s||this.ariaLabel||console.warn("You need to fill either the text or the ariaLabel props in the button component.",{text:s,ariaLabel:this.ariaLabel},this);const d=function(){let{navigate:t,isActive:a,isExactActive:n}=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return e(r.to||!r.href?"button":"a",{class:["button-vue",{"button-vue--icon-only":c&&!l,"button-vue--text-only":l&&!c,"button-vue--icon-and-text":c&&l,["button-vue--vue-".concat(r.type)]:r.type,"button-vue--wide":r.wide,active:a,"router-link-exact-active":n}],attrs:{"aria-label":r.ariaLabel,disabled:r.disabled,type:r.href?null:r.nativeType,role:r.href?"button":null,href:!r.to&&r.href?r.href:null,...r.$attrs},on:{...r.$listeners,click:e=>{var a,n;null===(a=r.$listeners)||void 0===a||null===(n=a.click)||void 0===n||n.call(a,e),null==t||t(e)}}},[e("span",{class:"button-vue__wrapper"},[c?e("span",{class:"button-vue__icon",attrs:{"aria-hidden":r.ariaHidden}},[r.$slots.icon]):null,l?e("span",{class:"button-vue__text"},[s]):null])])};return this.to?e("router-link",{props:{custom:!0,to:this.to,exact:this.exact},scopedSlots:{default:d}}):d()}};var o=a(3379),i=a.n(o),r=a(7795),s=a.n(r),l=a(569),c=a.n(l),d=a(3565),u=a.n(d),p=a(9216),A=a.n(p),m=a(4589),g=a.n(m),h=a(7196),v={};v.styleTagTransform=g(),v.setAttributes=u(),v.insert=c().bind(null,"head"),v.domAPI=s(),v.insertStyleElement=A();i()(h.Z,v);h.Z&&h.Z.locals&&h.Z.locals;var b=a(1900),C=a(2102),f=a.n(C),y=(0,b.Z)(n,undefined,undefined,!1,null,"4d05be2c",null);"function"==typeof f()&&f()(y);const k=y.exports},4378:(e,t,a)=>{"use strict";a.d(t,{default:()=>f});var n=a(281),o=a(1336);const i={name:"NcEllipsisedOption",components:{NcHighlight:n.default},props:{name:{type:String,default:""},search:{type:String,default:""}},computed:{needsTruncate(){return this.name&&this.name.length>=10},split(){return this.name.length-Math.min(Math.floor(this.name.length/2),10)},part1(){return this.needsTruncate?this.name.slice(0,this.split):this.name},part2(){return this.needsTruncate?this.name.slice(this.split):""},highlight1(){return this.search?(0,o.Z)(this.name,this.search):[]},highlight2(){return this.highlight1.map((e=>({start:e.start-this.split,end:e.end-this.split})))}}};var r=a(3379),s=a.n(r),l=a(7795),c=a.n(l),d=a(569),u=a.n(d),p=a(3565),A=a.n(p),m=a(9216),g=a.n(m),h=a(4589),v=a.n(h),b=a(436),C={};C.styleTagTransform=v(),C.setAttributes=A(),C.insert=u().bind(null,"head"),C.domAPI=c(),C.insertStyleElement=g();s()(b.Z,C);b.Z&&b.Z.locals&&b.Z.locals;const f=(0,a(1900).Z)(i,(function(){var e=this,t=e._self._c;return t("span",{staticClass:"name-parts",attrs:{title:e.name}},[t("NcHighlight",{staticClass:"name-parts__first",attrs:{text:e.part1,search:e.search,highlight:e.highlight1}}),e._v(" "),e.part2?t("NcHighlight",{staticClass:"name-parts__last",attrs:{text:e.part2,search:e.search,highlight:e.highlight2}}):e._e()],1)}),[],!1,null,"3daafbe0",null).exports},281:(e,t,a)=>{"use strict";a.d(t,{default:()=>c});var n=a(1336);const o={name:"NcHighlight",props:{text:{type:String,default:""},search:{type:String,default:""},highlight:{type:Array,default:()=>[]}},computed:{ranges(){let e=[];return this.search||0!==this.highlight.length?(e=this.highlight.length>0?this.highlight:(0,n.Z)(this.text,this.search),e.forEach(((t,a)=>{t.end<t.start&&(e[a]={start:t.end,end:t.start})})),e=e.reduce(((e,t)=>(t.start<this.text.length&&t.end>0&&e.push({start:t.start<0?0:t.start,end:t.end>this.text.length?this.text.length:t.end}),e)),[]),e.sort(((e,t)=>e.start-t.start)),e=e.reduce(((e,t)=>{if(e.length){const a=e.length-1;e[a].end>=t.start?e[a]={start:e[a].start,end:Math.max(e[a].end,t.end)}:e.push(t)}else e.push(t);return e}),[]),e):e},chunks(){if(0===this.ranges.length)return[{start:0,end:this.text.length,highlight:!1,text:this.text}];const e=[];let t=0,a=0;for(;t<this.text.length;){const n=this.ranges[a];n.start!==t?(e.push({start:t,end:n.start,highlight:!1,text:this.text.slice(t,n.start)}),t=n.start):(e.push({...n,highlight:!0,text:this.text.slice(n.start,n.end)}),a++,t=n.end,a>=this.ranges.length&&t<this.text.length&&(e.push({start:t,end:this.text.length,highlight:!1,text:this.text.slice(t)}),t=this.text.length))}return e}},render(e){return this.ranges.length?e("span",{},this.chunks.map((t=>t.highlight?e("strong",{},t.text):t.text))):e("span",{},this.text)}};var i=a(1900),r=a(6274),s=a.n(r),l=(0,i.Z)(o,undefined,undefined,!1,null,null,null);"function"==typeof s()&&s()(l);const c=l.exports},3314:(e,t,a)=>{"use strict";a.d(t,{default:()=>w});const n=__webpack_require__(/*! @skjnldsv/sanitize-svg */ "./node_modules/@skjnldsv/sanitize-svg/dist/index.js"),o={name:"NcIconSvgWrapper",props:{svg:{type:String,default:""},name:{type:String,default:""}},data:()=>({cleanSvg:""}),async beforeMount(){await this.sanitizeSVG()},methods:{async sanitizeSVG(){this.svg&&(this.cleanSvg=await(0,n.sanitizeSVG)(this.svg))}}};var i=a(3379),r=a.n(i),s=a(7795),l=a.n(s),c=a(569),d=a.n(c),u=a(3565),p=a.n(u),A=a(9216),m=a.n(A),g=a(4589),h=a.n(g),v=a(8402),b={};b.styleTagTransform=h(),b.setAttributes=p(),b.insert=d().bind(null,"head"),b.domAPI=l(),b.insertStyleElement=m();r()(v.Z,b);v.Z&&v.Z.locals&&v.Z.locals;var C=a(1900),f=a(1287),y=a.n(f),k=(0,C.Z)(o,(function(){var e=this;return(0,e._self._c)("span",{staticClass:"icon-vue",attrs:{role:"img","aria-hidden":!e.name,"aria-label":e.name},domProps:{innerHTML:e._s(e.cleanSvg)}})}),[],!1,null,"45b807d6",null);"function"==typeof y()&&y()(k);const w=k.exports},2321:(e,t,a)=>{"use strict";a.d(t,{default:()=>_});var n=a(7262),o=a(281),i=a(3314),r=a(3351);const s={name:"NcListItemIcon",components:{NcAvatar:n.default,NcHighlight:o.default,NcIconSvgWrapper:i.default},mixins:[r.iQ],props:{name:{type:String,required:!0},subname:{type:String,default:""},icon:{type:String,default:""},iconSvg:{type:String,default:""},iconName:{type:String,default:""},search:{type:String,default:""},avatarSize:{type:Number,default:32},noMargin:{type:Boolean,default:!1},displayName:{type:String,default:null},isNoUser:{type:Boolean,default:!1},id:{type:String,default:null}},data:()=>({margin:8}),computed:{hasIcon(){return""!==this.icon},hasIconSvg(){return""!==this.iconSvg},isValidSubname(){var e,t;return""!==(null===(e=this.subname)||void 0===e||null===(t=e.trim)||void 0===t?void 0:t.call(e))},isSizeBigEnough(){return this.avatarSize>=32},cssVars(){const e=this.noMargin?0:this.margin;return{"--height":this.avatarSize+2*e+"px","--margin":this.margin+"px"}}},beforeMount(){this.isNoUser||this.subname||this.fetchUserStatus(this.user)}},l=s;var c=a(3379),d=a.n(c),u=a(7795),p=a.n(u),A=a(569),m=a.n(A),g=a(3565),h=a.n(g),v=a(9216),b=a.n(v),C=a(4589),f=a.n(C),y=a(4629),k={};k.styleTagTransform=f(),k.setAttributes=h(),k.insert=m().bind(null,"head"),k.domAPI=p(),k.insertStyleElement=b();d()(y.Z,k);y.Z&&y.Z.locals&&y.Z.locals;var w=a(1900),S=a(8488),x=a.n(S),N=(0,w.Z)(l,(function(){var e=this,t=e._self._c;return t("span",e._g({staticClass:"option",style:e.cssVars,attrs:{id:e.id}},e.$listeners),[t("NcAvatar",e._b({staticClass:"option__avatar",attrs:{"disable-menu":!0,"disable-tooltip":!0,"display-name":e.displayName||e.name,"is-no-user":e.isNoUser,size:e.avatarSize}},"NcAvatar",e.$attrs,!1)),e._v(" "),t("div",{staticClass:"option__details"},[t("NcHighlight",{staticClass:"option__lineone",attrs:{text:e.name,search:e.search}}),e._v(" "),e.isValidSubname&&e.isSizeBigEnough?t("NcHighlight",{staticClass:"option__linetwo",attrs:{text:e.subname,search:e.search}}):e.hasStatus?t("span",[t("span",[e._v(e._s(e.userStatus.icon))]),e._v(" "),t("span",[e._v(e._s(e.userStatus.message))])]):e._e()],1),e._v(" "),e._t("default",(function(){return[e.hasIconSvg?t("NcIconSvgWrapper",{staticClass:"option__icon",attrs:{svg:e.iconSvg,name:e.iconName}}):e.hasIcon?t("span",{staticClass:"icon option__icon",class:e.icon,attrs:{"aria-label":e.iconName}}):e._e()]}))],2)}),[],!1,null,"160648e6",null);"function"==typeof x()&&x()(N);const _=N.exports},6492:(e,t,a)=>{"use strict";a.d(t,{default:()=>k});const n={name:"NcLoadingIcon",props:{size:{type:Number,default:20},appearance:{type:String,validator:e=>["auto","light","dark"].includes(e),default:"auto"},name:{type:String,default:""}},computed:{colors(){const e=["#777","#CCC"];return"light"===this.appearance?e:"dark"===this.appearance?e.reverse():["var(--color-loading-light)","var(--color-loading-dark)"]}}};var o=a(3379),i=a.n(o),r=a(7795),s=a.n(r),l=a(569),c=a.n(l),d=a(3565),u=a.n(d),p=a(9216),A=a.n(p),m=a(4589),g=a.n(m),h=a(8502),v={};v.styleTagTransform=g(),v.setAttributes=u(),v.insert=c().bind(null,"head"),v.domAPI=s(),v.insertStyleElement=A();i()(h.Z,v);h.Z&&h.Z.locals&&h.Z.locals;var b=a(1900),C=a(9280),f=a.n(C),y=(0,b.Z)(n,(function(){var e=this,t=e._self._c;return t("span",{staticClass:"material-design-icon loading-icon",attrs:{"aria-label":e.name,role:"img"}},[t("svg",{attrs:{width:e.size,height:e.size,viewBox:"0 0 24 24"}},[t("path",{attrs:{fill:e.colors[0],d:"M12,4V2A10,10 0 1,0 22,12H20A8,8 0 1,1 12,4Z"}}),e._v(" "),t("path",{attrs:{fill:e.colors[1],d:"M12,4V2A10,10 0 0,1 22,12H20A8,8 0 0,0 12,4Z"}},[e.name?t("title",[e._v(e._s(e.name))]):e._e()])])])}),[],!1,null,"27fa1197",null);"function"==typeof f()&&f()(y);const k=y.exports},2297:(e,t,a)=>{"use strict";a.d(t,{default:()=>N});var n=a(9454),o=a(4505),i=a(1206);const r={name:"NcPopover",components:{Dropdown:n.Dropdown},inheritAttrs:!1,props:{popoverBaseClass:{type:String,default:""},focusTrap:{type:Boolean,default:!0},setReturnFocus:{default:void 0,type:[HTMLElement,SVGElement,String,Boolean]}},emits:["after-show","after-hide"],beforeDestroy(){this.clearFocusTrap()},methods:{async useFocusTrap(){var e,t;if(await this.$nextTick(),!this.focusTrap)return;const a=null===(e=this.$refs.popover)||void 0===e||null===(t=e.$refs.popperContent)||void 0===t?void 0:t.$el;a&&(this.$focusTrap=(0,o.createFocusTrap)(a,{escapeDeactivates:!1,allowOutsideClick:!0,setReturnFocus:this.setReturnFocus,trapStack:(0,i.L)()}),this.$focusTrap.activate())},clearFocusTrap(){let e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};try{var t;null===(t=this.$focusTrap)||void 0===t||t.deactivate(e),this.$focusTrap=null}catch(e){console.warn(e)}},afterShow(){this.$nextTick((()=>{this.$emit("after-show"),this.useFocusTrap()}))},afterHide(){this.$emit("after-hide"),this.clearFocusTrap()}}},s=r;var l=a(3379),c=a.n(l),d=a(7795),u=a.n(d),p=a(569),A=a.n(p),m=a(3565),g=a.n(m),h=a(9216),v=a.n(h),b=a(4589),C=a.n(b),f=a(1625),y={};y.styleTagTransform=C(),y.setAttributes=g(),y.insert=A().bind(null,"head"),y.domAPI=u(),y.insertStyleElement=v();c()(f.Z,y);f.Z&&f.Z.locals&&f.Z.locals;var k=a(1900),w=a(2405),S=a.n(w),x=(0,k.Z)(s,(function(){var e=this;return(0,e._self._c)("Dropdown",e._g(e._b({ref:"popover",attrs:{distance:10,"arrow-padding":10,"no-auto-focus":!0,"popper-class":e.popoverBaseClass},on:{"apply-show":e.afterShow,"apply-hide":e.afterHide},scopedSlots:e._u([{key:"popper",fn:function(){return[e._t("default")]},proxy:!0}],null,!0)},"Dropdown",e.$attrs,!1),e.$listeners),[e._t("trigger")],2)}),[],!1,null,null,null);"function"==typeof S()&&S()(x);const N=x.exports},7357:(e,t,a)=>{"use strict";a.d(t,{default:()=>T});const n=__webpack_require__(/*! @nextcloud/vue-select */ "./node_modules/@nextcloud/vue-select/dist/vue-select.js");var o=a.n(n);__webpack_require__(/*! @nextcloud/vue-select/dist/vue-select.css */ "./node_modules/@nextcloud/vue-select/dist/vue-select.css");const i=__webpack_require__(/*! @floating-ui/dom */ "./node_modules/@floating-ui/dom/dist/floating-ui.dom.esm.js");var r=a(4875),s=a.n(r),l=a(8618),c=a.n(l),d=a(4378),u=a(2321),p=a(6492),A=a(3648);const m={name:"NcSelect",components:{ChevronDown:s(),NcEllipsisedOption:d.default,NcListItemIcon:u.default,NcLoadingIcon:p.default,VueSelect:o()},mixins:[A.Z],props:{...o().props,appendToBody:{type:Boolean,default:!0},calculatePosition:{type:Function,default:null},closeOnSelect:{type:Boolean,default:!0},components:{type:Object,default:()=>({Deselect:{render:e=>e(c(),{props:{size:20,fillColor:"var(--vs-controls-color)"},style:{cursor:"pointer"}})}})},limit:{type:Number,default:null},disabled:{type:Boolean,default:!1},dropdownShouldOpen:{type:Function,default:e=>{let{noDrop:t,open:a}=e;return!t&&a}},filterBy:{type:Function,default:null},inputClass:{type:[String,Object],default:null},inputId:{type:String,default:null},keyboardFocusBorder:{type:Boolean,default:!0},label:{type:String,default:null},loading:{type:Boolean,default:!1},multiple:{type:Boolean,default:!1},noWrap:{type:Boolean,default:!1},options:{type:Array,default:()=>[]},placeholder:{type:String,default:""},placement:{type:String,default:"bottom"},resetFocusOnOptionsChange:{type:Boolean,default:!0},userSelect:{type:Boolean,default:!1},value:{type:[String,Number,Object,Array],default:null}," ":{}},emits:[" "],data:()=>({search:""}),computed:{localCalculatePosition(){return null!==this.calculatePosition?this.calculatePosition:(e,t,a)=>{let{width:n}=a;e.style.width=n;const o={name:"addClass",fn:t=>(e.classList.add("vs__dropdown-menu--floating"),{})},r={name:"togglePlacementClass",fn(a){let{placement:n}=a;return t.$el.classList.toggle("select--drop-up","top"===n),e.classList.toggle("vs__dropdown-menu--floating-placement-top","top"===n),{}}};return(0,i.autoUpdate)(t.$refs.toggle,e,(()=>{(0,i.computePosition)(t.$refs.toggle,e,{placement:this.placement,middleware:[(0,i.offset)(-1),o,r,(0,i.flip)(),(0,i.shift)({limiter:(0,i.limitShift)()})]}).then((t=>{let{x:a,y:n}=t;Object.assign(e.style,{left:"".concat(a,"px"),top:"".concat(n,"px")})}))}))}},localFilterBy(){return null!==this.filterBy?this.filterBy:this.userSelect?(e,t,a)=>("".concat(t," ").concat(e.subname)||"").toLocaleLowerCase().indexOf(a.toLocaleLowerCase())>-1:o().props.filterBy.default},localLabel(){return null!==this.label?this.label:this.userSelect?"displayName":o().props.label.default},propsToForward(){const{inputClass:e,noWrap:t,placement:a,userSelect:n,...o}=this.$props;return{...o,calculatePosition:this.localCalculatePosition,filterBy:this.localFilterBy,label:this.localLabel}}}},g=m;var h=a(3379),v=a.n(h),b=a(7795),C=a.n(b),f=a(569),y=a.n(f),k=a(3565),w=a.n(k),S=a(9216),x=a.n(S),N=a(4589),_=a.n(N),z=a(6065),j={};j.styleTagTransform=_(),j.setAttributes=w(),j.insert=y().bind(null,"head"),j.domAPI=C(),j.insertStyleElement=x();v()(z.Z,j);z.Z&&z.Z.locals&&z.Z.locals;var P=a(1900),B=a(8220),E=a.n(B),D=(0,P.Z)(g,(function(){var e=this,t=e._self._c;return t("VueSelect",e._g(e._b({staticClass:"select",class:{"select--no-wrap":e.noWrap},on:{search:t=>e.search=t},scopedSlots:e._u([{key:"search",fn:function(a){let{attributes:n,events:o}=a;return[t("input",e._g(e._b({class:["vs__search",e.inputClass]},"input",n,!1),o))]}},{key:"open-indicator",fn:function(a){let{attributes:n}=a;return[t("ChevronDown",e._b({attrs:{"fill-color":"var(--vs-controls-color)",size:26}},"ChevronDown",n,!1))]}},{key:"option",fn:function(a){return[e.userSelect?t("NcListItemIcon",e._b({attrs:{name:a[e.localLabel],search:e.search}},"NcListItemIcon",a,!1)):t("NcEllipsisedOption",{attrs:{name:String(a[e.localLabel]),search:e.search}})]}},{key:"selected-option",fn:function(a){return[e.userSelect?t("NcListItemIcon",e._b({attrs:{name:a[e.localLabel],search:e.search}},"NcListItemIcon",a,!1)):t("NcEllipsisedOption",{attrs:{name:String(a[e.localLabel]),search:e.search}})]}},{key:"spinner",fn:function(a){return[a.loading?t("NcLoadingIcon"):e._e()]}},{key:"no-options",fn:function(){return[e._v("\n\t\t"+e._s(e.t("No results"))+"\n\t")]},proxy:!0},e._l(e.$scopedSlots,(function(t,a){return{key:a,fn:function(t){return[e._t(a,null,null,t)]}}}))],null,!0)},"VueSelect",e.propsToForward,!1),e.$listeners))}),[],!1,null,null,null);"function"==typeof E()&&E()(D);const T=D.exports},7993:(e,t,a)=>{"use strict";a.d(t,{default:()=>r});var n=a(6609);const o=__webpack_require__(/*! md5 */ "./node_modules/md5/md5.js");var i=a.n(o);const r=function(e){let t=e.toLowerCase();null===t.match(/^([0-9a-f]{4}-?){8}$/)&&(t=i()(t)),t=t.replace(/[^0-9a-f]/g,"");return(0,n.Z)(6)[function(e,t){let a=0;const n=[];for(let t=0;t<e.length;t++)n.push(parseInt(e.charAt(t),16)%16);for(const e in n)a+=n[e];return parseInt(parseInt(a,10)%t,10)}(t,18)]}},932:(e,t,a)=>{"use strict";a.d(t,{n:()=>r,t:()=>s});var n=a(7931);const o=(0,n.getGettextBuilder)().detectLocale();[{locale:"ar",translations:{"{tag} (invisible)":"{tag} (غير مرئي)","{tag} (restricted)":"{tag} (مقيد)",Actions:"الإجراءات",Activities:"النشاطات","Animals & Nature":"الحيوانات والطبيعة","Anything shared with the same group of people will show up here":"أي مادة تمت مشاركتها مع نفس المجموعة من الأشخاص سيتم عرضها هنا","Avatar of {displayName}":"صورة {displayName} الرمزية","Avatar of {displayName}, {status}":"صورة {displayName} الرمزية، {status}","Cancel changes":"إلغاء التغييرات","Change title":"تغيير العنوان",Choose:"إختيار","Clear text":"مسح النص",Close:"أغلق","Close modal":"قفل الشرط","Close navigation":"إغلاق المتصفح","Close sidebar":"قفل الشريط الجانبي","Confirm changes":"تأكيد التغييرات",Custom:"مخصص","Edit item":"تعديل عنصر","Error getting related resources":"خطأ في تحصيل مصادر ذات صلة","External documentation for {title}":"الوثائق الخارجية لـ{title}",Favorite:"مفضلة",Flags:"الأعلام","Food & Drink":"الطعام والشراب","Frequently used":"كثيرا ما تستخدم",Global:"عالمي","Go back to the list":"العودة إلى القائمة","Hide password":"إخفاء كلمة السر","Message limit of {count} characters reached":"تم الوصول إلى الحد الأقصى لعدد الأحرف في الرسالة: {count} حرف","More items …":"عناصر أخرى ...",Next:"التالي","No emoji found":"لم يتم العثور على أي رمز تعبيري","No results":"ليس هناك أية نتيجة",Objects:"الأشياء",Open:"فتح",'Open link to "{resourceTitle}"':'فتح رابط إلى "{resourceTitle}"',"Open navigation":"فتح المتصفح","Password is secure":"كلمة السر مُؤمّنة","Pause slideshow":"إيقاف العرض مؤقتًا","People & Body":"الناس والجسم","Pick an emoji":"اختر رمزًا تعبيريًا","Please select a time zone:":"الرجاء تحديد المنطقة الزمنية:",Previous:"السابق","Related resources":"مصادر ذات صلة",Search:"بحث","Search results":"نتائج البحث","Select a tag":"اختر علامة",Settings:"الإعدادات","Settings navigation":"إعدادات المتصفح","Show password":"أعرض كلمة السر","Smileys & Emotion":"الوجوه و الرموز التعبيرية","Start slideshow":"بدء العرض",Submit:"إرسال",Symbols:"الرموز","Travel & Places":"السفر والأماكن","Type to search time zone":"اكتب للبحث عن منطقة زمنية","Unable to search the group":"تعذر البحث في المجموعة","Undo changes":"التراجع عن التغييرات","Write message, @ to mention someone, : for emoji autocompletion …":"اكتب رسالة، @ للإشارة إلى شخص ما، : للإكمال التلقائي للرموز التعبيرية ..."}},{locale:"br",translations:{"{tag} (invisible)":"{tag} (diwelus)","{tag} (restricted)":"{tag} (bevennet)",Actions:"Oberioù",Activities:"Oberiantizoù","Animals & Nature":"Loened & Natur",Choose:"Dibab",Close:"Serriñ",Custom:"Personelañ",Flags:"Bannieloù","Food & Drink":"Boued & Evajoù","Frequently used":"Implijet alies",Next:"Da heul","No emoji found":"Emoji ebet kavet","No results":"Disoc'h ebet",Objects:"Traoù","Pause slideshow":"Arsav an diaporama","People & Body":"Tud & Korf","Pick an emoji":"Choaz un emoji",Previous:"A-raok",Search:"Klask","Search results":"Disoc'hoù an enklask","Select a tag":"Choaz ur c'hlav",Settings:"Arventennoù","Smileys & Emotion":"Smileyioù & Fromoù","Start slideshow":"Kregiñ an diaporama",Symbols:"Arouezioù","Travel & Places":"Beaj & Lec'hioù","Unable to search the group":"Dibosupl eo klask ar strollad"}},{locale:"ca",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (restringit)",Actions:"Accions",Activities:"Activitats","Animals & Nature":"Animals i natura","Anything shared with the same group of people will show up here":"Qualsevol cosa compartida amb el mateix grup de persones es mostrarà aquí","Avatar of {displayName}":"Avatar de {displayName}","Avatar of {displayName}, {status}":"Avatar de {displayName}, {status}","Cancel changes":"Cancel·la els canvis","Change title":"Canviar títol",Choose:"Tria","Clear text":"Netejar text",Close:"Tanca","Close modal":"Tancar el mode","Close navigation":"Tanca la navegació","Close sidebar":"Tancar la barra lateral","Confirm changes":"Confirmeu els canvis",Custom:"Personalitzat","Edit item":"Edita l'element","Error getting related resources":"Error obtenint els recursos relacionats","Error parsing svg":"Error en l'anàlisi del svg","External documentation for {title}":"Documentació externa per a {title}",Favorite:"Preferit",Flags:"Marques","Food & Drink":"Menjar i begudes","Frequently used":"Utilitzats recentment",Global:"Global","Go back to the list":"Torna a la llista","Hide password":"Amagar contrasenya","Message limit of {count} characters reached":"S'ha arribat al límit de {count} caràcters per missatge","More items …":"Més artícles...",Next:"Següent","No emoji found":"No s'ha trobat cap emoji","No results":"Sense resultats",Objects:"Objectes",Open:"Obrir",'Open link to "{resourceTitle}"':'Obrir enllaç a "{resourceTitle}"',"Open navigation":"Obre la navegació","Password is secure":"Contrasenya segura<br>","Pause slideshow":"Atura la presentació","People & Body":"Persones i cos","Pick an emoji":"Trieu un emoji","Please select a time zone:":"Seleccioneu una zona horària:",Previous:"Anterior","Related resources":"Recursos relacionats",Search:"Cerca","Search results":"Resultats de cerca","Select a tag":"Seleccioneu una etiqueta",Settings:"Paràmetres","Settings navigation":"Navegació d'opcions","Show password":"Mostrar contrasenya","Smileys & Emotion":"Cares i emocions","Start slideshow":"Inicia la presentació",Submit:"Envia",Symbols:"Símbols","Travel & Places":"Viatges i llocs","Type to search time zone":"Escriviu per cercar la zona horària","Unable to search the group":"No es pot cercar el grup","Undo changes":"Desfés els canvis",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Escriu missatge, fes servir "@" per esmentar algú, fes servir ":" per autocompletar emojis...'}},{locale:"cs_CZ",translations:{"{tag} (invisible)":"{tag} (neviditelné)","{tag} (restricted)":"{tag} (omezené)",Actions:"Akce",Activities:"Aktivity","Animals & Nature":"Zvířata a příroda","Anything shared with the same group of people will show up here":"Cokoli nasdíleného stejné skupině lidí se zobrazí zde","Avatar of {displayName}":"Zástupný obrázek uživatele {displayName}","Avatar of {displayName}, {status}":"Zástupný obrázek uživatele {displayName}, {status}","Cancel changes":"Zrušit změny","Change title":"Změnit nadpis",Choose:"Zvolit","Clear text":"Čitelný text",Close:"Zavřít","Close modal":"Zavřít dialogové okno","Close navigation":"Zavřít navigaci","Close sidebar":"Zavřít postranní panel","Confirm changes":"Potvrdit změny",Custom:"Uživatelsky určené","Edit item":"Upravit položku","Error getting related resources":"Chyba při získávání souvisejících prostředků","Error parsing svg":"Chyba při zpracovávání svg","External documentation for {title}":"Externí dokumentace k {title}",Favorite:"Oblíbené",Flags:"Příznaky","Food & Drink":"Jídlo a pití","Frequently used":"Často používané",Global:"Globální","Go back to the list":"Jít zpět na seznam","Hide password":"Skrýt heslo","Message limit of {count} characters reached":"Dosaženo limitu počtu ({count}) znaků zprávy","More items …":"Další položky…",Next:"Následující","No emoji found":"Nenalezeno žádné emoji","No results":"Nic nenalezeno",Objects:"Objekty",Open:"Otevřít",'Open link to "{resourceTitle}"':"Otevřít odkaz na „{resourceTitle}“","Open navigation":"Otevřít navigaci","Password is secure":"Heslo je bezpečné","Pause slideshow":"Pozastavit prezentaci","People & Body":"Lidé a tělo","Pick an emoji":"Vybrat emoji","Please select a time zone:":"Vyberte časovou zónu:",Previous:"Předchozí","Related resources":"Související prostředky",Search:"Hledat","Search results":"Výsledky hledání","Select a tag":"Vybrat štítek",Settings:"Nastavení","Settings navigation":"Pohyb po nastavení","Show password":"Zobrazit heslo","Smileys & Emotion":"Úsměvy a emoce","Start slideshow":"Spustit prezentaci",Submit:"Odeslat",Symbols:"Symboly","Travel & Places":"Cestování a místa","Type to search time zone":"Psaním vyhledejte časovou zónu","Unable to search the group":"Nedaří se hledat skupinu","Undo changes":"Vzít změny zpět",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':"Napište zprávu – pokud chcete někoho zmínit, napište před jeho uživatelským jménem „@“ (zavináč); automatické doplňování emotikonů zahájíte napsáním „:“ (dvojtečky)…"}},{locale:"da",translations:{"{tag} (invisible)":"{tag} (usynlig)","{tag} (restricted)":"{tag} (begrænset)",Actions:"Handlinger",Activities:"Aktiviteter","Animals & Nature":"Dyr & Natur","Anything shared with the same group of people will show up here":"Alt der deles med samme gruppe af personer vil vises her","Avatar of {displayName}":"Avatar af {displayName}","Avatar of {displayName}, {status}":"Avatar af {displayName}, {status}","Cancel changes":"Annuller ændringer","Change title":"Ret titel",Choose:"Vælg","Clear text":"Ryd tekst",Close:"Luk","Close modal":"Luk vindue","Close navigation":"Luk navigation","Close sidebar":"Luk sidepanel","Confirm changes":"Bekræft ændringer",Custom:"Brugerdefineret","Edit item":"Rediger emne","Error getting related resources":"Kunne ikke hente tilknyttede data","Error parsing svg":"Fejl ved analysering af svg","External documentation for {title}":"Ekstern dokumentation for {title}",Favorite:"Favorit",Flags:"Flag","Food & Drink":"Mad & Drikke","Frequently used":"Ofte brugt",Global:"Global","Go back to the list":"Tilbage til listen","Hide password":"Skjul kodeord","Message limit of {count} characters reached":"Begrænsning på {count} tegn er nået","More items …":"Mere ...",Next:"Videre","No emoji found":"Ingen emoji fundet","No results":"Ingen resultater",Objects:"Objekter",Open:"Åbn",'Open link to "{resourceTitle}"':'Åbn link til "{resourceTitle}"',"Open navigation":"Åbn navigation","Password is secure":"Kodeordet er sikkert","Pause slideshow":"Suspender fremvisning","People & Body":"Mennesker & Menneskekroppen","Pick an emoji":"Vælg en emoji","Please select a time zone:":"Vælg venligst en tidszone:",Previous:"Forrige","Related resources":"Relaterede emner",Search:"Søg","Search results":"Søgeresultater","Select a tag":"Vælg et mærke",Settings:"Indstillinger","Settings navigation":"Naviger i indstillinger","Show password":"Vis kodeord","Smileys & Emotion":"Smileys & Emotion","Start slideshow":"Start fremvisning",Submit:"Send",Symbols:"Symboler","Travel & Places":"Rejser & Rejsemål","Type to search time zone":"Indtast for at søge efter tidszone","Unable to search the group":"Kan ikke søge på denne gruppe","Undo changes":"Fortryd ændringer",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Skriv besked, brug "@" for at nævne nogen, brug ":" til emoji-autofuldførelse ...'}},{locale:"de",translations:{"{tag} (invisible)":"{tag} (unsichtbar)","{tag} (restricted)":"{tag} (eingeschränkt)",Actions:"Aktionen",Activities:"Aktivitäten","Animals & Nature":"Tiere & Natur","Anything shared with the same group of people will show up here":"Alles, das mit derselben Gruppe von Personen geteilt wird, wird hier angezeigt","Avatar of {displayName}":"Avatar von {displayName}","Avatar of {displayName}, {status}":"Avatar von {displayName}, {status}","Cancel changes":"Änderungen verwerfen","Change title":"Titel ändern",Choose:"Auswählen","Clear text":"Klartext",Close:"Schließen","Close modal":"Modal schließen","Close navigation":"Navigation schließen","Close sidebar":"Seitenleiste schließen","Confirm changes":"Änderungen bestätigen",Custom:"Benutzerdefiniert","Edit item":"Objekt bearbeiten","Error getting related resources":"Fehler beim Abrufen verwandter Ressourcen","Error parsing svg":"Fehler beim Einlesen der SVG","External documentation for {title}":"Externe Dokumentation für {title}",Favorite:"Favorit",Flags:"Flaggen","Food & Drink":"Essen & Trinken","Frequently used":"Häufig verwendet",Global:"Global","Go back to the list":"Zurück zur Liste","Hide password":"Passwort verbergen","Message limit of {count} characters reached":"Nachrichtenlimit von {count} Zeichen erreicht","More items …":"Weitere Elemente …",Next:"Weiter","No emoji found":"Kein Emoji gefunden","No results":"Keine Ergebnisse",Objects:"Gegenstände",Open:"Öffnen",'Open link to "{resourceTitle}"':'Link zu "{resourceTitle}" öffnen',"Open navigation":"Navigation öffnen","Password is secure":"Passwort ist sicher","Pause slideshow":"Diashow pausieren","People & Body":"Menschen & Körper","Pick an emoji":"Ein Emoji auswählen","Please select a time zone:":"Bitte wählen Sie eine Zeitzone:",Previous:"Vorherige","Related resources":"Verwandte Ressourcen",Search:"Suche","Search results":"Suchergebnisse","Select a tag":"Schlagwort auswählen",Settings:"Einstellungen","Settings navigation":"Einstellungen für die Navigation","Show password":"Passwort anzeigen","Smileys & Emotion":"Smileys & Emotionen","Start slideshow":"Diashow starten",Submit:"Einreichen",Symbols:"Symbole","Travel & Places":"Reisen & Orte","Type to search time zone":"Tippen, um Zeitzone zu suchen","Unable to search the group":"Die Gruppe konnte nicht durchsucht werden","Undo changes":"Änderungen rückgängig machen",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Nachricht schreiben, "@" um jemanden zu erwähnen, ":" für die automatische Vervollständigung von Emojis …'}},{locale:"de_DE",translations:{"{tag} (invisible)":"{tag} (unsichtbar)","{tag} (restricted)":"{tag} (eingeschränkt)",Actions:"Aktionen",Activities:"Aktivitäten","Animals & Nature":"Tiere & Natur","Anything shared with the same group of people will show up here":"Alles, das mit derselben Gruppe von Personen geteilt wird, wird hier angezeigt","Avatar of {displayName}":"Avatar von {displayName}","Avatar of {displayName}, {status}":"Avatar von {displayName}, {status}","Cancel changes":"Änderungen verwerfen","Change title":"Titel ändern",Choose:"Auswählen","Clear text":"Klartext",Close:"Schließen","Close modal":"Modal schließen","Close navigation":"Navigation schließen","Close sidebar":"Seitenleiste schließen","Confirm changes":"Änderungen bestätigen",Custom:"Benutzerdefiniert","Edit item":"Objekt bearbeiten","Error getting related resources":"Fehler beim Abrufen verwandter Ressourcen","Error parsing svg":"Fehler beim Einlesen der SVG","External documentation for {title}":"Externe Dokumentation für {title}",Favorite:"Favorit",Flags:"Flaggen","Food & Drink":"Essen & Trinken","Frequently used":"Häufig verwendet",Global:"Global","Go back to the list":"Zurück zur Liste","Hide password":"Passwort verbergen","Message limit of {count} characters reached":"Nachrichtenlimit von {count} Zeichen erreicht","More items …":"Weitere Elemente …",Next:"Weiter","No emoji found":"Kein Emoji gefunden","No results":"Keine Ergebnisse",Objects:"Objekte",Open:"Öffnen",'Open link to "{resourceTitle}"':'Link zu "{resourceTitle}" öffnen',"Open navigation":"Navigation öffnen","Password is secure":"Passwort ist sicher","Pause slideshow":"Diashow pausieren","People & Body":"Menschen & Körper","Pick an emoji":"Ein Emoji auswählen","Please select a time zone:":"Bitte eine Zeitzone auswählen:",Previous:"Vorherige","Related resources":"Verwandte Ressourcen",Search:"Suche","Search results":"Suchergebnisse","Select a tag":"Schlagwort auswählen",Settings:"Einstellungen","Settings navigation":"Einstellungen für die Navigation","Show password":"Passwort anzeigen","Smileys & Emotion":"Smileys & Emotionen","Start slideshow":"Diashow starten",Submit:"Einreichen",Symbols:"Symbole","Travel & Places":"Reisen & Orte","Type to search time zone":"Tippen, um eine Zeitzone zu suchen","Unable to search the group":"Die Gruppe kann nicht durchsucht werden","Undo changes":"Änderungen rückgängig machen",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Nachricht schreiben, "@" um jemanden zu erwähnen, ":" für die automatische Vervollständigung von Emojis …'}},{locale:"el",translations:{"{tag} (invisible)":"{tag} (αόρατο)","{tag} (restricted)":"{tag} (περιορισμένο)",Actions:"Ενέργειες",Activities:"Δραστηριότητες","Animals & Nature":"Ζώα & Φύση","Anything shared with the same group of people will show up here":"Οτιδήποτε μοιράζεται με την ίδια ομάδα ατόμων θα εμφανίζεται εδώ","Avatar of {displayName}":"Άβαταρ του {displayName}","Avatar of {displayName}, {status}":"Άβαταρ του {displayName}, {status}","Cancel changes":"Ακύρωση αλλαγών","Change title":"Αλλαγή τίτλου",Choose:"Επιλογή","Clear text":"Εκκαθάριση κειμένου",Close:"Κλείσιμο","Close modal":"Βοηθητικό κλείσιμο","Close navigation":"Κλείσιμο πλοήγησης","Close sidebar":"Κλείσιμο πλευρικής μπάρας","Confirm changes":"Επιβεβαίωση αλλαγών",Custom:"Προσαρμογή","Edit item":"Επεξεργασία","Error getting related resources":"Σφάλμα λήψης σχετικών πόρων","Error parsing svg":"Σφάλμα ανάλυσης svg","External documentation for {title}":"Εξωτερική τεκμηρίωση για {title}",Favorite:"Αγαπημένα",Flags:"Σημαίες","Food & Drink":"Φαγητό & Ποτό","Frequently used":"Συχνά χρησιμοποιούμενο",Global:"Καθολικό","Go back to the list":"Επιστροφή στην αρχική λίστα ","Hide password":"Απόκρυψη κωδικού πρόσβασης","Message limit of {count} characters reached":"Συμπληρώθηκε το όριο των {count} χαρακτήρων του μηνύματος","More items …":"Περισσότερα στοιχεία …",Next:"Επόμενο","No emoji found":"Δεν βρέθηκε emoji","No results":"Κανένα αποτέλεσμα",Objects:"Αντικείμενα",Open:"Άνοιγμα",'Open link to "{resourceTitle}"':'Άνοιγμα συνδέσμου στο "{resourceTitle}"',"Open navigation":"Άνοιγμα πλοήγησης","Password is secure":"Ο κωδικός πρόσβασης είναι ασφαλής","Pause slideshow":"Παύση προβολής διαφανειών","People & Body":"Άνθρωποι & Σώμα","Pick an emoji":"Επιλέξτε ένα emoji","Please select a time zone:":"Παρακαλούμε επιλέξτε μια ζώνη ώρας:",Previous:"Προηγούμενο","Related resources":"Σχετικοί πόροι",Search:"Αναζήτηση","Search results":"Αποτελέσματα αναζήτησης","Select a tag":"Επιλογή ετικέτας",Settings:"Ρυθμίσεις","Settings navigation":"Πλοήγηση ρυθμίσεων","Show password":"Εμφάνιση κωδικού πρόσβασης","Smileys & Emotion":"Φατσούλες & Συναίσθημα","Start slideshow":"Έναρξη προβολής διαφανειών",Submit:"Υποβολή",Symbols:"Σύμβολα","Travel & Places":"Ταξίδια & Τοποθεσίες","Type to search time zone":"Πληκτρολογήστε για αναζήτηση ζώνης ώρας","Unable to search the group":"Δεν είναι δυνατή η αναζήτηση της ομάδας","Undo changes":"Αναίρεση Αλλαγών",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Γράψτε μήνυμα, χρησιμοποιείστε "@" για να αναφέρετε κάποιον, χρησιμοποιείστε ":" για αυτόματη συμπλήρωση emoji …'}},{locale:"en_GB",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (restricted)",Actions:"Actions",Activities:"Activities","Animals & Nature":"Animals & Nature","Anything shared with the same group of people will show up here":"Anything shared with the same group of people will show up here","Avatar of {displayName}":"Avatar of {displayName}","Avatar of {displayName}, {status}":"Avatar of {displayName}, {status}","Cancel changes":"Cancel changes","Change title":"Change title",Choose:"Choose","Clear text":"Clear text",Close:"Close","Close modal":"Close modal","Close navigation":"Close navigation","Close sidebar":"Close sidebar","Confirm changes":"Confirm changes",Custom:"Custom","Edit item":"Edit item","Error getting related resources":"Error getting related resources","Error parsing svg":"Error parsing svg","External documentation for {title}":"External documentation for {title}",Favorite:"Favourite",Flags:"Flags","Food & Drink":"Food & Drink","Frequently used":"Frequently used",Global:"Global","Go back to the list":"Go back to the list","Hide password":"Hide password","Message limit of {count} characters reached":"Message limit of {count} characters reached","More items …":"More items …",Next:"Next","No emoji found":"No emoji found","No results":"No results",Objects:"Objects",Open:"Open",'Open link to "{resourceTitle}"':'Open link to "{resourceTitle}"',"Open navigation":"Open navigation","Password is secure":"Password is secure","Pause slideshow":"Pause slideshow","People & Body":"People & Body","Pick an emoji":"Pick an emoji","Please select a time zone:":"Please select a time zone:",Previous:"Previous","Related resources":"Related resources",Search:"Search","Search results":"Search results","Select a tag":"Select a tag",Settings:"Settings","Settings navigation":"Settings navigation","Show password":"Show password","Smileys & Emotion":"Smileys & Emotion","Start slideshow":"Start slideshow",Submit:"Submit",Symbols:"Symbols","Travel & Places":"Travel & Places","Type to search time zone":"Type to search time zone","Unable to search the group":"Unable to search the group","Undo changes":"Undo changes",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Write message, use "@" to mention someone, use ":" for emoji autocompletion …'}},{locale:"eo",translations:{"{tag} (invisible)":"{tag} (kaŝita)","{tag} (restricted)":"{tag} (limigita)",Actions:"Agoj",Activities:"Aktiveco","Animals & Nature":"Bestoj & Naturo",Choose:"Elektu",Close:"Fermu",Custom:"Propra",Flags:"Flagoj","Food & Drink":"Manĝaĵo & Trinkaĵo","Frequently used":"Ofte uzataj","Message limit of {count} characters reached":"La limo je {count} da literoj atingita",Next:"Sekva","No emoji found":"La emoĝio forestas","No results":"La rezulto forestas",Objects:"Objektoj","Pause slideshow":"Payzi bildprezenton","People & Body":"Homoj & Korpo","Pick an emoji":"Elekti emoĝion ",Previous:"Antaŭa",Search:"Serĉi","Search results":"Serĉrezultoj","Select a tag":"Elektu etikedon",Settings:"Agordo","Settings navigation":"Agorda navigado","Smileys & Emotion":"Ridoj kaj Emocioj","Start slideshow":"Komenci bildprezenton",Symbols:"Signoj","Travel & Places":"Vojaĵoj & Lokoj","Unable to search the group":"Ne eblas serĉi en la grupo","Write message, @ to mention someone …":"Mesaĝi, uzu @ por mencii iun ..."}},{locale:"es",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (restringido)",Actions:"Acciones",Activities:"Actividades","Animals & Nature":"Animales y naturaleza","Anything shared with the same group of people will show up here":"Cualquier cosa que sea compartida con el mismo grupo de personas se mostrará aquí","Avatar of {displayName}":"Avatar de {displayName}","Avatar of {displayName}, {status}":"Avatar de {displayName}, {status}","Cancel changes":"Cancelar cambios","Change title":"Cambiar título",Choose:"Elegir","Clear text":"Limpiar texto",Close:"Cerrar","Close modal":"Cerrar modal","Close navigation":"Cerrar navegación","Close sidebar":"Cerrar barra lateral","Confirm changes":"Confirmar cambios",Custom:"Personalizado","Edit item":"Editar elemento","Error getting related resources":"Se encontró un error al obtener los recursos relacionados","Error parsing svg":"Error procesando svg","External documentation for {title}":"Documentacion externa de {title}",Favorite:"Favorito",Flags:"Banderas","Food & Drink":"Comida y bebida","Frequently used":"Usado con frecuenca",Global:"Global","Go back to the list":"Volver a la lista","Hide password":"Ocultar contraseña","Message limit of {count} characters reached":"El mensaje ha alcanzado el límite de {count} caracteres","More items …":"Más ítems...",Next:"Siguiente","No emoji found":"No hay ningún emoji","No results":" Ningún resultado",Objects:"Objetos",Open:"Abrir",'Open link to "{resourceTitle}"':'Abrir enlace a "{resourceTitle}"',"Open navigation":"Abrir navegación","Password is secure":"La contraseña es segura","Pause slideshow":"Pausar la presentación ","People & Body":"Personas y cuerpos","Pick an emoji":"Elegir un emoji","Please select a time zone:":"Por favor elige un huso de horario:",Previous:"Anterior","Related resources":"Recursos relacionados",Search:"Buscar","Search results":"Resultados de la búsqueda","Select a tag":"Seleccione una etiqueta",Settings:"Ajustes","Settings navigation":"Navegación por ajustes","Show password":"Mostrar contraseña","Smileys & Emotion":"Smileys y emoticonos","Start slideshow":"Iniciar la presentación",Submit:"Enviar",Symbols:"Símbolos","Travel & Places":"Viajes y lugares","Type to search time zone":"Escribe para buscar un huso de horario","Unable to search the group":"No es posible buscar en el grupo","Undo changes":"Deshacer cambios",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Escribir mensaje, utilice "@" para mencionar a alguien, utilice ":" para autocompletado de emojis ...'}},{locale:"eu",translations:{"{tag} (invisible)":"{tag} (ikusezina)","{tag} (restricted)":"{tag} (mugatua)",Actions:"Ekintzak",Activities:"Jarduerak","Animals & Nature":"Animaliak eta Natura","Anything shared with the same group of people will show up here":"Pertsona-talde berarekin partekatutako edozer agertuko da hemen","Avatar of {displayName}":"{displayName}-(e)n irudia","Avatar of {displayName}, {status}":"{displayName} -(e)n irudia, {status}","Cancel changes":"Ezeztatu aldaketak","Change title":"Aldatu titulua",Choose:"Aukeratu","Clear text":"Garbitu testua",Close:"Itxi","Close modal":"Itxi modala","Close navigation":"Itxi nabigazioa","Close sidebar":"Itxi albo-barra","Confirm changes":"Baieztatu aldaketak",Custom:"Pertsonalizatua","Edit item":"Editatu elementua","Error getting related resources":"Errorea erlazionatutako baliabideak lortzerakoan","Error parsing svg":"Errore bat gertatu da svg-a analizatzean","External documentation for {title}":"Kanpoko dokumentazioa {title}(r)entzat",Favorite:"Gogokoa",Flags:"Banderak","Food & Drink":"Janaria eta edariak","Frequently used":"Askotan erabilia",Global:"Globala","Go back to the list":"Bueltatu zerrendara","Hide password":"Ezkutatu pasahitza","Message limit of {count} characters reached":"Mezuaren {count} karaketere-limitera heldu zara","More items …":"Elementu gehiago …",Next:"Hurrengoa","No emoji found":"Ez da emojirik aurkitu","No results":"Emaitzarik ez",Objects:"Objektuak",Open:"Ireki",'Open link to "{resourceTitle}"':'Ireki esteka: "{resourceTitle}"',"Open navigation":"Ireki nabigazioa","Password is secure":"Pasahitza segurua da","Pause slideshow":"Pausatu diaporama","People & Body":"Jendea eta gorputza","Pick an emoji":"Hautatu emoji bat","Please select a time zone:":"Mesedez hautatu ordu-zona bat:",Previous:"Aurrekoa","Related resources":"Erlazionatutako baliabideak",Search:"Bilatu","Search results":"Bilaketa emaitzak","Select a tag":"Hautatu etiketa bat",Settings:"Ezarpenak","Settings navigation":"Nabigazio ezarpenak","Show password":"Erakutsi pasahitza","Smileys & Emotion":"Smileyak eta emozioa","Start slideshow":"Hasi diaporama",Submit:"Bidali",Symbols:"Sinboloak","Travel & Places":"Bidaiak eta lekuak","Type to search time zone":"Idatzi ordu-zona bat bilatzeko","Unable to search the group":"Ezin izan da taldea bilatu","Undo changes":"Aldaketak desegin",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Idatzi mezua, erabili "@" norbait aipatzeko, erabili ":" emojiak automatikoki osatzeko...'}},{locale:"fi_FI",translations:{"{tag} (invisible)":"{tag} (näkymätön)","{tag} (restricted)":"{tag} (rajoitettu)",Actions:"Toiminnot",Activities:"Aktiviteetit","Animals & Nature":"Eläimet & luonto","Avatar of {displayName}":"Käyttäjän {displayName} avatar","Avatar of {displayName}, {status}":"Käyttäjän {displayName} avatar, {status}","Cancel changes":"Peruuta muutokset",Choose:"Valitse",Close:"Sulje","Close navigation":"Sulje navigaatio","Confirm changes":"Vahvista muutokset",Custom:"Mukautettu","Edit item":"Muokkaa kohdetta","External documentation for {title}":"Ulkoinen dokumentaatio kohteelle {title}",Flags:"Liput","Food & Drink":"Ruoka & juoma","Frequently used":"Usein käytetyt",Global:"Yleinen","Go back to the list":"Siirry takaisin listaan","Message limit of {count} characters reached":"Viestin merkken enimmäisimäärä {count} täynnä ",Next:"Seuraava","No emoji found":"Emojia ei löytynyt","No results":"Ei tuloksia",Objects:"Esineet & asiat","Open navigation":"Avaa navigaatio","Pause slideshow":"Keskeytä diaesitys","People & Body":"Ihmiset & keho","Pick an emoji":"Valitse emoji","Please select a time zone:":"Valitse aikavyöhyke:",Previous:"Edellinen",Search:"Etsi","Search results":"Hakutulokset","Select a tag":"Valitse tagi",Settings:"Asetukset","Settings navigation":"Asetusnavigaatio","Smileys & Emotion":"Hymiöt & tunteet","Start slideshow":"Aloita diaesitys",Submit:"Lähetä",Symbols:"Symbolit","Travel & Places":"Matkustus & kohteet","Type to search time zone":"Kirjoita etsiäksesi aikavyöhyke","Unable to search the group":"Ryhmää ei voi hakea","Undo changes":"Kumoa muutokset","Write message, @ to mention someone, : for emoji autocompletion …":"Kirjoita viesti, @ mainitaksesi käyttäjän, : emojin automaattitäydennykseen…"}},{locale:"fr",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (restreint)",Actions:"Actions",Activities:"Activités","Animals & Nature":"Animaux & Nature","Anything shared with the same group of people will show up here":"Tout ce qui est partagé avec le même groupe de personnes apparaîtra ici","Avatar of {displayName}":"Avatar de {displayName}","Avatar of {displayName}, {status}":"Avatar de {displayName}, {status}","Cancel changes":"Annuler les modifications","Change title":"Modifier le titre",Choose:"Choisir","Clear text":"Effacer le texte",Close:"Fermer","Close modal":"Fermer la fenêtre","Close navigation":"Fermer la navigation","Close sidebar":"Fermer la barre latérale","Confirm changes":"Confirmer les modifications",Custom:"Personnalisé","Edit item":"Éditer l'élément","Error getting related resources":"Erreur à la récupération des ressources liées","Error parsing svg":"Erreur d'analyse SVG","External documentation for {title}":"Documentation externe pour {title}",Favorite:"Favori",Flags:"Drapeaux","Food & Drink":"Nourriture & Boissons","Frequently used":"Utilisés fréquemment",Global:"Global","Go back to the list":"Retourner à la liste","Hide password":"Cacher le mot de passe","Message limit of {count} characters reached":"Limite de messages de {count} caractères atteinte","More items …":"Plus d'éléments...",Next:"Suivant","No emoji found":"Pas d’émoji trouvé","No results":"Aucun résultat",Objects:"Objets",Open:"Ouvrir",'Open link to "{resourceTitle}"':'Ouvrir le lien vers "{resourceTitle}"',"Open navigation":"Ouvrir la navigation","Password is secure":"Le mot de passe est sécurisé","Pause slideshow":"Mettre le diaporama en pause","People & Body":"Personnes & Corps","Pick an emoji":"Choisissez un émoji","Please select a time zone:":"Sélectionnez un fuseau horaire : ",Previous:"Précédent","Related resources":"Ressources liées",Search:"Chercher","Search results":"Résultats de recherche","Select a tag":"Sélectionnez une balise",Settings:"Paramètres","Settings navigation":"Navigation dans les paramètres","Show password":"Afficher le mot de passe","Smileys & Emotion":"Smileys & Émotions","Start slideshow":"Démarrer le diaporama",Submit:"Valider",Symbols:"Symboles","Travel & Places":"Voyage & Lieux","Type to search time zone":"Saisissez les premiers lettres pour rechercher un fuseau horaire","Unable to search the group":"Impossible de chercher le groupe","Undo changes":"Annuler les changements",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Écrire un message, utiliser "@" pour mentionner une personne, ":" pour l\'autocomplétion des émojis...'}},{locale:"gl",translations:{"{tag} (invisible)":"{tag} (invisíbel)","{tag} (restricted)":"{tag} (restrinxido)",Actions:"Accións",Activities:"Actividades","Animals & Nature":"Animais e natureza","Cancel changes":"Cancelar os cambios",Choose:"Escoller",Close:"Pechar","Confirm changes":"Confirma os cambios",Custom:"Personalizado","External documentation for {title}":"Documentación externa para {title}",Flags:"Bandeiras","Food & Drink":"Comida e bebida","Frequently used":"Usado con frecuencia","Message limit of {count} characters reached":"Acadouse o límite de {count} caracteres por mensaxe",Next:"Seguinte","No emoji found":"Non se atopou ningún «emoji»","No results":"Sen resultados",Objects:"Obxectos","Pause slideshow":"Pausar o diaporama","People & Body":"Persoas e corpo","Pick an emoji":"Escolla un «emoji»",Previous:"Anterir",Search:"Buscar","Search results":"Resultados da busca","Select a tag":"Seleccione unha etiqueta",Settings:"Axustes","Settings navigation":"Navegación polos axustes","Smileys & Emotion":"Sorrisos e emocións","Start slideshow":"Iniciar o diaporama",Submit:"Enviar",Symbols:"Símbolos","Travel & Places":"Viaxes e lugares","Unable to search the group":"Non foi posíbel buscar o grupo","Write message, @ to mention someone …":"Escriba a mensaxe, @ para mencionar a alguén…"}},{locale:"he",translations:{"{tag} (invisible)":"{tag} (נסתר)","{tag} (restricted)":"{tag} (מוגבל)",Actions:"פעולות",Activities:"פעילויות","Animals & Nature":"חיות וטבע",Choose:"בחירה",Close:"סגירה",Custom:"בהתאמה אישית",Flags:"דגלים","Food & Drink":"מזון ומשקאות","Frequently used":"בשימוש תדיר",Next:"הבא","No emoji found":"לא נמצא אמוג׳י","No results":"אין תוצאות",Objects:"חפצים","Pause slideshow":"השהיית מצגת","People & Body":"אנשים וגוף","Pick an emoji":"נא לבחור אמוג׳י",Previous:"הקודם",Search:"חיפוש","Search results":"תוצאות חיפוש","Select a tag":"בחירת תגית",Settings:"הגדרות","Smileys & Emotion":"חייכנים ורגשונים","Start slideshow":"התחלת המצגת",Symbols:"סמלים","Travel & Places":"טיולים ומקומות","Unable to search the group":"לא ניתן לחפש בקבוצה"}},{locale:"hu_HU",translations:{"{tag} (invisible)":"{tag} (láthatatlan)","{tag} (restricted)":"{tag} (korlátozott)",Actions:"Műveletek",Activities:"Tevékenységek","Animals & Nature":"Állatok és természet","Anything shared with the same group of people will show up here":"Minden, amit ugyanazzal a csoporttal oszt meg, itt fog megjelenni","Avatar of {displayName}":"{displayName} profilképe","Avatar of {displayName}, {status}":"{displayName} profilképe, {status}","Cancel changes":"Változtatások elvetése","Change title":"Cím megváltoztatása",Choose:"Válassszon","Clear text":"Szöveg törlése",Close:"Bezárás","Close modal":"Ablak bezárása","Close navigation":"Navigáció bezárása","Close sidebar":"Oldalsáv bezárása","Confirm changes":"Változtatások megerősítése",Custom:"Egyéni","Edit item":"Elem szerkesztése","Error getting related resources":"Hiba a kapcsolódó erőforrások lekérésekor","Error parsing svg":"Hiba az SVG feldolgozásakor","External documentation for {title}":"Külső dokumentáció ehhez: {title}",Favorite:"Kedvenc",Flags:"Zászlók","Food & Drink":"Étel és ital","Frequently used":"Gyakran használt",Global:"Globális","Go back to the list":"Ugrás vissza a listához","Hide password":"Jelszó elrejtése","Message limit of {count} characters reached":"{count} karakteres üzenetkorlát elérve","More items …":"További elemek...",Next:"Következő","No emoji found":"Nem található emodzsi","No results":"Nincs találat",Objects:"Tárgyak",Open:"Megnyitás",'Open link to "{resourceTitle}"':"A(z) „{resourceTitle}” hivatkozásának megnyitása","Open navigation":"Navigáció megnyitása","Password is secure":"A jelszó biztonságos","Pause slideshow":"Diavetítés szüneteltetése","People & Body":"Emberek és test","Pick an emoji":"Válasszon egy emodzsit","Please select a time zone:":"Válasszon időzónát:",Previous:"Előző","Related resources":"Kapcsolódó erőforrások",Search:"Keresés","Search results":"Találatok","Select a tag":"Válasszon címkét",Settings:"Beállítások","Settings navigation":"Navigáció a beállításokban","Show password":"Jelszó megjelenítése","Smileys & Emotion":"Mosolyok és érzelmek","Start slideshow":"Diavetítés indítása",Submit:"Beküldés",Symbols:"Szimbólumok","Travel & Places":"Utazás és helyek","Type to search time zone":"Gépeljen az időzóna kereséséhez","Unable to search the group":"A csoport nem kereshető","Undo changes":"Változtatások visszavonása",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':"Írjon egy üzenetet, használja a „@”-ot valaki megemlítéséhet, illetve a „:”-ot az emodzsik automatikus kiegészítéséhez…"}},{locale:"is",translations:{"{tag} (invisible)":"{tag} (ósýnilegt)","{tag} (restricted)":"{tag} (takmarkað)",Actions:"Aðgerðir",Activities:"Aðgerðir","Animals & Nature":"Dýr og náttúra",Choose:"Velja",Close:"Loka",Custom:"Sérsniðið",Flags:"Flögg","Food & Drink":"Matur og drykkur","Frequently used":"Oftast notað",Next:"Næsta","No emoji found":"Ekkert tjáningartákn fannst","No results":"Engar niðurstöður",Objects:"Hlutir","Pause slideshow":"Gera hlé á skyggnusýningu","People & Body":"Fólk og líkami","Pick an emoji":"Veldu tjáningartákn",Previous:"Fyrri",Search:"Leita","Search results":"Leitarniðurstöður","Select a tag":"Veldu merki",Settings:"Stillingar","Smileys & Emotion":"Broskallar og tilfinningar","Start slideshow":"Byrja skyggnusýningu",Symbols:"Tákn","Travel & Places":"Staðir og ferðalög","Unable to search the group":"Get ekki leitað í hópnum"}},{locale:"it",translations:{"{tag} (invisible)":"{tag} (invisibile)","{tag} (restricted)":"{tag} (limitato)",Actions:"Azioni",Activities:"Attività","Animals & Nature":"Animali e natura","Anything shared with the same group of people will show up here":"Tutto ciò che è stato condiviso con lo stesso gruppo di persone viene visualizzato qui","Avatar of {displayName}":"Avatar di {displayName}","Avatar of {displayName}, {status}":"Avatar di {displayName}, {status}","Cancel changes":"Annulla modifiche","Change title":"Modifica il titolo",Choose:"Scegli","Clear text":"Cancella il testo",Close:"Chiudi","Close modal":"Chiudi il messaggio modale","Close navigation":"Chiudi la navigazione","Close sidebar":"Chiudi la barra laterale","Confirm changes":"Conferma modifiche",Custom:"Personalizzato","Edit item":"Modifica l'elemento","Error getting related resources":"Errore nell'ottenere risorse correlate","Error parsing svg":"Errore nell'analizzare l'svg","External documentation for {title}":"Documentazione esterna per {title}",Favorite:"Preferito",Flags:"Bandiere","Food & Drink":"Cibo e bevande","Frequently used":"Usati di frequente",Global:"Globale","Go back to the list":"Torna all'elenco","Hide password":"Nascondi la password","Message limit of {count} characters reached":"Limite dei messaggi di {count} caratteri raggiunto","More items …":"Più elementi ...",Next:"Successivo","No emoji found":"Nessun emoji trovato","No results":"Nessun risultato",Objects:"Oggetti",Open:"Apri",'Open link to "{resourceTitle}"':'Apri il link a "{resourceTitle}"',"Open navigation":"Apri la navigazione","Password is secure":"La password è sicura","Pause slideshow":"Presentazione in pausa","People & Body":"Persone e corpo","Pick an emoji":"Scegli un emoji","Please select a time zone:":"Si prega di selezionare un fuso orario:",Previous:"Precedente","Related resources":"Risorse correlate",Search:"Cerca","Search results":"Risultati di ricerca","Select a tag":"Seleziona un'etichetta",Settings:"Impostazioni","Settings navigation":"Navigazione delle impostazioni","Show password":"Mostra la password","Smileys & Emotion":"Faccine ed emozioni","Start slideshow":"Avvia presentazione",Submit:"Invia",Symbols:"Simboli","Travel & Places":"Viaggi e luoghi","Type to search time zone":"Digita per cercare un fuso orario","Unable to search the group":"Impossibile cercare il gruppo","Undo changes":"Cancella i cambiamenti",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Scrivi un messaggio, "@" per menzionare qualcuno, ":" per il completamento automatico delle emoji ...'}},{locale:"ja_JP",translations:{"{tag} (invisible)":"{タグ} (不可視)","{tag} (restricted)":"{タグ} (制限付)",Actions:"操作",Activities:"アクティビティ","Animals & Nature":"動物と自然","Anything shared with the same group of people will show up here":"同じグループで共有しているものは、全てここに表示されます","Avatar of {displayName}":"{displayName} のアバター","Avatar of {displayName}, {status}":"{displayName}, {status} のアバター","Cancel changes":"変更をキャンセル","Change title":"タイトルを変更",Choose:"選択","Clear text":"テキストをクリア",Close:"閉じる","Close modal":"モーダルを閉じる","Close navigation":"ナビゲーションを閉じる","Close sidebar":"サイドバーを閉じる","Confirm changes":"変更を承認",Custom:"カスタム","Edit item":"編集","Error getting related resources":"関連リソースの取得エラー","Error parsing svg":"svgの解析エラー","External documentation for {title}":"{title} のための添付文書",Favorite:"お気に入り",Flags:"国旗","Food & Drink":"食べ物と飲み物","Frequently used":"よく使うもの",Global:"全体","Go back to the list":"リストに戻る","Hide password":"パスワードを非表示","Message limit of {count} characters reached":"{count} 文字のメッセージ上限に達しています","More items …":"他のアイテム",Next:"次","No emoji found":"絵文字が見つかりません","No results":"なし",Objects:"物",Open:"開く",'Open link to "{resourceTitle}"':'"{resourceTitle}"のリンクを開く',"Open navigation":"ナビゲーションを開く","Password is secure":"パスワードは保護されています","Pause slideshow":"スライドショーを一時停止","People & Body":"様々な人と体の部位","Pick an emoji":"絵文字を選択","Please select a time zone:":"タイムゾーンを選んで下さい：",Previous:"前","Related resources":"関連リソース",Search:"検索","Search results":"検索結果","Select a tag":"タグを選択",Settings:"設定","Settings navigation":"ナビゲーション設定","Show password":"パスワードを表示","Smileys & Emotion":"感情表現","Start slideshow":"スライドショーを開始",Submit:"提出",Symbols:"記号","Travel & Places":"旅行と場所","Type to search time zone":"タイムゾーン検索のため入力してください","Unable to search the group":"グループを検索できません","Undo changes":"変更を取り消し",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'メッセージを記入、"@"でメンション、"："で絵文字の自動補完 ...'}},{locale:"lt_LT",translations:{"{tag} (invisible)":"{tag} (nematoma)","{tag} (restricted)":"{tag} (apribota)",Actions:"Veiksmai",Activities:"Veiklos","Animals & Nature":"Gyvūnai ir gamta",Choose:"Pasirinkti",Close:"Užverti",Custom:"Tinkinti","External documentation for {title}":"Išorinė {title} dokumentacija",Flags:"Vėliavos","Food & Drink":"Maistas ir gėrimai","Frequently used":"Dažniausiai naudoti","Message limit of {count} characters reached":"Pasiekta {count} simbolių žinutės riba",Next:"Kitas","No emoji found":"Nerasta jaustukų","No results":"Nėra rezultatų",Objects:"Objektai","Pause slideshow":"Pristabdyti skaidrių rodymą","People & Body":"Žmonės ir kūnas","Pick an emoji":"Pasirinkti jaustuką",Previous:"Ankstesnis",Search:"Ieškoti","Search results":"Paieškos rezultatai","Select a tag":"Pasirinkti žymę",Settings:"Nustatymai","Settings navigation":"Naršymas nustatymuose","Smileys & Emotion":"Šypsenos ir emocijos","Start slideshow":"Pradėti skaidrių rodymą",Submit:"Pateikti",Symbols:"Simboliai","Travel & Places":"Kelionės ir vietos","Unable to search the group":"Nepavyko atlikti paiešką grupėje","Write message, @ to mention someone …":"Rašykite žinutę, naudokite @ norėdami kažką paminėti…"}},{locale:"lv",translations:{"{tag} (invisible)":"{tag} (neredzams)","{tag} (restricted)":"{tag} (ierobežots)",Choose:"Izvēlēties",Close:"Aizvērt",Next:"Nākamais","No results":"Nav rezultātu","Pause slideshow":"Pauzēt slaidrādi",Previous:"Iepriekšējais","Select a tag":"Izvēlēties birku",Settings:"Iestatījumi","Start slideshow":"Sākt slaidrādi"}},{locale:"mk",translations:{"{tag} (invisible)":"{tag} (невидливо)","{tag} (restricted)":"{tag} (ограничено)",Actions:"Акции",Activities:"Активности","Animals & Nature":"Животни & Природа","Avatar of {displayName}":"Аватар на {displayName}","Avatar of {displayName}, {status}":"Аватар на {displayName}, {status}","Cancel changes":"Откажи ги промените","Change title":"Промени наслов",Choose:"Избери",Close:"Затвори","Close modal":"Затвори модал","Close navigation":"Затвори навигација","Confirm changes":"Потврди ги промените",Custom:"Прилагодени","Edit item":"Уреди","External documentation for {title}":"Надворешна документација за {title}",Favorite:"Фаворити",Flags:"Знамиња","Food & Drink":"Храна & Пијалоци","Frequently used":"Најчесто користени",Global:"Глобално","Go back to the list":"Врати се на листата",items:"ставки","Message limit of {count} characters reached":"Ограничувањето на должината на пораката од {count} карактери е надминато","More {dashboardItemType} …":"Повеќе {dashboardItemType} …",Next:"Следно","No emoji found":"Не се пронајдени емотикони","No results":"Нема резултати",Objects:"Објекти",Open:"Отвори","Open navigation":"Отвори навигација","Pause slideshow":"Пузирај слајдшоу","People & Body":"Луѓе & Тело","Pick an emoji":"Избери емотикон","Please select a time zone:":"Изберете временска зона:",Previous:"Предходно",Search:"Барај","Search results":"Резултати од барувањето","Select a tag":"Избери ознака",Settings:"Параметри","Settings navigation":"Параметри за навигација","Smileys & Emotion":"Смешковци & Емотикони","Start slideshow":"Стартувај слајдшоу",Submit:"Испрати",Symbols:"Симболи","Travel & Places":"Патувања & Места","Type to search time zone":"Напишете за да пребарате временска зона","Unable to search the group":"Неможе да се принајде групата","Undo changes":"Врати ги промените","Write message, @ to mention someone, : for emoji autocompletion …":"Напиши порака, @ за да спомнете некого, : за емотинони автоатско комплетирање ..."}},{locale:"my",translations:{"{tag} (invisible)":"{tag} (ကွယ်ဝှက်ထား)","{tag} (restricted)":"{tag} (ကန့်သတ်)",Actions:"လုပ်ဆောင်ချက်များ",Activities:"ပြုလုပ်ဆောင်တာများ","Animals & Nature":"တိရစ္ဆာန်များနှင့် သဘာဝ","Avatar of {displayName}":"{displayName} ၏ ကိုယ်ပွား","Cancel changes":"ပြောင်းလဲမှုများ ပယ်ဖျက်ရန်",Choose:"ရွေးချယ်ရန်",Close:"ပိတ်ရန်","Confirm changes":"ပြောင်းလဲမှုများ အတည်ပြုရန်",Custom:"အလိုကျချိန်ညှိမှု","External documentation for {title}":"{title} အတွက် ပြင်ပ စာရွက်စာတမ်း",Flags:"အလံများ","Food & Drink":"အစားအသောက်","Frequently used":"မကြာခဏအသုံးပြုသော",Global:"ကမ္ဘာလုံးဆိုင်ရာ","Message limit of {count} characters reached":"ကန့်သတ် စာလုံးရေ {count} လုံး ပြည့်ပါပြီ",Next:"နောက်သို့ဆက်ရန်","No emoji found":"အီမိုဂျီ ရှာဖွေမတွေ့နိုင်ပါ","No results":"ရလဒ်မရှိပါ",Objects:"အရာဝတ္ထုများ","Pause slideshow":"စလိုက်ရှိုး ခေတ္တရပ်ရန်","People & Body":"လူပုဂ္ဂိုလ်များနှင့် ခန္ဓာကိုယ်","Pick an emoji":"အီမိုဂျီရွေးရန်","Please select a time zone:":"ဒေသစံတော်ချိန် ရွေးချယ်ပေးပါ",Previous:"ယခင်",Search:"ရှာဖွေရန်","Search results":"ရှာဖွေမှု ရလဒ်များ","Select a tag":"tag ရွေးချယ်ရန်",Settings:"ချိန်ညှိချက်များ","Settings navigation":"ချိန်ညှိချက်အညွှန်း","Smileys & Emotion":"စမိုင်လီများနှင့် အီမိုရှင်း","Start slideshow":"စလိုက်ရှိုးအား စတင်ရန်",Submit:"တင်သွင်းရန်",Symbols:"သင်္ကေတများ","Travel & Places":"ခရီးသွားလာခြင်းနှင့် နေရာများ","Type to search time zone":"ဒေသစံတော်ချိန်များ ရှာဖွေရန် စာရိုက်ပါ","Unable to search the group":"အဖွဲ့အား ရှာဖွေ၍ မရနိုင်ပါ","Write message, @ to mention someone …":"စာရေးသားရန်၊ တစ်စုံတစ်ဦးအား @ အသုံးပြု ရည်ညွှန်းရန်..."}},{locale:"nb_NO",translations:{"{tag} (invisible)":"{tag} (usynlig)","{tag} (restricted)":"{tag} (beskyttet)",Actions:"Handlinger",Activities:"Aktiviteter","Animals & Nature":"Dyr og natur","Anything shared with the same group of people will show up here":"Alt som er delt med den samme gruppen vil vises her","Avatar of {displayName}":"Avataren til {displayName}","Avatar of {displayName}, {status}":"{displayName}'s avatar, {status}","Cancel changes":"Avbryt endringer","Change title":"Endre tittel",Choose:"Velg","Clear text":"Fjern tekst",Close:"Lukk","Close modal":"Lukk modal","Close navigation":"Lukk navigasjon","Close sidebar":"Lukk sidepanel","Confirm changes":"Bekreft endringer",Custom:"Tilpasset","Edit item":"Rediger","Error getting related resources":"Feil ved henting av relaterte ressurser","Error parsing svg":"Feil ved parsing av svg","External documentation for {title}":"Ekstern dokumentasjon for {title}",Favorite:"Favoritt",Flags:"Flagg","Food & Drink":"Mat og drikke","Frequently used":"Ofte brukt",Global:"Global","Go back to the list":"Gå tilbake til listen","Hide password":"Skjul passord","Message limit of {count} characters reached":"Karakter begrensing {count} nådd i melding","More items …":"Flere gjenstander...",Next:"Neste","No emoji found":"Fant ingen emoji","No results":"Ingen resultater",Objects:"Objekter",Open:"Åpne",'Open link to "{resourceTitle}"':'Åpne link til "{resourceTitle}"',"Open navigation":"Åpne navigasjon","Password is secure":"Passordet er sikkert","Pause slideshow":"Pause lysbildefremvisning","People & Body":"Mennesker og kropp","Pick an emoji":"Velg en emoji","Please select a time zone:":"Vennligst velg tidssone",Previous:"Forrige","Related resources":"Relaterte ressurser",Search:"Søk","Search results":"Søkeresultater","Select a tag":"Velg en merkelapp",Settings:"Innstillinger","Settings navigation":"Navigasjonsinstillinger","Show password":"Vis passord","Smileys & Emotion":"Smilefjes og følelser","Start slideshow":"Start lysbildefremvisning",Submit:"Send",Symbols:"Symboler","Travel & Places":"Reise og steder","Type to search time zone":"Tast for å søke etter tidssone","Unable to search the group":"Kunne ikke søke i gruppen","Undo changes":"Tilbakestill endringer",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Skriv melding, bruk "@" for å nevne noen, bruk ":" for autofullføring av emoji...'}},{locale:"nl",translations:{"{tag} (invisible)":"{tag} (onzichtbaar)","{tag} (restricted)":"{tag} (beperkt)",Actions:"Acties",Activities:"Activiteiten","Animals & Nature":"Dieren & Natuur","Avatar of {displayName}":"Avatar van {displayName}","Avatar of {displayName}, {status}":"Avatar van {displayName}, {status}","Cancel changes":"Wijzigingen annuleren",Choose:"Kies",Close:"Sluiten","Close navigation":"Navigatie sluiten","Confirm changes":"Wijzigingen bevestigen",Custom:"Aangepast","Edit item":"Item bewerken","External documentation for {title}":"Externe documentatie voor {title}",Flags:"Vlaggen","Food & Drink":"Eten & Drinken","Frequently used":"Vaak gebruikt",Global:"Globaal","Go back to the list":"Ga terug naar de lijst","Message limit of {count} characters reached":"Berichtlimiet van {count} karakters bereikt",Next:"Volgende","No emoji found":"Geen emoji gevonden","No results":"Geen resultaten",Objects:"Objecten","Open navigation":"Navigatie openen","Pause slideshow":"Pauzeer diavoorstelling","People & Body":"Mensen & Lichaam","Pick an emoji":"Kies een emoji","Please select a time zone:":"Selecteer een tijdzone:",Previous:"Vorige",Search:"Zoeken","Search results":"Zoekresultaten","Select a tag":"Selecteer een label",Settings:"Instellingen","Settings navigation":"Instellingen navigatie","Smileys & Emotion":"Smileys & Emotie","Start slideshow":"Start diavoorstelling",Submit:"Verwerken",Symbols:"Symbolen","Travel & Places":"Reizen & Plaatsen","Type to search time zone":"Type om de tijdzone te zoeken","Unable to search the group":"Kan niet in de groep zoeken","Undo changes":"Wijzigingen ongedaan maken","Write message, @ to mention someone, : for emoji autocompletion …":"Schrijf bericht, @ om iemand te noemen, : voor emoji auto-aanvullen ..."}},{locale:"oc",translations:{"{tag} (invisible)":"{tag} (invisible)","{tag} (restricted)":"{tag} (limit)",Actions:"Accions",Choose:"Causir",Close:"Tampar",Next:"Seguent","No results":"Cap de resultat","Pause slideshow":"Metre en pausa lo diaporama",Previous:"Precedent","Select a tag":"Seleccionar una etiqueta",Settings:"Paramètres","Start slideshow":"Lançar lo diaporama"}},{locale:"pl",translations:{"{tag} (invisible)":"{tag} (niewidoczna)","{tag} (restricted)":"{tag} (ograniczona)",Actions:"Działania",Activities:"Aktywność","Animals & Nature":"Zwierzęta i natura","Anything shared with the same group of people will show up here":"Tutaj pojawi się wszystko, co zostało udostępnione tej samej grupie osób","Avatar of {displayName}":"Awatar {displayName}","Avatar of {displayName}, {status}":"Awatar {displayName}, {status}","Cancel changes":"Anuluj zmiany","Change title":"Zmień tytuł",Choose:"Wybierz","Clear text":"Wyczyść tekst",Close:"Zamknij","Close modal":"Zamknij modal","Close navigation":"Zamknij nawigację","Close sidebar":"Zamknij pasek boczny","Confirm changes":"Potwierdź zmiany",Custom:"Zwyczajne","Edit item":"Edytuj element","Error getting related resources":"Błąd podczas pobierania powiązanych zasobów","Error parsing svg":"Błąd podczas analizowania svg","External documentation for {title}":"Dokumentacja zewnętrzna dla {title}",Favorite:"Ulubiony",Flags:"Flagi","Food & Drink":"Jedzenie i picie","Frequently used":"Często używane",Global:"Globalnie","Go back to the list":"Powrót do listy","Hide password":"Ukryj hasło","Message limit of {count} characters reached":"Przekroczono limit wiadomości wynoszący {count} znaków","More items …":"Więcej pozycji…",Next:"Następny","No emoji found":"Nie znaleziono emoji","No results":"Brak wyników",Objects:"Obiekty",Open:"Otwórz",'Open link to "{resourceTitle}"':'Otwórz link do "{resourceTitle}"',"Open navigation":"Otwórz nawigację","Password is secure":"Hasło jest bezpieczne","Pause slideshow":"Wstrzymaj pokaz slajdów","People & Body":"Ludzie i ciało","Pick an emoji":"Wybierz emoji","Please select a time zone:":"Wybierz strefę czasową:",Previous:"Poprzedni","Related resources":"Powiązane zasoby",Search:"Szukaj","Search results":"Wyniki wyszukiwania","Select a tag":"Wybierz etykietę",Settings:"Ustawienia","Settings navigation":"Ustawienia nawigacji","Show password":"Pokaż hasło","Smileys & Emotion":"Buźki i emotikony","Start slideshow":"Rozpocznij pokaz slajdów",Submit:"Wyślij",Symbols:"Symbole","Travel & Places":"Podróże i miejsca","Type to search time zone":"Wpisz, aby wyszukać strefę czasową","Unable to search the group":"Nie można przeszukać grupy","Undo changes":"Cofnij zmiany",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Napisz wiadomość, "@" aby o kimś wspomnieć, ":" dla autouzupełniania emoji…'}},{locale:"pt_BR",translations:{"{tag} (invisible)":"{tag} (invisível)","{tag} (restricted)":"{tag} (restrito) ",Actions:"Ações",Activities:"Atividades","Animals & Nature":"Animais & Natureza","Anything shared with the same group of people will show up here":"Qualquer coisa compartilhada com o mesmo grupo de pessoas aparecerá aqui","Avatar of {displayName}":"Avatar de {displayName}","Avatar of {displayName}, {status}":"Avatar de {displayName}, {status}","Cancel changes":"Cancelar alterações","Change title":"Alterar título",Choose:"Escolher","Clear text":"Limpar texto",Close:"Fechar","Close modal":"Fechar modal","Close navigation":"Fechar navegação","Close sidebar":"Fechar barra lateral","Confirm changes":"Confirmar alterações",Custom:"Personalizado","Edit item":"Editar item","Error getting related resources":"Erro ao obter recursos relacionados","Error parsing svg":"Erro ao analisar svg","External documentation for {title}":"Documentação externa para {title}",Favorite:"Favorito",Flags:"Bandeiras","Food & Drink":"Comida & Bebida","Frequently used":"Mais usados",Global:"Global","Go back to the list":"Volte para a lista","Hide password":"Ocultar a senha","Message limit of {count} characters reached":"Limite de mensagem de {count} caracteres atingido","More items …":"Mais itens …",Next:"Próximo","No emoji found":"Nenhum emoji encontrado","No results":"Sem resultados",Objects:"Objetos",Open:"Aberto",'Open link to "{resourceTitle}"':'Abrir link para "{resourceTitle}"',"Open navigation":"Abrir navegação","Password is secure":"A senha é segura","Pause slideshow":"Pausar apresentação de slides","People & Body":"Pessoas & Corpo","Pick an emoji":"Escolha um emoji","Please select a time zone:":"Selecione um fuso horário: ",Previous:"Anterior","Related resources":"Recursos relacionados",Search:"Pesquisar","Search results":"Resultados da pesquisa","Select a tag":"Selecionar uma tag",Settings:"Configurações","Settings navigation":"Navegação de configurações","Show password":"Mostrar senha","Smileys & Emotion":"Smiles & Emoções","Start slideshow":"Iniciar apresentação de slides",Submit:"Enviar",Symbols:"Símbolo","Travel & Places":"Viagem & Lugares","Type to search time zone":"Digite para pesquisar o fuso horário ","Unable to search the group":"Não foi possível pesquisar o grupo","Undo changes":"Desfazer modificações",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Escreva mensagens, use "@" para mencionar algum, use ":" for autocompletar emoji …'}},{locale:"pt_PT",translations:{"{tag} (invisible)":"{tag} (invisivel)","{tag} (restricted)":"{tag} (restrito)",Actions:"Ações",Choose:"Escolher",Close:"Fechar",Next:"Seguinte","No results":"Sem resultados","Pause slideshow":"Pausar diaporama",Previous:"Anterior","Select a tag":"Selecionar uma etiqueta",Settings:"Definições","Start slideshow":"Iniciar diaporama","Unable to search the group":"Não é possível pesquisar o grupo"}},{locale:"ro",translations:{"{tag} (invisible)":"{tag} (invizibil)","{tag} (restricted)":"{tag} (restricționat)",Actions:"Acțiuni",Activities:"Activități","Animals & Nature":"Animale și natură","Anything shared with the same group of people will show up here":"Tot ceea ce este partajat cu același grup de persoane va fi afișat aici","Avatar of {displayName}":"Avatarul lui {displayName}","Avatar of {displayName}, {status}":"Avatarul lui {displayName}, {status}","Cancel changes":"Anulează modificările","Change title":"Modificați titlul",Choose:"Alegeți","Clear text":"Șterge textul",Close:"Închideți","Close modal":"Închideți modulul","Close navigation":"Închideți navigarea","Close sidebar":"Închide bara laterală","Confirm changes":"Confirmați modificările",Custom:"Personalizat","Edit item":"Editați elementul","Error getting related resources":" Eroare la returnarea resurselor legate","Error parsing svg":"Eroare de analizare a svg","External documentation for {title}":"Documentație externă pentru {title}",Favorite:"Favorit",Flags:"Marcaje","Food & Drink":"Alimente și băuturi","Frequently used":"Utilizate frecvent",Global:"Global","Go back to the list":"Întoarceți-vă la listă","Hide password":"Ascunde parola","Message limit of {count} characters reached":"Limita mesajului de {count} caractere a fost atinsă","More items …":"Mai multe articole ...",Next:"Următorul","No emoji found":"Nu s-a găsit niciun emoji","No results":"Nu există rezultate",Objects:"Obiecte",Open:"Deschideți",'Open link to "{resourceTitle}"':'Deschide legătura la "{resourceTitle}"',"Open navigation":"Deschideți navigația","Password is secure":"Parola este sigură","Pause slideshow":"Pauză prezentare de diapozitive","People & Body":"Oameni și corp","Pick an emoji":"Alege un emoji","Please select a time zone:":"Vă rugăm să selectați un fus orar:",Previous:"Anterior","Related resources":"Resurse legate",Search:"Căutare","Search results":"Rezultatele căutării","Select a tag":"Selectați o etichetă",Settings:"Setări","Settings navigation":"Navigare setări","Show password":"Arată parola","Smileys & Emotion":"Zâmbete și emoții","Start slideshow":"Începeți prezentarea de diapozitive",Submit:"Trimiteți",Symbols:"Simboluri","Travel & Places":"Călătorii și locuri","Type to search time zone":"Tastați pentru a căuta fusul orar","Unable to search the group":"Imposibilitatea de a căuta în grup","Undo changes":"Anularea modificărilor",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Scrie un mesaj, folosește "@" pentru a menționa pe cineva, folosește ":" pentru autocompletarea cu emoji ...'}},{locale:"ru",translations:{"{tag} (invisible)":"{tag} (невидимое)","{tag} (restricted)":"{tag} (ограниченное)",Actions:"Действия ",Activities:"События","Animals & Nature":"Животные и природа ","Avatar of {displayName}":"Аватар {displayName}","Avatar of {displayName}, {status}":"Фотография {displayName}, {status}","Cancel changes":"Отменить изменения",Choose:"Выберите",Close:"Закрыть","Close modal":"Закрыть модальное окно","Close navigation":"Закрыть навигацию","Confirm changes":"Подтвердить изменения",Custom:"Пользовательское","Edit item":"Изменить элемент","External documentation for {title}":"Внешняя документация для {title}",Flags:"Флаги","Food & Drink":"Еда, напиток","Frequently used":"Часто используемый",Global:"Глобальный","Go back to the list":"Вернуться к списку",items:"элементов","Message limit of {count} characters reached":"Достигнуто ограничение на количество символов в {count}","More {dashboardItemType} …":"Больше {dashboardItemType} …",Next:"Следующее","No emoji found":"Эмодзи не найдено","No results":"Результаты отсуствуют",Objects:"Объекты",Open:"Открыть","Open navigation":"Открыть навигацию","Pause slideshow":"Приостановить показ слйдов","People & Body":"Люди и тело","Pick an emoji":"Выберите эмодзи","Please select a time zone:":"Пожалуйста, выберите часовой пояс:",Previous:"Предыдущее",Search:"Поиск","Search results":"Результаты поиска","Select a tag":"Выберите метку",Settings:"Параметры","Settings navigation":"Навигация по настройкам","Smileys & Emotion":"Смайлики и эмоции","Start slideshow":"Начать показ слайдов",Submit:"Утвердить",Symbols:"Символы","Travel & Places":"Путешествия и места","Type to search time zone":"Введите для поиска часового пояса","Unable to search the group":"Невозможно найти группу","Undo changes":"Отменить изменения","Write message, @ to mention someone, : for emoji autocompletion …":"Напишите сообщение, @ - чтобы упомянуть кого-то, : - для автозаполнения эмодзи …"}},{locale:"sk_SK",translations:{"{tag} (invisible)":"{tag} (neviditeľný)","{tag} (restricted)":"{tag} (obmedzený)",Actions:"Akcie",Activities:"Aktivity","Animals & Nature":"Zvieratá a príroda","Avatar of {displayName}":"Avatar {displayName}","Avatar of {displayName}, {status}":"Avatar {displayName}, {status}","Cancel changes":"Zrušiť zmeny",Choose:"Vybrať",Close:"Zatvoriť","Close navigation":"Zavrieť navigáciu","Confirm changes":"Potvrdiť zmeny",Custom:"Zvyk","Edit item":"Upraviť položku","External documentation for {title}":"Externá dokumentácia pre {title}",Flags:"Vlajky","Food & Drink":"Jedlo a nápoje","Frequently used":"Často používané",Global:"Globálne","Go back to the list":"Naspäť na zoznam","Message limit of {count} characters reached":"Limit správy na {count} znakov dosiahnutý",Next:"Ďalší","No emoji found":"Nenašli sa žiadne emodži","No results":"Žiadne výsledky",Objects:"Objekty","Open navigation":"Otvoriť navigáciu","Pause slideshow":"Pozastaviť prezentáciu","People & Body":"Ľudia a telo","Pick an emoji":"Vyberte si emodži","Please select a time zone:":"Prosím vyberte časovú zónu:",Previous:"Predchádzajúci",Search:"Hľadať","Search results":"Výsledky vyhľadávania","Select a tag":"Vybrať štítok",Settings:"Nastavenia","Settings navigation":"Navigácia v nastaveniach","Smileys & Emotion":"Smajlíky a emócie","Start slideshow":"Začať prezentáciu",Submit:"Odoslať",Symbols:"Symboly","Travel & Places":"Cestovanie a miesta","Type to search time zone":"Začníte písať pre vyhľadávanie časovej zóny","Unable to search the group":"Skupinu sa nepodarilo nájsť","Undo changes":"Vrátiť zmeny","Write message, @ to mention someone, : for emoji autocompletion …":"Napíšte správu, @ ak chcete niekoho spomenúť, : pre automatické dopĺňanie emotikonov…"}},{locale:"sl",translations:{"{tag} (invisible)":"{tag} (nevidno)","{tag} (restricted)":"{tag} (omejeno)",Actions:"Dejanja",Activities:"Dejavnosti","Animals & Nature":"Živali in Narava","Avatar of {displayName}":"Podoba {displayName}","Avatar of {displayName}, {status}":"Prikazna slika {displayName}, {status}","Cancel changes":"Prekliči spremembe","Change title":"Spremeni naziv",Choose:"Izbor","Clear text":"Počisti besedilo",Close:"Zapri","Close modal":"Zapri pojavno okno","Close navigation":"Zapri krmarjenje","Close sidebar":"Zapri stransko vrstico","Confirm changes":"Potrdi spremembe",Custom:"Po meri","Edit item":"Uredi predmet","Error getting related resources":"Napaka pridobivanja povezanih virov","External documentation for {title}":"Zunanja dokumentacija za {title}",Favorite:"Priljubljeno",Flags:"Zastavice","Food & Drink":"Hrana in Pijača","Frequently used":"Pogostost uporabe",Global:"Splošno","Go back to the list":"Vrni se na seznam","Hide password":"Skrij geslo","Message limit of {count} characters reached":"Dosežena omejitev {count} znakov na sporočilo.","More items …":"Več predmetov ...",Next:"Naslednji","No emoji found":"Ni najdenih izraznih ikon","No results":"Ni zadetkov",Objects:"Predmeti",Open:"Odpri",'Open link to "{resourceTitle}"':"Odpri povezavo do »{resourceTitle}«","Open navigation":"Odpri krmarjenje","Password is secure":"Geslo je varno","Pause slideshow":"Ustavi predstavitev","People & Body":"Ljudje in Telo","Pick a date":"Izbor datuma","Pick a date and a time":"Izbor datuma in časa","Pick a month":"Izbor meseca","Pick a time":"Izbor časa","Pick a week":"Izbor tedna","Pick a year":"Izbor leta","Pick an emoji":"Izbor izrazne ikone","Please select a time zone:":"Izbor časovnega pasu:",Previous:"Predhodni","Related resources":"Povezani viri",Search:"Iskanje","Search results":"Zadetki iskanja","Select a tag":"Izbor oznake",Settings:"Nastavitve","Settings navigation":"Krmarjenje nastavitev","Show password":"Pokaži geslo","Smileys & Emotion":"Izrazne ikone","Start slideshow":"Začni predstavitev",Submit:"Pošlji",Symbols:"Simboli","Travel & Places":"Potovanja in Kraji","Type to search time zone":"Vpišite niz za iskanje časovnega pasu","Unable to search the group":"Ni mogoče iskati po skupini","Undo changes":"Razveljavi spremembe","Write message, @ to mention someone, : for emoji autocompletion …":"Napišite sporočilo, za omembo pred ime postavite@, začnite z : za vstavljanje izraznih ikon …"}},{locale:"sr",translations:{"{tag} (invisible)":"{tag} (nevidljivo)","{tag} (restricted)":"{tag} (ograničeno)",Actions:"Radnje",Activities:"Aktivnosti","Animals & Nature":"Životinje i Priroda","Avatar of {displayName}":"Avatar za {displayName}","Avatar of {displayName}, {status}":"Avatar za {displayName}, {status}","Cancel changes":"Otkaži izmene","Change title":"Izmeni naziv",Choose:"Изаберите",Close:"Затвори","Close modal":"Zatvori modal","Close navigation":"Zatvori navigaciju","Close sidebar":"Zatvori bočnu traku","Confirm changes":"Potvrdite promene",Custom:"Po meri","Edit item":"Uredi stavku","External documentation for {title}":"Eksterna dokumentacija za {title}",Favorite:"Omiljeni",Flags:"Zastave","Food & Drink":"Hrana i Piće","Frequently used":"Često korišćeno",Global:"Globalno","Go back to the list":"Natrag na listu",items:"stavke","Message limit of {count} characters reached":"Dostignuto je ograničenje za poruke od {count} znakova","More {dashboardItemType} …":"Više  {dashboardItemType} …",Next:"Следеће","No emoji found":"Nije pronađen nijedan emodži","No results":"Нема резултата",Objects:"Objekti",Open:"Otvori","Open navigation":"Otvori navigaciju","Pause slideshow":"Паузирај слајд шоу","People & Body":"Ljudi i Telo","Pick an emoji":"Izaberi emodži","Please select a time zone:":"Molimo izaberite vremensku zonu:",Previous:"Претходно",Search:"Pretraži","Search results":"Rezultati pretrage","Select a tag":"Изаберите ознаку",Settings:"Поставке","Settings navigation":"Navigacija u podešavanjima","Smileys & Emotion":"Smajli i Emocije","Start slideshow":"Покрени слајд шоу",Submit:"Prihvati",Symbols:"Simboli","Travel & Places":"Putovanja i Mesta","Type to search time zone":"Ukucaj da pretražiš vremenske zone","Unable to search the group":"Nije moguće pretražiti grupu","Undo changes":"Poništi promene","Write message, @ to mention someone, : for emoji autocompletion …":"Napišite poruku, @ da pomenete nekoga, : za automatsko dovršavanje emodžija…"}},{locale:"sv",translations:{"{tag} (invisible)":"{tag} (osynlig)","{tag} (restricted)":"{tag} (begränsad)",Actions:"Åtgärder",Activities:"Aktiviteter","Animals & Nature":"Djur & Natur","Anything shared with the same group of people will show up here":"Något som delats med samma grupp av personer kommer att visas här","Avatar of {displayName}":"{displayName}s avatar","Avatar of {displayName}, {status}":"{displayName}s avatar, {status}","Cancel changes":"Avbryt ändringar","Change title":"Ändra titel",Choose:"Välj","Clear text":"Ta bort text",Close:"Stäng","Close modal":"Stäng modal","Close navigation":"Stäng navigering","Close sidebar":"Stäng sidopanel","Confirm changes":"Bekräfta ändringar",Custom:"Anpassad","Edit item":"Ändra","Error getting related resources":"Problem att hämta relaterade resurser","Error parsing svg":"Fel vid inläsning av svg","External documentation for {title}":"Extern dokumentation för {title}",Favorite:"Favorit",Flags:"Flaggor","Food & Drink":"Mat & Dryck","Frequently used":"Används ofta",Global:"Global","Go back to the list":"Gå tillbaka till listan","Hide password":"Göm lössenordet","Message limit of {count} characters reached":"Meddelandegräns {count} tecken används","More items …":"Fler objekt",Next:"Nästa","No emoji found":"Hittade inga emojis","No results":"Inga resultat",Objects:"Objekt",Open:"Öppna",'Open link to "{resourceTitle}"':'Öppna länk till "{resourceTitle}"',"Open navigation":"Öppna navigering","Password is secure":"Lössenordet är säkert","Pause slideshow":"Pausa bildspelet","People & Body":"Kropp & Själ","Pick an emoji":"Välj en emoji","Please select a time zone:":"Välj tidszon:",Previous:"Föregående","Related resources":"Relaterade resurser",Search:"Sök","Search results":"Sökresultat","Select a tag":"Välj en tag",Settings:"Inställningar","Settings navigation":"Inställningsmeny","Show password":"Visa lössenordet","Smileys & Emotion":"Selfies & Känslor","Start slideshow":"Starta bildspelet",Submit:"Skicka",Symbols:"Symboler","Travel & Places":"Resor & Sevärdigheter","Type to search time zone":"Skriv för att välja tidszon","Unable to search the group":"Kunde inte söka i gruppen","Undo changes":"Ångra ändringar",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'Skriv meddelande, använd "@" för att nämna någon, använd ":" för automatiska emojiförslag ...'}},{locale:"tr",translations:{"{tag} (invisible)":"{tag} (görünmez)","{tag} (restricted)":"{tag} (kısıtlı)",Actions:"İşlemler",Activities:"Etkinlikler","Animals & Nature":"Hayvanlar ve Doğa","Anything shared with the same group of people will show up here":"Aynı kişi grubu ile paylaşılan herşey burada görüntülenir","Avatar of {displayName}":"{displayName} avatarı","Avatar of {displayName}, {status}":"{displayName}, {status} avatarı","Cancel changes":"Değişiklikleri iptal et","Change title":"Başlığı değiştir",Choose:"Seçin","Clear text":"Metni temizle",Close:"Kapat","Close modal":"Üste açılan pencereyi kapat","Close navigation":"Gezinmeyi kapat","Close sidebar":"Yan çubuğu kapat","Confirm changes":"Değişiklikleri onayla",Custom:"Özel","Edit item":"Ögeyi düzenle","Error getting related resources":"İlgili kaynaklar alınırken sorun çıktı","Error parsing svg":"svg işlenirken sorun çıktı","External documentation for {title}":"{title} için dış belgeler",Favorite:"Sık kullanılanlara ekle",Flags:"Bayraklar","Food & Drink":"Yeme ve İçme","Frequently used":"Sık kullanılanlar",Global:"Evrensel","Go back to the list":"Listeye dön","Hide password":"Parolayı gizle","Message limit of {count} characters reached":"{count} karakter ileti sınırına ulaşıldı","More items …":"Diğer ögeler…",Next:"Sonraki","No emoji found":"Herhangi bir emoji bulunamadı","No results":"Herhangi bir sonuç bulunamadı",Objects:"Nesneler",Open:"Aç",'Open link to "{resourceTitle}"':'"{resourceTitle}" bağlantısını aç',"Open navigation":"Gezinmeyi aç","Password is secure":"Parola güvenli","Pause slideshow":"Slayt sunumunu duraklat","People & Body":"İnsanlar ve Beden","Pick an emoji":"Bir emoji seçin","Please select a time zone:":"Lütfen bir saat dilimi seçin:",Previous:"Önceki","Related resources":"İlgili kaynaklar",Search:"Arama","Search results":"Arama sonuçları","Select a tag":"Bir etiket seçin",Settings:"Ayarlar","Settings navigation":"Gezinme ayarları","Show password":"Parolayı görüntüle","Smileys & Emotion":"İfadeler ve Duygular","Start slideshow":"Slayt sunumunu başlat",Submit:"Gönder",Symbols:"Simgeler","Travel & Places":"Gezi ve Yerler","Type to search time zone":"Saat dilimi aramak için yazmaya başlayın","Unable to search the group":"Grupta arama yapılamadı","Undo changes":"Değişiklikleri geri al",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'İleti yazın, birini anmak için @, otomatik emoji tamamlamak için ":" kullanın…'}},{locale:"uk",translations:{"{tag} (invisible)":"{tag} (невидимий)","{tag} (restricted)":"{tag} (обмежений)",Actions:"Дії",Activities:"Діяльність","Animals & Nature":"Тварини та природа","Avatar of {displayName}":"Аватар {displayName}","Avatar of {displayName}, {status}":"Аватар {displayName}, {status}","Cancel changes":"Скасувати зміни","Change title":"Змінити назву",Choose:"ВиберітьВиберіть","Clear text":"Очистити текст",Close:"Закрити","Close modal":"Закрити модаль","Close navigation":"Закрити навігацію","Close sidebar":"Закрити бічну панель","Confirm changes":"Підтвердити зміни",Custom:"Власне","Edit item":"Редагувати елемент","External documentation for {title}":"Зовнішня документація для {title}",Favorite:"Улюблений",Flags:"Прапори","Food & Drink":"Їжа та напої","Frequently used":"Найчастіші",Global:"Глобальний","Go back to the list":"Повернутися до списку","Hide password":"Приховати пароль",items:"елементи","Message limit of {count} characters reached":"Вичерпано ліміт у {count} символів для повідомлення","More {dashboardItemType} …":"Більше {dashboardItemType}…",Next:"Вперед","No emoji found":"Емоційки відсутні","No results":"Відсутні результати",Objects:"Об'єкти",Open:"Відкрити","Open navigation":"Відкрити навігацію","Password is secure":"Пароль безпечний","Pause slideshow":"Пауза у показі слайдів","People & Body":"Люди та жести","Pick an emoji":"Виберіть емоційку","Please select a time zone:":"Виберіть часовий пояс:",Previous:"Назад",Search:"Пошук","Search results":"Результати пошуку","Select a tag":"Виберіть позначку",Settings:"Налаштування","Settings navigation":"Навігація у налаштуваннях","Show password":"Показати пароль","Smileys & Emotion":"Смайли та емоції","Start slideshow":"Почати показ слайдів",Submit:"Надіслати",Symbols:"Символи","Travel & Places":"Поїздки та місця","Type to search time zone":"Введіть для пошуку часовий пояс","Unable to search the group":"Неможливо шукати в групі","Undo changes":"Скасувати зміни","Write message, @ to mention someone, : for emoji autocompletion …":"Напишіть повідомлення, @, щоб згадати когось, : для автозаповнення емодзі…"}},{locale:"zh_CN",translations:{"{tag} (invisible)":"{tag} （不可见）","{tag} (restricted)":"{tag} （受限）",Actions:"行为",Activities:"活动","Animals & Nature":"动物 & 自然","Anything shared with the same group of people will show up here":"与同组用户分享的所有内容都会显示于此","Avatar of {displayName}":"{displayName}的头像","Avatar of {displayName}, {status}":"{displayName}的头像，{status}","Cancel changes":"取消更改","Change title":"更改标题",Choose:"选择","Clear text":"清除文本",Close:"关闭","Close modal":"关闭窗口","Close navigation":"关闭导航","Close sidebar":"关闭侧边栏","Confirm changes":"确认更改",Custom:"自定义","Edit item":"编辑项目","Error getting related resources":"获取相关资源时出错","Error parsing svg":"解析 svg 时出错","External documentation for {title}":"{title}的外部文档",Favorite:"喜爱",Flags:"旗帜","Food & Drink":"食物 & 饮品","Frequently used":"经常使用",Global:"全局","Go back to the list":"返回至列表","Hide password":"隐藏密码","Message limit of {count} characters reached":"已达到 {count} 个字符的消息限制","More items …":"更多项目…",Next:"下一个","No emoji found":"表情未找到","No results":"无结果",Objects:"物体",Open:"打开",'Open link to "{resourceTitle}"':'打开"{resourceTitle}"的连接',"Open navigation":"开启导航","Password is secure":"密码安全","Pause slideshow":"暂停幻灯片","People & Body":"人 & 身体","Pick an emoji":"选择一个表情","Please select a time zone:":"请选择一个时区：",Previous:"上一个","Related resources":"相关资源",Search:"搜索","Search results":"搜索结果","Select a tag":"选择一个标签",Settings:"设置","Settings navigation":"设置向导","Show password":"显示密码","Smileys & Emotion":"笑脸 & 情感","Start slideshow":"开始幻灯片",Submit:"提交",Symbols:"符号","Travel & Places":"旅游 & 地点","Type to search time zone":"打字以搜索时区","Unable to search the group":"无法搜索分组","Undo changes":"撤销更改",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'写信息，使用"@"来提及某人，使用":"进行表情符号自动完成 ...'}},{locale:"zh_HK",translations:{"{tag} (invisible)":"{tag} (隱藏)","{tag} (restricted)":"{tag} (受限)",Actions:"動作",Activities:"活動","Animals & Nature":"動物與自然","Anything shared with the same group of people will show up here":"與同一組人共享的任何內容都會顯示在此處","Avatar of {displayName}":"{displayName} 的頭像","Avatar of {displayName}, {status}":"{displayName} 的頭像，{status}","Cancel changes":"取消更改","Change title":"更改標題",Choose:"選擇","Clear text":"清除文本",Close:"關閉","Close modal":"關閉模態","Close navigation":"關閉導航","Close sidebar":"關閉側邊欄","Confirm changes":"確認更改",Custom:"自定義","Edit item":"編輯項目","Error getting related resources":"獲取相關資源出錯","Error parsing svg":"解析 svg 時出錯","External documentation for {title}":"{title} 的外部文檔",Favorite:"喜愛",Flags:"旗幟","Food & Drink":"食物與飲料","Frequently used":"經常使用",Global:"全球的","Go back to the list":"返回清單","Hide password":"隱藏密碼","Message limit of {count} characters reached":"已達到訊息最多 {count} 字元限制","More items …":"更多項目 …",Next:"下一個","No emoji found":"未找到表情符號","No results":"無結果",Objects:"物件",Open:"打開",'Open link to "{resourceTitle}"':"打開指向 “{resourceTitle}” 的鏈結","Open navigation":"開啟導航","Password is secure":"密碼是安全的","Pause slideshow":"暫停幻燈片","People & Body":"人物","Pick an emoji":"選擇表情符號","Please select a time zone:":"請選擇時區：",Previous:"上一個","Related resources":"相關資源",Search:"搜尋","Search results":"搜尋結果","Select a tag":"選擇標籤",Settings:"設定","Settings navigation":"設定值導覽","Show password":"顯示密碼","Smileys & Emotion":"表情","Start slideshow":"開始幻燈片",Submit:"提交",Symbols:"標誌","Travel & Places":"旅遊與景點","Type to search time zone":"鍵入以搜索時區","Unable to search the group":"無法搜尋群組","Undo changes":"取消更改",'Write message, use "@" to mention someone, use ":" for emoji autocompletion …':'寫訊息，使用 "@" 來指代某人，使用 ":" 用於表情符號自動填充 ...'}},{locale:"zh_TW",translations:{"{tag} (invisible)":"{tag} (隱藏)","{tag} (restricted)":"{tag} (受限)",Actions:"動作",Activities:"活動","Animals & Nature":"動物與自然",Choose:"選擇",Close:"關閉",Custom:"自定義",Flags:"旗幟","Food & Drink":"食物與飲料","Frequently used":"最近使用","Message limit of {count} characters reached":"已達到訊息最多 {count} 字元限制",Next:"下一個","No emoji found":"未找到表情符號","No results":"無結果",Objects:"物件","Pause slideshow":"暫停幻燈片","People & Body":"人物","Pick an emoji":"選擇表情符號",Previous:"上一個",Search:"搜尋","Search results":"搜尋結果","Select a tag":"選擇標籤",Settings:"設定","Settings navigation":"設定值導覽","Smileys & Emotion":"表情","Start slideshow":"開始幻燈片",Symbols:"標誌","Travel & Places":"旅遊與景點","Unable to search the group":"無法搜尋群組","Write message, @ to mention someone …":"輸入訊息時可使用 @ 來標示某人..."}}].forEach((e=>{const t={};for(const a in e.translations)e.translations[a].pluralId?t[a]={msgid:a,msgid_plural:e.translations[a].pluralId,msgstr:e.translations[a].msgstr}:t[a]={msgid:a,msgstr:[e.translations[a]]};o.addTranslation(e.locale,{translations:{"":t}})}));const i=o.build(),r=i.ngettext.bind(i),s=i.gettext.bind(i)},723:(e,t,a)=>{"use strict";a.d(t,{Z:()=>i});var n=a(2734),o=a.n(n);const i={before(){this.$slots.default&&""!==this.text.trim()||(o().util.warn("".concat(this.$options.name," cannot be empty and requires a meaningful text content"),this),this.$destroy(),this.$el.remove())},beforeUpdate(){this.text=this.getText()},data(){return{text:this.getText()}},computed:{isLongText(){return this.text&&this.text.trim().length>20}},methods:{getText(){return this.$slots.default?this.$slots.default[0].text.trim():""}}}},1139:(e,t,a)=>{"use strict";a.d(t,{Z:()=>i});var n=a(723);const o=function(e,t){let a=e.$parent;for(;a;){if(a.$options.name===t)return a;a=a.$parent}},i={mixins:[n.Z],props:{icon:{type:String,default:""},name:{type:String,default:""},title:{type:String,default:""},closeAfterClick:{type:Boolean,default:!1},ariaLabel:{type:String,default:""},ariaHidden:{type:Boolean,default:null}},emits:["click"],computed:{isIconUrl(){try{return new URL(this.icon)}catch(e){return!1}}},methods:{onClick(e){if(this.$emit("click",e),this.closeAfterClick){const e=o(this,"NcActions");e&&e.closeMenu&&e.closeMenu(!1)}}}}},6730:()=>{"use strict"},3351:(e,t,a)=>{"use strict";a.d(t,{iQ:()=>l});a(6730),a(8136),a(334),a(3132);var n=a(3607),o=a(768),i=a.n(o);const r=__webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.js");var s=a(4262);const l={data:()=>({hasStatus:!1,userStatus:{status:null,message:null,icon:null}}),methods:{async fetchUserStatus(e){if(!e)return;const t=(0,r.getCapabilities)();if(Object.prototype.hasOwnProperty.call(t,"user_status")&&t.user_status.enabled&&(0,n.getCurrentUser)())try{const{data:t}=await i().get((0,s.generateOcsUrl)("apps/user_status/api/v1/statuses/{userId}",{userId:e})),{status:a,message:n,icon:o}=t.ocs.data;this.userStatus.status=a,this.userStatus.message=n||"",this.userStatus.icon=o||"",this.hasStatus=!0}catch(e){var a,o;if(404===e.response.status&&0===(null===(a=e.response.data.ocs)||void 0===a||null===(o=a.data)||void 0===o?void 0:o.length))return;console.error(e)}}}}},8136:()=>{"use strict"},334:(e,t,a)=>{"use strict";var n=a(2734);new(a.n(n)())({data:()=>({isMobile:!1}),watch:{isMobile(e){this.$emit("changed",e)}},created(){window.addEventListener("resize",this.handleWindowResize),this.handleWindowResize()},beforeDestroy(){window.removeEventListener("resize",this.handleWindowResize)},methods:{handleWindowResize(){this.isMobile=document.documentElement.clientWidth<1024}}})},3648:(e,t,a)=>{"use strict";a.d(t,{Z:()=>o});var n=a(932);const o={methods:{n:n.n,t:n.t}}},3132:(e,t,a)=>{"use strict";a(3330),a(1390);__webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");__webpack_require__(/*! striptags */ "./node_modules/striptags/src/striptags.js");a(2734);const n="(?:^|\\s)",o="(?:[^a-z]|$)";new RegExp("".concat(n,"(@[a-zA-Z0-9_.@\\-']+)(").concat(o,")"),"gi"),new RegExp("".concat(n,"(@&quot;[a-zA-Z0-9 _.@\\-']+&quot;)(").concat(o,")"),"gi")},1336:(e,t,a)=>{"use strict";a.d(t,{Z:()=>n});const n=(e,t)=>{const a=[];let n=0,o=e.toLowerCase().indexOf(t.toLowerCase(),n),i=0;for(;o>-1&&i<e.length;)n=o+t.length,a.push({start:o,end:n}),o=e.toLowerCase().indexOf(t.toLowerCase(),n),i++;return a}},6609:(e,t,a)=>{"use strict";function n(e,t,a){this.r=e,this.g=t,this.b=a}function o(e,t,a){const o=[];o.push(t);const i=function(e,t){const a=new Array(3);return a[0]=(t[1].r-t[0].r)/e,a[1]=(t[1].g-t[0].g)/e,a[2]=(t[1].b-t[0].b)/e,a}(e,[t,a]);for(let a=1;a<e;a++){const e=parseInt(t.r+i[0]*a,10),r=parseInt(t.g+i[1]*a,10),s=parseInt(t.b+i[2]*a,10);o.push(new n(e,r,s))}return o}a.d(t,{Z:()=>i});const i=function(e){e||(e=6);const t=new n(182,70,157),a=new n(221,203,85),i=new n(0,130,201),r=o(e,t,a),s=o(e,a,i),l=o(e,i,t);return r.concat(s).concat(l)}},1205:(e,t,a)=>{"use strict";a.d(t,{Z:()=>n});const n=e=>Math.random().toString(36).replace(/[^a-z]+/g,"").slice(0,e||5)},1390:(e,t,a)=>{"use strict";a.d(t,{Z:()=>i});const n=__webpack_require__(/*! linkify-string */ "./node_modules/linkify-string/dist/linkify-string.es.js");var o=a.n(n);const i=e=>o()(e,{defaultProtocol:"https",target:"_blank",className:"external linkified",attributes:{rel:"nofollow noopener noreferrer"}})},1206:(e,t,a)=>{"use strict";a.d(t,{L:()=>n});a(4505);const n=function(){return Object.assign(window,{_nc_focus_trap:window._nc_focus_trap||[]}),window._nc_focus_trap}},3490:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon[data-v-63ee0e66]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}li.active[data-v-63ee0e66]{background-color:var(--color-background-hover);border-radius:6px;padding:0}.action-link[data-v-63ee0e66]{display:flex;align-items:flex-start;width:100%;height:auto;margin:0;padding:0;padding-right:14px;box-sizing:border-box;cursor:pointer;white-space:nowrap;color:var(--color-main-text);border:0;border-radius:0;background-color:rgba(0,0,0,0);box-shadow:none;font-weight:normal;font-size:var(--default-font-size);line-height:44px}.action-link>span[data-v-63ee0e66]{cursor:pointer;white-space:nowrap}.action-link__icon[data-v-63ee0e66]{width:44px;height:44px;opacity:1;background-position:14px center;background-size:16px;background-repeat:no-repeat}.action-link[data-v-63ee0e66] .material-design-icon{width:44px;height:44px;opacity:1}.action-link[data-v-63ee0e66] .material-design-icon .material-design-icon__svg{vertical-align:middle}.action-link p[data-v-63ee0e66]{max-width:220px;line-height:1.6em;padding:10.8px 0;cursor:pointer;text-align:left;overflow:hidden;text-overflow:ellipsis}.action-link__longtext[data-v-63ee0e66]{cursor:pointer;white-space:pre-wrap}.action-link__name[data-v-63ee0e66]{font-weight:bold;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;max-width:100%;display:inline-block}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/assets/action.scss","webpack://./src/assets/variables.scss"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCiBC,2BACC,8CAAA,CACA,iBAAA,CACA,SAAA,CAqBF,8BACC,YAAA,CACA,sBAAA,CAEA,UAAA,CACA,WAAA,CACA,QAAA,CACA,SAAA,CACA,kBCxBY,CDyBZ,qBAAA,CAEA,cAAA,CACA,kBAAA,CAEA,4BAAA,CACA,QAAA,CACA,eAAA,CACA,8BAAA,CACA,eAAA,CAEA,kBAAA,CACA,kCAAA,CACA,gBC9Ce,CDgDf,mCACC,cAAA,CACA,kBAAA,CAGD,oCACC,UCtDc,CDuDd,WCvDc,CDwDd,SCrCY,CDsCZ,+BAAA,CACA,oBCtDS,CDuDT,2BAAA,CAGD,oDACC,UC/Dc,CDgEd,WChEc,CDiEd,SC9CY,CDgDZ,+EACC,qBAAA,CAKF,gCACC,eAAA,CACA,iBAAA,CAGA,gBAAA,CAEA,cAAA,CACA,eAAA,CAGA,eAAA,CACA,sBAAA,CAGD,wCACC,cAAA,CAEA,oBAAA,CAGD,oCACC,gBAAA,CACA,sBAAA,CACA,eAAA,CACA,kBAAA,CACA,cAAA,CACA,oBAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","/**\n * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @author John Molakvoæ <skjnldsv@protonmail.com>\n * @author Marco Ambrosini <marcoambrosini@icloud.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n\n@mixin action-active {\n\tli {\n\t\t&.active {\n\t\t\tbackground-color: var(--color-background-hover);\n\t\t\tborder-radius: 6px;\n\t\t\tpadding: 0;\n\t\t}\n\t}\n}\n\n@mixin action--disabled {\n\t.action--disabled {\n\t\tpointer-events: none;\n\t\topacity: $opacity_disabled;\n\t\t&:hover, &:focus {\n\t\t\tcursor: default;\n\t\t\topacity: $opacity_disabled;\n\t\t}\n\t\t& * {\n\t\t\topacity: 1 !important;\n\t\t}\n\t}\n}\n\n\n@mixin action-item($name) {\n\t.action-#{$name} {\n\t\tdisplay: flex;\n\t\talign-items: flex-start;\n\n\t\twidth: 100%;\n\t\theight: auto;\n\t\tmargin: 0;\n\t\tpadding: 0;\n\t\tpadding-right: $icon-margin;\n\t\tbox-sizing: border-box; // otherwise router-link overflows in Firefox\n\n\t\tcursor: pointer;\n\t\twhite-space: nowrap;\n\n\t\tcolor: var(--color-main-text);\n\t\tborder: 0;\n\t\tborder-radius: 0; // otherwise Safari will cut the border-radius area\n\t\tbackground-color: transparent;\n\t\tbox-shadow: none;\n\n\t\tfont-weight: normal;\n\t\tfont-size: var(--default-font-size);\n\t\tline-height: $clickable-area;\n\n\t\t& > span {\n\t\t\tcursor: pointer;\n\t\t\twhite-space: nowrap;\n\t\t}\n\n\t\t&__icon {\n\t\t\twidth: $clickable-area;\n\t\t\theight: $clickable-area;\n\t\t\topacity: $opacity_full;\n\t\t\tbackground-position: $icon-margin center;\n\t\t\tbackground-size: $icon-size;\n\t\t\tbackground-repeat: no-repeat;\n\t\t}\n\n\t\t&:deep(.material-design-icon) {\n\t\t\twidth: $clickable-area;\n\t\t\theight: $clickable-area;\n\t\t\topacity: $opacity_full;\n\n\t\t\t.material-design-icon__svg {\n\t\t\t\tvertical-align: middle;\n\t\t\t}\n\t\t}\n\n\t\t// long text area\n\t\tp {\n\t\t\tmax-width: 220px;\n\t\t\tline-height: 1.6em;\n\n\t\t\t// 14px are currently 1em line-height. Mixing units as '44px - 1.6em' does not work.\n\t\t\tpadding: #{math.div($clickable-area - 1.6 * 14px, 2)} 0;\n\n\t\t\tcursor: pointer;\n\t\t\ttext-align: left;\n\n\t\t\t// in case there are no spaces like long email addresses\n\t\t\toverflow: hidden;\n\t\t\ttext-overflow: ellipsis;\n\t\t}\n\n\t\t&__longtext {\n\t\t\tcursor: pointer;\n\t\t\t// allow the use of `\\n`\n\t\t\twhite-space: pre-wrap;\n\t\t}\n\n\t\t&__name {\n\t\t\tfont-weight: bold;\n\t\t\ttext-overflow: ellipsis;\n\t\t\toverflow: hidden;\n\t\t\twhite-space: nowrap;\n\t\t\tmax-width: 100%;\n\t\t\tdisplay: inline-block;\n\t\t}\n\t}\n}\n","/**\n * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @author John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n\n// https://uxplanet.org/7-rules-for-mobile-ui-button-design-e9cf2ea54556\n// recommended is 48px\n// 44px is what we choose and have very good visual-to-usability ratio\n$clickable-area: 44px;\n\n// background icon size\n// also used for the scss icon font\n$icon-size: 16px;\n\n// icon padding for a $clickable-area width and a $icon-size icon\n// ( 44px - 16px ) / 2\n$icon-margin: math.div($clickable-area - $icon-size, 2);\n\n// transparency background for icons\n$icon-focus-bg: rgba(127, 127, 127, .25);\n\n// popovermenu arrow width from the triangle center\n$arrow-width: 9px;\n\n// opacities\n$opacity_disabled: .5;\n$opacity_normal: .7;\n$opacity_full: 1;\n\n// menu round background hover feedback\n// good looking on dark AND white bg\n$action-background-hover: rgba(127, 127, 127, .25);\n\n// various structure data used in the \n// `AppNavigation` component\n$header-height: 50px;\n$navigation-width: 300px;\n\n// mobile breakpoint\n$breakpoint-mobile: 1024px;\n\n// top-bar spacing\n$topbar-margin: 4px;\n\n// navigation spacing\n$app-navigation-settings-margin: 3px;\n"],sourceRoot:""}]);const s=r},4825:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon[data-v-29452b76]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.action-items[data-v-29452b76]{display:flex;align-items:center}.action-items>button[data-v-29452b76]{margin-right:7px}.action-item[data-v-29452b76]{--open-background-color: var(--color-background-hover, $action-background-hover);position:relative;display:inline-block}.action-item.action-item--primary[data-v-29452b76]{--open-background-color: var(--color-primary-element-hover)}.action-item.action-item--secondary[data-v-29452b76]{--open-background-color: var(--color-primary-element-light-hover)}.action-item.action-item--error[data-v-29452b76]{--open-background-color: var(--color-error-hover)}.action-item.action-item--warning[data-v-29452b76]{--open-background-color: var(--color-warning-hover)}.action-item.action-item--success[data-v-29452b76]{--open-background-color: var(--color-success-hover)}.action-item.action-item--tertiary-no-background[data-v-29452b76]{--open-background-color: transparent}.action-item.action-item--open .action-item__menutoggle[data-v-29452b76]{background-color:var(--open-background-color)}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcActions/NcActions.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCLD,+BACC,YAAA,CACA,kBAAA,CAGA,sCACC,gBAAA,CAIF,8BACC,gFAAA,CACA,iBAAA,CACA,oBAAA,CAEA,mDACC,2DAAA,CAGD,qDACC,iEAAA,CAGD,iDACC,iDAAA,CAGD,mDACC,mDAAA,CAGD,mDACC,mDAAA,CAGD,kEACC,oCAAA,CAGD,yEACC,6CAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n// Inline buttons\n.action-items {\n\tdisplay: flex;\n\talign-items: center;\n\n\t// Spacing between buttons\n\t& > button {\n\t\tmargin-right: math.div($icon-margin, 2);\n\t}\n}\n\n.action-item {\n\t--open-background-color: var(--color-background-hover, $action-background-hover);\n\tposition: relative;\n\tdisplay: inline-block;\n\n\t&.action-item--primary {\n\t\t--open-background-color: var(--color-primary-element-hover);\n\t}\n\n\t&.action-item--secondary {\n\t\t--open-background-color: var(--color-primary-element-light-hover);\n\t}\n\n\t&.action-item--error {\n\t\t--open-background-color: var(--color-error-hover);\n\t}\n\n\t&.action-item--warning {\n\t\t--open-background-color: var(--color-warning-hover);\n\t}\n\n\t&.action-item--success {\n\t\t--open-background-color: var(--color-success-hover);\n\t}\n\n\t&.action-item--tertiary-no-background {\n\t\t--open-background-color: transparent;\n\t}\n\n\t&.action-item--open .action-item__menutoggle {\n\t\tbackground-color: var(--open-background-color);\n\t}\n}\n"],sourceRoot:""}]);const s=r},4946:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.v-popper--theme-dropdown.v-popper__popper.action-item__popper .v-popper__wrapper{border-radius:var(--border-radius-large);overflow:hidden}.v-popper--theme-dropdown.v-popper__popper.action-item__popper .v-popper__wrapper .v-popper__inner{border-radius:var(--border-radius-large);padding:4px;max-height:calc(50vh - 16px);overflow:auto}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcActions/NcActions.vue"],names:[],mappings:"AAGA,sBACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCJD,kFACC,wCAAA,CACA,eAAA,CAEA,mGACC,wCAAA,CACA,WAAA,CACA,4BAAA,CACA,aAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n// We overwrote the popover base class, so we can style\n// the popover__inner for actions only.\n.v-popper--theme-dropdown.v-popper__popper.action-item__popper .v-popper__wrapper {\n\tborder-radius: var(--border-radius-large);\n\toverflow:hidden;\n\n\t.v-popper__inner {\n\t\tborder-radius: var(--border-radius-large);\n\t\tpadding: 4px;\n\t\tmax-height: calc(50vh - 16px);\n\t\toverflow: auto;\n\t}\n}\n"],sourceRoot:""}]);const s=r},6222:(e,t,a)=>{"use strict";a.d(t,{Z:()=>h});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i),s=a(1667),l=a.n(s),c=new URL(a(3423),a.b),d=new URL(a(2605),a.b),u=new URL(a(7127),a.b),p=r()(o()),A=l()(c),m=l()(d),g=l()(u);p.push([e.id,`.material-design-icon[data-v-7de2f7ff]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.avatardiv[data-v-7de2f7ff]{position:relative;display:inline-block;width:var(--size);height:var(--size)}.avatardiv--unknown[data-v-7de2f7ff]{position:relative;background-color:var(--color-main-background)}.avatardiv[data-v-7de2f7ff]:not(.avatardiv--unknown){background-color:var(--color-main-background) !important;box-shadow:0 0 5px rgba(0,0,0,.05) inset}.avatardiv--with-menu[data-v-7de2f7ff]{cursor:pointer}.avatardiv--with-menu .action-item[data-v-7de2f7ff]{position:absolute;top:0;left:0}.avatardiv--with-menu[data-v-7de2f7ff] .action-item__menutoggle{cursor:pointer;opacity:0}.avatardiv--with-menu[data-v-7de2f7ff]:focus .action-item__menutoggle,.avatardiv--with-menu[data-v-7de2f7ff]:hover .action-item__menutoggle,.avatardiv--with-menu.avatardiv--with-menu-loading[data-v-7de2f7ff] .action-item__menutoggle{opacity:1}.avatardiv--with-menu:focus img[data-v-7de2f7ff],.avatardiv--with-menu:hover img[data-v-7de2f7ff],.avatardiv--with-menu.avatardiv--with-menu-loading img[data-v-7de2f7ff]{opacity:.3}.avatardiv--with-menu[data-v-7de2f7ff] .action-item__menutoggle,.avatardiv--with-menu img[data-v-7de2f7ff]{transition:opacity var(--animation-quick)}.avatardiv--with-menu[data-v-7de2f7ff]  .button-vue,.avatardiv--with-menu[data-v-7de2f7ff]  .button-vue__icon{height:var(--size);min-height:var(--size);width:var(--size) !important;min-width:var(--size)}.avatardiv .avatardiv__initials-wrapper[data-v-7de2f7ff]{height:var(--size);width:var(--size);background-color:var(--color-main-background);border-radius:50%}.avatardiv .avatardiv__initials-wrapper .unknown[data-v-7de2f7ff]{position:absolute;top:0;left:0;display:block;width:100%;text-align:center;font-weight:normal}.avatardiv img[data-v-7de2f7ff]{width:100%;height:100%;object-fit:cover}.avatardiv .material-design-icon[data-v-7de2f7ff]{width:var(--size);height:var(--size)}.avatardiv .avatardiv__user-status[data-v-7de2f7ff]{position:absolute;right:-4px;bottom:-4px;max-height:18px;max-width:18px;height:40%;width:40%;line-height:15px;font-size:var(--default-font-size);border:2px solid var(--color-main-background);background-color:var(--color-main-background);background-repeat:no-repeat;background-size:16px;background-position:center;border-radius:50%}.acli:hover .avatardiv .avatardiv__user-status[data-v-7de2f7ff]{border-color:var(--color-background-hover);background-color:var(--color-background-hover)}.acli.active .avatardiv .avatardiv__user-status[data-v-7de2f7ff]{border-color:var(--color-primary-element-light);background-color:var(--color-primary-element-light)}.avatardiv .avatardiv__user-status--online[data-v-7de2f7ff]{background-image:url(${A})}.avatardiv .avatardiv__user-status--dnd[data-v-7de2f7ff]{background-image:url(${m});background-color:#fff}.avatardiv .avatardiv__user-status--away[data-v-7de2f7ff]{background-image:url(${g})}.avatardiv .avatardiv__user-status--icon[data-v-7de2f7ff]{border:none;background-color:rgba(0,0,0,0)}.avatardiv .popovermenu-wrapper[data-v-7de2f7ff]{position:relative;display:inline-block}.avatar-class-icon[data-v-7de2f7ff]{border-radius:50%;background-color:var(--color-background-darker);height:100%}`,"",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcAvatar/NcAvatar.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,4BACC,iBAAA,CACA,oBAAA,CACA,iBAAA,CACA,kBAAA,CAEA,qCACC,iBAAA,CACA,6CAAA,CAGD,qDAEC,wDAAA,CACA,wCAAA,CAGD,uCACC,cAAA,CACA,oDACC,iBAAA,CACA,KAAA,CACA,MAAA,CAED,gEACC,cAAA,CACA,SAAA,CAKA,yOACC,SAAA,CAED,0KACC,UAAA,CAGF,2GAEC,yCAAA,CAGA,8GAEC,kBAAA,CACA,sBAAA,CACA,4BAAA,CACA,qBAAA,CAKH,yDACC,kBAAA,CACA,iBAAA,CACA,6CAAA,CACA,iBAAA,CAEA,kEACC,iBAAA,CACA,KAAA,CACA,MAAA,CACA,aAAA,CACA,UAAA,CACA,iBAAA,CACA,kBAAA,CAIF,gCAEC,UAAA,CACA,WAAA,CAEA,gBAAA,CAGD,kDACC,iBAAA,CACA,kBAAA,CAGD,oDACC,iBAAA,CACA,UAAA,CACA,WAAA,CACA,eAAA,CACA,cAAA,CACA,UAAA,CACA,SAAA,CACA,gBAAA,CACA,kCAAA,CACA,6CAAA,CACA,6CAAA,CACA,2BAAA,CACA,oBAAA,CACA,0BAAA,CACA,iBAAA,CAEA,gEACC,0CAAA,CACA,8CAAA,CAED,iEACC,+CAAA,CACA,mDAAA,CAGD,4DACC,wDAAA,CAED,yDACC,wDAAA,CACA,qBAAA,CAED,0DACC,wDAAA,CAED,0DACC,WAAA,CACA,8BAAA,CAIF,iDACC,iBAAA,CACA,oBAAA,CAIF,oCACC,iBAAA,CACA,+CAAA,CACA,WAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.avatardiv {\n\tposition: relative;\n\tdisplay: inline-block;\n\twidth: var(--size);\n\theight: var(--size);\n\n\t&--unknown {\n\t\tposition: relative;\n\t\tbackground-color: var(--color-main-background);\n\t}\n\n\t&:not(&--unknown) {\n\t\t// White/black background for avatars with transparency\n\t\tbackground-color: var(--color-main-background) !important;\n\t\tbox-shadow: 0 0 5px rgba(0, 0, 0, 0.05) inset;\n\t}\n\n\t&--with-menu {\n\t\tcursor: pointer;\n\t\t.action-item {\n\t\t\tposition: absolute;\n\t\t\ttop: 0;\n\t\t\tleft: 0;\n\t\t}\n\t\t:deep(.action-item__menutoggle) {\n\t\t\tcursor: pointer;\n\t\t\topacity: 0;\n\t\t}\n\t\t&:focus,\n\t\t&:hover,\n\t\t&#{&}-loading {\n\t\t\t:deep(.action-item__menutoggle) {\n\t\t\t\topacity: 1;\n\t\t\t}\n\t\t\timg {\n\t\t\t\topacity: 0.3;\n\t\t\t}\n\t\t}\n\t\t:deep(.action-item__menutoggle),\n\t\timg {\n\t\t\ttransition: opacity var(--animation-quick);\n\t\t}\n\t\t:deep() {\n\t\t\t.button-vue,\n\t\t\t.button-vue__icon {\n\t\t\t\theight: var(--size);\n\t\t\t\tmin-height: var(--size);\n\t\t\t\twidth: var(--size) !important;\n\t\t\t\tmin-width: var(--size);\n\t\t\t}\n\t\t}\n\t}\n\n\t.avatardiv__initials-wrapper {\n\t\theight: var(--size);\n\t\twidth: var(--size);\n\t\tbackground-color: var(--color-main-background);\n\t\tborder-radius: 50%;\n\n\t\t.unknown {\n\t\t\tposition: absolute;\n\t\t\ttop: 0;\n\t\t\tleft: 0;\n\t\t\tdisplay: block;\n\t\t\twidth: 100%;\n\t\t\ttext-align: center;\n\t\t\tfont-weight: normal;\n\t\t}\n\t}\n\n\timg {\n\t\t// Cover entire area\n\t\twidth: 100%;\n\t\theight: 100%;\n\t\t// Keep ratio\n\t\tobject-fit: cover;\n\t}\n\n\t.material-design-icon {\n\t\twidth: var(--size);\n\t\theight: var(--size);\n\t}\n\n\t.avatardiv__user-status {\n\t\tposition: absolute;\n\t\tright: -4px;\n\t\tbottom: -4px;\n\t\tmax-height: 18px;\n\t\tmax-width: 18px;\n\t\theight: 40%;\n\t\twidth: 40%;\n\t\tline-height: 15px;\n\t\tfont-size: var(--default-font-size);\n\t\tborder: 2px solid var(--color-main-background);\n\t\tbackground-color: var(--color-main-background);\n\t\tbackground-repeat: no-repeat;\n\t\tbackground-size: 16px;\n\t\tbackground-position: center;\n\t\tborder-radius: 50%;\n\n\t\t.acli:hover & {\n\t\t\tborder-color: var(--color-background-hover);\n\t\t\tbackground-color: var(--color-background-hover);\n\t\t}\n\t\t.acli.active & {\n\t\t\tborder-color: var(--color-primary-element-light);\n\t\t\tbackground-color: var(--color-primary-element-light);\n\t\t}\n\n\t\t&--online{\n\t\t\tbackground-image: url('../../assets/status-icons/user-status-online.svg');\n\t\t}\n\t\t&--dnd{\n\t\t\tbackground-image: url('../../assets/status-icons/user-status-dnd.svg');\n\t\t\tbackground-color: #ffffff;\n\t\t}\n\t\t&--away{\n\t\t\tbackground-image: url('../../assets/status-icons/user-status-away.svg');\n\t\t}\n\t\t&--icon {\n\t\t\tborder: none;\n\t\t\tbackground-color: transparent;\n\t\t}\n\t}\n\n\t.popovermenu-wrapper {\n\t\tposition: relative;\n\t\tdisplay: inline-block;\n\t}\n}\n\n.avatar-class-icon {\n\tborder-radius: 50%;\n\tbackground-color: var(--color-background-darker);\n\theight: 100%;\n}\n\n"],sourceRoot:""}]);const h=p},7196:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon[data-v-4d05be2c]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.button-vue[data-v-4d05be2c]{position:relative;width:fit-content;overflow:hidden;border:0;padding:0;font-size:var(--default-font-size);font-weight:bold;min-height:44px;min-width:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:22px;transition-property:color,border-color,background-color;transition-duration:.1s;transition-timing-function:linear;color:var(--color-primary-element-light-text);background-color:var(--color-primary-element-light)}.button-vue *[data-v-4d05be2c],.button-vue span[data-v-4d05be2c]{cursor:pointer}.button-vue[data-v-4d05be2c]:focus{outline:none}.button-vue[data-v-4d05be2c]:disabled{cursor:default;opacity:.5;filter:saturate(0.7)}.button-vue:disabled *[data-v-4d05be2c]{cursor:default}.button-vue[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-primary-element-light-hover)}.button-vue[data-v-4d05be2c]:active{background-color:var(--color-primary-element-light)}.button-vue__wrapper[data-v-4d05be2c]{display:inline-flex;align-items:center;justify-content:center;width:100%}.button-vue__icon[data-v-4d05be2c]{height:44px;width:44px;min-height:44px;min-width:44px;display:flex;justify-content:center;align-items:center}.button-vue__text[data-v-4d05be2c]{font-weight:bold;margin-bottom:1px;padding:2px 0;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}.button-vue--icon-only[data-v-4d05be2c]{width:44px !important}.button-vue--text-only[data-v-4d05be2c]{padding:0 12px}.button-vue--text-only .button-vue__text[data-v-4d05be2c]{margin-left:4px;margin-right:4px}.button-vue--icon-and-text[data-v-4d05be2c]{padding:0 16px 0 4px}.button-vue--wide[data-v-4d05be2c]{width:100%}.button-vue[data-v-4d05be2c]:focus-visible{outline:2px solid var(--color-main-text) !important}.button-vue:focus-visible.button-vue--vue-tertiary-on-primary[data-v-4d05be2c]{outline:2px solid var(--color-primary-element-text);border-radius:var(--border-radius);background-color:rgba(0,0,0,0)}.button-vue--vue-primary[data-v-4d05be2c]{background-color:var(--color-primary-element);color:var(--color-primary-element-text)}.button-vue--vue-primary[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-primary-element-hover)}.button-vue--vue-primary[data-v-4d05be2c]:active{background-color:var(--color-primary-element)}.button-vue--vue-secondary[data-v-4d05be2c]{color:var(--color-primary-element-light-text);background-color:var(--color-primary-element-light)}.button-vue--vue-secondary[data-v-4d05be2c]:hover:not(:disabled){color:var(--color-primary-element-light-text);background-color:var(--color-primary-element-light-hover)}.button-vue--vue-tertiary[data-v-4d05be2c]{color:var(--color-main-text);background-color:rgba(0,0,0,0)}.button-vue--vue-tertiary[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color);background-color:var(--color-background-hover)}.button-vue--vue-tertiary-no-background[data-v-4d05be2c]{color:var(--color-main-text);background-color:rgba(0,0,0,0)}.button-vue--vue-tertiary-no-background[data-v-4d05be2c]:hover:not(:disabled){background-color:rgba(0,0,0,0)}.button-vue--vue-tertiary-on-primary[data-v-4d05be2c]{color:var(--color-primary-element-text);background-color:rgba(0,0,0,0)}.button-vue--vue-tertiary-on-primary[data-v-4d05be2c]:hover:not(:disabled){background-color:rgba(0,0,0,0)}.button-vue--vue-success[data-v-4d05be2c]{background-color:var(--color-success);color:#fff}.button-vue--vue-success[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-success-hover)}.button-vue--vue-success[data-v-4d05be2c]:active{background-color:var(--color-success)}.button-vue--vue-warning[data-v-4d05be2c]{background-color:var(--color-warning);color:#fff}.button-vue--vue-warning[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-warning-hover)}.button-vue--vue-warning[data-v-4d05be2c]:active{background-color:var(--color-warning)}.button-vue--vue-error[data-v-4d05be2c]{background-color:var(--color-error);color:#fff}.button-vue--vue-error[data-v-4d05be2c]:hover:not(:disabled){background-color:var(--color-error-hover)}.button-vue--vue-error[data-v-4d05be2c]:active{background-color:var(--color-error)}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcButton/NcButton.vue","webpack://./src/assets/variables.scss"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCLD,6BACC,iBAAA,CACA,iBAAA,CACA,eAAA,CACA,QAAA,CACA,SAAA,CACA,kCAAA,CACA,gBAAA,CACA,eCcgB,CDbhB,cCagB,CDZhB,YAAA,CACA,kBAAA,CACA,sBAAA,CAGA,cAAA,CAKA,kBAAA,CACA,uDAAA,CACA,uBAAA,CACA,iCAAA,CAkBA,6CAAA,CACA,mDAAA,CA1BA,iEAEC,cAAA,CAQD,mCACC,YAAA,CAGD,sCACC,cAAA,CAIA,UCIiB,CDFjB,oBAAA,CALA,wCACC,cAAA,CAUF,kDACC,yDAAA,CAKD,oCACC,mDAAA,CAGD,sCACC,mBAAA,CACA,kBAAA,CACA,sBAAA,CACA,UAAA,CAGD,mCACC,WCvCe,CDwCf,UCxCe,CDyCf,eCzCe,CD0Cf,cC1Ce,CD2Cf,YAAA,CACA,sBAAA,CACA,kBAAA,CAGD,mCACC,gBAAA,CACA,iBAAA,CACA,aAAA,CACA,kBAAA,CACA,sBAAA,CACA,eAAA,CAID,wCACC,qBAAA,CAID,wCACC,cAAA,CACA,0DACC,eAAA,CACA,gBAAA,CAKF,4CACC,oBAAA,CAID,mCACC,UAAA,CAGD,2CACC,mDAAA,CACA,+EACC,mDAAA,CACA,kCAAA,CACA,8BAAA,CAOF,0CACC,6CAAA,CACA,uCAAA,CACA,+DACC,mDAAA,CAID,iDACC,6CAAA,CAKF,4CACC,6CAAA,CACA,mDAAA,CACA,iEACC,6CAAA,CACA,yDAAA,CAKF,2CACC,4BAAA,CACA,8BAAA,CACA,gEACC,6BAAA,CACA,8CAAA,CAKF,yDACC,4BAAA,CACA,8BAAA,CACA,8EACC,8BAAA,CAKF,sDACC,uCAAA,CACA,8BAAA,CAEA,2EACC,8BAAA,CAKF,0CACC,qCAAA,CACA,UAAA,CACA,+DACC,2CAAA,CAID,iDACC,qCAAA,CAKF,0CACC,qCAAA,CACA,UAAA,CACA,+DACC,2CAAA,CAID,iDACC,qCAAA,CAKF,wCACC,mCAAA,CACA,UAAA,CACA,6DACC,yCAAA,CAID,+CACC,mCAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n\n.button-vue {\n\tposition: relative;\n\twidth: fit-content;\n\toverflow: hidden;\n\tborder: 0;\n\tpadding: 0;\n\tfont-size: var(--default-font-size);\n\tfont-weight: bold;\n\tmin-height: $clickable-area;\n\tmin-width: $clickable-area;\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: center;\n\n\t// Cursor pointer on element and all children\n\tcursor: pointer;\n\t& *,\n\tspan {\n\t\tcursor: pointer;\n\t}\n\tborder-radius: math.div($clickable-area, 2);\n\ttransition-property: color, border-color, background-color;\n\ttransition-duration: 0.1s;\n\ttransition-timing-function: linear;\n\n\t// No outline feedback for focus. Handled with a toggled class in js (see data)\n\t&:focus {\n\t\toutline: none;\n\t}\n\n\t&:disabled {\n\t\tcursor: default;\n\t\t& * {\n\t\t\tcursor: default;\n\t\t}\n\t\topacity: $opacity_disabled;\n\t\t// Gives a wash out effect\n\t\tfilter: saturate($opacity_normal);\n\t}\n\n\t// Default button type\n\tcolor: var(--color-primary-element-light-text);\n\tbackground-color: var(--color-primary-element-light);\n\t&:hover:not(:disabled) {\n\t\tbackground-color: var(--color-primary-element-light-hover);\n\t}\n\n\t// Back to the default color for this button when active\n\t// TODO: add ripple effect\n\t&:active {\n\t\tbackground-color: var(--color-primary-element-light);\n\t}\n\n\t&__wrapper {\n\t\tdisplay: inline-flex;\n\t\talign-items: center;\n\t\tjustify-content: center;\n\t\twidth: 100%;\n\t}\n\n\t&__icon {\n\t\theight: $clickable-area;\n\t\twidth: $clickable-area;\n\t\tmin-height: $clickable-area;\n\t\tmin-width: $clickable-area;\n\t\tdisplay: flex;\n\t\tjustify-content: center;\n\t\talign-items: center;\n\t}\n\n\t&__text {\n\t\tfont-weight: bold;\n\t\tmargin-bottom: 1px;\n\t\tpadding: 2px 0;\n\t\twhite-space: nowrap;\n\t\ttext-overflow: ellipsis;\n\t\toverflow: hidden;\n\t}\n\n\t// Icon-only button\n\t&--icon-only {\n\t\twidth: $clickable-area !important;\n\t}\n\n\t// Text-only button\n\t&--text-only {\n\t\tpadding: 0 12px;\n\t\t& .button-vue__text {\n\t\t\tmargin-left: 4px;\n\t\t\tmargin-right: 4px;\n\t\t}\n\t}\n\n\t// Icon and text button\n\t&--icon-and-text {\n\t\tpadding: 0 16px 0 4px;\n\t}\n\n\t// Wide button spans the whole width of the container\n\t&--wide {\n\t\twidth: 100%;\n\t}\n\n\t&:focus-visible {\n\t\toutline: 2px solid var(--color-main-text) !important;\n\t\t&.button-vue--vue-tertiary-on-primary {\n\t\t\toutline: 2px solid var(--color-primary-element-text);\n\t\t\tborder-radius: var(--border-radius);\n\t\t\tbackground-color: transparent;\n\t\t}\n\t}\n\n\t// Button types\n\n\t// Primary\n\t&--vue-primary {\n\t\tbackground-color: var(--color-primary-element);\n\t\tcolor: var(--color-primary-element-text);\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color-primary-element-hover);\n\t\t}\n\t\t// Back to the default color for this button when active\n\t\t// TODO: add ripple effect\n\t\t&:active {\n\t\t\tbackground-color: var(--color-primary-element);\n\t\t}\n\t}\n\n\t// Secondary\n\t&--vue-secondary {\n\t\tcolor: var(--color-primary-element-light-text);\n\t\tbackground-color: var(--color-primary-element-light);\n\t\t&:hover:not(:disabled) {\n\t\t\tcolor: var(--color-primary-element-light-text);\n\t\t\tbackground-color: var(--color-primary-element-light-hover);\n\t\t}\n\t}\n\n\t// Tertiary\n\t&--vue-tertiary {\n\t\tcolor: var(--color-main-text);\n\t\tbackground-color: transparent;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color);\n\t\t\tbackground-color: var(--color-background-hover);\n\t\t}\n\t}\n\n\t// Tertiary, no background\n\t&--vue-tertiary-no-background {\n\t\tcolor: var(--color-main-text);\n\t\tbackground-color: transparent;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: transparent;\n\t\t}\n\t}\n\n\t// Tertiary on primary color (like the header)\n\t&--vue-tertiary-on-primary {\n\t\tcolor: var(--color-primary-element-text);\n\t\tbackground-color: transparent;\n\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: transparent;\n\t\t}\n\t}\n\n\t// Success\n\t&--vue-success {\n\t\tbackground-color: var(--color-success);\n\t\tcolor: white;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color-success-hover);\n\t\t}\n\t\t// Back to the default color for this button when active\n\t\t// : add ripple effect\n\t\t&:active {\n\t\t\tbackground-color: var(--color-success);\n\t\t}\n\t}\n\n\t// Warning\n\t&--vue-warning {\n\t\tbackground-color: var(--color-warning);\n\t\tcolor: white;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color-warning-hover);\n\t\t}\n\t\t// Back to the default color for this button when active\n\t\t// TODO: add ripple effect\n\t\t&:active {\n\t\t\tbackground-color: var(--color-warning);\n\t\t}\n\t}\n\n\t// Error\n\t&--vue-error {\n\t\tbackground-color: var(--color-error);\n\t\tcolor: white;\n\t\t&:hover:not(:disabled) {\n\t\t\tbackground-color: var(--color-error-hover);\n\t\t}\n\t\t// Back to the default color for this button when active\n\t\t// TODO: add ripple effect\n\t\t&:active {\n\t\t\tbackground-color: var(--color-error);\n\t\t}\n\t}\n}\n\n","/**\n * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @author John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n\n// https://uxplanet.org/7-rules-for-mobile-ui-button-design-e9cf2ea54556\n// recommended is 48px\n// 44px is what we choose and have very good visual-to-usability ratio\n$clickable-area: 44px;\n\n// background icon size\n// also used for the scss icon font\n$icon-size: 16px;\n\n// icon padding for a $clickable-area width and a $icon-size icon\n// ( 44px - 16px ) / 2\n$icon-margin: math.div($clickable-area - $icon-size, 2);\n\n// transparency background for icons\n$icon-focus-bg: rgba(127, 127, 127, .25);\n\n// popovermenu arrow width from the triangle center\n$arrow-width: 9px;\n\n// opacities\n$opacity_disabled: .5;\n$opacity_normal: .7;\n$opacity_full: 1;\n\n// menu round background hover feedback\n// good looking on dark AND white bg\n$action-background-hover: rgba(127, 127, 127, .25);\n\n// various structure data used in the \n// `AppNavigation` component\n$header-height: 50px;\n$navigation-width: 300px;\n\n// mobile breakpoint\n$breakpoint-mobile: 1024px;\n\n// top-bar spacing\n$topbar-margin: 4px;\n\n// navigation spacing\n$app-navigation-settings-margin: 3px;\n"],sourceRoot:""}]);const s=r},436:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon[data-v-3daafbe0]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.name-parts[data-v-3daafbe0]{display:flex;max-width:100%;cursor:inherit}.name-parts__first[data-v-3daafbe0]{overflow:hidden;text-overflow:ellipsis}.name-parts__first[data-v-3daafbe0],.name-parts__last[data-v-3daafbe0]{white-space:pre;cursor:inherit}.name-parts__first strong[data-v-3daafbe0],.name-parts__last strong[data-v-3daafbe0]{font-weight:bold}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcEllipsisedOption/NcEllipsisedOption.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,6BACC,YAAA,CACA,cAAA,CACA,cAAA,CACA,oCACC,eAAA,CACA,sBAAA,CAED,uEAGC,eAAA,CACA,cAAA,CACA,qFACC,gBAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.name-parts {\n\tdisplay: flex;\n\tmax-width: 100%;\n\tcursor: inherit;\n\t&__first {\n\t\toverflow: hidden;\n\t\ttext-overflow: ellipsis;\n\t}\n\t&__first,\n\t&__last {\n\t\t// prevent whitespace from being trimmed\n\t\twhite-space: pre;\n\t\tcursor: inherit;\n\t\tstrong {\n\t\t\tfont-weight: bold;\n\t\t}\n\t}\n}\n"],sourceRoot:""}]);const s=r},8402:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon[data-v-45b807d6]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.icon-vue[data-v-45b807d6]{display:flex;justify-content:center;align-items:center;width:44px;height:44px;opacity:1}.icon-vue[data-v-45b807d6] svg{fill:currentColor;max-width:20px;max-height:20px}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcIconSvgWrapper/NcIconSvgWrapper.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,2BACC,YAAA,CACA,sBAAA,CACA,kBAAA,CACA,UAAA,CACA,WAAA,CACA,SAAA,CAEA,+BACC,iBAAA,CACA,cAAA,CACA,eAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.icon-vue {\n\tdisplay: flex;\n\tjustify-content: center;\n\talign-items: center;\n\twidth: 44px;\n\theight: 44px;\n\topacity: 1;\n\n\t&:deep(svg) {\n\t\tfill: currentColor;\n\t\tmax-width: 20px;\n\t\tmax-height: 20px;\n\t}\n}\n"],sourceRoot:""}]);const s=r},4629:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon[data-v-160648e6]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.option[data-v-160648e6]{display:flex;align-items:center;width:100%;height:var(--height);cursor:inherit}.option__avatar[data-v-160648e6]{margin-right:var(--margin)}.option__details[data-v-160648e6]{display:flex;flex:1 1;flex-direction:column;justify-content:center;min-width:0}.option__lineone[data-v-160648e6]{color:var(--color-main-text)}.option__linetwo[data-v-160648e6]{color:var(--color-text-maxcontrast)}.option__lineone[data-v-160648e6],.option__linetwo[data-v-160648e6]{overflow:hidden;white-space:nowrap;text-overflow:ellipsis;line-height:1.1em}.option__lineone strong[data-v-160648e6],.option__linetwo strong[data-v-160648e6]{font-weight:bold}.option__icon[data-v-160648e6]{width:44px;height:44px;color:var(--color-text-maxcontrast)}.option__icon.icon[data-v-160648e6]{flex:0 0 44px;opacity:.7;background-position:center;background-size:16px}.option__details[data-v-160648e6],.option__lineone[data-v-160648e6],.option__linetwo[data-v-160648e6],.option__icon[data-v-160648e6]{cursor:inherit}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcListItemIcon/NcListItemIcon.vue","webpack://./src/assets/variables.scss"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,yBACC,YAAA,CACA,kBAAA,CACA,UAAA,CACA,oBAAA,CACA,cAAA,CAEA,iCACC,0BAAA,CAGD,kCACC,YAAA,CACA,QAAA,CACA,qBAAA,CACA,sBAAA,CACA,WAAA,CAGD,kCACC,4BAAA,CAGD,kCACC,mCAAA,CAGD,oEAEC,eAAA,CACA,kBAAA,CACA,sBAAA,CACA,iBAAA,CACA,kFACC,gBAAA,CAIF,+BACC,UChBe,CDiBf,WCjBe,CDkBf,mCAAA,CACA,oCACC,aAAA,CACA,UCHc,CDId,0BAAA,CACA,oBAAA,CAIF,qIAIC,cAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.option {\n\tdisplay: flex;\n\talign-items: center;\n\twidth: 100%;\n\theight: var(--height);\n\tcursor: inherit;\n\n\t&__avatar {\n\t\tmargin-right: var(--margin);\n\t}\n\n\t&__details {\n\t\tdisplay: flex;\n\t\tflex: 1 1;\n\t\tflex-direction: column;\n\t\tjustify-content: center;\n\t\tmin-width: 0;\n\t}\n\n\t&__lineone {\n\t\tcolor: var(--color-main-text);\n\t}\n\n\t&__linetwo {\n\t\tcolor: var(--color-text-maxcontrast);\n\t}\n\n\t&__lineone,\n\t&__linetwo {\n\t\toverflow: hidden;\n\t\twhite-space: nowrap;\n\t\ttext-overflow: ellipsis;\n\t\tline-height: 1.1em;\n\t\tstrong {\n\t\t\tfont-weight: bold;\n\t\t}\n\t}\n\n\t&__icon {\n\t\twidth: $clickable-area;\n\t\theight: $clickable-area;\n\t\tcolor: var(--color-text-maxcontrast);\n\t\t&.icon {\n\t\t\tflex: 0 0 $clickable-area;\n\t\t\topacity: $opacity_normal;\n\t\t\tbackground-position: center;\n\t\t\tbackground-size: 16px;\n\t\t}\n\t}\n\n\t&__details,\n\t&__lineone,\n\t&__linetwo,\n\t&__icon {\n\t\tcursor: inherit;\n\t}\n}\n","/**\n * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @author John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n\n// https://uxplanet.org/7-rules-for-mobile-ui-button-design-e9cf2ea54556\n// recommended is 48px\n// 44px is what we choose and have very good visual-to-usability ratio\n$clickable-area: 44px;\n\n// background icon size\n// also used for the scss icon font\n$icon-size: 16px;\n\n// icon padding for a $clickable-area width and a $icon-size icon\n// ( 44px - 16px ) / 2\n$icon-margin: math.div($clickable-area - $icon-size, 2);\n\n// transparency background for icons\n$icon-focus-bg: rgba(127, 127, 127, .25);\n\n// popovermenu arrow width from the triangle center\n$arrow-width: 9px;\n\n// opacities\n$opacity_disabled: .5;\n$opacity_normal: .7;\n$opacity_full: 1;\n\n// menu round background hover feedback\n// good looking on dark AND white bg\n$action-background-hover: rgba(127, 127, 127, .25);\n\n// various structure data used in the \n// `AppNavigation` component\n$header-height: 50px;\n$navigation-width: 300px;\n\n// mobile breakpoint\n$breakpoint-mobile: 1024px;\n\n// top-bar spacing\n$topbar-margin: 4px;\n\n// navigation spacing\n$app-navigation-settings-margin: 3px;\n"],sourceRoot:""}]);const s=r},8502:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon[data-v-27fa1197]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.loading-icon svg[data-v-27fa1197]{animation:rotate var(--animation-duration, 0.8s) linear infinite}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcLoadingIcon/NcLoadingIcon.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,mCACC,gEAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n.loading-icon svg{\n\tanimation: rotate var(--animation-duration, 0.8s) linear infinite;\n}\n"],sourceRoot:""}]);const s=r},1625:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.resize-observer{position:absolute;top:0;left:0;z-index:-1;width:100%;height:100%;border:none;background-color:rgba(0,0,0,0);pointer-events:none;display:block;overflow:hidden;opacity:0}.resize-observer object{display:block;position:absolute;top:0;left:0;height:100%;width:100%;overflow:hidden;pointer-events:none;z-index:-1}.v-popper--theme-dropdown.v-popper__popper{z-index:100000;top:0;left:0;display:block !important;filter:drop-shadow(0 1px 10px var(--color-box-shadow))}.v-popper--theme-dropdown.v-popper__popper .v-popper__inner{padding:0;color:var(--color-main-text);border-radius:var(--border-radius-large);overflow:hidden;background:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper .v-popper__arrow-container{position:absolute;z-index:1;width:0;height:0;border-style:solid;border-color:rgba(0,0,0,0);border-width:10px}.v-popper--theme-dropdown.v-popper__popper[data-popper-placement^=top] .v-popper__arrow-container{bottom:-10px;border-bottom-width:0;border-top-color:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper[data-popper-placement^=bottom] .v-popper__arrow-container{top:-10px;border-top-width:0;border-bottom-color:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper[data-popper-placement^=right] .v-popper__arrow-container{left:-10px;border-left-width:0;border-right-color:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper[data-popper-placement^=left] .v-popper__arrow-container{right:-10px;border-right-width:0;border-left-color:var(--color-main-background)}.v-popper--theme-dropdown.v-popper__popper[aria-hidden=true]{visibility:hidden;transition:opacity var(--animation-quick),visibility var(--animation-quick);opacity:0}.v-popper--theme-dropdown.v-popper__popper[aria-hidden=false]{visibility:visible;transition:opacity var(--animation-quick);opacity:1}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcPopover/NcPopover.vue"],names:[],mappings:"AAGA,sBACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCLD,iBACC,iBAAA,CACA,KAAA,CACA,MAAA,CACA,UAAA,CACA,UAAA,CACA,WAAA,CACA,WAAA,CACA,8BAAA,CACA,mBAAA,CACA,aAAA,CACA,eAAA,CACA,SAAA,CAGD,wBACC,aAAA,CACA,iBAAA,CACA,KAAA,CACA,MAAA,CACA,WAAA,CACA,UAAA,CACA,eAAA,CACA,mBAAA,CACA,UAAA,CAMA,2CACC,cAAA,CACA,KAAA,CACA,MAAA,CACA,wBAAA,CAEA,sDAAA,CAEA,4DACC,SAAA,CACA,4BAAA,CACA,wCAAA,CACA,eAAA,CACA,uCAAA,CAGD,sEACC,iBAAA,CACA,SAAA,CACA,OAAA,CACA,QAAA,CACA,kBAAA,CACA,0BAAA,CACA,iBA1BW,CA6BZ,kGACC,YAAA,CACA,qBAAA,CACA,6CAAA,CAGD,qGACC,SAAA,CACA,kBAAA,CACA,gDAAA,CAGD,oGACC,UAAA,CACA,mBAAA,CACA,+CAAA,CAGD,mGACC,WAAA,CACA,oBAAA,CACA,8CAAA,CAGD,6DACC,iBAAA,CACA,2EAAA,CACA,SAAA,CAGD,8DACC,kBAAA,CACA,yCAAA,CACA,SAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n\n.resize-observer {\n\tposition:absolute;\n\ttop:0;\n\tleft:0;\n\tz-index:-1;\n\twidth:100%;\n\theight:100%;\n\tborder:none;\n\tbackground-color:transparent;\n\tpointer-events:none;\n\tdisplay:block;\n\toverflow:hidden;\n\topacity:0\n}\n\n.resize-observer object {\n\tdisplay:block;\n\tposition:absolute;\n\ttop:0;\n\tleft:0;\n\theight:100%;\n\twidth:100%;\n\toverflow:hidden;\n\tpointer-events:none;\n\tz-index:-1\n}\n\n$arrow-width: 10px;\n\n.v-popper--theme-dropdown {\n\t&.v-popper__popper {\n\t\tz-index: 100000;\n\t\ttop: 0;\n\t\tleft: 0;\n\t\tdisplay: block !important;\n\n\t\tfilter: drop-shadow(0 1px 10px var(--color-box-shadow));\n\n\t\t.v-popper__inner {\n\t\t\tpadding: 0;\n\t\t\tcolor: var(--color-main-text);\n\t\t\tborder-radius: var(--border-radius-large);\n\t\t\toverflow: hidden;\n\t\t\tbackground: var(--color-main-background);\n\t\t}\n\n\t\t.v-popper__arrow-container {\n\t\t\tposition: absolute;\n\t\t\tz-index: 1;\n\t\t\twidth: 0;\n\t\t\theight: 0;\n\t\t\tborder-style: solid;\n\t\t\tborder-color: transparent;\n\t\t\tborder-width: $arrow-width;\n\t\t}\n\n\t\t&[data-popper-placement^='top'] .v-popper__arrow-container {\n\t\t\tbottom: -$arrow-width;\n\t\t\tborder-bottom-width: 0;\n\t\t\tborder-top-color: var(--color-main-background);\n\t\t}\n\n\t\t&[data-popper-placement^='bottom'] .v-popper__arrow-container {\n\t\t\ttop: -$arrow-width;\n\t\t\tborder-top-width: 0;\n\t\t\tborder-bottom-color: var(--color-main-background);\n\t\t}\n\n\t\t&[data-popper-placement^='right'] .v-popper__arrow-container {\n\t\t\tleft: -$arrow-width;\n\t\t\tborder-left-width: 0;\n\t\t\tborder-right-color: var(--color-main-background);\n\t\t}\n\n\t\t&[data-popper-placement^='left'] .v-popper__arrow-container {\n\t\t\tright: -$arrow-width;\n\t\t\tborder-right-width: 0;\n\t\t\tborder-left-color: var(--color-main-background);\n\t\t}\n\n\t\t&[aria-hidden='true'] {\n\t\t\tvisibility: hidden;\n\t\t\ttransition: opacity var(--animation-quick), visibility var(--animation-quick);\n\t\t\topacity: 0;\n\t\t}\n\n\t\t&[aria-hidden='false'] {\n\t\t\tvisibility: visible;\n\t\t\ttransition: opacity var(--animation-quick);\n\t\t\topacity: 1;\n\t\t}\n\t}\n}\n\n"],sourceRoot:""}]);const s=r},6466:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon[data-v-7dba3f6e]{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}.mention-bubble--primary .mention-bubble__content[data-v-7dba3f6e]{color:var(--color-primary-element-text);background-color:var(--color-primary-element)}.mention-bubble__wrapper[data-v-7dba3f6e]{max-width:150px;height:18px;vertical-align:text-bottom;display:inline-flex;align-items:center}.mention-bubble__content[data-v-7dba3f6e]{display:inline-flex;overflow:hidden;align-items:center;max-width:100%;height:20px;-webkit-user-select:none;user-select:none;padding-right:6px;padding-left:2px;border-radius:10px;background-color:var(--color-background-dark)}.mention-bubble__icon[data-v-7dba3f6e]{position:relative;width:16px;height:16px;border-radius:8px;background-color:var(--color-background-darker);background-repeat:no-repeat;background-position:center;background-size:12px}.mention-bubble__icon--with-avatar[data-v-7dba3f6e]{color:inherit;background-size:cover}.mention-bubble__title[data-v-7dba3f6e]{overflow:hidden;margin-left:2px;white-space:nowrap;text-overflow:ellipsis}.mention-bubble__title[data-v-7dba3f6e]::before{content:attr(title)}.mention-bubble__select[data-v-7dba3f6e]{position:absolute;z-index:-1;left:-1000px}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcRichContenteditable/NcMentionBubble.vue"],names:[],mappings:"AAGA,uCACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CAAA,mECCC,uCAAA,CACA,6CAAA,CAGD,0CACC,eAXiB,CAajB,WAAA,CACA,0BAAA,CACA,mBAAA,CACA,kBAAA,CAGD,0CACC,mBAAA,CACA,eAAA,CACA,kBAAA,CACA,cAAA,CACA,WAzBc,CA0Bd,wBAAA,CACA,gBAAA,CACA,iBAAA,CACA,gBA3Be,CA4Bf,kBAAA,CACA,6CAAA,CAGD,uCACC,iBAAA,CACA,UAjCmB,CAkCnB,WAlCmB,CAmCnB,iBAAA,CACA,+CAAA,CACA,2BAAA,CACA,0BAAA,CACA,oBAAA,CAEA,oDACC,aAAA,CACA,qBAAA,CAIF,wCACC,eAAA,CACA,eAlDe,CAmDf,kBAAA,CACA,sBAAA,CAEA,gDACC,mBAAA,CAKF,yCACC,iBAAA,CACA,UAAA,CACA,YAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\n$bubble-height: 20px;\n$bubble-max-width: 150px;\n$bubble-padding: 2px;\n$bubble-avatar-size: $bubble-height - 2 * $bubble-padding;\n\n.mention-bubble {\n\t&--primary &__content {\n\t\tcolor: var(--color-primary-element-text);\n\t\tbackground-color: var(--color-primary-element);\n\t}\n\n\t&__wrapper {\n\t\tmax-width: $bubble-max-width;\n\t\t// Align with text\n\t\theight: $bubble-height - $bubble-padding;\n\t\tvertical-align: text-bottom;\n\t\tdisplay: inline-flex;\n\t\talign-items: center;\n\t}\n\n\t&__content {\n\t\tdisplay: inline-flex;\n\t\toverflow: hidden;\n\t\talign-items: center;\n\t\tmax-width: 100%;\n\t\theight: $bubble-height ;\n\t\t-webkit-user-select: none;\n\t\tuser-select: none;\n\t\tpadding-right: $bubble-padding * 3;\n\t\tpadding-left: $bubble-padding;\n\t\tborder-radius: math.div($bubble-height, 2);\n\t\tbackground-color: var(--color-background-dark);\n\t}\n\n\t&__icon {\n\t\tposition: relative;\n\t\twidth: $bubble-avatar-size;\n\t\theight: $bubble-avatar-size;\n\t\tborder-radius: math.div($bubble-avatar-size, 2);\n\t\tbackground-color: var(--color-background-darker);\n\t\tbackground-repeat: no-repeat;\n\t\tbackground-position: center;\n\t\tbackground-size: $bubble-avatar-size - 2 * $bubble-padding;\n\n\t\t&--with-avatar {\n\t\t\tcolor: inherit;\n\t\t\tbackground-size: cover;\n\t\t}\n\t}\n\n\t&__title {\n\t\toverflow: hidden;\n\t\tmargin-left: $bubble-padding;\n\t\twhite-space: nowrap;\n\t\ttext-overflow: ellipsis;\n\t\t// Put title in ::before so it is not selectable\n\t\t&::before {\n\t\t\tcontent: attr(title);\n\t\t}\n\t}\n\n\t// Hide the mention id so it is selectable\n\t&__select {\n\t\tposition: absolute;\n\t\tz-index: -1;\n\t\tleft: -1000px;\n\t}\n}\n\n"],sourceRoot:""}]);const s=r},6065:(e,t,a)=>{"use strict";a.d(t,{Z:()=>s});var n=a(7537),o=a.n(n),i=a(3645),r=a.n(i)()(o());r.push([e.id,".material-design-icon{display:flex;align-self:center;justify-self:center;align-items:center;justify-content:center}body{--vs-search-input-color: var(--color-main-text);--vs-search-input-bg: var(--color-main-background);--vs-search-input-placeholder-color: var(--color-text-maxcontrast);--vs-font-size: var(--default-font-size);--vs-line-height: var(--default-line-height);--vs-state-disabled-bg: var(--color-background-hover);--vs-state-disabled-color: var(--color-text-maxcontrast);--vs-state-disabled-controls-color: var(--color-text-maxcontrast);--vs-state-disabled-cursor: not-allowed;--vs-disabled-bg: var(--color-background-hover);--vs-disabled-color: var(--color-text-maxcontrast);--vs-disabled-cursor: not-allowed;--vs-border-color: var(--color-border-maxcontrast);--vs-border-width: 2px;--vs-border-style: solid;--vs-border-radius: var(--border-radius-large);--vs-controls-color: var(--color-text-maxcontrast);--vs-selected-bg: var(--color-background-dark);--vs-selected-color: var(--color-main-text);--vs-dropdown-bg: var(--color-main-background);--vs-dropdown-color: var(--color-main-text);--vs-dropdown-z-index: 9999;--vs-dropdown-box-shadow: 0px 2px 2px 0px var(--color-box-shadow);--vs-dropdown-option-padding: 8px 20px;--vs-dropdown-option--active-bg: var(--color-background-hover);--vs-dropdown-option--active-color: var(--color-main-text);--vs-dropdown-option--kb-focus-box-shadow: inset 0px 0px 0px 2px var(--vs-border-color);--vs-dropdown-option--deselect-bg: var(--color-error);--vs-dropdown-option--deselect-color: #fff;--vs-transition-duration: 0ms}.v-select.select{min-height:44px;min-width:260px;margin:0}.v-select.select .vs__selected{min-height:36px;padding:0 .5em}.v-select.select .vs__clear{margin-right:2px}.v-select.select.vs--open .vs__dropdown-toggle{border-color:var(--color-primary-element);border-bottom-color:rgba(0,0,0,0)}.v-select.select:not(.vs--disabled,.vs--open) .vs__dropdown-toggle:hover{border-color:var(--color-primary-element)}.v-select.select.vs--disabled .vs__search,.v-select.select.vs--disabled .vs__selected{color:var(--color-text-maxcontrast)}.v-select.select.vs--disabled .vs__clear,.v-select.select.vs--disabled .vs__deselect{display:none}.v-select.select--no-wrap .vs__selected-options{flex-wrap:nowrap;overflow:auto;min-width:unset}.v-select.select--no-wrap .vs__selected-options .vs__selected{min-width:unset}.v-select.select--drop-up.vs--open .vs__dropdown-toggle{border-radius:0 0 var(--vs-border-radius) var(--vs-border-radius);border-top-color:rgba(0,0,0,0);border-bottom-color:var(--color-primary-element)}.v-select.select .vs__selected-options{min-height:40px}.v-select.select .vs__selected-options .vs__selected~.vs__search[readonly]{position:absolute}.v-select.select.vs--single.vs--loading .vs__selected,.v-select.select.vs--single.vs--open .vs__selected{max-width:100%}.v-select.select.vs--single .vs__selected-options{flex-wrap:nowrap}.vs__dropdown-menu{border-color:var(--color-primary-element) !important;padding:4px !important}.vs__dropdown-menu--floating{width:max-content;position:absolute;top:0;left:0}.vs__dropdown-menu--floating-placement-top{border-radius:var(--vs-border-radius) var(--vs-border-radius) 0 0 !important;border-top-style:var(--vs-border-style) !important;border-bottom-style:none !important;box-shadow:0px -1px 1px 0px var(--color-box-shadow) !important}.vs__dropdown-menu .vs__dropdown-option{border-radius:6px !important}.vs__dropdown-menu .vs__no-options{color:var(--color-text-lighter) !important}","",{version:3,sources:["webpack://./src/assets/material-icons.css","webpack://./src/components/NcSelect/NcSelect.vue","webpack://./src/assets/variables.scss"],names:[],mappings:"AAGA,sBACC,YAAA,CACA,iBAAA,CACA,mBAAA,CACA,kBAAA,CACA,sBAAA,CCND,KAOC,+CAAA,CACA,kDAAA,CACA,kEAAA,CAGA,wCAAA,CACA,4CAAA,CAGA,qDAAA,CACA,wDAAA,CACA,iEAAA,CACA,uCAAA,CACA,+CAAA,CACA,kDAAA,CACA,iCAAA,CAGA,kDAAA,CACA,sBAAA,CACA,wBAAA,CACA,8CAAA,CAGA,kDAAA,CAGA,8CAAA,CACA,2CAAA,CAGA,8CAAA,CACA,2CAAA,CACA,2BAAA,CACA,iEAAA,CAGA,sCAAA,CAGA,8DAAA,CACA,0DAAA,CAGA,uFAAA,CAGA,qDAAA,CACA,0CAAA,CAGA,6BAAA,CAGD,iBAEC,eCxCgB,CDyChB,eAAA,CACA,QAAA,CAEA,+BACC,eAAA,CACA,cAAA,CAGD,4BACC,gBAAA,CAGD,+CACC,yCAAA,CACA,iCAAA,CAGD,yEACC,yCAAA,CAIA,sFAEC,mCAAA,CAGD,qFAEC,YAAA,CAKD,gDACC,gBAAA,CACA,aAAA,CACA,eAAA,CACA,8DACC,eAAA,CAOD,wDACC,iEAAA,CACA,8BAAA,CACA,gDAAA,CAKH,uCAEC,eAAA,CAGA,2EACC,iBAAA,CAOA,yGAEC,cAAA,CAGF,kDACC,gBAAA,CAKH,mBACC,oDAAA,CACA,sBAAA,CAEA,6BAEC,iBAAA,CACA,iBAAA,CACA,KAAA,CACA,MAAA,CAEA,2CACC,4EAAA,CACA,kDAAA,CACA,mCAAA,CACA,8DAAA,CAIF,wCACC,4BAAA,CAGD,mCACC,0CAAA",sourcesContent:["/*\n* Ensure proper alignment of the vue material icons\n*/\n.material-design-icon {\n\tdisplay: flex;\n\talign-self: center;\n\tjustify-self: center;\n\talign-items: center;\n\tjustify-content: center;\n}\n","@use 'sass:math'; $scope_version:\"f7c85e6\"; @import 'variables'; @import 'material-icons';\n\nbody {\n\t/**\n\t * Set custom vue-select CSS variables.\n\t * Needs to be on the body (not :root) for theming to apply (see nextcloud/server#36462)\n\t */\n\n\t/* Search Input */\n\t--vs-search-input-color: var(--color-main-text);\n\t--vs-search-input-bg: var(--color-main-background);\n\t--vs-search-input-placeholder-color: var(--color-text-maxcontrast);\n\n\t/* Font */\n\t--vs-font-size: var(--default-font-size);\n\t--vs-line-height: var(--default-line-height);\n\n\t/* Disabled State */\n\t--vs-state-disabled-bg: var(--color-background-hover);\n\t--vs-state-disabled-color: var(--color-text-maxcontrast);\n\t--vs-state-disabled-controls-color: var(--color-text-maxcontrast);\n\t--vs-state-disabled-cursor: not-allowed;\n\t--vs-disabled-bg: var(--color-background-hover);\n\t--vs-disabled-color: var(--color-text-maxcontrast);\n\t--vs-disabled-cursor: not-allowed;\n\n\t/* Borders */\n\t--vs-border-color: var(--color-border-maxcontrast);\n\t--vs-border-width: 2px;\n\t--vs-border-style: solid;\n\t--vs-border-radius: var(--border-radius-large);\n\n\t/* Component Controls: Clear, Open Indicator */\n\t--vs-controls-color: var(--color-text-maxcontrast);\n\n\t/* Selected */\n\t--vs-selected-bg: var(--color-background-dark);\n\t--vs-selected-color: var(--color-main-text);\n\n\t/* Dropdown */\n\t--vs-dropdown-bg: var(--color-main-background);\n\t--vs-dropdown-color: var(--color-main-text);\n\t--vs-dropdown-z-index: 9999;\n\t--vs-dropdown-box-shadow: 0px 2px 2px 0px var(--color-box-shadow);\n\n\t/* Options */\n\t--vs-dropdown-option-padding: 8px 20px;\n\n\t/* Active State */\n\t--vs-dropdown-option--active-bg: var(--color-background-hover);\n\t--vs-dropdown-option--active-color: var(--color-main-text);\n\n\t/* Keyboard Focus State */\n\t--vs-dropdown-option--kb-focus-box-shadow: inset 0px 0px 0px 2px var(--vs-border-color);\n\n\t/* Deselect State */\n\t--vs-dropdown-option--deselect-bg: var(--color-error);\n\t--vs-dropdown-option--deselect-color: #fff;\n\n\t/* Transitions */\n\t--vs-transition-duration: 0ms;\n}\n\n.v-select.select {\n\t/* Override default vue-select styles */\n\tmin-height: $clickable-area;\n\tmin-width: 260px;\n\tmargin: 0;\n\n\t.vs__selected {\n\t\tmin-height: 36px;\n\t\tpadding: 0 0.5em;\n\t}\n\n\t.vs__clear {\n\t\tmargin-right: 2px;\n\t}\n\n\t&.vs--open .vs__dropdown-toggle {\n\t\tborder-color: var(--color-primary-element);\n\t\tborder-bottom-color: transparent;\n\t}\n\n\t&:not(.vs--disabled, .vs--open) .vs__dropdown-toggle:hover {\n\t\tborder-color: var(--color-primary-element);\n\t}\n\n\t&.vs--disabled {\n\t\t.vs__search,\n\t\t.vs__selected {\n\t\t\tcolor: var(--color-text-maxcontrast);\n\t\t}\n\n\t\t.vs__clear,\n\t\t.vs__deselect {\n\t\t\tdisplay: none;\n\t\t}\n\t}\n\n\t&--no-wrap {\n\t\t.vs__selected-options {\n\t\t\tflex-wrap: nowrap;\n\t\t\toverflow: auto;\n\t\t\tmin-width: unset;\n\t\t\t.vs__selected {\n\t\t\t\tmin-width: unset;\n\t\t\t}\n\t\t}\n\t}\n\n\t&--drop-up {\n\t\t&.vs--open {\n\t\t\t.vs__dropdown-toggle {\n\t\t\t\tborder-radius: 0 0 var(--vs-border-radius) var(--vs-border-radius);\n\t\t\t\tborder-top-color: transparent;\n\t\t\t\tborder-bottom-color: var(--color-primary-element);\n\t\t\t}\n\t\t}\n\t}\n\n\t.vs__selected-options {\n\t\t// If search is hidden, ensure that the height of the search is the same\n\t\tmin-height: 40px; // 36px search height + 4px search margin\n\n\t\t// Hide search from dom if unused to prevent unneeded flex wrap\n\t\t.vs__selected ~ .vs__search[readonly] {\n\t\t\tposition: absolute;\n\t\t}\n\t}\n\n\t&.vs--single {\n\t\t&.vs--loading,\n\t\t&.vs--open {\n\t\t\t.vs__selected {\n\t\t\t\t// Fix `max-width` for `position: absolute`\n\t\t\t\tmax-width: 100%;\n\t\t\t}\n\t\t}\n\t\t.vs__selected-options {\n\t\t\tflex-wrap: nowrap;\n\t\t}\n\t}\n}\n\n.vs__dropdown-menu {\n\tborder-color: var(--color-primary-element) !important;\n\tpadding: 4px !important;\n\n\t&--floating {\n\t\t/* Fallback styles overidden by programmatically set inline styles */\n\t\twidth: max-content;\n\t\tposition: absolute;\n\t\ttop: 0;\n\t\tleft: 0;\n\n\t\t&-placement-top {\n\t\t\tborder-radius: var(--vs-border-radius) var(--vs-border-radius) 0 0 !important;\n\t\t\tborder-top-style: var(--vs-border-style) !important;\n\t\t\tborder-bottom-style: none !important;\n\t\t\tbox-shadow: 0px -1px 1px 0px var(--color-box-shadow) !important;\n\t\t}\n\t}\n\n\t.vs__dropdown-option {\n\t\tborder-radius: 6px !important;\n\t}\n\n\t.vs__no-options {\n\t\tcolor: var(--color-text-lighter) !important;\n\t}\n}\n","/**\n * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @author John Molakvoæ <skjnldsv@protonmail.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n\n// https://uxplanet.org/7-rules-for-mobile-ui-button-design-e9cf2ea54556\n// recommended is 48px\n// 44px is what we choose and have very good visual-to-usability ratio\n$clickable-area: 44px;\n\n// background icon size\n// also used for the scss icon font\n$icon-size: 16px;\n\n// icon padding for a $clickable-area width and a $icon-size icon\n// ( 44px - 16px ) / 2\n$icon-margin: math.div($clickable-area - $icon-size, 2);\n\n// transparency background for icons\n$icon-focus-bg: rgba(127, 127, 127, .25);\n\n// popovermenu arrow width from the triangle center\n$arrow-width: 9px;\n\n// opacities\n$opacity_disabled: .5;\n$opacity_normal: .7;\n$opacity_full: 1;\n\n// menu round background hover feedback\n// good looking on dark AND white bg\n$action-background-hover: rgba(127, 127, 127, .25);\n\n// various structure data used in the \n// `AppNavigation` component\n$header-height: 50px;\n$navigation-width: 300px;\n\n// mobile breakpoint\n$breakpoint-mobile: 1024px;\n\n// top-bar spacing\n$topbar-margin: 4px;\n\n// navigation spacing\n$app-navigation-settings-margin: 3px;\n"],sourceRoot:""}]);const s=r},3645:e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var a="",n=void 0!==t[5];return t[4]&&(a+="@supports (".concat(t[4],") {")),t[2]&&(a+="@media ".concat(t[2]," {")),n&&(a+="@layer".concat(t[5].length>0?" ".concat(t[5]):""," {")),a+=e(t),n&&(a+="}"),t[2]&&(a+="}"),t[4]&&(a+="}"),a})).join("")},t.i=function(e,a,n,o,i){"string"==typeof e&&(e=[[null,e,void 0]]);var r={};if(n)for(var s=0;s<this.length;s++){var l=this[s][0];null!=l&&(r[l]=!0)}for(var c=0;c<e.length;c++){var d=[].concat(e[c]);n&&r[d[0]]||(void 0!==i&&(void 0===d[5]||(d[1]="@layer".concat(d[5].length>0?" ".concat(d[5]):""," {").concat(d[1],"}")),d[5]=i),a&&(d[2]?(d[1]="@media ".concat(d[2]," {").concat(d[1],"}"),d[2]=a):d[2]=a),o&&(d[4]?(d[1]="@supports (".concat(d[4],") {").concat(d[1],"}"),d[4]=o):d[4]="".concat(o)),t.push(d))}},t}},1667:e=>{"use strict";e.exports=function(e,t){return t||(t={}),e?(e=String(e.__esModule?e.default:e),/^['"].*['"]$/.test(e)&&(e=e.slice(1,-1)),t.hash&&(e+=t.hash),/["'() \t\n]|(%20)/.test(e)||t.needQuotes?'"'.concat(e.replace(/"/g,'\\"').replace(/\n/g,"\\n"),'"'):e):e}},7537:e=>{"use strict";e.exports=function(e){var t=e[1],a=e[3];if(!a)return t;if("function"==typeof btoa){var n=btoa(unescape(encodeURIComponent(JSON.stringify(a)))),o="sourceMappingURL=data:application/json;charset=utf-8;base64,".concat(n),i="/*# ".concat(o," */");return[t].concat([i]).join("\n")}return[t].join("\n")}},3379:e=>{"use strict";var t=[];function a(e){for(var a=-1,n=0;n<t.length;n++)if(t[n].identifier===e){a=n;break}return a}function n(e,n){for(var i={},r=[],s=0;s<e.length;s++){var l=e[s],c=n.base?l[0]+n.base:l[0],d=i[c]||0,u="".concat(c," ").concat(d);i[c]=d+1;var p=a(u),A={css:l[1],media:l[2],sourceMap:l[3],supports:l[4],layer:l[5]};if(-1!==p)t[p].references++,t[p].updater(A);else{var m=o(A,n);n.byIndex=s,t.splice(s,0,{identifier:u,updater:m,references:1})}r.push(u)}return r}function o(e,t){var a=t.domAPI(t);a.update(e);return function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap&&t.supports===e.supports&&t.layer===e.layer)return;a.update(e=t)}else a.remove()}}e.exports=function(e,o){var i=n(e=e||[],o=o||{});return function(e){e=e||[];for(var r=0;r<i.length;r++){var s=a(i[r]);t[s].references--}for(var l=n(e,o),c=0;c<i.length;c++){var d=a(i[c]);0===t[d].references&&(t[d].updater(),t.splice(d,1))}i=l}}},569:e=>{"use strict";var t={};e.exports=function(e,a){var n=function(e){if(void 0===t[e]){var a=document.querySelector(e);if(window.HTMLIFrameElement&&a instanceof window.HTMLIFrameElement)try{a=a.contentDocument.head}catch(e){a=null}t[e]=a}return t[e]}(e);if(!n)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");n.appendChild(a)}},9216:e=>{"use strict";e.exports=function(e){var t=document.createElement("style");return e.setAttributes(t,e.attributes),e.insert(t,e.options),t}},3565:(e,t,a)=>{"use strict";e.exports=function(e){var t=a.nc;t&&e.setAttribute("nonce",t)}},7795:e=>{"use strict";e.exports=function(e){if("undefined"==typeof document)return{update:function(){},remove:function(){}};var t=e.insertStyleElement(e);return{update:function(a){!function(e,t,a){var n="";a.supports&&(n+="@supports (".concat(a.supports,") {")),a.media&&(n+="@media ".concat(a.media," {"));var o=void 0!==a.layer;o&&(n+="@layer".concat(a.layer.length>0?" ".concat(a.layer):""," {")),n+=a.css,o&&(n+="}"),a.media&&(n+="}"),a.supports&&(n+="}");var i=a.sourceMap;i&&"undefined"!=typeof btoa&&(n+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(i))))," */")),t.styleTagTransform(n,e,t.options)}(t,e,a)},remove:function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(t)}}}},4589:e=>{"use strict";e.exports=function(e,t){if(t.styleSheet)t.styleSheet.cssText=e;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(e))}}},3330:(e,t,a)=>{"use strict";a.d(t,{Z:()=>C});var n=a(4262);const o={name:"NcMentionBubble",props:{id:{type:String,required:!0},title:{type:String,required:!0},icon:{type:String,required:!0},iconUrl:{type:[String,null],default:null},source:{type:String,required:!0},primary:{type:Boolean,default:!1}},computed:{avatarUrl(){return this.iconUrl?this.iconUrl:this.id&&"users"===this.source?this.getAvatarUrl(this.id,44):null},mentionText(){return this.id.includes(" ")||this.id.includes("/")?'@"'.concat(this.id,'"'):"@".concat(this.id)}},methods:{getAvatarUrl:(e,t)=>(0,n.generateUrl)("/avatar/{user}/{size}",{user:e,size:t})}};var i=a(3379),r=a.n(i),s=a(7795),l=a.n(s),c=a(569),d=a.n(c),u=a(3565),p=a.n(u),A=a(9216),m=a.n(A),g=a(4589),h=a.n(g),v=a(6466),b={};b.styleTagTransform=h(),b.setAttributes=p(),b.insert=d().bind(null,"head"),b.domAPI=l(),b.insertStyleElement=m();r()(v.Z,b);v.Z&&v.Z.locals&&v.Z.locals;const C=(0,a(1900).Z)(o,(function(){var e=this,t=e._self._c;return t("span",{staticClass:"mention-bubble",class:{"mention-bubble--primary":e.primary},attrs:{contenteditable:"false"}},[t("span",{staticClass:"mention-bubble__wrapper"},[t("span",{staticClass:"mention-bubble__content"},[t("span",{staticClass:"mention-bubble__icon",class:[e.icon,"mention-bubble__icon--".concat(e.avatarUrl?"with-avatar":"")],style:e.avatarUrl?{backgroundImage:"url(".concat(e.avatarUrl,")")}:null}),e._v(" "),t("span",{staticClass:"mention-bubble__title",attrs:{role:"heading",title:e.title}})]),e._v(" "),t("span",{staticClass:"mention-bubble__select",attrs:{role:"none"}},[e._v(e._s(e.mentionText))])])])}),[],!1,null,"7dba3f6e",null).exports},9158:()=>{},5727:()=>{},3051:()=>{},2102:()=>{},6274:()=>{},1287:()=>{},8488:()=>{},9280:()=>{},2405:()=>{},8220:()=>{},4076:()=>{},1900:(e,t,a)=>{"use strict";function n(e,t,a,n,o,i,r,s){var l,c="function"==typeof e?e.options:e;if(t&&(c.render=t,c.staticRenderFns=a,c._compiled=!0),n&&(c.functional=!0),i&&(c._scopeId="data-v-"+i),r?(l=function(e){(e=e||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(e=__VUE_SSR_CONTEXT__),o&&o.call(this,e),e&&e._registeredComponents&&e._registeredComponents.add(r)},c._ssrRegister=l):o&&(l=s?function(){o.call(this,(c.functional?this.parent:this).$root.$options.shadowRoot)}:o),l)if(c.functional){c._injectStyles=l;var d=c.render;c.render=function(e,t){return l.call(t),d(e,t)}}else{var u=c.beforeCreate;c.beforeCreate=u?[].concat(u,l):[l]}return{exports:e,options:c}}a.d(t,{Z:()=>n})},7127:e=>{"use strict";e.exports="data:image/svg+xml;base64,PCEtLSBUaGlzIGljb24gaXMgcGFydCBvZiBNYXRlcmlhbCBVSSBJY29ucy4gQ29weXJpZ2h0IDIwMjAgR29vZ2xlIEluYy4sIEFwYWNoZS0yLjAgTGljZW5zZSAtLT4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHBhdGggZmlsbD0ibm9uZSIgZD0iTS00LTRoMjR2MjRILTR6Ii8+PHBhdGggZD0iTTYuOS4xQzMgLjYtLjEgNC0uMSA4YzAgNC40IDMuNiA4IDggOCA0IDAgNy40LTMgOC02LjktMS4yIDEuMy0yLjkgMi4xLTQuNyAyLjEtMy41IDAtNi40LTIuOS02LjQtNi40IDAtMS45LjgtMy42IDIuMS00Ljd6IiBmaWxsPSIjZjRhMzMxIi8+PC9zdmc+Cg=="},2605:e=>{"use strict";e.exports="data:image/svg+xml;base64,PCEtLSBUaGlzIGljb24gaXMgcGFydCBvZiBNYXRlcmlhbCBVSSBJY29ucy4gQ29weXJpZ2h0IDIwMjAgR29vZ2xlIEluYy4sIEFwYWNoZS0yLjAgTGljZW5zZSAtLT4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHBhdGggZD0iTS00LTRoMjR2MjRILTRWLTR6IiBmaWxsPSJub25lIi8+PHBhdGggZD0iTTggMEMzLjYgMCAwIDMuNiAwIDhzMy42IDggOCA4IDgtMy42IDgtOC0zLjYtOC04LTh6IiBmaWxsPSIjZWQ0ODRjIi8+PHBhdGggZD0iTTUgNi41aDZjLjggMCAxLjUuNyAxLjUgMS41cy0uNyAxLjUtMS41IDEuNUg1Yy0uOCAwLTEuNS0uNy0xLjUtMS41UzQuMiA2LjUgNSA2LjV6IiBmaWxsPSIjZmRmZmZmIi8+PC9zdmc+Cg=="},3423:e=>{"use strict";e.exports="data:image/svg+xml;base64,PCEtLSBUaGlzIGljb24gaXMgcGFydCBvZiBNYXRlcmlhbCBVSSBJY29ucy4gQ29weXJpZ2h0IDIwMjAgR29vZ2xlIEluYy4sIEFwYWNoZS0yLjAgTGljZW5zZSAtLT4KPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PHBhdGggZD0iTTQuOCAxMS4yaDYuNFY0LjhINC44djYuNHpNOCAwQzMuNiAwIDAgMy42IDAgOHMzLjYgOCA4IDggOC0zLjYgOC04LTMuNi04LTgtOHoiIGZpbGw9IiM0OWIzODIiLz48L3N2Zz4K"},3607:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.js")},768:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.cjs")},7672:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/browser-storage */ "./node_modules/@nextcloud/browser-storage/dist/index.js")},542:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.cjs")},7931:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/l10n/gettext */ "./node_modules/@nextcloud/l10n/dist/gettext.js")},4262:e=>{"use strict";e.exports=__webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js")},4055:e=>{"use strict";e.exports=__webpack_require__(/*! @vueuse/components */ "./node_modules/@vueuse/components/index.cjs")},9454:e=>{"use strict";e.exports=__webpack_require__(/*! floating-vue */ "./node_modules/floating-vue/dist/floating-vue.es.js")},4505:e=>{"use strict";e.exports=__webpack_require__(/*! focus-trap */ "./node_modules/focus-trap/dist/focus-trap.esm.js")},2734:e=>{"use strict";e.exports=__webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js")},4875:e=>{"use strict";e.exports=__webpack_require__(/*! vue-material-design-icons/ChevronDown.vue */ "./node_modules/vue-material-design-icons/ChevronDown.vue")},8618:e=>{"use strict";e.exports=__webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue")},1441:e=>{"use strict";e.exports=__webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue")}},t={};function a(n){var o=t[n];if(void 0!==o)return o.exports;var i=t[n]={id:n,exports:{}};return e[n](i,i.exports,a),i.exports}a.m=e,a.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return a.d(t,{a:t}),t},a.d=(e,t)=>{for(var n in t)a.o(t,n)&&!a.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),a.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},a.b=document.baseURI||self.location.href,a.nc=void 0;var n={};return(()=>{"use strict";a.r(n),a.d(n,{default:()=>g});var e=a(4378),t=a(7357),o=a(768),i=a.n(o),r=a(4262);const s=e=>{let t={};if(1===e.nodeType){if(e.attributes.length>0){t["@attributes"]={};for(let a=0;a<e.attributes.length;a++){const n=e.attributes.item(a);t["@attributes"][n.nodeName]=n.nodeValue}}}else 3===e.nodeType&&(t=e.nodeValue);if(e.hasChildNodes())for(let a=0;a<e.childNodes.length;a++){const n=e.childNodes.item(a),o=n.nodeName;if(void 0===t[o])t[o]=s(n);else{if(void 0===t[o].push){const e=t[o];t[o]=[],t[o].push(e)}t[o].push(s(n))}}return t},l=e=>{const t=s((e=>{let t=null;try{t=(new DOMParser).parseFromString(e,"text/xml")}catch(e){console.error("Failed to parse xml document",e)}return t})(e)),a=t["d:multistatus"]["d:response"],n=[];for(const e in a){const t=a[e]["d:propstat"];"HTTP/1.1 200 OK"===t["d:status"]["#text"]&&n.push({id:parseInt(t["d:prop"]["oc:id"]["#text"]),displayName:t["d:prop"]["oc:display-name"]["#text"],canAssign:"true"===t["d:prop"]["oc:can-assign"]["#text"],userAssignable:"true"===t["d:prop"]["oc:user-assignable"]["#text"],userVisible:"true"===t["d:prop"]["oc:user-visible"]["#text"]})}return n};var c=a(932);const d={name:"NcSelectTags",components:{NcEllipsisedOption:e.default,NcSelect:t.default},props:{...t.default.props,fetchTags:{type:Boolean,default:!0},getOptionLabel:{type:Function,default:e=>{const{displayName:t,userVisible:a,userAssignable:n}=e;return!1===a?(0,c.t)("{tag} (invisible)",{tag:t}):!1===n?(0,c.t)("{tag} (restricted)",{tag:t}):t}},limit:{type:Number,default:5},multiple:{type:Boolean,default:!0},optionsFilter:{type:Function,default:null},passthru:{type:Boolean,default:!1},placeholder:{type:String,default:(0,c.t)("Select a tag")},value:{type:[Number,Array],default:null}," ":{}},emits:["input"," "],data:()=>({search:"",availableTags:[]}),computed:{availableOptions(){return this.optionsFilter?this.tags.filter(this.optionsFilter):this.tags},localValue(){return 0===this.tags.length?[]:this.multiple?this.value.filter((e=>""!==e)).map((e=>this.tags.find((t=>t.id===e)))):this.tags.find((e=>e.id===this.value))},propsToForward(){const{fetchTags:e,optionsFilter:t,passthru:a,...n}=this.$props;return n},tags(){return this.fetchTags?this.availableTags:this.options}},async created(){if(this.fetchTags)try{const e=await async function(){if(window.NextcloudVueDocs)return Promise.resolve(l(window.NextcloudVueDocs.tags));const e=await i()({method:"PROPFIND",url:(0,r.generateRemoteUrl)("dav")+"/systemtags/",data:'<?xml version="1.0"?>\n\t\t\t\t\t<d:propfind  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">\n\t\t\t\t\t  <d:prop>\n\t\t\t\t\t\t<oc:id />\n\t\t\t\t\t\t<oc:display-name />\n\t\t\t\t\t\t<oc:user-visible />\n\t\t\t\t\t\t<oc:user-assignable />\n\t\t\t\t\t\t<oc:can-assign />\n\t\t\t\t\t  </d:prop>\n\t\t\t\t\t</d:propfind>'});return l(e.data)}();this.availableTags=e}catch(e){console.error("Loading systemtags failed",e)}},methods:{handleInput(e){this.multiple?this.$emit("input",e.map((e=>e.id))):null===e?this.$emit("input",null):this.$emit("input",e.id)}}};var u=a(1900),p=a(4076),A=a.n(p),m=(0,u.Z)(d,(function(){var e=this,t=e._self._c;return t("NcSelect",e._g(e._b({attrs:{options:e.availableOptions,"close-on-select":!e.multiple,value:e.passthru?e.value:e.localValue},on:{search:t=>e.search=t},scopedSlots:e._u([{key:"option",fn:function(a){return[t("NcEllipsisedOption",{attrs:{name:e.getOptionLabel(a),search:e.search}})]}},{key:"selected-option",fn:function(a){return[t("NcEllipsisedOption",{attrs:{name:e.getOptionLabel(a),search:e.search}})]}},e._l(e.$scopedSlots,(function(t,a){return{key:a,fn:function(t){return[e._t(a,null,null,t)]}}}))],null,!0)},"NcSelect",e.propsToForward,!1),{...e.$listeners,input:e.passthru?e.$listeners.input:e.handleInput}))}),[],!1,null,null,null);"function"==typeof A()&&A()(m);const g=m.exports})(),n})()));
//# sourceMappingURL=NcSelectTags.js.map

/***/ }),

/***/ "./node_modules/@skjnldsv/sanitize-svg/dist/index.esm.js":
/*!***************************************************************!*\
  !*** ./node_modules/@skjnldsv/sanitize-svg/dist/index.esm.js ***!
  \***************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   sanitizeSVG: function() { return /* binding */ sanitizeSVG; }
/* harmony export */ });
/* harmony import */ var buffer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! buffer */ "./node_modules/buffer/index.js");
/* harmony import */ var is_svg__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! is-svg */ "./node_modules/@skjnldsv/sanitize-svg/node_modules/is-svg/index.js");
/* harmony import */ var is_svg__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(is_svg__WEBPACK_IMPORTED_MODULE_1__);



const readAsText = (svg) => new Promise((resolve) => {
    if (!isFile(svg)) {
        resolve(svg.toString('utf-8'));
    }
    else {
        const fileReader = new FileReader();
        fileReader.onload = () => {
            resolve(fileReader.result);
        };
        fileReader.readAsText(svg);
    }
});
const isFile = (obj) => {
    return obj.size !== undefined;
};
const sanitizeSVG = async (svg) => {
    if (!svg) {
        throw new Error('Not an svg');
    }
    let svgText = '';
    if (buffer__WEBPACK_IMPORTED_MODULE_0__.Buffer.isBuffer(svg) || svg instanceof File) {
        svgText = await readAsText(svg);
    }
    else {
        svgText = svg;
    }
    if (!is_svg__WEBPACK_IMPORTED_MODULE_1___default()(svgText)) {
        throw new Error('Not an svg');
    }
    const div = document.createElement('div');
    div.innerHTML = svgText;
    const svgEl = div.firstElementChild;
    const attributes = Array.from(svgEl.attributes).map(({ name }) => name);
    const hasScriptAttr = !!attributes.find((attr) => attr.startsWith('on'));
    const scripts = svgEl.getElementsByTagName('script');
    return scripts.length === 0 && !hasScriptAttr ? svg : null;
};


//# sourceMappingURL=index.esm.js.map


/***/ }),

/***/ "./apps/files/src/models/Tab.js":
/*!**************************************!*\
  !*** ./apps/files/src/models/Tab.js ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Tab; }
/* harmony export */ });
/* harmony import */ var _skjnldsv_sanitize_svg__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @skjnldsv/sanitize-svg */ "./node_modules/@skjnldsv/sanitize-svg/dist/index.esm.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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

var Tab = /*#__PURE__*/function () {
  /**
   * Create a new tab instance
   *
   * @param {object} options destructuring object
   * @param {string} options.id the unique id of this tab
   * @param {string} options.name the translated tab name
   * @param {?string} options.icon the icon css class
   * @param {?string} options.iconSvg the icon in svg format
   * @param {Function} options.mount function to mount the tab
   * @param {Function} [options.setIsActive] function to forward the active state of the tab
   * @param {Function} options.update function to update the tab
   * @param {Function} options.destroy function to destroy the tab
   * @param {Function} [options.enabled] define conditions whether this tab is active. Must returns a boolean
   * @param {Function} [options.scrollBottomReached] executed when the tab is scrolled to the bottom
   */
  function Tab() {
    var _this = this;
    var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
      id = _ref.id,
      name = _ref.name,
      icon = _ref.icon,
      iconSvg = _ref.iconSvg,
      mount = _ref.mount,
      setIsActive = _ref.setIsActive,
      update = _ref.update,
      destroy = _ref.destroy,
      enabled = _ref.enabled,
      scrollBottomReached = _ref.scrollBottomReached;
    _classCallCheck(this, Tab);
    _defineProperty(this, "_id", void 0);
    _defineProperty(this, "_name", void 0);
    _defineProperty(this, "_icon", void 0);
    _defineProperty(this, "_iconSvgSanitized", void 0);
    _defineProperty(this, "_mount", void 0);
    _defineProperty(this, "_setIsActive", void 0);
    _defineProperty(this, "_update", void 0);
    _defineProperty(this, "_destroy", void 0);
    _defineProperty(this, "_enabled", void 0);
    _defineProperty(this, "_scrollBottomReached", void 0);
    if (enabled === undefined) {
      enabled = function enabled() {
        return true;
      };
    }
    if (scrollBottomReached === undefined) {
      scrollBottomReached = function scrollBottomReached() {};
    }

    // Sanity checks
    if (typeof id !== 'string' || id.trim() === '') {
      throw new Error('The id argument is not a valid string');
    }
    if (typeof name !== 'string' || name.trim() === '') {
      throw new Error('The name argument is not a valid string');
    }
    if ((typeof icon !== 'string' || icon.trim() === '') && typeof iconSvg !== 'string') {
      throw new Error('Missing valid string for icon or iconSvg argument');
    }
    if (typeof mount !== 'function') {
      throw new Error('The mount argument should be a function');
    }
    if (setIsActive !== undefined && typeof setIsActive !== 'function') {
      throw new Error('The setIsActive argument should be a function');
    }
    if (typeof update !== 'function') {
      throw new Error('The update argument should be a function');
    }
    if (typeof destroy !== 'function') {
      throw new Error('The destroy argument should be a function');
    }
    if (typeof enabled !== 'function') {
      throw new Error('The enabled argument should be a function');
    }
    if (typeof scrollBottomReached !== 'function') {
      throw new Error('The scrollBottomReached argument should be a function');
    }
    this._id = id;
    this._name = name;
    this._icon = icon;
    this._mount = mount;
    this._setIsActive = setIsActive;
    this._update = update;
    this._destroy = destroy;
    this._enabled = enabled;
    this._scrollBottomReached = scrollBottomReached;
    if (typeof iconSvg === 'string') {
      (0,_skjnldsv_sanitize_svg__WEBPACK_IMPORTED_MODULE_0__.sanitizeSVG)(iconSvg).then(function (sanitizedSvg) {
        _this._iconSvgSanitized = sanitizedSvg;
      });
    }
  }
  _createClass(Tab, [{
    key: "id",
    get: function get() {
      return this._id;
    }
  }, {
    key: "name",
    get: function get() {
      return this._name;
    }
  }, {
    key: "icon",
    get: function get() {
      return this._icon;
    }
  }, {
    key: "iconSvg",
    get: function get() {
      return this._iconSvgSanitized;
    }
  }, {
    key: "mount",
    get: function get() {
      return this._mount;
    }
  }, {
    key: "setIsActive",
    get: function get() {
      return this._setIsActive || function () {
        return undefined;
      };
    }
  }, {
    key: "update",
    get: function get() {
      return this._update;
    }
  }, {
    key: "destroy",
    get: function get() {
      return this._destroy;
    }
  }, {
    key: "enabled",
    get: function get() {
      return this._enabled;
    }
  }, {
    key: "scrollBottomReached",
    get: function get() {
      return this._scrollBottomReached;
    }
  }]);
  return Tab;
}();


/***/ }),

/***/ "./apps/files/src/services/FileInfo.js":
/*!*********************************************!*\
  !*** ./apps/files/src/services/FileInfo.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* export default binding */ __WEBPACK_DEFAULT_EXPORT__; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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




/**
 * @param {any} url -
 */
/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(_x) {
  return _ref.apply(this, arguments);
}
function _ref() {
  _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(url) {
    var response, file, fileInfo;
    return _regeneratorRuntime().wrap(function _callee$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return (0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"])({
            method: 'PROPFIND',
            url: url,
            data: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetDefaultPropfind)()
          });
        case 2:
          response = _context.sent;
          // TODO: create new parser or use cdav-lib when available
          file = OCA.Files.App.fileList.filesClient._client.parseMultiStatus(response.data); // TODO: create new parser or use cdav-lib when available
          fileInfo = OCA.Files.App.fileList.filesClient._parseFileInfo(file[0]); // TODO remove when no more legacy backbone is used
          fileInfo.get = function (key) {
            return fileInfo[key];
          };
          fileInfo.isDirectory = function () {
            return fileInfo.mimetype === 'httpd/unix-directory';
          };
          return _context.abrupt("return", fileInfo);
        case 8:
        case "end":
          return _context.stop();
      }
    }, _callee);
  }));
  return _ref.apply(this, arguments);
}

/***/ }),

/***/ "./apps/files/src/services/Sidebar.js":
/*!********************************************!*\
  !*** ./apps/files/src/services/Sidebar.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ Sidebar; }
/* harmony export */ });
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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
var Sidebar = /*#__PURE__*/function () {
  function Sidebar() {
    _classCallCheck(this, Sidebar);
    _defineProperty(this, "_state", void 0);
    // init empty state
    this._state = {};

    // init default values
    this._state.tabs = [];
    this._state.views = [];
    this._state.file = '';
    this._state.activeTab = '';
    console.debug('OCA.Files.Sidebar initialized');
  }

  /**
   * Get the sidebar state
   *
   * @readonly
   * @memberof Sidebar
   * @return {object} the data state
   */
  _createClass(Sidebar, [{
    key: "state",
    get: function get() {
      return this._state;
    }

    /**
     * Register a new tab view
     *
     * @memberof Sidebar
     * @param {object} tab a new unregistered tab
     * @return {boolean}
     */
  }, {
    key: "registerTab",
    value: function registerTab(tab) {
      var hasDuplicate = this._state.tabs.findIndex(function (check) {
        return check.id === tab.id;
      }) > -1;
      if (!hasDuplicate) {
        this._state.tabs.push(tab);
        return true;
      }
      console.error("An tab with the same id ".concat(tab.id, " already exists"), tab);
      return false;
    }
  }, {
    key: "registerSecondaryView",
    value: function registerSecondaryView(view) {
      var hasDuplicate = this._state.views.findIndex(function (check) {
        return check.id === view.id;
      }) > -1;
      if (!hasDuplicate) {
        this._state.views.push(view);
        return true;
      }
      console.error('A similar view already exists', view);
      return false;
    }

    /**
     * Return current opened file
     *
     * @memberof Sidebar
     * @return {string} the current opened file
     */
  }, {
    key: "file",
    get: function get() {
      return this._state.file;
    }

    /**
     * Set the current visible sidebar tab
     *
     * @memberof Sidebar
     * @param {string} id the tab unique id
     */
  }, {
    key: "setActiveTab",
    value: function setActiveTab(id) {
      this._state.activeTab = id;
    }
  }]);
  return Sidebar;
}();


/***/ }),

/***/ "./apps/files/src/sidebar.js":
/*!***********************************!*\
  !*** ./apps/files/src/sidebar.js ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _views_Sidebar_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./views/Sidebar.vue */ "./apps/files/src/views/Sidebar.vue");
/* harmony import */ var _services_Sidebar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./services/Sidebar.js */ "./apps/files/src/services/Sidebar.js");
/* harmony import */ var _models_Tab_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./models/Tab.js */ "./apps/files/src/models/Tab.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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






vue__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.t = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate;

// Init Sidebar Service
if (!window.OCA.Files) {
  window.OCA.Files = {};
}
Object.assign(window.OCA.Files, {
  Sidebar: new _services_Sidebar_js__WEBPACK_IMPORTED_MODULE_2__["default"]()
});
Object.assign(window.OCA.Files.Sidebar, {
  Tab: _models_Tab_js__WEBPACK_IMPORTED_MODULE_3__["default"]
});
console.debug('OCA.Files.Sidebar initialized');
window.addEventListener('DOMContentLoaded', function () {
  var contentElement = document.querySelector('body > .content') || document.querySelector('body > #content');

  // Make sure we have a proper layout
  if (contentElement) {
    // Make sure we have a mountpoint
    if (!document.getElementById('app-sidebar')) {
      var sidebarElement = document.createElement('div');
      sidebarElement.id = 'app-sidebar';
      contentElement.appendChild(sidebarElement);
    }
  }

  // Init vue app
  var View = vue__WEBPACK_IMPORTED_MODULE_4__["default"].extend(_views_Sidebar_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
  var AppSidebar = new View({
    name: 'SidebarRoot'
  });
  AppSidebar.$mount('#app-sidebar');
  window.OCA.Files.Sidebar.open = AppSidebar.open;
  window.OCA.Files.Sidebar.close = AppSidebar.close;
  window.OCA.Files.Sidebar.setFullScreenMode = AppSidebar.setFullScreenMode;
});

/***/ }),

/***/ "./apps/systemtags/src/logger.ts":
/*!***************************************!*\
  !*** ./apps/systemtags/src/logger.ts ***!
  \***************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   logger: function() { return /* binding */ logger; }
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.js");
/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

var logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('systemtags').detectUser().build();

/***/ }),

/***/ "./apps/systemtags/src/services/api.ts":
/*!*********************************************!*\
  !*** ./apps/systemtags/src/services/api.ts ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createTag: function() { return /* binding */ createTag; },
/* harmony export */   deleteTag: function() { return /* binding */ deleteTag; },
/* harmony export */   fetchLastUsedTagIds: function() { return /* binding */ fetchLastUsedTagIds; },
/* harmony export */   fetchSelectedTags: function() { return /* binding */ fetchSelectedTags; },
/* harmony export */   fetchTags: function() { return /* binding */ fetchTags; },
/* harmony export */   selectTag: function() { return /* binding */ selectTag; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _davClient_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./davClient.js */ "./apps/systemtags/src/services/davClient.ts");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils.js */ "./apps/systemtags/src/utils.ts");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger.js */ "./apps/systemtags/src/logger.ts");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */






var fetchTagsBody = "<?xml version=\"1.0\"?>\n<d:propfind  xmlns:d=\"DAV:\" xmlns:oc=\"http://owncloud.org/ns\">\n\t<d:prop>\n\t\t<oc:id />\n\t\t<oc:display-name />\n\t\t<oc:user-visible />\n\t\t<oc:user-assignable />\n\t\t<oc:can-assign />\n\t</d:prop>\n</d:propfind>";
var fetchTags = /*#__PURE__*/function () {
  var _ref = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
    var path, _yield$davClient$getD, tags;
    return _regeneratorRuntime().wrap(function _callee$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          path = '/systemtags';
          _context.prev = 1;
          _context.next = 4;
          return _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.getDirectoryContents(path, {
            data: fetchTagsBody,
            details: true,
            glob: '/systemtags/*' // Filter out first empty tag
          });
        case 4:
          _yield$davClient$getD = _context.sent;
          tags = _yield$davClient$getD.data;
          return _context.abrupt("return", (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__.parseTags)(tags));
        case 9:
          _context.prev = 9;
          _context.t0 = _context["catch"](1);
          _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load tags'), {
            error: _context.t0
          });
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load tags'));
        case 13:
        case "end":
          return _context.stop();
      }
    }, _callee, null, [[1, 9]]);
  }));
  return function fetchTags() {
    return _ref.apply(this, arguments);
  };
}();
var fetchLastUsedTagIds = /*#__PURE__*/function () {
  var _ref2 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
    var url, _yield$axios$get, lastUsedTagIds;
    return _regeneratorRuntime().wrap(function _callee2$(_context2) {
      while (1) switch (_context2.prev = _context2.next) {
        case 0:
          url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/systemtags/lastused');
          _context2.prev = 1;
          _context2.next = 4;
          return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
        case 4:
          _yield$axios$get = _context2.sent;
          lastUsedTagIds = _yield$axios$get.data;
          return _context2.abrupt("return", lastUsedTagIds.map(Number));
        case 9:
          _context2.prev = 9;
          _context2.t0 = _context2["catch"](1);
          _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load last used tags'), {
            error: _context2.t0
          });
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load last used tags'));
        case 13:
        case "end":
          return _context2.stop();
      }
    }, _callee2, null, [[1, 9]]);
  }));
  return function fetchLastUsedTagIds() {
    return _ref2.apply(this, arguments);
  };
}();
var fetchSelectedTags = /*#__PURE__*/function () {
  var _ref3 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3(fileId) {
    var path, _yield$davClient$getD2, tags;
    return _regeneratorRuntime().wrap(function _callee3$(_context3) {
      while (1) switch (_context3.prev = _context3.next) {
        case 0:
          path = '/systemtags-relations/files/' + fileId;
          _context3.prev = 1;
          _context3.next = 4;
          return _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.getDirectoryContents(path, {
            data: fetchTagsBody,
            details: true,
            glob: '/systemtags-relations/files/*/*' // Filter out first empty tag
          });
        case 4:
          _yield$davClient$getD2 = _context3.sent;
          tags = _yield$davClient$getD2.data;
          return _context3.abrupt("return", (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__.parseTags)(tags));
        case 9:
          _context3.prev = 9;
          _context3.t0 = _context3["catch"](1);
          _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load selected tags'), {
            error: _context3.t0
          });
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load selected tags'));
        case 13:
        case "end":
          return _context3.stop();
      }
    }, _callee3, null, [[1, 9]]);
  }));
  return function fetchSelectedTags(_x) {
    return _ref3.apply(this, arguments);
  };
}();
var selectTag = /*#__PURE__*/function () {
  var _ref4 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4(fileId, tag) {
    var path, tagToPut;
    return _regeneratorRuntime().wrap(function _callee4$(_context4) {
      while (1) switch (_context4.prev = _context4.next) {
        case 0:
          path = '/systemtags-relations/files/' + fileId + '/' + tag.id;
          tagToPut = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__.formatTag)(tag);
          _context4.prev = 2;
          _context4.next = 5;
          return _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.customRequest(path, {
            method: 'PUT',
            data: tagToPut
          });
        case 5:
          _context4.next = 11;
          break;
        case 7:
          _context4.prev = 7;
          _context4.t0 = _context4["catch"](2);
          _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to select tag'), {
            error: _context4.t0
          });
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to select tag'));
        case 11:
        case "end":
          return _context4.stop();
      }
    }, _callee4, null, [[2, 7]]);
  }));
  return function selectTag(_x2, _x3) {
    return _ref4.apply(this, arguments);
  };
}();
/**
 * @return created tag id
 */
var createTag = /*#__PURE__*/function () {
  var _ref5 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5(fileId, tag) {
    var path, tagToPost, _yield$davClient$cust, headers, contentLocation, tagToPut;
    return _regeneratorRuntime().wrap(function _callee5$(_context5) {
      while (1) switch (_context5.prev = _context5.next) {
        case 0:
          path = '/systemtags';
          tagToPost = (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__.formatTag)(tag);
          _context5.prev = 2;
          _context5.next = 5;
          return _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.customRequest(path, {
            method: 'POST',
            data: tagToPost
          });
        case 5:
          _yield$davClient$cust = _context5.sent;
          headers = _yield$davClient$cust.headers;
          contentLocation = headers.get('content-location');
          if (!contentLocation) {
            _context5.next = 13;
            break;
          }
          tagToPut = _objectSpread(_objectSpread({}, tagToPost), {}, {
            id: (0,_utils_js__WEBPACK_IMPORTED_MODULE_4__.parseIdFromLocation)(contentLocation)
          });
          _context5.next = 12;
          return selectTag(fileId, tagToPut);
        case 12:
          return _context5.abrupt("return", tagToPut.id);
        case 13:
          _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Missing "Content-Location" header'));
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Missing "Content-Location" header'));
        case 17:
          _context5.prev = 17;
          _context5.t0 = _context5["catch"](2);
          _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to create tag'), {
            error: _context5.t0
          });
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to create tag'));
        case 21:
        case "end":
          return _context5.stop();
      }
    }, _callee5, null, [[2, 17]]);
  }));
  return function createTag(_x4, _x5) {
    return _ref5.apply(this, arguments);
  };
}();
var deleteTag = /*#__PURE__*/function () {
  var _ref6 = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee6(fileId, tag) {
    var path;
    return _regeneratorRuntime().wrap(function _callee6$(_context6) {
      while (1) switch (_context6.prev = _context6.next) {
        case 0:
          path = '/systemtags-relations/files/' + fileId + '/' + tag.id;
          _context6.prev = 1;
          _context6.next = 4;
          return _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.deleteFile(path);
        case 4:
          _context6.next = 10;
          break;
        case 6:
          _context6.prev = 6;
          _context6.t0 = _context6["catch"](1);
          _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to delete tag'), {
            error: _context6.t0
          });
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to delete tag'));
        case 10:
        case "end":
          return _context6.stop();
      }
    }, _callee6, null, [[1, 6]]);
  }));
  return function deleteTag(_x6, _x7) {
    return _ref6.apply(this, arguments);
  };
}();

/***/ }),

/***/ "./apps/systemtags/src/services/davClient.ts":
/*!***************************************************!*\
  !*** ./apps/systemtags/src/services/davClient.ts ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   davClient: function() { return /* binding */ davClient; }
/* harmony export */ });
/* harmony import */ var webdav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! webdav */ "./node_modules/webdav/dist/web/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
var _getRequestToken;
/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */



var rootUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateRemoteUrl)('dav');
var davClient = (0,webdav__WEBPACK_IMPORTED_MODULE_0__.createClient)(rootUrl, {
  headers: {
    requesttoken: (_getRequestToken = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getRequestToken)()) !== null && _getRequestToken !== void 0 ? _getRequestToken : ''
  }
});

/***/ }),

/***/ "./apps/systemtags/src/utils.ts":
/*!**************************************!*\
  !*** ./apps/systemtags/src/utils.ts ***!
  \**************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   formatTag: function() { return /* binding */ formatTag; },
/* harmony export */   parseIdFromLocation: function() { return /* binding */ parseIdFromLocation; },
/* harmony export */   parseTags: function() { return /* binding */ parseTags; }
/* harmony export */ });
/* harmony import */ var camelcase__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! camelcase */ "./node_modules/camelcase/index.js");
/* harmony import */ var camelcase__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(camelcase__WEBPACK_IMPORTED_MODULE_0__);
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(arr, i) { var _i = null == arr ? null : "undefined" != typeof Symbol && arr[Symbol.iterator] || arr["@@iterator"]; if (null != _i) { var _s, _e, _x, _r, _arr = [], _n = !0, _d = !1; try { if (_x = (_i = _i.call(arr)).next, 0 === i) { if (Object(_i) !== _i) return; _n = !1; } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0); } catch (err) { _d = !0, _e = err; } finally { try { if (!_n && null != _i.return && (_r = _i.return(), Object(_r) !== _r)) return; } finally { if (_d) throw _e; } } return _arr; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

var parseTags = function parseTags(tags) {
  return tags.map(function (_ref) {
    var props = _ref.props;
    return Object.fromEntries(Object.entries(props).map(function (_ref2) {
      var _ref3 = _slicedToArray(_ref2, 2),
        key = _ref3[0],
        value = _ref3[1];
      return [camelcase__WEBPACK_IMPORTED_MODULE_0___default()(key), value];
    }));
  });
};
/**
 * Parse id from `Content-Location` header
 */
var parseIdFromLocation = function parseIdFromLocation(url) {
  var queryPos = url.indexOf('?');
  if (queryPos > 0) {
    url = url.substring(0, queryPos);
  }
  var parts = url.split('/');
  var result;
  do {
    result = parts[parts.length - 1];
    parts.pop();
    // note: first result can be empty when there is a trailing slash,
    // so we take the part before that
  } while (!result && parts.length > 0);
  return Number(result);
};
var formatTag = function formatTag(initialTag) {
  var tag = _objectSpread({}, initialTag);
  if (tag.name && !tag.displayName) {
    return tag;
  }
  tag.name = tag.displayName;
  delete tag.displayName;
  return tag;
};

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts&":
/*!**********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts& ***!
  \**********************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelectTags_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelectTags.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelectTags.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelectTags_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSelectTags_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _services_api_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../services/api.js */ "./apps/systemtags/src/services/api.ts");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
var _excluded = ["id", "displayName"];
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(arr, i) { var _i = null == arr ? null : "undefined" != typeof Symbol && arr[Symbol.iterator] || arr["@@iterator"]; if (null != _i) { var _s, _e, _x, _r, _arr = [], _n = !0, _d = !1; try { if (_x = (_i = _i.call(arr)).next, 0 === i) { if (Object(_i) !== _i) return; _n = !1; } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0); } catch (err) { _d = !0, _e = err; } finally { try { if (!_n && null != _i.return && (_r = _i.return(), Object(_r) !== _r)) return; } finally { if (_d) throw _e; } } return _arr; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function _objectWithoutProperties(source, excluded) { if (source == null) return {}; var target = _objectWithoutPropertiesLoose(source, excluded); var key, i; if (Object.getOwnPropertySymbols) { var sourceSymbolKeys = Object.getOwnPropertySymbols(source); for (i = 0; i < sourceSymbolKeys.length; i++) { key = sourceSymbolKeys[i]; if (excluded.indexOf(key) >= 0) continue; if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue; target[key] = source[key]; } } return target; }
function _objectWithoutPropertiesLoose(source, excluded) { if (source == null) return {}; var target = {}; var sourceKeys = Object.keys(source); var key, i; for (i = 0; i < sourceKeys.length; i++) { key = sourceKeys[i]; if (excluded.indexOf(key) >= 0) continue; target[key] = source[key]; } return target; }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e2) { throw _e2; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e3) { didErr = true; err = _e3; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
// FIXME Vue TypeScript ESLint errors
/* eslint-disable */






var defaultBaseTag = {
  userVisible: true,
  userAssignable: true,
  canAssign: true
};
/* harmony default export */ __webpack_exports__["default"] = (vue__WEBPACK_IMPORTED_MODULE_5__["default"].extend({
  name: 'SystemTags',
  components: {
    NcLoadingIcon: (_nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcSelectTags: (_nextcloud_vue_dist_Components_NcSelectTags_js__WEBPACK_IMPORTED_MODULE_1___default())
  },
  props: {
    fileId: {
      type: Number,
      required: true
    }
  },
  data: function data() {
    return {
      sortedTags: [],
      selectedTags: [],
      loadingTags: false,
      loading: false
    };
  },
  created: function created() {
    var _this = this;
    return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
      var tags, lastUsedOrder, lastUsedTags, remainingTags, _iterator, _step, tag, sortByLastUsed;
      return _regeneratorRuntime().wrap(function _callee$(_context) {
        while (1) switch (_context.prev = _context.next) {
          case 0:
            _context.prev = 0;
            _context.next = 3;
            return (0,_services_api_js__WEBPACK_IMPORTED_MODULE_4__.fetchTags)();
          case 3:
            tags = _context.sent;
            _context.next = 6;
            return (0,_services_api_js__WEBPACK_IMPORTED_MODULE_4__.fetchLastUsedTagIds)();
          case 6:
            lastUsedOrder = _context.sent;
            lastUsedTags = [];
            remainingTags = [];
            _iterator = _createForOfIteratorHelper(tags);
            _context.prev = 10;
            _iterator.s();
          case 12:
            if ((_step = _iterator.n()).done) {
              _context.next = 20;
              break;
            }
            tag = _step.value;
            if (!lastUsedOrder.includes(tag.id)) {
              _context.next = 17;
              break;
            }
            lastUsedTags.push(tag);
            return _context.abrupt("continue", 18);
          case 17:
            remainingTags.push(tag);
          case 18:
            _context.next = 12;
            break;
          case 20:
            _context.next = 25;
            break;
          case 22:
            _context.prev = 22;
            _context.t0 = _context["catch"](10);
            _iterator.e(_context.t0);
          case 25:
            _context.prev = 25;
            _iterator.f();
            return _context.finish(25);
          case 28:
            sortByLastUsed = function sortByLastUsed(a, b) {
              return lastUsedOrder.indexOf(a.id) - lastUsedOrder.indexOf(b.id);
            };
            lastUsedTags.sort(sortByLastUsed);
            _this.sortedTags = [].concat(lastUsedTags, remainingTags);
            _context.next = 36;
            break;
          case 33:
            _context.prev = 33;
            _context.t1 = _context["catch"](0);
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load tags'));
          case 36:
          case "end":
            return _context.stop();
        }
      }, _callee, null, [[0, 33], [10, 22, 25, 28]]);
    }))();
  },
  watch: {
    fileId: {
      immediate: true,
      handler: function handler() {
        var _this2 = this;
        return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
          return _regeneratorRuntime().wrap(function _callee2$(_context2) {
            while (1) switch (_context2.prev = _context2.next) {
              case 0:
                _this2.loadingTags = true;
                _context2.prev = 1;
                _context2.next = 4;
                return (0,_services_api_js__WEBPACK_IMPORTED_MODULE_4__.fetchSelectedTags)(_this2.fileId);
              case 4:
                _this2.selectedTags = _context2.sent;
                _this2.$emit('has-tags', _this2.selectedTags.length > 0);
                _context2.next = 11;
                break;
              case 8:
                _context2.prev = 8;
                _context2.t0 = _context2["catch"](1);
                (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load selected tags'));
              case 11:
                _this2.loadingTags = false;
              case 12:
              case "end":
                return _context2.stop();
            }
          }, _callee2, null, [[1, 8]]);
        }))();
      }
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate,
    createOption: function createOption(newDisplayName) {
      var _iterator2 = _createForOfIteratorHelper(this.sortedTags),
        _step2;
      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var tag = _step2.value;
          var id = tag.id,
            displayName = tag.displayName,
            baseTag = _objectWithoutProperties(tag, _excluded);
          if (displayName === newDisplayName && Object.entries(baseTag).every(function (_ref) {
            var _ref2 = _slicedToArray(_ref, 2),
              key = _ref2[0],
              value = _ref2[1];
            return defaultBaseTag[key] === value;
          })) {
            // Return existing tag to prevent vue-select from thinking the tags are different and showing duplicate options
            return tag;
          }
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }
      return _objectSpread(_objectSpread({}, defaultBaseTag), {}, {
        displayName: newDisplayName
      });
    },
    handleInput: function handleInput(selectedTags) {
      /**
       * Filter out tags with no id to prevent duplicate selected options
       *
       * Created tags are added programmatically by `handleCreate()` with
       * their respective ids returned from the server
       */
      this.selectedTags = selectedTags.filter(function (selectedTag) {
        return Boolean(selectedTag.id);
      });
    },
    handleSelect: function handleSelect(tags) {
      var _this3 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
        var selectedTag, sortToFront;
        return _regeneratorRuntime().wrap(function _callee3$(_context3) {
          while (1) switch (_context3.prev = _context3.next) {
            case 0:
              selectedTag = tags[tags.length - 1];
              if (selectedTag.id) {
                _context3.next = 3;
                break;
              }
              return _context3.abrupt("return");
            case 3:
              _this3.loading = true;
              _context3.prev = 4;
              _context3.next = 7;
              return (0,_services_api_js__WEBPACK_IMPORTED_MODULE_4__.selectTag)(_this3.fileId, selectedTag);
            case 7:
              sortToFront = function sortToFront(a, b) {
                if (a.id === selectedTag.id) {
                  return -1;
                } else if (b.id === selectedTag.id) {
                  return 1;
                }
                return 0;
              };
              _this3.sortedTags.sort(sortToFront);
              _context3.next = 14;
              break;
            case 11:
              _context3.prev = 11;
              _context3.t0 = _context3["catch"](4);
              (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to select tag'));
            case 14:
              _this3.loading = false;
            case 15:
            case "end":
              return _context3.stop();
          }
        }, _callee3, null, [[4, 11]]);
      }))();
    },
    handleCreate: function handleCreate(tag) {
      var _this4 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee4() {
        var id, createdTag;
        return _regeneratorRuntime().wrap(function _callee4$(_context4) {
          while (1) switch (_context4.prev = _context4.next) {
            case 0:
              _this4.loading = true;
              _context4.prev = 1;
              _context4.next = 4;
              return (0,_services_api_js__WEBPACK_IMPORTED_MODULE_4__.createTag)(_this4.fileId, tag);
            case 4:
              id = _context4.sent;
              createdTag = _objectSpread(_objectSpread({}, tag), {}, {
                id: id
              });
              _this4.sortedTags.unshift(createdTag);
              _this4.selectedTags.push(createdTag);
              _context4.next = 13;
              break;
            case 10:
              _context4.prev = 10;
              _context4.t0 = _context4["catch"](1);
              (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to create tag'));
            case 13:
              _this4.loading = false;
            case 14:
            case "end":
              return _context4.stop();
          }
        }, _callee4, null, [[1, 10]]);
      }))();
    },
    handleDeselect: function handleDeselect(tag) {
      var _this5 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee5() {
        return _regeneratorRuntime().wrap(function _callee5$(_context5) {
          while (1) switch (_context5.prev = _context5.next) {
            case 0:
              _this5.loading = true;
              _context5.prev = 1;
              _context5.next = 4;
              return (0,_services_api_js__WEBPACK_IMPORTED_MODULE_4__.deleteTag)(_this5.fileId, tag);
            case 4:
              _context5.next = 9;
              break;
            case 6:
              _context5.prev = 6;
              _context5.t0 = _context5["catch"](1);
              (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to delete tag'));
            case 9:
              _this5.loading = false;
            case 10:
            case "end":
              return _context5.stop();
          }
        }, _callee5, null, [[1, 6]]);
      }))();
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'LegacyView',
  props: {
    component: {
      type: Object,
      required: true
    },
    fileInfo: {
      type: Object,
      default: function _default() {},
      required: true
    }
  },
  watch: {
    fileInfo: function fileInfo(_fileInfo) {
      // update the backbone model FileInfo
      this.setFileInfo(_fileInfo);
    }
  },
  mounted: function mounted() {
    // append the backbone element and set the FileInfo
    this.component.$el.replaceAll(this.$el);
    this.setFileInfo(this.fileInfo);
  },
  methods: {
    setFileInfo: function setFileInfo(fileInfo) {
      this.component.setFileInfo(new OCA.Files.FileInfoModel(fileInfo));
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebarTab.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_1__);
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'SidebarTab',
  components: {
    NcAppSidebarTab: (_nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcEmptyContent: (_nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_1___default())
  },
  props: {
    fileInfo: {
      type: Object,
      default: function _default() {},
      required: true
    },
    id: {
      type: String,
      required: true
    },
    name: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      required: false
    },
    /**
     * Lifecycle methods.
     * They are prefixed with `on` to avoid conflict with Vue
     * methods like this.destroy
     */
    onMount: {
      type: Function,
      required: true
    },
    onUpdate: {
      type: Function,
      required: true
    },
    onDestroy: {
      type: Function,
      required: true
    },
    onScrollBottomReached: {
      type: Function,
      default: function _default() {}
    }
  },
  data: function data() {
    return {
      loading: true
    };
  },
  computed: {
    // TODO: implement a better way to force pass a prop from Sidebar
    activeTab: function activeTab() {
      return this.$parent.activeTab;
    }
  },
  watch: {
    fileInfo: function fileInfo(newFile, oldFile) {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              if (!(newFile.id !== oldFile.id)) {
                _context.next = 5;
                break;
              }
              _this.loading = true;
              _context.next = 4;
              return _this.onUpdate(_this.fileInfo);
            case 4:
              _this.loading = false;
            case 5:
            case "end":
              return _context.stop();
          }
        }, _callee);
      }))();
    }
  },
  mounted: function mounted() {
    var _this2 = this;
    return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
      return _regeneratorRuntime().wrap(function _callee2$(_context2) {
        while (1) switch (_context2.prev = _context2.next) {
          case 0:
            _this2.loading = true;
            // Mount the tab:  mounting point,   fileInfo,      vue context
            _context2.next = 3;
            return _this2.onMount(_this2.$refs.mount, _this2.fileInfo, _this2.$refs.tab);
          case 3:
            _this2.loading = false;
          case 4:
          case "end":
            return _context2.stop();
        }
      }, _callee2);
    }))();
  },
  beforeDestroy: function beforeDestroy() {
    var _this3 = this;
    return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee3() {
      return _regeneratorRuntime().wrap(function _callee3$(_context3) {
        while (1) switch (_context3.prev = _context3.next) {
          case 0:
            _context3.next = 2;
            return _this3.onDestroy();
          case 2:
          case "end":
            return _context3.stop();
        }
      }, _callee3);
    }))();
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.js");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/moment */ "./node_modules/@nextcloud/moment/dist/index.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_moment__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _services_FileInfo_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../services/FileInfo.js */ "./apps/files/src/services/FileInfo.js");
/* harmony import */ var _components_LegacyView_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../components/LegacyView.vue */ "./apps/files/src/components/LegacyView.vue");
/* harmony import */ var _components_SidebarTab_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../components/SidebarTab.vue */ "./apps/files/src/components/SidebarTab.vue");
/* harmony import */ var _systemtags_src_components_SystemTags_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../../../systemtags/src/components/SystemTags.vue */ "./apps/systemtags/src/components/SystemTags.vue");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return exports; }; var exports = {}, Op = Object.prototype, hasOwn = Op.hasOwnProperty, defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; }, $Symbol = "function" == typeof Symbol ? Symbol : {}, iteratorSymbol = $Symbol.iterator || "@@iterator", asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator", toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag"; function define(obj, key, value) { return Object.defineProperty(obj, key, { value: value, enumerable: !0, configurable: !0, writable: !0 }), obj[key]; } try { define({}, ""); } catch (err) { define = function define(obj, key, value) { return obj[key] = value; }; } function wrap(innerFn, outerFn, self, tryLocsList) { var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator, generator = Object.create(protoGenerator.prototype), context = new Context(tryLocsList || []); return defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) }), generator; } function tryCatch(fn, obj, arg) { try { return { type: "normal", arg: fn.call(obj, arg) }; } catch (err) { return { type: "throw", arg: err }; } } exports.wrap = wrap; var ContinueSentinel = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var IteratorPrototype = {}; define(IteratorPrototype, iteratorSymbol, function () { return this; }); var getProto = Object.getPrototypeOf, NativeIteratorPrototype = getProto && getProto(getProto(values([]))); NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype); var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype); function defineIteratorMethods(prototype) { ["next", "throw", "return"].forEach(function (method) { define(prototype, method, function (arg) { return this._invoke(method, arg); }); }); } function AsyncIterator(generator, PromiseImpl) { function invoke(method, arg, resolve, reject) { var record = tryCatch(generator[method], generator, arg); if ("throw" !== record.type) { var result = record.arg, value = result.value; return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) { invoke("next", value, resolve, reject); }, function (err) { invoke("throw", err, resolve, reject); }) : PromiseImpl.resolve(value).then(function (unwrapped) { result.value = unwrapped, resolve(result); }, function (error) { return invoke("throw", error, resolve, reject); }); } reject(record.arg); } var previousPromise; defineProperty(this, "_invoke", { value: function value(method, arg) { function callInvokeWithMethodAndArg() { return new PromiseImpl(function (resolve, reject) { invoke(method, arg, resolve, reject); }); } return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(innerFn, self, context) { var state = "suspendedStart"; return function (method, arg) { if ("executing" === state) throw new Error("Generator is already running"); if ("completed" === state) { if ("throw" === method) throw arg; return doneResult(); } for (context.method = method, context.arg = arg;;) { var delegate = context.delegate; if (delegate) { var delegateResult = maybeInvokeDelegate(delegate, context); if (delegateResult) { if (delegateResult === ContinueSentinel) continue; return delegateResult; } } if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) { if ("suspendedStart" === state) throw state = "completed", context.arg; context.dispatchException(context.arg); } else "return" === context.method && context.abrupt("return", context.arg); state = "executing"; var record = tryCatch(innerFn, self, context); if ("normal" === record.type) { if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue; return { value: record.arg, done: context.done }; } "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg); } }; } function maybeInvokeDelegate(delegate, context) { var methodName = context.method, method = delegate.iterator[methodName]; if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel; var record = tryCatch(method, delegate.iterator, context.arg); if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel; var info = record.arg; return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel); } function pushTryEntry(locs) { var entry = { tryLoc: locs[0] }; 1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry); } function resetTryEntry(entry) { var record = entry.completion || {}; record.type = "normal", delete record.arg, entry.completion = record; } function Context(tryLocsList) { this.tryEntries = [{ tryLoc: "root" }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0); } function values(iterable) { if (iterable) { var iteratorMethod = iterable[iteratorSymbol]; if (iteratorMethod) return iteratorMethod.call(iterable); if ("function" == typeof iterable.next) return iterable; if (!isNaN(iterable.length)) { var i = -1, next = function next() { for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next; return next.value = undefined, next.done = !0, next; }; return next.next = next; } } return { next: doneResult }; } function doneResult() { return { value: undefined, done: !0 }; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), defineProperty(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) { var ctor = "function" == typeof genFun && genFun.constructor; return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name)); }, exports.mark = function (genFun) { return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun; }, exports.awrap = function (arg) { return { __await: arg }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () { return this; }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) { void 0 === PromiseImpl && (PromiseImpl = Promise); var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl); return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) { return result.done ? result.value : iter.next(); }); }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () { return this; }), define(Gp, "toString", function () { return "[object Generator]"; }), exports.keys = function (val) { var object = Object(val), keys = []; for (var key in object) keys.push(key); return keys.reverse(), function next() { for (; keys.length;) { var key = keys.pop(); if (key in object) return next.value = key, next.done = !1, next; } return next.done = !0, next; }; }, exports.values = values, Context.prototype = { constructor: Context, reset: function reset(skipTempReset) { if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined); }, stop: function stop() { this.done = !0; var rootRecord = this.tryEntries[0].completion; if ("throw" === rootRecord.type) throw rootRecord.arg; return this.rval; }, dispatchException: function dispatchException(exception) { if (this.done) throw exception; var context = this; function handle(loc, caught) { return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught; } for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i], record = entry.completion; if ("root" === entry.tryLoc) return handle("end"); if (entry.tryLoc <= this.prev) { var hasCatch = hasOwn.call(entry, "catchLoc"), hasFinally = hasOwn.call(entry, "finallyLoc"); if (hasCatch && hasFinally) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } else if (hasCatch) { if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0); } else { if (!hasFinally) throw new Error("try statement without catch or finally"); if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc); } } } }, abrupt: function abrupt(type, arg) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) { var finallyEntry = entry; break; } } finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null); var record = finallyEntry ? finallyEntry.completion : {}; return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record); }, complete: function complete(record, afterLoc) { if ("throw" === record.type) throw record.arg; return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel; }, finish: function finish(finallyLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel; } }, catch: function _catch(tryLoc) { for (var i = this.tryEntries.length - 1; i >= 0; --i) { var entry = this.tryEntries[i]; if (entry.tryLoc === tryLoc) { var record = entry.completion; if ("throw" === record.type) { var thrown = record.arg; resetTryEntry(entry); } return thrown; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(iterable, resultName, nextLoc) { return this.delegate = { iterator: values(iterable), resultName: resultName, nextLoc: nextLoc }, "next" === this.method && (this.arg = undefined), ContinueSentinel; } }, exports; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }















/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Sidebar',
  components: {
    LegacyView: _components_LegacyView_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_9___default()),
    NcAppSidebar: (_nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_8___default()),
    NcEmptyContent: (_nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_10___default()),
    SidebarTab: _components_SidebarTab_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    SystemTags: _systemtags_src_components_SystemTags_vue__WEBPACK_IMPORTED_MODULE_14__["default"]
  },
  data: function data() {
    return {
      // reactive state
      Sidebar: OCA.Files.Sidebar.state,
      showTags: false,
      error: null,
      loading: true,
      fileInfo: null,
      starLoading: false,
      isFullScreen: false,
      hasLowHeight: false
    };
  },
  computed: {
    /**
     * Current filename
     * This is bound to the Sidebar service and
     * is used to load a new file
     *
     * @return {string}
     */
    file: function file() {
      return this.Sidebar.file;
    },
    /**
     * List of all the registered tabs
     *
     * @return {Array}
     */
    tabs: function tabs() {
      return this.Sidebar.tabs;
    },
    /**
     * List of all the registered views
     *
     * @return {Array}
     */
    views: function views() {
      return this.Sidebar.views;
    },
    /**
     * Current user dav root path
     *
     * @return {string}
     */
    davPath: function davPath() {
      var user = OC.getCurrentUser().uid;
      return OC.linkToRemote("dav/files/".concat(user).concat((0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__.encodePath)(this.file)));
    },
    /**
     * Current active tab handler
     *
     * @param {string} id the tab id to set as active
     * @return {string} the current active tab
     */
    activeTab: function activeTab() {
      return this.Sidebar.activeTab;
    },
    /**
     * Sidebar subtitle
     *
     * @return {string}
     */
    subtitle: function subtitle() {
      return "".concat(this.size, ", ").concat(this.time);
    },
    /**
     * File last modified formatted string
     *
     * @return {string}
     */
    time: function time() {
      return OC.Util.relativeModifiedDate(this.fileInfo.mtime);
    },
    /**
     * File last modified full string
     *
     * @return {string}
     */
    fullTime: function fullTime() {
      return _nextcloud_moment__WEBPACK_IMPORTED_MODULE_7___default()(this.fileInfo.mtime).format('LLL');
    },
    /**
     * File size formatted string
     *
     * @return {string}
     */
    size: function size() {
      return OC.Util.humanFileSize(this.fileInfo.size);
    },
    /**
     * File background/figure to illustrate the sidebar header
     *
     * @return {string}
     */
    background: function background() {
      return this.getPreviewIfAny(this.fileInfo);
    },
    /**
     * App sidebar v-binding object
     *
     * @return {object}
     */
    appSidebar: function appSidebar() {
      if (this.fileInfo) {
        return {
          'data-mimetype': this.fileInfo.mimetype,
          'star-loading': this.starLoading,
          active: this.activeTab,
          background: this.background,
          class: {
            'app-sidebar--has-preview': this.fileInfo.hasPreview && !this.isFullScreen,
            'app-sidebar--full': this.isFullScreen
          },
          compact: this.hasLowHeight || !this.fileInfo.hasPreview || this.isFullScreen,
          loading: this.loading,
          starred: this.fileInfo.isFavourited,
          subname: this.subtitle,
          subtitle: this.fullTime,
          name: this.fileInfo.name,
          title: this.fileInfo.name
        };
      } else if (this.error) {
        return {
          key: 'error',
          // force key to re-render
          subname: '',
          name: '',
          class: {
            'app-sidebar--full': this.isFullScreen
          }
        };
      }
      // no fileInfo yet, showing empty data
      return {
        loading: this.loading,
        subname: '',
        name: '',
        class: {
          'app-sidebar--full': this.isFullScreen
        }
      };
    },
    /**
     * Default action object for the current file
     *
     * @return {object}
     */
    defaultAction: function defaultAction() {
      return this.fileInfo && OCA.Files && OCA.Files.App && OCA.Files.App.fileList && OCA.Files.App.fileList.fileActions && OCA.Files.App.fileList.fileActions.getDefaultFileAction && OCA.Files.App.fileList.fileActions.getDefaultFileAction(this.fileInfo.mimetype, this.fileInfo.type, OC.PERMISSION_READ);
    },
    /**
     * Dynamic header click listener to ensure
     * nothing is listening for a click if there
     * is no default action
     *
     * @return {string|null}
     */
    defaultActionListener: function defaultActionListener() {
      return this.defaultAction ? 'figure-click' : null;
    },
    isSystemTagsEnabled: function isSystemTagsEnabled() {
      return OCA && 'SystemTags' in OCA;
    }
  },
  created: function created() {
    window.addEventListener('resize', this.handleWindowResize);
    this.handleWindowResize();
  },
  beforeDestroy: function beforeDestroy() {
    window.removeEventListener('resize', this.handleWindowResize);
  },
  methods: {
    /**
     * Can this tab be displayed ?
     *
     * @param {object} tab a registered tab
     * @return {boolean}
     */
    canDisplay: function canDisplay(tab) {
      return tab.enabled(this.fileInfo);
    },
    resetData: function resetData() {
      var _this = this;
      this.error = null;
      this.fileInfo = null;
      this.$nextTick(function () {
        if (_this.$refs.tabs) {
          _this.$refs.tabs.updateTabs();
        }
      });
    },
    getPreviewIfAny: function getPreviewIfAny(fileInfo) {
      if (fileInfo.hasPreview && !this.isFullScreen) {
        return OC.generateUrl("/core/preview?fileId=".concat(fileInfo.id, "&x=").concat(screen.width, "&y=").concat(screen.height, "&a=true"));
      }
      return this.getIconUrl(fileInfo);
    },
    /**
     * Copied from https://github.com/nextcloud/server/blob/16e0887ec63591113ee3f476e0c5129e20180cde/apps/files/js/filelist.js#L1377
     * TODO: We also need this as a standalone library
     *
     * @param {object} fileInfo the fileinfo
     * @return {string} Url to the icon for mimeType
     */
    getIconUrl: function getIconUrl(fileInfo) {
      var mimeType = fileInfo.mimetype || 'application/octet-stream';
      if (mimeType === 'httpd/unix-directory') {
        // use default folder icon
        if (fileInfo.mountType === 'shared' || fileInfo.mountType === 'shared-root') {
          return OC.MimeType.getIconUrl('dir-shared');
        } else if (fileInfo.mountType === 'external-root') {
          return OC.MimeType.getIconUrl('dir-external');
        } else if (fileInfo.mountType !== undefined && fileInfo.mountType !== '') {
          return OC.MimeType.getIconUrl('dir-' + fileInfo.mountType);
        } else if (fileInfo.shareTypes && (fileInfo.shareTypes.indexOf(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.Type.SHARE_TYPE_LINK) > -1 || fileInfo.shareTypes.indexOf(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.Type.SHARE_TYPE_EMAIL) > -1)) {
          return OC.MimeType.getIconUrl('dir-public');
        } else if (fileInfo.shareTypes && fileInfo.shareTypes.length > 0) {
          return OC.MimeType.getIconUrl('dir-shared');
        }
        return OC.MimeType.getIconUrl('dir');
      }
      return OC.MimeType.getIconUrl(mimeType);
    },
    /**
     * Set current active tab
     *
     * @param {string} id tab unique id
     */
    setActiveTab: function setActiveTab(id) {
      OCA.Files.Sidebar.setActiveTab(id);
      this.tabs.forEach(function (tab) {
        try {
          tab.setIsActive(id === tab.id);
        } catch (error) {
          logger.error('Error while setting tab active state', {
            error: error,
            id: tab.id,
            tab: tab
          });
        }
      });
    },
    /**
     * Toggle favourite state
     * TODO: better implementation
     *
     * @param {boolean} state favourited or not
     */
    toggleStarred: function toggleStarred(state) {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        var isDir, Node;
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              _context.prev = 0;
              _this2.starLoading = true;
              _context.next = 4;
              return (0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"])({
                method: 'PROPPATCH',
                url: _this2.davPath,
                data: "<?xml version=\"1.0\"?>\n\t\t\t\t\t\t<d:propertyupdate xmlns:d=\"DAV:\" xmlns:oc=\"http://owncloud.org/ns\">\n\t\t\t\t\t\t".concat(state ? '<d:set>' : '<d:remove>', "\n\t\t\t\t\t\t\t<d:prop>\n\t\t\t\t\t\t\t\t<oc:favorite>1</oc:favorite>\n\t\t\t\t\t\t\t</d:prop>\n\t\t\t\t\t\t").concat(state ? '</d:set>' : '</d:remove>', "\n\t\t\t\t\t\t</d:propertyupdate>")
              });
            case 4:
              /**
               * TODO: adjust this when the Sidebar is finally using File/Folder classes
               * @see https://github.com/nextcloud/server/blob/8a75cb6e72acd42712ab9fea22296aa1af863ef5/apps/files/src/views/favorites.ts#L83-L115
               */
              isDir = _this2.fileInfo.type === 'dir';
              Node = isDir ? _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Folder : _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.File;
              (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)(state ? 'files:favorites:added' : 'files:favorites:removed', new Node({
                fileid: _this2.fileInfo.id,
                source: _this2.davPath,
                root: "/files/".concat((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__.getCurrentUser)().uid),
                mime: isDir ? undefined : _this2.fileInfo.mimetype
              }));
              _context.next = 13;
              break;
            case 9:
              _context.prev = 9;
              _context.t0 = _context["catch"](0);
              OC.Notification.showTemporary(t('files', 'Unable to change the favourite state of the file'));
              console.error('Unable to change favourite state', _context.t0);
            case 13:
              _this2.starLoading = false;
            case 14:
            case "end":
              return _context.stop();
          }
        }, _callee, null, [[0, 9]]);
      }))();
    },
    onDefaultAction: function onDefaultAction() {
      if (this.defaultAction) {
        // generate fake context
        this.defaultAction.action(this.fileInfo.name, {
          fileInfo: this.fileInfo,
          dir: this.fileInfo.dir,
          fileList: OCA.Files.App.fileList,
          $file: jquery__WEBPACK_IMPORTED_MODULE_5___default()('body')
        });
      }
    },
    /**
     * Toggle the tags selector
     */
    toggleTags: function toggleTags() {
      this.showTags = !this.showTags;
    },
    /**
     * Open the sidebar for the given file
     *
     * @param {string} path the file path to load
     * @return {Promise}
     * @throws {Error} loading failure
     */
    open: function open(path) {
      var _this3 = this;
      return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2() {
        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
          while (1) switch (_context2.prev = _context2.next) {
            case 0:
              if (!(!path || path.trim() === '')) {
                _context2.next = 2;
                break;
              }
              throw new Error("Invalid path '".concat(path, "'"));
            case 2:
              // update current opened file
              _this3.Sidebar.file = path;

              // reset data, keep old fileInfo to not reload all tabs and just hide them
              _this3.error = null;
              _this3.loading = true;
              _context2.prev = 5;
              _context2.next = 8;
              return (0,_services_FileInfo_js__WEBPACK_IMPORTED_MODULE_11__["default"])(_this3.davPath);
            case 8:
              _this3.fileInfo = _context2.sent;
              // adding this as fallback because other apps expect it
              _this3.fileInfo.dir = _this3.file.split('/').slice(0, -1).join('/');

              // DEPRECATED legacy views
              // TODO: remove
              _this3.views.forEach(function (view) {
                view.setFileInfo(_this3.fileInfo);
              });
              _this3.$nextTick(function () {
                if (_this3.$refs.tabs) {
                  _this3.$refs.tabs.updateTabs();
                }
                _this3.setActiveTab(_this3.Sidebar.activeTab || _this3.tabs[0].id);
              });
              _context2.next = 19;
              break;
            case 14:
              _context2.prev = 14;
              _context2.t0 = _context2["catch"](5);
              _this3.error = t('files', 'Error while loading the file data');
              console.error('Error while loading the file data', _context2.t0);
              throw new Error(_context2.t0);
            case 19:
              _context2.prev = 19;
              _this3.loading = false;
              return _context2.finish(19);
            case 22:
            case "end":
              return _context2.stop();
          }
        }, _callee2, null, [[5, 14, 19, 22]]);
      }))();
    },
    /**
     * Close the sidebar
     */
    close: function close() {
      this.Sidebar.file = '';
      this.showTags = false;
      this.resetData();
    },
    /**
     * Allow to set the Sidebar as fullscreen from OCA.Files.Sidebar
     *
     * @param {boolean} isFullScreen - Whether or not to render the Sidebar in fullscreen.
     */
    setFullScreenMode: function setFullScreenMode(isFullScreen) {
      this.isFullScreen = isFullScreen;
      if (isFullScreen) {
        var _document$querySelect, _document$querySelect2;
        ((_document$querySelect = document.querySelector('#content')) === null || _document$querySelect === void 0 ? void 0 : _document$querySelect.classList.add('with-sidebar--full')) || ((_document$querySelect2 = document.querySelector('#content-vue')) === null || _document$querySelect2 === void 0 ? void 0 : _document$querySelect2.classList.add('with-sidebar--full'));
      } else {
        var _document$querySelect3, _document$querySelect4;
        ((_document$querySelect3 = document.querySelector('#content')) === null || _document$querySelect3 === void 0 ? void 0 : _document$querySelect3.classList.remove('with-sidebar--full')) || ((_document$querySelect4 = document.querySelector('#content-vue')) === null || _document$querySelect4 === void 0 ? void 0 : _document$querySelect4.classList.remove('with-sidebar--full'));
      }
    },
    /**
     * Emit SideBar events.
     */
    handleOpening: function handleOpening() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:sidebar:opening');
    },
    handleOpened: function handleOpened() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:sidebar:opened');
    },
    handleClosing: function handleClosing() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:sidebar:closing');
    },
    handleClosed: function handleClosed() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:sidebar:closed');
    },
    handleWindowResize: function handleWindowResize() {
      this.hasLowHeight = document.documentElement.clientHeight < 1024;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5&":
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5& ***!
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
  return _c("div");
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080&":
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080& ***!
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
  return _c("NcAppSidebarTab", {
    ref: "tab",
    attrs: {
      id: _vm.id,
      name: _vm.name,
      icon: _vm.icon
    },
    on: {
      bottomReached: _vm.onScrollBottomReached
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_vm._t("icon")];
      },
      proxy: true
    }], null, true)
  }, [_vm._v(" "), _vm.loading ? _c("NcEmptyContent", {
    attrs: {
      icon: "icon-loading"
    }
  }) : _vm._e(), _vm._v(" "), _c("div", {
    ref: "mount"
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true& ***!
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
  return _vm.file ? _c("NcAppSidebar", _vm._b({
    ref: "sidebar",
    attrs: {
      "force-menu": true,
      tabindex: "0"
    },
    on: _vm._d({
      close: _vm.close,
      "update:active": _vm.setActiveTab,
      "update:starred": _vm.toggleStarred,
      opening: _vm.handleOpening,
      opened: _vm.handleOpened,
      closing: _vm.handleClosing,
      closed: _vm.handleClosed
    }, [_vm.defaultActionListener, function ($event) {
      $event.stopPropagation();
      $event.preventDefault();
      return _vm.onDefaultAction.apply(null, arguments);
    }]),
    scopedSlots: _vm._u([_vm.fileInfo ? {
      key: "description",
      fn: function fn() {
        return [_c("div", {
          staticClass: "sidebar__description"
        }, [_vm.isSystemTagsEnabled ? _c("SystemTags", {
          directives: [{
            name: "show",
            rawName: "v-show",
            value: _vm.showTags,
            expression: "showTags"
          }],
          attrs: {
            "file-id": _vm.fileInfo.id
          },
          on: {
            "has-tags": function hasTags(value) {
              return _vm.showTags = value;
            }
          }
        }) : _vm._e(), _vm._v(" "), _vm._l(_vm.views, function (view) {
          return _c("LegacyView", {
            key: view.cid,
            attrs: {
              component: view,
              "file-info": _vm.fileInfo
            }
          });
        })], 2)];
      },
      proxy: true
    } : null, _vm.fileInfo ? {
      key: "secondary-actions",
      fn: function fn() {
        return [_vm.isSystemTagsEnabled ? _c("NcActionButton", {
          attrs: {
            "close-after-click": true,
            icon: "icon-tag"
          },
          on: {
            click: _vm.toggleTags
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files", "Tags")) + "\n\t\t")]) : _vm._e()];
      },
      proxy: true
    } : null], null, true)
  }, "NcAppSidebar", _vm.appSidebar, false), [_vm._v(" "), _vm._v(" "), _vm.error ? _c("NcEmptyContent", {
    attrs: {
      icon: "icon-error"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.error) + "\n\t")]) : _vm.fileInfo ? _vm._l(_vm.tabs, function (tab) {
    return [tab.enabled(_vm.fileInfo) ? _c("SidebarTab", {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: !_vm.loading,
        expression: "!loading"
      }],
      key: tab.id,
      attrs: {
        id: tab.id,
        name: tab.name,
        icon: tab.icon,
        "on-mount": tab.mount,
        "on-update": tab.update,
        "on-destroy": tab.destroy,
        "on-scroll-bottom-reached": tab.scrollBottomReached,
        "file-info": _vm.fileInfo
      },
      scopedSlots: _vm._u([tab.iconSvg !== undefined ? {
        key: "icon",
        fn: function fn() {
          return [_c("span", {
            staticClass: "svg-icon",
            domProps: {
              innerHTML: _vm._s(tab.iconSvg)
            }
          })];
        },
        proxy: true
      } : null], null, true)
    }) : _vm._e()];
  }) : _vm._e()], 2) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* binding */ render; },
/* harmony export */   staticRenderFns: function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("div", {
    staticClass: "system-tags"
  }, [_vm.loadingTags ? _c("NcLoadingIcon", {
    attrs: {
      name: _vm.t("systemtags", "Loading collaborative tags …"),
      size: 32
    }
  }) : [_c("label", {
    attrs: {
      for: "system-tags-input"
    }
  }, [_vm._v(_vm._s(_vm.t("systemtags", "Search or create collaborative tags")))]), _vm._v(" "), _c("NcSelectTags", {
    staticClass: "system-tags__select",
    attrs: {
      "input-id": "system-tags-input",
      placeholder: _vm.t("systemtags", "Collaborative tags …"),
      options: _vm.sortedTags,
      value: _vm.selectedTags,
      "create-option": _vm.createOption,
      taggable: true,
      passthru: true,
      "fetch-tags": false,
      loading: _vm.loading
    },
    on: {
      input: _vm.handleInput,
      "option:selected": _vm.handleSelect,
      "option:created": _vm.handleCreate,
      "option:deselected": _vm.handleDeselect
    },
    scopedSlots: _vm._u([{
      key: "no-options",
      fn: function fn() {
        return [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("systemtags", "No tags to select, type to create a new tag")) + "\n\t\t\t")];
      },
      proxy: true
    }])
  })]], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/camelcase/index.js":
/*!*****************************************!*\
  !*** ./node_modules/camelcase/index.js ***!
  \*****************************************/
/***/ (function(module) {

"use strict";


const UPPERCASE = /[\p{Lu}]/u;
const LOWERCASE = /[\p{Ll}]/u;
const LEADING_CAPITAL = /^[\p{Lu}](?![\p{Lu}])/gu;
const IDENTIFIER = /([\p{Alpha}\p{N}_]|$)/u;
const SEPARATORS = /[_.\- ]+/;

const LEADING_SEPARATORS = new RegExp('^' + SEPARATORS.source);
const SEPARATORS_AND_IDENTIFIER = new RegExp(SEPARATORS.source + IDENTIFIER.source, 'gu');
const NUMBERS_AND_IDENTIFIER = new RegExp('\\d+' + IDENTIFIER.source, 'gu');

const preserveCamelCase = (string, toLowerCase, toUpperCase) => {
	let isLastCharLower = false;
	let isLastCharUpper = false;
	let isLastLastCharUpper = false;

	for (let i = 0; i < string.length; i++) {
		const character = string[i];

		if (isLastCharLower && UPPERCASE.test(character)) {
			string = string.slice(0, i) + '-' + string.slice(i);
			isLastCharLower = false;
			isLastLastCharUpper = isLastCharUpper;
			isLastCharUpper = true;
			i++;
		} else if (isLastCharUpper && isLastLastCharUpper && LOWERCASE.test(character)) {
			string = string.slice(0, i - 1) + '-' + string.slice(i - 1);
			isLastLastCharUpper = isLastCharUpper;
			isLastCharUpper = false;
			isLastCharLower = true;
		} else {
			isLastCharLower = toLowerCase(character) === character && toUpperCase(character) !== character;
			isLastLastCharUpper = isLastCharUpper;
			isLastCharUpper = toUpperCase(character) === character && toLowerCase(character) !== character;
		}
	}

	return string;
};

const preserveConsecutiveUppercase = (input, toLowerCase) => {
	LEADING_CAPITAL.lastIndex = 0;

	return input.replace(LEADING_CAPITAL, m1 => toLowerCase(m1));
};

const postProcess = (input, toUpperCase) => {
	SEPARATORS_AND_IDENTIFIER.lastIndex = 0;
	NUMBERS_AND_IDENTIFIER.lastIndex = 0;

	return input.replace(SEPARATORS_AND_IDENTIFIER, (_, identifier) => toUpperCase(identifier))
		.replace(NUMBERS_AND_IDENTIFIER, m => toUpperCase(m));
};

const camelCase = (input, options) => {
	if (!(typeof input === 'string' || Array.isArray(input))) {
		throw new TypeError('Expected the input to be `string | string[]`');
	}

	options = {
		pascalCase: false,
		preserveConsecutiveUppercase: false,
		...options
	};

	if (Array.isArray(input)) {
		input = input.map(x => x.trim())
			.filter(x => x.length)
			.join('-');
	} else {
		input = input.trim();
	}

	if (input.length === 0) {
		return '';
	}

	const toLowerCase = options.locale === false ?
		string => string.toLowerCase() :
		string => string.toLocaleLowerCase(options.locale);
	const toUpperCase = options.locale === false ?
		string => string.toUpperCase() :
		string => string.toLocaleUpperCase(options.locale);

	if (input.length === 1) {
		return options.pascalCase ? toUpperCase(input) : toLowerCase(input);
	}

	const hasUpperCase = input !== toLowerCase(input);

	if (hasUpperCase) {
		input = preserveCamelCase(input, toLowerCase, toUpperCase);
	}

	input = input.replace(LEADING_SEPARATORS, '');

	if (options.preserveConsecutiveUppercase) {
		input = preserveConsecutiveUppercase(input, toLowerCase);
	} else {
		input = toLowerCase(input);
	}

	if (options.pascalCase) {
		input = toUpperCase(input.charAt(0)) + input.slice(1);
	}

	return postProcess(input, toUpperCase);
};

module.exports = camelCase;
// TODO: Remove this for the next major release
module.exports["default"] = camelCase;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& ***!
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
___CSS_LOADER_EXPORT___.push([module.id, ".app-sidebar--has-preview[data-v-7c6102ee] .app-sidebar-header__figure {\n  background-size: cover;\n}\n.app-sidebar--has-preview[data-v-7c6102ee][data-mimetype=\"text/plain\"] .app-sidebar-header__figure, .app-sidebar--has-preview[data-v-7c6102ee][data-mimetype=\"text/markdown\"] .app-sidebar-header__figure {\n  background-size: contain;\n}\n.app-sidebar--full[data-v-7c6102ee] {\n  position: fixed !important;\n  z-index: 2025 !important;\n  top: 0 !important;\n  height: 100% !important;\n}\n.app-sidebar[data-v-7c6102ee] .app-sidebar-header__description {\n  margin: 0 16px 4px 16px !important;\n}\n.app-sidebar .svg-icon[data-v-7c6102ee] svg {\n  width: 20px;\n  height: 20px;\n  fill: currentColor;\n}\n.sidebar__description[data-v-7c6102ee] {\n  display: flex;\n  flex-direction: column;\n  width: 100%;\n  gap: 8px 0;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".system-tags[data-v-3f7729e4] {\n  display: flex;\n  flex-direction: column;\n}\n.system-tags label[for=system-tags-input][data-v-3f7729e4] {\n  margin-bottom: 2px;\n}\n.system-tags__select[data-v-3f7729e4] {\n  width: 100%;\n}\n.system-tags__select[data-v-3f7729e4] .vs__deselect {\n  padding: 0;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/moment/locale sync recursive ^\\.\\/.*$":
/*!***************************************************!*\
  !*** ./node_modules/moment/locale/ sync ^\.\/.*$ ***!
  \***************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./af": "./node_modules/moment/locale/af.js",
	"./af.js": "./node_modules/moment/locale/af.js",
	"./ar": "./node_modules/moment/locale/ar.js",
	"./ar-dz": "./node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "./node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "./node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "./node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "./node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "./node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "./node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "./node_modules/moment/locale/ar-ma.js",
	"./ar-sa": "./node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "./node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "./node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "./node_modules/moment/locale/ar-tn.js",
	"./ar.js": "./node_modules/moment/locale/ar.js",
	"./az": "./node_modules/moment/locale/az.js",
	"./az.js": "./node_modules/moment/locale/az.js",
	"./be": "./node_modules/moment/locale/be.js",
	"./be.js": "./node_modules/moment/locale/be.js",
	"./bg": "./node_modules/moment/locale/bg.js",
	"./bg.js": "./node_modules/moment/locale/bg.js",
	"./bm": "./node_modules/moment/locale/bm.js",
	"./bm.js": "./node_modules/moment/locale/bm.js",
	"./bn": "./node_modules/moment/locale/bn.js",
	"./bn-bd": "./node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "./node_modules/moment/locale/bn-bd.js",
	"./bn.js": "./node_modules/moment/locale/bn.js",
	"./bo": "./node_modules/moment/locale/bo.js",
	"./bo.js": "./node_modules/moment/locale/bo.js",
	"./br": "./node_modules/moment/locale/br.js",
	"./br.js": "./node_modules/moment/locale/br.js",
	"./bs": "./node_modules/moment/locale/bs.js",
	"./bs.js": "./node_modules/moment/locale/bs.js",
	"./ca": "./node_modules/moment/locale/ca.js",
	"./ca.js": "./node_modules/moment/locale/ca.js",
	"./cs": "./node_modules/moment/locale/cs.js",
	"./cs.js": "./node_modules/moment/locale/cs.js",
	"./cv": "./node_modules/moment/locale/cv.js",
	"./cv.js": "./node_modules/moment/locale/cv.js",
	"./cy": "./node_modules/moment/locale/cy.js",
	"./cy.js": "./node_modules/moment/locale/cy.js",
	"./da": "./node_modules/moment/locale/da.js",
	"./da.js": "./node_modules/moment/locale/da.js",
	"./de": "./node_modules/moment/locale/de.js",
	"./de-at": "./node_modules/moment/locale/de-at.js",
	"./de-at.js": "./node_modules/moment/locale/de-at.js",
	"./de-ch": "./node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "./node_modules/moment/locale/de-ch.js",
	"./de.js": "./node_modules/moment/locale/de.js",
	"./dv": "./node_modules/moment/locale/dv.js",
	"./dv.js": "./node_modules/moment/locale/dv.js",
	"./el": "./node_modules/moment/locale/el.js",
	"./el.js": "./node_modules/moment/locale/el.js",
	"./en-au": "./node_modules/moment/locale/en-au.js",
	"./en-au.js": "./node_modules/moment/locale/en-au.js",
	"./en-ca": "./node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "./node_modules/moment/locale/en-ca.js",
	"./en-gb": "./node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "./node_modules/moment/locale/en-gb.js",
	"./en-ie": "./node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "./node_modules/moment/locale/en-ie.js",
	"./en-il": "./node_modules/moment/locale/en-il.js",
	"./en-il.js": "./node_modules/moment/locale/en-il.js",
	"./en-in": "./node_modules/moment/locale/en-in.js",
	"./en-in.js": "./node_modules/moment/locale/en-in.js",
	"./en-nz": "./node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "./node_modules/moment/locale/en-nz.js",
	"./en-sg": "./node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "./node_modules/moment/locale/en-sg.js",
	"./eo": "./node_modules/moment/locale/eo.js",
	"./eo.js": "./node_modules/moment/locale/eo.js",
	"./es": "./node_modules/moment/locale/es.js",
	"./es-do": "./node_modules/moment/locale/es-do.js",
	"./es-do.js": "./node_modules/moment/locale/es-do.js",
	"./es-mx": "./node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "./node_modules/moment/locale/es-mx.js",
	"./es-us": "./node_modules/moment/locale/es-us.js",
	"./es-us.js": "./node_modules/moment/locale/es-us.js",
	"./es.js": "./node_modules/moment/locale/es.js",
	"./et": "./node_modules/moment/locale/et.js",
	"./et.js": "./node_modules/moment/locale/et.js",
	"./eu": "./node_modules/moment/locale/eu.js",
	"./eu.js": "./node_modules/moment/locale/eu.js",
	"./fa": "./node_modules/moment/locale/fa.js",
	"./fa.js": "./node_modules/moment/locale/fa.js",
	"./fi": "./node_modules/moment/locale/fi.js",
	"./fi.js": "./node_modules/moment/locale/fi.js",
	"./fil": "./node_modules/moment/locale/fil.js",
	"./fil.js": "./node_modules/moment/locale/fil.js",
	"./fo": "./node_modules/moment/locale/fo.js",
	"./fo.js": "./node_modules/moment/locale/fo.js",
	"./fr": "./node_modules/moment/locale/fr.js",
	"./fr-ca": "./node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "./node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "./node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "./node_modules/moment/locale/fr-ch.js",
	"./fr.js": "./node_modules/moment/locale/fr.js",
	"./fy": "./node_modules/moment/locale/fy.js",
	"./fy.js": "./node_modules/moment/locale/fy.js",
	"./ga": "./node_modules/moment/locale/ga.js",
	"./ga.js": "./node_modules/moment/locale/ga.js",
	"./gd": "./node_modules/moment/locale/gd.js",
	"./gd.js": "./node_modules/moment/locale/gd.js",
	"./gl": "./node_modules/moment/locale/gl.js",
	"./gl.js": "./node_modules/moment/locale/gl.js",
	"./gom-deva": "./node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "./node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "./node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "./node_modules/moment/locale/gom-latn.js",
	"./gu": "./node_modules/moment/locale/gu.js",
	"./gu.js": "./node_modules/moment/locale/gu.js",
	"./he": "./node_modules/moment/locale/he.js",
	"./he.js": "./node_modules/moment/locale/he.js",
	"./hi": "./node_modules/moment/locale/hi.js",
	"./hi.js": "./node_modules/moment/locale/hi.js",
	"./hr": "./node_modules/moment/locale/hr.js",
	"./hr.js": "./node_modules/moment/locale/hr.js",
	"./hu": "./node_modules/moment/locale/hu.js",
	"./hu.js": "./node_modules/moment/locale/hu.js",
	"./hy-am": "./node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "./node_modules/moment/locale/hy-am.js",
	"./id": "./node_modules/moment/locale/id.js",
	"./id.js": "./node_modules/moment/locale/id.js",
	"./is": "./node_modules/moment/locale/is.js",
	"./is.js": "./node_modules/moment/locale/is.js",
	"./it": "./node_modules/moment/locale/it.js",
	"./it-ch": "./node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "./node_modules/moment/locale/it-ch.js",
	"./it.js": "./node_modules/moment/locale/it.js",
	"./ja": "./node_modules/moment/locale/ja.js",
	"./ja.js": "./node_modules/moment/locale/ja.js",
	"./jv": "./node_modules/moment/locale/jv.js",
	"./jv.js": "./node_modules/moment/locale/jv.js",
	"./ka": "./node_modules/moment/locale/ka.js",
	"./ka.js": "./node_modules/moment/locale/ka.js",
	"./kk": "./node_modules/moment/locale/kk.js",
	"./kk.js": "./node_modules/moment/locale/kk.js",
	"./km": "./node_modules/moment/locale/km.js",
	"./km.js": "./node_modules/moment/locale/km.js",
	"./kn": "./node_modules/moment/locale/kn.js",
	"./kn.js": "./node_modules/moment/locale/kn.js",
	"./ko": "./node_modules/moment/locale/ko.js",
	"./ko.js": "./node_modules/moment/locale/ko.js",
	"./ku": "./node_modules/moment/locale/ku.js",
	"./ku.js": "./node_modules/moment/locale/ku.js",
	"./ky": "./node_modules/moment/locale/ky.js",
	"./ky.js": "./node_modules/moment/locale/ky.js",
	"./lb": "./node_modules/moment/locale/lb.js",
	"./lb.js": "./node_modules/moment/locale/lb.js",
	"./lo": "./node_modules/moment/locale/lo.js",
	"./lo.js": "./node_modules/moment/locale/lo.js",
	"./lt": "./node_modules/moment/locale/lt.js",
	"./lt.js": "./node_modules/moment/locale/lt.js",
	"./lv": "./node_modules/moment/locale/lv.js",
	"./lv.js": "./node_modules/moment/locale/lv.js",
	"./me": "./node_modules/moment/locale/me.js",
	"./me.js": "./node_modules/moment/locale/me.js",
	"./mi": "./node_modules/moment/locale/mi.js",
	"./mi.js": "./node_modules/moment/locale/mi.js",
	"./mk": "./node_modules/moment/locale/mk.js",
	"./mk.js": "./node_modules/moment/locale/mk.js",
	"./ml": "./node_modules/moment/locale/ml.js",
	"./ml.js": "./node_modules/moment/locale/ml.js",
	"./mn": "./node_modules/moment/locale/mn.js",
	"./mn.js": "./node_modules/moment/locale/mn.js",
	"./mr": "./node_modules/moment/locale/mr.js",
	"./mr.js": "./node_modules/moment/locale/mr.js",
	"./ms": "./node_modules/moment/locale/ms.js",
	"./ms-my": "./node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "./node_modules/moment/locale/ms-my.js",
	"./ms.js": "./node_modules/moment/locale/ms.js",
	"./mt": "./node_modules/moment/locale/mt.js",
	"./mt.js": "./node_modules/moment/locale/mt.js",
	"./my": "./node_modules/moment/locale/my.js",
	"./my.js": "./node_modules/moment/locale/my.js",
	"./nb": "./node_modules/moment/locale/nb.js",
	"./nb.js": "./node_modules/moment/locale/nb.js",
	"./ne": "./node_modules/moment/locale/ne.js",
	"./ne.js": "./node_modules/moment/locale/ne.js",
	"./nl": "./node_modules/moment/locale/nl.js",
	"./nl-be": "./node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "./node_modules/moment/locale/nl-be.js",
	"./nl.js": "./node_modules/moment/locale/nl.js",
	"./nn": "./node_modules/moment/locale/nn.js",
	"./nn.js": "./node_modules/moment/locale/nn.js",
	"./oc-lnc": "./node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "./node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "./node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "./node_modules/moment/locale/pa-in.js",
	"./pl": "./node_modules/moment/locale/pl.js",
	"./pl.js": "./node_modules/moment/locale/pl.js",
	"./pt": "./node_modules/moment/locale/pt.js",
	"./pt-br": "./node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "./node_modules/moment/locale/pt-br.js",
	"./pt.js": "./node_modules/moment/locale/pt.js",
	"./ro": "./node_modules/moment/locale/ro.js",
	"./ro.js": "./node_modules/moment/locale/ro.js",
	"./ru": "./node_modules/moment/locale/ru.js",
	"./ru.js": "./node_modules/moment/locale/ru.js",
	"./sd": "./node_modules/moment/locale/sd.js",
	"./sd.js": "./node_modules/moment/locale/sd.js",
	"./se": "./node_modules/moment/locale/se.js",
	"./se.js": "./node_modules/moment/locale/se.js",
	"./si": "./node_modules/moment/locale/si.js",
	"./si.js": "./node_modules/moment/locale/si.js",
	"./sk": "./node_modules/moment/locale/sk.js",
	"./sk.js": "./node_modules/moment/locale/sk.js",
	"./sl": "./node_modules/moment/locale/sl.js",
	"./sl.js": "./node_modules/moment/locale/sl.js",
	"./sq": "./node_modules/moment/locale/sq.js",
	"./sq.js": "./node_modules/moment/locale/sq.js",
	"./sr": "./node_modules/moment/locale/sr.js",
	"./sr-cyrl": "./node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "./node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "./node_modules/moment/locale/sr.js",
	"./ss": "./node_modules/moment/locale/ss.js",
	"./ss.js": "./node_modules/moment/locale/ss.js",
	"./sv": "./node_modules/moment/locale/sv.js",
	"./sv.js": "./node_modules/moment/locale/sv.js",
	"./sw": "./node_modules/moment/locale/sw.js",
	"./sw.js": "./node_modules/moment/locale/sw.js",
	"./ta": "./node_modules/moment/locale/ta.js",
	"./ta.js": "./node_modules/moment/locale/ta.js",
	"./te": "./node_modules/moment/locale/te.js",
	"./te.js": "./node_modules/moment/locale/te.js",
	"./tet": "./node_modules/moment/locale/tet.js",
	"./tet.js": "./node_modules/moment/locale/tet.js",
	"./tg": "./node_modules/moment/locale/tg.js",
	"./tg.js": "./node_modules/moment/locale/tg.js",
	"./th": "./node_modules/moment/locale/th.js",
	"./th.js": "./node_modules/moment/locale/th.js",
	"./tk": "./node_modules/moment/locale/tk.js",
	"./tk.js": "./node_modules/moment/locale/tk.js",
	"./tl-ph": "./node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "./node_modules/moment/locale/tl-ph.js",
	"./tlh": "./node_modules/moment/locale/tlh.js",
	"./tlh.js": "./node_modules/moment/locale/tlh.js",
	"./tr": "./node_modules/moment/locale/tr.js",
	"./tr.js": "./node_modules/moment/locale/tr.js",
	"./tzl": "./node_modules/moment/locale/tzl.js",
	"./tzl.js": "./node_modules/moment/locale/tzl.js",
	"./tzm": "./node_modules/moment/locale/tzm.js",
	"./tzm-latn": "./node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "./node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "./node_modules/moment/locale/tzm.js",
	"./ug-cn": "./node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "./node_modules/moment/locale/ug-cn.js",
	"./uk": "./node_modules/moment/locale/uk.js",
	"./uk.js": "./node_modules/moment/locale/uk.js",
	"./ur": "./node_modules/moment/locale/ur.js",
	"./ur.js": "./node_modules/moment/locale/ur.js",
	"./uz": "./node_modules/moment/locale/uz.js",
	"./uz-latn": "./node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "./node_modules/moment/locale/uz-latn.js",
	"./uz.js": "./node_modules/moment/locale/uz.js",
	"./vi": "./node_modules/moment/locale/vi.js",
	"./vi.js": "./node_modules/moment/locale/vi.js",
	"./x-pseudo": "./node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "./node_modules/moment/locale/x-pseudo.js",
	"./yo": "./node_modules/moment/locale/yo.js",
	"./yo.js": "./node_modules/moment/locale/yo.js",
	"./zh-cn": "./node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "./node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "./node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "./node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "./node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "./node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "./node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "./node_modules/moment/locale/zh-tw.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue":
/*!**************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LegacyView.vue?vue&type=template&id=6ac0bcb5& */ "./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5&");
/* harmony import */ var _LegacyView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LegacyView.vue?vue&type=script&lang=js& */ "./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _LegacyView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__.render,
  _LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/components/LegacyView.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue":
/*!**************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SidebarTab.vue?vue&type=template&id=9d2bd080& */ "./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080&");
/* harmony import */ var _SidebarTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SidebarTab.vue?vue&type=script&lang=js& */ "./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SidebarTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__.render,
  _SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/components/SidebarTab.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue":
/*!******************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue ***!
  \******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true& */ "./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true&");
/* harmony import */ var _Sidebar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=script&lang=js& */ "./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js&");
/* harmony import */ var _Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& */ "./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Sidebar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "7c6102ee",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/views/Sidebar.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTags.vue":
/*!*******************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTags.vue ***!
  \*******************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true& */ "./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true&");
/* harmony import */ var _SystemTags_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SystemTags.vue?vue&type=script&lang=ts& */ "./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts&");
/* harmony import */ var _SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true& */ "./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SystemTags_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3f7729e4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/systemtags/src/components/SystemTags.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts&":
/*!********************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts& ***!
  \********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTags.vue?vue&type=script&lang=ts& */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js&":
/*!***************************************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js& ***!
  \***************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LegacyView.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js&":
/*!***************************************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js& ***!
  \***************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTab.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js&":
/*!*******************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js& ***!
  \*******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5&":
/*!*********************************************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5& ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LegacyView.vue?vue&type=template&id=6ac0bcb5& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5&");


/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080&":
/*!*********************************************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080& ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTab.vue?vue&type=template&id=9d2bd080& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080&");


/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true&":
/*!*************************************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true&");


/***/ }),

/***/ "./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true&":
/*!**************************************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true& ***!
  \**************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   staticRenderFns: function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true&");


/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&":
/*!****************************************************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& ***!
  \****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true&":
/*!*****************************************************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true&");


/***/ }),

/***/ "?4f7e":
/*!********************************!*\
  !*** ./util.inspect (ignored) ***!
  \********************************/
/***/ (function() {

/* (ignored) */

/***/ }),

/***/ "?ed1b":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (function() {

/* (ignored) */

/***/ }),

/***/ "?d17e":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (function() {

/* (ignored) */

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
/******/ 			"files-sidebar": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/files/src/sidebar.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files-sidebar.js.map?v=98565b11cd3bff4ddd7e