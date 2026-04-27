const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=[window.OC.filePath('', '', 'dist/index-DD39fp6M.chunk.mjs'),window.OC.filePath('', '', 'dist/preload-helper-xAe3EUYB.chunk.mjs'),window.OC.filePath('', '', 'dist/ArrowRight-BC77f5L9.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-BOM4en5n.chunk.mjs'),window.OC.filePath('', '', 'dist/translation-DoG5ZELJ-2UfAUX2V.chunk.mjs'),window.OC.filePath('', '', 'dist/index-rAufP352.chunk.mjs'),window.OC.filePath('', '', 'dist/index-o76qk6sn.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-N3OwSN9O.chunk.css'),window.OC.filePath('', '', 'dist/ArrowRight-Ch8zyY_U.chunk.css'),window.OC.filePath('', '', 'dist/colors-BHGKZFDI-C0-WujoK.chunk.mjs'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-Da4dXMUU.chunk.mjs'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-B2iEdqc2.chunk.css'),window.OC.filePath('', '', 'dist/index-CCanY5eB.chunk.css'),window.OC.filePath('', '', 'dist/index-BcMnKoRR.chunk.mjs'),window.OC.filePath('', '', 'dist/NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs'),window.OC.filePath('', '', 'dist/TrashCanOutline-DgEtyFGH.chunk.mjs'),window.OC.filePath('', '', 'dist/TrashCanOutline-CWUlo4XY.chunk.css'),window.OC.filePath('', '', 'dist/NcSelect-DLheQ2yp-DEY3FLux.chunk.css'),window.OC.filePath('', '', 'dist/index-HT1ZTE-Z.chunk.css')])))=>i.map(i=>d[i]);
const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, k as useModel, n as computed, o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, v as normalizeClass, m as mergeProps, q as mergeModels, r as resolveComponent, i as renderSlot, N as normalizeStyle, M as withModifiers, h as createCommentVNode, c as createBlock, x as createVNode, w as withCtx, a as defineAsyncComponent, _ as __vitePreload } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { r as register, x as t39, _ as _export_sfc, b as t, c as createElementId, y as t48 } from "./Web-BOM4en5n.chunk.mjs";
import { a as ActionGlobalMixin } from "./PencilOutline-BMYBdzdS.chunk.mjs";
import { N as NcPasswordField } from "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import { _ as _sfc_main$2 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { q as getDefaultExportFromCjs } from "./index-rAufP352.chunk.mjs";
import { N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
register(t39);
const _hoisted_1$1 = ["for"];
const _hoisted_2$1 = ["id", "type", "value", "min", "max"];
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  ...{ inheritAttrs: false },
  __name: "NcDateTimePickerNative",
  props: /* @__PURE__ */ mergeModels({
    class: { default: void 0 },
    id: { default: () => createElementId() },
    inputClass: { default: "" },
    type: { default: "date" },
    label: { default: () => t("Please choose a date") },
    min: { default: null },
    max: { default: null },
    hideLabel: { type: Boolean }
  }, {
    "modelValue": { default: null },
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props) {
    const modelValue = useModel(__props, "modelValue");
    const props = __props;
    const formattedValue = computed(() => modelValue.value ? formatValue(modelValue.value) : "");
    const formattedMax = computed(() => props.max ? formatValue(props.max) : void 0);
    const formattedMin = computed(() => props.min ? formatValue(props.min) : void 0);
    function getReadableDate(value) {
      const yyyy = value.getFullYear().toString().padStart(4, "0");
      const MM = (value.getMonth() + 1).toString().padStart(2, "0");
      const dd = value.getDate().toString().padStart(2, "0");
      const hh = value.getHours().toString().padStart(2, "0");
      const mm = value.getMinutes().toString().padStart(2, "0");
      return { yyyy, MM, dd, hh, mm };
    }
    function formatValue(value) {
      const { yyyy, MM, dd, hh, mm } = getReadableDate(value);
      if (props.type === "datetime-local") {
        return `${yyyy}-${MM}-${dd}T${hh}:${mm}`;
      } else if (props.type === "date") {
        return `${yyyy}-${MM}-${dd}`;
      } else if (props.type === "month") {
        return `${yyyy}-${MM}`;
      } else if (props.type === "time") {
        return `${hh}:${mm}`;
      } else if (props.type === "week") {
        const startDate = new Date(Number.parseInt(yyyy), 0, 1);
        const daysSinceBeginningOfYear = Math.floor((value.getTime() - startDate.getTime()) / (24 * 60 * 60 * 1e3));
        const weekNumber = Math.ceil(daysSinceBeginningOfYear / 7);
        return `${yyyy}-W${weekNumber}`;
      }
      return "";
    }
    function onInput(event) {
      const input = event.target;
      if (!input || isNaN(input.valueAsNumber)) {
        modelValue.value = null;
      } else if (props.type === "time") {
        const time = input.value;
        const { yyyy, MM, dd } = getReadableDate(modelValue.value || /* @__PURE__ */ new Date());
        modelValue.value = /* @__PURE__ */ new Date(`${yyyy}-${MM}-${dd}T${time}`);
      } else if (props.type === "month") {
        const MM = (new Date(input.value).getMonth() + 1).toString().padStart(2, "0");
        const { yyyy, dd, hh, mm } = getReadableDate(modelValue.value || /* @__PURE__ */ new Date());
        modelValue.value = /* @__PURE__ */ new Date(`${yyyy}-${MM}-${dd}T${hh}:${mm}`);
      } else {
        const timezoneOffsetSeconds = new Date(input.valueAsNumber).getTimezoneOffset() * 1e3 * 60;
        const inputDateWithTimezone = input.valueAsNumber + timezoneOffsetSeconds;
        modelValue.value = new Date(inputDateWithTimezone);
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["native-datetime-picker", _ctx.$props.class])
      }, [
        createBaseVNode("label", {
          class: normalizeClass(["native-datetime-picker__label", { "hidden-visually": _ctx.hideLabel }]),
          for: _ctx.id
        }, toDisplayString(_ctx.label), 11, _hoisted_1$1),
        createBaseVNode("input", mergeProps({
          id: _ctx.id,
          class: ["native-datetime-picker__input", _ctx.inputClass],
          type: _ctx.type,
          value: formattedValue.value,
          min: formattedMin.value,
          max: formattedMax.value
        }, _ctx.$attrs, { onInput }), null, 16, _hoisted_2$1)
      ], 2);
    };
  }
});
const NcDateTimePickerNative = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-b97e1f7a"]]);
register(t48);
const _sfc_main = {
  name: "NcActionInput",
  components: {
    NcDateTimePickerNative,
    NcPasswordField,
    NcTextField: _sfc_main$2,
    // Lazy load components with more than 50kB bundle size impact
    NcColorPicker: defineAsyncComponent(() => __vitePreload(() => import("./index-DD39fp6M.chunk.mjs"), true ? __vite__mapDeps([0,1,2,3,4,5,6,7,8,9,10,11,12]) : void 0, import.meta.url)),
    NcDateTimePicker: defineAsyncComponent(() => __vitePreload(() => import("./index-BcMnKoRR.chunk.mjs"), true ? __vite__mapDeps([13,1,2,3,4,5,6,7,8,10,11,14,15,16,17,18]) : void 0, import.meta.url)),
    NcSelect: defineAsyncComponent(() => __vitePreload(() => Promise.resolve().then(() => index), true ? void 0 : void 0, import.meta.url))
  },
  mixins: [ActionGlobalMixin],
  inheritAttrs: false,
  props: {
    /**
     * id attribute of the checkbox element
     */
    id: {
      type: String,
      default: () => "action-" + createElementId(),
      validator: (id) => id.trim() !== ""
    },
    /**
     * id attribute of the text input element
     */
    inputId: {
      type: String,
      default: () => "action-input-" + createElementId(),
      validator: (id) => id.trim() !== ""
    },
    /**
     * Icon to show with the action, can be either a CSS class or an URL
     */
    icon: {
      type: String,
      default: ""
    },
    /**
     * type attribute of the input field
     */
    type: {
      type: String,
      default: "text",
      validator(type) {
        return [
          "date",
          "datetime-local",
          "month",
          "multiselect",
          "number",
          "password",
          "search",
          "tel",
          "text",
          "time",
          "url",
          "week",
          "color",
          "email"
        ].includes(type);
      }
    },
    /**
     * id attribute for the native date time picker
     */
    idNativeDateTimePicker: {
      type: String,
      default: "date-time-picker_id"
    },
    /**
     * Flag to use a native date time picker
     */
    isNativePicker: {
      type: Boolean,
      default: false
    },
    /**
     * The visible input label for accessibility purposes.
     */
    label: {
      type: String,
      default: null
    },
    /**
     * If you want to show the label just above the
     * input field, pass in `true` to this prop.
     */
    labelOutside: {
      type: Boolean,
      default: true
    },
    /**
     * value attribute of the input field
     */
    modelValue: {
      type: [String, Date, Number, Array],
      default: ""
    },
    /**
     * disabled state of the input field
     */
    disabled: {
      type: Boolean,
      default: false
    },
    /**
     * aria-label attribute of the input field
     */
    ariaLabel: {
      type: String,
      default: ""
    },
    /**
     * Attribute forwarded to the underlying NcPasswordField and NcTextField
     */
    showTrailingButton: {
      type: Boolean,
      default: true
    },
    /**
     * Trailing button label forwarded to the underlying NcTextField
     */
    trailingButtonLabel: {
      type: String,
      default: t("Submit")
    },
    /**
     * CSS class to apply to the root element.
     */
    class: {
      type: [String, Array, Object],
      default: ""
    }
  },
  emits: [
    "submit",
    "update:modelValue"
  ],
  computed: {
    isIconUrl() {
      try {
        return new URL(this.icon);
      } catch {
        return false;
      }
    },
    isMultiselectType() {
      return this.type === "multiselect";
    },
    nativeDatePickerType() {
      switch (this.type) {
        case "date":
        case "month":
        case "time":
        case "week":
        case "datetime-local":
          return this.type;
      }
      return false;
    },
    datePickerType() {
      if (!this.isNativePicker) {
        switch (this.type) {
          case "date":
          case "month":
          case "time":
            return this.type;
          case "datetime-local":
            return "datetime";
        }
      }
      return false;
    },
    /**
     * determines if the action is focusable
     *
     * @return {boolean} is the action focusable ?
     */
    isFocusable() {
      return !this.disabled;
    }
  },
  methods: {
    // closing datepicker popup on mouseleave = unfocus
    onLeave() {
      if (this.$refs.datetimepicker && this.$refs.datetimepicker.$refs.datepicker) {
        this.$refs.datetimepicker.$refs.datepicker.closePopup();
      }
    },
    onSubmit(event) {
      event.preventDefault();
      event.stopPropagation();
      if (!this.disabled) {
        this.$emit("submit", event);
      } else {
        return false;
      }
    },
    onUpdateModelValue(event) {
      this.$emit("update:modelValue", event);
    }
  }
};
const _hoisted_1 = { class: "action-input__icon-wrapper" };
const _hoisted_2 = ["disabled"];
const _hoisted_3 = { class: "action-input__container" };
const _hoisted_4 = ["for"];
const _hoisted_5 = { class: "action-input__input-container" };
const _hoisted_6 = {
  key: 4,
  class: "action-input__container"
};
const _hoisted_7 = ["for"];
const _hoisted_8 = { class: "action-input__input-container" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcDateTimePicker = resolveComponent("NcDateTimePicker");
  const _component_NcDateTimePickerNative = resolveComponent("NcDateTimePickerNative");
  const _component_NcSelect = resolveComponent("NcSelect");
  const _component_NcPasswordField = resolveComponent("NcPasswordField");
  const _component_NcColorPicker = resolveComponent("NcColorPicker");
  const _component_NcTextField = resolveComponent("NcTextField");
  return openBlock(), createElementBlock("li", {
    class: normalizeClass(["action", [{ "action--disabled": $props.disabled }, _ctx.$props.class]])
  }, [
    createBaseVNode("span", {
      class: normalizeClass(["action-input", {
        "action-input-picker--disabled": $props.disabled,
        "action-input--visible-label": $props.labelOutside && $props.label
      }]),
      onMouseleave: _cache[3] || (_cache[3] = (...args) => $options.onLeave && $options.onLeave(...args))
    }, [
      createBaseVNode("span", _hoisted_1, [
        renderSlot(_ctx.$slots, "icon", {}, () => [
          createBaseVNode("span", {
            "aria-hidden": "true",
            class: normalizeClass(["action-input__icon", [$options.isIconUrl ? "action-input__icon--url" : $props.icon]]),
            style: normalizeStyle({ backgroundImage: $options.isIconUrl ? `url(${$props.icon})` : null })
          }, null, 6)
        ], true)
      ]),
      createBaseVNode("form", {
        ref: "form",
        class: "action-input__form",
        disabled: $props.disabled,
        onSubmit: _cache[2] || (_cache[2] = withModifiers((...args) => $options.onSubmit && $options.onSubmit(...args), ["prevent"]))
      }, [
        createBaseVNode("div", _hoisted_3, [
          $props.label && $props.labelOutside && !$props.isNativePicker ? (openBlock(), createElementBlock("label", {
            key: 0,
            class: normalizeClass(["action-input__text-label", { "action-input__text-label--hidden": !$props.labelOutside }]),
            for: $props.inputId
          }, toDisplayString($props.label), 11, _hoisted_4)) : createCommentVNode("", true),
          createBaseVNode("div", _hoisted_5, [
            $options.datePickerType ? (openBlock(), createBlock(_component_NcDateTimePicker, mergeProps({
              key: 0,
              ref: "datetimepicker",
              modelValue: $props.modelValue,
              style: { "z-index": "99999999999" },
              placeholder: _ctx.text,
              disabled: $props.disabled,
              type: $options.datePickerType,
              inputClass: ["mx-input", { focusable: $options.isFocusable }],
              class: "action-input__datetimepicker",
              appendToBody: ""
            }, _ctx.$attrs, { "onUpdate:modelValue": $options.onUpdateModelValue }), null, 16, ["modelValue", "placeholder", "disabled", "type", "inputClass", "onUpdate:modelValue"])) : $props.isNativePicker ? (openBlock(), createBlock(_component_NcDateTimePickerNative, mergeProps({
              key: 1,
              id: $props.idNativeDateTimePicker,
              modelValue: $props.modelValue,
              label: $props.label,
              type: $options.nativeDatePickerType,
              inputClass: { focusable: $options.isFocusable },
              class: "action-input__datetimepicker"
            }, _ctx.$attrs, { "onUpdate:modelValue": $options.onUpdateModelValue }), null, 16, ["id", "modelValue", "label", "type", "inputClass", "onUpdate:modelValue"])) : $options.isMultiselectType ? (openBlock(), createBlock(_component_NcSelect, mergeProps({
              key: 2,
              modelValue: $props.modelValue,
              placeholder: _ctx.text,
              disabled: $props.disabled,
              appendToBody: false,
              inputClass: { focusable: $options.isFocusable },
              class: "action-input__multi"
            }, _ctx.$attrs, { "onUpdate:modelValue": $options.onUpdateModelValue }), null, 16, ["modelValue", "placeholder", "disabled", "inputClass", "onUpdate:modelValue"])) : $props.type === "password" ? (openBlock(), createBlock(_component_NcPasswordField, mergeProps({
              key: 3,
              id: $props.inputId,
              modelValue: $props.modelValue,
              label: $props.label,
              labelOutside: !$props.label || $props.labelOutside,
              placeholder: _ctx.text,
              disabled: $props.disabled,
              inputClass: { focusable: $options.isFocusable },
              showTrailingButton: $props.showTrailingButton && !$props.disabled
            }, _ctx.$attrs, { "onUpdate:modelValue": $options.onUpdateModelValue }), null, 16, ["id", "modelValue", "label", "labelOutside", "placeholder", "disabled", "inputClass", "showTrailingButton", "onUpdate:modelValue"])) : $props.type === "color" ? (openBlock(), createElementBlock("div", _hoisted_6, [
              $props.label && $props.type === "color" ? (openBlock(), createElementBlock("label", {
                key: 0,
                class: normalizeClass(["action-input__text-label", { "action-input__text-label--hidden": !$props.labelOutside }]),
                for: $props.inputId
              }, toDisplayString($props.label), 11, _hoisted_7)) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_8, [
                createVNode(_component_NcColorPicker, mergeProps({
                  id: "inputId",
                  modelValue: $props.modelValue,
                  class: "colorpicker__trigger"
                }, _ctx.$attrs, {
                  "onUpdate:modelValue": $options.onUpdateModelValue,
                  onSubmit: _cache[0] || (_cache[0] = ($event) => _ctx.$refs.form.requestSubmit())
                }), {
                  default: withCtx(() => [
                    createBaseVNode("button", {
                      class: normalizeClass(["colorpicker__preview", { focusable: $options.isFocusable }]),
                      style: normalizeStyle({ "background-color": $props.modelValue })
                    }, null, 6)
                  ]),
                  _: 1
                }, 16, ["modelValue", "onUpdate:modelValue"])
              ])
            ])) : (openBlock(), createBlock(_component_NcTextField, mergeProps({
              key: 5,
              id: $props.inputId,
              modelValue: $props.modelValue,
              label: $props.label,
              labelOutside: !$props.label || $props.labelOutside,
              placeholder: _ctx.text,
              disabled: $props.disabled,
              inputClass: { focusable: $options.isFocusable },
              type: $props.type,
              trailingButtonIcon: "arrowRight",
              trailingButtonLabel: $props.trailingButtonLabel,
              showTrailingButton: $props.showTrailingButton && !$props.disabled
            }, _ctx.$attrs, {
              onTrailingButtonClick: _cache[1] || (_cache[1] = ($event) => _ctx.$refs.form.requestSubmit()),
              "onUpdate:modelValue": $options.onUpdateModelValue
            }), null, 16, ["id", "modelValue", "label", "labelOutside", "placeholder", "disabled", "inputClass", "type", "trailingButtonLabel", "showTrailingButton", "onUpdate:modelValue"]))
          ])
        ])
      ], 40, _hoisted_2)
    ], 34)
  ], 2);
}
const NcActionInput = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-3a83acfa"]]);
var eventemitter3 = { exports: {} };
var hasRequiredEventemitter3;
function requireEventemitter3() {
  if (hasRequiredEventemitter3) return eventemitter3.exports;
  hasRequiredEventemitter3 = 1;
  (function(module) {
    var has = Object.prototype.hasOwnProperty, prefix = "~";
    function Events() {
    }
    if (Object.create) {
      Events.prototype = /* @__PURE__ */ Object.create(null);
      if (!new Events().__proto__) prefix = false;
    }
    function EE(fn, context, once) {
      this.fn = fn;
      this.context = context;
      this.once = once || false;
    }
    function addListener(emitter, event, fn, context, once) {
      if (typeof fn !== "function") {
        throw new TypeError("The listener must be a function");
      }
      var listener = new EE(fn, context || emitter, once), evt = prefix ? prefix + event : event;
      if (!emitter._events[evt]) emitter._events[evt] = listener, emitter._eventsCount++;
      else if (!emitter._events[evt].fn) emitter._events[evt].push(listener);
      else emitter._events[evt] = [emitter._events[evt], listener];
      return emitter;
    }
    function clearEvent(emitter, evt) {
      if (--emitter._eventsCount === 0) emitter._events = new Events();
      else delete emitter._events[evt];
    }
    function EventEmitter2() {
      this._events = new Events();
      this._eventsCount = 0;
    }
    EventEmitter2.prototype.eventNames = function eventNames() {
      var names = [], events, name;
      if (this._eventsCount === 0) return names;
      for (name in events = this._events) {
        if (has.call(events, name)) names.push(prefix ? name.slice(1) : name);
      }
      if (Object.getOwnPropertySymbols) {
        return names.concat(Object.getOwnPropertySymbols(events));
      }
      return names;
    };
    EventEmitter2.prototype.listeners = function listeners(event) {
      var evt = prefix ? prefix + event : event, handlers = this._events[evt];
      if (!handlers) return [];
      if (handlers.fn) return [handlers.fn];
      for (var i = 0, l = handlers.length, ee = new Array(l); i < l; i++) {
        ee[i] = handlers[i].fn;
      }
      return ee;
    };
    EventEmitter2.prototype.listenerCount = function listenerCount(event) {
      var evt = prefix ? prefix + event : event, listeners = this._events[evt];
      if (!listeners) return 0;
      if (listeners.fn) return 1;
      return listeners.length;
    };
    EventEmitter2.prototype.emit = function emit(event, a1, a2, a3, a4, a5) {
      var evt = prefix ? prefix + event : event;
      if (!this._events[evt]) return false;
      var listeners = this._events[evt], len = arguments.length, args, i;
      if (listeners.fn) {
        if (listeners.once) this.removeListener(event, listeners.fn, void 0, true);
        switch (len) {
          case 1:
            return listeners.fn.call(listeners.context), true;
          case 2:
            return listeners.fn.call(listeners.context, a1), true;
          case 3:
            return listeners.fn.call(listeners.context, a1, a2), true;
          case 4:
            return listeners.fn.call(listeners.context, a1, a2, a3), true;
          case 5:
            return listeners.fn.call(listeners.context, a1, a2, a3, a4), true;
          case 6:
            return listeners.fn.call(listeners.context, a1, a2, a3, a4, a5), true;
        }
        for (i = 1, args = new Array(len - 1); i < len; i++) {
          args[i - 1] = arguments[i];
        }
        listeners.fn.apply(listeners.context, args);
      } else {
        var length = listeners.length, j;
        for (i = 0; i < length; i++) {
          if (listeners[i].once) this.removeListener(event, listeners[i].fn, void 0, true);
          switch (len) {
            case 1:
              listeners[i].fn.call(listeners[i].context);
              break;
            case 2:
              listeners[i].fn.call(listeners[i].context, a1);
              break;
            case 3:
              listeners[i].fn.call(listeners[i].context, a1, a2);
              break;
            case 4:
              listeners[i].fn.call(listeners[i].context, a1, a2, a3);
              break;
            default:
              if (!args) for (j = 1, args = new Array(len - 1); j < len; j++) {
                args[j - 1] = arguments[j];
              }
              listeners[i].fn.apply(listeners[i].context, args);
          }
        }
      }
      return true;
    };
    EventEmitter2.prototype.on = function on(event, fn, context) {
      return addListener(this, event, fn, context, false);
    };
    EventEmitter2.prototype.once = function once(event, fn, context) {
      return addListener(this, event, fn, context, true);
    };
    EventEmitter2.prototype.removeListener = function removeListener(event, fn, context, once) {
      var evt = prefix ? prefix + event : event;
      if (!this._events[evt]) return this;
      if (!fn) {
        clearEvent(this, evt);
        return this;
      }
      var listeners = this._events[evt];
      if (listeners.fn) {
        if (listeners.fn === fn && (!once || listeners.once) && (!context || listeners.context === context)) {
          clearEvent(this, evt);
        }
      } else {
        for (var i = 0, events = [], length = listeners.length; i < length; i++) {
          if (listeners[i].fn !== fn || once && !listeners[i].once || context && listeners[i].context !== context) {
            events.push(listeners[i]);
          }
        }
        if (events.length) this._events[evt] = events.length === 1 ? events[0] : events;
        else clearEvent(this, evt);
      }
      return this;
    };
    EventEmitter2.prototype.removeAllListeners = function removeAllListeners(event) {
      var evt;
      if (event) {
        evt = prefix ? prefix + event : event;
        if (this._events[evt]) clearEvent(this, evt);
      } else {
        this._events = new Events();
        this._eventsCount = 0;
      }
      return this;
    };
    EventEmitter2.prototype.off = EventEmitter2.prototype.removeListener;
    EventEmitter2.prototype.addListener = EventEmitter2.prototype.on;
    EventEmitter2.prefixed = prefix;
    EventEmitter2.EventEmitter = EventEmitter2;
    {
      module.exports = EventEmitter2;
    }
  })(eventemitter3);
  return eventemitter3.exports;
}
var eventemitter3Exports = requireEventemitter3();
const EventEmitter = /* @__PURE__ */ getDefaultExportFromCjs(eventemitter3Exports);
class TimeoutError extends Error {
  name = "TimeoutError";
  constructor(message, options) {
    super(message, options);
    Error.captureStackTrace?.(this, TimeoutError);
  }
}
const getAbortedReason = (signal) => signal.reason ?? new DOMException("This operation was aborted.", "AbortError");
function pTimeout(promise, options) {
  const {
    milliseconds,
    fallback,
    message,
    customTimers = { setTimeout, clearTimeout },
    signal
  } = options;
  let timer;
  let abortHandler;
  const wrappedPromise = new Promise((resolve, reject) => {
    if (typeof milliseconds !== "number" || Math.sign(milliseconds) !== 1) {
      throw new TypeError(`Expected \`milliseconds\` to be a positive number, got \`${milliseconds}\``);
    }
    if (signal?.aborted) {
      reject(getAbortedReason(signal));
      return;
    }
    if (signal) {
      abortHandler = () => {
        reject(getAbortedReason(signal));
      };
      signal.addEventListener("abort", abortHandler, { once: true });
    }
    promise.then(resolve, reject);
    if (milliseconds === Number.POSITIVE_INFINITY) {
      return;
    }
    const timeoutError = new TimeoutError();
    timer = customTimers.setTimeout.call(void 0, () => {
      if (fallback) {
        try {
          resolve(fallback());
        } catch (error) {
          reject(error);
        }
        return;
      }
      if (typeof promise.cancel === "function") {
        promise.cancel();
      }
      if (message === false) {
        resolve();
      } else if (message instanceof Error) {
        reject(message);
      } else {
        timeoutError.message = message ?? `Promise timed out after ${milliseconds} milliseconds`;
        reject(timeoutError);
      }
    }, milliseconds);
  });
  const cancelablePromise = wrappedPromise.finally(() => {
    cancelablePromise.clear();
    if (abortHandler && signal) {
      signal.removeEventListener("abort", abortHandler);
    }
  });
  cancelablePromise.clear = () => {
    customTimers.clearTimeout.call(void 0, timer);
    timer = void 0;
  };
  return cancelablePromise;
}
function lowerBound(array, value, comparator) {
  let first = 0;
  let count = array.length;
  while (count > 0) {
    const step = Math.trunc(count / 2);
    let it = first + step;
    if (comparator(array[it], value) <= 0) {
      first = ++it;
      count -= step + 1;
    } else {
      count = step;
    }
  }
  return first;
}
class PriorityQueue {
  #queue = [];
  enqueue(run, options) {
    const { priority = 0, id } = options ?? {};
    const element = {
      priority,
      id,
      run
    };
    if (this.size === 0 || this.#queue[this.size - 1].priority >= priority) {
      this.#queue.push(element);
      return;
    }
    const index2 = lowerBound(this.#queue, element, (a, b) => b.priority - a.priority);
    this.#queue.splice(index2, 0, element);
  }
  setPriority(id, priority) {
    const index2 = this.#queue.findIndex((element) => element.id === id);
    if (index2 === -1) {
      throw new ReferenceError(`No promise function with the id "${id}" exists in the queue.`);
    }
    const [item] = this.#queue.splice(index2, 1);
    this.enqueue(item.run, { priority, id });
  }
  dequeue() {
    const item = this.#queue.shift();
    return item?.run;
  }
  filter(options) {
    return this.#queue.filter((element) => element.priority === options.priority).map((element) => element.run);
  }
  get size() {
    return this.#queue.length;
  }
}
class PQueue extends EventEmitter {
  #carryoverIntervalCount;
  #isIntervalIgnored;
  #intervalCount = 0;
  #intervalCap;
  #rateLimitedInInterval = false;
  #rateLimitFlushScheduled = false;
  #interval;
  #intervalEnd = 0;
  #lastExecutionTime = 0;
  #intervalId;
  #timeoutId;
  #strict;
  // Circular buffer implementation for better performance
  #strictTicks = [];
  #strictTicksStartIndex = 0;
  #queue;
  #queueClass;
  #pending = 0;
  // The `!` is needed because of https://github.com/microsoft/TypeScript/issues/32194
  #concurrency;
  #isPaused;
  // Use to assign a unique identifier to a promise function, if not explicitly specified
  #idAssigner = 1n;
  // Track currently running tasks for debugging
  #runningTasks = /* @__PURE__ */ new Map();
  /**
      Get or set the default timeout for all tasks. Can be changed at runtime.
  
      Operations will throw a `TimeoutError` if they don't complete within the specified time.
  
      The timeout begins when the operation is dequeued and starts execution, not while it's waiting in the queue.
  
      @example
      ```
      const queue = new PQueue({timeout: 5000});
  
      // Change timeout for all future tasks
      queue.timeout = 10000;
      ```
      */
  timeout;
  constructor(options) {
    super();
    options = {
      carryoverIntervalCount: false,
      intervalCap: Number.POSITIVE_INFINITY,
      interval: 0,
      concurrency: Number.POSITIVE_INFINITY,
      autoStart: true,
      queueClass: PriorityQueue,
      strict: false,
      ...options
    };
    if (!(typeof options.intervalCap === "number" && options.intervalCap >= 1)) {
      throw new TypeError(`Expected \`intervalCap\` to be a number from 1 and up, got \`${options.intervalCap?.toString() ?? ""}\` (${typeof options.intervalCap})`);
    }
    if (options.interval === void 0 || !(Number.isFinite(options.interval) && options.interval >= 0)) {
      throw new TypeError(`Expected \`interval\` to be a finite number >= 0, got \`${options.interval?.toString() ?? ""}\` (${typeof options.interval})`);
    }
    if (options.strict && options.interval === 0) {
      throw new TypeError("The `strict` option requires a non-zero `interval`");
    }
    if (options.strict && options.intervalCap === Number.POSITIVE_INFINITY) {
      throw new TypeError("The `strict` option requires a finite `intervalCap`");
    }
    this.#carryoverIntervalCount = options.carryoverIntervalCount ?? options.carryoverConcurrencyCount ?? false;
    this.#isIntervalIgnored = options.intervalCap === Number.POSITIVE_INFINITY || options.interval === 0;
    this.#intervalCap = options.intervalCap;
    this.#interval = options.interval;
    this.#strict = options.strict;
    this.#queue = new options.queueClass();
    this.#queueClass = options.queueClass;
    this.concurrency = options.concurrency;
    if (options.timeout !== void 0 && !(Number.isFinite(options.timeout) && options.timeout > 0)) {
      throw new TypeError(`Expected \`timeout\` to be a positive finite number, got \`${options.timeout}\` (${typeof options.timeout})`);
    }
    this.timeout = options.timeout;
    this.#isPaused = options.autoStart === false;
    this.#setupRateLimitTracking();
  }
  #cleanupStrictTicks(now) {
    while (this.#strictTicksStartIndex < this.#strictTicks.length) {
      const oldestTick = this.#strictTicks[this.#strictTicksStartIndex];
      if (oldestTick !== void 0 && now - oldestTick >= this.#interval) {
        this.#strictTicksStartIndex++;
      } else {
        break;
      }
    }
    const shouldCompact = this.#strictTicksStartIndex > 100 && this.#strictTicksStartIndex > this.#strictTicks.length / 2 || this.#strictTicksStartIndex === this.#strictTicks.length;
    if (shouldCompact) {
      this.#strictTicks = this.#strictTicks.slice(this.#strictTicksStartIndex);
      this.#strictTicksStartIndex = 0;
    }
  }
  // Helper methods for interval consumption
  #consumeIntervalSlot(now) {
    if (this.#strict) {
      this.#strictTicks.push(now);
    } else {
      this.#intervalCount++;
    }
  }
  #rollbackIntervalSlot() {
    if (this.#strict) {
      if (this.#strictTicks.length > this.#strictTicksStartIndex) {
        this.#strictTicks.pop();
      }
    } else if (this.#intervalCount > 0) {
      this.#intervalCount--;
    }
  }
  #getActiveTicksCount() {
    return this.#strictTicks.length - this.#strictTicksStartIndex;
  }
  get #doesIntervalAllowAnother() {
    if (this.#isIntervalIgnored) {
      return true;
    }
    if (this.#strict) {
      return this.#getActiveTicksCount() < this.#intervalCap;
    }
    return this.#intervalCount < this.#intervalCap;
  }
  get #doesConcurrentAllowAnother() {
    return this.#pending < this.#concurrency;
  }
  #next() {
    this.#pending--;
    if (this.#pending === 0) {
      this.emit("pendingZero");
    }
    this.#tryToStartAnother();
    this.emit("next");
  }
  #onResumeInterval() {
    this.#timeoutId = void 0;
    this.#onInterval();
    this.#initializeIntervalIfNeeded();
  }
  #isIntervalPausedAt(now) {
    if (this.#strict) {
      this.#cleanupStrictTicks(now);
      const activeTicksCount = this.#getActiveTicksCount();
      if (activeTicksCount >= this.#intervalCap) {
        const oldestTick = this.#strictTicks[this.#strictTicksStartIndex];
        const delay = this.#interval - (now - oldestTick);
        this.#createIntervalTimeout(delay);
        return true;
      }
      return false;
    }
    if (this.#intervalId === void 0) {
      const delay = this.#intervalEnd - now;
      if (delay < 0) {
        if (this.#lastExecutionTime > 0) {
          const timeSinceLastExecution = now - this.#lastExecutionTime;
          if (timeSinceLastExecution < this.#interval) {
            this.#createIntervalTimeout(this.#interval - timeSinceLastExecution);
            return true;
          }
        }
        this.#intervalCount = this.#carryoverIntervalCount ? this.#pending : 0;
      } else {
        this.#createIntervalTimeout(delay);
        return true;
      }
    }
    return false;
  }
  #createIntervalTimeout(delay) {
    if (this.#timeoutId !== void 0) {
      return;
    }
    this.#timeoutId = setTimeout(() => {
      this.#onResumeInterval();
    }, delay);
  }
  #clearIntervalTimer() {
    if (this.#intervalId) {
      clearInterval(this.#intervalId);
      this.#intervalId = void 0;
    }
  }
  #clearTimeoutTimer() {
    if (this.#timeoutId) {
      clearTimeout(this.#timeoutId);
      this.#timeoutId = void 0;
    }
  }
  #tryToStartAnother() {
    if (this.#queue.size === 0) {
      this.#clearIntervalTimer();
      this.emit("empty");
      if (this.#pending === 0) {
        this.#clearTimeoutTimer();
        if (this.#strict && this.#strictTicksStartIndex > 0) {
          const now = Date.now();
          this.#cleanupStrictTicks(now);
        }
        this.emit("idle");
      }
      return false;
    }
    let taskStarted = false;
    if (!this.#isPaused) {
      const now = Date.now();
      const canInitializeInterval = !this.#isIntervalPausedAt(now);
      if (this.#doesIntervalAllowAnother && this.#doesConcurrentAllowAnother) {
        const job = this.#queue.dequeue();
        if (!this.#isIntervalIgnored) {
          this.#consumeIntervalSlot(now);
          this.#scheduleRateLimitUpdate();
        }
        this.emit("active");
        job();
        if (canInitializeInterval) {
          this.#initializeIntervalIfNeeded();
        }
        taskStarted = true;
      }
    }
    return taskStarted;
  }
  #initializeIntervalIfNeeded() {
    if (this.#isIntervalIgnored || this.#intervalId !== void 0) {
      return;
    }
    if (this.#strict) {
      return;
    }
    this.#intervalId = setInterval(() => {
      this.#onInterval();
    }, this.#interval);
    this.#intervalEnd = Date.now() + this.#interval;
  }
  #onInterval() {
    if (!this.#strict) {
      if (this.#intervalCount === 0 && this.#pending === 0 && this.#intervalId) {
        this.#clearIntervalTimer();
      }
      this.#intervalCount = this.#carryoverIntervalCount ? this.#pending : 0;
    }
    this.#processQueue();
    this.#scheduleRateLimitUpdate();
  }
  /**
  Executes all queued functions until it reaches the limit.
  */
  #processQueue() {
    while (this.#tryToStartAnother()) {
    }
  }
  get concurrency() {
    return this.#concurrency;
  }
  set concurrency(newConcurrency) {
    if (!(typeof newConcurrency === "number" && newConcurrency >= 1)) {
      throw new TypeError(`Expected \`concurrency\` to be a number from 1 and up, got \`${newConcurrency}\` (${typeof newConcurrency})`);
    }
    this.#concurrency = newConcurrency;
    this.#processQueue();
  }
  /**
      Updates the priority of a promise function by its id, affecting its execution order. Requires a defined concurrency limit to take effect.
  
      For example, this can be used to prioritize a promise function to run earlier.
  
      ```js
      import PQueue from 'p-queue';
  
      const queue = new PQueue({concurrency: 1});
  
      queue.add(async () => '🦄', {priority: 1});
      queue.add(async () => '🦀', {priority: 0, id: '🦀'});
      queue.add(async () => '🦄', {priority: 1});
      queue.add(async () => '🦄', {priority: 1});
  
      queue.setPriority('🦀', 2);
      ```
  
      In this case, the promise function with `id: '🦀'` runs second.
  
      You can also deprioritize a promise function to delay its execution:
  
      ```js
      import PQueue from 'p-queue';
  
      const queue = new PQueue({concurrency: 1});
  
      queue.add(async () => '🦄', {priority: 1});
      queue.add(async () => '🦀', {priority: 1, id: '🦀'});
      queue.add(async () => '🦄');
      queue.add(async () => '🦄', {priority: 0});
  
      queue.setPriority('🦀', -1);
      ```
      Here, the promise function with `id: '🦀'` executes last.
      */
  setPriority(id, priority) {
    if (typeof priority !== "number" || !Number.isFinite(priority)) {
      throw new TypeError(`Expected \`priority\` to be a finite number, got \`${priority}\` (${typeof priority})`);
    }
    this.#queue.setPriority(id, priority);
  }
  async add(function_, options = {}) {
    options = {
      timeout: this.timeout,
      ...options,
      // Assign unique ID if not provided
      id: options.id ?? (this.#idAssigner++).toString()
    };
    return new Promise((resolve, reject) => {
      const taskSymbol = /* @__PURE__ */ Symbol(`task-${options.id}`);
      this.#queue.enqueue(async () => {
        this.#pending++;
        this.#runningTasks.set(taskSymbol, {
          id: options.id,
          priority: options.priority ?? 0,
          // Match priority-queue default
          startTime: Date.now(),
          timeout: options.timeout
        });
        let eventListener;
        try {
          try {
            options.signal?.throwIfAborted();
          } catch (error) {
            this.#rollbackIntervalConsumption();
            this.#runningTasks.delete(taskSymbol);
            throw error;
          }
          this.#lastExecutionTime = Date.now();
          let operation = function_({ signal: options.signal });
          if (options.timeout) {
            operation = pTimeout(Promise.resolve(operation), {
              milliseconds: options.timeout,
              message: `Task timed out after ${options.timeout}ms (queue has ${this.#pending} running, ${this.#queue.size} waiting)`
            });
          }
          if (options.signal) {
            const { signal } = options;
            operation = Promise.race([operation, new Promise((_resolve, reject2) => {
              eventListener = () => {
                reject2(signal.reason);
              };
              signal.addEventListener("abort", eventListener, { once: true });
            })]);
          }
          const result = await operation;
          resolve(result);
          this.emit("completed", result);
        } catch (error) {
          reject(error);
          this.emit("error", error);
        } finally {
          if (eventListener) {
            options.signal?.removeEventListener("abort", eventListener);
          }
          this.#runningTasks.delete(taskSymbol);
          queueMicrotask(() => {
            this.#next();
          });
        }
      }, options);
      this.emit("add");
      this.#tryToStartAnother();
    });
  }
  async addAll(functions, options) {
    return Promise.all(functions.map(async (function_) => this.add(function_, options)));
  }
  /**
  Start (or resume) executing enqueued tasks within concurrency limit. No need to call this if queue is not paused (via `options.autoStart = false` or by `.pause()` method.)
  */
  start() {
    if (!this.#isPaused) {
      return this;
    }
    this.#isPaused = false;
    this.#processQueue();
    return this;
  }
  /**
  Put queue execution on hold.
  */
  pause() {
    this.#isPaused = true;
  }
  /**
  Clear the queue.
  */
  clear() {
    this.#queue = new this.#queueClass();
    this.#clearIntervalTimer();
    this.#updateRateLimitState();
    this.emit("empty");
    if (this.#pending === 0) {
      this.#clearTimeoutTimer();
      this.emit("idle");
    }
    this.emit("next");
  }
  /**
      Can be called multiple times. Useful if you for example add additional items at a later time.
  
      @returns A promise that settles when the queue becomes empty.
      */
  async onEmpty() {
    if (this.#queue.size === 0) {
      return;
    }
    await this.#onEvent("empty");
  }
  /**
      @returns A promise that settles when the queue size is less than the given limit: `queue.size < limit`.
  
      If you want to avoid having the queue grow beyond a certain size you can `await queue.onSizeLessThan()` before adding a new item.
  
      Note that this only limits the number of items waiting to start. There could still be up to `concurrency` jobs already running that this call does not include in its calculation.
      */
  async onSizeLessThan(limit) {
    if (this.#queue.size < limit) {
      return;
    }
    await this.#onEvent("next", () => this.#queue.size < limit);
  }
  /**
      The difference with `.onEmpty` is that `.onIdle` guarantees that all work from the queue has finished. `.onEmpty` merely signals that the queue is empty, but it could mean that some promises haven't completed yet.
  
      @returns A promise that settles when the queue becomes empty, and all promises have completed; `queue.size === 0 && queue.pending === 0`.
      */
  async onIdle() {
    if (this.#pending === 0 && this.#queue.size === 0) {
      return;
    }
    await this.#onEvent("idle");
  }
  /**
      The difference with `.onIdle` is that `.onPendingZero` only waits for currently running tasks to finish, ignoring queued tasks.
  
      @returns A promise that settles when all currently running tasks have completed; `queue.pending === 0`.
      */
  async onPendingZero() {
    if (this.#pending === 0) {
      return;
    }
    await this.#onEvent("pendingZero");
  }
  /**
  @returns A promise that settles when the queue becomes rate-limited due to intervalCap.
  */
  async onRateLimit() {
    if (this.isRateLimited) {
      return;
    }
    await this.#onEvent("rateLimit");
  }
  /**
  @returns A promise that settles when the queue is no longer rate-limited.
  */
  async onRateLimitCleared() {
    if (!this.isRateLimited) {
      return;
    }
    await this.#onEvent("rateLimitCleared");
  }
  /**
      @returns A promise that rejects when any task in the queue errors.
  
      Use with `Promise.race([queue.onError(), queue.onIdle()])` to fail fast on the first error while still resolving normally when the queue goes idle.
  
      Important: The promise returned by `add()` still rejects. You must handle each `add()` promise (for example, `.catch(() => {})`) to avoid unhandled rejections.
  
      @example
      ```
      import PQueue from 'p-queue';
  
      const queue = new PQueue({concurrency: 2});
  
      queue.add(() => fetchData(1)).catch(() => {});
      queue.add(() => fetchData(2)).catch(() => {});
      queue.add(() => fetchData(3)).catch(() => {});
  
      // Stop processing on first error
      try {
          await Promise.race([
              queue.onError(),
              queue.onIdle()
          ]);
      } catch (error) {
          queue.pause(); // Stop processing remaining tasks
          console.error('Queue failed:', error);
      }
      ```
      */
  // eslint-disable-next-line @typescript-eslint/promise-function-async
  onError() {
    return new Promise((_resolve, reject) => {
      const handleError = (error) => {
        this.off("error", handleError);
        reject(error);
      };
      this.on("error", handleError);
    });
  }
  async #onEvent(event, filter) {
    return new Promise((resolve) => {
      const listener = () => {
        if (filter && !filter()) {
          return;
        }
        this.off(event, listener);
        resolve();
      };
      this.on(event, listener);
    });
  }
  /**
  Size of the queue, the number of queued items waiting to run.
  */
  get size() {
    return this.#queue.size;
  }
  /**
      Size of the queue, filtered by the given options.
  
      For example, this can be used to find the number of items remaining in the queue with a specific priority level.
      */
  sizeBy(options) {
    return this.#queue.filter(options).length;
  }
  /**
  Number of running items (no longer in the queue).
  */
  get pending() {
    return this.#pending;
  }
  /**
  Whether the queue is currently paused.
  */
  get isPaused() {
    return this.#isPaused;
  }
  #setupRateLimitTracking() {
    if (this.#isIntervalIgnored) {
      return;
    }
    this.on("add", () => {
      if (this.#queue.size > 0) {
        this.#scheduleRateLimitUpdate();
      }
    });
    this.on("next", () => {
      this.#scheduleRateLimitUpdate();
    });
  }
  #scheduleRateLimitUpdate() {
    if (this.#isIntervalIgnored || this.#rateLimitFlushScheduled) {
      return;
    }
    this.#rateLimitFlushScheduled = true;
    queueMicrotask(() => {
      this.#rateLimitFlushScheduled = false;
      this.#updateRateLimitState();
    });
  }
  #rollbackIntervalConsumption() {
    if (this.#isIntervalIgnored) {
      return;
    }
    this.#rollbackIntervalSlot();
    this.#scheduleRateLimitUpdate();
  }
  #updateRateLimitState() {
    const previous = this.#rateLimitedInInterval;
    if (this.#isIntervalIgnored || this.#queue.size === 0) {
      if (previous) {
        this.#rateLimitedInInterval = false;
        this.emit("rateLimitCleared");
      }
      return;
    }
    let count;
    if (this.#strict) {
      const now = Date.now();
      this.#cleanupStrictTicks(now);
      count = this.#getActiveTicksCount();
    } else {
      count = this.#intervalCount;
    }
    const shouldBeRateLimited = count >= this.#intervalCap;
    if (shouldBeRateLimited !== previous) {
      this.#rateLimitedInInterval = shouldBeRateLimited;
      this.emit(shouldBeRateLimited ? "rateLimit" : "rateLimitCleared");
    }
  }
  /**
  Whether the queue is currently rate-limited due to intervalCap.
  */
  get isRateLimited() {
    return this.#rateLimitedInInterval;
  }
  /**
      Whether the queue is saturated. Returns `true` when:
      - All concurrency slots are occupied and tasks are waiting, OR
      - The queue is rate-limited and tasks are waiting
  
      Useful for detecting backpressure and potential hanging tasks.
  
      ```js
      import PQueue from 'p-queue';
  
      const queue = new PQueue({concurrency: 2});
  
      // Backpressure handling
      if (queue.isSaturated) {
          console.log('Queue is saturated, waiting for capacity...');
          await queue.onSizeLessThan(queue.concurrency);
      }
  
      // Monitoring for stuck tasks
      setInterval(() => {
          if (queue.isSaturated) {
              console.warn(`Queue saturated: ${queue.pending} running, ${queue.size} waiting`);
          }
      }, 60000);
      ```
      */
  get isSaturated() {
    return this.#pending === this.#concurrency && this.#queue.size > 0 || this.isRateLimited && this.#queue.size > 0;
  }
  /**
      The tasks currently being executed. Each task includes its `id`, `priority`, `startTime`, and `timeout` (if set).
  
      Returns an array of task info objects.
  
      ```js
      import PQueue from 'p-queue';
  
      const queue = new PQueue({concurrency: 2});
  
      // Add tasks with IDs for better debugging
      queue.add(() => fetchUser(123), {id: 'user-123'});
      queue.add(() => fetchPosts(456), {id: 'posts-456', priority: 1});
  
      // Check what's running
      console.log(queue.runningTasks);
      // => [{
      //   id: 'user-123',
      //   priority: 0,
      //   startTime: 1759253001716,
      //   timeout: undefined
      // }, {
      //   id: 'posts-456',
      //   priority: 1,
      //   startTime: 1759253001916,
      //   timeout: undefined
      // }]
      ```
      */
  get runningTasks() {
    return [...this.#runningTasks.values()].map((task) => ({ ...task }));
  }
}
const index = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  default: NcSelect
}, Symbol.toStringTag, { value: "Module" }));
export {
  NcDateTimePickerNative as N,
  PQueue as P,
  NcActionInput as a
};
//# sourceMappingURL=index-CZV8rpGu.chunk.mjs.map
