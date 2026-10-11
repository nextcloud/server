import{e as m,o as v,f as _,p as b,w as g,l as h,u as r,y as x,t as f}from"./PencilOutline-C4ZkKkas.chunk.mjs";import{u as w,s as k}from"./usePlyrPlayer-DwOz7UEI.chunk.mjs";import{t as z}from"./neighbours-DZHrojkH.chunk.mjs";import{_ as P}from"./livePhotoUtils-CVUhgL4S.chunk.mjs";import"./star-outline-BpDZzOb8.chunk.mjs";import"./useViewerProps-H6LwP02J.chunk.mjs";import"./index-DHyMMYTN.chunk.mjs";import"./index-D6fZh9CR.chunk.mjs";import"./dav-BNCa0U-Z.chunk.mjs";import"./externalStorageUtils-mlnj1LcU.chunk.mjs";import"./translations-pLZlvQeM.chunk.mjs";import"./File-xDTWu8L0.chunk.mjs";import"./index-D_oRcFiB.chunk.mjs";(function(){try{if(typeof document<"u"){var t=document.createElement("style");t.appendChild(document.createTextNode(`audio[data-v-5b588862] {
  /* over arrows in tiny screens */
  z-index: 20050;
  align-self: center;
  max-width: 100%;
  max-height: 100%;
  background-color: black;
  justify-self: center;
}
[data-v-5b588862]  .plyr__progress__container {
  flex: 1 1;
}
[data-v-5b588862]  .plyr {
  /**
   * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
   * SPDX-License-Identifier: AGPL-3.0-or-later
   */
}
[data-v-5b588862]  .plyr {
  --plyr-color-main: var(--color-primary-element);
  --plyr-control-icon-size: 18px;
  --plyr-menu-background: var(--color-main-background);
  --plyr-menu-color: var(--color-main-text);
  --plyr-audio-controls-background: var(--color-main-background);
  --plyr-audio-control-color: var(--color-main-text);
  --plyr-button-size: 44px;
  --plyr-range-fill-background: var(--color-primary-element);
}
[data-v-5b588862]  .plyr .plyr__controls {
  flex-wrap: wrap;
}
[data-v-5b588862]  .plyr .plyr__controls .plyr__volume,[data-v-5b588862]  .plyr .plyr__controls .plyr__progress__container {
  max-width: 100%;
  flex: 1 1;
}
[data-v-5b588862]  .plyr .plyr__controls .plyr__progress__container {
  flex: 4 1;
}
[data-v-5b588862]  .plyr button {
  width: var(--plyr-button-size);
  height: var(--plyr-button-size);
  padding: calc((var(--plyr-button-size) - var(--plyr-control-icon-size)) / 2);
  cursor: pointer;
  border: none;
  background-color: transparent;
  line-height: inherit;
}
[data-v-5b588862]  .plyr button:hover,[data-v-5b588862]  .plyr button:focus {
  color: var(--color-main-text);
  background-color: var(--color-background-hover);
}
[data-v-5b588862]  .plyr button.plyr__control--overlaid {
  --plyr-button-size: 50px;
  width: var(--plyr-button-size);
  height: var(--plyr-button-size);
  color: var(--color-primary-element-text);
  background-color: var(--color-primary-element);
}
[data-v-5b588862]  .plyr button.plyr__control--overlaid:hover,[data-v-5b588862]  .plyr button.plyr__control--overlaid:focus {
  background-color: var(--color-primary-element-hover);
}
[data-v-5b588862]  .plyr .plyr__menu__container button {
  min-width: 120px;
  width: max-content;
  margin: 0;
  color: var(--color-main-text);
}
[data-v-5b588862]  .plyr .plyr__menu__container button:hover,[data-v-5b588862]  .plyr .plyr__menu__container button:focus {
  color: var(--color-main-text);
  background-color: var(--color-background-hover);
}
[data-v-5b588862]  .plyr .plyr__menu__container button.plyr__control--forward {
  padding-inline-end: 28px;
  padding-right: calc(var(--plyr-control-spacing, 10px) * 0.7 * 4);
}
[data-v-5b588862]  .plyr .plyr__menu__container button.plyr__control--back {
  margin: calc(var(--plyr-control-spacing, 10px) * 0.7);
  padding-inline-start: 28px;
  padding-left: calc(var(--plyr-control-spacing, 10px) * 0.7 * 4);
}
[data-v-5b588862]  .plyr .plyr__progress__buffer {
  width: calc(100% + var(--plyr-range-thumb-height, 13px));
  height: var(--plyr-range-track-height, 5px);
  background: transparent;
}
@media only screen and (max-width: 480px) {
[data-v-5b588862]  .plyr .plyr__volume {
    display: none;
}
}
[data-v-5b588862]  .plyr__menu__container {
  max-height: calc(40vh - var(--plyr-button-size, 44px) / 2 - 20px);
  overflow-y: auto;
}
@media only screen and (max-width: 500px) {
[data-v-5b588862]  .plyr--audio {
    top: calc(17.5vw + 30px);
}
}`)),document.head.appendChild(t)}}catch(e){console.error("vite-plugin-css-injected-by-js",e)}})();const S=["src"],C=m({name:"ViewerAudios",__name:"Audios",props:{file:{},files:{},maxHeight:{},maxWidth:{},editing:{type:Boolean},isSidebarShown:{type:Boolean},turns:{},localSource:{}},emits:["loaded","errored","update:canSwipe","update:editing","update:playing"],setup(t,{emit:e}){const d=t,u=e,{onFail:n,donePlaying:l,doneLoading:p,onPause:i,onPlay:c,options:s,src:y}=w(!0,d,u);return(A,o)=>(v(),_("div",null,[b(r(k),{ref:"plyr",options:r(s)},{default:g(()=>[h("audio",{ref:"audio",autoplay:!0,src:r(y),preload:"metadata",onErrorCapture:o[0]||(o[0]=x((...a)=>r(n)&&r(n)(...a),["prevent","stop"])),onEnded:o[1]||(o[1]=(...a)=>r(l)&&r(l)(...a)),onPause:o[2]||(o[2]=(...a)=>r(i)&&r(i)(...a)),onPlay:o[3]||(o[3]=(...a)=>r(c)&&r(c)(...a)),onCanplay:o[4]||(o[4]=(...a)=>r(p)&&r(p)(...a))},f(r(z)("Your browser does not support audio.")),41,S)]),_:1},8,["options"])]))}}),W=P(C,[["__scopeId","data-v-5b588862"]]);export{W as default};
//# sourceMappingURL=Audios-D4awE05l.chunk.mjs.map
