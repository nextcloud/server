const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, o as openBlock, f as createElementBlock, g as createBaseVNode, j as createTextVNode, t as toDisplayString, u as unref, x as createVNode, h as createCommentVNode, i as renderSlot, m as mergeProps } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { r as register, A as t26, _ as _export_sfc, b as t } from "./Web-BOM4en5n.chunk.mjs";
import { _ as _export_sfc$1 } from "./index-o76qk6sn.chunk.mjs";
const _sfc_main$1 = {
  name: "HelpCircleIcon",
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
const _hoisted_3$1 = { d: "M15.07,11.25L14.17,12.17C13.45,12.89 13,13.5 13,15H11V14.5C11,13.39 11.45,12.39 12.17,11.67L13.41,10.41C13.78,10.05 14,9.55 14,9C14,7.89 13.1,7 12,7A2,2 0 0,0 10,9H8A4,4 0 0,1 12,5A4,4 0 0,1 16,9C16,9.88 15.64,10.67 15.07,11.25M13,19H11V17H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12C22,6.47 17.5,2 12,2Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon help-circle-icon",
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
const HelpCircle = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
register(t26);
const _hoisted_1$2 = { class: "settings-section" };
const _hoisted_2$2 = { class: "settings-section__name" };
const _hoisted_3$2 = ["aria-label", "href", "title"];
const _hoisted_4$2 = {
  key: 0,
  class: "settings-section__desc"
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "NcSettingsSection",
  props: {
    name: {},
    description: { default: "" },
    docUrl: { default: "" }
  },
  setup(__props) {
    const ariaLabel = t("External documentation");
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1$2, [
        createBaseVNode("h2", _hoisted_2$2, [
          createTextVNode(toDisplayString(_ctx.name) + " ", 1),
          _ctx.docUrl ? (openBlock(), createElementBlock("a", {
            key: 0,
            "aria-label": unref(ariaLabel),
            class: "settings-section__info",
            href: _ctx.docUrl,
            rel: "noreferrer nofollow",
            target: "_blank",
            title: unref(ariaLabel)
          }, [
            createVNode(HelpCircle, { size: 20 })
          ], 8, _hoisted_3$2)) : createCommentVNode("", true)
        ]),
        _ctx.description ? (openBlock(), createElementBlock("p", _hoisted_4$2, toDisplayString(_ctx.description), 1)) : createCommentVNode("", true),
        renderSlot(_ctx.$slots, "default", {}, void 0, true)
      ]);
    };
  }
});
const NcSettingsSection = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-9cedb949"]]);
const _sfc_main = {
  name: "ContentCopyIcon",
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
const _hoisted_3 = { d: "M19,21H8V7H19M19,5H8A2,2 0 0,0 6,7V21A2,2 0 0,0 8,23H19A2,2 0 0,0 21,21V7A2,2 0 0,0 19,5M16,1H4A2,2 0 0,0 2,3V17H4V3H16V1Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon content-copy-icon",
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
const Information = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/ContentCopy.vue"]]);
export {
  Information as I,
  NcSettingsSection as N
};
//# sourceMappingURL=ContentCopy-C2t0ZTwU.chunk.mjs.map
