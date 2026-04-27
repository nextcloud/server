const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, o as openBlock, f as createElementBlock, i as renderSlot, h as createCommentVNode, u as unref, j as createTextVNode, t as toDisplayString } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc, c as createElementId } from "./Web-BOM4en5n.chunk.mjs";
const _hoisted_1 = ["aria-labelledby"];
const _hoisted_2 = {
  key: 0,
  class: "empty-content__icon",
  "aria-hidden": "true"
};
const _hoisted_3 = ["id"];
const _hoisted_4 = {
  key: 2,
  class: "empty-content__description"
};
const _hoisted_5 = {
  key: 3,
  class: "empty-content__action"
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcEmptyContent",
  props: {
    description: { default: "" },
    name: { default: "" }
  },
  setup(__props) {
    const nameId = createElementId();
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        "aria-labelledby": unref(nameId),
        class: "empty-content",
        role: "note"
      }, [
        _ctx.$slots.icon ? (openBlock(), createElementBlock("div", _hoisted_2, [
          renderSlot(_ctx.$slots, "icon", {}, void 0, true)
        ])) : createCommentVNode("", true),
        _ctx.name !== "" || _ctx.$slots.name ? (openBlock(), createElementBlock("div", {
          key: 1,
          id: unref(nameId),
          class: "empty-content__name"
        }, [
          renderSlot(_ctx.$slots, "name", {}, () => [
            createTextVNode(toDisplayString(_ctx.name), 1)
          ], true)
        ], 8, _hoisted_3)) : createCommentVNode("", true),
        _ctx.description !== "" || _ctx.$slots.description ? (openBlock(), createElementBlock("p", _hoisted_4, [
          renderSlot(_ctx.$slots, "description", {}, () => [
            createTextVNode(toDisplayString(_ctx.description), 1)
          ], true)
        ])) : createCommentVNode("", true),
        _ctx.$slots.action ? (openBlock(), createElementBlock("div", _hoisted_5, [
          renderSlot(_ctx.$slots, "action", {}, void 0, true)
        ])) : createCommentVNode("", true)
      ], 8, _hoisted_1);
    };
  }
});
const NcEmptyContent = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-b101d636"]]);
export {
  NcEmptyContent as N
};
//# sourceMappingURL=NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs.map
