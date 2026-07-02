import{f as e,i as t,n,r}from"./dist-BEXrfIzO.chunk.mjs";import{t as i}from"./dist-B1dKxqkS.chunk.mjs";import{r as a,t as o}from"./web-D-iWmCCi.chunk.mjs";import{a as s,i as c,o as l,r as u,s as d,t as f}from"./folder-29HuacU_-CvTjKp0d.chunk.mjs";function p(){return i(`files_sharing`,`isPublic`,null)??document.querySelector(`input#isPublic[type="hidden"][name="isPublic"][value="1"]`)!==null}function m(){return i(`files_sharing`,`sharingToken`,null)??document.querySelector(`input#sharingToken[type="hidden"]`)?.value??null}function h(e=``){let t=s.NONE;return e&&(e.includes(`G`)&&(t|=s.READ),e.includes(`W`)&&(t|=s.WRITE),e.includes(`CK`)&&(t|=s.CREATE),e.includes(`NV`)&&(t|=s.UPDATE),e.includes(`D`)&&(t|=s.DELETE),e.includes(`R`)&&(t|=s.SHARE)),t}var g=[`d:getcontentlength`,`d:getcontenttype`,`d:getetag`,`d:getlastmodified`,`d:creationdate`,`d:displayname`,`d:quota-available-bytes`,`d:resourcetype`,`nc:has-preview`,`nc:is-encrypted`,`nc:mount-type`,`oc:comments-unread`,`oc:favorite`,`oc:fileid`,`oc:owner-display-name`,`oc:owner-id`,`oc:permissions`,`oc:size`],_={d:`DAV:`,nc:`http://nextcloud.org/ns`,oc:`http://owncloud.org/ns`,ocs:`http://open-collaboration-services.org/ns`};function v(e,t={nc:`http://nextcloud.org/ns`}){d.davNamespaces??={..._},d.davProperties??=[...g];let n={...d.davNamespaces,...t};return d.davProperties.find(t=>t===e)?(l.warn(`${e} already registered`,{prop:e}),!1):e.startsWith(`<`)||e.split(`:`).length!==2?(l.error(`${e} is not valid. See example: 'oc:fileid'`,{prop:e}),!1):n[e.split(`:`)[0]]?(d.davProperties.push(e),d.davNamespaces=n,!0):(l.error(`${e} namespace unknown`,{prop:e,namespaces:n}),!1)}function y(){return d.davProperties??=[...g],d.davProperties.map(e=>`<${e} />`).join(` `)}function b(){return d.davNamespaces??={..._},Object.keys(d.davNamespaces).map(e=>`xmlns:${e}="${d.davNamespaces?.[e]}"`).join(` `)}function x(){return`<?xml version="1.0"?>
		<d:propfind ${b()}>
			<d:prop>
				${y()}
			</d:prop>
		</d:propfind>`}function S(){return`<?xml version="1.0"?>
		<oc:filter-files ${b()}>
			<d:prop>
				${y()}
			</d:prop>
			<oc:filter-rules>
				<oc:favorite>1</oc:favorite>
			</oc:filter-rules>
		</oc:filter-files>`}function C(e){return`<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest ${b()}
	xmlns:ns="https://github.com/icewind1991/SearchDAV/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>
				${y()}
			</d:prop>
		</d:select>
		<d:from>
			<d:scope>
				<d:href>/files/${n()?.uid}/</d:href>
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
				<d:gt>
					<d:prop>
						<d:getlastmodified/>
					</d:prop>
					<d:literal>${e}</d:literal>
				</d:gt>
			</d:and>
		</d:where>
		<d:orderby>
			<d:order>
				<d:prop>
					<d:getlastmodified/>
				</d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>100</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`}function w(){return p()?`/files/${m()}`:`/files/${n()?.uid}`}var T=w();function E(){let t=e(`dav`);return p()?t.replace(`remote.php`,`public.php`):t}var D=E();function O(e=D,n={}){let i=a(e,{headers:n});function s(e){i.setHeaders({...n,"X-Requested-With":`XMLHttpRequest`,requesttoken:e??``})}return t(s),s(r()),o().patch(`fetch`,(e,t)=>{let n=t.headers;return n?.method&&(t.method=n.method,delete n.method),fetch(e,t)}),i}async function k(e={}){let t=e.client??O(),n=e.path??`/`,r=e.davRoot??T;return(await t.getDirectoryContents(`${r}${n}`,{signal:e.signal,details:!0,data:S(),headers:{method:`REPORT`},includeSelf:!0})).data.filter(e=>e.filename!==n).map(e=>A(e,r))}function A(e,t=T,r=D){let i=n()?.uid;if(p())i??=`anonymous`;else if(!i)throw Error(`No user id found`);let a=e.props,o=h(a?.permissions),s=String(a?.[`owner-id`]||i),l=a.fileid||0,d=new Date(Date.parse(e.lastmod)),m=new Date(Date.parse(a.creationdate)),g={id:l,source:`${r}${e.filename}`,mtime:!isNaN(d.getTime())&&d.getTime()!==0?d:void 0,crtime:!isNaN(m.getTime())&&m.getTime()!==0?m:void 0,mime:e.mime||`application/octet-stream`,displayname:a.displayname===void 0?void 0:String(a.displayname),size:a?.size||Number.parseInt(a.getcontentlength||`0`),status:l<0?c.FAILED:void 0,permissions:o,owner:s,root:t,attributes:{...e,...a,hasPreview:a?.[`has-preview`]}};return delete g.attributes?.props,e.type===`file`?new f(g):new u(g)}export{y as a,C as c,v as d,A as f,b as i,E as l,T as n,x as o,O as r,k as s,D as t,w as u};
//# sourceMappingURL=dav-DuDWqhEc.chunk.mjs.map