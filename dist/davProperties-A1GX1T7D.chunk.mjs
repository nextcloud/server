import{c as v}from"./public-CCb6YOPg.chunk.mjs";import{g as y,a as g}from"./util-Du_YUKjQ.chunk.mjs";import{g as w}from"./fileType-CZsoCrgz.chunk.mjs";window._nc_files_scope??={},window._nc_files_scope.v4_0??={};const t=window._nc_files_scope.v4_0,s=y().setApp("@nextcloud/files").detectUser().build();var m=class extends EventTarget{dispatchTypedEvent(e,i){return super.dispatchEvent(i)}};class E extends m{}function x(){return t.registry??=new E,t.registry}const I=Object.freeze({DEFAULT:"default",HIDDEN:"hidden"});function B(e){if(b(e),t.fileActions??=new Map,t.fileActions.has(e.id)){s.error(`FileAction ${e.id} already registered`,{action:e});return}t.fileActions.set(e.id,e),x().dispatchTypedEvent("register:action",new CustomEvent("register:action",{detail:e}))}function b(e){if(!e.id||typeof e.id!="string")throw new Error("Invalid id");if(!e.displayName||typeof e.displayName!="function")throw new Error("Invalid displayName function");if("title"in e&&typeof e.title!="function")throw new Error("Invalid title function");if(!e.iconSvgInline||typeof e.iconSvgInline!="function")throw new Error("Invalid iconSvgInline function");if(!e.exec||typeof e.exec!="function")throw new Error("Invalid exec function");if("enabled"in e&&typeof e.enabled!="function")throw new Error("Invalid enabled function");if("execBatch"in e&&typeof e.execBatch!="function")throw new Error("Invalid execBatch function");if("order"in e&&typeof e.order!="number")throw new Error("Invalid order");if(e.destructive!==void 0&&typeof e.destructive!="boolean")throw new Error("Invalid destructive flag");if("parent"in e&&typeof e.parent!="string")throw new Error("Invalid parent");if(e.default&&!Object.values(I).includes(e.default))throw new Error("Invalid default");if("inline"in e&&typeof e.inline!="function")throw new Error("Invalid inline function");if("renderInline"in e&&typeof e.renderInline!="function")throw new Error("Invalid renderInline function");if("hotkey"in e&&e.hotkey!==void 0){if(typeof e.hotkey!="object")throw new Error("Invalid hotkey configuration");if(typeof e.hotkey.key!="string"||!e.hotkey.key)throw new Error("Missing or invalid hotkey key");if(typeof e.hotkey.description!="string"||!e.hotkey.description)throw new Error("Missing or invalid hotkey description")}}const a=["B","KB","MB","GB","TB","PB"],c=["B","KiB","MiB","GiB","TiB","PiB"];function N(e,i=!1,r=!1,o=!1){r=r&&!o,typeof e=="string"&&(e=Number(e));let n=e>0?Math.floor(Math.log(e)/Math.log(o?1e3:1024)):0;n=Math.min((r?c.length:a.length)-1,n);const h=r?c[n]:a[n];let d=(e/Math.pow(o?1e3:1024,n)).toFixed(1);return i===!0&&n===0?(d!=="0.0"?"< 1 ":"0 ")+(r?c[1]:a[1]):(n<2?d=parseFloat(d).toFixed(0):d=parseFloat(d).toLocaleString(v()),d+" "+h)}const f=["d:getcontentlength","d:getcontenttype","d:getetag","d:getlastmodified","d:creationdate","d:displayname","d:quota-available-bytes","d:resourcetype","nc:has-preview","nc:is-encrypted","nc:mount-type","oc:comments-unread","oc:favorite","oc:fileid","oc:owner-display-name","oc:owner-id","oc:permissions","oc:size","nc:upload_time"],u={d:"DAV:",nc:"http://nextcloud.org/ns",oc:"http://owncloud.org/ns",ocs:"http://open-collaboration-services.org/ns"};function M(e,i={nc:"http://nextcloud.org/ns"}){t.davNamespaces??={...u},t.davProperties??=[...f];const r={...t.davNamespaces,...i};if(t.davProperties.find(n=>n===e))return s.warn(`${e} already registered`,{prop:e}),!1;if(e.startsWith("<")||e.split(":").length!==2)return s.error(`${e} is not valid. See example: 'oc:fileid'`,{prop:e}),!1;const o=e.split(":")[0];return r[o]?(t.davProperties.push(e),t.davNamespaces=r,!0):(s.error(`${e} namespace unknown`,{prop:e,namespaces:r}),!1)}function l(){return t.davProperties??=[...f],t.davProperties.map(e=>`<${e} />`).join(" ")}function p(){return t.davNamespaces??={...u},Object.keys(t.davNamespaces).map(e=>`xmlns:${e}="${t.davNamespaces?.[e]}"`).join(" ")}function A(){return`<?xml version="1.0"?>
		<d:propfind ${p()}>
			<d:prop>
				${l()}
			</d:prop>
		</d:propfind>`}function F(){return`<?xml version="1.0"?>
		<oc:filter-files ${p()}>
			<d:prop>
				${l()}
			</d:prop>
			<oc:filter-rules>
				<oc:favorite>1</oc:favorite>
			</oc:filter-rules>
		</oc:filter-files>`}function q(e,i=100){const r=w(),o=r.dav?.search_supports_upload_time,n=r.dav?.search_supports_last_activity?"<nc:last_activity/>":"<d:getlastmodified/>";return`<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest ${p()}
	xmlns:ns="https://github.com/icewind1991/SearchDAV/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>
				${l()}
			</d:prop>
		</d:select>
		<d:from>
			<d:scope>
				<d:href>/files/${g()?.uid}/</d:href>
				<d:depth>infinity</d:depth>
			</d:scope>
		</d:from>
		<d:where>
			<d:and>
				<d:or>
					<d:not>
						<d:eq>
							<d:prop>
								<d:getcontenttype/>
							</d:prop>
							<d:literal>httpd/unix-directory</d:literal>
						</d:eq>
					</d:not>
					<d:eq>
						<d:prop>
							<oc:size/>
						</d:prop>
						<d:literal>0</d:literal>
					</d:eq>
				</d:or>
				${o?`
						<d:or>
							<d:gt>
								<d:prop>
									<d:getlastmodified/>
								</d:prop>
								<d:literal>${e}</d:literal>
							</d:gt>
							<d:gt>
								<d:prop>
									<nc:upload_time/>
								</d:prop>
								<d:literal>${e}</d:literal>
							</d:gt>
						</d:or>
				`:`
					<d:gt>
						<d:prop>
							<d:getlastmodified/>
						</d:prop>
						<d:literal>${e}</d:literal>
					</d:gt>
				`}
			</d:and>
		</d:where>
		<d:orderby>
			<d:order>
				<d:prop>
					${n}
				</d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>${i}</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`}export{I as D,x as a,p as b,l as c,M as d,q as e,N as f,F as g,A as h,m as i,s as l,B as r,t as s};
//# sourceMappingURL=davProperties-A1GX1T7D.chunk.mjs.map
