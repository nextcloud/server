import{F as E}from"./tr-B-CtBHrs.chunk.mjs";import{e as p}from"./fi-yJab7iGq.chunk.mjs";import{s as e,l as i,c as D}from"./te-C2QySp4q.chunk.mjs";import{a as F}from"./ko-RiHnx_R6.chunk.mjs";import{g as c}from"./pt-PT-NYS8B2ZP.chunk.mjs";class h extends p{constructor(...[a,t]){super(a,t)}get type(){return E.File}}function w(u){if(A(u),e.fileListActions??=new Map,e.fileListActions.has(u.id)){i.error(`FileListAction with id "${u.id}" is already registered`,{action:u});return}e.fileListActions.set(u.id,u),D().dispatchTypedEvent("register:listAction",new CustomEvent("register:listAction",{detail:u}))}function S(){return e.fileListActions?[...e.fileListActions.values()]:[]}function A(u){if(!u.id||typeof u.id!="string")throw new Error("Invalid id");if(!u.displayName||typeof u.displayName!="function")throw new Error("Invalid displayName function");if("iconSvgInline"in u&&typeof u.iconSvgInline!="function")throw new Error("Invalid iconSvgInline function");if("order"in u&&typeof u.order!="number")throw new Error("Invalid order");if("enabled"in u&&typeof u.enabled!="function")throw new Error("Invalid enabled function");if(!u.exec||typeof u.exec!="function")throw new Error("Invalid exec function")}const l=["d:getcontentlength","d:getcontenttype","d:getetag","d:getlastmodified","d:creationdate","d:displayname","d:quota-available-bytes","d:resourcetype","nc:has-preview","nc:is-encrypted","nc:mount-type","oc:comments-unread","oc:favorite","oc:fileid","oc:owner-display-name","oc:owner-id","oc:permissions","oc:size","nc:upload_time"],s={d:"DAV:",nc:"http://nextcloud.org/ns",oc:"http://owncloud.org/ns",ocs:"http://open-collaboration-services.org/ns"};function _(u,a={nc:"http://nextcloud.org/ns"}){e.davNamespaces??={...s},e.davProperties??=[...l];const t={...e.davNamespaces,...a};if(e.davProperties.find(r=>r===u))return i.warn(`${u} already registered`,{prop:u}),!1;if(u.startsWith("<")||u.split(":").length!==2)return i.error(`${u} is not valid. See example: 'oc:fileid'`,{prop:u}),!1;const n=u.split(":")[0];return t[n]?(e.davProperties.push(u),e.davNamespaces=t,!0):(i.error(`${u} namespace unknown`,{prop:u,namespaces:t}),!1)}function o(){return e.davProperties??=[...l],e.davProperties.map(u=>`<${u} />`).join(" ")}function d(){return e.davNamespaces??={...s},Object.keys(e.davNamespaces).map(u=>`xmlns:${u}="${e.davNamespaces?.[u]}"`).join(" ")}function P(){return`<?xml version="1.0"?>
		<d:propfind ${d()}>
			<d:prop>
				${o()}
			</d:prop>
		</d:propfind>`}function O(){return`<?xml version="1.0"?>
		<oc:filter-files ${d()}>
			<d:prop>
				${o()}
			</d:prop>
			<oc:filter-rules>
				<oc:favorite>1</oc:favorite>
			</oc:filter-rules>
		</oc:filter-files>`}function T(u,a=100){const t=c(),n=t.dav?.search_supports_upload_time,r=t.dav?.search_supports_last_activity?"<nc:last_activity/>":"<d:getlastmodified/>";return`<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest ${d()}
	xmlns:ns="https://github.com/icewind1991/SearchDAV/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>
				${o()}
			</d:prop>
		</d:select>
		<d:from>
			<d:scope>
				<d:href>/files/${F()?.uid}/</d:href>
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
								<d:literal>${u}</d:literal>
							</d:gt>
							<d:gt>
								<d:prop>
									<nc:upload_time/>
								</d:prop>
								<d:literal>${u}</d:literal>
							</d:gt>
						</d:or>
				`:`
					<d:gt>
						<d:prop>
							<d:getlastmodified/>
						</d:prop>
						<d:literal>${u}</d:literal>
					</d:gt>
				`}
			</d:and>
		</d:where>
		<d:orderby>
			<d:order>
				<d:prop>
					${r}
				</d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>${a}</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>`}var C={buttons:{play:"Oynat",pause:"Fasilə",replay:"Yenidən oynat",mute:"Səssiz et",unmute:"Səsi aç"},seek:{forward:"{seconds} saniyə irəliyə",backward:"{seconds} saniyə geriyə"},fullscreen:{enter:"Tam ekran",exit:"Tam ekrandan çıx"},captions:{enable:"Altyazıları aktiv et",disable:"Altyazıları deaktiv et"},pip:{enter:"Şəkildə şəkil rejimi",exit:"Şəkildə şəkil rejimindən çıx"},live:{playing:"Canlı yayımda",seekToEdge:"Canlı yayıma keç",badge:"Canlı"},cast:{start:"Yayımı başlat",stop:"Yayımı durdur",connecting:"Qoşulur"},airplay:{start:"AirPlay-i başlat",stop:"AirPlay-i dayandır"},slider:{seek:"Sürüşdür"},time:{current:"Cari vaxt",duration:"Müddət",remaining:"Qalan vaxt",elapsedSuffix:"{duration} keçən vaxt",durationSuffix:"{duration} müddət",remainingSuffix:"Qalan {duration}",showElapsed:"Keçən vaxtı göstər, {duration}.",showDuration:"Müddəti göstər, {duration}.",showRemaining:"Qalan vaxtı göstər, {duration}.",toggleElapsed:"Keçən vaxt və qalan vaxt arasında keçid edin.",toggleDuration:"Müddət və qalan vaxt arasında keçid edin.",position:"{current} / {duration}",unknown:"Media yüklənməyib, vaxt məlum deyil."},playback:{rate:"Oynatma sürəti {rate}"},volume:{mutedValue:"{percent}, səssiz",muted:"Səssiz",label:"Səs",value:"Səs {value}"},status:{captionsOn:"Altyazılar aktivdir",captionsOff:"Altyazılar deaktivdir",paused:"Dayandırılıb",playing:"Oynadılır",fullscreen:"Tam ekran",pip:"Şəkildə şəkil",exitPip:"Şəkildə şəkil rejimindən çıx",seekedTo:"{time} vaxtına keçildi"},container:{label:"Media pleyeri"},errors:{aborted:"Siz medianın oxudulmasını başa çatmamış dayandırdınız.",network:"Şəbəkə və ya server problemi səbəbindən bu media yüklənə bilmədi.",decode:"Bu media oxudula bilmədi. Media korlanmış ola bilər və ya brauzeriniz onun formatını dəstəkləməyə bilər.",source:"Bu media yüklənə bilmədi. O, əlçatan olmaya bilər və ya brauzeriniz onun formatını dəstəkləməyə bilər.",encrypted:"Şifrəsi açıla bilmədiyi üçün bu media oxudula bilmədi.",unplayable:"Bu media pleyer tərəfindən dəstəklənmir.",title:"Xəta oldu.",unexpected:"Gözlənilməz xəta baş verdi."},common:{empty:"",ok:"Bağla"},menu:{settings:"Parametrlər",quality:"Keyfiyyət",audio:"Səs",default:"Defolt",speed:"Sürət",captions:"Qapalı altyazılar",playbackRate:"Oynatma sürəti",back:"Geri",off:"Söndürülmüş",auto:"Avtomatik",autoWithLabel:"Avtomatik ({label})",subtitles:"Altyazılar"}};const z=Object.freeze(Object.defineProperty({__proto__:null,default:C},Symbol.toStringTag,{value:"Module"}));var m={buttons:{play:"Възпроизвеждане",pause:"Пауза",replay:"Повторно възпроизвеждане",mute:"Спиране на звука",unmute:"Включване на звука"},seek:{forward:"Превъртане напред с {seconds} секунди",backward:"Превъртане назад с {seconds} секунди"},fullscreen:{enter:"Цял екран",exit:"Изход от цял екран"},captions:{enable:"Включване на надписите",disable:"Изключване на надписите"},pip:{enter:"Картина в картина",exit:"Изход от картина в картина"},live:{playing:"На живо",seekToEdge:"Към потока на живо",badge:"На живо"},cast:{start:"Стартиране на предаване",stop:"Спиране на предаването",connecting:"Свързване"},airplay:{start:"Стартиране на AirPlay",stop:"Спиране на AirPlay"},slider:{seek:"Превъртане"},time:{current:"Текущо време",duration:"Продължителност",remaining:"Оставащо време",elapsedSuffix:"{duration} изминало време",durationSuffix:"Продължителност {duration}",remainingSuffix:"Остават {duration}",showElapsed:"Показване на изминалото време, {duration}.",showDuration:"Показване на продължителността, {duration}.",showRemaining:"Показване на оставащото време, {duration}.",toggleElapsed:"Превключване между изминалото и оставащото време.",toggleDuration:"Превключване между продължителността и оставащото време.",position:"{current} от {duration}",unknown:"Медията не е заредена, времето е неизвестно."},playback:{rate:"Скорост на възпроизвеждане {rate}"},volume:{mutedValue:"{percent}, без звук",muted:"Без звук",label:"Сила на звука",value:"Сила на звука {value}"},status:{captionsOn:"Надписите са включени",captionsOff:"Надписите са изключени",paused:"На пауза",playing:"Възпроизвеждане",fullscreen:"Цял екран",pip:"Картина в картина",exitPip:"Режимът „картина в картина“ е изключен",seekedTo:"Преместено на {time}"},container:{label:"Медиен плейър"},errors:{aborted:"Спряхте възпроизвеждането на медията, преди то да завърши.",network:"Тази медия не можа да бъде заредена поради проблем с мрежата или сървъра.",decode:"Тази медия не можа да бъде възпроизведена. Възможно е да е повредена или браузърът ви да не поддържа формата ѝ.",source:"Тази медия не можа да бъде заредена. Възможно е да не е налична или браузърът ви да не поддържа формата ѝ.",encrypted:"Тази медия не можа да бъде възпроизведена, защото не можа да бъде дешифрирана.",unplayable:"Този медиен файл не се поддържа от плейъра.",title:"Нещо се обърка.",unexpected:"Възникна неочаквана грешка."},common:{empty:"",ok:"OK"},menu:{settings:"Настройки",quality:"Качество",audio:"Аудио",default:"По подразбиране",speed:"Скорост",captions:"Надписи",playbackRate:"Скорост на възпроизвеждане",back:"Назад",off:"Изкл.",auto:"Авто",autoWithLabel:"Авто ({label})",subtitles:"Субтитри"}};const j=Object.freeze(Object.defineProperty({__proto__:null,default:m},Symbol.toStringTag,{value:"Module"}));var f={buttons:{play:"Воспроизвести",pause:"Приостановить",replay:"Воспроизвести снова",mute:"Отключить звук",unmute:"Включить звук"},seek:{forward:"Перемотать вперед на {seconds} с",backward:"Перемотать назад на {seconds} с"},fullscreen:{enter:"Полноэкранный режим",exit:"Выйти из полноэкранного режима"},captions:{enable:"Включить субтитры",disable:"Отключить субтитры"},pip:{enter:"Картинка в картинке",exit:"Закрыть картинку в картинке"},live:{playing:"Прямой эфир",seekToEdge:"Перейти к прямому эфиру",badge:"Прямой эфир"},cast:{start:"Начать трансляцию",stop:"Остановить трансляцию",connecting:"Подключение"},airplay:{start:"Запустить AirPlay",stop:"Остановить AirPlay"},slider:{seek:"Перемотка"},time:{current:"Текущее время",duration:"Продолжительность",remaining:"Оставшееся время",elapsedSuffix:"{duration} прошедшего времени",durationSuffix:"Продолжительность {duration}",remainingSuffix:"Осталось {duration}",showElapsed:"Показать прошедшее время, {duration}.",showDuration:"Показать продолжительность, {duration}.",showRemaining:"Показать оставшееся время, {duration}.",toggleElapsed:"Переключение между прошедшим и оставшимся временем.",toggleDuration:"Переключение между продолжительностью и оставшимся временем.",position:"{current} / {duration}",unknown:"Медиафайл не загружен, время неизвестно."},playback:{rate:"Скорость воспроизведения {rate}"},volume:{mutedValue:"{percent}, без звука",muted:"Без звука",label:"Громкость",value:"Громкость {value}"},status:{captionsOn:"Субтитры включены",captionsOff:"Субтитры выключены",paused:"На паузе",playing:"Воспроизведение",fullscreen:"Полноэкранный режим",pip:"Картинка в картинке",exitPip:"Режим «картинка в картинке» выключен",seekedTo:"Переход к отметке {time}"},container:{label:"Медиаплеер"},errors:{aborted:"Вы остановили воспроизведение медиафайла до его завершения.",network:"Не удалось загрузить этот медиафайл из-за проблемы с сетью или сервером.",decode:"Не удалось воспроизвести этот медиафайл. Возможно, он поврежден или ваш браузер не поддерживает его формат.",source:"Не удалось загрузить этот медиафайл. Возможно, он недоступен или ваш браузер не поддерживает его формат.",encrypted:"Не удалось воспроизвести этот медиафайл, так как его не удалось расшифровать.",unplayable:"Этот медиафайл не поддерживается плеером.",title:"Что-то пошло не так.",unexpected:"Произошла непредвиденная ошибка."},common:{empty:"",ok:"Закрыть"},menu:{settings:"Настройки",quality:"Качество",audio:"Аудио",default:"По умолчанию",speed:"Скорость",captions:"Субтитры",playbackRate:"Скорость воспроизведения",back:"Назад",off:"Выкл.",auto:"Авто",autoWithLabel:"Авто ({label})",subtitles:"Субтитры"}};const $=Object.freeze(Object.defineProperty({__proto__:null,default:f},Symbol.toStringTag,{value:"Module"}));var x={buttons:{play:"Spela upp",pause:"Pausa",replay:"Spela upp igen",mute:"Ljud av",unmute:"Ljud på"},seek:{forward:"Hoppa framåt {seconds} sekunder",backward:"Hoppa bakåt {seconds} sekunder"},fullscreen:{enter:"Fullskärm",exit:"Avsluta fullskärm"},captions:{enable:"Aktivera textning",disable:"Inaktivera textning"},pip:{enter:"Bild-i-bild",exit:"Avsluta bild-i-bild"},live:{playing:"Sänds live",seekToEdge:"Gå till live",badge:"Live"},cast:{start:"Börja casta",stop:"Sluta casta",connecting:"Ansluter"},airplay:{start:"Starta AirPlay",stop:"Stoppa AirPlay"},slider:{seek:"Spola"},time:{current:"Aktuell tid",duration:"Total tid",remaining:"Återstående tid",elapsedSuffix:"{duration} förfluten tid",durationSuffix:"{duration} total tid",remainingSuffix:"{duration} kvar",showElapsed:"Visa förfluten tid, {duration}.",showDuration:"Visa total tid, {duration}.",showRemaining:"Visa återstående tid, {duration}.",toggleElapsed:"Växla mellan förfluten och återstående tid.",toggleDuration:"Växla mellan total tid och återstående tid.",position:"{current} av {duration}",unknown:"Mediet laddades inte, okänd tid."},playback:{rate:"Uppspelningshastighet {rate}"},volume:{mutedValue:"{percent}, tystat",muted:"Tystat",label:"Volym",value:"Volym {value}"},status:{captionsOn:"Textning på",captionsOff:"Textning av",paused:"Pausad",playing:"Spelas upp",fullscreen:"Fullskärm",pip:"Bild-i-bild",exitPip:"Bild-i-bild avslutat",seekedTo:"Hoppade till {time}"},container:{label:"Mediaspelare"},errors:{aborted:"Du avbröt uppspelningen av mediet innan den var klar.",network:"Det gick inte att läsa in det här mediet på grund av ett nätverks- eller serverfel.",decode:"Det här mediet kunde inte spelas upp. Det kan vara skadat eller så stöder din webbläsare inte formatet.",source:"Det här mediet kunde inte läsas in. Det kan vara otillgängligt eller så stöder din webbläsare inte formatet.",encrypted:"Det här mediet kunde inte spelas upp eftersom det inte gick att dekryptera.",unplayable:"Det här mediet stöds inte av spelaren.",title:"Något gick fel.",unexpected:"Ett oväntat fel inträffade."},common:{empty:"",ok:"Stäng"},menu:{settings:"Inställningar",quality:"Kvalitet",audio:"Ljud",default:"Standard",speed:"Hastighet",captions:"Textning",playbackRate:"Uppspelningshastighet",back:"Tillbaka",off:"Av",auto:"Auto",autoWithLabel:"Auto ({label})",subtitles:"Undertexter"}};const L=Object.freeze(Object.defineProperty({__proto__:null,default:x},Symbol.toStringTag,{value:"Module"}));var B={buttons:{play:"播放",pause:"暫停",replay:"重播",mute:"靜音",unmute:"取消靜音"},seek:{forward:"快轉 {seconds} 秒",backward:"倒轉 {seconds} 秒"},fullscreen:{enter:"全螢幕",exit:"退出全螢幕"},captions:{enable:"開啟字幕",disable:"關閉字幕"},pip:{enter:"子母畫面",exit:"離開子母畫面"},live:{playing:"正在直播",seekToEdge:"跳轉至直播",badge:"直播"},cast:{start:"開始投放",stop:"停止投放",connecting:"連線中"},airplay:{start:"啟動 AirPlay",stop:"停止 AirPlay"},slider:{seek:"定位"},time:{current:"目前時間",duration:"總時長",remaining:"剩餘時間",elapsedSuffix:"已播放時間 {duration}",durationSuffix:"時長 {duration}",remainingSuffix:"剩餘 {duration}",showElapsed:"顯示已播放時間，{duration}。",showDuration:"顯示時長，{duration}。",showRemaining:"顯示剩餘時間，{duration}。",toggleElapsed:"在已播放時間和剩餘時間之間切換。",toggleDuration:"在時長和剩餘時間之間切換。",position:"{current}，總時長 {duration}",unknown:"媒體未載入，時間未知。"},playback:{rate:"播放速率 {rate}"},volume:{mutedValue:"{percent}，已靜音",muted:"已靜音",label:"音量",value:"音量 {value}"},status:{captionsOn:"字幕已開啟",captionsOff:"字幕已關閉",paused:"已暫停",playing:"正在播放",fullscreen:"全螢幕",pip:"子母畫面",exitPip:"離開子母畫面",seekedTo:"已跳轉至 {time}"},container:{label:"媒體播放器"},errors:{aborted:"您在媒體播放完成前停止了播放。",network:"由於網路或伺服器問題，無法載入此媒體。",decode:"無法播放此媒體。檔案可能已損毀，或您的瀏覽器可能不支援其格式。",source:"無法載入此媒體。媒體可能無法使用，或您的瀏覽器可能不支援其格式。",encrypted:"無法播放此媒體，因為無法解密。",unplayable:"播放器不支援此媒體。",title:"發生問題。",unexpected:"發生非預期的錯誤。"},common:{empty:"",ok:"關閉"},menu:{settings:"設定",quality:"畫質",audio:"音訊",default:"預設",speed:"速度",captions:"字幕",playbackRate:"播放速率",back:"返回",off:"關閉",auto:"自動",autoWithLabel:"自動（{label}）",subtitles:"字幕"}};const q=Object.freeze(Object.defineProperty({__proto__:null,default:B},Symbol.toStringTag,{value:"Module"}));export{h as F,d as a,o as b,_ as c,T as d,S as e,O as f,P as g,z as h,j as i,$ as j,w as r,L as s,q as z};
//# sourceMappingURL=zh-TW-CfJQ8G34.chunk.mjs.map
