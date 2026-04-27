const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, k as useModel, l as useTemplateRef, n as computed, o as openBlock, c as createBlock, p as createSlots, w as withCtx, i as renderSlot, u as unref, m as mergeProps, q as mergeModels } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { m as mdiArrowRight, a as mdiUndo, b as mdiClose } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { r as register, t as t50, a as t18, b as t, N as NcIconSvgWrapper } from "./Web-BOM4en5n.chunk.mjs";
import { N as NcInputField } from "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
register(t18, t50);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcTextField",
  props: /* @__PURE__ */ mergeModels({
    class: {},
    inputClass: {},
    id: {},
    label: {},
    labelOutside: { type: Boolean },
    type: {},
    placeholder: {},
    showTrailingButton: { type: Boolean },
    trailingButtonLabel: { default: void 0 },
    success: { type: Boolean },
    error: { type: Boolean },
    helperText: {},
    disabled: { type: Boolean },
    pill: { type: Boolean },
    trailingButtonIcon: { default: "close" }
  }, {
    "modelValue": { default: "" },
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props, { expose: __expose }) {
    const modelValue = useModel(__props, "modelValue");
    const props = __props;
    __expose({
      focus,
      select
    });
    const inputFieldInstance = useTemplateRef("inputField");
    const defaultTrailingButtonLabels = {
      arrowEnd: t("Save changes"),
      close: t("Clear text"),
      undo: t("Undo changes")
    };
    const NcInputFieldPropNames = new Set(Object.keys(NcInputField.props));
    const propsToForward = computed(() => {
      const sharedProps = Object.fromEntries(Object.entries(props).filter(([key]) => NcInputFieldPropNames.has(key)));
      sharedProps.trailingButtonLabel ??= defaultTrailingButtonLabels[props.trailingButtonIcon];
      return sharedProps;
    });
    function focus(options) {
      inputFieldInstance.value.focus(options);
    }
    function select() {
      inputFieldInstance.value.select();
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcInputField), mergeProps(propsToForward.value, {
        ref: "inputField",
        modelValue: modelValue.value,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => modelValue.value = $event)
      }), createSlots({ _: 2 }, [
        !!_ctx.$slots.icon ? {
          name: "icon",
          fn: withCtx(() => [
            renderSlot(_ctx.$slots, "icon")
          ]),
          key: "0"
        } : void 0,
        _ctx.type !== "search" ? {
          name: "trailing-button-icon",
          fn: withCtx(() => [
            _ctx.trailingButtonIcon === "arrowEnd" ? (openBlock(), createBlock(unref(NcIconSvgWrapper), {
              key: 0,
              directional: "",
              path: unref(mdiArrowRight)
            }, null, 8, ["path"])) : (openBlock(), createBlock(unref(NcIconSvgWrapper), {
              key: 1,
              path: _ctx.trailingButtonIcon === "undo" ? unref(mdiUndo) : unref(mdiClose)
            }, null, 8, ["path"]))
          ]),
          key: "1"
        } : void 0
      ]), 1040, ["modelValue"]);
    };
  }
});
export {
  _sfc_main as _
};
//# sourceMappingURL=NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs.map
