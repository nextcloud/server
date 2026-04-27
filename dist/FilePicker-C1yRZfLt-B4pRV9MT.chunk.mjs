const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { r as resolveComponent, o as openBlock, f as createElementBlock, c as createBlock, p as createSlots, w as withCtx, i as renderSlot, g as createBaseVNode, v as normalizeClass, j as createTextVNode, t as toDisplayString, m as mergeProps, h as createCommentVNode, x as createVNode, M as withModifiers, F as Fragment, ac as cloneVNode, a3 as h, a5 as watchEffect, X as toValue, y as ref, b as defineComponent, n as computed, z as watch, A as onMounted, u as unref, a8 as shallowRef, k as useModel, l as useTemplateRef, C as renderList, q as mergeModels, B as onUnmounted, P as nextTick, L as toHandlers, a0 as toRef, N as normalizeStyle } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { u as unsubscribe, d as debounce, s as subscribe, b as generateUrl, f as emit, n as join, a as getCurrentUser, r as extname } from "./index-rAufP352.chunk.mjs";
import { N as NcDialog } from "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import { N as NcEmptyContent } from "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import { v as validateFilename, I as InvalidFilenameError, e as InvalidFilenameErrorReason, f as formatFileSize, s as sortNodes } from "./index-DCPyCjGS.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { N as NcActionLink, a as NcActionRouter, _ as _sfc_main$i } from "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import { r as mdiFolder, s as mdiClock, t as mdiStar, u as mdiKey, v as mdiTagOutline, w as mdiLink, x as mdiAccountPlus, q as mdiAccountGroupOutline, c as mdiNetworkOutline } from "./mdi-BGU2G5q5.chunk.mjs";
import { S as ShareType } from "./ShareType-Cy_lTCmc.chunk.mjs";
import { _ as _export_sfc$1, c as createElementId, N as NcIconSvgWrapper } from "./Web-BOM4en5n.chunk.mjs";
import { F as FileType, i as isPublicShare, P as Permission } from "./public-CKeAb98h.chunk.mjs";
import "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { P as PQueue, a as NcActionInput } from "./index-CZV8rpGu.chunk.mjs";
import { t, l as logger, a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { a as NcActions, m as isSlotPopulated } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { N as NcActionButton } from "./PencilOutline-BMYBdzdS.chunk.mjs";
import { N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import { _ as _sfc_main$h } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { g as getClient, h as getFavoriteNodes, i as defaultRootPath, j as getRecentSearch, r as resultToNode, k as getDefaultPropfind } from "./dav-DGipjjQH.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
const _sfc_main$1$2 = {
  name: "ChevronRightIcon",
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
const _hoisted_1$1$1 = ["aria-hidden", "aria-label"];
const _hoisted_2$d = ["fill", "width", "height"];
const _hoisted_3$c = { d: "M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z" };
const _hoisted_4$c = { key: 0 };
function _sfc_render$1$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon chevron-right-icon",
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
      createBaseVNode("path", _hoisted_3$c, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$c, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$d))
  ], 16, _hoisted_1$1$1);
}
const ChevronRight = /* @__PURE__ */ _export_sfc$1(_sfc_main$1$2, [["render", _sfc_render$1$1]]);
const _sfc_main$g = {
  name: "NcBreadcrumb",
  components: {
    NcActions,
    ChevronRight,
    NcButton
  },
  inheritAttrs: false,
  props: {
    /**
     * The main text content of the entry.
     */
    name: {
      type: String,
      required: true
    },
    /**
     * The title attribute of the element.
     */
    title: {
      type: String,
      default: null
    },
    /**
     * Route Location the link should navigate to when clicked on.
     *
     * @see https://v3.router.vuejs.org/api/#to
     */
    to: {
      type: [String, Object],
      default: void 0
    },
    /**
     * Set this prop if your app doesn't use vue-router, breadcrumbs will show as normal links.
     */
    href: {
      type: String,
      default: void 0
    },
    /**
     * Set a css icon-class to show an icon along name text (if forceIconText is provided, otherwise just icon).
     */
    icon: {
      type: String,
      default: ""
    },
    /**
     * Enables text to accompany the icon, if the icon was provided. The text that will be displayed is the name prop.
     */
    forceIconText: {
      type: Boolean,
      default: false
    },
    /**
     * Disable dropping on this breadcrumb.
     */
    disableDrop: {
      type: Boolean,
      default: false
    },
    /**
     * Force the actions to display in a three dot menu
     */
    forceMenu: {
      type: Boolean,
      default: false
    },
    /**
     * Open state of the Actions menu
     */
    open: {
      type: Boolean,
      default: false
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
    "dragenter",
    "dragleave",
    "dropped",
    "update:open"
  ],
  setup() {
    const crumbId = createElementId();
    return {
      actionsContainer: `.vue-crumb[data-crumb-id="${crumbId}"]`,
      crumbId
    };
  },
  data() {
    return {
      /**
       * Variable to track if we hover over the breadcrumb
       */
      hovering: false
    };
  },
  computed: {
    /**
     * The attributes to pass to `router-link` or `a`
     */
    linkAttributes() {
      if (this.to) {
        return { to: this.to, ...this.$attrs };
      } else if (this.href) {
        return { href: this.href, ...this.$attrs };
      }
      return this.$attrs;
    }
  },
  methods: {
    /**
     * Function to handle changing the open state of the Actions menu
     * $emit the open state.
     *
     * @param {boolean} open The open state of the Actions menu
     */
    onOpenChange(open) {
      this.$emit("update:open", open);
    },
    /**
     * Function to handle a drop on the breadcrumb.
     * $emit the event and the path, remove the hovering state.
     *
     * @param {object} e The drop event
     * @return {boolean}
     */
    dropped(e) {
      if (this.disableDrop) {
        return false;
      }
      this.$emit("dropped", e, this.to || this.href);
      this.$parent.$emit("dropped", e, this.to || this.href);
      this.hovering = false;
      return false;
    },
    /**
     * Add the hovering state on drag enter
     *
     * @param {DragEvent} e The drag-enter event
     */
    dragEnter(e) {
      this.$emit("dragenter", e);
      if (this.disableDrop) {
        return;
      }
      this.hovering = true;
    },
    /**
     * Remove the hovering state on drag leave
     *
     * @param {DragEvent} e The drag leave event
     */
    dragLeave(e) {
      this.$emit("dragleave", e);
      if (this.disableDrop) {
        return;
      }
      if (e.target.contains(e.relatedTarget) || this.$refs.crumb.contains(e.relatedTarget)) {
        return;
      }
      this.hovering = false;
    }
  }
};
const _hoisted_1$e = ["data-crumb-id"];
function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcButton = resolveComponent("NcButton");
  const _component_NcActions = resolveComponent("NcActions");
  const _component_ChevronRight = resolveComponent("ChevronRight");
  return openBlock(), createElementBlock("li", {
    ref: "crumb",
    class: normalizeClass(["vue-crumb", [{ "vue-crumb--hovered": $data.hovering }, _ctx.$props.class]]),
    "data-crumb-id": $setup.crumbId,
    draggable: "false",
    onDragstart: withModifiers(() => {
    }, ["prevent"]),
    onDrop: _cache[0] || (_cache[0] = withModifiers((...args) => $options.dropped && $options.dropped(...args), ["prevent"])),
    onDragover: withModifiers(() => {
    }, ["prevent"]),
    onDragenter: _cache[1] || (_cache[1] = (...args) => $options.dragEnter && $options.dragEnter(...args)),
    onDragleave: _cache[2] || (_cache[2] = (...args) => $options.dragLeave && $options.dragLeave(...args))
  }, [
    ($props.name || $props.icon || _ctx.$slots.icon) && !_ctx.$slots.default ? (openBlock(), createBlock(_component_NcButton, mergeProps({
      key: 0,
      "aria-label": $props.icon ? $props.name : void 0,
      variant: "tertiary"
    }, $options.linkAttributes), createSlots({ _: 2 }, [
      _ctx.$slots.icon || $props.icon ? {
        name: "icon",
        fn: withCtx(() => [
          renderSlot(_ctx.$slots, "icon", {}, () => [
            createBaseVNode("span", {
              class: normalizeClass([$props.icon, "icon"])
            }, null, 2)
          ], true)
        ]),
        key: "0"
      } : void 0,
      !(_ctx.$slots.icon || $props.icon) || $props.forceIconText ? {
        name: "default",
        fn: withCtx(() => [
          createTextVNode(toDisplayString($props.name), 1)
        ]),
        key: "1"
      } : void 0
    ]), 1040, ["aria-label"])) : createCommentVNode("", true),
    _ctx.$slots.default ? (openBlock(), createBlock(_component_NcActions, {
      key: 1,
      ref: "actions",
      container: $setup.actionsContainer,
      forceMenu: $props.forceMenu,
      forceName: "",
      menuName: $props.name,
      open: $props.open,
      title: $props.title,
      variant: "tertiary",
      "onUpdate:open": $options.onOpenChange
    }, {
      icon: withCtx(() => [
        renderSlot(_ctx.$slots, "menu-icon", {}, void 0, true)
      ]),
      default: withCtx(() => [
        renderSlot(_ctx.$slots, "default", {}, void 0, true)
      ]),
      _: 3
    }, 8, ["container", "forceMenu", "menuName", "open", "title", "onUpdate:open"])) : createCommentVNode("", true),
    createVNode(_component_ChevronRight, {
      class: "vue-crumb__separator",
      size: 20
    })
  ], 42, _hoisted_1$e);
}
const NcBreadcrumb = /* @__PURE__ */ _export_sfc$1(_sfc_main$g, [["render", _sfc_render$9], ["__scopeId", "data-v-46306025"]]);
const _sfc_main$1$1 = {
  name: "FolderIcon",
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
const _hoisted_1$d = ["aria-hidden", "aria-label"];
const _hoisted_2$c = ["fill", "width", "height"];
const _hoisted_3$b = { d: "M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" };
const _hoisted_4$b = { key: 0 };
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon folder-icon",
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
      createBaseVNode("path", _hoisted_3$b, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$b, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$c))
  ], 16, _hoisted_1$d);
}
const IconFolder$1 = /* @__PURE__ */ _export_sfc$1(_sfc_main$1$1, [["render", _sfc_render$8]]);
const crumbClass = "vue-crumb";
const _sfc_main$f = {
  name: "NcBreadcrumbs",
  components: {
    NcActions,
    NcActionButton,
    NcActionRouter,
    NcActionLink,
    NcBreadcrumb,
    IconFolder: IconFolder$1
  },
  props: {
    /**
     * Set a css icon-class for the icon of the root breadcrumb to be used.
     */
    rootIcon: {
      type: String,
      default: "icon-home"
    },
    /**
     * Set the aria-label of the nav element.
     */
    ariaLabel: {
      type: String,
      default: null
    }
  },
  emits: ["dropped"],
  data() {
    return {
      /**
       * Array to track the hidden breadcrumbs by their index.
       * Comparing two crumbs somehow does not work, so we use the indices.
       */
      hiddenIndices: [],
      /**
       * This is the props of the middle Action menu
       * that show the ellipsised breadcrumbs
       */
      menuBreadcrumbProps: {
        // Don't show a name for this breadcrumb, only the Actions menu
        name: "",
        forceMenu: true,
        // Don't allow dropping directly on the actions breadcrumb
        disableDrop: true,
        // Is the menu open or not
        open: false
      },
      breadcrumbsRefs: []
    };
  },
  created() {
    window.addEventListener("resize", debounce(() => {
      this.handleWindowResize();
    }, 100));
    subscribe("navigation-toggled", this.delayedResize);
  },
  mounted() {
    this.handleWindowResize();
  },
  updated() {
    this.delayedResize();
    this.$nextTick(() => {
      this.hideCrumbs();
    });
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleWindowResize);
    unsubscribe("navigation-toggled", this.delayedResize);
  },
  methods: {
    /**
     * Close the actions menu
     *
     * @param {object} e The event
     */
    closeActions(e) {
      if (this.$refs.actionsBreadcrumb.$el.contains(e.relatedTarget)) {
        return;
      }
      this.menuBreadcrumbProps.open = false;
    },
    /**
     * Call the resize function after a delay
     */
    async delayedResize() {
      await this.$nextTick();
      this.handleWindowResize();
    },
    /**
     * Check the width of the breadcrumb and hide breadcrumbs
     * if we overflow otherwise.
     */
    handleWindowResize() {
      if (!this.$refs.container) {
        return;
      }
      const nrCrumbs = this.breadcrumbsRefs.length;
      const hiddenIndices = [];
      const availableWidth = this.$refs.container.offsetWidth;
      let totalWidth = this.getTotalWidth();
      if (this.$refs.breadcrumb__actions) {
        totalWidth += this.$refs.breadcrumb__actions.offsetWidth;
      }
      let overflow = totalWidth - availableWidth;
      overflow += overflow > 0 ? 64 : 0;
      let i = 0;
      const startIndex = Math.floor(nrCrumbs / 2);
      while (overflow > 0 && i < nrCrumbs - 2) {
        const currentIndex = startIndex + (i % 2 ? i + 1 : i) / 2 * Math.pow(-1, i + nrCrumbs % 2);
        overflow -= this.getWidth(this.breadcrumbsRefs[currentIndex]?.$el, currentIndex === this.breadcrumbsRefs.length - 1);
        hiddenIndices.push(currentIndex);
        i++;
      }
      if (!this.arraysEqual(this.hiddenIndices, hiddenIndices.sort((a, b) => a - b))) {
        this.hiddenIndices = hiddenIndices;
      }
    },
    /**
     * Checks if two arrays are equal.
     * Only works for primitive arrays, but that's enough here.
     *
     * @param {Array} a The first array
     * @param {Array} b The second array
     * @return {boolean} Wether the arrays are equal
     */
    arraysEqual(a, b) {
      if (a.length !== b.length) {
        return false;
      } else if (a === b) {
        return true;
      } else if (a === null || b === null) {
        return false;
      }
      for (let i = 0; i < a.length; ++i) {
        if (a[i] !== b[i]) {
          return false;
        }
      }
      return true;
    },
    /**
     * Calculates the total width of all breadcrumbs
     *
     * @return {number} The total width
     */
    getTotalWidth() {
      return this.breadcrumbsRefs.reduce((width, crumb, index) => width + this.getWidth(crumb.$el, index === this.breadcrumbsRefs.length - 1), 0);
    },
    /**
     * Calculates the width of the provided element
     *
     * @param {object} el The element
     * @param {boolean} isLast Is this the last crumb
     * @return {number} The width
     */
    getWidth(el, isLast) {
      if (!el?.classList) {
        return 0;
      }
      const hide = el.classList.contains(`${crumbClass}--hidden`);
      el.style.minWidth = "auto";
      if (isLast) {
        el.style.maxWidth = "210px";
      }
      el.classList.remove(`${crumbClass}--hidden`);
      const w = el.offsetWidth;
      if (hide) {
        el.classList.add(`${crumbClass}--hidden`);
      }
      el.style.minWidth = "";
      el.style.maxWidth = "";
      return w;
    },
    /**
     * Prevents the default of a provided event
     *
     * @param {object} e The event
     * @return {boolean}
     */
    preventDefault(e) {
      if (e.preventDefault) {
        e.preventDefault();
      }
      return false;
    },
    /**
     * Handles the drag start.
     * Prevents a breadcrumb from being draggable.
     *
     * @param {object} e The event
     * @return {boolean}
     */
    dragStart(e) {
      return this.preventDefault(e);
    },
    /**
     * Handles when something is dropped on the breadcrumb.
     *
     * @param {object} e The drop event
     * @param {string} path The path of the breadcrumb
     * @param {boolean} disabled Whether dropping is disabled for this breadcrumb
     * @return {boolean}
     */
    dropped(e, path, disabled) {
      if (!disabled) {
        this.$emit("dropped", e, path);
      }
      this.menuBreadcrumbProps.open = false;
      const crumbs = document.querySelectorAll(`.${crumbClass}`);
      for (const crumb of crumbs) {
        crumb.classList.remove(`${crumbClass}--hovered`);
      }
      return this.preventDefault(e);
    },
    /**
     * Handles the drag over event
     *
     * @param {object} e The drag over event
     * @return {boolean}
     */
    dragOver(e) {
      return this.preventDefault(e);
    },
    /**
     * Handles the drag enter event
     *
     * @param {object} e The drag over event
     * @param {boolean} disabled Whether dropping is disabled for this breadcrumb
     */
    dragEnter(e, disabled) {
      if (disabled) {
        return;
      }
      if (e.target.closest) {
        const target = e.target.closest(`.${crumbClass}`);
        if (target.classList && target.classList.contains(crumbClass)) {
          const crumbs = document.querySelectorAll(`.${crumbClass}`);
          for (const crumb of crumbs) {
            crumb.classList.remove(`${crumbClass}--hovered`);
          }
          target.classList.add(`${crumbClass}--hovered`);
        }
      }
    },
    /**
     * Handles the drag leave event
     *
     * @param {object} e The drag leave event
     * @param {boolean} disabled Whether dropping is disabled for this breadcrumb
     */
    dragLeave(e, disabled) {
      if (disabled) {
        return;
      }
      if (e.target.contains(e.relatedTarget)) {
        return;
      }
      if (e.target.closest) {
        const target = e.target.closest(`.${crumbClass}`);
        if (target.contains(e.relatedTarget)) {
          return;
        }
        if (target.classList && target.classList.contains(crumbClass)) {
          target.classList.remove(`${crumbClass}--hovered`);
        }
      }
    },
    /**
     * Check for each crumb if we have to hide it and
     * add it to the array of all crumbs.
     */
    hideCrumbs() {
      this.breadcrumbsRefs.forEach((crumb, i) => {
        if (crumb?.$el?.classList) {
          if (this.hiddenIndices.includes(i)) {
            crumb.$el.classList.add(`${crumbClass}--hidden`);
          } else {
            crumb.$el.classList.remove(`${crumbClass}--hidden`);
          }
        }
      });
    },
    isBreadcrumb(vnode) {
      return vnode?.type?.name === "NcBreadcrumb";
    }
  },
  /**
   * The render function to display the component
   *
   * @return {object|undefined} The created VNode
   */
  render() {
    let breadcrumbs = [];
    this.$slots.default?.().forEach((vnode) => {
      if (this.isBreadcrumb(vnode)) {
        breadcrumbs.push(vnode);
        return;
      }
      if (vnode?.type === Fragment) {
        vnode?.children?.forEach?.((child) => {
          if (this.isBreadcrumb(child)) {
            breadcrumbs.push(child);
          }
        });
      }
    });
    if (breadcrumbs.length === 0) {
      return;
    }
    breadcrumbs[0] = cloneVNode(breadcrumbs[0], {
      icon: this.rootIcon,
      ref: "breadcrumbs"
    });
    const breadcrumbsRefs = [];
    breadcrumbs = breadcrumbs.map((crumb, index) => cloneVNode(crumb, {
      ref: (crumb2) => {
        breadcrumbsRefs[index] = crumb2;
      }
    }));
    const crumbs = [...breadcrumbs];
    if (this.hiddenIndices.length) {
      crumbs.splice(
        Math.round(breadcrumbs.length / 2),
        0,
        // The Actions menu
        // Use a breadcrumb component for the hidden breadcrumbs
        // eslint-disable-line @stylistic/function-call-argument-newline
        h(NcBreadcrumb, {
          class: "dropdown",
          ...this.menuBreadcrumbProps,
          // Hide the dropdown menu from screen-readers,
          // since the crumbs in the menu are still in the list.
          "aria-hidden": true,
          // Add a ref to the Actions menu
          ref: "actionsBreadcrumb",
          key: "actions-breadcrumb-1",
          // Add handlers so the Actions menu opens on hover
          onDragenter: () => {
            this.menuBreadcrumbProps.open = true;
          },
          onDragleave: this.closeActions,
          // Make sure we keep the same open state
          // as the Actions component
          "onUpdate:open": (open) => {
            this.menuBreadcrumbProps.open = open;
          }
          // Add all hidden breadcrumbs as ActionRouter or ActionLink
        }, {
          default: () => this.hiddenIndices.filter((index) => index <= breadcrumbs.length - 1).map((index) => {
            const crumb = breadcrumbs[index];
            const {
              // Get the parameters from the breadcrumb component props
              to,
              href,
              disableDrop,
              name,
              // Props to forward
              ...propsToForward
            } = crumb.props;
            delete propsToForward.ref;
            let element = NcActionButton;
            let path = "";
            if (href) {
              element = NcActionLink;
              path = href;
            }
            if (to) {
              element = NcActionRouter;
              path = to;
            }
            const folderIcon = h(IconFolder$1, {
              size: 20
            });
            return h(element, {
              ...propsToForward,
              class: crumbClass,
              href: href || null,
              to: to || null,
              // Prevent the breadcrumbs from being draggable
              draggable: false,
              // Add the drag and drop handlers
              onDragstart: this.dragStart,
              onDrop: ($event) => this.dropped($event, path, disableDrop),
              onDragover: this.dragOver,
              onDragenter: ($event) => this.dragEnter($event, disableDrop),
              onDragleave: ($event) => this.dragLeave($event, disableDrop)
            }, {
              default: () => name,
              icon: () => folderIcon
            });
          })
        })
      );
    }
    const wrapper = [h("nav", { "aria-label": this.ariaLabel }, [h("ul", { class: "breadcrumb__crumbs" }, [crumbs])])];
    if (isSlotPopulated(this.$slots.actions?.())) {
      wrapper.push(h("div", { class: "breadcrumb__actions", ref: "breadcrumb__actions" }, this.$slots.actions?.()));
    }
    this.breadcrumbsRefs = breadcrumbsRefs;
    return h("div", { class: ["breadcrumb", { "breadcrumb--collapsed": this.hiddenIndices.length === breadcrumbs.length - 2 }], ref: "container" }, wrapper);
  }
};
const NcBreadcrumbs = /* @__PURE__ */ _export_sfc$1(_sfc_main$f, [["__scopeId", "data-v-0015282c"]]);
const queue = new PQueue({ concurrency: 5 });
function preloadImage(url) {
  const { resolve, promise } = Promise.withResolvers();
  queue.add(() => {
    const image = new Image();
    image.onerror = () => resolve(false);
    image.onload = () => resolve(true);
    image.src = url;
    return promise;
  });
  return promise;
}
function getPreviewURL(node, options = {}) {
  options = { size: 32, cropPreview: false, mimeFallback: true, ...options };
  try {
    const previewUrl = node.attributes?.previewUrl || generateUrl("/core/preview?fileId={fileid}", {
      fileid: node.fileid
    });
    let url;
    try {
      url = new URL(previewUrl);
    } catch {
      url = new URL(previewUrl, window.location.origin);
    }
    url.searchParams.set("x", `${options.size}`);
    url.searchParams.set("y", `${options.size}`);
    url.searchParams.set("mimeFallback", `${options.mimeFallback}`);
    url.searchParams.set("a", options.cropPreview === true ? "0" : "1");
    url.searchParams.set("c", `${node.attributes.etag}`);
    return url;
  } catch {
    return null;
  }
}
function usePreviewURL(node, options) {
  const previewURL = ref(null);
  const previewLoaded = ref(false);
  watchEffect(() => {
    previewLoaded.value = false;
    previewURL.value = getPreviewURL(toValue(node), toValue(options || {}));
    if (previewURL.value && toValue(node).type === FileType.File) {
      preloadImage(previewURL.value.href).then((success) => {
        previewLoaded.value = success;
      });
    }
  });
  return {
    previewURL,
    previewLoaded
  };
}
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main$e = {
  name: "FileIcon",
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
const _hoisted_1$c = ["aria-hidden", "aria-label"];
const _hoisted_2$b = ["fill", "width", "height"];
const _hoisted_3$a = { d: "M13,9V3.5L18.5,9M6,2C4.89,2 4,2.89 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6Z" };
const _hoisted_4$a = { key: 0 };
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon file-icon",
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
      createBaseVNode("path", _hoisted_3$a, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$a, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$b))
  ], 16, _hoisted_1$c);
}
const IconFile = /* @__PURE__ */ _export_sfc(_sfc_main$e, [["render", _sfc_render$7]]);
const _sfc_main$d = {
  name: "MenuDownIcon",
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
const _hoisted_1$b = ["aria-hidden", "aria-label"];
const _hoisted_2$a = ["fill", "width", "height"];
const _hoisted_3$9 = { d: "M7,10L12,15L17,10H7Z" };
const _hoisted_4$9 = { key: 0 };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon menu-down-icon",
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
      createBaseVNode("path", _hoisted_3$9, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$9, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$a))
  ], 16, _hoisted_1$b);
}
const IconSortDescending = /* @__PURE__ */ _export_sfc(_sfc_main$d, [["render", _sfc_render$6]]);
const _sfc_main$c = {
  name: "MenuUpIcon",
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
const _hoisted_1$a = ["aria-hidden", "aria-label"];
const _hoisted_2$9 = ["fill", "width", "height"];
const _hoisted_3$8 = { d: "M7,15L12,10L17,15H7Z" };
const _hoisted_4$8 = { key: 0 };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon menu-up-icon",
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
      createBaseVNode("path", _hoisted_3$8, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$8, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$9))
  ], 16, _hoisted_1$a);
}
const IconSortAscending = /* @__PURE__ */ _export_sfc(_sfc_main$c, [["render", _sfc_render$5]]);
const _sfc_main$b = {
  name: "FolderIcon",
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
const _hoisted_1$9 = ["aria-hidden", "aria-label"];
const _hoisted_2$8 = ["fill", "width", "height"];
const _hoisted_3$7 = { d: "M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" };
const _hoisted_4$7 = { key: 0 };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon folder-icon",
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
      createBaseVNode("path", _hoisted_3$7, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$7, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$8))
  ], 16, _hoisted_1$9);
}
const IconFolder = /* @__PURE__ */ _export_sfc(_sfc_main$b, [["render", _sfc_render$4]]);
const fileListIconStyles = {
  "file-picker__file-icon": "_file-picker__file-icon_3v9zx_9",
  "file-picker__file-icon--primary": "_file-picker__file-icon--primary_3v9zx_21",
  "file-picker__file-icon-overlay": "_file-picker__file-icon-overlay_3v9zx_25"
};
const _sfc_main$a = /* @__PURE__ */ defineComponent({
  __name: "FilePreview",
  props: {
    node: {},
    cropImagePreviews: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const fileListIconStyles$1 = ref(fileListIconStyles);
    const {
      previewURL,
      previewLoaded
    } = usePreviewURL(toRef(props, "node"), computed(() => ({ cropPreview: props.cropImagePreviews })));
    const isFile = computed(() => props.node.type === FileType.File);
    const folderDecorationIcon = computed(() => {
      if (props.node.type !== FileType.Folder) {
        return null;
      }
      if (props.node.attributes?.["is-encrypted"] === 1) {
        return mdiKey;
      }
      if (props.node.attributes?.["is-tag"]) {
        return mdiTagOutline;
      }
      const shareTypes = Object.values(props.node.attributes?.["share-types"] || {}).flat();
      if (shareTypes.some((type) => type === ShareType.Link || type === ShareType.Email)) {
        return mdiLink;
      }
      if (shareTypes.length > 0) {
        return mdiAccountPlus;
      }
      switch (props.node.attributes?.["mount-type"]) {
        case "external":
        case "external-session":
          return mdiNetworkOutline;
        case "group":
          return mdiAccountGroupOutline;
        case "shared":
          return mdiAccountPlus;
      }
      return null;
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        style: normalizeStyle(unref(previewLoaded) ? { backgroundImage: `url(${unref(previewURL)})` } : void 0),
        class: normalizeClass(fileListIconStyles$1.value["file-picker__file-icon"])
      }, [
        !unref(previewLoaded) ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
          isFile.value ? (openBlock(), createBlock(IconFile, {
            key: 0,
            size: 32
          })) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
            folderDecorationIcon.value ? (openBlock(), createBlock(unref(NcIconSvgWrapper), {
              key: 0,
              class: normalizeClass(fileListIconStyles$1.value["file-picker__file-icon-overlay"]),
              inline: "",
              path: folderDecorationIcon.value,
              size: 16
            }, null, 8, ["class", "path"])) : createCommentVNode("", true),
            createVNode(IconFolder, {
              class: normalizeClass(fileListIconStyles$1.value["file-picker__file-icon--primary"]),
              size: 32
            }, null, 8, ["class"])
          ], 64))
        ], 64)) : createCommentVNode("", true)
      ], 6);
    };
  }
});
const _hoisted_1$8 = ["tabindex", "aria-selected", "data-filename"];
const _hoisted_2$7 = { class: "row-name" };
const _hoisted_3$6 = {
  class: "file-picker__name-container",
  "data-testid": "row-name"
};
const _hoisted_4$6 = ["title", "textContent"];
const _hoisted_5$1 = ["textContent"];
const _hoisted_6$1 = { class: "row-size" };
const _hoisted_7$1 = { class: "row-modified" };
const _sfc_main$9 = /* @__PURE__ */ defineComponent({
  __name: "FileListRow",
  props: {
    allowPickDirectory: { type: Boolean },
    selected: { type: Boolean },
    showCheckbox: { type: Boolean },
    canPick: { type: Boolean },
    node: {},
    cropImagePreviews: { type: Boolean }
  },
  emits: ["update:selected", "enterDirectory"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit2 = __emit;
    const timestamp = computed(() => props.node.mtime ?? 0);
    const fileExtension = computed(() => extname(props.node.displayname));
    const displayName = computed(() => props.node.displayname.slice(0, fileExtension.value ? -fileExtension.value.length : void 0));
    const isDirectory = computed(() => props.node.type === FileType.Folder);
    const isPickable = computed(() => props.canPick && (props.allowPickDirectory || !isDirectory.value));
    const isNavigatable = computed(() => (props.node.permissions & Permission.READ) === Permission.READ);
    function toggleSelected() {
      if (!isPickable.value) {
        return;
      }
      emit2("update:selected", !props.selected);
    }
    function handleClick() {
      if (isDirectory.value) {
        if (isNavigatable.value) {
          emit2("enterDirectory", props.node);
        }
      } else {
        toggleSelected();
      }
    }
    function handleKeyDown(event) {
      if (event.key === "Enter") {
        handleClick();
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("tr", mergeProps({
        tabindex: __props.showCheckbox && !isDirectory.value ? void 0 : 0,
        "aria-selected": !isPickable.value ? void 0 : __props.selected,
        class: ["file-picker__row", [
          {
            "file-picker__row--selected": __props.selected && !__props.showCheckbox,
            "file-picker__row--not-navigatable": isDirectory.value && !isNavigatable.value,
            "file-picker__row--not-pickable": !isPickable.value
          }
        ]],
        "data-filename": __props.node.basename,
        "data-testid": "file-list-row"
      }, toHandlers({
        click: handleClick,
        /* same as tabindex -> if we hide the checkbox or this is a directory we need keyboard access to enter the directory or select the node */
        ...!__props.showCheckbox || isDirectory.value ? { keydown: handleKeyDown } : {}
      }, true)), [
        __props.showCheckbox ? (openBlock(), createElementBlock("td", {
          key: 0,
          class: "row-checkbox",
          onClick: withModifiers(() => {
          }, ["stop"])
        }, [
          createVNode(unref(NcCheckboxRadioSwitch), {
            "aria-label": unref(t)("Select the row for {nodename}", { nodename: displayName.value }),
            disabled: !isPickable.value,
            "data-testid": "row-checkbox",
            modelValue: __props.selected,
            "onUpdate:modelValue": toggleSelected
          }, null, 8, ["aria-label", "disabled", "modelValue"])
        ])) : createCommentVNode("", true),
        createBaseVNode("td", _hoisted_2$7, [
          createBaseVNode("div", _hoisted_3$6, [
            createVNode(_sfc_main$a, {
              node: __props.node,
              cropImagePreviews: __props.cropImagePreviews
            }, null, 8, ["node", "cropImagePreviews"]),
            createBaseVNode("div", {
              class: "file-picker__file-name",
              title: displayName.value,
              textContent: toDisplayString(displayName.value)
            }, null, 8, _hoisted_4$6),
            createBaseVNode("div", {
              class: "file-picker__file-extension",
              textContent: toDisplayString(fileExtension.value)
            }, null, 8, _hoisted_5$1)
          ])
        ]),
        createBaseVNode("td", _hoisted_6$1, toDisplayString(unref(formatFileSize)(__props.node.size || 0)), 1),
        createBaseVNode("td", _hoisted_7$1, [
          createVNode(unref(_sfc_main$i), {
            timestamp: timestamp.value,
            ignoreSeconds: ""
          }, null, 8, ["timestamp"])
        ])
      ], 16, _hoisted_1$8);
    };
  }
});
const FileListRow = /* @__PURE__ */ _export_sfc(_sfc_main$9, [["__scopeId", "data-v-7857e8bd"]]);
const _hoisted_1$7 = {
  "aria-hidden": "true",
  class: "file-picker__row loading-row"
};
const _hoisted_2$6 = {
  key: 0,
  class: "row-checkbox"
};
const _hoisted_3$5 = { class: "row-name" };
const _hoisted_4$5 = { class: "row-wrapper" };
const _sfc_main$8 = /* @__PURE__ */ defineComponent({
  __name: "LoadingTableRow",
  props: {
    showCheckbox: { type: Boolean }
  },
  setup(__props) {
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("tr", _hoisted_1$7, [
        __props.showCheckbox ? (openBlock(), createElementBlock("td", _hoisted_2$6, [..._cache[0] || (_cache[0] = [
          createBaseVNode("span", null, null, -1)
        ])])) : createCommentVNode("", true),
        createBaseVNode("td", _hoisted_3$5, [
          createBaseVNode("div", _hoisted_4$5, [
            createBaseVNode("span", {
              class: normalizeClass(unref(fileListIconStyles)["file-picker__file-icon"])
            }, null, 2),
            _cache[1] || (_cache[1] = createBaseVNode("span", null, null, -1))
          ])
        ]),
        _cache[2] || (_cache[2] = createBaseVNode("td", { class: "row-size" }, [
          createBaseVNode("span")
        ], -1)),
        _cache[3] || (_cache[3] = createBaseVNode("td", { class: "row-modified" }, [
          createBaseVNode("span")
        ], -1))
      ]);
    };
  }
});
const LoadingTableRow = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["__scopeId", "data-v-1f96131b"]]);
function useFilesSettings() {
  const filesUserState = loadState("files", "config", null);
  const showHiddenFiles = ref(filesUserState?.show_hidden ?? true);
  const sortFavoritesFirst = ref(filesUserState?.sort_favorites_first ?? true);
  const cropImagePreviews = ref(filesUserState?.crop_image_previews ?? true);
  onMounted(async () => {
    if (!isPublicShare()) {
      try {
        const { data } = await cancelableClient.get(generateUrl("/apps/files/api/v1/configs"));
        showHiddenFiles.value = data?.data?.show_hidden ?? false;
        sortFavoritesFirst.value = data?.data?.sort_favorites_first ?? true;
        cropImagePreviews.value = data?.data?.crop_image_previews ?? true;
      } catch (error) {
        logger.error("Could not load files settings", { error });
        showError(t("Could not load files settings"));
      }
    } else {
      logger.debug("Skip loading files settings - currently on public share");
    }
  });
  return {
    showHiddenFiles,
    sortFavoritesFirst,
    cropImagePreviews
  };
}
function useFilesViews(currentView) {
  const convertOrder = (order2) => order2 === "asc" ? "ascending" : order2 === "desc" ? "descending" : "none";
  const filesViewsState = loadState("files", "viewConfigs", null);
  const filesViewConfig = ref({
    sortBy: filesViewsState?.files?.sorting_mode ?? "basename",
    order: convertOrder(filesViewsState?.files?.sorting_direction ?? "asc")
  });
  const recentViewConfig = ref({
    sortBy: filesViewsState?.recent?.sorting_mode ?? "basename",
    order: convertOrder(filesViewsState?.recent?.sorting_direction ?? "asc")
  });
  const favoritesViewConfig = ref({
    sortBy: filesViewsState?.favorites?.sorting_mode ?? "basename",
    order: convertOrder(filesViewsState?.favorites?.sorting_direction ?? "asc")
  });
  onMounted(async () => {
    if (!isPublicShare()) {
      try {
        const { data } = await cancelableClient.get(generateUrl("/apps/files/api/v1/views"));
        filesViewConfig.value = {
          sortBy: data?.data?.files?.sorting_mode ?? "basename",
          order: convertOrder(data?.data?.files?.sorting_direction)
        };
        favoritesViewConfig.value = {
          sortBy: data?.data?.favorites?.sorting_mode ?? "basename",
          order: convertOrder(data?.data?.favorites?.sorting_direction)
        };
        recentViewConfig.value = {
          sortBy: data?.data?.recent?.sorting_mode ?? "basename",
          order: convertOrder(data?.data?.recent?.sorting_direction)
        };
      } catch (error) {
        logger.error("Could not load files views", { error });
        showError(t("Could not load files views"));
      }
    } else {
      logger.debug("Skip loading files views - currently on public share");
    }
  });
  const currentConfig = computed(() => toValue(currentView || "files") === "files" ? filesViewConfig.value : toValue(currentView) === "recent" ? recentViewConfig.value : favoritesViewConfig.value);
  const sortBy = computed(() => currentConfig.value.sortBy);
  const order = computed(() => currentConfig.value.order);
  return {
    filesViewConfig,
    favoritesViewConfig,
    recentViewConfig,
    currentConfig,
    sortBy,
    order
  };
}
const _hoisted_1$6 = {
  key: 0,
  class: "row-checkbox"
};
const _hoisted_2$5 = { class: "hidden-visually" };
const _hoisted_3$4 = ["aria-sort"];
const _hoisted_4$4 = { class: "header-wrapper" };
const _hoisted_5 = {
  key: 2,
  style: { "width": "44px" }
};
const _hoisted_6 = ["aria-sort"];
const _hoisted_7 = {
  key: 2,
  style: { "width": "44px" }
};
const _hoisted_8 = ["aria-sort"];
const _hoisted_9 = {
  key: 2,
  style: { "width": "44px" }
};
const _sfc_main$7 = /* @__PURE__ */ defineComponent({
  __name: "FileList",
  props: /* @__PURE__ */ mergeModels({
    currentView: {},
    multiselect: { type: Boolean },
    allowPickDirectory: { type: Boolean },
    loading: { type: Boolean },
    files: {},
    canPick: { type: Function }
  }, {
    "path": { required: true },
    "pathModifiers": {},
    "selectedFiles": { required: true },
    "selectedFilesModifiers": {}
  }),
  emits: ["update:path", "update:selectedFiles"],
  setup(__props) {
    const path = useModel(__props, "path");
    const selectedFiles = useModel(__props, "selectedFiles");
    const props = __props;
    const customSortingConfig = ref();
    const { currentConfig: filesAppSorting } = useFilesViews(props.currentView);
    const sortingConfig = computed(() => customSortingConfig.value ?? filesAppSorting.value);
    const sortByName = computed(() => sortingConfig.value.sortBy === "basename" ? sortingConfig.value.order === "none" ? void 0 : sortingConfig.value.order : void 0);
    const sortBySize = computed(() => sortingConfig.value.sortBy === "size" ? sortingConfig.value.order === "none" ? void 0 : sortingConfig.value.order : void 0);
    const sortByModified = computed(() => sortingConfig.value.sortBy === "mtime" ? sortingConfig.value.order === "none" ? void 0 : sortingConfig.value.order : void 0);
    function toggleSorting(sortBy) {
      if (sortingConfig.value.sortBy === sortBy) {
        if (sortingConfig.value.order === "ascending") {
          customSortingConfig.value = { sortBy: sortingConfig.value.sortBy, order: "descending" };
        } else {
          customSortingConfig.value = { sortBy: sortingConfig.value.sortBy, order: "ascending" };
        }
      } else {
        customSortingConfig.value = { sortBy, order: "ascending" };
      }
    }
    const { sortFavoritesFirst, cropImagePreviews } = useFilesSettings();
    const sortedFiles = computed(() => {
      return sortNodes(props.files, {
        sortFoldersFirst: true,
        sortFavoritesFirst: sortFavoritesFirst.value,
        sortingOrder: sortingConfig.value.order === "descending" ? "desc" : "asc",
        sortingMode: sortingConfig.value.sortBy
      });
    });
    const selectableFiles = computed(() => props.files.filter((file) => props.allowPickDirectory || file.type !== FileType.Folder));
    const allSelected = computed(() => !props.loading && selectedFiles.value.length > 0 && selectedFiles.value.length >= selectableFiles.value.length);
    function onSelectAll() {
      if (selectedFiles.value.length < selectableFiles.value.length) {
        selectedFiles.value = [...selectableFiles.value];
      } else {
        selectedFiles.value = [];
      }
    }
    function onNodeSelected(file) {
      if (selectedFiles.value.includes(file)) {
        selectedFiles.value = selectedFiles.value.filter((f) => f.path !== file.path);
      } else {
        if (props.multiselect) {
          selectedFiles.value = [...selectedFiles.value, file];
        } else {
          selectedFiles.value = [file];
        }
      }
    }
    function onChangeDirectory(dir) {
      path.value = dir.path;
    }
    const skeletonNumber = ref(4);
    const fileContainer = ref();
    {
      const resize = () => nextTick(() => {
        const nodes = fileContainer.value?.parentElement?.children || [];
        let height = fileContainer.value?.parentElement?.clientHeight || 450;
        for (let index = 0; index < nodes.length; index++) {
          if (!fileContainer.value?.isSameNode(nodes[index])) {
            height -= nodes[index].clientHeight;
          }
        }
        skeletonNumber.value = Math.max(1, Math.floor((height - 50) / 50));
      });
      onMounted(() => {
        window.addEventListener("resize", resize);
        resize();
      });
      onUnmounted(() => {
        window.removeEventListener("resize", resize);
      });
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        ref_key: "fileContainer",
        ref: fileContainer,
        class: "file-picker__files"
      }, [
        createBaseVNode("table", null, [
          createBaseVNode("thead", null, [
            createBaseVNode("tr", null, [
              __props.multiselect ? (openBlock(), createElementBlock("th", _hoisted_1$6, [
                createBaseVNode("span", _hoisted_2$5, toDisplayString(unref(t)("Select entry")), 1),
                __props.multiselect ? (openBlock(), createBlock(unref(NcCheckboxRadioSwitch), {
                  key: 0,
                  "aria-label": unref(t)("Select all entries"),
                  "data-testid": "select-all-checkbox",
                  modelValue: allSelected.value,
                  "onUpdate:modelValue": onSelectAll
                }, null, 8, ["aria-label", "modelValue"])) : createCommentVNode("", true)
              ])) : createCommentVNode("", true),
              createBaseVNode("th", {
                "aria-sort": sortByName.value,
                class: "row-name"
              }, [
                createBaseVNode("div", _hoisted_4$4, [
                  _cache[3] || (_cache[3] = createBaseVNode("span", { class: "file-picker__header-preview" }, null, -1)),
                  createVNode(unref(NcButton), {
                    "data-test": "file-picker_sort-name",
                    variant: "tertiary",
                    wide: "",
                    onClick: _cache[0] || (_cache[0] = ($event) => toggleSorting("basename"))
                  }, {
                    icon: withCtx(() => [
                      sortByName.value === "ascending" ? (openBlock(), createBlock(IconSortAscending, {
                        key: 0,
                        size: 20
                      })) : sortByName.value === "descending" ? (openBlock(), createBlock(IconSortDescending, {
                        key: 1,
                        size: 20
                      })) : (openBlock(), createElementBlock("span", _hoisted_5))
                    ]),
                    default: withCtx(() => [
                      createTextVNode(" " + toDisplayString(unref(t)("Name")), 1)
                    ]),
                    _: 1
                  })
                ])
              ], 8, _hoisted_3$4),
              createBaseVNode("th", {
                "aria-sort": sortBySize.value,
                class: "row-size"
              }, [
                createVNode(unref(NcButton), {
                  variant: "tertiary",
                  wide: "",
                  onClick: _cache[1] || (_cache[1] = ($event) => toggleSorting("size"))
                }, {
                  icon: withCtx(() => [
                    sortBySize.value === "ascending" ? (openBlock(), createBlock(IconSortAscending, {
                      key: 0,
                      size: 20
                    })) : sortBySize.value === "descending" ? (openBlock(), createBlock(IconSortDescending, {
                      key: 1,
                      size: 20
                    })) : (openBlock(), createElementBlock("span", _hoisted_7))
                  ]),
                  default: withCtx(() => [
                    createTextVNode(" " + toDisplayString(unref(t)("Size")), 1)
                  ]),
                  _: 1
                })
              ], 8, _hoisted_6),
              createBaseVNode("th", {
                "aria-sort": sortByModified.value,
                class: "row-modified"
              }, [
                createVNode(unref(NcButton), {
                  variant: "tertiary",
                  wide: "",
                  onClick: _cache[2] || (_cache[2] = ($event) => toggleSorting("mtime"))
                }, {
                  icon: withCtx(() => [
                    sortByModified.value === "ascending" ? (openBlock(), createBlock(IconSortAscending, {
                      key: 0,
                      size: 20
                    })) : sortByModified.value === "descending" ? (openBlock(), createBlock(IconSortDescending, {
                      key: 1,
                      size: 20
                    })) : (openBlock(), createElementBlock("span", _hoisted_9))
                  ]),
                  default: withCtx(() => [
                    createTextVNode(" " + toDisplayString(unref(t)("Modified")), 1)
                  ]),
                  _: 1
                })
              ], 8, _hoisted_8)
            ])
          ]),
          createBaseVNode("tbody", null, [
            __props.loading ? (openBlock(true), createElementBlock(Fragment, { key: 0 }, renderList(skeletonNumber.value, (index) => {
              return openBlock(), createBlock(LoadingTableRow, {
                key: index,
                showCheckbox: __props.multiselect
              }, null, 8, ["showCheckbox"]);
            }), 128)) : (openBlock(true), createElementBlock(Fragment, { key: 1 }, renderList(sortedFiles.value, (file) => {
              return openBlock(), createBlock(FileListRow, {
                key: file.fileid || file.path,
                allowPickDirectory: __props.allowPickDirectory,
                showCheckbox: __props.multiselect,
                canPick: (__props.multiselect || selectedFiles.value.length === 0 || selectedFiles.value.includes(file)) && (__props.canPick === void 0 || __props.canPick(file)),
                selected: selectedFiles.value.includes(file),
                node: file,
                cropImagePreviews: unref(cropImagePreviews),
                "onUpdate:selected": ($event) => onNodeSelected(file),
                onEnterDirectory: onChangeDirectory
              }, null, 8, ["allowPickDirectory", "showCheckbox", "canPick", "selected", "node", "cropImagePreviews", "onUpdate:selected"]);
            }), 128))
          ])
        ])
      ], 512);
    };
  }
});
const FileList = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["__scopeId", "data-v-412efd5c"]]);
const _sfc_main$6 = {
  name: "HomeIcon",
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
const _hoisted_1$5 = ["aria-hidden", "aria-label"];
const _hoisted_2$4 = ["fill", "width", "height"];
const _hoisted_3$3 = { d: "M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" };
const _hoisted_4$3 = { key: 0 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon home-icon",
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
      createBaseVNode("path", _hoisted_3$3, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$3, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$4))
  ], 16, _hoisted_1$5);
}
const IconHome = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$3]]);
const _sfc_main$5 = {
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
const _hoisted_1$4 = ["aria-hidden", "aria-label"];
const _hoisted_2$3 = ["fill", "width", "height"];
const _hoisted_3$2 = { d: "M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" };
const _hoisted_4$2 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
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
      createBaseVNode("path", _hoisted_3$2, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$2, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$3))
  ], 16, _hoisted_1$4);
}
const IconPlus = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$2]]);
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "FilePickerBreadcrumbs",
  props: /* @__PURE__ */ mergeModels({
    showMenu: { type: Boolean }
  }, {
    "path": { required: true },
    "pathModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["createNode"], ["update:path"]),
  setup(__props, { emit: __emit }) {
    const path = useModel(__props, "path");
    const emit2 = __emit;
    const actionsOpen = ref(false);
    const newNodeName = ref("");
    const nameInput = useTemplateRef("nameInput");
    function validateInput() {
      const name = newNodeName.value.trim();
      const input = nameInput.value?.$el?.querySelector("input");
      let validity = "";
      try {
        validateFilename(name);
      } catch (error) {
        if (!(error instanceof InvalidFilenameError)) {
          throw error;
        }
        switch (error.reason) {
          case InvalidFilenameErrorReason.Character:
            validity = t('"{char}" is not allowed inside a folder name.', { char: error.segment });
            break;
          case InvalidFilenameErrorReason.ReservedName:
            validity = t('"{segment}" is a reserved name and not allowed for folder names.', { segment: error.segment });
            break;
          case InvalidFilenameErrorReason.Extension:
            validity = t('Folder names must not end with "{extension}".', { extension: error.segment });
            break;
          default:
            validity = t("Invalid folder name.");
        }
      }
      if (input) {
        input.setCustomValidity(validity);
      }
      return validity === "";
    }
    function onSubmit() {
      const name = newNodeName.value.trim();
      if (validateInput()) {
        actionsOpen.value = false;
        emit2("createNode", name);
        newNodeName.value = "";
      }
    }
    const pathElements = computed(() => path.value.split("/").filter((v) => v !== "").map((v, i, elements) => ({
      name: v,
      path: "/" + elements.slice(0, i + 1).join("/")
    })));
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcBreadcrumbs), { class: "file-picker__breadcrumbs" }, createSlots({
        default: withCtx(() => [
          createVNode(unref(NcBreadcrumb), {
            name: unref(t)("All files"),
            title: unref(t)("Home"),
            onClick: _cache[0] || (_cache[0] = ($event) => path.value = "/")
          }, {
            icon: withCtx(() => [
              createVNode(IconHome, { size: 20 })
            ]),
            _: 1
          }, 8, ["name", "title"]),
          (openBlock(true), createElementBlock(Fragment, null, renderList(pathElements.value, (dir) => {
            return openBlock(), createBlock(unref(NcBreadcrumb), {
              key: dir.path,
              name: dir.name,
              title: dir.path,
              onClick: ($event) => path.value = dir.path
            }, null, 8, ["name", "title", "onClick"]);
          }), 128))
        ]),
        _: 2
      }, [
        __props.showMenu ? {
          name: "actions",
          fn: withCtx(() => [
            createVNode(unref(NcActions), {
              open: actionsOpen.value,
              "onUpdate:open": _cache[2] || (_cache[2] = ($event) => actionsOpen.value = $event),
              "aria-label": unref(t)("Create directory"),
              forceMenu: true,
              forceName: true,
              menuName: unref(t)("New"),
              variant: "secondary",
              onClose: _cache[3] || (_cache[3] = ($event) => newNodeName.value = "")
            }, {
              icon: withCtx(() => [
                createVNode(IconPlus, { size: 20 })
              ]),
              default: withCtx(() => [
                createVNode(unref(NcActionInput), {
                  ref_key: "nameInput",
                  ref: nameInput,
                  modelValue: newNodeName.value,
                  "onUpdate:modelValue": [
                    _cache[1] || (_cache[1] = ($event) => newNodeName.value = $event),
                    validateInput
                  ],
                  label: unref(t)("New folder"),
                  placeholder: unref(t)("New folder name"),
                  onSubmit
                }, {
                  icon: withCtx(() => [
                    createVNode(IconFolder, { size: 20 })
                  ]),
                  _: 1
                }, 8, ["modelValue", "label", "placeholder"])
              ]),
              _: 1
            }, 8, ["open", "aria-label", "menuName"])
          ]),
          key: "0"
        } : void 0
      ]), 1024);
    };
  }
});
const FilePickerBreadcrumbs = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-b448b141"]]);
const _sfc_main$3 = {
  name: "CloseIcon",
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
const _hoisted_1$3 = ["aria-hidden", "aria-label"];
const _hoisted_2$2 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon close-icon",
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
    ], 8, _hoisted_2$2))
  ], 16, _hoisted_1$3);
}
const IconClose = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$1]]);
const _sfc_main$2 = {
  name: "MagnifyIcon",
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
const _hoisted_1$2 = ["aria-hidden", "aria-label"];
const _hoisted_2$1 = ["fill", "width", "height"];
const _hoisted_3 = { d: "M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon magnify-icon",
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
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$1))
  ], 16, _hoisted_1$2);
}
const IconMagnify = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render]]);
function useViews(isAnonymous) {
  const allViews = [
    {
      id: "files",
      label: t("All files"),
      icon: mdiFolder
    },
    {
      id: "recent",
      label: t("Recent"),
      icon: mdiClock
    },
    {
      id: "favorites",
      label: t("Favorites"),
      icon: mdiStar
    }
  ];
  const availableViews = isAnonymous.value ? allViews.filter(({ id }) => id === "files") : allViews;
  return {
    allViews,
    availableViews
  };
}
const _hoisted_1$1 = {
  key: 0,
  class: "file-picker__side"
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "FilePickerNavigation",
  props: {
    currentView: {},
    filterString: {},
    isCollapsed: { type: Boolean },
    disabledNavigation: { type: Boolean }
  },
  emits: ["update:currentView", "update:filterString"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit2 = __emit;
    const { availableViews } = useViews(ref(getCurrentUser() === null));
    const currentViewObject = computed(() => availableViews.filter((v) => v.id === props.currentView)[0] ?? availableViews[0]);
    const updateFilterValue = (value) => emit2("update:filterString", value.toString());
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock(Fragment, null, [
        createVNode(unref(_sfc_main$h), {
          class: "file-picker__filter-input",
          label: unref(t)("Filter file list"),
          showTrailingButton: !!__props.filterString,
          modelValue: __props.filterString,
          "onUpdate:modelValue": updateFilterValue,
          onTrailingButtonClick: _cache[0] || (_cache[0] = ($event) => updateFilterValue(""))
        }, {
          "trailing-button-icon": withCtx(() => [
            createVNode(IconClose, { size: 16 })
          ]),
          default: withCtx(() => [
            createVNode(IconMagnify, { size: 16 })
          ]),
          _: 1
        }, 8, ["label", "showTrailingButton", "modelValue"]),
        unref(availableViews).length > 1 && !__props.disabledNavigation ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
          !__props.isCollapsed ? (openBlock(), createElementBlock("ul", _hoisted_1$1, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(unref(availableViews), (view) => {
              return openBlock(), createElementBlock("li", {
                key: view.id
              }, [
                createVNode(unref(NcButton), {
                  variant: __props.currentView === view.id ? "primary" : "tertiary",
                  wide: true,
                  onClick: ($event) => _ctx.$emit("update:currentView", view.id)
                }, {
                  icon: withCtx(() => [
                    createVNode(unref(NcIconSvgWrapper), {
                      path: view.icon,
                      size: 20
                    }, null, 8, ["path"])
                  ]),
                  default: withCtx(() => [
                    createTextVNode(" " + toDisplayString(view.label), 1)
                  ]),
                  _: 2
                }, 1032, ["variant", "onClick"])
              ]);
            }), 128))
          ])) : (openBlock(), createBlock(unref(NcSelect), {
            key: 1,
            "aria-label": unref(t)("Current view selector"),
            clearable: false,
            searchable: false,
            options: unref(availableViews),
            modelValue: currentViewObject.value,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => emit2("update:currentView", $event.id))
          }, null, 8, ["aria-label", "options", "modelValue"]))
        ], 64)) : createCommentVNode("", true)
      ], 64);
    };
  }
});
const FilePickerNavigation = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-e1c54e23"]]);
async function getRecentNodes({ client, signal }) {
  const lastTwoWeek = Math.round(Date.now() / 1e3) - 60 * 60 * 24 * 14;
  const { data } = await client.search("/", {
    signal,
    details: true,
    data: getRecentSearch(lastTwoWeek)
  });
  return data.results.map((result) => resultToNode(result));
}
async function getNodes({ client, path, signal }) {
  const results = await client.getDirectoryContents(join(defaultRootPath, path), {
    signal,
    details: true,
    includeSelf: true,
    data: getDefaultPropfind()
  });
  const nodes = results.data.map((result) => resultToNode(result));
  return {
    contents: nodes.filter(({ path: nodePath }) => nodePath !== path),
    folder: nodes.find(({ path: nodePath }) => path === nodePath)
  };
}
async function getFile(client, path) {
  const { data } = await client.stat(join(defaultRootPath, path), {
    details: true,
    data: getDefaultPropfind()
  });
  return resultToNode(data);
}
function useDAVFiles(currentView, currentPath) {
  const client = getClient();
  const files = shallowRef([]);
  const folder = shallowRef(null);
  const isLoading = ref(true);
  let abortController;
  async function createDirectory(name) {
    const path = join(currentPath.value, name);
    await client.createDirectory(join(defaultRootPath, path));
    const directory = await getFile(client, path);
    files.value = [...files.value, directory];
    return directory;
  }
  async function loadDAVFiles() {
    if (abortController) {
      abortController.abort();
      abortController = void 0;
    }
    abortController = new AbortController();
    isLoading.value = true;
    try {
      if (currentView.value === "favorites") {
        files.value = await getFavoriteNodes({ client, path: currentPath.value, signal: abortController.signal });
        folder.value = null;
      } else if (currentView.value === "recent") {
        files.value = await getRecentNodes({ client, signal: abortController.signal });
        folder.value = null;
      } else {
        const content = await getNodes({ client, path: currentPath.value, signal: abortController.signal });
        folder.value = content.folder;
        files.value = content.contents;
      }
    } catch (error) {
      if (error instanceof Error && error.name === "AbortError") {
        return;
      }
      throw error;
    } finally {
      abortController = void 0;
      isLoading.value = false;
    }
  }
  watch([currentView, currentPath], () => loadDAVFiles());
  onMounted(() => loadDAVFiles());
  return {
    isLoading,
    files,
    folder,
    loadFiles: loadDAVFiles,
    createDirectory
  };
}
function useMimeFilter(allowedMIMETypes) {
  const splittedTypes = computed(() => allowedMIMETypes.value.map((filter) => filter.split("/")));
  const isSupportedMimeType = (mime) => {
    const mimeTypeArray = mime.split("/");
    return splittedTypes.value.some(([type, subtype]) => (
      // check mime type matches or is wildcard
      (mimeTypeArray[0] === type || type === "*") && (mimeTypeArray[1] === subtype || subtype === "*")
    ));
  };
  return {
    isSupportedMimeType
  };
}
const _hoisted_1 = { class: "file-picker__main" };
const _hoisted_2 = {
  key: 1,
  class: "file-picker__view"
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "FilePicker",
  props: {
    buttons: {},
    name: {},
    allowPickDirectory: { type: Boolean, default: false },
    noMenu: { type: Boolean, default: false },
    disabledNavigation: { type: Boolean, default: false },
    filterFn: { type: Function, default: void 0 },
    canPickFn: { type: Function, default: void 0 },
    mimetypeFilter: { default: () => [] },
    multiselect: { type: Boolean, default: false },
    path: { default: void 0 }
  },
  emits: ["close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit$1 = __emit;
    const isOpen = ref(true);
    const currentView = ref("files");
    const savedPath = ref(window?.sessionStorage.getItem("NC.FilePicker.LastPath") || "/");
    const navigatedPath = ref("");
    const currentPath = computed({
      get: () => {
        return currentView.value === "files" ? navigatedPath.value || props.path || savedPath.value : "/";
      },
      set: (path) => {
        navigatedPath.value = path;
      }
    });
    const selectedFiles = shallowRef([]);
    const {
      files,
      folder: currentFolder,
      isLoading,
      loadFiles,
      createDirectory
    } = useDAVFiles(currentView, currentPath);
    watch([navigatedPath], () => {
      if (props.path === void 0 && navigatedPath.value) {
        window.sessionStorage.setItem("NC.FilePicker.LastPath", navigatedPath.value);
      }
      selectedFiles.value = [];
    });
    let isHandlingCallback = false;
    const dialogButtons = computed(() => {
      const nodes = selectedFiles.value.length === 0 && props.allowPickDirectory && currentFolder.value ? [currentFolder.value] : selectedFiles.value;
      const buttons = typeof props.buttons === "function" ? props.buttons(nodes, currentPath.value, currentView.value) : props.buttons;
      return buttons.map((button) => ({
        ...button,
        disabled: button.disabled || isLoading.value,
        callback: () => {
          isHandlingCallback = true;
          handleButtonClick(button.callback, nodes);
        }
      }));
    });
    async function handleButtonClick(callback, nodes) {
      await callback(nodes);
      emit$1("close", nodes);
      isHandlingCallback = false;
    }
    const viewHeadline = computed(() => currentView.value === "favorites" ? t("Favorites") : currentView.value === "recent" ? t("Recent") : "");
    const filterString = ref("");
    const { isSupportedMimeType } = useMimeFilter(toRef(props, "mimetypeFilter"));
    onMounted(() => loadFiles());
    const { showHiddenFiles } = useFilesSettings();
    const filteredFiles = computed(() => {
      let filtered = files.value;
      if (!showHiddenFiles.value) {
        filtered = filtered.filter((file) => !file.basename.startsWith("."));
      }
      if (props.mimetypeFilter.length > 0) {
        filtered = filtered.filter((file) => file.type === "folder" || file.mime && isSupportedMimeType(file.mime));
      }
      if (filterString.value) {
        filtered = filtered.filter((file) => file.basename.toLowerCase().includes(filterString.value.toLowerCase()));
      }
      if (props.filterFn) {
        filtered = filtered.filter((f) => props.filterFn(f));
      }
      return filtered;
    });
    const noFilesDescription = computed(() => {
      if (currentView.value === "files") {
        return t("Upload some content or sync with your devices!");
      } else if (currentView.value === "recent") {
        return t("Files and folders you recently modified will show up here.");
      } else {
        return t("Files and folders you mark as favorite will show up here.");
      }
    });
    async function onCreateFolder(name) {
      try {
        const folder = await createDirectory(name);
        navigatedPath.value = folder.path;
        emit("files:node:created", files.value.filter((file) => file.basename === name)[0]);
      } catch (error) {
        logger.warn("Could not create new folder", { name, error });
        showError(t("Could not create the new folder"));
      }
    }
    function handleClose(open) {
      if (!open && !isHandlingCallback) {
        emit$1("close");
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcDialog), {
        open: isOpen.value,
        "onUpdate:open": [
          _cache[6] || (_cache[6] = ($event) => isOpen.value = $event),
          handleClose
        ],
        buttons: dialogButtons.value,
        name: __props.name,
        size: "large",
        contentClasses: "file-picker__content",
        dialogClasses: "file-picker",
        navigationClasses: "file-picker__navigation"
      }, {
        navigation: withCtx(({ isCollapsed }) => [
          createVNode(FilePickerNavigation, {
            currentView: currentView.value,
            "onUpdate:currentView": _cache[0] || (_cache[0] = ($event) => currentView.value = $event),
            filterString: filterString.value,
            "onUpdate:filterString": _cache[1] || (_cache[1] = ($event) => filterString.value = $event),
            isCollapsed,
            disabledNavigation: __props.disabledNavigation
          }, null, 8, ["currentView", "filterString", "isCollapsed", "disabledNavigation"])
        ]),
        default: withCtx(() => [
          createBaseVNode("div", _hoisted_1, [
            currentView.value === "files" ? (openBlock(), createBlock(FilePickerBreadcrumbs, {
              key: 0,
              path: currentPath.value,
              "onUpdate:path": _cache[2] || (_cache[2] = ($event) => currentPath.value = $event),
              showMenu: !__props.noMenu,
              onCreateNode: onCreateFolder
            }, null, 8, ["path", "showMenu"])) : (openBlock(), createElementBlock("div", _hoisted_2, [
              createBaseVNode("h3", null, toDisplayString(viewHeadline.value), 1)
            ])),
            unref(isLoading) || filteredFiles.value.length > 0 ? (openBlock(), createBlock(FileList, {
              key: 2,
              path: currentPath.value,
              "onUpdate:path": [
                _cache[3] || (_cache[3] = ($event) => currentPath.value = $event),
                _cache[5] || (_cache[5] = ($event) => currentView.value = "files")
              ],
              selectedFiles: selectedFiles.value,
              "onUpdate:selectedFiles": _cache[4] || (_cache[4] = ($event) => selectedFiles.value = $event),
              allowPickDirectory: __props.allowPickDirectory,
              currentView: currentView.value,
              files: filteredFiles.value,
              multiselect: __props.multiselect,
              loading: unref(isLoading),
              name: viewHeadline.value,
              canPick: __props.canPickFn
            }, null, 8, ["path", "selectedFiles", "allowPickDirectory", "currentView", "files", "multiselect", "loading", "name", "canPick"])) : filterString.value ? (openBlock(), createBlock(unref(NcEmptyContent), {
              key: 3,
              name: unref(t)("No matching files"),
              description: unref(t)("No files matching your filter were found.")
            }, {
              icon: withCtx(() => [
                createVNode(IconFile)
              ]),
              _: 1
            }, 8, ["name", "description"])) : (openBlock(), createBlock(unref(NcEmptyContent), {
              key: 4,
              name: unref(t)("No files in here"),
              description: noFilesDescription.value
            }, {
              icon: withCtx(() => [
                createVNode(IconFile)
              ]),
              _: 1
            }, 8, ["name", "description"]))
          ])
        ]),
        _: 1
      }, 8, ["open", "buttons", "name"]);
    };
  }
});
const FilePicker = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-9b6534b1"]]);
export {
  FilePicker as default
};
//# sourceMappingURL=FilePicker-C1yRZfLt-B4pRV9MT.chunk.mjs.map
