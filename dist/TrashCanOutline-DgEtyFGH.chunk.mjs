const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, n as computed, o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc } from "./Web-BOM4en5n.chunk.mjs";
import { _ as _export_sfc$1 } from "./index-o76qk6sn.chunk.mjs";
const _hoisted_1$1 = ["aria-label"];
const _hoisted_2$1 = ["width", "height"];
const _hoisted_3$1 = ["fill"];
const _hoisted_4$1 = ["fill"];
const _hoisted_5 = { key: 0 };
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "NcLoadingIcon",
  props: {
    appearance: { default: "auto" },
    name: { default: "" },
    size: { default: 20 }
  },
  setup(__props) {
    const props = __props;
    const colors = computed(() => {
      const colors2 = ["#777", "#CCC"];
      if (props.appearance === "light") {
        return colors2;
      } else if (props.appearance === "dark") {
        return colors2.reverse();
      }
      return ["var(--color-loading-light)", "var(--color-loading-dark)"];
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("span", {
        "aria-label": _ctx.name,
        role: "img",
        class: "material-design-icon loading-icon"
      }, [
        (openBlock(), createElementBlock("svg", {
          width: _ctx.size,
          height: _ctx.size,
          viewBox: "0 0 24 24"
        }, [
          createBaseVNode("path", {
            fill: colors.value[0],
            d: "M12,4V2A10,10 0 1,0 22,12H20A8,8 0 1,1 12,4Z"
          }, null, 8, _hoisted_3$1),
          createBaseVNode("path", {
            fill: colors.value[1],
            d: "M12,4V2A10,10 0 0,1 22,12H20A8,8 0 0,0 12,4Z"
          }, [
            _ctx.name ? (openBlock(), createElementBlock("title", _hoisted_5, toDisplayString(_ctx.name), 1)) : createCommentVNode("", true)
          ], 8, _hoisted_4$1)
        ], 8, _hoisted_2$1))
      ], 8, _hoisted_1$1);
    };
  }
});
const NcLoadingIcon = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-cf399190"]]);
const _sfc_main = {
  name: "TrashCanOutlineIcon",
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
const _hoisted_3 = { d: "M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon trash-can-outline-icon",
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
const Delete = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/TrashCanOutline.vue"]]);
export {
  Delete as D,
  NcLoadingIcon as N
};
//# sourceMappingURL=TrashCanOutline-DgEtyFGH.chunk.mjs.map
