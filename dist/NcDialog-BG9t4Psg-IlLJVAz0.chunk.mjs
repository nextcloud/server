const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { e as createApp, b as defineComponent, o as openBlock, c as createBlock, w as withCtx, j as createTextVNode, t as toDisplayString, i as renderSlot, u as unref, h as createCommentVNode, y as ref, k as useModel, s as useSlots, l as useTemplateRef, n as computed, g as createBaseVNode, K as resolveDynamicComponent, m as mergeProps, L as toHandlers, v as normalizeClass, f as createElementBlock, F as Fragment, C as renderList, q as mergeModels } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { u as useElementSize, N as NcModal } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { r as register, b as t, N as NcIconSvgWrapper, i as t33, _ as _export_sfc, c as createElementId } from "./Web-BOM4en5n.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
function spawnDialog(dialog, props = {}, options = {}) {
  let { container } = options;
  if ("container" in props && typeof props.container === "string") {
    container ??= props.container;
  }
  const resolvedContainer = typeof container === "string" && document.querySelector(container) || document.body;
  const element = resolvedContainer.appendChild(document.createElement("div"));
  return new Promise((resolve, reject) => {
    const app = createApp(dialog, {
      ...props,
      // If dialog has no `container` prop passing a falsy value does nothing
      // Otherwise it is expected that `null` disables teleport and mounts dialog in place like NcDialog/NcModal
      container: null,
      onClose(...rest) {
        const payload = rest.length > 1 ? rest : rest[0];
        app.unmount();
        element.remove();
        resolve(payload);
      },
      "onVue:unmounted": () => {
        app.unmount();
        element.remove();
        reject(new Error("Dialog was unmounted without close event"));
      }
    });
    app.mount(element);
  });
}
register(t33);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "NcDialogButton",
  props: {
    callback: { type: Function, default: () => {
    } },
    disabled: { type: Boolean, default: false },
    icon: { default: void 0 },
    label: {},
    type: { default: "button" },
    variant: { default: "tertiary" }
  },
  emits: ["click"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const isLoading = ref(false);
    async function handleClick(e) {
      if (isLoading.value) {
        return;
      }
      isLoading.value = true;
      try {
        const fallback = props.type === "reset" ? false : void 0;
        const result = await props.callback?.() ?? fallback;
        if (result !== false) {
          emit("click", e, result);
        }
      } finally {
        isLoading.value = false;
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcButton), {
        "aria-label": _ctx.label,
        disabled: _ctx.disabled,
        type: _ctx.type,
        variant: _ctx.variant,
        onClick: handleClick
      }, {
        icon: withCtx(() => [
          renderSlot(_ctx.$slots, "icon", {}, () => [
            isLoading.value ? (openBlock(), createBlock(unref(NcLoadingIcon), {
              key: 0,
              name: unref(t)("Loading …")
              /* TRANSLATORS: The button is in a loading state*/
            }, null, 8, ["name"])) : _ctx.icon !== void 0 ? (openBlock(), createBlock(unref(NcIconSvgWrapper), {
              key: 1,
              svg: _ctx.icon
            }, null, 8, ["svg"])) : createCommentVNode("", true)
          ])
        ]),
        default: withCtx(() => [
          createTextVNode(toDisplayString(_ctx.label) + " ", 1)
        ]),
        _: 3
      }, 8, ["aria-label", "disabled", "type", "variant"]);
    };
  }
});
const _hoisted_1 = ["id", "textContent"];
const _hoisted_2 = ["aria-label", "aria-labelledby"];
const _hoisted_3 = { class: "dialog__text" };
const _hoisted_4 = { class: "dialog__actions" };
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcDialog",
  props: /* @__PURE__ */ mergeModels({
    name: {},
    message: { default: "" },
    additionalTrapElements: { default: () => [] },
    container: { default: "body" },
    size: { default: "small" },
    buttons: { default: () => [] },
    isForm: { type: Boolean },
    noClose: { type: Boolean },
    closeOnClickOutside: { type: Boolean },
    outTransition: { type: Boolean },
    navigationAriaLabel: { default: "" },
    navigationAriaLabelledby: { default: "" },
    contentClasses: { default: "" },
    dialogClasses: { default: "" },
    navigationClasses: { default: "" }
  }, {
    "open": { type: Boolean, ...{ default: true } },
    "openModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["closing", "reset", "submit"], ["update:open"]),
  setup(__props, { emit: __emit }) {
    const open = useModel(__props, "open");
    const props = __props;
    const emit = __emit;
    const slots = useSlots();
    const wrapperElement = useTemplateRef("wrapper");
    const { width: dialogWidth } = useElementSize(wrapperElement, { width: 900, height: 0 });
    const isNavigationCollapsed = computed(() => dialogWidth.value < 876);
    const hasNavigation = computed(() => slots?.navigation !== void 0);
    const navigationId = createElementId();
    const navigationAriaLabelAttr = computed(() => props.navigationAriaLabel || void 0);
    const navigationAriaLabelledbyAttr = computed(() => {
      if (props.navigationAriaLabel) {
        return void 0;
      }
      return props.navigationAriaLabelledby || navigationId;
    });
    const dialogRootElement = useTemplateRef("dialogElement");
    const dialogTagName = computed(() => props.isForm && !hasNavigation.value ? "form" : "div");
    const dialogListeners = computed(() => {
      if (dialogTagName.value !== "form") {
        return {};
      }
      return {
        /**
         * @param event - Form submit event
         */
        submit(event) {
          event.preventDefault();
          emit("submit", event);
        },
        /**
         * @param event - Form submit event
         */
        reset(event) {
          event.preventDefault();
          emit("reset", event);
        }
      };
    });
    const showModal = ref(true);
    function handleButtonClose(button, result) {
      if (button.type === "submit" && dialogTagName.value === "form" && "reportValidity" in dialogRootElement.value && !dialogRootElement.value.reportValidity()) {
        return;
      }
      handleClosing(result);
      window.setTimeout(() => handleClosed(), 300);
    }
    function handleClosing(result) {
      showModal.value = false;
      emit("closing", result);
    }
    function handleClosed() {
      showModal.value = true;
      open.value = false;
    }
    const modalProps = computed(() => ({
      noClose: props.noClose,
      container: props.container === void 0 ? "body" : props.container,
      // we do not pass the name as we already have the name as the headline
      // name: props.name,
      // But we need to set the correct label id so the dialog is labelled
      labelId: navigationId,
      size: props.size,
      show: open.value && showModal.value,
      outTransition: props.outTransition,
      closeOnClickOutside: props.closeOnClickOutside,
      additionalTrapElements: props.additionalTrapElements
    }));
    return (_ctx, _cache) => {
      return open.value ? (openBlock(), createBlock(unref(NcModal), mergeProps({
        key: 0,
        class: "dialog__modal",
        disableSwipe: ""
      }, modalProps.value, {
        onClose: handleClosed,
        "onUpdate:show": _cache[0] || (_cache[0] = ($event) => handleClosing())
      }), {
        default: withCtx(() => [
          createBaseVNode("h2", {
            id: unref(navigationId),
            class: "dialog__name",
            textContent: toDisplayString(_ctx.name)
          }, null, 8, _hoisted_1),
          (openBlock(), createBlock(resolveDynamicComponent(dialogTagName.value), mergeProps({
            ref: "dialogElement",
            class: ["dialog", _ctx.dialogClasses]
          }, toHandlers(dialogListeners.value)), {
            default: withCtx(() => [
              createBaseVNode("div", {
                ref: "wrapper",
                class: normalizeClass(["dialog__wrapper", [{ "dialog__wrapper--collapsed": isNavigationCollapsed.value }]])
              }, [
                hasNavigation.value ? (openBlock(), createElementBlock("nav", {
                  key: 0,
                  class: normalizeClass(["dialog__navigation", _ctx.navigationClasses]),
                  "aria-label": navigationAriaLabelAttr.value,
                  "aria-labelledby": navigationAriaLabelledbyAttr.value
                }, [
                  renderSlot(_ctx.$slots, "navigation", { isCollapsed: isNavigationCollapsed.value }, void 0, true)
                ], 10, _hoisted_2)) : createCommentVNode("", true),
                createBaseVNode("div", {
                  class: normalizeClass(["dialog__content", _ctx.contentClasses])
                }, [
                  renderSlot(_ctx.$slots, "default", {}, () => [
                    createBaseVNode("p", _hoisted_3, toDisplayString(_ctx.message), 1)
                  ], true)
                ], 2)
              ], 2),
              createBaseVNode("div", _hoisted_4, [
                renderSlot(_ctx.$slots, "actions", {}, () => [
                  (openBlock(true), createElementBlock(Fragment, null, renderList(_ctx.buttons, (button, idx) => {
                    return openBlock(), createBlock(unref(_sfc_main$1), mergeProps({ key: idx }, { ref_for: true }, button, {
                      onClick: (_, result) => handleButtonClose(button, result)
                    }), null, 16, ["onClick"]);
                  }), 128))
                ], true)
              ])
            ]),
            _: 3
          }, 16, ["class"]))
        ]),
        _: 3
      }, 16)) : createCommentVNode("", true);
    };
  }
});
const NcDialog = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-24e91b99"]]);
export {
  NcDialog as N,
  spawnDialog as s
};
//# sourceMappingURL=NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs.map
