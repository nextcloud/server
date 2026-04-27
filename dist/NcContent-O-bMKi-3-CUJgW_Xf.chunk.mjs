const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { l as getBuilder, f as emit } from "./index-rAufP352.chunk.mjs";
import { l as loadState, g as getCapabilities } from "./index-o76qk6sn.chunk.mjs";
import { b as useSwipe, i as isRtl, c as useIsMobile } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { s as useSlots, n as computed, z as watch, A as onMounted, R as onBeforeUnmount, a1 as provide, o as openBlock, c as createBlock, K as resolveDynamicComponent, U as inject, a2 as getCurrentInstance, f as createElementBlock, i as renderSlot, N as normalizeStyle, u as unref, y as ref, a3 as h, P as nextTick, r as resolveComponent, t as toDisplayString, h as createCommentVNode, F as Fragment, v as normalizeClass, M as withModifiers, E as withDirectives, G as vShow, g as createBaseVNode, x as createVNode, w as withCtx, b as defineComponent, Q as onBeforeMount, j as createTextVNode, a4 as Teleport } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { l as logger, m as mdiArrowRight, N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { r as register, j as t27, _ as _export_sfc, N as NcIconSvgWrapper, b as t, k as t30 } from "./Web-BOM4en5n.chunk.mjs";
const Pe = {
  __name: "splitpanes",
  props: {
    horizontal: { type: Boolean, default: false },
    pushOtherPanes: { type: Boolean, default: true },
    maximizePanes: { type: Boolean, default: true },
    // Maximize pane on splitter double click/tap.
    rtl: { type: Boolean, default: false },
    // Right to left direction.
    firstSplitter: { type: Boolean, default: false }
  },
  emits: [
    "ready",
    "resize",
    "resized",
    "pane-click",
    "pane-maximize",
    "pane-add",
    "pane-remove",
    "splitter-click",
    "splitter-dblclick"
  ],
  setup(D, { emit: h$1 }) {
    const y = h$1, u = D, E = useSlots(), l = ref([]), M = computed(() => l.value.reduce((e, n) => (e[~~n.id] = n) && e, {})), m = computed(() => l.value.length), x = ref(null), S = ref(false), c = ref({
      mouseDown: false,
      dragging: false,
      activeSplitter: null,
      cursorOffset: 0
      // Cursor offset within the splitter.
    }), f = ref({
      // Used to detect double click on touch devices.
      splitter: null,
      timeoutId: null
    }), _ = computed(() => ({
      [`splitpanes splitpanes--${u.horizontal ? "horizontal" : "vertical"}`]: true,
      "splitpanes--dragging": c.value.dragging
    })), R = () => {
      document.addEventListener("mousemove", r, { passive: false }), document.addEventListener("mouseup", P), "ontouchstart" in window && (document.addEventListener("touchmove", r, { passive: false }), document.addEventListener("touchend", P));
    }, O = () => {
      document.removeEventListener("mousemove", r, { passive: false }), document.removeEventListener("mouseup", P), "ontouchstart" in window && (document.removeEventListener("touchmove", r, { passive: false }), document.removeEventListener("touchend", P));
    }, b = (e, n) => {
      const t2 = e.target.closest(".splitpanes__splitter");
      if (t2) {
        const { left: i, top: a } = t2.getBoundingClientRect(), { clientX: s, clientY: o } = "ontouchstart" in window && e.touches ? e.touches[0] : e;
        c.value.cursorOffset = u.horizontal ? o - a : s - i;
      }
      R(), c.value.mouseDown = true, c.value.activeSplitter = n;
    }, r = (e) => {
      c.value.mouseDown && (e.preventDefault(), c.value.dragging = true, requestAnimationFrame(() => {
        K(I(e)), d("resize", { event: e }, true);
      }));
    }, P = (e) => {
      c.value.dragging && (window.getSelection().removeAllRanges(), d("resized", { event: e }, true)), c.value.mouseDown = false, c.value.activeSplitter = null, setTimeout(() => {
        c.value.dragging = false, O();
      }, 100);
    }, A = (e, n) => {
      "ontouchstart" in window && (e.preventDefault(), f.value.splitter === n ? (clearTimeout(f.value.timeoutId), f.value.timeoutId = null, U(e, n), f.value.splitter = null) : (f.value.splitter = n, f.value.timeoutId = setTimeout(() => f.value.splitter = null, 500))), c.value.dragging || d("splitter-click", { event: e, index: n }, true);
    }, U = (e, n) => {
      if (d("splitter-dblclick", { event: e, index: n }, true), u.maximizePanes) {
        let t2 = 0;
        l.value = l.value.map((i, a) => (i.size = a === n ? i.max : i.min, a !== n && (t2 += i.min), i)), l.value[n].size -= t2, d("pane-maximize", { event: e, index: n, pane: l.value[n] }), d("resized", { event: e, index: n }, true);
      }
    }, W = (e, n) => {
      d("pane-click", {
        event: e,
        index: M.value[n].index,
        pane: M.value[n]
      });
    }, I = (e) => {
      const n = x.value.getBoundingClientRect(), { clientX: t2, clientY: i } = "ontouchstart" in window && e.touches ? e.touches[0] : e;
      return {
        x: t2 - (u.horizontal ? 0 : c.value.cursorOffset) - n.left,
        y: i - (u.horizontal ? c.value.cursorOffset : 0) - n.top
      };
    }, J = (e) => {
      e = e[u.horizontal ? "y" : "x"];
      const n = x.value[u.horizontal ? "clientHeight" : "clientWidth"];
      return u.rtl && !u.horizontal && (e = n - e), e * 100 / n;
    }, K = (e) => {
      const n = c.value.activeSplitter;
      let t2 = {
        prevPanesSize: $(n),
        nextPanesSize: N(n),
        prevReachedMinPanes: 0,
        nextReachedMinPanes: 0
      };
      const i = 0 + (u.pushOtherPanes ? 0 : t2.prevPanesSize), a = 100 - (u.pushOtherPanes ? 0 : t2.nextPanesSize), s = Math.max(Math.min(J(e), a), i);
      let o = [n, n + 1], v = l.value[o[0]] || null, p = l.value[o[1]] || null;
      const H = v.max < 100 && s >= v.max + t2.prevPanesSize, ue = p.max < 100 && s <= 100 - (p.max + N(n + 1));
      if (H || ue) {
        H ? (v.size = v.max, p.size = Math.max(100 - v.max - t2.prevPanesSize - t2.nextPanesSize, 0)) : (v.size = Math.max(100 - p.max - t2.prevPanesSize - N(n + 1), 0), p.size = p.max);
        return;
      }
      if (u.pushOtherPanes) {
        const j = Q(t2, s);
        if (!j) return;
        ({ sums: t2, panesToResize: o } = j), v = l.value[o[0]] || null, p = l.value[o[1]] || null;
      }
      v !== null && (v.size = Math.min(Math.max(s - t2.prevPanesSize - t2.prevReachedMinPanes, v.min), v.max)), p !== null && (p.size = Math.min(Math.max(100 - s - t2.nextPanesSize - t2.nextReachedMinPanes, p.min), p.max));
    }, Q = (e, n) => {
      const t2 = c.value.activeSplitter, i = [t2, t2 + 1];
      return n < e.prevPanesSize + l.value[i[0]].min && (i[0] = V(t2).index, e.prevReachedMinPanes = 0, i[0] < t2 && l.value.forEach((a, s) => {
        s > i[0] && s <= t2 && (a.size = a.min, e.prevReachedMinPanes += a.min);
      }), e.prevPanesSize = $(i[0]), i[0] === void 0) ? (e.prevReachedMinPanes = 0, l.value[0].size = l.value[0].min, l.value.forEach((a, s) => {
        s > 0 && s <= t2 && (a.size = a.min, e.prevReachedMinPanes += a.min);
      }), l.value[i[1]].size = 100 - e.prevReachedMinPanes - l.value[0].min - e.prevPanesSize - e.nextPanesSize, null) : n > 100 - e.nextPanesSize - l.value[i[1]].min && (i[1] = Z(t2).index, e.nextReachedMinPanes = 0, i[1] > t2 + 1 && l.value.forEach((a, s) => {
        s > t2 && s < i[1] && (a.size = a.min, e.nextReachedMinPanes += a.min);
      }), e.nextPanesSize = N(i[1] - 1), i[1] === void 0) ? (e.nextReachedMinPanes = 0, l.value.forEach((a, s) => {
        s < m.value - 1 && s >= t2 + 1 && (a.size = a.min, e.nextReachedMinPanes += a.min);
      }), l.value[i[0]].size = 100 - e.prevPanesSize - N(i[0] - 1), null) : { sums: e, panesToResize: i };
    }, $ = (e) => l.value.reduce((n, t2, i) => n + (i < e ? t2.size : 0), 0), N = (e) => l.value.reduce((n, t2, i) => n + (i > e + 1 ? t2.size : 0), 0), V = (e) => [...l.value].reverse().find((t2) => t2.index < e && t2.size > t2.min) || {}, Z = (e) => l.value.find((t2) => t2.index > e + 1 && t2.size > t2.min) || {}, ee = () => {
      var n;
      const e = Array.from(((n = x.value) == null ? void 0 : n.children) || []);
      for (const t2 of e) {
        const i = t2.classList.contains("splitpanes__pane"), a = t2.classList.contains("splitpanes__splitter");
        !i && !a && (t2.remove(), console.warn("Splitpanes: Only <pane> elements are allowed at the root of <splitpanes>. One of your DOM nodes was removed."));
      }
    }, F = (e, n, t2 = false) => {
      const i = e - 1, a = document.createElement("div");
      a.classList.add("splitpanes__splitter"), t2 || (a.onmousedown = (s) => b(s, i), typeof window < "u" && "ontouchstart" in window && (a.ontouchstart = (s) => b(s, i)), a.onclick = (s) => A(s, i + 1)), a.ondblclick = (s) => U(s, i + 1), n.parentNode.insertBefore(a, n);
    }, ne = (e) => {
      e.onmousedown = void 0, e.onclick = void 0, e.ondblclick = void 0, e.remove();
    }, C = () => {
      var t2;
      const e = Array.from(((t2 = x.value) == null ? void 0 : t2.children) || []);
      for (const i of e)
        i.className.includes("splitpanes__splitter") && ne(i);
      let n = 0;
      for (const i of e)
        i.className.includes("splitpanes__pane") && (!n && u.firstSplitter ? F(n, i, true) : n && F(n, i), n++);
    }, ie = ({ uid: e, ...n }) => {
      const t2 = M.value[e];
      for (const [i, a] of Object.entries(n)) t2[i] = a;
    }, te = (e) => {
      var t2;
      let n = -1;
      Array.from(((t2 = x.value) == null ? void 0 : t2.children) || []).some((i) => (i.className.includes("splitpanes__pane") && n++, i.isSameNode(e.el))), l.value.splice(n, 0, { ...e, index: n }), l.value.forEach((i, a) => i.index = a), S.value && nextTick(() => {
        C(), L({ addedPane: l.value[n] }), d("pane-add", { pane: l.value[n] });
      });
    }, ae = (e) => {
      const n = l.value.findIndex((i) => i.id === e);
      l.value[n].el = null;
      const t2 = l.value.splice(n, 1)[0];
      l.value.forEach((i, a) => i.index = a), nextTick(() => {
        C(), d("pane-remove", { pane: t2 }), L({ removedPane: { ...t2 } });
      });
    }, L = (e = {}) => {
      !e.addedPane && !e.removedPane ? le() : l.value.some((n) => n.givenSize !== null || n.min || n.max < 100) ? oe(e) : se(), S.value && d("resized");
    }, se = () => {
      const e = 100 / m.value;
      let n = 0;
      const t2 = [], i = [];
      for (const a of l.value)
        a.size = Math.max(Math.min(e, a.max), a.min), n -= a.size, a.size >= a.max && t2.push(a.id), a.size <= a.min && i.push(a.id);
      n > 0.1 && q(n, t2, i);
    }, le = () => {
      let e = 100;
      const n = [], t2 = [];
      let i = 0;
      for (const s of l.value)
        e -= s.size, s.givenSize !== null && i++, s.size >= s.max && n.push(s.id), s.size <= s.min && t2.push(s.id);
      let a = 100;
      if (e > 0.1) {
        for (const s of l.value)
          s.givenSize === null && (s.size = Math.max(Math.min(e / (m.value - i), s.max), s.min)), a -= s.size;
        a > 0.1 && q(a, n, t2);
      }
    }, oe = ({ addedPane: e, removedPane: n } = {}) => {
      let t2 = 100 / m.value, i = 0;
      const a = [], s = [];
      ((e == null ? void 0 : e.givenSize) ?? null) !== null && (t2 = (100 - e.givenSize) / (m.value - 1));
      for (const o of l.value)
        i -= o.size, o.size >= o.max && a.push(o.id), o.size <= o.min && s.push(o.id);
      if (!(Math.abs(i) < 0.1)) {
        for (const o of l.value)
          (e == null ? void 0 : e.givenSize) !== null && (e == null ? void 0 : e.id) === o.id || (o.size = Math.max(Math.min(t2, o.max), o.min)), i -= o.size, o.size >= o.max && a.push(o.id), o.size <= o.min && s.push(o.id);
        i > 0.1 && q(i, a, s);
      }
    }, q = (e, n, t2) => {
      let i;
      e > 0 ? i = e / (m.value - n.length) : i = e / (m.value - t2.length), l.value.forEach((a, s) => {
        if (e > 0 && !n.includes(a.id)) {
          const o = Math.max(Math.min(a.size + i, a.max), a.min), v = o - a.size;
          e -= v, a.size = o;
        } else if (!t2.includes(a.id)) {
          const o = Math.max(Math.min(a.size + i, a.max), a.min), v = o - a.size;
          e -= v, a.size = o;
        }
      }), Math.abs(e) > 0.1 && nextTick(() => {
        S.value && console.warn("Splitpanes: Could not resize panes correctly due to their constraints.");
      });
    }, d = (e, n = void 0, t2 = false) => {
      const i = (n == null ? void 0 : n.index) ?? c.value.activeSplitter ?? null;
      y(e, {
        ...n,
        ...i !== null && { index: i },
        ...t2 && i !== null && {
          prevPane: l.value[i - (u.firstSplitter ? 1 : 0)],
          nextPane: l.value[i + (u.firstSplitter ? 0 : 1)]
        },
        panes: l.value.map((a) => ({ min: a.min, max: a.max, size: a.size }))
      });
    };
    watch(() => u.firstSplitter, () => C()), onMounted(() => {
      ee(), C(), L(), d("ready"), S.value = true;
    }), onBeforeUnmount(() => S.value = false);
    const re = () => {
      var e;
      return h(
        "div",
        { ref: x, class: _.value },
        (e = E.default) == null ? void 0 : e.call(E)
      );
    };
    return provide("panes", l), provide("indexedPanes", M), provide("horizontal", computed(() => u.horizontal)), provide("requestUpdate", ie), provide("onPaneAdd", te), provide("onPaneRemove", ae), provide("onPaneClick", W), (e, n) => (openBlock(), createBlock(resolveDynamicComponent(re)));
  }
}, ge = {
  __name: "pane",
  props: {
    size: { type: [Number, String] },
    minSize: { type: [Number, String], default: 0 },
    maxSize: { type: [Number, String], default: 100 }
  },
  setup(D) {
    var b;
    const h2 = D, y = inject("requestUpdate"), u = inject("onPaneAdd"), E = inject("horizontal"), l = inject("onPaneRemove"), M = inject("onPaneClick"), m = (b = getCurrentInstance()) == null ? void 0 : b.uid, x = inject("indexedPanes"), S = computed(() => x.value[m]), c = ref(null), f = computed(() => {
      const r = isNaN(h2.size) || h2.size === void 0 ? 0 : parseFloat(h2.size);
      return Math.max(Math.min(r, R.value), _.value);
    }), _ = computed(() => {
      const r = parseFloat(h2.minSize);
      return isNaN(r) ? 0 : r;
    }), R = computed(() => {
      const r = parseFloat(h2.maxSize);
      return isNaN(r) ? 100 : r;
    }), O = computed(() => {
      var r;
      return `${E.value ? "height" : "width"}: ${(r = S.value) == null ? void 0 : r.size}%`;
    });
    return watch(() => f.value, (r) => y({ uid: m, size: r })), watch(() => _.value, (r) => y({ uid: m, min: r })), watch(() => R.value, (r) => y({ uid: m, max: r })), onMounted(() => {
      u({
        id: m,
        el: c.value,
        min: _.value,
        max: R.value,
        // The given size (useful to know the user intention).
        givenSize: h2.size === void 0 ? null : f.value,
        size: f.value
        // The computed current size at any time.
      });
    }), onBeforeUnmount(() => l(m)), (r, P) => (openBlock(), createElementBlock("div", {
      ref_key: "paneEl",
      ref: c,
      class: "splitpanes__pane",
      onClick: P[0] || (P[0] = (A) => unref(M)(A, r._.uid)),
      style: normalizeStyle(O.value)
    }, [
      renderSlot(r.$slots, "default")
    ], 4));
  }
};
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
function once(func) {
  let wasCalled = false;
  let result;
  return (...args) => {
    if (!wasCalled) {
      wasCalled = true;
      result = func(...args);
    }
    return result;
  };
}
let realAppName = "missing-app-name";
try {
  realAppName = appName;
} catch {
  logger.error("The `@nextcloud/vue` library was used without setting / replacing the `appName`.");
}
const APP_NAME = realAppName;
let realAppVersion = "";
try {
  realAppVersion = appVersion;
} catch {
  logger.error("The `@nextcloud/vue` library was used without setting / replacing the `appVersion`.");
}
function useAppName() {
  return inject("appName", APP_NAME);
}
const useLocalizedAppName = once(() => {
  const apps = loadState("core", "apps", []);
  const realAppName2 = useAppName();
  return apps.find(({ id }) => id === realAppName2)?.name ?? realAppName2;
});
register(t27);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "NcAppContentDetailsToggle",
  setup(__props) {
    const isMobile = useIsMobile();
    watch(isMobile, toggleAppNavigationButton);
    onMounted(() => {
      toggleAppNavigationButton(isMobile.value);
    });
    onBeforeUnmount(() => {
      if (isMobile.value) {
        toggleAppNavigationButton(false);
      }
    });
    function toggleAppNavigationButton(hide = true) {
      const appNavigationToggle = document.querySelector(".app-navigation .app-navigation-toggle");
      if (appNavigationToggle) {
        appNavigationToggle.style.display = hide ? "none" : "";
        if (hide === true) {
          emit("toggle-navigation", { open: false });
        }
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcButton), {
        "aria-label": unref(t)("Go back to the list"),
        class: normalizeClass(["app-details-toggle", { "app-details-toggle--mobile": unref(isMobile) }]),
        title: unref(t)("Go back to the list"),
        variant: "tertiary"
      }, {
        icon: withCtx(() => [
          createVNode(unref(NcIconSvgWrapper), {
            directional: "",
            path: unref(mdiArrowRight)
          }, null, 8, ["path"])
        ]),
        _: 1
      }, 8, ["aria-label", "class", "title"]);
    };
  }
});
const NcAppContentDetailsToggle = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-a28923a1"]]);
const browserStorage = getBuilder("nextcloud").persist().build();
const instanceName = getCapabilities().theming?.name ?? "Nextcloud";
const _sfc_main$2 = {
  name: "NcAppContent",
  components: {
    NcAppContentDetailsToggle,
    Pane: ge,
    Splitpanes: Pe
  },
  props: {
    /**
     * Allows to disable the control by swipe of the app navigation open state.
     */
    disableSwipe: {
      type: Boolean,
      default: false
    },
    /**
     * Allows you to set the default width of the resizable list in % on vertical-split
     * or respectively the default height on horizontal-split.
     *
     * Must be between `listMinWidth` and `listMaxWidth`.
     */
    listSize: {
      type: Number,
      default: 20
    },
    /**
     * Allows you to set the minimum width of the list column in % on vertical-split
     * or respectively the minimum height on horizontal-split.
     */
    listMinWidth: {
      type: Number,
      default: 15
    },
    /**
     * Allows you to set the maximum width of the list column in % on vertical-split
     * or respectively the maximum height on horizontal-split.
     */
    listMaxWidth: {
      type: Number,
      default: 40
    },
    /**
     * Specify the config key for the pane config sizes
     * Default is the global var appName if you use the webpack-vue-config
     */
    paneConfigKey: {
      type: String,
      default: ""
    },
    /**
     * When in mobile view, only the list or the details are shown.
     *
     * If you provide a list, you need to provide a variable
     * that will be set to true by the user when an element of
     * the list gets selected. The details will then show a back
     * arrow to return to the list that will update this prop to false.
     */
    showDetails: {
      type: Boolean,
      default: true
    },
    /**
     * Content layout used when there is a list together with content:
     * - `vertical-split` - a 2-column layout with list and default content separated vertically
     * - `no-split` - a single column layout; List is shown when `showDetails` is `false`, otherwise the default slot content is shown with a back button to return to the list.
     * - 'horizontal-split' - a 2-column layout with list and default content separated horizontally
     * On mobile screen `no-split` layout is forced.
     */
    layout: {
      type: String,
      default: "vertical-split",
      validator(value) {
        return ["no-split", "vertical-split", "horizontal-split"].includes(value);
      }
    },
    /**
     * Specify the `<h1>` page heading
     */
    pageHeading: {
      type: String,
      default: null
    },
    /**
     * Allow setting the page's `<title>`
     *
     * If a page heading is set it defaults to `{pageHeading} - {appName} - {instanceName}` e.g. `Favorites - Files - MyPersonalCloud`.
     * When the page heading and the app name is the same only one is used, e.g. `Files - Files - MyPersonalCloud` is shown as `Files - MyPersonalCloud`.
     * When setting the prop then the following format will be used: `{pageTitle} - {instanceName}`
     */
    pageTitle: {
      type: String,
      default: null
    }
  },
  emits: [
    "update:showDetails",
    "resizeList"
  ],
  setup() {
    return {
      appName: useAppName(),
      localizedAppName: useLocalizedAppName(),
      isMobile: useIsMobile(),
      isRtl
    };
  },
  data() {
    return {
      contentHeight: 0,
      swiping: {},
      listPaneSize: this.restorePaneConfig()
    };
  },
  computed: {
    paneConfigID() {
      if (this.paneConfigKey !== "") {
        return `pane-list-size-${this.paneConfigKey}`;
      }
      try {
        return `pane-list-size-${this.appName}`;
      } catch {
        logger.info("[NcAppContent]: falling back to global nextcloud pane config");
        return "pane-list-size-nextcloud";
      }
    },
    detailsPaneSize() {
      if (this.listPaneSize) {
        return 100 - this.listPaneSize;
      }
      return this.paneDefaults.details.size;
    },
    paneDefaults() {
      return {
        list: {
          size: this.listSize,
          min: this.listMinWidth,
          max: this.listMaxWidth
        },
        // set the inverse values of the details column
        // based on the provided (or default) values of the list column
        details: {
          size: 100 - this.listSize,
          min: 100 - this.listMaxWidth,
          max: 100 - this.listMinWidth
        }
      };
    },
    realPageTitle() {
      const entries = /* @__PURE__ */ new Set();
      if (this.pageTitle) {
        for (const part of this.pageTitle.split(" - ")) {
          entries.add(part);
        }
      } else if (this.pageHeading) {
        for (const part of this.pageHeading.split(" - ")) {
          entries.add(part);
        }
        if (entries.size > 0) {
          entries.add(this.localizedAppName);
        }
      } else {
        return null;
      }
      entries.add(instanceName);
      return [...entries.values()].join(" - ");
    }
  },
  watch: {
    realPageTitle: {
      immediate: true,
      handler() {
        if (this.realPageTitle !== null) {
          document.title = this.realPageTitle;
        }
      }
    },
    paneConfigKey: {
      immediate: true,
      handler() {
        this.restorePaneConfig();
      }
    }
  },
  mounted() {
    if (!this.disableSwipe) {
      this.swiping = useSwipe(this.$el, {
        onSwipeEnd: this.handleSwipe
      });
    }
    this.restorePaneConfig();
  },
  methods: {
    /**
     * handle the swipe event
     *
     * @param {TouchEvent} e The touch event
     * @param {import('@vueuse/core').SwipeDirection} direction The swipe direction of the event
     */
    handleSwipe(e, direction) {
      const minSwipeX = 70;
      const touchZone = 300;
      if (Math.abs(this.swiping.lengthX) > minSwipeX) {
        if (this.swiping.coordsStart.x < touchZone / 2 && direction === "right") {
          emit("toggle-navigation", {
            open: true
          });
        } else if (this.swiping.coordsStart.x < touchZone * 1.5 && direction === "left") {
          emit("toggle-navigation", {
            open: false
          });
        }
      }
    },
    handlePaneResize(event) {
      const listPaneSize = parseInt(event.panes[0].size, 10);
      browserStorage.setItem(this.paneConfigID, JSON.stringify(listPaneSize));
      this.listPaneSize = listPaneSize;
      this.$emit("resizeList", { size: listPaneSize });
      logger.debug("[NcAppContent] pane config", { listPaneSize });
    },
    // browserStorage is not reactive, we need to update this manually
    restorePaneConfig() {
      const listPaneSize = parseInt(browserStorage.getItem(this.paneConfigID), 10);
      if (!isNaN(listPaneSize) && listPaneSize !== this.listPaneSize) {
        logger.debug("[NcAppContent] pane config", { listPaneSize });
        this.listPaneSize = listPaneSize;
        return listPaneSize;
      }
    },
    /**
     * The user clicked the back arrow from the details view
     */
    hideDetails() {
      this.$emit("update:showDetails", false);
    }
  }
};
const _hoisted_1$1 = {
  key: 0,
  class: "hidden-visually"
};
const _hoisted_2$1 = {
  key: 1,
  class: "app-content-wrapper"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcAppContentDetailsToggle = resolveComponent("NcAppContentDetailsToggle");
  const _component_Pane = resolveComponent("Pane");
  const _component_Splitpanes = resolveComponent("Splitpanes");
  return openBlock(), createElementBlock("main", {
    id: "app-content-vue",
    class: normalizeClass(["app-content no-snapper", { "app-content--has-list": !!_ctx.$slots.list }])
  }, [
    $props.pageHeading ? (openBlock(), createElementBlock("h1", _hoisted_1$1, toDisplayString($props.pageHeading), 1)) : createCommentVNode("", true),
    !!_ctx.$slots.list ? (openBlock(), createElementBlock(Fragment, { key: 1 }, [
      $setup.isMobile || $props.layout === "no-split" ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: normalizeClass(["app-content-wrapper app-content-wrapper--no-split", {
          "app-content-wrapper--show-details": $props.showDetails,
          "app-content-wrapper--show-list": !$props.showDetails,
          "app-content-wrapper--mobile": $setup.isMobile
        }])
      }, [
        $props.showDetails ? (openBlock(), createBlock(_component_NcAppContentDetailsToggle, {
          key: 0,
          onClick: withModifiers($options.hideDetails, ["stop", "prevent"])
        }, null, 8, ["onClick"])) : createCommentVNode("", true),
        withDirectives(createBaseVNode("div", null, [
          renderSlot(_ctx.$slots, "list", {}, void 0, true)
        ], 512), [
          [vShow, !$props.showDetails]
        ]),
        $props.showDetails ? renderSlot(_ctx.$slots, "default", { key: 1 }, void 0, true) : createCommentVNode("", true)
      ], 2)) : $props.layout === "vertical-split" || $props.layout === "horizontal-split" ? (openBlock(), createElementBlock("div", _hoisted_2$1, [
        createVNode(_component_Splitpanes, {
          horizontal: $props.layout === "horizontal-split",
          class: normalizeClass(["default-theme", {
            "splitpanes--horizontal": $props.layout === "horizontal-split",
            "splitpanes--vertical": $props.layout === "vertical-split"
          }]),
          rtl: $setup.isRtl,
          onResized: $options.handlePaneResize
        }, {
          default: withCtx(() => [
            createVNode(_component_Pane, {
              class: "splitpanes__pane-list",
              size: $data.listPaneSize || $options.paneDefaults.list.size,
              minSize: $options.paneDefaults.list.min,
              maxSize: $options.paneDefaults.list.max
            }, {
              default: withCtx(() => [
                renderSlot(_ctx.$slots, "list", {}, void 0, true)
              ]),
              _: 3
            }, 8, ["size", "minSize", "maxSize"]),
            createVNode(_component_Pane, {
              class: "splitpanes__pane-details",
              size: $options.detailsPaneSize,
              minSize: $options.paneDefaults.details.min,
              maxSize: $options.paneDefaults.details.max
            }, {
              default: withCtx(() => [
                renderSlot(_ctx.$slots, "default", {}, void 0, true)
              ]),
              _: 3
            }, 8, ["size", "minSize", "maxSize"])
          ]),
          _: 3
        }, 8, ["horizontal", "class", "rtl", "onResized"])
      ])) : createCommentVNode("", true)
    ], 64)) : createCommentVNode("", true),
    !_ctx.$slots.list ? renderSlot(_ctx.$slots, "default", { key: 2 }, void 0, true) : createCommentVNode("", true)
  ], 2);
}
const NcAppContent = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render], ["__scopeId", "data-v-563c4ac4"]]);
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const HAS_APP_NAVIGATION_KEY = /* @__PURE__ */ Symbol.for("NcContent:setHasAppNavigation");
const CONTENT_SELECTOR_KEY = /* @__PURE__ */ Symbol.for("NcContent:selector");
register(t30);
const contentSvg = '<!--\n  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors\n  - SPDX-License-Identifier: AGPL-3.0-or-later\n-->\n<svg width="395" height="314" viewBox="0 0 395 314" fill="none" xmlns="http://www.w3.org/2000/svg">\n<rect width="395" height="314" rx="11" fill="#439DCD"/>\n<rect x="13" y="51" width="366" height="248" rx="8" fill="white"/>\n<rect x="22" y="111" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="127" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="63" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="191" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="143" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="79" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="159" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="95" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="175" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<path d="M288 145C277.56 147.8 265.32 149 254 149C242.68 149 230.44 147.8 220 145L218 153C225.44 155 234 156.32 242 157V209H250V185H258V209H266V157C274 156.32 282.56 155 290 153L288 145ZM254 145C258.4 145 262 141.4 262 137C262 132.6 258.4 129 254 129C249.6 129 246 132.6 246 137C246 141.4 249.6 145 254 145Z" fill="#DEDEDE"/>\n<path d="M43.5358 13C38.6641 13 34.535 16.2415 33.2552 20.6333C32.143 18.3038 29.7327 16.6718 26.9564 16.6718C23.1385 16.6718 20 19.7521 20 23.4993C20 27.2465 23.1385 30.3282 26.9564 30.3282C29.7327 30.3282 32.1429 28.6952 33.2552 26.3653C34.535 30.7575 38.6641 34 43.5358 34C48.3715 34 52.4796 30.8064 53.7921 26.4637C54.9249 28.7407 57.3053 30.3282 60.0421 30.3282C63.8601 30.3282 67 27.2465 67 23.4993C67 19.7521 63.8601 16.6718 60.0421 16.6718C57.3053 16.6718 54.9249 18.2583 53.7921 20.5349C52.4796 16.1926 48.3715 13 43.5358 13ZM43.5358 17.0079C47.2134 17.0079 50.1512 19.8899 50.1512 23.4993C50.1512 27.1087 47.2134 29.9921 43.5358 29.9921C39.8583 29.9921 36.9218 27.1087 36.9218 23.4993C36.9218 19.8899 39.8583 17.0079 43.5358 17.0079ZM26.9564 20.6797C28.5677 20.6797 29.8307 21.9179 29.8307 23.4993C29.8307 25.0807 28.5677 26.3203 26.9564 26.3203C25.3452 26.3203 24.0836 25.0807 24.0836 23.4993C24.0836 21.9179 25.3452 20.6797 26.9564 20.6797ZM60.0421 20.6797C61.6534 20.6797 62.9164 21.9179 62.9164 23.4993C62.9164 25.0807 61.6534 26.3203 60.0421 26.3203C58.4309 26.3203 57.1693 25.0807 57.1693 23.4993C57.1693 21.9179 58.4309 20.6797 60.0421 20.6797Z" fill="white"/>\n<rect x="79" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="99" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="119" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="139" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="159" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="179" y="20" width="8" height="8" rx="4" fill="white"/>\n<path fill-rule="evenodd" clip-rule="evenodd" d="M12 0C5.37258 0 0 5.37259 0 12V302C0 308.627 5.37259 314 12 314H383C389.627 314 395 308.627 395 302V12C395 5.37258 389.627 0 383 0H12ZM140 44C132.268 44 126 50.268 126 58V292C126 299.732 132.268 306 140 306H372C379.732 306 386 299.732 386 292V58C386 50.268 379.732 44 372 44H140Z" fill="black" fill-opacity="0.35"/>\n</svg>\n';
const navigationSvg = '<!--\n  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors\n  - SPDX-License-Identifier: AGPL-3.0-or-later\n-->\n<svg width="395" height="314" viewBox="0 0 395 314" fill="none" xmlns="http://www.w3.org/2000/svg">\n<rect width="395" height="314" rx="11" fill="#439DCD"/>\n<rect x="13" y="51" width="366" height="248" rx="8" fill="white"/>\n<rect x="22" y="111" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="127" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="63" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="191" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="143" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="79" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="159" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="95" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="175" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<path d="M288 145C277.56 147.8 265.32 149 254 149C242.68 149 230.44 147.8 220 145L218 153C225.44 155 234 156.32 242 157V209H250V185H258V209H266V157C274 156.32 282.56 155 290 153L288 145ZM254 145C258.4 145 262 141.4 262 137C262 132.6 258.4 129 254 129C249.6 129 246 132.6 246 137C246 141.4 249.6 145 254 145Z" fill="#DEDEDE"/>\n<path d="M43.5358 13C38.6641 13 34.535 16.2415 33.2552 20.6333C32.143 18.3038 29.7327 16.6718 26.9564 16.6718C23.1385 16.6718 20 19.7521 20 23.4993C20 27.2465 23.1385 30.3282 26.9564 30.3282C29.7327 30.3282 32.1429 28.6952 33.2552 26.3653C34.535 30.7575 38.6641 34 43.5358 34C48.3715 34 52.4796 30.8064 53.7921 26.4637C54.9249 28.7407 57.3053 30.3282 60.0421 30.3282C63.8601 30.3282 67 27.2465 67 23.4993C67 19.7521 63.8601 16.6718 60.0421 16.6718C57.3053 16.6718 54.9249 18.2583 53.7921 20.5349C52.4796 16.1926 48.3715 13 43.5358 13ZM43.5358 17.0079C47.2134 17.0079 50.1512 19.8899 50.1512 23.4993C50.1512 27.1087 47.2134 29.9921 43.5358 29.9921C39.8583 29.9921 36.9218 27.1087 36.9218 23.4993C36.9218 19.8899 39.8583 17.0079 43.5358 17.0079ZM26.9564 20.6797C28.5677 20.6797 29.8307 21.9179 29.8307 23.4993C29.8307 25.0807 28.5677 26.3203 26.9564 26.3203C25.3452 26.3203 24.0836 25.0807 24.0836 23.4993C24.0836 21.9179 25.3452 20.6797 26.9564 20.6797ZM60.0421 20.6797C61.6534 20.6797 62.9164 21.9179 62.9164 23.4993C62.9164 25.0807 61.6534 26.3203 60.0421 26.3203C58.4309 26.3203 57.1693 25.0807 57.1693 23.4993C57.1693 21.9179 58.4309 20.6797 60.0421 20.6797Z" fill="white"/>\n<rect x="79" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="99" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="119" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="139" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="159" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="179" y="20" width="8" height="8" rx="4" fill="white"/>\n<path fill-rule="evenodd" clip-rule="evenodd" d="M12 0C5.37258 0 0 5.37259 0 12V302C0 308.627 5.37259 314 12 314H383C389.627 314 395 308.627 395 302V12C395 5.37258 389.627 0 383 0H12ZM112 44C119.732 44 126 50.268 126 58V292C126 299.732 119.732 306 112 306H20C12.268 306 6 299.732 6 292V58C6 50.268 12.268 44 20 44H112Z" fill="black" fill-opacity="0.35"/>\n</svg>\n';
const _hoisted_1 = { class: "vue-skip-actions__container" };
const _hoisted_2 = { class: "vue-skip-actions__headline" };
const _hoisted_3 = { class: "vue-skip-actions__buttons" };
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcContent",
  props: {
    appName: {}
  },
  setup(__props) {
    const props = __props;
    provide(HAS_APP_NAVIGATION_KEY, setAppNavigation);
    provide(CONTENT_SELECTOR_KEY, "#content-vue");
    provide("appName", computed(() => props.appName));
    const isMobile = useIsMobile();
    const hasAppNavigation = ref(false);
    const currentFocus = ref();
    const currentImage = computed(() => currentFocus.value === "navigation" ? navigationSvg : contentSvg);
    onBeforeMount(() => {
      const container = document.getElementById("skip-actions");
      if (container) {
        container.innerHTML = "";
        container.classList.add("vue-skip-actions");
      }
    });
    function openAppNavigation() {
      emit("toggle-navigation", { open: true });
      nextTick(() => {
        window.location.hash = "app-navigation-vue";
        document.getElementById("app-navigation-vue").focus();
      });
    }
    function setAppNavigation(value) {
      hasAppNavigation.value = value;
      if (!currentFocus.value) {
        currentFocus.value = "navigation";
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        id: "content-vue",
        class: normalizeClass(["content", `app-${_ctx.appName.toLowerCase()}`])
      }, [
        (openBlock(), createBlock(Teleport, { to: "#skip-actions" }, [
          createBaseVNode("div", _hoisted_1, [
            createBaseVNode("div", _hoisted_2, toDisplayString(unref(t)("Keyboard navigation help")), 1),
            createBaseVNode("div", _hoisted_3, [
              withDirectives(createVNode(NcButton, {
                href: "#app-navigation-vue",
                variant: "tertiary",
                onClick: withModifiers(openAppNavigation, ["prevent"]),
                onFocusin: _cache[0] || (_cache[0] = ($event) => currentFocus.value = "navigation"),
                onMouseover: _cache[1] || (_cache[1] = ($event) => currentFocus.value = "navigation")
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(unref(t)("Skip to app navigation")), 1)
                ]),
                _: 1
              }, 512), [
                [vShow, hasAppNavigation.value]
              ]),
              createVNode(NcButton, {
                href: "#app-content-vue",
                variant: "tertiary",
                onFocusin: _cache[2] || (_cache[2] = ($event) => currentFocus.value = "content"),
                onMouseover: _cache[3] || (_cache[3] = ($event) => currentFocus.value = "content")
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(unref(t)("Skip to main content")), 1)
                ]),
                _: 1
              })
            ]),
            withDirectives(createVNode(NcIconSvgWrapper, {
              class: "vue-skip-actions__image",
              svg: currentImage.value,
              size: "auto"
            }, null, 8, ["svg"]), [
              [vShow, !unref(isMobile)]
            ])
          ])
        ])),
        renderSlot(_ctx.$slots, "default", {}, void 0, true)
      ], 2);
    };
  }
});
const NcContent = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-d9b0d7e8"]]);
export {
  NcContent as N,
  NcAppContent as a
};
//# sourceMappingURL=NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs.map
