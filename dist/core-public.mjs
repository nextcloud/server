const s=document.body,o=document.querySelector("footer");let t=o?.offsetHeight;const c=r=>{for(const n of r){const e=n.contentRect.height;if(e===t)return;t=e,s.style.setProperty("--footer-height",`${e}px`)}};o&&new ResizeObserver(c).observe(o,{box:"border-box"});
//# sourceMappingURL=core-public.mjs.map
