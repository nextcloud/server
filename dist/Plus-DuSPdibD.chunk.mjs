const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, s as useSlots, n as computed, o as openBlock, f as createElementBlock, i as renderSlot, c as createBlock, h as createCommentVNode, g as createBaseVNode, j as createTextVNode, t as toDisplayString, w as withCtx, x as createVNode, u as unref, v as normalizeClass, m as mergeProps } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { b as mdiClose } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcActionButton } from "./PencilOutline-BMYBdzdS.chunk.mjs";
import { a as NcActions } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { r as register, z as t19, N as NcIconSvgWrapper, b as t, _ as _export_sfc } from "./Web-BOM4en5n.chunk.mjs";
import { _ as _export_sfc$1 } from "./index-o76qk6sn.chunk.mjs";
register(t19);
const _hoisted_1$1 = {
  key: 0,
  class: "nc-chip__icon"
};
const _hoisted_2$1 = { class: "nc-chip__text" };
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "NcChip",
  props: {
    ariaLabelClose: { default: t("Close") },
    actionsContainer: { default: "body" },
    text: { default: "" },
    iconPath: { default: void 0 },
    iconSvg: { default: void 0 },
    noClose: { type: Boolean },
    variant: { default: "secondary" }
  },
  emits: ["close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const slots = useSlots();
    const canClose = computed(() => !props.noClose);
    const hasActions = () => !!slots.actions;
    const hasIcon = () => Boolean(props.iconPath || props.iconSvg || !!slots.icon);
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["nc-chip", {
          [`nc-chip--${_ctx.variant}`]: true,
          "nc-chip--no-actions": _ctx.noClose && !hasActions(),
          "nc-chip--no-icon": !hasIcon()
        }])
      }, [
        hasIcon() ? (openBlock(), createElementBlock("span", _hoisted_1$1, [
          renderSlot(_ctx.$slots, "icon", {}, () => [
            _ctx.iconPath || _ctx.iconSvg ? (openBlock(), createBlock(NcIconSvgWrapper, {
              key: 0,
              inline: "",
              path: _ctx.iconPath,
              svg: _ctx.iconPath ? void 0 : _ctx.iconSvg,
              size: 18
            }, null, 8, ["path", "svg"])) : createCommentVNode("", true)
          ], true)
        ])) : createCommentVNode("", true),
        createBaseVNode("span", _hoisted_2$1, [
          renderSlot(_ctx.$slots, "default", {}, () => [
            createTextVNode(toDisplayString(_ctx.text), 1)
          ], true)
        ]),
        canClose.value || hasActions() ? (openBlock(), createBlock(NcActions, {
          key: 1,
          class: "nc-chip__actions",
          container: _ctx.actionsContainer,
          forceMenu: !canClose.value,
          variant: "tertiary-no-background"
        }, {
          default: withCtx(() => [
            canClose.value ? (openBlock(), createBlock(NcActionButton, {
              key: 0,
              closeAfterClick: "",
              onClick: _cache[0] || (_cache[0] = ($event) => emit("close"))
            }, {
              icon: withCtx(() => [
                createVNode(NcIconSvgWrapper, {
                  path: unref(mdiClose),
                  size: 20
                }, null, 8, ["path"])
              ]),
              default: withCtx(() => [
                createTextVNode(" " + toDisplayString(_ctx.ariaLabelClose), 1)
              ]),
              _: 1
            })) : createCommentVNode("", true),
            renderSlot(_ctx.$slots, "actions", {}, void 0, true)
          ]),
          _: 3
        }, 8, ["container", "forceMenu"])) : createCommentVNode("", true)
      ], 2);
    };
  }
});
const NcChip = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-8f5d3c40"]]);
const _sfc_main = {
  name: "PlusIcon",
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
const _hoisted_3 = { d: "M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon plus-icon",
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
const PlusIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Plus.vue"]]);
export {
  NcChip as N,
  PlusIcon as P
};
//# sourceMappingURL=Plus-DuSPdibD.chunk.mjs.map
