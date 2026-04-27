const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, O as reactive, U as inject, n as computed, a3 as h, a5 as watchEffect, u as unref, a2 as getCurrentInstance, o as openBlock, c as createBlock, w as withCtx, g as createBaseVNode, i as renderSlot, j as createTextVNode, t as toDisplayString, m as mergeProps, K as resolveDynamicComponent, f as createElementBlock, h as createCommentVNode } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc } from "./Web-BOM4en5n.chunk.mjs";
import { g as getLoggerBuilder, w as getBaseUrl, m as getRootUrl } from "./index-rAufP352.chunk.mjs";
import { _ as _export_sfc$1 } from "./index-o76qk6sn.chunk.mjs";
/*!
 * vue-router v5.0.2
 * (c) 2026 Eduardo San Martin Morote
 * @license MIT
 */
const isBrowser = typeof document !== "undefined";
const noop = () => {
};
const isArray = Array.isArray;
const routerKey = /* @__PURE__ */ Symbol("router");
const routeLocationKey = /* @__PURE__ */ Symbol("route location");
/*!
 * vue-router v5.0.2
 * (c) 2026 Eduardo San Martin Morote
 * @license MIT
 */
function warn$1(msg) {
  const args = Array.from(arguments).slice(1);
  console.warn.apply(console, ["[Vue Router warn]: " + msg].concat(args));
}
function isSameRouteRecord(a, b) {
  return (a.aliasOf || a) === (b.aliasOf || b);
}
function isSameRouteLocationParams(a, b) {
  if (Object.keys(a).length !== Object.keys(b).length) return false;
  for (var key in a) if (!isSameRouteLocationParamsValue(a[key], b[key])) return false;
  return true;
}
function isSameRouteLocationParamsValue(a, b) {
  return isArray(a) ? isEquivalentArray(a, b) : isArray(b) ? isEquivalentArray(b, a) : (a && a.valueOf()) === (b && b.valueOf());
}
function isEquivalentArray(a, b) {
  return isArray(b) ? a.length === b.length && a.every((value, i) => value === b[i]) : a.length === 1 && a[0] === b;
}
function isRouteLocation(route) {
  return typeof route === "string" || route && typeof route === "object";
}
/*!
 * vue-router v5.0.2
 * (c) 2026 Eduardo San Martin Morote
 * @license MIT
 */
