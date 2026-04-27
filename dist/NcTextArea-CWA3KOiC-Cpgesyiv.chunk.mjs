const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, k as useModel, s as useSlots, l as useTemplateRef, z as watch, o as openBlock, f as createElementBlock, x as createVNode, w as withCtx, u as unref, v as normalizeClass, i as renderSlot, h as createCommentVNode, q as mergeModels, y as ref, P as nextTick, r as resolveComponent, m as mergeProps, g as createBaseVNode, c as createBlock, t as toDisplayString, N as normalizeStyle, D as useAttrs, n as computed, j as createTextVNode } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { r as register, B as t20, C as t14, D as t21, t as t50, E as t23, F as t43, s as t16, b as t, G as t46, H as t47, I as t31, J as t15, K as t28, L as t6, M as t35, O as t7, _ as _export_sfc, N as NcIconSvgWrapper, P as t38, Q as t41, R as t9, S as t49, c as createElementId } from "./Web-BOM4en5n.chunk.mjs";
import { d as debounce } from "./index-rAufP352.chunk.mjs";
import "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { l as logger, i as isLegacy, e as mdiCheck, f as mdiAlertCircleOutline } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { a as NcActions, G as useFocusWithin } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { I as IconClose, _ as _sfc_main$3, N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import { N as NcInputField } from "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import { P as PQueue } from "./index-CZV8rpGu.chunk.mjs";
import { a as userStatus, N as NcAvatar } from "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import { g as getCapabilities } from "./index-o76qk6sn.chunk.mjs";
register();
register(t20);
({
  props: {
    /**
     * Any [NcActions](#/Components/NcActions?id=ncactions-1) prop
     */
    // Not an actual prop but needed to show in vue-styleguidist docs
    ...NcActions.props
  }
});
register(t14);
register(t21);
register(t23, t50);
register(t16, t43);
/* @__PURE__ */ defineComponent({
  __name: "NcAppNavigationSearch",
  props: /* @__PURE__ */ mergeModels({
    /**
     * Text used to label the search input
     */
    label: {
      type: String,
      default: t("Search …")
    },
    /**
     * Placeholder of the search input
     * By default the value of `label` is used.
     */
    placeholder: {
      type: String,
      default: null
    }
  }, {
    "modelValue": { default: "" },
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props) {
    const model = useModel(__props, "modelValue");
    const slots = useSlots();
    const inputElement = ref();
    const { focused: inputHasFocus } = useFocusWithin(inputElement);
    const transitionTimeout = Number.parseInt(window.getComputedStyle(window.document.body).getPropertyValue("--animation-quick")) || 100;
    const actionsContainerElement = useTemplateRef("actionsContainer");
    const hasActions = () => !!slots.actions?.({});
    const showActions = ref(true);
    const timeoutId = ref();
    const hideActions = ref(false);
    watch(inputHasFocus, () => {
      showActions.value = !inputHasFocus.value;
      window.clearTimeout(timeoutId.value);
      if (showActions.value) {
        hideActions.value = false;
      } else {
        window.setTimeout(() => {
          hideActions.value = !showActions.value;
        }, transitionTimeout);
      }
    });
    function onCloseSearch() {
      model.value = "";
      if (hasActions()) {
        showActions.value = true;
        nextTick(() => actionsContainerElement.value?.querySelector("button")?.focus());
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["app-navigation-search", {
          "app-navigation-search--has-actions": hasActions()
        }])
      }, [
        createVNode(NcInputField, {
          ref_key: "inputElement",
          ref: inputElement,
          modelValue: model.value,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => model.value = $event),
          "aria-label": __props.label,
          class: "app-navigation-search__input",
          labelOutside: "",
          placeholder: __props.placeholder ?? __props.label,
          showTrailingButton: "",
          trailingButtonLabel: unref(t)("Clear search"),
          type: "search",
          onTrailingButtonClick: onCloseSearch
        }, {
          "trailing-button-icon": withCtx(() => [
            createVNode(IconClose, { size: 20 })
          ]),
          _: 1
        }, 8, ["modelValue", "aria-label", "placeholder", "trailingButtonLabel"]),
        hasActions() ? (openBlock(), createElementBlock("div", {
          key: 0,
          ref: "actionsContainer",
          class: normalizeClass(["app-navigation-search__actions", {
            "app-navigation-search__actions--hidden": !showActions.value,
            "hidden-visually": hideActions.value
          }])
        }, [
          renderSlot(_ctx.$slots, "actions", {}, void 0, true)
        ], 2)) : createCommentVNode("", true)
      ], 2);
    };
  }
});
register(t46);
register(t47);
register(t31);
const LOCALHOST = "LOCALHOST";
const COLON = "COLON";
const defaults = {
  defaultProtocol: "http",
  events: null,
  format: noop,
  formatHref: noop,
  nl2br: false,
  tagName: "a",
  target: null,
  rel: null,
  validate: true,
  truncate: Infinity,
  className: null,
  attributes: null,
  ignoreTags: [],
  render: null
};
function Options(opts, defaultRender = null) {
  let o = Object.assign({}, defaults);
  if (opts) {
    o = Object.assign(o, opts instanceof Options ? opts.o : opts);
  }
  const ignoredTags = o.ignoreTags;
  const uppercaseIgnoredTags = [];
  for (let i = 0; i < ignoredTags.length; i++) {
    uppercaseIgnoredTags.push(ignoredTags[i].toUpperCase());
  }
  this.o = o;
  if (defaultRender) {
    this.defaultRender = defaultRender;
  }
  this.ignoreTags = uppercaseIgnoredTags;
}
Options.prototype = {
  o: defaults,
  /**
   * @type string[]
   */
  ignoreTags: [],
  /**
   * @param {IntermediateRepresentation} ir
   * @returns {any}
   */
  defaultRender(ir) {
    return ir;
  },
  /**
   * Returns true or false based on whether a token should be displayed as a
   * link based on the user options.
   * @param {MultiToken} token
   * @returns {boolean}
   */
  check(token) {
    return this.get("validate", token.toString(), token);
  },
  // Private methods
  /**
   * Resolve an option's value based on the value of the option and the given
   * params. If operator and token are specified and the target option is
   * callable, automatically calls the function with the given argument.
   * @template {keyof Opts} K
   * @param {K} key Name of option to use
   * @param {string} [operator] will be passed to the target option if it's a
   * function. If not specified, RAW function value gets returned
   * @param {MultiToken} [token] The token from linkify.tokenize
   * @returns {Opts[K] | any}
   */
  get(key, operator, token) {
    const isCallable = operator != null;
    let option = this.o[key];
    if (!option) {
      return option;
    }
    if (typeof option === "object") {
      option = token.t in option ? option[token.t] : defaults[key];
      if (typeof option === "function" && isCallable) {
        option = option(operator, token);
      }
    } else if (typeof option === "function" && isCallable) {
      option = option(operator, token.t, token);
    }
    return option;
  },
  /**
   * @template {keyof Opts} L
   * @param {L} key Name of options object to use
   * @param {string} [operator]
   * @param {MultiToken} [token]
   * @returns {Opts[L] | any}
   */
  getObj(key, operator, token) {
    let obj = this.o[key];
    if (typeof obj === "function" && operator != null) {
      obj = obj(operator, token.t, token);
    }
    return obj;
  },
  /**
   * Convert the given token to a rendered element that may be added to the
   * calling-interface's DOM
   * @param {MultiToken} token Token to render to an HTML element
   * @returns {any} Render result; e.g., HTML string, DOM element, React
   *   Component, etc.
   */
  render(token) {
    const ir = token.render(this);
    const renderFn = this.get("render", null, token) || this.defaultRender;
    return renderFn(ir, token.t, token);
  }
};
function noop(val) {
  return val;
}
function MultiToken(value, tokens) {
  this.t = "token";
  this.v = value;
  this.tk = tokens;
}
MultiToken.prototype = {
  isLink: false,
  /**
   * Return the string this token represents.
   * @return {string}
   */
  toString() {
    return this.v;
  },
  /**
   * What should the value for this token be in the `href` HTML attribute?
   * Returns the `.toString` value by default.
   * @param {string} [scheme]
   * @return {string}
   */
  toHref(scheme) {
    return this.toString();
  },
  /**
   * @param {Options} options Formatting options
   * @returns {string}
   */
  toFormattedString(options) {
    const val = this.toString();
    const truncate = options.get("truncate", val, this);
    const formatted = options.get("format", val, this);
    return truncate && formatted.length > truncate ? formatted.substring(0, truncate) + "…" : formatted;
  },
  /**
   *
   * @param {Options} options
   * @returns {string}
   */
  toFormattedHref(options) {
    return options.get("formatHref", this.toHref(options.get("defaultProtocol")), this);
  },
  /**
   * The start index of this token in the original input string
   * @returns {number}
   */
  startIndex() {
    return this.tk[0].s;
  },
  /**
   * The end index of this token in the original input string (up to this
   * index but not including it)
   * @returns {number}
   */
  endIndex() {
    return this.tk[this.tk.length - 1].e;
  },
  /**
  	Returns an object  of relevant values for this token, which includes keys
  	* type - Kind of token ('url', 'email', etc.)
  	* value - Original text
  	* href - The value that should be added to the anchor tag's href
  		attribute
  		@method toObject
  	@param {string} [protocol] `'http'` by default
  */
  toObject(protocol = defaults.defaultProtocol) {
    return {
      type: this.t,
      value: this.toString(),
      isLink: this.isLink,
      href: this.toHref(protocol),
      start: this.startIndex(),
      end: this.endIndex()
    };
  },
  /**
   *
   * @param {Options} options Formatting option
   */
  toFormattedObject(options) {
    return {
      type: this.t,
      value: this.toFormattedString(options),
      isLink: this.isLink,
      href: this.toFormattedHref(options),
      start: this.startIndex(),
      end: this.endIndex()
    };
  },
  /**
   * Whether this token should be rendered as a link according to the given options
   * @param {Options} options
   * @returns {boolean}
   */
  validate(options) {
    return options.get("validate", this.toString(), this);
  },
  /**
   * Return an object that represents how this link should be rendered.
   * @param {Options} options Formattinng options
   */
  render(options) {
    const token = this;
    const href = this.toHref(options.get("defaultProtocol"));
    const formattedHref = options.get("formatHref", href, this);
    const tagName = options.get("tagName", href, token);
    const content = this.toFormattedString(options);
    const attributes = {};
    const className = options.get("className", href, token);
    const target = options.get("target", href, token);
    const rel = options.get("rel", href, token);
    const attrs = options.getObj("attributes", href, token);
    const eventListeners = options.getObj("events", href, token);
    attributes.href = formattedHref;
    if (className) {
      attributes.class = className;
    }
    if (target) {
      attributes.target = target;
    }
    if (rel) {
      attributes.rel = rel;
    }
    if (attrs) {
      Object.assign(attributes, attrs);
    }
    return {
      tagName,
      attributes,
      content,
      eventListeners
    };
  }
};
function createTokenClass(type, props) {
  class Token extends MultiToken {
    constructor(value, tokens) {
      super(value, tokens);
      this.t = type;
    }
  }
  for (const p in props) {
    Token.prototype[p] = props[p];
  }
  Token.t = type;
  return Token;
}
createTokenClass("email", {
  isLink: true,
  toHref() {
    return "mailto:" + this.toString();
  }
});
createTokenClass("text");
createTokenClass("nl");
createTokenClass("url", {
  isLink: true,
  /**
  	Lowercases relevant parts of the domain and adds the protocol if
  	required. Note that this will not escape unsafe HTML characters in the
  	URL.
  		@param {string} [scheme] default scheme (e.g., 'https')
  	@return {string} the full href
  */
  toHref(scheme = defaults.defaultProtocol) {
    return this.hasProtocol() ? this.v : `${scheme}://${this.v}`;
  },
  /**
   * Check whether this URL token has a protocol
   * @return {boolean}
   */
  hasProtocol() {
    const tokens = this.tk;
    return tokens.length >= 2 && tokens[0].t !== LOCALHOST && tokens[1].t === COLON;
  }
});
register(t15);
new PQueue({ concurrency: 5 });
register(t28);
register(t6);
register(t35);
({
  props: {
    /**
     * The text of show more button.
     *
     * Expected to be in the form "More {itemName} …"
     */
    showMoreLabel: {
      default: t("More items …")
    }
  }
});
register(t7);
register(t31);
const margin = 8;
const defaultSize = 32;
const _sfc_main$2 = {
  name: "NcListItemIcon",
  components: {
    NcAvatar,
    NcHighlight: _sfc_main$3,
    NcIconSvgWrapper
  },
  mixins: [
    userStatus
  ],
  props: {
    /**
     * Default first line text
     */
    name: {
      type: String,
      required: true
    },
    /**
     * Secondary optional line
     * Only visible on size of 32 and above
     */
    subname: {
      type: String,
      default: ""
    },
    /**
     * Icon class to be displayed at the end of the component
     */
    icon: {
      type: String,
      default: ""
    },
    /**
     * SVG icon to be displayed at the end of the component
     */
    iconSvg: {
      type: String,
      default: ""
    },
    /**
     * Descriptive name for the icon
     */
    iconName: {
      type: String,
      default: ""
    },
    /**
     * Search within the highlight of name/subname
     */
    search: {
      type: String,
      default: ""
    },
    /**
     * Set a size in px that will define the avatar height/width
     * and therefore, the height of the component
     */
    avatarSize: {
      type: Number,
      default: defaultSize
    },
    /**
     * Disable the margins of this component.
     * Useful for integration in `NcSelect` for example
     */
    noMargin: {
      type: Boolean,
      default: false
    },
    /**
     * See the [Avatar](#Avatar) displayName prop
     * Fallback to name
     */
    displayName: {
      type: String,
      default: null
    },
    /**
     * See the [Avatar](#Avatar) isNoUser prop
     * Enable/disable the UserStatus fetching
     */
    isNoUser: {
      type: Boolean,
      default: false
    },
    /**
     * Unique list item ID
     */
    id: {
      type: String,
      default: null
    }
  },
  setup() {
    return {
      margin,
      defaultSize
    };
  },
  computed: {
    hasIcon() {
      return this.icon !== "";
    },
    hasIconSvg() {
      return this.iconSvg !== "";
    },
    isValidSubname() {
      return this.subname?.trim?.() !== "";
    },
    isSizeBigEnough() {
      return this.avatarSize >= 26;
    },
    cssVars() {
      const margin2 = this.noMargin ? 0 : this.margin;
      return {
        "--height": this.avatarSize + 2 * margin2 + "px",
        "--margin": this.margin + "px"
      };
    },
    /**
     * Separates the search property into two parts, the first one is the search part on the name, the second on the subname.
     *
     * @return {[string, string]}
     */
    searchParts() {
      const EMAIL_NOTATION = /^([^<]*)<([^>]+)>?$/;
      const match = this.search.match(EMAIL_NOTATION);
      if (this.isNoUser || !match) {
        return [this.search, this.search];
      }
      return [match[1].trim(), match[2]];
    }
  },
  beforeMount() {
    if (!this.isNoUser && !this.subname) {
      this.fetchUserStatus(this.user);
    }
  }
};
const _hoisted_1$1 = ["id"];
const _hoisted_2$1 = { class: "option__details" };
const _hoisted_3$1 = { key: 1 };
const _hoisted_4$1 = ["aria-label"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcAvatar = resolveComponent("NcAvatar");
  const _component_NcHighlight = resolveComponent("NcHighlight");
  const _component_NcIconSvgWrapper = resolveComponent("NcIconSvgWrapper");
  return openBlock(), createElementBlock("span", {
    id: $props.id,
    class: normalizeClass(["option", { "option--compact": $props.avatarSize < $setup.defaultSize }]),
    style: normalizeStyle($options.cssVars)
  }, [
    createVNode(_component_NcAvatar, mergeProps(_ctx.$attrs, {
      disableMenu: "",
      disableTooltip: "",
      displayName: $props.displayName || $props.name,
      isNoUser: $props.isNoUser,
      size: $props.avatarSize,
      class: "option__avatar"
    }), null, 16, ["displayName", "isNoUser", "size"]),
    createBaseVNode("div", _hoisted_2$1, [
      createVNode(_component_NcHighlight, {
        class: "option__lineone",
        text: $props.name,
        search: $options.searchParts[0]
      }, null, 8, ["text", "search"]),
      $options.isValidSubname && $options.isSizeBigEnough ? (openBlock(), createBlock(_component_NcHighlight, {
        key: 0,
        class: "option__linetwo",
        text: $props.subname,
        search: $options.searchParts[1]
      }, null, 8, ["text", "search"])) : _ctx.hasStatus ? (openBlock(), createElementBlock("span", _hoisted_3$1, [
        createBaseVNode("span", null, toDisplayString(_ctx.userStatus.icon), 1),
        createBaseVNode("span", null, toDisplayString(_ctx.userStatus.message), 1)
      ])) : createCommentVNode("", true)
    ]),
    renderSlot(_ctx.$slots, "default", {}, () => [
      $options.hasIconSvg ? (openBlock(), createBlock(_component_NcIconSvgWrapper, {
        key: 0,
        class: "option__icon",
        svg: $props.iconSvg,
        name: $props.iconName
      }, null, 8, ["svg", "name"])) : $options.hasIcon ? (openBlock(), createElementBlock("span", {
        key: 1,
        class: normalizeClass(["icon option__icon", $props.icon]),
        "aria-label": $props.iconName
      }, null, 10, _hoisted_4$1)) : createCommentVNode("", true)
    ], true)
  ], 14, _hoisted_1$1);
}
const NcListItemIcon = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render], ["__scopeId", "data-v-0ee94269"]]);
Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-grid-baseline"));
Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-clickable-area"));
Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--clickable-area-small"));
register(t38);
register(t41);
getCapabilities()?.circles?.teamResourceProviders ?? [];
register(t9);
({
  /* eslint vue/require-prop-comment: warn -- TODO: Add a proper doc block about what this props do */
  props: {
    /**
     * Make the header name dynamic
     */
    header: {
      default: t("Related resources")
    },
    description: {
      default: t("Anything shared with the same group of people will show up here")
    }
  }
});
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "NcSelectUsers",
  props: /* @__PURE__ */ mergeModels({
    ariaLabelClearSelected: {},
    ariaLabelListbox: {},
    ariaLabelDeselectOption: { type: Function },
    disabled: { type: Boolean },
    inputId: {},
    inputLabel: {},
    labelOutside: { type: Boolean },
    keepOpen: { type: Boolean },
    loading: { type: Boolean },
    multiple: { type: Boolean },
    noWrap: { type: Boolean },
    options: {},
    placeholder: {},
    required: { type: Boolean }
  }, {
    "modelValue": {},
    "modelModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["search"], ["update:modelValue"]),
  setup(__props, { emit: __emit }) {
    const modelValue = useModel(__props, "modelValue");
    const emit = __emit;
    const search = ref("");
    watch(search, () => emit("search", search.value));
    const clickableArea = Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-clickable-area"));
    const gridBaseLine = Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-grid-baseline"));
    const avatarSize = clickableArea - 2 * gridBaseLine;
    function filterBy(option, label, search2) {
      const EMAIL_NOTATION = /[^<]*<([^>]+)/;
      const match = search2.match(EMAIL_NOTATION);
      const subname = option.subname?.toLocaleLowerCase() ?? "";
      return match && subname.indexOf(match[1].toLocaleLowerCase()) > -1 || `${label} ${option.subname}`.toLocaleLowerCase().indexOf(search2.toLocaleLowerCase()) > -1;
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcSelect), mergeProps({
        modelValue: modelValue.value,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => modelValue.value = $event),
        class: "nc-select-users"
      }, _ctx.$props, {
        filterBy,
        label: "displayName",
        onSearch: _cache[1] || (_cache[1] = ($event) => search.value = $event)
      }), {
        option: withCtx((option) => [
          createVNode(unref(NcListItemIcon), mergeProps(option, {
            avatarSize: 32,
            name: option.displayName,
            search: search.value
          }), null, 16, ["name", "search"])
        ]),
        "selected-option": withCtx((selectedOption) => [
          createVNode(unref(NcListItemIcon), mergeProps(selectedOption, {
            avatarSize,
            name: selectedOption.displayName,
            noMargin: "",
            search: search.value
          }), null, 16, ["name", "search"])
        ]),
        _: 1
      }, 16, ["modelValue"]);
    };
  }
});
const NcSelectUsers = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-e8e18fd3"]]);
register(t49);
({
  methods: {
    /**
     * Debounce the group search (reduce API calls)
     */
    onSearch: debounce(function(query) {
      this.loadGroup(query);
    }, 200)
  }
});
const _hoisted_1 = { class: "textarea__main-wrapper" };
const _hoisted_2 = ["id", "aria-describedby", "disabled", "placeholder", "value"];
const _hoisted_3 = ["for"];
const _hoisted_4 = ["id"];
const _sfc_main = /* @__PURE__ */ defineComponent({
  ...{ inheritAttrs: false },
  __name: "NcTextArea",
  props: /* @__PURE__ */ mergeModels({
    disabled: { type: Boolean },
    error: { type: Boolean },
    helperText: { default: void 0 },
    id: { default: () => createElementId() },
    inputClass: { default: "" },
    label: { default: void 0 },
    labelOutside: { type: Boolean },
    placeholder: { default: void 0 },
    resize: { default: "both" },
    success: { type: Boolean }
  }, {
    "modelValue": { required: true },
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
    const attrs = useAttrs();
    const textAreaElement = useTemplateRef("input");
    const internalPlaceholder = computed(() => props.placeholder || (isLegacy ? props.label : void 0));
    watch(() => props.labelOutside, () => {
      if (!props.labelOutside && !props.label) {
        logger.warn("[NcTextArea] You need to add a label to the NcInputField component. Either use the prop label or use an external one, as per the example in the documentation.");
      }
    });
    const ariaDescribedby = computed(() => {
      const ariaDescribedby2 = [];
      if (props.helperText) {
        ariaDescribedby2.push(`${props.id}-helper-text`);
      }
      if (typeof attrs["aria-describedby"] === "string") {
        ariaDescribedby2.push(attrs["aria-describedby"]);
      }
      return ariaDescribedby2.join(" ") || void 0;
    });
    function handleInput(event) {
      const { value } = event.target;
      modelValue.value = value;
    }
    function focus(options) {
      textAreaElement.value.focus(options);
    }
    function select() {
      textAreaElement.value.select();
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["textarea", [
          _ctx.$attrs.class,
          {
            "textarea--disabled": _ctx.disabled,
            "textarea--legacy": unref(isLegacy)
          }
        ]])
      }, [
        createBaseVNode("div", _hoisted_1, [
          createBaseVNode("textarea", mergeProps({ ..._ctx.$attrs, class: void 0 }, {
            id: _ctx.id,
            ref: "input",
            "aria-describedby": ariaDescribedby.value,
            "aria-live": "polite",
            class: ["textarea__input", [
              _ctx.inputClass,
              {
                "textarea__input--label-outside": _ctx.labelOutside,
                "textarea__input--legacy": unref(isLegacy),
                "textarea__input--success": _ctx.success,
                "textarea__input--error": _ctx.error
              }
            ]],
            disabled: _ctx.disabled,
            placeholder: internalPlaceholder.value,
            style: { resize: _ctx.resize },
            value: modelValue.value,
            onInput: handleInput
          }), null, 16, _hoisted_2),
          !_ctx.labelOutside ? (openBlock(), createElementBlock("label", {
            key: 0,
            class: "textarea__label",
            for: _ctx.id
          }, toDisplayString(_ctx.label), 9, _hoisted_3)) : createCommentVNode("", true)
        ]),
        _ctx.helperText ? (openBlock(), createElementBlock("p", {
          key: 0,
          id: `${_ctx.id}-helper-text`,
          class: normalizeClass(["textarea__helper-text-message", {
            "textarea__helper-text-message--error": _ctx.error,
            "textarea__helper-text-message--success": _ctx.success
          }])
        }, [
          _ctx.success ? (openBlock(), createBlock(NcIconSvgWrapper, {
            key: 0,
            class: "textarea__helper-text-message__icon",
            path: unref(mdiCheck),
            inline: ""
          }, null, 8, ["path"])) : _ctx.error ? (openBlock(), createBlock(NcIconSvgWrapper, {
            key: 1,
            class: "textarea__helper-text-message__icon",
            path: unref(mdiAlertCircleOutline),
            inline: ""
          }, null, 8, ["path"])) : createCommentVNode("", true),
          createTextVNode(" " + toDisplayString(_ctx.helperText), 1)
        ], 10, _hoisted_4)) : createCommentVNode("", true)
      ], 2);
    };
  }
});
const NcTextArea = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-a0d5539d"]]);
export {
  NcSelectUsers as N,
  NcTextArea as a
};
//# sourceMappingURL=NcTextArea-CWA3KOiC-Cpgesyiv.chunk.mjs.map
