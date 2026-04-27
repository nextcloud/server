const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, k as useModel, z as watch, l as useTemplateRef, n as computed, o as openBlock, c as createBlock, p as createSlots, w as withCtx, i as renderSlot, x as createVNode, u as unref, m as mergeProps, q as mergeModels, y as ref } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { l as logger, c as mdiEyeOff, d as mdiEye } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { g as getCapabilities } from "./index-o76qk6sn.chunk.mjs";
import { d as debounce, c as generateOcsUrl } from "./index-rAufP352.chunk.mjs";
import { r as register, e as t29, _ as _export_sfc, b as t, N as NcIconSvgWrapper } from "./Web-BOM4en5n.chunk.mjs";
import { N as NcInputField } from "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
register(t29);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcPasswordField",
  props: /* @__PURE__ */ mergeModels({
    class: {},
    inputClass: { default: "" },
    id: {},
    label: {},
    labelOutside: { type: Boolean },
    placeholder: {},
    showTrailingButton: { type: Boolean, default: true },
    success: { type: Boolean },
    error: { type: Boolean },
    helperText: {},
    disabled: { type: Boolean },
    pill: { type: Boolean },
    checkPasswordStrength: { type: Boolean },
    minlength: { default: void 0 },
    asText: { type: Boolean }
  }, {
    "modelValue": { default: "" },
    "modelModifiers": {},
    "visible": { type: Boolean, ...{ default: false } },
    "visibleModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["valid", "invalid"], ["update:modelValue", "update:visible"]),
  setup(__props, { expose: __expose, emit: __emit }) {
    const modelValue = useModel(__props, "modelValue");
    const visible = useModel(__props, "visible");
    const props = __props;
    const emit = __emit;
    watch(modelValue, debounce(checkPassword, 500));
    __expose({
      focus,
      select
    });
    const { password_policy: passwordPolicy } = getCapabilities();
    const inputFieldInstance = useTemplateRef("inputField");
    const internalHelpMessage = ref("");
    const isValid = ref();
    const propsToForward = computed(() => {
      const all = { ...props };
      delete all.checkPasswordStrength;
      delete all.minlength;
      delete all.asText;
      delete all.error;
      delete all.helperText;
      delete all.inputClass;
      delete all.success;
      return all;
    });
    const minLengthWithPolicy = computed(() => {
      return props.minlength ?? (props.checkPasswordStrength ? passwordPolicy?.minLength : void 0) ?? void 0;
    });
    async function checkPassword() {
      if (!props.checkPasswordStrength) {
        return;
      }
      try {
        const { data } = await cancelableClient.post(generateOcsUrl("apps/password_policy/api/v1/validate"), { password: modelValue.value });
        isValid.value = data.ocs.data.passed;
        if (data.ocs.data.passed) {
          internalHelpMessage.value = t("Password is secure");
          emit("valid");
          return;
        }
        internalHelpMessage.value = data.ocs.data.reason;
        emit("invalid");
      } catch (error) {
        logger.error("Password policy returned an error", { error });
      }
    }
    function toggleVisibility() {
      visible.value = !visible.value;
    }
    function focus(options) {
      inputFieldInstance.value.focus(options);
    }
    function select() {
      inputFieldInstance.value.select();
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(NcInputField, mergeProps(propsToForward.value, {
        ref: "inputField",
        modelValue: modelValue.value,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => modelValue.value = $event),
        error: _ctx.error || isValid.value === false,
        helperText: _ctx.helperText || internalHelpMessage.value,
        inputClass: [_ctx.inputClass, { "password-field__input--secure-text": !visible.value && _ctx.asText }],
        minlength: minLengthWithPolicy.value,
        success: _ctx.success || isValid.value === true,
        trailingButtonLabel: visible.value ? unref(t)("Hide password") : unref(t)("Show password"),
        type: visible.value || _ctx.asText ? "text" : "password",
        onTrailingButtonClick: toggleVisibility
      }), createSlots({
        "trailing-button-icon": withCtx(() => [
          createVNode(NcIconSvgWrapper, {
            path: visible.value ? unref(mdiEyeOff) : unref(mdiEye)
          }, null, 8, ["path"])
        ]),
        _: 2
      }, [
        !!_ctx.$slots.icon ? {
          name: "icon",
          fn: withCtx(() => [
            renderSlot(_ctx.$slots, "icon", {}, void 0, true)
          ]),
          key: "0"
        } : void 0
      ]), 1040, ["modelValue", "error", "helperText", "inputClass", "minlength", "success", "trailingButtonLabel", "type"]);
    };
  }
});
const NcPasswordField = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-00e75248"]]);
export {
  NcPasswordField as N
};
//# sourceMappingURL=NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs.map
