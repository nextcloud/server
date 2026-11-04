import{F as p,h as c}from"./tr-BYfz54C4.chunk.mjs";import{b8 as m}from"./sr-TvbeBsrd.chunk.mjs";import{s as t,l as u,c as f}from"./pt-B7ciMCad.chunk.mjs";import{a as g}from"./th-xm605lYx.chunk.mjs";class w extends m{constructor(...[i,a]){super(i,a)}get type(){return p.File}}function S(e){if(k(e),t.fileListActions??=new Map,t.fileListActions.has(e.id)){u.error(`FileListAction with id "${e.id}" is already registered`,{action:e});return}t.fileListActions.set(e.id,e),f().dispatchTypedEvent("register:listAction",new CustomEvent("register:listAction",{detail:e}))}function B(){return t.fileListActions?[...t.fileListActions.values()]:[]}function k(e){if(!e.id||typeof e.id!="string")throw new Error("Invalid id");if(!e.displayName||typeof e.displayName!="function")throw new Error("Invalid displayName function");if("iconSvgInline"in e&&typeof e.iconSvgInline!="function")throw new Error("Invalid iconSvgInline function");if("order"in e&&typeof e.order!="number")throw new Error("Invalid order");if("enabled"in e&&typeof e.enabled!="function")throw new Error("Invalid enabled function");if(!e.exec||typeof e.exec!="function")throw new Error("Invalid exec function")}const s=["d:getcontentlength","d:getcontenttype","d:getetag","d:getlastmodified","d:creationdate","d:displayname","d:quota-available-bytes","d:resourcetype","nc:has-preview","nc:is-encrypted","nc:mount-type","oc:comments-unread","oc:favorite","oc:fileid","oc:owner-display-name","oc:owner-id","oc:permissions","oc:size","nc:upload_time"],l={d:"DAV:",nc:"http://nextcloud.org/ns",oc:"http://owncloud.org/ns",ocs:"http://open-collaboration-services.org/ns"};function C(e,i={nc:"http://nextcloud.org/ns"}){t.davNamespaces??={...l},t.davProperties??=[...s];const a={...t.davNamespaces,...i};if(t.davProperties.find(o=>o===e))return u.warn(`${e} already registered`,{prop:e}),!1;if(e.startsWith("<")||e.split(":").length!==2)return u.error(`${e} is not valid. See example: 'oc:fileid'`,{prop:e}),!1;const n=e.split(":")[0];return a[n]?(t.davProperties.push(e),t.davNamespaces=a,!0):(u.error(`${e} namespace unknown`,{prop:e,namespaces:a}),!1)}function r(){return t.davProperties??=[...s],t.davProperties.map(e=>`<${e} />`).join(" ")}function d(){return t.davNamespaces??={...l},Object.keys(t.davNamespaces).map(e=>`xmlns:${e}="${t.davNamespaces?.[e]}"`).join(" ")}function P(){return`<?xml version="1.0"?>
		<d:propfind ${d()}>
			<d:prop>
				${r()}
			</d:prop>
		</d:propfind>`}function T(){return`<?xml version="1.0"?>
		<oc:filter-files ${d()}>
			<d:prop>
				${r()}
			</d:prop>
			<oc:filter-rules>
				<oc:favorite>1</oc:favorite>
			</oc:filter-rules>
		</oc:filter-files>`}function j(e,i=100){const a=c(),n=a.dav?.search_supports_upload_time,o=a.dav?.search_supports_last_activity?"<nc:last_activity/>":"<d:getlastmodified/>";return`<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest ${d()}
	xmlns:ns="https://github.com/icewind1991/SearchDAV/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>
				${r()}
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
				${n?`
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
					${o}
				</d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>${i}</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`}var b={buttons:{play:"Reproducir",pause:"Pausar",replay:"Volver a reproducir",mute:"Silenciar",unmute:"Activar el sonido"},seek:{forward:"Avanzar {seconds} segundos",backward:"Retroceder {seconds} segundos"},fullscreen:{enter:"Pantalla completa",exit:"Salir de pantalla completa"},captions:{enable:"Activar subtítulos",disable:"Desactivar subtítulos"},pip:{enter:"Imagen en imagen",exit:"Salir de imagen en imagen"},live:{playing:"Reproduciendo en directo",seekToEdge:"Ir al directo",badge:"Directo"},cast:{start:"Iniciar transmisión",stop:"Detener transmisión",connecting:"Conectando"},airplay:{start:"Iniciar AirPlay",stop:"Detener AirPlay"},slider:{seek:"Buscar"},time:{current:"Tiempo reproducido",duration:"Duración total",remaining:"Tiempo restante",elapsedSuffix:"{duration} de tiempo transcurrido",durationSuffix:"{duration} de duración",remainingSuffix:"Quedan {duration}",showElapsed:"Mostrar tiempo transcurrido, {duration}.",showDuration:"Mostrar duración, {duration}.",showRemaining:"Mostrar tiempo restante, {duration}.",toggleElapsed:"Alternar entre el tiempo transcurrido y el tiempo restante.",toggleDuration:"Alternar entre la duración y el tiempo restante.",position:"{current} de {duration}",unknown:"Contenido multimedia no cargado, tiempo desconocido."},playback:{rate:"Velocidad de reproducción {rate}"},volume:{mutedValue:"{percent}, silenciado",muted:"Silenciado",label:"Volumen",value:"Volumen {value}"},status:{captionsOn:"Subtítulos activados",captionsOff:"Subtítulos desactivados",paused:"En pausa",playing:"Reproduciendo",fullscreen:"Pantalla completa",pip:"Imagen en imagen",exitPip:"Salir de imagen en imagen",seekedTo:"Se ha saltado a {time}"},container:{label:"Reproductor multimedia"},errors:{aborted:"Has detenido la reproducción del contenido multimedia antes de que terminara.",network:"No se ha podido cargar este contenido multimedia debido a un problema de red o del servidor.",decode:"No se ha podido reproducir este contenido multimedia. Puede que esté dañado o que tu navegador no admita su formato.",source:"No se ha podido cargar este contenido multimedia. Puede que no esté disponible o que tu navegador no admita su formato.",encrypted:"No se ha podido reproducir este contenido multimedia porque no se ha podido descifrar.",unplayable:"El reproductor no admite este contenido multimedia.",title:"Algo ha salido mal.",unexpected:"Se ha producido un error inesperado."},common:{empty:"",ok:"Cerrar"},menu:{settings:"Configuración",quality:"Calidad",audio:"Audio",default:"Predeterminado",speed:"Velocidad",captions:"Subtítulos",playbackRate:"Velocidad de reproducción",back:"Atrás",off:"Desactivado",auto:"Automático",autoWithLabel:"Automático ({label})",subtitles:"Subtítulos"}};const M=Object.freeze(Object.defineProperty({__proto__:null,default:b},Symbol.toStringTag,{value:"Module"}));var v={buttons:{play:"Putar",pause:"Jeda",replay:"Putar ulang",mute:"Bisukan",unmute:"Bunyikan"},seek:{forward:"Maju {seconds} detik",backward:"Mundur {seconds} detik"},fullscreen:{enter:"Masuk layar penuh",exit:"Keluar dari layar penuh"},captions:{enable:"Aktifkan teks",disable:"Nonaktifkan teks"},pip:{enter:"Masuk mode mini",exit:"Keluar dari mode mini"},live:{playing:"Sedang diputar langsung",seekToEdge:"Ke siaran langsung",badge:"Langsung"},cast:{start:"Mulai transmisi",stop:"Hentikan transmisi",connecting:"Menghubungkan"},airplay:{start:"Mulai AirPlay",stop:"Hentikan AirPlay"},slider:{seek:"Bilah geser"},time:{current:"Waktu saat ini",duration:"Durasi",remaining:"Waktu tersisa",elapsedSuffix:"{duration} berlalu",durationSuffix:"Durasi {duration}",remainingSuffix:"{duration} tersisa",showElapsed:"Tampilkan waktu berlalu, {duration}.",showDuration:"Tampilkan durasi, {duration}.",showRemaining:"Tampilkan waktu tersisa, {duration}.",toggleElapsed:"Beralih antara waktu berlalu dan waktu tersisa.",toggleDuration:"Beralih antara durasi dan waktu tersisa.",position:"{current} dari {duration}",unknown:"Media belum dimuat, waktu tidak diketahui."},playback:{rate:"Kecepatan pemutaran {rate}"},volume:{mutedValue:"{percent}, dibisukan",muted:"Dibisukan",label:"Volume",value:"Volume {value}"},status:{captionsOn:"Teks aktif",captionsOff:"Teks nonaktif",paused:"Dijeda",playing:"Sedang diputar",fullscreen:"Layar penuh",pip:"Mode mini",exitPip:"Keluar dari mode mini",seekedTo:"Melompat ke {time}"},container:{label:"Pemutar media"},errors:{aborted:"Anda menghentikan pemutaran media sebelum selesai.",network:"Media tidak dapat dimuat karena masalah jaringan atau server.",decode:"Media tidak dapat diputar. Media mungkin rusak atau formatnya tidak didukung oleh peramban Anda.",source:"Media tidak dapat dimuat. Media mungkin tidak tersedia atau formatnya tidak didukung oleh peramban Anda.",encrypted:"Media tidak dapat diputar karena tidak dapat didekripsi.",unplayable:"Media ini tidak didukung oleh pemutar.",title:"Terjadi kesalahan.",unexpected:"Terjadi kesalahan yang tidak terduga."},common:{empty:"",ok:"OK"},menu:{settings:"Pengaturan",quality:"Kualitas",audio:"Audio",default:"Bawaan",speed:"Kecepatan",captions:"Pilihan teks",playbackRate:"Kecepatan pemutaran",back:"Kembali",off:"Nonaktif",auto:"Otomatis",autoWithLabel:"Otomatis ({label})",subtitles:"Subtitel"}};const _=Object.freeze(Object.defineProperty({__proto__:null,default:v},Symbol.toStringTag,{value:"Module"}));var D={buttons:{play:"Riproduci",pause:"Pausa",replay:"Riproduci di nuovo",mute:"Disattiva l’audio",unmute:"Attiva l’audio"},seek:{forward:"Avanti di {seconds} secondi",backward:"Indietro di {seconds} secondi"},fullscreen:{enter:"Schermo intero",exit:"Esci da schermo intero"},captions:{enable:"Attiva i sottotitoli",disable:"Disattiva i sottotitoli"},pip:{enter:"Riproduzione in finestra",exit:"Chiudi riproduzione in finestra"},live:{playing:"Riproduzione in diretta",seekToEdge:"Vai alla diretta",badge:"In diretta"},cast:{start:"Avvia trasmissione",stop:"Interrompi trasmissione",connecting:"Connessione"},airplay:{start:"Avvia AirPlay",stop:"Interrompi AirPlay"},slider:{seek:"Scorrimento"},time:{current:"Tempo attuale",duration:"Durata",remaining:"Tempo rimanente",elapsedSuffix:"{duration} di tempo trascorso",durationSuffix:"{duration} di durata",remainingSuffix:"Restano {duration}",showElapsed:"Mostra tempo trascorso, {duration}.",showDuration:"Mostra durata, {duration}.",showRemaining:"Mostra tempo rimanente, {duration}.",toggleElapsed:"Alterna tra il tempo trascorso e il tempo rimanente.",toggleDuration:"Alterna tra la durata e il tempo rimanente.",position:"{current} di {duration}",unknown:"Contenuto multimediale non caricato, tempo sconosciuto."},playback:{rate:"Velocità di riproduzione {rate}"},volume:{mutedValue:"{percent}, audio disattivato",muted:"Audio disattivato",label:"Livello del volume",value:"Livello del volume {value}"},status:{captionsOn:"Sottotitoli attivi",captionsOff:"Sottotitoli disattivi",paused:"In pausa",playing:"In riproduzione",fullscreen:"Schermo intero",pip:"Riproduzione in finestra",exitPip:"Chiudi riproduzione in finestra",seekedTo:"Posizione di riproduzione: {time}"},container:{label:"Lettore multimediale"},errors:{aborted:"Hai interrotto la riproduzione del contenuto multimediale prima della fine.",network:"Impossibile caricare il contenuto multimediale a causa di un problema di rete o del server.",decode:"Impossibile riprodurre il contenuto multimediale. Potrebbe essere danneggiato oppure il browser potrebbe non supportarne il formato.",source:"Impossibile caricare il contenuto multimediale. Potrebbe non essere disponibile oppure il browser potrebbe non supportarne il formato.",encrypted:"Impossibile riprodurre il contenuto multimediale perché non è stato possibile decriptarlo.",unplayable:"Questo contenuto multimediale non è supportato dal lettore.",title:"Qualcosa è andato storto.",unexpected:"Si è verificato un errore imprevisto."},common:{empty:"",ok:"Chiudi"},menu:{settings:"Impostazioni",quality:"Qualità",audio:"Audio",default:"Predefinito",speed:"Velocità",captions:"Sottotitoli",playbackRate:"Velocità di riproduzione",back:"Indietro",off:"Disattivato",auto:"Auto",autoWithLabel:"Auto ({label})",subtitles:"Sottotitoli"}};const I=Object.freeze(Object.defineProperty({__proto__:null,default:D},Symbol.toStringTag,{value:"Module"}));var F={buttons:{play:"Spill av",pause:"Pause",replay:"Spill av på nytt",mute:"Slå av lyden",unmute:"Slå på lyden"},seek:{forward:"Hopp frem {seconds} sekunder",backward:"Hopp tilbake {seconds} sekunder"},fullscreen:{enter:"Fullskjerm",exit:"Avslutt fullskjerm"},captions:{enable:"Slå på teksting",disable:"Slå av teksting"},pip:{enter:"Bilde-i-bilde",exit:"Avslutt bilde-i-bilde"},live:{playing:"Spiller direkte",seekToEdge:"Gå til direktesendingen",badge:"Direkte"},cast:{start:"Start casting",stop:"Stopp casting",connecting:"Kobler til"},airplay:{start:"Start AirPlay",stop:"Stopp AirPlay"},slider:{seek:"Spol"},time:{current:"Aktuell tid",duration:"Varighet",remaining:"Gjenstående tid",elapsedSuffix:"{duration} avspilt tid",durationSuffix:"{duration} varighet",remainingSuffix:"{duration} igjen",showElapsed:"Vis avspilt tid, {duration}.",showDuration:"Vis varighet, {duration}.",showRemaining:"Vis gjenstående tid, {duration}.",toggleElapsed:"Bytt mellom avspilt og gjenstående tid.",toggleDuration:"Bytt mellom varighet og gjenstående tid.",position:"{current} av {duration}",unknown:"Mediet er ikke lastet inn, ukjent tid."},playback:{rate:"Avspillingshastighet {rate}"},volume:{mutedValue:"{percent}, dempet",muted:"Dempet",label:"Volum",value:"Volum {value}"},status:{captionsOn:"Teksting på",captionsOff:"Teksting av",paused:"Satt på pause",playing:"Spiller",fullscreen:"Fullskjerm",pip:"Bilde-i-bilde",exitPip:"Bilde-i-bilde av",seekedTo:"Hoppet til {time}"},container:{label:"Mediespiller"},errors:{aborted:"Du stoppet avspillingen av mediet før den var ferdig.",network:"Dette mediet kunne ikke lastes inn på grunn av en nettverks- eller serverfeil.",decode:"Dette mediet kunne ikke spilles av. Det kan være ødelagt, eller nettleseren din støtter kanskje ikke formatet.",source:"Dette mediet kunne ikke lastes inn. Det kan være utilgjengelig, eller nettleseren din støtter kanskje ikke formatet.",encrypted:"Dette mediet kunne ikke spilles av fordi det ikke kunne dekrypteres.",unplayable:"Denne mediefilen støttes ikke av spilleren.",title:"Noe gikk galt.",unexpected:"Det oppstod en uventet feil."},common:{empty:"",ok:"Lukk"},menu:{settings:"Innstillinger",quality:"Kvalitet",audio:"Lyd",default:"Standard",speed:"Hastighet",captions:"Teksting",playbackRate:"Avspillingshastighet",back:"Tilbake",off:"Av",auto:"Auto",autoWithLabel:"Auto ({label})",subtitles:"Undertekster"}};const V=Object.freeze(Object.defineProperty({__proto__:null,default:F},Symbol.toStringTag,{value:"Module"}));var y={buttons:{play:"播放",pause:"暫停",replay:"重播",mute:"靜音",unmute:"取消靜音"},seek:{forward:"快轉 {seconds} 秒",backward:"倒轉 {seconds} 秒"},fullscreen:{enter:"全螢幕",exit:"退出全螢幕"},captions:{enable:"開啟字幕",disable:"關閉字幕"},pip:{enter:"子母畫面",exit:"離開子母畫面"},live:{playing:"正在直播",seekToEdge:"跳轉至直播",badge:"直播"},cast:{start:"開始投放",stop:"停止投放",connecting:"連線中"},airplay:{start:"啟動 AirPlay",stop:"停止 AirPlay"},slider:{seek:"定位"},time:{current:"目前時間",duration:"總時長",remaining:"剩餘時間",elapsedSuffix:"已播放時間 {duration}",durationSuffix:"時長 {duration}",remainingSuffix:"剩餘 {duration}",showElapsed:"顯示已播放時間，{duration}。",showDuration:"顯示時長，{duration}。",showRemaining:"顯示剩餘時間，{duration}。",toggleElapsed:"在已播放時間和剩餘時間之間切換。",toggleDuration:"在時長和剩餘時間之間切換。",position:"{current}，總時長 {duration}",unknown:"媒體未載入，時間未知。"},playback:{rate:"播放速率 {rate}"},volume:{mutedValue:"{percent}，已靜音",muted:"已靜音",label:"音量",value:"音量 {value}"},status:{captionsOn:"字幕已開啟",captionsOff:"字幕已關閉",paused:"已暫停",playing:"正在播放",fullscreen:"全螢幕",pip:"子母畫面",exitPip:"離開子母畫面",seekedTo:"已跳轉至 {time}"},container:{label:"媒體播放器"},errors:{aborted:"您在媒體播放完成前停止了播放。",network:"由於網路或伺服器問題，無法載入此媒體。",decode:"無法播放此媒體。檔案可能已損毀，或您的瀏覽器可能不支援其格式。",source:"無法載入此媒體。媒體可能無法使用，或您的瀏覽器可能不支援其格式。",encrypted:"無法播放此媒體，因為無法解密。",unplayable:"播放器不支援此媒體。",title:"發生問題。",unexpected:"發生非預期的錯誤。"},common:{empty:"",ok:"關閉"},menu:{settings:"設定",quality:"畫質",audio:"音訊",default:"預設",speed:"速度",captions:"字幕",playbackRate:"播放速率",back:"返回",off:"關閉",auto:"自動",autoWithLabel:"自動（{label}）",subtitles:"字幕"}};const O=Object.freeze(Object.defineProperty({__proto__:null,default:y},Symbol.toStringTag,{value:"Module"}));export{w as F,d as a,r as b,C as c,j as d,B as e,T as f,P as g,M as h,_ as i,I as j,V as n,S as r,O as z};
//# sourceMappingURL=zh-TW-dJnGa48D.chunk.mjs.map
