const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { t as process$1 } from "./index-rAufP352.chunk.mjs";
import { af as global } from "./preload-helper-xAe3EUYB.chunk.mjs";
var define_process_env_default = {};
/*! For license information please see index.js.LICENSE.txt */
var t = { 2: (t2) => {
  function e2(t3, e3, o2) {
    t3 instanceof RegExp && (t3 = n2(t3, o2)), e3 instanceof RegExp && (e3 = n2(e3, o2));
    var i2 = r2(t3, e3, o2);
    return i2 && { start: i2[0], end: i2[1], pre: o2.slice(0, i2[0]), body: o2.slice(i2[0] + t3.length, i2[1]), post: o2.slice(i2[1] + e3.length) };
  }
  function n2(t3, e3) {
    var n3 = e3.match(t3);
    return n3 ? n3[0] : null;
  }
  function r2(t3, e3, n3) {
    var r3, o2, i2, s2, a2, u2 = n3.indexOf(t3), l2 = n3.indexOf(e3, u2 + 1), c2 = u2;
    if (u2 >= 0 && l2 > 0) {
      for (r3 = [], i2 = n3.length; c2 >= 0 && !a2; ) c2 == u2 ? (r3.push(c2), u2 = n3.indexOf(t3, c2 + 1)) : 1 == r3.length ? a2 = [r3.pop(), l2] : ((o2 = r3.pop()) < i2 && (i2 = o2, s2 = l2), l2 = n3.indexOf(e3, c2 + 1)), c2 = u2 < l2 && u2 >= 0 ? u2 : l2;
      r3.length && (a2 = [i2, s2]);
    }
    return a2;
  }
  t2.exports = e2, e2.range = r2;
}, 47: (t2, e2, n2) => {
  var r2 = n2(410), o2 = function(t3) {
    return "string" == typeof t3;
  };
  function i2(t3, e3) {
    for (var n3 = [], r3 = 0; r3 < t3.length; r3++) {
      var o3 = t3[r3];
      o3 && "." !== o3 && (".." === o3 ? n3.length && ".." !== n3[n3.length - 1] ? n3.pop() : e3 && n3.push("..") : n3.push(o3));
    }
    return n3;
  }
  var s2 = /^(\/?|)([\s\S]*?)((?:\.{1,2}|[^\/]+?|)(\.[^.\/]*|))(?:[\/]*)$/, a2 = {};
  function u2(t3) {
    return s2.exec(t3).slice(1);
  }
  a2.resolve = function() {
    for (var t3 = "", e3 = false, n3 = arguments.length - 1; n3 >= -1 && !e3; n3--) {
      var r3 = n3 >= 0 ? arguments[n3] : process$1.cwd();
      if (!o2(r3)) throw new TypeError("Arguments to path.resolve must be strings");
      r3 && (t3 = r3 + "/" + t3, e3 = "/" === r3.charAt(0));
    }
    return (e3 ? "/" : "") + (t3 = i2(t3.split("/"), !e3).join("/")) || ".";
  }, a2.normalize = function(t3) {
    var e3 = a2.isAbsolute(t3), n3 = "/" === t3.substr(-1);
    return (t3 = i2(t3.split("/"), !e3).join("/")) || e3 || (t3 = "."), t3 && n3 && (t3 += "/"), (e3 ? "/" : "") + t3;
  }, a2.isAbsolute = function(t3) {
    return "/" === t3.charAt(0);
  }, a2.join = function() {
    for (var t3 = "", e3 = 0; e3 < arguments.length; e3++) {
      var n3 = arguments[e3];
      if (!o2(n3)) throw new TypeError("Arguments to path.join must be strings");
      n3 && (t3 += t3 ? "/" + n3 : n3);
    }
    return a2.normalize(t3);
  }, a2.relative = function(t3, e3) {
    function n3(t4) {
      for (var e4 = 0; e4 < t4.length && "" === t4[e4]; e4++) ;
      for (var n4 = t4.length - 1; n4 >= 0 && "" === t4[n4]; n4--) ;
      return e4 > n4 ? [] : t4.slice(e4, n4 + 1);
    }
    t3 = a2.resolve(t3).substr(1), e3 = a2.resolve(e3).substr(1);
    for (var r3 = n3(t3.split("/")), o3 = n3(e3.split("/")), i3 = Math.min(r3.length, o3.length), s3 = i3, u3 = 0; u3 < i3; u3++) if (r3[u3] !== o3[u3]) {
      s3 = u3;
      break;
    }
    var l2 = [];
    for (u3 = s3; u3 < r3.length; u3++) l2.push("..");
    return (l2 = l2.concat(o3.slice(s3))).join("/");
  }, a2._makeLong = function(t3) {
    return t3;
  }, a2.dirname = function(t3) {
    var e3 = u2(t3), n3 = e3[0], r3 = e3[1];
    return n3 || r3 ? (r3 && (r3 = r3.substr(0, r3.length - 1)), n3 + r3) : ".";
  }, a2.basename = function(t3, e3) {
    var n3 = u2(t3)[2];
    return e3 && n3.substr(-1 * e3.length) === e3 && (n3 = n3.substr(0, n3.length - e3.length)), n3;
  }, a2.extname = function(t3) {
    return u2(t3)[3];
  }, a2.format = function(t3) {
    if (!r2.isObject(t3)) throw new TypeError("Parameter 'pathObject' must be an object, not " + typeof t3);
    var e3 = t3.root || "";
    if (!o2(e3)) throw new TypeError("'pathObject.root' must be a string or undefined, not " + typeof t3.root);
    return (t3.dir ? t3.dir + a2.sep : "") + (t3.base || "");
  }, a2.parse = function(t3) {
    if (!o2(t3)) throw new TypeError("Parameter 'pathString' must be a string, not " + typeof t3);
    var e3 = u2(t3);
    if (!e3 || 4 !== e3.length) throw new TypeError("Invalid path '" + t3 + "'");
    return e3[1] = e3[1] || "", e3[2] = e3[2] || "", e3[3] = e3[3] || "", { root: e3[0], dir: e3[0] + e3[1].slice(0, e3[1].length - 1), base: e3[2], ext: e3[3], name: e3[2].slice(0, e3[2].length - e3[3].length) };
  }, a2.sep = "/", a2.delimiter = ":", t2.exports = a2;
}, 101: function(t2, e2, n2) {
  var r2;
  t2 = n2.nmd(t2), (function() {
    var o2 = (t2 && t2.exports, "object" == typeof global && global);
    o2.global !== o2 && o2.window;
    var i2 = function(t3) {
      this.message = t3;
    };
    (i2.prototype = new Error()).name = "InvalidCharacterError";
    var s2 = function(t3) {
      throw new i2(t3);
    }, a2 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", u2 = /[\t\n\f\r ]/g, l2 = { encode: function(t3) {
      t3 = String(t3), /[^\0-\xFF]/.test(t3) && s2("The string to be encoded contains characters outside of the Latin1 range.");
      for (var e3, n3, r3, o3, i3 = t3.length % 3, u3 = "", l3 = -1, c2 = t3.length - i3; ++l3 < c2; ) e3 = t3.charCodeAt(l3) << 16, n3 = t3.charCodeAt(++l3) << 8, r3 = t3.charCodeAt(++l3), u3 += a2.charAt((o3 = e3 + n3 + r3) >> 18 & 63) + a2.charAt(o3 >> 12 & 63) + a2.charAt(o3 >> 6 & 63) + a2.charAt(63 & o3);
      return 2 == i3 ? (e3 = t3.charCodeAt(l3) << 8, n3 = t3.charCodeAt(++l3), u3 += a2.charAt((o3 = e3 + n3) >> 10) + a2.charAt(o3 >> 4 & 63) + a2.charAt(o3 << 2 & 63) + "=") : 1 == i3 && (o3 = t3.charCodeAt(l3), u3 += a2.charAt(o3 >> 2) + a2.charAt(o3 << 4 & 63) + "=="), u3;
    }, decode: function(t3) {
      var e3 = (t3 = String(t3).replace(u2, "")).length;
      e3 % 4 == 0 && (e3 = (t3 = t3.replace(/==?$/, "")).length), (e3 % 4 == 1 || /[^+a-zA-Z0-9/]/.test(t3)) && s2("Invalid character: the string to be decoded is not correctly encoded.");
      for (var n3, r3, o3 = 0, i3 = "", l3 = -1; ++l3 < e3; ) r3 = a2.indexOf(t3.charAt(l3)), n3 = o3 % 4 ? 64 * n3 + r3 : r3, o3++ % 4 && (i3 += String.fromCharCode(255 & n3 >> (-2 * o3 & 6)));
      return i3;
    }, version: "1.0.0" };
    void 0 === (r2 = function() {
      return l2;
    }.call(e2, n2, e2, t2)) || (t2.exports = r2);
  })();
}, 135: (t2) => {
  function e2(t3) {
    return !!t3.constructor && "function" == typeof t3.constructor.isBuffer && t3.constructor.isBuffer(t3);
  }
  t2.exports = function(t3) {
    return null != t3 && (e2(t3) || (function(t4) {
      return "function" == typeof t4.readFloatLE && "function" == typeof t4.slice && e2(t4.slice(0, 0));
    })(t3) || !!t3._isBuffer);
  };
}, 172: (t2, e2) => {
  e2.d = function(t3) {
    if (!t3) return 0;
    for (var e3 = (t3 = t3.toString()).length, n2 = t3.length; n2--; ) {
      var r2 = t3.charCodeAt(n2);
      56320 <= r2 && r2 <= 57343 && n2--, 127 < r2 && r2 <= 2047 ? e3++ : 2047 < r2 && r2 <= 65535 && (e3 += 2);
    }
    return e3;
  };
}, 285: (t2, e2, n2) => {
  var r2 = n2(2);
  t2.exports = function(t3) {
    return t3 ? ("{}" === t3.substr(0, 2) && (t3 = "\\{\\}" + t3.substr(2)), m2((function(t4) {
      return t4.split("\\\\").join(o2).split("\\{").join(i2).split("\\}").join(s2).split("\\,").join(a2).split("\\.").join(u2);
    })(t3), true).map(c2)) : [];
  };
  var o2 = "\0SLASH" + Math.random() + "\0", i2 = "\0OPEN" + Math.random() + "\0", s2 = "\0CLOSE" + Math.random() + "\0", a2 = "\0COMMA" + Math.random() + "\0", u2 = "\0PERIOD" + Math.random() + "\0";
  function l2(t3) {
    return parseInt(t3, 10) == t3 ? parseInt(t3, 10) : t3.charCodeAt(0);
  }
  function c2(t3) {
    return t3.split(o2).join("\\").split(i2).join("{").split(s2).join("}").split(a2).join(",").split(u2).join(".");
  }
  function h2(t3) {
    if (!t3) return [""];
    var e3 = [], n3 = r2("{", "}", t3);
    if (!n3) return t3.split(",");
    var o3 = n3.pre, i3 = n3.body, s3 = n3.post, a3 = o3.split(",");
    a3[a3.length - 1] += "{" + i3 + "}";
    var u3 = h2(s3);
    return s3.length && (a3[a3.length - 1] += u3.shift(), a3.push.apply(a3, u3)), e3.push.apply(e3, a3), e3;
  }
  function p2(t3) {
    return "{" + t3 + "}";
  }
  function f2(t3) {
    return /^-?0\d/.test(t3);
  }
  function d2(t3, e3) {
    return t3 <= e3;
  }
  function g2(t3, e3) {
    return t3 >= e3;
  }
  function m2(t3, e3) {
    var n3 = [], o3 = r2("{", "}", t3);
    if (!o3) return [t3];
    var i3 = o3.pre, a3 = o3.post.length ? m2(o3.post, false) : [""];
    if (/\$$/.test(o3.pre)) for (var u3 = 0; u3 < a3.length; u3++) {
      var c3 = i3 + "{" + o3.body + "}" + a3[u3];
      n3.push(c3);
    }
    else {
      var y2, v2, b2 = /^-?\d+\.\.-?\d+(?:\.\.-?\d+)?$/.test(o3.body), w2 = /^[a-zA-Z]\.\.[a-zA-Z](?:\.\.-?\d+)?$/.test(o3.body), x2 = b2 || w2, E2 = o3.body.indexOf(",") >= 0;
      if (!x2 && !E2) return o3.post.match(/,(?!,).*\}/) ? m2(t3 = o3.pre + "{" + o3.body + s2 + o3.post) : [t3];
      if (x2) y2 = o3.body.split(/\.\./);
      else if (1 === (y2 = h2(o3.body)).length && 1 === (y2 = m2(y2[0], false).map(p2)).length) return a3.map((function(t4) {
        return o3.pre + y2[0] + t4;
      }));
      if (x2) {
        var N2 = l2(y2[0]), P2 = l2(y2[1]), A2 = Math.max(y2[0].length, y2[1].length), T2 = 3 == y2.length ? Math.abs(l2(y2[2])) : 1, O2 = d2;
        P2 < N2 && (T2 *= -1, O2 = g2);
        var S2 = y2.some(f2);
        v2 = [];
        for (var j2 = N2; O2(j2, P2); j2 += T2) {
          var I2;
          if (w2) "\\" === (I2 = String.fromCharCode(j2)) && (I2 = "");
          else if (I2 = String(j2), S2) {
            var $2 = A2 - I2.length;
            if ($2 > 0) {
              var C2 = new Array($2 + 1).join("0");
              I2 = j2 < 0 ? "-" + C2 + I2.slice(1) : C2 + I2;
            }
          }
          v2.push(I2);
        }
      } else {
        v2 = [];
        for (var R2 = 0; R2 < y2.length; R2++) v2.push.apply(v2, m2(y2[R2], false));
      }
      for (R2 = 0; R2 < v2.length; R2++) for (u3 = 0; u3 < a3.length; u3++) c3 = i3 + v2[R2] + a3[u3], (!e3 || x2 || c3) && n3.push(c3);
    }
    return n3;
  }
}, 298: (t2) => {
  var e2, n2;
  e2 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", n2 = { rotl: function(t3, e3) {
    return t3 << e3 | t3 >>> 32 - e3;
  }, rotr: function(t3, e3) {
    return t3 << 32 - e3 | t3 >>> e3;
  }, endian: function(t3) {
    if (t3.constructor == Number) return 16711935 & n2.rotl(t3, 8) | 4278255360 & n2.rotl(t3, 24);
    for (var e3 = 0; e3 < t3.length; e3++) t3[e3] = n2.endian(t3[e3]);
    return t3;
  }, randomBytes: function(t3) {
    for (var e3 = []; t3 > 0; t3--) e3.push(Math.floor(256 * Math.random()));
    return e3;
  }, bytesToWords: function(t3) {
    for (var e3 = [], n3 = 0, r2 = 0; n3 < t3.length; n3++, r2 += 8) e3[r2 >>> 5] |= t3[n3] << 24 - r2 % 32;
    return e3;
  }, wordsToBytes: function(t3) {
    for (var e3 = [], n3 = 0; n3 < 32 * t3.length; n3 += 8) e3.push(t3[n3 >>> 5] >>> 24 - n3 % 32 & 255);
    return e3;
  }, bytesToHex: function(t3) {
    for (var e3 = [], n3 = 0; n3 < t3.length; n3++) e3.push((t3[n3] >>> 4).toString(16)), e3.push((15 & t3[n3]).toString(16));
    return e3.join("");
  }, hexToBytes: function(t3) {
    for (var e3 = [], n3 = 0; n3 < t3.length; n3 += 2) e3.push(parseInt(t3.substr(n3, 2), 16));
    return e3;
  }, bytesToBase64: function(t3) {
    for (var n3 = [], r2 = 0; r2 < t3.length; r2 += 3) for (var o2 = t3[r2] << 16 | t3[r2 + 1] << 8 | t3[r2 + 2], i2 = 0; i2 < 4; i2++) 8 * r2 + 6 * i2 <= 8 * t3.length ? n3.push(e2.charAt(o2 >>> 6 * (3 - i2) & 63)) : n3.push("=");
    return n3.join("");
  }, base64ToBytes: function(t3) {
    t3 = t3.replace(/[^A-Z0-9+\/]/gi, "");
    for (var n3 = [], r2 = 0, o2 = 0; r2 < t3.length; o2 = ++r2 % 4) 0 != o2 && n3.push((e2.indexOf(t3.charAt(r2 - 1)) & Math.pow(2, -2 * o2 + 8) - 1) << 2 * o2 | e2.indexOf(t3.charAt(r2)) >>> 6 - 2 * o2);
    return n3;
  } }, t2.exports = n2;
}, 345: () => {
}, 388: () => {
}, 410: () => {
}, 526: (t2) => {
  var e2 = { utf8: { stringToBytes: function(t3) {
    return e2.bin.stringToBytes(unescape(encodeURIComponent(t3)));
  }, bytesToString: function(t3) {
    return decodeURIComponent(escape(e2.bin.bytesToString(t3)));
  } }, bin: { stringToBytes: function(t3) {
    for (var e3 = [], n2 = 0; n2 < t3.length; n2++) e3.push(255 & t3.charCodeAt(n2));
    return e3;
  }, bytesToString: function(t3) {
    for (var e3 = [], n2 = 0; n2 < t3.length; n2++) e3.push(String.fromCharCode(t3[n2]));
    return e3.join("");
  } } };
  t2.exports = e2;
}, 542: (t2, e2, n2) => {
  !(function() {
    var e3 = n2(298), r2 = n2(526).utf8, o2 = n2(135), i2 = n2(526).bin, s2 = function(t3, n3) {
      t3.constructor == String ? t3 = n3 && "binary" === n3.encoding ? i2.stringToBytes(t3) : r2.stringToBytes(t3) : o2(t3) ? t3 = Array.prototype.slice.call(t3, 0) : Array.isArray(t3) || t3.constructor === Uint8Array || (t3 = t3.toString());
      for (var a2 = e3.bytesToWords(t3), u2 = 8 * t3.length, l2 = 1732584193, c2 = -271733879, h2 = -1732584194, p2 = 271733878, f2 = 0; f2 < a2.length; f2++) a2[f2] = 16711935 & (a2[f2] << 8 | a2[f2] >>> 24) | 4278255360 & (a2[f2] << 24 | a2[f2] >>> 8);
      a2[u2 >>> 5] |= 128 << u2 % 32, a2[14 + (u2 + 64 >>> 9 << 4)] = u2;
      var d2 = s2._ff, g2 = s2._gg, m2 = s2._hh, y2 = s2._ii;
      for (f2 = 0; f2 < a2.length; f2 += 16) {
        var v2 = l2, b2 = c2, w2 = h2, x2 = p2;
        l2 = d2(l2, c2, h2, p2, a2[f2 + 0], 7, -680876936), p2 = d2(p2, l2, c2, h2, a2[f2 + 1], 12, -389564586), h2 = d2(h2, p2, l2, c2, a2[f2 + 2], 17, 606105819), c2 = d2(c2, h2, p2, l2, a2[f2 + 3], 22, -1044525330), l2 = d2(l2, c2, h2, p2, a2[f2 + 4], 7, -176418897), p2 = d2(p2, l2, c2, h2, a2[f2 + 5], 12, 1200080426), h2 = d2(h2, p2, l2, c2, a2[f2 + 6], 17, -1473231341), c2 = d2(c2, h2, p2, l2, a2[f2 + 7], 22, -45705983), l2 = d2(l2, c2, h2, p2, a2[f2 + 8], 7, 1770035416), p2 = d2(p2, l2, c2, h2, a2[f2 + 9], 12, -1958414417), h2 = d2(h2, p2, l2, c2, a2[f2 + 10], 17, -42063), c2 = d2(c2, h2, p2, l2, a2[f2 + 11], 22, -1990404162), l2 = d2(l2, c2, h2, p2, a2[f2 + 12], 7, 1804603682), p2 = d2(p2, l2, c2, h2, a2[f2 + 13], 12, -40341101), h2 = d2(h2, p2, l2, c2, a2[f2 + 14], 17, -1502002290), l2 = g2(l2, c2 = d2(c2, h2, p2, l2, a2[f2 + 15], 22, 1236535329), h2, p2, a2[f2 + 1], 5, -165796510), p2 = g2(p2, l2, c2, h2, a2[f2 + 6], 9, -1069501632), h2 = g2(h2, p2, l2, c2, a2[f2 + 11], 14, 643717713), c2 = g2(c2, h2, p2, l2, a2[f2 + 0], 20, -373897302), l2 = g2(l2, c2, h2, p2, a2[f2 + 5], 5, -701558691), p2 = g2(p2, l2, c2, h2, a2[f2 + 10], 9, 38016083), h2 = g2(h2, p2, l2, c2, a2[f2 + 15], 14, -660478335), c2 = g2(c2, h2, p2, l2, a2[f2 + 4], 20, -405537848), l2 = g2(l2, c2, h2, p2, a2[f2 + 9], 5, 568446438), p2 = g2(p2, l2, c2, h2, a2[f2 + 14], 9, -1019803690), h2 = g2(h2, p2, l2, c2, a2[f2 + 3], 14, -187363961), c2 = g2(c2, h2, p2, l2, a2[f2 + 8], 20, 1163531501), l2 = g2(l2, c2, h2, p2, a2[f2 + 13], 5, -1444681467), p2 = g2(p2, l2, c2, h2, a2[f2 + 2], 9, -51403784), h2 = g2(h2, p2, l2, c2, a2[f2 + 7], 14, 1735328473), l2 = m2(l2, c2 = g2(c2, h2, p2, l2, a2[f2 + 12], 20, -1926607734), h2, p2, a2[f2 + 5], 4, -378558), p2 = m2(p2, l2, c2, h2, a2[f2 + 8], 11, -2022574463), h2 = m2(h2, p2, l2, c2, a2[f2 + 11], 16, 1839030562), c2 = m2(c2, h2, p2, l2, a2[f2 + 14], 23, -35309556), l2 = m2(l2, c2, h2, p2, a2[f2 + 1], 4, -1530992060), p2 = m2(p2, l2, c2, h2, a2[f2 + 4], 11, 1272893353), h2 = m2(h2, p2, l2, c2, a2[f2 + 7], 16, -155497632), c2 = m2(c2, h2, p2, l2, a2[f2 + 10], 23, -1094730640), l2 = m2(l2, c2, h2, p2, a2[f2 + 13], 4, 681279174), p2 = m2(p2, l2, c2, h2, a2[f2 + 0], 11, -358537222), h2 = m2(h2, p2, l2, c2, a2[f2 + 3], 16, -722521979), c2 = m2(c2, h2, p2, l2, a2[f2 + 6], 23, 76029189), l2 = m2(l2, c2, h2, p2, a2[f2 + 9], 4, -640364487), p2 = m2(p2, l2, c2, h2, a2[f2 + 12], 11, -421815835), h2 = m2(h2, p2, l2, c2, a2[f2 + 15], 16, 530742520), l2 = y2(l2, c2 = m2(c2, h2, p2, l2, a2[f2 + 2], 23, -995338651), h2, p2, a2[f2 + 0], 6, -198630844), p2 = y2(p2, l2, c2, h2, a2[f2 + 7], 10, 1126891415), h2 = y2(h2, p2, l2, c2, a2[f2 + 14], 15, -1416354905), c2 = y2(c2, h2, p2, l2, a2[f2 + 5], 21, -57434055), l2 = y2(l2, c2, h2, p2, a2[f2 + 12], 6, 1700485571), p2 = y2(p2, l2, c2, h2, a2[f2 + 3], 10, -1894986606), h2 = y2(h2, p2, l2, c2, a2[f2 + 10], 15, -1051523), c2 = y2(c2, h2, p2, l2, a2[f2 + 1], 21, -2054922799), l2 = y2(l2, c2, h2, p2, a2[f2 + 8], 6, 1873313359), p2 = y2(p2, l2, c2, h2, a2[f2 + 15], 10, -30611744), h2 = y2(h2, p2, l2, c2, a2[f2 + 6], 15, -1560198380), c2 = y2(c2, h2, p2, l2, a2[f2 + 13], 21, 1309151649), l2 = y2(l2, c2, h2, p2, a2[f2 + 4], 6, -145523070), p2 = y2(p2, l2, c2, h2, a2[f2 + 11], 10, -1120210379), h2 = y2(h2, p2, l2, c2, a2[f2 + 2], 15, 718787259), c2 = y2(c2, h2, p2, l2, a2[f2 + 9], 21, -343485551), l2 = l2 + v2 >>> 0, c2 = c2 + b2 >>> 0, h2 = h2 + w2 >>> 0, p2 = p2 + x2 >>> 0;
      }
      return e3.endian([l2, c2, h2, p2]);
    };
    s2._ff = function(t3, e4, n3, r3, o3, i3, s3) {
      var a2 = t3 + (e4 & n3 | ~e4 & r3) + (o3 >>> 0) + s3;
      return (a2 << i3 | a2 >>> 32 - i3) + e4;
    }, s2._gg = function(t3, e4, n3, r3, o3, i3, s3) {
      var a2 = t3 + (e4 & r3 | n3 & ~r3) + (o3 >>> 0) + s3;
      return (a2 << i3 | a2 >>> 32 - i3) + e4;
    }, s2._hh = function(t3, e4, n3, r3, o3, i3, s3) {
      var a2 = t3 + (e4 ^ n3 ^ r3) + (o3 >>> 0) + s3;
      return (a2 << i3 | a2 >>> 32 - i3) + e4;
    }, s2._ii = function(t3, e4, n3, r3, o3, i3, s3) {
      var a2 = t3 + (n3 ^ (e4 | ~r3)) + (o3 >>> 0) + s3;
      return (a2 << i3 | a2 >>> 32 - i3) + e4;
    }, s2._blocksize = 16, s2._digestsize = 16, t2.exports = function(t3, n3) {
      if (null == t3) throw new Error("Illegal argument " + t3);
      var r3 = e3.wordsToBytes(s2(t3, n3));
      return n3 && n3.asBytes ? r3 : n3 && n3.asString ? i2.bytesToString(r3) : e3.bytesToHex(r3);
    };
  })();
}, 647: (t2, e2) => {
  var n2 = Object.prototype.hasOwnProperty;
  function r2(t3) {
    try {
      return decodeURIComponent(t3.replace(/\+/g, " "));
    } catch (t4) {
      return null;
    }
  }
  function o2(t3) {
    try {
      return encodeURIComponent(t3);
    } catch (t4) {
      return null;
    }
  }
  e2.stringify = function(t3, e3) {
    e3 = e3 || "";
    var r3, i2, s2 = [];
    for (i2 in "string" != typeof e3 && (e3 = "?"), t3) if (n2.call(t3, i2)) {
      if ((r3 = t3[i2]) || null != r3 && !isNaN(r3) || (r3 = ""), i2 = o2(i2), r3 = o2(r3), null === i2 || null === r3) continue;
      s2.push(i2 + "=" + r3);
    }
    return s2.length ? e3 + s2.join("&") : "";
  }, e2.parse = function(t3) {
    for (var e3, n3 = /([^=?#&]+)=?([^&]*)/g, o3 = {}; e3 = n3.exec(t3); ) {
      var i2 = r2(e3[1]), s2 = r2(e3[2]);
      null === i2 || null === s2 || i2 in o3 || (o3[i2] = s2);
    }
    return o3;
  };
}, 670: (t2) => {
  t2.exports = function(t3, e2) {
    if (e2 = e2.split(":")[0], !(t3 = +t3)) return false;
    switch (e2) {
      case "http":
      case "ws":
        return 80 !== t3;
      case "https":
      case "wss":
        return 443 !== t3;
      case "ftp":
        return 21 !== t3;
      case "gopher":
        return 70 !== t3;
      case "file":
        return false;
    }
    return 0 !== t3;
  };
}, 737: (t2, e2, n2) => {
  var r2 = n2(670), o2 = n2(647), i2 = /^[\x00-\x20\u00a0\u1680\u2000-\u200a\u2028\u2029\u202f\u205f\u3000\ufeff]+/, s2 = /[\n\r\t]/g, a2 = /^[A-Za-z][A-Za-z0-9+-.]*:\/\//, u2 = /:\d+$/, l2 = /^([a-z][a-z0-9.+-]*:)?(\/\/)?([\\/]+)?([\S\s]*)/i, c2 = /^[a-zA-Z]:/;
  function h2(t3) {
    return (t3 || "").toString().replace(i2, "");
  }
  var p2 = [["#", "hash"], ["?", "query"], function(t3, e3) {
    return g2(e3.protocol) ? t3.replace(/\\/g, "/") : t3;
  }, ["/", "pathname"], ["@", "auth", 1], [NaN, "host", void 0, 1, 1], [/:(\d*)$/, "port", void 0, 1], [NaN, "hostname", void 0, 1, 1]], f2 = { hash: 1, query: 1 };
  function d2(t3) {
    var e3, n3 = ("undefined" != typeof window ? window : "undefined" != typeof global ? global : "undefined" != typeof self ? self : {}).location || {}, r3 = {}, o3 = typeof (t3 = t3 || n3);
    if ("blob:" === t3.protocol) r3 = new y2(unescape(t3.pathname), {});
    else if ("string" === o3) for (e3 in r3 = new y2(t3, {}), f2) delete r3[e3];
    else if ("object" === o3) {
      for (e3 in t3) e3 in f2 || (r3[e3] = t3[e3]);
      void 0 === r3.slashes && (r3.slashes = a2.test(t3.href));
    }
    return r3;
  }
  function g2(t3) {
    return "file:" === t3 || "ftp:" === t3 || "http:" === t3 || "https:" === t3 || "ws:" === t3 || "wss:" === t3;
  }
  function m2(t3, e3) {
    t3 = (t3 = h2(t3)).replace(s2, ""), e3 = e3 || {};
    var n3, r3 = l2.exec(t3), o3 = r3[1] ? r3[1].toLowerCase() : "", i3 = !!r3[2], a3 = !!r3[3], u3 = 0;
    return i3 ? a3 ? (n3 = r3[2] + r3[3] + r3[4], u3 = r3[2].length + r3[3].length) : (n3 = r3[2] + r3[4], u3 = r3[2].length) : a3 ? (n3 = r3[3] + r3[4], u3 = r3[3].length) : n3 = r3[4], "file:" === o3 ? u3 >= 2 && (n3 = n3.slice(2)) : g2(o3) ? n3 = r3[4] : o3 ? i3 && (n3 = n3.slice(2)) : u3 >= 2 && g2(e3.protocol) && (n3 = r3[4]), { protocol: o3, slashes: i3 || g2(o3), slashesCount: u3, rest: n3 };
  }
  function y2(t3, e3, n3) {
    if (t3 = (t3 = h2(t3)).replace(s2, ""), !(this instanceof y2)) return new y2(t3, e3, n3);
    var i3, a3, u3, l3, f3, v2, b2 = p2.slice(), w2 = typeof e3, x2 = this, E2 = 0;
    for ("object" !== w2 && "string" !== w2 && (n3 = e3, e3 = null), n3 && "function" != typeof n3 && (n3 = o2.parse), i3 = !(a3 = m2(t3 || "", e3 = d2(e3))).protocol && !a3.slashes, x2.slashes = a3.slashes || i3 && e3.slashes, x2.protocol = a3.protocol || e3.protocol || "", t3 = a3.rest, ("file:" === a3.protocol && (2 !== a3.slashesCount || c2.test(t3)) || !a3.slashes && (a3.protocol || a3.slashesCount < 2 || !g2(x2.protocol))) && (b2[3] = [/(.*)/, "pathname"]); E2 < b2.length; E2++) "function" != typeof (l3 = b2[E2]) ? (u3 = l3[0], v2 = l3[1], u3 != u3 ? x2[v2] = t3 : "string" == typeof u3 ? ~(f3 = "@" === u3 ? t3.lastIndexOf(u3) : t3.indexOf(u3)) && ("number" == typeof l3[2] ? (x2[v2] = t3.slice(0, f3), t3 = t3.slice(f3 + l3[2])) : (x2[v2] = t3.slice(f3), t3 = t3.slice(0, f3))) : (f3 = u3.exec(t3)) && (x2[v2] = f3[1], t3 = t3.slice(0, f3.index)), x2[v2] = x2[v2] || i3 && l3[3] && e3[v2] || "", l3[4] && (x2[v2] = x2[v2].toLowerCase())) : t3 = l3(t3, x2);
    n3 && (x2.query = n3(x2.query)), i3 && e3.slashes && "/" !== x2.pathname.charAt(0) && ("" !== x2.pathname || "" !== e3.pathname) && (x2.pathname = (function(t4, e4) {
      if ("" === t4) return e4;
      for (var n4 = (e4 || "/").split("/").slice(0, -1).concat(t4.split("/")), r3 = n4.length, o3 = n4[r3 - 1], i4 = false, s3 = 0; r3--; ) "." === n4[r3] ? n4.splice(r3, 1) : ".." === n4[r3] ? (n4.splice(r3, 1), s3++) : s3 && (0 === r3 && (i4 = true), n4.splice(r3, 1), s3--);
      return i4 && n4.unshift(""), "." !== o3 && ".." !== o3 || n4.push(""), n4.join("/");
    })(x2.pathname, e3.pathname)), "/" !== x2.pathname.charAt(0) && g2(x2.protocol) && (x2.pathname = "/" + x2.pathname), r2(x2.port, x2.protocol) || (x2.host = x2.hostname, x2.port = ""), x2.username = x2.password = "", x2.auth && (~(f3 = x2.auth.indexOf(":")) ? (x2.username = x2.auth.slice(0, f3), x2.username = encodeURIComponent(decodeURIComponent(x2.username)), x2.password = x2.auth.slice(f3 + 1), x2.password = encodeURIComponent(decodeURIComponent(x2.password))) : x2.username = encodeURIComponent(decodeURIComponent(x2.auth)), x2.auth = x2.password ? x2.username + ":" + x2.password : x2.username), x2.origin = "file:" !== x2.protocol && g2(x2.protocol) && x2.host ? x2.protocol + "//" + x2.host : "null", x2.href = x2.toString();
  }
  y2.prototype = { set: function(t3, e3, n3) {
    var i3 = this;
    switch (t3) {
      case "query":
        "string" == typeof e3 && e3.length && (e3 = (n3 || o2.parse)(e3)), i3[t3] = e3;
        break;
      case "port":
        i3[t3] = e3, r2(e3, i3.protocol) ? e3 && (i3.host = i3.hostname + ":" + e3) : (i3.host = i3.hostname, i3[t3] = "");
        break;
      case "hostname":
        i3[t3] = e3, i3.port && (e3 += ":" + i3.port), i3.host = e3;
        break;
      case "host":
        i3[t3] = e3, u2.test(e3) ? (e3 = e3.split(":"), i3.port = e3.pop(), i3.hostname = e3.join(":")) : (i3.hostname = e3, i3.port = "");
        break;
      case "protocol":
        i3.protocol = e3.toLowerCase(), i3.slashes = !n3;
        break;
      case "pathname":
      case "hash":
        if (e3) {
          var s3 = "pathname" === t3 ? "/" : "#";
          i3[t3] = e3.charAt(0) !== s3 ? s3 + e3 : e3;
        } else i3[t3] = e3;
        break;
      case "username":
      case "password":
        i3[t3] = encodeURIComponent(e3);
        break;
      case "auth":
        var a3 = e3.indexOf(":");
        ~a3 ? (i3.username = e3.slice(0, a3), i3.username = encodeURIComponent(decodeURIComponent(i3.username)), i3.password = e3.slice(a3 + 1), i3.password = encodeURIComponent(decodeURIComponent(i3.password))) : i3.username = encodeURIComponent(decodeURIComponent(e3));
    }
    for (var l3 = 0; l3 < p2.length; l3++) {
      var c3 = p2[l3];
      c3[4] && (i3[c3[1]] = i3[c3[1]].toLowerCase());
    }
    return i3.auth = i3.password ? i3.username + ":" + i3.password : i3.username, i3.origin = "file:" !== i3.protocol && g2(i3.protocol) && i3.host ? i3.protocol + "//" + i3.host : "null", i3.href = i3.toString(), i3;
  }, toString: function(t3) {
    t3 && "function" == typeof t3 || (t3 = o2.stringify);
    var e3, n3 = this, r3 = n3.host, i3 = n3.protocol;
    i3 && ":" !== i3.charAt(i3.length - 1) && (i3 += ":");
    var s3 = i3 + (n3.protocol && n3.slashes || g2(n3.protocol) ? "//" : "");
    return n3.username ? (s3 += n3.username, n3.password && (s3 += ":" + n3.password), s3 += "@") : n3.password ? (s3 += ":" + n3.password, s3 += "@") : "file:" !== n3.protocol && g2(n3.protocol) && !r3 && "/" !== n3.pathname && (s3 += "@"), (":" === r3[r3.length - 1] || u2.test(n3.hostname) && !n3.port) && (r3 += ":"), s3 += r3 + n3.pathname, (e3 = "object" == typeof n3.query ? t3(n3.query) : n3.query) && (s3 += "?" !== e3.charAt(0) ? "?" + e3 : e3), n3.hash && (s3 += n3.hash), s3;
  } }, y2.extractProtocol = m2, y2.location = d2, y2.trimLeft = h2, y2.qs = o2, t2.exports = y2;
}, 800: () => {
}, 805: () => {
}, 829: (t2) => {
  function e2(t3) {
    return e2 = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(t4) {
      return typeof t4;
    } : function(t4) {
      return t4 && "function" == typeof Symbol && t4.constructor === Symbol && t4 !== Symbol.prototype ? "symbol" : typeof t4;
    }, e2(t3);
  }
  function n2(t3) {
    var e3 = "function" == typeof Map ? /* @__PURE__ */ new Map() : void 0;
    return n2 = function(t4) {
      if (null === t4 || (n3 = t4, -1 === Function.toString.call(n3).indexOf("[native code]"))) return t4;
      var n3;
      if ("function" != typeof t4) throw new TypeError("Super expression must either be null or a function");
      if (void 0 !== e3) {
        if (e3.has(t4)) return e3.get(t4);
        e3.set(t4, s3);
      }
      function s3() {
        return r2(t4, arguments, i2(this).constructor);
      }
      return s3.prototype = Object.create(t4.prototype, { constructor: { value: s3, enumerable: false, writable: true, configurable: true } }), o2(s3, t4);
    }, n2(t3);
  }
  function r2(t3, e3, n3) {
    return r2 = (function() {
      if ("undefined" == typeof Reflect || !Reflect.construct) return false;
      if (Reflect.construct.sham) return false;
      if ("function" == typeof Proxy) return true;
      try {
        return Date.prototype.toString.call(Reflect.construct(Date, [], (function() {
        }))), true;
      } catch (t4) {
        return false;
      }
    })() ? Reflect.construct : function(t4, e4, n4) {
      var r3 = [null];
      r3.push.apply(r3, e4);
      var i3 = new (Function.bind.apply(t4, r3))();
      return n4 && o2(i3, n4.prototype), i3;
    }, r2.apply(null, arguments);
  }
  function o2(t3, e3) {
    return o2 = Object.setPrototypeOf || function(t4, e4) {
      return t4.__proto__ = e4, t4;
    }, o2(t3, e3);
  }
  function i2(t3) {
    return i2 = Object.setPrototypeOf ? Object.getPrototypeOf : function(t4) {
      return t4.__proto__ || Object.getPrototypeOf(t4);
    }, i2(t3);
  }
  var s2 = (function(t3) {
    function n3(t4) {
      var r3;
      return (function(t5, e3) {
        if (!(t5 instanceof e3)) throw new TypeError("Cannot call a class as a function");
      })(this, n3), (r3 = (function(t5, n4) {
        return !n4 || "object" !== e2(n4) && "function" != typeof n4 ? (function(t6) {
          if (void 0 === t6) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
          return t6;
        })(t5) : n4;
      })(this, i2(n3).call(this, t4))).name = "ObjectPrototypeMutationError", r3;
    }
    return (function(t4, e3) {
      if ("function" != typeof e3 && null !== e3) throw new TypeError("Super expression must either be null or a function");
      t4.prototype = Object.create(e3 && e3.prototype, { constructor: { value: t4, writable: true, configurable: true } }), e3 && o2(t4, e3);
    })(n3, t3), n3;
  })(n2(Error));
  function a2(t3, n3) {
    for (var r3 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : function() {
    }, o3 = n3.split("."), i3 = o3.length, s3 = function(e3) {
      var n4 = o3[e3];
      if (!t3) return { v: void 0 };
      if ("+" === n4) {
        if (Array.isArray(t3)) return { v: t3.map((function(n5, i5) {
          var s4 = o3.slice(e3 + 1);
          return s4.length > 0 ? a2(n5, s4.join("."), r3) : r3(t3, i5, o3, e3);
        })) };
        var i4 = o3.slice(0, e3).join(".");
        throw new Error("Object at wildcard (".concat(i4, ") is not an array"));
      }
      t3 = r3(t3, n4, o3, e3);
    }, u3 = 0; u3 < i3; u3++) {
      var l2 = s3(u3);
      if ("object" === e2(l2)) return l2.v;
    }
    return t3;
  }
  function u2(t3, e3) {
    return t3.length === e3 + 1;
  }
  t2.exports = { set: function(t3, n3, r3) {
    if ("object" != e2(t3) || null === t3) return t3;
    if (void 0 === n3) return t3;
    if ("number" == typeof n3) return t3[n3] = r3, t3[n3];
    try {
      return a2(t3, n3, (function(t4, e3, n4, o3) {
        if (t4 === Reflect.getPrototypeOf({})) throw new s2("Attempting to mutate Object.prototype");
        if (!t4[e3]) {
          var i3 = Number.isInteger(Number(n4[o3 + 1])), a3 = "+" === n4[o3 + 1];
          t4[e3] = i3 || a3 ? [] : {};
        }
        return u2(n4, o3) && (t4[e3] = r3), t4[e3];
      }));
    } catch (e3) {
      if (e3 instanceof s2) throw e3;
      return t3;
    }
  }, get: function(t3, n3) {
    if ("object" != e2(t3) || null === t3) return t3;
    if (void 0 === n3) return t3;
    if ("number" == typeof n3) return t3[n3];
    try {
      return a2(t3, n3, (function(t4, e3) {
        return t4[e3];
      }));
    } catch (e3) {
      return t3;
    }
  }, has: function(t3, n3) {
    var r3 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
    if ("object" != e2(t3) || null === t3) return false;
    if (void 0 === n3) return false;
    if ("number" == typeof n3) return n3 in t3;
    try {
      var o3 = false;
      return a2(t3, n3, (function(t4, e3, n4, i3) {
        if (!u2(n4, i3)) return t4 && t4[e3];
        o3 = r3.own ? t4.hasOwnProperty(e3) : e3 in t4;
      })), o3;
    } catch (t4) {
      return false;
    }
  }, hasOwn: function(t3, e3, n3) {
    return this.has(t3, e3, n3 || { own: true });
  }, isIn: function(t3, n3, r3) {
    var o3 = arguments.length > 3 && void 0 !== arguments[3] ? arguments[3] : {};
    if ("object" != e2(t3) || null === t3) return false;
    if (void 0 === n3) return false;
    try {
      var i3 = false, s3 = false;
      return a2(t3, n3, (function(t4, n4, o4, a3) {
        return i3 = i3 || t4 === r3 || !!t4 && t4[n4] === r3, s3 = u2(o4, a3) && "object" === e2(t4) && n4 in t4, t4 && t4[n4];
      })), o3.validPath ? i3 && s3 : i3;
    } catch (t4) {
      return false;
    }
  }, ObjectPrototypeMutationError: s2 };
} }, e = {};
function n(r2) {
  var o2 = e[r2];
  if (void 0 !== o2) return o2.exports;
  var i2 = e[r2] = { id: r2, loaded: false, exports: {} };
  return t[r2].call(i2.exports, i2, i2.exports, n), i2.loaded = true, i2.exports;
}
n.n = (t2) => {
  var e2 = t2 && t2.__esModule ? () => t2.default : () => t2;
  return n.d(e2, { a: e2 }), e2;
}, n.d = (t2, e2) => {
  for (var r2 in e2) n.o(e2, r2) && !n.o(t2, r2) && Object.defineProperty(t2, r2, { enumerable: true, get: e2[r2] });
}, n.o = (t2, e2) => Object.prototype.hasOwnProperty.call(t2, e2), n.nmd = (t2) => (t2.paths = [], t2.children || (t2.children = []), t2);
var r = n(737), o = n.n(r);
function i(t2) {
  if (!s(t2)) throw new Error("Parameter was not an error");
}
function s(t2) {
  return !!t2 && "object" == typeof t2 && "[object Error]" === (e2 = t2, Object.prototype.toString.call(e2)) || t2 instanceof Error;
  var e2;
}
class a extends Error {
  constructor(t2, e2) {
    const n2 = [...arguments], { options: r2, shortMessage: o2 } = (function(t3) {
      let e3, n3 = "";
      if (0 === t3.length) e3 = {};
      else if (s(t3[0])) e3 = { cause: t3[0] }, n3 = t3.slice(1).join(" ") || "";
      else if (t3[0] && "object" == typeof t3[0]) e3 = Object.assign({}, t3[0]), n3 = t3.slice(1).join(" ") || "";
      else {
        if ("string" != typeof t3[0]) throw new Error("Invalid arguments passed to Layerr");
        e3 = {}, n3 = n3 = t3.join(" ") || "";
      }
      return { options: e3, shortMessage: n3 };
    })(n2);
    let i2 = o2;
    if (r2.cause && (i2 = `${i2}: ${r2.cause.message}`), super(i2), this.message = i2, r2.name && "string" == typeof r2.name ? this.name = r2.name : this.name = "Layerr", r2.cause && Object.defineProperty(this, "_cause", { value: r2.cause }), Object.defineProperty(this, "_info", { value: {} }), r2.info && "object" == typeof r2.info && Object.assign(this._info, r2.info), Error.captureStackTrace) {
      const t3 = r2.constructorOpt || this.constructor;
      Error.captureStackTrace(this, t3);
    }
  }
  static cause(t2) {
    return i(t2), t2._cause && s(t2._cause) ? t2._cause : null;
  }
  static fullStack(t2) {
    i(t2);
    const e2 = a.cause(t2);
    return e2 ? `${t2.stack}
caused by: ${a.fullStack(e2)}` : t2.stack ?? "";
  }
  static info(t2) {
    i(t2);
    const e2 = {}, n2 = a.cause(t2);
    return n2 && Object.assign(e2, a.info(n2)), t2._info && Object.assign(e2, t2._info), e2;
  }
  toString() {
    let t2 = this.name || this.constructor.name || this.constructor.prototype.name;
    return this.message && (t2 = `${t2}: ${this.message}`), t2;
  }
}
var u = n(47), l = n.n(u);
const c = "__PATH_SEPARATOR_POSIX__", h = "__PATH_SEPARATOR_WINDOWS__";
function p(t2) {
  try {
    const e2 = t2.replace(/\//g, c).replace(/\\\\/g, h);
    return encodeURIComponent(e2).split(h).join("\\\\").split(c).join("/");
  } catch (t3) {
    throw new a(t3, "Failed encoding path");
  }
}
function f(t2) {
  return t2.startsWith("/") ? t2 : "/" + t2;
}
function d(t2) {
  let e2 = t2;
  return "/" !== e2[0] && (e2 = "/" + e2), /^.+\/$/.test(e2) && (e2 = e2.substr(0, e2.length - 1)), e2;
}
function g(t2) {
  let e2 = new (o())(t2).pathname;
  return e2.length <= 0 && (e2 = "/"), d(e2);
}
function m() {
  for (var t2 = arguments.length, e2 = new Array(t2), n2 = 0; n2 < t2; n2++) e2[n2] = arguments[n2];
  return (function() {
    return (function(t3) {
      var e3 = [];
      if (0 === t3.length) return "";
      if ("string" != typeof t3[0]) throw new TypeError("Url must be a string. Received " + t3[0]);
      if (t3[0].match(/^[^/:]+:\/*$/) && t3.length > 1) {
        var n3 = t3.shift();
        t3[0] = n3 + t3[0];
      }
      t3[0].match(/^file:\/\/\//) ? t3[0] = t3[0].replace(/^([^/:]+):\/*/, "$1:///") : t3[0] = t3[0].replace(/^([^/:]+):\/*/, "$1://");
      for (var r2 = 0; r2 < t3.length; r2++) {
        var o2 = t3[r2];
        if ("string" != typeof o2) throw new TypeError("Url must be a string. Received " + o2);
        "" !== o2 && (r2 > 0 && (o2 = o2.replace(/^[\/]+/, "")), o2 = r2 < t3.length - 1 ? o2.replace(/[\/]+$/, "") : o2.replace(/[\/]+$/, "/"), e3.push(o2));
      }
      var i2 = e3.join("/"), s2 = (i2 = i2.replace(/\/(\?|&|#[^!])/g, "$1")).split("?");
      return s2.shift() + (s2.length > 0 ? "?" : "") + s2.join("&");
    })("object" == typeof arguments[0] ? arguments[0] : [].slice.call(arguments));
  })(e2.reduce(((t3, e3, n3) => ((0 === n3 || "/" !== e3 || "/" === e3 && "/" !== t3[t3.length - 1]) && t3.push(e3), t3)), []));
}
var y = n(542), v = n.n(y);
function b(t2, e2) {
  const n2 = t2.url.replace("//", ""), r2 = -1 == n2.indexOf("/") ? "/" : n2.slice(n2.indexOf("/")), o2 = t2.method ? t2.method.toUpperCase() : "GET", i2 = !!/(^|,)\s*auth\s*($|,)/.test(e2.qop) && "auth", s2 = `00000000${e2.nc}`.slice(-8), a2 = (function(t3, e3, n3, r3, o3, i3, s3) {
    const a3 = s3 || v()(`${e3}:${n3}:${r3}`);
    return t3 && "md5-sess" === t3.toLowerCase() ? v()(`${a3}:${o3}:${i3}`) : a3;
  })(e2.algorithm, e2.username, e2.realm, e2.password, e2.nonce, e2.cnonce, e2.ha1), u2 = v()(`${o2}:${r2}`), l2 = i2 ? v()(`${a2}:${e2.nonce}:${s2}:${e2.cnonce}:${i2}:${u2}`) : v()(`${a2}:${e2.nonce}:${u2}`), c2 = { username: e2.username, realm: e2.realm, nonce: e2.nonce, uri: r2, qop: i2, response: l2, nc: s2, cnonce: e2.cnonce, algorithm: e2.algorithm, opaque: e2.opaque }, h2 = [];
  for (const t3 in c2) c2[t3] && ("qop" === t3 || "nc" === t3 || "algorithm" === t3 ? h2.push(`${t3}=${c2[t3]}`) : h2.push(`${t3}="${c2[t3]}"`));
  return `Digest ${h2.join(", ")}`;
}
function w(t2) {
  return "digest" === (t2.headers && t2.headers.get("www-authenticate") || "").split(/\s/)[0].toLowerCase();
}
var x = n(101), E = n.n(x);
function N(t2) {
  return E().decode(t2);
}
function P(t2, e2) {
  var n2;
  return `Basic ${n2 = `${t2}:${e2}`, E().encode(n2)}`;
}
const A = "undefined" != typeof WorkerGlobalScope && self instanceof WorkerGlobalScope ? self : "undefined" != typeof window ? window : globalThis, T = A.fetch.bind(A);
let j = (function(t2) {
  return t2.Auto = "auto", t2.Digest = "digest", t2.None = "none", t2.Password = "password", t2.Token = "token", t2;
})({}), I = (function(t2) {
  return t2.DataTypeNoLength = "data-type-no-length", t2.InvalidAuthType = "invalid-auth-type", t2.InvalidOutputFormat = "invalid-output-format", t2.LinkUnsupportedAuthType = "link-unsupported-auth", t2.InvalidUpdateRange = "invalid-update-range", t2.NotSupported = "not-supported", t2;
})({});
function $(t2, e2, n2, r2, o2) {
  switch (t2.authType) {
    case j.Auto:
      e2 && n2 && (t2.headers.Authorization = P(e2, n2));
      break;
    case j.Digest:
      t2.digest = /* @__PURE__ */ (function(t3, e3, n3) {
        return { username: t3, password: e3, ha1: n3, nc: 0, algorithm: "md5", hasDigestAuth: false };
      })(e2, n2, o2);
      break;
    case j.None:
      break;
    case j.Password:
      t2.headers.Authorization = P(e2, n2);
      break;
    case j.Token:
      t2.headers.Authorization = `${(i2 = r2).token_type} ${i2.access_token}`;
      break;
    default:
      throw new a({ info: { code: I.InvalidAuthType } }, `Invalid auth type: ${t2.authType}`);
  }
  var i2;
}
n(345), n(800);
const C = "@@HOTPATCHER", R = () => {
};
function k(t2) {
  return { original: t2, methods: [t2], final: false };
}
class M {
  constructor() {
    this._configuration = { registry: {}, getEmptyAction: "null" }, this.__type__ = C;
  }
  get configuration() {
    return this._configuration;
  }
  get getEmptyAction() {
    return this.configuration.getEmptyAction;
  }
  set getEmptyAction(t2) {
    this.configuration.getEmptyAction = t2;
  }
  control(t2) {
    let e2 = arguments.length > 1 && void 0 !== arguments[1] && arguments[1];
    if (!t2 || t2.__type__ !== C) throw new Error("Failed taking control of target HotPatcher instance: Invalid type or object");
    return Object.keys(t2.configuration.registry).forEach(((n2) => {
      this.configuration.registry.hasOwnProperty(n2) ? e2 && (this.configuration.registry[n2] = Object.assign({}, t2.configuration.registry[n2])) : this.configuration.registry[n2] = Object.assign({}, t2.configuration.registry[n2]);
    })), t2._configuration = this.configuration, this;
  }
  execute(t2) {
    const e2 = this.get(t2) || R;
    for (var n2 = arguments.length, r2 = new Array(n2 > 1 ? n2 - 1 : 0), o2 = 1; o2 < n2; o2++) r2[o2 - 1] = arguments[o2];
    return e2(...r2);
  }
  get(t2) {
    const e2 = this.configuration.registry[t2];
    if (!e2) switch (this.getEmptyAction) {
      case "null":
        return null;
      case "throw":
        throw new Error(`Failed handling method request: No method provided for override: ${t2}`);
      default:
        throw new Error(`Failed handling request which resulted in an empty method: Invalid empty-action specified: ${this.getEmptyAction}`);
    }
    return (function() {
      for (var t3 = arguments.length, e3 = new Array(t3), n2 = 0; n2 < t3; n2++) e3[n2] = arguments[n2];
      if (0 === e3.length) throw new Error("Failed creating sequence: No functions provided");
      return function() {
        for (var t4 = arguments.length, n3 = new Array(t4), r2 = 0; r2 < t4; r2++) n3[r2] = arguments[r2];
        let o2 = n3;
        const i2 = this;
        for (; e3.length > 0; ) o2 = [e3.shift().apply(i2, o2)];
        return o2[0];
      };
    })(...e2.methods);
  }
  isPatched(t2) {
    return !!this.configuration.registry[t2];
  }
  patch(t2, e2) {
    let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
    const { chain: r2 = false } = n2;
    if (this.configuration.registry[t2] && this.configuration.registry[t2].final) throw new Error(`Failed patching '${t2}': Method marked as being final`);
    if ("function" != typeof e2) throw new Error(`Failed patching '${t2}': Provided method is not a function`);
    if (r2) this.configuration.registry[t2] ? this.configuration.registry[t2].methods.push(e2) : this.configuration.registry[t2] = k(e2);
    else if (this.isPatched(t2)) {
      const { original: n3 } = this.configuration.registry[t2];
      this.configuration.registry[t2] = Object.assign(k(e2), { original: n3 });
    } else this.configuration.registry[t2] = k(e2);
    return this;
  }
  patchInline(t2, e2) {
    this.isPatched(t2) || this.patch(t2, e2);
    for (var n2 = arguments.length, r2 = new Array(n2 > 2 ? n2 - 2 : 0), o2 = 2; o2 < n2; o2++) r2[o2 - 2] = arguments[o2];
    return this.execute(t2, ...r2);
  }
  plugin(t2) {
    for (var e2 = arguments.length, n2 = new Array(e2 > 1 ? e2 - 1 : 0), r2 = 1; r2 < e2; r2++) n2[r2 - 1] = arguments[r2];
    return n2.forEach(((e3) => {
      this.patch(t2, e3, { chain: true });
    })), this;
  }
  restore(t2) {
    if (!this.isPatched(t2)) throw new Error(`Failed restoring method: No method present for key: ${t2}`);
    if ("function" != typeof this.configuration.registry[t2].original) throw new Error(`Failed restoring method: Original method not found or of invalid type for key: ${t2}`);
    return this.configuration.registry[t2].methods = [this.configuration.registry[t2].original], this;
  }
  setFinal(t2) {
    if (!this.configuration.registry.hasOwnProperty(t2)) throw new Error(`Failed marking '${t2}' as final: No method found for key`);
    return this.configuration.registry[t2].final = true, this;
  }
}
let L = null;
function _() {
  return L || (L = new M()), L;
}
function U(t2) {
  return (function(t3) {
    if ("object" != typeof t3 || null === t3 || "[object Object]" != Object.prototype.toString.call(t3)) return false;
    if (null === Object.getPrototypeOf(t3)) return true;
    let e2 = t3;
    for (; null !== Object.getPrototypeOf(e2); ) e2 = Object.getPrototypeOf(e2);
    return Object.getPrototypeOf(t3) === e2;
  })(t2) ? Object.assign({}, t2) : Object.setPrototypeOf(Object.assign({}, t2), Object.getPrototypeOf(t2));
}
function D() {
  for (var t2 = arguments.length, e2 = new Array(t2), n2 = 0; n2 < t2; n2++) e2[n2] = arguments[n2];
  let r2 = null, o2 = [...e2];
  for (; o2.length > 0; ) {
    const t3 = o2.shift();
    r2 = r2 ? F(r2, t3) : U(t3);
  }
  return r2;
}
function F(t2, e2) {
  const n2 = U(t2);
  return Object.keys(e2).forEach(((t3) => {
    n2.hasOwnProperty(t3) ? Array.isArray(e2[t3]) ? n2[t3] = Array.isArray(n2[t3]) ? [...n2[t3], ...e2[t3]] : [...e2[t3]] : "object" == typeof e2[t3] && e2[t3] ? n2[t3] = "object" == typeof n2[t3] && n2[t3] ? F(n2[t3], e2[t3]) : U(e2[t3]) : n2[t3] = e2[t3] : n2[t3] = e2[t3];
  })), n2;
}
function V(t2) {
  const e2 = {};
  for (const n2 of t2.keys()) e2[n2] = t2.get(n2);
  return e2;
}
function B() {
  for (var t2 = arguments.length, e2 = new Array(t2), n2 = 0; n2 < t2; n2++) e2[n2] = arguments[n2];
  if (0 === e2.length) return {};
  const r2 = {};
  return e2.reduce(((t3, e3) => (Object.keys(e3).forEach(((n3) => {
    const o2 = n3.toLowerCase();
    r2.hasOwnProperty(o2) ? t3[r2[o2]] = e3[n3] : (r2[o2] = n3, t3[n3] = e3[n3]);
  })), t3)), {});
}
n(805);
const W = "function" == typeof ArrayBuffer, { toString: z } = Object.prototype;
function G(t2) {
  return W && (t2 instanceof ArrayBuffer || "[object ArrayBuffer]" === z.call(t2));
}
function q(t2) {
  return null != t2 && null != t2.constructor && "function" == typeof t2.constructor.isBuffer && t2.constructor.isBuffer(t2);
}
function H(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
}
function Z(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
const Y = H((function(t2) {
  const e2 = t2._digest;
  return delete t2._digest, e2.hasDigestAuth && (t2 = D(t2, { headers: { Authorization: b(t2, e2) } })), Z(Q(t2), (function(n2) {
    let r2 = false;
    return o2 = function(t3) {
      return r2 ? t3 : n2;
    }, (i2 = (function() {
      if (401 == n2.status) return e2.hasDigestAuth = (function(t3, e3) {
        if (!w(t3)) return false;
        const n3 = /([a-z0-9_-]+)=(?:"([^"]+)"|([a-z0-9_-]+))/gi;
        for (; ; ) {
          const r3 = t3.headers && t3.headers.get("www-authenticate") || "", o3 = n3.exec(r3);
          if (!o3) break;
          e3[o3[1]] = o3[2] || o3[3];
        }
        return e3.nc += 1, e3.cnonce = (function() {
          let t4 = "";
          for (let e4 = 0; e4 < 32; ++e4) t4 = `${t4}${"abcdef0123456789"[Math.floor(16 * Math.random())]}`;
          return t4;
        })(), true;
      })(n2, e2), (function() {
        if (e2.hasDigestAuth) return Z(Q(t2 = D(t2, { headers: { Authorization: b(t2, e2) } })), (function(t3) {
          return 401 == t3.status ? e2.hasDigestAuth = false : e2.nc++, r2 = true, t3;
        }));
      })();
      e2.nc++;
    })()) && i2.then ? i2.then(o2) : o2(i2);
    var o2, i2;
  }));
})), X = H((function(t2, e2) {
  return Z(Q(t2), (function(n2) {
    return n2.ok ? (e2.authType = j.Password, n2) : 401 == n2.status && w(n2) ? (e2.authType = j.Digest, $(e2, e2.username, e2.password, void 0, void 0), t2._digest = e2.digest, Y(t2)) : n2;
  }));
})), K = H((function(t2, e2) {
  return e2.authType === j.Auto ? X(t2, e2) : t2._digest ? Y(t2) : Q(t2);
}));
function J(t2, e2, n2) {
  const r2 = U(t2);
  return r2.headers = B(e2.headers, r2.headers || {}, n2.headers || {}), void 0 !== n2.data && (r2.data = n2.data), n2.signal && (r2.signal = n2.signal), e2.httpAgent && (r2.httpAgent = e2.httpAgent), e2.httpsAgent && (r2.httpsAgent = e2.httpsAgent), e2.digest && (r2._digest = e2.digest), "boolean" == typeof e2.withCredentials && (r2.withCredentials = e2.withCredentials), r2;
}
function Q(t2) {
  const e2 = _();
  return e2.patchInline("request", ((t3) => e2.patchInline("fetch", T, t3.url, (function(t4) {
    let e3 = {};
    const n2 = { method: t4.method };
    if (t4.headers && (e3 = B(e3, t4.headers)), void 0 !== t4.data) {
      const [r2, o2] = (function(t5) {
        if ("string" == typeof t5) return [t5, {}];
        if (q(t5)) return [t5, {}];
        if (G(t5)) return [t5, {}];
        if (t5 && "object" == typeof t5) return [JSON.stringify(t5), { "content-type": "application/json" }];
        throw new Error("Unable to convert request body: Unexpected body type: " + typeof t5);
      })(t4.data);
      n2.body = r2, e3 = B(e3, o2);
    }
    return t4.signal && (n2.signal = t4.signal), t4.withCredentials && (n2.credentials = "include"), n2.headers = e3, n2;
  })(t3))), t2);
}
var tt = n(285);
const et = (t2) => {
  if ("string" != typeof t2) throw new TypeError("invalid pattern");
  if (t2.length > 65536) throw new TypeError("pattern is too long");
}, nt = { "[:alnum:]": ["\\p{L}\\p{Nl}\\p{Nd}", true], "[:alpha:]": ["\\p{L}\\p{Nl}", true], "[:ascii:]": ["\\x00-\\x7f", false], "[:blank:]": ["\\p{Zs}\\t", true], "[:cntrl:]": ["\\p{Cc}", true], "[:digit:]": ["\\p{Nd}", true], "[:graph:]": ["\\p{Z}\\p{C}", true, true], "[:lower:]": ["\\p{Ll}", true], "[:print:]": ["\\p{C}", true], "[:punct:]": ["\\p{P}", true], "[:space:]": ["\\p{Z}\\t\\r\\n\\v\\f", true], "[:upper:]": ["\\p{Lu}", true], "[:word:]": ["\\p{L}\\p{Nl}\\p{Nd}\\p{Pc}", true], "[:xdigit:]": ["A-Fa-f0-9", false] }, rt = (t2) => t2.replace(/[[\]\\-]/g, "\\$&"), ot = (t2) => t2.join(""), it = (t2, e2) => {
  const n2 = e2;
  if ("[" !== t2.charAt(n2)) throw new Error("not in a brace expression");
  const r2 = [], o2 = [];
  let i2 = n2 + 1, s2 = false, a2 = false, u2 = false, l2 = false, c2 = n2, h2 = "";
  t: for (; i2 < t2.length; ) {
    const e3 = t2.charAt(i2);
    if ("!" !== e3 && "^" !== e3 || i2 !== n2 + 1) {
      if ("]" === e3 && s2 && !u2) {
        c2 = i2 + 1;
        break;
      }
      if (s2 = true, "\\" !== e3 || u2) {
        if ("[" === e3 && !u2) {
          for (const [e4, [s3, u3, l3]] of Object.entries(nt)) if (t2.startsWith(e4, i2)) {
            if (h2) return ["$.", false, t2.length - n2, true];
            i2 += e4.length, l3 ? o2.push(s3) : r2.push(s3), a2 = a2 || u3;
            continue t;
          }
        }
        u2 = false, h2 ? (e3 > h2 ? r2.push(rt(h2) + "-" + rt(e3)) : e3 === h2 && r2.push(rt(e3)), h2 = "", i2++) : t2.startsWith("-]", i2 + 1) ? (r2.push(rt(e3 + "-")), i2 += 2) : t2.startsWith("-", i2 + 1) ? (h2 = e3, i2 += 2) : (r2.push(rt(e3)), i2++);
      } else u2 = true, i2++;
    } else l2 = true, i2++;
  }
  if (c2 < i2) return ["", false, 0, false];
  if (!r2.length && !o2.length) return ["$.", false, t2.length - n2, true];
  if (0 === o2.length && 1 === r2.length && /^\\?.$/.test(r2[0]) && !l2) {
    return [(p2 = 2 === r2[0].length ? r2[0].slice(-1) : r2[0], p2.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&")), false, c2 - n2, false];
  }
  var p2;
  const f2 = "[" + (l2 ? "^" : "") + ot(r2) + "]", d2 = "[" + (l2 ? "" : "^") + ot(o2) + "]";
  return [r2.length && o2.length ? "(" + f2 + "|" + d2 + ")" : r2.length ? f2 : d2, a2, c2 - n2, true];
}, st = function(t2) {
  let { windowsPathsNoEscape: e2 = false } = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
  return e2 ? t2.replace(/\[([^\/\\])\]/g, "$1") : t2.replace(/((?!\\).|^)\[([^\/\\])\]/g, "$1$2").replace(/\\([^\/])/g, "$1");
}, at = /* @__PURE__ */ new Set(["!", "?", "+", "*", "@"]), ut = (t2) => at.has(t2), lt = "(?!\\.)", ct = /* @__PURE__ */ new Set(["[", "."]), ht = /* @__PURE__ */ new Set(["..", "."]), pt = new Set("().*{}+?[]^$\\!"), ft = "[^/]", dt = ft + "*?", gt = ft + "+?";
class mt {
  type;
  #t;
  #e;
  #n = false;
  #r = [];
  #o;
  #i;
  #s;
  #a = false;
  #u;
  #l;
  #c = false;
  constructor(t2, e2) {
    let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
    this.type = t2, t2 && (this.#e = true), this.#o = e2, this.#t = this.#o ? this.#o.#t : this, this.#u = this.#t === this ? n2 : this.#t.#u, this.#s = this.#t === this ? [] : this.#t.#s, "!" !== t2 || this.#t.#a || this.#s.push(this), this.#i = this.#o ? this.#o.#r.length : 0;
  }
  get hasMagic() {
    if (void 0 !== this.#e) return this.#e;
    for (const t2 of this.#r) if ("string" != typeof t2 && (t2.type || t2.hasMagic)) return this.#e = true;
    return this.#e;
  }
  toString() {
    return void 0 !== this.#l ? this.#l : this.type ? this.#l = this.type + "(" + this.#r.map(((t2) => String(t2))).join("|") + ")" : this.#l = this.#r.map(((t2) => String(t2))).join("");
  }
  #h() {
    if (this !== this.#t) throw new Error("should only call on root");
    if (this.#a) return this;
    let t2;
    for (this.toString(), this.#a = true; t2 = this.#s.pop(); ) {
      if ("!" !== t2.type) continue;
      let e2 = t2, n2 = e2.#o;
      for (; n2; ) {
        for (let r2 = e2.#i + 1; !n2.type && r2 < n2.#r.length; r2++) for (const e3 of t2.#r) {
          if ("string" == typeof e3) throw new Error("string part in extglob AST??");
          e3.copyIn(n2.#r[r2]);
        }
        e2 = n2, n2 = e2.#o;
      }
    }
    return this;
  }
  push() {
    for (var t2 = arguments.length, e2 = new Array(t2), n2 = 0; n2 < t2; n2++) e2[n2] = arguments[n2];
    for (const t3 of e2) if ("" !== t3) {
      if ("string" != typeof t3 && !(t3 instanceof mt && t3.#o === this)) throw new Error("invalid part: " + t3);
      this.#r.push(t3);
    }
  }
  toJSON() {
    const t2 = null === this.type ? this.#r.slice().map(((t3) => "string" == typeof t3 ? t3 : t3.toJSON())) : [this.type, ...this.#r.map(((t3) => t3.toJSON()))];
    return this.isStart() && !this.type && t2.unshift([]), this.isEnd() && (this === this.#t || this.#t.#a && "!" === this.#o?.type) && t2.push({}), t2;
  }
  isStart() {
    if (this.#t === this) return true;
    if (!this.#o?.isStart()) return false;
    if (0 === this.#i) return true;
    const t2 = this.#o;
    for (let e2 = 0; e2 < this.#i; e2++) {
      const n2 = t2.#r[e2];
      if (!(n2 instanceof mt && "!" === n2.type)) return false;
    }
    return true;
  }
  isEnd() {
    if (this.#t === this) return true;
    if ("!" === this.#o?.type) return true;
    if (!this.#o?.isEnd()) return false;
    if (!this.type) return this.#o?.isEnd();
    const t2 = this.#o ? this.#o.#r.length : 0;
    return this.#i === t2 - 1;
  }
  copyIn(t2) {
    "string" == typeof t2 ? this.push(t2) : this.push(t2.clone(this));
  }
  clone(t2) {
    const e2 = new mt(this.type, t2);
    for (const t3 of this.#r) e2.copyIn(t3);
    return e2;
  }
  static #p(t2, e2, n2, r2) {
    let o2 = false, i2 = false, s2 = -1, a2 = false;
    if (null === e2.type) {
      let u3 = n2, l3 = "";
      for (; u3 < t2.length; ) {
        const n3 = t2.charAt(u3++);
        if (o2 || "\\" === n3) o2 = !o2, l3 += n3;
        else if (i2) u3 === s2 + 1 ? "^" !== n3 && "!" !== n3 || (a2 = true) : "]" !== n3 || u3 === s2 + 2 && a2 || (i2 = false), l3 += n3;
        else if ("[" !== n3) if (r2.noext || !ut(n3) || "(" !== t2.charAt(u3)) l3 += n3;
        else {
          e2.push(l3), l3 = "";
          const o3 = new mt(n3, e2);
          u3 = mt.#p(t2, o3, u3, r2), e2.push(o3);
        }
        else i2 = true, s2 = u3, a2 = false, l3 += n3;
      }
      return e2.push(l3), u3;
    }
    let u2 = n2 + 1, l2 = new mt(null, e2);
    const c2 = [];
    let h2 = "";
    for (; u2 < t2.length; ) {
      const n3 = t2.charAt(u2++);
      if (o2 || "\\" === n3) o2 = !o2, h2 += n3;
      else if (i2) u2 === s2 + 1 ? "^" !== n3 && "!" !== n3 || (a2 = true) : "]" !== n3 || u2 === s2 + 2 && a2 || (i2 = false), h2 += n3;
      else if ("[" !== n3) if (ut(n3) && "(" === t2.charAt(u2)) {
        l2.push(h2), h2 = "";
        const e3 = new mt(n3, l2);
        l2.push(e3), u2 = mt.#p(t2, e3, u2, r2);
      } else if ("|" !== n3) {
        if (")" === n3) return "" === h2 && 0 === e2.#r.length && (e2.#c = true), l2.push(h2), h2 = "", e2.push(...c2, l2), u2;
        h2 += n3;
      } else l2.push(h2), h2 = "", c2.push(l2), l2 = new mt(null, e2);
      else i2 = true, s2 = u2, a2 = false, h2 += n3;
    }
    return e2.type = null, e2.#e = void 0, e2.#r = [t2.substring(n2 - 1)], u2;
  }
  static fromGlob(t2) {
    let e2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
    const n2 = new mt(null, void 0, e2);
    return mt.#p(t2, n2, 0, e2), n2;
  }
  toMMPattern() {
    if (this !== this.#t) return this.#t.toMMPattern();
    const t2 = this.toString(), [e2, n2, r2, o2] = this.toRegExpSource();
    if (!(r2 || this.#e || this.#u.nocase && !this.#u.nocaseMagicOnly && t2.toUpperCase() !== t2.toLowerCase())) return n2;
    const i2 = (this.#u.nocase ? "i" : "") + (o2 ? "u" : "");
    return Object.assign(new RegExp(`^${e2}$`, i2), { _src: e2, _glob: t2 });
  }
  get options() {
    return this.#u;
  }
  toRegExpSource(t2) {
    const e2 = t2 ?? !!this.#u.dot;
    if (this.#t === this && this.#h(), !this.type) {
      const n3 = this.isStart() && this.isEnd(), r3 = this.#r.map(((e3) => {
        const [r4, o4, i4, s3] = "string" == typeof e3 ? mt.#f(e3, this.#e, n3) : e3.toRegExpSource(t2);
        return this.#e = this.#e || i4, this.#n = this.#n || s3, r4;
      })).join("");
      let o3 = "";
      if (this.isStart() && "string" == typeof this.#r[0] && (1 !== this.#r.length || !ht.has(this.#r[0]))) {
        const n4 = ct, i4 = e2 && n4.has(r3.charAt(0)) || r3.startsWith("\\.") && n4.has(r3.charAt(2)) || r3.startsWith("\\.\\.") && n4.has(r3.charAt(4)), s3 = !e2 && !t2 && n4.has(r3.charAt(0));
        o3 = i4 ? "(?!(?:^|/)\\.\\.?(?:$|/))" : s3 ? lt : "";
      }
      let i3 = "";
      return this.isEnd() && this.#t.#a && "!" === this.#o?.type && (i3 = "(?:$|\\/)"), [o3 + r3 + i3, st(r3), this.#e = !!this.#e, this.#n];
    }
    const n2 = "*" === this.type || "+" === this.type, r2 = "!" === this.type ? "(?:(?!(?:" : "(?:";
    let o2 = this.#d(e2);
    if (this.isStart() && this.isEnd() && !o2 && "!" !== this.type) {
      const t3 = this.toString();
      return this.#r = [t3], this.type = null, this.#e = void 0, [t3, st(this.toString()), false, false];
    }
    let i2 = !n2 || t2 || e2 ? "" : this.#d(true);
    i2 === o2 && (i2 = ""), i2 && (o2 = `(?:${o2})(?:${i2})*?`);
    let s2 = "";
    return s2 = "!" === this.type && this.#c ? (this.isStart() && !e2 ? lt : "") + gt : r2 + o2 + ("!" === this.type ? "))" + (!this.isStart() || e2 || t2 ? "" : lt) + dt + ")" : "@" === this.type ? ")" : "?" === this.type ? ")?" : "+" === this.type && i2 ? ")" : "*" === this.type && i2 ? ")?" : `)${this.type}`), [s2, st(o2), this.#e = !!this.#e, this.#n];
  }
  #d(t2) {
    return this.#r.map(((e2) => {
      if ("string" == typeof e2) throw new Error("string type in extglob ast??");
      const [n2, r2, o2, i2] = e2.toRegExpSource(t2);
      return this.#n = this.#n || i2, n2;
    })).filter(((t3) => !(this.isStart() && this.isEnd() && !t3))).join("|");
  }
  static #f(t2, e2) {
    let n2 = arguments.length > 2 && void 0 !== arguments[2] && arguments[2], r2 = false, o2 = "", i2 = false;
    for (let s2 = 0; s2 < t2.length; s2++) {
      const a2 = t2.charAt(s2);
      if (r2) r2 = false, o2 += (pt.has(a2) ? "\\" : "") + a2;
      else if ("\\" !== a2) {
        if ("[" === a2) {
          const [n3, r3, a3, u2] = it(t2, s2);
          if (a3) {
            o2 += n3, i2 = i2 || r3, s2 += a3 - 1, e2 = e2 || u2;
            continue;
          }
        }
        "*" !== a2 ? "?" !== a2 ? o2 += a2.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&") : (o2 += ft, e2 = true) : (o2 += n2 && "*" === t2 ? gt : dt, e2 = true);
      } else s2 === t2.length - 1 ? o2 += "\\\\" : r2 = true;
    }
    return [o2, st(t2), !!e2, i2];
  }
}
const yt = function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  return et(e2), !(!n2.nocomment && "#" === e2.charAt(0)) && new Wt(e2, n2).match(t2);
}, vt = /^\*+([^+@!?\*\[\(]*)$/, bt = (t2) => (e2) => !e2.startsWith(".") && e2.endsWith(t2), wt = (t2) => (e2) => e2.endsWith(t2), xt = (t2) => (t2 = t2.toLowerCase(), (e2) => !e2.startsWith(".") && e2.toLowerCase().endsWith(t2)), Et = (t2) => (t2 = t2.toLowerCase(), (e2) => e2.toLowerCase().endsWith(t2)), Nt = /^\*+\.\*+$/, Pt = (t2) => !t2.startsWith(".") && t2.includes("."), At = (t2) => "." !== t2 && ".." !== t2 && t2.includes("."), Tt = /^\.\*+$/, Ot = (t2) => "." !== t2 && ".." !== t2 && t2.startsWith("."), St = /^\*+$/, jt = (t2) => 0 !== t2.length && !t2.startsWith("."), It = (t2) => 0 !== t2.length && "." !== t2 && ".." !== t2, $t = /^\?+([^+@!?\*\[\(]*)?$/, Ct = (t2) => {
  let [e2, n2 = ""] = t2;
  const r2 = Lt([e2]);
  return n2 ? (n2 = n2.toLowerCase(), (t3) => r2(t3) && t3.toLowerCase().endsWith(n2)) : r2;
}, Rt = (t2) => {
  let [e2, n2 = ""] = t2;
  const r2 = _t([e2]);
  return n2 ? (n2 = n2.toLowerCase(), (t3) => r2(t3) && t3.toLowerCase().endsWith(n2)) : r2;
}, kt = (t2) => {
  let [e2, n2 = ""] = t2;
  const r2 = _t([e2]);
  return n2 ? (t3) => r2(t3) && t3.endsWith(n2) : r2;
}, Mt = (t2) => {
  let [e2, n2 = ""] = t2;
  const r2 = Lt([e2]);
  return n2 ? (t3) => r2(t3) && t3.endsWith(n2) : r2;
}, Lt = (t2) => {
  let [e2] = t2;
  const n2 = e2.length;
  return (t3) => t3.length === n2 && !t3.startsWith(".");
}, _t = (t2) => {
  let [e2] = t2;
  const n2 = e2.length;
  return (t3) => t3.length === n2 && "." !== t3 && ".." !== t3;
}, Ut = "object" == typeof process$1 && process$1 ? "object" == typeof define_process_env_default && define_process_env_default && define_process_env_default.__MINIMATCH_TESTING_PLATFORM__ || process$1.platform : "posix";
yt.sep = "win32" === Ut ? "\\" : "/";
const Dt = /* @__PURE__ */ Symbol("globstar **");
yt.GLOBSTAR = Dt, yt.filter = function(t2) {
  let e2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
  return (n2) => yt(n2, t2, e2);
};
const Ft = function(t2) {
  let e2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
  return Object.assign({}, t2, e2);
};
yt.defaults = (t2) => {
  if (!t2 || "object" != typeof t2 || !Object.keys(t2).length) return yt;
  const e2 = yt;
  return Object.assign((function(n2, r2) {
    return e2(n2, r2, Ft(t2, arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {}));
  }), { Minimatch: class extends e2.Minimatch {
    constructor(e3) {
      super(e3, Ft(t2, arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {}));
    }
    static defaults(n2) {
      return e2.defaults(Ft(t2, n2)).Minimatch;
    }
  }, AST: class extends e2.AST {
    constructor(e3, n2) {
      super(e3, n2, Ft(t2, arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {}));
    }
    static fromGlob(n2) {
      let r2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
      return e2.AST.fromGlob(n2, Ft(t2, r2));
    }
  }, unescape: function(n2) {
    let r2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
    return e2.unescape(n2, Ft(t2, r2));
  }, escape: function(n2) {
    let r2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
    return e2.escape(n2, Ft(t2, r2));
  }, filter: function(n2) {
    let r2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
    return e2.filter(n2, Ft(t2, r2));
  }, defaults: (n2) => e2.defaults(Ft(t2, n2)), makeRe: function(n2) {
    let r2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
    return e2.makeRe(n2, Ft(t2, r2));
  }, braceExpand: function(n2) {
    let r2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
    return e2.braceExpand(n2, Ft(t2, r2));
  }, match: function(n2, r2) {
    let o2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
    return e2.match(n2, r2, Ft(t2, o2));
  }, sep: e2.sep, GLOBSTAR: Dt });
};
const Vt = function(t2) {
  let e2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
  return et(t2), e2.nobrace || !/\{(?:(?!\{).)*\}/.test(t2) ? [t2] : tt(t2);
};
yt.braceExpand = Vt, yt.makeRe = function(t2) {
  return new Wt(t2, arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {}).makeRe();
}, yt.match = function(t2, e2) {
  const n2 = new Wt(e2, arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {});
  return t2 = t2.filter(((t3) => n2.match(t3))), n2.options.nonull && !t2.length && t2.push(e2), t2;
};
const Bt = /[?*]|[+@!]\(.*?\)|\[|\]/;
class Wt {
  options;
  set;
  pattern;
  windowsPathsNoEscape;
  nonegate;
  negate;
  comment;
  empty;
  preserveMultipleSlashes;
  partial;
  globSet;
  globParts;
  nocase;
  isWindows;
  platform;
  windowsNoMagicRoot;
  regexp;
  constructor(t2) {
    let e2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
    et(t2), e2 = e2 || {}, this.options = e2, this.pattern = t2, this.platform = e2.platform || Ut, this.isWindows = "win32" === this.platform, this.windowsPathsNoEscape = !!e2.windowsPathsNoEscape || false === e2.allowWindowsEscape, this.windowsPathsNoEscape && (this.pattern = this.pattern.replace(/\\/g, "/")), this.preserveMultipleSlashes = !!e2.preserveMultipleSlashes, this.regexp = null, this.negate = false, this.nonegate = !!e2.nonegate, this.comment = false, this.empty = false, this.partial = !!e2.partial, this.nocase = !!this.options.nocase, this.windowsNoMagicRoot = void 0 !== e2.windowsNoMagicRoot ? e2.windowsNoMagicRoot : !(!this.isWindows || !this.nocase), this.globSet = [], this.globParts = [], this.set = [], this.make();
  }
  hasMagic() {
    if (this.options.magicalBraces && this.set.length > 1) return true;
    for (const t2 of this.set) for (const e2 of t2) if ("string" != typeof e2) return true;
    return false;
  }
  debug() {
  }
  make() {
    const t2 = this.pattern, e2 = this.options;
    if (!e2.nocomment && "#" === t2.charAt(0)) return void (this.comment = true);
    if (!t2) return void (this.empty = true);
    this.parseNegate(), this.globSet = [...new Set(this.braceExpand())], e2.debug && (this.debug = function() {
      return console.error(...arguments);
    }), this.debug(this.pattern, this.globSet);
    const n2 = this.globSet.map(((t3) => this.slashSplit(t3)));
    this.globParts = this.preprocess(n2), this.debug(this.pattern, this.globParts);
    let r2 = this.globParts.map(((t3, e3, n3) => {
      if (this.isWindows && this.windowsNoMagicRoot) {
        const e4 = !("" !== t3[0] || "" !== t3[1] || "?" !== t3[2] && Bt.test(t3[2]) || Bt.test(t3[3])), n4 = /^[a-z]:/i.test(t3[0]);
        if (e4) return [...t3.slice(0, 4), ...t3.slice(4).map(((t4) => this.parse(t4)))];
        if (n4) return [t3[0], ...t3.slice(1).map(((t4) => this.parse(t4)))];
      }
      return t3.map(((t4) => this.parse(t4)));
    }));
    if (this.debug(this.pattern, r2), this.set = r2.filter(((t3) => -1 === t3.indexOf(false))), this.isWindows) for (let t3 = 0; t3 < this.set.length; t3++) {
      const e3 = this.set[t3];
      "" === e3[0] && "" === e3[1] && "?" === this.globParts[t3][2] && "string" == typeof e3[3] && /^[a-z]:$/i.test(e3[3]) && (e3[2] = "?");
    }
    this.debug(this.pattern, this.set);
  }
  preprocess(t2) {
    if (this.options.noglobstar) for (let e3 = 0; e3 < t2.length; e3++) for (let n2 = 0; n2 < t2[e3].length; n2++) "**" === t2[e3][n2] && (t2[e3][n2] = "*");
    const { optimizationLevel: e2 = 1 } = this.options;
    return e2 >= 2 ? (t2 = this.firstPhasePreProcess(t2), t2 = this.secondPhasePreProcess(t2)) : t2 = e2 >= 1 ? this.levelOneOptimize(t2) : this.adjascentGlobstarOptimize(t2), t2;
  }
  adjascentGlobstarOptimize(t2) {
    return t2.map(((t3) => {
      let e2 = -1;
      for (; -1 !== (e2 = t3.indexOf("**", e2 + 1)); ) {
        let n2 = e2;
        for (; "**" === t3[n2 + 1]; ) n2++;
        n2 !== e2 && t3.splice(e2, n2 - e2);
      }
      return t3;
    }));
  }
  levelOneOptimize(t2) {
    return t2.map(((t3) => 0 === (t3 = t3.reduce(((t4, e2) => {
      const n2 = t4[t4.length - 1];
      return "**" === e2 && "**" === n2 ? t4 : ".." === e2 && n2 && ".." !== n2 && "." !== n2 && "**" !== n2 ? (t4.pop(), t4) : (t4.push(e2), t4);
    }), [])).length ? [""] : t3));
  }
  levelTwoFileOptimize(t2) {
    Array.isArray(t2) || (t2 = this.slashSplit(t2));
    let e2 = false;
    do {
      if (e2 = false, !this.preserveMultipleSlashes) {
        for (let n3 = 1; n3 < t2.length - 1; n3++) {
          const r2 = t2[n3];
          1 === n3 && "" === r2 && "" === t2[0] || "." !== r2 && "" !== r2 || (e2 = true, t2.splice(n3, 1), n3--);
        }
        "." !== t2[0] || 2 !== t2.length || "." !== t2[1] && "" !== t2[1] || (e2 = true, t2.pop());
      }
      let n2 = 0;
      for (; -1 !== (n2 = t2.indexOf("..", n2 + 1)); ) {
        const r2 = t2[n2 - 1];
        r2 && "." !== r2 && ".." !== r2 && "**" !== r2 && (e2 = true, t2.splice(n2 - 1, 2), n2 -= 2);
      }
    } while (e2);
    return 0 === t2.length ? [""] : t2;
  }
  firstPhasePreProcess(t2) {
    let e2 = false;
    do {
      e2 = false;
      for (let n2 of t2) {
        let r2 = -1;
        for (; -1 !== (r2 = n2.indexOf("**", r2 + 1)); ) {
          let o3 = r2;
          for (; "**" === n2[o3 + 1]; ) o3++;
          o3 > r2 && n2.splice(r2 + 1, o3 - r2);
          let i2 = n2[r2 + 1];
          const s2 = n2[r2 + 2], a2 = n2[r2 + 3];
          if (".." !== i2) continue;
          if (!s2 || "." === s2 || ".." === s2 || !a2 || "." === a2 || ".." === a2) continue;
          e2 = true, n2.splice(r2, 1);
          const u2 = n2.slice(0);
          u2[r2] = "**", t2.push(u2), r2--;
        }
        if (!this.preserveMultipleSlashes) {
          for (let t3 = 1; t3 < n2.length - 1; t3++) {
            const r3 = n2[t3];
            1 === t3 && "" === r3 && "" === n2[0] || "." !== r3 && "" !== r3 || (e2 = true, n2.splice(t3, 1), t3--);
          }
          "." !== n2[0] || 2 !== n2.length || "." !== n2[1] && "" !== n2[1] || (e2 = true, n2.pop());
        }
        let o2 = 0;
        for (; -1 !== (o2 = n2.indexOf("..", o2 + 1)); ) {
          const t3 = n2[o2 - 1];
          if (t3 && "." !== t3 && ".." !== t3 && "**" !== t3) {
            e2 = true;
            const t4 = 1 === o2 && "**" === n2[o2 + 1] ? ["."] : [];
            n2.splice(o2 - 1, 2, ...t4), 0 === n2.length && n2.push(""), o2 -= 2;
          }
        }
      }
    } while (e2);
    return t2;
  }
  secondPhasePreProcess(t2) {
    for (let e2 = 0; e2 < t2.length - 1; e2++) for (let n2 = e2 + 1; n2 < t2.length; n2++) {
      const r2 = this.partsMatch(t2[e2], t2[n2], !this.preserveMultipleSlashes);
      if (r2) {
        t2[e2] = [], t2[n2] = r2;
        break;
      }
    }
    return t2.filter(((t3) => t3.length));
  }
  partsMatch(t2, e2) {
    let n2 = arguments.length > 2 && void 0 !== arguments[2] && arguments[2], r2 = 0, o2 = 0, i2 = [], s2 = "";
    for (; r2 < t2.length && o2 < e2.length; ) if (t2[r2] === e2[o2]) i2.push("b" === s2 ? e2[o2] : t2[r2]), r2++, o2++;
    else if (n2 && "**" === t2[r2] && e2[o2] === t2[r2 + 1]) i2.push(t2[r2]), r2++;
    else if (n2 && "**" === e2[o2] && t2[r2] === e2[o2 + 1]) i2.push(e2[o2]), o2++;
    else if ("*" !== t2[r2] || !e2[o2] || !this.options.dot && e2[o2].startsWith(".") || "**" === e2[o2]) {
      if ("*" !== e2[o2] || !t2[r2] || !this.options.dot && t2[r2].startsWith(".") || "**" === t2[r2]) return false;
      if ("a" === s2) return false;
      s2 = "b", i2.push(e2[o2]), r2++, o2++;
    } else {
      if ("b" === s2) return false;
      s2 = "a", i2.push(t2[r2]), r2++, o2++;
    }
    return t2.length === e2.length && i2;
  }
  parseNegate() {
    if (this.nonegate) return;
    const t2 = this.pattern;
    let e2 = false, n2 = 0;
    for (let r2 = 0; r2 < t2.length && "!" === t2.charAt(r2); r2++) e2 = !e2, n2++;
    n2 && (this.pattern = t2.slice(n2)), this.negate = e2;
  }
  matchOne(t2, e2) {
    let n2 = arguments.length > 2 && void 0 !== arguments[2] && arguments[2];
    const r2 = this.options;
    if (this.isWindows) {
      const n3 = "string" == typeof t2[0] && /^[a-z]:$/i.test(t2[0]), r3 = !n3 && "" === t2[0] && "" === t2[1] && "?" === t2[2] && /^[a-z]:$/i.test(t2[3]), o3 = "string" == typeof e2[0] && /^[a-z]:$/i.test(e2[0]), i3 = r3 ? 3 : n3 ? 0 : void 0, s3 = !o3 && "" === e2[0] && "" === e2[1] && "?" === e2[2] && "string" == typeof e2[3] && /^[a-z]:$/i.test(e2[3]) ? 3 : o3 ? 0 : void 0;
      if ("number" == typeof i3 && "number" == typeof s3) {
        const [n4, r4] = [t2[i3], e2[s3]];
        n4.toLowerCase() === r4.toLowerCase() && (e2[s3] = n4, s3 > i3 ? e2 = e2.slice(s3) : i3 > s3 && (t2 = t2.slice(i3)));
      }
    }
    const { optimizationLevel: o2 = 1 } = this.options;
    o2 >= 2 && (t2 = this.levelTwoFileOptimize(t2)), this.debug("matchOne", this, { file: t2, pattern: e2 }), this.debug("matchOne", t2.length, e2.length);
    for (var i2 = 0, s2 = 0, a2 = t2.length, u2 = e2.length; i2 < a2 && s2 < u2; i2++, s2++) {
      this.debug("matchOne loop");
      var l2 = e2[s2], c2 = t2[i2];
      if (this.debug(e2, l2, c2), false === l2) return false;
      if (l2 === Dt) {
        this.debug("GLOBSTAR", [e2, l2, c2]);
        var h2 = i2, p2 = s2 + 1;
        if (p2 === u2) {
          for (this.debug("** at the end"); i2 < a2; i2++) if ("." === t2[i2] || ".." === t2[i2] || !r2.dot && "." === t2[i2].charAt(0)) return false;
          return true;
        }
        for (; h2 < a2; ) {
          var f2 = t2[h2];
          if (this.debug("\nglobstar while", t2, h2, e2, p2, f2), this.matchOne(t2.slice(h2), e2.slice(p2), n2)) return this.debug("globstar found match!", h2, a2, f2), true;
          if ("." === f2 || ".." === f2 || !r2.dot && "." === f2.charAt(0)) {
            this.debug("dot detected!", t2, h2, e2, p2);
            break;
          }
          this.debug("globstar swallow a segment, and continue"), h2++;
        }
        return !(!n2 || (this.debug("\n>>> no match, partial?", t2, h2, e2, p2), h2 !== a2));
      }
      let o3;
      if ("string" == typeof l2 ? (o3 = c2 === l2, this.debug("string match", l2, c2, o3)) : (o3 = l2.test(c2), this.debug("pattern match", l2, c2, o3)), !o3) return false;
    }
    if (i2 === a2 && s2 === u2) return true;
    if (i2 === a2) return n2;
    if (s2 === u2) return i2 === a2 - 1 && "" === t2[i2];
    throw new Error("wtf?");
  }
  braceExpand() {
    return Vt(this.pattern, this.options);
  }
  parse(t2) {
    et(t2);
    const e2 = this.options;
    if ("**" === t2) return Dt;
    if ("" === t2) return "";
    let n2, r2 = null;
    (n2 = t2.match(St)) ? r2 = e2.dot ? It : jt : (n2 = t2.match(vt)) ? r2 = (e2.nocase ? e2.dot ? Et : xt : e2.dot ? wt : bt)(n2[1]) : (n2 = t2.match($t)) ? r2 = (e2.nocase ? e2.dot ? Rt : Ct : e2.dot ? kt : Mt)(n2) : (n2 = t2.match(Nt)) ? r2 = e2.dot ? At : Pt : (n2 = t2.match(Tt)) && (r2 = Ot);
    const o2 = mt.fromGlob(t2, this.options).toMMPattern();
    return r2 && "object" == typeof o2 && Reflect.defineProperty(o2, "test", { value: r2 }), o2;
  }
  makeRe() {
    if (this.regexp || false === this.regexp) return this.regexp;
    const t2 = this.set;
    if (!t2.length) return this.regexp = false, this.regexp;
    const e2 = this.options, n2 = e2.noglobstar ? "[^/]*?" : e2.dot ? "(?:(?!(?:\\/|^)(?:\\.{1,2})($|\\/)).)*?" : "(?:(?!(?:\\/|^)\\.).)*?", r2 = new Set(e2.nocase ? ["i"] : []);
    let o2 = t2.map(((t3) => {
      const e3 = t3.map(((t4) => {
        if (t4 instanceof RegExp) for (const e4 of t4.flags.split("")) r2.add(e4);
        return "string" == typeof t4 ? t4.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&") : t4 === Dt ? Dt : t4._src;
      }));
      return e3.forEach(((t4, r3) => {
        const o3 = e3[r3 + 1], i3 = e3[r3 - 1];
        t4 === Dt && i3 !== Dt && (void 0 === i3 ? void 0 !== o3 && o3 !== Dt ? e3[r3 + 1] = "(?:\\/|" + n2 + "\\/)?" + o3 : e3[r3] = n2 : void 0 === o3 ? e3[r3 - 1] = i3 + "(?:\\/|" + n2 + ")?" : o3 !== Dt && (e3[r3 - 1] = i3 + "(?:\\/|\\/" + n2 + "\\/)" + o3, e3[r3 + 1] = Dt));
      })), e3.filter(((t4) => t4 !== Dt)).join("/");
    })).join("|");
    const [i2, s2] = t2.length > 1 ? ["(?:", ")"] : ["", ""];
    o2 = "^" + i2 + o2 + s2 + "$", this.negate && (o2 = "^(?!" + o2 + ").+$");
    try {
      this.regexp = new RegExp(o2, [...r2].join(""));
    } catch (t3) {
      this.regexp = false;
    }
    return this.regexp;
  }
  slashSplit(t2) {
    return this.preserveMultipleSlashes ? t2.split("/") : this.isWindows && /^\/\/[^\/]+/.test(t2) ? ["", ...t2.split(/\/+/)] : t2.split(/\/+/);
  }
  match(t2) {
    let e2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : this.partial;
    if (this.debug("match", t2, this.pattern), this.comment) return false;
    if (this.empty) return "" === t2;
    if ("/" === t2 && e2) return true;
    const n2 = this.options;
    this.isWindows && (t2 = t2.split("\\").join("/"));
    const r2 = this.slashSplit(t2);
    this.debug(this.pattern, "split", r2);
    const o2 = this.set;
    this.debug(this.pattern, "set", o2);
    let i2 = r2[r2.length - 1];
    if (!i2) for (let t3 = r2.length - 2; !i2 && t3 >= 0; t3--) i2 = r2[t3];
    for (let t3 = 0; t3 < o2.length; t3++) {
      const s2 = o2[t3];
      let a2 = r2;
      if (n2.matchBase && 1 === s2.length && (a2 = [i2]), this.matchOne(a2, s2, e2)) return !!n2.flipNegate || !this.negate;
    }
    return !n2.flipNegate && this.negate;
  }
  static defaults(t2) {
    return yt.defaults(t2).Minimatch;
  }
}
function zt(t2) {
  const e2 = new Error(`${arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : ""}Invalid response: ${t2.status} ${t2.statusText}`);
  return e2.status = t2.status, e2.response = t2, e2;
}
function Gt(t2, e2) {
  const { status: n2 } = e2;
  if (401 === n2 && t2.digest) return e2;
  if (n2 >= 400) throw zt(e2);
  return e2;
}
function qt(t2, e2) {
  return arguments.length > 2 && void 0 !== arguments[2] && arguments[2] ? { data: e2, headers: t2.headers ? V(t2.headers) : {}, status: t2.status, statusText: t2.statusText } : e2;
}
yt.AST = mt, yt.Minimatch = Wt, yt.escape = function(t2) {
  let { windowsPathsNoEscape: e2 = false } = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
  return e2 ? t2.replace(/[?*()[\]]/g, "[$&]") : t2.replace(/[?*()[\]\\]/g, "\\$&");
}, yt.unescape = st;
const Ht = (Zt = function(t2, e2, n2) {
  let r2 = arguments.length > 3 && void 0 !== arguments[3] ? arguments[3] : {};
  const o2 = J({ url: m(t2.remoteURL, p(e2)), method: "COPY", headers: { Destination: m(t2.remoteURL, p(n2)), Overwrite: false === r2.overwrite ? "F" : "T", Depth: r2.shallow ? "0" : "infinity" } }, t2, r2);
  return s2 = function(e3) {
    Gt(t2, e3);
  }, (i2 = K(o2, t2)) && i2.then || (i2 = Promise.resolve(i2)), s2 ? i2.then(s2) : i2;
  var i2, s2;
}, function() {
  for (var t2 = [], e2 = 0; e2 < arguments.length; e2++) t2[e2] = arguments[e2];
  try {
    return Promise.resolve(Zt.apply(this, t2));
  } catch (t3) {
    return Promise.reject(t3);
  }
});
var Zt;
const Yt = { preserveOrder: false, attributeNamePrefix: "@_", attributesGroupName: false, textNodeName: "#text", ignoreAttributes: true, removeNSPrefix: false, allowBooleanAttributes: false, parseTagValue: true, parseAttributeValue: false, trimValues: true, cdataPropName: false, numberParseOptions: { hex: true, leadingZeros: true, eNotation: true }, tagValueProcessor: function(t2, e2) {
  return e2;
}, attributeValueProcessor: function(t2, e2) {
  return e2;
}, stopNodes: [], alwaysCreateTextNode: false, isArray: () => false, commentPropName: false, unpairedTags: [], processEntities: true, htmlEntities: false, ignoreDeclaration: false, ignorePiTags: false, transformTagName: false, transformAttributeName: false, updateTag: function(t2, e2, n2) {
  return t2;
}, captureMetaData: false }, Xt = ":A-Za-z_\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u02FF\\u0370-\\u037D\\u037F-\\u1FFF\\u200C-\\u200D\\u2070-\\u218F\\u2C00-\\u2FEF\\u3001-\\uD7FF\\uF900-\\uFDCF\\uFDF0-\\uFFFD", Kt = new RegExp("^[" + Xt + "][" + Xt + "\\-.\\d\\u00B7\\u0300-\\u036F\\u203F-\\u2040]*$");
function Jt(t2, e2) {
  const n2 = [];
  let r2 = e2.exec(t2);
  for (; r2; ) {
    const o2 = [];
    o2.startIndex = e2.lastIndex - r2[0].length;
    const i2 = r2.length;
    for (let t3 = 0; t3 < i2; t3++) o2.push(r2[t3]);
    n2.push(o2), r2 = e2.exec(t2);
  }
  return n2;
}
const Qt = function(t2) {
  return !(null == Kt.exec(t2));
};
let te;
te = "function" != typeof Symbol ? "@@xmlMetadata" : /* @__PURE__ */ Symbol("XML Node Metadata");
class ee {
  constructor(t2) {
    this.tagname = t2, this.child = [], this[":@"] = {};
  }
  add(t2, e2) {
    "__proto__" === t2 && (t2 = "#__proto__"), this.child.push({ [t2]: e2 });
  }
  addChild(t2, e2) {
    "__proto__" === t2.tagname && (t2.tagname = "#__proto__"), t2[":@"] && Object.keys(t2[":@"]).length > 0 ? this.child.push({ [t2.tagname]: t2.child, ":@": t2[":@"] }) : this.child.push({ [t2.tagname]: t2.child }), void 0 !== e2 && (this.child[this.child.length - 1][te] = { startIndex: e2 });
  }
  static getMetaDataSymbol() {
    return te;
  }
}
class ne {
  constructor(t2) {
    this.suppressValidationErr = !t2;
  }
  readDocType(t2, e2) {
    const n2 = {};
    if ("O" !== t2[e2 + 3] || "C" !== t2[e2 + 4] || "T" !== t2[e2 + 5] || "Y" !== t2[e2 + 6] || "P" !== t2[e2 + 7] || "E" !== t2[e2 + 8]) throw new Error("Invalid Tag instead of DOCTYPE");
    {
      e2 += 9;
      let r2 = 1, o2 = false, i2 = false, s2 = "";
      for (; e2 < t2.length; e2++) if ("<" !== t2[e2] || i2) if (">" === t2[e2]) {
        if (i2 ? "-" === t2[e2 - 1] && "-" === t2[e2 - 2] && (i2 = false, r2--) : r2--, 0 === r2) break;
      } else "[" === t2[e2] ? o2 = true : s2 += t2[e2];
      else {
        if (o2 && oe(t2, "!ENTITY", e2)) {
          let r3, o3;
          e2 += 7, [r3, o3, e2] = this.readEntityExp(t2, e2 + 1, this.suppressValidationErr), -1 === o3.indexOf("&") && (n2[r3] = { regx: RegExp(`&${r3};`, "g"), val: o3 });
        } else if (o2 && oe(t2, "!ELEMENT", e2)) {
          e2 += 8;
          const { index: n3 } = this.readElementExp(t2, e2 + 1);
          e2 = n3;
        } else if (o2 && oe(t2, "!ATTLIST", e2)) e2 += 8;
        else if (o2 && oe(t2, "!NOTATION", e2)) {
          e2 += 9;
          const { index: n3 } = this.readNotationExp(t2, e2 + 1, this.suppressValidationErr);
          e2 = n3;
        } else {
          if (!oe(t2, "!--", e2)) throw new Error("Invalid DOCTYPE");
          i2 = true;
        }
        r2++, s2 = "";
      }
      if (0 !== r2) throw new Error("Unclosed DOCTYPE");
    }
    return { entities: n2, i: e2 };
  }
  readEntityExp(t2, e2) {
    e2 = re(t2, e2);
    let n2 = "";
    for (; e2 < t2.length && !/\s/.test(t2[e2]) && '"' !== t2[e2] && "'" !== t2[e2]; ) n2 += t2[e2], e2++;
    if (ie(n2), e2 = re(t2, e2), !this.suppressValidationErr) {
      if ("SYSTEM" === t2.substring(e2, e2 + 6).toUpperCase()) throw new Error("External entities are not supported");
      if ("%" === t2[e2]) throw new Error("Parameter entities are not supported");
    }
    let r2 = "";
    return [e2, r2] = this.readIdentifierVal(t2, e2, "entity"), [n2, r2, --e2];
  }
  readNotationExp(t2, e2) {
    e2 = re(t2, e2);
    let n2 = "";
    for (; e2 < t2.length && !/\s/.test(t2[e2]); ) n2 += t2[e2], e2++;
    !this.suppressValidationErr && ie(n2), e2 = re(t2, e2);
    const r2 = t2.substring(e2, e2 + 6).toUpperCase();
    if (!this.suppressValidationErr && "SYSTEM" !== r2 && "PUBLIC" !== r2) throw new Error(`Expected SYSTEM or PUBLIC, found "${r2}"`);
    e2 += r2.length, e2 = re(t2, e2);
    let o2 = null, i2 = null;
    if ("PUBLIC" === r2) [e2, o2] = this.readIdentifierVal(t2, e2, "publicIdentifier"), '"' !== t2[e2 = re(t2, e2)] && "'" !== t2[e2] || ([e2, i2] = this.readIdentifierVal(t2, e2, "systemIdentifier"));
    else if ("SYSTEM" === r2 && ([e2, i2] = this.readIdentifierVal(t2, e2, "systemIdentifier"), !this.suppressValidationErr && !i2)) throw new Error("Missing mandatory system identifier for SYSTEM notation");
    return { notationName: n2, publicIdentifier: o2, systemIdentifier: i2, index: --e2 };
  }
  readIdentifierVal(t2, e2, n2) {
    let r2 = "";
    const o2 = t2[e2];
    if ('"' !== o2 && "'" !== o2) throw new Error(`Expected quoted string, found "${o2}"`);
    for (e2++; e2 < t2.length && t2[e2] !== o2; ) r2 += t2[e2], e2++;
    if (t2[e2] !== o2) throw new Error(`Unterminated ${n2} value`);
    return [++e2, r2];
  }
  readElementExp(t2, e2) {
    e2 = re(t2, e2);
    let n2 = "";
    for (; e2 < t2.length && !/\s/.test(t2[e2]); ) n2 += t2[e2], e2++;
    if (!this.suppressValidationErr && !Qt(n2)) throw new Error(`Invalid element name: "${n2}"`);
    let r2 = "";
    if ("E" === t2[e2 = re(t2, e2)] && oe(t2, "MPTY", e2)) e2 += 4;
    else if ("A" === t2[e2] && oe(t2, "NY", e2)) e2 += 2;
    else if ("(" === t2[e2]) {
      for (e2++; e2 < t2.length && ")" !== t2[e2]; ) r2 += t2[e2], e2++;
      if (")" !== t2[e2]) throw new Error("Unterminated content model");
    } else if (!this.suppressValidationErr) throw new Error(`Invalid Element Expression, found "${t2[e2]}"`);
    return { elementName: n2, contentModel: r2.trim(), index: e2 };
  }
  readAttlistExp(t2, e2) {
    e2 = re(t2, e2);
    let n2 = "";
    for (; e2 < t2.length && !/\s/.test(t2[e2]); ) n2 += t2[e2], e2++;
    ie(n2), e2 = re(t2, e2);
    let r2 = "";
    for (; e2 < t2.length && !/\s/.test(t2[e2]); ) r2 += t2[e2], e2++;
    if (!ie(r2)) throw new Error(`Invalid attribute name: "${r2}"`);
    e2 = re(t2, e2);
    let o2 = "";
    if ("NOTATION" === t2.substring(e2, e2 + 8).toUpperCase()) {
      if (o2 = "NOTATION", "(" !== t2[e2 = re(t2, e2 += 8)]) throw new Error(`Expected '(', found "${t2[e2]}"`);
      e2++;
      let n3 = [];
      for (; e2 < t2.length && ")" !== t2[e2]; ) {
        let r3 = "";
        for (; e2 < t2.length && "|" !== t2[e2] && ")" !== t2[e2]; ) r3 += t2[e2], e2++;
        if (r3 = r3.trim(), !ie(r3)) throw new Error(`Invalid notation name: "${r3}"`);
        n3.push(r3), "|" === t2[e2] && (e2++, e2 = re(t2, e2));
      }
      if (")" !== t2[e2]) throw new Error("Unterminated list of notations");
      e2++, o2 += " (" + n3.join("|") + ")";
    } else {
      for (; e2 < t2.length && !/\s/.test(t2[e2]); ) o2 += t2[e2], e2++;
      const n3 = ["CDATA", "ID", "IDREF", "IDREFS", "ENTITY", "ENTITIES", "NMTOKEN", "NMTOKENS"];
      if (!this.suppressValidationErr && !n3.includes(o2.toUpperCase())) throw new Error(`Invalid attribute type: "${o2}"`);
    }
    e2 = re(t2, e2);
    let i2 = "";
    return "#REQUIRED" === t2.substring(e2, e2 + 8).toUpperCase() ? (i2 = "#REQUIRED", e2 += 8) : "#IMPLIED" === t2.substring(e2, e2 + 7).toUpperCase() ? (i2 = "#IMPLIED", e2 += 7) : [e2, i2] = this.readIdentifierVal(t2, e2, "ATTLIST"), { elementName: n2, attributeName: r2, attributeType: o2, defaultValue: i2, index: e2 };
  }
}
const re = (t2, e2) => {
  for (; e2 < t2.length && /\s/.test(t2[e2]); ) e2++;
  return e2;
};
function oe(t2, e2, n2) {
  for (let r2 = 0; r2 < e2.length; r2++) if (e2[r2] !== t2[n2 + r2 + 1]) return false;
  return true;
}
function ie(t2) {
  if (Qt(t2)) return t2;
  throw new Error(`Invalid entity name ${t2}`);
}
const se = /^[-+]?0x[a-fA-F0-9]+$/, ae = /^([\-\+])?(0*)([0-9]*(\.[0-9]*)?)$/, ue = { hex: true, leadingZeros: true, decimalPoint: ".", eNotation: true };
const le = /^([-+])?(0*)(\d*(\.\d*)?[eE][-\+]?\d+)$/;
function ce(t2) {
  return "function" == typeof t2 ? t2 : Array.isArray(t2) ? (e2) => {
    for (const n2 of t2) {
      if ("string" == typeof n2 && e2 === n2) return true;
      if (n2 instanceof RegExp && n2.test(e2)) return true;
    }
  } : () => false;
}
class he {
  constructor(t2) {
    if (this.options = t2, this.currentNode = null, this.tagsNodeStack = [], this.docTypeEntities = {}, this.lastEntities = { apos: { regex: /&(apos|#39|#x27);/g, val: "'" }, gt: { regex: /&(gt|#62|#x3E);/g, val: ">" }, lt: { regex: /&(lt|#60|#x3C);/g, val: "<" }, quot: { regex: /&(quot|#34|#x22);/g, val: '"' } }, this.ampEntity = { regex: /&(amp|#38|#x26);/g, val: "&" }, this.htmlEntities = { space: { regex: /&(nbsp|#160);/g, val: " " }, cent: { regex: /&(cent|#162);/g, val: "¢" }, pound: { regex: /&(pound|#163);/g, val: "£" }, yen: { regex: /&(yen|#165);/g, val: "¥" }, euro: { regex: /&(euro|#8364);/g, val: "€" }, copyright: { regex: /&(copy|#169);/g, val: "©" }, reg: { regex: /&(reg|#174);/g, val: "®" }, inr: { regex: /&(inr|#8377);/g, val: "₹" }, num_dec: { regex: /&#([0-9]{1,7});/g, val: (t3, e2) => Te(e2, 10, "&#") }, num_hex: { regex: /&#x([0-9a-fA-F]{1,6});/g, val: (t3, e2) => Te(e2, 16, "&#x") } }, this.addExternalEntities = pe, this.parseXml = ye, this.parseTextData = fe, this.resolveNameSpace = de, this.buildAttributesMap = me, this.isItStopNode = xe, this.replaceEntitiesValue = be, this.readStopNodeData = Pe, this.saveTextToParentTag = we, this.addChild = ve, this.ignoreAttributesFn = ce(this.options.ignoreAttributes), this.options.stopNodes && this.options.stopNodes.length > 0) {
      this.stopNodesExact = /* @__PURE__ */ new Set(), this.stopNodesWildcard = /* @__PURE__ */ new Set();
      for (let t3 = 0; t3 < this.options.stopNodes.length; t3++) {
        const e2 = this.options.stopNodes[t3];
        "string" == typeof e2 && (e2.startsWith("*.") ? this.stopNodesWildcard.add(e2.substring(2)) : this.stopNodesExact.add(e2));
      }
    }
  }
}
function pe(t2) {
  const e2 = Object.keys(t2);
  for (let n2 = 0; n2 < e2.length; n2++) {
    const r2 = e2[n2];
    this.lastEntities[r2] = { regex: new RegExp("&" + r2 + ";", "g"), val: t2[r2] };
  }
}
function fe(t2, e2, n2, r2, o2, i2, s2) {
  if (void 0 !== t2 && (this.options.trimValues && !r2 && (t2 = t2.trim()), t2.length > 0)) {
    s2 || (t2 = this.replaceEntitiesValue(t2));
    const r3 = this.options.tagValueProcessor(e2, t2, n2, o2, i2);
    return null == r3 ? t2 : typeof r3 != typeof t2 || r3 !== t2 ? r3 : this.options.trimValues || t2.trim() === t2 ? Ae(t2, this.options.parseTagValue, this.options.numberParseOptions) : t2;
  }
}
function de(t2) {
  if (this.options.removeNSPrefix) {
    const e2 = t2.split(":"), n2 = "/" === t2.charAt(0) ? "/" : "";
    if ("xmlns" === e2[0]) return "";
    2 === e2.length && (t2 = n2 + e2[1]);
  }
  return t2;
}
const ge = new RegExp(`([^\\s=]+)\\s*(=\\s*(['"])([\\s\\S]*?)\\3)?`, "gm");
function me(t2, e2) {
  if (true !== this.options.ignoreAttributes && "string" == typeof t2) {
    const n2 = Jt(t2, ge), r2 = n2.length, o2 = {};
    for (let t3 = 0; t3 < r2; t3++) {
      const r3 = this.resolveNameSpace(n2[t3][1]);
      if (this.ignoreAttributesFn(r3, e2)) continue;
      let i2 = n2[t3][4], s2 = this.options.attributeNamePrefix + r3;
      if (r3.length) if (this.options.transformAttributeName && (s2 = this.options.transformAttributeName(s2)), "__proto__" === s2 && (s2 = "#__proto__"), void 0 !== i2) {
        this.options.trimValues && (i2 = i2.trim()), i2 = this.replaceEntitiesValue(i2);
        const t4 = this.options.attributeValueProcessor(r3, i2, e2);
        o2[s2] = null == t4 ? i2 : typeof t4 != typeof i2 || t4 !== i2 ? t4 : Ae(i2, this.options.parseAttributeValue, this.options.numberParseOptions);
      } else this.options.allowBooleanAttributes && (o2[s2] = true);
    }
    if (!Object.keys(o2).length) return;
    if (this.options.attributesGroupName) {
      const t3 = {};
      return t3[this.options.attributesGroupName] = o2, t3;
    }
    return o2;
  }
}
const ye = function(t2) {
  t2 = t2.replace(/\r\n?/g, "\n");
  const e2 = new ee("!xml");
  let n2 = e2, r2 = "", o2 = "";
  const i2 = new ne(this.options.processEntities);
  for (let s2 = 0; s2 < t2.length; s2++) if ("<" === t2[s2]) if ("/" === t2[s2 + 1]) {
    const e3 = Ee(t2, ">", s2, "Closing Tag is not closed.");
    let i3 = t2.substring(s2 + 2, e3).trim();
    if (this.options.removeNSPrefix) {
      const t3 = i3.indexOf(":");
      -1 !== t3 && (i3 = i3.substr(t3 + 1));
    }
    this.options.transformTagName && (i3 = this.options.transformTagName(i3)), n2 && (r2 = this.saveTextToParentTag(r2, n2, o2));
    const a2 = o2.substring(o2.lastIndexOf(".") + 1);
    if (i3 && -1 !== this.options.unpairedTags.indexOf(i3)) throw new Error(`Unpaired tag can not be used as closing tag: </${i3}>`);
    let u2 = 0;
    a2 && -1 !== this.options.unpairedTags.indexOf(a2) ? (u2 = o2.lastIndexOf(".", o2.lastIndexOf(".") - 1), this.tagsNodeStack.pop()) : u2 = o2.lastIndexOf("."), o2 = o2.substring(0, u2), n2 = this.tagsNodeStack.pop(), r2 = "", s2 = e3;
  } else if ("?" === t2[s2 + 1]) {
    let e3 = Ne(t2, s2, false, "?>");
    if (!e3) throw new Error("Pi Tag is not closed.");
    if (r2 = this.saveTextToParentTag(r2, n2, o2), this.options.ignoreDeclaration && "?xml" === e3.tagName || this.options.ignorePiTags) ;
    else {
      const t3 = new ee(e3.tagName);
      t3.add(this.options.textNodeName, ""), e3.tagName !== e3.tagExp && e3.attrExpPresent && (t3[":@"] = this.buildAttributesMap(e3.tagExp, o2)), this.addChild(n2, t3, o2, s2);
    }
    s2 = e3.closeIndex + 1;
  } else if ("!--" === t2.substr(s2 + 1, 3)) {
    const e3 = Ee(t2, "-->", s2 + 4, "Comment is not closed.");
    if (this.options.commentPropName) {
      const i3 = t2.substring(s2 + 4, e3 - 2);
      r2 = this.saveTextToParentTag(r2, n2, o2), n2.add(this.options.commentPropName, [{ [this.options.textNodeName]: i3 }]);
    }
    s2 = e3;
  } else if ("!D" === t2.substr(s2 + 1, 2)) {
    const e3 = i2.readDocType(t2, s2);
    this.docTypeEntities = e3.entities, s2 = e3.i;
  } else if ("![" === t2.substr(s2 + 1, 2)) {
    const e3 = Ee(t2, "]]>", s2, "CDATA is not closed.") - 2, i3 = t2.substring(s2 + 9, e3);
    r2 = this.saveTextToParentTag(r2, n2, o2);
    let a2 = this.parseTextData(i3, n2.tagname, o2, true, false, true, true);
    null == a2 && (a2 = ""), this.options.cdataPropName ? n2.add(this.options.cdataPropName, [{ [this.options.textNodeName]: i3 }]) : n2.add(this.options.textNodeName, a2), s2 = e3 + 2;
  } else {
    let i3 = Ne(t2, s2, this.options.removeNSPrefix), a2 = i3.tagName;
    const u2 = i3.rawTagName;
    let l2 = i3.tagExp, c2 = i3.attrExpPresent, h2 = i3.closeIndex;
    if (this.options.transformTagName) {
      const t3 = this.options.transformTagName(a2);
      l2 === a2 && (l2 = t3), a2 = t3;
    }
    n2 && r2 && "!xml" !== n2.tagname && (r2 = this.saveTextToParentTag(r2, n2, o2, false));
    const p2 = n2;
    p2 && -1 !== this.options.unpairedTags.indexOf(p2.tagname) && (n2 = this.tagsNodeStack.pop(), o2 = o2.substring(0, o2.lastIndexOf("."))), a2 !== e2.tagname && (o2 += o2 ? "." + a2 : a2);
    const f2 = s2;
    if (this.isItStopNode(this.stopNodesExact, this.stopNodesWildcard, o2, a2)) {
      let e3 = "";
      if (l2.length > 0 && l2.lastIndexOf("/") === l2.length - 1) "/" === a2[a2.length - 1] ? (a2 = a2.substr(0, a2.length - 1), o2 = o2.substr(0, o2.length - 1), l2 = a2) : l2 = l2.substr(0, l2.length - 1), s2 = i3.closeIndex;
      else if (-1 !== this.options.unpairedTags.indexOf(a2)) s2 = i3.closeIndex;
      else {
        const n3 = this.readStopNodeData(t2, u2, h2 + 1);
        if (!n3) throw new Error(`Unexpected end of ${u2}`);
        s2 = n3.i, e3 = n3.tagContent;
      }
      const r3 = new ee(a2);
      a2 !== l2 && c2 && (r3[":@"] = this.buildAttributesMap(l2, o2)), e3 && (e3 = this.parseTextData(e3, a2, o2, true, c2, true, true)), o2 = o2.substr(0, o2.lastIndexOf(".")), r3.add(this.options.textNodeName, e3), this.addChild(n2, r3, o2, f2);
    } else {
      if (l2.length > 0 && l2.lastIndexOf("/") === l2.length - 1) {
        if ("/" === a2[a2.length - 1] ? (a2 = a2.substr(0, a2.length - 1), o2 = o2.substr(0, o2.length - 1), l2 = a2) : l2 = l2.substr(0, l2.length - 1), this.options.transformTagName) {
          const t4 = this.options.transformTagName(a2);
          l2 === a2 && (l2 = t4), a2 = t4;
        }
        const t3 = new ee(a2);
        a2 !== l2 && c2 && (t3[":@"] = this.buildAttributesMap(l2, o2)), this.addChild(n2, t3, o2, f2), o2 = o2.substr(0, o2.lastIndexOf("."));
      } else {
        const t3 = new ee(a2);
        this.tagsNodeStack.push(n2), a2 !== l2 && c2 && (t3[":@"] = this.buildAttributesMap(l2, o2)), this.addChild(n2, t3, o2, f2), n2 = t3;
      }
      r2 = "", s2 = h2;
    }
  }
  else r2 += t2[s2];
  return e2.child;
};
function ve(t2, e2, n2, r2) {
  this.options.captureMetaData || (r2 = void 0);
  const o2 = this.options.updateTag(e2.tagname, n2, e2[":@"]);
  false === o2 || ("string" == typeof o2 ? (e2.tagname = o2, t2.addChild(e2, r2)) : t2.addChild(e2, r2));
}
const be = function(t2) {
  if (this.options.processEntities) {
    for (let e2 in this.docTypeEntities) {
      const n2 = this.docTypeEntities[e2];
      t2 = t2.replace(n2.regx, n2.val);
    }
    for (let e2 in this.lastEntities) {
      const n2 = this.lastEntities[e2];
      t2 = t2.replace(n2.regex, n2.val);
    }
    if (this.options.htmlEntities) for (let e2 in this.htmlEntities) {
      const n2 = this.htmlEntities[e2];
      t2 = t2.replace(n2.regex, n2.val);
    }
    t2 = t2.replace(this.ampEntity.regex, this.ampEntity.val);
  }
  return t2;
};
function we(t2, e2, n2, r2) {
  return t2 && (void 0 === r2 && (r2 = 0 === e2.child.length), void 0 !== (t2 = this.parseTextData(t2, e2.tagname, n2, false, !!e2[":@"] && 0 !== Object.keys(e2[":@"]).length, r2)) && "" !== t2 && e2.add(this.options.textNodeName, t2), t2 = ""), t2;
}
function xe(t2, e2, n2, r2) {
  return !(!e2 || !e2.has(r2)) || !(!t2 || !t2.has(n2));
}
function Ee(t2, e2, n2, r2) {
  const o2 = t2.indexOf(e2, n2);
  if (-1 === o2) throw new Error(r2);
  return o2 + e2.length - 1;
}
function Ne(t2, e2, n2) {
  const r2 = (function(t3, e3) {
    let n3, r3 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : ">", o3 = "";
    for (let i3 = e3; i3 < t3.length; i3++) {
      let e4 = t3[i3];
      if (n3) e4 === n3 && (n3 = "");
      else if ('"' === e4 || "'" === e4) n3 = e4;
      else if (e4 === r3[0]) {
        if (!r3[1]) return { data: o3, index: i3 };
        if (t3[i3 + 1] === r3[1]) return { data: o3, index: i3 };
      } else "	" === e4 && (e4 = " ");
      o3 += e4;
    }
  })(t2, e2 + 1, arguments.length > 3 && void 0 !== arguments[3] ? arguments[3] : ">");
  if (!r2) return;
  let o2 = r2.data;
  const i2 = r2.index, s2 = o2.search(/\s/);
  let a2 = o2, u2 = true;
  -1 !== s2 && (a2 = o2.substring(0, s2), o2 = o2.substring(s2 + 1).trimStart());
  const l2 = a2;
  if (n2) {
    const t3 = a2.indexOf(":");
    -1 !== t3 && (a2 = a2.substr(t3 + 1), u2 = a2 !== r2.data.substr(t3 + 1));
  }
  return { tagName: a2, tagExp: o2, closeIndex: i2, attrExpPresent: u2, rawTagName: l2 };
}
function Pe(t2, e2, n2) {
  const r2 = n2;
  let o2 = 1;
  for (; n2 < t2.length; n2++) if ("<" === t2[n2]) if ("/" === t2[n2 + 1]) {
    const i2 = Ee(t2, ">", n2, `${e2} is not closed`);
    if (t2.substring(n2 + 2, i2).trim() === e2 && (o2--, 0 === o2)) return { tagContent: t2.substring(r2, n2), i: i2 };
    n2 = i2;
  } else if ("?" === t2[n2 + 1]) n2 = Ee(t2, "?>", n2 + 1, "StopNode is not closed.");
  else if ("!--" === t2.substr(n2 + 1, 3)) n2 = Ee(t2, "-->", n2 + 3, "StopNode is not closed.");
  else if ("![" === t2.substr(n2 + 1, 2)) n2 = Ee(t2, "]]>", n2, "StopNode is not closed.") - 2;
  else {
    const r3 = Ne(t2, n2, ">");
    r3 && ((r3 && r3.tagName) === e2 && "/" !== r3.tagExp[r3.tagExp.length - 1] && o2++, n2 = r3.closeIndex);
  }
}
function Ae(t2, e2, n2) {
  if (e2 && "string" == typeof t2) {
    const e3 = t2.trim();
    return "true" === e3 || "false" !== e3 && (function(t3) {
      let e4 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
      if (e4 = Object.assign({}, ue, e4), !t3 || "string" != typeof t3) return t3;
      let n3 = t3.trim();
      if (void 0 !== e4.skipLike && e4.skipLike.test(n3)) return t3;
      if ("0" === t3) return 0;
      if (e4.hex && se.test(n3)) return (function(t4) {
        if (parseInt) return parseInt(t4, 16);
        if (Number.parseInt) return Number.parseInt(t4, 16);
        if (window && window.parseInt) return window.parseInt(t4, 16);
        throw new Error("parseInt, Number.parseInt, window.parseInt are not supported");
      })(n3);
      if (n3.includes("e") || n3.includes("E")) return (function(t4, e5, n4) {
        if (!n4.eNotation) return t4;
        const r3 = e5.match(le);
        if (r3) {
          let o2 = r3[1] || "";
          const i2 = -1 === r3[3].indexOf("e") ? "E" : "e", s2 = r3[2], a2 = o2 ? t4[s2.length + 1] === i2 : t4[s2.length] === i2;
          return s2.length > 1 && a2 ? t4 : 1 !== s2.length || !r3[3].startsWith(`.${i2}`) && r3[3][0] !== i2 ? n4.leadingZeros && !a2 ? (e5 = (r3[1] || "") + r3[3], Number(e5)) : t4 : Number(e5);
        }
        return t4;
      })(t3, n3, e4);
      {
        const o2 = ae.exec(n3);
        if (o2) {
          const i2 = o2[1] || "", s2 = o2[2];
          let a2 = (r2 = o2[3]) && -1 !== r2.indexOf(".") ? ("." === (r2 = r2.replace(/0+$/, "")) ? r2 = "0" : "." === r2[0] ? r2 = "0" + r2 : "." === r2[r2.length - 1] && (r2 = r2.substring(0, r2.length - 1)), r2) : r2;
          const u2 = i2 ? "." === t3[s2.length + 1] : "." === t3[s2.length];
          if (!e4.leadingZeros && (s2.length > 1 || 1 === s2.length && !u2)) return t3;
          {
            const r3 = Number(n3), o3 = String(r3);
            if (0 === r3) return r3;
            if (-1 !== o3.search(/[eE]/)) return e4.eNotation ? r3 : t3;
            if (-1 !== n3.indexOf(".")) return "0" === o3 || o3 === a2 || o3 === `${i2}${a2}` ? r3 : t3;
            let u3 = s2 ? a2 : n3;
            return s2 ? u3 === o3 || i2 + u3 === o3 ? r3 : t3 : u3 === o3 || u3 === i2 + o3 ? r3 : t3;
          }
        }
        return t3;
      }
      var r2;
    })(t2, n2);
  }
  return void 0 !== t2 ? t2 : "";
}
function Te(t2, e2, n2) {
  const r2 = Number.parseInt(t2, e2);
  return r2 >= 0 && r2 <= 1114111 ? String.fromCodePoint(r2) : n2 + t2 + ";";
}
const Oe = ee.getMetaDataSymbol();
function Se(t2, e2) {
  return je(t2, e2);
}
function je(t2, e2, n2) {
  let r2;
  const o2 = {};
  for (let i2 = 0; i2 < t2.length; i2++) {
    const s2 = t2[i2], a2 = Ie(s2);
    let u2 = "";
    if (u2 = void 0 === n2 ? a2 : n2 + "." + a2, a2 === e2.textNodeName) void 0 === r2 ? r2 = s2[a2] : r2 += "" + s2[a2];
    else {
      if (void 0 === a2) continue;
      if (s2[a2]) {
        let t3 = je(s2[a2], e2, u2);
        const n3 = Ce(t3, e2);
        void 0 !== s2[Oe] && (t3[Oe] = s2[Oe]), s2[":@"] ? $e(t3, s2[":@"], u2, e2) : 1 !== Object.keys(t3).length || void 0 === t3[e2.textNodeName] || e2.alwaysCreateTextNode ? 0 === Object.keys(t3).length && (e2.alwaysCreateTextNode ? t3[e2.textNodeName] = "" : t3 = "") : t3 = t3[e2.textNodeName], void 0 !== o2[a2] && o2.hasOwnProperty(a2) ? (Array.isArray(o2[a2]) || (o2[a2] = [o2[a2]]), o2[a2].push(t3)) : e2.isArray(a2, u2, n3) ? o2[a2] = [t3] : o2[a2] = t3;
      }
    }
  }
  return "string" == typeof r2 ? r2.length > 0 && (o2[e2.textNodeName] = r2) : void 0 !== r2 && (o2[e2.textNodeName] = r2), o2;
}
function Ie(t2) {
  const e2 = Object.keys(t2);
  for (let t3 = 0; t3 < e2.length; t3++) {
    const n2 = e2[t3];
    if (":@" !== n2) return n2;
  }
}
function $e(t2, e2, n2, r2) {
  if (e2) {
    const o2 = Object.keys(e2), i2 = o2.length;
    for (let s2 = 0; s2 < i2; s2++) {
      const i3 = o2[s2];
      r2.isArray(i3, n2 + "." + i3, true, true) ? t2[i3] = [e2[i3]] : t2[i3] = e2[i3];
    }
  }
}
function Ce(t2, e2) {
  const { textNodeName: n2 } = e2, r2 = Object.keys(t2).length;
  return 0 === r2 || !(1 !== r2 || !t2[n2] && "boolean" != typeof t2[n2] && 0 !== t2[n2]);
}
const Re = { allowBooleanAttributes: false, unpairedTags: [] };
function ke(t2) {
  return " " === t2 || "	" === t2 || "\n" === t2 || "\r" === t2;
}
function Me(t2, e2) {
  const n2 = e2;
  for (; e2 < t2.length; e2++) if ("?" != t2[e2] && " " != t2[e2]) ;
  else {
    const r2 = t2.substr(n2, e2 - n2);
    if (e2 > 5 && "xml" === r2) return Ve("InvalidXml", "XML declaration allowed only at the start of the document.", We(t2, e2));
    if ("?" == t2[e2] && ">" == t2[e2 + 1]) {
      e2++;
      break;
    }
  }
  return e2;
}
function Le(t2, e2) {
  if (t2.length > e2 + 5 && "-" === t2[e2 + 1] && "-" === t2[e2 + 2]) {
    for (e2 += 3; e2 < t2.length; e2++) if ("-" === t2[e2] && "-" === t2[e2 + 1] && ">" === t2[e2 + 2]) {
      e2 += 2;
      break;
    }
  } else if (t2.length > e2 + 8 && "D" === t2[e2 + 1] && "O" === t2[e2 + 2] && "C" === t2[e2 + 3] && "T" === t2[e2 + 4] && "Y" === t2[e2 + 5] && "P" === t2[e2 + 6] && "E" === t2[e2 + 7]) {
    let n2 = 1;
    for (e2 += 8; e2 < t2.length; e2++) if ("<" === t2[e2]) n2++;
    else if (">" === t2[e2] && (n2--, 0 === n2)) break;
  } else if (t2.length > e2 + 9 && "[" === t2[e2 + 1] && "C" === t2[e2 + 2] && "D" === t2[e2 + 3] && "A" === t2[e2 + 4] && "T" === t2[e2 + 5] && "A" === t2[e2 + 6] && "[" === t2[e2 + 7]) {
    for (e2 += 8; e2 < t2.length; e2++) if ("]" === t2[e2] && "]" === t2[e2 + 1] && ">" === t2[e2 + 2]) {
      e2 += 2;
      break;
    }
  }
  return e2;
}
function _e(t2, e2) {
  let n2 = "", r2 = "", o2 = false;
  for (; e2 < t2.length; e2++) {
    if ('"' === t2[e2] || "'" === t2[e2]) "" === r2 ? r2 = t2[e2] : r2 !== t2[e2] || (r2 = "");
    else if (">" === t2[e2] && "" === r2) {
      o2 = true;
      break;
    }
    n2 += t2[e2];
  }
  return "" === r2 && { value: n2, index: e2, tagClosed: o2 };
}
const Ue = new RegExp(`(\\s*)([^\\s=]+)(\\s*=)?(\\s*(['"])(([\\s\\S])*?)\\5)?`, "g");
function De(t2, e2) {
  const n2 = Jt(t2, Ue), r2 = {};
  for (let t3 = 0; t3 < n2.length; t3++) {
    if (0 === n2[t3][1].length) return Ve("InvalidAttr", "Attribute '" + n2[t3][2] + "' has no space in starting.", ze(n2[t3]));
    if (void 0 !== n2[t3][3] && void 0 === n2[t3][4]) return Ve("InvalidAttr", "Attribute '" + n2[t3][2] + "' is without value.", ze(n2[t3]));
    if (void 0 === n2[t3][3] && !e2.allowBooleanAttributes) return Ve("InvalidAttr", "boolean attribute '" + n2[t3][2] + "' is not allowed.", ze(n2[t3]));
    const o2 = n2[t3][2];
    if (!Be(o2)) return Ve("InvalidAttr", "Attribute '" + o2 + "' is an invalid name.", ze(n2[t3]));
    if (r2.hasOwnProperty(o2)) return Ve("InvalidAttr", "Attribute '" + o2 + "' is repeated.", ze(n2[t3]));
    r2[o2] = 1;
  }
  return true;
}
function Fe(t2, e2) {
  if (";" === t2[++e2]) return -1;
  if ("#" === t2[e2]) return (function(t3, e3) {
    let n3 = /\d/;
    for ("x" === t3[e3] && (e3++, n3 = /[\da-fA-F]/); e3 < t3.length; e3++) {
      if (";" === t3[e3]) return e3;
      if (!t3[e3].match(n3)) break;
    }
    return -1;
  })(t2, ++e2);
  let n2 = 0;
  for (; e2 < t2.length; e2++, n2++) if (!(t2[e2].match(/\w/) && n2 < 20)) {
    if (";" === t2[e2]) break;
    return -1;
  }
  return e2;
}
function Ve(t2, e2, n2) {
  return { err: { code: t2, msg: e2, line: n2.line || n2, col: n2.col } };
}
function Be(t2) {
  return Qt(t2);
}
function We(t2, e2) {
  const n2 = t2.substring(0, e2).split(/\r?\n/);
  return { line: n2.length, col: n2[n2.length - 1].length + 1 };
}
function ze(t2) {
  return t2.startIndex + t2[1].length;
}
class Ge {
  constructor(t2) {
    this.externalEntities = {}, this.options = (function(t3) {
      return Object.assign({}, Yt, t3);
    })(t2);
  }
  parse(t2, e2) {
    if ("string" != typeof t2 && t2.toString) t2 = t2.toString();
    else if ("string" != typeof t2) throw new Error("XML data is accepted in String or Bytes[] form.");
    if (e2) {
      true === e2 && (e2 = {});
      const n3 = (function(t3, e3) {
        e3 = Object.assign({}, Re, e3);
        const n4 = [];
        let r3 = false, o2 = false;
        "\uFEFF" === t3[0] && (t3 = t3.substr(1));
        for (let i2 = 0; i2 < t3.length; i2++) if ("<" === t3[i2] && "?" === t3[i2 + 1]) {
          if (i2 += 2, i2 = Me(t3, i2), i2.err) return i2;
        } else {
          if ("<" !== t3[i2]) {
            if (ke(t3[i2])) continue;
            return Ve("InvalidChar", "char '" + t3[i2] + "' is not expected.", We(t3, i2));
          }
          {
            let s2 = i2;
            if (i2++, "!" === t3[i2]) {
              i2 = Le(t3, i2);
              continue;
            }
            {
              let a2 = false;
              "/" === t3[i2] && (a2 = true, i2++);
              let u2 = "";
              for (; i2 < t3.length && ">" !== t3[i2] && " " !== t3[i2] && "	" !== t3[i2] && "\n" !== t3[i2] && "\r" !== t3[i2]; i2++) u2 += t3[i2];
              if (u2 = u2.trim(), "/" === u2[u2.length - 1] && (u2 = u2.substring(0, u2.length - 1), i2--), !Qt(u2)) {
                let e4;
                return e4 = 0 === u2.trim().length ? "Invalid space after '<'." : "Tag '" + u2 + "' is an invalid name.", Ve("InvalidTag", e4, We(t3, i2));
              }
              const l2 = _e(t3, i2);
              if (false === l2) return Ve("InvalidAttr", "Attributes for '" + u2 + "' have open quote.", We(t3, i2));
              let c2 = l2.value;
              if (i2 = l2.index, "/" === c2[c2.length - 1]) {
                const n5 = i2 - c2.length;
                c2 = c2.substring(0, c2.length - 1);
                const o3 = De(c2, e3);
                if (true !== o3) return Ve(o3.err.code, o3.err.msg, We(t3, n5 + o3.err.line));
                r3 = true;
              } else if (a2) {
                if (!l2.tagClosed) return Ve("InvalidTag", "Closing tag '" + u2 + "' doesn't have proper closing.", We(t3, i2));
                if (c2.trim().length > 0) return Ve("InvalidTag", "Closing tag '" + u2 + "' can't have attributes or invalid starting.", We(t3, s2));
                if (0 === n4.length) return Ve("InvalidTag", "Closing tag '" + u2 + "' has not been opened.", We(t3, s2));
                {
                  const e4 = n4.pop();
                  if (u2 !== e4.tagName) {
                    let n5 = We(t3, e4.tagStartPos);
                    return Ve("InvalidTag", "Expected closing tag '" + e4.tagName + "' (opened in line " + n5.line + ", col " + n5.col + ") instead of closing tag '" + u2 + "'.", We(t3, s2));
                  }
                  0 == n4.length && (o2 = true);
                }
              } else {
                const a3 = De(c2, e3);
                if (true !== a3) return Ve(a3.err.code, a3.err.msg, We(t3, i2 - c2.length + a3.err.line));
                if (true === o2) return Ve("InvalidXml", "Multiple possible root nodes found.", We(t3, i2));
                -1 !== e3.unpairedTags.indexOf(u2) || n4.push({ tagName: u2, tagStartPos: s2 }), r3 = true;
              }
              for (i2++; i2 < t3.length; i2++) if ("<" === t3[i2]) {
                if ("!" === t3[i2 + 1]) {
                  i2++, i2 = Le(t3, i2);
                  continue;
                }
                if ("?" !== t3[i2 + 1]) break;
                if (i2 = Me(t3, ++i2), i2.err) return i2;
              } else if ("&" === t3[i2]) {
                const e4 = Fe(t3, i2);
                if (-1 == e4) return Ve("InvalidChar", "char '&' is not expected.", We(t3, i2));
                i2 = e4;
              } else if (true === o2 && !ke(t3[i2])) return Ve("InvalidXml", "Extra text at the end", We(t3, i2));
              "<" === t3[i2] && i2--;
            }
          }
        }
        return r3 ? 1 == n4.length ? Ve("InvalidTag", "Unclosed tag '" + n4[0].tagName + "'.", We(t3, n4[0].tagStartPos)) : !(n4.length > 0) || Ve("InvalidXml", "Invalid '" + JSON.stringify(n4.map(((t4) => t4.tagName)), null, 4).replace(/\r?\n/g, "") + "' found.", { line: 1, col: 1 }) : Ve("InvalidXml", "Start tag expected.", 1);
      })(t2, e2);
      if (true !== n3) throw Error(`${n3.err.msg}:${n3.err.line}:${n3.err.col}`);
    }
    const n2 = new he(this.options);
    n2.addExternalEntities(this.externalEntities);
    const r2 = n2.parseXml(t2);
    return this.options.preserveOrder || void 0 === r2 ? r2 : Se(r2, this.options);
  }
  addEntity(t2, e2) {
    if (-1 !== e2.indexOf("&")) throw new Error("Entity value can't have '&'");
    if (-1 !== t2.indexOf("&") || -1 !== t2.indexOf(";")) throw new Error("An entity must be set without '&' and ';'. Eg. use '#xD' for '&#xD;'");
    if ("&" === e2) throw new Error("An entity with value '&' is not permitted");
    this.externalEntities[t2] = e2;
  }
  static getMetaDataSymbol() {
    return ee.getMetaDataSymbol();
  }
}
var qe = n(829), He = n.n(qe), Ze = (function(t2) {
  return t2.Array = "array", t2.Object = "object", t2.Original = "original", t2;
})(Ze || {});
function Ye(t2, e2) {
  if (!t2.endsWith("propstat.prop.displayname")) return e2;
}
function Xe(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : Ze.Original;
  const r2 = He().get(t2, e2);
  return "array" === n2 && false === Array.isArray(r2) ? [r2] : "object" === n2 && Array.isArray(r2) ? r2[0] : r2;
}
function Ke(t2, e2) {
  return e2 = e2 ?? { attributeNamePrefix: "@", attributeParsers: [], tagParsers: [Ye] }, new Promise(((n2) => {
    n2((function(t3) {
      const { multistatus: e3 } = t3;
      if ("" === e3) return { multistatus: { response: [] } };
      if (!e3) throw new Error("Invalid response: No root multistatus found");
      const n3 = { multistatus: Array.isArray(e3) ? e3[0] : e3 };
      return He().set(n3, "multistatus.response", Xe(n3, "multistatus.response", Ze.Array)), He().set(n3, "multistatus.response", He().get(n3, "multistatus.response").map(((t4) => (function(t5) {
        const e4 = Object.assign({}, t5);
        return e4.status ? He().set(e4, "status", Xe(e4, "status", Ze.Object)) : (He().set(e4, "propstat", Xe(e4, "propstat", Ze.Object)), He().set(e4, "propstat.prop", Xe(e4, "propstat.prop", Ze.Object))), e4;
      })(t4)))), n3;
    })((function(t3) {
      let { attributeNamePrefix: e3, attributeParsers: n3, tagParsers: r2 } = t3;
      return new Ge({ allowBooleanAttributes: true, attributeNamePrefix: e3, textNodeName: "text", ignoreAttributes: false, removeNSPrefix: true, numberParseOptions: { hex: true, leadingZeros: false }, attributeValueProcessor(t4, e4, r3) {
        for (const t5 of n3) try {
          const n4 = t5(r3, e4);
          if (n4 !== e4) return n4;
        } catch (t6) {
        }
        return e4;
      }, tagValueProcessor(t4, e4, n4) {
        for (const t5 of r2) try {
          const r3 = t5(n4, e4);
          if (r3 !== e4) return r3;
        } catch (t6) {
        }
        return e4;
      } });
    })(e2).parse(t2)));
  }));
}
function Je(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] && arguments[2];
  const { getlastmodified: r2 = null, getcontentlength: o2 = "0", resourcetype: i2 = null, getcontenttype: s2 = null, getetag: a2 = null } = t2, u2 = i2 && "object" == typeof i2 && void 0 !== i2.collection ? "directory" : "file", c2 = { filename: e2, basename: l().basename(e2), lastmod: r2, size: parseInt(o2, 10), type: u2, etag: "string" == typeof a2 ? a2.replace(/"/g, "") : null };
  return "file" === u2 && (c2.mime = s2 && "string" == typeof s2 ? s2.split(";")[0] : ""), n2 && (void 0 !== t2.displayname && (t2.displayname = String(t2.displayname)), c2.props = t2), c2;
}
function Qe(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] && arguments[2], r2 = null;
  try {
    t2.multistatus.response[0].propstat && (r2 = t2.multistatus.response[0]);
  } catch (t3) {
  }
  if (!r2) throw new Error("Failed getting item stat: bad response");
  const { propstat: { prop: o2, status: i2 } } = r2, [s2, a2, u2] = i2.split(" ", 3), l2 = parseInt(a2, 10);
  if (l2 >= 400) {
    const t3 = new Error(`Invalid response: ${l2} ${u2}`);
    throw t3.status = l2, t3;
  }
  return Je(o2, d(e2), n2);
}
function tn(t2) {
  switch (String(t2)) {
    case "-3":
      return "unlimited";
    case "-2":
    case "-1":
      return "unknown";
    default:
      return parseInt(String(t2), 10);
  }
}
function en(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
const nn = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const { details: r2 = false } = n2, o2 = J({ url: m(t2.remoteURL, p(e2)), method: "PROPFIND", headers: { Accept: "text/plain,application/xml", Depth: "0" } }, t2, n2);
  return en(K(o2, t2), (function(n3) {
    return Gt(t2, n3), en(n3.text(), (function(o3) {
      return en(Ke(o3, t2.parsing), (function(t3) {
        const o4 = Qe(t3, e2, r2);
        return qt(n3, o4, r2);
      }));
    }));
  }));
}));
function rn(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
const on = sn((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const r2 = (function(t3) {
    if (!t3 || "/" === t3) return [];
    let e3 = t3;
    const n3 = [];
    do {
      n3.push(e3), e3 = l().dirname(e3);
    } while (e3 && "/" !== e3);
    return n3;
  })(d(e2));
  r2.sort(((t3, e3) => t3.length > e3.length ? 1 : e3.length > t3.length ? -1 : 0));
  let o2 = false;
  return (function(t3, e3, n3) {
    if ("function" == typeof t3[ln]) {
      let c2 = function(t4) {
        try {
          for (; !(r3 = s2.next()).done; ) if ((t4 = e3(r3.value)) && t4.then) {
            if (!pn(t4)) return void t4.then(c2, i2 || (i2 = cn.bind(null, o3 = new hn(), 2)));
            t4 = t4.v;
          }
          o3 ? cn(o3, 1, t4) : o3 = t4;
        } catch (t5) {
          cn(o3 || (o3 = new hn()), 2, t5);
        }
      };
      var r3, o3, i2, s2 = t3[ln]();
      if (c2(), s2.return) {
        var a2 = function(t4) {
          try {
            r3.done || s2.return();
          } catch (t5) {
          }
          return t4;
        };
        if (o3 && o3.then) return o3.then(a2, (function(t4) {
          throw a2(t4);
        }));
        a2();
      }
      return o3;
    }
    if (!("length" in t3)) throw new TypeError("Object is not iterable");
    for (var u2 = [], l2 = 0; l2 < t3.length; l2++) u2.push(t3[l2]);
    return (function(t4, e4, n4) {
      var r4, o4, i3 = -1;
      return (function s3(a3) {
        try {
          for (; ++i3 < t4.length && (!n4 || !n4()); ) if ((a3 = e4(i3)) && a3.then) {
            if (!pn(a3)) return void a3.then(s3, o4 || (o4 = cn.bind(null, r4 = new hn(), 2)));
            a3 = a3.v;
          }
          r4 ? cn(r4, 1, a3) : r4 = a3;
        } catch (t5) {
          cn(r4 || (r4 = new hn()), 2, t5);
        }
      })(), r4;
    })(u2, (function(t4) {
      return e3(u2[t4]);
    }), n3);
  })(r2, (function(r3) {
    return i2 = function() {
      return (function(n3, o3) {
        try {
          var i3 = rn(nn(t2, r3), (function(t3) {
            if ("directory" !== t3.type) throw new Error(`Path includes a file: ${e2}`);
          }));
        } catch (t3) {
          return o3(t3);
        }
        return i3 && i3.then ? i3.then(void 0, o3) : i3;
      })(0, (function(e3) {
        const i3 = e3;
        return (function() {
          if (404 === i3.status) return o2 = true, un(fn(t2, r3, { ...n2, recursive: false }));
          throw e3;
        })();
      }));
    }, (s2 = (function() {
      if (o2) return un(fn(t2, r3, { ...n2, recursive: false }));
    })()) && s2.then ? s2.then(i2) : i2();
    var i2, s2;
  }), (function() {
    return false;
  }));
}));
function sn(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
}
function an() {
}
function un(t2, e2) {
  return t2 && t2.then ? t2.then(an) : Promise.resolve();
}
const ln = "undefined" != typeof Symbol ? Symbol.iterator || (Symbol.iterator = /* @__PURE__ */ Symbol("Symbol.iterator")) : "@@iterator";
function cn(t2, e2, n2) {
  if (!t2.s) {
    if (n2 instanceof hn) {
      if (!n2.s) return void (n2.o = cn.bind(null, t2, e2));
      1 & e2 && (e2 = n2.s), n2 = n2.v;
    }
    if (n2 && n2.then) return void n2.then(cn.bind(null, t2, e2), cn.bind(null, t2, 2));
    t2.s = e2, t2.v = n2;
    const r2 = t2.o;
    r2 && r2(t2);
  }
}
const hn = (function() {
  function t2() {
  }
  return t2.prototype.then = function(e2, n2) {
    const r2 = new t2(), o2 = this.s;
    if (o2) {
      const t3 = 1 & o2 ? e2 : n2;
      if (t3) {
        try {
          cn(r2, 1, t3(this.v));
        } catch (t4) {
          cn(r2, 2, t4);
        }
        return r2;
      }
      return this;
    }
    return this.o = function(t3) {
      try {
        const o3 = t3.v;
        1 & t3.s ? cn(r2, 1, e2 ? e2(o3) : o3) : n2 ? cn(r2, 1, n2(o3)) : cn(r2, 2, o3);
      } catch (t4) {
        cn(r2, 2, t4);
      }
    }, r2;
  }, t2;
})();
function pn(t2) {
  return t2 instanceof hn && 1 & t2.s;
}
const fn = sn((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  if (true === n2.recursive) return on(t2, e2, n2);
  const r2 = J({ url: m(t2.remoteURL, (o2 = p(e2), o2.endsWith("/") ? o2 : o2 + "/")), method: "MKCOL" }, t2, n2);
  var o2;
  return rn(K(r2, t2), (function(e3) {
    Gt(t2, e3);
  }));
}));
var dn = n(388), gn = n.n(dn);
const mn = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const r2 = {};
  if ("object" == typeof n2.range && "number" == typeof n2.range.start) {
    let t3 = `bytes=${n2.range.start}-`;
    "number" == typeof n2.range.end && (t3 = `${t3}${n2.range.end}`), r2.Range = t3;
  }
  const o2 = J({ url: m(t2.remoteURL, p(e2)), method: "GET", headers: r2 }, t2, n2);
  return s2 = function(e3) {
    if (Gt(t2, e3), r2.Range && 206 !== e3.status) {
      const t3 = new Error(`Invalid response code for partial request: ${e3.status}`);
      throw t3.status = e3.status, t3;
    }
    return n2.callback && setTimeout((() => {
      n2.callback(e3);
    }), 0), e3.body;
  }, (i2 = K(o2, t2)) && i2.then || (i2 = Promise.resolve(i2)), s2 ? i2.then(s2) : i2;
  var i2, s2;
})), yn = () => {
}, vn = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2, n2) {
  n2.url || (n2.url = m(t2.remoteURL, p(e2)));
  const r2 = J(n2, t2, {});
  return i2 = function(e3) {
    return Gt(t2, e3), e3;
  }, (o2 = K(r2, t2)) && o2.then || (o2 = Promise.resolve(o2)), i2 ? o2.then(i2) : o2;
  var o2, i2;
})), bn = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const r2 = J({ url: m(t2.remoteURL, p(e2)), method: "DELETE" }, t2, n2);
  return i2 = function(e3) {
    Gt(t2, e3);
  }, (o2 = K(r2, t2)) && o2.then || (o2 = Promise.resolve(o2)), i2 ? o2.then(i2) : o2;
  var o2, i2;
})), wn = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  return (function(r2, o2) {
    try {
      var i2 = (s2 = nn(t2, e2, n2), a2 = function() {
        return true;
      }, u2 ? a2 ? a2(s2) : s2 : (s2 && s2.then || (s2 = Promise.resolve(s2)), a2 ? s2.then(a2) : s2));
    } catch (t3) {
      return o2(t3);
    }
    var s2, a2, u2;
    return i2 && i2.then ? i2.then(void 0, o2) : i2;
  })(0, (function(t3) {
    if (404 === t3.status) return false;
    throw t3;
  }));
}));
function xn(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
const En = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const r2 = J({ url: m(t2.remoteURL, p(e2), "/"), method: "PROPFIND", headers: { Accept: "text/plain,application/xml", Depth: n2.deep ? "infinity" : "1" } }, t2, n2);
  return xn(K(r2, t2), (function(r3) {
    return Gt(t2, r3), xn(r3.text(), (function(o2) {
      if (!o2) throw new Error("Failed parsing directory contents: Empty response");
      return xn(Ke(o2, t2.parsing), (function(o3) {
        const i2 = f(e2);
        let s2 = (function(t3, e3, n3) {
          let r4 = arguments.length > 3 && void 0 !== arguments[3] && arguments[3], o4 = arguments.length > 4 && void 0 !== arguments[4] && arguments[4];
          const i3 = l().join(e3, "/"), { multistatus: { response: s3 } } = t3, u2 = s3.map(((t4) => {
            const e4 = (function(t5) {
              try {
                return t5.replace(/^https?:\/\/[^\/]+/, "");
              } catch (t6) {
                throw new a(t6, "Failed normalising HREF");
              }
            })(t4.href), { propstat: { prop: n4 } } = t4;
            return Je(n4, "/" === i3 ? decodeURIComponent(d(e4)) : d(l().relative(decodeURIComponent(i3), decodeURIComponent(e4))), r4);
          }));
          return o4 ? u2 : u2.filter(((t4) => t4.basename && ("file" === t4.type || t4.filename !== n3.replace(/\/$/, ""))));
        })(o3, f(t2.remoteBasePath || t2.remotePath), i2, n2.details, n2.includeSelf);
        return n2.glob && (s2 = (function(t3, e3) {
          return t3.filter(((t4) => yt(t4.filename, e3, { matchBase: true })));
        })(s2, n2.glob)), qt(r3, s2, n2.details);
      }));
    }));
  }));
}));
function Nn(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
}
const Pn = Nn((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const r2 = J({ url: m(t2.remoteURL, p(e2)), method: "GET", headers: { Accept: "text/plain" }, transformResponse: [Sn] }, t2, n2);
  return An(K(r2, t2), (function(e3) {
    return Gt(t2, e3), An(e3.text(), (function(t3) {
      return qt(e3, t3, n2.details);
    }));
  }));
}));
function An(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
const Tn = Nn((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const r2 = J({ url: m(t2.remoteURL, p(e2)), method: "GET" }, t2, n2);
  return An(K(r2, t2), (function(e3) {
    let r3;
    return Gt(t2, e3), (function(t3, e4) {
      var n3 = t3();
      return n3 && n3.then ? n3.then(e4) : e4();
    })((function() {
      return An(e3.arrayBuffer(), (function(t3) {
        r3 = t3;
      }));
    }), (function() {
      return qt(e3, r3, n2.details);
    }));
  }));
})), On = Nn((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const { format: r2 = "binary" } = n2;
  if ("binary" !== r2 && "text" !== r2) throw new a({ info: { code: I.InvalidOutputFormat } }, `Invalid output format: ${r2}`);
  return "text" === r2 ? Pn(t2, e2, n2) : Tn(t2, e2, n2);
})), Sn = (t2) => t2;
function jn(t2, e2) {
  let n2 = "";
  return e2.format && e2.indentBy.length > 0 && (n2 = "\n"), In(t2, e2, "", n2);
}
function In(t2, e2, n2, r2) {
  let o2 = "", i2 = false;
  for (let s2 = 0; s2 < t2.length; s2++) {
    const a2 = t2[s2], u2 = $n(a2);
    if (void 0 === u2) continue;
    let l2 = "";
    if (l2 = 0 === n2.length ? u2 : `${n2}.${u2}`, u2 === e2.textNodeName) {
      let t3 = a2[u2];
      Rn(l2, e2) || (t3 = e2.tagValueProcessor(u2, t3), t3 = kn(t3, e2)), i2 && (o2 += r2), o2 += t3, i2 = false;
      continue;
    }
    if (u2 === e2.cdataPropName) {
      i2 && (o2 += r2), o2 += `<![CDATA[${a2[u2][0][e2.textNodeName]}]]>`, i2 = false;
      continue;
    }
    if (u2 === e2.commentPropName) {
      o2 += r2 + `<!--${a2[u2][0][e2.textNodeName]}-->`, i2 = true;
      continue;
    }
    if ("?" === u2[0]) {
      const t3 = Cn(a2[":@"], e2), n3 = "?xml" === u2 ? "" : r2;
      let s3 = a2[u2][0][e2.textNodeName];
      s3 = 0 !== s3.length ? " " + s3 : "", o2 += n3 + `<${u2}${s3}${t3}?>`, i2 = true;
      continue;
    }
    let c2 = r2;
    "" !== c2 && (c2 += e2.indentBy);
    const h2 = r2 + `<${u2}${Cn(a2[":@"], e2)}`, p2 = In(a2[u2], e2, l2, c2);
    -1 !== e2.unpairedTags.indexOf(u2) ? e2.suppressUnpairedNode ? o2 += h2 + ">" : o2 += h2 + "/>" : p2 && 0 !== p2.length || !e2.suppressEmptyNode ? p2 && p2.endsWith(">") ? o2 += h2 + `>${p2}${r2}</${u2}>` : (o2 += h2 + ">", p2 && "" !== r2 && (p2.includes("/>") || p2.includes("</")) ? o2 += r2 + e2.indentBy + p2 + r2 : o2 += p2, o2 += `</${u2}>`) : o2 += h2 + "/>", i2 = true;
  }
  return o2;
}
function $n(t2) {
  const e2 = Object.keys(t2);
  for (let n2 = 0; n2 < e2.length; n2++) {
    const r2 = e2[n2];
    if (t2.hasOwnProperty(r2) && ":@" !== r2) return r2;
  }
}
function Cn(t2, e2) {
  let n2 = "";
  if (t2 && !e2.ignoreAttributes) for (let r2 in t2) {
    if (!t2.hasOwnProperty(r2)) continue;
    let o2 = e2.attributeValueProcessor(r2, t2[r2]);
    o2 = kn(o2, e2), true === o2 && e2.suppressBooleanAttributes ? n2 += ` ${r2.substr(e2.attributeNamePrefix.length)}` : n2 += ` ${r2.substr(e2.attributeNamePrefix.length)}="${o2}"`;
  }
  return n2;
}
function Rn(t2, e2) {
  let n2 = (t2 = t2.substr(0, t2.length - e2.textNodeName.length - 1)).substr(t2.lastIndexOf(".") + 1);
  for (let r2 in e2.stopNodes) if (e2.stopNodes[r2] === t2 || e2.stopNodes[r2] === "*." + n2) return true;
  return false;
}
function kn(t2, e2) {
  if (t2 && t2.length > 0 && e2.processEntities) for (let n2 = 0; n2 < e2.entities.length; n2++) {
    const r2 = e2.entities[n2];
    t2 = t2.replace(r2.regex, r2.val);
  }
  return t2;
}
const Mn = { attributeNamePrefix: "@_", attributesGroupName: false, textNodeName: "#text", ignoreAttributes: true, cdataPropName: false, format: false, indentBy: "  ", suppressEmptyNode: false, suppressUnpairedNode: true, suppressBooleanAttributes: true, tagValueProcessor: function(t2, e2) {
  return e2;
}, attributeValueProcessor: function(t2, e2) {
  return e2;
}, preserveOrder: false, commentPropName: false, unpairedTags: [], entities: [{ regex: new RegExp("&", "g"), val: "&amp;" }, { regex: new RegExp(">", "g"), val: "&gt;" }, { regex: new RegExp("<", "g"), val: "&lt;" }, { regex: new RegExp("'", "g"), val: "&apos;" }, { regex: new RegExp('"', "g"), val: "&quot;" }], processEntities: true, stopNodes: [], oneListGroup: false };
function Ln(t2) {
  this.options = Object.assign({}, Mn, t2), true === this.options.ignoreAttributes || this.options.attributesGroupName ? this.isAttribute = function() {
    return false;
  } : (this.ignoreAttributesFn = ce(this.options.ignoreAttributes), this.attrPrefixLen = this.options.attributeNamePrefix.length, this.isAttribute = Dn), this.processTextOrObjNode = _n, this.options.format ? (this.indentate = Un, this.tagEndChar = ">\n", this.newLine = "\n") : (this.indentate = function() {
    return "";
  }, this.tagEndChar = ">", this.newLine = "");
}
function _n(t2, e2, n2, r2) {
  const o2 = this.j2x(t2, n2 + 1, r2.concat(e2));
  return void 0 !== t2[this.options.textNodeName] && 1 === Object.keys(t2).length ? this.buildTextValNode(t2[this.options.textNodeName], e2, o2.attrStr, n2) : this.buildObjectNode(o2.val, e2, o2.attrStr, n2);
}
function Un(t2) {
  return this.options.indentBy.repeat(t2);
}
function Dn(t2) {
  return !(!t2.startsWith(this.options.attributeNamePrefix) || t2 === this.options.textNodeName) && t2.substr(this.attrPrefixLen);
}
function Fn(t2) {
  return new Ln({ attributeNamePrefix: "@_", format: true, ignoreAttributes: false, suppressEmptyNode: true }).build(Vn({ lockinfo: { "@_xmlns:d": "DAV:", lockscope: { exclusive: {} }, locktype: { write: {} }, owner: { href: t2 } } }, "d"));
}
function Vn(t2, e2) {
  const n2 = { ...t2 };
  for (const t3 in n2) n2.hasOwnProperty(t3) && (n2[t3] && "object" == typeof n2[t3] && -1 === t3.indexOf(":") ? (n2[`${e2}:${t3}`] = Vn(n2[t3], e2), delete n2[t3]) : false === /^@_/.test(t3) && (n2[`${e2}:${t3}`] = n2[t3], delete n2[t3]));
  return n2;
}
function Bn(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
function Wn(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
}
Ln.prototype.build = function(t2) {
  return this.options.preserveOrder ? jn(t2, this.options) : (Array.isArray(t2) && this.options.arrayNodeName && this.options.arrayNodeName.length > 1 && (t2 = { [this.options.arrayNodeName]: t2 }), this.j2x(t2, 0, []).val);
}, Ln.prototype.j2x = function(t2, e2, n2) {
  let r2 = "", o2 = "";
  const i2 = n2.join(".");
  for (let s2 in t2) if (Object.prototype.hasOwnProperty.call(t2, s2)) if (void 0 === t2[s2]) this.isAttribute(s2) && (o2 += "");
  else if (null === t2[s2]) this.isAttribute(s2) || s2 === this.options.cdataPropName ? o2 += "" : "?" === s2[0] ? o2 += this.indentate(e2) + "<" + s2 + "?" + this.tagEndChar : o2 += this.indentate(e2) + "<" + s2 + "/" + this.tagEndChar;
  else if (t2[s2] instanceof Date) o2 += this.buildTextValNode(t2[s2], s2, "", e2);
  else if ("object" != typeof t2[s2]) {
    const n3 = this.isAttribute(s2);
    if (n3 && !this.ignoreAttributesFn(n3, i2)) r2 += this.buildAttrPairStr(n3, "" + t2[s2]);
    else if (!n3) if (s2 === this.options.textNodeName) {
      let e3 = this.options.tagValueProcessor(s2, "" + t2[s2]);
      o2 += this.replaceEntitiesValue(e3);
    } else o2 += this.buildTextValNode(t2[s2], s2, "", e2);
  } else if (Array.isArray(t2[s2])) {
    const r3 = t2[s2].length;
    let i3 = "", a2 = "";
    for (let u2 = 0; u2 < r3; u2++) {
      const r4 = t2[s2][u2];
      if (void 0 === r4) ;
      else if (null === r4) "?" === s2[0] ? o2 += this.indentate(e2) + "<" + s2 + "?" + this.tagEndChar : o2 += this.indentate(e2) + "<" + s2 + "/" + this.tagEndChar;
      else if ("object" == typeof r4) if (this.options.oneListGroup) {
        const t3 = this.j2x(r4, e2 + 1, n2.concat(s2));
        i3 += t3.val, this.options.attributesGroupName && r4.hasOwnProperty(this.options.attributesGroupName) && (a2 += t3.attrStr);
      } else i3 += this.processTextOrObjNode(r4, s2, e2, n2);
      else if (this.options.oneListGroup) {
        let t3 = this.options.tagValueProcessor(s2, r4);
        t3 = this.replaceEntitiesValue(t3), i3 += t3;
      } else i3 += this.buildTextValNode(r4, s2, "", e2);
    }
    this.options.oneListGroup && (i3 = this.buildObjectNode(i3, s2, a2, e2)), o2 += i3;
  } else if (this.options.attributesGroupName && s2 === this.options.attributesGroupName) {
    const e3 = Object.keys(t2[s2]), n3 = e3.length;
    for (let o3 = 0; o3 < n3; o3++) r2 += this.buildAttrPairStr(e3[o3], "" + t2[s2][e3[o3]]);
  } else o2 += this.processTextOrObjNode(t2[s2], s2, e2, n2);
  return { attrStr: r2, val: o2 };
}, Ln.prototype.buildAttrPairStr = function(t2, e2) {
  return e2 = this.options.attributeValueProcessor(t2, "" + e2), e2 = this.replaceEntitiesValue(e2), this.options.suppressBooleanAttributes && "true" === e2 ? " " + t2 : " " + t2 + '="' + e2 + '"';
}, Ln.prototype.buildObjectNode = function(t2, e2, n2, r2) {
  if ("" === t2) return "?" === e2[0] ? this.indentate(r2) + "<" + e2 + n2 + "?" + this.tagEndChar : this.indentate(r2) + "<" + e2 + n2 + this.closeTag(e2) + this.tagEndChar;
  {
    let o2 = "</" + e2 + this.tagEndChar, i2 = "";
    return "?" === e2[0] && (i2 = "?", o2 = ""), !n2 && "" !== n2 || -1 !== t2.indexOf("<") ? false !== this.options.commentPropName && e2 === this.options.commentPropName && 0 === i2.length ? this.indentate(r2) + `<!--${t2}-->` + this.newLine : this.indentate(r2) + "<" + e2 + n2 + i2 + this.tagEndChar + t2 + this.indentate(r2) + o2 : this.indentate(r2) + "<" + e2 + n2 + i2 + ">" + t2 + o2;
  }
}, Ln.prototype.closeTag = function(t2) {
  let e2 = "";
  return -1 !== this.options.unpairedTags.indexOf(t2) ? this.options.suppressUnpairedNode || (e2 = "/") : e2 = this.options.suppressEmptyNode ? "/" : `></${t2}`, e2;
}, Ln.prototype.buildTextValNode = function(t2, e2, n2, r2) {
  if (false !== this.options.cdataPropName && e2 === this.options.cdataPropName) return this.indentate(r2) + `<![CDATA[${t2}]]>` + this.newLine;
  if (false !== this.options.commentPropName && e2 === this.options.commentPropName) return this.indentate(r2) + `<!--${t2}-->` + this.newLine;
  if ("?" === e2[0]) return this.indentate(r2) + "<" + e2 + n2 + "?" + this.tagEndChar;
  {
    let o2 = this.options.tagValueProcessor(e2, t2);
    return o2 = this.replaceEntitiesValue(o2), "" === o2 ? this.indentate(r2) + "<" + e2 + n2 + this.closeTag(e2) + this.tagEndChar : this.indentate(r2) + "<" + e2 + n2 + ">" + o2 + "</" + e2 + this.tagEndChar;
  }
}, Ln.prototype.replaceEntitiesValue = function(t2) {
  if (t2 && t2.length > 0 && this.options.processEntities) for (let e2 = 0; e2 < this.options.entities.length; e2++) {
    const n2 = this.options.entities[e2];
    t2 = t2.replace(n2.regex, n2.val);
  }
  return t2;
};
const zn = Wn((function(t2, e2, n2) {
  let r2 = arguments.length > 3 && void 0 !== arguments[3] ? arguments[3] : {};
  const o2 = J({ url: m(t2.remoteURL, p(e2)), method: "UNLOCK", headers: { "Lock-Token": n2 } }, t2, r2);
  return Bn(K(o2, t2), (function(e3) {
    if (Gt(t2, e3), 204 !== e3.status && 200 !== e3.status) throw zt(e3);
  }));
})), Gn = Wn((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const { refreshToken: r2, timeout: o2 = qn } = n2, i2 = { Accept: "text/plain,application/xml", Timeout: o2 };
  r2 && (i2.If = r2);
  const s2 = J({ url: m(t2.remoteURL, p(e2)), method: "LOCK", headers: i2, data: Fn(t2.contactHref) }, t2, n2);
  return Bn(K(s2, t2), (function(e3) {
    return Gt(t2, e3), Bn(e3.text(), (function(t3) {
      const n3 = (i3 = t3, new Ge({ removeNSPrefix: true, parseAttributeValue: true, parseTagValue: true }).parse(i3)), r3 = He().get(n3, "prop.lockdiscovery.activelock.locktoken.href"), o3 = He().get(n3, "prop.lockdiscovery.activelock.timeout");
      var i3;
      if (!r3) throw zt(e3, "No lock token received: ");
      return { token: r3, serverTimeout: o3 };
    }));
  }));
})), qn = "Infinite, Second-4100000000";
function Hn(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
const Zn = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2) {
  let e2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
  const n2 = e2.path || "/", r2 = J({ url: m(t2.remoteURL, n2), method: "PROPFIND", headers: { Accept: "text/plain,application/xml", Depth: "0" } }, t2, e2);
  return Hn(K(r2, t2), (function(n3) {
    return Gt(t2, n3), Hn(n3.text(), (function(r3) {
      return Hn(Ke(r3, t2.parsing), (function(t3) {
        const r4 = (function(t4) {
          try {
            const [e3] = t4.multistatus.response, { propstat: { prop: { "quota-used-bytes": n4, "quota-available-bytes": r5 } } } = e3;
            return void 0 !== n4 && void 0 !== r5 ? { used: parseInt(String(n4), 10), available: tn(r5) } : null;
          } catch (t5) {
          }
          return null;
        })(t3);
        return qt(n3, r4, e2.details);
      }));
    }));
  }));
}));
function Yn(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
const Xn = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const { details: r2 = false } = n2, o2 = J({ url: m(t2.remoteURL, p(e2)), method: "SEARCH", headers: { Accept: "text/plain,application/xml", "Content-Type": t2.headers["Content-Type"] || "application/xml; charset=utf-8" } }, t2, n2);
  return Yn(K(o2, t2), (function(n3) {
    return Gt(t2, n3), Yn(n3.text(), (function(o3) {
      return Yn(Ke(o3, t2.parsing), (function(t3) {
        const o4 = (function(t4, e3, n4) {
          const r3 = { truncated: false, results: [] };
          return r3.truncated = t4.multistatus.response.some(((t5) => "507" === (t5.status || t5.propstat?.status).split(" ", 3)?.[1] && t5.href.replace(/\/$/, "").endsWith(p(e3).replace(/\/$/, "")))), t4.multistatus.response.forEach(((t5) => {
            if (void 0 === t5.propstat) return;
            const e4 = t5.href.split("/").map(decodeURIComponent).join("/");
            r3.results.push(Je(t5.propstat.prop, e4, n4));
          })), r3;
        })(t3, e2, r2);
        return qt(n3, o4, r2);
      }));
    }));
  }));
})), Kn = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2, n2) {
  let r2 = arguments.length > 3 && void 0 !== arguments[3] ? arguments[3] : {};
  const o2 = J({ url: m(t2.remoteURL, p(e2)), method: "MOVE", headers: { Destination: m(t2.remoteURL, p(n2)), Overwrite: false === r2.overwrite ? "F" : "T" } }, t2, r2);
  return s2 = function(e3) {
    Gt(t2, e3);
  }, (i2 = K(o2, t2)) && i2.then || (i2 = Promise.resolve(i2)), s2 ? i2.then(s2) : i2;
  var i2, s2;
}));
var Jn = n(172);
function Qn(t2) {
  if (G(t2)) return t2.byteLength;
  if (q(t2)) return t2.length;
  if ("string" == typeof t2) return (0, Jn.d)(t2);
  throw new a({ info: { code: I.DataTypeNoLength } }, "Cannot calculate data length: Invalid type");
}
const tr = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2, n2) {
  let r2 = arguments.length > 3 && void 0 !== arguments[3] ? arguments[3] : {};
  const { contentLength: o2 = true, overwrite: i2 = true } = r2, s2 = { "Content-Type": "application/octet-stream" };
  false === o2 || (s2["Content-Length"] = "number" == typeof o2 ? `${o2}` : `${Qn(n2)}`), i2 || (s2["If-None-Match"] = "*");
  const a2 = J({ url: m(t2.remoteURL, p(e2)), method: "PUT", headers: s2, data: n2 }, t2, r2);
  return l2 = function(e3) {
    try {
      Gt(t2, e3);
    } catch (t3) {
      const e4 = t3;
      if (412 !== e4.status || i2) throw e4;
      return false;
    }
    return true;
  }, (u2 = K(a2, t2)) && u2.then || (u2 = Promise.resolve(u2)), l2 ? u2.then(l2) : u2;
  var u2, l2;
})), er = /* @__PURE__ */ (function(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
})((function(t2, e2) {
  let n2 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
  const r2 = J({ url: m(t2.remoteURL, p(e2)), method: "OPTIONS" }, t2, n2);
  return i2 = function(e3) {
    try {
      Gt(t2, e3);
    } catch (t3) {
      throw t3;
    }
    return { compliance: (e3.headers.get("DAV") ?? "").split(",").map(((t3) => t3.trim())), server: e3.headers.get("Server") ?? "" };
  }, (o2 = K(r2, t2)) && o2.then || (o2 = Promise.resolve(o2)), i2 ? o2.then(i2) : o2;
  var o2, i2;
}));
function nr(t2, e2, n2) {
  return n2 ? e2 ? e2(t2) : t2 : (t2 && t2.then || (t2 = Promise.resolve(t2)), e2 ? t2.then(e2) : t2);
}
const rr = sr((function(t2, e2, n2, r2, o2) {
  let i2 = arguments.length > 5 && void 0 !== arguments[5] ? arguments[5] : {};
  if (n2 > r2 || n2 < 0) throw new a({ info: { code: I.InvalidUpdateRange } }, `Invalid update range ${n2} for partial update`);
  const s2 = { "Content-Type": "application/octet-stream", "Content-Length": "" + (r2 - n2 + 1), "Content-Range": `bytes ${n2}-${r2}/*` }, u2 = J({ url: m(t2.remoteURL, p(e2)), method: "PUT", headers: s2, data: o2 }, t2, i2);
  return nr(K(u2, t2), (function(e3) {
    Gt(t2, e3);
  }));
}));
function or(t2, e2) {
  var n2 = t2();
  return n2 && n2.then ? n2.then(e2) : e2(n2);
}
const ir = sr((function(t2, e2, n2, r2, o2) {
  let i2 = arguments.length > 5 && void 0 !== arguments[5] ? arguments[5] : {};
  if (n2 > r2 || n2 < 0) throw new a({ info: { code: I.InvalidUpdateRange } }, `Invalid update range ${n2} for partial update`);
  const s2 = { "Content-Type": "application/x-sabredav-partialupdate", "Content-Length": "" + (r2 - n2 + 1), "X-Update-Range": `bytes=${n2}-${r2}` }, u2 = J({ url: m(t2.remoteURL, p(e2)), method: "PATCH", headers: s2, data: o2 }, t2, i2);
  return nr(K(u2, t2), (function(e3) {
    Gt(t2, e3);
  }));
}));
function sr(t2) {
  return function() {
    for (var e2 = [], n2 = 0; n2 < arguments.length; n2++) e2[n2] = arguments[n2];
    try {
      return Promise.resolve(t2.apply(this, e2));
    } catch (t3) {
      return Promise.reject(t3);
    }
  };
}
const ar = sr((function(t2, e2, n2, r2, o2) {
  let i2 = arguments.length > 5 && void 0 !== arguments[5] ? arguments[5] : {};
  return nr(er(t2, e2, i2), (function(s2) {
    let u2 = false;
    return or((function() {
      if (s2.compliance.includes("sabredav-partialupdate")) return nr(ir(t2, e2, n2, r2, o2, i2), (function(t3) {
        return u2 = true, t3;
      }));
    }), (function(l2) {
      let c2 = false;
      return u2 ? l2 : or((function() {
        if (s2.server.includes("Apache") && s2.compliance.includes("<http://apache.org/dav/propset/fs/1>")) return nr(rr(t2, e2, n2, r2, o2, i2), (function(t3) {
          return c2 = true, t3;
        }));
      }), (function(t3) {
        if (c2) return t3;
        throw new a({ info: { code: I.NotSupported } }, "Not supported");
      }));
    }));
  }));
})), ur = "https://github.com/perry-mitchell/webdav-client/blob/master/LOCK_CONTACT.md";
function lr(t2) {
  let e2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
  const { authType: n2 = null, remoteBasePath: r2, contactHref: o2 = ur, ha1: i2, headers: s2 = {}, httpAgent: u2, httpsAgent: l2, password: c2, token: h2, username: f2, withCredentials: d2 } = e2;
  let y2 = n2;
  y2 || (y2 = f2 || c2 ? j.Password : j.None);
  const v2 = { authType: y2, remoteBasePath: r2, contactHref: o2, ha1: i2, headers: Object.assign({}, s2), httpAgent: u2, httpsAgent: l2, password: c2, parsing: { attributeNamePrefix: e2.attributeNamePrefix ?? "@", attributeParsers: [], tagParsers: [Ye] }, remotePath: g(t2), remoteURL: t2, token: h2, username: f2, withCredentials: d2 };
  return $(v2, f2, c2, h2, i2), { copyFile: (t3, e3, n3) => Ht(v2, t3, e3, n3), createDirectory: (t3, e3) => fn(v2, t3, e3), createReadStream: (t3, e3) => (function(t4, e4) {
    let n3 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
    const r3 = new (gn()).PassThrough();
    return mn(t4, e4, n3).then(((t5) => {
      t5.pipe(r3);
    })).catch(((t5) => {
      r3.emit("error", t5);
    })), r3;
  })(v2, t3, e3), createWriteStream: (t3, e3, n3) => (function(t4, e4) {
    let n4 = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {}, r3 = arguments.length > 3 && void 0 !== arguments[3] ? arguments[3] : yn;
    const o3 = new (gn()).PassThrough(), i3 = {};
    false === n4.overwrite && (i3["If-None-Match"] = "*");
    const s3 = J({ url: m(t4.remoteURL, p(e4)), method: "PUT", headers: i3, data: o3, maxRedirects: 0 }, t4, n4);
    return K(s3, t4).then(((e5) => Gt(t4, e5))).then(((t5) => {
      setTimeout((() => {
        r3(t5);
      }), 0);
    })).catch(((t5) => {
      o3.emit("error", t5);
    })), o3;
  })(v2, t3, e3, n3), customRequest: (t3, e3) => vn(v2, t3, e3), deleteFile: (t3, e3) => bn(v2, t3, e3), exists: (t3, e3) => wn(v2, t3, e3), getDirectoryContents: (t3, e3) => En(v2, t3, e3), getFileContents: (t3, e3) => On(v2, t3, e3), getFileDownloadLink: (t3) => (function(t4, e3) {
    let n3 = m(t4.remoteURL, p(e3));
    const r3 = /^https:/i.test(n3) ? "https" : "http";
    switch (t4.authType) {
      case j.None:
        break;
      case j.Password: {
        const e4 = N(t4.headers.Authorization.replace(/^Basic /i, "").trim());
        n3 = n3.replace(/^https?:\/\//, `${r3}://${e4}@`);
        break;
      }
      default:
        throw new a({ info: { code: I.LinkUnsupportedAuthType } }, `Unsupported auth type for file link: ${t4.authType}`);
    }
    return n3;
  })(v2, t3), getFileUploadLink: (t3) => (function(t4, e3) {
    let n3 = `${m(t4.remoteURL, p(e3))}?Content-Type=application/octet-stream`;
    const r3 = /^https:/i.test(n3) ? "https" : "http";
    switch (t4.authType) {
      case j.None:
        break;
      case j.Password: {
        const e4 = N(t4.headers.Authorization.replace(/^Basic /i, "").trim());
        n3 = n3.replace(/^https?:\/\//, `${r3}://${e4}@`);
        break;
      }
      default:
        throw new a({ info: { code: I.LinkUnsupportedAuthType } }, `Unsupported auth type for file link: ${t4.authType}`);
    }
    return n3;
  })(v2, t3), getHeaders: () => Object.assign({}, v2.headers), getQuota: (t3) => Zn(v2, t3), lock: (t3, e3) => Gn(v2, t3, e3), moveFile: (t3, e3, n3) => Kn(v2, t3, e3, n3), putFileContents: (t3, e3, n3) => tr(v2, t3, e3, n3), partialUpdateFileContents: (t3, e3, n3, r3, o3) => ar(v2, t3, e3, n3, r3, o3), getDAVCompliance: (t3) => er(v2, t3), search: (t3, e3) => Xn(v2, t3, e3), setHeaders: (t3) => {
    v2.headers = Object.assign({}, t3);
  }, stat: (t3, e3) => nn(v2, t3, e3), unlock: (t3, e3, n3) => zn(v2, t3, e3, n3), registerAttributeParser: (t3) => {
    v2.parsing.attributeParsers.push(t3);
  }, registerTagParser: (t3) => {
    v2.parsing.tagParsers.push(t3);
  } };
}
export {
  Ke as K,
  _,
  lr as l
};
//# sourceMappingURL=index-595Vk4Ec.chunk.mjs.map
