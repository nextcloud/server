const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, k as useModel, n as computed, z as watch, H as warn, o as openBlock, c as createBlock, w as withCtx, i as renderSlot, K as resolveDynamicComponent, m as mergeProps, x as createVNode, N as normalizeStyle, g as createBaseVNode, t as toDisplayString, f as createElementBlock, h as createCommentVNode, q as mergeModels } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { R as RouterLink } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcAvatar } from "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import { A as NcPopover } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { _ as _export_sfc } from "./Web-BOM4en5n.chunk.mjs";
const _sfc_main$1 = {};
function _sfc_render(_ctx, _cache) {
  return openBlock(), createElementBlock("div", null, [
    renderSlot(_ctx.$slots, "trigger")
  ]);
}
const NcUserBubbleDiv = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render]]);
const _hoisted_1 = { class: "user-bubble__name" };
const _hoisted_2 = {
  key: 0,
  class: "user-bubble__secondary"
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcUserBubble",
  props: /* @__PURE__ */ mergeModels({
    avatarImage: { default: void 0 },
    user: { default: void 0 },
    displayName: { default: void 0 },
    showUserStatus: { type: Boolean },
    url: { default: void 0 },
    to: { default: void 0 },
    primary: { type: Boolean },
    size: { default: 20 },
    margin: { default: 2 }
  }, {
    "open": { type: Boolean },
    "openModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["click"], ["update:open"]),
  setup(__props, { emit: __emit }) {
    const isOpen = useModel(__props, "open");
    const props = __props;
    const emit = __emit;
    const isAvatarUrl = computed(() => {
      if (!props.avatarImage) {
        return false;
      }
      try {
        const url = new URL(props.avatarImage);
        return !!url;
      } catch {
        return false;
      }
    });
    const isCustomAvatar = computed(() => !!props.avatarImage);
    const avatarStyle = computed(() => ({
      marginInlineStart: `${props.margin}px`
    }));
    const hasUrl = computed(() => {
      if (!props.url || props.url.trim() === "") {
        return false;
      }
      try {
        const url = new URL(props.url, props.url?.startsWith?.("/") ? window.location.href : void 0);
        return !!url;
      } catch {
        warn("[NcUserBubble] Invalid URL passed", { url: props.url });
        return false;
      }
    });
    const href = computed(() => hasUrl.value ? props.url : void 0);
    const contentComponent = computed(() => {
      if (hasUrl.value) {
        return "a";
      } else if (props.to) {
        return RouterLink;
      } else {
        return "div";
      }
    });
    const contentStyle = computed(() => ({
      height: `${props.size}px`,
      lineHeight: `${props.size}px`,
      borderRadius: `${props.size / 2}px`
    }));
    watch([() => props.displayName, () => props.user], () => {
      if (!props.displayName && !props.user) {
        warn("[NcUserBubble] At least `displayName` or `user` property should be set.");
      }
    });
    return (_ctx, _cache) => {
      return openBlock(), createBlock(resolveDynamicComponent(!!_ctx.$slots.default ? NcPopover : NcUserBubbleDiv), {
        shown: isOpen.value,
        "onUpdate:shown": _cache[1] || (_cache[1] = ($event) => isOpen.value = $event),
        class: "user-bubble__wrapper",
        trigger: "hover focus"
      }, {
        trigger: withCtx(({ attrs }) => [
          (openBlock(), createBlock(resolveDynamicComponent(contentComponent.value), mergeProps({
            class: ["user-bubble__content", { "user-bubble__content--primary": _ctx.primary }],
            style: contentStyle.value,
            to: _ctx.to,
            href: href.value
          }, attrs, {
            onClick: _cache[0] || (_cache[0] = ($event) => emit("click", $event))
          }), {
            default: withCtx(() => [
              createVNode(NcAvatar, {
                url: isCustomAvatar.value && isAvatarUrl.value ? _ctx.avatarImage : void 0,
                iconClass: isCustomAvatar.value && !isAvatarUrl.value ? _ctx.avatarImage : void 0,
                user: _ctx.user,
                displayName: _ctx.displayName,
                size: _ctx.size - _ctx.margin * 2,
                style: normalizeStyle(avatarStyle.value),
                disableTooltip: "",
                disableMenu: "",
                hideStatus: !_ctx.showUserStatus,
                class: "user-bubble__avatar"
              }, null, 8, ["url", "iconClass", "user", "displayName", "size", "style", "hideStatus"]),
              createBaseVNode("span", _hoisted_1, toDisplayString(_ctx.displayName || _ctx.user), 1),
              !!_ctx.$slots.name ? (openBlock(), createElementBlock("span", _hoisted_2, [
                renderSlot(_ctx.$slots, "name", {}, void 0, true)
              ])) : createCommentVNode("", true)
            ]),
            _: 2
          }, 1040, ["class", "style", "to", "href"]))
        ]),
        default: withCtx(() => [
          renderSlot(_ctx.$slots, "default", {}, void 0, true)
        ]),
        _: 3
      }, 40, ["shown"]);
    };
  }
});
const NcUserBubble = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-9189d023"]]);
export {
  NcUserBubble as N
};
//# sourceMappingURL=NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs.map
