const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { n as createCoords, r as rectToClientRect, o as floor, p as round, q as computePosition$1, s as max, t as min, v as offset$1, x as flip$1, y as shift$1, z as limitShift$1 } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps, a9 as resolveDirective, i as renderSlot, I as normalizeProps, J as guardReactiveProps, F as Fragment, C as renderList, j as createTextVNode, c as createBlock, K as resolveDynamicComponent, L as toHandlers, E as withDirectives, G as vShow, x as createVNode, w as withCtx, M as withModifiers, v as normalizeClass, ad as Transition, b as defineComponent, a3 as h, r as resolveComponent, p as createSlots, H as warn } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc, r as register, o as t17, b as t, c as createElementId } from "./Web-BOM4en5n.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { i as isLegacy } from "./ArrowRight-BC77f5L9.chunk.mjs";
const _sfc_main$4 = {
  name: "ChevronDownIcon",
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
const _hoisted_1$3 = ["aria-hidden", "aria-label"];
const _hoisted_2$2 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon chevron-down-icon",
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
    ], 8, _hoisted_2$2))
  ], 16, _hoisted_1$3);
}
const ChevronDown = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$3]]);
const _sfc_main$3 = {
  name: "CloseIcon",
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
const _hoisted_1$2 = ["aria-hidden", "aria-label"];
const _hoisted_2$1 = ["fill", "width", "height"];
const _hoisted_3 = { d: "M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon close-icon",
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
      createBaseVNode("path", _hoisted_3, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$1))
  ], 16, _hoisted_1$2);
}
const IconClose = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$2]]);
function hasWindow() {
  return typeof window !== "undefined";
}
function getNodeName(node) {
  if (isNode(node)) {
    return (node.nodeName || "").toLowerCase();
  }
  return "#document";
}
function getWindow(node) {
  var _node$ownerDocument;
  return (node == null || (_node$ownerDocument = node.ownerDocument) == null ? void 0 : _node$ownerDocument.defaultView) || window;
}
function getDocumentElement(node) {
  var _ref;
  return (_ref = (isNode(node) ? node.ownerDocument : node.document) || window.document) == null ? void 0 : _ref.documentElement;
}
function isNode(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof Node || value instanceof getWindow(value).Node;
}
function isElement(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof Element || value instanceof getWindow(value).Element;
}
function isHTMLElement(value) {
  if (!hasWindow()) {
    return false;
  }
  return value instanceof HTMLElement || value instanceof getWindow(value).HTMLElement;
}
function isShadowRoot(value) {
  if (!hasWindow() || typeof ShadowRoot === "undefined") {
    return false;
  }
  return value instanceof ShadowRoot || value instanceof getWindow(value).ShadowRoot;
}
const invalidOverflowDisplayValues = /* @__PURE__ */ new Set(["inline", "contents"]);
function isOverflowElement(element) {
  const {
    overflow,
    overflowX,
    overflowY,
    display
  } = getComputedStyle$1(element);
  return /auto|scroll|overlay|hidden|clip/.test(overflow + overflowY + overflowX) && !invalidOverflowDisplayValues.has(display);
}
const tableElements = /* @__PURE__ */ new Set(["table", "td", "th"]);
function isTableElement(element) {
  return tableElements.has(getNodeName(element));
}
const topLayerSelectors = [":popover-open", ":modal"];
function isTopLayer(element) {
  return topLayerSelectors.some((selector) => {
    try {
      return element.matches(selector);
    } catch (_e2) {
      return false;
    }
  });
}
const transformProperties = ["transform", "translate", "scale", "rotate", "perspective"];
const willChangeValues = ["transform", "translate", "scale", "rotate", "perspective", "filter"];
const containValues = ["paint", "layout", "strict", "content"];
function isContainingBlock(elementOrCss) {
  const webkit = isWebKit();
  const css = isElement(elementOrCss) ? getComputedStyle$1(elementOrCss) : elementOrCss;
  return transformProperties.some((value) => css[value] ? css[value] !== "none" : false) || (css.containerType ? css.containerType !== "normal" : false) || !webkit && (css.backdropFilter ? css.backdropFilter !== "none" : false) || !webkit && (css.filter ? css.filter !== "none" : false) || willChangeValues.some((value) => (css.willChange || "").includes(value)) || containValues.some((value) => (css.contain || "").includes(value));
}
function getContainingBlock(element) {
  let currentNode = getParentNode(element);
  while (isHTMLElement(currentNode) && !isLastTraversableNode(currentNode)) {
    if (isContainingBlock(currentNode)) {
      return currentNode;
    } else if (isTopLayer(currentNode)) {
      return null;
    }
    currentNode = getParentNode(currentNode);
  }
  return null;
}
function isWebKit() {
  if (typeof CSS === "undefined" || !CSS.supports) return false;
  return CSS.supports("-webkit-backdrop-filter", "none");
}
const lastTraversableNodeNames = /* @__PURE__ */ new Set(["html", "body", "#document"]);
function isLastTraversableNode(node) {
  return lastTraversableNodeNames.has(getNodeName(node));
}
function getComputedStyle$1(element) {
  return getWindow(element).getComputedStyle(element);
}
function getNodeScroll(element) {
  if (isElement(element)) {
    return {
      scrollLeft: element.scrollLeft,
      scrollTop: element.scrollTop
    };
  }
  return {
    scrollLeft: element.scrollX,
    scrollTop: element.scrollY
  };
}
function getParentNode(node) {
  if (getNodeName(node) === "html") {
    return node;
  }
  const result = (
    // Step into the shadow DOM of the parent of a slotted node.
    node.assignedSlot || // DOM Element detected.
    node.parentNode || // ShadowRoot detected.
    isShadowRoot(node) && node.host || // Fallback.
    getDocumentElement(node)
  );
  return isShadowRoot(result) ? result.host : result;
}
function getNearestOverflowAncestor(node) {
  const parentNode = getParentNode(node);
  if (isLastTraversableNode(parentNode)) {
    return node.ownerDocument ? node.ownerDocument.body : node.body;
  }
  if (isHTMLElement(parentNode) && isOverflowElement(parentNode)) {
    return parentNode;
  }
  return getNearestOverflowAncestor(parentNode);
}
function getOverflowAncestors(node, list, traverseIframes) {
  var _node$ownerDocument2;
  if (list === void 0) {
    list = [];
  }
  if (traverseIframes === void 0) {
    traverseIframes = true;
  }
  const scrollableAncestor = getNearestOverflowAncestor(node);
  const isBody = scrollableAncestor === ((_node$ownerDocument2 = node.ownerDocument) == null ? void 0 : _node$ownerDocument2.body);
  const win = getWindow(scrollableAncestor);
  if (isBody) {
    const frameElement = getFrameElement(win);
    return list.concat(win, win.visualViewport || [], isOverflowElement(scrollableAncestor) ? scrollableAncestor : [], frameElement && traverseIframes ? getOverflowAncestors(frameElement) : []);
  }
  return list.concat(scrollableAncestor, getOverflowAncestors(scrollableAncestor, [], traverseIframes));
}
function getFrameElement(win) {
  return win.parent && Object.getPrototypeOf(win.parent) ? win.frameElement : null;
}
function getCssDimensions(element) {
  const css = getComputedStyle$1(element);
  let width = parseFloat(css.width) || 0;
  let height = parseFloat(css.height) || 0;
  const hasOffset = isHTMLElement(element);
  const offsetWidth = hasOffset ? element.offsetWidth : width;
  const offsetHeight = hasOffset ? element.offsetHeight : height;
  const shouldFallback = round(width) !== offsetWidth || round(height) !== offsetHeight;
  if (shouldFallback) {
    width = offsetWidth;
    height = offsetHeight;
  }
  return {
    width,
    height,
    $: shouldFallback
  };
}
function unwrapElement(element) {
  return !isElement(element) ? element.contextElement : element;
}
function getScale(element) {
  const domElement = unwrapElement(element);
  if (!isHTMLElement(domElement)) {
    return createCoords(1);
  }
  const rect = domElement.getBoundingClientRect();
  const {
    width,
    height,
    $
  } = getCssDimensions(domElement);
  let x2 = ($ ? round(rect.width) : rect.width) / width;
  let y = ($ ? round(rect.height) : rect.height) / height;
  if (!x2 || !Number.isFinite(x2)) {
    x2 = 1;
  }
  if (!y || !Number.isFinite(y)) {
    y = 1;
  }
  return {
    x: x2,
    y
  };
}
const noOffsets = /* @__PURE__ */ createCoords(0);
function getVisualOffsets(element) {
  const win = getWindow(element);
  if (!isWebKit() || !win.visualViewport) {
    return noOffsets;
  }
  return {
    x: win.visualViewport.offsetLeft,
    y: win.visualViewport.offsetTop
  };
}
function shouldAddVisualOffsets(element, isFixed, floatingOffsetParent) {
  if (isFixed === void 0) {
    isFixed = false;
  }
  if (!floatingOffsetParent || isFixed && floatingOffsetParent !== getWindow(element)) {
    return false;
  }
  return isFixed;
}
function getBoundingClientRect(element, includeScale, isFixedStrategy, offsetParent) {
  if (includeScale === void 0) {
    includeScale = false;
  }
  if (isFixedStrategy === void 0) {
    isFixedStrategy = false;
  }
  const clientRect = element.getBoundingClientRect();
  const domElement = unwrapElement(element);
  let scale = createCoords(1);
  if (includeScale) {
    if (offsetParent) {
      if (isElement(offsetParent)) {
        scale = getScale(offsetParent);
      }
    } else {
      scale = getScale(element);
    }
  }
  const visualOffsets = shouldAddVisualOffsets(domElement, isFixedStrategy, offsetParent) ? getVisualOffsets(domElement) : createCoords(0);
  let x2 = (clientRect.left + visualOffsets.x) / scale.x;
  let y = (clientRect.top + visualOffsets.y) / scale.y;
  let width = clientRect.width / scale.x;
  let height = clientRect.height / scale.y;
  if (domElement) {
    const win = getWindow(domElement);
    const offsetWin = offsetParent && isElement(offsetParent) ? getWindow(offsetParent) : offsetParent;
    let currentWin = win;
    let currentIFrame = getFrameElement(currentWin);
    while (currentIFrame && offsetParent && offsetWin !== currentWin) {
      const iframeScale = getScale(currentIFrame);
      const iframeRect = currentIFrame.getBoundingClientRect();
      const css = getComputedStyle$1(currentIFrame);
      const left = iframeRect.left + (currentIFrame.clientLeft + parseFloat(css.paddingLeft)) * iframeScale.x;
      const top = iframeRect.top + (currentIFrame.clientTop + parseFloat(css.paddingTop)) * iframeScale.y;
      x2 *= iframeScale.x;
      y *= iframeScale.y;
      width *= iframeScale.x;
      height *= iframeScale.y;
      x2 += left;
      y += top;
      currentWin = getWindow(currentIFrame);
      currentIFrame = getFrameElement(currentWin);
    }
  }
  return rectToClientRect({
    width,
    height,
    x: x2,
    y
  });
}
function getWindowScrollBarX(element, rect) {
  const leftScroll = getNodeScroll(element).scrollLeft;
  if (!rect) {
    return getBoundingClientRect(getDocumentElement(element)).left + leftScroll;
  }
  return rect.left + leftScroll;
}
function getHTMLOffset(documentElement, scroll) {
  const htmlRect = documentElement.getBoundingClientRect();
  const x2 = htmlRect.left + scroll.scrollLeft - getWindowScrollBarX(documentElement, htmlRect);
  const y = htmlRect.top + scroll.scrollTop;
  return {
    x: x2,
    y
  };
}
function convertOffsetParentRelativeRectToViewportRelativeRect(_ref) {
  let {
    elements,
    rect,
    offsetParent,
    strategy
  } = _ref;
  const isFixed = strategy === "fixed";
  const documentElement = getDocumentElement(offsetParent);
  const topLayer = elements ? isTopLayer(elements.floating) : false;
  if (offsetParent === documentElement || topLayer && isFixed) {
    return rect;
  }
  let scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  let scale = createCoords(1);
  const offsets = createCoords(0);
  const isOffsetParentAnElement = isHTMLElement(offsetParent);
  if (isOffsetParentAnElement || !isOffsetParentAnElement && !isFixed) {
    if (getNodeName(offsetParent) !== "body" || isOverflowElement(documentElement)) {
      scroll = getNodeScroll(offsetParent);
    }
    if (isHTMLElement(offsetParent)) {
      const offsetRect = getBoundingClientRect(offsetParent);
      scale = getScale(offsetParent);
      offsets.x = offsetRect.x + offsetParent.clientLeft;
      offsets.y = offsetRect.y + offsetParent.clientTop;
    }
  }
  const htmlOffset = documentElement && !isOffsetParentAnElement && !isFixed ? getHTMLOffset(documentElement, scroll) : createCoords(0);
  return {
    width: rect.width * scale.x,
    height: rect.height * scale.y,
    x: rect.x * scale.x - scroll.scrollLeft * scale.x + offsets.x + htmlOffset.x,
    y: rect.y * scale.y - scroll.scrollTop * scale.y + offsets.y + htmlOffset.y
  };
}
function getClientRects(element) {
  return Array.from(element.getClientRects());
}
function getDocumentRect(element) {
  const html = getDocumentElement(element);
  const scroll = getNodeScroll(element);
  const body = element.ownerDocument.body;
  const width = max(html.scrollWidth, html.clientWidth, body.scrollWidth, body.clientWidth);
  const height = max(html.scrollHeight, html.clientHeight, body.scrollHeight, body.clientHeight);
  let x2 = -scroll.scrollLeft + getWindowScrollBarX(element);
  const y = -scroll.scrollTop;
  if (getComputedStyle$1(body).direction === "rtl") {
    x2 += max(html.clientWidth, body.clientWidth) - width;
  }
  return {
    width,
    height,
    x: x2,
    y
  };
}
const SCROLLBAR_MAX = 25;
function getViewportRect(element, strategy) {
  const win = getWindow(element);
  const html = getDocumentElement(element);
  const visualViewport = win.visualViewport;
  let width = html.clientWidth;
  let height = html.clientHeight;
  let x2 = 0;
  let y = 0;
  if (visualViewport) {
    width = visualViewport.width;
    height = visualViewport.height;
    const visualViewportBased = isWebKit();
    if (!visualViewportBased || visualViewportBased && strategy === "fixed") {
      x2 = visualViewport.offsetLeft;
      y = visualViewport.offsetTop;
    }
  }
  const windowScrollbarX = getWindowScrollBarX(html);
  if (windowScrollbarX <= 0) {
    const doc = html.ownerDocument;
    const body = doc.body;
    const bodyStyles = getComputedStyle(body);
    const bodyMarginInline = doc.compatMode === "CSS1Compat" ? parseFloat(bodyStyles.marginLeft) + parseFloat(bodyStyles.marginRight) || 0 : 0;
    const clippingStableScrollbarWidth = Math.abs(html.clientWidth - body.clientWidth - bodyMarginInline);
    if (clippingStableScrollbarWidth <= SCROLLBAR_MAX) {
      width -= clippingStableScrollbarWidth;
    }
  } else if (windowScrollbarX <= SCROLLBAR_MAX) {
    width += windowScrollbarX;
  }
  return {
    width,
    height,
    x: x2,
    y
  };
}
const absoluteOrFixed = /* @__PURE__ */ new Set(["absolute", "fixed"]);
function getInnerBoundingClientRect(element, strategy) {
  const clientRect = getBoundingClientRect(element, true, strategy === "fixed");
  const top = clientRect.top + element.clientTop;
  const left = clientRect.left + element.clientLeft;
  const scale = isHTMLElement(element) ? getScale(element) : createCoords(1);
  const width = element.clientWidth * scale.x;
  const height = element.clientHeight * scale.y;
  const x2 = left * scale.x;
  const y = top * scale.y;
  return {
    width,
    height,
    x: x2,
    y
  };
}
function getClientRectFromClippingAncestor(element, clippingAncestor, strategy) {
  let rect;
  if (clippingAncestor === "viewport") {
    rect = getViewportRect(element, strategy);
  } else if (clippingAncestor === "document") {
    rect = getDocumentRect(getDocumentElement(element));
  } else if (isElement(clippingAncestor)) {
    rect = getInnerBoundingClientRect(clippingAncestor, strategy);
  } else {
    const visualOffsets = getVisualOffsets(element);
    rect = {
      x: clippingAncestor.x - visualOffsets.x,
      y: clippingAncestor.y - visualOffsets.y,
      width: clippingAncestor.width,
      height: clippingAncestor.height
    };
  }
  return rectToClientRect(rect);
}
function hasFixedPositionAncestor(element, stopNode) {
  const parentNode = getParentNode(element);
  if (parentNode === stopNode || !isElement(parentNode) || isLastTraversableNode(parentNode)) {
    return false;
  }
  return getComputedStyle$1(parentNode).position === "fixed" || hasFixedPositionAncestor(parentNode, stopNode);
}
function getClippingElementAncestors(element, cache) {
  const cachedResult = cache.get(element);
  if (cachedResult) {
    return cachedResult;
  }
  let result = getOverflowAncestors(element, [], false).filter((el) => isElement(el) && getNodeName(el) !== "body");
  let currentContainingBlockComputedStyle = null;
  const elementIsFixed = getComputedStyle$1(element).position === "fixed";
  let currentNode = elementIsFixed ? getParentNode(element) : element;
  while (isElement(currentNode) && !isLastTraversableNode(currentNode)) {
    const computedStyle = getComputedStyle$1(currentNode);
    const currentNodeIsContaining = isContainingBlock(currentNode);
    if (!currentNodeIsContaining && computedStyle.position === "fixed") {
      currentContainingBlockComputedStyle = null;
    }
    const shouldDropCurrentNode = elementIsFixed ? !currentNodeIsContaining && !currentContainingBlockComputedStyle : !currentNodeIsContaining && computedStyle.position === "static" && !!currentContainingBlockComputedStyle && absoluteOrFixed.has(currentContainingBlockComputedStyle.position) || isOverflowElement(currentNode) && !currentNodeIsContaining && hasFixedPositionAncestor(element, currentNode);
    if (shouldDropCurrentNode) {
      result = result.filter((ancestor) => ancestor !== currentNode);
    } else {
      currentContainingBlockComputedStyle = computedStyle;
    }
    currentNode = getParentNode(currentNode);
  }
  cache.set(element, result);
  return result;
}
function getClippingRect(_ref) {
  let {
    element,
    boundary,
    rootBoundary,
    strategy
  } = _ref;
  const elementClippingAncestors = boundary === "clippingAncestors" ? isTopLayer(element) ? [] : getClippingElementAncestors(element, this._c) : [].concat(boundary);
  const clippingAncestors = [...elementClippingAncestors, rootBoundary];
  const firstClippingAncestor = clippingAncestors[0];
  const clippingRect = clippingAncestors.reduce((accRect, clippingAncestor) => {
    const rect = getClientRectFromClippingAncestor(element, clippingAncestor, strategy);
    accRect.top = max(rect.top, accRect.top);
    accRect.right = min(rect.right, accRect.right);
    accRect.bottom = min(rect.bottom, accRect.bottom);
    accRect.left = max(rect.left, accRect.left);
    return accRect;
  }, getClientRectFromClippingAncestor(element, firstClippingAncestor, strategy));
  return {
    width: clippingRect.right - clippingRect.left,
    height: clippingRect.bottom - clippingRect.top,
    x: clippingRect.left,
    y: clippingRect.top
  };
}
function getDimensions(element) {
  const {
    width,
    height
  } = getCssDimensions(element);
  return {
    width,
    height
  };
}
function getRectRelativeToOffsetParent(element, offsetParent, strategy) {
  const isOffsetParentAnElement = isHTMLElement(offsetParent);
  const documentElement = getDocumentElement(offsetParent);
  const isFixed = strategy === "fixed";
  const rect = getBoundingClientRect(element, true, isFixed, offsetParent);
  let scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  const offsets = createCoords(0);
  function setLeftRTLScrollbarOffset() {
    offsets.x = getWindowScrollBarX(documentElement);
  }
  if (isOffsetParentAnElement || !isOffsetParentAnElement && !isFixed) {
    if (getNodeName(offsetParent) !== "body" || isOverflowElement(documentElement)) {
      scroll = getNodeScroll(offsetParent);
    }
    if (isOffsetParentAnElement) {
      const offsetRect = getBoundingClientRect(offsetParent, true, isFixed, offsetParent);
      offsets.x = offsetRect.x + offsetParent.clientLeft;
      offsets.y = offsetRect.y + offsetParent.clientTop;
    } else if (documentElement) {
      setLeftRTLScrollbarOffset();
    }
  }
  if (isFixed && !isOffsetParentAnElement && documentElement) {
    setLeftRTLScrollbarOffset();
  }
  const htmlOffset = documentElement && !isOffsetParentAnElement && !isFixed ? getHTMLOffset(documentElement, scroll) : createCoords(0);
  const x2 = rect.left + scroll.scrollLeft - offsets.x - htmlOffset.x;
  const y = rect.top + scroll.scrollTop - offsets.y - htmlOffset.y;
  return {
    x: x2,
    y,
    width: rect.width,
    height: rect.height
  };
}
function isStaticPositioned(element) {
  return getComputedStyle$1(element).position === "static";
}
function getTrueOffsetParent(element, polyfill) {
  if (!isHTMLElement(element) || getComputedStyle$1(element).position === "fixed") {
    return null;
  }
  if (polyfill) {
    return polyfill(element);
  }
  let rawOffsetParent = element.offsetParent;
  if (getDocumentElement(element) === rawOffsetParent) {
    rawOffsetParent = rawOffsetParent.ownerDocument.body;
  }
  return rawOffsetParent;
}
function getOffsetParent(element, polyfill) {
  const win = getWindow(element);
  if (isTopLayer(element)) {
    return win;
  }
  if (!isHTMLElement(element)) {
    let svgOffsetParent = getParentNode(element);
    while (svgOffsetParent && !isLastTraversableNode(svgOffsetParent)) {
      if (isElement(svgOffsetParent) && !isStaticPositioned(svgOffsetParent)) {
        return svgOffsetParent;
      }
      svgOffsetParent = getParentNode(svgOffsetParent);
    }
    return win;
  }
  let offsetParent = getTrueOffsetParent(element, polyfill);
  while (offsetParent && isTableElement(offsetParent) && isStaticPositioned(offsetParent)) {
    offsetParent = getTrueOffsetParent(offsetParent, polyfill);
  }
  if (offsetParent && isLastTraversableNode(offsetParent) && isStaticPositioned(offsetParent) && !isContainingBlock(offsetParent)) {
    return win;
  }
  return offsetParent || getContainingBlock(element) || win;
}
const getElementRects = async function(data) {
  const getOffsetParentFn = this.getOffsetParent || getOffsetParent;
  const getDimensionsFn = this.getDimensions;
  const floatingDimensions = await getDimensionsFn(data.floating);
  return {
    reference: getRectRelativeToOffsetParent(data.reference, await getOffsetParentFn(data.floating), data.strategy),
    floating: {
      x: 0,
      y: 0,
      width: floatingDimensions.width,
      height: floatingDimensions.height
    }
  };
};
function isRTL(element) {
  return getComputedStyle$1(element).direction === "rtl";
}
const platform = {
  convertOffsetParentRelativeRectToViewportRelativeRect,
  getDocumentElement,
  getClippingRect,
  getOffsetParent,
  getElementRects,
  getClientRects,
  getDimensions,
  getScale,
  isElement,
  isRTL
};
function rectsAreEqual(a, b) {
  return a.x === b.x && a.y === b.y && a.width === b.width && a.height === b.height;
}
function observeMove(element, onMove) {
  let io = null;
  let timeoutId;
  const root = getDocumentElement(element);
  function cleanup() {
    var _io;
    clearTimeout(timeoutId);
    (_io = io) == null || _io.disconnect();
    io = null;
  }
  function refresh(skip, threshold) {
    if (skip === void 0) {
      skip = false;
    }
    if (threshold === void 0) {
      threshold = 1;
    }
    cleanup();
    const elementRectForRootMargin = element.getBoundingClientRect();
    const {
      left,
      top,
      width,
      height
    } = elementRectForRootMargin;
    if (!skip) {
      onMove();
    }
    if (!width || !height) {
      return;
    }
    const insetTop = floor(top);
    const insetRight = floor(root.clientWidth - (left + width));
    const insetBottom = floor(root.clientHeight - (top + height));
    const insetLeft = floor(left);
    const rootMargin = -insetTop + "px " + -insetRight + "px " + -insetBottom + "px " + -insetLeft + "px";
    const options = {
      rootMargin,
      threshold: max(0, min(1, threshold)) || 1
    };
    let isFirstUpdate = true;
    function handleObserve(entries) {
      const ratio = entries[0].intersectionRatio;
      if (ratio !== threshold) {
        if (!isFirstUpdate) {
          return refresh();
        }
        if (!ratio) {
          timeoutId = setTimeout(() => {
            refresh(false, 1e-7);
          }, 1e3);
        } else {
          refresh(false, ratio);
        }
      }
      if (ratio === 1 && !rectsAreEqual(elementRectForRootMargin, element.getBoundingClientRect())) {
        refresh();
      }
      isFirstUpdate = false;
    }
    try {
      io = new IntersectionObserver(handleObserve, {
        ...options,
        // Handle <iframe>s
        root: root.ownerDocument
      });
    } catch (_e2) {
      io = new IntersectionObserver(handleObserve, options);
    }
    io.observe(element);
  }
  refresh(true);
  return cleanup;
}
function autoUpdate(reference, floating, update, options) {
  if (options === void 0) {
    options = {};
  }
  const {
    ancestorScroll = true,
    ancestorResize = true,
    elementResize = typeof ResizeObserver === "function",
    layoutShift = typeof IntersectionObserver === "function",
    animationFrame = false
  } = options;
  const referenceEl = unwrapElement(reference);
  const ancestors = ancestorScroll || ancestorResize ? [...referenceEl ? getOverflowAncestors(referenceEl) : [], ...getOverflowAncestors(floating)] : [];
  ancestors.forEach((ancestor) => {
    ancestorScroll && ancestor.addEventListener("scroll", update, {
      passive: true
    });
    ancestorResize && ancestor.addEventListener("resize", update);
  });
  const cleanupIo = referenceEl && layoutShift ? observeMove(referenceEl, update) : null;
  let reobserveFrame = -1;
  let resizeObserver = null;
  if (elementResize) {
    resizeObserver = new ResizeObserver((_ref) => {
      let [firstEntry] = _ref;
      if (firstEntry && firstEntry.target === referenceEl && resizeObserver) {
        resizeObserver.unobserve(floating);
        cancelAnimationFrame(reobserveFrame);
        reobserveFrame = requestAnimationFrame(() => {
          var _resizeObserver;
          (_resizeObserver = resizeObserver) == null || _resizeObserver.observe(floating);
        });
      }
      update();
    });
    if (referenceEl && !animationFrame) {
      resizeObserver.observe(referenceEl);
    }
    resizeObserver.observe(floating);
  }
  let frameId;
  let prevRefRect = animationFrame ? getBoundingClientRect(reference) : null;
  if (animationFrame) {
    frameLoop();
  }
  function frameLoop() {
    const nextRefRect = getBoundingClientRect(reference);
    if (prevRefRect && !rectsAreEqual(prevRefRect, nextRefRect)) {
      update();
    }
    prevRefRect = nextRefRect;
    frameId = requestAnimationFrame(frameLoop);
  }
  update();
  return () => {
    var _resizeObserver2;
    ancestors.forEach((ancestor) => {
      ancestorScroll && ancestor.removeEventListener("scroll", update);
      ancestorResize && ancestor.removeEventListener("resize", update);
    });
    cleanupIo == null || cleanupIo();
    (_resizeObserver2 = resizeObserver) == null || _resizeObserver2.disconnect();
    resizeObserver = null;
    if (animationFrame) {
      cancelAnimationFrame(frameId);
    }
  };
}
const offset = offset$1;
const shift = shift$1;
const flip = flip$1;
const limitShift = limitShift$1;
const computePosition = (reference, floating, options) => {
  const cache = /* @__PURE__ */ new Map();
  const mergedOptions = {
    platform,
    ...options
  };
  const platformWithCache = {
    ...mergedOptions.platform,
    _c: cache
  };
  return computePosition$1(reference, floating, {
    ...mergedOptions,
    platform: platformWithCache
  });
};
var E = Object.defineProperty, M = Object.defineProperties;
var x = Object.getOwnPropertyDescriptors;
var V = Object.getOwnPropertySymbols;
var I = Object.prototype.hasOwnProperty, N = Object.prototype.propertyIsEnumerable;
var C = (e, t2, s) => t2 in e ? E(e, t2, { enumerable: true, configurable: true, writable: true, value: s }) : e[t2] = s, f = (e, t2) => {
  for (var s in t2 || (t2 = {}))
    I.call(t2, s) && C(e, s, t2[s]);
  if (V)
    for (var s of V(t2))
      N.call(t2, s) && C(e, s, t2[s]);
  return e;
}, m = (e, t2) => M(e, x(t2));
const U = {
  props: {
    autoscroll: {
      type: Boolean,
      default: true
    }
  },
  watch: {
    typeAheadPointer() {
      this.autoscroll && this.maybeAdjustScroll();
    },
    open(e) {
      this.autoscroll && e && this.$nextTick(() => this.maybeAdjustScroll());
    }
  },
  methods: {
    maybeAdjustScroll() {
      var t2;
      const e = ((t2 = this.$refs.dropdownMenu) == null ? void 0 : t2.children[this.typeAheadPointer]) || false;
      if (e) {
        const s = this.getDropdownViewport(), { top: n, bottom: l, height: i } = e.getBoundingClientRect();
        if (n < s.top)
          return this.$refs.dropdownMenu.scrollTop = e.offsetTop;
        if (l > s.bottom)
          return this.$refs.dropdownMenu.scrollTop = e.offsetTop - (s.height - i);
      }
    },
    getDropdownViewport() {
      return this.$refs.dropdownMenu ? this.$refs.dropdownMenu.getBoundingClientRect() : {
        height: 0,
        top: 0,
        bottom: 0
      };
    }
  }
}, q = {
  data() {
    return {
      typeAheadPointer: -1
    };
  },
  watch: {
    filteredOptions() {
      for (let e = 0; e < this.filteredOptions.length; e++)
        if (this.selectable(this.filteredOptions[e])) {
          this.typeAheadPointer = e;
          break;
        }
    },
    open(e) {
      e && this.typeAheadToLastSelected();
    },
    selectedValue() {
      this.open && this.typeAheadToLastSelected();
    }
  },
  methods: {
    typeAheadUp() {
      for (let e = this.typeAheadPointer - 1; e >= 0; e--)
        if (this.selectable(this.filteredOptions[e])) {
          this.typeAheadPointer = e;
          break;
        }
    },
    typeAheadDown() {
      for (let e = this.typeAheadPointer + 1; e < this.filteredOptions.length; e++)
        if (this.selectable(this.filteredOptions[e])) {
          this.typeAheadPointer = e;
          break;
        }
    },
    typeAheadSelect() {
      const e = this.filteredOptions[this.typeAheadPointer];
      e && this.selectable(e) && this.select(e);
    },
    typeAheadToLastSelected() {
      this.typeAheadPointer = this.selectedValue.length !== 0 ? this.filteredOptions.indexOf(this.selectedValue[this.selectedValue.length - 1]) : -1;
    }
  }
}, J = {
  props: {
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      mutableLoading: false
    };
  },
  watch: {
    search() {
      this.$emit("search", this.search, this.toggleLoading);
    },
    loading(e) {
      this.mutableLoading = e;
    }
  },
  methods: {
    toggleLoading(e = null) {
      return e == null ? this.mutableLoading = !this.mutableLoading : this.mutableLoading = e;
    }
  }
}, S = (e, t2) => {
  const s = e.__vccOpts || e;
  for (const [n, l] of t2)
    s[n] = l;
  return s;
}, H = {}, X = {
  xmlns: "http://www.w3.org/2000/svg",
  width: "10",
  height: "10"
}, Y = /* @__PURE__ */ createBaseVNode("path", { d: "M6.895455 5l2.842897-2.842898c.348864-.348863.348864-.914488 0-1.263636L9.106534.261648c-.348864-.348864-.914489-.348864-1.263636 0L5 3.104545 2.157102.261648c-.348863-.348864-.914488-.348864-1.263636 0L.261648.893466c-.348864.348864-.348864.914489 0 1.263636L3.104545 5 .261648 7.842898c-.348864.348863-.348864.914488 0 1.263636l.631818.631818c.348864.348864.914773.348864 1.263636 0L5 6.895455l2.842898 2.842897c.348863.348864.914772.348864 1.263636 0l.631818-.631818c.348864-.348864.348864-.914489 0-1.263636L6.895455 5z" }, null, -1), Q = [
  Y
];
function G(e, t2) {
  return openBlock(), createElementBlock("svg", X, Q);
}
const W = /* @__PURE__ */ S(H, [["render", G]]), Z = {}, ee = {
  xmlns: "http://www.w3.org/2000/svg",
  width: "14",
  height: "10"
}, te = /* @__PURE__ */ createBaseVNode("path", { d: "M9.211364 7.59931l4.48338-4.867229c.407008-.441854.407008-1.158247 0-1.60046l-.73712-.80023c-.407008-.441854-1.066904-.441854-1.474243 0L7 5.198617 2.51662.33139c-.407008-.441853-1.066904-.441853-1.474243 0l-.737121.80023c-.407008.441854-.407008 1.158248 0 1.600461l4.48338 4.867228L7 10l2.211364-2.40069z" }, null, -1), se = [
  te
];
function ie(e, t2) {
  return openBlock(), createElementBlock("svg", ee, se);
}
const oe = /* @__PURE__ */ S(Z, [["render", ie]]), T = {
  Deselect: W,
  OpenIndicator: oe
}, ne = {
  mounted(e, { instance: t2 }) {
    if (t2.appendToBody) {
      const {
        height: s,
        top: n,
        left: l,
        width: i
      } = t2.$refs.toggle.getBoundingClientRect();
      let y = window.scrollX || window.pageXOffset, o = window.scrollY || window.pageYOffset;
      e.unbindPosition = t2.calculatePosition(e, t2, {
        width: i + "px",
        left: y + l + "px",
        top: o + n + s + "px"
      }), document.body.appendChild(e);
    }
  },
  unmounted(e, { instance: t2 }) {
    t2.appendToBody && (e.unbindPosition && typeof e.unbindPosition == "function" && e.unbindPosition(), e.parentNode && e.parentNode.removeChild(e));
  }
};
function le(e) {
  const t2 = {};
  return Object.keys(e).sort().forEach((s) => {
    t2[s] = e[s];
  }), JSON.stringify(t2);
}
let ae = 0;
function re() {
  return ++ae;
}
const de = {
  components: f({}, T),
  directives: { appendToBody: ne },
  mixins: [U, q, J],
  compatConfig: {
    MODE: 3
  },
  emits: [
    "open",
    "close",
    "update:modelValue",
    "search",
    "search:compositionstart",
    "search:compositionend",
    "search:keydown",
    "search:blur",
    "search:focus",
    "search:input",
    "option:created",
    "option:selecting",
    "option:selected",
    "option:deselecting",
    "option:deselected"
  ],
  props: {
    modelValue: {},
    components: {
      type: Object,
      default: () => ({})
    },
    options: {
      type: Array,
      default() {
        return [];
      }
    },
    disabled: {
      type: Boolean,
      default: false
    },
    clearable: {
      type: Boolean,
      default: true
    },
    deselectFromDropdown: {
      type: Boolean,
      default: false
    },
    searchable: {
      type: Boolean,
      default: true
    },
    multiple: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: ""
    },
    transition: {
      type: String,
      default: "vs__fade"
    },
    clearSearchOnSelect: {
      type: Boolean,
      default: true
    },
    closeOnSelect: {
      type: Boolean,
      default: true
    },
    label: {
      type: String,
      default: "label"
    },
    autocomplete: {
      type: String,
      default: "off"
    },
    reduce: {
      type: Function,
      default: (e) => e
    },
    selectable: {
      type: Function,
      default: (e) => true
    },
    getOptionLabel: {
      type: Function,
      default(e) {
        return typeof e == "object" ? e.hasOwnProperty(this.label) ? e[this.label] : console.warn(`[vue-select warn]: Label key "option.${this.label}" does not exist in options object ${JSON.stringify(e)}.
https://vue-select.org/api/props.html#getoptionlabel`) : e;
      }
    },
    getOptionKey: {
      type: Function,
      default(e) {
        if (typeof e != "object")
          return e;
        try {
          return e.hasOwnProperty("id") ? e.id : le(e);
        } catch (t2) {
          return console.warn(`[vue-select warn]: Could not stringify this option to generate unique key. Please provide'getOptionKey' prop to return a unique key for each option.
https://vue-select.org/api/props.html#getoptionkey`, e, t2);
        }
      }
    },
    onTab: {
      type: Function,
      default: function() {
        this.selectOnTab && !this.isComposing && this.typeAheadSelect();
      }
    },
    taggable: {
      type: Boolean,
      default: false
    },
    tabindex: {
      type: Number,
      default: null
    },
    pushTags: {
      type: Boolean,
      default: false
    },
    filterable: {
      type: Boolean,
      default: true
    },
    filterBy: {
      type: Function,
      default(e, t2, s) {
        return (t2 || "").toLocaleLowerCase().indexOf(s.toLocaleLowerCase()) > -1;
      }
    },
    filter: {
      type: Function,
      default(e, t2) {
        return e.filter((s) => {
          let n = this.getOptionLabel(s);
          return typeof n == "number" && (n = n.toString()), this.filterBy(s, n, t2);
        });
      }
    },
    createOption: {
      type: Function,
      default(e) {
        return typeof this.optionList[0] == "object" ? { [this.label]: e } : e;
      }
    },
    resetOnOptionsChange: {
      default: false,
      validator: (e) => ["function", "boolean"].includes(typeof e)
    },
    clearSearchOnBlur: {
      type: Function,
      default: function({ clearSearchOnSelect: e, multiple: t2 }) {
        return e && !t2;
      }
    },
    noDrop: {
      type: Boolean,
      default: false
    },
    inputId: {
      type: String
    },
    dir: {
      type: String,
      default: "auto"
    },
    selectOnTab: {
      type: Boolean,
      default: false
    },
    selectOnKeyCodes: {
      type: Array,
      default: () => [13]
    },
    searchInputQuerySelector: {
      type: String,
      default: "[type=search]"
    },
    mapKeydown: {
      type: Function,
      default: (e, t2) => e
    },
    appendToBody: {
      type: Boolean,
      default: false
    },
    calculatePosition: {
      type: Function,
      default(e, t2, { width: s, top: n, left: l }) {
        e.style.top = n, e.style.left = l, e.style.width = s;
      }
    },
    dropdownShouldOpen: {
      type: Function,
      default({ noDrop: e, open: t2, mutableLoading: s }) {
        return e ? false : t2 && !s;
      }
    },
    uid: {
      type: [String, Number],
      default: () => re()
    }
  },
  data() {
    return {
      search: "",
      open: false,
      isComposing: false,
      pushedTags: [],
      _value: [],
      deselectButtons: []
    };
  },
  computed: {
    isReducingValues() {
      return this.$props.reduce !== this.$options.props.reduce.default;
    },
    isTrackingValues() {
      return typeof this.modelValue == "undefined" || this.isReducingValues;
    },
    selectedValue() {
      let e = this.modelValue;
      return this.isTrackingValues && (e = this.$data._value), e != null && e !== "" ? [].concat(e) : [];
    },
    optionList() {
      return this.options.concat(this.pushTags ? this.pushedTags : []);
    },
    searchEl() {
      return this.$slots.search ? this.$refs.selectedOptions.querySelector(this.searchInputQuerySelector) : this.$refs.search;
    },
    scope() {
      const e = {
        search: this.search,
        loading: this.loading,
        searching: this.searching,
        filteredOptions: this.filteredOptions
      };
      return {
        search: {
          attributes: f({
            disabled: this.disabled,
            placeholder: this.searchPlaceholder,
            tabindex: this.tabindex,
            readonly: !this.searchable,
            id: this.inputId,
            "aria-autocomplete": "list",
            "aria-labelledby": `vs${this.uid}__combobox`,
            "aria-controls": `vs${this.uid}__listbox`,
            ref: "search",
            type: "search",
            autocomplete: this.autocomplete,
            value: this.search
          }, this.dropdownOpen && this.filteredOptions[this.typeAheadPointer] ? {
            "aria-activedescendant": `vs${this.uid}__option-${this.typeAheadPointer}`
          } : {}),
          events: {
            compositionstart: () => this.isComposing = true,
            compositionend: () => this.isComposing = false,
            keydown: this.onSearchKeyDown,
            blur: this.onSearchBlur,
            focus: this.onSearchFocus,
            input: (t2) => this.search = t2.target.value
          }
        },
        spinner: {
          loading: this.mutableLoading
        },
        noOptions: {
          search: this.search,
          loading: this.mutableLoading,
          searching: this.searching
        },
        openIndicator: {
          attributes: {
            ref: "openIndicator",
            role: "presentation",
            class: "vs__open-indicator"
          }
        },
        listHeader: e,
        listFooter: e,
        header: m(f({}, e), { deselect: this.deselect }),
        footer: m(f({}, e), { deselect: this.deselect })
      };
    },
    childComponents() {
      return f(f({}, T), this.components);
    },
    stateClasses() {
      return {
        "vs--open": this.dropdownOpen,
        "vs--single": !this.multiple,
        "vs--multiple": this.multiple,
        "vs--searching": this.searching && !this.noDrop,
        "vs--searchable": this.searchable && !this.noDrop,
        "vs--unsearchable": !this.searchable,
        "vs--loading": this.mutableLoading,
        "vs--disabled": this.disabled
      };
    },
    searching() {
      return !!this.search;
    },
    dropdownOpen() {
      return this.dropdownShouldOpen(this);
    },
    searchPlaceholder() {
      return this.isValueEmpty && this.placeholder ? this.placeholder : void 0;
    },
    filteredOptions() {
      const e = [].concat(this.optionList);
      if (!this.filterable && !this.taggable)
        return e;
      const t2 = this.search.length ? this.filter(e, this.search, this) : e;
      if (this.taggable && this.search.length) {
        const s = this.createOption(this.search);
        this.optionExists(s) || t2.unshift(s);
      }
      return t2;
    },
    isValueEmpty() {
      return this.selectedValue.length === 0;
    },
    showClearButton() {
      return !this.multiple && this.clearable && !this.open && !this.isValueEmpty;
    }
  },
  watch: {
    options(e, t2) {
      const s = () => typeof this.resetOnOptionsChange == "function" ? this.resetOnOptionsChange(e, t2, this.selectedValue) : this.resetOnOptionsChange;
      !this.taggable && s() && this.clearSelection(), this.modelValue && this.isTrackingValues && this.setInternalValueFromOptions(this.modelValue);
    },
    modelValue: {
      immediate: true,
      handler(e) {
        this.isTrackingValues && this.setInternalValueFromOptions(e);
      }
    },
    multiple() {
      this.clearSelection();
    },
    open(e) {
      this.$emit(e ? "open" : "close");
    }
  },
  created() {
    this.mutableLoading = this.loading;
  },
  methods: {
    setInternalValueFromOptions(e) {
      Array.isArray(e) ? this.$data._value = e.map((t2) => this.findOptionFromReducedValue(t2)) : this.$data._value = this.findOptionFromReducedValue(e);
    },
    select(e) {
      this.$emit("option:selecting", e), this.isOptionSelected(e) ? this.deselectFromDropdown && (this.clearable || this.multiple && this.selectedValue.length > 1) && this.deselect(e) : (this.taggable && !this.optionExists(e) && (this.$emit("option:created", e), this.pushTag(e)), this.multiple && (e = this.selectedValue.concat(e)), this.updateValue(e), this.$emit("option:selected", e)), this.onAfterSelect(e);
    },
    deselect(e) {
      this.$emit("option:deselecting", e), this.updateValue(this.selectedValue.filter((t2) => !this.optionComparator(t2, e))), this.$emit("option:deselected", e);
    },
    clearSelection() {
      this.updateValue(this.multiple ? [] : null);
    },
    onAfterSelect(e) {
      this.closeOnSelect && (this.open = !this.open, this.searchEl.blur()), this.clearSearchOnSelect && (this.search = "");
    },
    updateValue(e) {
      typeof this.modelValue == "undefined" && (this.$data._value = e), e !== null && (Array.isArray(e) ? e = e.map((t2) => this.reduce(t2)) : e = this.reduce(e)), this.$emit("update:modelValue", e);
    },
    toggleDropdown(e) {
      const t2 = e.target !== this.searchEl;
      t2 && e.preventDefault();
      const s = [
        ...this.deselectButtons || [],
        this.$refs.clearButton
      ];
      if (this.searchEl === void 0 || s.filter(Boolean).some((n) => n.contains(e.target) || n === e.target)) {
        e.preventDefault();
        return;
      }
      this.open && t2 ? this.searchEl.blur() : this.disabled || (this.open = true, this.searchEl.focus());
    },
    isOptionSelected(e) {
      return this.selectedValue.some((t2) => this.optionComparator(t2, e));
    },
    isOptionDeselectable(e) {
      return this.isOptionSelected(e) && this.deselectFromDropdown;
    },
    optionComparator(e, t2) {
      return this.getOptionKey(e) === this.getOptionKey(t2);
    },
    findOptionFromReducedValue(e) {
      const t2 = (n) => JSON.stringify(this.reduce(n)) === JSON.stringify(e), s = [...this.options, ...this.pushedTags].filter(t2);
      return s.length === 1 ? s[0] : s.find((n) => this.optionComparator(n, this.$data._value)) || e;
    },
    closeSearchOptions() {
      this.open = false, this.$emit("search:blur");
    },
    maybeDeleteValue() {
      if (!this.searchEl.value.length && this.selectedValue && this.selectedValue.length && this.clearable) {
        let e = null;
        this.multiple && (e = [
          ...this.selectedValue.slice(0, this.selectedValue.length - 1)
        ]), this.updateValue(e);
      }
    },
    optionExists(e) {
      return this.optionList.some((t2) => this.optionComparator(t2, e));
    },
    normalizeOptionForSlot(e) {
      return typeof e == "object" ? e : { [this.label]: e };
    },
    pushTag(e) {
      this.pushedTags.push(e);
    },
    onEscape() {
      this.search.length ? this.search = "" : this.searchEl.blur();
    },
    onSearchBlur() {
      if (this.mousedown && !this.searching)
        this.mousedown = false;
      else {
        const { clearSearchOnSelect: e, multiple: t2 } = this;
        this.clearSearchOnBlur({ clearSearchOnSelect: e, multiple: t2 }) && (this.search = ""), this.closeSearchOptions();
        return;
      }
      if (this.search.length === 0 && this.options.length === 0) {
        this.closeSearchOptions();
        return;
      }
    },
    onSearchFocus() {
      this.open = true, this.$emit("search:focus");
    },
    onMousedown() {
      this.mousedown = true;
    },
    onMouseUp() {
      this.mousedown = false;
    },
    onSearchKeyDown(e) {
      const t2 = (l) => (l.preventDefault(), !this.isComposing && this.typeAheadSelect()), s = {
        8: (l) => this.maybeDeleteValue(),
        9: (l) => this.onTab(),
        27: (l) => this.onEscape(),
        38: (l) => (l.preventDefault(), this.typeAheadUp()),
        40: (l) => (l.preventDefault(), this.typeAheadDown())
      };
      this.selectOnKeyCodes.forEach((l) => s[l] = t2);
      const n = this.mapKeydown(s, this);
      if (typeof n[e.keyCode] == "function")
        return n[e.keyCode](e);
    }
  }
}, he = ["dir"], ce = ["id", "aria-expanded", "aria-owns"], ue = {
  ref: "selectedOptions",
  class: "vs__selected-options"
}, pe = ["disabled", "title", "aria-label", "onClick"], fe = {
  ref: "actions",
  class: "vs__actions"
}, ge = ["disabled"], ye = { class: "vs__spinner" }, me = ["id"], be = ["id", "aria-selected", "onMouseover", "onClick"], _e = {
  key: 0,
  class: "vs__no-options"
}, Oe = /* @__PURE__ */ createTextVNode(" Sorry, no matching options. "), we = ["id"];
function ve(e, t2, s, n, l, i) {
  const y = resolveDirective("append-to-body");
  return openBlock(), createElementBlock("div", {
    dir: s.dir,
    class: normalizeClass(["v-select", i.stateClasses])
  }, [
    renderSlot(e.$slots, "header", normalizeProps(guardReactiveProps(i.scope.header))),
    createBaseVNode("div", {
      id: `vs${s.uid}__combobox`,
      ref: "toggle",
      class: "vs__dropdown-toggle",
      role: "combobox",
      "aria-expanded": i.dropdownOpen.toString(),
      "aria-owns": `vs${s.uid}__listbox`,
      "aria-label": "Search for option",
      onMousedown: t2[1] || (t2[1] = (o) => i.toggleDropdown(o))
    }, [
      createBaseVNode("div", ue, [
        (openBlock(true), createElementBlock(Fragment, null, renderList(i.selectedValue, (o, p) => renderSlot(e.$slots, "selected-option-container", {
          option: i.normalizeOptionForSlot(o),
          deselect: i.deselect,
          multiple: s.multiple,
          disabled: s.disabled
        }, () => [
          (openBlock(), createElementBlock("span", {
            key: s.getOptionKey(o),
            class: "vs__selected"
          }, [
            renderSlot(e.$slots, "selected-option", normalizeProps(guardReactiveProps(i.normalizeOptionForSlot(o))), () => [
              createTextVNode(toDisplayString(s.getOptionLabel(o)), 1)
            ]),
            s.multiple ? (openBlock(), createElementBlock("button", {
              key: 0,
              ref_for: true,
              ref: (g) => l.deselectButtons[p] = g,
              disabled: s.disabled,
              type: "button",
              class: "vs__deselect",
              title: `Deselect ${s.getOptionLabel(o)}`,
              "aria-label": `Deselect ${s.getOptionLabel(o)}`,
              onClick: (g) => i.deselect(o)
            }, [
              (openBlock(), createBlock(resolveDynamicComponent(i.childComponents.Deselect)))
            ], 8, pe)) : createCommentVNode("", true)
          ]))
        ])), 256)),
        renderSlot(e.$slots, "search", normalizeProps(guardReactiveProps(i.scope.search)), () => [
          createBaseVNode("input", mergeProps({ class: "vs__search" }, i.scope.search.attributes, toHandlers(i.scope.search.events)), null, 16)
        ])
      ], 512),
      createBaseVNode("div", fe, [
        withDirectives(createBaseVNode("button", {
          ref: "clearButton",
          disabled: s.disabled,
          type: "button",
          class: "vs__clear",
          title: "Clear Selected",
          "aria-label": "Clear Selected",
          onClick: t2[0] || (t2[0] = (...o) => i.clearSelection && i.clearSelection(...o))
        }, [
          (openBlock(), createBlock(resolveDynamicComponent(i.childComponents.Deselect)))
        ], 8, ge), [
          [vShow, i.showClearButton]
        ]),
        renderSlot(e.$slots, "open-indicator", normalizeProps(guardReactiveProps(i.scope.openIndicator)), () => [
          s.noDrop ? createCommentVNode("", true) : (openBlock(), createBlock(resolveDynamicComponent(i.childComponents.OpenIndicator), normalizeProps(mergeProps({ key: 0 }, i.scope.openIndicator.attributes)), null, 16))
        ]),
        renderSlot(e.$slots, "spinner", normalizeProps(guardReactiveProps(i.scope.spinner)), () => [
          withDirectives(createBaseVNode("div", ye, "Loading...", 512), [
            [vShow, e.mutableLoading]
          ])
        ])
      ], 512)
    ], 40, ce),
    createVNode(Transition, { name: s.transition }, {
      default: withCtx(() => [
        i.dropdownOpen ? withDirectives((openBlock(), createElementBlock("ul", {
          id: `vs${s.uid}__listbox`,
          ref: "dropdownMenu",
          key: `vs${s.uid}__listbox`,
          class: "vs__dropdown-menu",
          role: "listbox",
          tabindex: "-1",
          onMousedown: t2[2] || (t2[2] = withModifiers((...o) => i.onMousedown && i.onMousedown(...o), ["prevent"])),
          onMouseup: t2[3] || (t2[3] = (...o) => i.onMouseUp && i.onMouseUp(...o))
        }, [
          renderSlot(e.$slots, "list-header", normalizeProps(guardReactiveProps(i.scope.listHeader))),
          (openBlock(true), createElementBlock(Fragment, null, renderList(i.filteredOptions, (o, p) => (openBlock(), createElementBlock("li", {
            id: `vs${s.uid}__option-${p}`,
            key: s.getOptionKey(o),
            role: "option",
            class: normalizeClass(["vs__dropdown-option", {
              "vs__dropdown-option--deselect": i.isOptionDeselectable(o) && p === e.typeAheadPointer,
              "vs__dropdown-option--selected": i.isOptionSelected(o),
              "vs__dropdown-option--highlight": p === e.typeAheadPointer,
              "vs__dropdown-option--disabled": !s.selectable(o)
            }]),
            "aria-selected": p === e.typeAheadPointer ? true : null,
            onMouseover: (g) => s.selectable(o) ? e.typeAheadPointer = p : null,
            onClick: withModifiers((g) => s.selectable(o) ? i.select(o) : null, ["prevent", "stop"])
          }, [
            renderSlot(e.$slots, "option", normalizeProps(guardReactiveProps(i.normalizeOptionForSlot(o))), () => [
              createTextVNode(toDisplayString(s.getOptionLabel(o)), 1)
            ])
          ], 42, be))), 128)),
          i.filteredOptions.length === 0 ? (openBlock(), createElementBlock("li", _e, [
            renderSlot(e.$slots, "no-options", normalizeProps(guardReactiveProps(i.scope.noOptions)), () => [
              Oe
            ])
          ])) : createCommentVNode("", true),
          renderSlot(e.$slots, "list-footer", normalizeProps(guardReactiveProps(i.scope.listFooter)))
        ], 40, me)), [
          [y]
        ]) : (openBlock(), createElementBlock("ul", {
          key: 1,
          id: `vs${s.uid}__listbox`,
          role: "listbox",
          style: { display: "none", visibility: "hidden" }
        }, null, 8, we))
      ]),
      _: 3
    }, 8, ["name"]),
    renderSlot(e.$slots, "footer", normalizeProps(guardReactiveProps(i.scope.footer)))
  ], 10, he);
}
const Ce = /* @__PURE__ */ S(de, [["render", ve]]);
/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
function findRanges(text, search) {
  const ranges = [];
  let currentIndex = 0;
  let index = text.toLowerCase().indexOf(search.toLowerCase(), currentIndex);
  let i = 0;
  while (index > -1 && i++ < text.length) {
    currentIndex = index + search.length;
    ranges.push({ start: index, end: currentIndex });
    index = text.toLowerCase().indexOf(search.toLowerCase(), currentIndex);
  }
  return ranges;
}
const _sfc_main$2 = defineComponent({
  name: "NcHighlight",
  props: {
    /**
     * The string to display
     */
    text: {
      type: String,
      default: ""
    },
    /**
     * The string to match and highlight
     */
    search: {
      type: String,
      default: ""
    },
    /**
     * The ranges to highlight, takes precedence over the search prop.
     */
    highlight: {
      type: Array,
      default: () => []
    }
  },
  computed: {
    /**
     * The indice ranges which should be highlighted.
     * If an array with ranges is provided, we use it. Otherwise
     * we calculate it based on the provided substring to highlight.
     *
     * @return The array of ranges to highlight
     */
    ranges() {
      let ranges = [];
      if (!this.search && this.highlight.length === 0) {
        return ranges;
      }
      if (this.highlight.length > 0) {
        ranges = this.highlight;
      } else {
        ranges = findRanges(this.text, this.search);
      }
      ranges.forEach((range, i) => {
        if (range.end < range.start) {
          ranges[i] = {
            start: range.end,
            end: range.start
          };
        }
      });
      ranges = ranges.reduce((validRanges, range) => {
        if (range.start < this.text.length && range.end > 0) {
          validRanges.push({
            start: range.start < 0 ? 0 : range.start,
            end: range.end > this.text.length ? this.text.length : range.end
          });
        }
        return validRanges;
      }, []);
      ranges.sort((a, b) => {
        return a.start - b.start;
      });
      ranges = ranges.reduce((mergedRanges, range) => {
        if (!mergedRanges.length) {
          mergedRanges.push(range);
        } else {
          const idx = mergedRanges.length - 1;
          if (mergedRanges[idx].end >= range.start) {
            mergedRanges[idx] = {
              start: mergedRanges[idx].start,
              end: Math.max(mergedRanges[idx].end, range.end)
            };
          } else {
            mergedRanges.push(range);
          }
        }
        return mergedRanges;
      }, []);
      return ranges;
    },
    /**
     * Calculate the different chunks to show based on the ranges to highlight.
     */
    chunks() {
      if (this.ranges.length === 0) {
        return [{
          start: 0,
          end: this.text.length,
          highlight: false,
          text: this.text
        }];
      }
      const chunks = [];
      let currentIndex = 0;
      let currentRange = 0;
      while (currentIndex < this.text.length) {
        const range = this.ranges[currentRange];
        if (range.start === currentIndex) {
          chunks.push({
            ...range,
            highlight: true,
            text: this.text.slice(range.start, range.end)
          });
          currentRange++;
          currentIndex = range.end;
          if (currentRange >= this.ranges.length && currentIndex < this.text.length) {
            chunks.push({
              start: currentIndex,
              end: this.text.length,
              highlight: false,
              text: this.text.slice(currentIndex)
            });
            currentIndex = this.text.length;
          }
          continue;
        }
        chunks.push({
          start: currentIndex,
          end: range.start,
          highlight: false,
          text: this.text.slice(currentIndex, range.start)
        });
        currentIndex = range.start;
      }
      return chunks;
    }
  },
  /**
   * The render function to display the component
   */
  render() {
    if (!this.ranges.length) {
      return h("span", {}, this.text);
    }
    return h("span", {}, this.chunks.map((chunk) => {
      return chunk.highlight ? h("strong", {}, chunk.text) : chunk.text;
    }));
  }
});
const _sfc_main$1 = {
  name: "NcEllipsisedOption",
  components: {
    NcHighlight: _sfc_main$2
  },
  props: {
    /**
     * The text to be display in one line. If it is longer than 10 characters, it is be truncated with ellipsis in the end but keeping up to 10 last characters to fit the parent container.
     */
    name: {
      type: String,
      default: ""
    },
    /**
     * The search value to highlight in the text
     */
    search: {
      type: String,
      default: ""
    }
  },
  computed: {
    needsTruncate() {
      return this.name && this.name.length >= 10;
    },
    /**
     * Index at which to split the name if it is longer than 10 characters.
     *
     * @return {number} The position at which to split
     */
    split() {
      return this.name.length - Math.min(Math.floor(this.name.length / 2), 10);
    },
    part1() {
      if (this.needsTruncate) {
        return this.name.slice(0, this.split);
      }
      return this.name;
    },
    part2() {
      if (this.needsTruncate) {
        return this.name.slice(this.split);
      }
      return "";
    },
    /**
     * The ranges to highlight. Since we split the string for ellipsising,
     * the Highlight component cannot figure this out itself and needs the ranges provided.
     *
     * @return {Array} The array with the ranges to highlight
     */
    highlight1() {
      if (!this.search) {
        return [];
      }
      return findRanges(this.name, this.search);
    },
    /**
     * We shift the ranges for the second part by the position of the split.
     * Ranges out of the string length are discarded by the Highlight component,
     * so we don't need to take care of this here.
     *
     * @return {Array} The array with the ranges to highlight
     */
    highlight2() {
      return this.highlight1.map((range) => {
        return {
          start: range.start - this.split,
          end: range.end - this.split
        };
      });
    }
  }
};
const _hoisted_1$1 = ["title"];
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcHighlight = resolveComponent("NcHighlight");
  return openBlock(), createElementBlock("span", {
    dir: "auto",
    class: "name-parts",
    title: $props.name
  }, [
    createVNode(_component_NcHighlight, {
      class: "name-parts__first",
      text: $options.part1,
      search: $props.search,
      highlight: $options.highlight1
    }, null, 8, ["text", "search", "highlight"]),
    $options.part2 ? (openBlock(), createBlock(_component_NcHighlight, {
      key: 0,
      class: "name-parts__last",
      text: $options.part2,
      search: $props.search,
      highlight: $options.highlight2
    }, null, 8, ["text", "search", "highlight"])) : createCommentVNode("", true)
  ], 8, _hoisted_1$1);
}
const NcEllipsisedOption = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-a612f185"]]);
register(t17);
const _sfc_main = {
  name: "NcSelect",
  components: {
    ChevronDown,
    NcEllipsisedOption,
    NcLoadingIcon,
    VueSelect: Ce
  },
  props: {
    // Add VueSelect props to $props
    ...Ce.props,
    ...Ce.mixins.reduce((allProps, mixin) => ({ ...allProps, ...mixin.props }), {}),
    /**
     * `aria-label` for the clear input button
     */
    ariaLabelClearSelected: {
      type: String,
      default: t("Clear selected")
    },
    /**
     * `aria-label` for the search input
     *
     * A descriptive `inputLabel` is preferred as this is not visible.
     */
    ariaLabelCombobox: {
      type: String,
      default: null
    },
    /**
     * `aria-label` for the listbox element
     */
    ariaLabelListbox: {
      type: String,
      default: t("Options")
    },
    /**
     * Allows to customize the `aria-label` for the deselect-option button
     * The default is "Deselect " + optionLabel
     *
     * @type {(optionLabel: string) => string}
     */
    ariaLabelDeselectOption: {
      type: Function,
      default: (optionLabel) => t("Deselect {option}", { option: optionLabel })
    },
    /**
     * Append the dropdown element to the end of the body
     * and size/position it dynamically.
     *
     * @see https://vue-select.org/api/props.html#appendtobody
     */
    appendToBody: {
      type: Boolean,
      default: true
    },
    /**
     * When `appendToBody` is true, this function is responsible for
     * positioning the drop down list.
     *
     * If a function is returned from `calculatePosition`, it will
     * be called when the drop down list is removed from the DOM.
     * This allows for any garbage collection you may need to do.
     *
     * @see https://vue-select.org/api/props.html#calculateposition
     */
    calculatePosition: {
      type: Function,
      default: null
    },
    /**
     * Keep the dropdown open after selecting an option.
     *
     * @default false
     * @since 8.25.0
     */
    keepOpen: {
      type: Boolean,
      default: false
    },
    /**
     * Replace default vue-select components
     *
     * @see https://vue-select.org/api/props.html#components
     */
    components: {
      type: Object,
      default: () => ({
        Deselect: {
          render: () => h(IconClose, {
            size: 20,
            fillColor: "var(--vs-controls-color)",
            style: [
              { cursor: "pointer" }
            ]
          })
        }
      })
    },
    /**
     * Sets the maximum number of options to display in the dropdown list
     */
    limit: {
      type: Number,
      default: null
    },
    /**
     * Disable the component
     *
     * @see https://vue-select.org/api/props.html#disabled
     */
    disabled: {
      type: Boolean,
      default: false
    },
    /**
     * Determines whether the dropdown should be open.
     * Receives the component instance as the only argument.
     *
     * @see https://vue-select.org/api/props.html#dropdownshouldopen
     */
    dropdownShouldOpen: {
      type: Function,
      default: ({ noDrop, open }) => {
        return noDrop ? false : open;
      }
    },
    /**
     * Callback to determine if the provided option should
     * match the current search text. Used to determine
     * if the option should be displayed.
     *
     * Defaults to the internal vue-select function documented at the link
     * below
     *
     * @see https://vue-select.org/api/props.html#filterby
     */
    filterBy: {
      type: Function,
      default: null
    },
    /**
     * Class for the `input`
     *
     * Necessary for use in NcActionInput
     */
    inputClass: {
      type: [String, Object],
      default: null
    },
    /**
     * Input element id
     */
    inputId: {
      type: String,
      default: () => createElementId()
    },
    /**
     * Visible label for the input element
     */
    inputLabel: {
      type: String,
      default: null
    },
    /**
     * Pass true if you are using an external label
     */
    labelOutside: {
      type: Boolean,
      default: false
    },
    /**
     * Display a visible border around dropdown options
     * which have keyboard focus
     */
    keyboardFocusBorder: {
      type: Boolean,
      default: true
    },
    /**
     * Key of the displayed label for object options
     *
     * Defaults to the internal vue-select string documented at the link
     * below
     *
     * @see https://vue-select.org/api/props.html#label
     */
    label: {
      type: String,
      default: null
    },
    /**
     * Show the loading icon
     *
     * @see https://vue-select.org/api/props.html#loading
     */
    loading: {
      type: Boolean,
      default: false
    },
    /**
     * Allow selection of multiple options
     *
     * @see https://vue-select.org/api/props.html#multiple
     */
    multiple: {
      type: Boolean,
      default: false
    },
    /**
     * Disable automatic wrapping when selected options overflow the width
     */
    noWrap: {
      type: Boolean,
      default: false
    },
    /**
     * Array of options
     *
     * @type {Array<string | number | Record<string | number, any>>}
     *
     * @see https://vue-select.org/api/props.html#options
     */
    options: {
      type: Array,
      default: () => []
    },
    /**
     * Placeholder text
     *
     * @see https://vue-select.org/api/props.html#placeholder
     */
    placeholder: {
      type: String,
      default: ""
    },
    /**
     * Customized component's response to keydown events while the search input has focus
     *
     * @see https://vue-select.org/guide/keydown.html#mapkeydown
     */
    mapKeydown: {
      type: Function,
      /**
       * Patched Vue-Select keydown events handlers map to stop Escape propagation in open select
       *
       * @param {Record<number, Function>} map - Mapped keyCode to handlers { <keyCode>:<callback> }
       * @param {import('@nextcloud/vue-select').VueSelect} vm - VueSelect instance
       * @return {Record<number, Function>} patched keydown event handlers
       */
      default(map, vm) {
        return {
          ...map,
          /**
           * Patched Escape handler to stop propagation from open select
           *
           * @param {KeyboardEvent} event - default keydown event handler
           */
          27: (event) => {
            if (vm.open) {
              event.stopPropagation();
            }
            map[27](event);
          }
        };
      }
    },
    /**
     * A unique identifier used to generate IDs and DOM attributes. Must be unique for every instance of the component.
     *
     * @see https://vue-select.org/api/props.html#uid
     */
    uid: {
      type: String,
      default: () => createElementId()
    },
    /**
     * When `appendToBody` is true, this sets the placement of the dropdown
     *
     * @type {'bottom' | 'top'}
     */
    placement: {
      type: String,
      default: "bottom"
    },
    /**
     * If false, the focused dropdown option will not be reset when filtered
     * options change
     */
    resetFocusOnOptionsChange: {
      type: Boolean,
      default: true
    },
    /**
     * Currently selected value
     *
     * The `v-model` directive may be used for two-way data binding
     *
     * @type {string | number | Record<string | number, any> | Array<any>}
     *
     * @see https://vue-select.org/api/props.html#value
     */
    modelValue: {
      type: [String, Number, Object, Array],
      default: null
    },
    /**
     * Enable if a value is required for native form validation
     */
    required: {
      type: Boolean,
      default: false
    },
    /**
     * Any available prop
     *
     * @see https://vue-select.org/api/props.html
     */
    // Not an actual prop but needed to show in vue-styleguidist docs
    // eslint-disable-next-line
    " ": {}
  },
  emits: [
    /**
     * All events from https://vue-select.org/api/events.html
     */
    // Not an actual event but needed to show in vue-styleguidist docs
    " ",
    "update:modelValue"
  ],
  setup() {
    const clickableArea = Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-clickable-area"));
    const gridBaseLine = Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-grid-baseline"));
    const avatarSize = clickableArea - 2 * gridBaseLine;
    return {
      avatarSize,
      isLegacy
    };
  },
  data() {
    return {
      search: ""
    };
  },
  computed: {
    inputRequired() {
      if (!this.required) {
        return null;
      }
      return this.modelValue === null || Array.isArray(this.modelValue) && this.modelValue.length === 0;
    },
    localCalculatePosition() {
      if (this.calculatePosition !== null) {
        return this.calculatePosition;
      }
      return (dropdownMenu, component, { width }) => {
        dropdownMenu.style.width = width;
        const addClass = {
          name: "addClass",
          fn() {
            dropdownMenu.classList.add("vs__dropdown-menu--floating");
            return {};
          }
        };
        const togglePlacementClass = {
          name: "togglePlacementClass",
          fn({ placement }) {
            component.$el.classList.toggle(
              "select--drop-up",
              placement === "top"
            );
            dropdownMenu.classList.toggle(
              "vs__dropdown-menu--floating-placement-top",
              placement === "top"
            );
            return {};
          }
        };
        const updatePosition = () => {
          computePosition(component.$refs.toggle, dropdownMenu, {
            placement: this.placement,
            middleware: [
              offset(-1),
              addClass,
              togglePlacementClass,
              // Match popperjs default collision prevention behavior by appending the following middleware in order
              flip(),
              shift({ limiter: limitShift() })
            ]
          }).then(({ x: x2, y }) => {
            Object.assign(dropdownMenu.style, {
              left: `${x2}px`,
              top: `${y}px`,
              width: `${component.$refs.toggle.getBoundingClientRect().width}px`
            });
          });
        };
        const cleanup = autoUpdate(
          component.$refs.toggle,
          dropdownMenu,
          updatePosition
        );
        return cleanup;
      };
    },
    localFilterBy() {
      return this.filterBy ?? Ce.props.filterBy.default;
    },
    localLabel() {
      return this.label ?? Ce.props.label.default;
    },
    propsToForward() {
      const vueSelectKeys = [
        ...Object.keys(Ce.props),
        ...Ce.mixins.flatMap((mixin) => Object.keys(mixin.props ?? {}))
      ];
      const initialPropsToForward = Object.fromEntries(Object.entries(this.$props).filter(([key, _value]) => vueSelectKeys.includes(key)));
      const propsToForward = {
        ...initialPropsToForward,
        // Custom overrides of vue-select props
        calculatePosition: this.localCalculatePosition,
        closeOnSelect: !this.keepOpen,
        filterBy: this.localFilterBy,
        label: this.localLabel
      };
      return propsToForward;
    }
  },
  mounted() {
    if (!this.labelOutside && !this.inputLabel && !this.ariaLabelCombobox) {
      warn("[NcSelect] An `inputLabel` or `ariaLabelCombobox` should be set. If an external label is used, `labelOutside` should be set to `true`.");
    }
    if (this.inputLabel && this.ariaLabelCombobox) {
      warn("[NcSelect] Only one of `inputLabel` or `ariaLabelCombobox` should to be set.");
    }
  },
  methods: {
    t
  }
};
const _hoisted_1 = ["for"];
const _hoisted_2 = ["required"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ChevronDown = resolveComponent("ChevronDown");
  const _component_NcEllipsisedOption = resolveComponent("NcEllipsisedOption");
  const _component_NcLoadingIcon = resolveComponent("NcLoadingIcon");
  const _component_VueSelect = resolveComponent("VueSelect");
  return openBlock(), createBlock(_component_VueSelect, mergeProps({
    class: ["select", {
      "select--legacy": $setup.isLegacy,
      "select--no-wrap": $props.noWrap
    }]
  }, $options.propsToForward, {
    onSearch: _cache[0] || (_cache[0] = ($event) => $data.search = $event),
    "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => _ctx.$emit("update:modelValue", $event))
  }), createSlots({
    search: withCtx(({ attributes, events }) => [
      createBaseVNode("input", mergeProps({
        class: ["vs__search", [$props.inputClass]]
      }, attributes, {
        required: $options.inputRequired,
        dir: "auto"
      }, toHandlers(events, true)), null, 16, _hoisted_2)
    ]),
    "open-indicator": withCtx(({ attributes }) => [
      createVNode(_component_ChevronDown, mergeProps(attributes, {
        fillColor: "var(--vs-controls-color)",
        style: {
          cursor: !$props.disabled ? "pointer" : null
        },
        size: 26
      }), null, 16, ["style"])
    ]),
    option: withCtx((option) => [
      renderSlot(_ctx.$slots, "option", normalizeProps(guardReactiveProps(option)), () => [
        createVNode(_component_NcEllipsisedOption, {
          name: String(option[$options.localLabel]),
          search: $data.search
        }, null, 8, ["name", "search"])
      ])
    ]),
    "selected-option": withCtx((selectedOption) => [
      renderSlot(_ctx.$slots, "selected-option", normalizeProps(guardReactiveProps(selectedOption)), () => [
        createVNode(_component_NcEllipsisedOption, {
          name: String(selectedOption[$options.localLabel]),
          search: $data.search
        }, null, 8, ["name", "search"])
      ])
    ]),
    spinner: withCtx((spinner) => [
      spinner.loading ? (openBlock(), createBlock(_component_NcLoadingIcon, { key: 0 })) : createCommentVNode("", true)
    ]),
    "no-options": withCtx(() => [
      createTextVNode(toDisplayString($options.t("No results")), 1)
    ]),
    _: 2
  }, [
    !$props.labelOutside && $props.inputLabel ? {
      name: "header",
      fn: withCtx(() => [
        createBaseVNode("label", {
          for: $props.inputId,
          class: "select__label"
        }, toDisplayString($props.inputLabel), 9, _hoisted_1)
      ]),
      key: "0"
    } : void 0,
    renderList(_ctx.$slots, (_, name) => {
      return {
        name,
        fn: withCtx((data) => [
          renderSlot(_ctx.$slots, name, normalizeProps(guardReactiveProps(data)))
        ])
      };
    })
  ]), 1040, ["class"]);
}
const NcSelect = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  IconClose as I,
  NcSelect as N,
  _sfc_main$2 as _,
  NcEllipsisedOption as a
};
//# sourceMappingURL=NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs.map