function useLink(props) {
  const router = inject(routerKey);
  const currentRoute = inject(routeLocationKey);
  let hasPrevious = false;
  let previousTo = null;
  const route = computed(() => {
    const to = unref(props.to);
    if (!hasPrevious || to !== previousTo) {
      if (!isRouteLocation(to)) if (hasPrevious) warn$1(`Invalid value for prop "to" in useLink()
- to:`, to, `
- previous to:`, previousTo, `
- props:`, props);
      else warn$1(`Invalid value for prop "to" in useLink()
- to:`, to, `
- props:`, props);
      previousTo = to;
      hasPrevious = true;
    }
    return router.resolve(to);
  });
  const activeRecordIndex = computed(() => {
    const { matched } = route.value;
    const { length } = matched;
    const routeMatched = matched[length - 1];
    const currentMatched = currentRoute.matched;
    if (!routeMatched || !currentMatched.length) return -1;
    const index = currentMatched.findIndex(isSameRouteRecord.bind(null, routeMatched));
    if (index > -1) return index;
    const parentRecordPath = getOriginalPath(matched[length - 2]);
    return length > 1 && getOriginalPath(routeMatched) === parentRecordPath && currentMatched[currentMatched.length - 1].path !== parentRecordPath ? currentMatched.findIndex(isSameRouteRecord.bind(null, matched[length - 2])) : index;
  });
  const isActive = computed(() => activeRecordIndex.value > -1 && includesParams(currentRoute.params, route.value.params));
  const isExactActive = computed(() => activeRecordIndex.value > -1 && activeRecordIndex.value === currentRoute.matched.length - 1 && isSameRouteLocationParams(currentRoute.params, route.value.params));
  function navigate(e = {}) {
    if (guardEvent(e)) {
      const p = router[unref(props.replace) ? "replace" : "push"](unref(props.to)).catch(noop);
      if (props.viewTransition && typeof document !== "undefined" && "startViewTransition" in document) document.startViewTransition(() => p);
      return p;
    }
    return Promise.resolve();
  }
  if (isBrowser) {
    const instance = getCurrentInstance();
    if (instance) {
      const linkContextDevtools = {
        route: route.value,
        isActive: isActive.value,
        isExactActive: isExactActive.value,
        error: null
      };
      instance.__vrl_devtools = instance.__vrl_devtools || [];
      instance.__vrl_devtools.push(linkContextDevtools);
      watchEffect(() => {
        linkContextDevtools.route = route.value;
        linkContextDevtools.isActive = isActive.value;
        linkContextDevtools.isExactActive = isExactActive.value;
        linkContextDevtools.error = isRouteLocation(unref(props.to)) ? null : 'Invalid "to" value';
      }, { flush: "post" });
    }
  }
  return {
    route,
    href: computed(() => route.value.href),
    isActive,
    isExactActive,
    navigate
  };
}
function preferSingleVNode(vnodes) {
  return vnodes.length === 1 ? vnodes[0] : vnodes;
}
const RouterLinkImpl = /* @__PURE__ */ defineComponent({
  name: "RouterLink",
  compatConfig: { MODE: 3 },
  props: {
    to: {
      type: [String, Object],
      required: true
    },
    replace: Boolean,
    activeClass: String,
    exactActiveClass: String,
    custom: Boolean,
    ariaCurrentValue: {
      type: String,
      default: "page"
    },
    viewTransition: Boolean
  },
  useLink,
  setup(props, { slots }) {
    const link = reactive(useLink(props));
    const { options } = inject(routerKey);
    const elClass = computed(() => ({
      [getLinkClass(props.activeClass, options.linkActiveClass, "router-link-active")]: link.isActive,
      [getLinkClass(props.exactActiveClass, options.linkExactActiveClass, "router-link-exact-active")]: link.isExactActive
    }));
    return () => {
      const children = slots.default && preferSingleVNode(slots.default(link));
      return props.custom ? children : h("a", {
        "aria-current": link.isExactActive ? props.ariaCurrentValue : null,
        href: link.href,
        onClick: link.navigate,
        class: elClass.value
      }, children);
    };
  }
});
const RouterLink = RouterLinkImpl;
function guardEvent(e) {
  if (e.metaKey || e.altKey || e.ctrlKey || e.shiftKey) return;
  if (e.defaultPrevented) return;
  if (e.button !== void 0 && e.button !== 0) return;
  if (e.currentTarget && e.currentTarget.getAttribute) {
    const target = e.currentTarget.getAttribute("target");
    if (/\b_blank\b/i.test(target)) return;
  }
  if (e.preventDefault) e.preventDefault();
  return true;
}
function includesParams(outer, inner) {
  for (const key in inner) {
    const innerValue = inner[key];
    const outerValue = outer[key];
    if (typeof innerValue === "string") {
      if (innerValue !== outerValue) return false;
    } else if (!isArray(outerValue) || outerValue.length !== innerValue.length || innerValue.some((value, i) => value.valueOf() !== outerValue[i].valueOf())) return false;
  }
  return true;
}
function getOriginalPath(record) {
  return record ? record.aliasOf ? record.aliasOf.path : record.path : "";
}
const getLinkClass = (propClass, globalClass, defaultClass) => propClass != null ? propClass : globalClass != null ? globalClass : defaultClass;
const [majorVersion] = window.OC?.config?.version?.split(".") ?? [];
const isLegacy = Number.parseInt(majorVersion ?? "32") < 32;
const NC_FORM_BOX_CONTEXT_KEY = /* @__PURE__ */ Symbol.for("NcFormBox:context");
function useNcFormBox() {
  return inject(NC_FORM_BOX_CONTEXT_KEY, {
    isInFormBox: false,
    formBoxItemClass: void 0
  });
}
const _hoisted_1$1 = { class: "button-vue__wrapper" };
const _hoisted_2$1 = { class: "button-vue__icon" };
const _hoisted_3$1 = { class: "button-vue__text" };
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "NcButton",
  props: {
    alignment: { default: "center" },
    ariaLabel: { default: void 0 },
    disabled: { type: Boolean },
    download: { type: [String, Boolean], default: void 0 },
    href: { default: void 0 },
    pressed: { type: Boolean, default: void 0 },
    size: { default: "normal" },
    target: { default: "_self" },
    text: { default: void 0 },
    to: { default: void 0 },
    type: { default: "button" },
    variant: { default: "secondary" },
    wide: { type: Boolean }
  },
  emits: ["click", "update:pressed"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const { formBoxItemClass } = useNcFormBox();
    const hasVueRouterContext = inject(routerKey, null) !== null;
    const tag = computed(() => {
      if (hasVueRouterContext && props.to) {
        return "RouterLink";
      } else if (props.href) {
        return "a";
      } else {
        return "button";
      }
    });
    const hasPressedState = computed(() => tag.value === "button" && typeof props.pressed === "boolean");
    const variantWithPressed = computed(() => {
      if (props.pressed) {
        return "primary";
      }
      if (props.pressed === false && props.variant === "primary") {
        return "secondary";
      }
      return props.variant;
    });
    const isTertiaryVariant = computed(() => variantWithPressed.value.startsWith("tertiary"));
    const flexAlignment = computed(() => props.alignment.split("-")[0]);
    const isReverseAligned = computed(() => props.alignment.includes("-"));
    const getNcPopoverTriggerAttrs = inject("NcPopover:trigger:attrs", () => ({}), false);
    const ncPopoverTriggerAttrs = computed(() => getNcPopoverTriggerAttrs());
    const attrs = computed(() => {
      if (tag.value === "RouterLink") {
        return {
          to: props.to,
          activeClass: "active"
        };
      } else if (tag.value === "a") {
        return {
          href: props.href || "#",
          target: props.target,
          rel: "nofollow noreferrer noopener",
          download: props.download || void 0
        };
      } else if (tag.value === "button") {
        return {
          ...ncPopoverTriggerAttrs.value,
          "aria-pressed": props.pressed,
          type: props.type,
          disabled: props.disabled
        };
      }
      return void 0;
    });
    function onClick(event) {
      if (hasPressedState.value) {
        emit("update:pressed", !props.pressed);
      }
      emit("click", event);
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(resolveDynamicComponent(tag.value), mergeProps({
        class: ["button-vue", [
          `button-vue--size-${_ctx.size}`,
          {
            [`button-vue--${variantWithPressed.value}`]: variantWithPressed.value,
            "button-vue--tertiary": isTertiaryVariant.value,
            "button-vue--wide": _ctx.wide,
            [`button-vue--${flexAlignment.value}`]: flexAlignment.value !== "center",
            "button-vue--reverse": isReverseAligned.value,
            "button-vue--legacy": unref(isLegacy)
          },
          unref(formBoxItemClass)
        ]],
        "aria-label": _ctx.ariaLabel
      }, attrs.value, { onClick }), {
        default: withCtx(() => [
          createBaseVNode("span", _hoisted_1$1, [
            createBaseVNode("span", _hoisted_2$1, [
              renderSlot(_ctx.$slots, "icon", {}, void 0, true)
            ]),
            createBaseVNode("span", _hoisted_3$1, [
              renderSlot(_ctx.$slots, "default", {}, () => [
                createTextVNode(toDisplayString(_ctx.text), 1)
              ], true)
            ])
          ])
        ]),
        _: 3
      }, 16, ["class", "aria-label"]);
    };
  }
});
const NcButton = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-06ad9b25"]]);
var mdiAlert = "M13 14H11V9H13M13 18H11V16H13M1 21H23L12 2L1 21Z";
var mdiAlertCircleOutline = "M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z";
var mdiAlertDecagram = "M23,12L20.56,9.22L20.9,5.54L17.29,4.72L15.4,1.54L12,3L8.6,1.54L6.71,4.72L3.1,5.53L3.44,9.21L1,12L3.44,14.78L3.1,18.47L6.71,19.29L8.6,22.47L12,21L15.4,22.46L17.29,19.28L20.9,18.46L20.56,14.78L23,12M13,17H11V15H13V17M13,13H11V7H13V13Z";
var mdiArrowLeft = "M20,11V13H8L13.5,18.5L12.08,19.92L4.16,12L12.08,4.08L13.5,5.5L8,11H20Z";
var mdiArrowRight = "M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z";
var mdiCalendarBlank = "M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1";
var mdiCheck = "M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z";
var mdiCheckboxMarkedCircle = "M10,17L5,12L6.41,10.58L10,14.17L17.59,6.58L19,8M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z";
var mdiChevronDown = "M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z";
var mdiChevronLeft = "M15.41,16.58L10.83,12L15.41,7.41L14,6L8,12L14,18L15.41,16.58Z";
var mdiChevronRight = "M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z";
var mdiChevronUp = "M7.41,15.41L12,10.83L16.59,15.41L18,14L12,8L6,14L7.41,15.41Z";
var mdiClock = "M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M16.2,16.2L11,13V7H12.5V12.2L17,14.9L16.2,16.2Z";
var mdiClose = "M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z";
var mdiCloseCircleOutline = "M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2C6.47,2 2,6.47 2,12C2,17.53 6.47,22 12,22C17.53,22 22,17.53 22,12C22,6.47 17.53,2 12,2M14.59,8L12,10.59L9.41,8L8,9.41L10.59,12L8,14.59L9.41,16L12,13.41L14.59,16L16,14.59L13.41,12L16,9.41L14.59,8Z";
var mdiContentCopy = "M19,21H8V7H19M19,5H8A2,2 0 0,0 6,7V21A2,2 0 0,0 8,23H19A2,2 0 0,0 21,21V7A2,2 0 0,0 19,5M16,1H4A2,2 0 0,0 2,3V17H4V3H16V1Z";
var mdiDotsHorizontal = "M16,12A2,2 0 0,1 18,10A2,2 0 0,1 20,12A2,2 0 0,1 18,14A2,2 0 0,1 16,12M10,12A2,2 0 0,1 12,10A2,2 0 0,1 14,12A2,2 0 0,1 12,14A2,2 0 0,1 10,12M4,12A2,2 0 0,1 6,10A2,2 0 0,1 8,12A2,2 0 0,1 6,14A2,2 0 0,1 4,12Z";
var mdiEye = "M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z";
var mdiEyeOff = "M11.83,9L15,12.16C15,12.11 15,12.05 15,12A3,3 0 0,0 12,9C11.94,9 11.89,9 11.83,9M7.53,9.8L9.08,11.35C9.03,11.56 9,11.77 9,12A3,3 0 0,0 12,15C12.22,15 12.44,14.97 12.65,14.92L14.2,16.47C13.53,16.8 12.79,17 12,17A5,5 0 0,1 7,12C7,11.21 7.2,10.47 7.53,9.8M2,4.27L4.28,6.55L4.73,7C3.08,8.3 1.78,10 1,12C2.73,16.39 7,19.5 12,19.5C13.55,19.5 15.03,19.2 16.38,18.66L16.81,19.08L19.73,22L21,20.73L3.27,3M12,7A5,5 0 0,1 17,12C17,12.64 16.87,13.26 16.64,13.82L19.57,16.75C21.07,15.5 22.27,13.86 23,12C21.27,7.61 17,4.5 12,4.5C10.6,4.5 9.26,4.75 8,5.2L10.17,7.35C10.74,7.13 11.35,7 12,7Z";
var mdiInformation = "M13,9H11V7H13M13,17H11V11H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z";
var mdiPause = "M14,19H18V5H14M6,19H10V5H6V19Z";
var mdiPlay = "M8,5.14V19.14L19,12.14L8,5.14Z";
var mdiUndo = "M12.5,8C9.85,8 7.45,9 5.6,10.6L2,7V16H11L7.38,12.38C8.77,11.22 10.54,10.5 12.5,10.5C16.04,10.5 19.05,12.81 20.1,16L22.47,15.22C21.08,11.03 17.15,8 12.5,8Z";
const logger = getLoggerBuilder().detectUser().setApp("@nextcloud/vue").build();
function u(type, props, value) {
  const node = { type: String(type) };
  if ((value === void 0 || value === null) && (typeof props === "string" || Array.isArray(props))) {
    value = props;
  } else {
    Object.assign(node, props);
  }
  if (Array.isArray(value)) {
    node.children = value;
  } else if (value !== void 0 && value !== null) {
    node.value = String(value);
  }
  return node;
}
const convert = (
  // Note: overloads in JSDoc can’t yet use different `@template`s.
  /**
   * @type {(
   *   (<Condition extends string>(test: Condition) => (node: unknown, index?: number | null | undefined, parent?: Parent | null | undefined, context?: unknown) => node is Node & {type: Condition}) &
   *   (<Condition extends Props>(test: Condition) => (node: unknown, index?: number | null | undefined, parent?: Parent | null | undefined, context?: unknown) => node is Node & Condition) &
   *   (<Condition extends TestFunction>(test: Condition) => (node: unknown, index?: number | null | undefined, parent?: Parent | null | undefined, context?: unknown) => node is Node & Predicate<Condition, Node>) &
   *   ((test?: null | undefined) => (node?: unknown, index?: number | null | undefined, parent?: Parent | null | undefined, context?: unknown) => node is Node) &
   *   ((test?: Test) => Check)
   * )}
   */
  /**
   * @param {Test} [test]
   * @returns {Check}
   */
  (function(test) {
    if (test === null || test === void 0) {
      return ok;
    }
    if (typeof test === "function") {
      return castFactory(test);
    }
    if (typeof test === "object") {
      return Array.isArray(test) ? anyFactory(test) : (
        // Cast because `ReadonlyArray` goes into the above but `isArray`
        // narrows to `Array`.
        propertiesFactory(
          /** @type {Props} */
          test
        )
      );
    }
    if (typeof test === "string") {
      return typeFactory(test);
    }
    throw new Error("Expected function, string, or object as test");
  })
);
function anyFactory(tests) {
  const checks = [];
  let index = -1;
  while (++index < tests.length) {
    checks[index] = convert(tests[index]);
  }
  return castFactory(any);
  function any(...parameters) {
    let index2 = -1;
    while (++index2 < checks.length) {
      if (checks[index2].apply(this, parameters)) return true;
    }
    return false;
  }
}
function propertiesFactory(check) {
  const checkAsRecord = (
    /** @type {Record<string, unknown>} */
    check
  );
  return castFactory(all);
  function all(node) {
    const nodeAsRecord = (
      /** @type {Record<string, unknown>} */
      /** @type {unknown} */
      node
    );
    let key;
    for (key in check) {
      if (nodeAsRecord[key] !== checkAsRecord[key]) return false;
    }
    return true;
  }
}
function typeFactory(check) {
  return castFactory(type);
  function type(node) {
    return node && node.type === check;
  }
}
function castFactory(testFunction) {
  return check;
  function check(value, index, parent) {
    return Boolean(
      looksLikeANode(value) && testFunction.call(
        this,
        value,
        typeof index === "number" ? index : void 0,
        parent || void 0
      )
    );
  }
}
function ok() {
  return true;
}
function looksLikeANode(value) {
  return value !== null && typeof value === "object" && "type" in value;
}
function color(d) {
  return d;
}
const empty = [];
const CONTINUE = true;
const EXIT = false;
const SKIP = "skip";
function visitParents(tree, test, visitor, reverse) {
  let check;
  if (typeof test === "function" && typeof visitor !== "function") {
    reverse = visitor;
    visitor = test;
  } else {
    check = test;
  }
  const is = convert(check);
  const step = reverse ? -1 : 1;
  factory(tree, void 0, [])();
  function factory(node, index, parents) {
    const value = (
      /** @type {Record<string, unknown>} */
      node && typeof node === "object" ? node : {}
    );
    if (typeof value.type === "string") {
      const name = (
        // `hast`
        typeof value.tagName === "string" ? value.tagName : (
          // `xast`
          typeof value.name === "string" ? value.name : void 0
        )
      );
      Object.defineProperty(visit2, "name", {
        value: "node (" + color(node.type + (name ? "<" + name + ">" : "")) + ")"
      });
    }
    return visit2;
    function visit2() {
      let result = empty;
      let subresult;
      let offset;
      let grandparents;
      if (!test || is(node, index, parents[parents.length - 1] || void 0)) {
        result = toResult(visitor(node, parents));
        if (result[0] === EXIT) {
          return result;
        }
      }
      if ("children" in node && node.children) {
        const nodeAsParent = (
          /** @type {UnistParent} */
          node
        );
        if (nodeAsParent.children && result[0] !== SKIP) {
          offset = (reverse ? nodeAsParent.children.length : -1) + step;
          grandparents = parents.concat(nodeAsParent);
          while (offset > -1 && offset < nodeAsParent.children.length) {
            const child = nodeAsParent.children[offset];
            subresult = factory(child, offset, grandparents)();
            if (subresult[0] === EXIT) {
              return subresult;
            }
            offset = typeof subresult[1] === "number" ? subresult[1] : offset + step;
          }
        }
      }
      return result;
    }
  }
}
function toResult(value) {
  if (Array.isArray(value)) {
    return value;
  }
  if (typeof value === "number") {
    return [CONTINUE, value];
  }
  return value === null || value === void 0 ? empty : [value];
}
function visit(tree, testOrVisitor, visitorOrReverse, maybeReverse) {
  let reverse;
  let test;
  let visitor;
  if (typeof testOrVisitor === "function" && typeof visitorOrReverse !== "function") {
    test = void 0;
    visitor = testOrVisitor;
    reverse = visitorOrReverse;
  } else {
    test = testOrVisitor;
    visitor = visitorOrReverse;
    reverse = maybeReverse;
  }
  visitParents(tree, test, overload, reverse);
  function overload(node, parents) {
    const parent = parents[parents.length - 1];
    const index = parent ? parent.children.indexOf(node) : void 0;
    return visitor(node, index, parent);
  }
}
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const URL_PATTERN = /(\s|^)(https?:\/\/)([-A-Z0-9+_.]+(?::[0-9]+)?(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|$)/ig;
const URL_PATTERN_AUTOLINK = /(\s|\(|^)((https?:\/\/)([-A-Z0-9+_.]+[-A-Z0-9]+(?::[0-9]+)?(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*))(?=\s|\)|$)/ig;
const NcLink = defineComponent({
  name: "NcLink",
  props: {
    href: {
      type: String,
      required: true
    }
  },
  render() {
    return h("a", {
      href: this.href,
      rel: "noopener noreferrer",
      target: "_blank",
      class: "rich-text--external-link"
    }, [this.href.trim()]);
  }
});
function remarkAutolink({ autolink, useMarkdown, useExtendedMarkdown }) {
  return function(tree) {
    if (useExtendedMarkdown || !useMarkdown || !autolink) {
      return;
    }
    visit(tree, (node) => node.type === "text", (node, index, parent) => {
      let parsed = parseUrl(node.value);
      if (typeof parsed === "string") {
        parsed = [u("text", parsed)];
      } else {
        parsed = parsed.map((n) => {
          if (typeof n === "string") {
            return u("text", n);
          }
          return u("link", {
            url: n.props.href
          }, [u("text", n.props.href)]);
        }).filter((x) => x).flat();
      }
      parent.children.splice(index, 1, ...parsed);
      return [SKIP, (index ?? 0) + parsed.length];
    });
  };
}
function parseUrl(text) {
  let match = URL_PATTERN_AUTOLINK.exec(text);
  const list = [];
  let start = 0;
  while (match !== null) {
    let href = match[2];
    let textAfter;
    let textBefore = text.substring(start, match.index + match[1].length);
    if (href[0] === " ") {
      textBefore += href[0];
      href = href.substring(1).trim();
    }
    const lastChar = href[href.length - 1];
    if (lastChar === "." || lastChar === "," || lastChar === ";" || match[0][0] === "(" && lastChar === ")") {
      href = href.substring(0, href.length - 1);
      textAfter = lastChar;
    }
    list.push(textBefore);
    list.push({ component: NcLink, props: { href } });
    if (textAfter) {
      list.push(textAfter);
    }
    start = match.index + match[0].length;
    match = URL_PATTERN_AUTOLINK.exec(text);
  }
  list.push(text.substring(start));
  const joinedText = list.map((item) => typeof item === "string" ? item : item.props.href).join("");
  if (text === joinedText) {
    return list;
  }
  logger.error("[NcRichText] Failed to reassemble the chunked text: " + text);
  return text;
}
function getRoute(router, url) {
  const removePrefix = (str, prefix) => str.startsWith(prefix) ? str.slice(prefix.length) : str;
  const removePrefixes = (str, ...prefixes) => prefixes.reduce((acc, prefix) => removePrefix(acc, prefix), str);
  if (!router) {
    return null;
  }
  const isAbsoluteURL = /^https?:\/\//.test(url);
  const isNonHttpLink = /^[a-z][a-z0-9+.-]*:.+/.test(url);
  if (!isAbsoluteURL && isNonHttpLink) {
    return null;
  }
  if (isAbsoluteURL && !url.startsWith(getBaseUrl())) {
    return null;
  }
  if (!isAbsoluteURL && !url.startsWith("/")) {
    return null;
  }
  const relativeUrl = isAbsoluteURL ? removePrefixes(url, getBaseUrl(), "/index.php") : url;
  const relativeRouterBase = removePrefixes(router.options.history.base, getRootUrl(), "/index.php");
  const potentialRouterPath = removePrefixes(relativeUrl, relativeRouterBase) || "/";
  const route = router.resolve(potentialRouterPath);
  if (!route.matched.length) {
    return null;
  }
  return route.fullPath;
}
const _sfc_main = {
  name: "ArrowRightIcon",
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
const _hoisted_1 = ["aria-hidden", "aria-label"];
const _hoisted_2 = ["fill", "width", "height"];
const _hoisted_3 = { d: "M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon arrow-right-icon",
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
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2))
  ], 16, _hoisted_1);
}
const ArrowRightIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/ArrowRight.vue"]]);
export {
  ArrowRightIcon as A,
  parseUrl as B,
  remarkAutolink as C,
  u as D,
  EXIT as E,
  mdiChevronUp as F,
  mdiChevronDown as G,
  mdiClock as H,
  mdiCalendarBlank as I,
  NcButton as N,
  RouterLink as R,
  SKIP as S,
  URL_PATTERN as U,
  mdiUndo as a,
  mdiClose as b,
  mdiEyeOff as c,
  mdiEye as d,
  mdiCheck as e,
  mdiAlertCircleOutline as f,
  getRoute as g,
  mdiCloseCircleOutline as h,
  isLegacy as i,
  mdiArrowLeft as j,
  mdiDotsHorizontal as k,
  logger as l,
  mdiArrowRight as m,
  mdiChevronRight as n,
  mdiAlert as o,
  mdiInformation as p,
  mdiCheckboxMarkedCircle as q,
  mdiAlertDecagram as r,
  mdiPause as s,
  mdiPlay as t,
  mdiChevronLeft as u,
  convert as v,
  visit as w,
  mdiContentCopy as x,
  routerKey as y,
  visitParents as z
};
//# sourceMappingURL=ArrowRight-BC77f5L9.chunk.mjs.map
