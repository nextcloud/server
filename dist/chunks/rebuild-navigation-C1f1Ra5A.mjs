/*! third party licenses: dist/vendor.LICENSE.txt */
import{bR as a,b$ as s,bQ as o}from"../core-common.mjs";const n=()=>a.get(s("core/navigation",2)+"/apps?format=json").then(({data:t})=>{t.ocs.meta.statuscode===200&&(o("nextcloud:app-menu.refresh",{apps:t.ocs.data}),window.dispatchEvent(new Event("resize")))});export{n as r};
