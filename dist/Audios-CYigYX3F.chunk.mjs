import{b as f,o as m,j as v,h as _,w as g,g as b,u as r,N as h,t as x}from"./Web-0o0ZsUfo.chunk.mjs";import{u as w,s as k}from"./usePlyrPlayer-BgJGPoXL.chunk.mjs";import{u as z}from"./canDownload-CHDy5NnS.chunk.mjs";import{t as P}from"./translations-CXi-IObO.chunk.mjs";import{_ as S}from"./_plugin-vue_export-helper-DPNLE7fV.chunk.mjs";import"./translation-DoG5ZELJ-DosHP9c_.chunk.mjs";import"./PencilOutline-Cr5fPBe7.chunk.mjs";import"./dav-ajmPPC2W.chunk.mjs";import"./star-outline-3oU1ox7V.chunk.mjs";import"./previewUtils-BiOsv7h1.chunk.mjs";import"./Link-BG7j_u2w.chunk.mjs";import"./index-BvPFWo9y.chunk.mjs";(function(){try{if(typeof document<"u"){var t=document.createElement("style");t.appendChild(document.createTextNode(`audio[data-v-4fc0f3a9] {
  /* over arrows in tiny screens */
  z-index: 20050;
  align-self: center;
  max-width: 100%;
  max-height: 100%;
  background-color: black;
  justify-self: center;
}
[data-v-4fc0f3a9]  .plyr__progress__container {
  flex: 1 1;
}
[data-v-4fc0f3a9]  .plyr {
  /**
   * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
   * SPDX-License-Identifier: AGPL-3.0-or-later
   */
}
[data-v-4fc0f3a9]  .plyr {
  --plyr-color-main: var(--color-primary-element);
  --plyr-control-icon-size: 18px;
  --plyr-menu-background: var(--color-main-background);
  --plyr-menu-color: var(--color-main-text);
  --plyr-audio-controls-background: var(--color-main-background);
  --plyr-audio-control-color: var(--color-main-text);
  --plyr-button-size: 44px;
  --plyr-range-fill-background: var(--color-primary-element);
}
[data-v-4fc0f3a9]  .plyr .plyr__controls {
  flex-wrap: wrap;
}
[data-v-4fc0f3a9]  .plyr .plyr__controls .plyr__volume,[data-v-4fc0f3a9]  .plyr .plyr__controls .plyr__progress__container {
  max-width: 100%;
  flex: 1 1;
}
[data-v-4fc0f3a9]  .plyr .plyr__controls .plyr__progress__container {
  flex: 4 1;
}
[data-v-4fc0f3a9]  .plyr button {
  width: var(--plyr-button-size);
  height: var(--plyr-button-size);
  padding: calc((var(--plyr-button-size) - var(--plyr-control-icon-size)) / 2);
  cursor: pointer;
  border: none;
  background-color: transparent;
  line-height: inherit;
}
[data-v-4fc0f3a9]  .plyr button:hover,[data-v-4fc0f3a9]  .plyr button:focus {
  color: var(--color-main-text);
  background-color: var(--color-background-hover);
}
[data-v-4fc0f3a9]  .plyr button.plyr__control--overlaid {
  --plyr-button-size: 50px;
  width: var(--plyr-button-size);
  height: var(--plyr-button-size);
  color: var(--color-primary-element-text);
  background-color: var(--color-primary-element);
}
[data-v-4fc0f3a9]  .plyr button.plyr__control--overlaid:hover,[data-v-4fc0f3a9]  .plyr button.plyr__control--overlaid:focus {
  background-color: var(--color-primary-element-hover);
}
[data-v-4fc0f3a9]  .plyr .plyr__menu__container button {
  min-width: 120px;
  width: max-content;
  margin: 0;
  color: var(--color-main-text);
}
[data-v-4fc0f3a9]  .plyr .plyr__menu__container button:hover,[data-v-4fc0f3a9]  .plyr .plyr__menu__container button:focus {
  color: var(--color-main-text);
  background-color: var(--color-background-hover);
}
[data-v-4fc0f3a9]  .plyr .plyr__menu__container button.plyr__control--forward {
  padding-inline-end: 28px;
  padding-right: calc(var(--plyr-control-spacing, 10px) * 0.7 * 4);
}
[data-v-4fc0f3a9]  .plyr .plyr__menu__container button.plyr__control--back {
  margin: calc(var(--plyr-control-spacing, 10px) * 0.7);
  padding-inline-start: 28px;
  padding-left: calc(var(--plyr-control-spacing, 10px) * 0.7 * 4);
}
[data-v-4fc0f3a9]  .plyr .plyr__progress__buffer {
  width: calc(100% + var(--plyr-range-thumb-height, 13px));
  height: var(--plyr-range-track-height, 5px);
  background: transparent;
}
@media only screen and (max-width: 480px) {
[data-v-4fc0f3a9]  .plyr .plyr__volume {
    display: none;
}
}
[data-v-4fc0f3a9]  .plyr__menu__container {
  max-height: calc(40vh - var(--plyr-button-size, 44px) / 2 - 20px);
  overflow-y: auto;
}
@media only screen and (max-width: 500px) {
[data-v-4fc0f3a9]  .plyr--audio {
    top: calc(17.5vw + 30px);
}
}`)),document.head.appendChild(t)}}catch(n){console.error("vite-plugin-css-injected-by-js",n)}})();const C=["src"],j=f({name:"ViewerAudios",__name:"Audios",props:{file:{},files:{},maxHeight:{},maxWidth:{},editing:{type:Boolean},isSidebarShown:{type:Boolean},turns:{},localSource:{}},emits:["loaded","errored","update:canSwipe","update:editing","update:playing"],setup(t,{emit:n}){const e=t,u=n,{onFail:l,donePlaying:c,doneLoading:p,onPause:i,onPlay:d,options:s}=w(!0,e,u),{src:y}=z(e);return(N,o)=>(m(),v("div",null,[_(r(k),{ref:"plyr",options:r(s)},{default:g(()=>[b("audio",{ref:"audio",autoplay:!0,src:r(y),preload:"metadata",onErrorCaptureOnce:o[0]||(o[0]=h((...a)=>r(l)&&r(l)(...a),["prevent","stop"])),onEnded:o[1]||(o[1]=(...a)=>r(c)&&r(c)(...a)),onPause:o[2]||(o[2]=(...a)=>r(i)&&r(i)(...a)),onPlay:o[3]||(o[3]=(...a)=>r(d)&&r(d)(...a)),onCanplay:o[4]||(o[4]=(...a)=>r(p)&&r(p)(...a))},x(r(P)("Your browser does not support audio.")),41,C)]),_:1},8,["options"])]))}}),O=S(j,[["__scopeId","data-v-4fc0f3a9"]]);export{O as default};
//# sourceMappingURL=Audios-CYigYX3F.chunk.mjs.map
