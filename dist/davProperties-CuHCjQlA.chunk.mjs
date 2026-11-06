import{g as l,a as f}from"./index-x_cFefV-.chunk.mjs";import{g as u}from"./PencilOutline-UgxLe6al.chunk.mjs";window._nc_files_scope??={},window._nc_files_scope.v4_0??={};const t=window._nc_files_scope.v4_0,i=l().setApp("@nextcloud/files").detectUser().build();var h=class extends EventTarget{dispatchTypedEvent(e,n){return super.dispatchEvent(n)}};class v extends h{}function y(){return t.registry??=new v,t.registry}const w=Object.freeze({DEFAULT:"default",HIDDEN:"hidden"});function I(e){if(g(e),t.fileActions??=new Map,t.fileActions.has(e.id)){i.error(`FileAction ${e.id} already registered`,{action:e});return}t.fileActions.set(e.id,e),y().dispatchTypedEvent("register:action",new CustomEvent("register:action",{detail:e}))}function g(e){if(!e.id||typeof e.id!="string")throw new Error("Invalid id");if(!e.displayName||typeof e.displayName!="function")throw new Error("Invalid displayName function");if("title"in e&&typeof e.title!="function")throw new Error("Invalid title function");if(!e.iconSvgInline||typeof e.iconSvgInline!="function")throw new Error("Invalid iconSvgInline function");if(!e.exec||typeof e.exec!="function")throw new Error("Invalid exec function");if("enabled"in e&&typeof e.enabled!="function")throw new Error("Invalid enabled function");if("execBatch"in e&&typeof e.execBatch!="function")throw new Error("Invalid execBatch function");if("order"in e&&typeof e.order!="number")throw new Error("Invalid order");if(e.destructive!==void 0&&typeof e.destructive!="boolean")throw new Error("Invalid destructive flag");if("parent"in e&&typeof e.parent!="string")throw new Error("Invalid parent");if(e.default&&!Object.values(w).includes(e.default))throw new Error("Invalid default");if("inline"in e&&typeof e.inline!="function")throw new Error("Invalid inline function");if("renderInline"in e&&typeof e.renderInline!="function")throw new Error("Invalid renderInline function");if("hotkey"in e&&e.hotkey!==void 0){if(typeof e.hotkey!="object")throw new Error("Invalid hotkey configuration");if(typeof e.hotkey.key!="string"||!e.hotkey.key)throw new Error("Missing or invalid hotkey key");if(typeof e.hotkey.description!="string"||!e.hotkey.description)throw new Error("Missing or invalid hotkey description")}}const c=["d:getcontentlength","d:getcontenttype","d:getetag","d:getlastmodified","d:creationdate","d:displayname","d:quota-available-bytes","d:resourcetype","nc:has-preview","nc:is-encrypted","nc:mount-type","oc:comments-unread","oc:favorite","oc:fileid","oc:owner-display-name","oc:owner-id","oc:permissions","oc:size","nc:upload_time"],p={d:"DAV:",nc:"http://nextcloud.org/ns",oc:"http://owncloud.org/ns",ocs:"http://open-collaboration-services.org/ns"};function x(e,n={nc:"http://nextcloud.org/ns"}){t.davNamespaces??={...p},t.davProperties??=[...c];const r={...t.davNamespaces,...n};if(t.davProperties.find(d=>d===e))return i.warn(`${e} already registered`,{prop:e}),!1;if(e.startsWith("<")||e.split(":").length!==2)return i.error(`${e} is not valid. See example: 'oc:fileid'`,{prop:e}),!1;const o=e.split(":")[0];return r[o]?(t.davProperties.push(e),t.davNamespaces=r,!0):(i.error(`${e} namespace unknown`,{prop:e,namespaces:r}),!1)}function s(){return t.davProperties??=[...c],t.davProperties.map(e=>`<${e} />`).join(" ")}function a(){return t.davNamespaces??={...p},Object.keys(t.davNamespaces).map(e=>`xmlns:${e}="${t.davNamespaces?.[e]}"`).join(" ")}function _(){return`<?xml version="1.0"?>
		<d:propfind ${a()}>
			<d:prop>
				${s()}
			</d:prop>
		</d:propfind>`}function $(){return`<?xml version="1.0"?>
		<oc:filter-files ${a()}>
			<d:prop>
				${s()}
			</d:prop>
			<oc:filter-rules>
				<oc:favorite>1</oc:favorite>
			</oc:filter-rules>
		</oc:filter-files>`}function b(e,n=100){const r=u(),o=r.dav?.search_supports_upload_time,d=r.dav?.search_supports_last_activity?"<nc:last_activity/>":"<d:getlastmodified/>";return`<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest ${a()}
	xmlns:ns="https://github.com/icewind1991/SearchDAV/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>
				${s()}
			</d:prop>
		</d:select>
		<d:from>
			<d:scope>
				<d:href>/files/${f()?.uid}/</d:href>
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
					${d}
				</d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>${n}</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`}export{w as D,y as a,a as b,s as c,x as d,b as e,_ as f,$ as g,h,i as l,I as r,t as s};
//# sourceMappingURL=davProperties-CuHCjQlA.chunk.mjs.map
