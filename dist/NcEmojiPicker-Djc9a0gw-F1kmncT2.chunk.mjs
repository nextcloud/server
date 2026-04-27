const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { s as setCurrentSkinTone, g as getCurrentSkinTone, d as data } from "./emoji-BY_D0V5K-BlCul1cD.chunk.mjs";
import { o as openBlock, f as createElementBlock, F as Fragment, C as renderList, N as normalizeStyle, v as normalizeClass, g as createBaseVNode, c as createBlock, w as withCtx, t as toDisplayString, K as resolveDynamicComponent, h as createCommentVNode, r as resolveComponent, x as createVNode, E as withDirectives, ae as vModelText, V as withKeys, i as renderSlot, G as vShow, m as mergeProps, M as withModifiers, p as createSlots, I as normalizeProps, J as guardReactiveProps } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import { A as NcPopover, B as isFocusable, C as useTrapStackControl } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { r as register, p as t42, q as t37, s as t16, u as t5, b as t, _ as _export_sfc$1 } from "./Web-BOM4en5n.chunk.mjs";
import NcColorPicker from "./index-DD39fp6M.chunk.mjs";
import { C as Color } from "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { _ as _sfc_main$9 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
var NAMESPACE = "emoji-mart";
const _JSON = JSON;
var isLocalStorageSupported = typeof window !== "undefined" && "localStorage" in window;
let getter;
let setter;
function setHandlers(handlers) {
  handlers || (handlers = {});
  getter = handlers.getter;
  setter = handlers.setter;
}
function setNamespace(namespace) {
  NAMESPACE = namespace;
}
function update(state) {
  for (let key in state) {
    let value = state[key];
    set(key, value);
  }
}
function set(key, value) {
  if (setter) {
    setter(key, value);
  } else {
    if (!isLocalStorageSupported) return;
    try {
      window.localStorage[`${NAMESPACE}.${key}`] = _JSON.stringify(value);
    } catch (e) {
    }
  }
}
function get$1(key) {
  if (getter) {
    return getter(key);
  } else {
    if (!isLocalStorageSupported) return;
    try {
      var value = window.localStorage[`${NAMESPACE}.${key}`];
    } catch (e) {
      return;
    }
    if (value) {
      return JSON.parse(value);
    }
  }
}
const store = { update, set, get: get$1, setNamespace, setHandlers };
const mapping = {
  name: "a",
  unified: "b",
  non_qualified: "c",
  has_img_apple: "d",
  has_img_google: "e",
  has_img_twitter: "f",
  has_img_facebook: "h",
  keywords: "j",
  sheet: "k",
  emoticons: "l",
  text: "m",
  short_names: "n",
  added_in: "o"
};
const buildSearch = (emoji) => {
  const search = [];
  var addToSearch = (strings, split) => {
    if (!strings) {
      return;
    }
    (Array.isArray(strings) ? strings : [strings]).forEach((string) => {
      (split ? string.split(/[-|_|\s]+/) : [string]).forEach((s) => {
        s = s.toLowerCase();
        if (search.indexOf(s) == -1) {
          search.push(s);
        }
      });
    });
  };
  addToSearch(emoji.short_names, true);
  addToSearch(emoji.name, true);
  addToSearch(emoji.keywords, false);
  addToSearch(emoji.emoticons, false);
  return search.join(",");
};
function deepFreeze(object) {
  var propNames = Object.getOwnPropertyNames(object);
  for (let name of propNames) {
    let value = object[name];
    object[name] = value && typeof value === "object" ? deepFreeze(value) : value;
  }
  return Object.freeze(object);
}
const uncompress = (data2) => {
  if (!data2.compressed) {
    return data2;
  }
  data2.compressed = false;
  for (let id in data2.emojis) {
    let emoji = data2.emojis[id];
    for (let key in mapping) {
      emoji[key] = emoji[mapping[key]];
      delete emoji[mapping[key]];
    }
    if (!emoji.short_names) emoji.short_names = [];
    emoji.short_names.unshift(id);
    emoji.sheet_x = emoji.sheet[0];
    emoji.sheet_y = emoji.sheet[1];
    delete emoji.sheet;
    if (!emoji.text) emoji.text = "";
    if (!emoji.added_in) emoji.added_in = 6;
    emoji.added_in = emoji.added_in.toFixed(1);
    emoji.search = buildSearch(emoji);
  }
  data2 = deepFreeze(data2);
  return data2;
};
const DEFAULTS = [
  "+1",
  "grinning",
  "kissing_heart",
  "heart_eyes",
  "laughing",
  "stuck_out_tongue_winking_eye",
  "sweat_smile",
  "joy",
  "scream",
  "disappointed",
  "unamused",
  "weary",
  "sob",
  "sunglasses",
  "heart",
  "hankey"
];
let frequently, initialized;
let defaults = {};
function init() {
  initialized = true;
  frequently = store.get("frequently");
}
function add(emoji) {
  if (!initialized) init();
  var { id } = emoji;
  frequently || (frequently = defaults);
  frequently[id] || (frequently[id] = 0);
  frequently[id] += 1;
  store.set("last", id);
  store.set("frequently", frequently);
}
function get(maxNumber) {
  if (!initialized) init();
  if (!frequently) {
    defaults = {};
    const result = [];
    let defaultLength = Math.min(maxNumber, DEFAULTS.length);
    for (let i = 0; i < defaultLength; i++) {
      defaults[DEFAULTS[i]] = parseInt((defaultLength - i) / 4, 10) + 1;
      result.push(DEFAULTS[i]);
    }
    return result;
  }
  const quantity = maxNumber;
  const frequentlyKeys = [];
  for (let key in frequently) {
    if (frequently.hasOwnProperty(key)) {
      frequentlyKeys.push(key);
    }
  }
  const sorted = frequentlyKeys.sort((a, b) => frequently[a] - frequently[b]).reverse();
  const sliced = sorted.slice(0, quantity);
  const last = store.get("last");
  if (last && sliced.indexOf(last) == -1) {
    sliced.pop();
    sliced.push(last);
  }
  return sliced;
}
const frequently$1 = { add, get };
const SVGs = {
  activity: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M12 0C5.373 0 0 5.372 0 12c0 6.627 5.373 12 12 12 6.628 0 12-5.373 12-12 0-6.628-5.372-12-12-12m9.949 11H17.05c.224-2.527 1.232-4.773 1.968-6.113A9.966 9.966 0 0 1 21.949 11M13 11V2.051a9.945 9.945 0 0 1 4.432 1.564c-.858 1.491-2.156 4.22-2.392 7.385H13zm-2 0H8.961c-.238-3.165-1.536-5.894-2.393-7.385A9.95 9.95 0 0 1 11 2.051V11zm0 2v8.949a9.937 9.937 0 0 1-4.432-1.564c.857-1.492 2.155-4.221 2.393-7.385H11zm4.04 0c.236 3.164 1.534 5.893 2.392 7.385A9.92 9.92 0 0 1 13 21.949V13h2.04zM4.982 4.887C5.718 6.227 6.726 8.473 6.951 11h-4.9a9.977 9.977 0 0 1 2.931-6.113M2.051 13h4.9c-.226 2.527-1.233 4.771-1.969 6.113A9.972 9.972 0 0 1 2.051 13m16.967 6.113c-.735-1.342-1.744-3.586-1.968-6.113h4.899a9.961 9.961 0 0 1-2.931 6.113"/></svg>`,
  custom: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><g transform="translate(2.000000, 1.000000)"><rect id="Rectangle" x="8" y="0" width="3" height="21" rx="1.5"></rect><rect id="Rectangle" transform="translate(9.843, 10.549) rotate(60) translate(-9.843, -10.549) " x="8.343" y="0.049" width="3" height="21" rx="1.5"></rect><rect id="Rectangle" transform="translate(9.843, 10.549) rotate(-60) translate(-9.843, -10.549) " x="8.343" y="0.049" width="3" height="21" rx="1.5"></rect></g></svg>`,
  flags: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M0 0l6.084 24H8L1.916 0zM21 5h-4l-1-4H4l3 12h3l1 4h13L21 5zM6.563 3h7.875l2 8H8.563l-2-8zm8.832 10l-2.856 1.904L12.063 13h3.332zM19 13l-1.5-6h1.938l2 8H16l3-2z"/></svg>`,
  foods: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M17 4.978c-1.838 0-2.876.396-3.68.934.513-1.172 1.768-2.934 4.68-2.934a1 1 0 0 0 0-2c-2.921 0-4.629 1.365-5.547 2.512-.064.078-.119.162-.18.244C11.73 1.838 10.798.023 9.207.023 8.579.022 7.85.306 7 .978 5.027 2.54 5.329 3.902 6.492 4.999 3.609 5.222 0 7.352 0 12.969c0 4.582 4.961 11.009 9 11.009 1.975 0 2.371-.486 3-1 .629.514 1.025 1 3 1 4.039 0 9-6.418 9-11 0-5.953-4.055-8-7-8M8.242 2.546c.641-.508.943-.523.965-.523.426.169.975 1.405 1.357 3.055-1.527-.629-2.741-1.352-2.98-1.846.059-.112.241-.356.658-.686M15 21.978c-1.08 0-1.21-.109-1.559-.402l-.176-.146c-.367-.302-.816-.452-1.266-.452s-.898.15-1.266.452l-.176.146c-.347.292-.477.402-1.557.402-2.813 0-7-5.389-7-9.009 0-5.823 4.488-5.991 5-5.991 1.939 0 2.484.471 3.387 1.251l.323.276a1.995 1.995 0 0 0 2.58 0l.323-.276c.902-.78 1.447-1.251 3.387-1.251.512 0 5 .168 5 6 0 3.617-4.187 9-7 9"/></svg>`,
  nature: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M15.5 8a1.5 1.5 0 1 0 .001 3.001A1.5 1.5 0 0 0 15.5 8M8.5 8a1.5 1.5 0 1 0 .001 3.001A1.5 1.5 0 0 0 8.5 8"/><path d="M18.933 0h-.027c-.97 0-2.138.787-3.018 1.497-1.274-.374-2.612-.51-3.887-.51-1.285 0-2.616.133-3.874.517C7.245.79 6.069 0 5.093 0h-.027C3.352 0 .07 2.67.002 7.026c-.039 2.479.276 4.238 1.04 5.013.254.258.882.677 1.295.882.191 3.177.922 5.238 2.536 6.38.897.637 2.187.949 3.2 1.102C8.04 20.6 8 20.795 8 21c0 1.773 2.35 3 4 3 1.648 0 4-1.227 4-3 0-.201-.038-.393-.072-.586 2.573-.385 5.435-1.877 5.925-7.587.396-.22.887-.568 1.104-.788.763-.774 1.079-2.534 1.04-5.013C23.929 2.67 20.646 0 18.933 0M3.223 9.135c-.237.281-.837 1.155-.884 1.238-.15-.41-.368-1.349-.337-3.291.051-3.281 2.478-4.972 3.091-5.031.256.015.731.27 1.265.646-1.11 1.171-2.275 2.915-2.352 5.125-.133.546-.398.858-.783 1.313M12 22c-.901 0-1.954-.693-2-1 0-.654.475-1.236 1-1.602V20a1 1 0 1 0 2 0v-.602c.524.365 1 .947 1 1.602-.046.307-1.099 1-2 1m3-3.48v.02a4.752 4.752 0 0 0-1.262-1.02c1.092-.516 2.239-1.334 2.239-2.217 0-1.842-1.781-2.195-3.977-2.195-2.196 0-3.978.354-3.978 2.195 0 .883 1.148 1.701 2.238 2.217A4.8 4.8 0 0 0 9 18.539v-.025c-1-.076-2.182-.281-2.973-.842-1.301-.92-1.838-3.045-1.853-6.478l.023-.041c.496-.826 1.49-1.45 1.804-3.102 0-2.047 1.357-3.631 2.362-4.522C9.37 3.178 10.555 3 11.948 3c1.447 0 2.685.192 3.733.57 1 .9 2.316 2.465 2.316 4.48.313 1.651 1.307 2.275 1.803 3.102.035.058.068.117.102.178-.059 5.967-1.949 7.01-4.902 7.19m6.628-8.202c-.037-.065-.074-.13-.113-.195a7.587 7.587 0 0 0-.739-.987c-.385-.455-.648-.768-.782-1.313-.076-2.209-1.241-3.954-2.353-5.124.531-.376 1.004-.63 1.261-.647.636.071 3.044 1.764 3.096 5.031.027 1.81-.347 3.218-.37 3.235"/></svg>`,
  objects: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M12 0a9 9 0 0 0-5 16.482V21s2.035 3 5 3 5-3 5-3v-4.518A9 9 0 0 0 12 0zm0 2c3.86 0 7 3.141 7 7s-3.14 7-7 7-7-3.141-7-7 3.14-7 7-7zM9 17.477c.94.332 1.946.523 3 .523s2.06-.19 3-.523v.834c-.91.436-1.925.689-3 .689a6.924 6.924 0 0 1-3-.69v-.833zm.236 3.07A8.854 8.854 0 0 0 12 21c.965 0 1.888-.167 2.758-.451C14.155 21.173 13.153 22 12 22c-1.102 0-2.117-.789-2.764-1.453z"/><path d="M14.745 12.449h-.004c-.852-.024-1.188-.858-1.577-1.824-.421-1.061-.703-1.561-1.182-1.566h-.009c-.481 0-.783.497-1.235 1.537-.436.982-.801 1.811-1.636 1.791l-.276-.043c-.565-.171-.853-.691-1.284-1.794-.125-.313-.202-.632-.27-.913-.051-.213-.127-.53-.195-.634C7.067 9.004 7.039 9 6.99 9A1 1 0 0 1 7 7h.01c1.662.017 2.015 1.373 2.198 2.134.486-.981 1.304-2.058 2.797-2.075 1.531.018 2.28 1.153 2.731 2.141l.002-.008C14.944 8.424 15.327 7 16.979 7h.032A1 1 0 1 1 17 9h-.011c-.149.076-.256.474-.319.709a6.484 6.484 0 0 1-.311.951c-.429.973-.79 1.789-1.614 1.789"/></svg>`,
  smileys: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0m0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10"/><path d="M8 7a2 2 0 1 0-.001 3.999A2 2 0 0 0 8 7M16 7a2 2 0 1 0-.001 3.999A2 2 0 0 0 16 7M15.232 15c-.693 1.195-1.87 2-3.349 2-1.477 0-2.655-.805-3.347-2H15m3-2H6a6 6 0 1 0 12 0"/></svg>`,
  people: `<svg xmlns:svg="http://www.w3.org/2000/svg" height="24" width="24" viewBox="0 0 24 24"> <path id="path3814" d="m 3.3591089,21.17726 c 0.172036,0.09385 4.265994,2.29837 8.8144451,2.29837 4.927767,0 8.670894,-2.211883 8.82782,-2.306019 0.113079,-0.06785 0.182268,-0.190051 0.182267,-0.321923 0,-3.03119 -0.929494,-5.804936 -2.617196,-7.810712 -1.180603,-1.403134 -2.661918,-2.359516 -4.295699,-2.799791 4.699118,-2.236258 3.102306,-9.28617162 -2.097191,-9.28617162 -5.1994978,0 -6.7963103,7.04991362 -2.097192,9.28617162 -1.6337821,0.440275 -3.1150971,1.396798 -4.2956991,2.799791 -1.687703,2.005776 -2.617196,4.779522 -2.617196,7.810712 1.2e-6,0.137378 0.075039,0.263785 0.195641,0.329572 z M 8.0439319,5.8308783 C 8.0439309,2.151521 12.492107,0.30955811 15.093491,2.9109411 17.694874,5.5123241 15.852911,9.9605006 12.173554,9.9605 9.8938991,9.9579135 8.0465186,8.1105332 8.0439319,5.8308783 Z m -1.688782,7.6894977 c 1.524535,-1.811449 3.5906601,-2.809035 5.8184041,-2.809035 2.227744,0 4.293869,0.997586 5.818404,2.809035 1.533639,1.822571 2.395932,4.339858 2.439152,7.108301 -0.803352,0.434877 -4.141636,2.096112 -8.257556,2.096112 -3.8062921,0 -7.3910861,-1.671043 -8.2573681,-2.104981 0.04505,-2.765017 0.906968,-5.278785 2.438964,-7.099432 z" /> <path id="path3816" d="M 12.173828 0.38867188 C 9.3198513 0.38867187 7.3770988 2.3672285 6.8652344 4.6308594 C 6.4218608 6.5916015 7.1153562 8.7676117 8.9648438 10.126953 C 7.6141249 10.677376 6.3550511 11.480944 5.3496094 12.675781 C 3.5629317 14.799185 2.6015625 17.701475 2.6015625 20.847656 C 2.6015654 21.189861 2.7894276 21.508002 3.0898438 21.671875 C 3.3044068 21.788925 7.4436239 24.039062 12.173828 24.039062 C 17.269918 24.039062 21.083568 21.776786 21.291016 21.652344 C 21.57281 21.483266 21.746097 21.176282 21.746094 20.847656 C 21.746094 17.701475 20.78277 14.799185 18.996094 12.675781 C 17.990455 11.480591 16.733818 10.675362 15.382812 10.125 C 17.231132 8.7655552 17.925675 6.5910701 17.482422 4.6308594 C 16.970557 2.3672285 15.027805 0.38867188 12.173828 0.38867188 z M 12.792969 2.3007812 C 13.466253 2.4161792 14.125113 2.7383941 14.695312 3.3085938 C 15.835712 4.4489931 15.985604 5.9473549 15.46875 7.1953125 C 14.951896 8.4432701 13.786828 9.3984378 12.173828 9.3984375 C 10.197719 9.3961954 8.607711 7.806187 8.6054688 5.8300781 C 8.6054683 4.2170785 9.5606362 3.0520102 10.808594 2.5351562 C 11.432573 2.2767293 12.119685 2.1853833 12.792969 2.3007812 z M 12.173828 11.273438 C 14.233647 11.273438 16.133674 12.185084 17.5625 13.882812 C 18.93069 15.508765 19.698347 17.776969 19.808594 20.283203 C 18.807395 20.800235 15.886157 22.162109 12.173828 22.162109 C 8.7614632 22.162109 5.6245754 20.787069 4.5390625 20.265625 C 4.6525896 17.766717 5.4203315 15.504791 6.7851562 13.882812 C 8.2139827 12.185084 10.11401 11.273438 12.173828 11.273438 z " /> </svg>`,
  places: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M6.5 12C5.122 12 4 13.121 4 14.5S5.122 17 6.5 17 9 15.879 9 14.5 7.878 12 6.5 12m0 3c-.275 0-.5-.225-.5-.5s.225-.5.5-.5.5.225.5.5-.225.5-.5.5M17.5 12c-1.378 0-2.5 1.121-2.5 2.5s1.122 2.5 2.5 2.5 2.5-1.121 2.5-2.5-1.122-2.5-2.5-2.5m0 3c-.275 0-.5-.225-.5-.5s.225-.5.5-.5.5.225.5.5-.225.5-.5.5"/><path d="M22.482 9.494l-1.039-.346L21.4 9h.6c.552 0 1-.439 1-.992 0-.006-.003-.008-.003-.008H23c0-1-.889-2-1.984-2h-.642l-.731-1.717C19.262 3.012 18.091 2 16.764 2H7.236C5.909 2 4.738 3.012 4.357 4.283L3.626 6h-.642C1.889 6 1 7 1 8h.003S1 8.002 1 8.008C1 8.561 1.448 9 2 9h.6l-.043.148-1.039.346a2.001 2.001 0 0 0-1.359 2.097l.751 7.508a1 1 0 0 0 .994.901H3v1c0 1.103.896 2 2 2h2c1.104 0 2-.897 2-2v-1h6v1c0 1.103.896 2 2 2h2c1.104 0 2-.897 2-2v-1h1.096a.999.999 0 0 0 .994-.901l.751-7.508a2.001 2.001 0 0 0-1.359-2.097M6.273 4.857C6.402 4.43 6.788 4 7.236 4h9.527c.448 0 .834.43.963.857L19.313 9H4.688l1.585-4.143zM7 21H5v-1h2v1zm12 0h-2v-1h2v1zm2.189-3H2.811l-.662-6.607L3 11h18l.852.393L21.189 18z"/></svg>`,
  recent: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M13 4h-2l-.001 7H9v2h2v2h2v-2h4v-2h-4z"/><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0m0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10"/></svg>`,
  symbols: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M0 0h11v2H0zM4 11h3V6h4V4H0v2h4zM15.5 17c1.381 0 2.5-1.116 2.5-2.493s-1.119-2.493-2.5-2.493S13 13.13 13 14.507 14.119 17 15.5 17m0-2.986c.276 0 .5.222.5.493 0 .272-.224.493-.5.493s-.5-.221-.5-.493.224-.493.5-.493M21.5 19.014c-1.381 0-2.5 1.116-2.5 2.493S20.119 24 21.5 24s2.5-1.116 2.5-2.493-1.119-2.493-2.5-2.493m0 2.986a.497.497 0 0 1-.5-.493c0-.271.224-.493.5-.493s.5.222.5.493a.497.497 0 0 1-.5.493M22 13l-9 9 1.513 1.5 8.99-9.009zM17 11c2.209 0 4-1.119 4-2.5V2s.985-.161 1.498.949C23.01 4.055 23 6 23 6s1-1.119 1-3.135C24-.02 21 0 21 0h-2v6.347A5.853 5.853 0 0 0 17 6c-2.209 0-4 1.119-4 2.5s1.791 2.5 4 2.5M10.297 20.482l-1.475-1.585a47.54 47.54 0 0 1-1.442 1.129c-.307-.288-.989-1.016-2.045-2.183.902-.836 1.479-1.466 1.729-1.892s.376-.871.376-1.336c0-.592-.273-1.178-.818-1.759-.546-.581-1.329-.871-2.349-.871-1.008 0-1.79.293-2.344.879-.556.587-.832 1.181-.832 1.784 0 .813.419 1.748 1.256 2.805-.847.614-1.444 1.208-1.794 1.784a3.465 3.465 0 0 0-.523 1.833c0 .857.308 1.56.924 2.107.616.549 1.423.823 2.42.823 1.173 0 2.444-.379 3.813-1.137L8.235 24h2.819l-2.09-2.383 1.333-1.135zm-6.736-6.389a1.02 1.02 0 0 1 .73-.286c.31 0 .559.085.747.254a.849.849 0 0 1 .283.659c0 .518-.419 1.112-1.257 1.784-.536-.651-.805-1.231-.805-1.742a.901.901 0 0 1 .302-.669M3.74 22c-.427 0-.778-.116-1.057-.349-.279-.232-.418-.487-.418-.766 0-.594.509-1.288 1.527-2.083.968 1.134 1.717 1.946 2.248 2.438-.921.507-1.686.76-2.3.76"/></svg>`
};
const _sfc_main$8 = {
  props: {
    i18n: {
      type: Object,
      required: true
    },
    color: {
      type: String
    },
    categories: {
      type: Array,
      required: true
    },
    activeCategory: {
      type: Object,
      default() {
        return {};
      }
    }
  },
  emits: ["click"],
  created() {
    this.svgs = SVGs;
  }
};
const _hoisted_1$7 = {
  role: "tablist",
  class: "emoji-mart-anchors"
};
const _hoisted_2$6 = ["aria-label", "aria-selected", "data-title", "onClick"];
const _hoisted_3$5 = ["innerHTML"];
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$7, [
    (openBlock(true), createElementBlock(
      Fragment,
      null,
      renderList($props.categories, (category) => {
        return openBlock(), createElementBlock("button", {
          role: "tab",
          type: "button",
          "aria-label": category.name,
          "aria-selected": category.id == $props.activeCategory.id,
          key: category.id,
          class: normalizeClass({
            "emoji-mart-anchor": true,
            "emoji-mart-anchor-selected": category.id == $props.activeCategory.id
          }),
          style: normalizeStyle({ color: category.id == $props.activeCategory.id ? $props.color : "" }),
          "data-title": $props.i18n.categories[category.id],
          onClick: ($event) => _ctx.$emit("click", category)
        }, [
          createBaseVNode("div", {
            "aria-hidden": "true",
            innerHTML: _ctx.svgs[category.id]
          }, null, 8, _hoisted_3$5),
          createBaseVNode(
            "span",
            {
              "aria-hidden": "true",
              class: "emoji-mart-anchor-bar",
              style: normalizeStyle({ backgroundColor: $props.color })
            },
            null,
            4
            /* STYLE */
          )
        ], 14, _hoisted_2$6);
      }),
      128
      /* KEYED_FRAGMENT */
    ))
  ]);
}
const Anchors = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$8], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/emoji-mart-vue-fast/src/components/anchors.vue"]]);
const _String = String;
const stringFromCodePoint = _String.fromCodePoint || function stringFromCodePoint2() {
  var MAX_SIZE = 16384;
  var codeUnits = [];
  var highSurrogate;
  var lowSurrogate;
  var index = -1;
  var length = arguments.length;
  if (!length) {
    return "";
  }
  var result = "";
  while (++index < length) {
    var codePoint = Number(arguments[index]);
    if (!isFinite(codePoint) || // `NaN`, `+Infinity`, or `-Infinity`
    codePoint < 0 || // not a valid Unicode code point
    codePoint > 1114111 || // not a valid Unicode code point
    Math.floor(codePoint) != codePoint) {
      throw RangeError("Invalid code point: " + codePoint);
    }
    if (codePoint <= 65535) {
      codeUnits.push(codePoint);
    } else {
      codePoint -= 65536;
      highSurrogate = (codePoint >> 10) + 55296;
      lowSurrogate = codePoint % 1024 + 56320;
      codeUnits.push(highSurrogate, lowSurrogate);
    }
    if (index + 1 === length || codeUnits.length > MAX_SIZE) {
      result += String.fromCharCode.apply(null, codeUnits);
      codeUnits.length = 0;
    }
  }
  return result;
};
function unifiedToNative(unified) {
  var unicodes = unified.split("-"), codePoints = unicodes.map((u) => `0x${u}`);
  return stringFromCodePoint.apply(null, codePoints);
}
function uniq(arr) {
  return arr.reduce((acc, item) => {
    if (acc.indexOf(item) === -1) {
      acc.push(item);
    }
    return acc;
  }, []);
}
function intersect(a, b) {
  const uniqA = uniq(a);
  const uniqB = uniq(b);
  return uniqA.filter((item) => uniqB.indexOf(item) >= 0);
}
function deepMerge(a, b) {
  var o = {};
  for (let key in a) {
    let originalValue = a[key], value = originalValue;
    if (Object.prototype.hasOwnProperty.call(b, key)) {
      value = b[key];
    }
    if (typeof value === "object") {
      value = deepMerge(originalValue, value);
    }
    o[key] = value;
  }
  return o;
}
function measureScrollbar() {
  if (typeof document == "undefined") return 0;
  const div = document.createElement("div");
  div.style.width = "100px";
  div.style.height = "100px";
  div.style.overflow = "scroll";
  div.style.position = "absolute";
  div.style.top = "-9999px";
  document.body.appendChild(div);
  const scrollbarWidth = div.offsetWidth - div.clientWidth;
  document.body.removeChild(div);
  return scrollbarWidth;
}
const SHEET_COLUMNS = 61;
const COLONS_REGEX = /^(?:\:([^\:]+)\:)(?:\:skin-tone-(\d)\:)?$/;
const SKINS = ["1F3FA", "1F3FB", "1F3FC", "1F3FD", "1F3FE", "1F3FF"];
class EmojiIndex {
  /**
   * Constructor.
   *
   * @param {object} data - Raw json data, see the structure above.
   * @param {object} options - additional options, as an object:
   * @param {Function} emojisToShowFilter - optional, function to filter out
   *   some emojis, function(emoji) { return true|false }
   *   where `emoji` is an raw emoji object, see data.emojis above.
   * @param {Array} include - optional, a list of category ids to include.
   * @param {Array} exclude - optional, a list of category ids to exclude.
   * @param {Array} custom - optional, a list custom emojis, each emoji is
   *   an object, see data.emojis above for examples.
   */
  constructor(data2, {
    emojisToShowFilter,
    include,
    exclude,
    custom,
    recent,
    recentLength = 20
  } = {}) {
    this._data = uncompress(data2);
    this._emojisFilter = emojisToShowFilter || null;
    this._include = include || null;
    this._exclude = exclude || null;
    this._custom = custom || [];
    this._recent = recent || frequently$1.get(recentLength);
    this._emojis = {};
    this._nativeEmojis = {};
    this._emoticons = {};
    this._categories = [];
    this._recentCategory = { id: "recent", name: "Recent", emojis: [] };
    this._customCategory = { id: "custom", name: "Custom", emojis: [] };
    this._searchIndex = {};
    this.buildIndex();
    Object.freeze(this);
  }
  buildIndex() {
    let allCategories = this._data.categories;
    if (this._include) {
      allCategories = allCategories.filter((item) => {
        return this._include.includes(item.id);
      });
      allCategories = allCategories.sort((a, b) => {
        const indexA = this._include.indexOf(a.id);
        const indexB = this._include.indexOf(b.id);
        if (indexA < indexB) {
          return -1;
        }
        if (indexA > indexB) {
          return 1;
        }
        return 0;
      });
    }
    allCategories.forEach((categoryData) => {
      if (!this.isCategoryNeeded(categoryData.id)) {
        return;
      }
      let category = {
        id: categoryData.id,
        name: categoryData.name,
        emojis: []
      };
      categoryData.emojis.forEach((emojiId) => {
        let emoji = this.addEmoji(emojiId);
        if (emoji) {
          category.emojis.push(emoji);
        }
      });
      if (category.emojis.length) {
        this._categories.push(category);
      }
    });
    if (this.isCategoryNeeded("custom")) {
      if (this._custom.length > 0) {
        for (let customEmoji of this._custom) {
          this.addCustomEmoji(customEmoji);
        }
      }
      if (this._customCategory.emojis.length) {
        this._categories.push(this._customCategory);
      }
    }
    if (this.isCategoryNeeded("recent")) {
      if (this._recent.length) {
        this._recent.map((id) => {
          for (let customEmoji of this._customCategory.emojis) {
            if (customEmoji.id === id) {
              this._recentCategory.emojis.push(customEmoji);
              return;
            }
          }
          if (this.hasEmoji(id)) {
            this._recentCategory.emojis.push(this.emoji(id));
          }
          return;
        });
      }
      if (this._recentCategory.emojis.length) {
        this._categories.unshift(this._recentCategory);
      }
    }
  }
  /**
   * Find the emoji from the string
   */
  findEmoji(emoji, skin) {
    let matches = emoji.match(COLONS_REGEX);
    if (matches) {
      emoji = matches[1];
      if (matches[2]) {
        skin = parseInt(matches[2], 10);
      }
    }
    if (this._data.aliases.hasOwnProperty(emoji)) {
      emoji = this._data.aliases[emoji];
    }
    if (this._emojis.hasOwnProperty(emoji)) {
      let emojiObject = this._emojis[emoji];
      if (skin) {
        return emojiObject.getSkin(skin);
      }
      return emojiObject;
    }
    if (this._nativeEmojis.hasOwnProperty(emoji)) {
      return this._nativeEmojis[emoji];
    }
    return null;
  }
  categories() {
    return this._categories;
  }
  emoji(emojiId) {
    if (this._data.aliases.hasOwnProperty(emojiId)) {
      emojiId = this._data.aliases[emojiId];
    }
    let emoji = this._emojis[emojiId];
    if (!emoji) {
      throw new Error("Can not find emoji by id: " + emojiId);
    }
    return emoji;
  }
  firstEmoji() {
    let emoji = this._emojis[Object.keys(this._emojis)[0]];
    if (!emoji) {
      throw new Error("Can not get first emoji");
    }
    return emoji;
  }
  hasEmoji(emojiId) {
    if (this._data.aliases.hasOwnProperty(emojiId)) {
      emojiId = this._data.aliases[emojiId];
    }
    if (this._emojis[emojiId]) {
      return true;
    }
    return false;
  }
  nativeEmoji(unicodeEmoji) {
    if (this._nativeEmojis.hasOwnProperty(unicodeEmoji)) {
      return this._nativeEmojis[unicodeEmoji];
    }
    return null;
  }
  search(value, maxResults) {
    maxResults || (maxResults = 75);
    if (!value.length) {
      return null;
    }
    if (value == "-" || value == "-1") {
      return [this.emoji("-1")];
    }
    let values = value.toLowerCase().split(/[\s|,|\-|_]+/);
    let allResults = [];
    if (values.length > 2) {
      values = [values[0], values[1]];
    }
    allResults = values.map((value2) => {
      let emojis = this._emojis;
      let currentIndex = this._searchIndex;
      let length = 0;
      for (let charIndex = 0; charIndex < value2.length; charIndex++) {
        const char = value2[charIndex];
        length++;
        currentIndex[char] || (currentIndex[char] = {});
        currentIndex = currentIndex[char];
        if (!currentIndex.results) {
          let scores = {};
          currentIndex.results = [];
          currentIndex.emojis = {};
          for (let emojiId in emojis) {
            let emoji = emojis[emojiId];
            let search = emoji._data.search;
            let sub = value2.substr(0, length);
            let subIndex = search.indexOf(sub);
            if (subIndex != -1) {
              let score = subIndex + 1;
              if (sub == emojiId) score = 0;
              currentIndex.results.push(emoji);
              currentIndex.emojis[emojiId] = emoji;
              scores[emojiId] = score;
            }
          }
          currentIndex.results.sort((a, b) => {
            var aScore = scores[a.id], bScore = scores[b.id];
            return aScore - bScore;
          });
        }
        emojis = currentIndex.emojis;
      }
      return currentIndex.results;
    }).filter((a) => a);
    var results = null;
    if (allResults.length > 1) {
      results = intersect.apply(null, allResults);
    } else if (allResults.length) {
      results = allResults[0];
    } else {
      results = [];
    }
    if (results && results.length > maxResults) {
      results = results.slice(0, maxResults);
    }
    return results;
  }
  addCustomEmoji(customEmoji) {
    let emojiData = Object.assign({}, customEmoji, {
      id: customEmoji.short_names[0],
      custom: true
    });
    if (!emojiData.search) {
      emojiData.search = buildSearch(emojiData);
    }
    let emoji = new EmojiData(emojiData);
    this._emojis[emoji.id] = emoji;
    this._customCategory.emojis.push(emoji);
    return emoji;
  }
  addEmoji(emojiId) {
    let data2 = this._data.emojis[emojiId];
    if (!this.isEmojiNeeded(data2)) {
      return false;
    }
    let emoji = new EmojiData(data2);
    this._emojis[emojiId] = emoji;
    if (emoji.native) {
      this._nativeEmojis[emoji.native] = emoji;
    }
    if (emoji._skins) {
      for (let idx in emoji._skins) {
        let skin = emoji._skins[idx];
        if (skin.native) {
          this._nativeEmojis[skin.native] = skin;
        }
      }
    }
    if (emoji.emoticons) {
      emoji.emoticons.forEach((emoticon) => {
        if (this._emoticons[emoticon]) {
          return;
        }
        this._emoticons[emoticon] = emojiId;
      });
    }
    return emoji;
  }
  /**
   * Check if we need to include given category.
   *
   * @param {string} category_id - The category id.
   * @return {boolean} - Whether to include the emoji.
   */
  isCategoryNeeded(category_id) {
    let isIncluded = this._include && this._include.length ? this._include.indexOf(category_id) > -1 : true;
    let isExcluded = this._exclude && this._exclude.length ? this._exclude.indexOf(category_id) > -1 : false;
    if (!isIncluded || isExcluded) {
      return false;
    }
    return true;
  }
  /**
   * Check if we need to include given emoji.
   *
   * @param {object} emoji - The raw emoji object.
   * @return {boolean} - Whether to include the emoji.
   */
  isEmojiNeeded(emoji) {
    if (this._emojisFilter) {
      return this._emojisFilter(emoji);
    }
    return true;
  }
}
class EmojiData {
  constructor(data2) {
    this._data = Object.assign({}, data2);
    this._skins = null;
    if (this._data.skin_variations) {
      this._skins = [];
      for (var skinIdx in SKINS) {
        let skinKey = SKINS[skinIdx];
        let variationData = this._data.skin_variations[skinKey];
        let skinData = Object.assign({}, data2);
        for (let k in variationData) {
          skinData[k] = variationData[k];
        }
        delete skinData.skin_variations;
        skinData["skin_tone"] = parseInt(skinIdx) + 1;
        this._skins.push(new EmojiData(skinData));
      }
    }
    this._sanitized = sanitize(this._data);
    for (let key in this._sanitized) {
      this[key] = this._sanitized[key];
    }
    this.short_names = this._data.short_names;
    this.short_name = this._data.short_names[0];
    Object.freeze(this);
  }
  getSkin(skinIdx) {
    if (skinIdx && skinIdx != "native" && this._skins) {
      return this._skins[skinIdx - 1];
    }
    return this;
  }
  getPosition() {
    let adjustedColumns = SHEET_COLUMNS - 1, x = +(100 / adjustedColumns * this._data.sheet_x).toFixed(2), y = +(100 / adjustedColumns * this._data.sheet_y).toFixed(2);
    return `${x}% ${y}%`;
  }
  ariaLabel() {
    return [this.native].concat(this.short_names).filter(Boolean).join(", ");
  }
}
class EmojiView {
  /**
   * emoji - Emoji to display
   * set - string, emoji set name
   * native - boolean, whether to render native emoji
   * fallback - fallback function to render missing emoji, optional
   * emojiTooltip - wether we need to show the emoji tooltip, optional
   * emojiSize - emoji size in pixels, optional
   */
  constructor(emoji, skin, set2, native, fallback, emojiTooltip, emojiSize) {
    this._emoji = emoji;
    this._native = native;
    this._skin = skin;
    this._set = set2;
    this._fallback = fallback;
    this.canRender = this._canRender();
    this.cssClass = this._cssClass();
    this.cssStyle = this._cssStyle(emojiSize);
    this.content = this._content();
    this.title = emojiTooltip === true ? emoji.short_name : null;
    this.ariaLabel = emoji.ariaLabel();
    Object.freeze(this);
  }
  getEmoji() {
    return this._emoji.getSkin(this._skin);
  }
  _canRender() {
    return this._isCustom() || this._isNative() || this._hasEmoji() || this._fallback;
  }
  _cssClass() {
    return ["emoji-set-" + this._set, "emoji-type-" + this._emojiType()];
  }
  _cssStyle(emojiSize) {
    let cssStyle = {};
    if (this._isCustom()) {
      cssStyle = {
        backgroundImage: "url(" + this.getEmoji()._data.imageUrl + ")",
        backgroundSize: "100%",
        width: emojiSize + "px",
        height: emojiSize + "px"
      };
    } else if (this._hasEmoji() && !this._isNative()) {
      cssStyle = {
        backgroundPosition: this.getEmoji().getPosition()
      };
    }
    if (emojiSize) {
      if (this._isNative()) {
        cssStyle = Object.assign(cssStyle, {
          // font-size is used for native emoji which we need
          // to scale with 0.95 factor to have them look approximately
          // the same size as image-based emoji.
          fontSize: Math.round(emojiSize * 0.95 * 10) / 10 + "px"
        });
      } else {
        cssStyle = Object.assign(cssStyle, {
          width: emojiSize + "px",
          height: emojiSize + "px"
        });
      }
    }
    return cssStyle;
  }
  _content() {
    if (this._isCustom()) {
      return "";
    }
    if (this._isNative()) {
      return this.getEmoji().native;
    }
    if (this._hasEmoji()) {
      return "";
    }
    return this._fallback ? this._fallback(this.getEmoji()) : null;
  }
  _isNative() {
    return this._native;
  }
  _isCustom() {
    return this.getEmoji().custom;
  }
  _hasEmoji() {
    if (!this.getEmoji()._data) {
      return false;
    }
    const hasImage = this.getEmoji()._data["has_img_" + this._set];
    if (hasImage === void 0) {
      return true;
    }
    return hasImage;
  }
  _emojiType() {
    if (this._isCustom()) {
      return "custom";
    }
    if (this._isNative()) {
      return "native";
    }
    if (this._hasEmoji()) {
      return "image";
    }
    return "fallback";
  }
}
function sanitize(emoji) {
  var {
    name,
    short_names,
    skin_tone,
    skin_variations,
    emoticons,
    unified,
    custom,
    imageUrl
  } = emoji, id = emoji.id || short_names[0], colons = `:${id}:`;
  if (custom) {
    return {
      id,
      name,
      colons,
      emoticons,
      custom,
      imageUrl
    };
  }
  if (skin_tone) {
    colons += `:skin-tone-${skin_tone}:`;
  }
  return {
    id,
    name,
    colons,
    emoticons,
    unified: unified.toLowerCase(),
    skin: skin_tone || (skin_variations ? 1 : null),
    native: unifiedToNative(unified)
  };
}
const EmojiProps = {
  native: {
    type: Boolean,
    default: false
  },
  tooltip: {
    type: Boolean,
    default: false
  },
  fallback: {
    type: Function
  },
  skin: {
    type: Number,
    default: 1
  },
  set: {
    type: String,
    default: "apple"
  },
  emoji: {
    type: [String, Object],
    required: true
  },
  size: {
    type: Number,
    default: null
  },
  tag: {
    type: String,
    default: "span"
  }
};
const PickerProps = {
  perLine: {
    type: Number,
    default: 9
  },
  maxSearchResults: {
    type: Number,
    default: 75
  },
  emojiSize: {
    type: Number,
    default: 24
  },
  title: {
    type: String,
    default: "Emoji Mart™"
  },
  emoji: {
    type: String,
    default: "department_store"
  },
  color: {
    type: String,
    default: "#ae65c5"
  },
  set: {
    type: String,
    default: "apple"
  },
  skin: {
    type: Number,
    default: null
  },
  defaultSkin: {
    type: Number,
    default: 1
  },
  native: {
    type: Boolean,
    default: false
  },
  emojiTooltip: {
    type: Boolean,
    default: false
  },
  autoFocus: {
    type: Boolean,
    default: false
  },
  i18n: {
    type: Object,
    default() {
      return {};
    }
  },
  showPreview: {
    type: Boolean,
    default: true
  },
  showSearch: {
    type: Boolean,
    default: true
  },
  showCategories: {
    type: Boolean,
    default: true
  },
  showSkinTones: {
    type: Boolean,
    default: true
  },
  infiniteScroll: {
    type: Boolean,
    default: true
  },
  pickerStyles: {
    type: Object,
    default() {
      return {};
    }
  }
};
const _sfc_main$7 = {
  props: {
    ...EmojiProps,
    data: {
      type: Object,
      required: true
    }
  },
  emits: ["click", "mouseenter", "mouseleave"],
  computed: {
    view() {
      return new EmojiView(
        this.emojiObject,
        this.skin,
        this.set,
        this.native,
        this.fallback,
        this.tooltip,
        this.size
      );
    },
    sanitizedData() {
      return this.emojiObject._sanitized;
    },
    title() {
      return this.tooltip ? this.emojiObject.short_name : null;
    },
    emojiObject() {
      if (typeof this.emoji == "string") {
        return this.data.findEmoji(this.emoji);
      } else {
        return this.emoji;
      }
    }
  },
  created() {
  },
  methods: {
    onClick() {
      this.$emit("click", this.emojiObject);
    },
    onMouseEnter() {
      this.$emit("mouseenter", this.emojiObject);
    },
    onMouseLeave() {
      this.$emit("mouseleave", this.emojiObject);
    }
  }
};
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return $options.view.canRender ? (openBlock(), createBlock(resolveDynamicComponent(_ctx.tag), {
    key: 0,
    title: $options.view.title,
    "aria-label": $options.view.ariaLabel,
    "data-title": $options.title,
    class: "emoji-mart-emoji",
    onMouseenter: $options.onMouseEnter,
    onMouseleave: $options.onMouseLeave,
    onClick: $options.onClick
  }, {
    default: withCtx(() => [
      createBaseVNode(
        "span",
        {
          class: normalizeClass($options.view.cssClass),
          style: normalizeStyle($options.view.cssStyle)
        },
        toDisplayString($options.view.content),
        7
        /* TEXT, CLASS, STYLE */
      )
    ]),
    _: 1
    /* STABLE */
  }, 40, ["title", "aria-label", "data-title", "onMouseenter", "onMouseleave", "onClick"])) : createCommentVNode("v-if", true);
}
const Emoji = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/emoji-mart-vue-fast/src/components/Emoji.vue"]]);
const _sfc_main$6 = {
  props: {
    data: {
      type: Object,
      required: true
    },
    i18n: {
      type: Object,
      required: true
    },
    id: {
      type: String,
      required: true
    },
    name: {
      type: String,
      required: true
    },
    emojis: {
      type: Array
    },
    emojiProps: {
      type: Object,
      required: true
    }
  },
  methods: {
    activeClass: function(emojiObject) {
      if (!this.emojiProps.selectedEmoji) {
        return "";
      }
      if (!this.emojiProps.selectedEmojiCategory) {
        return "";
      }
      if (this.emojiProps.selectedEmoji.id == emojiObject.id && this.emojiProps.selectedEmojiCategory.id == this.id) {
        return "emoji-mart-emoji-selected";
      }
      return "";
    }
  },
  computed: {
    isVisible() {
      return !!this.emojis;
    },
    isSearch() {
      return this.name == "Search";
    },
    hasResults() {
      return this.emojis.length > 0;
    },
    emojiObjects() {
      return this.emojis.map((emoji) => {
        let emojiObject = emoji;
        let emojiView = new EmojiView(
          emoji,
          this.emojiProps.skin,
          this.emojiProps.set,
          this.emojiProps.native,
          this.emojiProps.fallback,
          this.emojiProps.emojiTooltip,
          this.emojiProps.emojiSize
        );
        return { emojiObject, emojiView };
      });
    }
  },
  components: {
    Emoji
  }
};
const _hoisted_1$6 = ["aria-label"];
const _hoisted_2$5 = { class: "emoji-mart-category-label" };
const _hoisted_3$4 = { class: "emoji-mart-category-label" };
const _hoisted_4$3 = ["aria-label", "data-title", "title", "onMouseenter", "onMouseleave", "onClick"];
const _hoisted_5$1 = { key: 0 };
const _hoisted_6$1 = { class: "emoji-mart-no-results-label" };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_emoji = resolveComponent("emoji");
  return $options.isVisible && ($options.isSearch || $options.hasResults) ? (openBlock(), createElementBlock("section", {
    key: 0,
    class: normalizeClass({
      "emoji-mart-category": true,
      "emoji-mart-no-results": !$options.hasResults
    }),
    "aria-label": $props.i18n.categories[$props.id]
  }, [
    createBaseVNode("div", _hoisted_2$5, [
      createBaseVNode(
        "h3",
        _hoisted_3$4,
        toDisplayString($props.i18n.categories[$props.id]),
        1
        /* TEXT */
      )
    ]),
    (openBlock(true), createElementBlock(
      Fragment,
      null,
      renderList($options.emojiObjects, ({ emojiObject, emojiView }) => {
        return openBlock(), createElementBlock(
          Fragment,
          null,
          [
            emojiView.canRender ? (openBlock(), createElementBlock("button", {
              "aria-label": emojiView.ariaLabel,
              role: "option",
              "aria-selected": "false",
              "aria-posinset": "1",
              "aria-setsize": "1812",
              type: "button",
              "data-title": emojiObject.short_name,
              key: emojiObject.id,
              title: emojiView.title,
              class: normalizeClass(["emoji-mart-emoji", $options.activeClass(emojiObject)]),
              onMouseenter: ($event) => $props.emojiProps.onEnter(emojiView.getEmoji()),
              onMouseleave: ($event) => $props.emojiProps.onLeave(emojiView.getEmoji()),
              onClick: ($event) => $props.emojiProps.onClick(emojiView.getEmoji())
            }, [
              createBaseVNode(
                "span",
                {
                  class: normalizeClass(emojiView.cssClass),
                  style: normalizeStyle(emojiView.cssStyle)
                },
                toDisplayString(emojiView.content),
                7
                /* TEXT, CLASS, STYLE */
              )
            ], 42, _hoisted_4$3)) : createCommentVNode("v-if", true)
          ],
          64
          /* STABLE_FRAGMENT */
        );
      }),
      256
      /* UNKEYED_FRAGMENT */
    )),
    !$options.hasResults ? (openBlock(), createElementBlock("div", _hoisted_5$1, [
      createVNode(_component_emoji, {
        data: $props.data,
        emoji: "sleuth_or_spy",
        native: $props.emojiProps.native,
        skin: $props.emojiProps.skin,
        set: $props.emojiProps.set
      }, null, 8, ["data", "native", "skin", "set"]),
      createBaseVNode(
        "div",
        _hoisted_6$1,
        toDisplayString($props.i18n.notfound),
        1
        /* TEXT */
      )
    ])) : createCommentVNode("v-if", true)
  ], 10, _hoisted_1$6)) : createCommentVNode("v-if", true);
}
const Category = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/emoji-mart-vue-fast/src/components/category.vue"]]);
const _sfc_main$5 = {
  props: {
    skin: {
      type: Number,
      required: true
    }
  },
  emits: ["change"],
  data() {
    return {
      opened: false
    };
  },
  methods: {
    onClick(skinTone) {
      if (this.opened) {
        if (skinTone != this.skin) {
          this.$emit("change", skinTone);
        }
      }
      this.opened = !this.opened;
    }
  }
};
const _hoisted_1$5 = ["onClick"];
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass({ "emoji-mart-skin-swatches": true, "emoji-mart-skin-swatches-opened": $data.opened })
    },
    [
      (openBlock(), createElementBlock(
        Fragment,
        null,
        renderList(6, (skinTone) => {
          return createBaseVNode(
            "span",
            {
              key: skinTone,
              class: normalizeClass({ "emoji-mart-skin-swatch": true, "emoji-mart-skin-swatch-selected": $props.skin == skinTone })
            },
            [
              createBaseVNode("span", {
                class: normalizeClass("emoji-mart-skin emoji-mart-skin-tone-" + skinTone),
                onClick: ($event) => $options.onClick(skinTone)
              }, null, 10, _hoisted_1$5)
            ],
            2
            /* CLASS */
          );
        }),
        64
        /* STABLE_FRAGMENT */
      ))
    ],
    2
    /* CLASS */
  );
}
const Skins = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/emoji-mart-vue-fast/src/components/skins.vue"]]);
const _sfc_main$4 = {
  props: {
    data: {
      type: Object,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    emoji: {
      type: [String, Object]
    },
    idleEmoji: {
      type: [String, Object],
      required: true
    },
    showSkinTones: {
      type: Boolean,
      default: true
    },
    emojiProps: {
      type: Object,
      required: true
    },
    skinProps: {
      type: Object,
      required: true
    },
    onSkinChange: {
      type: Function,
      required: true
    }
  },
  computed: {
    emojiData() {
      if (this.emoji) {
        return this.emoji;
      } else {
        return {};
      }
    },
    emojiShortNames() {
      return this.emojiData.short_names;
    },
    emojiEmoticons() {
      return this.emojiData.emoticons;
    }
  },
  components: {
    Emoji,
    Skins
  }
};
const _hoisted_1$4 = { class: "emoji-mart-preview" };
const _hoisted_2$4 = { class: "emoji-mart-preview-emoji" };
const _hoisted_3$3 = { class: "emoji-mart-preview-data" };
const _hoisted_4$2 = { class: "emoji-mart-preview-name" };
const _hoisted_5 = { class: "emoji-mart-preview-shortnames" };
const _hoisted_6 = { class: "emoji-mart-preview-emoticons" };
const _hoisted_7 = { class: "emoji-mart-preview-emoji" };
const _hoisted_8 = { class: "emoji-mart-preview-data" };
const _hoisted_9 = { class: "emoji-mart-title-label" };
const _hoisted_10 = {
  key: 0,
  class: "emoji-mart-preview-skins"
};
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_emoji = resolveComponent("emoji");
  const _component_skins = resolveComponent("skins");
  return openBlock(), createElementBlock("div", _hoisted_1$4, [
    $props.emoji ? (openBlock(), createElementBlock(
      Fragment,
      { key: 0 },
      [
        createBaseVNode("div", _hoisted_2$4, [
          createVNode(_component_emoji, {
            data: $props.data,
            emoji: $props.emoji,
            native: $props.emojiProps.native,
            skin: $props.emojiProps.skin,
            set: $props.emojiProps.set
          }, null, 8, ["data", "emoji", "native", "skin", "set"])
        ]),
        createBaseVNode("div", _hoisted_3$3, [
          createBaseVNode(
            "div",
            _hoisted_4$2,
            toDisplayString($props.emoji.name),
            1
            /* TEXT */
          ),
          createBaseVNode("div", _hoisted_5, [
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($options.emojiShortNames, (shortName) => {
                return openBlock(), createElementBlock(
                  "span",
                  {
                    key: shortName,
                    class: "emoji-mart-preview-shortname"
                  },
                  ":" + toDisplayString(shortName) + ":",
                  1
                  /* TEXT */
                );
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ]),
          createBaseVNode("div", _hoisted_6, [
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($options.emojiEmoticons, (emoticon) => {
                return openBlock(), createElementBlock(
                  "span",
                  {
                    key: emoticon,
                    class: "emoji-mart-preview-emoticon"
                  },
                  toDisplayString(emoticon),
                  1
                  /* TEXT */
                );
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ])
        ])
      ],
      64
      /* STABLE_FRAGMENT */
    )) : (openBlock(), createElementBlock(
      Fragment,
      { key: 1 },
      [
        createBaseVNode("div", _hoisted_7, [
          createVNode(_component_emoji, {
            data: $props.data,
            emoji: $props.idleEmoji,
            native: $props.emojiProps.native,
            skin: $props.emojiProps.skin,
            set: $props.emojiProps.set
          }, null, 8, ["data", "emoji", "native", "skin", "set"])
        ]),
        createBaseVNode("div", _hoisted_8, [
          createBaseVNode(
            "span",
            _hoisted_9,
            toDisplayString($props.title),
            1
            /* TEXT */
          )
        ]),
        $props.showSkinTones ? (openBlock(), createElementBlock("div", _hoisted_10, [
          createVNode(_component_skins, {
            skin: $props.skinProps.skin,
            onChange: _cache[0] || (_cache[0] = ($event) => $props.onSkinChange($event))
          }, null, 8, ["skin"])
        ])) : createCommentVNode("v-if", true)
      ],
      64
      /* STABLE_FRAGMENT */
    ))
  ]);
}
const Preview = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/emoji-mart-vue-fast/src/components/preview.vue"]]);
const _sfc_main$3 = {
  props: {
    data: {
      type: Object,
      required: true
    },
    i18n: {
      type: Object,
      required: true
    },
    autoFocus: {
      type: Boolean,
      default: false
    },
    onSearch: {
      type: Function,
      required: true
    },
    onArrowLeft: {
      type: Function,
      required: false
    },
    onArrowRight: {
      type: Function,
      required: false
    },
    onArrowDown: {
      type: Function,
      required: false
    },
    onArrowUp: {
      type: Function,
      required: false
    },
    onEnter: {
      type: Function,
      required: false
    }
  },
  emits: ["search", "enter", "arrowUp", "arrowDown", "arrowRight", "arrowLeft"],
  data() {
    return {
      value: ""
    };
  },
  computed: {
    emojiIndex() {
      return this.data;
    }
  },
  watch: {
    value() {
      this.$emit("search", this.value);
    }
  },
  methods: {
    clear() {
      this.value = "";
    }
  },
  mounted() {
    let $input = this.$el.querySelector("input");
    if (this.autoFocus) {
      $input.focus();
    }
  }
};
const _hoisted_1$3 = { class: "emoji-mart-search" };
const _hoisted_2$3 = ["placeholder"];
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$3, [
    withDirectives(createBaseVNode("input", {
      type: "text",
      placeholder: $props.i18n.search,
      role: "textbox",
      "aria-autocomplete": "list",
      "aria-owns": "emoji-mart-list",
      "aria-label": "Search for an emoji",
      "aria-describedby": "emoji-mart-search-description",
      onKeydown: [
        _cache[0] || (_cache[0] = withKeys(($event) => _ctx.$emit("arrowLeft", $event), ["left"])),
        _cache[1] || (_cache[1] = withKeys(() => _ctx.$emit("arrowRight"), ["right"])),
        _cache[2] || (_cache[2] = withKeys(() => _ctx.$emit("arrowDown"), ["down"])),
        _cache[3] || (_cache[3] = withKeys(($event) => _ctx.$emit("arrowUp", $event), ["up"])),
        _cache[4] || (_cache[4] = withKeys(() => _ctx.$emit("enter"), ["enter"]))
      ],
      "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.value = $event)
    }, null, 40, _hoisted_2$3), [
      [vModelText, $data.value]
    ]),
    _cache[6] || (_cache[6] = createBaseVNode(
      "span",
      {
        class: "hidden",
        id: "emoji-picker-search-description"
      },
      "Use the left, right, up and down arrow keys to navigate the emoji search results.",
      -1
      /* CACHED */
    ))
  ]);
}
const Search = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/emoji-mart-vue-fast/src/components/search.vue"]]);
var isWindowAvailable = typeof window !== "undefined";
isWindowAvailable && (function() {
  var lastTime = 0;
  var vendors = ["ms", "moz", "webkit", "o"];
  for (var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x) {
    window.requestAnimationFrame = window[vendors[x] + "RequestAnimationFrame"];
    window.cancelAnimationFrame = window[vendors[x] + "CancelAnimationFrame"] || window[vendors[x] + "CancelRequestAnimationFrame"];
  }
  if (!window.requestAnimationFrame)
    window.requestAnimationFrame = function(callback, element) {
      var currTime = (/* @__PURE__ */ new Date()).getTime();
      var timeToCall = Math.max(0, 16 - (currTime - lastTime));
      var id = window.setTimeout(function() {
        callback(currTime + timeToCall);
      }, timeToCall);
      lastTime = currTime + timeToCall;
      return id;
    };
  if (!window.cancelAnimationFrame)
    window.cancelAnimationFrame = function(id) {
      clearTimeout(id);
    };
})();
class PickerView {
  constructor(pickerComponent) {
    this._vm = pickerComponent;
    this._data = pickerComponent.data;
    this._perLine = pickerComponent.perLine;
    this._categories = [];
    this._categories.push(...this._data.categories());
    this._categories = this._categories.filter((category) => {
      return category.emojis.length > 0;
    });
    this._categories[0].first = true;
    Object.freeze(this._categories);
    this.activeCategory = this._categories[0];
    this.searchEmojis = null;
    this.previewEmoji = null;
    this.previewEmojiCategoryIdx = 0;
    this.previewEmojiIdx = -1;
  }
  onScroll() {
    const scrollElement = this._vm.$refs.scroll;
    if (!scrollElement) {
      return;
    }
    const scrollTop = scrollElement.scrollTop;
    let activeCategory = this.filteredCategories[0];
    for (let i = 0, l = this.filteredCategories.length; i < l; i++) {
      let category = this.filteredCategories[i];
      let component = this._vm.getCategoryComponent(i);
      if (component && component.$el.offsetTop - 50 > scrollTop) {
        break;
      }
      activeCategory = category;
    }
    this.activeCategory = activeCategory;
  }
  get allCategories() {
    return this._categories;
  }
  get filteredCategories() {
    if (this.searchEmojis) {
      return [
        {
          id: "search",
          name: "Search",
          emojis: this.searchEmojis
        }
      ];
    }
    return this._categories.filter((category) => {
      let hasEmojis = category.emojis.length > 0;
      return hasEmojis;
    });
  }
  get previewEmojiCategory() {
    if (this.previewEmojiCategoryIdx >= 0) {
      return this.filteredCategories[this.previewEmojiCategoryIdx];
    }
    return null;
  }
  onAnchorClick(category) {
    if (this.searchEmojis) {
      return;
    }
    let i = this.filteredCategories.indexOf(category);
    let component = this._vm.getCategoryComponent(i);
    let scrollToComponent = () => {
      if (component) {
        let top = component.$el.offsetTop;
        if (category.first) {
          top = 0;
        }
        this._vm.$refs.scroll.scrollTop = top;
      }
    };
    if (this._vm.infiniteScroll) {
      scrollToComponent();
    } else {
      this.activeCategory = this.filteredCategories[i];
    }
  }
  onSearch(value) {
    let emojis = this._data.search(value, this.maxSearchResults);
    this.searchEmojis = emojis;
    this.previewEmojiCategoryIdx = 0;
    this.previewEmojiIdx = 0;
    this.updatePreviewEmoji();
  }
  onEmojiEnter(emoji) {
    this.previewEmoji = emoji;
    this.previewEmojiIdx = -1;
    this.previewEmojiCategoryIdx = -1;
  }
  onEmojiLeave(emoji) {
    this.previewEmoji = null;
  }
  onArrowLeft() {
    if (this.previewEmojiIdx > 0) {
      this.previewEmojiIdx -= 1;
    } else {
      this.previewEmojiCategoryIdx -= 1;
      if (this.previewEmojiCategoryIdx < 0) {
        this.previewEmojiCategoryIdx = 0;
      } else {
        this.previewEmojiIdx = this.filteredCategories[this.previewEmojiCategoryIdx].emojis.length - 1;
      }
    }
    this.updatePreviewEmoji();
  }
  onArrowRight() {
    if (this.previewEmojiIdx < this.emojisLength(this.previewEmojiCategoryIdx) - 1) {
      this.previewEmojiIdx += 1;
    } else {
      this.previewEmojiCategoryIdx += 1;
      if (this.previewEmojiCategoryIdx >= this.filteredCategories.length) {
        this.previewEmojiCategoryIdx = this.filteredCategories.length - 1;
      } else {
        this.previewEmojiIdx = 0;
      }
    }
    this.updatePreviewEmoji();
  }
  onArrowDown() {
    if (this.previewEmojiIdx == -1) {
      return this.onArrowRight();
    }
    const categoryLength = this.filteredCategories[this.previewEmojiCategoryIdx].emojis.length;
    let diff = this._perLine;
    if (this.previewEmojiIdx + diff > categoryLength) {
      diff = categoryLength % this._perLine;
    }
    for (let i = 0; i < diff; i++) {
      this.onArrowRight();
    }
    this.updatePreviewEmoji();
  }
  onArrowUp() {
    let diff = this._perLine;
    if (this.previewEmojiIdx - diff < 0) {
      if (this.previewEmojiCategoryIdx > 0) {
        const prevCategoryLastRowLength = this.filteredCategories[this.previewEmojiCategoryIdx - 1].emojis.length % this._perLine;
        diff = prevCategoryLastRowLength;
      } else {
        diff = 0;
      }
    }
    for (let i = 0; i < diff; i++) {
      this.onArrowLeft();
    }
    this.updatePreviewEmoji();
  }
  updatePreviewEmoji() {
    this.previewEmoji = this.filteredCategories[this.previewEmojiCategoryIdx].emojis[this.previewEmojiIdx];
    this._vm.$nextTick(() => {
      const scrollEl = this._vm.$refs.scroll;
      const emojiEl = scrollEl.querySelector(".emoji-mart-emoji-selected");
      const scrollHeight = scrollEl.offsetTop - scrollEl.offsetHeight;
      if (emojiEl && emojiEl.offsetTop + emojiEl.offsetHeight > scrollHeight + scrollEl.scrollTop) {
        scrollEl.scrollTop += emojiEl.offsetHeight;
      }
      if (emojiEl && emojiEl.offsetTop < scrollEl.scrollTop) {
        scrollEl.scrollTop -= emojiEl.offsetHeight;
      }
    });
  }
  emojisLength(categoryIdx) {
    if (categoryIdx == -1) {
      return 0;
    }
    return this.filteredCategories[categoryIdx].emojis.length;
  }
}
const I18N = {
  search: "Search",
  notfound: "No Emoji Found",
  categories: {
    search: "Search Results",
    recent: "Frequently Used",
    smileys: "Smileys & Emotion",
    people: "People & Body",
    nature: "Animals & Nature",
    foods: "Food & Drink",
    activity: "Activity",
    places: "Travel & Places",
    objects: "Objects",
    symbols: "Symbols",
    flags: "Flags",
    custom: "Custom"
  }
};
const _sfc_main$2 = {
  props: {
    ...PickerProps,
    data: {
      type: Object,
      required: true
    }
  },
  emits: ["select", "skin-change"],
  data() {
    return {
      activeSkin: this.skin || store.get("skin") || this.defaultSkin,
      view: new PickerView(this)
    };
  },
  computed: {
    customStyles() {
      return {
        width: this.calculateWidth + "px",
        ...this.pickerStyles
      };
    },
    emojiProps() {
      return {
        native: this.native,
        skin: this.activeSkin,
        set: this.set,
        emojiTooltip: this.emojiTooltip,
        emojiSize: this.emojiSize,
        selectedEmoji: this.view.previewEmoji,
        selectedEmojiCategory: this.view.previewEmojiCategory,
        onEnter: this.onEmojiEnter.bind(this),
        onLeave: this.onEmojiLeave.bind(this),
        onClick: this.onEmojiClick.bind(this)
      };
    },
    skinProps() {
      return {
        skin: this.activeSkin
      };
    },
    calculateWidth() {
      return this.perLine * (this.emojiSize + 12) + 12 + 2 + measureScrollbar();
    },
    // emojisPerRow() {
    //   const listEl = this.$refs.scrollContent
    //   const emojiEl = listEl.querySelector('.emoji-mart-emoji')
    //   return Math.floor(listEl.offsetWidth / emojiEl.offsetWidth)
    // },
    filteredCategories() {
      return this.view.filteredCategories;
    },
    mergedI18n() {
      return Object.freeze(deepMerge(I18N, this.i18n));
    },
    idleEmoji() {
      try {
        return this.data.emoji(this.emoji);
      } catch (e) {
        console.error(
          "Default preview emoji `" + this.emoji + "` is not available, check the Picker `emoji` property"
        );
        console.error(e);
        return this.data.firstEmoji();
      }
    },
    isSearching() {
      return this.view.searchEmojis != null;
    }
  },
  watch: {
    skin() {
      this.onSkinChange(this.skin);
    }
  },
  methods: {
    onScroll() {
      if (this.infiniteScroll && !this.waitingForPaint) {
        this.waitingForPaint = true;
        window.requestAnimationFrame(this.onScrollPaint.bind(this));
      }
    },
    onScrollPaint() {
      this.waitingForPaint = false;
      this.view.onScroll();
    },
    onAnchorClick(category) {
      this.view.onAnchorClick(category);
    },
    onSearch(value) {
      this.view.onSearch(value);
    },
    onEmojiEnter(emoji) {
      this.view.onEmojiEnter(emoji);
    },
    onEmojiLeave(emoji) {
      this.view.onEmojiLeave(emoji);
    },
    onArrowLeft($event) {
      const oldIdx = this.view.previewEmojiIdx;
      this.view.onArrowLeft();
      if ($event && this.view.previewEmojiIdx !== oldIdx) {
        $event.preventDefault();
      }
    },
    onArrowRight() {
      this.view.onArrowRight();
    },
    onArrowDown() {
      this.view.onArrowDown();
    },
    onArrowUp($event) {
      this.view.onArrowUp();
      $event.preventDefault();
    },
    onEnter() {
      if (!this.view.previewEmoji) {
        return;
      }
      this.$emit("select", this.view.previewEmoji);
      frequently$1.add(this.view.previewEmoji);
    },
    onEmojiClick(emoji) {
      this.$emit("select", emoji);
      frequently$1.add(emoji);
    },
    onTextSelect($event) {
      $event.stopPropagation();
    },
    onSkinChange(skin) {
      this.activeSkin = skin;
      store.update({ skin });
      this.$emit("skin-change", skin);
    },
    getCategoryComponent(index) {
      let component = this.$refs["categories_" + index];
      if (component && "0" in component) {
        return component["0"];
      }
      return component;
    }
  },
  components: {
    Anchors,
    Category,
    Preview,
    Search
  }
};
const _hoisted_1$2 = {
  key: 0,
  class: "emoji-mart-bar emoji-mart-bar-anchors"
};
const _hoisted_2$2 = {
  id: "emoji-mart-list",
  ref: "scrollContent",
  role: "listbox",
  "aria-expanded": "true"
};
const _hoisted_3$2 = {
  key: 0,
  class: "emoji-mart-bar emoji-mart-bar-preview"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_anchors = resolveComponent("anchors");
  const _component_search = resolveComponent("search");
  const _component_category = resolveComponent("category");
  const _component_preview = resolveComponent("preview");
  return openBlock(), createElementBlock(
    "section",
    {
      class: "emoji-mart emoji-mart-static",
      style: normalizeStyle($options.customStyles)
    },
    [
      _ctx.showCategories ? (openBlock(), createElementBlock("div", _hoisted_1$2, [
        createVNode(_component_anchors, {
          data: $props.data,
          i18n: $options.mergedI18n,
          color: _ctx.color,
          categories: $data.view.allCategories,
          "active-category": $data.view.activeCategory,
          onClick: $options.onAnchorClick
        }, null, 8, ["data", "i18n", "color", "categories", "active-category", "onClick"])
      ])) : createCommentVNode("v-if", true),
      renderSlot(_ctx.$slots, "searchTemplate", {
        data: $props.data,
        i18n: _ctx.i18n,
        autoFocus: _ctx.autoFocus,
        onSearch: $options.onSearch
      }, () => [
        _ctx.showSearch ? (openBlock(), createBlock(_component_search, {
          key: 0,
          ref: "search",
          data: $props.data,
          i18n: $options.mergedI18n,
          "auto-focus": _ctx.autoFocus,
          "on-search": $options.onSearch,
          onSearch: $options.onSearch,
          onArrowLeft: $options.onArrowLeft,
          onArrowRight: $options.onArrowRight,
          onArrowDown: $options.onArrowDown,
          onArrowUp: $options.onArrowUp,
          onEnter: $options.onEnter,
          onSelect: $options.onTextSelect
        }, null, 8, ["data", "i18n", "auto-focus", "on-search", "onSearch", "onArrowLeft", "onArrowRight", "onArrowDown", "onArrowUp", "onEnter", "onSelect"])) : createCommentVNode("v-if", true)
      ]),
      createBaseVNode(
        "div",
        {
          role: "tabpanel",
          class: "emoji-mart-scroll",
          ref: "scroll",
          onScroll: _cache[0] || (_cache[0] = (...args) => $options.onScroll && $options.onScroll(...args))
        },
        [
          createBaseVNode(
            "div",
            _hoisted_2$2,
            [
              renderSlot(_ctx.$slots, "customCategory"),
              (openBlock(true), createElementBlock(
                Fragment,
                null,
                renderList($data.view.filteredCategories, (category, idx) => {
                  return withDirectives((openBlock(), createBlock(_component_category, {
                    ref_for: true,
                    ref: "categories_" + idx,
                    key: category.id,
                    data: $props.data,
                    i18n: $options.mergedI18n,
                    id: category.id,
                    name: category.name,
                    emojis: category.emojis,
                    "emoji-props": $options.emojiProps
                  }, null, 8, ["data", "i18n", "id", "name", "emojis", "emoji-props"])), [
                    [vShow, _ctx.infiniteScroll || category == $data.view.activeCategory || $options.isSearching]
                  ]);
                }),
                128
                /* KEYED_FRAGMENT */
              ))
            ],
            512
            /* NEED_PATCH */
          )
        ],
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ),
      renderSlot(_ctx.$slots, "previewTemplate", {
        data: $props.data,
        title: _ctx.title,
        emoji: $data.view.previewEmoji,
        idleEmoji: $options.idleEmoji,
        showSkinTones: _ctx.showSkinTones,
        emojiProps: $options.emojiProps,
        skinProps: $options.skinProps,
        onSkinChange: $options.onSkinChange
      }, () => [
        _ctx.showPreview ? (openBlock(), createElementBlock("div", _hoisted_3$2, [
          createVNode(_component_preview, {
            data: $props.data,
            title: _ctx.title,
            emoji: $data.view.previewEmoji,
            "idle-emoji": $options.idleEmoji,
            "show-skin-tones": _ctx.showSkinTones,
            "emoji-props": $options.emojiProps,
            "skin-props": $options.skinProps,
            "on-skin-change": $options.onSkinChange
          }, null, 8, ["data", "title", "emoji", "idle-emoji", "show-skin-tones", "emoji-props", "skin-props", "on-skin-change"])
        ])) : createCommentVNode("v-if", true)
      ])
    ],
    4
    /* STYLE */
  );
}
const Picker = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/emoji-mart-vue-fast/src/components/Picker.vue"]]);
const _sfc_main$1 = {
  name: "CircleIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$1 = ["aria-hidden", "aria-label"];
const _hoisted_2$1 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon circle-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$1, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$1, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$1))
  ], 16, _hoisted_1$1);
}
const IconCircle = /* @__PURE__ */ _export_sfc$1(_sfc_main$1, [["render", _sfc_render$1]]);
register(t5, t16, t37, t42);
let emojiIndex;
const i18n = {
  search: t("Search emoji"),
  notfound: t("No emoji found"),
  categories: {
    search: t("Search results"),
    recent: t("Frequently used"),
    smileys: t("Smileys & Emotion"),
    people: t("People & Body"),
    nature: t("Animals & Nature"),
    foods: t("Food & Drink"),
    activity: t("Activities"),
    places: t("Travel & Places"),
    objects: t("Objects"),
    symbols: t("Symbols"),
    flags: t("Flags"),
    custom: t("Custom")
  }
};
const skinTonePalette = [
  new Color(255, 222, 52, t("Neutral skin color")),
  new Color(228, 205, 166, t("Light skin tone")),
  new Color(250, 221, 192, t("Medium light skin tone")),
  new Color(174, 129, 87, t("Medium skin tone")),
  new Color(158, 113, 88, t("Medium dark skin tone")),
  new Color(96, 79, 69, t("Dark skin tone"))
];
const _sfc_main = {
  name: "NcEmojiPicker",
  components: {
    IconCircle,
    NcButton,
    NcColorPicker,
    NcPopover,
    NcTextField: _sfc_main$9,
    Emoji,
    Picker
  },
  props: {
    /**
     * The emoji-set
     */
    activeSet: {
      type: String,
      default: "native"
    },
    /**
     * Show preview section when hovering emoji
     */
    showPreview: {
      type: Boolean,
      default: false
    },
    /**
     * Allow unselecting the selected emoji
     */
    allowUnselect: {
      type: Boolean,
      default: false
    },
    /**
     * Selected emoji to allow unselecting
     */
    selectedEmoji: {
      type: String,
      default: ""
    },
    /**
     * The fallback emoji in the preview section
     */
    previewFallbackEmoji: {
      type: String,
      default: "grinning"
    },
    /**
     * The fallback text in the preview section
     */
    previewFallbackName: {
      type: String,
      default: t("Pick an emoji")
    },
    /**
     * Whether to close the emoji picker after picking one
     */
    closeOnSelect: {
      type: Boolean,
      default: true
    },
    /**
     * Selector for the popover container
     */
    container: {
      type: [Boolean, String, Object, Element],
      default: "body"
    }
  },
  emits: [
    "select",
    "selectData",
    "unselect"
  ],
  setup() {
    if (!emojiIndex) {
      emojiIndex = new EmojiIndex(data);
    }
    return {
      // Non-reactive constants
      emojiIndex,
      skinTonePalette,
      i18n
    };
  },
  data() {
    const currentSkinTone = getCurrentSkinTone();
    return {
      /**
       * The current active color from the skin tone palette
       */
      currentColor: skinTonePalette[currentSkinTone - 1],
      /**
       * The current active skin tone
       *
       * @type {1|2|3|4|5|6}
       */
      currentSkinTone,
      search: "",
      open: false
    };
  },
  computed: {
    native() {
      return this.activeSet === "native";
    }
  },
  created() {
    useTrapStackControl(() => this.open);
  },
  methods: {
    t,
    clearSearch() {
      this.search = "";
      this.$refs.search.focus();
    },
    /**
     * Update the current skin tone by the result of the color picker
     *
     * @param {string} color Color set
     */
    onChangeSkinTone(color) {
      const index = this.skinTonePalette.findIndex((tone) => tone.color.toLowerCase() === color.toLowerCase());
      if (index > -1) {
        this.currentSkinTone = index + 1;
        this.currentColor = this.skinTonePalette[index];
        setCurrentSkinTone(this.currentSkinTone);
      }
    },
    select(emojiObject) {
      this.$emit("select", emojiObject.native);
      this.$emit("selectData", emojiObject);
      if (this.closeOnSelect) {
        this.open = false;
      }
    },
    unselect() {
      this.$emit("unselect");
    },
    afterShow() {
      this.$refs.search.focus();
    },
    afterHide() {
      if (!document.activeElement || this.$refs.picker.$el.contains(document.activeElement) || !isFocusable(document.activeElement)) {
        this.$refs.popover.$el.querySelector('button, [role="button"]')?.focus();
      }
    },
    /**
     * Manually handle Tab navigation skipping emoji buttons.
     * Navigation over emojis is handled by Arrow keys.
     *
     * @param {KeyboardEvent} event - Keyboard event
     */
    handleTabNavigationSkippingEmojis(event) {
      const current = event.target;
      const focusable = Array.from(this.$refs.picker.$el.querySelectorAll("button:not(.emoji-mart-emoji), input"));
      if (!event.shiftKey) {
        const nextNode = focusable.find((node) => current.compareDocumentPosition(node) & Node.DOCUMENT_POSITION_FOLLOWING) || focusable[0];
        nextNode.focus();
      } else {
        const prevNode = focusable.findLast((node) => current.compareDocumentPosition(node) & Node.DOCUMENT_POSITION_PRECEDING) || focusable.at(-1);
        prevNode.focus();
      }
    },
    /**
     * Handle arrow navigation via <Picker>'s handlers with scroll bug fix
     *
     * @param {'onArrowLeft' | 'onArrowRight' | 'onArrowDown' | 'onArrowUp'} originalHandlerName - Picker's arrow keydown handler name
     * @param {KeyboardEvent} event - Keyboard event
     */
    async callPickerArrowHandlerWithScrollFix(originalHandlerName, event) {
      this.$refs.picker[originalHandlerName](event);
      await this.$nextTick();
      const selectedEmoji = this.$refs.picker.$el.querySelector(".emoji-mart-emoji-selected");
      selectedEmoji?.scrollIntoView({
        block: "center",
        inline: "center"
      });
    }
  }
};
const _hoisted_1 = { class: "nc-emoji-picker-container" };
const _hoisted_2 = { class: "search__wrapper" };
const _hoisted_3 = { class: "emoji-mart-category-label" };
const _hoisted_4 = { class: "emoji-mart-category-label" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcTextField = resolveComponent("NcTextField");
  const _component_IconCircle = resolveComponent("IconCircle");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_NcColorPicker = resolveComponent("NcColorPicker");
  const _component_Emoji = resolveComponent("Emoji");
  const _component_Picker = resolveComponent("Picker");
  const _component_NcPopover = resolveComponent("NcPopover");
  return openBlock(), createBlock(_component_NcPopover, {
    ref: "popover",
    shown: $data.open,
    "onUpdate:shown": _cache[6] || (_cache[6] = ($event) => $data.open = $event),
    container: $props.container,
    popupRole: "dialog",
    noFocusTrap: true,
    onAfterShow: $options.afterShow,
    onAfterHide: $options.afterHide
  }, {
    trigger: withCtx((slotProps) => [
      renderSlot(_ctx.$slots, "default", normalizeProps(guardReactiveProps(slotProps)), void 0, true)
    ]),
    default: withCtx(() => [
      createBaseVNode("div", _hoisted_1, [
        createVNode(_component_Picker, mergeProps({
          ref: "picker",
          color: "var(--color-primary-element)",
          data: $setup.emojiIndex,
          emoji: $props.previewFallbackEmoji,
          i18n: $setup.i18n,
          native: $options.native,
          emojiSize: 20,
          perLine: 8,
          pickerStyles: { width: "320px" },
          showPreview: $props.showPreview,
          skin: $data.currentSkinTone,
          showSkinTones: false,
          title: $props.previewFallbackName,
          role: "dialog",
          "aria-modal": "true",
          "aria-label": $options.t("Emoji picker")
        }, _ctx.$attrs, {
          onKeydown: withKeys(withModifiers($options.handleTabNavigationSkippingEmojis, ["prevent"]), ["tab"]),
          onSelect: $options.select
        }), createSlots({
          searchTemplate: withCtx(({ onSearch }) => [
            createBaseVNode("div", _hoisted_2, [
              createVNode(_component_NcTextField, {
                ref: "search",
                modelValue: $data.search,
                "onUpdate:modelValue": [
                  _cache[0] || (_cache[0] = ($event) => $data.search = $event),
                  ($event) => onSearch($data.search)
                ],
                class: "search",
                label: $options.t("Search"),
                labelVisible: true,
                placeholder: $setup.i18n.search,
                trailingButtonIcon: "close",
                trailingButtonLabel: $options.t("Clear search"),
                showTrailingButton: $data.search !== "",
                onKeydown: [
                  _cache[1] || (_cache[1] = withKeys(($event) => $options.callPickerArrowHandlerWithScrollFix("onArrowLeft", $event), ["left"])),
                  _cache[2] || (_cache[2] = withKeys(($event) => $options.callPickerArrowHandlerWithScrollFix("onArrowRight", $event), ["right"])),
                  _cache[3] || (_cache[3] = withKeys(($event) => $options.callPickerArrowHandlerWithScrollFix("onArrowDown", $event), ["down"])),
                  _cache[4] || (_cache[4] = withKeys(($event) => $options.callPickerArrowHandlerWithScrollFix("onArrowUp", $event), ["up"])),
                  _cache[5] || (_cache[5] = withKeys(($event) => _ctx.$refs.picker.onEnter($event), ["enter"]))
                ],
                onTrailingButtonClick: ($event) => {
                  $options.clearSearch();
                  onSearch("");
                }
              }, null, 8, ["modelValue", "label", "placeholder", "trailingButtonLabel", "showTrailingButton", "onTrailingButtonClick", "onUpdate:modelValue"]),
              createVNode(_component_NcColorPicker, {
                paletteOnly: "",
                container: $props.container,
                palette: $setup.skinTonePalette,
                modelValue: $data.currentColor.color,
                "onUpdate:modelValue": $options.onChangeSkinTone
              }, {
                default: withCtx(() => [
                  createVNode(_component_NcButton, {
                    "aria-label": $options.t("Skin tone"),
                    variant: "tertiary-no-background"
                  }, {
                    icon: withCtx(() => [
                      createVNode(_component_IconCircle, {
                        style: normalizeStyle({ color: $data.currentColor.color }),
                        title: $data.currentColor.name,
                        size: 20
                      }, null, 8, ["style", "title"])
                    ]),
                    _: 1
                  }, 8, ["aria-label"])
                ]),
                _: 1
              }, 8, ["container", "palette", "modelValue", "onUpdate:modelValue"])
            ])
          ]),
          _: 2
        }, [
          $props.allowUnselect && $props.selectedEmoji ? {
            name: "customCategory",
            fn: withCtx(() => [
              createBaseVNode("div", _hoisted_3, [
                createBaseVNode("h3", _hoisted_4, toDisplayString($options.t("Selected")), 1)
              ]),
              createVNode(_component_Emoji, {
                class: "emoji-selected",
                data: $setup.emojiIndex,
                emoji: $props.selectedEmoji,
                native: "",
                size: 32,
                onClick: $options.unselect
              }, null, 8, ["data", "emoji", "onClick"]),
              createVNode(_component_Emoji, {
                class: "emoji-delete",
                data: $setup.emojiIndex,
                emoji: ":x:",
                native: "",
                size: 10,
                onClick: $options.unselect
              }, null, 8, ["data", "onClick"])
            ]),
            key: "0"
          } : void 0
        ]), 1040, ["data", "emoji", "i18n", "native", "showPreview", "skin", "title", "aria-label", "onKeydown", "onSelect"])
      ])
    ]),
    _: 3
  }, 8, ["shown", "container", "onAfterShow", "onAfterHide"]);
}
const NcEmojiPicker = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-11acdb77"]]);
export {
  NcEmojiPicker as N
};
//# sourceMappingURL=NcEmojiPicker-Djc9a0gw-F1kmncT2.chunk.mjs.map
