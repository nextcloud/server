/*! third party licenses: dist/vendor.LICENSE.txt */
import{bQ as a}from"../core-common.mjs";const r=(o,n)=>{let e=o.getElementsByTagName("head")[0].getAttribute("data-requesttoken");return{getToken:()=>e,setToken:s=>{e=s,n("csrf-token-update",{token:e})}}},t=r(document,a),g=t.getToken,c=t.setToken;export{g,c as s};
