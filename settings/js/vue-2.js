(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[2],{

/***/ "./settings/node_modules/dompurify/dist/purify.js":
/*!********************************************************!*\
  !*** ./settings/node_modules/dompurify/dist/purify.js ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

(function (global, factory) {
	 true ? module.exports = factory() :
	undefined;
}(this, (function () { 'use strict';

var freeze$1 = Object.freeze || function (x) {
  return x;
};

var html = freeze$1(['a', 'abbr', 'acronym', 'address', 'area', 'article', 'aside', 'audio', 'b', 'bdi', 'bdo', 'big', 'blink', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'content', 'data', 'datalist', 'dd', 'decorator', 'del', 'details', 'dfn', 'dir', 'div', 'dl', 'dt', 'element', 'em', 'fieldset', 'figcaption', 'figure', 'font', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'i', 'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'main', 'map', 'mark', 'marquee', 'menu', 'menuitem', 'meter', 'nav', 'nobr', 'ol', 'optgroup', 'option', 'output', 'p', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'select', 'shadow', 'small', 'source', 'spacer', 'span', 'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead', 'time', 'tr', 'track', 'tt', 'u', 'ul', 'var', 'video', 'wbr']);

// SVG
var svg = freeze$1(['svg', 'a', 'altglyph', 'altglyphdef', 'altglyphitem', 'animatecolor', 'animatemotion', 'animatetransform', 'audio', 'canvas', 'circle', 'clippath', 'defs', 'desc', 'ellipse', 'filter', 'font', 'g', 'glyph', 'glyphref', 'hkern', 'image', 'line', 'lineargradient', 'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon', 'polyline', 'radialgradient', 'rect', 'stop', 'style', 'switch', 'symbol', 'text', 'textpath', 'title', 'tref', 'tspan', 'video', 'view', 'vkern']);

var svgFilters = freeze$1(['feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR', 'feGaussianBlur', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset', 'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence']);

var mathMl = freeze$1(['math', 'menclose', 'merror', 'mfenced', 'mfrac', 'mglyph', 'mi', 'mlabeledtr', 'mmultiscripts', 'mn', 'mo', 'mover', 'mpadded', 'mphantom', 'mroot', 'mrow', 'ms', 'mspace', 'msqrt', 'mstyle', 'msub', 'msup', 'msubsup', 'mtable', 'mtd', 'mtext', 'mtr', 'munder', 'munderover']);

var text = freeze$1(['#text']);

var freeze$2 = Object.freeze || function (x) {
  return x;
};

var html$1 = freeze$2(['accept', 'action', 'align', 'alt', 'autocomplete', 'background', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'checked', 'cite', 'class', 'clear', 'color', 'cols', 'colspan', 'coords', 'crossorigin', 'datetime', 'default', 'dir', 'disabled', 'download', 'enctype', 'face', 'for', 'headers', 'height', 'hidden', 'high', 'href', 'hreflang', 'id', 'integrity', 'ismap', 'label', 'lang', 'list', 'loop', 'low', 'max', 'maxlength', 'media', 'method', 'min', 'multiple', 'name', 'noshade', 'novalidate', 'nowrap', 'open', 'optimum', 'pattern', 'placeholder', 'poster', 'preload', 'pubdate', 'radiogroup', 'readonly', 'rel', 'required', 'rev', 'reversed', 'role', 'rows', 'rowspan', 'spellcheck', 'scope', 'selected', 'shape', 'size', 'sizes', 'span', 'srclang', 'start', 'src', 'srcset', 'step', 'style', 'summary', 'tabindex', 'title', 'type', 'usemap', 'valign', 'value', 'width', 'xmlns']);

var svg$1 = freeze$2(['accent-height', 'accumulate', 'additive', 'alignment-baseline', 'ascent', 'attributename', 'attributetype', 'azimuth', 'basefrequency', 'baseline-shift', 'begin', 'bias', 'by', 'class', 'clip', 'clip-path', 'clip-rule', 'color', 'color-interpolation', 'color-interpolation-filters', 'color-profile', 'color-rendering', 'cx', 'cy', 'd', 'dx', 'dy', 'diffuseconstant', 'direction', 'display', 'divisor', 'dur', 'edgemode', 'elevation', 'end', 'fill', 'fill-opacity', 'fill-rule', 'filter', 'flood-color', 'flood-opacity', 'font-family', 'font-size', 'font-size-adjust', 'font-stretch', 'font-style', 'font-variant', 'font-weight', 'fx', 'fy', 'g1', 'g2', 'glyph-name', 'glyphref', 'gradientunits', 'gradienttransform', 'height', 'href', 'id', 'image-rendering', 'in', 'in2', 'k', 'k1', 'k2', 'k3', 'k4', 'kerning', 'keypoints', 'keysplines', 'keytimes', 'lang', 'lengthadjust', 'letter-spacing', 'kernelmatrix', 'kernelunitlength', 'lighting-color', 'local', 'marker-end', 'marker-mid', 'marker-start', 'markerheight', 'markerunits', 'markerwidth', 'maskcontentunits', 'maskunits', 'max', 'mask', 'media', 'method', 'mode', 'min', 'name', 'numoctaves', 'offset', 'operator', 'opacity', 'order', 'orient', 'orientation', 'origin', 'overflow', 'paint-order', 'path', 'pathlength', 'patterncontentunits', 'patterntransform', 'patternunits', 'points', 'preservealpha', 'preserveaspectratio', 'r', 'rx', 'ry', 'radius', 'refx', 'refy', 'repeatcount', 'repeatdur', 'restart', 'result', 'rotate', 'scale', 'seed', 'shape-rendering', 'specularconstant', 'specularexponent', 'spreadmethod', 'stddeviation', 'stitchtiles', 'stop-color', 'stop-opacity', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'stroke', 'stroke-width', 'style', 'surfacescale', 'tabindex', 'targetx', 'targety', 'transform', 'text-anchor', 'text-decoration', 'text-rendering', 'textlength', 'type', 'u1', 'u2', 'unicode', 'values', 'viewbox', 'visibility', 'vert-adv-y', 'vert-origin-x', 'vert-origin-y', 'width', 'word-spacing', 'wrap', 'writing-mode', 'xchannelselector', 'ychannelselector', 'x', 'x1', 'x2', 'xmlns', 'y', 'y1', 'y2', 'z', 'zoomandpan']);

var mathMl$1 = freeze$2(['accent', 'accentunder', 'align', 'bevelled', 'close', 'columnsalign', 'columnlines', 'columnspan', 'denomalign', 'depth', 'dir', 'display', 'displaystyle', 'fence', 'frame', 'height', 'href', 'id', 'largeop', 'length', 'linethickness', 'lspace', 'lquote', 'mathbackground', 'mathcolor', 'mathsize', 'mathvariant', 'maxsize', 'minsize', 'movablelimits', 'notation', 'numalign', 'open', 'rowalign', 'rowlines', 'rowspacing', 'rowspan', 'rspace', 'rquote', 'scriptlevel', 'scriptminsize', 'scriptsizemultiplier', 'selection', 'separator', 'separators', 'stretchy', 'subscriptshift', 'supscriptshift', 'symmetric', 'voffset', 'width', 'xmlns']);

var xml = freeze$2(['xlink:href', 'xml:id', 'xlink:title', 'xml:space', 'xmlns:xlink']);

var hasOwnProperty = Object.hasOwnProperty;
var setPrototypeOf = Object.setPrototypeOf;

var _ref$1 = typeof Reflect !== 'undefined' && Reflect;
var apply$1 = _ref$1.apply;

if (!apply$1) {
  apply$1 = function apply(fun, thisValue, args) {
    return fun.apply(thisValue, args);
  };
}

/* Add properties to a lookup table */
function addToSet(set, array) {
  if (setPrototypeOf) {
    // Make 'in' and truthy checks like Boolean(set.constructor)
    // independent of any properties defined on Object.prototype.
    // Prevent prototype setters from intercepting set as a this value.
    setPrototypeOf(set, null);
  }
  var l = array.length;
  while (l--) {
    var element = array[l];
    if (typeof element === 'string') {
      var lcElement = element.toLowerCase();
      if (lcElement !== element) {
        array[l] = lcElement;
        element = lcElement;
      }
    }
    set[element] = true;
  }
  return set;
}

/* Shallow clone an object */
function clone(object) {
  var newObject = {};
  var property = void 0;
  for (property in object) {
    if (apply$1(hasOwnProperty, object, [property])) {
      newObject[property] = object[property];
    }
  }
  return newObject;
}

var seal = Object.seal || function (x) {
  return x;
};

var MUSTACHE_EXPR = seal(/\{\{[\s\S]*|[\s\S]*\}\}/gm); // Specify template detection regex for SAFE_FOR_TEMPLATES mode
var ERB_EXPR = seal(/<%[\s\S]*|[\s\S]*%>/gm);
var DATA_ATTR = seal(/^data-[\-\w.\u00B7-\uFFFF]/); // eslint-disable-line no-useless-escape
var ARIA_ATTR = seal(/^aria-[\-\w]+$/); // eslint-disable-line no-useless-escape
var IS_ALLOWED_URI = seal(/^(?:(?:(?:f|ht)tps?|mailto|tel|callto|cid|xmpp):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i // eslint-disable-line no-useless-escape
);
var IS_SCRIPT_OR_DATA = seal(/^(?:\w+script|data):/i);
var ATTR_WHITESPACE = seal(/[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205f\u3000]/g // eslint-disable-line no-control-regex
);

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

var _ref = typeof Reflect !== 'undefined' && Reflect;
var apply = _ref.apply;

var arraySlice = Array.prototype.slice;
var freeze = Object.freeze;

var getGlobal = function getGlobal() {
  return typeof window === 'undefined' ? null : window;
};

if (!apply) {
  apply = function apply(fun, thisValue, args) {
    return fun.apply(thisValue, args);
  };
}

/**
 * Creates a no-op policy for internal use only.
 * Don't export this function outside this module!
 * @param {?TrustedTypePolicyFactory} trustedTypes The policy factory.
 * @param {Document} document The document object (to determine policy name suffix)
 * @return {?TrustedTypePolicy} The policy created (or null, if Trusted Types
 * are not supported).
 */
var _createTrustedTypesPolicy = function _createTrustedTypesPolicy(trustedTypes, document) {
  if ((typeof trustedTypes === 'undefined' ? 'undefined' : _typeof(trustedTypes)) !== 'object' || typeof trustedTypes.createPolicy !== 'function') {
    return null;
  }

  // Allow the callers to control the unique policy name
  // by adding a data-tt-policy-suffix to the script element with the DOMPurify.
  // Policy creation with duplicate names throws in Trusted Types.
  var suffix = null;
  var ATTR_NAME = 'data-tt-policy-suffix';
  if (document.currentScript && document.currentScript.hasAttribute(ATTR_NAME)) {
    suffix = document.currentScript.getAttribute(ATTR_NAME);
  }

  var policyName = 'dompurify' + (suffix ? '#' + suffix : '');

  try {
    return trustedTypes.createPolicy(policyName, {
      createHTML: function createHTML(html$$1) {
        return html$$1;
      }
    });
  } catch (e) {
    // Policy creation failed (most likely another DOMPurify script has
    // already run). Skip creating the policy, as this will only cause errors
    // if TT are enforced.
    console.warn('TrustedTypes policy ' + policyName + ' could not be created.');
    return null;
  }
};

function createDOMPurify() {
  var window = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : getGlobal();

  var DOMPurify = function DOMPurify(root) {
    return createDOMPurify(root);
  };

  /**
   * Version label, exposed for easier checks
   * if DOMPurify is up to date or not
   */
  DOMPurify.version = '1.0.9';

  /**
   * Array of elements that DOMPurify removed during sanitation.
   * Empty if nothing was removed.
   */
  DOMPurify.removed = [];

  if (!window || !window.document || window.document.nodeType !== 9) {
    // Not running in a browser, provide a factory function
    // so that you can pass your own Window
    DOMPurify.isSupported = false;

    return DOMPurify;
  }

  var originalDocument = window.document;
  var useDOMParser = false;
  var removeTitle = false;

  var document = window.document;
  var DocumentFragment = window.DocumentFragment,
      HTMLTemplateElement = window.HTMLTemplateElement,
      Node = window.Node,
      NodeFilter = window.NodeFilter,
      _window$NamedNodeMap = window.NamedNodeMap,
      NamedNodeMap = _window$NamedNodeMap === undefined ? window.NamedNodeMap || window.MozNamedAttrMap : _window$NamedNodeMap,
      Text = window.Text,
      Comment = window.Comment,
      DOMParser = window.DOMParser,
      TrustedTypes = window.TrustedTypes;

  // As per issue #47, the web-components registry is inherited by a
  // new document created via createHTMLDocument. As per the spec
  // (http://w3c.github.io/webcomponents/spec/custom/#creating-and-passing-registries)
  // a new empty registry is used when creating a template contents owner
  // document, so we use that as our parent document to ensure nothing
  // is inherited.

  if (typeof HTMLTemplateElement === 'function') {
    var template = document.createElement('template');
    if (template.content && template.content.ownerDocument) {
      document = template.content.ownerDocument;
    }
  }

  var trustedTypesPolicy = _createTrustedTypesPolicy(TrustedTypes, originalDocument);
  var emptyHTML = trustedTypesPolicy ? trustedTypesPolicy.createHTML('') : '';

  var _document = document,
      implementation = _document.implementation,
      createNodeIterator = _document.createNodeIterator,
      getElementsByTagName = _document.getElementsByTagName,
      createDocumentFragment = _document.createDocumentFragment;
  var importNode = originalDocument.importNode;


  var hooks = {};

  /**
   * Expose whether this browser supports running the full DOMPurify.
   */
  DOMPurify.isSupported = implementation && typeof implementation.createHTMLDocument !== 'undefined' && document.documentMode !== 9;

  var MUSTACHE_EXPR$$1 = MUSTACHE_EXPR,
      ERB_EXPR$$1 = ERB_EXPR,
      DATA_ATTR$$1 = DATA_ATTR,
      ARIA_ATTR$$1 = ARIA_ATTR,
      IS_SCRIPT_OR_DATA$$1 = IS_SCRIPT_OR_DATA,
      ATTR_WHITESPACE$$1 = ATTR_WHITESPACE;
  var IS_ALLOWED_URI$$1 = IS_ALLOWED_URI;
  /**
   * We consider the elements and attributes below to be safe. Ideally
   * don't add any new ones but feel free to remove unwanted ones.
   */

  /* allowed element names */

  var ALLOWED_TAGS = null;
  var DEFAULT_ALLOWED_TAGS = addToSet({}, [].concat(_toConsumableArray(html), _toConsumableArray(svg), _toConsumableArray(svgFilters), _toConsumableArray(mathMl), _toConsumableArray(text)));

  /* Allowed attribute names */
  var ALLOWED_ATTR = null;
  var DEFAULT_ALLOWED_ATTR = addToSet({}, [].concat(_toConsumableArray(html$1), _toConsumableArray(svg$1), _toConsumableArray(mathMl$1), _toConsumableArray(xml)));

  /* Explicitly forbidden tags (overrides ALLOWED_TAGS/ADD_TAGS) */
  var FORBID_TAGS = null;

  /* Explicitly forbidden attributes (overrides ALLOWED_ATTR/ADD_ATTR) */
  var FORBID_ATTR = null;

  /* Decide if ARIA attributes are okay */
  var ALLOW_ARIA_ATTR = true;

  /* Decide if custom data attributes are okay */
  var ALLOW_DATA_ATTR = true;

  /* Decide if unknown protocols are okay */
  var ALLOW_UNKNOWN_PROTOCOLS = false;

  /* Output should be safe for jQuery's $() factory? */
  var SAFE_FOR_JQUERY = false;

  /* Output should be safe for common template engines.
   * This means, DOMPurify removes data attributes, mustaches and ERB
   */
  var SAFE_FOR_TEMPLATES = false;

  /* Decide if document with <html>... should be returned */
  var WHOLE_DOCUMENT = false;

  /* Track whether config is already set on this instance of DOMPurify. */
  var SET_CONFIG = false;

  /* Decide if all elements (e.g. style, script) must be children of
   * document.body. By default, browsers might move them to document.head */
  var FORCE_BODY = false;

  /* Decide if a DOM `HTMLBodyElement` should be returned, instead of a html
   * string (or a TrustedHTML object if Trusted Types are supported).
   * If `WHOLE_DOCUMENT` is enabled a `HTMLHtmlElement` will be returned instead
   */
  var RETURN_DOM = false;

  /* Decide if a DOM `DocumentFragment` should be returned, instead of a html
   * string  (or a TrustedHTML object if Trusted Types are supported) */
  var RETURN_DOM_FRAGMENT = false;

  /* If `RETURN_DOM` or `RETURN_DOM_FRAGMENT` is enabled, decide if the returned DOM
   * `Node` is imported into the current `Document`. If this flag is not enabled the
   * `Node` will belong (its ownerDocument) to a fresh `HTMLDocument`, created by
   * DOMPurify. */
  var RETURN_DOM_IMPORT = false;

  /* Output should be free from DOM clobbering attacks? */
  var SANITIZE_DOM = true;

  /* Keep element content when removing element? */
  var KEEP_CONTENT = true;

  /* If a `Node` is passed to sanitize(), then performs sanitization in-place instead
   * of importing it into a new Document and returning a sanitized copy */
  var IN_PLACE = false;

  /* Allow usage of profiles like html, svg and mathMl */
  var USE_PROFILES = {};

  /* Tags to ignore content of when KEEP_CONTENT is true */
  var FORBID_CONTENTS = addToSet({}, ['audio', 'head', 'math', 'script', 'style', 'template', 'svg', 'video']);

  /* Tags that are safe for data: URIs */
  var DATA_URI_TAGS = addToSet({}, ['audio', 'video', 'img', 'source', 'image']);

  /* Attributes safe for values like "javascript:" */
  var URI_SAFE_ATTRIBUTES = addToSet({}, ['alt', 'class', 'for', 'id', 'label', 'name', 'pattern', 'placeholder', 'summary', 'title', 'value', 'style', 'xmlns']);

  /* Keep a reference to config to pass to hooks */
  var CONFIG = null;

  /* Ideally, do not touch anything below this line */
  /* ______________________________________________ */

  var formElement = document.createElement('form');

  /**
   * _parseConfig
   *
   * @param  {Object} cfg optional config literal
   */
  // eslint-disable-next-line complexity
  var _parseConfig = function _parseConfig(cfg) {
    if (CONFIG && CONFIG === cfg) {
      return;
    }

    /* Shield configuration object from tampering */
    if (!cfg || (typeof cfg === 'undefined' ? 'undefined' : _typeof(cfg)) !== 'object') {
      cfg = {};
    }
    /* Set configuration parameters */
    ALLOWED_TAGS = 'ALLOWED_TAGS' in cfg ? addToSet({}, cfg.ALLOWED_TAGS) : DEFAULT_ALLOWED_TAGS;
    ALLOWED_ATTR = 'ALLOWED_ATTR' in cfg ? addToSet({}, cfg.ALLOWED_ATTR) : DEFAULT_ALLOWED_ATTR;
    FORBID_TAGS = 'FORBID_TAGS' in cfg ? addToSet({}, cfg.FORBID_TAGS) : {};
    FORBID_ATTR = 'FORBID_ATTR' in cfg ? addToSet({}, cfg.FORBID_ATTR) : {};
    USE_PROFILES = 'USE_PROFILES' in cfg ? cfg.USE_PROFILES : false;
    ALLOW_ARIA_ATTR = cfg.ALLOW_ARIA_ATTR !== false; // Default true
    ALLOW_DATA_ATTR = cfg.ALLOW_DATA_ATTR !== false; // Default true
    ALLOW_UNKNOWN_PROTOCOLS = cfg.ALLOW_UNKNOWN_PROTOCOLS || false; // Default false
    SAFE_FOR_JQUERY = cfg.SAFE_FOR_JQUERY || false; // Default false
    SAFE_FOR_TEMPLATES = cfg.SAFE_FOR_TEMPLATES || false; // Default false
    WHOLE_DOCUMENT = cfg.WHOLE_DOCUMENT || false; // Default false
    RETURN_DOM = cfg.RETURN_DOM || false; // Default false
    RETURN_DOM_FRAGMENT = cfg.RETURN_DOM_FRAGMENT || false; // Default false
    RETURN_DOM_IMPORT = cfg.RETURN_DOM_IMPORT || false; // Default false
    FORCE_BODY = cfg.FORCE_BODY || false; // Default false
    SANITIZE_DOM = cfg.SANITIZE_DOM !== false; // Default true
    KEEP_CONTENT = cfg.KEEP_CONTENT !== false; // Default true
    IN_PLACE = cfg.IN_PLACE || false; // Default false

    IS_ALLOWED_URI$$1 = cfg.ALLOWED_URI_REGEXP || IS_ALLOWED_URI$$1;

    if (SAFE_FOR_TEMPLATES) {
      ALLOW_DATA_ATTR = false;
    }

    if (RETURN_DOM_FRAGMENT) {
      RETURN_DOM = true;
    }

    /* Parse profile info */
    if (USE_PROFILES) {
      ALLOWED_TAGS = addToSet({}, [].concat(_toConsumableArray(text)));
      ALLOWED_ATTR = [];
      if (USE_PROFILES.html === true) {
        addToSet(ALLOWED_TAGS, html);
        addToSet(ALLOWED_ATTR, html$1);
      }
      if (USE_PROFILES.svg === true) {
        addToSet(ALLOWED_TAGS, svg);
        addToSet(ALLOWED_ATTR, svg$1);
        addToSet(ALLOWED_ATTR, xml);
      }
      if (USE_PROFILES.svgFilters === true) {
        addToSet(ALLOWED_TAGS, svgFilters);
        addToSet(ALLOWED_ATTR, svg$1);
        addToSet(ALLOWED_ATTR, xml);
      }
      if (USE_PROFILES.mathMl === true) {
        addToSet(ALLOWED_TAGS, mathMl);
        addToSet(ALLOWED_ATTR, mathMl$1);
        addToSet(ALLOWED_ATTR, xml);
      }
    }

    /* Merge configuration parameters */
    if (cfg.ADD_TAGS) {
      if (ALLOWED_TAGS === DEFAULT_ALLOWED_TAGS) {
        ALLOWED_TAGS = clone(ALLOWED_TAGS);
      }
      addToSet(ALLOWED_TAGS, cfg.ADD_TAGS);
    }
    if (cfg.ADD_ATTR) {
      if (ALLOWED_ATTR === DEFAULT_ALLOWED_ATTR) {
        ALLOWED_ATTR = clone(ALLOWED_ATTR);
      }
      addToSet(ALLOWED_ATTR, cfg.ADD_ATTR);
    }
    if (cfg.ADD_URI_SAFE_ATTR) {
      addToSet(URI_SAFE_ATTRIBUTES, cfg.ADD_URI_SAFE_ATTR);
    }

    /* Add #text in case KEEP_CONTENT is set to true */
    if (KEEP_CONTENT) {
      ALLOWED_TAGS['#text'] = true;
    }

    /* Add html, head and body to ALLOWED_TAGS in case WHOLE_DOCUMENT is true */
    if (WHOLE_DOCUMENT) {
      addToSet(ALLOWED_TAGS, ['html', 'head', 'body']);
    }

    /* Add tbody to ALLOWED_TAGS in case tables are permitted, see #286 */
    if (ALLOWED_TAGS.table) {
      addToSet(ALLOWED_TAGS, ['tbody']);
    }

    // Prevent further manipulation of configuration.
    // Not available in IE8, Safari 5, etc.
    if (freeze) {
      freeze(cfg);
    }

    CONFIG = cfg;
  };

  /**
   * _forceRemove
   *
   * @param  {Node} node a DOM node
   */
  var _forceRemove = function _forceRemove(node) {
    DOMPurify.removed.push({ element: node });
    try {
      node.parentNode.removeChild(node);
    } catch (err) {
      node.outerHTML = emptyHTML;
    }
  };

  /**
   * _removeAttribute
   *
   * @param  {String} name an Attribute name
   * @param  {Node} node a DOM node
   */
  var _removeAttribute = function _removeAttribute(name, node) {
    try {
      DOMPurify.removed.push({
        attribute: node.getAttributeNode(name),
        from: node
      });
    } catch (err) {
      DOMPurify.removed.push({
        attribute: null,
        from: node
      });
    }
    node.removeAttribute(name);
  };

  /**
   * _initDocument
   *
   * @param  {String} dirty a string of dirty markup
   * @return {Document} a DOM, filled with the dirty markup
   */
  var _initDocument = function _initDocument(dirty) {
    /* Create a HTML document */
    var doc = void 0;
    var leadingWhitespace = void 0;

    if (FORCE_BODY) {
      dirty = '<remove></remove>' + dirty;
    } else {
      /* If FORCE_BODY isn't used, leading whitespace needs to be preserved manually */
      var matches = dirty.match(/^[\s]+/);
      leadingWhitespace = matches && matches[0];
      if (leadingWhitespace) {
        dirty = dirty.slice(leadingWhitespace.length);
      }
    }

    /* Use DOMParser to workaround Firefox bug (see comment below) */
    if (useDOMParser) {
      try {
        doc = new DOMParser().parseFromString(dirty, 'text/html');
      } catch (err) {}
    }

    /* Remove title to fix a mXSS bug in older MS Edge */
    if (removeTitle) {
      addToSet(FORBID_TAGS, ['title']);
    }

    /* Otherwise use createHTMLDocument, because DOMParser is unsafe in
    Safari (see comment below) */
    if (!doc || !doc.documentElement) {
      doc = implementation.createHTMLDocument('');
      var _doc = doc,
          body = _doc.body;

      body.parentNode.removeChild(body.parentNode.firstElementChild);
      body.outerHTML = trustedTypesPolicy ? trustedTypesPolicy.createHTML(dirty) : dirty;
    }

    if (leadingWhitespace) {
      doc.body.insertBefore(document.createTextNode(leadingWhitespace), doc.body.childNodes[0] || null);
    }

    /* Work on whole document or just its body */
    return getElementsByTagName.call(doc, WHOLE_DOCUMENT ? 'html' : 'body')[0];
  };

  // Firefox uses a different parser for innerHTML rather than
  // DOMParser (see https://bugzilla.mozilla.org/show_bug.cgi?id=1205631)
  // which means that you *must* use DOMParser, otherwise the output may
  // not be safe if used in a document.write context later.
  //
  // So we feature detect the Firefox bug and use the DOMParser if necessary.
  //
  // MS Edge, in older versions, is affected by an mXSS behavior. The second
  // check tests for the behavior and fixes it if necessary.
  if (DOMPurify.isSupported) {
    (function () {
      try {
        var doc = _initDocument('<svg><p><style><img src="</style><img src=x onerror=1//">');
        if (doc.querySelector('svg img')) {
          useDOMParser = true;
        }
      } catch (err) {}
    })();
    (function () {
      try {
        var doc = _initDocument('<x/><title>&lt;/title&gt;&lt;img&gt;');
        if (doc.querySelector('title').innerHTML.match(/<\/title/)) {
          removeTitle = true;
        }
      } catch (err) {}
    })();
  }

  /**
   * _createIterator
   *
   * @param  {Document} root document/fragment to create iterator for
   * @return {Iterator} iterator instance
   */
  var _createIterator = function _createIterator(root) {
    return createNodeIterator.call(root.ownerDocument || root, root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_COMMENT | NodeFilter.SHOW_TEXT, function () {
      return NodeFilter.FILTER_ACCEPT;
    }, false);
  };

  /**
   * _isClobbered
   *
   * @param  {Node} elm element to check for clobbering attacks
   * @return {Boolean} true if clobbered, false if safe
   */
  var _isClobbered = function _isClobbered(elm) {
    if (elm instanceof Text || elm instanceof Comment) {
      return false;
    }
    if (typeof elm.nodeName !== 'string' || typeof elm.textContent !== 'string' || typeof elm.removeChild !== 'function' || !(elm.attributes instanceof NamedNodeMap) || typeof elm.removeAttribute !== 'function' || typeof elm.setAttribute !== 'function') {
      return true;
    }
    return false;
  };

  /**
   * _isNode
   *
   * @param  {Node} obj object to check whether it's a DOM node
   * @return {Boolean} true is object is a DOM node
   */
  var _isNode = function _isNode(obj) {
    return (typeof Node === 'undefined' ? 'undefined' : _typeof(Node)) === 'object' ? obj instanceof Node : obj && (typeof obj === 'undefined' ? 'undefined' : _typeof(obj)) === 'object' && typeof obj.nodeType === 'number' && typeof obj.nodeName === 'string';
  };

  /**
   * _executeHook
   * Execute user configurable hooks
   *
   * @param  {String} entryPoint  Name of the hook's entry point
   * @param  {Node} currentNode node to work on with the hook
   * @param  {Object} data additional hook parameters
   */
  var _executeHook = function _executeHook(entryPoint, currentNode, data) {
    if (!hooks[entryPoint]) {
      return;
    }

    hooks[entryPoint].forEach(function (hook) {
      hook.call(DOMPurify, currentNode, data, CONFIG);
    });
  };

  /**
   * _sanitizeElements
   *
   * @protect nodeName
   * @protect textContent
   * @protect removeChild
   *
   * @param   {Node} currentNode to check for permission to exist
   * @return  {Boolean} true if node was killed, false if left alive
   */
  var _sanitizeElements = function _sanitizeElements(currentNode) {
    var content = void 0;

    /* Execute a hook if present */
    _executeHook('beforeSanitizeElements', currentNode, null);

    /* Check if element is clobbered or can clobber */
    if (_isClobbered(currentNode)) {
      _forceRemove(currentNode);
      return true;
    }

    /* Now let's check the element's type and name */
    var tagName = currentNode.nodeName.toLowerCase();

    /* Execute a hook if present */
    _executeHook('uponSanitizeElement', currentNode, {
      tagName: tagName,
      allowedTags: ALLOWED_TAGS
    });

    /* Remove element if anything forbids its presence */
    if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
      /* Keep content except for black-listed elements */
      if (KEEP_CONTENT && !FORBID_CONTENTS[tagName] && typeof currentNode.insertAdjacentHTML === 'function') {
        try {
          var htmlToInsert = currentNode.innerHTML;
          currentNode.insertAdjacentHTML('AfterEnd', trustedTypesPolicy ? trustedTypesPolicy.createHTML(htmlToInsert) : htmlToInsert);
        } catch (err) {}
      }
      _forceRemove(currentNode);
      return true;
    }

    /* Convert markup to cover jQuery behavior */
    if (SAFE_FOR_JQUERY && !currentNode.firstElementChild && (!currentNode.content || !currentNode.content.firstElementChild) && /</g.test(currentNode.textContent)) {
      DOMPurify.removed.push({ element: currentNode.cloneNode() });
      if (currentNode.innerHTML) {
        currentNode.innerHTML = currentNode.innerHTML.replace(/</g, '&lt;');
      } else {
        currentNode.innerHTML = currentNode.textContent.replace(/</g, '&lt;');
      }
    }

    /* Sanitize element content to be template-safe */
    if (SAFE_FOR_TEMPLATES && currentNode.nodeType === 3) {
      /* Get the element's text content */
      content = currentNode.textContent;
      content = content.replace(MUSTACHE_EXPR$$1, ' ');
      content = content.replace(ERB_EXPR$$1, ' ');
      if (currentNode.textContent !== content) {
        DOMPurify.removed.push({ element: currentNode.cloneNode() });
        currentNode.textContent = content;
      }
    }

    /* Execute a hook if present */
    _executeHook('afterSanitizeElements', currentNode, null);

    return false;
  };

  /**
   * _isValidAttribute
   *
   * @param  {string} lcTag Lowercase tag name of containing element.
   * @param  {string} lcName Lowercase attribute name.
   * @param  {string} value Attribute value.
   * @return {Boolean} Returns true if `value` is valid, otherwise false.
   */
  var _isValidAttribute = function _isValidAttribute(lcTag, lcName, value) {
    /* Make sure attribute cannot clobber */
    if (SANITIZE_DOM && (lcName === 'id' || lcName === 'name') && (value in document || value in formElement)) {
      return false;
    }

    /* Sanitize attribute content to be template-safe */
    if (SAFE_FOR_TEMPLATES) {
      value = value.replace(MUSTACHE_EXPR$$1, ' ');
      value = value.replace(ERB_EXPR$$1, ' ');
    }

    /* Allow valid data-* attributes: At least one character after "-"
        (https://html.spec.whatwg.org/multipage/dom.html#embedding-custom-non-visible-data-with-the-data-*-attributes)
        XML-compatible (https://html.spec.whatwg.org/multipage/infrastructure.html#xml-compatible and http://www.w3.org/TR/xml/#d0e804)
        We don't need to check the value; it's always URI safe. */
    if (ALLOW_DATA_ATTR && DATA_ATTR$$1.test(lcName)) {
      // This attribute is safe
    } else if (ALLOW_ARIA_ATTR && ARIA_ATTR$$1.test(lcName)) {
      // This attribute is safe
      /* Otherwise, check the name is permitted */
    } else if (!ALLOWED_ATTR[lcName] || FORBID_ATTR[lcName]) {
      return false;

      /* Check value is safe. First, is attr inert? If so, is safe */
    } else if (URI_SAFE_ATTRIBUTES[lcName]) {
      // This attribute is safe
      /* Check no script, data or unknown possibly unsafe URI
        unless we know URI values are safe for that attribute */
    } else if (IS_ALLOWED_URI$$1.test(value.replace(ATTR_WHITESPACE$$1, ''))) {
      // This attribute is safe
      /* Keep image data URIs alive if src/xlink:href is allowed */
      /* Further prevent gadget XSS for dynamically built script tags */
    } else if ((lcName === 'src' || lcName === 'xlink:href') && lcTag !== 'script' && value.indexOf('data:') === 0 && DATA_URI_TAGS[lcTag]) {
      // This attribute is safe
      /* Allow unknown protocols: This provides support for links that
        are handled by protocol handlers which may be unknown ahead of
        time, e.g. fb:, spotify: */
    } else if (ALLOW_UNKNOWN_PROTOCOLS && !IS_SCRIPT_OR_DATA$$1.test(value.replace(ATTR_WHITESPACE$$1, ''))) {
      // This attribute is safe
      /* Check for binary attributes */
      // eslint-disable-next-line no-negated-condition
    } else if (!value) {
      // Binary attributes are safe at this point
      /* Anything else, presume unsafe, do not add it back */
    } else {
      return false;
    }
    return true;
  };

  /**
   * _sanitizeAttributes
   *
   * @protect attributes
   * @protect nodeName
   * @protect removeAttribute
   * @protect setAttribute
   *
   * @param  {Node} node to sanitize
   */
  // eslint-disable-next-line complexity
  var _sanitizeAttributes = function _sanitizeAttributes(currentNode) {
    var attr = void 0;
    var value = void 0;
    var lcName = void 0;
    var idAttr = void 0;
    var l = void 0;
    /* Execute a hook if present */
    _executeHook('beforeSanitizeAttributes', currentNode, null);

    var attributes = currentNode.attributes;

    /* Check if we have attributes; if not we might have a text node */

    if (!attributes) {
      return;
    }

    var hookEvent = {
      attrName: '',
      attrValue: '',
      keepAttr: true,
      allowedAttributes: ALLOWED_ATTR
    };
    l = attributes.length;

    /* Go backwards over all attributes; safely remove bad ones */
    while (l--) {
      attr = attributes[l];
      var _attr = attr,
          name = _attr.name,
          namespaceURI = _attr.namespaceURI;

      value = attr.value.trim();
      lcName = name.toLowerCase();

      /* Execute a hook if present */
      hookEvent.attrName = lcName;
      hookEvent.attrValue = value;
      hookEvent.keepAttr = true;
      _executeHook('uponSanitizeAttribute', currentNode, hookEvent);
      value = hookEvent.attrValue;

      /* Remove attribute */
      // Safari (iOS + Mac), last tested v8.0.5, crashes if you try to
      // remove a "name" attribute from an <img> tag that has an "id"
      // attribute at the time.
      if (lcName === 'name' && currentNode.nodeName === 'IMG' && attributes.id) {
        idAttr = attributes.id;
        attributes = apply(arraySlice, attributes, []);
        _removeAttribute('id', currentNode);
        _removeAttribute(name, currentNode);
        if (attributes.indexOf(idAttr) > l) {
          currentNode.setAttribute('id', idAttr.value);
        }
      } else if (
      // This works around a bug in Safari, where input[type=file]
      // cannot be dynamically set after type has been removed
      currentNode.nodeName === 'INPUT' && lcName === 'type' && value === 'file' && (ALLOWED_ATTR[lcName] || !FORBID_ATTR[lcName])) {
        continue;
      } else {
        // This avoids a crash in Safari v9.0 with double-ids.
        // The trick is to first set the id to be empty and then to
        // remove the attribute
        if (name === 'id') {
          currentNode.setAttribute(name, '');
        }
        _removeAttribute(name, currentNode);
      }

      /* Did the hooks approve of the attribute? */
      if (!hookEvent.keepAttr) {
        continue;
      }

      /* Is `value` valid for this attribute? */
      var lcTag = currentNode.nodeName.toLowerCase();
      if (!_isValidAttribute(lcTag, lcName, value)) {
        continue;
      }

      /* Handle invalid data-* attribute set by try-catching it */
      try {
        if (namespaceURI) {
          currentNode.setAttributeNS(namespaceURI, name, value);
        } else {
          /* Fallback to setAttribute() for browser-unrecognized namespaces e.g. "x-schema". */
          currentNode.setAttribute(name, value);
        }
        DOMPurify.removed.pop();
      } catch (err) {}
    }

    /* Execute a hook if present */
    _executeHook('afterSanitizeAttributes', currentNode, null);
  };

  /**
   * _sanitizeShadowDOM
   *
   * @param  {DocumentFragment} fragment to iterate over recursively
   */
  var _sanitizeShadowDOM = function _sanitizeShadowDOM(fragment) {
    var shadowNode = void 0;
    var shadowIterator = _createIterator(fragment);

    /* Execute a hook if present */
    _executeHook('beforeSanitizeShadowDOM', fragment, null);

    while (shadowNode = shadowIterator.nextNode()) {
      /* Execute a hook if present */
      _executeHook('uponSanitizeShadowNode', shadowNode, null);

      /* Sanitize tags and elements */
      if (_sanitizeElements(shadowNode)) {
        continue;
      }

      /* Deep shadow DOM detected */
      if (shadowNode.content instanceof DocumentFragment) {
        _sanitizeShadowDOM(shadowNode.content);
      }

      /* Check attributes, sanitize if necessary */
      _sanitizeAttributes(shadowNode);
    }

    /* Execute a hook if present */
    _executeHook('afterSanitizeShadowDOM', fragment, null);
  };

  /**
   * Sanitize
   * Public method providing core sanitation functionality
   *
   * @param {String|Node} dirty string or DOM node
   * @param {Object} configuration object
   */
  // eslint-disable-next-line complexity
  DOMPurify.sanitize = function (dirty, cfg) {
    var body = void 0;
    var importedNode = void 0;
    var currentNode = void 0;
    var oldNode = void 0;
    var returnNode = void 0;
    /* Make sure we have a string to sanitize.
      DO NOT return early, as this will return the wrong type if
      the user has requested a DOM object rather than a string */
    if (!dirty) {
      dirty = '<!-->';
    }

    /* Stringify, in case dirty is an object */
    if (typeof dirty !== 'string' && !_isNode(dirty)) {
      // eslint-disable-next-line no-negated-condition
      if (typeof dirty.toString !== 'function') {
        throw new TypeError('toString is not a function');
      } else {
        dirty = dirty.toString();
        if (typeof dirty !== 'string') {
          throw new TypeError('dirty is not a string, aborting');
        }
      }
    }

    /* Check we can run. Otherwise fall back or ignore */
    if (!DOMPurify.isSupported) {
      if (_typeof(window.toStaticHTML) === 'object' || typeof window.toStaticHTML === 'function') {
        if (typeof dirty === 'string') {
          return window.toStaticHTML(dirty);
        }
        if (_isNode(dirty)) {
          return window.toStaticHTML(dirty.outerHTML);
        }
      }
      return dirty;
    }

    /* Assign config vars */
    if (!SET_CONFIG) {
      _parseConfig(cfg);
    }

    /* Clean up removed elements */
    DOMPurify.removed = [];

    if (IN_PLACE) {
      /* No special handling necessary for in-place sanitization */
    } else if (dirty instanceof Node) {
      /* If dirty is a DOM element, append to an empty document to avoid
         elements being stripped by the parser */
      body = _initDocument('<!-->');
      importedNode = body.ownerDocument.importNode(dirty, true);
      if (importedNode.nodeType === 1 && importedNode.nodeName === 'BODY') {
        /* Node is already a body, use as is */
        body = importedNode;
      } else {
        body.appendChild(importedNode);
      }
    } else {
      /* Exit directly if we have nothing to do */
      if (!RETURN_DOM && !WHOLE_DOCUMENT && dirty.indexOf('<') === -1) {
        return trustedTypesPolicy ? trustedTypesPolicy.createHTML(dirty) : dirty;
      }

      /* Initialize the document to work on */
      body = _initDocument(dirty);

      /* Check we have a DOM node from the data */
      if (!body) {
        return RETURN_DOM ? null : emptyHTML;
      }
    }

    /* Remove first element node (ours) if FORCE_BODY is set */
    if (body && FORCE_BODY) {
      _forceRemove(body.firstChild);
    }

    /* Get node iterator */
    var nodeIterator = _createIterator(IN_PLACE ? dirty : body);

    /* Now start iterating over the created document */
    while (currentNode = nodeIterator.nextNode()) {
      /* Fix IE's strange behavior with manipulated textNodes #89 */
      if (currentNode.nodeType === 3 && currentNode === oldNode) {
        continue;
      }

      /* Sanitize tags and elements */
      if (_sanitizeElements(currentNode)) {
        continue;
      }

      /* Shadow DOM detected, sanitize it */
      if (currentNode.content instanceof DocumentFragment) {
        _sanitizeShadowDOM(currentNode.content);
      }

      /* Check attributes, sanitize if necessary */
      _sanitizeAttributes(currentNode);

      oldNode = currentNode;
    }

    oldNode = null;

    /* If we sanitized `dirty` in-place, return it. */
    if (IN_PLACE) {
      return dirty;
    }

    /* Return sanitized string or DOM */
    if (RETURN_DOM) {
      if (RETURN_DOM_FRAGMENT) {
        returnNode = createDocumentFragment.call(body.ownerDocument);

        while (body.firstChild) {
          returnNode.appendChild(body.firstChild);
        }
      } else {
        returnNode = body;
      }

      if (RETURN_DOM_IMPORT) {
        /* AdoptNode() is not used because internal state is not reset
               (e.g. the past names map of a HTMLFormElement), this is safe
               in theory but we would rather not risk another attack vector.
               The state that is cloned by importNode() is explicitly defined
               by the specs. */
        returnNode = importNode.call(originalDocument, returnNode, true);
      }

      return returnNode;
    }

    var serializedHTML = WHOLE_DOCUMENT ? body.outerHTML : body.innerHTML;
    return trustedTypesPolicy ? trustedTypesPolicy.createHTML(serializedHTML) : serializedHTML;
  };

  /**
   * Public method to set the configuration once
   * setConfig
   *
   * @param {Object} cfg configuration object
   */
  DOMPurify.setConfig = function (cfg) {
    _parseConfig(cfg);
    SET_CONFIG = true;
  };

  /**
   * Public method to remove the configuration
   * clearConfig
   *
   */
  DOMPurify.clearConfig = function () {
    CONFIG = null;
    SET_CONFIG = false;
  };

  /**
   * Public method to check if an attribute value is valid.
   * Uses last set config, if any. Otherwise, uses config defaults.
   * isValidAttribute
   *
   * @param  {string} tag Tag name of containing element.
   * @param  {string} attr Attribute name.
   * @param  {string} value Attribute value.
   * @return {Boolean} Returns true if `value` is valid. Otherwise, returns false.
   */
  DOMPurify.isValidAttribute = function (tag, attr, value) {
    /* Initialize shared config vars if necessary. */
    if (!CONFIG) {
      _parseConfig({});
    }
    var lcTag = tag.toLowerCase();
    var lcName = attr.toLowerCase();
    return _isValidAttribute(lcTag, lcName, value);
  };

  /**
   * AddHook
   * Public method to add DOMPurify hooks
   *
   * @param {String} entryPoint entry point for the hook to add
   * @param {Function} hookFunction function to execute
   */
  DOMPurify.addHook = function (entryPoint, hookFunction) {
    if (typeof hookFunction !== 'function') {
      return;
    }
    hooks[entryPoint] = hooks[entryPoint] || [];
    hooks[entryPoint].push(hookFunction);
  };

  /**
   * RemoveHook
   * Public method to remove a DOMPurify hook at a given entryPoint
   * (pops it from the stack of hooks if more are present)
   *
   * @param {String} entryPoint entry point for the hook to remove
   */
  DOMPurify.removeHook = function (entryPoint) {
    if (hooks[entryPoint]) {
      hooks[entryPoint].pop();
    }
  };

  /**
   * RemoveHooks
   * Public method to remove all DOMPurify hooks at a given entryPoint
   *
   * @param  {String} entryPoint entry point for the hooks to remove
   */
  DOMPurify.removeHooks = function (entryPoint) {
    if (hooks[entryPoint]) {
      hooks[entryPoint] = [];
    }
  };

  /**
   * RemoveAllHooks
   * Public method to remove all DOMPurify hooks
   *
   */
  DOMPurify.removeAllHooks = function () {
    hooks = {};
  };

  return DOMPurify;
}

var purify = createDOMPurify();

return purify;

})));
//# sourceMappingURL=purify.js.map


/***/ }),

/***/ "./settings/node_modules/marked/lib/marked.js":
/*!****************************************************!*\
  !*** ./settings/node_modules/marked/lib/marked.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {/**
 * marked - a markdown parser
 * Copyright (c) 2011-2018, Christopher Jeffrey. (MIT Licensed)
 * https://github.com/markedjs/marked
 */

;(function(root) {
'use strict';

/**
 * Block-Level Grammar
 */

var block = {
  newline: /^\n+/,
  code: /^( {4}[^\n]+\n*)+/,
  fences: noop,
  hr: /^ {0,3}((?:- *){3,}|(?:_ *){3,}|(?:\* *){3,})(?:\n+|$)/,
  heading: /^ *(#{1,6}) *([^\n]+?) *(?:#+ *)?(?:\n+|$)/,
  nptable: noop,
  blockquote: /^( {0,3}> ?(paragraph|[^\n]*)(?:\n|$))+/,
  list: /^( {0,3})(bull) [\s\S]+?(?:hr|def|\n{2,}(?! )(?!\1bull )\n*|\s*$)/,
  html: '^ {0,3}(?:' // optional indentation
    + '<(script|pre|style)[\\s>][\\s\\S]*?(?:</\\1>[^\\n]*\\n+|$)' // (1)
    + '|comment[^\\n]*(\\n+|$)' // (2)
    + '|<\\?[\\s\\S]*?\\?>\\n*' // (3)
    + '|<![A-Z][\\s\\S]*?>\\n*' // (4)
    + '|<!\\[CDATA\\[[\\s\\S]*?\\]\\]>\\n*' // (5)
    + '|</?(tag)(?: +|\\n|/?>)[\\s\\S]*?(?:\\n{2,}|$)' // (6)
    + '|<(?!script|pre|style)([a-z][\\w-]*)(?:attribute)*? */?>(?=\\h*\\n)[\\s\\S]*?(?:\\n{2,}|$)' // (7) open tag
    + '|</(?!script|pre|style)[a-z][\\w-]*\\s*>(?=\\h*\\n)[\\s\\S]*?(?:\\n{2,}|$)' // (7) closing tag
    + ')',
  def: /^ {0,3}\[(label)\]: *\n? *<?([^\s>]+)>?(?:(?: +\n? *| *\n *)(title))? *(?:\n+|$)/,
  table: noop,
  lheading: /^([^\n]+)\n *(=|-){2,} *(?:\n+|$)/,
  paragraph: /^([^\n]+(?:\n(?!hr|heading|lheading| {0,3}>|<\/?(?:tag)(?: +|\n|\/?>)|<(?:script|pre|style|!--))[^\n]+)*)/,
  text: /^[^\n]+/
};

block._label = /(?!\s*\])(?:\\[\[\]]|[^\[\]])+/;
block._title = /(?:"(?:\\"?|[^"\\])*"|'[^'\n]*(?:\n[^'\n]+)*\n?'|\([^()]*\))/;
block.def = edit(block.def)
  .replace('label', block._label)
  .replace('title', block._title)
  .getRegex();

block.bullet = /(?:[*+-]|\d{1,9}\.)/;
block.item = /^( *)(bull) ?[^\n]*(?:\n(?!\1bull ?)[^\n]*)*/;
block.item = edit(block.item, 'gm')
  .replace(/bull/g, block.bullet)
  .getRegex();

block.list = edit(block.list)
  .replace(/bull/g, block.bullet)
  .replace('hr', '\\n+(?=\\1?(?:(?:- *){3,}|(?:_ *){3,}|(?:\\* *){3,})(?:\\n+|$))')
  .replace('def', '\\n+(?=' + block.def.source + ')')
  .getRegex();

block._tag = 'address|article|aside|base|basefont|blockquote|body|caption'
  + '|center|col|colgroup|dd|details|dialog|dir|div|dl|dt|fieldset|figcaption'
  + '|figure|footer|form|frame|frameset|h[1-6]|head|header|hr|html|iframe'
  + '|legend|li|link|main|menu|menuitem|meta|nav|noframes|ol|optgroup|option'
  + '|p|param|section|source|summary|table|tbody|td|tfoot|th|thead|title|tr'
  + '|track|ul';
block._comment = /<!--(?!-?>)[\s\S]*?-->/;
block.html = edit(block.html, 'i')
  .replace('comment', block._comment)
  .replace('tag', block._tag)
  .replace('attribute', / +[a-zA-Z:_][\w.:-]*(?: *= *"[^"\n]*"| *= *'[^'\n]*'| *= *[^\s"'=<>`]+)?/)
  .getRegex();

block.paragraph = edit(block.paragraph)
  .replace('hr', block.hr)
  .replace('heading', block.heading)
  .replace('lheading', block.lheading)
  .replace('tag', block._tag) // pars can be interrupted by type (6) html blocks
  .getRegex();

block.blockquote = edit(block.blockquote)
  .replace('paragraph', block.paragraph)
  .getRegex();

/**
 * Normal Block Grammar
 */

block.normal = merge({}, block);

/**
 * GFM Block Grammar
 */

block.gfm = merge({}, block.normal, {
  fences: /^ {0,3}(`{3,}|~{3,})([^`\n]*)\n(?:|([\s\S]*?)\n)(?: {0,3}\1[~`]* *(?:\n+|$)|$)/,
  paragraph: /^/,
  heading: /^ *(#{1,6}) +([^\n]+?) *#* *(?:\n+|$)/
});

block.gfm.paragraph = edit(block.paragraph)
  .replace('(?!', '(?!'
    + block.gfm.fences.source.replace('\\1', '\\2') + '|'
    + block.list.source.replace('\\1', '\\3') + '|')
  .getRegex();

/**
 * GFM + Tables Block Grammar
 */

block.tables = merge({}, block.gfm, {
  nptable: /^ *([^|\n ].*\|.*)\n *([-:]+ *\|[-| :]*)(?:\n((?:.*[^>\n ].*(?:\n|$))*)\n*|$)/,
  table: /^ *\|(.+)\n *\|?( *[-:]+[-| :]*)(?:\n((?: *[^>\n ].*(?:\n|$))*)\n*|$)/
});

/**
 * Pedantic grammar
 */

block.pedantic = merge({}, block.normal, {
  html: edit(
    '^ *(?:comment *(?:\\n|\\s*$)'
    + '|<(tag)[\\s\\S]+?</\\1> *(?:\\n{2,}|\\s*$)' // closed tag
    + '|<tag(?:"[^"]*"|\'[^\']*\'|\\s[^\'"/>\\s]*)*?/?> *(?:\\n{2,}|\\s*$))')
    .replace('comment', block._comment)
    .replace(/tag/g, '(?!(?:'
      + 'a|em|strong|small|s|cite|q|dfn|abbr|data|time|code|var|samp|kbd|sub'
      + '|sup|i|b|u|mark|ruby|rt|rp|bdi|bdo|span|br|wbr|ins|del|img)'
      + '\\b)\\w+(?!:|[^\\w\\s@]*@)\\b')
    .getRegex(),
  def: /^ *\[([^\]]+)\]: *<?([^\s>]+)>?(?: +(["(][^\n]+[")]))? *(?:\n+|$)/
});

/**
 * Block Lexer
 */

function Lexer(options) {
  this.tokens = [];
  this.tokens.links = Object.create(null);
  this.options = options || marked.defaults;
  this.rules = block.normal;

  if (this.options.pedantic) {
    this.rules = block.pedantic;
  } else if (this.options.gfm) {
    if (this.options.tables) {
      this.rules = block.tables;
    } else {
      this.rules = block.gfm;
    }
  }
}

/**
 * Expose Block Rules
 */

Lexer.rules = block;

/**
 * Static Lex Method
 */

Lexer.lex = function(src, options) {
  var lexer = new Lexer(options);
  return lexer.lex(src);
};

/**
 * Preprocessing
 */

Lexer.prototype.lex = function(src) {
  src = src
    .replace(/\r\n|\r/g, '\n')
    .replace(/\t/g, '    ')
    .replace(/\u00a0/g, ' ')
    .replace(/\u2424/g, '\n');

  return this.token(src, true);
};

/**
 * Lexing
 */

Lexer.prototype.token = function(src, top) {
  src = src.replace(/^ +$/gm, '');
  var next,
      loose,
      cap,
      bull,
      b,
      item,
      listStart,
      listItems,
      t,
      space,
      i,
      tag,
      l,
      isordered,
      istask,
      ischecked;

  while (src) {
    // newline
    if (cap = this.rules.newline.exec(src)) {
      src = src.substring(cap[0].length);
      if (cap[0].length > 1) {
        this.tokens.push({
          type: 'space'
        });
      }
    }

    // code
    if (cap = this.rules.code.exec(src)) {
      src = src.substring(cap[0].length);
      cap = cap[0].replace(/^ {4}/gm, '');
      this.tokens.push({
        type: 'code',
        text: !this.options.pedantic
          ? rtrim(cap, '\n')
          : cap
      });
      continue;
    }

    // fences (gfm)
    if (cap = this.rules.fences.exec(src)) {
      src = src.substring(cap[0].length);
      this.tokens.push({
        type: 'code',
        lang: cap[2] ? cap[2].trim() : cap[2],
        text: cap[3] || ''
      });
      continue;
    }

    // heading
    if (cap = this.rules.heading.exec(src)) {
      src = src.substring(cap[0].length);
      this.tokens.push({
        type: 'heading',
        depth: cap[1].length,
        text: cap[2]
      });
      continue;
    }

    // table no leading pipe (gfm)
    if (top && (cap = this.rules.nptable.exec(src))) {
      item = {
        type: 'table',
        header: splitCells(cap[1].replace(/^ *| *\| *$/g, '')),
        align: cap[2].replace(/^ *|\| *$/g, '').split(/ *\| */),
        cells: cap[3] ? cap[3].replace(/\n$/, '').split('\n') : []
      };

      if (item.header.length === item.align.length) {
        src = src.substring(cap[0].length);

        for (i = 0; i < item.align.length; i++) {
          if (/^ *-+: *$/.test(item.align[i])) {
            item.align[i] = 'right';
          } else if (/^ *:-+: *$/.test(item.align[i])) {
            item.align[i] = 'center';
          } else if (/^ *:-+ *$/.test(item.align[i])) {
            item.align[i] = 'left';
          } else {
            item.align[i] = null;
          }
        }

        for (i = 0; i < item.cells.length; i++) {
          item.cells[i] = splitCells(item.cells[i], item.header.length);
        }

        this.tokens.push(item);

        continue;
      }
    }

    // hr
    if (cap = this.rules.hr.exec(src)) {
      src = src.substring(cap[0].length);
      this.tokens.push({
        type: 'hr'
      });
      continue;
    }

    // blockquote
    if (cap = this.rules.blockquote.exec(src)) {
      src = src.substring(cap[0].length);

      this.tokens.push({
        type: 'blockquote_start'
      });

      cap = cap[0].replace(/^ *> ?/gm, '');

      // Pass `top` to keep the current
      // "toplevel" state. This is exactly
      // how markdown.pl works.
      this.token(cap, top);

      this.tokens.push({
        type: 'blockquote_end'
      });

      continue;
    }

    // list
    if (cap = this.rules.list.exec(src)) {
      src = src.substring(cap[0].length);
      bull = cap[2];
      isordered = bull.length > 1;

      listStart = {
        type: 'list_start',
        ordered: isordered,
        start: isordered ? +bull : '',
        loose: false
      };

      this.tokens.push(listStart);

      // Get each top-level item.
      cap = cap[0].match(this.rules.item);

      listItems = [];
      next = false;
      l = cap.length;
      i = 0;

      for (; i < l; i++) {
        item = cap[i];

        // Remove the list item's bullet
        // so it is seen as the next token.
        space = item.length;
        item = item.replace(/^ *([*+-]|\d+\.) */, '');

        // Outdent whatever the
        // list item contains. Hacky.
        if (~item.indexOf('\n ')) {
          space -= item.length;
          item = !this.options.pedantic
            ? item.replace(new RegExp('^ {1,' + space + '}', 'gm'), '')
            : item.replace(/^ {1,4}/gm, '');
        }

        // Determine whether the next list item belongs here.
        // Backpedal if it does not belong in this list.
        if (i !== l - 1) {
          b = block.bullet.exec(cap[i + 1])[0];
          if (bull.length > 1 ? b.length === 1
            : (b.length > 1 || (this.options.smartLists && b !== bull))) {
            src = cap.slice(i + 1).join('\n') + src;
            i = l - 1;
          }
        }

        // Determine whether item is loose or not.
        // Use: /(^|\n)(?! )[^\n]+\n\n(?!\s*$)/
        // for discount behavior.
        loose = next || /\n\n(?!\s*$)/.test(item);
        if (i !== l - 1) {
          next = item.charAt(item.length - 1) === '\n';
          if (!loose) loose = next;
        }

        if (loose) {
          listStart.loose = true;
        }

        // Check for task list items
        istask = /^\[[ xX]\] /.test(item);
        ischecked = undefined;
        if (istask) {
          ischecked = item[1] !== ' ';
          item = item.replace(/^\[[ xX]\] +/, '');
        }

        t = {
          type: 'list_item_start',
          task: istask,
          checked: ischecked,
          loose: loose
        };

        listItems.push(t);
        this.tokens.push(t);

        // Recurse.
        this.token(item, false);

        this.tokens.push({
          type: 'list_item_end'
        });
      }

      if (listStart.loose) {
        l = listItems.length;
        i = 0;
        for (; i < l; i++) {
          listItems[i].loose = true;
        }
      }

      this.tokens.push({
        type: 'list_end'
      });

      continue;
    }

    // html
    if (cap = this.rules.html.exec(src)) {
      src = src.substring(cap[0].length);
      this.tokens.push({
        type: this.options.sanitize
          ? 'paragraph'
          : 'html',
        pre: !this.options.sanitizer
          && (cap[1] === 'pre' || cap[1] === 'script' || cap[1] === 'style'),
        text: cap[0]
      });
      continue;
    }

    // def
    if (top && (cap = this.rules.def.exec(src))) {
      src = src.substring(cap[0].length);
      if (cap[3]) cap[3] = cap[3].substring(1, cap[3].length - 1);
      tag = cap[1].toLowerCase().replace(/\s+/g, ' ');
      if (!this.tokens.links[tag]) {
        this.tokens.links[tag] = {
          href: cap[2],
          title: cap[3]
        };
      }
      continue;
    }

    // table (gfm)
    if (top && (cap = this.rules.table.exec(src))) {
      item = {
        type: 'table',
        header: splitCells(cap[1].replace(/^ *| *\| *$/g, '')),
        align: cap[2].replace(/^ *|\| *$/g, '').split(/ *\| */),
        cells: cap[3] ? cap[3].replace(/(?: *\| *)?\n$/, '').split('\n') : []
      };

      if (item.header.length === item.align.length) {
        src = src.substring(cap[0].length);

        for (i = 0; i < item.align.length; i++) {
          if (/^ *-+: *$/.test(item.align[i])) {
            item.align[i] = 'right';
          } else if (/^ *:-+: *$/.test(item.align[i])) {
            item.align[i] = 'center';
          } else if (/^ *:-+ *$/.test(item.align[i])) {
            item.align[i] = 'left';
          } else {
            item.align[i] = null;
          }
        }

        for (i = 0; i < item.cells.length; i++) {
          item.cells[i] = splitCells(
            item.cells[i].replace(/^ *\| *| *\| *$/g, ''),
            item.header.length);
        }

        this.tokens.push(item);

        continue;
      }
    }

    // lheading
    if (cap = this.rules.lheading.exec(src)) {
      src = src.substring(cap[0].length);
      this.tokens.push({
        type: 'heading',
        depth: cap[2] === '=' ? 1 : 2,
        text: cap[1]
      });
      continue;
    }

    // top-level paragraph
    if (top && (cap = this.rules.paragraph.exec(src))) {
      src = src.substring(cap[0].length);
      this.tokens.push({
        type: 'paragraph',
        text: cap[1].charAt(cap[1].length - 1) === '\n'
          ? cap[1].slice(0, -1)
          : cap[1]
      });
      continue;
    }

    // text
    if (cap = this.rules.text.exec(src)) {
      // Top-level should never reach here.
      src = src.substring(cap[0].length);
      this.tokens.push({
        type: 'text',
        text: cap[0]
      });
      continue;
    }

    if (src) {
      throw new Error('Infinite loop on byte: ' + src.charCodeAt(0));
    }
  }

  return this.tokens;
};

/**
 * Inline-Level Grammar
 */

var inline = {
  escape: /^\\([!"#$%&'()*+,\-./:;<=>?@\[\]\\^_`{|}~])/,
  autolink: /^<(scheme:[^\s\x00-\x1f<>]*|email)>/,
  url: noop,
  tag: '^comment'
    + '|^</[a-zA-Z][\\w:-]*\\s*>' // self-closing tag
    + '|^<[a-zA-Z][\\w-]*(?:attribute)*?\\s*/?>' // open tag
    + '|^<\\?[\\s\\S]*?\\?>' // processing instruction, e.g. <?php ?>
    + '|^<![a-zA-Z]+\\s[\\s\\S]*?>' // declaration, e.g. <!DOCTYPE html>
    + '|^<!\\[CDATA\\[[\\s\\S]*?\\]\\]>', // CDATA section
  link: /^!?\[(label)\]\(href(?:\s+(title))?\s*\)/,
  reflink: /^!?\[(label)\]\[(?!\s*\])((?:\\[\[\]]?|[^\[\]\\])+)\]/,
  nolink: /^!?\[(?!\s*\])((?:\[[^\[\]]*\]|\\[\[\]]|[^\[\]])*)\](?:\[\])?/,
  strong: /^__([^\s_])__(?!_)|^\*\*([^\s*])\*\*(?!\*)|^__([^\s][\s\S]*?[^\s])__(?!_)|^\*\*([^\s][\s\S]*?[^\s])\*\*(?!\*)/,
  em: /^_([^\s_])_(?!_)|^\*([^\s*"<\[])\*(?!\*)|^_([^\s][\s\S]*?[^\s_])_(?!_|[^\spunctuation])|^_([^\s_][\s\S]*?[^\s])_(?!_|[^\spunctuation])|^\*([^\s"<\[][\s\S]*?[^\s*])\*(?!\*)|^\*([^\s*"<\[][\s\S]*?[^\s])\*(?!\*)/,
  code: /^(`+)([^`]|[^`][\s\S]*?[^`])\1(?!`)/,
  br: /^( {2,}|\\)\n(?!\s*$)/,
  del: noop,
  text: /^(`+|[^`])[\s\S]*?(?=[\\<!\[`*]|\b_| {2,}\n|$)/
};

// list of punctuation marks from common mark spec
// without ` and ] to workaround Rule 17 (inline code blocks/links)
inline._punctuation = '!"#$%&\'()*+,\\-./:;<=>?@\\[^_{|}~';
inline.em = edit(inline.em).replace(/punctuation/g, inline._punctuation).getRegex();

inline._escapes = /\\([!"#$%&'()*+,\-./:;<=>?@\[\]\\^_`{|}~])/g;

inline._scheme = /[a-zA-Z][a-zA-Z0-9+.-]{1,31}/;
inline._email = /[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+(@)[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+(?![-_])/;
inline.autolink = edit(inline.autolink)
  .replace('scheme', inline._scheme)
  .replace('email', inline._email)
  .getRegex();

inline._attribute = /\s+[a-zA-Z:_][\w.:-]*(?:\s*=\s*"[^"]*"|\s*=\s*'[^']*'|\s*=\s*[^\s"'=<>`]+)?/;

inline.tag = edit(inline.tag)
  .replace('comment', block._comment)
  .replace('attribute', inline._attribute)
  .getRegex();

inline._label = /(?:\[[^\[\]]*\]|\\[\[\]]?|`[^`]*`|[^\[\]\\])*?/;
inline._href = /\s*(<(?:\\[<>]?|[^\s<>\\])*>|(?:\\[()]?|\([^\s\x00-\x1f\\]*\)|[^\s\x00-\x1f()\\])*?)/;
inline._title = /"(?:\\"?|[^"\\])*"|'(?:\\'?|[^'\\])*'|\((?:\\\)?|[^)\\])*\)/;

inline.link = edit(inline.link)
  .replace('label', inline._label)
  .replace('href', inline._href)
  .replace('title', inline._title)
  .getRegex();

inline.reflink = edit(inline.reflink)
  .replace('label', inline._label)
  .getRegex();

/**
 * Normal Inline Grammar
 */

inline.normal = merge({}, inline);

/**
 * Pedantic Inline Grammar
 */

inline.pedantic = merge({}, inline.normal, {
  strong: /^__(?=\S)([\s\S]*?\S)__(?!_)|^\*\*(?=\S)([\s\S]*?\S)\*\*(?!\*)/,
  em: /^_(?=\S)([\s\S]*?\S)_(?!_)|^\*(?=\S)([\s\S]*?\S)\*(?!\*)/,
  link: edit(/^!?\[(label)\]\((.*?)\)/)
    .replace('label', inline._label)
    .getRegex(),
  reflink: edit(/^!?\[(label)\]\s*\[([^\]]*)\]/)
    .replace('label', inline._label)
    .getRegex()
});

/**
 * GFM Inline Grammar
 */

inline.gfm = merge({}, inline.normal, {
  escape: edit(inline.escape).replace('])', '~|])').getRegex(),
  _extended_email: /[A-Za-z0-9._+-]+(@)[a-zA-Z0-9-_]+(?:\.[a-zA-Z0-9-_]*[a-zA-Z0-9])+(?![-_])/,
  url: /^((?:ftp|https?):\/\/|www\.)(?:[a-zA-Z0-9\-]+\.?)+[^\s<]*|^email/,
  _backpedal: /(?:[^?!.,:;*_~()&]+|\([^)]*\)|&(?![a-zA-Z0-9]+;$)|[?!.,:;*_~)]+(?!$))+/,
  del: /^~+(?=\S)([\s\S]*?\S)~+/,
  text: edit(inline.text)
    .replace(']|', '~]|')
    .replace('|$', '|https?://|ftp://|www\\.|[a-zA-Z0-9.!#$%&\'*+/=?^_`{\\|}~-]+@|$')
    .getRegex()
});

inline.gfm.url = edit(inline.gfm.url, 'i')
  .replace('email', inline.gfm._extended_email)
  .getRegex();
/**
 * GFM + Line Breaks Inline Grammar
 */

inline.breaks = merge({}, inline.gfm, {
  br: edit(inline.br).replace('{2,}', '*').getRegex(),
  text: edit(inline.gfm.text).replace('{2,}', '*').getRegex()
});

/**
 * Inline Lexer & Compiler
 */

function InlineLexer(links, options) {
  this.options = options || marked.defaults;
  this.links = links;
  this.rules = inline.normal;
  this.renderer = this.options.renderer || new Renderer();
  this.renderer.options = this.options;

  if (!this.links) {
    throw new Error('Tokens array requires a `links` property.');
  }

  if (this.options.pedantic) {
    this.rules = inline.pedantic;
  } else if (this.options.gfm) {
    if (this.options.breaks) {
      this.rules = inline.breaks;
    } else {
      this.rules = inline.gfm;
    }
  }
}

/**
 * Expose Inline Rules
 */

InlineLexer.rules = inline;

/**
 * Static Lexing/Compiling Method
 */

InlineLexer.output = function(src, links, options) {
  var inline = new InlineLexer(links, options);
  return inline.output(src);
};

/**
 * Lexing/Compiling
 */

InlineLexer.prototype.output = function(src) {
  var out = '',
      link,
      text,
      href,
      title,
      cap,
      prevCapZero;

  while (src) {
    // escape
    if (cap = this.rules.escape.exec(src)) {
      src = src.substring(cap[0].length);
      out += escape(cap[1]);
      continue;
    }

    // tag
    if (cap = this.rules.tag.exec(src)) {
      if (!this.inLink && /^<a /i.test(cap[0])) {
        this.inLink = true;
      } else if (this.inLink && /^<\/a>/i.test(cap[0])) {
        this.inLink = false;
      }
      if (!this.inRawBlock && /^<(pre|code|kbd|script)(\s|>)/i.test(cap[0])) {
        this.inRawBlock = true;
      } else if (this.inRawBlock && /^<\/(pre|code|kbd|script)(\s|>)/i.test(cap[0])) {
        this.inRawBlock = false;
      }

      src = src.substring(cap[0].length);
      out += this.options.sanitize
        ? this.options.sanitizer
          ? this.options.sanitizer(cap[0])
          : escape(cap[0])
        : cap[0];
      continue;
    }

    // link
    if (cap = this.rules.link.exec(src)) {
      src = src.substring(cap[0].length);
      this.inLink = true;
      href = cap[2];
      if (this.options.pedantic) {
        link = /^([^'"]*[^\s])\s+(['"])(.*)\2/.exec(href);

        if (link) {
          href = link[1];
          title = link[3];
        } else {
          title = '';
        }
      } else {
        title = cap[3] ? cap[3].slice(1, -1) : '';
      }
      href = href.trim().replace(/^<([\s\S]*)>$/, '$1');
      out += this.outputLink(cap, {
        href: InlineLexer.escapes(href),
        title: InlineLexer.escapes(title)
      });
      this.inLink = false;
      continue;
    }

    // reflink, nolink
    if ((cap = this.rules.reflink.exec(src))
        || (cap = this.rules.nolink.exec(src))) {
      src = src.substring(cap[0].length);
      link = (cap[2] || cap[1]).replace(/\s+/g, ' ');
      link = this.links[link.toLowerCase()];
      if (!link || !link.href) {
        out += cap[0].charAt(0);
        src = cap[0].substring(1) + src;
        continue;
      }
      this.inLink = true;
      out += this.outputLink(cap, link);
      this.inLink = false;
      continue;
    }

    // strong
    if (cap = this.rules.strong.exec(src)) {
      src = src.substring(cap[0].length);
      out += this.renderer.strong(this.output(cap[4] || cap[3] || cap[2] || cap[1]));
      continue;
    }

    // em
    if (cap = this.rules.em.exec(src)) {
      src = src.substring(cap[0].length);
      out += this.renderer.em(this.output(cap[6] || cap[5] || cap[4] || cap[3] || cap[2] || cap[1]));
      continue;
    }

    // code
    if (cap = this.rules.code.exec(src)) {
      src = src.substring(cap[0].length);
      out += this.renderer.codespan(escape(cap[2].trim(), true));
      continue;
    }

    // br
    if (cap = this.rules.br.exec(src)) {
      src = src.substring(cap[0].length);
      out += this.renderer.br();
      continue;
    }

    // del (gfm)
    if (cap = this.rules.del.exec(src)) {
      src = src.substring(cap[0].length);
      out += this.renderer.del(this.output(cap[1]));
      continue;
    }

    // autolink
    if (cap = this.rules.autolink.exec(src)) {
      src = src.substring(cap[0].length);
      if (cap[2] === '@') {
        text = escape(this.mangle(cap[1]));
        href = 'mailto:' + text;
      } else {
        text = escape(cap[1]);
        href = text;
      }
      out += this.renderer.link(href, null, text);
      continue;
    }

    // url (gfm)
    if (!this.inLink && (cap = this.rules.url.exec(src))) {
      if (cap[2] === '@') {
        text = escape(cap[0]);
        href = 'mailto:' + text;
      } else {
        // do extended autolink path validation
        do {
          prevCapZero = cap[0];
          cap[0] = this.rules._backpedal.exec(cap[0])[0];
        } while (prevCapZero !== cap[0]);
        text = escape(cap[0]);
        if (cap[1] === 'www.') {
          href = 'http://' + text;
        } else {
          href = text;
        }
      }
      src = src.substring(cap[0].length);
      out += this.renderer.link(href, null, text);
      continue;
    }

    // text
    if (cap = this.rules.text.exec(src)) {
      src = src.substring(cap[0].length);
      if (this.inRawBlock) {
        out += this.renderer.text(cap[0]);
      } else {
        out += this.renderer.text(escape(this.smartypants(cap[0])));
      }
      continue;
    }

    if (src) {
      throw new Error('Infinite loop on byte: ' + src.charCodeAt(0));
    }
  }

  return out;
};

InlineLexer.escapes = function(text) {
  return text ? text.replace(InlineLexer.rules._escapes, '$1') : text;
};

/**
 * Compile Link
 */

InlineLexer.prototype.outputLink = function(cap, link) {
  var href = link.href,
      title = link.title ? escape(link.title) : null;

  return cap[0].charAt(0) !== '!'
    ? this.renderer.link(href, title, this.output(cap[1]))
    : this.renderer.image(href, title, escape(cap[1]));
};

/**
 * Smartypants Transformations
 */

InlineLexer.prototype.smartypants = function(text) {
  if (!this.options.smartypants) return text;
  return text
    // em-dashes
    .replace(/---/g, '\u2014')
    // en-dashes
    .replace(/--/g, '\u2013')
    // opening singles
    .replace(/(^|[-\u2014/(\[{"\s])'/g, '$1\u2018')
    // closing singles & apostrophes
    .replace(/'/g, '\u2019')
    // opening doubles
    .replace(/(^|[-\u2014/(\[{\u2018\s])"/g, '$1\u201c')
    // closing doubles
    .replace(/"/g, '\u201d')
    // ellipses
    .replace(/\.{3}/g, '\u2026');
};

/**
 * Mangle Links
 */

InlineLexer.prototype.mangle = function(text) {
  if (!this.options.mangle) return text;
  var out = '',
      l = text.length,
      i = 0,
      ch;

  for (; i < l; i++) {
    ch = text.charCodeAt(i);
    if (Math.random() > 0.5) {
      ch = 'x' + ch.toString(16);
    }
    out += '&#' + ch + ';';
  }

  return out;
};

/**
 * Renderer
 */

function Renderer(options) {
  this.options = options || marked.defaults;
}

Renderer.prototype.code = function(code, infostring, escaped) {
  var lang = (infostring || '').match(/\S*/)[0];
  if (this.options.highlight) {
    var out = this.options.highlight(code, lang);
    if (out != null && out !== code) {
      escaped = true;
      code = out;
    }
  }

  if (!lang) {
    return '<pre><code>'
      + (escaped ? code : escape(code, true))
      + '</code></pre>';
  }

  return '<pre><code class="'
    + this.options.langPrefix
    + escape(lang, true)
    + '">'
    + (escaped ? code : escape(code, true))
    + '</code></pre>\n';
};

Renderer.prototype.blockquote = function(quote) {
  return '<blockquote>\n' + quote + '</blockquote>\n';
};

Renderer.prototype.html = function(html) {
  return html;
};

Renderer.prototype.heading = function(text, level, raw, slugger) {
  if (this.options.headerIds) {
    return '<h'
      + level
      + ' id="'
      + this.options.headerPrefix
      + slugger.slug(raw)
      + '">'
      + text
      + '</h'
      + level
      + '>\n';
  }
  // ignore IDs
  return '<h' + level + '>' + text + '</h' + level + '>\n';
};

Renderer.prototype.hr = function() {
  return this.options.xhtml ? '<hr/>\n' : '<hr>\n';
};

Renderer.prototype.list = function(body, ordered, start) {
  var type = ordered ? 'ol' : 'ul',
      startatt = (ordered && start !== 1) ? (' start="' + start + '"') : '';
  return '<' + type + startatt + '>\n' + body + '</' + type + '>\n';
};

Renderer.prototype.listitem = function(text) {
  return '<li>' + text + '</li>\n';
};

Renderer.prototype.checkbox = function(checked) {
  return '<input '
    + (checked ? 'checked="" ' : '')
    + 'disabled="" type="checkbox"'
    + (this.options.xhtml ? ' /' : '')
    + '> ';
};

Renderer.prototype.paragraph = function(text) {
  return '<p>' + text + '</p>\n';
};

Renderer.prototype.table = function(header, body) {
  if (body) body = '<tbody>' + body + '</tbody>';

  return '<table>\n'
    + '<thead>\n'
    + header
    + '</thead>\n'
    + body
    + '</table>\n';
};

Renderer.prototype.tablerow = function(content) {
  return '<tr>\n' + content + '</tr>\n';
};

Renderer.prototype.tablecell = function(content, flags) {
  var type = flags.header ? 'th' : 'td';
  var tag = flags.align
    ? '<' + type + ' align="' + flags.align + '">'
    : '<' + type + '>';
  return tag + content + '</' + type + '>\n';
};

// span level renderer
Renderer.prototype.strong = function(text) {
  return '<strong>' + text + '</strong>';
};

Renderer.prototype.em = function(text) {
  return '<em>' + text + '</em>';
};

Renderer.prototype.codespan = function(text) {
  return '<code>' + text + '</code>';
};

Renderer.prototype.br = function() {
  return this.options.xhtml ? '<br/>' : '<br>';
};

Renderer.prototype.del = function(text) {
  return '<del>' + text + '</del>';
};

Renderer.prototype.link = function(href, title, text) {
  href = cleanUrl(this.options.sanitize, this.options.baseUrl, href);
  if (href === null) {
    return text;
  }
  var out = '<a href="' + escape(href) + '"';
  if (title) {
    out += ' title="' + title + '"';
  }
  out += '>' + text + '</a>';
  return out;
};

Renderer.prototype.image = function(href, title, text) {
  href = cleanUrl(this.options.sanitize, this.options.baseUrl, href);
  if (href === null) {
    return text;
  }

  var out = '<img src="' + href + '" alt="' + text + '"';
  if (title) {
    out += ' title="' + title + '"';
  }
  out += this.options.xhtml ? '/>' : '>';
  return out;
};

Renderer.prototype.text = function(text) {
  return text;
};

/**
 * TextRenderer
 * returns only the textual part of the token
 */

function TextRenderer() {}

// no need for block level renderers

TextRenderer.prototype.strong =
TextRenderer.prototype.em =
TextRenderer.prototype.codespan =
TextRenderer.prototype.del =
TextRenderer.prototype.text = function (text) {
  return text;
};

TextRenderer.prototype.link =
TextRenderer.prototype.image = function(href, title, text) {
  return '' + text;
};

TextRenderer.prototype.br = function() {
  return '';
};

/**
 * Parsing & Compiling
 */

function Parser(options) {
  this.tokens = [];
  this.token = null;
  this.options = options || marked.defaults;
  this.options.renderer = this.options.renderer || new Renderer();
  this.renderer = this.options.renderer;
  this.renderer.options = this.options;
  this.slugger = new Slugger();
}

/**
 * Static Parse Method
 */

Parser.parse = function(src, options) {
  var parser = new Parser(options);
  return parser.parse(src);
};

/**
 * Parse Loop
 */

Parser.prototype.parse = function(src) {
  this.inline = new InlineLexer(src.links, this.options);
  // use an InlineLexer with a TextRenderer to extract pure text
  this.inlineText = new InlineLexer(
    src.links,
    merge({}, this.options, {renderer: new TextRenderer()})
  );
  this.tokens = src.reverse();

  var out = '';
  while (this.next()) {
    out += this.tok();
  }

  return out;
};

/**
 * Next Token
 */

Parser.prototype.next = function() {
  return this.token = this.tokens.pop();
};

/**
 * Preview Next Token
 */

Parser.prototype.peek = function() {
  return this.tokens[this.tokens.length - 1] || 0;
};

/**
 * Parse Text Tokens
 */

Parser.prototype.parseText = function() {
  var body = this.token.text;

  while (this.peek().type === 'text') {
    body += '\n' + this.next().text;
  }

  return this.inline.output(body);
};

/**
 * Parse Current Token
 */

Parser.prototype.tok = function() {
  switch (this.token.type) {
    case 'space': {
      return '';
    }
    case 'hr': {
      return this.renderer.hr();
    }
    case 'heading': {
      return this.renderer.heading(
        this.inline.output(this.token.text),
        this.token.depth,
        unescape(this.inlineText.output(this.token.text)),
        this.slugger);
    }
    case 'code': {
      return this.renderer.code(this.token.text,
        this.token.lang,
        this.token.escaped);
    }
    case 'table': {
      var header = '',
          body = '',
          i,
          row,
          cell,
          j;

      // header
      cell = '';
      for (i = 0; i < this.token.header.length; i++) {
        cell += this.renderer.tablecell(
          this.inline.output(this.token.header[i]),
          { header: true, align: this.token.align[i] }
        );
      }
      header += this.renderer.tablerow(cell);

      for (i = 0; i < this.token.cells.length; i++) {
        row = this.token.cells[i];

        cell = '';
        for (j = 0; j < row.length; j++) {
          cell += this.renderer.tablecell(
            this.inline.output(row[j]),
            { header: false, align: this.token.align[j] }
          );
        }

        body += this.renderer.tablerow(cell);
      }
      return this.renderer.table(header, body);
    }
    case 'blockquote_start': {
      body = '';

      while (this.next().type !== 'blockquote_end') {
        body += this.tok();
      }

      return this.renderer.blockquote(body);
    }
    case 'list_start': {
      body = '';
      var ordered = this.token.ordered,
          start = this.token.start;

      while (this.next().type !== 'list_end') {
        body += this.tok();
      }

      return this.renderer.list(body, ordered, start);
    }
    case 'list_item_start': {
      body = '';
      var loose = this.token.loose;

      if (this.token.task) {
        body += this.renderer.checkbox(this.token.checked);
      }

      while (this.next().type !== 'list_item_end') {
        body += !loose && this.token.type === 'text'
          ? this.parseText()
          : this.tok();
      }

      return this.renderer.listitem(body);
    }
    case 'html': {
      // TODO parse inline content if parameter markdown=1
      return this.renderer.html(this.token.text);
    }
    case 'paragraph': {
      return this.renderer.paragraph(this.inline.output(this.token.text));
    }
    case 'text': {
      return this.renderer.paragraph(this.parseText());
    }
    default: {
      var errMsg = 'Token with "' + this.token.type + '" type was not found.';
      if (this.options.silent) {
        console.log(errMsg);
      } else {
        throw new Error(errMsg);
      }
    }
  }
};

/**
 * Slugger generates header id
 */

function Slugger () {
  this.seen = {};
}

/**
 * Convert string to unique id
 */

Slugger.prototype.slug = function (value) {
  var slug = value
    .toLowerCase()
    .trim()
    .replace(/[\u2000-\u206F\u2E00-\u2E7F\\'!"#$%&()*+,./:;<=>?@[\]^`{|}~]/g, '')
    .replace(/\s/g, '-');

  if (this.seen.hasOwnProperty(slug)) {
    var originalSlug = slug;
    do {
      this.seen[originalSlug]++;
      slug = originalSlug + '-' + this.seen[originalSlug];
    } while (this.seen.hasOwnProperty(slug));
  }
  this.seen[slug] = 0;

  return slug;
};

/**
 * Helpers
 */

function escape(html, encode) {
  if (encode) {
    if (escape.escapeTest.test(html)) {
      return html.replace(escape.escapeReplace, function (ch) { return escape.replacements[ch]; });
    }
  } else {
    if (escape.escapeTestNoEncode.test(html)) {
      return html.replace(escape.escapeReplaceNoEncode, function (ch) { return escape.replacements[ch]; });
    }
  }

  return html;
}

escape.escapeTest = /[&<>"']/;
escape.escapeReplace = /[&<>"']/g;
escape.replacements = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;'
};

escape.escapeTestNoEncode = /[<>"']|&(?!#?\w+;)/;
escape.escapeReplaceNoEncode = /[<>"']|&(?!#?\w+;)/g;

function unescape(html) {
  // explicitly match decimal, hex, and named HTML entities
  return html.replace(/&(#(?:\d+)|(?:#x[0-9A-Fa-f]+)|(?:\w+));?/ig, function(_, n) {
    n = n.toLowerCase();
    if (n === 'colon') return ':';
    if (n.charAt(0) === '#') {
      return n.charAt(1) === 'x'
        ? String.fromCharCode(parseInt(n.substring(2), 16))
        : String.fromCharCode(+n.substring(1));
    }
    return '';
  });
}

function edit(regex, opt) {
  regex = regex.source || regex;
  opt = opt || '';
  return {
    replace: function(name, val) {
      val = val.source || val;
      val = val.replace(/(^|[^\[])\^/g, '$1');
      regex = regex.replace(name, val);
      return this;
    },
    getRegex: function() {
      return new RegExp(regex, opt);
    }
  };
}

function cleanUrl(sanitize, base, href) {
  if (sanitize) {
    try {
      var prot = decodeURIComponent(unescape(href))
        .replace(/[^\w:]/g, '')
        .toLowerCase();
    } catch (e) {
      return null;
    }
    if (prot.indexOf('javascript:') === 0 || prot.indexOf('vbscript:') === 0 || prot.indexOf('data:') === 0) {
      return null;
    }
  }
  if (base && !originIndependentUrl.test(href)) {
    href = resolveUrl(base, href);
  }
  try {
    href = encodeURI(href).replace(/%25/g, '%');
  } catch (e) {
    return null;
  }
  return href;
}

function resolveUrl(base, href) {
  if (!baseUrls[' ' + base]) {
    // we can ignore everything in base after the last slash of its path component,
    // but we might need to add _that_
    // https://tools.ietf.org/html/rfc3986#section-3
    if (/^[^:]+:\/*[^/]*$/.test(base)) {
      baseUrls[' ' + base] = base + '/';
    } else {
      baseUrls[' ' + base] = rtrim(base, '/', true);
    }
  }
  base = baseUrls[' ' + base];

  if (href.slice(0, 2) === '//') {
    return base.replace(/:[\s\S]*/, ':') + href;
  } else if (href.charAt(0) === '/') {
    return base.replace(/(:\/*[^/]*)[\s\S]*/, '$1') + href;
  } else {
    return base + href;
  }
}
var baseUrls = {};
var originIndependentUrl = /^$|^[a-z][a-z0-9+.-]*:|^[?#]/i;

function noop() {}
noop.exec = noop;

function merge(obj) {
  var i = 1,
      target,
      key;

  for (; i < arguments.length; i++) {
    target = arguments[i];
    for (key in target) {
      if (Object.prototype.hasOwnProperty.call(target, key)) {
        obj[key] = target[key];
      }
    }
  }

  return obj;
}

function splitCells(tableRow, count) {
  // ensure that every cell-delimiting pipe has a space
  // before it to distinguish it from an escaped pipe
  var row = tableRow.replace(/\|/g, function (match, offset, str) {
        var escaped = false,
            curr = offset;
        while (--curr >= 0 && str[curr] === '\\') escaped = !escaped;
        if (escaped) {
          // odd number of slashes means | is escaped
          // so we leave it alone
          return '|';
        } else {
          // add space before unescaped |
          return ' |';
        }
      }),
      cells = row.split(/ \|/),
      i = 0;

  if (cells.length > count) {
    cells.splice(count);
  } else {
    while (cells.length < count) cells.push('');
  }

  for (; i < cells.length; i++) {
    // leading or trailing whitespace is ignored per the gfm spec
    cells[i] = cells[i].trim().replace(/\\\|/g, '|');
  }
  return cells;
}

// Remove trailing 'c's. Equivalent to str.replace(/c*$/, '').
// /c*$/ is vulnerable to REDOS.
// invert: Remove suffix of non-c chars instead. Default falsey.
function rtrim(str, c, invert) {
  if (str.length === 0) {
    return '';
  }

  // Length of suffix matching the invert condition.
  var suffLen = 0;

  // Step left until we fail to match the invert condition.
  while (suffLen < str.length) {
    var currChar = str.charAt(str.length - suffLen - 1);
    if (currChar === c && !invert) {
      suffLen++;
    } else if (currChar !== c && invert) {
      suffLen++;
    } else {
      break;
    }
  }

  return str.substr(0, str.length - suffLen);
}

/**
 * Marked
 */

function marked(src, opt, callback) {
  // throw error in case of non string input
  if (typeof src === 'undefined' || src === null) {
    throw new Error('marked(): input parameter is undefined or null');
  }
  if (typeof src !== 'string') {
    throw new Error('marked(): input parameter is of type '
      + Object.prototype.toString.call(src) + ', string expected');
  }

  if (callback || typeof opt === 'function') {
    if (!callback) {
      callback = opt;
      opt = null;
    }

    opt = merge({}, marked.defaults, opt || {});

    var highlight = opt.highlight,
        tokens,
        pending,
        i = 0;

    try {
      tokens = Lexer.lex(src, opt);
    } catch (e) {
      return callback(e);
    }

    pending = tokens.length;

    var done = function(err) {
      if (err) {
        opt.highlight = highlight;
        return callback(err);
      }

      var out;

      try {
        out = Parser.parse(tokens, opt);
      } catch (e) {
        err = e;
      }

      opt.highlight = highlight;

      return err
        ? callback(err)
        : callback(null, out);
    };

    if (!highlight || highlight.length < 3) {
      return done();
    }

    delete opt.highlight;

    if (!pending) return done();

    for (; i < tokens.length; i++) {
      (function(token) {
        if (token.type !== 'code') {
          return --pending || done();
        }
        return highlight(token.text, token.lang, function(err, code) {
          if (err) return done(err);
          if (code == null || code === token.text) {
            return --pending || done();
          }
          token.text = code;
          token.escaped = true;
          --pending || done();
        });
      })(tokens[i]);
    }

    return;
  }
  try {
    if (opt) opt = merge({}, marked.defaults, opt);
    return Parser.parse(Lexer.lex(src, opt), opt);
  } catch (e) {
    e.message += '\nPlease report this to https://github.com/markedjs/marked.';
    if ((opt || marked.defaults).silent) {
      return '<p>An error occurred:</p><pre>'
        + escape(e.message + '', true)
        + '</pre>';
    }
    throw e;
  }
}

/**
 * Options
 */

marked.options =
marked.setOptions = function(opt) {
  merge(marked.defaults, opt);
  return marked;
};

marked.getDefaults = function () {
  return {
    baseUrl: null,
    breaks: false,
    gfm: true,
    headerIds: true,
    headerPrefix: '',
    highlight: null,
    langPrefix: 'language-',
    mangle: true,
    pedantic: false,
    renderer: new Renderer(),
    sanitize: false,
    sanitizer: null,
    silent: false,
    smartLists: false,
    smartypants: false,
    tables: true,
    xhtml: false
  };
};

marked.defaults = marked.getDefaults();

/**
 * Expose
 */

marked.Parser = Parser;
marked.parser = Parser.parse;

marked.Renderer = Renderer;
marked.TextRenderer = TextRenderer;

marked.Lexer = Lexer;
marked.lexer = Lexer.lex;

marked.InlineLexer = InlineLexer;
marked.inlineLexer = InlineLexer.output;

marked.Slugger = Slugger;

marked.parse = marked;

if (true) {
  module.exports = marked;
} else {}
})(this || (typeof window !== 'undefined' ? window : global));

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../../../node_modules/webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./settings/node_modules/nextcloud-vue/dist/ncvuecomponents.js":
/*!*********************************************************************!*\
  !*** ./settings/node_modules/nextcloud-vue/dist/ncvuecomponents.js ***!
  \*********************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(t,e){ true?module.exports=e():undefined}(window,function(){return function(t){var e={};function n(i){if(e[i])return e[i].exports;var r=e[i]={i:i,l:!1,exports:{}};return t[i].call(r.exports,r,r.exports,n),r.l=!0,r.exports}return n.m=t,n.c=e,n.d=function(t,e,i){n.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:i})},n.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},n.t=function(t,e){if(1&e&&(t=n(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var i=Object.create(null);if(n.r(i),Object.defineProperty(i,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)n.d(i,r,function(e){return t[e]}.bind(null,r));return i},n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="/dist/",n(n.s=59)}([function(t,e,n){"use strict";var i=n(11),r=n(12),o=Object.prototype.toString;function a(t){return"[object Array]"===o.call(t)}function s(t){return null!==t&&"object"==typeof t}function l(t){return"[object Function]"===o.call(t)}function u(t,e){if(null!=t)if("object"!=typeof t&&(t=[t]),a(t))for(var n=0,i=t.length;n<i;n++)e.call(null,t[n],n,t);else for(var r in t)Object.prototype.hasOwnProperty.call(t,r)&&e.call(null,t[r],r,t)}t.exports={isArray:a,isArrayBuffer:function(t){return"[object ArrayBuffer]"===o.call(t)},isBuffer:r,isFormData:function(t){return"undefined"!=typeof FormData&&t instanceof FormData},isArrayBufferView:function(t){return"undefined"!=typeof ArrayBuffer&&ArrayBuffer.isView?ArrayBuffer.isView(t):t&&t.buffer&&t.buffer instanceof ArrayBuffer},isString:function(t){return"string"==typeof t},isNumber:function(t){return"number"==typeof t},isObject:s,isUndefined:function(t){return void 0===t},isDate:function(t){return"[object Date]"===o.call(t)},isFile:function(t){return"[object File]"===o.call(t)},isBlob:function(t){return"[object Blob]"===o.call(t)},isFunction:l,isStream:function(t){return s(t)&&l(t.pipe)},isURLSearchParams:function(t){return"undefined"!=typeof URLSearchParams&&t instanceof URLSearchParams},isStandardBrowserEnv:function(){return("undefined"==typeof navigator||"ReactNative"!==navigator.product)&&"undefined"!=typeof window&&"undefined"!=typeof document},forEach:u,merge:function t(){var e={};function n(n,i){"object"==typeof e[i]&&"object"==typeof n?e[i]=t(e[i],n):e[i]=n}for(var i=0,r=arguments.length;i<r;i++)u(arguments[i],n);return e},extend:function(t,e,n){return u(e,function(e,r){t[r]=n&&"function"==typeof e?i(e,n):e}),t},trim:function(t){return t.replace(/^\s*/,"").replace(/\s*$/,"")}}},function(t,e){function n(t){return"function"==typeof t.value||(console.warn("[Vue-click-outside:] provided expression",t.expression,"is not a function."),!1)}function i(t){return void 0!==t.componentInstance&&t.componentInstance.$isServer}t.exports={bind:function(t,e,r){function o(e){if(r.context){var n=e.path||e.composedPath&&e.composedPath();n&&n.length>0&&n.unshift(e.target),t.contains(e.target)||function(t,e){if(!t||!e)return!1;for(var n=0,i=e.length;n<i;n++)try{if(t.contains(e[n]))return!0;if(e[n].contains(t))return!1}catch(t){return!1}return!1}(r.context.popupItem,n)||t.__vueClickOutside__.callback(e)}}n(e)&&(t.__vueClickOutside__={handler:o,callback:e.value},!i(r)&&document.addEventListener("click",o))},update:function(t,e){n(e)&&(t.__vueClickOutside__.callback=e.value)},unbind:function(t,e,n){!i(n)&&document.removeEventListener("click",t.__vueClickOutside__.handler),delete t.__vueClickOutside__}}},function(t,e,n){"use strict";t.exports=function(t){var e=[];return e.toString=function(){return this.map(function(e){var n=function(t,e){var n=t[1]||"",i=t[3];if(!i)return n;if(e&&"function"==typeof btoa){var r=(a=i,"/*# sourceMappingURL=data:application/json;charset=utf-8;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(a))))+" */"),o=i.sources.map(function(t){return"/*# sourceURL="+i.sourceRoot+t+" */"});return[n].concat(o).concat([r]).join("\n")}var a;return[n].join("\n")}(e,t);return e[2]?"@media "+e[2]+"{"+n+"}":n}).join("")},e.i=function(t,n){"string"==typeof t&&(t=[[null,t,""]]);for(var i={},r=0;r<this.length;r++){var o=this[r][0];null!=o&&(i[o]=!0)}for(r=0;r<t.length;r++){var a=t[r];null!=a[0]&&i[a[0]]||(n&&!a[2]?a[2]=n:n&&(a[2]="("+a[2]+") and ("+n+")"),e.push(a))}},e}},function(t,e,n){"use strict";function i(t,e){for(var n=[],i={},r=0;r<e.length;r++){var o=e[r],a=o[0],s={id:t+":"+r,css:o[1],media:o[2],sourceMap:o[3]};i[a]?i[a].parts.push(s):n.push(i[a]={id:a,parts:[s]})}return n}n.r(e),n.d(e,"default",function(){return f});var r="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!r)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var o={},a=r&&(document.head||document.getElementsByTagName("head")[0]),s=null,l=0,u=!1,c=function(){},p=null,d="data-vue-ssr-id",A="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function f(t,e,n,r){u=n,p=r||{};var a=i(t,e);return h(a),function(e){for(var n=[],r=0;r<a.length;r++){var s=a[r];(l=o[s.id]).refs--,n.push(l)}e?h(a=i(t,e)):a=[];for(r=0;r<n.length;r++){var l;if(0===(l=n[r]).refs){for(var u=0;u<l.parts.length;u++)l.parts[u]();delete o[l.id]}}}}function h(t){for(var e=0;e<t.length;e++){var n=t[e],i=o[n.id];if(i){i.refs++;for(var r=0;r<i.parts.length;r++)i.parts[r](n.parts[r]);for(;r<n.parts.length;r++)i.parts.push(v(n.parts[r]));i.parts.length>n.parts.length&&(i.parts.length=n.parts.length)}else{var a=[];for(r=0;r<n.parts.length;r++)a.push(v(n.parts[r]));o[n.id]={id:n.id,refs:1,parts:a}}}}function m(){var t=document.createElement("style");return t.type="text/css",a.appendChild(t),t}function v(t){var e,n,i=document.querySelector("style["+d+'~="'+t.id+'"]');if(i){if(u)return c;i.parentNode.removeChild(i)}if(A){var r=l++;i=s||(s=m()),e=b.bind(null,i,r,!1),n=b.bind(null,i,r,!0)}else i=m(),e=function(t,e){var n=e.css,i=e.media,r=e.sourceMap;i&&t.setAttribute("media",i);p.ssrId&&t.setAttribute(d,e.id);r&&(n+="\n/*# sourceURL="+r.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */");if(t.styleSheet)t.styleSheet.cssText=n;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(n))}}.bind(null,i),n=function(){i.parentNode.removeChild(i)};return e(t),function(i){if(i){if(i.css===t.css&&i.media===t.media&&i.sourceMap===t.sourceMap)return;e(t=i)}else n()}}var g,y=(g=[],function(t,e){return g[t]=e,g.filter(Boolean).join("\n")});function b(t,e,n,i){var r=n?"":i.css;if(t.styleSheet)t.styleSheet.cssText=y(e,r);else{var o=document.createTextNode(r),a=t.childNodes;a[e]&&t.removeChild(a[e]),a.length?t.insertBefore(o,a[e]):t.appendChild(o)}}},function(t,e,n){var i=n(22);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);(0,n(3).default)("31067db4",i,!1,{})},function(t,e,n){var i=n(52);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);(0,n(3).default)("7995b501",i,!1,{})},function(t,e,n){var i=n(54);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);(0,n(3).default)("6bd496c7",i,!1,{})},function(t,e,n){var i=n(58);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);(0,n(3).default)("fe61d5e0",i,!1,{})},function(t,e,n){"use strict";(function(t){n.d(e,"a",function(){return Rt});for(
/**!
 * @fileOverview Kickass library to create and place poppers near their reference elements.
 * @version 1.14.3
 * @license
 * Copyright (c) 2016 Federico Zivolo and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
var i="undefined"!=typeof window&&"undefined"!=typeof document,r=["Edge","Trident","Firefox"],o=0,a=0;a<r.length;a+=1)if(i&&navigator.userAgent.indexOf(r[a])>=0){o=1;break}var s=i&&window.Promise?function(t){var e=!1;return function(){e||(e=!0,window.Promise.resolve().then(function(){e=!1,t()}))}}:function(t){var e=!1;return function(){e||(e=!0,setTimeout(function(){e=!1,t()},o))}};function l(t){return t&&"[object Function]"==={}.toString.call(t)}function u(t,e){if(1!==t.nodeType)return[];var n=getComputedStyle(t,null);return e?n[e]:n}function c(t){return"HTML"===t.nodeName?t:t.parentNode||t.host}function p(t){if(!t)return document.body;switch(t.nodeName){case"HTML":case"BODY":return t.ownerDocument.body;case"#document":return t.body}var e=u(t),n=e.overflow,i=e.overflowX,r=e.overflowY;return/(auto|scroll|overlay)/.test(n+r+i)?t:p(c(t))}var d=i&&!(!window.MSInputMethodContext||!document.documentMode),A=i&&/MSIE 10/.test(navigator.userAgent);function f(t){return 11===t?d:10===t?A:d||A}function h(t){if(!t)return document.documentElement;for(var e=f(10)?document.body:null,n=t.offsetParent;n===e&&t.nextElementSibling;)n=(t=t.nextElementSibling).offsetParent;var i=n&&n.nodeName;return i&&"BODY"!==i&&"HTML"!==i?-1!==["TD","TABLE"].indexOf(n.nodeName)&&"static"===u(n,"position")?h(n):n:t?t.ownerDocument.documentElement:document.documentElement}function m(t){return null!==t.parentNode?m(t.parentNode):t}function v(t,e){if(!(t&&t.nodeType&&e&&e.nodeType))return document.documentElement;var n=t.compareDocumentPosition(e)&Node.DOCUMENT_POSITION_FOLLOWING,i=n?t:e,r=n?e:t,o=document.createRange();o.setStart(i,0),o.setEnd(r,0);var a,s,l=o.commonAncestorContainer;if(t!==l&&e!==l||i.contains(r))return"BODY"===(s=(a=l).nodeName)||"HTML"!==s&&h(a.firstElementChild)!==a?h(l):l;var u=m(t);return u.host?v(u.host,e):v(t,m(e).host)}function g(t){var e="top"===(arguments.length>1&&void 0!==arguments[1]?arguments[1]:"top")?"scrollTop":"scrollLeft",n=t.nodeName;if("BODY"===n||"HTML"===n){var i=t.ownerDocument.documentElement;return(t.ownerDocument.scrollingElement||i)[e]}return t[e]}function y(t,e){var n="x"===e?"Left":"Top",i="Left"===n?"Right":"Bottom";return parseFloat(t["border"+n+"Width"],10)+parseFloat(t["border"+i+"Width"],10)}function b(t,e,n,i){return Math.max(e["offset"+t],e["scroll"+t],n["client"+t],n["offset"+t],n["scroll"+t],f(10)?n["offset"+t]+i["margin"+("Height"===t?"Top":"Left")]+i["margin"+("Height"===t?"Bottom":"Right")]:0)}function w(){var t=document.body,e=document.documentElement,n=f(10)&&getComputedStyle(e);return{height:b("Height",t,e,n),width:b("Width",t,e,n)}}var _=function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")},x=function(){function t(t,e){for(var n=0;n<e.length;n++){var i=e[n];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(t,i.key,i)}}return function(e,n,i){return n&&t(e.prototype,n),i&&t(e,i),e}}(),D=function(t,e,n){return e in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t},C=Object.assign||function(t){for(var e=1;e<arguments.length;e++){var n=arguments[e];for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(t[i]=n[i])}return t};function E(t){return C({},t,{right:t.left+t.width,bottom:t.top+t.height})}function k(t){var e={};try{if(f(10)){e=t.getBoundingClientRect();var n=g(t,"top"),i=g(t,"left");e.top+=n,e.left+=i,e.bottom+=n,e.right+=i}else e=t.getBoundingClientRect()}catch(t){}var r={left:e.left,top:e.top,width:e.right-e.left,height:e.bottom-e.top},o="HTML"===t.nodeName?w():{},a=o.width||t.clientWidth||r.right-r.left,s=o.height||t.clientHeight||r.bottom-r.top,l=t.offsetWidth-a,c=t.offsetHeight-s;if(l||c){var p=u(t);l-=y(p,"x"),c-=y(p,"y"),r.width-=l,r.height-=c}return E(r)}function S(t,e){var n=arguments.length>2&&void 0!==arguments[2]&&arguments[2],i=f(10),r="HTML"===e.nodeName,o=k(t),a=k(e),s=p(t),l=u(e),c=parseFloat(l.borderTopWidth,10),d=parseFloat(l.borderLeftWidth,10);n&&"HTML"===e.nodeName&&(a.top=Math.max(a.top,0),a.left=Math.max(a.left,0));var A=E({top:o.top-a.top-c,left:o.left-a.left-d,width:o.width,height:o.height});if(A.marginTop=0,A.marginLeft=0,!i&&r){var h=parseFloat(l.marginTop,10),m=parseFloat(l.marginLeft,10);A.top-=c-h,A.bottom-=c-h,A.left-=d-m,A.right-=d-m,A.marginTop=h,A.marginLeft=m}return(i&&!n?e.contains(s):e===s&&"BODY"!==s.nodeName)&&(A=function(t,e){var n=arguments.length>2&&void 0!==arguments[2]&&arguments[2],i=g(e,"top"),r=g(e,"left"),o=n?-1:1;return t.top+=i*o,t.bottom+=i*o,t.left+=r*o,t.right+=r*o,t}(A,e)),A}function O(t){if(!t||!t.parentElement||f())return document.documentElement;for(var e=t.parentElement;e&&"none"===u(e,"transform");)e=e.parentElement;return e||document.documentElement}function T(t,e,n,i){var r=arguments.length>4&&void 0!==arguments[4]&&arguments[4],o={top:0,left:0},a=r?O(t):v(t,e);if("viewport"===i)o=function(t){var e=arguments.length>1&&void 0!==arguments[1]&&arguments[1],n=t.ownerDocument.documentElement,i=S(t,n),r=Math.max(n.clientWidth,window.innerWidth||0),o=Math.max(n.clientHeight,window.innerHeight||0),a=e?0:g(n),s=e?0:g(n,"left");return E({top:a-i.top+i.marginTop,left:s-i.left+i.marginLeft,width:r,height:o})}(a,r);else{var s=void 0;"scrollParent"===i?"BODY"===(s=p(c(e))).nodeName&&(s=t.ownerDocument.documentElement):s="window"===i?t.ownerDocument.documentElement:i;var l=S(s,a,r);if("HTML"!==s.nodeName||function t(e){var n=e.nodeName;return"BODY"!==n&&"HTML"!==n&&("fixed"===u(e,"position")||t(c(e)))}(a))o=l;else{var d=w(),A=d.height,f=d.width;o.top+=l.top-l.marginTop,o.bottom=A+l.top,o.left+=l.left-l.marginLeft,o.right=f+l.left}}return o.left+=n,o.top+=n,o.right-=n,o.bottom-=n,o}function M(t,e,n,i,r){var o=arguments.length>5&&void 0!==arguments[5]?arguments[5]:0;if(-1===t.indexOf("auto"))return t;var a=T(n,i,o,r),s={top:{width:a.width,height:e.top-a.top},right:{width:a.right-e.right,height:a.height},bottom:{width:a.width,height:a.bottom-e.bottom},left:{width:e.left-a.left,height:a.height}},l=Object.keys(s).map(function(t){return C({key:t},s[t],{area:(e=s[t],e.width*e.height)});var e}).sort(function(t,e){return e.area-t.area}),u=l.filter(function(t){var e=t.width,i=t.height;return e>=n.clientWidth&&i>=n.clientHeight}),c=u.length>0?u[0].key:l[0].key,p=t.split("-")[1];return c+(p?"-"+p:"")}function B(t,e,n){var i=arguments.length>3&&void 0!==arguments[3]?arguments[3]:null;return S(n,i?O(e):v(e,n),i)}function N(t){var e=getComputedStyle(t),n=parseFloat(e.marginTop)+parseFloat(e.marginBottom),i=parseFloat(e.marginLeft)+parseFloat(e.marginRight);return{width:t.offsetWidth+i,height:t.offsetHeight+n}}function I(t){var e={left:"right",right:"left",bottom:"top",top:"bottom"};return t.replace(/left|right|bottom|top/g,function(t){return e[t]})}function L(t,e,n){n=n.split("-")[0];var i=N(t),r={width:i.width,height:i.height},o=-1!==["right","left"].indexOf(n),a=o?"top":"left",s=o?"left":"top",l=o?"height":"width",u=o?"width":"height";return r[a]=e[a]+e[l]/2-i[l]/2,r[s]=n===s?e[s]-i[u]:e[I(s)],r}function P(t,e){return Array.prototype.find?t.find(e):t.filter(e)[0]}function j(t,e,n){return(void 0===n?t:t.slice(0,function(t,e,n){if(Array.prototype.findIndex)return t.findIndex(function(t){return t[e]===n});var i=P(t,function(t){return t[e]===n});return t.indexOf(i)}(t,"name",n))).forEach(function(t){t.function&&console.warn("`modifier.function` is deprecated, use `modifier.fn`!");var n=t.function||t.fn;t.enabled&&l(n)&&(e.offsets.popper=E(e.offsets.popper),e.offsets.reference=E(e.offsets.reference),e=n(e,t))}),e}function $(t,e){return t.some(function(t){var n=t.name;return t.enabled&&n===e})}function Y(t){for(var e=[!1,"ms","Webkit","Moz","O"],n=t.charAt(0).toUpperCase()+t.slice(1),i=0;i<e.length;i++){var r=e[i],o=r?""+r+n:t;if(void 0!==document.body.style[o])return o}return null}function V(t){var e=t.ownerDocument;return e?e.defaultView:window}function Q(t,e,n,i){n.updateBound=i,V(t).addEventListener("resize",n.updateBound,{passive:!0});var r=p(t);return function t(e,n,i,r){var o="BODY"===e.nodeName,a=o?e.ownerDocument.defaultView:e;a.addEventListener(n,i,{passive:!0}),o||t(p(a.parentNode),n,i,r),r.push(a)}(r,"scroll",n.updateBound,n.scrollParents),n.scrollElement=r,n.eventsEnabled=!0,n}function F(){var t,e;this.state.eventsEnabled&&(cancelAnimationFrame(this.scheduleUpdate),this.state=(t=this.reference,e=this.state,V(t).removeEventListener("resize",e.updateBound),e.scrollParents.forEach(function(t){t.removeEventListener("scroll",e.updateBound)}),e.updateBound=null,e.scrollParents=[],e.scrollElement=null,e.eventsEnabled=!1,e))}function R(t){return""!==t&&!isNaN(parseFloat(t))&&isFinite(t)}function G(t,e){Object.keys(e).forEach(function(n){var i="";-1!==["width","height","top","right","bottom","left"].indexOf(n)&&R(e[n])&&(i="px"),t.style[n]=e[n]+i})}function H(t,e,n){var i=P(t,function(t){return t.name===e}),r=!!i&&t.some(function(t){return t.name===n&&t.enabled&&t.order<i.order});if(!r){var o="`"+e+"`",a="`"+n+"`";console.warn(a+" modifier is required by "+o+" modifier in order to work, be sure to include it before "+o+"!")}return r}var U=["auto-start","auto","auto-end","top-start","top","top-end","right-start","right","right-end","bottom-end","bottom","bottom-start","left-end","left","left-start"],W=U.slice(3);function z(t){var e=arguments.length>1&&void 0!==arguments[1]&&arguments[1],n=W.indexOf(t),i=W.slice(n+1).concat(W.slice(0,n));return e?i.reverse():i}var Z={FLIP:"flip",CLOCKWISE:"clockwise",COUNTERCLOCKWISE:"counterclockwise"};function J(t,e,n,i){var r=[0,0],o=-1!==["right","left"].indexOf(i),a=t.split(/(\+|\-)/).map(function(t){return t.trim()}),s=a.indexOf(P(a,function(t){return-1!==t.search(/,|\s/)}));a[s]&&-1===a[s].indexOf(",")&&console.warn("Offsets separated by white space(s) are deprecated, use a comma (,) instead.");var l=/\s*,\s*|\s+/,u=-1!==s?[a.slice(0,s).concat([a[s].split(l)[0]]),[a[s].split(l)[1]].concat(a.slice(s+1))]:[a];return(u=u.map(function(t,i){var r=(1===i?!o:o)?"height":"width",a=!1;return t.reduce(function(t,e){return""===t[t.length-1]&&-1!==["+","-"].indexOf(e)?(t[t.length-1]=e,a=!0,t):a?(t[t.length-1]+=e,a=!1,t):t.concat(e)},[]).map(function(t){return function(t,e,n,i){var r=t.match(/((?:\-|\+)?\d*\.?\d*)(.*)/),o=+r[1],a=r[2];if(!o)return t;if(0===a.indexOf("%")){var s=void 0;switch(a){case"%p":s=n;break;case"%":case"%r":default:s=i}return E(s)[e]/100*o}if("vh"===a||"vw"===a)return("vh"===a?Math.max(document.documentElement.clientHeight,window.innerHeight||0):Math.max(document.documentElement.clientWidth,window.innerWidth||0))/100*o;return o}(t,r,e,n)})})).forEach(function(t,e){t.forEach(function(n,i){R(n)&&(r[e]+=n*("-"===t[i-1]?-1:1))})}),r}var q={placement:"bottom",positionFixed:!1,eventsEnabled:!0,removeOnDestroy:!1,onCreate:function(){},onUpdate:function(){},modifiers:{shift:{order:100,enabled:!0,fn:function(t){var e=t.placement,n=e.split("-")[0],i=e.split("-")[1];if(i){var r=t.offsets,o=r.reference,a=r.popper,s=-1!==["bottom","top"].indexOf(n),l=s?"left":"top",u=s?"width":"height",c={start:D({},l,o[l]),end:D({},l,o[l]+o[u]-a[u])};t.offsets.popper=C({},a,c[i])}return t}},offset:{order:200,enabled:!0,fn:function(t,e){var n=e.offset,i=t.placement,r=t.offsets,o=r.popper,a=r.reference,s=i.split("-")[0],l=void 0;return l=R(+n)?[+n,0]:J(n,o,a,s),"left"===s?(o.top+=l[0],o.left-=l[1]):"right"===s?(o.top+=l[0],o.left+=l[1]):"top"===s?(o.left+=l[0],o.top-=l[1]):"bottom"===s&&(o.left+=l[0],o.top+=l[1]),t.popper=o,t},offset:0},preventOverflow:{order:300,enabled:!0,fn:function(t,e){var n=e.boundariesElement||h(t.instance.popper);t.instance.reference===n&&(n=h(n));var i=Y("transform"),r=t.instance.popper.style,o=r.top,a=r.left,s=r[i];r.top="",r.left="",r[i]="";var l=T(t.instance.popper,t.instance.reference,e.padding,n,t.positionFixed);r.top=o,r.left=a,r[i]=s,e.boundaries=l;var u=e.priority,c=t.offsets.popper,p={primary:function(t){var n=c[t];return c[t]<l[t]&&!e.escapeWithReference&&(n=Math.max(c[t],l[t])),D({},t,n)},secondary:function(t){var n="right"===t?"left":"top",i=c[n];return c[t]>l[t]&&!e.escapeWithReference&&(i=Math.min(c[n],l[t]-("right"===t?c.width:c.height))),D({},n,i)}};return u.forEach(function(t){var e=-1!==["left","top"].indexOf(t)?"primary":"secondary";c=C({},c,p[e](t))}),t.offsets.popper=c,t},priority:["left","right","top","bottom"],padding:5,boundariesElement:"scrollParent"},keepTogether:{order:400,enabled:!0,fn:function(t){var e=t.offsets,n=e.popper,i=e.reference,r=t.placement.split("-")[0],o=Math.floor,a=-1!==["top","bottom"].indexOf(r),s=a?"right":"bottom",l=a?"left":"top",u=a?"width":"height";return n[s]<o(i[l])&&(t.offsets.popper[l]=o(i[l])-n[u]),n[l]>o(i[s])&&(t.offsets.popper[l]=o(i[s])),t}},arrow:{order:500,enabled:!0,fn:function(t,e){var n;if(!H(t.instance.modifiers,"arrow","keepTogether"))return t;var i=e.element;if("string"==typeof i){if(!(i=t.instance.popper.querySelector(i)))return t}else if(!t.instance.popper.contains(i))return console.warn("WARNING: `arrow.element` must be child of its popper element!"),t;var r=t.placement.split("-")[0],o=t.offsets,a=o.popper,s=o.reference,l=-1!==["left","right"].indexOf(r),c=l?"height":"width",p=l?"Top":"Left",d=p.toLowerCase(),A=l?"left":"top",f=l?"bottom":"right",h=N(i)[c];s[f]-h<a[d]&&(t.offsets.popper[d]-=a[d]-(s[f]-h)),s[d]+h>a[f]&&(t.offsets.popper[d]+=s[d]+h-a[f]),t.offsets.popper=E(t.offsets.popper);var m=s[d]+s[c]/2-h/2,v=u(t.instance.popper),g=parseFloat(v["margin"+p],10),y=parseFloat(v["border"+p+"Width"],10),b=m-t.offsets.popper[d]-g-y;return b=Math.max(Math.min(a[c]-h,b),0),t.arrowElement=i,t.offsets.arrow=(D(n={},d,Math.round(b)),D(n,A,""),n),t},element:"[x-arrow]"},flip:{order:600,enabled:!0,fn:function(t,e){if($(t.instance.modifiers,"inner"))return t;if(t.flipped&&t.placement===t.originalPlacement)return t;var n=T(t.instance.popper,t.instance.reference,e.padding,e.boundariesElement,t.positionFixed),i=t.placement.split("-")[0],r=I(i),o=t.placement.split("-")[1]||"",a=[];switch(e.behavior){case Z.FLIP:a=[i,r];break;case Z.CLOCKWISE:a=z(i);break;case Z.COUNTERCLOCKWISE:a=z(i,!0);break;default:a=e.behavior}return a.forEach(function(s,l){if(i!==s||a.length===l+1)return t;i=t.placement.split("-")[0],r=I(i);var u=t.offsets.popper,c=t.offsets.reference,p=Math.floor,d="left"===i&&p(u.right)>p(c.left)||"right"===i&&p(u.left)<p(c.right)||"top"===i&&p(u.bottom)>p(c.top)||"bottom"===i&&p(u.top)<p(c.bottom),A=p(u.left)<p(n.left),f=p(u.right)>p(n.right),h=p(u.top)<p(n.top),m=p(u.bottom)>p(n.bottom),v="left"===i&&A||"right"===i&&f||"top"===i&&h||"bottom"===i&&m,g=-1!==["top","bottom"].indexOf(i),y=!!e.flipVariations&&(g&&"start"===o&&A||g&&"end"===o&&f||!g&&"start"===o&&h||!g&&"end"===o&&m);(d||v||y)&&(t.flipped=!0,(d||v)&&(i=a[l+1]),y&&(o=function(t){return"end"===t?"start":"start"===t?"end":t}(o)),t.placement=i+(o?"-"+o:""),t.offsets.popper=C({},t.offsets.popper,L(t.instance.popper,t.offsets.reference,t.placement)),t=j(t.instance.modifiers,t,"flip"))}),t},behavior:"flip",padding:5,boundariesElement:"viewport"},inner:{order:700,enabled:!1,fn:function(t){var e=t.placement,n=e.split("-")[0],i=t.offsets,r=i.popper,o=i.reference,a=-1!==["left","right"].indexOf(n),s=-1===["top","left"].indexOf(n);return r[a?"left":"top"]=o[n]-(s?r[a?"width":"height"]:0),t.placement=I(e),t.offsets.popper=E(r),t}},hide:{order:800,enabled:!0,fn:function(t){if(!H(t.instance.modifiers,"hide","preventOverflow"))return t;var e=t.offsets.reference,n=P(t.instance.modifiers,function(t){return"preventOverflow"===t.name}).boundaries;if(e.bottom<n.top||e.left>n.right||e.top>n.bottom||e.right<n.left){if(!0===t.hide)return t;t.hide=!0,t.attributes["x-out-of-boundaries"]=""}else{if(!1===t.hide)return t;t.hide=!1,t.attributes["x-out-of-boundaries"]=!1}return t}},computeStyle:{order:850,enabled:!0,fn:function(t,e){var n=e.x,i=e.y,r=t.offsets.popper,o=P(t.instance.modifiers,function(t){return"applyStyle"===t.name}).gpuAcceleration;void 0!==o&&console.warn("WARNING: `gpuAcceleration` option moved to `computeStyle` modifier and will not be supported in future versions of Popper.js!");var a=void 0!==o?o:e.gpuAcceleration,s=k(h(t.instance.popper)),l={position:r.position},u={left:Math.floor(r.left),top:Math.round(r.top),bottom:Math.round(r.bottom),right:Math.floor(r.right)},c="bottom"===n?"top":"bottom",p="right"===i?"left":"right",d=Y("transform"),A=void 0,f=void 0;if(f="bottom"===c?-s.height+u.bottom:u.top,A="right"===p?-s.width+u.right:u.left,a&&d)l[d]="translate3d("+A+"px, "+f+"px, 0)",l[c]=0,l[p]=0,l.willChange="transform";else{var m="bottom"===c?-1:1,v="right"===p?-1:1;l[c]=f*m,l[p]=A*v,l.willChange=c+", "+p}var g={"x-placement":t.placement};return t.attributes=C({},g,t.attributes),t.styles=C({},l,t.styles),t.arrowStyles=C({},t.offsets.arrow,t.arrowStyles),t},gpuAcceleration:!0,x:"bottom",y:"right"},applyStyle:{order:900,enabled:!0,fn:function(t){var e,n;return G(t.instance.popper,t.styles),e=t.instance.popper,n=t.attributes,Object.keys(n).forEach(function(t){!1!==n[t]?e.setAttribute(t,n[t]):e.removeAttribute(t)}),t.arrowElement&&Object.keys(t.arrowStyles).length&&G(t.arrowElement,t.arrowStyles),t},onLoad:function(t,e,n,i,r){var o=B(r,e,t,n.positionFixed),a=M(n.placement,o,e,t,n.modifiers.flip.boundariesElement,n.modifiers.flip.padding);return e.setAttribute("x-placement",a),G(e,{position:n.positionFixed?"fixed":"absolute"}),n},gpuAcceleration:void 0}}},X=function(){function t(e,n){var i=this,r=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{};_(this,t),this.scheduleUpdate=function(){return requestAnimationFrame(i.update)},this.update=s(this.update.bind(this)),this.options=C({},t.Defaults,r),this.state={isDestroyed:!1,isCreated:!1,scrollParents:[]},this.reference=e&&e.jquery?e[0]:e,this.popper=n&&n.jquery?n[0]:n,this.options.modifiers={},Object.keys(C({},t.Defaults.modifiers,r.modifiers)).forEach(function(e){i.options.modifiers[e]=C({},t.Defaults.modifiers[e]||{},r.modifiers?r.modifiers[e]:{})}),this.modifiers=Object.keys(this.options.modifiers).map(function(t){return C({name:t},i.options.modifiers[t])}).sort(function(t,e){return t.order-e.order}),this.modifiers.forEach(function(t){t.enabled&&l(t.onLoad)&&t.onLoad(i.reference,i.popper,i.options,t,i.state)}),this.update();var o=this.options.eventsEnabled;o&&this.enableEventListeners(),this.state.eventsEnabled=o}return x(t,[{key:"update",value:function(){return function(){if(!this.state.isDestroyed){var t={instance:this,styles:{},arrowStyles:{},attributes:{},flipped:!1,offsets:{}};t.offsets.reference=B(this.state,this.popper,this.reference,this.options.positionFixed),t.placement=M(this.options.placement,t.offsets.reference,this.popper,this.reference,this.options.modifiers.flip.boundariesElement,this.options.modifiers.flip.padding),t.originalPlacement=t.placement,t.positionFixed=this.options.positionFixed,t.offsets.popper=L(this.popper,t.offsets.reference,t.placement),t.offsets.popper.position=this.options.positionFixed?"fixed":"absolute",t=j(this.modifiers,t),this.state.isCreated?this.options.onUpdate(t):(this.state.isCreated=!0,this.options.onCreate(t))}}.call(this)}},{key:"destroy",value:function(){return function(){return this.state.isDestroyed=!0,$(this.modifiers,"applyStyle")&&(this.popper.removeAttribute("x-placement"),this.popper.style.position="",this.popper.style.top="",this.popper.style.left="",this.popper.style.right="",this.popper.style.bottom="",this.popper.style.willChange="",this.popper.style[Y("transform")]=""),this.disableEventListeners(),this.options.removeOnDestroy&&this.popper.parentNode.removeChild(this.popper),this}.call(this)}},{key:"enableEventListeners",value:function(){return function(){this.state.eventsEnabled||(this.state=Q(this.reference,this.options,this.state,this.scheduleUpdate))}.call(this)}},{key:"disableEventListeners",value:function(){return F.call(this)}}]),t}();X.Utils=("undefined"!=typeof window?window:t).PopperUtils,X.placements=U,X.Defaults=q;var K=function(){};function tt(t){return"string"==typeof t&&(t=t.split(" ")),t}function et(t,e){var n=tt(e),i=void 0;i=t.className instanceof K?tt(t.className.baseVal):tt(t.className),n.forEach(function(t){-1===i.indexOf(t)&&i.push(t)}),t instanceof SVGElement?t.setAttribute("class",i.join(" ")):t.className=i.join(" ")}function nt(t,e){var n=tt(e),i=void 0;i=t.className instanceof K?tt(t.className.baseVal):tt(t.className),n.forEach(function(t){var e=i.indexOf(t);-1!==e&&i.splice(e,1)}),t instanceof SVGElement?t.setAttribute("class",i.join(" ")):t.className=i.join(" ")}"undefined"!=typeof window&&(K=window.SVGAnimatedString);var it=!1;if("undefined"!=typeof window){it=!1;try{var rt=Object.defineProperty({},"passive",{get:function(){it=!0}});window.addEventListener("test",null,rt)}catch(t){}}var ot="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},at=function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")},st=function(){function t(t,e){for(var n=0;n<e.length;n++){var i=e[n];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(t,i.key,i)}}return function(e,n,i){return n&&t(e.prototype,n),i&&t(e,i),e}}(),lt=Object.assign||function(t){for(var e=1;e<arguments.length;e++){var n=arguments[e];for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(t[i]=n[i])}return t},ut={container:!1,delay:0,html:!1,placement:"top",title:"",template:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",offset:0},ct=[],pt=function(){function t(e,n){at(this,t),dt.call(this),n=lt({},ut,n),e.jquery&&(e=e[0]),this.reference=e,this.options=n,this._isOpen=!1,this._init()}return st(t,[{key:"setClasses",value:function(t){this._classes=t}},{key:"setContent",value:function(t){this.options.title=t,this._tooltipNode&&this._setContent(t,this.options)}},{key:"setOptions",value:function(t){var e=!1,n=t&&t.classes||wt.options.defaultClass;this._classes!==n&&(this.setClasses(n),e=!0),t=mt(t);var i=!1,r=!1;for(var o in this.options.offset===t.offset&&this.options.placement===t.placement||(i=!0),(this.options.template!==t.template||this.options.trigger!==t.trigger||this.options.container!==t.container||e)&&(r=!0),t)this.options[o]=t[o];if(this._tooltipNode)if(r){var a=this._isOpen;this.dispose(),this._init(),a&&this.show()}else i&&this.popperInstance.update()}},{key:"_init",value:function(){var t="string"==typeof this.options.trigger?this.options.trigger.split(" ").filter(function(t){return-1!==["click","hover","focus"].indexOf(t)}):[];this._isDisposed=!1,this._enableDocumentTouch=-1===t.indexOf("manual"),this._setEventListeners(this.reference,t,this.options)}},{key:"_create",value:function(t,e){var n=window.document.createElement("div");n.innerHTML=e.trim();var i=n.childNodes[0];return i.id="tooltip_"+Math.random().toString(36).substr(2,10),i.setAttribute("aria-hidden","true"),this.options.autoHide&&-1!==this.options.trigger.indexOf("hover")&&(i.addEventListener("mouseenter",this.hide),i.addEventListener("click",this.hide)),i}},{key:"_setContent",value:function(t,e){var n=this;this.asyncContent=!1,this._applyContent(t,e).then(function(){n.popperInstance.update()})}},{key:"_applyContent",value:function(t,e){var n=this;return new Promise(function(i,r){var o=e.html,a=n._tooltipNode;if(a){var s=a.querySelector(n.options.innerSelector);if(1===t.nodeType){if(o){for(;s.firstChild;)s.removeChild(s.firstChild);s.appendChild(t)}}else{if("function"==typeof t){var l=t();return void(l&&"function"==typeof l.then?(n.asyncContent=!0,e.loadingClass&&et(a,e.loadingClass),e.loadingContent&&n._applyContent(e.loadingContent,e),l.then(function(t){return e.loadingClass&&nt(a,e.loadingClass),n._applyContent(t,e)}).then(i).catch(r)):n._applyContent(l,e).then(i).catch(r))}o?s.innerHTML=t:s.innerText=t}i()}})}},{key:"_show",value:function(t,e){if(e&&"string"==typeof e.container&&!document.querySelector(e.container))return;clearTimeout(this._disposeTimer),delete(e=Object.assign({},e)).offset;var n=!0;this._tooltipNode&&(et(this._tooltipNode,this._classes),n=!1);var i=this._ensureShown(t,e);return n&&this._tooltipNode&&et(this._tooltipNode,this._classes),et(t,["v-tooltip-open"]),i}},{key:"_ensureShown",value:function(t,e){var n=this;if(this._isOpen)return this;if(this._isOpen=!0,ct.push(this),this._tooltipNode)return this._tooltipNode.style.display="",this._tooltipNode.setAttribute("aria-hidden","false"),this.popperInstance.enableEventListeners(),this.popperInstance.update(),this.asyncContent&&this._setContent(e.title,e),this;var i=t.getAttribute("title")||e.title;if(!i)return this;var r=this._create(t,e.template);this._tooltipNode=r,this._setContent(i,e),t.setAttribute("aria-describedby",r.id);var o=this._findContainer(e.container,t);this._append(r,o);var a=lt({},e.popperOptions,{placement:e.placement});return a.modifiers=lt({},a.modifiers,{arrow:{element:this.options.arrowSelector}}),e.boundariesElement&&(a.modifiers.preventOverflow={boundariesElement:e.boundariesElement}),this.popperInstance=new X(t,r,a),requestAnimationFrame(function(){!n._isDisposed&&n.popperInstance?(n.popperInstance.update(),requestAnimationFrame(function(){n._isDisposed?n.dispose():n._isOpen&&r.setAttribute("aria-hidden","false")})):n.dispose()}),this}},{key:"_noLongerOpen",value:function(){var t=ct.indexOf(this);-1!==t&&ct.splice(t,1)}},{key:"_hide",value:function(){var t=this;if(!this._isOpen)return this;this._isOpen=!1,this._noLongerOpen(),this._tooltipNode.style.display="none",this._tooltipNode.setAttribute("aria-hidden","true"),this.popperInstance.disableEventListeners(),clearTimeout(this._disposeTimer);var e=wt.options.disposeTimeout;return null!==e&&(this._disposeTimer=setTimeout(function(){t._tooltipNode&&(t._tooltipNode.removeEventListener("mouseenter",t.hide),t._tooltipNode.removeEventListener("click",t.hide),t._tooltipNode.parentNode.removeChild(t._tooltipNode),t._tooltipNode=null)},e)),nt(this.reference,["v-tooltip-open"]),this}},{key:"_dispose",value:function(){var t=this;return this._isDisposed=!0,this._events.forEach(function(e){var n=e.func,i=e.event;t.reference.removeEventListener(i,n)}),this._events=[],this._tooltipNode?(this._hide(),this._tooltipNode.removeEventListener("mouseenter",this.hide),this._tooltipNode.removeEventListener("click",this.hide),this.popperInstance.destroy(),this.popperInstance.options.removeOnDestroy||(this._tooltipNode.parentNode.removeChild(this._tooltipNode),this._tooltipNode=null)):this._noLongerOpen(),this}},{key:"_findContainer",value:function(t,e){return"string"==typeof t?t=window.document.querySelector(t):!1===t&&(t=e.parentNode),t}},{key:"_append",value:function(t,e){e.appendChild(t)}},{key:"_setEventListeners",value:function(t,e,n){var i=this,r=[],o=[];e.forEach(function(t){switch(t){case"hover":r.push("mouseenter"),o.push("mouseleave"),i.options.hideOnTargetClick&&o.push("click");break;case"focus":r.push("focus"),o.push("blur"),i.options.hideOnTargetClick&&o.push("click");break;case"click":r.push("click"),o.push("click")}}),r.forEach(function(e){var r=function(e){!0!==i._isOpen&&(e.usedByTooltip=!0,i._scheduleShow(t,n.delay,n,e))};i._events.push({event:e,func:r}),t.addEventListener(e,r)}),o.forEach(function(e){var r=function(e){!0!==e.usedByTooltip&&i._scheduleHide(t,n.delay,n,e)};i._events.push({event:e,func:r}),t.addEventListener(e,r)})}},{key:"_onDocumentTouch",value:function(t){this._enableDocumentTouch&&this._scheduleHide(this.reference,this.options.delay,this.options,t)}},{key:"_scheduleShow",value:function(t,e,n){var i=this,r=e&&e.show||e||0;clearTimeout(this._scheduleTimer),this._scheduleTimer=window.setTimeout(function(){return i._show(t,n)},r)}},{key:"_scheduleHide",value:function(t,e,n,i){var r=this,o=e&&e.hide||e||0;clearTimeout(this._scheduleTimer),this._scheduleTimer=window.setTimeout(function(){if(!1!==r._isOpen&&document.body.contains(r._tooltipNode)){if("mouseleave"===i.type)if(r._setTooltipNodeEvent(i,t,e,n))return;r._hide(t,n)}},o)}}]),t}(),dt=function(){var t=this;this.show=function(){t._show(t.reference,t.options)},this.hide=function(){t._hide()},this.dispose=function(){t._dispose()},this.toggle=function(){return t._isOpen?t.hide():t.show()},this._events=[],this._setTooltipNodeEvent=function(e,n,i,r){var o=e.relatedreference||e.toElement||e.relatedTarget;return!!t._tooltipNode.contains(o)&&(t._tooltipNode.addEventListener(e.type,function i(o){var a=o.relatedreference||o.toElement||o.relatedTarget;t._tooltipNode.removeEventListener(e.type,i),n.contains(a)||t._scheduleHide(n,r.delay,r,o)}),!0)}};"undefined"!=typeof document&&document.addEventListener("touchstart",function(t){for(var e=0;e<ct.length;e++)ct[e]._onDocumentTouch(t)},!it||{passive:!0,capture:!0});var At={enabled:!0},ft=["top","top-start","top-end","right","right-start","right-end","bottom","bottom-start","bottom-end","left","left-start","left-end"],ht={defaultPlacement:"top",defaultClass:"vue-tooltip-theme",defaultTargetClass:"has-tooltip",defaultHtml:!0,defaultTemplate:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',defaultArrowSelector:".tooltip-arrow, .tooltip__arrow",defaultInnerSelector:".tooltip-inner, .tooltip__inner",defaultDelay:0,defaultTrigger:"hover focus",defaultOffset:0,defaultContainer:"body",defaultBoundariesElement:void 0,defaultPopperOptions:{},defaultLoadingClass:"tooltip-loading",defaultLoadingContent:"...",autoHide:!0,defaultHideOnTargetClick:!0,disposeTimeout:5e3,popover:{defaultPlacement:"bottom",defaultClass:"vue-popover-theme",defaultBaseClass:"tooltip popover",defaultWrapperClass:"wrapper",defaultInnerClass:"tooltip-inner popover-inner",defaultArrowClass:"tooltip-arrow popover-arrow",defaultDelay:0,defaultTrigger:"click",defaultOffset:0,defaultContainer:"body",defaultBoundariesElement:void 0,defaultPopperOptions:{},defaultAutoHide:!0,defaultHandleResize:!0}};function mt(t){var e={placement:void 0!==t.placement?t.placement:wt.options.defaultPlacement,delay:void 0!==t.delay?t.delay:wt.options.defaultDelay,html:void 0!==t.html?t.html:wt.options.defaultHtml,template:void 0!==t.template?t.template:wt.options.defaultTemplate,arrowSelector:void 0!==t.arrowSelector?t.arrowSelector:wt.options.defaultArrowSelector,innerSelector:void 0!==t.innerSelector?t.innerSelector:wt.options.defaultInnerSelector,trigger:void 0!==t.trigger?t.trigger:wt.options.defaultTrigger,offset:void 0!==t.offset?t.offset:wt.options.defaultOffset,container:void 0!==t.container?t.container:wt.options.defaultContainer,boundariesElement:void 0!==t.boundariesElement?t.boundariesElement:wt.options.defaultBoundariesElement,autoHide:void 0!==t.autoHide?t.autoHide:wt.options.autoHide,hideOnTargetClick:void 0!==t.hideOnTargetClick?t.hideOnTargetClick:wt.options.defaultHideOnTargetClick,loadingClass:void 0!==t.loadingClass?t.loadingClass:wt.options.defaultLoadingClass,loadingContent:void 0!==t.loadingContent?t.loadingContent:wt.options.defaultLoadingContent,popperOptions:lt({},void 0!==t.popperOptions?t.popperOptions:wt.options.defaultPopperOptions)};if(e.offset){var n=ot(e.offset),i=e.offset;("number"===n||"string"===n&&-1===i.indexOf(","))&&(i="0, "+i),e.popperOptions.modifiers||(e.popperOptions.modifiers={}),e.popperOptions.modifiers.offset={offset:i}}return e.trigger&&-1!==e.trigger.indexOf("click")&&(e.hideOnTargetClick=!1),e}function vt(t,e){for(var n=t.placement,i=0;i<ft.length;i++){var r=ft[i];e[r]&&(n=r)}return n}function gt(t){var e=void 0===t?"undefined":ot(t);return"string"===e?t:!(!t||"object"!==e)&&t.content}function yt(t){t._tooltip&&(t._tooltip.dispose(),delete t._tooltip,delete t._tooltipOldShow),t._tooltipTargetClasses&&(nt(t,t._tooltipTargetClasses),delete t._tooltipTargetClasses)}function bt(t,e){var n=e.value,i=(e.oldValue,e.modifiers),r=gt(n);if(r&&At.enabled){var o=void 0;t._tooltip?((o=t._tooltip).setContent(r),o.setOptions(lt({},n,{placement:vt(n,i)}))):o=function(t,e){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{},i=gt(e),r=void 0!==e.classes?e.classes:wt.options.defaultClass,o=lt({title:i},mt(lt({},e,{placement:vt(e,n)}))),a=t._tooltip=new pt(t,o);a.setClasses(r),a._vueEl=t;var s=void 0!==e.targetClasses?e.targetClasses:wt.options.defaultTargetClass;return t._tooltipTargetClasses=s,et(t,s),a}(t,n,i),void 0!==n.show&&n.show!==t._tooltipOldShow&&(t._tooltipOldShow=n.show,n.show?o.show():o.hide())}else yt(t)}var wt={options:ht,bind:bt,update:bt,unbind:function(t){yt(t)}};function _t(t){t.addEventListener("click",Dt),t.addEventListener("touchstart",Ct,!!it&&{passive:!0})}function xt(t){t.removeEventListener("click",Dt),t.removeEventListener("touchstart",Ct),t.removeEventListener("touchend",Et),t.removeEventListener("touchcancel",kt)}function Dt(t){var e=t.currentTarget;t.closePopover=!e.$_vclosepopover_touch,t.closeAllPopover=e.$_closePopoverModifiers&&!!e.$_closePopoverModifiers.all}function Ct(t){if(1===t.changedTouches.length){var e=t.currentTarget;e.$_vclosepopover_touch=!0;var n=t.changedTouches[0];e.$_vclosepopover_touchPoint=n,e.addEventListener("touchend",Et),e.addEventListener("touchcancel",kt)}}function Et(t){var e=t.currentTarget;if(e.$_vclosepopover_touch=!1,1===t.changedTouches.length){var n=t.changedTouches[0],i=e.$_vclosepopover_touchPoint;t.closePopover=Math.abs(n.screenY-i.screenY)<20&&Math.abs(n.screenX-i.screenX)<20,t.closeAllPopover=e.$_closePopoverModifiers&&!!e.$_closePopoverModifiers.all}}function kt(t){t.currentTarget.$_vclosepopover_touch=!1}var St={bind:function(t,e){var n=e.value,i=e.modifiers;t.$_closePopoverModifiers=i,(void 0===n||n)&&_t(t)},update:function(t,e){var n=e.value,i=e.oldValue,r=e.modifiers;t.$_closePopoverModifiers=r,n!==i&&(void 0===n||n?_t(t):xt(t))},unbind:function(t){xt(t)}};var Ot=void 0;function Tt(){Tt.init||(Tt.init=!0,Ot=-1!==function(){var t=window.navigator.userAgent,e=t.indexOf("MSIE ");if(e>0)return parseInt(t.substring(e+5,t.indexOf(".",e)),10);if(t.indexOf("Trident/")>0){var n=t.indexOf("rv:");return parseInt(t.substring(n+3,t.indexOf(".",n)),10)}var i=t.indexOf("Edge/");return i>0?parseInt(t.substring(i+5,t.indexOf(".",i)),10):-1}())}var Mt={render:function(){var t=this.$createElement;return(this._self._c||t)("div",{staticClass:"resize-observer",attrs:{tabindex:"-1"}})},staticRenderFns:[],_scopeId:"data-v-b329ee4c",name:"resize-observer",methods:{notify:function(){this.$emit("notify")},addResizeHandlers:function(){this._resizeObject.contentDocument.defaultView.addEventListener("resize",this.notify),this._w===this.$el.offsetWidth&&this._h===this.$el.offsetHeight||this.notify()},removeResizeHandlers:function(){this._resizeObject&&this._resizeObject.onload&&(!Ot&&this._resizeObject.contentDocument&&this._resizeObject.contentDocument.defaultView.removeEventListener("resize",this.notify),delete this._resizeObject.onload)}},mounted:function(){var t=this;Tt(),this.$nextTick(function(){t._w=t.$el.offsetWidth,t._h=t.$el.offsetHeight});var e=document.createElement("object");this._resizeObject=e,e.setAttribute("style","display: block; position: absolute; top: 0; left: 0; height: 100%; width: 100%; overflow: hidden; pointer-events: none; z-index: -1;"),e.setAttribute("aria-hidden","true"),e.setAttribute("tabindex",-1),e.onload=this.addResizeHandlers,e.type="text/html",Ot&&this.$el.appendChild(e),e.data="about:blank",Ot||this.$el.appendChild(e)},beforeDestroy:function(){this.removeResizeHandlers()}};var Bt={version:"0.4.4",install:function(t){t.component("resize-observer",Mt)}},Nt=null;function It(t){var e=wt.options.popover[t];return void 0===e?wt.options[t]:e}"undefined"!=typeof window?Nt=window.Vue:void 0!==t&&(Nt=t.Vue),Nt&&Nt.use(Bt);var Lt=!1;"undefined"!=typeof window&&"undefined"!=typeof navigator&&(Lt=/iPad|iPhone|iPod/.test(navigator.userAgent)&&!window.MSStream);var Pt=[],jt=function(){};"undefined"!=typeof window&&(jt=window.Element);var $t={render:function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"v-popover",class:t.cssClass},[n("span",{ref:"trigger",staticClass:"trigger",staticStyle:{display:"inline-block"},attrs:{"aria-describedby":t.popoverId,tabindex:-1!==t.trigger.indexOf("focus")?0:-1}},[t._t("default")],2),t._v(" "),n("div",{ref:"popover",class:[t.popoverBaseClass,t.popoverClass,t.cssClass],style:{visibility:t.isOpen?"visible":"hidden"},attrs:{id:t.popoverId,"aria-hidden":t.isOpen?"false":"true"}},[n("div",{class:t.popoverWrapperClass},[n("div",{ref:"inner",class:t.popoverInnerClass,staticStyle:{position:"relative"}},[n("div",[t._t("popover")],2),t._v(" "),t.handleResize?n("ResizeObserver",{on:{notify:t.$_handleResize}}):t._e()],1),t._v(" "),n("div",{ref:"arrow",class:t.popoverArrowClass})])])])},staticRenderFns:[],name:"VPopover",components:{ResizeObserver:Mt},props:{open:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},placement:{type:String,default:function(){return It("defaultPlacement")}},delay:{type:[String,Number,Object],default:function(){return It("defaultDelay")}},offset:{type:[String,Number],default:function(){return It("defaultOffset")}},trigger:{type:String,default:function(){return It("defaultTrigger")}},container:{type:[String,Object,jt,Boolean],default:function(){return It("defaultContainer")}},boundariesElement:{type:[String,jt],default:function(){return It("defaultBoundariesElement")}},popperOptions:{type:Object,default:function(){return It("defaultPopperOptions")}},popoverClass:{type:[String,Array],default:function(){return It("defaultClass")}},popoverBaseClass:{type:[String,Array],default:function(){return wt.options.popover.defaultBaseClass}},popoverInnerClass:{type:[String,Array],default:function(){return wt.options.popover.defaultInnerClass}},popoverWrapperClass:{type:[String,Array],default:function(){return wt.options.popover.defaultWrapperClass}},popoverArrowClass:{type:[String,Array],default:function(){return wt.options.popover.defaultArrowClass}},autoHide:{type:Boolean,default:function(){return wt.options.popover.defaultAutoHide}},handleResize:{type:Boolean,default:function(){return wt.options.popover.defaultHandleResize}},openGroup:{type:String,default:null}},data:function(){return{isOpen:!1,id:Math.random().toString(36).substr(2,10)}},computed:{cssClass:function(){return{open:this.isOpen}},popoverId:function(){return"popover_"+this.id}},watch:{open:function(t){t?this.show():this.hide()},disabled:function(t,e){t!==e&&(t?this.hide():this.open&&this.show())},container:function(t){if(this.isOpen&&this.popperInstance){var e=this.$refs.popover,n=this.$refs.trigger,i=this.$_findContainer(this.container,n);if(!i)return void console.warn("No container for popover",this);i.appendChild(e),this.popperInstance.scheduleUpdate()}},trigger:function(t){this.$_removeEventListeners(),this.$_addEventListeners()},placement:function(t){var e=this;this.$_updatePopper(function(){e.popperInstance.options.placement=t})},offset:"$_restartPopper",boundariesElement:"$_restartPopper",popperOptions:{handler:"$_restartPopper",deep:!0}},created:function(){this.$_isDisposed=!1,this.$_mounted=!1,this.$_events=[],this.$_preventOpen=!1},mounted:function(){var t=this.$refs.popover;t.parentNode&&t.parentNode.removeChild(t),this.$_init(),this.open&&this.show()},beforeDestroy:function(){this.dispose()},methods:{show:function(){var t=this,e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},n=e.event,i=(e.skipDelay,e.force);!(void 0!==i&&i)&&this.disabled||(this.$_scheduleShow(n),this.$emit("show")),this.$emit("update:open",!0),this.$_beingShowed=!0,requestAnimationFrame(function(){t.$_beingShowed=!1})},hide:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},e=t.event;t.skipDelay;this.$_scheduleHide(e),this.$emit("hide"),this.$emit("update:open",!1)},dispose:function(){if(this.$_isDisposed=!0,this.$_removeEventListeners(),this.hide({skipDelay:!0}),this.popperInstance&&(this.popperInstance.destroy(),!this.popperInstance.options.removeOnDestroy)){var t=this.$refs.popover;t.parentNode&&t.parentNode.removeChild(t)}this.$_mounted=!1,this.popperInstance=null,this.isOpen=!1,this.$emit("dispose")},$_init:function(){-1===this.trigger.indexOf("manual")&&this.$_addEventListeners()},$_show:function(){var t=this,e=this.$refs.trigger,n=this.$refs.popover;if(clearTimeout(this.$_disposeTimer),!this.isOpen){if(this.popperInstance&&(this.isOpen=!0,this.popperInstance.enableEventListeners(),this.popperInstance.scheduleUpdate()),!this.$_mounted){var i=this.$_findContainer(this.container,e);if(!i)return void console.warn("No container for popover",this);i.appendChild(n),this.$_mounted=!0}if(!this.popperInstance){var r=lt({},this.popperOptions,{placement:this.placement});if(r.modifiers=lt({},r.modifiers,{arrow:lt({},r.modifiers&&r.modifiers.arrow,{element:this.$refs.arrow})}),this.offset){var o=this.$_getOffset();r.modifiers.offset=lt({},r.modifiers&&r.modifiers.offset,{offset:o})}this.boundariesElement&&(r.modifiers.preventOverflow=lt({},r.modifiers&&r.modifiers.preventOverflow,{boundariesElement:this.boundariesElement})),this.popperInstance=new X(e,n,r),requestAnimationFrame(function(){!t.$_isDisposed&&t.popperInstance?(t.popperInstance.scheduleUpdate(),requestAnimationFrame(function(){t.$_isDisposed?t.dispose():t.isOpen=!0})):t.dispose()})}var a=this.openGroup;if(a)for(var s=void 0,l=0;l<Pt.length;l++)(s=Pt[l]).openGroup!==a&&(s.hide(),s.$emit("close-group"));Pt.push(this),this.$emit("apply-show")}},$_hide:function(){var t=this;if(this.isOpen){var e=Pt.indexOf(this);-1!==e&&Pt.splice(e,1),this.isOpen=!1,this.popperInstance&&this.popperInstance.disableEventListeners(),clearTimeout(this.$_disposeTimer);var n=wt.options.popover.disposeTimeout||wt.options.disposeTimeout;null!==n&&(this.$_disposeTimer=setTimeout(function(){var e=t.$refs.popover;e&&(e.parentNode&&e.parentNode.removeChild(e),t.$_mounted=!1)},n)),this.$emit("apply-hide")}},$_findContainer:function(t,e){return"string"==typeof t?t=window.document.querySelector(t):!1===t&&(t=e.parentNode),t},$_getOffset:function(){var t=ot(this.offset),e=this.offset;return("number"===t||"string"===t&&-1===e.indexOf(","))&&(e="0, "+e),e},$_addEventListeners:function(){var t=this,e=this.$refs.trigger,n=[],i=[];("string"==typeof this.trigger?this.trigger.split(" ").filter(function(t){return-1!==["click","hover","focus"].indexOf(t)}):[]).forEach(function(t){switch(t){case"hover":n.push("mouseenter"),i.push("mouseleave");break;case"focus":n.push("focus"),i.push("blur");break;case"click":n.push("click"),i.push("click")}}),n.forEach(function(n){var i=function(e){t.isOpen||(e.usedByTooltip=!0,!t.$_preventOpen&&t.show({event:e}))};t.$_events.push({event:n,func:i}),e.addEventListener(n,i)}),i.forEach(function(n){var i=function(e){e.usedByTooltip||t.hide({event:e})};t.$_events.push({event:n,func:i}),e.addEventListener(n,i)})},$_scheduleShow:function(){var t=arguments.length>1&&void 0!==arguments[1]&&arguments[1];if(clearTimeout(this.$_scheduleTimer),t)this.$_show();else{var e=parseInt(this.delay&&this.delay.show||this.delay||0);this.$_scheduleTimer=setTimeout(this.$_show.bind(this),e)}},$_scheduleHide:function(){var t=this,e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null,n=arguments.length>1&&void 0!==arguments[1]&&arguments[1];if(clearTimeout(this.$_scheduleTimer),n)this.$_hide();else{var i=parseInt(this.delay&&this.delay.hide||this.delay||0);this.$_scheduleTimer=setTimeout(function(){if(t.isOpen){if(e&&"mouseleave"===e.type)if(t.$_setTooltipNodeEvent(e))return;t.$_hide()}},i)}},$_setTooltipNodeEvent:function(t){var e=this,n=this.$refs.trigger,i=this.$refs.popover,r=t.relatedreference||t.toElement||t.relatedTarget;return!!i.contains(r)&&(i.addEventListener(t.type,function r(o){var a=o.relatedreference||o.toElement||o.relatedTarget;i.removeEventListener(t.type,r),n.contains(a)||e.hide({event:o})}),!0)},$_removeEventListeners:function(){var t=this.$refs.trigger;this.$_events.forEach(function(e){var n=e.func,i=e.event;t.removeEventListener(i,n)}),this.$_events=[]},$_updatePopper:function(t){this.popperInstance&&(t(),this.isOpen&&this.popperInstance.scheduleUpdate())},$_restartPopper:function(){if(this.popperInstance){var t=this.isOpen;this.dispose(),this.$_isDisposed=!1,this.$_init(),t&&this.show({skipDelay:!0,force:!0})}},$_handleGlobalClose:function(t){var e=this,n=arguments.length>1&&void 0!==arguments[1]&&arguments[1];this.$_beingShowed||(this.hide({event:t}),t.closePopover?this.$emit("close-directive"):this.$emit("auto-hide"),n&&(this.$_preventOpen=!0,setTimeout(function(){e.$_preventOpen=!1},300)))},$_handleResize:function(){this.isOpen&&this.popperInstance&&(this.popperInstance.scheduleUpdate(),this.$emit("resize"))}}};function Yt(t){var e=arguments.length>1&&void 0!==arguments[1]&&arguments[1];requestAnimationFrame(function(){for(var n=void 0,i=0;i<Pt.length;i++)if((n=Pt[i]).$refs.popover){var r=n.$refs.popover.contains(t.target);(t.closeAllPopover||t.closePopover&&r||n.autoHide&&!r)&&n.$_handleGlobalClose(t,e)}})}"undefined"!=typeof document&&"undefined"!=typeof window&&(Lt?document.addEventListener("touchend",function(t){Yt(t,!0)},!it||{passive:!0,capture:!0}):window.addEventListener("click",function(t){Yt(t)},!0));var Vt="undefined"!=typeof window?window:void 0!==t?t:"undefined"!=typeof self?self:{};var Qt,Ft=(function(t,e){var n=200,i="__lodash_hash_undefined__",r=800,o=16,a=9007199254740991,s="[object Arguments]",l="[object AsyncFunction]",u="[object Function]",c="[object GeneratorFunction]",p="[object Null]",d="[object Object]",A="[object Proxy]",f="[object Undefined]",h=/^\[object .+?Constructor\]$/,m=/^(?:0|[1-9]\d*)$/,v={};v["[object Float32Array]"]=v["[object Float64Array]"]=v["[object Int8Array]"]=v["[object Int16Array]"]=v["[object Int32Array]"]=v["[object Uint8Array]"]=v["[object Uint8ClampedArray]"]=v["[object Uint16Array]"]=v["[object Uint32Array]"]=!0,v[s]=v["[object Array]"]=v["[object ArrayBuffer]"]=v["[object Boolean]"]=v["[object DataView]"]=v["[object Date]"]=v["[object Error]"]=v[u]=v["[object Map]"]=v["[object Number]"]=v[d]=v["[object RegExp]"]=v["[object Set]"]=v["[object String]"]=v["[object WeakMap]"]=!1;var g="object"==typeof Vt&&Vt&&Vt.Object===Object&&Vt,y="object"==typeof self&&self&&self.Object===Object&&self,b=g||y||Function("return this")(),w=e&&!e.nodeType&&e,_=w&&t&&!t.nodeType&&t,x=_&&_.exports===w,D=x&&g.process,C=function(){try{return D&&D.binding&&D.binding("util")}catch(t){}}(),E=C&&C.isTypedArray;function k(t,e){return"__proto__"==e?void 0:t[e]}var S,O,T,M=Array.prototype,B=Function.prototype,N=Object.prototype,I=b["__core-js_shared__"],L=B.toString,P=N.hasOwnProperty,j=(S=/[^.]+$/.exec(I&&I.keys&&I.keys.IE_PROTO||""))?"Symbol(src)_1."+S:"",$=N.toString,Y=L.call(Object),V=RegExp("^"+L.call(P).replace(/[\\^$.*+?()[\]{}|]/g,"\\$&").replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,"$1.*?")+"$"),Q=x?b.Buffer:void 0,F=b.Symbol,R=b.Uint8Array,G=Q?Q.allocUnsafe:void 0,H=(O=Object.getPrototypeOf,T=Object,function(t){return O(T(t))}),U=Object.create,W=N.propertyIsEnumerable,z=M.splice,Z=F?F.toStringTag:void 0,J=function(){try{var t=wt(Object,"defineProperty");return t({},"",{}),t}catch(t){}}(),q=Q?Q.isBuffer:void 0,X=Math.max,K=Date.now,tt=wt(b,"Map"),et=wt(Object,"create"),nt=function(){function t(){}return function(e){if(!Bt(e))return{};if(U)return U(e);t.prototype=e;var n=new t;return t.prototype=void 0,n}}();function it(t){var e=-1,n=null==t?0:t.length;for(this.clear();++e<n;){var i=t[e];this.set(i[0],i[1])}}function rt(t){var e=-1,n=null==t?0:t.length;for(this.clear();++e<n;){var i=t[e];this.set(i[0],i[1])}}function ot(t){var e=-1,n=null==t?0:t.length;for(this.clear();++e<n;){var i=t[e];this.set(i[0],i[1])}}function at(t){var e=this.__data__=new rt(t);this.size=e.size}function st(t,e){var n=kt(t),i=!n&&Et(t),r=!n&&!i&&Ot(t),o=!n&&!i&&!r&&It(t),a=n||i||r||o,s=a?function(t,e){for(var n=-1,i=Array(t);++n<t;)i[n]=e(n);return i}(t.length,String):[],l=s.length;for(var u in t)!e&&!P.call(t,u)||a&&("length"==u||r&&("offset"==u||"parent"==u)||o&&("buffer"==u||"byteLength"==u||"byteOffset"==u)||_t(u,l))||s.push(u);return s}function lt(t,e,n){(void 0===n||Ct(t[e],n))&&(void 0!==n||e in t)||pt(t,e,n)}function ut(t,e,n){var i=t[e];P.call(t,e)&&Ct(i,n)&&(void 0!==n||e in t)||pt(t,e,n)}function ct(t,e){for(var n=t.length;n--;)if(Ct(t[n][0],e))return n;return-1}function pt(t,e,n){"__proto__"==e&&J?J(t,e,{configurable:!0,enumerable:!0,value:n,writable:!0}):t[e]=n}it.prototype.clear=function(){this.__data__=et?et(null):{},this.size=0},it.prototype.delete=function(t){var e=this.has(t)&&delete this.__data__[t];return this.size-=e?1:0,e},it.prototype.get=function(t){var e=this.__data__;if(et){var n=e[t];return n===i?void 0:n}return P.call(e,t)?e[t]:void 0},it.prototype.has=function(t){var e=this.__data__;return et?void 0!==e[t]:P.call(e,t)},it.prototype.set=function(t,e){var n=this.__data__;return this.size+=this.has(t)?0:1,n[t]=et&&void 0===e?i:e,this},rt.prototype.clear=function(){this.__data__=[],this.size=0},rt.prototype.delete=function(t){var e=this.__data__,n=ct(e,t);return!(n<0||(n==e.length-1?e.pop():z.call(e,n,1),--this.size,0))},rt.prototype.get=function(t){var e=this.__data__,n=ct(e,t);return n<0?void 0:e[n][1]},rt.prototype.has=function(t){return ct(this.__data__,t)>-1},rt.prototype.set=function(t,e){var n=this.__data__,i=ct(n,t);return i<0?(++this.size,n.push([t,e])):n[i][1]=e,this},ot.prototype.clear=function(){this.size=0,this.__data__={hash:new it,map:new(tt||rt),string:new it}},ot.prototype.delete=function(t){var e=bt(this,t).delete(t);return this.size-=e?1:0,e},ot.prototype.get=function(t){return bt(this,t).get(t)},ot.prototype.has=function(t){return bt(this,t).has(t)},ot.prototype.set=function(t,e){var n=bt(this,t),i=n.size;return n.set(t,e),this.size+=n.size==i?0:1,this},at.prototype.clear=function(){this.__data__=new rt,this.size=0},at.prototype.delete=function(t){var e=this.__data__,n=e.delete(t);return this.size=e.size,n},at.prototype.get=function(t){return this.__data__.get(t)},at.prototype.has=function(t){return this.__data__.has(t)},at.prototype.set=function(t,e){var i=this.__data__;if(i instanceof rt){var r=i.__data__;if(!tt||r.length<n-1)return r.push([t,e]),this.size=++i.size,this;i=this.__data__=new ot(r)}return i.set(t,e),this.size=i.size,this};var dt,At=function(t,e,n){for(var i=-1,r=Object(t),o=n(t),a=o.length;a--;){var s=o[dt?a:++i];if(!1===e(r[s],s,r))break}return t};function ft(t){return null==t?void 0===t?f:p:Z&&Z in Object(t)?function(t){var e=P.call(t,Z),n=t[Z];try{t[Z]=void 0;var i=!0}catch(t){}var r=$.call(t);i&&(e?t[Z]=n:delete t[Z]);return r}(t):function(t){return $.call(t)}(t)}function ht(t){return Nt(t)&&ft(t)==s}function mt(t){return!(!Bt(t)||(e=t,j&&j in e))&&(Tt(t)?V:h).test(function(t){if(null!=t){try{return L.call(t)}catch(t){}try{return t+""}catch(t){}}return""}(t));var e}function vt(t){if(!Bt(t))return function(t){var e=[];if(null!=t)for(var n in Object(t))e.push(n);return e}(t);var e=xt(t),n=[];for(var i in t)("constructor"!=i||!e&&P.call(t,i))&&n.push(i);return n}function gt(t,e,n,i,r){t!==e&&At(e,function(o,a){if(Bt(o))r||(r=new at),function(t,e,n,i,r,o,a){var s=k(t,n),l=k(e,n),u=a.get(l);if(u)return void lt(t,n,u);var c=o?o(s,l,n+"",t,e,a):void 0,p=void 0===c;if(p){var A=kt(l),f=!A&&Ot(l),h=!A&&!f&&It(l);c=l,A||f||h?kt(s)?c=s:Nt(b=s)&&St(b)?c=function(t,e){var n=-1,i=t.length;e||(e=Array(i));for(;++n<i;)e[n]=t[n];return e}(s):f?(p=!1,c=function(t,e){if(e)return t.slice();var n=t.length,i=G?G(n):new t.constructor(n);return t.copy(i),i}(l,!0)):h?(p=!1,m=l,v= true?(g=m.buffer,y=new g.constructor(g.byteLength),new R(y).set(new R(g)),y):undefined,c=new m.constructor(v,m.byteOffset,m.length)):c=[]:function(t){if(!Nt(t)||ft(t)!=d)return!1;var e=H(t);if(null===e)return!0;var n=P.call(e,"constructor")&&e.constructor;return"function"==typeof n&&n instanceof n&&L.call(n)==Y}(l)||Et(l)?(c=s,Et(s)?c=function(t){return function(t,e,n,i){var r=!n;n||(n={});var o=-1,a=e.length;for(;++o<a;){var s=e[o],l=i?i(n[s],t[s],s,n,t):void 0;void 0===l&&(l=t[s]),r?pt(n,s,l):ut(n,s,l)}return n}(t,Lt(t))}(s):(!Bt(s)||i&&Tt(s))&&(c=function(t){return"function"!=typeof t.constructor||xt(t)?{}:nt(H(t))}(l))):p=!1}var m,v,g,y;var b;p&&(a.set(l,c),r(c,l,i,o,a),a.delete(l));lt(t,n,c)}(t,e,a,n,gt,i,r);else{var s=i?i(k(t,a),o,a+"",t,e,r):void 0;void 0===s&&(s=o),lt(t,a,s)}},Lt)}function yt(t,e){return Dt(function(t,e,n){return e=X(void 0===e?t.length-1:e,0),function(){for(var i=arguments,r=-1,o=X(i.length-e,0),a=Array(o);++r<o;)a[r]=i[e+r];r=-1;for(var s=Array(e+1);++r<e;)s[r]=i[r];return s[e]=n(a),function(t,e,n){switch(n.length){case 0:return t.call(e);case 1:return t.call(e,n[0]);case 2:return t.call(e,n[0],n[1]);case 3:return t.call(e,n[0],n[1],n[2])}return t.apply(e,n)}(t,this,s)}}(t,e,$t),t+"")}function bt(t,e){var n,i,r=t.__data__;return("string"==(i=typeof(n=e))||"number"==i||"symbol"==i||"boolean"==i?"__proto__"!==n:null===n)?r["string"==typeof e?"string":"hash"]:r.map}function wt(t,e){var n=function(t,e){return null==t?void 0:t[e]}(t,e);return mt(n)?n:void 0}function _t(t,e){var n=typeof t;return!!(e=null==e?a:e)&&("number"==n||"symbol"!=n&&m.test(t))&&t>-1&&t%1==0&&t<e}function xt(t){var e=t&&t.constructor;return t===("function"==typeof e&&e.prototype||N)}var Dt=function(t){var e=0,n=0;return function(){var i=K(),a=o-(i-n);if(n=i,a>0){if(++e>=r)return arguments[0]}else e=0;return t.apply(void 0,arguments)}}(J?function(t,e){return J(t,"toString",{configurable:!0,enumerable:!1,value:(n=e,function(){return n}),writable:!0});var n}:$t);function Ct(t,e){return t===e||t!=t&&e!=e}var Et=ht(function(){return arguments}())?ht:function(t){return Nt(t)&&P.call(t,"callee")&&!W.call(t,"callee")},kt=Array.isArray;function St(t){return null!=t&&Mt(t.length)&&!Tt(t)}var Ot=q||function(){return!1};function Tt(t){if(!Bt(t))return!1;var e=ft(t);return e==u||e==c||e==l||e==A}function Mt(t){return"number"==typeof t&&t>-1&&t%1==0&&t<=a}function Bt(t){var e=typeof t;return null!=t&&("object"==e||"function"==e)}function Nt(t){return null!=t&&"object"==typeof t}var It=E?function(t){return function(e){return t(e)}}(E):function(t){return Nt(t)&&Mt(t.length)&&!!v[ft(t)]};function Lt(t){return St(t)?st(t,!0):vt(t)}var Pt,jt=(Pt=function(t,e,n){gt(t,e,n)},yt(function(t,e){var n=-1,i=e.length,r=i>1?e[i-1]:void 0,o=i>2?e[2]:void 0;for(r=Pt.length>3&&"function"==typeof r?(i--,r):void 0,o&&function(t,e,n){if(!Bt(n))return!1;var i=typeof e;return!!("number"==i?St(n)&&_t(e,n.length):"string"==i&&e in n)&&Ct(n[e],t)}(e[0],e[1],o)&&(r=i<3?void 0:r,i=1),t=Object(t);++n<i;){var a=e[n];a&&Pt(t,a,n,r)}return t}));function $t(t){return t}t.exports=jt}(Qt={exports:{}},Qt.exports),Qt.exports);var Rt=wt,Gt={install:function t(e){var n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};if(!t.installed){t.installed=!0;var i={};Ft(i,ht,n),Gt.options=i,wt.options=i,e.directive("tooltip",wt),e.directive("close-popover",St),e.component("v-popover",$t)}},get enabled(){return At.enabled},set enabled(t){At.enabled=t}},Ht=null;"undefined"!=typeof window?Ht=window.Vue:void 0!==t&&(Ht=t.Vue),Ht&&Ht.use(Gt)}).call(this,n(30))},function(t,e,n){window,t.exports=function(t){var e={};function n(i){if(e[i])return e[i].exports;var r=e[i]={i:i,l:!1,exports:{}};return t[i].call(r.exports,r,r.exports,n),r.l=!0,r.exports}return n.m=t,n.c=e,n.d=function(t,e,i){n.o(t,e)||Object.defineProperty(t,e,{configurable:!1,enumerable:!0,get:i})},n.r=function(t){Object.defineProperty(t,"__esModule",{value:!0})},n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="",n(n.s=3)}([function(t,e,n){var i;!function(r){"use strict";var o={},a=/d{1,4}|M{1,4}|YY(?:YY)?|S{1,3}|Do|ZZ|([HhMsDm])\1?|[aA]|"[^"]*"|'[^']*'/g,s=/\d\d?/,l=/[0-9]*['a-z\u00A0-\u05FF\u0700-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+|[\u0600-\u06FF\/]+(\s*?[\u0600-\u06FF]+){1,2}/i,u=/\[([^]*?)\]/gm,c=function(){};function p(t,e){for(var n=[],i=0,r=t.length;i<r;i++)n.push(t[i].substr(0,e));return n}function d(t){return function(e,n,i){var r=i[t].indexOf(n.charAt(0).toUpperCase()+n.substr(1).toLowerCase());~r&&(e.month=r)}}function A(t,e){for(t=String(t),e=e||2;t.length<e;)t="0"+t;return t}var f=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],h=["January","February","March","April","May","June","July","August","September","October","November","December"],m=p(h,3),v=p(f,3);o.i18n={dayNamesShort:v,dayNames:f,monthNamesShort:m,monthNames:h,amPm:["am","pm"],DoFn:function(t){return t+["th","st","nd","rd"][t%10>3?0:(t-t%10!=10)*t%10]}};var g={D:function(t){return t.getDate()},DD:function(t){return A(t.getDate())},Do:function(t,e){return e.DoFn(t.getDate())},d:function(t){return t.getDay()},dd:function(t){return A(t.getDay())},ddd:function(t,e){return e.dayNamesShort[t.getDay()]},dddd:function(t,e){return e.dayNames[t.getDay()]},M:function(t){return t.getMonth()+1},MM:function(t){return A(t.getMonth()+1)},MMM:function(t,e){return e.monthNamesShort[t.getMonth()]},MMMM:function(t,e){return e.monthNames[t.getMonth()]},YY:function(t){return String(t.getFullYear()).substr(2)},YYYY:function(t){return A(t.getFullYear(),4)},h:function(t){return t.getHours()%12||12},hh:function(t){return A(t.getHours()%12||12)},H:function(t){return t.getHours()},HH:function(t){return A(t.getHours())},m:function(t){return t.getMinutes()},mm:function(t){return A(t.getMinutes())},s:function(t){return t.getSeconds()},ss:function(t){return A(t.getSeconds())},S:function(t){return Math.round(t.getMilliseconds()/100)},SS:function(t){return A(Math.round(t.getMilliseconds()/10),2)},SSS:function(t){return A(t.getMilliseconds(),3)},a:function(t,e){return t.getHours()<12?e.amPm[0]:e.amPm[1]},A:function(t,e){return t.getHours()<12?e.amPm[0].toUpperCase():e.amPm[1].toUpperCase()},ZZ:function(t){var e=t.getTimezoneOffset();return(e>0?"-":"+")+A(100*Math.floor(Math.abs(e)/60)+Math.abs(e)%60,4)}},y={D:[s,function(t,e){t.day=e}],Do:[new RegExp(s.source+l.source),function(t,e){t.day=parseInt(e,10)}],M:[s,function(t,e){t.month=e-1}],YY:[s,function(t,e){var n=+(""+(new Date).getFullYear()).substr(0,2);t.year=""+(e>68?n-1:n)+e}],h:[s,function(t,e){t.hour=e}],m:[s,function(t,e){t.minute=e}],s:[s,function(t,e){t.second=e}],YYYY:[/\d{4}/,function(t,e){t.year=e}],S:[/\d/,function(t,e){t.millisecond=100*e}],SS:[/\d{2}/,function(t,e){t.millisecond=10*e}],SSS:[/\d{3}/,function(t,e){t.millisecond=e}],d:[s,c],ddd:[l,c],MMM:[l,d("monthNamesShort")],MMMM:[l,d("monthNames")],a:[l,function(t,e,n){var i=e.toLowerCase();i===n.amPm[0]?t.isPm=!1:i===n.amPm[1]&&(t.isPm=!0)}],ZZ:[/([\+\-]\d\d:?\d\d|Z)/,function(t,e){"Z"===e&&(e="+00:00");var n,i=(e+"").match(/([\+\-]|\d\d)/gi);i&&(n=60*i[1]+parseInt(i[2],10),t.timezoneOffset="+"===i[0]?n:-n)}]};y.dd=y.d,y.dddd=y.ddd,y.DD=y.D,y.mm=y.m,y.hh=y.H=y.HH=y.h,y.MM=y.M,y.ss=y.s,y.A=y.a,o.masks={default:"ddd MMM DD YYYY HH:mm:ss",shortDate:"M/D/YY",mediumDate:"MMM D, YYYY",longDate:"MMMM D, YYYY",fullDate:"dddd, MMMM D, YYYY",shortTime:"HH:mm",mediumTime:"HH:mm:ss",longTime:"HH:mm:ss.SSS"},o.format=function(t,e,n){var i=n||o.i18n;if("number"==typeof t&&(t=new Date(t)),"[object Date]"!==Object.prototype.toString.call(t)||isNaN(t.getTime()))throw new Error("Invalid Date in fecha.format");var r=[];return(e=(e=(e=o.masks[e]||e||o.masks.default).replace(u,function(t,e){return r.push(e),"??"})).replace(a,function(e){return e in g?g[e](t,i):e.slice(1,e.length-1)})).replace(/\?\?/g,function(){return r.shift()})},o.parse=function(t,e,n){var i=n||o.i18n;if("string"!=typeof e)throw new Error("Invalid format in fecha.parse");if(e=o.masks[e]||e,t.length>1e3)return!1;var r=!0,s={};if(e.replace(a,function(e){if(y[e]){var n=y[e],o=t.search(n[0]);~o?t.replace(n[0],function(e){return n[1](s,e,i),t=t.substr(o+e.length),e}):r=!1}return y[e]?"":e.slice(1,e.length-1)}),!r)return!1;var l,u=new Date;return!0===s.isPm&&null!=s.hour&&12!=+s.hour?s.hour=+s.hour+12:!1===s.isPm&&12==+s.hour&&(s.hour=0),null!=s.timezoneOffset?(s.minute=+(s.minute||0)-+s.timezoneOffset,l=new Date(Date.UTC(s.year||u.getFullYear(),s.month||0,s.day||1,s.hour||0,s.minute||0,s.second||0,s.millisecond||0))):l=new Date(s.year||u.getFullYear(),s.month||0,s.day||1,s.hour||0,s.minute||0,s.second||0,s.millisecond||0),l},void 0!==t&&t.exports?t.exports=o:void 0===(i=function(){return o}.call(e,n,e,t))||(t.exports=i)}()},function(t,e){var n=/^(attrs|props|on|nativeOn|class|style|hook)$/;function i(t,e){return function(){t&&t.apply(this,arguments),e&&e.apply(this,arguments)}}t.exports=function(t){return t.reduce(function(t,e){var r,o,a,s,l;for(a in e)if(r=t[a],o=e[a],r&&n.test(a))if("class"===a&&("string"==typeof r&&(l=r,t[a]=r={},r[l]=!0),"string"==typeof o&&(l=o,e[a]=o={},o[l]=!0)),"on"===a||"nativeOn"===a||"hook"===a)for(s in o)r[s]=i(r[s],o[s]);else if(Array.isArray(r))t[a]=r.concat(o);else if(Array.isArray(o))t[a]=[r].concat(o);else for(s in o)r[s]=o[s];else t[a]=e[a];return t},{})}},function(t,e,n){"use strict";function i(t,e){for(var n=[],i={},r=0;r<e.length;r++){var o=e[r],a=o[0],s={id:t+":"+r,css:o[1],media:o[2],sourceMap:o[3]};i[a]?i[a].parts.push(s):n.push(i[a]={id:a,parts:[s]})}return n}n.r(e),n.d(e,"default",function(){return f});var r="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!r)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var o={},a=r&&(document.head||document.getElementsByTagName("head")[0]),s=null,l=0,u=!1,c=function(){},p=null,d="data-vue-ssr-id",A="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function f(t,e,n,r){u=n,p=r||{};var a=i(t,e);return h(a),function(e){for(var n=[],r=0;r<a.length;r++){var s=a[r];(l=o[s.id]).refs--,n.push(l)}for(e?h(a=i(t,e)):a=[],r=0;r<n.length;r++){var l;if(0===(l=n[r]).refs){for(var u=0;u<l.parts.length;u++)l.parts[u]();delete o[l.id]}}}}function h(t){for(var e=0;e<t.length;e++){var n=t[e],i=o[n.id];if(i){i.refs++;for(var r=0;r<i.parts.length;r++)i.parts[r](n.parts[r]);for(;r<n.parts.length;r++)i.parts.push(v(n.parts[r]));i.parts.length>n.parts.length&&(i.parts.length=n.parts.length)}else{var a=[];for(r=0;r<n.parts.length;r++)a.push(v(n.parts[r]));o[n.id]={id:n.id,refs:1,parts:a}}}}function m(){var t=document.createElement("style");return t.type="text/css",a.appendChild(t),t}function v(t){var e,n,i=document.querySelector("style["+d+'~="'+t.id+'"]');if(i){if(u)return c;i.parentNode.removeChild(i)}if(A){var r=l++;i=s||(s=m()),e=b.bind(null,i,r,!1),n=b.bind(null,i,r,!0)}else i=m(),e=function(t,e){var n=e.css,i=e.media,r=e.sourceMap;if(i&&t.setAttribute("media",i),p.ssrId&&t.setAttribute(d,e.id),r&&(n+="\n/*# sourceURL="+r.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */"),t.styleSheet)t.styleSheet.cssText=n;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(n))}}.bind(null,i),n=function(){i.parentNode.removeChild(i)};return e(t),function(i){if(i){if(i.css===t.css&&i.media===t.media&&i.sourceMap===t.sourceMap)return;e(t=i)}else n()}}var g,y=(g=[],function(t,e){return g[t]=e,g.filter(Boolean).join("\n")});function b(t,e,n,i){var r=n?"":i.css;if(t.styleSheet)t.styleSheet.cssText=y(e,r);else{var o=document.createTextNode(r),a=t.childNodes;a[e]&&t.removeChild(a[e]),a.length?t.insertBefore(o,a[e]):t.appendChild(o)}}},function(t,e,n){"use strict";n.r(e);var i=n(0),r=n.n(i),o={bind:function(t,e,n){t["@clickoutside"]=function(i){t.contains(i.target)||n.context.popupElm&&n.context.popupElm.contains(i.target)||!e.expression||!n.context[e.expression]||e.value()},document.addEventListener("click",t["@clickoutside"],!1)},unbind:function(t){document.removeEventListener("click",t["@clickoutside"],!1)}};function a(t){return"[object Object]"===Object.prototype.toString.call(t)}function s(t){return t instanceof Date}function l(t){return null!=t&&!isNaN(new Date(t).getTime())}function u(t){var e=(t||"").split(":");return e.length>=2?{hours:parseInt(e[0],10),minutes:parseInt(e[1],10)}:null}function c(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"24",n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"a",i=t.hours,r=(i=(i="24"===e?i:i%12||12)<10?"0"+i:i)+":"+(t.minutes<10?"0"+t.minutes:t.minutes);if("12"===e){var o=t.hours>=12?"pm":"am";"A"===n&&(o=o.toUpperCase()),r=r+" "+o}return r}function p(t,e){if(!t)return"";try{return r.a.format(new Date(t),e)}catch(t){return""}}var d={date:{value2date:function(t){return l(t)?new Date(t):null},date2value:function(t){return t}},timestamp:{value2date:function(t){return l(t)?new Date(t):null},date2value:function(t){return t&&new Date(t).getTime()}}},A={zh:{days:["日","一","二","三","四","五","六"],months:["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],pickers:["未来7天","未来30天","最近7天","最近30天"],placeholder:{date:"请选择日期",dateRange:"请选择日期范围"}},en:{days:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],months:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],pickers:["next 7 days","next 30 days","previous 7 days","previous 30 days"],placeholder:{date:"Select Date",dateRange:"Select Date Range"}},ro:{days:["Lun","Mar","Mie","Joi","Vin","Sâm","Dum"],months:["Ian","Feb","Mar","Apr","Mai","Iun","Iul","Aug","Sep","Oct","Noi","Dec"],pickers:["urmatoarele 7 zile","urmatoarele 30 zile","ultimele 7 zile","ultimele 30 zile"],placeholder:{date:"Selectați Data",dateRange:"Selectați Intervalul De Date"}},fr:{days:["Dim","Lun","Mar","Mer","Jeu","Ven","Sam"],months:["Jan","Fev","Mar","Avr","Mai","Juin","Juil","Aout","Sep","Oct","Nov","Dec"],pickers:["7 jours suivants","30 jours suivants","7 jours précédents","30 jours précédents"],placeholder:{date:"Sélectionnez une date",dateRange:"Sélectionnez une période"}},es:{days:["Dom","Lun","mar","Mie","Jue","Vie","Sab"],months:["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"],pickers:["próximos 7 días","próximos 30 días","7 días anteriores","30 días anteriores"],placeholder:{date:"Seleccionar fecha",dateRange:"Seleccionar un rango de fechas"}},"pt-br":{days:["Dom","Seg","Ter","Qua","Quin","Sex","Sáb"],months:["Jan","Fev","Mar","Abr","Maio","Jun","Jul","Ago","Set","Out","Nov","Dez"],pickers:["próximos 7 dias","próximos 30 dias","7 dias anteriores"," 30 dias anteriores"],placeholder:{date:"Selecione uma data",dateRange:"Selecione um período"}},ru:{days:["Вс","Пн","Вт","Ср","Чт","Пт","Сб"],months:["Янв","Фев","Мар","Апр","Май","Июн","Июл","Авг","Сен","Окт","Ноя","Дек"],pickers:["след. 7 дней","след. 30 дней","прош. 7 дней","прош. 30 дней"],placeholder:{date:"Выберите дату",dateRange:"Выберите период"}},de:{days:["So","Mo","Di","Mi","Do","Fr","Sa"],months:["Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"],pickers:["nächsten 7 Tage","nächsten 30 Tage","vorigen 7 Tage","vorigen 30 Tage"],placeholder:{date:"Datum auswählen",dateRange:"Zeitraum auswählen"}},it:{days:["Dom","Lun","Mar","Mer","Gio","Ven","Sab"],months:["Gen","Feb","Mar","Apr","Mag","Giu","Lug","Ago","Set","Ott","Nov","Dic"],pickers:["successivi 7 giorni","successivi 30 giorni","precedenti 7 giorni","precedenti 30 giorni"],placeholder:{date:"Seleziona una data",dateRange:"Seleziona un intervallo date"}},cs:{days:["Ned","Pon","Úte","Stř","Čtv","Pát","Sob"],months:["Led","Úno","Bře","Dub","Kvě","Čer","Čerc","Srp","Zář","Říj","Lis","Pro"],pickers:["příštích 7 dní","příštích 30 dní","předchozích 7 dní","předchozích 30 dní"],placeholder:{date:"Vyberte datum",dateRange:"Vyberte časové rozmezí"}},sl:{days:["Ned","Pon","Tor","Sre","Čet","Pet","Sob"],months:["Jan","Feb","Mar","Apr","Maj","Jun","Jul","Avg","Sep","Okt","Nov","Dec"],pickers:["naslednjih 7 dni","naslednjih 30 dni","prejšnjih 7 dni","prejšnjih 30 dni"],placeholder:{date:"Izberite datum",dateRange:"Izberite razpon med 2 datumoma"}}},f=A.zh,h={methods:{t:function(t){for(var e=this,n=e.$options.name;e&&(!n||"DatePicker"!==n);)(e=e.$parent)&&(n=e.$options.name);for(var i=e&&e.language||f,r=t.split("."),o=i,a=void 0,s=0,l=r.length;s<l;s++){if(a=o[r[s]],s===l-1)return a;if(!a)return"";o=a}return""}}};function m(t,e){if(e){for(var n=[],i=e.offsetParent;i&&t!==i&&t.contains(i);)n.push(i),i=i.offsetParent;var r=e.offsetTop+n.reduce(function(t,e){return t+e.offsetTop},0),o=r+e.offsetHeight,a=t.scrollTop,s=a+t.clientHeight;r<a?t.scrollTop=r:o>s&&(t.scrollTop=o-t.clientHeight)}else t.scrollTop=0}var v=n(1),g=n.n(v);function y(t){if(Array.isArray(t)){for(var e=0,n=Array(t.length);e<t.length;e++)n[e]=t[e];return n}return Array.from(t)}function b(t,e,n,i,r,o,a,s){var l,u="function"==typeof t?t.options:t;if(e&&(u.render=e,u.staticRenderFns=n,u._compiled=!0),i&&(u.functional=!0),o&&(u._scopeId="data-v-"+o),a?(l=function(t){(t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),r&&r.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(a)},u._ssrRegister=l):r&&(l=s?function(){r.call(this,this.$root.$options.shadowRoot)}:r),l)if(u.functional){u._injectStyles=l;var c=u.render;u.render=function(t,e){return l.call(e),c(t,e)}}else{var p=u.beforeCreate;u.beforeCreate=p?[].concat(p,l):[l]}return{exports:t,options:u}}var w=b({name:"CalendarPanel",components:{PanelDate:{name:"panelDate",mixins:[h],props:{value:null,startAt:null,endAt:null,dateFormat:{type:String,default:"YYYY-MM-DD"},calendarMonth:{default:(new Date).getMonth()},calendarYear:{default:(new Date).getFullYear()},firstDayOfWeek:{default:7,type:Number,validator:function(t){return t>=1&&t<=7}},disabledDate:{type:Function,default:function(){return!1}}},methods:{selectDate:function(t){var e=t.year,n=t.month,i=t.day,r=new Date(e,n,i);this.disabledDate(r)||this.$emit("select",r)},getDays:function(t){var e=this.t("days"),n=parseInt(t,10);return e.concat(e).slice(n,n+7)},getDates:function(t,e,n){var i=[],r=new Date(t,e);r.setDate(0);for(var o=(r.getDay()+7-n)%7+1,a=r.getDate()-(o-1),s=0;s<o;s++)i.push({year:t,month:e-1,day:a+s});r.setMonth(r.getMonth()+2,0);for(var l=r.getDate(),u=0;u<l;u++)i.push({year:t,month:e,day:1+u});r.setMonth(r.getMonth()+1,1);for(var c=42-(o+l),p=0;p<c;p++)i.push({year:t,month:e+1,day:1+p});return i},getCellClasses:function(t){var e=t.year,n=t.month,i=t.day,r=[],o=new Date(e,n,i).getTime(),a=(new Date).setHours(0,0,0,0),s=this.value&&new Date(this.value).setHours(0,0,0,0),l=this.startAt&&new Date(this.startAt).setHours(0,0,0,0),u=this.endAt&&new Date(this.endAt).setHours(0,0,0,0);return n<this.calendarMonth?r.push("last-month"):n>this.calendarMonth?r.push("next-month"):r.push("cur-month"),o===a&&r.push("today"),this.disabledDate(o)&&r.push("disabled"),s&&(o===s?r.push("actived"):l&&o<=s?r.push("inrange"):u&&o>=s&&r.push("inrange")),r},getCellTitle:function(t){var e=t.year,n=t.month,i=t.day;return p(new Date(e,n,i),this.dateFormat)}},render:function(t){var e=this,n=this.getDays(this.firstDayOfWeek).map(function(e){return t("th",[e])}),i=this.getDates(this.calendarYear,this.calendarMonth,this.firstDayOfWeek),r=Array.apply(null,{length:6}).map(function(n,r){var o=i.slice(7*r,7*r+7).map(function(n){var i={class:e.getCellClasses(n)};return t("td",g()([{class:"cell"},i,{attrs:{title:e.getCellTitle(n)},on:{click:e.selectDate.bind(e,n)}}]),[n.day])});return t("tr",[o])});return t("table",{class:"mx-panel mx-panel-date"},[t("thead",[t("tr",[n])]),t("tbody",[r])])}},PanelYear:{name:"panelYear",props:{value:null,firstYear:Number,disabledYear:Function},methods:{isDisabled:function(t){return!("function"!=typeof this.disabledYear||!this.disabledYear(t))},selectYear:function(t){this.isDisabled(t)||this.$emit("select",t)}},render:function(t){var e=this,n=10*Math.floor(this.firstYear/10),i=this.value&&new Date(this.value).getFullYear(),r=Array.apply(null,{length:10}).map(function(r,o){var a=n+o;return t("span",{class:{cell:!0,actived:i===a,disabled:e.isDisabled(a)},on:{click:e.selectYear.bind(e,a)}},[a])});return t("div",{class:"mx-panel mx-panel-year"},[r])}},PanelMonth:{name:"panelMonth",mixins:[h],props:{value:null,calendarYear:{default:(new Date).getFullYear()},disabledMonth:Function},methods:{isDisabled:function(t){return!("function"!=typeof this.disabledMonth||!this.disabledMonth(t))},selectMonth:function(t){this.isDisabled(t)||this.$emit("select",t)}},render:function(t){var e=this,n=this.t("months"),i=this.value&&new Date(this.value).getFullYear(),r=this.value&&new Date(this.value).getMonth();return n=n.map(function(n,o){return t("span",{class:{cell:!0,actived:i===e.calendarYear&&r===o,disabled:e.isDisabled(o)},on:{click:e.selectMonth.bind(e,o)}},[n])}),t("div",{class:"mx-panel mx-panel-month"},[n])}},PanelTime:{name:"panelTime",props:{timePickerOptions:{type:[Object,Function],default:function(){return null}},minuteStep:{type:Number,default:0,validator:function(t){return t>=0&&t<=60}},value:null,timeType:{type:Array,default:function(){return["24","a"]}},disabledTime:Function},computed:{currentHours:function(){return this.value?new Date(this.value).getHours():0},currentMinutes:function(){return this.value?new Date(this.value).getMinutes():0},currentSeconds:function(){return this.value?new Date(this.value).getSeconds():0}},methods:{stringifyText:function(t){return("00"+t).slice(String(t).length)},selectTime:function(t){"function"==typeof this.disabledTime&&this.disabledTime(t)||this.$emit("select",new Date(t))},pickTime:function(t){"function"==typeof this.disabledTime&&this.disabledTime(t)||this.$emit("pick",new Date(t))},getTimeSelectOptions:function(){var t=[],e=this.timePickerOptions;if(!e)return[];if("function"==typeof e)return e()||[];var n=u(e.start),i=u(e.end),r=u(e.step);if(n&&i&&r)for(var o=n.minutes+60*n.hours,a=i.minutes+60*i.hours,s=r.minutes+60*r.hours,l=Math.floor((a-o)/s),p=0;p<=l;p++){var d=o+p*s,A={hours:Math.floor(d/60),minutes:d%60};t.push({value:A,label:c.apply(void 0,[A].concat(y(this.timeType)))})}return t}},render:function(t){var e=this,n=new Date(this.value),i="function"==typeof this.disabledTime&&this.disabledTime,r=this.getTimeSelectOptions();if(Array.isArray(r)&&r.length)return r=r.map(function(r){var o=r.value.hours,a=r.value.minutes,s=new Date(n).setHours(o,a,0);return t("li",{class:{"mx-time-picker-item":!0,cell:!0,actived:o===e.currentHours&&a===e.currentMinutes,disabled:i&&i(s)},on:{click:e.pickTime.bind(e,s)}},[r.label])}),t("div",{class:"mx-panel mx-panel-time"},[t("ul",{class:"mx-time-list"},[r])]);var o=Array.apply(null,{length:24}).map(function(r,o){var a=new Date(n).setHours(o);return t("li",{class:{cell:!0,actived:o===e.currentHours,disabled:i&&i(a)},on:{click:e.selectTime.bind(e,a)}},[e.stringifyText(o)])}),a=this.minuteStep||1,s=parseInt(60/a),l=Array.apply(null,{length:s}).map(function(r,o){var s=o*a,l=new Date(n).setMinutes(s);return t("li",{class:{cell:!0,actived:s===e.currentMinutes,disabled:i&&i(l)},on:{click:e.selectTime.bind(e,l)}},[e.stringifyText(s)])}),u=Array.apply(null,{length:60}).map(function(r,o){var a=new Date(n).setSeconds(o);return t("li",{class:{cell:!0,actived:o===e.currentSeconds,disabled:i&&i(a)},on:{click:e.selectTime.bind(e,a)}},[e.stringifyText(o)])}),c=[o,l];return 0===this.minuteStep&&c.push(u),c=c.map(function(e){return t("ul",{class:"mx-time-list",style:{width:100/c.length+"%"}},[e])}),t("div",{class:"mx-panel mx-panel-time"},[c])}}},mixins:[h,{methods:{dispatch:function(t,e,n){for(var i=this.$parent||this.$root,r=i.$options.name;i&&(!r||r!==t);)(i=i.$parent)&&(r=i.$options.name);r&&r===t&&(i=i||this).$emit.apply(i,[e].concat(n))}}}],props:{value:{default:null,validator:function(t){return null===t||l(t)}},startAt:null,endAt:null,visible:{type:Boolean,default:!1},type:{type:String,default:"date"},dateFormat:{type:String,default:"YYYY-MM-DD"},firstDayOfWeek:{default:7,type:Number,validator:function(t){return t>=1&&t<=7}},notBefore:{default:null,validator:function(t){return!t||l(t)}},notAfter:{default:null,validator:function(t){return!t||l(t)}},disabledDays:{type:[Array,Function],default:function(){return[]}},minuteStep:{type:Number,default:0,validator:function(t){return t>=0&&t<=60}},timePickerOptions:{type:[Object,Function],default:function(){return null}}},data:function(){var t=new Date,e=t.getFullYear();return{panel:"NONE",dates:[],calendarMonth:t.getMonth(),calendarYear:e,firstYear:10*Math.floor(e/10)}},computed:{now:{get:function(){return new Date(this.calendarYear,this.calendarMonth).getTime()},set:function(t){var e=new Date(t);this.calendarYear=e.getFullYear(),this.calendarMonth=e.getMonth()}},timeType:function(){return[/h+/.test(this.$parent.format)?"12":"24",/A/.test(this.$parent.format)?"A":"a"]},timeHeader:function(){return"time"===this.type?this.$parent.format:this.value&&p(this.value,this.dateFormat)},yearHeader:function(){return this.firstYear+" ~ "+(this.firstYear+9)},months:function(){return this.t("months")},notBeforeTime:function(){return this.getCriticalTime(this.notBefore)},notAfterTime:function(){return this.getCriticalTime(this.notAfter)}},watch:{value:{immediate:!0,handler:"updateNow"},visible:{immediate:!0,handler:"init"},panel:{handler:"handelPanelChange"}},methods:{handelPanelChange:function(t,e){var n=this;this.dispatch("DatePicker","panel-change",[t,e]),"YEAR"===t?this.firstYear=10*Math.floor(this.calendarYear/10):"TIME"===t&&this.$nextTick(function(){for(var t=n.$el.querySelectorAll(".mx-panel-time .mx-time-list"),e=0,i=t.length;e<i;e++){var r=t[e];m(r,r.querySelector(".actived"))}})},init:function(t){if(t){var e=this.type;"month"===e?this.showPanelMonth():"year"===e?this.showPanelYear():"time"===e?this.showPanelTime():this.showPanelDate()}else this.showPanelNone(),this.updateNow(this.value)},updateNow:function(t){var e=t?new Date(t):new Date,n=new Date(this.now);this.now=e,this.visible&&this.dispatch("DatePicker","calendar-change",[e,n])},getCriticalTime:function(t){if(!t)return null;var e=new Date(t);return"year"===this.type?new Date(e.getFullYear(),0).getTime():"month"===this.type?new Date(e.getFullYear(),e.getMonth()).getTime():"date"===this.type?e.setHours(0,0,0,0):e.getTime()},inBefore:function(t,e){return void 0===e&&(e=this.startAt),this.notBeforeTime&&t<this.notBeforeTime||e&&t<this.getCriticalTime(e)},inAfter:function(t,e){return void 0===e&&(e=this.endAt),this.notAfterTime&&t>this.notAfterTime||e&&t>this.getCriticalTime(e)},inDisabledDays:function(t){var e=this;return Array.isArray(this.disabledDays)?this.disabledDays.some(function(n){return e.getCriticalTime(n)===t}):"function"==typeof this.disabledDays&&this.disabledDays(new Date(t))},isDisabledYear:function(t){var e=new Date(t,0).getTime(),n=new Date(t+1,0).getTime()-1;return this.inBefore(n)||this.inAfter(e)||"year"===this.type&&this.inDisabledDays(e)},isDisabledMonth:function(t){var e=new Date(this.calendarYear,t).getTime(),n=new Date(this.calendarYear,t+1).getTime()-1;return this.inBefore(n)||this.inAfter(e)||"month"===this.type&&this.inDisabledDays(e)},isDisabledDate:function(t){var e=new Date(t).getTime(),n=new Date(t).setHours(23,59,59,999);return this.inBefore(n)||this.inAfter(e)||this.inDisabledDays(e)},isDisabledTime:function(t,e,n){var i=new Date(t).getTime();return this.inBefore(i,e)||this.inAfter(i,n)||this.inDisabledDays(i)},selectDate:function(t){if("datetime"===this.type){var e=new Date(t);return s(this.value)&&e.setHours(this.value.getHours(),this.value.getMinutes(),this.value.getSeconds()),this.isDisabledTime(e)&&(e.setHours(0,0,0,0),this.notBefore&&e.getTime()<new Date(this.notBefore).getTime()&&(e=new Date(this.notBefore)),this.startAt&&e.getTime()<new Date(this.startAt).getTime()&&(e=new Date(this.startAt))),this.selectTime(e),void this.showPanelTime()}this.$emit("select-date",t)},selectYear:function(t){if(this.changeCalendarYear(t),"year"===this.type.toLowerCase())return this.selectDate(new Date(this.now));this.showPanelMonth()},selectMonth:function(t){if(this.changeCalendarMonth(t),"month"===this.type.toLowerCase())return this.selectDate(new Date(this.now));this.showPanelDate()},selectTime:function(t){this.$emit("select-time",t,!1)},pickTime:function(t){this.$emit("select-time",t,!0)},changeCalendarYear:function(t){this.updateNow(new Date(t,this.calendarMonth))},changeCalendarMonth:function(t){this.updateNow(new Date(this.calendarYear,t))},getSibling:function(){var t=this,e=this.$parent.$children.filter(function(e){return e.$options.name===t.$options.name});return e[1^e.indexOf(this)]},handleIconMonth:function(t){var e=this.calendarMonth;this.changeCalendarMonth(e+t),this.$parent.$emit("change-calendar-month",{month:e,flag:t,vm:this,sibling:this.getSibling()})},handleIconYear:function(t){if("YEAR"===this.panel)this.changePanelYears(t);else{var e=this.calendarYear;this.changeCalendarYear(e+t),this.$parent.$emit("change-calendar-year",{year:e,flag:t,vm:this,sibling:this.getSibling()})}},handleBtnYear:function(){this.showPanelYear()},handleBtnMonth:function(){this.showPanelMonth()},handleTimeHeader:function(){"time"!==this.type&&this.showPanelDate()},changePanelYears:function(t){this.firstYear=this.firstYear+10*t},showPanelNone:function(){this.panel="NONE"},showPanelTime:function(){this.panel="TIME"},showPanelDate:function(){this.panel="DATE"},showPanelYear:function(){this.panel="YEAR"},showPanelMonth:function(){this.panel="MONTH"}}},function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"mx-calendar",class:"mx-calendar-panel-"+t.panel.toLowerCase()},[n("div",{staticClass:"mx-calendar-header"},[n("a",{directives:[{name:"show",rawName:"v-show",value:"TIME"!==t.panel,expression:"panel !== 'TIME'"}],staticClass:"mx-icon-last-year",on:{click:function(e){t.handleIconYear(-1)}}},[t._v("«")]),t._v(" "),n("a",{directives:[{name:"show",rawName:"v-show",value:"DATE"===t.panel,expression:"panel === 'DATE'"}],staticClass:"mx-icon-last-month",on:{click:function(e){t.handleIconMonth(-1)}}},[t._v("‹")]),t._v(" "),n("a",{directives:[{name:"show",rawName:"v-show",value:"TIME"!==t.panel,expression:"panel !== 'TIME'"}],staticClass:"mx-icon-next-year",on:{click:function(e){t.handleIconYear(1)}}},[t._v("»")]),t._v(" "),n("a",{directives:[{name:"show",rawName:"v-show",value:"DATE"===t.panel,expression:"panel === 'DATE'"}],staticClass:"mx-icon-next-month",on:{click:function(e){t.handleIconMonth(1)}}},[t._v("›")]),t._v(" "),n("a",{directives:[{name:"show",rawName:"v-show",value:"DATE"===t.panel,expression:"panel === 'DATE'"}],staticClass:"mx-current-month",on:{click:t.handleBtnMonth}},[t._v(t._s(t.months[t.calendarMonth]))]),t._v(" "),n("a",{directives:[{name:"show",rawName:"v-show",value:"DATE"===t.panel||"MONTH"===t.panel,expression:"panel === 'DATE' || panel === 'MONTH'"}],staticClass:"mx-current-year",on:{click:t.handleBtnYear}},[t._v(t._s(t.calendarYear))]),t._v(" "),n("a",{directives:[{name:"show",rawName:"v-show",value:"YEAR"===t.panel,expression:"panel === 'YEAR'"}],staticClass:"mx-current-year"},[t._v(t._s(t.yearHeader))]),t._v(" "),n("a",{directives:[{name:"show",rawName:"v-show",value:"TIME"===t.panel,expression:"panel === 'TIME'"}],staticClass:"mx-time-header",on:{click:t.handleTimeHeader}},[t._v(t._s(t.timeHeader))])]),t._v(" "),n("div",{staticClass:"mx-calendar-content"},[n("panel-date",{directives:[{name:"show",rawName:"v-show",value:"DATE"===t.panel,expression:"panel === 'DATE'"}],attrs:{value:t.value,"date-format":t.dateFormat,"calendar-month":t.calendarMonth,"calendar-year":t.calendarYear,"start-at":t.startAt,"end-at":t.endAt,"first-day-of-week":t.firstDayOfWeek,"disabled-date":t.isDisabledDate},on:{select:t.selectDate}}),t._v(" "),n("panel-year",{directives:[{name:"show",rawName:"v-show",value:"YEAR"===t.panel,expression:"panel === 'YEAR'"}],attrs:{value:t.value,"disabled-year":t.isDisabledYear,"first-year":t.firstYear},on:{select:t.selectYear}}),t._v(" "),n("panel-month",{directives:[{name:"show",rawName:"v-show",value:"MONTH"===t.panel,expression:"panel === 'MONTH'"}],attrs:{value:t.value,"disabled-month":t.isDisabledMonth,"calendar-year":t.calendarYear},on:{select:t.selectMonth}}),t._v(" "),n("panel-time",{directives:[{name:"show",rawName:"v-show",value:"TIME"===t.panel,expression:"panel === 'TIME'"}],attrs:{"minute-step":t.minuteStep,"time-picker-options":t.timePickerOptions,value:t.value,"disabled-time":t.isDisabledTime,"time-type":t.timeType},on:{select:t.selectTime,pick:t.pickTime}})],1)])},[],!1,null,null,null).exports,_=Object.assign||function(t){for(var e=1;e<arguments.length;e++){var n=arguments[e];for(var i in n)Object.prototype.hasOwnProperty.call(n,i)&&(t[i]=n[i])}return t},x=b({fecha:r.a,name:"DatePicker",components:{CalendarPanel:w},mixins:[h],directives:{clickoutside:o},props:{value:null,valueType:{default:"date",validator:function(t){return-1!==["timestamp","format","date"].indexOf(t)||a(t)}},placeholder:{type:String,default:null},lang:{type:[String,Object],default:"zh"},format:{type:[String,Object],default:"YYYY-MM-DD"},dateFormat:{type:String},type:{type:String,default:"date"},range:{type:Boolean,default:!1},rangeSeparator:{type:String,default:"~"},width:{type:[String,Number],default:null},confirmText:{type:String,default:"OK"},confirm:{type:Boolean,default:!1},editable:{type:Boolean,default:!0},disabled:{type:Boolean,default:!1},clearable:{type:Boolean,default:!0},shortcuts:{type:[Boolean,Array],default:!0},inputName:{type:String,default:"date"},inputClass:{type:[String,Array],default:"mx-input"},inputAttr:Object,appendToBody:{type:Boolean,default:!1},popupStyle:{type:Object}},data:function(){return{currentValue:this.range?[null,null]:null,userInput:null,popupVisible:!1,position:{}}},watch:{value:{immediate:!0,handler:"handleValueChange"},popupVisible:function(t){t?this.initCalendar():this.userInput=null}},computed:{transform:function(){var t=this.valueType;return a(t)?_({},d.date,t):"format"===t?{value2date:this.parse.bind(this),date2value:this.stringify.bind(this)}:d[t]||d.date},language:function(){return a(this.lang)?_({},A.en,this.lang):A[this.lang]||A.en},innerPlaceholder:function(){return"string"==typeof this.placeholder?this.placeholder:this.range?this.t("placeholder.dateRange"):this.t("placeholder.date")},text:function(){if(null!==this.userInput)return this.userInput;var t=this.transform.value2date;return this.range?this.isValidRangeValue(this.value)?this.stringify(t(this.value[0]))+" "+this.rangeSeparator+" "+this.stringify(t(this.value[1])):"":this.isValidValue(this.value)?this.stringify(t(this.value)):""},computedWidth:function(){return"number"==typeof this.width||"string"==typeof this.width&&/^\d+$/.test(this.width)?this.width+"px":this.width},showClearIcon:function(){return!this.disabled&&this.clearable&&(this.range?this.isValidRangeValue(this.value):this.isValidValue(this.value))},innerType:function(){return String(this.type).toLowerCase()},innerShortcuts:function(){if(Array.isArray(this.shortcuts))return this.shortcuts;if(!1===this.shortcuts)return[];var t=this.t("pickers");return[{text:t[0],onClick:function(t){t.currentValue=[new Date,new Date(Date.now()+6048e5)],t.updateDate(!0)}},{text:t[1],onClick:function(t){t.currentValue=[new Date,new Date(Date.now()+2592e6)],t.updateDate(!0)}},{text:t[2],onClick:function(t){t.currentValue=[new Date(Date.now()-6048e5),new Date],t.updateDate(!0)}},{text:t[3],onClick:function(t){t.currentValue=[new Date(Date.now()-2592e6),new Date],t.updateDate(!0)}}]},innerDateFormat:function(){return this.dateFormat?this.dateFormat:"string"!=typeof this.format?"YYYY-MM-DD":"date"===this.innerType?this.format:this.format.replace(/[Hh]+.*[msSaAZ]|\[.*?\]/g,"").trim()||"YYYY-MM-DD"},innerPopupStyle:function(){return _({},this.position,this.popupStyle)}},mounted:function(){var t,e,n,i=this;this.appendToBody&&(this.popupElm=this.$refs.calendar,document.body.appendChild(this.popupElm)),this._displayPopup=(t=function(){i.popupVisible&&i.displayPopup()},e=0,n=null,function(){var i=this;if(!n){var r=arguments,o=function(){e=Date.now(),n=null,t.apply(i,r)};Date.now()-e>=200?o():n=setTimeout(o,200)}}),window.addEventListener("resize",this._displayPopup),window.addEventListener("scroll",this._displayPopup)},beforeDestroy:function(){this.popupElm&&this.popupElm.parentNode===document.body&&document.body.removeChild(this.popupElm),window.removeEventListener("resize",this._displayPopup),window.removeEventListener("scroll",this._displayPopup)},methods:{initCalendar:function(){this.handleValueChange(this.value),this.displayPopup()},stringify:function(t){return a(this.format)&&"function"==typeof this.format.stringify?this.format.stringify(t):p(t,this.format)},parse:function(t){return a(this.format)&&"function"==typeof this.format.parse?this.format.parse(t):function(t,e){try{return r.a.parse(t,e)}catch(t){return null}}(t,this.format)},isValidValue:function(t){return l((0,this.transform.value2date)(t))},isValidRangeValue:function(t){var e=this.transform.value2date;return Array.isArray(t)&&2===t.length&&this.isValidValue(t[0])&&this.isValidValue(t[1])&&e(t[1]).getTime()>=e(t[0]).getTime()},dateEqual:function(t,e){return s(t)&&s(e)&&t.getTime()===e.getTime()},rangeEqual:function(t,e){var n=this;return Array.isArray(t)&&Array.isArray(e)&&t.length===e.length&&t.every(function(t,i){return n.dateEqual(t,e[i])})},selectRange:function(t){if("function"==typeof t.onClick)return t.onClick(this);this.currentValue=[new Date(t.start),new Date(t.end)],this.updateDate(!0)},clearDate:function(){var t=this.range?[null,null]:null;this.currentValue=t,this.updateDate(!0),this.$emit("clear")},confirmDate:function(){var t;(this.range?(t=this.currentValue,Array.isArray(t)&&2===t.length&&l(t[0])&&l(t[1])&&new Date(t[1]).getTime()>=new Date(t[0]).getTime()):l(this.currentValue))&&this.updateDate(!0),this.emitDate("confirm"),this.closePopup()},updateDate:function(){var t=arguments.length>0&&void 0!==arguments[0]&&arguments[0];return!(this.confirm&&!t||this.disabled||(this.range?this.rangeEqual(this.value,this.currentValue):this.dateEqual(this.value,this.currentValue))||(this.emitDate("input"),this.emitDate("change"),0))},emitDate:function(t){var e=this.transform.date2value,n=this.range?this.currentValue.map(e):e(this.currentValue);this.$emit(t,n)},handleValueChange:function(t){var e=this.transform.value2date;this.range?this.currentValue=this.isValidRangeValue(t)?t.map(e):[null,null]:this.currentValue=this.isValidValue(t)?e(t):null},selectDate:function(t){this.currentValue=t,this.updateDate()&&this.closePopup()},selectStartDate:function(t){this.$set(this.currentValue,0,t),this.currentValue[1]&&this.updateDate()},selectEndDate:function(t){this.$set(this.currentValue,1,t),this.currentValue[0]&&this.updateDate()},selectTime:function(t,e){this.currentValue=t,this.updateDate()&&e&&this.closePopup()},selectStartTime:function(t){this.selectStartDate(t)},selectEndTime:function(t){this.selectEndDate(t)},showPopup:function(){this.disabled||(this.popupVisible=!0)},closePopup:function(){this.popupVisible=!1},getPopupSize:function(t){var e=t.style.display,n=t.style.visibility;t.style.display="block",t.style.visibility="hidden";var i=window.getComputedStyle(t),r={width:t.offsetWidth+parseInt(i.marginLeft)+parseInt(i.marginRight),height:t.offsetHeight+parseInt(i.marginTop)+parseInt(i.marginBottom)};return t.style.display=e,t.style.visibility=n,r},displayPopup:function(){var t=document.documentElement.clientWidth,e=document.documentElement.clientHeight,n=this.$el.getBoundingClientRect(),i=this._popupRect||(this._popupRect=this.getPopupSize(this.$refs.calendar)),r={},o=0,a=0;this.appendToBody&&(o=window.pageXOffset+n.left,a=window.pageYOffset+n.top),t-n.left<i.width&&n.right<i.width?r.left=o-n.left+1+"px":n.left+n.width/2<=t/2?r.left=o+"px":r.left=o+n.width-i.width+"px",n.top<=i.height&&e-n.bottom<=i.height?r.top=a+e-n.top-i.height+"px":n.top+n.height/2<=e/2?r.top=a+n.height+"px":r.top=a-i.height+"px",r.top===this.position.top&&r.left===this.position.left||(this.position=r)},handleInput:function(t){this.userInput=t.target.value},handleChange:function(t){var e=t.target.value;if(this.editable&&null!==this.userInput){var n=this.$refs.calendarPanel.isDisabledTime;if(!e)return void this.clearDate();if(this.range){var i=e.split(" "+this.rangeSeparator+" ");if(2===i.length){var r=this.parse(i[0]),o=this.parse(i[1]);if(r&&o&&!n(r,null,o)&&!n(o,r,null))return this.currentValue=[r,o],this.updateDate(!0),void this.closePopup()}}else{var a=this.parse(e);if(a&&!n(a,null,null))return this.currentValue=a,this.updateDate(!0),void this.closePopup()}this.$emit("input-error",e)}}}},function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{directives:[{name:"clickoutside",rawName:"v-clickoutside",value:t.closePopup,expression:"closePopup"}],staticClass:"mx-datepicker",class:{"mx-datepicker-range":t.range,disabled:t.disabled},style:{width:t.computedWidth}},[n("div",{staticClass:"mx-input-wrapper",on:{click:function(e){return e.stopPropagation(),t.showPopup(e)}}},[n("input",t._b({ref:"input",class:t.inputClass,attrs:{name:t.inputName,type:"text",autocomplete:"off",disabled:t.disabled,readonly:!t.editable,placeholder:t.innerPlaceholder},domProps:{value:t.text},on:{input:t.handleInput,change:t.handleChange}},"input",t.inputAttr,!1)),t._v(" "),n("span",{staticClass:"mx-input-append"},[t._t("calendar-icon",[n("svg",{staticClass:"mx-calendar-icon",attrs:{xmlns:"http://www.w3.org/2000/svg",version:"1.1",viewBox:"0 0 200 200"}},[n("rect",{attrs:{x:"13",y:"29",rx:"14",ry:"14",width:"174",height:"158",fill:"transparent"}}),t._v(" "),n("line",{attrs:{x1:"46",x2:"46",y1:"8",y2:"50"}}),t._v(" "),n("line",{attrs:{x1:"154",x2:"154",y1:"8",y2:"50"}}),t._v(" "),n("line",{attrs:{x1:"13",x2:"187",y1:"70",y2:"70"}}),t._v(" "),n("text",{attrs:{x:"50%",y:"135","font-size":"90","stroke-width":"1","text-anchor":"middle","dominant-baseline":"middle"}},[t._v(t._s((new Date).getDate()))])])])],2),t._v(" "),t.showClearIcon?n("span",{staticClass:"mx-input-append mx-clear-wrapper",on:{click:function(e){return e.stopPropagation(),t.clearDate(e)}}},[t._t("mx-clear-icon",[n("i",{staticClass:"mx-input-icon mx-clear-icon"})])],2):t._e()]),t._v(" "),n("div",{directives:[{name:"show",rawName:"v-show",value:t.popupVisible,expression:"popupVisible"}],ref:"calendar",staticClass:"mx-datepicker-popup",style:t.innerPopupStyle,on:{click:function(t){t.stopPropagation(),t.preventDefault()}}},[t._t("header",[t.range&&t.innerShortcuts.length?n("div",{staticClass:"mx-shortcuts-wrapper"},t._l(t.innerShortcuts,function(e,i){return n("button",{key:i,staticClass:"mx-shortcuts",attrs:{type:"button"},on:{click:function(n){t.selectRange(e)}}},[t._v(t._s(e.text))])})):t._e()]),t._v(" "),t.range?n("div",{staticClass:"mx-range-wrapper"},[n("calendar-panel",t._b({ref:"calendarPanel",staticStyle:{"box-shadow":"1px 0 rgba(0, 0, 0, .1)"},attrs:{type:t.innerType,"date-format":t.innerDateFormat,value:t.currentValue[0],"end-at":t.currentValue[1],"start-at":null,visible:t.popupVisible},on:{"select-date":t.selectStartDate,"select-time":t.selectStartTime}},"calendar-panel",t.$attrs,!1)),t._v(" "),n("calendar-panel",t._b({attrs:{type:t.innerType,"date-format":t.innerDateFormat,value:t.currentValue[1],"start-at":t.currentValue[0],"end-at":null,visible:t.popupVisible},on:{"select-date":t.selectEndDate,"select-time":t.selectEndTime}},"calendar-panel",t.$attrs,!1))],1):n("calendar-panel",t._b({ref:"calendarPanel",attrs:{type:t.innerType,"date-format":t.innerDateFormat,value:t.currentValue,visible:t.popupVisible},on:{"select-date":t.selectDate,"select-time":t.selectTime}},"calendar-panel",t.$attrs,!1)),t._v(" "),t._t("footer",[t.confirm?n("div",{staticClass:"mx-datepicker-footer"},[n("button",{staticClass:"mx-datepicker-btn mx-datepicker-btn-confirm",attrs:{type:"button"},on:{click:t.confirmDate}},[t._v(t._s(t.confirmText))])]):t._e()],{confirm:t.confirmDate})],2)])},[],!1,null,null,null).exports;n(7),x.install=function(t){t.component(x.name,x)},"undefined"!=typeof window&&window.Vue&&x.install(window.Vue),e.default=x},function(t,e){t.exports=function(){var t=[];return t.toString=function(){for(var t=[],e=0;e<this.length;e++){var n=this[e];n[2]?t.push("@media "+n[2]+"{"+n[1]+"}"):t.push(n[1])}return t.join("")},t.i=function(e,n){"string"==typeof e&&(e=[[null,e,""]]);for(var i={},r=0;r<this.length;r++){var o=this[r][0];"number"==typeof o&&(i[o]=!0)}for(r=0;r<e.length;r++){var a=e[r];"number"==typeof a[0]&&i[a[0]]||(n&&!a[2]?a[2]=n:n&&(a[2]="("+a[2]+") and ("+n+")"),t.push(a))}},t}},,function(t,e,n){(t.exports=n(4)()).push([t.i,"",""])},function(t,e,n){var i=n(6);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals),(0,n(2).default)("4ff1a776",i,!0,{})}])},function(t,e,n){"use strict";(function(e){var i=n(0),r=n(35),o={"Content-Type":"application/x-www-form-urlencoded"};function a(t,e){!i.isUndefined(t)&&i.isUndefined(t["Content-Type"])&&(t["Content-Type"]=e)}var s,l={adapter:("undefined"!=typeof XMLHttpRequest?s=n(13):void 0!==e&&(s=n(13)),s),transformRequest:[function(t,e){return r(e,"Content-Type"),i.isFormData(t)||i.isArrayBuffer(t)||i.isBuffer(t)||i.isStream(t)||i.isFile(t)||i.isBlob(t)?t:i.isArrayBufferView(t)?t.buffer:i.isURLSearchParams(t)?(a(e,"application/x-www-form-urlencoded;charset=utf-8"),t.toString()):i.isObject(t)?(a(e,"application/json;charset=utf-8"),JSON.stringify(t)):t}],transformResponse:[function(t){if("string"==typeof t)try{t=JSON.parse(t)}catch(t){}return t}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,validateStatus:function(t){return t>=200&&t<300}};l.headers={common:{Accept:"application/json, text/plain, */*"}},i.forEach(["delete","get","head"],function(t){l.headers[t]={}}),i.forEach(["post","put","patch"],function(t){l.headers[t]=i.merge(o)}),t.exports=l}).call(this,n(34))},function(t,e,n){"use strict";t.exports=function(t,e){return function(){for(var n=new Array(arguments.length),i=0;i<n.length;i++)n[i]=arguments[i];return t.apply(e,n)}}},function(t,e){function n(t){return!!t.constructor&&"function"==typeof t.constructor.isBuffer&&t.constructor.isBuffer(t)}
/*!
 * Determine if an object is a Buffer
 *
 * @author   Feross Aboukhadijeh <https://feross.org>
 * @license  MIT
 */
t.exports=function(t){return null!=t&&(n(t)||function(t){return"function"==typeof t.readFloatLE&&"function"==typeof t.slice&&n(t.slice(0,0))}(t)||!!t._isBuffer)}},function(t,e,n){"use strict";var i=n(0),r=n(36),o=n(38),a=n(39),s=n(40),l=n(14),u="undefined"!=typeof window&&window.btoa&&window.btoa.bind(window)||n(41);t.exports=function(t){return new Promise(function(e,c){var p=t.data,d=t.headers;i.isFormData(p)&&delete d["Content-Type"];var A=new XMLHttpRequest,f="onreadystatechange",h=!1;if("undefined"==typeof window||!window.XDomainRequest||"withCredentials"in A||s(t.url)||(A=new window.XDomainRequest,f="onload",h=!0,A.onprogress=function(){},A.ontimeout=function(){}),t.auth){var m=t.auth.username||"",v=t.auth.password||"";d.Authorization="Basic "+u(m+":"+v)}if(A.open(t.method.toUpperCase(),o(t.url,t.params,t.paramsSerializer),!0),A.timeout=t.timeout,A[f]=function(){if(A&&(4===A.readyState||h)&&(0!==A.status||A.responseURL&&0===A.responseURL.indexOf("file:"))){var n="getAllResponseHeaders"in A?a(A.getAllResponseHeaders()):null,i={data:t.responseType&&"text"!==t.responseType?A.response:A.responseText,status:1223===A.status?204:A.status,statusText:1223===A.status?"No Content":A.statusText,headers:n,config:t,request:A};r(e,c,i),A=null}},A.onerror=function(){c(l("Network Error",t,null,A)),A=null},A.ontimeout=function(){c(l("timeout of "+t.timeout+"ms exceeded",t,"ECONNABORTED",A)),A=null},i.isStandardBrowserEnv()){var g=n(42),y=(t.withCredentials||s(t.url))&&t.xsrfCookieName?g.read(t.xsrfCookieName):void 0;y&&(d[t.xsrfHeaderName]=y)}if("setRequestHeader"in A&&i.forEach(d,function(t,e){void 0===p&&"content-type"===e.toLowerCase()?delete d[e]:A.setRequestHeader(e,t)}),t.withCredentials&&(A.withCredentials=!0),t.responseType)try{A.responseType=t.responseType}catch(e){if("json"!==t.responseType)throw e}"function"==typeof t.onDownloadProgress&&A.addEventListener("progress",t.onDownloadProgress),"function"==typeof t.onUploadProgress&&A.upload&&A.upload.addEventListener("progress",t.onUploadProgress),t.cancelToken&&t.cancelToken.promise.then(function(t){A&&(A.abort(),c(t),A=null)}),void 0===p&&(p=null),A.send(p)})}},function(t,e,n){"use strict";var i=n(37);t.exports=function(t,e,n,r,o){var a=new Error(t);return i(a,e,n,r,o)}},function(t,e,n){"use strict";t.exports=function(t){return!(!t||!t.__CANCEL__)}},function(t,e,n){"use strict";function i(t){this.message=t}i.prototype.toString=function(){return"Cancel"+(this.message?": "+this.message:"")},i.prototype.__CANCEL__=!0,t.exports=i},function(t,e){var n={utf8:{stringToBytes:function(t){return n.bin.stringToBytes(unescape(encodeURIComponent(t)))},bytesToString:function(t){return decodeURIComponent(escape(n.bin.bytesToString(t)))}},bin:{stringToBytes:function(t){for(var e=[],n=0;n<t.length;n++)e.push(255&t.charCodeAt(n));return e},bytesToString:function(t){for(var e=[],n=0;n<t.length;n++)e.push(String.fromCharCode(t[n]));return e.join("")}}};t.exports=n},function(t,e,n){t.exports=function(t){function e(i){if(n[i])return n[i].exports;var r=n[i]={i:i,l:!1,exports:{}};return t[i].call(r.exports,r,r.exports,e),r.l=!0,r.exports}var n={};return e.m=t,e.c=n,e.i=function(t){return t},e.d=function(t,n,i){e.o(t,n)||Object.defineProperty(t,n,{configurable:!1,enumerable:!0,get:i})},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="/",e(e.s=60)}([function(t,e){var n=t.exports="undefined"!=typeof window&&window.Math==Math?window:"undefined"!=typeof self&&self.Math==Math?self:Function("return this")();"number"==typeof __g&&(__g=n)},function(t,e,n){var i=n(49)("wks"),r=n(30),o=n(0).Symbol,a="function"==typeof o;(t.exports=function(t){return i[t]||(i[t]=a&&o[t]||(a?o:r)("Symbol."+t))}).store=i},function(t,e,n){var i=n(5);t.exports=function(t){if(!i(t))throw TypeError(t+" is not an object!");return t}},function(t,e,n){var i=n(0),r=n(10),o=n(8),a=n(6),s=n(11),l=function(t,e,n){var u,c,p,d,A=t&l.F,f=t&l.G,h=t&l.S,m=t&l.P,v=t&l.B,g=f?i:h?i[e]||(i[e]={}):(i[e]||{}).prototype,y=f?r:r[e]||(r[e]={}),b=y.prototype||(y.prototype={});for(u in f&&(n=e),n)c=!A&&g&&void 0!==g[u],p=(c?g:n)[u],d=v&&c?s(p,i):m&&"function"==typeof p?s(Function.call,p):p,g&&a(g,u,p,t&l.U),y[u]!=p&&o(y,u,d),m&&b[u]!=p&&(b[u]=p)};i.core=r,l.F=1,l.G=2,l.S=4,l.P=8,l.B=16,l.W=32,l.U=64,l.R=128,t.exports=l},function(t,e,n){t.exports=!n(7)(function(){return 7!=Object.defineProperty({},"a",{get:function(){return 7}}).a})},function(t,e){t.exports=function(t){return"object"==typeof t?null!==t:"function"==typeof t}},function(t,e,n){var i=n(0),r=n(8),o=n(12),a=n(30)("src"),s=Function.toString,l=(""+s).split("toString");n(10).inspectSource=function(t){return s.call(t)},(t.exports=function(t,e,n,s){var u="function"==typeof n;u&&(o(n,"name")||r(n,"name",e)),t[e]!==n&&(u&&(o(n,a)||r(n,a,t[e]?""+t[e]:l.join(String(e)))),t===i?t[e]=n:s?t[e]?t[e]=n:r(t,e,n):(delete t[e],r(t,e,n)))})(Function.prototype,"toString",function(){return"function"==typeof this&&this[a]||s.call(this)})},function(t,e){t.exports=function(t){try{return!!t()}catch(t){return!0}}},function(t,e,n){var i=n(13),r=n(25);t.exports=n(4)?function(t,e,n){return i.f(t,e,r(1,n))}:function(t,e,n){return t[e]=n,t}},function(t,e){var n={}.toString;t.exports=function(t){return n.call(t).slice(8,-1)}},function(t,e){var n=t.exports={version:"2.5.7"};"number"==typeof __e&&(__e=n)},function(t,e,n){var i=n(14);t.exports=function(t,e,n){if(i(t),void 0===e)return t;switch(n){case 1:return function(n){return t.call(e,n)};case 2:return function(n,i){return t.call(e,n,i)};case 3:return function(n,i,r){return t.call(e,n,i,r)}}return function(){return t.apply(e,arguments)}}},function(t,e){var n={}.hasOwnProperty;t.exports=function(t,e){return n.call(t,e)}},function(t,e,n){var i=n(2),r=n(41),o=n(29),a=Object.defineProperty;e.f=n(4)?Object.defineProperty:function(t,e,n){if(i(t),e=o(e,!0),i(n),r)try{return a(t,e,n)}catch(t){}if("get"in n||"set"in n)throw TypeError("Accessors not supported!");return"value"in n&&(t[e]=n.value),t}},function(t,e){t.exports=function(t){if("function"!=typeof t)throw TypeError(t+" is not a function!");return t}},function(t,e){t.exports={}},function(t,e){t.exports=function(t){if(null==t)throw TypeError("Can't call method on  "+t);return t}},function(t,e,n){"use strict";var i=n(7);t.exports=function(t,e){return!!t&&i(function(){e?t.call(null,function(){},1):t.call(null)})}},function(t,e,n){var i=n(23),r=n(16);t.exports=function(t){return i(r(t))}},function(t,e,n){var i=n(53),r=Math.min;t.exports=function(t){return t>0?r(i(t),9007199254740991):0}},function(t,e,n){var i=n(11),r=n(23),o=n(28),a=n(19),s=n(64);t.exports=function(t,e){var n=1==t,l=2==t,u=3==t,c=4==t,p=6==t,d=5==t||p,A=e||s;return function(e,s,f){for(var h,m,v=o(e),g=r(v),y=i(s,f,3),b=a(g.length),w=0,_=n?A(e,b):l?A(e,0):void 0;b>w;w++)if((d||w in g)&&(h=g[w],m=y(h,w,v),t))if(n)_[w]=m;else if(m)switch(t){case 3:return!0;case 5:return h;case 6:return w;case 2:_.push(h)}else if(c)return!1;return p?-1:u||c?c:_}}},function(t,e,n){var i=n(5),r=n(0).document,o=i(r)&&i(r.createElement);t.exports=function(t){return o?r.createElement(t):{}}},function(t,e){t.exports="constructor,hasOwnProperty,isPrototypeOf,propertyIsEnumerable,toLocaleString,toString,valueOf".split(",")},function(t,e,n){var i=n(9);t.exports=Object("z").propertyIsEnumerable(0)?Object:function(t){return"String"==i(t)?t.split(""):Object(t)}},function(t,e){t.exports=!1},function(t,e){t.exports=function(t,e){return{enumerable:!(1&t),configurable:!(2&t),writable:!(4&t),value:e}}},function(t,e,n){var i=n(13).f,r=n(12),o=n(1)("toStringTag");t.exports=function(t,e,n){t&&!r(t=n?t:t.prototype,o)&&i(t,o,{configurable:!0,value:e})}},function(t,e,n){var i=n(49)("keys"),r=n(30);t.exports=function(t){return i[t]||(i[t]=r(t))}},function(t,e,n){var i=n(16);t.exports=function(t){return Object(i(t))}},function(t,e,n){var i=n(5);t.exports=function(t,e){if(!i(t))return t;var n,r;if(e&&"function"==typeof(n=t.toString)&&!i(r=n.call(t)))return r;if("function"==typeof(n=t.valueOf)&&!i(r=n.call(t)))return r;if(!e&&"function"==typeof(n=t.toString)&&!i(r=n.call(t)))return r;throw TypeError("Can't convert object to primitive value")}},function(t,e){var n=0,i=Math.random();t.exports=function(t){return"Symbol(".concat(void 0===t?"":t,")_",(++n+i).toString(36))}},function(t,e,n){"use strict";var i=n(0),r=n(12),o=n(9),a=n(67),s=n(29),l=n(7),u=n(77).f,c=n(45).f,p=n(13).f,d=n(51).trim,A=i.Number,f=A,h=A.prototype,m="Number"==o(n(44)(h)),v="trim"in String.prototype,g=function(t){var e=s(t,!1);if("string"==typeof e&&e.length>2){var n,i,r,o=(e=v?e.trim():d(e,3)).charCodeAt(0);if(43===o||45===o){if(88===(n=e.charCodeAt(2))||120===n)return NaN}else if(48===o){switch(e.charCodeAt(1)){case 66:case 98:i=2,r=49;break;case 79:case 111:i=8,r=55;break;default:return+e}for(var a,l=e.slice(2),u=0,c=l.length;u<c;u++)if((a=l.charCodeAt(u))<48||a>r)return NaN;return parseInt(l,i)}}return+e};if(!A(" 0o1")||!A("0b1")||A("+0x1")){A=function(t){var e=arguments.length<1?0:t,n=this;return n instanceof A&&(m?l(function(){h.valueOf.call(n)}):"Number"!=o(n))?a(new f(g(e)),n,A):g(e)};for(var y,b=n(4)?u(f):"MAX_VALUE,MIN_VALUE,NaN,NEGATIVE_INFINITY,POSITIVE_INFINITY,EPSILON,isFinite,isInteger,isNaN,isSafeInteger,MAX_SAFE_INTEGER,MIN_SAFE_INTEGER,parseFloat,parseInt,isInteger".split(","),w=0;b.length>w;w++)r(f,y=b[w])&&!r(A,y)&&p(A,y,c(f,y));A.prototype=h,h.constructor=A,n(6)(i,"Number",A)}},function(t,e,n){"use strict";function i(t){return!(0===t||(!Array.isArray(t)||0!==t.length)&&t)}function r(t){return function(){return!t.apply(void 0,arguments)}}function o(t,e,n,i){return t.filter(function(t){return function(t,e){return void 0===t&&(t="undefined"),null===t&&(t="null"),!1===t&&(t="false"),-1!==t.toString().toLowerCase().indexOf(e.trim())}(i(t,n),e)})}function a(t){return t.filter(function(t){return!t.$isLabel})}function s(t,e){return function(n){return n.reduce(function(n,i){return i[t]&&i[t].length?(n.push({$groupLabel:i[e],$isLabel:!0}),n.concat(i[t])):n},[])}}function l(t,e,i,r,a){return function(s){return s.map(function(s){var l;if(!s[i])return console.warn("Options passed to vue-multiselect do not contain groups, despite the config."),[];var u=o(s[i],t,e,a);return u.length?(l={},n.i(A.a)(l,r,s[r]),n.i(A.a)(l,i,u),l):[]})}}var u=n(59),c=n(54),p=(n.n(c),n(95)),d=(n.n(p),n(31)),A=(n.n(d),n(58)),f=n(91),h=(n.n(f),n(98)),m=(n.n(h),n(92)),v=(n.n(m),n(88)),g=(n.n(v),n(97)),y=(n.n(g),n(89)),b=(n.n(y),n(96)),w=(n.n(b),n(93)),_=(n.n(w),n(90)),x=(n.n(_),function(){for(var t=arguments.length,e=new Array(t),n=0;n<t;n++)e[n]=arguments[n];return function(t){return e.reduce(function(t,e){return e(t)},t)}});e.a={data:function(){return{search:"",isOpen:!1,prefferedOpenDirection:"below",optimizedHeight:this.maxHeight}},props:{internalSearch:{type:Boolean,default:!0},options:{type:Array,required:!0},multiple:{type:Boolean,default:!1},value:{type:null,default:function(){return[]}},trackBy:{type:String},label:{type:String},searchable:{type:Boolean,default:!0},clearOnSelect:{type:Boolean,default:!0},hideSelected:{type:Boolean,default:!1},placeholder:{type:String,default:"Select option"},allowEmpty:{type:Boolean,default:!0},resetAfter:{type:Boolean,default:!1},closeOnSelect:{type:Boolean,default:!0},customLabel:{type:Function,default:function(t,e){return i(t)?"":e?t[e]:t}},taggable:{type:Boolean,default:!1},tagPlaceholder:{type:String,default:"Press enter to create a tag"},tagPosition:{type:String,default:"top"},max:{type:[Number,Boolean],default:!1},id:{default:null},optionsLimit:{type:Number,default:1e3},groupValues:{type:String},groupLabel:{type:String},groupSelect:{type:Boolean,default:!1},blockKeys:{type:Array,default:function(){return[]}},preserveSearch:{type:Boolean,default:!1},preselectFirst:{type:Boolean,default:!1}},mounted:function(){this.multiple||this.clearOnSelect||console.warn("[Vue-Multiselect warn]: ClearOnSelect and Multiple props can’t be both set to false."),!this.multiple&&this.max&&console.warn("[Vue-Multiselect warn]: Max prop should not be used when prop Multiple equals false."),this.preselectFirst&&!this.internalValue.length&&this.options.length&&this.select(this.filteredOptions[0])},computed:{internalValue:function(){return this.value||0===this.value?Array.isArray(this.value)?this.value:[this.value]:[]},filteredOptions:function(){var t=this.search||"",e=t.toLowerCase().trim(),n=this.options.concat();return n=this.internalSearch?this.groupValues?this.filterAndFlat(n,e,this.label):o(n,e,this.label,this.customLabel):this.groupValues?s(this.groupValues,this.groupLabel)(n):n,n=this.hideSelected?n.filter(r(this.isSelected)):n,this.taggable&&e.length&&!this.isExistingOption(e)&&("bottom"===this.tagPosition?n.push({isTag:!0,label:t}):n.unshift({isTag:!0,label:t})),n.slice(0,this.optionsLimit)},valueKeys:function(){var t=this;return this.trackBy?this.internalValue.map(function(e){return e[t.trackBy]}):this.internalValue},optionKeys:function(){var t=this;return(this.groupValues?this.flatAndStrip(this.options):this.options).map(function(e){return t.customLabel(e,t.label).toString().toLowerCase()})},currentOptionLabel:function(){return this.multiple?this.searchable?"":this.placeholder:this.internalValue.length?this.getOptionLabel(this.internalValue[0]):this.searchable?"":this.placeholder}},watch:{internalValue:function(){this.resetAfter&&this.internalValue.length&&(this.search="",this.$emit("input",this.multiple?[]:null))},search:function(){this.$emit("search-change",this.search,this.id)}},methods:{getValue:function(){return this.multiple?this.internalValue:0===this.internalValue.length?null:this.internalValue[0]},filterAndFlat:function(t,e,n){return x(l(e,n,this.groupValues,this.groupLabel,this.customLabel),s(this.groupValues,this.groupLabel))(t)},flatAndStrip:function(t){return x(s(this.groupValues,this.groupLabel),a)(t)},updateSearch:function(t){this.search=t},isExistingOption:function(t){return!!this.options&&this.optionKeys.indexOf(t)>-1},isSelected:function(t){var e=this.trackBy?t[this.trackBy]:t;return this.valueKeys.indexOf(e)>-1},getOptionLabel:function(t){if(i(t))return"";if(t.isTag)return t.label;if(t.$isLabel)return t.$groupLabel;var e=this.customLabel(t,this.label);return i(e)?"":e},select:function(t,e){if(t.$isLabel&&this.groupSelect)this.selectGroup(t);else if(!(-1!==this.blockKeys.indexOf(e)||this.disabled||t.$isDisabled||t.$isLabel)&&(!this.max||!this.multiple||this.internalValue.length!==this.max)&&("Tab"!==e||this.pointerDirty)){if(t.isTag)this.$emit("tag",t.label,this.id),this.search="",this.closeOnSelect&&!this.multiple&&this.deactivate();else{if(this.isSelected(t))return void("Tab"!==e&&this.removeElement(t));this.$emit("select",t,this.id),this.multiple?this.$emit("input",this.internalValue.concat([t]),this.id):this.$emit("input",t,this.id),this.clearOnSelect&&(this.search="")}this.closeOnSelect&&this.deactivate()}},selectGroup:function(t){var e=this,n=this.options.find(function(n){return n[e.groupLabel]===t.$groupLabel});if(n)if(this.wholeGroupSelected(n)){this.$emit("remove",n[this.groupValues],this.id);var i=this.internalValue.filter(function(t){return-1===n[e.groupValues].indexOf(t)});this.$emit("input",i,this.id)}else{var o=n[this.groupValues].filter(r(this.isSelected));this.$emit("select",o,this.id),this.$emit("input",this.internalValue.concat(o),this.id)}},wholeGroupSelected:function(t){return t[this.groupValues].every(this.isSelected)},removeElement:function(t){var e=!(arguments.length>1&&void 0!==arguments[1])||arguments[1];if(!this.disabled){if(!this.allowEmpty&&this.internalValue.length<=1)return void this.deactivate();var i="object"===n.i(u.a)(t)?this.valueKeys.indexOf(t[this.trackBy]):this.valueKeys.indexOf(t);if(this.$emit("remove",t,this.id),this.multiple){var r=this.internalValue.slice(0,i).concat(this.internalValue.slice(i+1));this.$emit("input",r,this.id)}else this.$emit("input",null,this.id);this.closeOnSelect&&e&&this.deactivate()}},removeLastElement:function(){-1===this.blockKeys.indexOf("Delete")&&0===this.search.length&&Array.isArray(this.internalValue)&&this.removeElement(this.internalValue[this.internalValue.length-1],!1)},activate:function(){var t=this;this.isOpen||this.disabled||(this.adjustPosition(),this.groupValues&&0===this.pointer&&this.filteredOptions.length&&(this.pointer=1),this.isOpen=!0,this.searchable?(this.preserveSearch||(this.search=""),this.$nextTick(function(){return t.$refs.search.focus()})):this.$el.focus(),this.$emit("open",this.id))},deactivate:function(){this.isOpen&&(this.isOpen=!1,this.searchable?this.$refs.search.blur():this.$el.blur(),this.preserveSearch||(this.search=""),this.$emit("close",this.getValue(),this.id))},toggle:function(){this.isOpen?this.deactivate():this.activate()},adjustPosition:function(){if("undefined"!=typeof window){var t=this.$el.getBoundingClientRect().top,e=window.innerHeight-this.$el.getBoundingClientRect().bottom;e>this.maxHeight||e>t||"below"===this.openDirection||"bottom"===this.openDirection?(this.prefferedOpenDirection="below",this.optimizedHeight=Math.min(e-40,this.maxHeight)):(this.prefferedOpenDirection="above",this.optimizedHeight=Math.min(t-40,this.maxHeight))}}}}},function(t,e,n){"use strict";var i=n(54),r=(n.n(i),n(31));n.n(r),e.a={data:function(){return{pointer:0,pointerDirty:!1}},props:{showPointer:{type:Boolean,default:!0},optionHeight:{type:Number,default:40}},computed:{pointerPosition:function(){return this.pointer*this.optionHeight},visibleElements:function(){return this.optimizedHeight/this.optionHeight}},watch:{filteredOptions:function(){this.pointerAdjust()},isOpen:function(){this.pointerDirty=!1}},methods:{optionHighlight:function(t,e){return{"multiselect__option--highlight":t===this.pointer&&this.showPointer,"multiselect__option--selected":this.isSelected(e)}},groupHighlight:function(t,e){var n=this;if(!this.groupSelect)return["multiselect__option--group","multiselect__option--disabled"];var i=this.options.find(function(t){return t[n.groupLabel]===e.$groupLabel});return["multiselect__option--group",{"multiselect__option--highlight":t===this.pointer&&this.showPointer},{"multiselect__option--group-selected":this.wholeGroupSelected(i)}]},addPointerElement:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"Enter",e=t.key;this.filteredOptions.length>0&&this.select(this.filteredOptions[this.pointer],e),this.pointerReset()},pointerForward:function(){this.pointer<this.filteredOptions.length-1&&(this.pointer++,this.$refs.list.scrollTop<=this.pointerPosition-(this.visibleElements-1)*this.optionHeight&&(this.$refs.list.scrollTop=this.pointerPosition-(this.visibleElements-1)*this.optionHeight),this.filteredOptions[this.pointer]&&this.filteredOptions[this.pointer].$isLabel&&!this.groupSelect&&this.pointerForward()),this.pointerDirty=!0},pointerBackward:function(){this.pointer>0?(this.pointer--,this.$refs.list.scrollTop>=this.pointerPosition&&(this.$refs.list.scrollTop=this.pointerPosition),this.filteredOptions[this.pointer]&&this.filteredOptions[this.pointer].$isLabel&&!this.groupSelect&&this.pointerBackward()):this.filteredOptions[this.pointer]&&this.filteredOptions[0].$isLabel&&!this.groupSelect&&this.pointerForward(),this.pointerDirty=!0},pointerReset:function(){this.closeOnSelect&&(this.pointer=0,this.$refs.list&&(this.$refs.list.scrollTop=0))},pointerAdjust:function(){this.pointer>=this.filteredOptions.length-1&&(this.pointer=this.filteredOptions.length?this.filteredOptions.length-1:0),this.filteredOptions.length>0&&this.filteredOptions[this.pointer].$isLabel&&!this.groupSelect&&this.pointerForward()},pointerSet:function(t){this.pointer=t,this.pointerDirty=!0}}}},function(t,e,n){"use strict";var i=n(36),r=n(74),o=n(15),a=n(18);t.exports=n(72)(Array,"Array",function(t,e){this._t=a(t),this._i=0,this._k=e},function(){var t=this._t,e=this._k,n=this._i++;return!t||n>=t.length?(this._t=void 0,r(1)):r(0,"keys"==e?n:"values"==e?t[n]:[n,t[n]])},"values"),o.Arguments=o.Array,i("keys"),i("values"),i("entries")},function(t,e,n){"use strict";var i=n(31),r=(n.n(i),n(32)),o=n(33);e.a={name:"vue-multiselect",mixins:[r.a,o.a],props:{name:{type:String,default:""},selectLabel:{type:String,default:"Press enter to select"},selectGroupLabel:{type:String,default:"Press enter to select group"},selectedLabel:{type:String,default:"Selected"},deselectLabel:{type:String,default:"Press enter to remove"},deselectGroupLabel:{type:String,default:"Press enter to deselect group"},showLabels:{type:Boolean,default:!0},limit:{type:Number,default:99999},maxHeight:{type:Number,default:300},limitText:{type:Function,default:function(t){return"and ".concat(t," more")}},loading:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},openDirection:{type:String,default:""},showNoOptions:{type:Boolean,default:!0},showNoResults:{type:Boolean,default:!0},tabindex:{type:Number,default:0}},computed:{isSingleLabelVisible:function(){return this.singleValue&&(!this.isOpen||!this.searchable)&&!this.visibleValues.length},isPlaceholderVisible:function(){return!(this.internalValue.length||this.searchable&&this.isOpen)},visibleValues:function(){return this.multiple?this.internalValue.slice(0,this.limit):[]},singleValue:function(){return this.internalValue[0]},deselectLabelText:function(){return this.showLabels?this.deselectLabel:""},deselectGroupLabelText:function(){return this.showLabels?this.deselectGroupLabel:""},selectLabelText:function(){return this.showLabels?this.selectLabel:""},selectGroupLabelText:function(){return this.showLabels?this.selectGroupLabel:""},selectedLabelText:function(){return this.showLabels?this.selectedLabel:""},inputStyle:function(){if(this.searchable||this.multiple&&this.value&&this.value.length)return this.isOpen?{width:"auto"}:{width:"0",position:"absolute",padding:"0"}},contentStyle:function(){return this.options.length?{display:"inline-block"}:{display:"block"}},isAbove:function(){return"above"===this.openDirection||"top"===this.openDirection||"below"!==this.openDirection&&"bottom"!==this.openDirection&&"above"===this.prefferedOpenDirection},showSearchInput:function(){return this.searchable&&(!this.hasSingleSelectedSlot||!this.visibleSingleValue&&0!==this.visibleSingleValue||this.isOpen)}}}},function(t,e,n){var i=n(1)("unscopables"),r=Array.prototype;null==r[i]&&n(8)(r,i,{}),t.exports=function(t){r[i][t]=!0}},function(t,e,n){var i=n(18),r=n(19),o=n(85);t.exports=function(t){return function(e,n,a){var s,l=i(e),u=r(l.length),c=o(a,u);if(t&&n!=n){for(;u>c;)if((s=l[c++])!=s)return!0}else for(;u>c;c++)if((t||c in l)&&l[c]===n)return t||c||0;return!t&&-1}}},function(t,e,n){var i=n(9),r=n(1)("toStringTag"),o="Arguments"==i(function(){return arguments}());t.exports=function(t){var e,n,a;return void 0===t?"Undefined":null===t?"Null":"string"==typeof(n=function(t,e){try{return t[e]}catch(t){}}(e=Object(t),r))?n:o?i(e):"Object"==(a=i(e))&&"function"==typeof e.callee?"Arguments":a}},function(t,e,n){"use strict";var i=n(2);t.exports=function(){var t=i(this),e="";return t.global&&(e+="g"),t.ignoreCase&&(e+="i"),t.multiline&&(e+="m"),t.unicode&&(e+="u"),t.sticky&&(e+="y"),e}},function(t,e,n){var i=n(0).document;t.exports=i&&i.documentElement},function(t,e,n){t.exports=!n(4)&&!n(7)(function(){return 7!=Object.defineProperty(n(21)("div"),"a",{get:function(){return 7}}).a})},function(t,e,n){var i=n(9);t.exports=Array.isArray||function(t){return"Array"==i(t)}},function(t,e,n){"use strict";function i(t){var e,n;this.promise=new t(function(t,i){if(void 0!==e||void 0!==n)throw TypeError("Bad Promise constructor");e=t,n=i}),this.resolve=r(e),this.reject=r(n)}var r=n(14);t.exports.f=function(t){return new i(t)}},function(t,e,n){var i=n(2),r=n(76),o=n(22),a=n(27)("IE_PROTO"),s=function(){},l=function(){var t,e=n(21)("iframe"),i=o.length;for(e.style.display="none",n(40).appendChild(e),e.src="javascript:",(t=e.contentWindow.document).open(),t.write("<script>document.F=Object<\/script>"),t.close(),l=t.F;i--;)delete l.prototype[o[i]];return l()};t.exports=Object.create||function(t,e){var n;return null!==t?(s.prototype=i(t),n=new s,s.prototype=null,n[a]=t):n=l(),void 0===e?n:r(n,e)}},function(t,e,n){var i=n(79),r=n(25),o=n(18),a=n(29),s=n(12),l=n(41),u=Object.getOwnPropertyDescriptor;e.f=n(4)?u:function(t,e){if(t=o(t),e=a(e,!0),l)try{return u(t,e)}catch(t){}if(s(t,e))return r(!i.f.call(t,e),t[e])}},function(t,e,n){var i=n(12),r=n(18),o=n(37)(!1),a=n(27)("IE_PROTO");t.exports=function(t,e){var n,s=r(t),l=0,u=[];for(n in s)n!=a&&i(s,n)&&u.push(n);for(;e.length>l;)i(s,n=e[l++])&&(~o(u,n)||u.push(n));return u}},function(t,e,n){var i=n(46),r=n(22);t.exports=Object.keys||function(t){return i(t,r)}},function(t,e,n){var i=n(2),r=n(5),o=n(43);t.exports=function(t,e){if(i(t),r(e)&&e.constructor===t)return e;var n=o.f(t);return(0,n.resolve)(e),n.promise}},function(t,e,n){var i=n(10),r=n(0),o=r["__core-js_shared__"]||(r["__core-js_shared__"]={});(t.exports=function(t,e){return o[t]||(o[t]=void 0!==e?e:{})})("versions",[]).push({version:i.version,mode:n(24)?"pure":"global",copyright:"© 2018 Denis Pushkarev (zloirock.ru)"})},function(t,e,n){var i=n(2),r=n(14),o=n(1)("species");t.exports=function(t,e){var n,a=i(t).constructor;return void 0===a||null==(n=i(a)[o])?e:r(n)}},function(t,e,n){var i=n(3),r=n(16),o=n(7),a=n(84),s="["+a+"]",l=RegExp("^"+s+s+"*"),u=RegExp(s+s+"*$"),c=function(t,e,n){var r={},s=o(function(){return!!a[t]()||"​"!="​"[t]()}),l=r[t]=s?e(p):a[t];n&&(r[n]=l),i(i.P+i.F*s,"String",r)},p=c.trim=function(t,e){return t=String(r(t)),1&e&&(t=t.replace(l,"")),2&e&&(t=t.replace(u,"")),t};t.exports=c},function(t,e,n){var i,r,o,a=n(11),s=n(68),l=n(40),u=n(21),c=n(0),p=c.process,d=c.setImmediate,A=c.clearImmediate,f=c.MessageChannel,h=c.Dispatch,m=0,v={},g=function(){var t=+this;if(v.hasOwnProperty(t)){var e=v[t];delete v[t],e()}},y=function(t){g.call(t.data)};d&&A||(d=function(t){for(var e=[],n=1;arguments.length>n;)e.push(arguments[n++]);return v[++m]=function(){s("function"==typeof t?t:Function(t),e)},i(m),m},A=function(t){delete v[t]},"process"==n(9)(p)?i=function(t){p.nextTick(a(g,t,1))}:h&&h.now?i=function(t){h.now(a(g,t,1))}:f?(r=new f,o=r.port2,r.port1.onmessage=y,i=a(o.postMessage,o,1)):c.addEventListener&&"function"==typeof postMessage&&!c.importScripts?(i=function(t){c.postMessage(t+"","*")},c.addEventListener("message",y,!1)):i="onreadystatechange"in u("script")?function(t){l.appendChild(u("script")).onreadystatechange=function(){l.removeChild(this),g.call(t)}}:function(t){setTimeout(a(g,t,1),0)}),t.exports={set:d,clear:A}},function(t,e){var n=Math.ceil,i=Math.floor;t.exports=function(t){return isNaN(t=+t)?0:(t>0?i:n)(t)}},function(t,e,n){"use strict";var i=n(3),r=n(20)(5),o=!0;"find"in[]&&Array(1).find(function(){o=!1}),i(i.P+i.F*o,"Array",{find:function(t){return r(this,t,arguments.length>1?arguments[1]:void 0)}}),n(36)("find")},function(t,e,n){"use strict";var i,r,o,a,s=n(24),l=n(0),u=n(11),c=n(38),p=n(3),d=n(5),A=n(14),f=n(61),h=n(66),m=n(50),v=n(52).set,g=n(75)(),y=n(43),b=n(80),w=n(86),_=n(48),x=l.TypeError,D=l.process,C=D&&D.versions,E=C&&C.v8||"",k=l.Promise,S="process"==c(D),O=function(){},T=r=y.f,M=!!function(){try{var t=k.resolve(1),e=(t.constructor={})[n(1)("species")]=function(t){t(O,O)};return(S||"function"==typeof PromiseRejectionEvent)&&t.then(O)instanceof e&&0!==E.indexOf("6.6")&&-1===w.indexOf("Chrome/66")}catch(t){}}(),B=function(t){var e;return!(!d(t)||"function"!=typeof(e=t.then))&&e},N=function(t,e){if(!t._n){t._n=!0;var n=t._c;g(function(){for(var i=t._v,r=1==t._s,o=0;n.length>o;)!function(e){var n,o,a,s=r?e.ok:e.fail,l=e.resolve,u=e.reject,c=e.domain;try{s?(r||(2==t._h&&P(t),t._h=1),!0===s?n=i:(c&&c.enter(),n=s(i),c&&(c.exit(),a=!0)),n===e.promise?u(x("Promise-chain cycle")):(o=B(n))?o.call(n,l,u):l(n)):u(i)}catch(t){c&&!a&&c.exit(),u(t)}}(n[o++]);t._c=[],t._n=!1,e&&!t._h&&I(t)})}},I=function(t){v.call(l,function(){var e,n,i,r=t._v,o=L(t);if(o&&(e=b(function(){S?D.emit("unhandledRejection",r,t):(n=l.onunhandledrejection)?n({promise:t,reason:r}):(i=l.console)&&i.error&&i.error("Unhandled promise rejection",r)}),t._h=S||L(t)?2:1),t._a=void 0,o&&e.e)throw e.v})},L=function(t){return 1!==t._h&&0===(t._a||t._c).length},P=function(t){v.call(l,function(){var e;S?D.emit("rejectionHandled",t):(e=l.onrejectionhandled)&&e({promise:t,reason:t._v})})},j=function(t){var e=this;e._d||(e._d=!0,(e=e._w||e)._v=t,e._s=2,e._a||(e._a=e._c.slice()),N(e,!0))},$=function(t){var e,n=this;if(!n._d){n._d=!0,n=n._w||n;try{if(n===t)throw x("Promise can't be resolved itself");(e=B(t))?g(function(){var i={_w:n,_d:!1};try{e.call(t,u($,i,1),u(j,i,1))}catch(t){j.call(i,t)}}):(n._v=t,n._s=1,N(n,!1))}catch(t){j.call({_w:n,_d:!1},t)}}};M||(k=function(t){f(this,k,"Promise","_h"),A(t),i.call(this);try{t(u($,this,1),u(j,this,1))}catch(t){j.call(this,t)}},(i=function(t){this._c=[],this._a=void 0,this._s=0,this._d=!1,this._v=void 0,this._h=0,this._n=!1}).prototype=n(81)(k.prototype,{then:function(t,e){var n=T(m(this,k));return n.ok="function"!=typeof t||t,n.fail="function"==typeof e&&e,n.domain=S?D.domain:void 0,this._c.push(n),this._a&&this._a.push(n),this._s&&N(this,!1),n.promise},catch:function(t){return this.then(void 0,t)}}),o=function(){var t=new i;this.promise=t,this.resolve=u($,t,1),this.reject=u(j,t,1)},y.f=T=function(t){return t===k||t===a?new o(t):r(t)}),p(p.G+p.W+p.F*!M,{Promise:k}),n(26)(k,"Promise"),n(83)("Promise"),a=n(10).Promise,p(p.S+p.F*!M,"Promise",{reject:function(t){var e=T(this);return(0,e.reject)(t),e.promise}}),p(p.S+p.F*(s||!M),"Promise",{resolve:function(t){return _(s&&this===a?k:this,t)}}),p(p.S+p.F*!(M&&n(73)(function(t){k.all(t).catch(O)})),"Promise",{all:function(t){var e=this,n=T(e),i=n.resolve,r=n.reject,o=b(function(){var n=[],o=0,a=1;h(t,!1,function(t){var s=o++,l=!1;n.push(void 0),a++,e.resolve(t).then(function(t){l||(l=!0,n[s]=t,--a||i(n))},r)}),--a||i(n)});return o.e&&r(o.v),n.promise},race:function(t){var e=this,n=T(e),i=n.reject,r=b(function(){h(t,!1,function(t){e.resolve(t).then(n.resolve,i)})});return r.e&&i(r.v),n.promise}})},function(t,e,n){"use strict";var i=n(3),r=n(10),o=n(0),a=n(50),s=n(48);i(i.P+i.R,"Promise",{finally:function(t){var e=a(this,r.Promise||o.Promise),n="function"==typeof t;return this.then(n?function(n){return s(e,t()).then(function(){return n})}:t,n?function(n){return s(e,t()).then(function(){throw n})}:t)}})},function(t,e,n){"use strict";var i=n(35),r=n(101),o=n(100),a=function(t){n(99)},s=o(i.a,r.a,!1,a,null,null);e.a=s.exports},function(t,e,n){"use strict";e.a=function(t,e,n){return e in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}},function(t,e,n){"use strict";function i(t){return(i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function r(t){return(r="function"==typeof Symbol&&"symbol"===i(Symbol.iterator)?function(t){return i(t)}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":i(t)})(t)}e.a=r},function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i=n(34),r=(n.n(i),n(55)),o=(n.n(r),n(56)),a=(n.n(o),n(57)),s=n(32),l=n(33);n.d(e,"Multiselect",function(){return a.a}),n.d(e,"multiselectMixin",function(){return s.a}),n.d(e,"pointerMixin",function(){return l.a}),e.default=a.a},function(t,e){t.exports=function(t,e,n,i){if(!(t instanceof e)||void 0!==i&&i in t)throw TypeError(n+": incorrect invocation!");return t}},function(t,e,n){var i=n(14),r=n(28),o=n(23),a=n(19);t.exports=function(t,e,n,s,l){i(e);var u=r(t),c=o(u),p=a(u.length),d=l?p-1:0,A=l?-1:1;if(n<2)for(;;){if(d in c){s=c[d],d+=A;break}if(d+=A,l?d<0:p<=d)throw TypeError("Reduce of empty array with no initial value")}for(;l?d>=0:p>d;d+=A)d in c&&(s=e(s,c[d],d,u));return s}},function(t,e,n){var i=n(5),r=n(42),o=n(1)("species");t.exports=function(t){var e;return r(t)&&("function"!=typeof(e=t.constructor)||e!==Array&&!r(e.prototype)||(e=void 0),i(e)&&null===(e=e[o])&&(e=void 0)),void 0===e?Array:e}},function(t,e,n){var i=n(63);t.exports=function(t,e){return new(i(t))(e)}},function(t,e,n){"use strict";var i=n(8),r=n(6),o=n(7),a=n(16),s=n(1);t.exports=function(t,e,n){var l=s(t),u=n(a,l,""[t]),c=u[0],p=u[1];o(function(){var e={};return e[l]=function(){return 7},7!=""[t](e)})&&(r(String.prototype,t,c),i(RegExp.prototype,l,2==e?function(t,e){return p.call(t,this,e)}:function(t){return p.call(t,this)}))}},function(t,e,n){var i=n(11),r=n(70),o=n(69),a=n(2),s=n(19),l=n(87),u={},c={},e=t.exports=function(t,e,n,p,d){var A,f,h,m,v=d?function(){return t}:l(t),g=i(n,p,e?2:1),y=0;if("function"!=typeof v)throw TypeError(t+" is not iterable!");if(o(v)){for(A=s(t.length);A>y;y++)if((m=e?g(a(f=t[y])[0],f[1]):g(t[y]))===u||m===c)return m}else for(h=v.call(t);!(f=h.next()).done;)if((m=r(h,g,f.value,e))===u||m===c)return m};e.BREAK=u,e.RETURN=c},function(t,e,n){var i=n(5),r=n(82).set;t.exports=function(t,e,n){var o,a=e.constructor;return a!==n&&"function"==typeof a&&(o=a.prototype)!==n.prototype&&i(o)&&r&&r(t,o),t}},function(t,e){t.exports=function(t,e,n){var i=void 0===n;switch(e.length){case 0:return i?t():t.call(n);case 1:return i?t(e[0]):t.call(n,e[0]);case 2:return i?t(e[0],e[1]):t.call(n,e[0],e[1]);case 3:return i?t(e[0],e[1],e[2]):t.call(n,e[0],e[1],e[2]);case 4:return i?t(e[0],e[1],e[2],e[3]):t.call(n,e[0],e[1],e[2],e[3])}return t.apply(n,e)}},function(t,e,n){var i=n(15),r=n(1)("iterator"),o=Array.prototype;t.exports=function(t){return void 0!==t&&(i.Array===t||o[r]===t)}},function(t,e,n){var i=n(2);t.exports=function(t,e,n,r){try{return r?e(i(n)[0],n[1]):e(n)}catch(e){var o=t.return;throw void 0!==o&&i(o.call(t)),e}}},function(t,e,n){"use strict";var i=n(44),r=n(25),o=n(26),a={};n(8)(a,n(1)("iterator"),function(){return this}),t.exports=function(t,e,n){t.prototype=i(a,{next:r(1,n)}),o(t,e+" Iterator")}},function(t,e,n){"use strict";var i=n(24),r=n(3),o=n(6),a=n(8),s=n(15),l=n(71),u=n(26),c=n(78),p=n(1)("iterator"),d=!([].keys&&"next"in[].keys()),A=function(){return this};t.exports=function(t,e,n,f,h,m,v){l(n,e,f);var g,y,b,w=function(t){if(!d&&t in C)return C[t];switch(t){case"keys":case"values":return function(){return new n(this,t)}}return function(){return new n(this,t)}},_=e+" Iterator",x="values"==h,D=!1,C=t.prototype,E=C[p]||C["@@iterator"]||h&&C[h],k=E||w(h),S=h?x?w("entries"):k:void 0,O="Array"==e&&C.entries||E;if(O&&(b=c(O.call(new t)))!==Object.prototype&&b.next&&(u(b,_,!0),i||"function"==typeof b[p]||a(b,p,A)),x&&E&&"values"!==E.name&&(D=!0,k=function(){return E.call(this)}),i&&!v||!d&&!D&&C[p]||a(C,p,k),s[e]=k,s[_]=A,h)if(g={values:x?k:w("values"),keys:m?k:w("keys"),entries:S},v)for(y in g)y in C||o(C,y,g[y]);else r(r.P+r.F*(d||D),e,g);return g}},function(t,e,n){var i=n(1)("iterator"),r=!1;try{var o=[7][i]();o.return=function(){r=!0},Array.from(o,function(){throw 2})}catch(t){}t.exports=function(t,e){if(!e&&!r)return!1;var n=!1;try{var o=[7],a=o[i]();a.next=function(){return{done:n=!0}},o[i]=function(){return a},t(o)}catch(t){}return n}},function(t,e){t.exports=function(t,e){return{value:e,done:!!t}}},function(t,e,n){var i=n(0),r=n(52).set,o=i.MutationObserver||i.WebKitMutationObserver,a=i.process,s=i.Promise,l="process"==n(9)(a);t.exports=function(){var t,e,n,u=function(){var i,r;for(l&&(i=a.domain)&&i.exit();t;){r=t.fn,t=t.next;try{r()}catch(i){throw t?n():e=void 0,i}}e=void 0,i&&i.enter()};if(l)n=function(){a.nextTick(u)};else if(!o||i.navigator&&i.navigator.standalone)if(s&&s.resolve){var c=s.resolve(void 0);n=function(){c.then(u)}}else n=function(){r.call(i,u)};else{var p=!0,d=document.createTextNode("");new o(u).observe(d,{characterData:!0}),n=function(){d.data=p=!p}}return function(i){var r={fn:i,next:void 0};e&&(e.next=r),t||(t=r,n()),e=r}}},function(t,e,n){var i=n(13),r=n(2),o=n(47);t.exports=n(4)?Object.defineProperties:function(t,e){r(t);for(var n,a=o(e),s=a.length,l=0;s>l;)i.f(t,n=a[l++],e[n]);return t}},function(t,e,n){var i=n(46),r=n(22).concat("length","prototype");e.f=Object.getOwnPropertyNames||function(t){return i(t,r)}},function(t,e,n){var i=n(12),r=n(28),o=n(27)("IE_PROTO"),a=Object.prototype;t.exports=Object.getPrototypeOf||function(t){return t=r(t),i(t,o)?t[o]:"function"==typeof t.constructor&&t instanceof t.constructor?t.constructor.prototype:t instanceof Object?a:null}},function(t,e){e.f={}.propertyIsEnumerable},function(t,e){t.exports=function(t){try{return{e:!1,v:t()}}catch(t){return{e:!0,v:t}}}},function(t,e,n){var i=n(6);t.exports=function(t,e,n){for(var r in e)i(t,r,e[r],n);return t}},function(t,e,n){var i=n(5),r=n(2),o=function(t,e){if(r(t),!i(e)&&null!==e)throw TypeError(e+": can't set as prototype!")};t.exports={set:Object.setPrototypeOf||("__proto__"in{}?function(t,e,i){try{(i=n(11)(Function.call,n(45).f(Object.prototype,"__proto__").set,2))(t,[]),e=!(t instanceof Array)}catch(t){e=!0}return function(t,n){return o(t,n),e?t.__proto__=n:i(t,n),t}}({},!1):void 0),check:o}},function(t,e,n){"use strict";var i=n(0),r=n(13),o=n(4),a=n(1)("species");t.exports=function(t){var e=i[t];o&&e&&!e[a]&&r.f(e,a,{configurable:!0,get:function(){return this}})}},function(t,e){t.exports="\t\n\v\f\r   ᠎             　\u2028\u2029\ufeff"},function(t,e,n){var i=n(53),r=Math.max,o=Math.min;t.exports=function(t,e){return(t=i(t))<0?r(t+e,0):o(t,e)}},function(t,e,n){var i=n(0),r=i.navigator;t.exports=r&&r.userAgent||""},function(t,e,n){var i=n(38),r=n(1)("iterator"),o=n(15);t.exports=n(10).getIteratorMethod=function(t){if(null!=t)return t[r]||t["@@iterator"]||o[i(t)]}},function(t,e,n){"use strict";var i=n(3),r=n(20)(2);i(i.P+i.F*!n(17)([].filter,!0),"Array",{filter:function(t){return r(this,t,arguments[1])}})},function(t,e,n){"use strict";var i=n(3),r=n(37)(!1),o=[].indexOf,a=!!o&&1/[1].indexOf(1,-0)<0;i(i.P+i.F*(a||!n(17)(o)),"Array",{indexOf:function(t){return a?o.apply(this,arguments)||0:r(this,t,arguments[1])}})},function(t,e,n){var i=n(3);i(i.S,"Array",{isArray:n(42)})},function(t,e,n){"use strict";var i=n(3),r=n(20)(1);i(i.P+i.F*!n(17)([].map,!0),"Array",{map:function(t){return r(this,t,arguments[1])}})},function(t,e,n){"use strict";var i=n(3),r=n(62);i(i.P+i.F*!n(17)([].reduce,!0),"Array",{reduce:function(t){return r(this,t,arguments.length,arguments[1],!1)}})},function(t,e,n){var i=Date.prototype,r=i.toString,o=i.getTime;new Date(NaN)+""!="Invalid Date"&&n(6)(i,"toString",function(){var t=o.call(this);return t==t?r.call(this):"Invalid Date"})},function(t,e,n){n(4)&&"g"!=/./g.flags&&n(13).f(RegExp.prototype,"flags",{configurable:!0,get:n(39)})},function(t,e,n){n(65)("search",1,function(t,e,n){return[function(n){"use strict";var i=t(this),r=null==n?void 0:n[e];return void 0!==r?r.call(n,i):new RegExp(n)[e](String(i))},n]})},function(t,e,n){"use strict";n(94);var i=n(2),r=n(39),o=n(4),a=/./.toString,s=function(t){n(6)(RegExp.prototype,"toString",t,!0)};n(7)(function(){return"/a/b"!=a.call({source:"a",flags:"b"})})?s(function(){var t=i(this);return"/".concat(t.source,"/","flags"in t?t.flags:!o&&t instanceof RegExp?r.call(t):void 0)}):"toString"!=a.name&&s(function(){return a.call(this)})},function(t,e,n){"use strict";n(51)("trim",function(t){return function(){return t(this,3)}})},function(t,e,n){for(var i=n(34),r=n(47),o=n(6),a=n(0),s=n(8),l=n(15),u=n(1),c=u("iterator"),p=u("toStringTag"),d=l.Array,A={CSSRuleList:!0,CSSStyleDeclaration:!1,CSSValueList:!1,ClientRectList:!1,DOMRectList:!1,DOMStringList:!1,DOMTokenList:!0,DataTransferItemList:!1,FileList:!1,HTMLAllCollection:!1,HTMLCollection:!1,HTMLFormElement:!1,HTMLSelectElement:!1,MediaList:!0,MimeTypeArray:!1,NamedNodeMap:!1,NodeList:!0,PaintRequestList:!1,Plugin:!1,PluginArray:!1,SVGLengthList:!1,SVGNumberList:!1,SVGPathSegList:!1,SVGPointList:!1,SVGStringList:!1,SVGTransformList:!1,SourceBufferList:!1,StyleSheetList:!0,TextTrackCueList:!1,TextTrackList:!1,TouchList:!1},f=r(A),h=0;h<f.length;h++){var m,v=f[h],g=A[v],y=a[v],b=y&&y.prototype;if(b&&(b[c]||s(b,c,d),b[p]||s(b,p,v),l[v]=d,g))for(m in i)b[m]||o(b,m,i[m],!0)}},function(t,e){},function(t,e){t.exports=function(t,e,n,i,r,o){var a,s=t=t||{},l=typeof t.default;"object"!==l&&"function"!==l||(a=t,s=t.default);var u,c="function"==typeof s?s.options:s;if(e&&(c.render=e.render,c.staticRenderFns=e.staticRenderFns,c._compiled=!0),n&&(c.functional=!0),r&&(c._scopeId=r),o?(u=function(t){(t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),i&&i.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(o)},c._ssrRegister=u):i&&(u=i),u){var p=c.functional,d=p?c.render:c.beforeCreate;p?(c._injectStyles=u,c.render=function(t,e){return u.call(e),d(t,e)}):c.beforeCreate=d?[].concat(d,u):[u]}return{esModule:a,exports:s,options:c}}},function(t,e,n){"use strict";var i={render:function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"multiselect",class:{"multiselect--active":t.isOpen,"multiselect--disabled":t.disabled,"multiselect--above":t.isAbove},attrs:{tabindex:t.searchable?-1:t.tabindex},on:{focus:function(e){t.activate()},blur:function(e){!t.searchable&&t.deactivate()},keydown:[function(e){return"button"in e||!t._k(e.keyCode,"down",40,e.key,["Down","ArrowDown"])?e.target!==e.currentTarget?null:(e.preventDefault(),void t.pointerForward()):null},function(e){return"button"in e||!t._k(e.keyCode,"up",38,e.key,["Up","ArrowUp"])?e.target!==e.currentTarget?null:(e.preventDefault(),void t.pointerBackward()):null},function(e){return"button"in e||!t._k(e.keyCode,"enter",13,e.key,"Enter")||!t._k(e.keyCode,"tab",9,e.key,"Tab")?(e.stopPropagation(),e.target!==e.currentTarget?null:void t.addPointerElement(e)):null}],keyup:function(e){if(!("button"in e)&&t._k(e.keyCode,"esc",27,e.key,"Escape"))return null;t.deactivate()}}},[t._t("caret",[n("div",{staticClass:"multiselect__select",on:{mousedown:function(e){e.preventDefault(),e.stopPropagation(),t.toggle()}}})],{toggle:t.toggle}),t._v(" "),t._t("clear",null,{search:t.search}),t._v(" "),n("div",{ref:"tags",staticClass:"multiselect__tags"},[t._t("selection",[n("div",{directives:[{name:"show",rawName:"v-show",value:t.visibleValues.length>0,expression:"visibleValues.length > 0"}],staticClass:"multiselect__tags-wrap"},[t._l(t.visibleValues,function(e,i){return[t._t("tag",[n("span",{key:i,staticClass:"multiselect__tag"},[n("span",{domProps:{textContent:t._s(t.getOptionLabel(e))}}),t._v(" "),n("i",{staticClass:"multiselect__tag-icon",attrs:{"aria-hidden":"true",tabindex:"1"},on:{keydown:function(n){if(!("button"in n)&&t._k(n.keyCode,"enter",13,n.key,"Enter"))return null;n.preventDefault(),t.removeElement(e)},mousedown:function(n){n.preventDefault(),t.removeElement(e)}}})])],{option:e,search:t.search,remove:t.removeElement})]})],2),t._v(" "),t.internalValue&&t.internalValue.length>t.limit?[t._t("limit",[n("strong",{staticClass:"multiselect__strong",domProps:{textContent:t._s(t.limitText(t.internalValue.length-t.limit))}})])]:t._e()],{search:t.search,remove:t.removeElement,values:t.visibleValues,isOpen:t.isOpen}),t._v(" "),n("transition",{attrs:{name:"multiselect__loading"}},[t._t("loading",[n("div",{directives:[{name:"show",rawName:"v-show",value:t.loading,expression:"loading"}],staticClass:"multiselect__spinner"})])],2),t._v(" "),t.searchable?n("input",{ref:"search",staticClass:"multiselect__input",style:t.inputStyle,attrs:{name:t.name,id:t.id,type:"text",autocomplete:"off",placeholder:t.placeholder,disabled:t.disabled,tabindex:t.tabindex},domProps:{value:t.search},on:{input:function(e){t.updateSearch(e.target.value)},focus:function(e){e.preventDefault(),t.activate()},blur:function(e){e.preventDefault(),t.deactivate()},keyup:function(e){if(!("button"in e)&&t._k(e.keyCode,"esc",27,e.key,"Escape"))return null;t.deactivate()},keydown:[function(e){if(!("button"in e)&&t._k(e.keyCode,"down",40,e.key,["Down","ArrowDown"]))return null;e.preventDefault(),t.pointerForward()},function(e){if(!("button"in e)&&t._k(e.keyCode,"up",38,e.key,["Up","ArrowUp"]))return null;e.preventDefault(),t.pointerBackward()},function(e){return"button"in e||!t._k(e.keyCode,"enter",13,e.key,"Enter")?(e.preventDefault(),e.stopPropagation(),e.target!==e.currentTarget?null:void t.addPointerElement(e)):null},function(e){if(!("button"in e)&&t._k(e.keyCode,"delete",[8,46],e.key,["Backspace","Delete"]))return null;e.stopPropagation(),t.removeLastElement()}]}}):t._e(),t._v(" "),t.isSingleLabelVisible?n("span",{staticClass:"multiselect__single",on:{mousedown:function(e){return e.preventDefault(),t.toggle(e)}}},[t._t("singleLabel",[[t._v(t._s(t.currentOptionLabel))]],{option:t.singleValue})],2):t._e(),t._v(" "),t.isPlaceholderVisible?n("span",{staticClass:"multiselect__placeholder",on:{mousedown:function(e){return e.preventDefault(),t.toggle(e)}}},[t._t("placeholder",[t._v("\n            "+t._s(t.placeholder)+"\n        ")])],2):t._e()],2),t._v(" "),n("transition",{attrs:{name:"multiselect"}},[n("div",{directives:[{name:"show",rawName:"v-show",value:t.isOpen,expression:"isOpen"}],ref:"list",staticClass:"multiselect__content-wrapper",style:{maxHeight:t.optimizedHeight+"px"},attrs:{tabindex:"-1"},on:{focus:t.activate,mousedown:function(t){t.preventDefault()}}},[n("ul",{staticClass:"multiselect__content",style:t.contentStyle},[t._t("beforeList"),t._v(" "),t.multiple&&t.max===t.internalValue.length?n("li",[n("span",{staticClass:"multiselect__option"},[t._t("maxElements",[t._v("Maximum of "+t._s(t.max)+" options selected. First remove a selected option to select another.")])],2)]):t._e(),t._v(" "),!t.max||t.internalValue.length<t.max?t._l(t.filteredOptions,function(e,i){return n("li",{key:i,staticClass:"multiselect__element"},[e&&(e.$isLabel||e.$isDisabled)?t._e():n("span",{staticClass:"multiselect__option",class:t.optionHighlight(i,e),attrs:{"data-select":e&&e.isTag?t.tagPlaceholder:t.selectLabelText,"data-selected":t.selectedLabelText,"data-deselect":t.deselectLabelText},on:{click:function(n){n.stopPropagation(),t.select(e)},mouseenter:function(e){if(e.target!==e.currentTarget)return null;t.pointerSet(i)}}},[t._t("option",[n("span",[t._v(t._s(t.getOptionLabel(e)))])],{option:e,search:t.search})],2),t._v(" "),e&&(e.$isLabel||e.$isDisabled)?n("span",{staticClass:"multiselect__option",class:t.groupHighlight(i,e),attrs:{"data-select":t.groupSelect&&t.selectGroupLabelText,"data-deselect":t.groupSelect&&t.deselectGroupLabelText},on:{mouseenter:function(e){if(e.target!==e.currentTarget)return null;t.groupSelect&&t.pointerSet(i)},mousedown:function(n){n.preventDefault(),t.selectGroup(e)}}},[t._t("option",[n("span",[t._v(t._s(t.getOptionLabel(e)))])],{option:e,search:t.search})],2):t._e()])}):t._e(),t._v(" "),n("li",{directives:[{name:"show",rawName:"v-show",value:t.showNoResults&&0===t.filteredOptions.length&&t.search&&!t.loading,expression:"showNoResults && (filteredOptions.length === 0 && search && !loading)"}]},[n("span",{staticClass:"multiselect__option"},[t._t("noResult",[t._v("No elements found. Consider changing the search query.")])],2)]),t._v(" "),n("li",{directives:[{name:"show",rawName:"v-show",value:t.showNoOptions&&0===t.options.length&&!t.search&&!t.loading,expression:"showNoOptions && (options.length === 0 && !search && !loading)"}]},[n("span",{staticClass:"multiselect__option"},[t._t("noOptions",[t._v("List is empty.")])],2)]),t._v(" "),t._t("afterList")],2)])])],2)},staticRenderFns:[]};e.a=i}])},function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i=n(31).default.create({headers:{requesttoken:OC.requestToken}});e.default=i},function(t,e,n){var i,r,o,a,s;i=n(50),r=n(17).utf8,o=n(12),a=n(17).bin,(s=function(t,e){t.constructor==String?t=e&&"binary"===e.encoding?a.stringToBytes(t):r.stringToBytes(t):o(t)?t=Array.prototype.slice.call(t,0):Array.isArray(t)||(t=t.toString());for(var n=i.bytesToWords(t),l=8*t.length,u=1732584193,c=-271733879,p=-1732584194,d=271733878,A=0;A<n.length;A++)n[A]=16711935&(n[A]<<8|n[A]>>>24)|4278255360&(n[A]<<24|n[A]>>>8);n[l>>>5]|=128<<l%32,n[14+(l+64>>>9<<4)]=l;var f=s._ff,h=s._gg,m=s._hh,v=s._ii;for(A=0;A<n.length;A+=16){var g=u,y=c,b=p,w=d;u=f(u,c,p,d,n[A+0],7,-680876936),d=f(d,u,c,p,n[A+1],12,-389564586),p=f(p,d,u,c,n[A+2],17,606105819),c=f(c,p,d,u,n[A+3],22,-1044525330),u=f(u,c,p,d,n[A+4],7,-176418897),d=f(d,u,c,p,n[A+5],12,1200080426),p=f(p,d,u,c,n[A+6],17,-1473231341),c=f(c,p,d,u,n[A+7],22,-45705983),u=f(u,c,p,d,n[A+8],7,1770035416),d=f(d,u,c,p,n[A+9],12,-1958414417),p=f(p,d,u,c,n[A+10],17,-42063),c=f(c,p,d,u,n[A+11],22,-1990404162),u=f(u,c,p,d,n[A+12],7,1804603682),d=f(d,u,c,p,n[A+13],12,-40341101),p=f(p,d,u,c,n[A+14],17,-1502002290),u=h(u,c=f(c,p,d,u,n[A+15],22,1236535329),p,d,n[A+1],5,-165796510),d=h(d,u,c,p,n[A+6],9,-1069501632),p=h(p,d,u,c,n[A+11],14,643717713),c=h(c,p,d,u,n[A+0],20,-373897302),u=h(u,c,p,d,n[A+5],5,-701558691),d=h(d,u,c,p,n[A+10],9,38016083),p=h(p,d,u,c,n[A+15],14,-660478335),c=h(c,p,d,u,n[A+4],20,-405537848),u=h(u,c,p,d,n[A+9],5,568446438),d=h(d,u,c,p,n[A+14],9,-1019803690),p=h(p,d,u,c,n[A+3],14,-187363961),c=h(c,p,d,u,n[A+8],20,1163531501),u=h(u,c,p,d,n[A+13],5,-1444681467),d=h(d,u,c,p,n[A+2],9,-51403784),p=h(p,d,u,c,n[A+7],14,1735328473),u=m(u,c=h(c,p,d,u,n[A+12],20,-1926607734),p,d,n[A+5],4,-378558),d=m(d,u,c,p,n[A+8],11,-2022574463),p=m(p,d,u,c,n[A+11],16,1839030562),c=m(c,p,d,u,n[A+14],23,-35309556),u=m(u,c,p,d,n[A+1],4,-1530992060),d=m(d,u,c,p,n[A+4],11,1272893353),p=m(p,d,u,c,n[A+7],16,-155497632),c=m(c,p,d,u,n[A+10],23,-1094730640),u=m(u,c,p,d,n[A+13],4,681279174),d=m(d,u,c,p,n[A+0],11,-358537222),p=m(p,d,u,c,n[A+3],16,-722521979),c=m(c,p,d,u,n[A+6],23,76029189),u=m(u,c,p,d,n[A+9],4,-640364487),d=m(d,u,c,p,n[A+12],11,-421815835),p=m(p,d,u,c,n[A+15],16,530742520),u=v(u,c=m(c,p,d,u,n[A+2],23,-995338651),p,d,n[A+0],6,-198630844),d=v(d,u,c,p,n[A+7],10,1126891415),p=v(p,d,u,c,n[A+14],15,-1416354905),c=v(c,p,d,u,n[A+5],21,-57434055),u=v(u,c,p,d,n[A+12],6,1700485571),d=v(d,u,c,p,n[A+3],10,-1894986606),p=v(p,d,u,c,n[A+10],15,-1051523),c=v(c,p,d,u,n[A+1],21,-2054922799),u=v(u,c,p,d,n[A+8],6,1873313359),d=v(d,u,c,p,n[A+15],10,-30611744),p=v(p,d,u,c,n[A+6],15,-1560198380),c=v(c,p,d,u,n[A+13],21,1309151649),u=v(u,c,p,d,n[A+4],6,-145523070),d=v(d,u,c,p,n[A+11],10,-1120210379),p=v(p,d,u,c,n[A+2],15,718787259),c=v(c,p,d,u,n[A+9],21,-343485551),u=u+g>>>0,c=c+y>>>0,p=p+b>>>0,d=d+w>>>0}return i.endian([u,c,p,d])})._ff=function(t,e,n,i,r,o,a){var s=t+(e&n|~e&i)+(r>>>0)+a;return(s<<o|s>>>32-o)+e},s._gg=function(t,e,n,i,r,o,a){var s=t+(e&i|n&~i)+(r>>>0)+a;return(s<<o|s>>>32-o)+e},s._hh=function(t,e,n,i,r,o,a){var s=t+(e^n^i)+(r>>>0)+a;return(s<<o|s>>>32-o)+e},s._ii=function(t,e,n,i,r,o,a){var s=t+(n^(e|~i))+(r>>>0)+a;return(s<<o|s>>>32-o)+e},s._blocksize=16,s._digestsize=16,t.exports=function(t,e){if(null==t)throw new Error("Illegal argument "+t);var n=i.wordsToBytes(s(t,e));return e&&e.asBytes?n:e&&e.asString?a.bytesToString(n):i.bytesToHex(n)}},function(t,e,n){"use strict";var i=n(4);n.n(i).a},function(t,e,n){(t.exports=n(2)(!1)).push([t.i,"\nbutton.menuitem[data-v-512ea768] {\n\ttext-align: left;\n}\nbutton.menuitem *[data-v-512ea768] {\n\tcursor: pointer;\n}\n.menuitem.active[data-v-512ea768] {\n\tbox-shadow: inset 2px 0 var(--color-primary);\n\tborder-radius: 0;\n}\n",""])},function(t,e,n){var i=n(24);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);(0,n(3).default)("56ea6c9e",i,!1,{})},function(t,e,n){e=t.exports=n(2)(!1);var i=n(25),r=i(n(26)),o=i(n(27)),a=i(n(28)),s=i(n(29));e.push([t.i,'@charset "UTF-8";\n@font-face {\n  font-family: "iconfont-vue";\n  src: url('+r+");\n  /* IE9 Compat Modes */\n  src: url("+r+') format("embedded-opentype"), url('+o+') format("woff"), url('+a+') format("truetype"), url('+s+') format("svg");\n  /* Legacy iOS */ }\n\n.icon {\n  font-style: normal;\n  font-weight: 400; }\n  .icon.arrow-left-double:before {\n    font-family: "iconfont-vue";\n    content: ""; }\n  .icon.arrow-left:before {\n    font-family: "iconfont-vue";\n    content: ""; }\n  .icon.arrow-right-double:before {\n    font-family: "iconfont-vue";\n    content: ""; }\n  .icon.arrow-right:before {\n    font-family: "iconfont-vue";\n    content: ""; }\n\n.mx-datepicker[data-v-97c9b20] {\n  width: 210px;\n  color: inherit;\n  user-select: none;\n  position: relative;\n  display: inline-block;\n  /* INPUT CONTAINER */\n  /* FOOTER if confirm option enabled*/ }\n  .mx-datepicker[data-v-97c9b20].disabled {\n    opacity: .7;\n    cursor: not-allowed; }\n  .mx-datepicker[data-v-97c9b20] .mx-input-wrapper .mx-input {\n    width: 100%; }\n  .mx-datepicker[data-v-97c9b20] .mx-input-wrapper .mx-input-append {\n    position: absolute;\n    top: 0;\n    right: 0;\n    width: 30px;\n    height: 100%;\n    padding: 6px;\n    background-color: var(--color-main-background);\n    background-clip: content-box; }\n    .mx-datepicker[data-v-97c9b20] .mx-input-wrapper .mx-input-append .mx-input-icon {\n      display: inline-block;\n      font-style: normal;\n      text-align: center;\n      cursor: pointer; }\n    .mx-datepicker[data-v-97c9b20] .mx-input-wrapper .mx-input-append .mx-clear-wrapper {\n      display: none; }\n    .mx-datepicker[data-v-97c9b20] .mx-input-wrapper .mx-input-append .mx-calendar-icon {\n      stroke-width: 8px;\n      stroke: currentColor;\n      fill: currentColor;\n      width: 100%;\n      height: 100%;\n      color: var(--color-text-lighter); }\n  .mx-datepicker[data-v-97c9b20] .mx-datepicker-popup {\n    box-shadow: none;\n    background-color: var(--color-main-background);\n    position: absolute;\n    margin-top: 1px;\n    margin-bottom: 1px;\n    z-index: 1000; }\n  .mx-datepicker[data-v-97c9b20] .mx-range-wrapper {\n    display: flex;\n    overflow: hidden; }\n    .mx-datepicker[data-v-97c9b20] .mx-range-wrapper .mx-calendar:first-child {\n      box-shadow: var(--color-border) 1px 0px !important; }\n    .mx-datepicker[data-v-97c9b20] .mx-range-wrapper .mx-calendar-content .mx-panel .cell.actived {\n      border-radius: var(--border-radius) 0 0 var(--border-radius); }\n    .mx-datepicker[data-v-97c9b20] .mx-range-wrapper .mx-calendar-content .mx-panel .cell.inrange + .cell.actived {\n      border-radius: 0 var(--border-radius) var(--border-radius) 0; }\n  .mx-datepicker[data-v-97c9b20] .mx-shortcuts-wrapper {\n    display: flex;\n    justify-content: space-evenly;\n    padding: 5px;\n    border-bottom: 1px solid var(--color-border); }\n    .mx-datepicker[data-v-97c9b20] .mx-shortcuts-wrapper .mx-shortcuts {\n      font-weight: normal; }\n  .mx-datepicker[data-v-97c9b20] .mx-calendar {\n    font: inherit;\n    color: var(--color-main-text);\n    padding: 5px;\n    width: 240px; }\n  .mx-datepicker[data-v-97c9b20] .mx-calendar-header {\n    padding: 0 4px;\n    margin-bottom: 4px;\n    text-align: center;\n    overflow: hidden;\n    display: flex;\n    align-items: center;\n    justify-content: space-between; }\n    .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a {\n      text-decoration: none;\n      cursor: pointer;\n      color: var(--color-text-lighter);\n      padding: 7px 10px;\n      margin: 0 auto;\n      border-radius: 32px;\n      height: 32px;\n      line-height: 20px;\n      min-width: 32px; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a:hover, .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a:focus {\n        opacity: 1;\n        color: var(--color-main-text);\n        background-color: var(--color-background-darker); }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-last-year, .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-last-month, .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-next-month, .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-next-year {\n        background-position: center;\n        background-repeat: no-repeat;\n        font-size: 0;\n        opacity: .5;\n        display: flex;\n        align-items: center;\n        justify-content: center;\n        padding: 0; }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-last-year:before, .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-last-month:before, .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-next-month:before, .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-next-year:before {\n          display: block;\n          font-size: 16px; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-last-year:before {\n        font-family: "iconfont-vue";\n        font-style: normal;\n        font-weight: 400;\n        content: ""; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-last-month:before {\n        font-family: "iconfont-vue";\n        font-style: normal;\n        font-weight: 400;\n        content: ""; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-next-month {\n        order: 3; }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-next-month:before {\n          font-family: "iconfont-vue";\n          font-style: normal;\n          font-weight: 400;\n          content: ""; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-next-year {\n        order: 4; }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-header > a.mx-icon-next-year:before {\n          font-family: "iconfont-vue";\n          font-style: normal;\n          font-weight: 400;\n          content: ""; }\n  .mx-datepicker[data-v-97c9b20] .mx-calendar-content {\n    /* DATE SELECTOR */\n    /* YEAR SELECTOR */\n    /* MONTH SELECTOR */\n    /* TIME SELECTOR */ }\n    .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel {\n      width: 100%;\n      height: 100%;\n      text-align: center; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell {\n        opacity: 0.7;\n        border-radius: 50px;\n        transition: all 100ms ease-in-out;\n        cursor: pointer; }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell:hover, .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell:focus, .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell.actived, .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell.inrange {\n          font-weight: bold;\n          opacity: 1;\n          color: var(--color-primary-text);\n          background-color: var(--color-primary-element); }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell.inrange, .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell.disabled {\n          border-radius: 0;\n          font-weight: normal; }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell.inrange {\n          opacity: 0.7; }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel .cell.disabled {\n          color: var(--color-text-lighter);\n          opacity: 0.5;\n          background-color: var(--color-background-darker); }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel span.cell,\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel li.cell {\n        min-height: 32px; }\n    .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date {\n      table-layout: fixed;\n      border-collapse: collapse;\n      border-spacing: 0; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date td, .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date th {\n        font-size: 12px;\n        width: 32px;\n        height: 32px;\n        padding: 0;\n        overflow: hidden;\n        text-align: center; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date th {\n        color: var(--color-text-lighter);\n        opacity: .5; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date td.today {\n        color: var(--color-primary);\n        opacity: 1;\n        font-weight: bold; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date td.last-month, .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date td.next-month {\n        color: var(--color-text-lighter);\n        opacity: 0.5; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date tr:hover,\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date tr:focus,\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-date tr:active {\n        background: none; }\n    .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-year,\n    .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-month {\n      display: flex;\n      flex-wrap: wrap;\n      justify-content: space-around; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-year span.cell,\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-month span.cell {\n        display: block;\n        padding: 5px;\n        height: 44px;\n        line-height: 36px;\n        margin-bottom: 1%; }\n    .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-year .cell {\n      width: 45%; }\n    .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-month .cell {\n      width: 30%; }\n    .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-time {\n      display: flex; }\n      .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-time .mx-time-list {\n        position: relative;\n        width: 100%;\n        height: 100%;\n        padding: 5px;\n        margin: 0;\n        list-style: none;\n        overflow-y: auto;\n        max-height: 220px; }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-time .mx-time-list .mx-time-picker-item {\n          display: block;\n          text-align: left;\n          padding-left: 10px; }\n        .mx-datepicker[data-v-97c9b20] .mx-calendar-content .mx-panel-time .mx-time-list .cell {\n          display: flex;\n          justify-content: center;\n          margin-bottom: 1px;\n          width: 100%;\n          font-size: 12px;\n          height: 32px;\n          line-height: 32px; }\n  .mx-datepicker[data-v-97c9b20] .mx-datepicker-footer {\n    padding: 4px;\n    clear: both;\n    text-align: right;\n    border-top: 1px solid var(--color-border); }\n',""])},function(t,e,n){"use strict";t.exports=function(t,e){return"string"!=typeof t?t:(/^['"].*['"]$/.test(t)&&(t=t.slice(1,-1)),/["'() \t\n]/.test(t)||e?'"'+t.replace(/"/g,'\\"').replace(/\n/g,"\\n")+'"':t)}},function(t,e){t.exports="data:application/vnd.ms-fontobject;base64,GgcAAHAGAAABAAIAAAAAAAIABQMAAAAAAAABQJABAAAAAExQAAAAABAAAAAAAAAAAAAAAAAAAAEAAAAAbVjKVQAAAAAAAAAAAAAAAAAAAAAAABgAAGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAAAAAAAAFgAAVgBlAHIAcwBpAG8AbgAgADEALgAwAAAYAABpAGMAbwBuAGYAbwBuAHQALQB2AHUAZQAAAAAAAQAAAAoAgAADACBPUy8ydOOQgQAAAKwAAABgY21hcAAN66oAAAEMAAABQmdseWbNrYLjAAACUAAAANhoZWFkH8AsGQAAAygAAAA2aGhlYSNoFzQAAANgAAAAJGhtdHgTiAAAAAADhAAAAA5sb2NhAHoAogAAA5QAAAAMbWF4cAERABgAAAOgAAAAIG5hbWUNIFD5AAADwAAAAkZwb3N0f+yD6wAABggAAABoAAQTiAGQAAUAAAxlDawAAAK8DGUNrAAACWAA9QUKAAACAAUDAAAAAAAAAAAAABAAAAAAAAAAAAAAAFBmRWQAQOoB6gQTiAAAAcITiAAAAAAAAQAAAAAAAAAAAAAAIAAAAAAAAwAAAAMAAAAcAAEAAAAAADwAAwABAAAAHAAEACAAAAAEAAQAAQAA6gT//wAA6gH//xYAAAEAAAAAAAABBgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAADqYPQwAFAAsAAAkCEQkEEQkBDqb6ggV++7oERvqC+oIFfvu6BEYPQvqC+oIBOARGBEYBOPqC+oIBOARGBEYAAQAAAAANbhJQAAUAAAkBEQkBEQYbB1P3dAiMCcT4rf7ICIsIjP7HAAIAAAAAD98PQwAFAAsAAAkCEQkEEQkBBOIFfvqCBEb7ugV+BX/6gQRG+7oERgV+BX7+yPu6+7r+yAV+BX7+yPu6+7oAAQAAAAAOphJQAAUAAAkBEQkBEQ1u+K0Ii/d1CcQHUwE593T3dQE4AAEAAAABAABVylhtXw889QALE4gAAAAA2InSogAAAADYOPaiAAAAAA/fElAAAAAIAAIAAAAAAAAAAQAAE4gAAAAAE4gAAAOpD98AAQAAAAAAAAAAAAAAAAAAAAIAAAAAE4gAAAAAAAAAAAAAAAAAAAAiADYAWABsAAEAAAAFAAwAAgAAAAAAAgAAAAoACgAAAP8AAAAAAAAAAAAQAMYAAQAAAAAAAQAMAAAAAQAAAAAAAgAHAAwAAQAAAAAAAwAMABMAAQAAAAAABAAMAB8AAQAAAAAABQALACsAAQAAAAAABgAMADYAAQAAAAAACgArAEIAAQAAAAAACwATAG0AAwABBAkAAQAYAIAAAwABBAkAAgAOAJgAAwABBAkAAwAYAKYAAwABBAkABAAYAL4AAwABBAkABQAWANYAAwABBAkABgAYAOwAAwABBAkACgBWAQQAAwABBAkACwAmAVppY29uZm9udC12dWVSZWd1bGFyaWNvbmZvbnQtdnVlaWNvbmZvbnQtdnVlVmVyc2lvbiAxLjBpY29uZm9udC12dWVHZW5lcmF0ZWQgYnkgc3ZnMnR0ZiBmcm9tIEZvbnRlbGxvIHByb2plY3QuaHR0cDovL2ZvbnRlbGxvLmNvbQBpAGMAbwBuAGYAbwBuAHQALQB2AHUAZQBSAGUAZwB1AGwAYQByAGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAGkAYwBvAG4AZgBvAG4AdAAtAHYAdQBlAFYAZQByAHMAaQBvAG4AIAAxAC4AMABpAGMAbwBuAGYAbwBuAHQALQB2AHUAZQBHAGUAbgBlAHIAYQB0AGUAZAAgAGIAeQAgAHMAdgBnADIAdAB0AGYAIABmAHIAbwBtACAARgBvAG4AdABlAGwAbABvACAAcAByAG8AagBlAGMAdAAuAGgAdAB0AHAAOgAvAC8AZgBvAG4AdABlAGwAbABvAC4AYwBvAG0AAAACAAAAAAAAADIAAAAAAAAAAAAAAAAAAAAAAAAAAAAFAAUAAAECAQMBBAEFEWFycm93LWxlZnQtZG91YmxlCmFycm93LWxlZnQSYXJyb3ctcmlnaHQtZG91YmxlC2Fycm93LXJpZ2h0"},function(t,e){t.exports="data:font/woff;base64,d09GRgABAAAAAAa4AAoAAAAABnAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAABPUy8yAAAA9AAAAGAAAABgdOOQgWNtYXAAAAFUAAABQgAAAUIADeuqZ2x5ZgAAApgAAADYAAAA2M2tguNoZWFkAAADcAAAADYAAAA2H8AsGWhoZWEAAAOoAAAAJAAAACQjaBc0aG10eAAAA8wAAAAOAAAADhOIAABsb2NhAAAD3AAAAAwAAAAMAHoAom1heHAAAAPoAAAAIAAAACABEQAYbmFtZQAABAgAAAJGAAACRg0gUPlwb3N0AAAGUAAAAGgAAABof+yD6wAEE4gBkAAFAAAMZQ2sAAACvAxlDawAAAlgAPUFCgAAAgAFAwAAAAAAAAAAAAAQAAAAAAAAAAAAAABQZkVkAEDqAeoEE4gAAAHCE4gAAAAAAAEAAAAAAAAAAAAAACAAAAAAAAMAAAADAAAAHAABAAAAAAA8AAMAAQAAABwABAAgAAAABAAEAAEAAOoE//8AAOoB//8WAAABAAAAAAAAAQYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAA6mD0MABQALAAAJAhEJBBEJAQ6m+oIFfvu6BEb6gvqCBX77ugRGD0L6gvqCATgERgRGATj6gvqCATgERgRGAAEAAAAADW4SUAAFAAAJAREJAREGGwdT93QIjAnE+K3+yAiLCIz+xwACAAAAAA/fD0MABQALAAAJAhEJBBEJAQTiBX76ggRG+7oFfgV/+oEERvu6BEYFfgV+/sj7uvu6/sgFfgV+/sj7uvu6AAEAAAAADqYSUAAFAAAJAREJARENbvitCIv3dQnEB1MBOfd093UBOAABAAAAAQAAVcpYbV8PPPUACxOIAAAAANiJ0qIAAAAA2Dj2ogAAAAAP3xJQAAAACAACAAAAAAAAAAEAABOIAAAAABOIAAADqQ/fAAEAAAAAAAAAAAAAAAAAAAACAAAAABOIAAAAAAAAAAAAAAAAAAAAIgA2AFgAbAABAAAABQAMAAIAAAAAAAIAAAAKAAoAAAD/AAAAAAAAAAAAEADGAAEAAAAAAAEADAAAAAEAAAAAAAIABwAMAAEAAAAAAAMADAATAAEAAAAAAAQADAAfAAEAAAAAAAUACwArAAEAAAAAAAYADAA2AAEAAAAAAAoAKwBCAAEAAAAAAAsAEwBtAAMAAQQJAAEAGACAAAMAAQQJAAIADgCYAAMAAQQJAAMAGACmAAMAAQQJAAQAGAC+AAMAAQQJAAUAFgDWAAMAAQQJAAYAGADsAAMAAQQJAAoAVgEEAAMAAQQJAAsAJgFaaWNvbmZvbnQtdnVlUmVndWxhcmljb25mb250LXZ1ZWljb25mb250LXZ1ZVZlcnNpb24gMS4waWNvbmZvbnQtdnVlR2VuZXJhdGVkIGJ5IHN2ZzJ0dGYgZnJvbSBGb250ZWxsbyBwcm9qZWN0Lmh0dHA6Ly9mb250ZWxsby5jb20AaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUAUgBlAGcAdQBsAGEAcgBpAGMAbwBuAGYAbwBuAHQALQB2AHUAZQBpAGMAbwBuAGYAbwBuAHQALQB2AHUAZQBWAGUAcgBzAGkAbwBuACAAMQAuADAAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUARwBlAG4AZQByAGEAdABlAGQAIABiAHkAIABzAHYAZwAyAHQAdABmACAAZgByAG8AbQAgAEYAbwBuAHQAZQBsAGwAbwAgAHAAcgBvAGoAZQBjAHQALgBoAHQAdABwADoALwAvAGYAbwBuAHQAZQBsAGwAbwAuAGMAbwBtAAAAAgAAAAAAAAAyAAAAAAAAAAAAAAAAAAAAAAAAAAAABQAFAAABAgEDAQQBBRFhcnJvdy1sZWZ0LWRvdWJsZQphcnJvdy1sZWZ0EmFycm93LXJpZ2h0LWRvdWJsZQthcnJvdy1yaWdodA=="},function(t,e){t.exports="data:font/ttf;base64,AAEAAAAKAIAAAwAgT1MvMnTjkIEAAACsAAAAYGNtYXAADeuqAAABDAAAAUJnbHlmza2C4wAAAlAAAADYaGVhZB/ALBkAAAMoAAAANmhoZWEjaBc0AAADYAAAACRobXR4E4gAAAAAA4QAAAAObG9jYQB6AKIAAAOUAAAADG1heHABEQAYAAADoAAAACBuYW1lDSBQ+QAAA8AAAAJGcG9zdH/sg+sAAAYIAAAAaAAEE4gBkAAFAAAMZQ2sAAACvAxlDawAAAlgAPUFCgAAAgAFAwAAAAAAAAAAAAAQAAAAAAAAAAAAAABQZkVkAEDqAeoEE4gAAAHCE4gAAAAAAAEAAAAAAAAAAAAAACAAAAAAAAMAAAADAAAAHAABAAAAAAA8AAMAAQAAABwABAAgAAAABAAEAAEAAOoE//8AAOoB//8WAAABAAAAAAAAAQYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAA6mD0MABQALAAAJAhEJBBEJAQ6m+oIFfvu6BEb6gvqCBX77ugRGD0L6gvqCATgERgRGATj6gvqCATgERgRGAAEAAAAADW4SUAAFAAAJAREJAREGGwdT93QIjAnE+K3+yAiLCIz+xwACAAAAAA/fD0MABQALAAAJAhEJBBEJAQTiBX76ggRG+7oFfgV/+oEERvu6BEYFfgV+/sj7uvu6/sgFfgV+/sj7uvu6AAEAAAAADqYSUAAFAAAJAREJARENbvitCIv3dQnEB1MBOfd093UBOAABAAAAAQAAVcpYbV8PPPUACxOIAAAAANiJ0qIAAAAA2Dj2ogAAAAAP3xJQAAAACAACAAAAAAAAAAEAABOIAAAAABOIAAADqQ/fAAEAAAAAAAAAAAAAAAAAAAACAAAAABOIAAAAAAAAAAAAAAAAAAAAIgA2AFgAbAABAAAABQAMAAIAAAAAAAIAAAAKAAoAAAD/AAAAAAAAAAAAEADGAAEAAAAAAAEADAAAAAEAAAAAAAIABwAMAAEAAAAAAAMADAATAAEAAAAAAAQADAAfAAEAAAAAAAUACwArAAEAAAAAAAYADAA2AAEAAAAAAAoAKwBCAAEAAAAAAAsAEwBtAAMAAQQJAAEAGACAAAMAAQQJAAIADgCYAAMAAQQJAAMAGACmAAMAAQQJAAQAGAC+AAMAAQQJAAUAFgDWAAMAAQQJAAYAGADsAAMAAQQJAAoAVgEEAAMAAQQJAAsAJgFaaWNvbmZvbnQtdnVlUmVndWxhcmljb25mb250LXZ1ZWljb25mb250LXZ1ZVZlcnNpb24gMS4waWNvbmZvbnQtdnVlR2VuZXJhdGVkIGJ5IHN2ZzJ0dGYgZnJvbSBGb250ZWxsbyBwcm9qZWN0Lmh0dHA6Ly9mb250ZWxsby5jb20AaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUAUgBlAGcAdQBsAGEAcgBpAGMAbwBuAGYAbwBuAHQALQB2AHUAZQBpAGMAbwBuAGYAbwBuAHQALQB2AHUAZQBWAGUAcgBzAGkAbwBuACAAMQAuADAAaQBjAG8AbgBmAG8AbgB0AC0AdgB1AGUARwBlAG4AZQByAGEAdABlAGQAIABiAHkAIABzAHYAZwAyAHQAdABmACAAZgByAG8AbQAgAEYAbwBuAHQAZQBsAGwAbwAgAHAAcgBvAGoAZQBjAHQALgBoAHQAdABwADoALwAvAGYAbwBuAHQAZQBsAGwAbwAuAGMAbwBtAAAAAgAAAAAAAAAyAAAAAAAAAAAAAAAAAAAAAAAAAAAABQAFAAABAgEDAQQBBRFhcnJvdy1sZWZ0LWRvdWJsZQphcnJvdy1sZWZ0EmFycm93LXJpZ2h0LWRvdWJsZQthcnJvdy1yaWdodA=="},function(t,e){t.exports="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCIgPjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48bWV0YWRhdGE+PC9tZXRhZGF0YT48ZGVmcz48Zm9udCBpZD0iaWNvbmZvbnQtdnVlIiBob3Jpei1hZHYteD0iNTAwMCI+PGZvbnQtZmFjZSBmb250LWZhbWlseT0iaWNvbmZvbnQtdnVlIiBmb250LXdlaWdodD0iNDAwIiBmb250LXN0cmV0Y2g9Im5vcm1hbCIgdW5pdHMtcGVyLWVtPSI1MDAwIiBwYW5vc2UtMT0iMiAwIDUgMyAwIDAgMCAwIDAgMCIgYXNjZW50PSI1MDAwIiBkZXNjZW50PSIwIiB4LWhlaWdodD0iMCIgYmJveD0iMCAwIDQwNjMgNDY4OCIgdW5kZXJsaW5lLXRoaWNrbmVzcz0iMCIgdW5kZXJsaW5lLXBvc2l0aW9uPSI1MCIgdW5pY29kZS1yYW5nZT0iVStlYTAxLWVhMDQiIC8+PG1pc3NpbmctZ2x5cGggaG9yaXotYWR2LXg9IjAiICAvPjxnbHlwaCBnbHlwaC1uYW1lPSJhcnJvdy1sZWZ0LWRvdWJsZSIgdW5pY29kZT0iJiN4ZWEwMTsiIGQ9Ik0zNzUwIDM5MDYgbC0xNDA2IC0xNDA2IGwxNDA2IC0xNDA2IGwwIDMxMiBsLTEwOTQgMTA5NCBsMTA5NCAxMDk0IGwwIDMxMiBaTTIzNDQgMzkwNiBsLTE0MDYgLTE0MDYgbDE0MDYgLTE0MDYgbDAgMzEyIGwtMTA5NCAxMDk0IGwxMDk0IDEwOTQgbDAgMzEyIFoiIC8+PGdseXBoIGdseXBoLW5hbWU9ImFycm93LWxlZnQiIHVuaWNvZGU9IiYjeGVhMDI7IiBkPSJNMTU2MyAyNTAwIGwxODc1IC0xODc1IGwwIC0zMTIgbC0yMTg4IDIxODcgbDIxODggMjE4OCBsMCAtMzEzIGwtMTg3NSAtMTg3NSBaIiAvPjxnbHlwaCBnbHlwaC1uYW1lPSJhcnJvdy1yaWdodC1kb3VibGUiIHVuaWNvZGU9IiYjeGVhMDM7IiBkPSJNMTI1MCAxMDk0IGwxNDA2IDE0MDYgbC0xNDA2IDE0MDYgbDAgLTMxMiBsMTA5NCAtMTA5NCBsLTEwOTQgLTEwOTQgbDAgLTMxMiBaTTI2NTYgMTA5NCBsMTQwNyAxNDA2IGwtMTQwNyAxNDA2IGwwIC0zMTIgbDEwOTQgLTEwOTQgbC0xMDk0IC0xMDk0IGwwIC0zMTIgWiIgLz48Z2x5cGggZ2x5cGgtbmFtZT0iYXJyb3ctcmlnaHQiIHVuaWNvZGU9IiYjeGVhMDQ7IiBkPSJNMzQzOCAyNTAwIGwtMTg3NSAxODc1IGwwIDMxMyBsMjE4NyAtMjE4OCBsLTIxODcgLTIxODcgbDAgMzEyIGwxODc1IDE4NzUgWiIgLz48L2ZvbnQ+PC9kZWZzPjwvc3ZnPg=="},function(t,e){var n;n=function(){return this}();try{n=n||new Function("return this")()}catch(t){"object"==typeof window&&(n=window)}t.exports=n},function(t,e,n){t.exports=n(32)},function(t,e,n){"use strict";var i=n(0),r=n(11),o=n(33),a=n(10);function s(t){var e=new o(t),n=r(o.prototype.request,e);return i.extend(n,o.prototype,e),i.extend(n,e),n}var l=s(a);l.Axios=o,l.create=function(t){return s(i.merge(a,t))},l.Cancel=n(16),l.CancelToken=n(48),l.isCancel=n(15),l.all=function(t){return Promise.all(t)},l.spread=n(49),t.exports=l,t.exports.default=l},function(t,e,n){"use strict";var i=n(10),r=n(0),o=n(43),a=n(44);function s(t){this.defaults=t,this.interceptors={request:new o,response:new o}}s.prototype.request=function(t){"string"==typeof t&&(t=r.merge({url:arguments[0]},arguments[1])),(t=r.merge(i,{method:"get"},this.defaults,t)).method=t.method.toLowerCase();var e=[a,void 0],n=Promise.resolve(t);for(this.interceptors.request.forEach(function(t){e.unshift(t.fulfilled,t.rejected)}),this.interceptors.response.forEach(function(t){e.push(t.fulfilled,t.rejected)});e.length;)n=n.then(e.shift(),e.shift());return n},r.forEach(["delete","get","head","options"],function(t){s.prototype[t]=function(e,n){return this.request(r.merge(n||{},{method:t,url:e}))}}),r.forEach(["post","put","patch"],function(t){s.prototype[t]=function(e,n,i){return this.request(r.merge(i||{},{method:t,url:e,data:n}))}}),t.exports=s},function(t,e){var n,i,r=t.exports={};function o(){throw new Error("setTimeout has not been defined")}function a(){throw new Error("clearTimeout has not been defined")}function s(t){if(n===setTimeout)return setTimeout(t,0);if((n===o||!n)&&setTimeout)return n=setTimeout,setTimeout(t,0);try{return n(t,0)}catch(e){try{return n.call(null,t,0)}catch(e){return n.call(this,t,0)}}}!function(){try{n="function"==typeof setTimeout?setTimeout:o}catch(t){n=o}try{i="function"==typeof clearTimeout?clearTimeout:a}catch(t){i=a}}();var l,u=[],c=!1,p=-1;function d(){c&&l&&(c=!1,l.length?u=l.concat(u):p=-1,u.length&&A())}function A(){if(!c){var t=s(d);c=!0;for(var e=u.length;e;){for(l=u,u=[];++p<e;)l&&l[p].run();p=-1,e=u.length}l=null,c=!1,function(t){if(i===clearTimeout)return clearTimeout(t);if((i===a||!i)&&clearTimeout)return i=clearTimeout,clearTimeout(t);try{i(t)}catch(e){try{return i.call(null,t)}catch(e){return i.call(this,t)}}}(t)}}function f(t,e){this.fun=t,this.array=e}function h(){}r.nextTick=function(t){var e=new Array(arguments.length-1);if(arguments.length>1)for(var n=1;n<arguments.length;n++)e[n-1]=arguments[n];u.push(new f(t,e)),1!==u.length||c||s(A)},f.prototype.run=function(){this.fun.apply(null,this.array)},r.title="browser",r.browser=!0,r.env={},r.argv=[],r.version="",r.versions={},r.on=h,r.addListener=h,r.once=h,r.off=h,r.removeListener=h,r.removeAllListeners=h,r.emit=h,r.prependListener=h,r.prependOnceListener=h,r.listeners=function(t){return[]},r.binding=function(t){throw new Error("process.binding is not supported")},r.cwd=function(){return"/"},r.chdir=function(t){throw new Error("process.chdir is not supported")},r.umask=function(){return 0}},function(t,e,n){"use strict";var i=n(0);t.exports=function(t,e){i.forEach(t,function(n,i){i!==e&&i.toUpperCase()===e.toUpperCase()&&(t[e]=n,delete t[i])})}},function(t,e,n){"use strict";var i=n(14);t.exports=function(t,e,n){var r=n.config.validateStatus;n.status&&r&&!r(n.status)?e(i("Request failed with status code "+n.status,n.config,null,n.request,n)):t(n)}},function(t,e,n){"use strict";t.exports=function(t,e,n,i,r){return t.config=e,n&&(t.code=n),t.request=i,t.response=r,t}},function(t,e,n){"use strict";var i=n(0);function r(t){return encodeURIComponent(t).replace(/%40/gi,"@").replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+").replace(/%5B/gi,"[").replace(/%5D/gi,"]")}t.exports=function(t,e,n){if(!e)return t;var o;if(n)o=n(e);else if(i.isURLSearchParams(e))o=e.toString();else{var a=[];i.forEach(e,function(t,e){null!=t&&(i.isArray(t)?e+="[]":t=[t],i.forEach(t,function(t){i.isDate(t)?t=t.toISOString():i.isObject(t)&&(t=JSON.stringify(t)),a.push(r(e)+"="+r(t))}))}),o=a.join("&")}return o&&(t+=(-1===t.indexOf("?")?"?":"&")+o),t}},function(t,e,n){"use strict";var i=n(0),r=["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"];t.exports=function(t){var e,n,o,a={};return t?(i.forEach(t.split("\n"),function(t){if(o=t.indexOf(":"),e=i.trim(t.substr(0,o)).toLowerCase(),n=i.trim(t.substr(o+1)),e){if(a[e]&&r.indexOf(e)>=0)return;a[e]="set-cookie"===e?(a[e]?a[e]:[]).concat([n]):a[e]?a[e]+", "+n:n}}),a):a}},function(t,e,n){"use strict";var i=n(0);t.exports=i.isStandardBrowserEnv()?function(){var t,e=/(msie|trident)/i.test(navigator.userAgent),n=document.createElement("a");function r(t){var i=t;return e&&(n.setAttribute("href",i),i=n.href),n.setAttribute("href",i),{href:n.href,protocol:n.protocol?n.protocol.replace(/:$/,""):"",host:n.host,search:n.search?n.search.replace(/^\?/,""):"",hash:n.hash?n.hash.replace(/^#/,""):"",hostname:n.hostname,port:n.port,pathname:"/"===n.pathname.charAt(0)?n.pathname:"/"+n.pathname}}return t=r(window.location.href),function(e){var n=i.isString(e)?r(e):e;return n.protocol===t.protocol&&n.host===t.host}}():function(){return!0}},function(t,e,n){"use strict";var i="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";function r(){this.message="String contains an invalid character"}r.prototype=new Error,r.prototype.code=5,r.prototype.name="InvalidCharacterError",t.exports=function(t){for(var e,n,o=String(t),a="",s=0,l=i;o.charAt(0|s)||(l="=",s%1);a+=l.charAt(63&e>>8-s%1*8)){if((n=o.charCodeAt(s+=.75))>255)throw new r;e=e<<8|n}return a}},function(t,e,n){"use strict";var i=n(0);t.exports=i.isStandardBrowserEnv()?{write:function(t,e,n,r,o,a){var s=[];s.push(t+"="+encodeURIComponent(e)),i.isNumber(n)&&s.push("expires="+new Date(n).toGMTString()),i.isString(r)&&s.push("path="+r),i.isString(o)&&s.push("domain="+o),!0===a&&s.push("secure"),document.cookie=s.join("; ")},read:function(t){var e=document.cookie.match(new RegExp("(^|;\\s*)("+t+")=([^;]*)"));return e?decodeURIComponent(e[3]):null},remove:function(t){this.write(t,"",Date.now()-864e5)}}:{write:function(){},read:function(){return null},remove:function(){}}},function(t,e,n){"use strict";var i=n(0);function r(){this.handlers=[]}r.prototype.use=function(t,e){return this.handlers.push({fulfilled:t,rejected:e}),this.handlers.length-1},r.prototype.eject=function(t){this.handlers[t]&&(this.handlers[t]=null)},r.prototype.forEach=function(t){i.forEach(this.handlers,function(e){null!==e&&t(e)})},t.exports=r},function(t,e,n){"use strict";var i=n(0),r=n(45),o=n(15),a=n(10),s=n(46),l=n(47);function u(t){t.cancelToken&&t.cancelToken.throwIfRequested()}t.exports=function(t){return u(t),t.baseURL&&!s(t.url)&&(t.url=l(t.baseURL,t.url)),t.headers=t.headers||{},t.data=r(t.data,t.headers,t.transformRequest),t.headers=i.merge(t.headers.common||{},t.headers[t.method]||{},t.headers||{}),i.forEach(["delete","get","head","post","put","patch","common"],function(e){delete t.headers[e]}),(t.adapter||a.adapter)(t).then(function(e){return u(t),e.data=r(e.data,e.headers,t.transformResponse),e},function(e){return o(e)||(u(t),e&&e.response&&(e.response.data=r(e.response.data,e.response.headers,t.transformResponse))),Promise.reject(e)})}},function(t,e,n){"use strict";var i=n(0);t.exports=function(t,e,n){return i.forEach(n,function(n){t=n(t,e)}),t}},function(t,e,n){"use strict";t.exports=function(t){return/^([a-z][a-z\d\+\-\.]*:)?\/\//i.test(t)}},function(t,e,n){"use strict";t.exports=function(t,e){return e?t.replace(/\/+$/,"")+"/"+e.replace(/^\/+/,""):t}},function(t,e,n){"use strict";var i=n(16);function r(t){if("function"!=typeof t)throw new TypeError("executor must be a function.");var e;this.promise=new Promise(function(t){e=t});var n=this;t(function(t){n.reason||(n.reason=new i(t),e(n.reason))})}r.prototype.throwIfRequested=function(){if(this.reason)throw this.reason},r.source=function(){var t;return{token:new r(function(e){t=e}),cancel:t}},t.exports=r},function(t,e,n){"use strict";t.exports=function(t){return function(e){return t.apply(null,e)}}},function(t,e){var n,i;n="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",i={rotl:function(t,e){return t<<e|t>>>32-e},rotr:function(t,e){return t<<32-e|t>>>e},endian:function(t){if(t.constructor==Number)return 16711935&i.rotl(t,8)|4278255360&i.rotl(t,24);for(var e=0;e<t.length;e++)t[e]=i.endian(t[e]);return t},randomBytes:function(t){for(var e=[];t>0;t--)e.push(Math.floor(256*Math.random()));return e},bytesToWords:function(t){for(var e=[],n=0,i=0;n<t.length;n++,i+=8)e[i>>>5]|=t[n]<<24-i%32;return e},wordsToBytes:function(t){for(var e=[],n=0;n<32*t.length;n+=8)e.push(t[n>>>5]>>>24-n%32&255);return e},bytesToHex:function(t){for(var e=[],n=0;n<t.length;n++)e.push((t[n]>>>4).toString(16)),e.push((15&t[n]).toString(16));return e.join("")},hexToBytes:function(t){for(var e=[],n=0;n<t.length;n+=2)e.push(parseInt(t.substr(n,2),16));return e},bytesToBase64:function(t){for(var e=[],i=0;i<t.length;i+=3)for(var r=t[i]<<16|t[i+1]<<8|t[i+2],o=0;o<4;o++)8*i+6*o<=8*t.length?e.push(n.charAt(r>>>6*(3-o)&63)):e.push("=");return e.join("")},base64ToBytes:function(t){t=t.replace(/[^A-Z0-9+\/]/gi,"");for(var e=[],i=0,r=0;i<t.length;r=++i%4)0!=r&&e.push((n.indexOf(t.charAt(i-1))&Math.pow(2,-2*r+8)-1)<<2*r|n.indexOf(t.charAt(i))>>>6-2*r);return e}},t.exports=i},function(t,e,n){"use strict";var i=n(5);n.n(i).a},function(t,e,n){(t.exports=n(2)(!1)).push([t.i,"\n.avatardiv[data-v-100e3b6f] {\n\tdisplay: inline-block;\n}\n.avatardiv.unknown[data-v-100e3b6f] {\n\tbackground-color: var(--color-text-maxcontrast);\n\tposition: relative;\n}\n.avatardiv > .unknown[data-v-100e3b6f] {\n\tposition: absolute;\n\tcolor: var(--color-main-background);\n\twidth: 100%;\n\ttext-align: center;\n\tdisplay: block;\n\tleft: 0;\n\ttop: 0;\n}\n.avatardiv img[data-v-100e3b6f] {\n\twidth: 100%;\n\theight: 100%;\n}\n.popovermenu-wrapper[data-v-100e3b6f] {\n\tposition: relative;\n\tdisplay: inline-block;\n}\n.popovermenu[data-v-100e3b6f] {\n\tdisplay: block;\n\tmargin: 0;\n\tfont-size: initial;\n}\n",""])},function(t,e,n){"use strict";var i=n(6);n.n(i).a},function(t,e,n){(t.exports=n(2)(!1)).push([t.i,".option[data-v-72601db4] {\n  display: flex;\n  align-items: center;\n  height: 32px;\n  width: 100%;\n}\n.option__avatar[data-v-72601db4] {\n    flex: 0 0 32px;\n    width: 32px;\n    height: 32px;\n    margin-right: 6px;\n}\n.option__desc[data-v-72601db4] {\n    display: flex;\n    flex-direction: column;\n    justify-content: center;\n    flex: 1 1;\n}\n.option__desc--lineone[data-v-72601db4] {\n      color: var(--color-text-light);\n}\n.option__desc--lineone--highlight[data-v-72601db4] {\n        font-weight: 600;\n}\n.option__desc--linetwo[data-v-72601db4] {\n      opacity: .7;\n}\n.option__icon[data-v-72601db4] {\n    width: 44px;\n    height: 44px;\n    flex: 0 0 44px;\n    margin: -6px;\n    opacity: .5;\n}\n",""])},function(t,e,n){var i=n(56);"string"==typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);(0,n(3).default)("3eae9ff2",i,!1,{})},function(t,e,n){(t.exports=n(2)(!1)).push([t.i,".multiselect[data-v-97c9b20] {\n  margin: 0;\n  padding: 0 !important;\n  display: inline-block;\n  /* override this rule with your width styling if you need */\n  min-width: 160px;\n  position: relative;\n  background-color: var(--color-main-background);\n  /* results wrapper */\n  /* ABOVE display */\n  /* Icon before option select */\n  /* No need for an icon here */\n  /* Mouse feedback */ }\n  .multiselect[data-v-97c9b20].multiselect--active {\n    /* Opened: force display the input */ }\n    .multiselect[data-v-97c9b20].multiselect--active input.multiselect__input {\n      opacity: 1 !important;\n      cursor: text !important;\n      border-radius: var(--border-radius) var(--border-radius) 0 0; }\n  .multiselect[data-v-97c9b20].multiselect--active.multiselect--above input.multiselect__input {\n    border-radius: 0 0 var(--border-radius) var(--border-radius); }\n  .multiselect[data-v-97c9b20].multiselect--disabled,\n  .multiselect[data-v-97c9b20].multiselect--disabled .multiselect__single {\n    background-color: var(--color-background-dark) !important; }\n  .multiselect[data-v-97c9b20].icon-loading-small::after {\n    left: 100%;\n    margin-left: -24px; }\n  .multiselect[data-v-97c9b20] .multiselect__tags {\n    /* space between tags and limit tag */\n    display: flex;\n    flex-wrap: nowrap;\n    overflow: hidden;\n    border: 1px solid var(--color-border-dark);\n    cursor: pointer;\n    position: relative;\n    border-radius: 3px;\n    height: 34px;\n    /* tag wrapper */\n    /* Single select default value\n\t\tor default placeholder if search disabled*/\n    /* displayed text if tag limit reached */\n    /* default multiselect input for search and placeholder */ }\n    .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__tags-wrap {\n      align-items: center;\n      display: inline-flex;\n      overflow: hidden;\n      max-width: 100%;\n      position: relative;\n      padding: 3px 5px;\n      flex-grow: 1;\n      /* no tags or simple select? Show input directly\n\t\t\tinput is used to display single value */\n      /* selected tag */ }\n      .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__tags-wrap:empty ~ input.multiselect__input {\n        opacity: 1 !important;\n        /* hide default empty text like .multiselect__placeholder,\n\t\t\t\tand show input instead. It looks better without a transition between\n\t\t\t\ta span and the input that have different styling */ }\n        .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__tags-wrap:empty ~ input.multiselect__input + span:not(.multiselect__single) {\n          display: none; }\n      .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__tags-wrap .multiselect__tag {\n        flex: 1 0 0;\n        line-height: 20px;\n        padding: 1px 5px;\n        background-image: none;\n        color: var(--color-text-lighter);\n        border: 1px solid var(--color-border-dark);\n        display: inline-flex;\n        align-items: center;\n        border-radius: 3px;\n        /* require to override the default width\n\t\t\t\tand force the tag to shring properly */\n        min-width: 0;\n        max-width: 50%;\n        max-width: fit-content;\n        max-width: -moz-fit-content;\n        /* css hack, detect if more than two tags\n\t\t\t\tif so, flex-basis is set to half */\n        /* ellipsis the groups to be sure\n\t\t\t\twe display at least two of them */ }\n        .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__tags-wrap .multiselect__tag:only-child {\n          flex: 0 1 auto; }\n        .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__tags-wrap .multiselect__tag:not(:last-child) {\n          margin-right: 5px; }\n        .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__tags-wrap .multiselect__tag > span {\n          white-space: nowrap;\n          text-overflow: ellipsis;\n          overflow: hidden; }\n    .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__single,\n    .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__placeholder {\n      padding: 7px 6px;\n      flex: 0 0 100%;\n      z-index: 1;\n      /* above input */\n      background-color: var(--color-main-background);\n      cursor: pointer;\n      line-height: 18px;\n      color: var(--color-text-lighter); }\n    .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__strong,\n    .multiselect[data-v-97c9b20] .multiselect__tags .multiselect__limit {\n      flex: 0 0 auto;\n      line-height: 20px;\n      color: var(--color-text-lighter);\n      display: inline-flex;\n      align-items: center;\n      opacity: .7;\n      margin-right: 5px;\n      /* above the input */\n      z-index: 5; }\n    .multiselect[data-v-97c9b20] .multiselect__tags input.multiselect__input {\n      width: 100% !important;\n      position: absolute !important;\n      margin: 0;\n      opacity: 0;\n      /* let's leave it on top of tags but hide it */\n      height: 100%;\n      border: none;\n      /* override hide to force show the placeholder */\n      display: block !important;\n      /* only when not active */\n      cursor: pointer;\n      /* override inline styling of the lib */\n      padding: 7px 6px !important; }\n  .multiselect[data-v-97c9b20] .multiselect__content-wrapper {\n    position: absolute;\n    width: 100%;\n    margin-top: -1px;\n    border: 1px solid var(--color-border-dark);\n    background: var(--color-main-background);\n    z-index: 50;\n    max-height: 250px;\n    overflow-y: auto;\n    border-radius: 0 0 var(--border-radius) var(--border-radius); }\n    .multiselect[data-v-97c9b20] .multiselect__content-wrapper .multiselect__content {\n      width: 100%;\n      padding: 0; }\n    .multiselect[data-v-97c9b20] .multiselect__content-wrapper li {\n      position: relative;\n      display: flex;\n      align-items: center;\n      background-color: transparent; }\n      .multiselect[data-v-97c9b20] .multiselect__content-wrapper li,\n      .multiselect[data-v-97c9b20] .multiselect__content-wrapper li span {\n        cursor: pointer; }\n      .multiselect[data-v-97c9b20] .multiselect__content-wrapper li > span {\n        padding: 8px;\n        white-space: nowrap;\n        overflow: hidden;\n        text-overflow: ellipsis;\n        margin: 0;\n        height: auto;\n        min-height: 1em;\n        -webkit-touch-callout: none;\n        -webkit-user-select: none;\n        -moz-user-select: none;\n        -ms-user-select: none;\n        user-select: none;\n        display: inline-flex;\n        align-items: center;\n        background-color: transparent;\n        color: var(--color-text-lighter);\n        width: 100%;\n        /* selected checkmark icon */\n        /* add the prop tag-placeholder=\"create\" to add the +\n\t\t\t\ticon on top of an unknown-and-ready-to-be-created entry */ }\n        .multiselect[data-v-97c9b20] .multiselect__content-wrapper li > span::before {\n          content: ' ';\n          background-repeat: no-repeat;\n          background-position: center;\n          min-width: 16px;\n          min-height: 16px;\n          display: block;\n          opacity: .5;\n          margin-right: 5px;\n          visibility: hidden; }\n        .multiselect[data-v-97c9b20] .multiselect__content-wrapper li > span.multiselect__option--disabled {\n          background-color: var(--color-background-dark);\n          opacity: .5; }\n        .multiselect[data-v-97c9b20] .multiselect__content-wrapper li > span[data-select='create']::before {\n          background-image: var(--icon-add-000);\n          visibility: visible; }\n        .multiselect[data-v-97c9b20] .multiselect__content-wrapper li > span.multiselect__option--highlight {\n          color: var(--color-main-text);\n          background-color: var(--color-background-dark); }\n        .multiselect[data-v-97c9b20] .multiselect__content-wrapper li > span:not(.multiselect__option--disabled):hover::before {\n          opacity: .3; }\n        .multiselect[data-v-97c9b20] .multiselect__content-wrapper li > span.multiselect__option--selected::before, .multiselect[data-v-97c9b20] .multiselect__content-wrapper li > span:not(.multiselect__option--disabled):hover::before {\n          visibility: visible; }\n  .multiselect[data-v-97c9b20].multiselect--above .multiselect__content-wrapper {\n    bottom: 100%;\n    margin-bottom: -1px; }\n  .multiselect[data-v-97c9b20].multiselect--multiple .multiselect__content-wrapper li > span::before {\n    background-image: var(--icon-checkmark-000); }\n  .multiselect[data-v-97c9b20].multiselect--single .multiselect__content-wrapper li > span::before {\n    display: none; }\n  .multiselect[data-v-97c9b20]:hover .multiselect__placeholder,\n  .multiselect[data-v-97c9b20] input.multiselect__input .multiselect__placeholder {\n    color: var(--color-main-text); }\n",""])},function(t,e,n){"use strict";var i=n(7);n.n(i).a},function(t,e,n){(t.exports=n(2)(!1)).push([t.i,".action-item[data-v-886e6e62] {\n  display: inline-block;\n}\n.action-item--single[data-v-886e6e62], .action-item__menutoggle[data-v-886e6e62] {\n    padding: 14px;\n    height: 44px;\n    width: 44px;\n    cursor: pointer;\n}\n.action-item__menutoggle[data-v-886e6e62] {\n    display: inline-block;\n}\n.action-item--multiple[data-v-886e6e62] {\n    position: relative;\n}\n",""])},function(e,n,i){"use strict";i.r(n);var r={};i.r(r),i.d(r,"AppContent",function(){return l}),i.d(r,"AppNavigationItem",function(){return b}),i.d(r,"AppNavigationNew",function(){return x}),i.d(r,"AppNavigationSettings",function(){return E}),i.d(r,"PopoverMenu",function(){return m}),i.d(r,"DatetimePicker",function(){return N}),i.d(r,"Multiselect",function(){return tt}),i.d(r,"Avatar",function(){return W}),i.d(r,"Action",function(){return rt});var o=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{class:"app-"+t.appName,attrs:{id:"content"}},[void 0!==t.$slots.navigation?n("div",{class:t.navigationClass,attrs:{id:"app-navigation"}},[t._t("navigation")],2):t._e(),t._v(" "),void 0!==t.$slots.content?n("div",{class:t.contentClass,attrs:{id:"app-content"}},[t._t("content")],2):t._e(),t._v(" "),t._t("default"),t._v(" "),void 0!==t.$slots.sidebar?n("div",{attrs:{id:"app-sidebar"}},[t._t("sidebar")],2):t._e()],2)};function a(t,e,n,i,r,o,a,s){var l,u="function"==typeof t?t.options:t;if(e&&(u.render=e,u.staticRenderFns=n,u._compiled=!0),i&&(u.functional=!0),o&&(u._scopeId="data-v-"+o),a?(l=function(t){(t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext)||"undefined"==typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),r&&r.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(a)},u._ssrRegister=l):r&&(l=s?function(){r.call(this,this.$root.$options.shadowRoot)}:r),l)if(u.functional){u._injectStyles=l;var c=u.render;u.render=function(t,e){return l.call(e),c(t,e)}}else{var p=u.beforeCreate;u.beforeCreate=p?[].concat(p,l):[l]}return{exports:t,options:u}}o._withStripped=!0;var s=a({props:{appName:{type:String,required:!0},navigationClass:{type:[String,Array,Object],required:!1,default:""},contentClass:{type:[String,Array,Object],required:!1,default:""}}},o,[],!1,null,null,null);s.options.__file="src/components/AppContent/AppContent.vue";var l=s.exports,u=function(){var t=this,e=t.$createElement,n=t._self._c||e;return t.item.caption?n("li",{staticClass:"app-navigation-caption"},[t._v("\n\t"+t._s(t.item.text)+"\n")]):n("nav-element",t._b({class:[{"icon-loading-small":t.item.loading,open:t.opened,collapsible:t.collapsible},t.item.classes],attrs:{id:t.item.id,title:t.item.title}},"nav-element",t.navElement(t.item),!1),[t.item.bullet?n("div",{staticClass:"app-navigation-entry-bullet",style:{backgroundColor:t.item.bullet}}):t._e(),t._v(" "),t.collapsible?n("button",{staticClass:"collapse",on:{click:function(e){return e.preventDefault(),e.stopPropagation(),t.toggleCollapse(e)}}}):t._e(),t._v(" "),t.simpleAction?n("a",{class:t.item.icon,attrs:{href:"#"},on:{click:function(e){return e.preventDefault(),e.stopPropagation(),t.simpleAction(e)}}},[t.item.iconUrl?n("img",{attrs:{alt:t.item.text,src:t.item.iconUrl}}):t._e(),t._v("\n\t\t"+t._s(t.item.text)+"\n\t")]):n("a",{class:t.item.icon,attrs:{href:t.item.href?t.item.href:"#"}},[t.item.iconUrl?n("img",{attrs:{alt:t.item.text,src:t.item.iconUrl}}):t._e(),t._v("\n\t\t"+t._s(t.item.text)+"\n\t")]),t._v(" "),t.item.utils?n("div",{staticClass:"app-navigation-entry-utils"},[n("ul",[Number.isInteger(t.item.utils.counter)&&t.item.utils.counter>0?n("li",{staticClass:"app-navigation-entry-utils-counter"},[t._v("\n\t\t\t\t"+t._s(t.item.utils.counter)+"\n\t\t\t")]):t._e(),t._v(" "),t.item.utils.actions&&1===t.item.utils.actions.length?n("li",{staticClass:"app-navigation-entry-utils-menu-button"},[n("button",{class:t.item.utils.actions[0].icon,attrs:{title:t.item.utils.actions[0].text},on:{click:t.item.utils.actions[0].action}})]):t.item.utils.actions&&2===t.item.utils.actions.length&&!Number.isInteger(t.item.utils.counter)?t._l(t.item.utils.actions,function(t){return n("li",{key:t.action,staticClass:"app-navigation-entry-utils-menu-button"},[n("button",{class:t.icon,attrs:{title:t.text},on:{click:t.action}})])}):t.item.utils.actions&&t.item.utils.actions.length>1&&(Number.isInteger(t.item.utils.counter)||t.item.utils.actions.length>2)?n("li",{staticClass:"app-navigation-entry-utils-menu-button"},[n("button",{directives:[{name:"click-outside",rawName:"v-click-outside",value:t.hideMenu,expression:"hideMenu"}],on:{click:t.showMenu}})]):t._e()],2)]):t._e(),t._v(" "),t.item.utils&&t.item.utils.actions&&t.item.utils.actions.length>1&&(Number.isInteger(t.item.utils.counter)||t.item.utils.actions.length>2)?n("div",{staticClass:"app-navigation-entry-menu",class:{open:t.openedMenu}},[n("popover-menu",{attrs:{menu:t.item.utils.actions}})],1):t._e(),t._v(" "),t.item.undo?n("div",{staticClass:"app-navigation-entry-deleted"},[n("div",{staticClass:"app-navigation-entry-deleted-description"},[t._v("\n\t\t\t"+t._s(t.item.undo.text)+"\n\t\t")]),t._v(" "),n("button",{staticClass:"app-navigation-entry-deleted-button icon-history",attrs:{title:t.t("settings","Undo")}})]):t._e(),t._v(" "),t.item.edit?n("div",{staticClass:"app-navigation-entry-edit"},[n("form",{on:{submit:function(e){return e.preventDefault(),e.stopPropagation(),t.item.edit.action(e)}}},[n("input",{attrs:{placeholder:t.item.edit.text,type:"text"}}),t._v(" "),n("input",{staticClass:"icon-confirm",attrs:{type:"submit",value:""}}),t._v(" "),n("input",{staticClass:"icon-close",attrs:{type:"submit",value:""},on:{click:function(e){return e.stopPropagation(),e.preventDefault(),t.cancelEdit(e)}}})])]):t._e(),t._v(" "),t.item.children?n("ul",t._l(t.item.children,function(t,e){return n("app-navigation-item",{key:e,attrs:{item:t}})}),1):t._e()])};
/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */u._withStripped=!0;var c=function(){var t=this.$createElement,e=this._self._c||t;return e("ul",this._l(this.menu,function(t,n){return e("popover-menu-item",{key:n,attrs:{item:t}})}),1)};c._withStripped=!0;var p=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("li",[t.item.href?n("a",{attrs:{href:t.item.href?t.item.href:"#",target:t.item.target?t.item.target:"",rel:"noreferrer noopener"},on:{click:t.action}},[t.iconIsUrl?n("img",{attrs:{src:t.item.icon}}):n("span",{class:t.item.icon}),t._v(" "),t.item.text&&t.item.longtext?n("p",[n("strong",{staticClass:"menuitem-text"},[t._v("\n\t\t\t\t"+t._s(t.item.text)+"\n\t\t\t")]),n("br"),t._v(" "),n("span",{staticClass:"menuitem-text-detail"},[t._v("\n\t\t\t\t"+t._s(t.item.longtext)+"\n\t\t\t")])]):t.item.text?n("span",[t._v("\n\t\t\t"+t._s(t.item.text)+"\n\t\t")]):t.item.longtext?n("p",[t._v("\n\t\t\t"+t._s(t.item.longtext)+"\n\t\t")]):t._e()]):t.item.input?n("span",{staticClass:"menuitem",class:{active:t.item.active}},["checkbox"!==t.item.input?n("span",{class:t.item.icon}):t._e(),t._v(" "),"text"===t.item.input?n("form",{class:t.item.input,on:{submit:function(e){return e.preventDefault(),t.item.action(e)}}},[n("input",{attrs:{type:t.item.input,placeholder:t.item.text,required:""},domProps:{value:t.item.value}}),t._v(" "),n("input",{staticClass:"icon-confirm",attrs:{type:"submit",value:""}})]):["checkbox"===t.item.input?n("input",{directives:[{name:"model",rawName:"v-model",value:t.item.model,expression:"item.model"}],class:t.item.input,attrs:{id:t.key,type:"checkbox"},domProps:{checked:Array.isArray(t.item.model)?t._i(t.item.model,null)>-1:t.item.model},on:{change:[function(e){var n=t.item.model,i=e.target,r=!!i.checked;if(Array.isArray(n)){var o=t._i(n,null);i.checked?o<0&&t.$set(t.item,"model",n.concat([null])):o>-1&&t.$set(t.item,"model",n.slice(0,o).concat(n.slice(o+1)))}else t.$set(t.item,"model",r)},t.item.action]}}):"radio"===t.item.input?n("input",{directives:[{name:"model",rawName:"v-model",value:t.item.model,expression:"item.model"}],class:t.item.input,attrs:{id:t.key,type:"radio"},domProps:{checked:t._q(t.item.model,null)},on:{change:[function(e){return t.$set(t.item,"model",null)},t.item.action]}}):n("input",{directives:[{name:"model",rawName:"v-model",value:t.item.model,expression:"item.model"}],class:t.item.input,attrs:{id:t.key,type:t.item.input},domProps:{value:t.item.model},on:{change:t.item.action,input:function(e){e.target.composing||t.$set(t.item,"model",e.target.value)}}}),t._v(" "),n("label",{attrs:{for:t.key},on:{click:function(e){return e.stopPropagation(),e.preventDefault(),t.item.action(e)}}},[t._v("\n\t\t\t\t"+t._s(t.item.text)+"\n\t\t\t")])]],2):t.item.action?n("button",{staticClass:"menuitem",class:{active:t.item.active},on:{click:function(e){return e.stopPropagation(),e.preventDefault(),t.item.action(e)}}},[n("span",{class:t.item.icon}),t._v(" "),t.item.text&&t.item.longtext?n("p",[n("strong",{staticClass:"menuitem-text"},[t._v("\n\t\t\t\t"+t._s(t.item.text)+"\n\t\t\t")]),n("br"),t._v(" "),n("span",{staticClass:"menuitem-text-detail"},[t._v("\n\t\t\t\t"+t._s(t.item.longtext)+"\n\t\t\t")])]):t.item.text?n("span",[t._v("\n\t\t\t"+t._s(t.item.text)+"\n\t\t")]):t.item.longtext?n("p",[t._v("\n\t\t\t"+t._s(t.item.longtext)+"\n\t\t")]):t._e()]):n("span",{staticClass:"menuitem",class:{active:t.item.active}},[n("span",{class:t.item.icon}),t._v(" "),t.item.text&&t.item.longtext?n("p",[n("strong",{staticClass:"menuitem-text"},[t._v("\n\t\t\t\t"+t._s(t.item.text)+"\n\t\t\t")]),n("br"),t._v(" "),n("span",{staticClass:"menuitem-text-detail"},[t._v("\n\t\t\t\t"+t._s(t.item.longtext)+"\n\t\t\t")])]):t.item.text?n("span",[t._v("\n\t\t\t"+t._s(t.item.text)+"\n\t\t")]):t.item.longtext?n("p",[t._v("\n\t\t\t"+t._s(t.item.longtext)+"\n\t\t")]):t._e()])])};p._withStripped=!0;var d={name:"PopoverMenuItem",props:{item:{type:Object,required:!0,default:function(){return{key:"nextcloud-link",href:"https://nextcloud.com",icon:"icon-links",text:"Nextcloud"}},validator:function(t){return!t.input||-1!==["text","checkbox"].indexOf(t.input)}}},computed:{key:function(){return this.item.key?this.item.key:Math.round(16*Math.random()*1e6).toString(16)},iconIsUrl:function(){try{return new URL(this.item.icon),!0}catch(t){return!1}}},methods:{action:function(t){this.item.action&&this.item.action(t)}}},A=(i(21),a(d,p,[],!1,null,"512ea768",null));A.options.__file="src/components/PopoverMenu/PopoverMenuItem.vue";var f=a({name:"PopoverMenu",components:{PopoverMenuItem:A.exports},props:{menu:{type:Array,default:function(){return[{href:"https://nextcloud.com",icon:"icon-links",text:"Nextcloud"}]},required:!0}}},c,[],!1,null,null,null);f.options.__file="src/components/PopoverMenu/PopoverMenu.vue";var h=f.exports,m=h,v=i(1),g=i.n(v),y=a({name:"AppNavigationItem",components:{PopoverMenu:h},directives:{ClickOutside:g.a},props:{item:{type:Object,required:!0}},data:function(){return{openedMenu:!1,opened:!!this.item.opened}},computed:{collapsible:function(){return this.item.collapsible&&this.item.children&&this.item.children.length>0},simpleAction:function(){return this.collapsible&&!this.item.action?this.toggleCollapse:this.item.action}},watch:{item:function(t,e){this.opened=!!e.opened}},mounted:function(){this.popupItem=this.$el},methods:{showMenu:function(){this.openedMenu=!0},hideMenu:function(){this.openedMenu=!1},toggleCollapse:function(){this.opened=!this.opened},cancelEdit:function(t){Array.isArray(this.item.classes)&&(this.item.classes=this.item.classes.filter(function(t){return"editing"!==t})),this.item.edit.reset(t)},navElement:function(t){if(t.router){var e=t.router.exact;return void 0===t.router.exact&&(e=!0),{is:"router-link",tag:"li",to:t.router,exact:e}}return{is:"li"}}}},u,[],!1,null,null,null);
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */y.options.__file="src/components/AppNavigationItem/AppNavigationItem.vue";var b=y.exports,w=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"app-navigation-new"},[n("button",{class:t.buttonClass,attrs:{id:t.buttonId,type:"button",disabled:t.disabled},on:{click:function(e){return t.$emit("click")}}},[t._v("\n\t\t"+t._s(t.text)+"\n\t")])])};
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */w._withStripped=!0;var _=a({props:{buttonId:{type:String,required:!1,default:""},buttonClass:{type:String,required:!1,default:""},disabled:{type:Boolean,required:!1,default:!1},text:{type:String,required:!0}}},w,[],!1,null,null,null);_.options.__file="src/components/AppNavigationNew/AppNavigationNew.vue";var x=_.exports,D=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{directives:[{name:"click-outside",rawName:"v-click-outside",value:t.closeMenu,expression:"closeMenu"}],class:{open:t.open},attrs:{id:"app-settings"}},[n("div",{attrs:{id:"app-settings-header"}},[n("button",{staticClass:"settings-button",attrs:{"data-apps-slide-toggle":"#app-settings-content"},on:{click:t.toggleMenu}},[t._v("\n\t\t\t"+t._s(t.title)+"\n\t\t")])]),t._v(" "),n("div",{attrs:{id:"app-settings-content"}},[t._t("default")],2)])};
/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */D._withStripped=!0;var C=a({directives:{ClickOutside:g.a},props:{title:{type:String,required:!1,default:t("core","Settings")}},data:function(){return{open:!1}},methods:{toggleMenu:function(){this.open=!this.open},closeMenu:function(){this.open=!1}}},D,[],!1,null,null,null);C.options.__file="src/components/AppNavigationSettings/AppNavigationSettings.vue";var E=C.exports,k=function(t){t.mounted?Array.isArray(t.mounted)||(t.mounted=[t.mounted]):t.mounted=[],t.mounted.push(function(){this.$el.setAttribute("data-v-".concat("97c9b20"),"")})},S=function(){var t=this,e=t.$createElement;return(t._self._c||e)("date-picker",t._g(t._b({attrs:{"minute-step":10,clearable:!1,value:t.value},on:{"update:value":function(e){return t.$emit("update:value",t.value)}}},"date-picker",t.$attrs,!1),t.$listeners))};
/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */S._withStripped=!0;var O=i(9),T=i.n(O);T.a.components.CalendarPanel.components.PanelTime.methods.stringifyText=function(t){return t},T.a.methods.displayPopup=function(){var t=this.$el.querySelector(".mx-datepicker-popup");t&&!t.classList.contains("popovermenu")&&(t.className+=" popovermenu menu-center open")};var M=a({name:"DatetimePicker",components:{DatePicker:T.a},inheritAttrs:!1,props:{value:{default:function(){return new Date}}}},S,[],!1,null,null,null);M.options.__file="src/components/DatetimePicker/DatetimePicker.vue";var B=M.exports;i(23);
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
k(B);var N=B,I=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("vue-multiselect",t._g(t._b({class:{"icon-loading-small":t.loading,"multiselect--multiple":t.multiple,"multiselect--single":!t.multiple},attrs:{value:t.value,limit:t.maxOptions,"close-on-select":!t.multiple,multiple:t.multiple,label:t.label,"track-by":t.trackBy,"tag-placeholder":"create"},on:{"update:value":function(e){return t.$emit("update:value",t.value)}},scopedSlots:t._u([{key:"option",fn:function(e){return t.$scopedSlots.option||t.userSelect?[t.userSelect?n("avatar-select-option",{attrs:{option:e.option}}):t._t("option",null,null,e)]:void 0}},{key:"singleLabel",fn:function(e){return t.$scopedSlots.singleLabel?[t._t("singleLabel",null,null,e)]:void 0}}],!0)},"vue-multiselect",t.$attrs,!1),t.$listeners),[t._v(" "),t.multiple?n("span",{directives:[{name:"tooltip",rawName:"v-tooltip.auto",value:t.formatLimitTitle(t.value),expression:"formatLimitTitle(value)",modifiers:{auto:!0}}],staticClass:"multiselect__limit",attrs:{slot:"limit"},slot:"limit"},[t._v("\n\t\t"+t._s(t.limitString)+"\n\t")]):t._e()])};I._withStripped=!0;var L=i(18),P=i.n(L),j=i(8),$=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("span",{staticClass:"option"},[n("avatar",{staticClass:"option__avatar",attrs:{"display-name":t.option.displayName,user:t.option.user,"disable-tooltip":!0,"is-no-user":t.option.isNoUser}}),t._v(" "),n("div",{staticClass:"option__desc"},[n("span",{staticClass:"option__desc--lineone"},[t._v("\n\t\t\t"+t._s(t.option.displayName)+"\n\t\t")]),t._v(" "),t.option.desc?n("span",{staticClass:"option__desc--linetwo"},[t._v("\n\t\t\t"+t._s(t.option.desc)+"\n\t\t")]):t._e()]),t._v(" "),t.option.icon?n("span",{staticClass:"icon option__icon",class:t.option.icon}):t._e()],1)};$._withStripped=!0;var Y=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{directives:[{name:"tooltip",rawName:"v-tooltip",value:t.tooltip,expression:"tooltip"},{name:"click-outside",rawName:"v-click-outside",value:t.closeMenu,expression:"closeMenu"}],staticClass:"avatardiv popovermenu-wrapper",class:{"icon-loading":t.loadingState,unknown:t.userDoesNotExist},style:t.avatarStyle,on:{click:t.toggleMenu}},[t.loadingState||t.userDoesNotExist?t._e():n("img",{attrs:{src:t.avatarUrlLoaded,srcset:t.avatarSrcSetLoaded}}),t._v(" "),t.userDoesNotExist?n("div",{staticClass:"unknown"},[t._v("\n\t\t"+t._s(t.initials)+"\n\t")]):t._e(),t._v(" "),n("div",{directives:[{name:"show",rawName:"v-show",value:t.contactsMenuOpenState,expression:"contactsMenuOpenState"}],staticClass:"popovermenu"},[n("popover-menu",{attrs:{"is-open":t.contactsMenuOpenState,menu:t.menu}})],1)])};Y._withStripped=!0;var V=i(19),Q=i.n(V),F=i(20),R=i.n(F),G=function(t){var e=t.toLowerCase();function n(t,e,n){this.r=t,this.g=e,this.b=n}function i(t,e,i){var r=[];r.push(e);for(var o=function(t,e){var n=new Array(3);return n[0]=(e[1].r-e[0].r)/t,n[1]=(e[1].g-e[0].g)/t,n[2]=(e[1].b-e[0].b)/t,n}(t,[e,i]),a=1;a<t;a++){var s=parseInt(e.r+o[0]*a),l=parseInt(e.g+o[1]*a),u=parseInt(e.b+o[2]*a);r.push(new n(s,l,u))}return r}null===e.match(/^([0-9a-f]{4}-?){8}$/)&&(e=R()(e)),e=e.replace(/[^0-9a-f]/g,"");var r=new n(182,70,157),o=new n(221,203,85),a=new n(0,130,201),s=i(6,r,o),l=i(6,o,a),u=i(6,a,r);return s.concat(l).concat(u)[function(t,e){for(var n=0,i=[],r=0;r<t.length;r++)i.push(parseInt(t.charAt(r),16)%16);for(var o in i)n+=i[o];return parseInt(parseInt(n)%e)}(e,18)]},H={name:"Avatar",directives:{tooltip:j.a,ClickOutside:g.a},components:{PopoverMenu:h},props:{url:{type:String,default:void 0},user:{type:String,default:void 0},displayName:{type:String,default:void 0},size:{type:Number,default:32},allowPlaceholder:{type:Boolean,default:!0},disableTooltip:{type:Boolean,default:!1},tooltipMessage:{type:String,default:null},isNoUser:{type:Boolean,default:!1}},data:function(){return{avatarUrlLoaded:null,avatarSrcSetLoaded:null,userDoesNotExist:!1,loadingState:!0,contactsMenuActions:[],contactsMenuOpenState:!1}},computed:{getUserIdentifier:function(){return this.isDisplayNameDefined?this.displayName:this.isUserDefined?this.user:""},isUserDefined:function(){return void 0!==this.user},isDisplayNameDefined:function(){return void 0!==this.displayName},isUrlDefined:function(){return void 0!==this.url},shouldShowPlaceholder:function(){return this.allowPlaceholder&&this.userDoesNotExist},avatarStyle:function(){var t={width:this.size+"px",height:this.size+"px",lineHeight:this.size+"px",fontSize:Math.round(.55*this.size)+"px"};if(!this.shouldShowPlaceholder)return t;var e=G(this.getUserIdentifier);return t.backgroundColor="rgb("+e.r+", "+e.g+", "+e.b+")",t},tooltip:function(){return!this.disableTooltip&&(this.tooltipMessage?this.tooltipMessage:this.displayName)},initials:function(){return this.shouldShowPlaceholder?this.getUserIdentifier.charAt(0).toUpperCase():"?"},menu:function(){return this.contactsMenuActions.map(function(t){return{href:t.hyperlink,icon:t.icon,text:t.title}})}},watch:{url:function(){this.userDoesNotExist=!1,this.loadAvatarUrl()},user:function(){this.userDoesNotExist=!1,this.loadAvatarUrl()}},mounted:function(){this.loadAvatarUrl()},methods:{toggleMenu:function(){this.user===OC.getCurrentUser().uid||this.userDoesNotExist||this.url||(this.contactsMenuOpenState=!this.contactsMenuOpenState,this.contactsMenuOpenState&&this.fetchContactsMenu())},closeMenu:function(){this.contactsMenuOpenState=!1},fetchContactsMenu:function(){var t=this;Q.a.post(OC.generateUrl("contactsmenu/findOne"),"shareType=0&shareWith="+encodeURIComponent(this.user)).then(function(e){t.contactsMenuActions=[e.data.topAction].concat(e.data.actions)}).catch(function(){t.contactsMenuOpenState=!1})},loadAvatarUrl:function(){var t=this;if(this.loadingState=!0,!this.isUrlDefined&&(!this.isUserDefined||this.isNoUser))return this.loadingState=!1,void(this.userDoesNotExist=!0);var e=function(t,e){var n=OC.generateUrl("/avatar/{user}/{size}",{user:t,size:e});return t===OC.getCurrentUser().uid&&"undefined"!=typeof oc_userconfig&&(n+="?v="+oc_userconfig.avatar.version),n},n=e(this.user,this.size);this.isUrlDefined&&(n=this.url);var i=[n+" 1x",e(this.user,2*this.size)+" 2x",e(this.user,4*this.size)+" 4x"].join(", "),r=new Image;r.onload=function(){t.avatarUrlLoaded=n,t.isUrlDefined||(t.avatarSrcSetLoaded=i),t.loadingState=!1},r.onerror=function(){t.userDoesNotExist=!0,t.loadingState=!1},this.isUrlDefined||(r.srcset=i),r.src=n}}},U=(i(51),a(H,Y,[],!1,null,"100e3b6f",null));U.options.__file="src/components/Avatar/Avatar.vue";var W=U.exports,z={name:"AvatarSelectOption",components:{Avatar:W},props:{option:{type:Object,default:function(){return{desc:"",displayName:"Admin",icon:"icon-user",user:"admin",isNoUser:!1}},validator:function(t){return"displayName"in t}}}},Z=(i(53),a(z,$,[],!1,null,"72601db4",null));
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */Z.options.__file="src/components/Multiselect/AvatarSelectOption.vue";var J=Z.exports;function q(t){return(q="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}var X=a({name:"Multiselect",components:{VueMultiselect:P.a,AvatarSelectOption:J},directives:{tooltip:j.a},inheritAttrs:!1,props:{value:{default:function(){return[]}},multiple:{type:Boolean,default:!1},limit:{type:Number,default:99999},label:{type:String},trackBy:{type:String},userSelect:{type:Boolean,default:!1},loading:{type:Boolean,default:!1},autoLimit:{type:Boolean,default:!0},tagWidth:{type:Number,default:150,validator:function(t){return t>0}}},data:function(){return{elWidth:0}},computed:{maxOptions:function(){if(this.autoLimit&&this.elWidth>0&&0!==this.tagWidth){var t=Math.floor(this.elWidth/this.tagWidth);return t>0?t:1}return this.limit?this.limit:9999},limitString:function(){return"+".concat(this.value.length-this.maxOptions)}},watch:{value:function(){this.updateWidth()}},mounted:function(){this.updateWidth(),window.addEventListener("resize",this.updateWidth)},beforeDestroy:function(){window.removeEventListener("resize",this.updateWidth)},methods:{formatLimitTitle:function(t){var e=this;if(Array.isArray(t)&&t.length>0){var n=t;return"object"===q(t[0])&&(n=t.map(function(t){return t[e.label]})),n.slice(this.maxOptions).join(", ")}return""},updateWidth:function(){this.elWidth=this.$el.querySelector(".multiselect__tags-wrap").offsetWidth-10}}},I,[],!1,null,null,null);X.options.__file="src/components/Multiselect/Multiselect.vue";var K=X.exports;i(55);
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
k(K);var tt=K,et=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("action",t._g(t._b({staticClass:"action-item",class:[t.isSingleAction?t.firstAction.icon+" action-item--single":"action-item--multiple"],attrs:{href:t.isSingleAction&&t.firstAction.href?t.firstAction.href:"#"}},"action",t.mainActionElement(),!1),t.isSingleAction&&t.firstAction.action?{click:t.firstAction.action}:{}),[t.isSingleAction?t._e():[n("div",{directives:[{name:"click-outside",rawName:"v-click-outside",value:t.closeMenu,expression:"closeMenu"}],staticClass:"action-item__menutoggle icon-more",attrs:{tabindex:"0"},on:{click:function(e){return e.preventDefault(),t.toggleMenu(e)}}}),t._v(" "),n("div",{staticClass:"action-item__menu popovermenu",class:{open:t.opened}},[n("popover-menu",{attrs:{menu:t.actions}})],1)]],2)};et._withStripped=!0;var nt={name:"Action",components:{PopoverMenu:h},directives:{ClickOutside:g.a},props:{actions:{type:Array,required:!0,default:function(){return[{href:"https://nextcloud.com",icon:"icon-links",text:"Nextcloud"},{action:function(){alert("Deleted !")},icon:"icon-delete",text:"Delete"}]}}},data:function(){return{opened:!1}},computed:{isSingleAction:function(){return 1===this.actions.length},firstAction:function(){return this.actions[0]}},mounted:function(){this.popupItem=this.$el},methods:{toggleMenu:function(){this.opened=!this.opened},closeMenu:function(){this.opened=!1},mainActionElement:function(){return{is:this.isSingleAction?"a":"div"}}}},it=(i(57),a(nt,et,[],!1,null,"886e6e62",null));it.options.__file="src/components/Action/Action.vue";var rt=it.exports;
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */function ot(t,e,n){return e in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */function at(t){Object.values(r).forEach(function(e){t.component(e.name,e)})}
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */i.d(n,"AppContent",function(){return l}),i.d(n,"AppNavigationItem",function(){return b}),i.d(n,"AppNavigationNew",function(){return x}),i.d(n,"AppNavigationSettings",function(){return E}),i.d(n,"PopoverMenu",function(){return m}),i.d(n,"DatetimePicker",function(){return N}),i.d(n,"Multiselect",function(){return tt}),i.d(n,"Avatar",function(){return W}),i.d(n,"Action",function(){return rt}),"undefined"!=typeof window&&window.Vue&&at(window.Vue);n.default=function(t){for(var e=1;e<arguments.length;e++){var n=null!=arguments[e]?arguments[e]:{},i=Object.keys(n);"function"==typeof Object.getOwnPropertySymbols&&(i=i.concat(Object.getOwnPropertySymbols(n).filter(function(t){return Object.getOwnPropertyDescriptor(n,t).enumerable}))),i.forEach(function(e){ot(t,e,n[e])})}return t}({install:at},r)}])});
//# sourceMappingURL=ncvuecomponents.js.map

/***/ }),

/***/ "./settings/node_modules/vue-localstorage/dist/vue-local-storage.js":
/*!**************************************************************************!*\
  !*** ./settings/node_modules/vue-localstorage/dist/vue-local-storage.js ***!
  \**************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(process) {/**
 * vue-local-storage v0.6.0
 * (c) 2017 Alexander Avakov
 * @license MIT
 */
(function (global, factory) {
	 true ? module.exports = factory() :
	undefined;
}(this, (function () { 'use strict';

var VueLocalStorage = function VueLocalStorage () {
  this._properties = {};
  this._namespace = '';
  this._isSupported = true;
};

var prototypeAccessors = { namespace: {} };

/**
 * Namespace getter.
 *
 * @returns {string}
 */
prototypeAccessors.namespace.get = function () {
  return this._namespace
};

/**
 * Namespace setter.
 *
 * @param {string} value
 */
prototypeAccessors.namespace.set = function (value) {
  this._namespace = value ? (value + ".") : '';
};

/**
 * Concatenates localStorage key with namespace prefix.
 *
 * @param {string} lsKey
 * @returns {string}
 * @private
 */
VueLocalStorage.prototype._getLsKey = function _getLsKey (lsKey) {
  return ("" + (this._namespace) + lsKey)
};

/**
 * Set a value to localStorage giving respect to the namespace.
 *
 * @param {string} lsKey
 * @param {*} rawValue
 * @param {*} type
 * @private
 */
VueLocalStorage.prototype._lsSet = function _lsSet (lsKey, rawValue, type) {
  var key = this._getLsKey(lsKey);
  var value = type && [Array, Object].includes(type)
    ? JSON.stringify(rawValue)
    : rawValue;

  window.localStorage.setItem(key, value);
};

/**
 * Get value from localStorage giving respect to the namespace.
 *
 * @param {string} lsKey
 * @returns {any}
 * @private
 */
VueLocalStorage.prototype._lsGet = function _lsGet (lsKey) {
  var key = this._getLsKey(lsKey);

  return window.localStorage[key]
};

/**
 * Get value from localStorage
 *
 * @param {String} lsKey
 * @param {*} defaultValue
 * @param {*} defaultType
 * @returns {*}
 */
VueLocalStorage.prototype.get = function get (lsKey, defaultValue, defaultType) {
    var this$1 = this;
    if ( defaultValue === void 0 ) defaultValue = null;
    if ( defaultType === void 0 ) defaultType = String;

  if (!this._isSupported) {
    return null
  }

  if (this._lsGet(lsKey)) {
    var type = defaultType;

    for (var key in this$1._properties) {
      if (key === lsKey) {
        type = this$1._properties[key].type;
        break
      }
    }

    return this._process(type, this._lsGet(lsKey))
  }

  return defaultValue !== null ? defaultValue : null
};

/**
 * Set localStorage value
 *
 * @param {String} lsKey
 * @param {*} value
 * @returns {*}
 */
VueLocalStorage.prototype.set = function set (lsKey, value) {
    var this$1 = this;

  if (!this._isSupported) {
    return null
  }

  for (var key in this$1._properties) {
    var type = this$1._properties[key].type;

    if ((key === lsKey)) {
      this$1._lsSet(lsKey, value, type);

      return value
    }
  }

  this._lsSet(lsKey, value);

  return value
};

/**
 * Remove value from localStorage
 *
 * @param {String} lsKey
 */
VueLocalStorage.prototype.remove = function remove (lsKey) {
  if (!this._isSupported) {
    return null
  }

  return window.localStorage.removeItem(lsKey)
};

/**
 * Add new property to localStorage
 *
 * @param {String} key
 * @param {function} type
 * @param {*} defaultValue
 */
VueLocalStorage.prototype.addProperty = function addProperty (key, type, defaultValue) {
    if ( defaultValue === void 0 ) defaultValue = undefined;

  type = type || String;

  this._properties[key] = { type: type };

  if (!this._lsGet(key) && defaultValue !== null) {
    this._lsSet(key, defaultValue, type);
  }
};

/**
 * Process the value before return it from localStorage
 *
 * @param {String} type
 * @param {*} value
 * @returns {*}
 * @private
 */
VueLocalStorage.prototype._process = function _process (type, value) {
  switch (type) {
    case Boolean:
      return value === 'true'
    case Number:
      return parseFloat(value)
    case Array:
      try {
        var array = JSON.parse(value);

        return Array.isArray(array) ? array : []
      } catch (e) {
        return []
      }
    case Object:
      try {
        return JSON.parse(value)
      } catch (e) {
        return {}
      }
    default:
      return value
  }
};

Object.defineProperties( VueLocalStorage.prototype, prototypeAccessors );

var vueLocalStorage = new VueLocalStorage();

var index = {
  /**
   * Install vue-local-storage plugin
   *
   * @param {Vue} Vue
   * @param {Object} options
   */
  install: function (Vue, options) {
    if ( options === void 0 ) options = {};

    if (typeof process !== 'undefined' &&
      (
        process.server ||
        process.SERVER_BUILD ||
        (process.env && process.env.VUE_ENV === 'server')
      )
    ) {
      return
    }

    var isSupported = true;

    try {
      var test = '__vue-localstorage-test__';

      window.localStorage.setItem(test, test);
      window.localStorage.removeItem(test);
    } catch (e) {
      isSupported = false;
      vueLocalStorage._isSupported = false;

      console.error('Local storage is not supported');
    }

    var name = options.name || 'localStorage';
    var bind = options.bind;

    if (options.namespace) {
      vueLocalStorage.namespace = options.namespace;
    }

    Vue.mixin({
      beforeCreate: function beforeCreate () {
        var this$1 = this;

        if (!isSupported) {
          return
        }

        if (this.$options[name]) {
          Object.keys(this.$options[name]).forEach(function (key) {
            var config = this$1.$options[name][key];
            var ref = [config.type, config.default];
            var type = ref[0];
            var defaultValue = ref[1];

            vueLocalStorage.addProperty(key, type, defaultValue);

            var existingProp = Object.getOwnPropertyDescriptor(vueLocalStorage, key);

            if (!existingProp) {
              var prop = {
                get: function () { return Vue.localStorage.get(key, defaultValue); },
                set: function (val) { return Vue.localStorage.set(key, val); },
                configurable: true
              };

              Object.defineProperty(vueLocalStorage, key, prop);
              Vue.util.defineReactive(vueLocalStorage, key, defaultValue);
            } else if (!Vue.config.silent) {
              console.log((key + ": is already defined and will be reused"));
            }

            if ((bind || config.bind) && config.bind !== false) {
              this$1.$options.computed = this$1.$options.computed || {};

              if (!this$1.$options.computed[key]) {
                this$1.$options.computed[key] = {
                  get: function () { return Vue.localStorage[key]; },
                  set: function (val) { Vue.localStorage[key] = val; }
                };
              }
            }
          });
        }
      }
    });

    Vue[name] = vueLocalStorage;
    Vue.prototype[("$" + name)] = vueLocalStorage;
  }
};

return index;

})));

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../../../node_modules/process/browser.js */ "./node_modules/process/browser.js")))

/***/ }),

/***/ "./settings/node_modules/vue-multiselect/dist/vue-multiselect.min.js":
/*!***************************************************************************!*\
  !*** ./settings/node_modules/vue-multiselect/dist/vue-multiselect.min.js ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(t,e){ true?module.exports=e():undefined}(this,function(){return function(t){function e(i){if(n[i])return n[i].exports;var r=n[i]={i:i,l:!1,exports:{}};return t[i].call(r.exports,r,r.exports,e),r.l=!0,r.exports}var n={};return e.m=t,e.c=n,e.i=function(t){return t},e.d=function(t,n,i){e.o(t,n)||Object.defineProperty(t,n,{configurable:!1,enumerable:!0,get:i})},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="/",e(e.s=60)}([function(t,e){var n=t.exports="undefined"!=typeof window&&window.Math==Math?window:"undefined"!=typeof self&&self.Math==Math?self:Function("return this")();"number"==typeof __g&&(__g=n)},function(t,e,n){var i=n(49)("wks"),r=n(30),o=n(0).Symbol,s="function"==typeof o;(t.exports=function(t){return i[t]||(i[t]=s&&o[t]||(s?o:r)("Symbol."+t))}).store=i},function(t,e,n){var i=n(5);t.exports=function(t){if(!i(t))throw TypeError(t+" is not an object!");return t}},function(t,e,n){var i=n(0),r=n(10),o=n(8),s=n(6),u=n(11),a=function(t,e,n){var l,c,f,p,h=t&a.F,d=t&a.G,v=t&a.S,g=t&a.P,m=t&a.B,y=d?i:v?i[e]||(i[e]={}):(i[e]||{}).prototype,b=d?r:r[e]||(r[e]={}),_=b.prototype||(b.prototype={});d&&(n=e);for(l in n)c=!h&&y&&void 0!==y[l],f=(c?y:n)[l],p=m&&c?u(f,i):g&&"function"==typeof f?u(Function.call,f):f,y&&s(y,l,f,t&a.U),b[l]!=f&&o(b,l,p),g&&_[l]!=f&&(_[l]=f)};i.core=r,a.F=1,a.G=2,a.S=4,a.P=8,a.B=16,a.W=32,a.U=64,a.R=128,t.exports=a},function(t,e,n){t.exports=!n(7)(function(){return 7!=Object.defineProperty({},"a",{get:function(){return 7}}).a})},function(t,e){t.exports=function(t){return"object"==typeof t?null!==t:"function"==typeof t}},function(t,e,n){var i=n(0),r=n(8),o=n(12),s=n(30)("src"),u=Function.toString,a=(""+u).split("toString");n(10).inspectSource=function(t){return u.call(t)},(t.exports=function(t,e,n,u){var l="function"==typeof n;l&&(o(n,"name")||r(n,"name",e)),t[e]!==n&&(l&&(o(n,s)||r(n,s,t[e]?""+t[e]:a.join(String(e)))),t===i?t[e]=n:u?t[e]?t[e]=n:r(t,e,n):(delete t[e],r(t,e,n)))})(Function.prototype,"toString",function(){return"function"==typeof this&&this[s]||u.call(this)})},function(t,e){t.exports=function(t){try{return!!t()}catch(t){return!0}}},function(t,e,n){var i=n(13),r=n(25);t.exports=n(4)?function(t,e,n){return i.f(t,e,r(1,n))}:function(t,e,n){return t[e]=n,t}},function(t,e){var n={}.toString;t.exports=function(t){return n.call(t).slice(8,-1)}},function(t,e){var n=t.exports={version:"2.5.7"};"number"==typeof __e&&(__e=n)},function(t,e,n){var i=n(14);t.exports=function(t,e,n){if(i(t),void 0===e)return t;switch(n){case 1:return function(n){return t.call(e,n)};case 2:return function(n,i){return t.call(e,n,i)};case 3:return function(n,i,r){return t.call(e,n,i,r)}}return function(){return t.apply(e,arguments)}}},function(t,e){var n={}.hasOwnProperty;t.exports=function(t,e){return n.call(t,e)}},function(t,e,n){var i=n(2),r=n(41),o=n(29),s=Object.defineProperty;e.f=n(4)?Object.defineProperty:function(t,e,n){if(i(t),e=o(e,!0),i(n),r)try{return s(t,e,n)}catch(t){}if("get"in n||"set"in n)throw TypeError("Accessors not supported!");return"value"in n&&(t[e]=n.value),t}},function(t,e){t.exports=function(t){if("function"!=typeof t)throw TypeError(t+" is not a function!");return t}},function(t,e){t.exports={}},function(t,e){t.exports=function(t){if(void 0==t)throw TypeError("Can't call method on  "+t);return t}},function(t,e,n){"use strict";var i=n(7);t.exports=function(t,e){return!!t&&i(function(){e?t.call(null,function(){},1):t.call(null)})}},function(t,e,n){var i=n(23),r=n(16);t.exports=function(t){return i(r(t))}},function(t,e,n){var i=n(53),r=Math.min;t.exports=function(t){return t>0?r(i(t),9007199254740991):0}},function(t,e,n){var i=n(11),r=n(23),o=n(28),s=n(19),u=n(64);t.exports=function(t,e){var n=1==t,a=2==t,l=3==t,c=4==t,f=6==t,p=5==t||f,h=e||u;return function(e,u,d){for(var v,g,m=o(e),y=r(m),b=i(u,d,3),_=s(y.length),x=0,w=n?h(e,_):a?h(e,0):void 0;_>x;x++)if((p||x in y)&&(v=y[x],g=b(v,x,m),t))if(n)w[x]=g;else if(g)switch(t){case 3:return!0;case 5:return v;case 6:return x;case 2:w.push(v)}else if(c)return!1;return f?-1:l||c?c:w}}},function(t,e,n){var i=n(5),r=n(0).document,o=i(r)&&i(r.createElement);t.exports=function(t){return o?r.createElement(t):{}}},function(t,e){t.exports="constructor,hasOwnProperty,isPrototypeOf,propertyIsEnumerable,toLocaleString,toString,valueOf".split(",")},function(t,e,n){var i=n(9);t.exports=Object("z").propertyIsEnumerable(0)?Object:function(t){return"String"==i(t)?t.split(""):Object(t)}},function(t,e){t.exports=!1},function(t,e){t.exports=function(t,e){return{enumerable:!(1&t),configurable:!(2&t),writable:!(4&t),value:e}}},function(t,e,n){var i=n(13).f,r=n(12),o=n(1)("toStringTag");t.exports=function(t,e,n){t&&!r(t=n?t:t.prototype,o)&&i(t,o,{configurable:!0,value:e})}},function(t,e,n){var i=n(49)("keys"),r=n(30);t.exports=function(t){return i[t]||(i[t]=r(t))}},function(t,e,n){var i=n(16);t.exports=function(t){return Object(i(t))}},function(t,e,n){var i=n(5);t.exports=function(t,e){if(!i(t))return t;var n,r;if(e&&"function"==typeof(n=t.toString)&&!i(r=n.call(t)))return r;if("function"==typeof(n=t.valueOf)&&!i(r=n.call(t)))return r;if(!e&&"function"==typeof(n=t.toString)&&!i(r=n.call(t)))return r;throw TypeError("Can't convert object to primitive value")}},function(t,e){var n=0,i=Math.random();t.exports=function(t){return"Symbol(".concat(void 0===t?"":t,")_",(++n+i).toString(36))}},function(t,e,n){"use strict";var i=n(0),r=n(12),o=n(9),s=n(67),u=n(29),a=n(7),l=n(77).f,c=n(45).f,f=n(13).f,p=n(51).trim,h=i.Number,d=h,v=h.prototype,g="Number"==o(n(44)(v)),m="trim"in String.prototype,y=function(t){var e=u(t,!1);if("string"==typeof e&&e.length>2){e=m?e.trim():p(e,3);var n,i,r,o=e.charCodeAt(0);if(43===o||45===o){if(88===(n=e.charCodeAt(2))||120===n)return NaN}else if(48===o){switch(e.charCodeAt(1)){case 66:case 98:i=2,r=49;break;case 79:case 111:i=8,r=55;break;default:return+e}for(var s,a=e.slice(2),l=0,c=a.length;l<c;l++)if((s=a.charCodeAt(l))<48||s>r)return NaN;return parseInt(a,i)}}return+e};if(!h(" 0o1")||!h("0b1")||h("+0x1")){h=function(t){var e=arguments.length<1?0:t,n=this;return n instanceof h&&(g?a(function(){v.valueOf.call(n)}):"Number"!=o(n))?s(new d(y(e)),n,h):y(e)};for(var b,_=n(4)?l(d):"MAX_VALUE,MIN_VALUE,NaN,NEGATIVE_INFINITY,POSITIVE_INFINITY,EPSILON,isFinite,isInteger,isNaN,isSafeInteger,MAX_SAFE_INTEGER,MIN_SAFE_INTEGER,parseFloat,parseInt,isInteger".split(","),x=0;_.length>x;x++)r(d,b=_[x])&&!r(h,b)&&f(h,b,c(d,b));h.prototype=v,v.constructor=h,n(6)(i,"Number",h)}},function(t,e,n){"use strict";function i(t){return 0!==t&&(!(!Array.isArray(t)||0!==t.length)||!t)}function r(t){return function(){return!t.apply(void 0,arguments)}}function o(t,e){return void 0===t&&(t="undefined"),null===t&&(t="null"),!1===t&&(t="false"),-1!==t.toString().toLowerCase().indexOf(e.trim())}function s(t,e,n,i){return t.filter(function(t){return o(i(t,n),e)})}function u(t){return t.filter(function(t){return!t.$isLabel})}function a(t,e){return function(n){return n.reduce(function(n,i){return i[t]&&i[t].length?(n.push({$groupLabel:i[e],$isLabel:!0}),n.concat(i[t])):n},[])}}function l(t,e,i,r,o){return function(u){return u.map(function(u){var a;if(!u[i])return console.warn("Options passed to vue-multiselect do not contain groups, despite the config."),[];var l=s(u[i],t,e,o);return l.length?(a={},n.i(d.a)(a,r,u[r]),n.i(d.a)(a,i,l),a):[]})}}var c=n(59),f=n(54),p=(n.n(f),n(95)),h=(n.n(p),n(31)),d=(n.n(h),n(58)),v=n(91),g=(n.n(v),n(98)),m=(n.n(g),n(92)),y=(n.n(m),n(88)),b=(n.n(y),n(97)),_=(n.n(b),n(89)),x=(n.n(_),n(96)),w=(n.n(x),n(93)),S=(n.n(w),n(90)),O=(n.n(S),function(){for(var t=arguments.length,e=new Array(t),n=0;n<t;n++)e[n]=arguments[n];return function(t){return e.reduce(function(t,e){return e(t)},t)}});e.a={data:function(){return{search:"",isOpen:!1,prefferedOpenDirection:"below",optimizedHeight:this.maxHeight}},props:{internalSearch:{type:Boolean,default:!0},options:{type:Array,required:!0},multiple:{type:Boolean,default:!1},value:{type:null,default:function(){return[]}},trackBy:{type:String},label:{type:String},searchable:{type:Boolean,default:!0},clearOnSelect:{type:Boolean,default:!0},hideSelected:{type:Boolean,default:!1},placeholder:{type:String,default:"Select option"},allowEmpty:{type:Boolean,default:!0},resetAfter:{type:Boolean,default:!1},closeOnSelect:{type:Boolean,default:!0},customLabel:{type:Function,default:function(t,e){return i(t)?"":e?t[e]:t}},taggable:{type:Boolean,default:!1},tagPlaceholder:{type:String,default:"Press enter to create a tag"},tagPosition:{type:String,default:"top"},max:{type:[Number,Boolean],default:!1},id:{default:null},optionsLimit:{type:Number,default:1e3},groupValues:{type:String},groupLabel:{type:String},groupSelect:{type:Boolean,default:!1},blockKeys:{type:Array,default:function(){return[]}},preserveSearch:{type:Boolean,default:!1},preselectFirst:{type:Boolean,default:!1}},mounted:function(){this.multiple||this.clearOnSelect||console.warn("[Vue-Multiselect warn]: ClearOnSelect and Multiple props can’t be both set to false."),!this.multiple&&this.max&&console.warn("[Vue-Multiselect warn]: Max prop should not be used when prop Multiple equals false."),this.preselectFirst&&!this.internalValue.length&&this.options.length&&this.select(this.filteredOptions[0])},computed:{internalValue:function(){return this.value||0===this.value?Array.isArray(this.value)?this.value:[this.value]:[]},filteredOptions:function(){var t=this.search||"",e=t.toLowerCase().trim(),n=this.options.concat();return n=this.internalSearch?this.groupValues?this.filterAndFlat(n,e,this.label):s(n,e,this.label,this.customLabel):this.groupValues?a(this.groupValues,this.groupLabel)(n):n,n=this.hideSelected?n.filter(r(this.isSelected)):n,this.taggable&&e.length&&!this.isExistingOption(e)&&("bottom"===this.tagPosition?n.push({isTag:!0,label:t}):n.unshift({isTag:!0,label:t})),n.slice(0,this.optionsLimit)},valueKeys:function(){var t=this;return this.trackBy?this.internalValue.map(function(e){return e[t.trackBy]}):this.internalValue},optionKeys:function(){var t=this;return(this.groupValues?this.flatAndStrip(this.options):this.options).map(function(e){return t.customLabel(e,t.label).toString().toLowerCase()})},currentOptionLabel:function(){return this.multiple?this.searchable?"":this.placeholder:this.internalValue.length?this.getOptionLabel(this.internalValue[0]):this.searchable?"":this.placeholder}},watch:{internalValue:function(){this.resetAfter&&this.internalValue.length&&(this.search="",this.$emit("input",this.multiple?[]:null))},search:function(){this.$emit("search-change",this.search,this.id)}},methods:{getValue:function(){return this.multiple?this.internalValue:0===this.internalValue.length?null:this.internalValue[0]},filterAndFlat:function(t,e,n){return O(l(e,n,this.groupValues,this.groupLabel,this.customLabel),a(this.groupValues,this.groupLabel))(t)},flatAndStrip:function(t){return O(a(this.groupValues,this.groupLabel),u)(t)},updateSearch:function(t){this.search=t},isExistingOption:function(t){return!!this.options&&this.optionKeys.indexOf(t)>-1},isSelected:function(t){var e=this.trackBy?t[this.trackBy]:t;return this.valueKeys.indexOf(e)>-1},getOptionLabel:function(t){if(i(t))return"";if(t.isTag)return t.label;if(t.$isLabel)return t.$groupLabel;var e=this.customLabel(t,this.label);return i(e)?"":e},select:function(t,e){if(t.$isLabel&&this.groupSelect)return void this.selectGroup(t);if(!(-1!==this.blockKeys.indexOf(e)||this.disabled||t.$isDisabled||t.$isLabel)&&(!this.max||!this.multiple||this.internalValue.length!==this.max)&&("Tab"!==e||this.pointerDirty)){if(t.isTag)this.$emit("tag",t.label,this.id),this.search="",this.closeOnSelect&&!this.multiple&&this.deactivate();else{if(this.isSelected(t))return void("Tab"!==e&&this.removeElement(t));this.$emit("select",t,this.id),this.multiple?this.$emit("input",this.internalValue.concat([t]),this.id):this.$emit("input",t,this.id),this.clearOnSelect&&(this.search="")}this.closeOnSelect&&this.deactivate()}},selectGroup:function(t){var e=this,n=this.options.find(function(n){return n[e.groupLabel]===t.$groupLabel});if(n)if(this.wholeGroupSelected(n)){this.$emit("remove",n[this.groupValues],this.id);var i=this.internalValue.filter(function(t){return-1===n[e.groupValues].indexOf(t)});this.$emit("input",i,this.id)}else{var o=n[this.groupValues].filter(r(this.isSelected));this.$emit("select",o,this.id),this.$emit("input",this.internalValue.concat(o),this.id)}},wholeGroupSelected:function(t){return t[this.groupValues].every(this.isSelected)},removeElement:function(t){var e=!(arguments.length>1&&void 0!==arguments[1])||arguments[1];if(!this.disabled){if(!this.allowEmpty&&this.internalValue.length<=1)return void this.deactivate();var i="object"===n.i(c.a)(t)?this.valueKeys.indexOf(t[this.trackBy]):this.valueKeys.indexOf(t);if(this.$emit("remove",t,this.id),this.multiple){var r=this.internalValue.slice(0,i).concat(this.internalValue.slice(i+1));this.$emit("input",r,this.id)}else this.$emit("input",null,this.id);this.closeOnSelect&&e&&this.deactivate()}},removeLastElement:function(){-1===this.blockKeys.indexOf("Delete")&&0===this.search.length&&Array.isArray(this.internalValue)&&this.removeElement(this.internalValue[this.internalValue.length-1],!1)},activate:function(){var t=this;this.isOpen||this.disabled||(this.adjustPosition(),this.groupValues&&0===this.pointer&&this.filteredOptions.length&&(this.pointer=1),this.isOpen=!0,this.searchable?(this.preserveSearch||(this.search=""),this.$nextTick(function(){return t.$refs.search.focus()})):this.$el.focus(),this.$emit("open",this.id))},deactivate:function(){this.isOpen&&(this.isOpen=!1,this.searchable?this.$refs.search.blur():this.$el.blur(),this.preserveSearch||(this.search=""),this.$emit("close",this.getValue(),this.id))},toggle:function(){this.isOpen?this.deactivate():this.activate()},adjustPosition:function(){if("undefined"!=typeof window){var t=this.$el.getBoundingClientRect().top,e=window.innerHeight-this.$el.getBoundingClientRect().bottom;e>this.maxHeight||e>t||"below"===this.openDirection||"bottom"===this.openDirection?(this.prefferedOpenDirection="below",this.optimizedHeight=Math.min(e-40,this.maxHeight)):(this.prefferedOpenDirection="above",this.optimizedHeight=Math.min(t-40,this.maxHeight))}}}}},function(t,e,n){"use strict";var i=n(54),r=(n.n(i),n(31));n.n(r);e.a={data:function(){return{pointer:0,pointerDirty:!1}},props:{showPointer:{type:Boolean,default:!0},optionHeight:{type:Number,default:40}},computed:{pointerPosition:function(){return this.pointer*this.optionHeight},visibleElements:function(){return this.optimizedHeight/this.optionHeight}},watch:{filteredOptions:function(){this.pointerAdjust()},isOpen:function(){this.pointerDirty=!1}},methods:{optionHighlight:function(t,e){return{"multiselect__option--highlight":t===this.pointer&&this.showPointer,"multiselect__option--selected":this.isSelected(e)}},groupHighlight:function(t,e){var n=this;if(!this.groupSelect)return["multiselect__option--group","multiselect__option--disabled"];var i=this.options.find(function(t){return t[n.groupLabel]===e.$groupLabel});return["multiselect__option--group",{"multiselect__option--highlight":t===this.pointer&&this.showPointer},{"multiselect__option--group-selected":this.wholeGroupSelected(i)}]},addPointerElement:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"Enter",e=t.key;this.filteredOptions.length>0&&this.select(this.filteredOptions[this.pointer],e),this.pointerReset()},pointerForward:function(){this.pointer<this.filteredOptions.length-1&&(this.pointer++,this.$refs.list.scrollTop<=this.pointerPosition-(this.visibleElements-1)*this.optionHeight&&(this.$refs.list.scrollTop=this.pointerPosition-(this.visibleElements-1)*this.optionHeight),this.filteredOptions[this.pointer]&&this.filteredOptions[this.pointer].$isLabel&&!this.groupSelect&&this.pointerForward()),this.pointerDirty=!0},pointerBackward:function(){this.pointer>0?(this.pointer--,this.$refs.list.scrollTop>=this.pointerPosition&&(this.$refs.list.scrollTop=this.pointerPosition),this.filteredOptions[this.pointer]&&this.filteredOptions[this.pointer].$isLabel&&!this.groupSelect&&this.pointerBackward()):this.filteredOptions[this.pointer]&&this.filteredOptions[0].$isLabel&&!this.groupSelect&&this.pointerForward(),this.pointerDirty=!0},pointerReset:function(){this.closeOnSelect&&(this.pointer=0,this.$refs.list&&(this.$refs.list.scrollTop=0))},pointerAdjust:function(){this.pointer>=this.filteredOptions.length-1&&(this.pointer=this.filteredOptions.length?this.filteredOptions.length-1:0),this.filteredOptions.length>0&&this.filteredOptions[this.pointer].$isLabel&&!this.groupSelect&&this.pointerForward()},pointerSet:function(t){this.pointer=t,this.pointerDirty=!0}}}},function(t,e,n){"use strict";var i=n(36),r=n(74),o=n(15),s=n(18);t.exports=n(72)(Array,"Array",function(t,e){this._t=s(t),this._i=0,this._k=e},function(){var t=this._t,e=this._k,n=this._i++;return!t||n>=t.length?(this._t=void 0,r(1)):"keys"==e?r(0,n):"values"==e?r(0,t[n]):r(0,[n,t[n]])},"values"),o.Arguments=o.Array,i("keys"),i("values"),i("entries")},function(t,e,n){"use strict";var i=n(31),r=(n.n(i),n(32)),o=n(33);e.a={name:"vue-multiselect",mixins:[r.a,o.a],props:{name:{type:String,default:""},selectLabel:{type:String,default:"Press enter to select"},selectGroupLabel:{type:String,default:"Press enter to select group"},selectedLabel:{type:String,default:"Selected"},deselectLabel:{type:String,default:"Press enter to remove"},deselectGroupLabel:{type:String,default:"Press enter to deselect group"},showLabels:{type:Boolean,default:!0},limit:{type:Number,default:99999},maxHeight:{type:Number,default:300},limitText:{type:Function,default:function(t){return"and ".concat(t," more")}},loading:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},openDirection:{type:String,default:""},showNoOptions:{type:Boolean,default:!0},showNoResults:{type:Boolean,default:!0},tabindex:{type:Number,default:0}},computed:{isSingleLabelVisible:function(){return this.singleValue&&(!this.isOpen||!this.searchable)&&!this.visibleValues.length},isPlaceholderVisible:function(){return!(this.internalValue.length||this.searchable&&this.isOpen)},visibleValues:function(){return this.multiple?this.internalValue.slice(0,this.limit):[]},singleValue:function(){return this.internalValue[0]},deselectLabelText:function(){return this.showLabels?this.deselectLabel:""},deselectGroupLabelText:function(){return this.showLabels?this.deselectGroupLabel:""},selectLabelText:function(){return this.showLabels?this.selectLabel:""},selectGroupLabelText:function(){return this.showLabels?this.selectGroupLabel:""},selectedLabelText:function(){return this.showLabels?this.selectedLabel:""},inputStyle:function(){if(this.searchable||this.multiple&&this.value&&this.value.length)return this.isOpen?{width:"auto"}:{width:"0",position:"absolute",padding:"0"}},contentStyle:function(){return this.options.length?{display:"inline-block"}:{display:"block"}},isAbove:function(){return"above"===this.openDirection||"top"===this.openDirection||"below"!==this.openDirection&&"bottom"!==this.openDirection&&"above"===this.prefferedOpenDirection},showSearchInput:function(){return this.searchable&&(!this.hasSingleSelectedSlot||!this.visibleSingleValue&&0!==this.visibleSingleValue||this.isOpen)}}}},function(t,e,n){var i=n(1)("unscopables"),r=Array.prototype;void 0==r[i]&&n(8)(r,i,{}),t.exports=function(t){r[i][t]=!0}},function(t,e,n){var i=n(18),r=n(19),o=n(85);t.exports=function(t){return function(e,n,s){var u,a=i(e),l=r(a.length),c=o(s,l);if(t&&n!=n){for(;l>c;)if((u=a[c++])!=u)return!0}else for(;l>c;c++)if((t||c in a)&&a[c]===n)return t||c||0;return!t&&-1}}},function(t,e,n){var i=n(9),r=n(1)("toStringTag"),o="Arguments"==i(function(){return arguments}()),s=function(t,e){try{return t[e]}catch(t){}};t.exports=function(t){var e,n,u;return void 0===t?"Undefined":null===t?"Null":"string"==typeof(n=s(e=Object(t),r))?n:o?i(e):"Object"==(u=i(e))&&"function"==typeof e.callee?"Arguments":u}},function(t,e,n){"use strict";var i=n(2);t.exports=function(){var t=i(this),e="";return t.global&&(e+="g"),t.ignoreCase&&(e+="i"),t.multiline&&(e+="m"),t.unicode&&(e+="u"),t.sticky&&(e+="y"),e}},function(t,e,n){var i=n(0).document;t.exports=i&&i.documentElement},function(t,e,n){t.exports=!n(4)&&!n(7)(function(){return 7!=Object.defineProperty(n(21)("div"),"a",{get:function(){return 7}}).a})},function(t,e,n){var i=n(9);t.exports=Array.isArray||function(t){return"Array"==i(t)}},function(t,e,n){"use strict";function i(t){var e,n;this.promise=new t(function(t,i){if(void 0!==e||void 0!==n)throw TypeError("Bad Promise constructor");e=t,n=i}),this.resolve=r(e),this.reject=r(n)}var r=n(14);t.exports.f=function(t){return new i(t)}},function(t,e,n){var i=n(2),r=n(76),o=n(22),s=n(27)("IE_PROTO"),u=function(){},a=function(){var t,e=n(21)("iframe"),i=o.length;for(e.style.display="none",n(40).appendChild(e),e.src="javascript:",t=e.contentWindow.document,t.open(),t.write("<script>document.F=Object<\/script>"),t.close(),a=t.F;i--;)delete a.prototype[o[i]];return a()};t.exports=Object.create||function(t,e){var n;return null!==t?(u.prototype=i(t),n=new u,u.prototype=null,n[s]=t):n=a(),void 0===e?n:r(n,e)}},function(t,e,n){var i=n(79),r=n(25),o=n(18),s=n(29),u=n(12),a=n(41),l=Object.getOwnPropertyDescriptor;e.f=n(4)?l:function(t,e){if(t=o(t),e=s(e,!0),a)try{return l(t,e)}catch(t){}if(u(t,e))return r(!i.f.call(t,e),t[e])}},function(t,e,n){var i=n(12),r=n(18),o=n(37)(!1),s=n(27)("IE_PROTO");t.exports=function(t,e){var n,u=r(t),a=0,l=[];for(n in u)n!=s&&i(u,n)&&l.push(n);for(;e.length>a;)i(u,n=e[a++])&&(~o(l,n)||l.push(n));return l}},function(t,e,n){var i=n(46),r=n(22);t.exports=Object.keys||function(t){return i(t,r)}},function(t,e,n){var i=n(2),r=n(5),o=n(43);t.exports=function(t,e){if(i(t),r(e)&&e.constructor===t)return e;var n=o.f(t);return(0,n.resolve)(e),n.promise}},function(t,e,n){var i=n(10),r=n(0),o=r["__core-js_shared__"]||(r["__core-js_shared__"]={});(t.exports=function(t,e){return o[t]||(o[t]=void 0!==e?e:{})})("versions",[]).push({version:i.version,mode:n(24)?"pure":"global",copyright:"© 2018 Denis Pushkarev (zloirock.ru)"})},function(t,e,n){var i=n(2),r=n(14),o=n(1)("species");t.exports=function(t,e){var n,s=i(t).constructor;return void 0===s||void 0==(n=i(s)[o])?e:r(n)}},function(t,e,n){var i=n(3),r=n(16),o=n(7),s=n(84),u="["+s+"]",a="​",l=RegExp("^"+u+u+"*"),c=RegExp(u+u+"*$"),f=function(t,e,n){var r={},u=o(function(){return!!s[t]()||a[t]()!=a}),l=r[t]=u?e(p):s[t];n&&(r[n]=l),i(i.P+i.F*u,"String",r)},p=f.trim=function(t,e){return t=String(r(t)),1&e&&(t=t.replace(l,"")),2&e&&(t=t.replace(c,"")),t};t.exports=f},function(t,e,n){var i,r,o,s=n(11),u=n(68),a=n(40),l=n(21),c=n(0),f=c.process,p=c.setImmediate,h=c.clearImmediate,d=c.MessageChannel,v=c.Dispatch,g=0,m={},y=function(){var t=+this;if(m.hasOwnProperty(t)){var e=m[t];delete m[t],e()}},b=function(t){y.call(t.data)};p&&h||(p=function(t){for(var e=[],n=1;arguments.length>n;)e.push(arguments[n++]);return m[++g]=function(){u("function"==typeof t?t:Function(t),e)},i(g),g},h=function(t){delete m[t]},"process"==n(9)(f)?i=function(t){f.nextTick(s(y,t,1))}:v&&v.now?i=function(t){v.now(s(y,t,1))}:d?(r=new d,o=r.port2,r.port1.onmessage=b,i=s(o.postMessage,o,1)):c.addEventListener&&"function"==typeof postMessage&&!c.importScripts?(i=function(t){c.postMessage(t+"","*")},c.addEventListener("message",b,!1)):i="onreadystatechange"in l("script")?function(t){a.appendChild(l("script")).onreadystatechange=function(){a.removeChild(this),y.call(t)}}:function(t){setTimeout(s(y,t,1),0)}),t.exports={set:p,clear:h}},function(t,e){var n=Math.ceil,i=Math.floor;t.exports=function(t){return isNaN(t=+t)?0:(t>0?i:n)(t)}},function(t,e,n){"use strict";var i=n(3),r=n(20)(5),o=!0;"find"in[]&&Array(1).find(function(){o=!1}),i(i.P+i.F*o,"Array",{find:function(t){return r(this,t,arguments.length>1?arguments[1]:void 0)}}),n(36)("find")},function(t,e,n){"use strict";var i,r,o,s,u=n(24),a=n(0),l=n(11),c=n(38),f=n(3),p=n(5),h=n(14),d=n(61),v=n(66),g=n(50),m=n(52).set,y=n(75)(),b=n(43),_=n(80),x=n(86),w=n(48),S=a.TypeError,O=a.process,L=O&&O.versions,P=L&&L.v8||"",k=a.Promise,T="process"==c(O),E=function(){},V=r=b.f,A=!!function(){try{var t=k.resolve(1),e=(t.constructor={})[n(1)("species")]=function(t){t(E,E)};return(T||"function"==typeof PromiseRejectionEvent)&&t.then(E)instanceof e&&0!==P.indexOf("6.6")&&-1===x.indexOf("Chrome/66")}catch(t){}}(),C=function(t){var e;return!(!p(t)||"function"!=typeof(e=t.then))&&e},j=function(t,e){if(!t._n){t._n=!0;var n=t._c;y(function(){for(var i=t._v,r=1==t._s,o=0;n.length>o;)!function(e){var n,o,s,u=r?e.ok:e.fail,a=e.resolve,l=e.reject,c=e.domain;try{u?(r||(2==t._h&&$(t),t._h=1),!0===u?n=i:(c&&c.enter(),n=u(i),c&&(c.exit(),s=!0)),n===e.promise?l(S("Promise-chain cycle")):(o=C(n))?o.call(n,a,l):a(n)):l(i)}catch(t){c&&!s&&c.exit(),l(t)}}(n[o++]);t._c=[],t._n=!1,e&&!t._h&&N(t)})}},N=function(t){m.call(a,function(){var e,n,i,r=t._v,o=D(t);if(o&&(e=_(function(){T?O.emit("unhandledRejection",r,t):(n=a.onunhandledrejection)?n({promise:t,reason:r}):(i=a.console)&&i.error&&i.error("Unhandled promise rejection",r)}),t._h=T||D(t)?2:1),t._a=void 0,o&&e.e)throw e.v})},D=function(t){return 1!==t._h&&0===(t._a||t._c).length},$=function(t){m.call(a,function(){var e;T?O.emit("rejectionHandled",t):(e=a.onrejectionhandled)&&e({promise:t,reason:t._v})})},M=function(t){var e=this;e._d||(e._d=!0,e=e._w||e,e._v=t,e._s=2,e._a||(e._a=e._c.slice()),j(e,!0))},F=function(t){var e,n=this;if(!n._d){n._d=!0,n=n._w||n;try{if(n===t)throw S("Promise can't be resolved itself");(e=C(t))?y(function(){var i={_w:n,_d:!1};try{e.call(t,l(F,i,1),l(M,i,1))}catch(t){M.call(i,t)}}):(n._v=t,n._s=1,j(n,!1))}catch(t){M.call({_w:n,_d:!1},t)}}};A||(k=function(t){d(this,k,"Promise","_h"),h(t),i.call(this);try{t(l(F,this,1),l(M,this,1))}catch(t){M.call(this,t)}},i=function(t){this._c=[],this._a=void 0,this._s=0,this._d=!1,this._v=void 0,this._h=0,this._n=!1},i.prototype=n(81)(k.prototype,{then:function(t,e){var n=V(g(this,k));return n.ok="function"!=typeof t||t,n.fail="function"==typeof e&&e,n.domain=T?O.domain:void 0,this._c.push(n),this._a&&this._a.push(n),this._s&&j(this,!1),n.promise},catch:function(t){return this.then(void 0,t)}}),o=function(){var t=new i;this.promise=t,this.resolve=l(F,t,1),this.reject=l(M,t,1)},b.f=V=function(t){return t===k||t===s?new o(t):r(t)}),f(f.G+f.W+f.F*!A,{Promise:k}),n(26)(k,"Promise"),n(83)("Promise"),s=n(10).Promise,f(f.S+f.F*!A,"Promise",{reject:function(t){var e=V(this);return(0,e.reject)(t),e.promise}}),f(f.S+f.F*(u||!A),"Promise",{resolve:function(t){return w(u&&this===s?k:this,t)}}),f(f.S+f.F*!(A&&n(73)(function(t){k.all(t).catch(E)})),"Promise",{all:function(t){var e=this,n=V(e),i=n.resolve,r=n.reject,o=_(function(){var n=[],o=0,s=1;v(t,!1,function(t){var u=o++,a=!1;n.push(void 0),s++,e.resolve(t).then(function(t){a||(a=!0,n[u]=t,--s||i(n))},r)}),--s||i(n)});return o.e&&r(o.v),n.promise},race:function(t){var e=this,n=V(e),i=n.reject,r=_(function(){v(t,!1,function(t){e.resolve(t).then(n.resolve,i)})});return r.e&&i(r.v),n.promise}})},function(t,e,n){"use strict";var i=n(3),r=n(10),o=n(0),s=n(50),u=n(48);i(i.P+i.R,"Promise",{finally:function(t){var e=s(this,r.Promise||o.Promise),n="function"==typeof t;return this.then(n?function(n){return u(e,t()).then(function(){return n})}:t,n?function(n){return u(e,t()).then(function(){throw n})}:t)}})},function(t,e,n){"use strict";function i(t){n(99)}var r=n(35),o=n(101),s=n(100),u=i,a=s(r.a,o.a,!1,u,null,null);e.a=a.exports},function(t,e,n){"use strict";function i(t,e,n){return e in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}e.a=i},function(t,e,n){"use strict";function i(t){return(i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function r(t){return(r="function"==typeof Symbol&&"symbol"===i(Symbol.iterator)?function(t){return i(t)}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":i(t)})(t)}e.a=r},function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i=n(34),r=(n.n(i),n(55)),o=(n.n(r),n(56)),s=(n.n(o),n(57)),u=n(32),a=n(33);n.d(e,"Multiselect",function(){return s.a}),n.d(e,"multiselectMixin",function(){return u.a}),n.d(e,"pointerMixin",function(){return a.a}),e.default=s.a},function(t,e){t.exports=function(t,e,n,i){if(!(t instanceof e)||void 0!==i&&i in t)throw TypeError(n+": incorrect invocation!");return t}},function(t,e,n){var i=n(14),r=n(28),o=n(23),s=n(19);t.exports=function(t,e,n,u,a){i(e);var l=r(t),c=o(l),f=s(l.length),p=a?f-1:0,h=a?-1:1;if(n<2)for(;;){if(p in c){u=c[p],p+=h;break}if(p+=h,a?p<0:f<=p)throw TypeError("Reduce of empty array with no initial value")}for(;a?p>=0:f>p;p+=h)p in c&&(u=e(u,c[p],p,l));return u}},function(t,e,n){var i=n(5),r=n(42),o=n(1)("species");t.exports=function(t){var e;return r(t)&&(e=t.constructor,"function"!=typeof e||e!==Array&&!r(e.prototype)||(e=void 0),i(e)&&null===(e=e[o])&&(e=void 0)),void 0===e?Array:e}},function(t,e,n){var i=n(63);t.exports=function(t,e){return new(i(t))(e)}},function(t,e,n){"use strict";var i=n(8),r=n(6),o=n(7),s=n(16),u=n(1);t.exports=function(t,e,n){var a=u(t),l=n(s,a,""[t]),c=l[0],f=l[1];o(function(){var e={};return e[a]=function(){return 7},7!=""[t](e)})&&(r(String.prototype,t,c),i(RegExp.prototype,a,2==e?function(t,e){return f.call(t,this,e)}:function(t){return f.call(t,this)}))}},function(t,e,n){var i=n(11),r=n(70),o=n(69),s=n(2),u=n(19),a=n(87),l={},c={},e=t.exports=function(t,e,n,f,p){var h,d,v,g,m=p?function(){return t}:a(t),y=i(n,f,e?2:1),b=0;if("function"!=typeof m)throw TypeError(t+" is not iterable!");if(o(m)){for(h=u(t.length);h>b;b++)if((g=e?y(s(d=t[b])[0],d[1]):y(t[b]))===l||g===c)return g}else for(v=m.call(t);!(d=v.next()).done;)if((g=r(v,y,d.value,e))===l||g===c)return g};e.BREAK=l,e.RETURN=c},function(t,e,n){var i=n(5),r=n(82).set;t.exports=function(t,e,n){var o,s=e.constructor;return s!==n&&"function"==typeof s&&(o=s.prototype)!==n.prototype&&i(o)&&r&&r(t,o),t}},function(t,e){t.exports=function(t,e,n){var i=void 0===n;switch(e.length){case 0:return i?t():t.call(n);case 1:return i?t(e[0]):t.call(n,e[0]);case 2:return i?t(e[0],e[1]):t.call(n,e[0],e[1]);case 3:return i?t(e[0],e[1],e[2]):t.call(n,e[0],e[1],e[2]);case 4:return i?t(e[0],e[1],e[2],e[3]):t.call(n,e[0],e[1],e[2],e[3])}return t.apply(n,e)}},function(t,e,n){var i=n(15),r=n(1)("iterator"),o=Array.prototype;t.exports=function(t){return void 0!==t&&(i.Array===t||o[r]===t)}},function(t,e,n){var i=n(2);t.exports=function(t,e,n,r){try{return r?e(i(n)[0],n[1]):e(n)}catch(e){var o=t.return;throw void 0!==o&&i(o.call(t)),e}}},function(t,e,n){"use strict";var i=n(44),r=n(25),o=n(26),s={};n(8)(s,n(1)("iterator"),function(){return this}),t.exports=function(t,e,n){t.prototype=i(s,{next:r(1,n)}),o(t,e+" Iterator")}},function(t,e,n){"use strict";var i=n(24),r=n(3),o=n(6),s=n(8),u=n(15),a=n(71),l=n(26),c=n(78),f=n(1)("iterator"),p=!([].keys&&"next"in[].keys()),h=function(){return this};t.exports=function(t,e,n,d,v,g,m){a(n,e,d);var y,b,_,x=function(t){if(!p&&t in L)return L[t];switch(t){case"keys":case"values":return function(){return new n(this,t)}}return function(){return new n(this,t)}},w=e+" Iterator",S="values"==v,O=!1,L=t.prototype,P=L[f]||L["@@iterator"]||v&&L[v],k=P||x(v),T=v?S?x("entries"):k:void 0,E="Array"==e?L.entries||P:P;if(E&&(_=c(E.call(new t)))!==Object.prototype&&_.next&&(l(_,w,!0),i||"function"==typeof _[f]||s(_,f,h)),S&&P&&"values"!==P.name&&(O=!0,k=function(){return P.call(this)}),i&&!m||!p&&!O&&L[f]||s(L,f,k),u[e]=k,u[w]=h,v)if(y={values:S?k:x("values"),keys:g?k:x("keys"),entries:T},m)for(b in y)b in L||o(L,b,y[b]);else r(r.P+r.F*(p||O),e,y);return y}},function(t,e,n){var i=n(1)("iterator"),r=!1;try{var o=[7][i]();o.return=function(){r=!0},Array.from(o,function(){throw 2})}catch(t){}t.exports=function(t,e){if(!e&&!r)return!1;var n=!1;try{var o=[7],s=o[i]();s.next=function(){return{done:n=!0}},o[i]=function(){return s},t(o)}catch(t){}return n}},function(t,e){t.exports=function(t,e){return{value:e,done:!!t}}},function(t,e,n){var i=n(0),r=n(52).set,o=i.MutationObserver||i.WebKitMutationObserver,s=i.process,u=i.Promise,a="process"==n(9)(s);t.exports=function(){var t,e,n,l=function(){var i,r;for(a&&(i=s.domain)&&i.exit();t;){r=t.fn,t=t.next;try{r()}catch(i){throw t?n():e=void 0,i}}e=void 0,i&&i.enter()};if(a)n=function(){s.nextTick(l)};else if(!o||i.navigator&&i.navigator.standalone)if(u&&u.resolve){var c=u.resolve(void 0);n=function(){c.then(l)}}else n=function(){r.call(i,l)};else{var f=!0,p=document.createTextNode("");new o(l).observe(p,{characterData:!0}),n=function(){p.data=f=!f}}return function(i){var r={fn:i,next:void 0};e&&(e.next=r),t||(t=r,n()),e=r}}},function(t,e,n){var i=n(13),r=n(2),o=n(47);t.exports=n(4)?Object.defineProperties:function(t,e){r(t);for(var n,s=o(e),u=s.length,a=0;u>a;)i.f(t,n=s[a++],e[n]);return t}},function(t,e,n){var i=n(46),r=n(22).concat("length","prototype");e.f=Object.getOwnPropertyNames||function(t){return i(t,r)}},function(t,e,n){var i=n(12),r=n(28),o=n(27)("IE_PROTO"),s=Object.prototype;t.exports=Object.getPrototypeOf||function(t){return t=r(t),i(t,o)?t[o]:"function"==typeof t.constructor&&t instanceof t.constructor?t.constructor.prototype:t instanceof Object?s:null}},function(t,e){e.f={}.propertyIsEnumerable},function(t,e){t.exports=function(t){try{return{e:!1,v:t()}}catch(t){return{e:!0,v:t}}}},function(t,e,n){var i=n(6);t.exports=function(t,e,n){for(var r in e)i(t,r,e[r],n);return t}},function(t,e,n){var i=n(5),r=n(2),o=function(t,e){if(r(t),!i(e)&&null!==e)throw TypeError(e+": can't set as prototype!")};t.exports={set:Object.setPrototypeOf||("__proto__"in{}?function(t,e,i){try{i=n(11)(Function.call,n(45).f(Object.prototype,"__proto__").set,2),i(t,[]),e=!(t instanceof Array)}catch(t){e=!0}return function(t,n){return o(t,n),e?t.__proto__=n:i(t,n),t}}({},!1):void 0),check:o}},function(t,e,n){"use strict";var i=n(0),r=n(13),o=n(4),s=n(1)("species");t.exports=function(t){var e=i[t];o&&e&&!e[s]&&r.f(e,s,{configurable:!0,get:function(){return this}})}},function(t,e){t.exports="\t\n\v\f\r   ᠎             　\u2028\u2029\ufeff"},function(t,e,n){var i=n(53),r=Math.max,o=Math.min;t.exports=function(t,e){return t=i(t),t<0?r(t+e,0):o(t,e)}},function(t,e,n){var i=n(0),r=i.navigator;t.exports=r&&r.userAgent||""},function(t,e,n){var i=n(38),r=n(1)("iterator"),o=n(15);t.exports=n(10).getIteratorMethod=function(t){if(void 0!=t)return t[r]||t["@@iterator"]||o[i(t)]}},function(t,e,n){"use strict";var i=n(3),r=n(20)(2);i(i.P+i.F*!n(17)([].filter,!0),"Array",{filter:function(t){return r(this,t,arguments[1])}})},function(t,e,n){"use strict";var i=n(3),r=n(37)(!1),o=[].indexOf,s=!!o&&1/[1].indexOf(1,-0)<0;i(i.P+i.F*(s||!n(17)(o)),"Array",{indexOf:function(t){return s?o.apply(this,arguments)||0:r(this,t,arguments[1])}})},function(t,e,n){var i=n(3);i(i.S,"Array",{isArray:n(42)})},function(t,e,n){"use strict";var i=n(3),r=n(20)(1);i(i.P+i.F*!n(17)([].map,!0),"Array",{map:function(t){return r(this,t,arguments[1])}})},function(t,e,n){"use strict";var i=n(3),r=n(62);i(i.P+i.F*!n(17)([].reduce,!0),"Array",{reduce:function(t){return r(this,t,arguments.length,arguments[1],!1)}})},function(t,e,n){var i=Date.prototype,r=i.toString,o=i.getTime;new Date(NaN)+""!="Invalid Date"&&n(6)(i,"toString",function(){var t=o.call(this);return t===t?r.call(this):"Invalid Date"})},function(t,e,n){n(4)&&"g"!=/./g.flags&&n(13).f(RegExp.prototype,"flags",{configurable:!0,get:n(39)})},function(t,e,n){n(65)("search",1,function(t,e,n){return[function(n){"use strict";var i=t(this),r=void 0==n?void 0:n[e];return void 0!==r?r.call(n,i):new RegExp(n)[e](String(i))},n]})},function(t,e,n){"use strict";n(94);var i=n(2),r=n(39),o=n(4),s=/./.toString,u=function(t){n(6)(RegExp.prototype,"toString",t,!0)};n(7)(function(){return"/a/b"!=s.call({source:"a",flags:"b"})})?u(function(){var t=i(this);return"/".concat(t.source,"/","flags"in t?t.flags:!o&&t instanceof RegExp?r.call(t):void 0)}):"toString"!=s.name&&u(function(){return s.call(this)})},function(t,e,n){"use strict";n(51)("trim",function(t){return function(){return t(this,3)}})},function(t,e,n){for(var i=n(34),r=n(47),o=n(6),s=n(0),u=n(8),a=n(15),l=n(1),c=l("iterator"),f=l("toStringTag"),p=a.Array,h={CSSRuleList:!0,CSSStyleDeclaration:!1,CSSValueList:!1,ClientRectList:!1,DOMRectList:!1,DOMStringList:!1,DOMTokenList:!0,DataTransferItemList:!1,FileList:!1,HTMLAllCollection:!1,HTMLCollection:!1,HTMLFormElement:!1,HTMLSelectElement:!1,MediaList:!0,MimeTypeArray:!1,NamedNodeMap:!1,NodeList:!0,PaintRequestList:!1,Plugin:!1,PluginArray:!1,SVGLengthList:!1,SVGNumberList:!1,SVGPathSegList:!1,SVGPointList:!1,SVGStringList:!1,SVGTransformList:!1,SourceBufferList:!1,StyleSheetList:!0,TextTrackCueList:!1,TextTrackList:!1,TouchList:!1},d=r(h),v=0;v<d.length;v++){var g,m=d[v],y=h[m],b=s[m],_=b&&b.prototype;if(_&&(_[c]||u(_,c,p),_[f]||u(_,f,m),a[m]=p,y))for(g in i)_[g]||o(_,g,i[g],!0)}},function(t,e){},function(t,e){t.exports=function(t,e,n,i,r,o){var s,u=t=t||{},a=typeof t.default;"object"!==a&&"function"!==a||(s=t,u=t.default);var l="function"==typeof u?u.options:u;e&&(l.render=e.render,l.staticRenderFns=e.staticRenderFns,l._compiled=!0),n&&(l.functional=!0),r&&(l._scopeId=r);var c;if(o?(c=function(t){t=t||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,t||"undefined"==typeof __VUE_SSR_CONTEXT__||(t=__VUE_SSR_CONTEXT__),i&&i.call(this,t),t&&t._registeredComponents&&t._registeredComponents.add(o)},l._ssrRegister=c):i&&(c=i),c){var f=l.functional,p=f?l.render:l.beforeCreate;f?(l._injectStyles=c,l.render=function(t,e){return c.call(e),p(t,e)}):l.beforeCreate=p?[].concat(p,c):[c]}return{esModule:s,exports:u,options:l}}},function(t,e,n){"use strict";var i=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"multiselect",class:{"multiselect--active":t.isOpen,"multiselect--disabled":t.disabled,"multiselect--above":t.isAbove},attrs:{tabindex:t.searchable?-1:t.tabindex},on:{focus:function(e){t.activate()},blur:function(e){!t.searchable&&t.deactivate()},keydown:[function(e){return"button"in e||!t._k(e.keyCode,"down",40,e.key,["Down","ArrowDown"])?e.target!==e.currentTarget?null:(e.preventDefault(),void t.pointerForward()):null},function(e){return"button"in e||!t._k(e.keyCode,"up",38,e.key,["Up","ArrowUp"])?e.target!==e.currentTarget?null:(e.preventDefault(),void t.pointerBackward()):null},function(e){return"button"in e||!t._k(e.keyCode,"enter",13,e.key,"Enter")||!t._k(e.keyCode,"tab",9,e.key,"Tab")?(e.stopPropagation(),e.target!==e.currentTarget?null:void t.addPointerElement(e)):null}],keyup:function(e){if(!("button"in e)&&t._k(e.keyCode,"esc",27,e.key,"Escape"))return null;t.deactivate()}}},[t._t("caret",[n("div",{staticClass:"multiselect__select",on:{mousedown:function(e){e.preventDefault(),e.stopPropagation(),t.toggle()}}})],{toggle:t.toggle}),t._v(" "),t._t("clear",null,{search:t.search}),t._v(" "),n("div",{ref:"tags",staticClass:"multiselect__tags"},[t._t("selection",[n("div",{directives:[{name:"show",rawName:"v-show",value:t.visibleValues.length>0,expression:"visibleValues.length > 0"}],staticClass:"multiselect__tags-wrap"},[t._l(t.visibleValues,function(e,i){return[t._t("tag",[n("span",{key:i,staticClass:"multiselect__tag"},[n("span",{domProps:{textContent:t._s(t.getOptionLabel(e))}}),t._v(" "),n("i",{staticClass:"multiselect__tag-icon",attrs:{"aria-hidden":"true",tabindex:"1"},on:{keydown:function(n){if(!("button"in n)&&t._k(n.keyCode,"enter",13,n.key,"Enter"))return null;n.preventDefault(),t.removeElement(e)},mousedown:function(n){n.preventDefault(),t.removeElement(e)}}})])],{option:e,search:t.search,remove:t.removeElement})]})],2),t._v(" "),t.internalValue&&t.internalValue.length>t.limit?[t._t("limit",[n("strong",{staticClass:"multiselect__strong",domProps:{textContent:t._s(t.limitText(t.internalValue.length-t.limit))}})])]:t._e()],{search:t.search,remove:t.removeElement,values:t.visibleValues,isOpen:t.isOpen}),t._v(" "),n("transition",{attrs:{name:"multiselect__loading"}},[t._t("loading",[n("div",{directives:[{name:"show",rawName:"v-show",value:t.loading,expression:"loading"}],staticClass:"multiselect__spinner"})])],2),t._v(" "),t.searchable?n("input",{ref:"search",staticClass:"multiselect__input",style:t.inputStyle,attrs:{name:t.name,id:t.id,type:"text",autocomplete:"off",placeholder:t.placeholder,disabled:t.disabled,tabindex:t.tabindex},domProps:{value:t.search},on:{input:function(e){t.updateSearch(e.target.value)},focus:function(e){e.preventDefault(),t.activate()},blur:function(e){e.preventDefault(),t.deactivate()},keyup:function(e){if(!("button"in e)&&t._k(e.keyCode,"esc",27,e.key,"Escape"))return null;t.deactivate()},keydown:[function(e){if(!("button"in e)&&t._k(e.keyCode,"down",40,e.key,["Down","ArrowDown"]))return null;e.preventDefault(),t.pointerForward()},function(e){if(!("button"in e)&&t._k(e.keyCode,"up",38,e.key,["Up","ArrowUp"]))return null;e.preventDefault(),t.pointerBackward()},function(e){return"button"in e||!t._k(e.keyCode,"enter",13,e.key,"Enter")?(e.preventDefault(),e.stopPropagation(),e.target!==e.currentTarget?null:void t.addPointerElement(e)):null},function(e){if(!("button"in e)&&t._k(e.keyCode,"delete",[8,46],e.key,["Backspace","Delete"]))return null;e.stopPropagation(),t.removeLastElement()}]}}):t._e(),t._v(" "),t.isSingleLabelVisible?n("span",{staticClass:"multiselect__single",on:{mousedown:function(e){return e.preventDefault(),t.toggle(e)}}},[t._t("singleLabel",[[t._v(t._s(t.currentOptionLabel))]],{option:t.singleValue})],2):t._e(),t._v(" "),t.isPlaceholderVisible?n("span",{staticClass:"multiselect__placeholder",on:{mousedown:function(e){return e.preventDefault(),t.toggle(e)}}},[t._t("placeholder",[t._v("\n            "+t._s(t.placeholder)+"\n        ")])],2):t._e()],2),t._v(" "),n("transition",{attrs:{name:"multiselect"}},[n("div",{directives:[{name:"show",rawName:"v-show",value:t.isOpen,expression:"isOpen"}],ref:"list",staticClass:"multiselect__content-wrapper",style:{maxHeight:t.optimizedHeight+"px"},attrs:{tabindex:"-1"},on:{focus:t.activate,mousedown:function(t){t.preventDefault()}}},[n("ul",{staticClass:"multiselect__content",style:t.contentStyle},[t._t("beforeList"),t._v(" "),t.multiple&&t.max===t.internalValue.length?n("li",[n("span",{staticClass:"multiselect__option"},[t._t("maxElements",[t._v("Maximum of "+t._s(t.max)+" options selected. First remove a selected option to select another.")])],2)]):t._e(),t._v(" "),!t.max||t.internalValue.length<t.max?t._l(t.filteredOptions,function(e,i){return n("li",{key:i,staticClass:"multiselect__element"},[e&&(e.$isLabel||e.$isDisabled)?t._e():n("span",{staticClass:"multiselect__option",class:t.optionHighlight(i,e),attrs:{"data-select":e&&e.isTag?t.tagPlaceholder:t.selectLabelText,"data-selected":t.selectedLabelText,"data-deselect":t.deselectLabelText},on:{click:function(n){n.stopPropagation(),t.select(e)},mouseenter:function(e){if(e.target!==e.currentTarget)return null;t.pointerSet(i)}}},[t._t("option",[n("span",[t._v(t._s(t.getOptionLabel(e)))])],{option:e,search:t.search})],2),t._v(" "),e&&(e.$isLabel||e.$isDisabled)?n("span",{staticClass:"multiselect__option",class:t.groupHighlight(i,e),attrs:{"data-select":t.groupSelect&&t.selectGroupLabelText,"data-deselect":t.groupSelect&&t.deselectGroupLabelText},on:{mouseenter:function(e){if(e.target!==e.currentTarget)return null;t.groupSelect&&t.pointerSet(i)},mousedown:function(n){n.preventDefault(),t.selectGroup(e)}}},[t._t("option",[n("span",[t._v(t._s(t.getOptionLabel(e)))])],{option:e,search:t.search})],2):t._e()])}):t._e(),t._v(" "),n("li",{directives:[{name:"show",rawName:"v-show",value:t.showNoResults&&0===t.filteredOptions.length&&t.search&&!t.loading,expression:"showNoResults && (filteredOptions.length === 0 && search && !loading)"}]},[n("span",{staticClass:"multiselect__option"},[t._t("noResult",[t._v("No elements found. Consider changing the search query.")])],2)]),t._v(" "),n("li",{directives:[{name:"show",rawName:"v-show",value:t.showNoOptions&&0===t.options.length&&!t.search&&!t.loading,expression:"showNoOptions && (options.length === 0 && !search && !loading)"}]},[n("span",{staticClass:"multiselect__option"},[t._t("noOptions",[t._v("List is empty.")])],2)]),t._v(" "),t._t("afterList")],2)])])],2)},r=[],o={render:i,staticRenderFns:r};e.a=o}])});

/***/ })

}]);
//# sourceMappingURL=vue-2.js.map