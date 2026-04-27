const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { o as openBlock, f as createElementBlock, N as normalizeStyle, r as resolveComponent, g as createBaseVNode, x as createVNode, E as withDirectives, ae as vModelText, t as toDisplayString, v as normalizeClass, c as createBlock, h as createCommentVNode, G as vShow, b as defineComponent, k as useModel, n as computed, w as withCtx, u as unref, ad as Transition, F as Fragment, C as renderList, j as createTextVNode, i as renderSlot, I as normalizeProps, J as guardReactiveProps, q as mergeModels, y as ref } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { e as mdiCheck, h as mdiCloseCircleOutline, N as NcButton, j as mdiArrowLeft, k as mdiDotsHorizontal, l as logger } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { r as register, w as t1, _ as _export_sfc, c as createElementId, b as t, N as NcIconSvgWrapper } from "./Web-BOM4en5n.chunk.mjs";
import { a as COLOR_BLACK, b as COLOR_WHITE, d as defaultPalette } from "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import { A as NcPopover } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
import "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
const prefix = "";
function styleInject(css, ref2) {
  if (ref2 === void 0) ref2 = {};
  var insertAt = ref2.insertAt;
  if (!css || typeof document === "undefined") {
    return;
  }
  var head = document.head || document.getElementsByTagName("head")[0];
  var style = document.createElement("style");
  style.type = "text/css";
  if (insertAt === "top") {
    if (head.firstChild) {
      head.insertBefore(style, head.firstChild);
    } else {
      head.appendChild(style);
    }
  } else {
    head.appendChild(style);
  }
  if (style.styleSheet) {
    style.styleSheet.cssText = css;
  } else {
    style.appendChild(document.createTextNode(css));
  }
}
const install = function(app, options) {
  const { componentPrefix = prefix } = options || {};
  app.component(`${componentPrefix}${this.name}`, this);
};
const _checkboardCache = {};
var script$5 = {
  name: "Checkboard",
  props: {
    size: {
      type: [Number, String],
      default: 8
    },
    white: {
      type: String,
      default: "#fff"
    },
    grey: {
      type: String,
      default: "#e6e6e6"
    }
  },
  computed: {
    bgStyle() {
      return {
        "background-image": `url(${getCheckboard(this.white, this.grey, this.size)})`
      };
    }
  }
};
function renderCheckboard(c1, c2, size) {
  if (typeof document === "undefined")
    return null;
  const canvas = document.createElement("canvas");
  canvas.width = canvas.height = size * 2;
  const ctx = canvas.getContext("2d");
  if (!ctx)
    return null;
  ctx.fillStyle = c1;
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = c2;
  ctx.fillRect(0, 0, size, size);
  ctx.translate(size, size);
  ctx.fillRect(0, 0, size, size);
  return canvas.toDataURL();
}
function getCheckboard(c1, c2, size) {
  const key = `${c1},${c2},${size}`;
  if (_checkboardCache[key])
    return _checkboardCache[key];
  const checkboard = renderCheckboard(c1, c2, size);
  _checkboardCache[key] = checkboard;
  return checkboard;
}
function render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: "vc-checkerboard",
      style: normalizeStyle($options.bgStyle)
    },
    null,
    4
    /* STYLE */
  );
}
var css_248z$5 = ".vc-checkerboard{background-size:contain;bottom:0;left:0;position:absolute;right:0;top:0}";
styleInject(css_248z$5);
script$5.render = render$5;
script$5.__file = "src/components/checkboard/checkboard.vue";
script$5.install = install;
var script$4 = {
  name: "Alpha",
  components: {
    Checkboard: script$5
  },
  props: {
    value: Object,
    onChange: Function
  },
  computed: {
    colors() {
      return this.value;
    },
    gradientColor() {
      const { rgba } = this.colors;
      const rgbStr = [rgba.r, rgba.g, rgba.b].join(",");
      return `linear-gradient(to right, rgba(${rgbStr}, 0) 0%, rgba(${rgbStr}, 1) 100%)`;
    }
  },
  methods: {
    handleChange(e, skip) {
      !skip && e.preventDefault();
      const { container } = this.$refs;
      if (!container) {
        return;
      }
      const containerWidth = container.clientWidth;
      const xOffset = container.getBoundingClientRect().left + window.pageXOffset;
      const pageX = e.pageX || (e.touches ? e.touches[0].pageX : 0);
      const left = pageX - xOffset;
      let a;
      if (left < 0)
        a = 0;
      else if (left > containerWidth)
        a = 1;
      else
        a = Math.round(left * 100 / containerWidth) / 100;
      if (this.colors.a !== a) {
        this.$emit("change", {
          h: this.colors.hsl.h,
          s: this.colors.hsl.s,
          l: this.colors.hsl.l,
          a,
          source: "rgba"
        });
      }
    },
    handleMouseDown(e) {
      this.handleChange(e, true);
      window.addEventListener("mousemove", this.handleChange);
      window.addEventListener("mouseup", this.handleMouseUp);
    },
    handleMouseUp() {
      this.unbindEventListeners();
    },
    unbindEventListeners() {
      window.removeEventListener("mousemove", this.handleChange);
      window.removeEventListener("mouseup", this.handleMouseUp);
    }
  }
};
const _hoisted_1$5 = { class: "vc-alpha" };
const _hoisted_2$5 = { class: "vc-alpha-checkboard-wrap" };
const _hoisted_3$5 = /* @__PURE__ */ createBaseVNode(
  "div",
  { class: "vc-alpha-picker" },
  null,
  -1
  /* HOISTED */
);
const _hoisted_4$4 = [
  _hoisted_3$5
];
function render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Checkboard = resolveComponent("Checkboard");
  return openBlock(), createElementBlock("div", _hoisted_1$5, [
    createBaseVNode("div", _hoisted_2$5, [
      createVNode(_component_Checkboard)
    ]),
    createBaseVNode(
      "div",
      {
        class: "vc-alpha-gradient",
        style: normalizeStyle({ background: $options.gradientColor })
      },
      null,
      4
      /* STYLE */
    ),
    createBaseVNode(
      "div",
      {
        ref: "container",
        class: "vc-alpha-container",
        onMousedown: _cache[0] || (_cache[0] = (...args) => $options.handleMouseDown && $options.handleMouseDown(...args)),
        onTouchmove: _cache[1] || (_cache[1] = (...args) => $options.handleChange && $options.handleChange(...args)),
        onTouchstart: _cache[2] || (_cache[2] = (...args) => $options.handleChange && $options.handleChange(...args))
      },
      [
        createBaseVNode(
          "div",
          {
            class: "vc-alpha-pointer",
            style: normalizeStyle({ left: `${$options.colors.a * 100}%` })
          },
          _hoisted_4$4,
          4
          /* STYLE */
        )
      ],
      544
      /* HYDRATE_EVENTS, NEED_PATCH */
    )
  ]);
}
var css_248z$4 = ".vc-alpha,.vc-alpha-checkboard-wrap{bottom:0;left:0;position:absolute;right:0;top:0}.vc-alpha-checkboard-wrap{overflow:hidden}.vc-alpha-gradient{bottom:0;left:0;position:absolute;right:0;top:0}.vc-alpha-container{cursor:pointer;height:100%;margin:0 3px;position:relative;z-index:2}.vc-alpha-pointer{position:absolute;z-index:2}.vc-alpha-picker{background:#fff;border-radius:1px;box-shadow:0 0 2px rgba(0,0,0,.6);cursor:pointer;height:8px;margin-top:1px;transform:translateX(-2px);width:4px}";
styleInject(css_248z$4);
script$4.render = render$4;
script$4.__file = "src/components/alpha/alpha.vue";
script$4.install = install;
function bound01(n, max) {
  if (isOnePointZero(n)) {
    n = "100%";
  }
  var isPercent = isPercentage(n);
  n = max === 360 ? n : Math.min(max, Math.max(0, parseFloat(n)));
  if (isPercent) {
    n = parseInt(String(n * max), 10) / 100;
  }
  if (Math.abs(n - max) < 1e-6) {
    return 1;
  }
  if (max === 360) {
    n = (n < 0 ? n % max + max : n % max) / parseFloat(String(max));
  } else {
    n = n % max / parseFloat(String(max));
  }
  return n;
}
function clamp01(val) {
  return Math.min(1, Math.max(0, val));
}
function isOnePointZero(n) {
  return typeof n === "string" && n.indexOf(".") !== -1 && parseFloat(n) === 1;
}
function isPercentage(n) {
  return typeof n === "string" && n.indexOf("%") !== -1;
}
function boundAlpha(a) {
  a = parseFloat(a);
  if (isNaN(a) || a < 0 || a > 1) {
    a = 1;
  }
  return a;
}
function convertToPercentage(n) {
  if (n <= 1) {
    return "".concat(Number(n) * 100, "%");
  }
  return n;
}
function pad2(c) {
  return c.length === 1 ? "0" + c : String(c);
}
function rgbToRgb(r, g, b) {
  return {
    r: bound01(r, 255) * 255,
    g: bound01(g, 255) * 255,
    b: bound01(b, 255) * 255
  };
}
function rgbToHsl(r, g, b) {
  r = bound01(r, 255);
  g = bound01(g, 255);
  b = bound01(b, 255);
  var max = Math.max(r, g, b);
  var min = Math.min(r, g, b);
  var h = 0;
  var s = 0;
  var l = (max + min) / 2;
  if (max === min) {
    s = 0;
    h = 0;
  } else {
    var d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r:
        h = (g - b) / d + (g < b ? 6 : 0);
        break;
      case g:
        h = (b - r) / d + 2;
        break;
      case b:
        h = (r - g) / d + 4;
        break;
    }
    h /= 6;
  }
  return { h, s, l };
}
function hue2rgb(p, q, t2) {
  if (t2 < 0) {
    t2 += 1;
  }
  if (t2 > 1) {
    t2 -= 1;
  }
  if (t2 < 1 / 6) {
    return p + (q - p) * (6 * t2);
  }
  if (t2 < 1 / 2) {
    return q;
  }
  if (t2 < 2 / 3) {
    return p + (q - p) * (2 / 3 - t2) * 6;
  }
  return p;
}
function hslToRgb(h, s, l) {
  var r;
  var g;
  var b;
  h = bound01(h, 360);
  s = bound01(s, 100);
  l = bound01(l, 100);
  if (s === 0) {
    g = l;
    b = l;
    r = l;
  } else {
    var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    var p = 2 * l - q;
    r = hue2rgb(p, q, h + 1 / 3);
    g = hue2rgb(p, q, h);
    b = hue2rgb(p, q, h - 1 / 3);
  }
  return { r: r * 255, g: g * 255, b: b * 255 };
}
function rgbToHsv(r, g, b) {
  r = bound01(r, 255);
  g = bound01(g, 255);
  b = bound01(b, 255);
  var max = Math.max(r, g, b);
  var min = Math.min(r, g, b);
  var h = 0;
  var v = max;
  var d = max - min;
  var s = max === 0 ? 0 : d / max;
  if (max === min) {
    h = 0;
  } else {
    switch (max) {
      case r:
        h = (g - b) / d + (g < b ? 6 : 0);
        break;
      case g:
        h = (b - r) / d + 2;
        break;
      case b:
        h = (r - g) / d + 4;
        break;
    }
    h /= 6;
  }
  return { h, s, v };
}
function hsvToRgb(h, s, v) {
  h = bound01(h, 360) * 6;
  s = bound01(s, 100);
  v = bound01(v, 100);
  var i = Math.floor(h);
  var f = h - i;
  var p = v * (1 - s);
  var q = v * (1 - f * s);
  var t2 = v * (1 - (1 - f) * s);
  var mod = i % 6;
  var r = [v, q, p, p, t2, v][mod];
  var g = [t2, v, v, q, p, p][mod];
  var b = [p, p, t2, v, v, q][mod];
  return { r: r * 255, g: g * 255, b: b * 255 };
}
function rgbToHex(r, g, b, allow3Char) {
  var hex = [
    pad2(Math.round(r).toString(16)),
    pad2(Math.round(g).toString(16)),
    pad2(Math.round(b).toString(16))
  ];
  if (allow3Char && hex[0].startsWith(hex[0].charAt(1)) && hex[1].startsWith(hex[1].charAt(1)) && hex[2].startsWith(hex[2].charAt(1))) {
    return hex[0].charAt(0) + hex[1].charAt(0) + hex[2].charAt(0);
  }
  return hex.join("");
}
function rgbaToHex(r, g, b, a, allow4Char) {
  var hex = [
    pad2(Math.round(r).toString(16)),
    pad2(Math.round(g).toString(16)),
    pad2(Math.round(b).toString(16)),
    pad2(convertDecimalToHex(a))
  ];
  if (allow4Char && hex[0].startsWith(hex[0].charAt(1)) && hex[1].startsWith(hex[1].charAt(1)) && hex[2].startsWith(hex[2].charAt(1)) && hex[3].startsWith(hex[3].charAt(1))) {
    return hex[0].charAt(0) + hex[1].charAt(0) + hex[2].charAt(0) + hex[3].charAt(0);
  }
  return hex.join("");
}
function convertDecimalToHex(d) {
  return Math.round(parseFloat(d) * 255).toString(16);
}
function convertHexToDecimal(h) {
  return parseIntFromHex(h) / 255;
}
function parseIntFromHex(val) {
  return parseInt(val, 16);
}
function numberInputToObject(color) {
  return {
    r: color >> 16,
    g: (color & 65280) >> 8,
    b: color & 255
  };
}
var names = {
  aliceblue: "#f0f8ff",
  antiquewhite: "#faebd7",
  aqua: "#00ffff",
  aquamarine: "#7fffd4",
  azure: "#f0ffff",
  beige: "#f5f5dc",
  bisque: "#ffe4c4",
  black: "#000000",
  blanchedalmond: "#ffebcd",
  blue: "#0000ff",
  blueviolet: "#8a2be2",
  brown: "#a52a2a",
  burlywood: "#deb887",
  cadetblue: "#5f9ea0",
  chartreuse: "#7fff00",
  chocolate: "#d2691e",
  coral: "#ff7f50",
  cornflowerblue: "#6495ed",
  cornsilk: "#fff8dc",
  crimson: "#dc143c",
  cyan: "#00ffff",
  darkblue: "#00008b",
  darkcyan: "#008b8b",
  darkgoldenrod: "#b8860b",
  darkgray: "#a9a9a9",
  darkgreen: "#006400",
  darkgrey: "#a9a9a9",
  darkkhaki: "#bdb76b",
  darkmagenta: "#8b008b",
  darkolivegreen: "#556b2f",
  darkorange: "#ff8c00",
  darkorchid: "#9932cc",
  darkred: "#8b0000",
  darksalmon: "#e9967a",
  darkseagreen: "#8fbc8f",
  darkslateblue: "#483d8b",
  darkslategray: "#2f4f4f",
  darkslategrey: "#2f4f4f",
  darkturquoise: "#00ced1",
  darkviolet: "#9400d3",
  deeppink: "#ff1493",
  deepskyblue: "#00bfff",
  dimgray: "#696969",
  dimgrey: "#696969",
  dodgerblue: "#1e90ff",
  firebrick: "#b22222",
  floralwhite: "#fffaf0",
  forestgreen: "#228b22",
  fuchsia: "#ff00ff",
  gainsboro: "#dcdcdc",
  ghostwhite: "#f8f8ff",
  goldenrod: "#daa520",
  gold: "#ffd700",
  gray: "#808080",
  green: "#008000",
  greenyellow: "#adff2f",
  grey: "#808080",
  honeydew: "#f0fff0",
  hotpink: "#ff69b4",
  indianred: "#cd5c5c",
  indigo: "#4b0082",
  ivory: "#fffff0",
  khaki: "#f0e68c",
  lavenderblush: "#fff0f5",
  lavender: "#e6e6fa",
  lawngreen: "#7cfc00",
  lemonchiffon: "#fffacd",
  lightblue: "#add8e6",
  lightcoral: "#f08080",
  lightcyan: "#e0ffff",
  lightgoldenrodyellow: "#fafad2",
  lightgray: "#d3d3d3",
  lightgreen: "#90ee90",
  lightgrey: "#d3d3d3",
  lightpink: "#ffb6c1",
  lightsalmon: "#ffa07a",
  lightseagreen: "#20b2aa",
  lightskyblue: "#87cefa",
  lightslategray: "#778899",
  lightslategrey: "#778899",
  lightsteelblue: "#b0c4de",
  lightyellow: "#ffffe0",
  lime: "#00ff00",
  limegreen: "#32cd32",
  linen: "#faf0e6",
  magenta: "#ff00ff",
  maroon: "#800000",
  mediumaquamarine: "#66cdaa",
  mediumblue: "#0000cd",
  mediumorchid: "#ba55d3",
  mediumpurple: "#9370db",
  mediumseagreen: "#3cb371",
  mediumslateblue: "#7b68ee",
  mediumspringgreen: "#00fa9a",
  mediumturquoise: "#48d1cc",
  mediumvioletred: "#c71585",
  midnightblue: "#191970",
  mintcream: "#f5fffa",
  mistyrose: "#ffe4e1",
  moccasin: "#ffe4b5",
  navajowhite: "#ffdead",
  navy: "#000080",
  oldlace: "#fdf5e6",
  olive: "#808000",
  olivedrab: "#6b8e23",
  orange: "#ffa500",
  orangered: "#ff4500",
  orchid: "#da70d6",
  palegoldenrod: "#eee8aa",
  palegreen: "#98fb98",
  paleturquoise: "#afeeee",
  palevioletred: "#db7093",
  papayawhip: "#ffefd5",
  peachpuff: "#ffdab9",
  peru: "#cd853f",
  pink: "#ffc0cb",
  plum: "#dda0dd",
  powderblue: "#b0e0e6",
  purple: "#800080",
  rebeccapurple: "#663399",
  red: "#ff0000",
  rosybrown: "#bc8f8f",
  royalblue: "#4169e1",
  saddlebrown: "#8b4513",
  salmon: "#fa8072",
  sandybrown: "#f4a460",
  seagreen: "#2e8b57",
  seashell: "#fff5ee",
  sienna: "#a0522d",
  silver: "#c0c0c0",
  skyblue: "#87ceeb",
  slateblue: "#6a5acd",
  slategray: "#708090",
  slategrey: "#708090",
  snow: "#fffafa",
  springgreen: "#00ff7f",
  steelblue: "#4682b4",
  tan: "#d2b48c",
  teal: "#008080",
  thistle: "#d8bfd8",
  tomato: "#ff6347",
  turquoise: "#40e0d0",
  violet: "#ee82ee",
  wheat: "#f5deb3",
  white: "#ffffff",
  whitesmoke: "#f5f5f5",
  yellow: "#ffff00",
  yellowgreen: "#9acd32"
};
function inputToRGB(color) {
  var rgb = { r: 0, g: 0, b: 0 };
  var a = 1;
  var s = null;
  var v = null;
  var l = null;
  var ok = false;
  var format = false;
  if (typeof color === "string") {
    color = stringInputToObject(color);
  }
  if (typeof color === "object") {
    if (isValidCSSUnit(color.r) && isValidCSSUnit(color.g) && isValidCSSUnit(color.b)) {
      rgb = rgbToRgb(color.r, color.g, color.b);
      ok = true;
      format = String(color.r).substr(-1) === "%" ? "prgb" : "rgb";
    } else if (isValidCSSUnit(color.h) && isValidCSSUnit(color.s) && isValidCSSUnit(color.v)) {
      s = convertToPercentage(color.s);
      v = convertToPercentage(color.v);
      rgb = hsvToRgb(color.h, s, v);
      ok = true;
      format = "hsv";
    } else if (isValidCSSUnit(color.h) && isValidCSSUnit(color.s) && isValidCSSUnit(color.l)) {
      s = convertToPercentage(color.s);
      l = convertToPercentage(color.l);
      rgb = hslToRgb(color.h, s, l);
      ok = true;
      format = "hsl";
    }
    if (Object.prototype.hasOwnProperty.call(color, "a")) {
      a = color.a;
    }
  }
  a = boundAlpha(a);
  return {
    ok,
    format: color.format || format,
    r: Math.min(255, Math.max(rgb.r, 0)),
    g: Math.min(255, Math.max(rgb.g, 0)),
    b: Math.min(255, Math.max(rgb.b, 0)),
    a
  };
}
var CSS_INTEGER = "[-\\+]?\\d+%?";
var CSS_NUMBER = "[-\\+]?\\d*\\.\\d+%?";
var CSS_UNIT = "(?:".concat(CSS_NUMBER, ")|(?:").concat(CSS_INTEGER, ")");
var PERMISSIVE_MATCH3 = "[\\s|\\(]+(".concat(CSS_UNIT, ")[,|\\s]+(").concat(CSS_UNIT, ")[,|\\s]+(").concat(CSS_UNIT, ")\\s*\\)?");
var PERMISSIVE_MATCH4 = "[\\s|\\(]+(".concat(CSS_UNIT, ")[,|\\s]+(").concat(CSS_UNIT, ")[,|\\s]+(").concat(CSS_UNIT, ")[,|\\s]+(").concat(CSS_UNIT, ")\\s*\\)?");
var matchers = {
  CSS_UNIT: new RegExp(CSS_UNIT),
  rgb: new RegExp("rgb" + PERMISSIVE_MATCH3),
  rgba: new RegExp("rgba" + PERMISSIVE_MATCH4),
  hsl: new RegExp("hsl" + PERMISSIVE_MATCH3),
  hsla: new RegExp("hsla" + PERMISSIVE_MATCH4),
  hsv: new RegExp("hsv" + PERMISSIVE_MATCH3),
  hsva: new RegExp("hsva" + PERMISSIVE_MATCH4),
  hex3: /^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,
  hex6: /^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/,
  hex4: /^#?([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,
  hex8: /^#?([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/
};
function stringInputToObject(color) {
  color = color.trim().toLowerCase();
  if (color.length === 0) {
    return false;
  }
  var named = false;
  if (names[color]) {
    color = names[color];
    named = true;
  } else if (color === "transparent") {
    return { r: 0, g: 0, b: 0, a: 0, format: "name" };
  }
  var match = matchers.rgb.exec(color);
  if (match) {
    return { r: match[1], g: match[2], b: match[3] };
  }
  match = matchers.rgba.exec(color);
  if (match) {
    return { r: match[1], g: match[2], b: match[3], a: match[4] };
  }
  match = matchers.hsl.exec(color);
  if (match) {
    return { h: match[1], s: match[2], l: match[3] };
  }
  match = matchers.hsla.exec(color);
  if (match) {
    return { h: match[1], s: match[2], l: match[3], a: match[4] };
  }
  match = matchers.hsv.exec(color);
  if (match) {
    return { h: match[1], s: match[2], v: match[3] };
  }
  match = matchers.hsva.exec(color);
  if (match) {
    return { h: match[1], s: match[2], v: match[3], a: match[4] };
  }
  match = matchers.hex8.exec(color);
  if (match) {
    return {
      r: parseIntFromHex(match[1]),
      g: parseIntFromHex(match[2]),
      b: parseIntFromHex(match[3]),
      a: convertHexToDecimal(match[4]),
      format: named ? "name" : "hex8"
    };
  }
  match = matchers.hex6.exec(color);
  if (match) {
    return {
      r: parseIntFromHex(match[1]),
      g: parseIntFromHex(match[2]),
      b: parseIntFromHex(match[3]),
      format: named ? "name" : "hex"
    };
  }
  match = matchers.hex4.exec(color);
  if (match) {
    return {
      r: parseIntFromHex(match[1] + match[1]),
      g: parseIntFromHex(match[2] + match[2]),
      b: parseIntFromHex(match[3] + match[3]),
      a: convertHexToDecimal(match[4] + match[4]),
      format: named ? "name" : "hex8"
    };
  }
  match = matchers.hex3.exec(color);
  if (match) {
    return {
      r: parseIntFromHex(match[1] + match[1]),
      g: parseIntFromHex(match[2] + match[2]),
      b: parseIntFromHex(match[3] + match[3]),
      format: named ? "name" : "hex"
    };
  }
  return false;
}
function isValidCSSUnit(color) {
  return Boolean(matchers.CSS_UNIT.exec(String(color)));
}
var TinyColor = (
  /** @class */
  (function() {
    function TinyColor2(color, opts) {
      if (color === void 0) {
        color = "";
      }
      if (opts === void 0) {
        opts = {};
      }
      var _a;
      if (color instanceof TinyColor2) {
        return color;
      }
      if (typeof color === "number") {
        color = numberInputToObject(color);
      }
      this.originalInput = color;
      var rgb = inputToRGB(color);
      this.originalInput = color;
      this.r = rgb.r;
      this.g = rgb.g;
      this.b = rgb.b;
      this.a = rgb.a;
      this.roundA = Math.round(100 * this.a) / 100;
      this.format = (_a = opts.format) !== null && _a !== void 0 ? _a : rgb.format;
      this.gradientType = opts.gradientType;
      if (this.r < 1) {
        this.r = Math.round(this.r);
      }
      if (this.g < 1) {
        this.g = Math.round(this.g);
      }
      if (this.b < 1) {
        this.b = Math.round(this.b);
      }
      this.isValid = rgb.ok;
    }
    TinyColor2.prototype.isDark = function() {
      return this.getBrightness() < 128;
    };
    TinyColor2.prototype.isLight = function() {
      return !this.isDark();
    };
    TinyColor2.prototype.getBrightness = function() {
      var rgb = this.toRgb();
      return (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1e3;
    };
    TinyColor2.prototype.getLuminance = function() {
      var rgb = this.toRgb();
      var R;
      var G;
      var B;
      var RsRGB = rgb.r / 255;
      var GsRGB = rgb.g / 255;
      var BsRGB = rgb.b / 255;
      if (RsRGB <= 0.03928) {
        R = RsRGB / 12.92;
      } else {
        R = Math.pow((RsRGB + 0.055) / 1.055, 2.4);
      }
      if (GsRGB <= 0.03928) {
        G = GsRGB / 12.92;
      } else {
        G = Math.pow((GsRGB + 0.055) / 1.055, 2.4);
      }
      if (BsRGB <= 0.03928) {
        B = BsRGB / 12.92;
      } else {
        B = Math.pow((BsRGB + 0.055) / 1.055, 2.4);
      }
      return 0.2126 * R + 0.7152 * G + 0.0722 * B;
    };
    TinyColor2.prototype.getAlpha = function() {
      return this.a;
    };
    TinyColor2.prototype.setAlpha = function(alpha) {
      this.a = boundAlpha(alpha);
      this.roundA = Math.round(100 * this.a) / 100;
      return this;
    };
    TinyColor2.prototype.isMonochrome = function() {
      var s = this.toHsl().s;
      return s === 0;
    };
    TinyColor2.prototype.toHsv = function() {
      var hsv = rgbToHsv(this.r, this.g, this.b);
      return { h: hsv.h * 360, s: hsv.s, v: hsv.v, a: this.a };
    };
    TinyColor2.prototype.toHsvString = function() {
      var hsv = rgbToHsv(this.r, this.g, this.b);
      var h = Math.round(hsv.h * 360);
      var s = Math.round(hsv.s * 100);
      var v = Math.round(hsv.v * 100);
      return this.a === 1 ? "hsv(".concat(h, ", ").concat(s, "%, ").concat(v, "%)") : "hsva(".concat(h, ", ").concat(s, "%, ").concat(v, "%, ").concat(this.roundA, ")");
    };
    TinyColor2.prototype.toHsl = function() {
      var hsl = rgbToHsl(this.r, this.g, this.b);
      return { h: hsl.h * 360, s: hsl.s, l: hsl.l, a: this.a };
    };
    TinyColor2.prototype.toHslString = function() {
      var hsl = rgbToHsl(this.r, this.g, this.b);
      var h = Math.round(hsl.h * 360);
      var s = Math.round(hsl.s * 100);
      var l = Math.round(hsl.l * 100);
      return this.a === 1 ? "hsl(".concat(h, ", ").concat(s, "%, ").concat(l, "%)") : "hsla(".concat(h, ", ").concat(s, "%, ").concat(l, "%, ").concat(this.roundA, ")");
    };
    TinyColor2.prototype.toHex = function(allow3Char) {
      if (allow3Char === void 0) {
        allow3Char = false;
      }
      return rgbToHex(this.r, this.g, this.b, allow3Char);
    };
    TinyColor2.prototype.toHexString = function(allow3Char) {
      if (allow3Char === void 0) {
        allow3Char = false;
      }
      return "#" + this.toHex(allow3Char);
    };
    TinyColor2.prototype.toHex8 = function(allow4Char) {
      if (allow4Char === void 0) {
        allow4Char = false;
      }
      return rgbaToHex(this.r, this.g, this.b, this.a, allow4Char);
    };
    TinyColor2.prototype.toHex8String = function(allow4Char) {
      if (allow4Char === void 0) {
        allow4Char = false;
      }
      return "#" + this.toHex8(allow4Char);
    };
    TinyColor2.prototype.toHexShortString = function(allowShortChar) {
      if (allowShortChar === void 0) {
        allowShortChar = false;
      }
      return this.a === 1 ? this.toHexString(allowShortChar) : this.toHex8String(allowShortChar);
    };
    TinyColor2.prototype.toRgb = function() {
      return {
        r: Math.round(this.r),
        g: Math.round(this.g),
        b: Math.round(this.b),
        a: this.a
      };
    };
    TinyColor2.prototype.toRgbString = function() {
      var r = Math.round(this.r);
      var g = Math.round(this.g);
      var b = Math.round(this.b);
      return this.a === 1 ? "rgb(".concat(r, ", ").concat(g, ", ").concat(b, ")") : "rgba(".concat(r, ", ").concat(g, ", ").concat(b, ", ").concat(this.roundA, ")");
    };
    TinyColor2.prototype.toPercentageRgb = function() {
      var fmt = function(x) {
        return "".concat(Math.round(bound01(x, 255) * 100), "%");
      };
      return {
        r: fmt(this.r),
        g: fmt(this.g),
        b: fmt(this.b),
        a: this.a
      };
    };
    TinyColor2.prototype.toPercentageRgbString = function() {
      var rnd = function(x) {
        return Math.round(bound01(x, 255) * 100);
      };
      return this.a === 1 ? "rgb(".concat(rnd(this.r), "%, ").concat(rnd(this.g), "%, ").concat(rnd(this.b), "%)") : "rgba(".concat(rnd(this.r), "%, ").concat(rnd(this.g), "%, ").concat(rnd(this.b), "%, ").concat(this.roundA, ")");
    };
    TinyColor2.prototype.toName = function() {
      if (this.a === 0) {
        return "transparent";
      }
      if (this.a < 1) {
        return false;
      }
      var hex = "#" + rgbToHex(this.r, this.g, this.b, false);
      for (var _i = 0, _a = Object.entries(names); _i < _a.length; _i++) {
        var _b = _a[_i], key = _b[0], value = _b[1];
        if (hex === value) {
          return key;
        }
      }
      return false;
    };
    TinyColor2.prototype.toString = function(format) {
      var formatSet = Boolean(format);
      format = format !== null && format !== void 0 ? format : this.format;
      var formattedString = false;
      var hasAlpha = this.a < 1 && this.a >= 0;
      var needsAlphaFormat = !formatSet && hasAlpha && (format.startsWith("hex") || format === "name");
      if (needsAlphaFormat) {
        if (format === "name" && this.a === 0) {
          return this.toName();
        }
        return this.toRgbString();
      }
      if (format === "rgb") {
        formattedString = this.toRgbString();
      }
      if (format === "prgb") {
        formattedString = this.toPercentageRgbString();
      }
      if (format === "hex" || format === "hex6") {
        formattedString = this.toHexString();
      }
      if (format === "hex3") {
        formattedString = this.toHexString(true);
      }
      if (format === "hex4") {
        formattedString = this.toHex8String(true);
      }
      if (format === "hex8") {
        formattedString = this.toHex8String();
      }
      if (format === "name") {
        formattedString = this.toName();
      }
      if (format === "hsl") {
        formattedString = this.toHslString();
      }
      if (format === "hsv") {
        formattedString = this.toHsvString();
      }
      return formattedString || this.toHexString();
    };
    TinyColor2.prototype.toNumber = function() {
      return (Math.round(this.r) << 16) + (Math.round(this.g) << 8) + Math.round(this.b);
    };
    TinyColor2.prototype.clone = function() {
      return new TinyColor2(this.toString());
    };
    TinyColor2.prototype.lighten = function(amount) {
      if (amount === void 0) {
        amount = 10;
      }
      var hsl = this.toHsl();
      hsl.l += amount / 100;
      hsl.l = clamp01(hsl.l);
      return new TinyColor2(hsl);
    };
    TinyColor2.prototype.brighten = function(amount) {
      if (amount === void 0) {
        amount = 10;
      }
      var rgb = this.toRgb();
      rgb.r = Math.max(0, Math.min(255, rgb.r - Math.round(255 * -(amount / 100))));
      rgb.g = Math.max(0, Math.min(255, rgb.g - Math.round(255 * -(amount / 100))));
      rgb.b = Math.max(0, Math.min(255, rgb.b - Math.round(255 * -(amount / 100))));
      return new TinyColor2(rgb);
    };
    TinyColor2.prototype.darken = function(amount) {
      if (amount === void 0) {
        amount = 10;
      }
      var hsl = this.toHsl();
      hsl.l -= amount / 100;
      hsl.l = clamp01(hsl.l);
      return new TinyColor2(hsl);
    };
    TinyColor2.prototype.tint = function(amount) {
      if (amount === void 0) {
        amount = 10;
      }
      return this.mix("white", amount);
    };
    TinyColor2.prototype.shade = function(amount) {
      if (amount === void 0) {
        amount = 10;
      }
      return this.mix("black", amount);
    };
    TinyColor2.prototype.desaturate = function(amount) {
      if (amount === void 0) {
        amount = 10;
      }
      var hsl = this.toHsl();
      hsl.s -= amount / 100;
      hsl.s = clamp01(hsl.s);
      return new TinyColor2(hsl);
    };
    TinyColor2.prototype.saturate = function(amount) {
      if (amount === void 0) {
        amount = 10;
      }
      var hsl = this.toHsl();
      hsl.s += amount / 100;
      hsl.s = clamp01(hsl.s);
      return new TinyColor2(hsl);
    };
    TinyColor2.prototype.greyscale = function() {
      return this.desaturate(100);
    };
    TinyColor2.prototype.spin = function(amount) {
      var hsl = this.toHsl();
      var hue = (hsl.h + amount) % 360;
      hsl.h = hue < 0 ? 360 + hue : hue;
      return new TinyColor2(hsl);
    };
    TinyColor2.prototype.mix = function(color, amount) {
      if (amount === void 0) {
        amount = 50;
      }
      var rgb1 = this.toRgb();
      var rgb2 = new TinyColor2(color).toRgb();
      var p = amount / 100;
      var rgba = {
        r: (rgb2.r - rgb1.r) * p + rgb1.r,
        g: (rgb2.g - rgb1.g) * p + rgb1.g,
        b: (rgb2.b - rgb1.b) * p + rgb1.b,
        a: (rgb2.a - rgb1.a) * p + rgb1.a
      };
      return new TinyColor2(rgba);
    };
    TinyColor2.prototype.analogous = function(results, slices) {
      if (results === void 0) {
        results = 6;
      }
      if (slices === void 0) {
        slices = 30;
      }
      var hsl = this.toHsl();
      var part = 360 / slices;
      var ret = [this];
      for (hsl.h = (hsl.h - (part * results >> 1) + 720) % 360; --results; ) {
        hsl.h = (hsl.h + part) % 360;
        ret.push(new TinyColor2(hsl));
      }
      return ret;
    };
    TinyColor2.prototype.complement = function() {
      var hsl = this.toHsl();
      hsl.h = (hsl.h + 180) % 360;
      return new TinyColor2(hsl);
    };
    TinyColor2.prototype.monochromatic = function(results) {
      if (results === void 0) {
        results = 6;
      }
      var hsv = this.toHsv();
      var h = hsv.h;
      var s = hsv.s;
      var v = hsv.v;
      var res = [];
      var modification = 1 / results;
      while (results--) {
        res.push(new TinyColor2({ h, s, v }));
        v = (v + modification) % 1;
      }
      return res;
    };
    TinyColor2.prototype.splitcomplement = function() {
      var hsl = this.toHsl();
      var h = hsl.h;
      return [
        this,
        new TinyColor2({ h: (h + 72) % 360, s: hsl.s, l: hsl.l }),
        new TinyColor2({ h: (h + 216) % 360, s: hsl.s, l: hsl.l })
      ];
    };
    TinyColor2.prototype.onBackground = function(background) {
      var fg = this.toRgb();
      var bg = new TinyColor2(background).toRgb();
      var alpha = fg.a + bg.a * (1 - fg.a);
      return new TinyColor2({
        r: (fg.r * fg.a + bg.r * bg.a * (1 - fg.a)) / alpha,
        g: (fg.g * fg.a + bg.g * bg.a * (1 - fg.a)) / alpha,
        b: (fg.b * fg.a + bg.b * bg.a * (1 - fg.a)) / alpha,
        a: alpha
      });
    };
    TinyColor2.prototype.triad = function() {
      return this.polyad(3);
    };
    TinyColor2.prototype.tetrad = function() {
      return this.polyad(4);
    };
    TinyColor2.prototype.polyad = function(n) {
      var hsl = this.toHsl();
      var h = hsl.h;
      var result = [this];
      var increment = 360 / n;
      for (var i = 1; i < n; i++) {
        result.push(new TinyColor2({ h: (h + i * increment) % 360, s: hsl.s, l: hsl.l }));
      }
      return result;
    };
    TinyColor2.prototype.equals = function(color) {
      return this.toRgbString() === new TinyColor2(color).toRgbString();
    };
    return TinyColor2;
  })()
);
function tinycolor(...args) {
  return new TinyColor(...args);
}
function _colorChange(data, oldHue) {
  const alpha = data && data.a;
  let color;
  if (data && data.hsl)
    color = tinycolor(data.hsl);
  else if (data && data.hex && data.hex.length > 0)
    color = tinycolor(data.hex);
  else if (data && data.hsv)
    color = tinycolor(data.hsv);
  else if (data && data.rgba)
    color = tinycolor(data.rgba);
  else if (data && data.rgb)
    color = tinycolor(data.rgb);
  else
    color = tinycolor(data);
  if (color && (color._a === void 0 || color._a === null))
    color.setAlpha(alpha || color.getAlpha());
  const hsl = color.toHsl();
  const hsv = color.toHsv();
  if (hsl.s === 0)
    hsv.h = hsl.h = data.h || data.hsl && data.hsl.h || oldHue || 0;
  if (hsv.v < 0.0164) {
    hsv.h = data.h || data.hsv && data.hsv.h || 0;
    hsv.s = data.s || data.hsv && data.hsv.s || 0;
  }
  if (hsl.l < 0.01) {
    hsl.h = data.h || data.hsl && data.hsl.h || 0;
    hsl.s = data.s || data.hsl && data.hsl.s || 0;
  }
  return {
    hsl,
    hex: color.toHexString().toUpperCase(),
    hex8: color.toHex8String().toUpperCase(),
    rgba: color.toRgb(),
    hsv,
    oldHue: data.h || oldHue || hsl.h,
    source: data.source,
    a: color.getAlpha()
  };
}
var colorMixin = {
  model: {
    prop: "modelValue",
    event: "update:modelValue"
  },
  props: ["modelValue"],
  data() {
    return {
      val: _colorChange(this.modelValue)
    };
  },
  computed: {
    colors: {
      get() {
        return this.val;
      },
      set(newVal) {
        this.val = newVal;
        this.$emit("update:modelValue", newVal);
      }
    }
  },
  watch: {
    modelValue(newVal) {
      this.val = _colorChange(newVal);
    }
  },
  methods: {
    colorChange(data, oldHue) {
      this.oldHue = this.colors.hsl.h;
      this.colors = _colorChange(data, oldHue || this.oldHue);
    },
    isValidHex(hex) {
      return tinycolor(hex).isValid;
    },
    simpleCheckForValidColor(data) {
      const keysToCheck = ["r", "g", "b", "a", "h", "s", "l", "v"];
      let checked = 0;
      let passed = 0;
      for (let i = 0; i < keysToCheck.length; i++) {
        const letter = keysToCheck[i];
        if (data[letter]) {
          checked++;
          if (!isNaN(data[letter]))
            passed++;
        }
      }
      if (checked === passed)
        return data;
    },
    paletteUpperCase(palette) {
      return palette.map((c) => c.toUpperCase());
    },
    isTransparent(color) {
      return tinycolor(color).getAlpha() === 0;
    }
  }
};
var script$3 = {
  name: "EditableInput",
  props: {
    label: String,
    labelText: String,
    desc: String,
    value: [String, Number],
    max: Number,
    min: Number,
    arrowOffset: {
      type: Number,
      default: 1
    }
  },
  computed: {
    val: {
      get() {
        return this.value;
      },
      set(v) {
        if (!(this.max === void 0) && +v > this.max)
          this.$refs.input.value = this.max;
        else
          return v;
      }
    },
    labelId() {
      return `input__label__${this.label}__${Math.random().toString().slice(2, 5)}`;
    },
    labelSpanText() {
      return this.labelText || this.label;
    }
  },
  methods: {
    update(e) {
      this.handleChange(e.target.value);
    },
    handleChange(newVal) {
      const data = {};
      data[this.label] = newVal;
      if (data.hex === void 0 && data["#"] === void 0)
        this.$emit("change", data);
      else if (newVal.length > 5)
        this.$emit("change", data);
    },
    // **** unused
    // handleBlur (e) {
    //   console.log(e)
    // },
    handleKeyDown(e) {
      let { val } = this;
      const number = Number(val);
      if (number) {
        const amount = this.arrowOffset || 1;
        if (e.keyCode === 38) {
          val = number + amount;
          this.handleChange(val);
          e.preventDefault();
        }
        if (e.keyCode === 40) {
          val = number - amount;
          this.handleChange(val);
          e.preventDefault();
        }
      }
    }
    // **** unused
    // handleDrag (e) {
    //   console.log(e)
    // },
    // handleMouseDown (e) {
    //   console.log(e)
    // }
  }
};
const _hoisted_1$4 = { class: "vc-editable-input" };
const _hoisted_2$4 = ["aria-labelledby"];
const _hoisted_3$4 = ["id", "for"];
const _hoisted_4$3 = { class: "vc-input__desc" };
function render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$4, [
    withDirectives(createBaseVNode("input", {
      ref: "input",
      "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $options.val = $event),
      "aria-labelledby": $options.labelId,
      class: "vc-input__input",
      onKeydown: _cache[1] || (_cache[1] = (...args) => $options.handleKeyDown && $options.handleKeyDown(...args)),
      onInput: _cache[2] || (_cache[2] = (...args) => $options.update && $options.update(...args))
    }, null, 40, _hoisted_2$4), [
      [vModelText, $options.val]
    ]),
    createBaseVNode("span", {
      id: $options.labelId,
      for: $props.label,
      class: "vc-input__label"
    }, toDisplayString($options.labelSpanText), 9, _hoisted_3$4),
    createBaseVNode(
      "span",
      _hoisted_4$3,
      toDisplayString($props.desc),
      1
      /* TEXT */
    )
  ]);
}
var css_248z$3 = ".vc-editable-input{position:relative}.vc-input__input{border:0;outline:none;padding:0}.vc-input__label{text-transform:capitalize}";
styleInject(css_248z$3);
script$3.render = render$3;
script$3.__file = "src/components/editable-input/editable-input.vue";
script$3.install = install;
function clamp(value, min, max) {
  return min < max ? value < min ? min : value > max ? max : value : value < max ? max : value > min ? min : value;
}
var script$2 = {
  name: "Saturation",
  props: {
    value: Object
  },
  computed: {
    colors() {
      return this.value;
    },
    bgColor() {
      return `hsl(${this.colors.hsv.h}, 100%, 50%)`;
    },
    pointerTop() {
      return `${-(this.colors.hsv.v * 100) + 1 + 100}%`;
    },
    pointerLeft() {
      return `${this.colors.hsv.s * 100}%`;
    }
  },
  methods: {
    handleChange(e, skip) {
      !skip && e.preventDefault();
      const { container } = this.$refs;
      if (!container) {
        return;
      }
      const containerWidth = container.clientWidth;
      const containerHeight = container.clientHeight;
      const xOffset = container.getBoundingClientRect().left + window.pageXOffset;
      const yOffset = container.getBoundingClientRect().top + window.pageYOffset;
      const pageX = e.pageX || (e.touches ? e.touches[0].pageX : 0);
      const pageY = e.pageY || (e.touches ? e.touches[0].pageY : 0);
      const left = clamp(pageX - xOffset, 0, containerWidth);
      const top = clamp(pageY - yOffset, 0, containerHeight);
      const saturation = left / containerWidth;
      const bright = clamp(-(top / containerHeight) + 1, 0, 1);
      this.onChange({
        h: this.colors.hsv.h,
        s: saturation,
        v: bright,
        a: this.colors.hsv.a,
        source: "hsva"
      });
    },
    onChange(param) {
      this.$emit("change", param);
    },
    handleMouseDown(e) {
      window.addEventListener("mousemove", this.handleChange);
      window.addEventListener("mouseup", this.handleChange);
      window.addEventListener("mouseup", this.handleMouseUp);
    },
    handleMouseUp(e) {
      this.unbindEventListeners();
    },
    unbindEventListeners() {
      window.removeEventListener("mousemove", this.handleChange);
      window.removeEventListener("mouseup", this.handleChange);
      window.removeEventListener("mouseup", this.handleMouseUp);
    }
  }
};
const _hoisted_1$3 = /* @__PURE__ */ createBaseVNode(
  "div",
  { class: "vc-saturation--white" },
  null,
  -1
  /* HOISTED */
);
const _hoisted_2$3 = /* @__PURE__ */ createBaseVNode(
  "div",
  { class: "vc-saturation--black" },
  null,
  -1
  /* HOISTED */
);
const _hoisted_3$3 = /* @__PURE__ */ createBaseVNode(
  "div",
  { class: "vc-saturation-circle" },
  null,
  -1
  /* HOISTED */
);
const _hoisted_4$2 = [
  _hoisted_3$3
];
function render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      ref: "container",
      class: "vc-saturation",
      style: normalizeStyle({ background: $options.bgColor }),
      onMousedown: _cache[0] || (_cache[0] = (...args) => $options.handleMouseDown && $options.handleMouseDown(...args)),
      onTouchmove: _cache[1] || (_cache[1] = (...args) => $options.handleChange && $options.handleChange(...args)),
      onTouchstart: _cache[2] || (_cache[2] = (...args) => $options.handleChange && $options.handleChange(...args))
    },
    [
      _hoisted_1$3,
      _hoisted_2$3,
      createBaseVNode(
        "div",
        {
          class: "vc-saturation-pointer",
          style: normalizeStyle({ top: $options.pointerTop, left: $options.pointerLeft })
        },
        _hoisted_4$2,
        4
        /* STYLE */
      )
    ],
    36
    /* STYLE, HYDRATE_EVENTS */
  );
}
var css_248z$2 = ".vc-saturation,.vc-saturation--black,.vc-saturation--white{bottom:0;cursor:pointer;left:0;position:absolute;right:0;top:0}.vc-saturation--white{background:linear-gradient(90deg,#fff,hsla(0,0%,100%,0))}.vc-saturation--black{background:linear-gradient(0deg,#000,transparent)}.vc-saturation-pointer{cursor:pointer;position:absolute}.vc-saturation-circle{border-radius:50%;box-shadow:0 0 0 1.5px #fff,inset 0 0 1px 1px rgba(0,0,0,.3),0 0 1px 2px rgba(0,0,0,.4);cursor:head;height:4px;transform:translate(-2px,-2px);width:4px}";
styleInject(css_248z$2);
script$2.render = render$2;
script$2.__file = "src/components/saturation/saturation.vue";
script$2.install = install;
var script$1 = {
  name: "Hue",
  props: {
    value: Object,
    direction: {
      type: String,
      // [horizontal | vertical]
      default: "horizontal"
    }
  },
  data() {
    return {
      oldHue: 0,
      pullDirection: ""
    };
  },
  computed: {
    colors() {
      return this.value;
    },
    directionClass() {
      return {
        "vc-hue--horizontal": this.direction === "horizontal",
        "vc-hue--vertical": this.direction === "vertical"
      };
    },
    pointerTop() {
      if (this.direction === "vertical") {
        if (this.colors.hsl.h === 0 && this.pullDirection === "right")
          return 0;
        return `${-(this.colors.hsl.h * 100 / 360) + 100}%`;
      }
      return 0;
    },
    pointerLeft() {
      if (this.direction === "vertical")
        return 0;
      if (this.colors.hsl.h === 0 && this.pullDirection === "right")
        return "100%";
      return `${this.colors.hsl.h * 100 / 360}%`;
    }
  },
  watch: {
    value: {
      handler(value, oldVal) {
        const { h } = value.hsl;
        if (h !== 0 && h - this.oldHue > 0)
          this.pullDirection = "right";
        if (h !== 0 && h - this.oldHue < 0)
          this.pullDirection = "left";
        this.oldHue = h;
      },
      deep: true,
      immediate: true
    }
  },
  methods: {
    handleChange(e, skip) {
      !skip && e.preventDefault();
      const { container } = this.$refs;
      if (!container) {
        return;
      }
      const containerWidth = container.clientWidth;
      const containerHeight = container.clientHeight;
      const xOffset = container.getBoundingClientRect().left + window.pageXOffset;
      const yOffset = container.getBoundingClientRect().top + window.pageYOffset;
      const pageX = e.pageX || (e.touches ? e.touches[0].pageX : 0);
      const pageY = e.pageY || (e.touches ? e.touches[0].pageY : 0);
      const left = pageX - xOffset;
      const top = pageY - yOffset;
      let h;
      let percent;
      if (this.direction === "vertical") {
        if (top < 0) {
          h = 360;
        } else if (top > containerHeight) {
          h = 0;
        } else {
          percent = -(top * 100 / containerHeight) + 100;
          h = 360 * percent / 100;
        }
        if (this.colors.hsl.h !== h) {
          this.$emit("change", {
            h,
            s: this.colors.hsl.s,
            l: this.colors.hsl.l,
            a: this.colors.hsl.a,
            source: "hsl"
          });
        }
      } else {
        if (left < 0) {
          h = 0;
        } else if (left > containerWidth) {
          h = 360;
        } else {
          percent = left * 100 / containerWidth;
          h = 360 * percent / 100;
        }
        if (this.colors.hsl.h !== h) {
          this.$emit("change", {
            h,
            s: this.colors.hsl.s,
            l: this.colors.hsl.l,
            a: this.colors.hsl.a,
            source: "hsl"
          });
        }
      }
    },
    handleMouseDown(e) {
      this.handleChange(e, true);
      window.addEventListener("mousemove", this.handleChange);
      window.addEventListener("mouseup", this.handleChange);
      window.addEventListener("mouseup", this.handleMouseUp);
    },
    handleMouseUp(e) {
      this.unbindEventListeners();
    },
    unbindEventListeners() {
      window.removeEventListener("mousemove", this.handleChange);
      window.removeEventListener("mouseup", this.handleChange);
      window.removeEventListener("mouseup", this.handleMouseUp);
    }
  }
};
const _hoisted_1$2 = ["aria-valuenow"];
const _hoisted_2$2 = /* @__PURE__ */ createBaseVNode(
  "div",
  { class: "vc-hue-picker" },
  null,
  -1
  /* HOISTED */
);
const _hoisted_3$2 = [
  _hoisted_2$2
];
function render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass(["vc-hue", [$options.directionClass]])
    },
    [
      createBaseVNode("div", {
        ref: "container",
        class: "vc-hue-container",
        role: "slider",
        "aria-valuenow": $options.colors.hsl.h,
        "aria-valuemin": "0",
        "aria-valuemax": "360",
        onMousedown: _cache[0] || (_cache[0] = (...args) => $options.handleMouseDown && $options.handleMouseDown(...args)),
        onTouchmove: _cache[1] || (_cache[1] = (...args) => $options.handleChange && $options.handleChange(...args)),
        onTouchstart: _cache[2] || (_cache[2] = (...args) => $options.handleChange && $options.handleChange(...args))
      }, [
        createBaseVNode(
          "div",
          {
            class: "vc-hue-pointer",
            style: normalizeStyle({ top: $options.pointerTop, left: $options.pointerLeft }),
            role: "presentation"
          },
          _hoisted_3$2,
          4
          /* STYLE */
        )
      ], 40, _hoisted_1$2)
    ],
    2
    /* CLASS */
  );
}
var css_248z$1 = ".vc-hue{border-radius:2px;bottom:0;left:0;position:absolute;right:0;top:0}.vc-hue--horizontal{background:linear-gradient(90deg,red 0,#ff0 17%,#0f0 33%,#0ff 50%,#00f 67%,#f0f 83%,red)}.vc-hue--vertical{background:linear-gradient(0deg,red 0,#ff0 17%,#0f0 33%,#0ff 50%,#00f 67%,#f0f 83%,red)}.vc-hue-container{cursor:pointer;height:100%;margin:0 2px;position:relative}.vc-hue-pointer{position:absolute;z-index:2}.vc-hue-picker{background:#fff;border-radius:1px;box-shadow:0 0 2px rgba(0,0,0,.6);cursor:pointer;height:8px;margin-top:1px;transform:translateX(-2px);width:4px}";
styleInject(css_248z$1);
script$1.render = render$1;
script$1.__file = "src/components/hue/hue.vue";
script$1.install = install;
var script = {
  name: "Chrome",
  components: {
    Saturation: script$2,
    Hue: script$1,
    Alpha: script$4,
    EdIn: script$3,
    Checkboard: script$5
  },
  mixins: [colorMixin],
  props: {
    disableAlpha: {
      type: Boolean,
      default: false
    },
    disableFields: {
      type: Boolean,
      default: false
    },
    format: {
      type: String,
      default: "hex"
    }
  },
  data() {
    return {
      fieldsIndex: "hex",
      highlight: false
    };
  },
  computed: {
    hsl() {
      const { h, s, l } = this.colors.hsl;
      return {
        h: h.toFixed(),
        s: `${(s * 100).toFixed()}%`,
        l: `${(l * 100).toFixed()}%`
      };
    },
    activeColor() {
      const { rgba } = this.colors;
      return `rgba(${[rgba.r, rgba.g, rgba.b, rgba.a].join(",")})`;
    },
    hasAlpha() {
      return this.colors.a < 1;
    }
  },
  watch: {
    format: {
      handler(val) {
        this.fieldsIndex = val;
      },
      immediate: true
    }
  },
  methods: {
    childChange(data) {
      this.colorChange(data);
    },
    inputChange(data) {
      if (!data)
        return;
      if (data.hex) {
        this.isValidHex(data.hex) && this.colorChange({
          hex: data.hex,
          source: "hex"
        });
      } else if (data.r || data.g || data.b || data.a) {
        this.colorChange({
          r: data.r || this.colors.rgba.r,
          g: data.g || this.colors.rgba.g,
          b: data.b || this.colors.rgba.b,
          a: data.a || this.colors.rgba.a,
          source: "rgba"
        });
      } else if (data.h || data.s || data.l) {
        const s = data.s ? data.s.replace("%", "") / 100 : this.colors.hsl.s;
        const l = data.l ? data.l.replace("%", "") / 100 : this.colors.hsl.l;
        this.colorChange({
          h: data.h || this.colors.hsl.h,
          s,
          l,
          source: "hsl"
        });
      }
    },
    toggleViews() {
      switch (this.fieldsIndex) {
        case "hex":
          this.fieldsIndex = `rgb${this.disableAlpha ? "" : "a"}`;
          break;
        case "rgb":
        case "rgba":
          this.fieldsIndex = `hsl${this.disableAlpha ? "" : "a"}`;
          break;
        default:
          this.fieldsIndex = "hex";
          break;
      }
      this.$emit("update:format", this.fieldsIndex);
    },
    showHighlight() {
      this.highlight = true;
    },
    hideHighlight() {
      this.highlight = false;
    }
  }
};
const _hoisted_1$1 = { class: "vc-chrome-saturation-wrap" };
const _hoisted_2$1 = { class: "vc-chrome-body" };
const _hoisted_3$1 = { class: "vc-chrome-controls" };
const _hoisted_4$1 = { class: "vc-chrome-color-wrap" };
const _hoisted_5$1 = ["aria-label"];
const _hoisted_6$1 = { class: "vc-chrome-sliders" };
const _hoisted_7 = { class: "vc-chrome-hue-wrap" };
const _hoisted_8 = {
  key: 0,
  class: "vc-chrome-alpha-wrap"
};
const _hoisted_9 = {
  key: 0,
  class: "vc-chrome-fields-wrap"
};
const _hoisted_10 = { class: "vc-chrome-fields" };
const _hoisted_11 = { class: "vc-chrome-field" };
const _hoisted_12 = { class: "vc-chrome-fields" };
const _hoisted_13 = { class: "vc-chrome-field" };
const _hoisted_14 = { class: "vc-chrome-field" };
const _hoisted_15 = { class: "vc-chrome-field" };
const _hoisted_16 = {
  key: 0,
  class: "vc-chrome-field"
};
const _hoisted_17 = { class: "vc-chrome-fields" };
const _hoisted_18 = { class: "vc-chrome-field" };
const _hoisted_19 = { class: "vc-chrome-field" };
const _hoisted_20 = { class: "vc-chrome-field" };
const _hoisted_21 = {
  key: 0,
  class: "vc-chrome-field"
};
const _hoisted_22 = { class: "vc-chrome-toggle-icon" };
const _hoisted_23 = /* @__PURE__ */ createBaseVNode(
  "path",
  {
    fill: "#333",
    d: "M12,18.17L8.83,15L7.42,16.41L12,21L16.59,16.41L15.17,15M12,5.83L15.17,9L16.58,7.59L12,3L7.41,7.59L8.83,9L12,5.83Z"
  },
  null,
  -1
  /* HOISTED */
);
const _hoisted_24 = [
  _hoisted_23
];
const _hoisted_25 = { class: "vc-chrome-toggle-icon-highlight" };
function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Saturation = resolveComponent("Saturation");
  const _component_Checkboard = resolveComponent("Checkboard");
  const _component_Hue = resolveComponent("Hue");
  const _component_Alpha = resolveComponent("Alpha");
  const _component_EdIn = resolveComponent("EdIn");
  return openBlock(), createElementBlock(
    "div",
    {
      role: "application",
      "aria-label": "Chrome color picker",
      class: normalizeClass(["vc-chrome", [$props.disableAlpha ? "vc-chrome__disable-alpha" : ""]])
    },
    [
      createBaseVNode("div", _hoisted_1$1, [
        createVNode(_component_Saturation, {
          value: _ctx.colors,
          onChange: $options.childChange
        }, null, 8, ["value", "onChange"])
      ]),
      createBaseVNode("div", _hoisted_2$1, [
        createBaseVNode("div", _hoisted_3$1, [
          createBaseVNode("div", _hoisted_4$1, [
            createBaseVNode("div", {
              "aria-label": `current color is ${_ctx.colors.hex}`,
              class: "vc-chrome-active-color",
              style: normalizeStyle({ background: $options.activeColor })
            }, null, 12, _hoisted_5$1),
            !$props.disableAlpha ? (openBlock(), createBlock(_component_Checkboard, { key: 0 })) : createCommentVNode("v-if", true)
          ]),
          createBaseVNode("div", _hoisted_6$1, [
            createBaseVNode("div", _hoisted_7, [
              createVNode(_component_Hue, {
                value: _ctx.colors,
                onChange: $options.childChange
              }, null, 8, ["value", "onChange"])
            ]),
            !$props.disableAlpha ? (openBlock(), createElementBlock("div", _hoisted_8, [
              createVNode(_component_Alpha, {
                value: _ctx.colors,
                onChange: $options.childChange
              }, null, 8, ["value", "onChange"])
            ])) : createCommentVNode("v-if", true)
          ])
        ]),
        !$props.disableFields ? (openBlock(), createElementBlock("div", _hoisted_9, [
          withDirectives(createBaseVNode(
            "div",
            _hoisted_10,
            [
              createCommentVNode(" hex "),
              createBaseVNode("div", _hoisted_11, [
                !$options.hasAlpha ? (openBlock(), createBlock(_component_EdIn, {
                  key: 0,
                  label: "hex",
                  value: _ctx.colors.hex,
                  onChange: $options.inputChange
                }, null, 8, ["value", "onChange"])) : createCommentVNode("v-if", true),
                $options.hasAlpha ? (openBlock(), createBlock(_component_EdIn, {
                  key: 1,
                  label: "hex",
                  value: _ctx.colors.hex8,
                  onChange: $options.inputChange
                }, null, 8, ["value", "onChange"])) : createCommentVNode("v-if", true)
              ])
            ],
            512
            /* NEED_PATCH */
          ), [
            [vShow, $data.fieldsIndex === "hex"]
          ]),
          withDirectives(createBaseVNode(
            "div",
            _hoisted_12,
            [
              createCommentVNode(" rgba "),
              createBaseVNode("div", _hoisted_13, [
                createVNode(_component_EdIn, {
                  label: "r",
                  value: _ctx.colors.rgba.r,
                  onChange: $options.inputChange
                }, null, 8, ["value", "onChange"])
              ]),
              createBaseVNode("div", _hoisted_14, [
                createVNode(_component_EdIn, {
                  label: "g",
                  value: _ctx.colors.rgba.g,
                  onChange: $options.inputChange
                }, null, 8, ["value", "onChange"])
              ]),
              createBaseVNode("div", _hoisted_15, [
                createVNode(_component_EdIn, {
                  label: "b",
                  value: _ctx.colors.rgba.b,
                  onChange: $options.inputChange
                }, null, 8, ["value", "onChange"])
              ]),
              !$props.disableAlpha ? (openBlock(), createElementBlock("div", _hoisted_16, [
                createVNode(_component_EdIn, {
                  label: "a",
                  value: _ctx.colors.a,
                  "arrow-offset": 0.01,
                  max: 1,
                  onChange: $options.inputChange
                }, null, 8, ["value", "arrow-offset", "onChange"])
              ])) : createCommentVNode("v-if", true)
            ],
            512
            /* NEED_PATCH */
          ), [
            [vShow, ["rgb", "rgba"].includes($data.fieldsIndex)]
          ]),
          withDirectives(createBaseVNode(
            "div",
            _hoisted_17,
            [
              createCommentVNode(" hsla "),
              createBaseVNode("div", _hoisted_18, [
                createVNode(_component_EdIn, {
                  label: "h",
                  value: $options.hsl.h,
                  onChange: $options.inputChange
                }, null, 8, ["value", "onChange"])
              ]),
              createBaseVNode("div", _hoisted_19, [
                createVNode(_component_EdIn, {
                  label: "s",
                  value: $options.hsl.s,
                  onChange: $options.inputChange
                }, null, 8, ["value", "onChange"])
              ]),
              createBaseVNode("div", _hoisted_20, [
                createVNode(_component_EdIn, {
                  label: "l",
                  value: $options.hsl.l,
                  onChange: $options.inputChange
                }, null, 8, ["value", "onChange"])
              ]),
              !$props.disableAlpha ? (openBlock(), createElementBlock("div", _hoisted_21, [
                createVNode(_component_EdIn, {
                  label: "a",
                  value: _ctx.colors.a,
                  "arrow-offset": 0.01,
                  max: 1,
                  onChange: $options.inputChange
                }, null, 8, ["value", "arrow-offset", "onChange"])
              ])) : createCommentVNode("v-if", true)
            ],
            512
            /* NEED_PATCH */
          ), [
            [vShow, ["hsl", "hsla"].includes($data.fieldsIndex)]
          ]),
          createCommentVNode(" btn "),
          createBaseVNode("div", {
            class: "vc-chrome-toggle-btn",
            role: "button",
            "aria-label": "Change another color definition",
            onClick: _cache[3] || (_cache[3] = (...args) => $options.toggleViews && $options.toggleViews(...args))
          }, [
            createBaseVNode("div", _hoisted_22, [
              (openBlock(), createElementBlock(
                "svg",
                {
                  style: { "width": "24px", "height": "24px" },
                  viewBox: "0 0 24 24",
                  onMouseover: _cache[0] || (_cache[0] = (...args) => $options.showHighlight && $options.showHighlight(...args)),
                  onMouseenter: _cache[1] || (_cache[1] = (...args) => $options.showHighlight && $options.showHighlight(...args)),
                  onMouseout: _cache[2] || (_cache[2] = (...args) => $options.hideHighlight && $options.hideHighlight(...args))
                },
                _hoisted_24,
                32
                /* HYDRATE_EVENTS */
              ))
            ]),
            withDirectives(createBaseVNode(
              "div",
              _hoisted_25,
              null,
              512
              /* NEED_PATCH */
            ), [
              [vShow, $data.highlight]
            ])
          ]),
          createCommentVNode(" btn ")
        ])) : createCommentVNode("v-if", true)
      ])
    ],
    2
    /* CLASS */
  );
}
var css_248z = ".vc-chrome{background:#fff;background-color:#fff;border-radius:2px;box-shadow:0 0 2px rgba(0,0,0,.3),0 4px 8px rgba(0,0,0,.3);box-sizing:initial;font-family:Menlo;width:225px}.vc-chrome-controls{display:flex}.vc-chrome-color-wrap{position:relative;width:36px}.vc-chrome-active-color{border-radius:15px;height:30px;overflow:hidden;position:relative;width:30px;z-index:1}.vc-chrome-color-wrap .vc-checkerboard{background-size:auto;border-radius:15px;height:30px;width:30px}.vc-chrome-sliders{flex:1}.vc-chrome-fields-wrap{display:flex;padding-top:16px}.vc-chrome-fields{display:flex;flex:1;margin-left:-6px}.vc-chrome-field{padding-left:6px;width:100%}.vc-chrome-toggle-btn{position:relative;text-align:right;width:32px}.vc-chrome-toggle-icon{cursor:pointer;margin-right:-4px;margin-top:12px;position:relative;z-index:2}.vc-chrome-toggle-icon-highlight{background:#eee;border-radius:4px;height:28px;left:12px;position:absolute;top:10px;width:24px}.vc-chrome-hue-wrap{margin-bottom:8px}.vc-chrome-alpha-wrap,.vc-chrome-hue-wrap{height:10px;position:relative}.vc-chrome-alpha-wrap .vc-alpha-gradient,.vc-chrome-hue-wrap .vc-hue{border-radius:2px}.vc-chrome-alpha-wrap .vc-alpha-picker,.vc-chrome-hue-wrap .vc-hue-picker{background-color:#f8f8f8;border-radius:6px;box-shadow:0 1px 4px 0 rgba(0,0,0,.37);height:12px;transform:translate(-6px,-2px);width:12px}.vc-chrome-body{background-color:#fff;padding:16px 16px 12px}.vc-chrome-saturation-wrap{border-radius:2px 2px 0 0;overflow:hidden;padding-bottom:55%;position:relative;width:100%}.vc-chrome-saturation-wrap .vc-saturation-circle{height:12px;width:12px}.vc-chrome-fields .vc-input__input{border:none;border-radius:2px;box-shadow:inset 0 0 0 1px #dadada;color:#333;font-size:11px;height:21px;text-align:center;width:100%}.vc-chrome-fields .vc-input__label{color:#969696;display:block;font-size:11px;line-height:11px;margin-top:12px;text-align:center;text-transform:uppercase}.vc-chrome__disable-alpha .vc-chrome-active-color{height:18px;width:18px}.vc-chrome__disable-alpha .vc-chrome-color-wrap{width:30px}.vc-chrome__disable-alpha .vc-chrome-hue-wrap{margin-bottom:4px;margin-top:4px}";
styleInject(css_248z);
script.render = render;
script.__file = "src/components/chrome/chrome.vue";
script.install = install;
register(t1);
const _hoisted_1 = ["aria-label"];
const _hoisted_2 = {
  key: 0,
  class: "color-picker__simple"
};
const _hoisted_3 = ["aria-label", "name", "checked", "onClick"];
const _hoisted_4 = ["title"];
const _hoisted_5 = ["aria-label", "name", "checked"];
const _hoisted_6 = {
  key: 0,
  class: "color-picker__navigation"
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcColorPicker",
  props: /* @__PURE__ */ mergeModels({
    advancedFields: { type: Boolean },
    clearable: { type: Boolean },
    container: { default: "body" },
    palette: { default: () => [] },
    paletteOnly: { type: Boolean }
  }, {
    "modelValue": { required: true },
    "modelModifiers": {},
    "open": { type: Boolean },
    "openModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["submit", "closed"], ["update:modelValue", "update:open"]),
  setup(__props, { emit: __emit }) {
    const currentColor = useModel(__props, "modelValue");
    const open = useModel(__props, "open");
    const props = __props;
    const emit = __emit;
    const HEX_REGEX = /^#([a-f0-9]{3}|[a-f0-9]{6})$/i;
    const id = createElementId();
    const advanced = ref(false);
    const normalizedPalette = computed(() => {
      let palette = props.palette;
      for (const color of palette) {
        if (typeof color === "string" && !color.match(HEX_REGEX) || typeof color === "object" && !color.color?.match(HEX_REGEX)) {
          logger.error("[NcColorPicker] Invalid palette passed", { color });
          palette = [];
          break;
        }
      }
      if (palette.length === 0) {
        palette = props.clearable ? [...defaultPalette, COLOR_BLACK, COLOR_WHITE] : [...defaultPalette];
      }
      return palette.map((item) => ({
        color: typeof item === "object" ? item.color : item,
        name: typeof item === "object" && item.name ? item.name : t("A color with a HEX value {hex}", { hex: typeof item === "string" ? item : item.color })
      }));
    });
    function handleConfirm(hideCallback) {
      emit("submit", currentColor.value);
      hideCallback();
      advanced.value = false;
    }
    function toggleColor(color) {
      color = typeof color === "string" ? color : color.color;
      if (props.clearable && currentColor.value === color) {
        currentColor.value = void 0;
      } else {
        currentColor.value = color;
      }
    }
    function pickCustomColor(color) {
      currentColor.value = color.hex;
    }
    function getContrastColor(color) {
      return calculateLuma(color) > 0.5 ? COLOR_BLACK.color : COLOR_WHITE.color;
    }
    function calculateLuma(color) {
      const [red, green, blue] = hexToRGB(color);
      return (0.2126 * red + 0.7152 * green + 0.0722 * blue) / 255;
    }
    function hexToRGB(hex) {
      const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      if (!result) {
        return [0, 0, 0];
      }
      return [parseInt(result[1], 16), parseInt(result[2], 16), parseInt(result[3], 16)];
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcPopover), {
        shown: open.value,
        "onUpdate:shown": _cache[3] || (_cache[3] = ($event) => open.value = $event),
        container: _ctx.container,
        popupRole: "dialog",
        onApplyHide: _cache[4] || (_cache[4] = ($event) => emit("closed"))
      }, {
        trigger: withCtx((slotProps) => [
          renderSlot(_ctx.$slots, "default", normalizeProps(guardReactiveProps(slotProps)), void 0, true)
        ]),
        default: withCtx((slotProps) => [
          createBaseVNode("div", {
            role: "dialog",
            class: normalizeClass(["color-picker", {
              "color-picker--advanced-fields": advanced.value && _ctx.advancedFields,
              "color-picker--clearable": _ctx.clearable
            }]),
            "aria-modal": "true",
            "aria-label": unref(t)("Color picker")
          }, [
            createVNode(Transition, {
              name: "slide",
              mode: "out-in"
            }, {
              default: withCtx(() => [
                !advanced.value ? (openBlock(), createElementBlock("div", _hoisted_2, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList(normalizedPalette.value, ({ color, name }, index) => {
                    return openBlock(), createElementBlock("label", {
                      key: index,
                      class: normalizeClass(["color-picker__simple-color-circle", { "color-picker__simple-color-circle--active": color === currentColor.value }]),
                      style: normalizeStyle({
                        backgroundColor: color,
                        color: getContrastColor(color)
                      })
                    }, [
                      color === currentColor.value ? (openBlock(), createBlock(unref(NcIconSvgWrapper), {
                        key: 0,
                        path: unref(mdiCheck)
                      }, null, 8, ["path"])) : createCommentVNode("", true),
                      createBaseVNode("input", {
                        type: "radio",
                        class: "hidden-visually",
                        "aria-label": name,
                        name: `color-picker-${unref(id)}`,
                        checked: color === currentColor.value,
                        onClick: ($event) => toggleColor(color)
                      }, null, 8, _hoisted_3)
                    ], 6);
                  }), 128)),
                  _ctx.clearable ? (openBlock(), createElementBlock("label", {
                    key: 0,
                    class: "color-picker__clear",
                    title: unref(t)("No color")
                  }, [
                    createVNode(unref(NcIconSvgWrapper), {
                      size: currentColor.value ? 28 : 34,
                      path: unref(mdiCloseCircleOutline)
                    }, null, 8, ["size", "path"]),
                    createBaseVNode("input", {
                      type: "radio",
                      class: "hidden-visually",
                      "aria-label": unref(t)("No color"),
                      name: `color-picker-${unref(id)}`,
                      checked: !currentColor.value,
                      onClick: _cache[0] || (_cache[0] = ($event) => currentColor.value = void 0)
                    }, null, 8, _hoisted_5)
                  ], 8, _hoisted_4)) : createCommentVNode("", true)
                ])) : (openBlock(), createBlock(unref(script), {
                  key: 1,
                  class: "color-picker__advanced",
                  disableAlpha: "",
                  disableFields: !_ctx.advancedFields,
                  modelValue: currentColor.value ?? "#000000",
                  "onUpdate:modelValue": pickCustomColor
                }, null, 8, ["disableFields", "modelValue"]))
              ]),
              _: 1
            }),
            !_ctx.paletteOnly ? (openBlock(), createElementBlock("div", _hoisted_6, [
              advanced.value ? (openBlock(), createBlock(unref(NcButton), {
                key: 0,
                "aria-label": unref(t)("Back"),
                title: unref(t)("Back"),
                variant: "tertiary",
                onClick: _cache[1] || (_cache[1] = ($event) => advanced.value = false)
              }, {
                icon: withCtx(() => [
                  createVNode(unref(NcIconSvgWrapper), {
                    directional: "",
                    path: unref(mdiArrowLeft)
                  }, null, 8, ["path"])
                ]),
                _: 1
              }, 8, ["aria-label", "title"])) : (openBlock(), createBlock(unref(NcButton), {
                key: 1,
                "aria-label": unref(t)("More options"),
                title: unref(t)("More options"),
                variant: "tertiary",
                onClick: _cache[2] || (_cache[2] = ($event) => advanced.value = true)
              }, {
                icon: withCtx(() => [
                  createVNode(unref(NcIconSvgWrapper), { path: unref(mdiDotsHorizontal) }, null, 8, ["path"])
                ]),
                _: 1
              }, 8, ["aria-label", "title"])),
              createVNode(unref(NcButton), {
                variant: "primary",
                onClick: ($event) => handleConfirm(slotProps.hide)
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(unref(t)("Choose")), 1)
                ]),
                _: 2
              }, 1032, ["onClick"])
            ])) : createCommentVNode("", true)
          ], 10, _hoisted_1)
        ]),
        _: 3
      }, 8, ["shown", "container"]);
    };
  }
});
const NcColorPicker = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-45e1396f"]]);
export {
  NcColorPicker as default
};
//# sourceMappingURL=index-DD39fp6M.chunk.mjs.map
