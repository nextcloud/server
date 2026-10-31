import{F as S}from"./fileType-C3N8eFWN.chunk.mjs";import{n as $,j as l,x as N,w as x,h,a as O}from"./externalStorageUtils-KuBigrYs.chunk.mjs";import{g as R}from"./PencilOutline-D_8aq9iQ.chunk.mjs";import{s,l as c}from"./fileAction-BOfDVRqE.chunk.mjs";const d=Object.freeze({NONE:0,READ:1,UPDATE:2,CREATE:4,WRITE:4,DELETE:8,SHARE:16,ALL:31}),k=Object.freeze({NEW:"new",FAILED:"failed",LOADING:"loading",LOCKED:"locked"});function f(t,e){return t.match(e)!==null}function a(t,e){if(t.id&&typeof t.id!="number"&&typeof t.id!="string")throw new Error("Invalid id type of value");if(!t.source)throw new Error("Missing mandatory source");try{new URL(t.source)}catch{throw new Error("Invalid source format, source must be a valid URL")}if(!t.source.startsWith("http"))throw new Error("Invalid source format, only http(s) is supported");if(!t.root)throw new Error("Missing mandatory root");if(typeof t.root!="string")throw new Error("Invalid root type");if(!t.root.startsWith("/"))throw new Error("Root must start with a leading slash");if(!t.source.includes(t.root))throw new Error("Root must be part of the source");if(f(t.source,e)){const r=t.source.match(e)[0];if(!t.source.includes($(r,t.root)))throw new Error("The root must be relative to the service. e.g /files/emma")}if(t.displayname&&typeof t.displayname!="string")throw new Error("Invalid displayname type");if(t.mtime&&!(t.mtime instanceof Date))throw new Error("Invalid mtime type");if(t.crtime&&!(t.crtime instanceof Date))throw new Error("Invalid crtime type");if(!t.mime||typeof t.mime!="string"||!t.mime.match(/^[-\w.]+\/[-+\w.]+$/gi))throw new Error("Missing or invalid mandatory mime");if("size"in t&&typeof t.size!="number"&&t.size!==void 0)throw new Error("Invalid size type");if("permissions"in t&&t.permissions!==void 0&&!(typeof t.permissions=="number"&&t.permissions>=d.NONE&&t.permissions<=d.ALL))throw new Error("Invalid permissions");if(t.owner&&t.owner!==null&&typeof t.owner!="string")throw new Error("Invalid owner type");if(t.attributes&&typeof t.attributes!="object")throw new Error("Invalid attributes type");if(t.status&&!Object.values(k).includes(t.status))throw new Error("Status must be a valid NodeStatus")}function I(t){t.mtime&&typeof t.mtime=="string"&&!isNaN(Date.parse(t.mtime))&&JSON.stringify(new Date(t.mtime))===JSON.stringify(t.mtime)&&(t.mtime=new Date(t.mtime)),t.crtime&&typeof t.crtime=="string"&&!isNaN(Date.parse(t.crtime))&&JSON.stringify(new Date(t.crtime))===JSON.stringify(t.crtime)&&(t.crtime=new Date(t.crtime))}function A(t){if(t instanceof RegExp)return t;const e=t.match(/(\/?)(.+)\1([a-z]*)/i);if(!e)throw new Error("Invalid regular expression format.");const r=Array.from(new Set(e[3])).filter(i=>"gimsuy".includes(i)).join("");return new RegExp(e[2],r)}class p{_attributes;_data;_knownDavService=/(remote|public)\.php\/(web)?dav/i;readonlyAttributes=Object.entries(Object.getOwnPropertyDescriptors(p.prototype)).filter(e=>typeof e[1].get=="function"&&e[0]!=="__proto__").map(e=>e[0]);handler={set:(e,r,i)=>this.readonlyAttributes.includes(r)?!1:Reflect.set(e,r,i),deleteProperty:(e,r)=>this.readonlyAttributes.includes(r)?!1:Reflect.deleteProperty(e,r)};constructor(...[e,r]){e.mime||(e.mime="application/octet-stream"),I(e),r=A(r||this._knownDavService),a(e,r),this._data={...e,attributes:{}},this._attributes=new Proxy(this._data.attributes,this.handler),this.update(e.attributes??{}),r&&(this._knownDavService=r)}get source(){return this._data.source.replace(/\/$/i,"")}get encodedSource(){const{origin:e}=new URL(this.source);return e+l(this.source.slice(e.length))}get basename(){return N(this.source)}get displayname(){return this._data.displayname||this.basename}set displayname(e){a({...this._data,displayname:e},this._knownDavService),this._data.displayname=e}get extension(){return x(this.source)}get dirname(){return h(this.path)}get mime(){return this._data.mime||"application/octet-stream"}set mime(e){e??="application/octet-stream",a({...this._data,mime:e},this._knownDavService),this._data.mime=e}get mtime(){return this._data.mtime}set mtime(e){a({...this._data,mtime:e},this._knownDavService),this._data.mtime=e}get crtime(){return this._data.crtime}get size(){return this._data.size}set size(e){a({...this._data,size:e},this._knownDavService),this.updateMtime(),this._data.size=e}get attributes(){return this._attributes}get permissions(){return this.owner===null&&!this.isDavResource?d.READ:this._data.permissions!==void 0?this._data.permissions:d.NONE}set permissions(e){a({...this._data,permissions:e},this._knownDavService),this.updateMtime(),this._data.permissions=e}get owner(){return this.isDavResource?this._data.owner:null}get isDavResource(){return f(this.source,this._knownDavService)}get root(){return this._data.root.replace(/^(.+)\/$/,"$1")}get path(){const e=this.source.indexOf("://"),r=this.source.slice(0,e),i=this.source.slice(e+3),o=i.indexOf("/"),g=i.slice(0,o),y=i.slice(o),_=`${r}://${g}${l(y)}`,b=new URL(_);let n=decodeURIComponent(b.pathname);this.isDavResource&&(n=n.split(this._knownDavService).pop());const E=n.indexOf(this.root),D=this.root.replace(/\/$/,"");return n.slice(E+D.length)||"/"}get fileid(){return typeof this._data?.id=="number"?this._data.id:void 0}get id(){if(!(typeof this._data?.id>"u"||typeof this._data.id=="number"&&this._data.id<0))return String(this._data.id)}get status(){return this._data?.status}set status(e){a({...this._data,status:e},this._knownDavService),this._data.status=e}move(e){a({...this._data,source:e},this._knownDavService);const r=this.basename;this._data.source=e,this.displayname===r&&this.basename!==r&&(this.displayname=this.basename)}rename(e){if(e.includes("/"))throw new Error("Invalid basename");this.move(h(this.source)+"/"+e)}updateMtime(){this._data.mtime&&(this._data.mtime=new Date)}update(e){for(const[r,i]of Object.entries(e))try{i===void 0?delete this.attributes[r]:this.attributes[r]=i}catch(o){if(o instanceof TypeError)continue;throw o}}clone(){return new this.constructor(structuredClone(this._data),this._knownDavService)}toJSON(){return JSON.stringify([structuredClone(this._data),this._knownDavService.toString()])}}class T extends p{constructor(...[e,r]){super({...e,mime:"httpd/unix-directory"},r)}get type(){return S.Folder}get extension(){return null}get mime(){return"httpd/unix-directory"}}const w=["d:getcontentlength","d:getcontenttype","d:getetag","d:getlastmodified","d:creationdate","d:displayname","d:quota-available-bytes","d:resourcetype","nc:has-preview","nc:is-encrypted","nc:mount-type","oc:comments-unread","oc:favorite","oc:fileid","oc:owner-display-name","oc:owner-id","oc:permissions","oc:size","nc:upload_time"],v={d:"DAV:",nc:"http://nextcloud.org/ns",oc:"http://owncloud.org/ns",ocs:"http://open-collaboration-services.org/ns"};function U(t,e={nc:"http://nextcloud.org/ns"}){s.davNamespaces??={...v},s.davProperties??=[...w];const r={...s.davNamespaces,...e};if(s.davProperties.find(o=>o===t))return c.warn(`${t} already registered`,{prop:t}),!1;if(t.startsWith("<")||t.split(":").length!==2)return c.error(`${t} is not valid. See example: 'oc:fileid'`,{prop:t}),!1;const i=t.split(":")[0];return r[i]?(s.davProperties.push(t),s.davNamespaces=r,!0):(c.error(`${t} namespace unknown`,{prop:t,namespaces:r}),!1)}function m(){return s.davProperties??=[...w],s.davProperties.map(t=>`<${t} />`).join(" ")}function u(){return s.davNamespaces??={...v},Object.keys(s.davNamespaces).map(t=>`xmlns:${t}="${s.davNamespaces?.[t]}"`).join(" ")}function q(){return`<?xml version="1.0"?>
		<d:propfind ${u()}>
			<d:prop>
				${m()}
			</d:prop>
		</d:propfind>`}function F(){return`<?xml version="1.0"?>
		<oc:filter-files ${u()}>
			<d:prop>
				${m()}
			</d:prop>
			<oc:filter-rules>
				<oc:favorite>1</oc:favorite>
			</oc:filter-rules>
		</oc:filter-files>`}function J(t,e=100){const r=R(),i=r.dav?.search_supports_upload_time,o=r.dav?.search_supports_last_activity?"<nc:last_activity/>":"<d:getlastmodified/>";return`<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest ${u()}
	xmlns:ns="https://github.com/icewind1991/SearchDAV/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>
				${m()}
			</d:prop>
		</d:select>
		<d:from>
			<d:scope>
				<d:href>/files/${O()?.uid}/</d:href>
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
				${i?`
						<d:or>
							<d:gt>
								<d:prop>
									<d:getlastmodified/>
								</d:prop>
								<d:literal>${t}</d:literal>
							</d:gt>
							<d:gt>
								<d:prop>
									<nc:upload_time/>
								</d:prop>
								<d:literal>${t}</d:literal>
							</d:gt>
						</d:or>
				`:`
					<d:gt>
						<d:prop>
							<d:getlastmodified/>
						</d:prop>
						<d:literal>${t}</d:literal>
					</d:gt>
				`}
			</d:and>
		</d:where>
		<d:orderby>
			<d:order>
				<d:prop>
					${o}
				</d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>${e}</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`}export{T as F,p as N,d as P,k as a,u as b,m as c,J as d,q as e,F as g,U as r};
//# sourceMappingURL=davProperties-DQigZkzf.chunk.mjs.map
