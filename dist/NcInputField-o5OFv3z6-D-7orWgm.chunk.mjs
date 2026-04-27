const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, k as useModel, D as useAttrs, l as useTemplateRef, n as computed, o as openBlock, f as createElementBlock, g as createBaseVNode, m as mergeProps, t as toDisplayString, h as createCommentVNode, E as withDirectives, G as vShow, i as renderSlot, c as createBlock, w as withCtx, u as unref, j as createTextVNode, v as normalizeClass, q as mergeModels, H as warn } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { N as NcButton, e as mdiCheck, f as mdiAlertCircleOutline, i as isLegacy } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { _ as _export_sfc, N as NcIconSvgWrapper, c as createElementId } from "./Web-BOM4en5n.chunk.mjs";
const _hoisted_1 = { class: "input-field__main-wrapper" };
const _hoisted_2 = ["id", "aria-describedby", "disabled", "placeholder", "type", "value"];
const _hoisted_3 = ["for"];
const _hoisted_4 = { class: "input-field__icon input-field__icon--leading" };
const _hoisted_5 = {
  key: 2,
  class: "input-field__icon input-field__icon--trailing"
};
const _hoisted_6 = ["id"];
const _sfc_main = /* @__PURE__ */ defineComponent({
  ...{
    inheritAttrs: false
  },
  __name: "NcInputField",
  props: /* @__PURE__ */ mergeModels({
    class: { default: "" },
    inputClass: { default: "" },
    id: { default: () => createElementId() },
    label: { default: void 0 },
    labelOutside: { type: Boolean },
    type: { default: "text" },
    placeholder: { default: void 0 },
    showTrailingButton: { type: Boolean },
    trailingButtonLabel: { default: void 0 },
    success: { type: Boolean },
    error: { type: Boolean },
    helperText: { default: "" },
    disabled: { type: Boolean },
    pill: { type: Boolean }
  }, {
    "modelValue": { required: true },
    "modelModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["trailingButtonClick"], ["update:modelValue"]),
  setup(__props, { expose: __expose, emit: __emit }) {
    const modelValue = useModel(__props, "modelValue");
    const props = __props;
    const emit = __emit;
    __expose({
      focus,
      select
    });
    const attrs = useAttrs();
    const inputElement = useTemplateRef("input");
    const hasTrailingIcon = computed(() => props.showTrailingButton || props.success);
    const internalPlaceholder = computed(() => {
      if (props.placeholder) {
        return props.placeholder;
      }
      if (props.label) {
        return isLegacy ? props.label : "";
      }
      return void 0;
    });
    const isValidLabel = computed(() => {
      const isValidLabel2 = props.label || props.labelOutside;
      if (!isValidLabel2) {
        warn("You need to add a label to the NcInputField component. Either use the prop label or use an external one, as per the example in the documentation.");
      }
      return isValidLabel2;
    });
    const ariaDescribedby = computed(() => {
      const ariaDescribedby2 = [];
      if (props.helperText) {
        ariaDescribedby2.push(`${props.id}-helper-text`);
      }
      if (attrs["aria-describedby"]) {
        ariaDescribedby2.push(String(attrs["aria-describedby"]));
      }
      return ariaDescribedby2.join(" ") || void 0;
    });
    function focus(options) {
      inputElement.value.focus(options);
    }
    function select() {
      inputElement.value.select();
    }
    function handleInput(event) {
      const target = event.target;
      modelValue.value = props.type === "number" && typeof modelValue.value === "number" ? parseFloat(target.value) : target.value;
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["input-field", [{
          "input-field--disabled": _ctx.disabled,
          "input-field--error": _ctx.error,
          "input-field--label-outside": _ctx.labelOutside || !isValidLabel.value,
          "input-field--leading-icon": !!_ctx.$slots.icon,
          "input-field--trailing-icon": hasTrailingIcon.value,
          "input-field--pill": _ctx.pill,
          "input-field--success": _ctx.success,
          "input-field--legacy": unref(isLegacy)
        }, _ctx.$props.class]])
      }, [
        createBaseVNode("div", _hoisted_1, [
          createBaseVNode("input", mergeProps(_ctx.$attrs, {
            id: _ctx.id,
            ref: "input",
            "aria-describedby": ariaDescribedby.value,
            "aria-live": "polite",
            class: ["input-field__input", _ctx.inputClass],
            disabled: _ctx.disabled,
            placeholder: internalPlaceholder.value,
            type: _ctx.type,
            value: modelValue.value.toString(),
            onInput: handleInput
          }), null, 16, _hoisted_2),
          !_ctx.labelOutside && isValidLabel.value ? (openBlock(), createElementBlock("label", {
            key: 0,
            class: "input-field__label",
            for: _ctx.id
          }, toDisplayString(_ctx.label), 9, _hoisted_3)) : createCommentVNode("", true),
          withDirectives(createBaseVNode("div", _hoisted_4, [
            renderSlot(_ctx.$slots, "icon", {}, void 0, true)
          ], 512), [
            [vShow, !!_ctx.$slots.icon]
          ]),
          _ctx.showTrailingButton ? (openBlock(), createBlock(NcButton, {
            key: 1,
            class: "input-field__trailing-button",
            "aria-label": _ctx.trailingButtonLabel,
            disabled: _ctx.disabled,
            variant: "tertiary-no-background",
            onClick: _cache[0] || (_cache[0] = ($event) => emit("trailingButtonClick", $event))
          }, {
            icon: withCtx(() => [
              renderSlot(_ctx.$slots, "trailing-button-icon", {}, void 0, true)
            ]),
            _: 3
          }, 8, ["aria-label", "disabled"])) : _ctx.success || _ctx.error ? (openBlock(), createElementBlock("div", _hoisted_5, [
            _ctx.success ? (openBlock(), createBlock(NcIconSvgWrapper, {
              key: 0,
              path: unref(mdiCheck)
            }, null, 8, ["path"])) : (openBlock(), createBlock(NcIconSvgWrapper, {
              key: 1,
              path: unref(mdiAlertCircleOutline)
            }, null, 8, ["path"]))
          ])) : createCommentVNode("", true)
        ]),
        _ctx.helperText ? (openBlock(), createElementBlock("p", {
          key: 0,
          id: `${_ctx.id}-helper-text`,
          class: "input-field__helper-text-message"
        }, [
          _ctx.success ? (openBlock(), createBlock(NcIconSvgWrapper, {
            key: 0,
            class: "input-field__helper-text-message__icon",
            path: unref(mdiCheck),
            inline: ""
          }, null, 8, ["path"])) : _ctx.error ? (openBlock(), createBlock(NcIconSvgWrapper, {
            key: 1,
            class: "input-field__helper-text-message__icon",
            path: unref(mdiAlertCircleOutline),
            inline: ""
          }, null, 8, ["path"])) : createCommentVNode("", true),
          createTextVNode(" " + toDisplayString(_ctx.helperText), 1)
        ], 8, _hoisted_6)) : createCommentVNode("", true)
      ], 2);
    };
  }
});
const NcInputField = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-bfba6aa6"]]);
export {
  NcInputField as N
};
//# sourceMappingURL=NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs.map
