const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { a as NcActions } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { b as defineComponent, n as computed, o as openBlock, f as createElementBlock, t as toDisplayString, v as normalizeClass, r as resolveComponent, c as createBlock, w as withCtx, g as createBaseVNode, m as mergeProps, V as withKeys, i as renderSlot, j as createTextVNode, h as createCommentVNode, E as withDirectives, G as vShow, x as createVNode, p as createSlots, I as normalizeProps, J as guardReactiveProps, K as resolveDynamicComponent } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { b as getCanonicalLocale } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { _ as _export_sfc } from "./Web-BOM4en5n.chunk.mjs";
import { _ as _export_sfc$1 } from "./index-o76qk6sn.chunk.mjs";
const _sfc_main$3 = defineComponent({
  name: "NcVNodes",
  props: {
    /**
     * The vnodes to render
     */
    vnodes: {
      type: [Array, Object],
      default: null
    }
  },
  /**
   * The render function to display the component
   */
  render() {
    return this.vnodes || this.$slots?.default?.({});
  }
});
const _hoisted_1$2 = ["title"];
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "NcCounterBubble",
  props: {
    count: {},
    active: { type: Boolean },
    type: { default: "" },
    raw: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const humanizedCount = computed(() => {
      if (props.raw) {
        return props.count.toString();
      }
      const formatter = new Intl.NumberFormat(getCanonicalLocale(), {
        notation: "compact",
        compactDisplay: "short"
      });
      return formatter.format(props.count);
    });
    const originalCountAsTitleIfNeeded = computed(() => {
      if (props.raw) {
        return;
      }
      const countAsString = props.count.toString();
      if (countAsString === humanizedCount.value) {
        return;
      }
      return countAsString;
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["counter-bubble__counter", {
          active: _ctx.active,
          "counter-bubble__counter--highlighted": _ctx.type === "highlighted",
          "counter-bubble__counter--outlined": _ctx.type === "outlined"
        }]),
        title: originalCountAsTitleIfNeeded.value
      }, toDisplayString(humanizedCount.value), 11, _hoisted_1$2);
    };
  }
});
const NcCounterBubble = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-36ffc13f"]]);
const _sfc_main$1 = {
  name: "NcListItem",
  components: {
    NcActions,
    NcCounterBubble,
    NcVNodes: _sfc_main$3
  },
  inheritAttrs: false,
  props: {
    /**
     * The details text displayed in the upper right part of the component
     */
    details: {
      type: String,
      default: ""
    },
    /**
     * Name (first line of text)
     */
    name: {
      type: String,
      default: void 0
    },
    /**
     * The route for the router link.
     */
    to: {
      type: [String, Object],
      default: null
    },
    /**
     * The value for the external link
     */
    href: {
      type: String,
      default: "#"
    },
    /**
     * The HTML target attribute used for the link
     */
    target: {
      type: String,
      default: ""
    },
    /**
     * Id for the `<a>` element
     */
    anchorId: {
      type: String,
      default: ""
    },
    /**
     * Make subname bold
     */
    bold: {
      type: Boolean,
      default: false
    },
    /**
     * Show the NcListItem in compact design
     */
    compact: {
      type: Boolean,
      default: false
    },
    /**
     * Toggle the active state of the component
     */
    active: {
      type: Boolean,
      default: void 0
    },
    /**
     * Aria label for the wrapper element
     */
    linkAriaLabel: {
      type: String,
      default: ""
    },
    /**
     * Aria label for the actions toggle
     */
    actionsAriaLabel: {
      type: String,
      default: void 0
    },
    /**
     * If different from 0 this component will display the
     * NcCounterBubble component
     */
    counterNumber: {
      type: [Number, String],
      default: 0
    },
    /**
     * Outlined or highlighted state of the counter
     */
    counterType: {
      type: String,
      default: "",
      validator(value) {
        return ["highlighted", "outlined", ""].indexOf(value) !== -1;
      }
    },
    /**
     * To be used only when the elements in the actions menu are very important
     */
    forceDisplayActions: {
      type: Boolean,
      default: false
    },
    /**
     * Force the actions to display in a three dot menu
     */
    forceMenu: {
      type: Boolean,
      default: false
    },
    /**
     * Show the list component layout
     */
    oneLine: {
      type: Boolean,
      default: false
    }
  },
  emits: [
    "click",
    "dragstart",
    "update:menuOpen"
  ],
  data() {
    return {
      hovered: false,
      hasActions: false,
      hasSubname: false,
      displayActionsOnHoverFocus: false,
      menuOpen: false,
      hasIndicator: false,
      hasDetails: false
    };
  },
  computed: {
    showAdditionalElements() {
      return !this.displayActionsOnHoverFocus || this.forceDisplayActions;
    },
    showDetails() {
      return (this.details !== "" || this.hasDetails) && (!this.displayActionsOnHoverFocus || this.forceDisplayActions);
    }
  },
  watch: {
    menuOpen(newValue) {
      if (!newValue && !this.hovered) {
        this.displayActionsOnHoverFocus = false;
      }
    }
  },
  mounted() {
    this.checkSlots();
  },
  updated() {
    this.checkSlots();
  },
  methods: {
    /**
     * Handle link click
     *
     * @param {MouseEvent|KeyboardEvent} event - Native click or keydown event
     * @param {Function} [navigate] - VueRouter link's navigate if any
     * @param {string} [routerLinkHref] - VueRouter link's href
     */
    onClick(event, navigate, routerLinkHref) {
      this.$emit("click", event);
      if (event.metaKey || event.altKey || event.ctrlKey || event.shiftKey) {
        return;
      }
      if (routerLinkHref) {
        navigate?.(event);
        event.preventDefault();
      }
    },
    showActions() {
      if (this.hasActions) {
        this.displayActionsOnHoverFocus = true;
      }
      this.hovered = false;
    },
    hideActions() {
      this.displayActionsOnHoverFocus = false;
    },
    /**
     * @param {FocusEvent} event UI event
     */
    handleBlur(event) {
      if (this.menuOpen) {
        return;
      }
      if (this.$refs["list-item"]?.contains(event.relatedTarget)) {
        return;
      }
      this.hideActions();
    },
    /**
     * Hide the actions on mouseleave unless the menu is open
     */
    handleMouseleave() {
      if (!this.menuOpen) {
        this.displayActionsOnHoverFocus = false;
      }
      this.hovered = false;
    },
    handleMouseover() {
      this.showActions();
      this.hovered = true;
    },
    handleActionsUpdateOpen(e) {
      this.menuOpen = e;
      this.$emit("update:menuOpen", e);
    },
    // Check if subname and actions slots are populated
    checkSlots() {
      if (this.hasActions !== !!this.$slots.actions) {
        this.hasActions = !!this.$slots.actions;
      }
      if (this.hasSubname !== !!this.$slots.subname) {
        this.hasSubname = !!this.$slots.subname;
      }
      if (this.hasIndicator !== !!this.$slots.indicator) {
        this.hasIndicator = !!this.$slots.indicator;
      }
      if (this.hasDetails !== !!this.$slots.details) {
        this.hasDetails = !!this.$slots.details;
      }
    }
  }
};
const _hoisted_1$1 = ["id", "aria-label", "href", "target", "rel", "onClick"];
const _hoisted_2$1 = { class: "list-item-content" };
const _hoisted_3$1 = { class: "list-item-content__main" };
const _hoisted_4$1 = { class: "list-item-content__name" };
const _hoisted_5 = { class: "list-item-content__details" };
const _hoisted_6 = {
  key: 0,
  class: "list-item-details__details"
};
const _hoisted_7 = {
  key: 1,
  class: "list-item-details__extra"
};
const _hoisted_8 = {
  key: 1,
  class: "list-item-details__indicator"
};
const _hoisted_9 = {
  key: 0,
  class: "list-item-content__extra-actions"
};
const _hoisted_10 = {
  key: 2,
  class: "list-item__extra"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcCounterBubble = resolveComponent("NcCounterBubble");
  const _component_NcActions = resolveComponent("NcActions");
  return openBlock(), createBlock(resolveDynamicComponent($props.to ? "router-link" : "NcVNodes"), normalizeProps(guardReactiveProps({ ...$props.to && { custom: true, to: $props.to } })), {
    default: withCtx(({ href: routerLinkHref, navigate, isActive }) => [
      createBaseVNode("li", mergeProps({
        class: ["list-item__wrapper", { "list-item__wrapper--active": $props.active ?? isActive }]
      }, _ctx.$attrs), [
        createBaseVNode("div", {
          ref: "list-item",
          class: normalizeClass(["list-item", {
            "list-item--compact": $props.compact,
            "list-item--one-line": $props.oneLine
          }]),
          onMouseover: _cache[5] || (_cache[5] = (...args) => $options.handleMouseover && $options.handleMouseover(...args)),
          onMouseleave: _cache[6] || (_cache[6] = (...args) => $options.handleMouseleave && $options.handleMouseleave(...args))
        }, [
          createBaseVNode("a", {
            id: $props.anchorId || void 0,
            "aria-label": $props.linkAriaLabel,
            class: "list-item__anchor",
            href: routerLinkHref || $props.href,
            target: $props.target || ($props.href === "#" ? void 0 : "_blank"),
            rel: $props.href === "#" ? void 0 : "noopener noreferrer",
            onFocus: _cache[0] || (_cache[0] = (...args) => $options.showActions && $options.showActions(...args)),
            onFocusout: _cache[1] || (_cache[1] = (...args) => $options.handleBlur && $options.handleBlur(...args)),
            onClick: ($event) => $options.onClick($event, navigate, routerLinkHref),
            onDragstart: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("dragstart", $event)),
            onKeydown: _cache[3] || (_cache[3] = withKeys((...args) => $options.hideActions && $options.hideActions(...args), ["esc"]))
          }, [
            renderSlot(_ctx.$slots, "icon", {}, void 0, true),
            createBaseVNode("div", _hoisted_2$1, [
              createBaseVNode("div", _hoisted_3$1, [
                createBaseVNode("div", _hoisted_4$1, [
                  renderSlot(_ctx.$slots, "name", {}, () => [
                    createTextVNode(toDisplayString($props.name), 1)
                  ], true)
                ]),
                $data.hasSubname ? (openBlock(), createElementBlock("div", {
                  key: 0,
                  class: normalizeClass(["list-item-content__subname", { "list-item-content__subname--bold": $props.bold }])
                }, [
                  renderSlot(_ctx.$slots, "subname", {}, void 0, true)
                ], 2)) : createCommentVNode("", true)
              ]),
              createBaseVNode("div", _hoisted_5, [
                $options.showDetails ? (openBlock(), createElementBlock("div", _hoisted_6, [
                  renderSlot(_ctx.$slots, "details", {}, () => [
                    createTextVNode(toDisplayString($props.details), 1)
                  ], true)
                ])) : createCommentVNode("", true),
                $props.counterNumber !== 0 || $data.hasIndicator ? withDirectives((openBlock(), createElementBlock("div", _hoisted_7, [
                  $props.counterNumber !== 0 ? (openBlock(), createBlock(_component_NcCounterBubble, {
                    key: 0,
                    count: $props.counterNumber,
                    active: $props.active ?? isActive,
                    class: "list-item-details__counter",
                    type: $props.counterType
                  }, null, 8, ["count", "active", "type"])) : createCommentVNode("", true),
                  $data.hasIndicator ? (openBlock(), createElementBlock("span", _hoisted_8, [
                    renderSlot(_ctx.$slots, "indicator", {}, void 0, true)
                  ])) : createCommentVNode("", true)
                ], 512)), [
                  [vShow, $options.showAdditionalElements]
                ]) : createCommentVNode("", true)
              ])
            ])
          ], 40, _hoisted_1$1),
          _ctx.$slots["extra-actions"] ? (openBlock(), createElementBlock("div", _hoisted_9, [
            renderSlot(_ctx.$slots, "extra-actions", {}, void 0, true)
          ])) : createCommentVNode("", true),
          $props.forceDisplayActions || $data.displayActionsOnHoverFocus ? (openBlock(), createElementBlock("div", {
            key: 1,
            class: "list-item-content__actions",
            onFocusout: _cache[4] || (_cache[4] = (...args) => $options.handleBlur && $options.handleBlur(...args))
          }, [
            createVNode(_component_NcActions, {
              ref: "actions",
              primary: $props.active ?? isActive,
              forceMenu: $props.forceMenu,
              "aria-label": $props.actionsAriaLabel,
              "onUpdate:open": $options.handleActionsUpdateOpen
            }, createSlots({
              default: withCtx(() => [
                renderSlot(_ctx.$slots, "actions", {}, void 0, true)
              ]),
              _: 2
            }, [
              _ctx.$slots["actions-icon"] ? {
                name: "icon",
                fn: withCtx(() => [
                  renderSlot(_ctx.$slots, "actions-icon", {}, void 0, true)
                ]),
                key: "0"
              } : void 0
            ]), 1032, ["primary", "forceMenu", "aria-label", "onUpdate:open"])
          ], 32)) : createCommentVNode("", true),
          _ctx.$slots.extra ? (openBlock(), createElementBlock("div", _hoisted_10, [
            renderSlot(_ctx.$slots, "extra", {}, void 0, true)
          ])) : createCommentVNode("", true)
        ], 34)
      ], 16)
    ]),
    _: 3
  }, 16);
}
const NcListItem = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-bc710154"]]);
const svgCheck = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-check" viewBox="0 0 24 24"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" /></svg>';
const _sfc_main = {
  name: "TrayArrowDownIcon",
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
const _hoisted_3 = { d: "M2 12H4V17H20V12H22V17C22 18.11 21.11 19 20 19H4C2.9 19 2 18.11 2 17V12M12 15L17.55 9.54L16.13 8.13L13 11.25V2H11V11.25L7.88 8.13L6.46 9.55L12 15Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon tray-arrow-down-icon",
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
const Download = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/TrayArrowDown.vue"]]);
export {
  Download as D,
  NcListItem as N,
  svgCheck as s
};
//# sourceMappingURL=TrayArrowDown-DVjUGg6-.chunk.mjs.map
