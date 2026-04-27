const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { A as ActionTextMixin } from "./PencilOutline-BMYBdzdS.chunk.mjs";
import { j as NC_ACTIONS_IS_SEMANTIC_MENU, D as useFormatRelativeTime, E as useFormatTime } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { o as openBlock, f as createElementBlock, g as createBaseVNode, i as renderSlot, N as normalizeStyle, v as normalizeClass, t as toDisplayString, h as createCommentVNode, r as resolveComponent, x as createVNode, w as withCtx, b as defineComponent, u as unref, n as computed, a0 as toRef } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc } from "./Web-BOM4en5n.chunk.mjs";
const _sfc_main$2 = {
  name: "NcActionLink",
  mixins: [ActionTextMixin],
  inject: {
    isInSemanticMenu: {
      from: NC_ACTIONS_IS_SEMANTIC_MENU,
      default: false
    }
  },
  props: {
    /**
     * destionation to link to
     */
    href: {
      type: String,
      required: true,
      validator: (value) => {
        try {
          return new URL(value);
        } catch {
          return value.startsWith("#") || value.startsWith("/");
        }
      }
    },
    /**
     * download the link instead of opening
     */
    download: {
      type: String,
      default: null
    },
    /**
     * target to open the link
     */
    target: {
      type: String,
      default: "_self",
      validator: (value) => {
        return value && (!value.startsWith("_") || ["_blank", "_self", "_parent", "_top"].indexOf(value) > -1);
      }
    },
    /**
     * Declares a native tooltip when not null
     */
    title: {
      type: String,
      default: null
    }
  }
};
const _hoisted_1$2 = ["role"];
const _hoisted_2$1 = ["download", "href", "aria-label", "target", "title", "role"];
const _hoisted_3$1 = {
  key: 0,
  class: "action-link__longtext-wrapper"
};
const _hoisted_4$1 = { class: "action-link__name" };
const _hoisted_5$1 = ["textContent"];
const _hoisted_6$1 = ["textContent"];
const _hoisted_7 = {
  key: 2,
  class: "action-link__text"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("li", {
    class: "action",
    role: $options.isInSemanticMenu && "presentation"
  }, [
    createBaseVNode("a", {
      download: $props.download,
      href: $props.href,
      "aria-label": _ctx.ariaLabel,
      target: $props.target,
      title: $props.title,
      class: "action-link focusable",
      rel: "nofollow noreferrer noopener",
      role: $options.isInSemanticMenu && "menuitem",
      onClick: _cache[0] || (_cache[0] = (...args) => _ctx.onClick && _ctx.onClick(...args))
    }, [
      renderSlot(_ctx.$slots, "icon", {}, () => [
        createBaseVNode("span", {
          "aria-hidden": "true",
          class: normalizeClass(["action-link__icon", [_ctx.isIconUrl ? "action-link__icon--url" : _ctx.icon]]),
          style: normalizeStyle({ backgroundImage: _ctx.isIconUrl ? `url(${_ctx.icon})` : null })
        }, null, 6)
      ], true),
      _ctx.name ? (openBlock(), createElementBlock("span", _hoisted_3$1, [
        createBaseVNode("strong", _hoisted_4$1, toDisplayString(_ctx.name), 1),
        _cache[1] || (_cache[1] = createBaseVNode("br", null, null, -1)),
        createBaseVNode("span", {
          class: "action-link__longtext",
          textContent: toDisplayString(_ctx.text)
        }, null, 8, _hoisted_5$1)
      ])) : _ctx.isLongText ? (openBlock(), createElementBlock("span", {
        key: 1,
        class: "action-link__longtext",
        textContent: toDisplayString(_ctx.text)
      }, null, 8, _hoisted_6$1)) : (openBlock(), createElementBlock("span", _hoisted_7, toDisplayString(_ctx.text), 1)),
      createCommentVNode("", true)
    ], 8, _hoisted_2$1)
  ], 8, _hoisted_1$2);
}
const NcActionLink = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$1], ["__scopeId", "data-v-32f01b7a"]]);
const _sfc_main$1 = {
  name: "NcActionRouter",
  mixins: [ActionTextMixin],
  inject: {
    isInSemanticMenu: {
      from: NC_ACTIONS_IS_SEMANTIC_MENU,
      default: false
    }
  },
  props: {
    /**
     * router-link to prop [https://router.vuejs.org/api/#to](https://router.vuejs.org/api/#to)
     */
    to: {
      type: [String, Object],
      required: true
    }
  }
};
const _hoisted_1$1 = ["role"];
const _hoisted_2 = {
  key: 0,
  class: "action-router__longtext-wrapper"
};
const _hoisted_3 = { class: "action-router__name" };
const _hoisted_4 = ["textContent"];
const _hoisted_5 = ["textContent"];
const _hoisted_6 = {
  key: 2,
  class: "action-router__text"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_RouterLink = resolveComponent("RouterLink");
  return openBlock(), createElementBlock("li", {
    class: "action",
    role: $options.isInSemanticMenu && "presentation"
  }, [
    createVNode(_component_RouterLink, {
      "aria-label": _ctx.ariaLabel,
      class: "action-router focusable",
      rel: "nofollow noreferrer noopener",
      role: $options.isInSemanticMenu && "menuitem",
      title: _ctx.title,
      to: $props.to,
      onClick: _ctx.onClick
    }, {
      default: withCtx(() => [
        renderSlot(_ctx.$slots, "icon", {}, () => [
          createBaseVNode("span", {
            "aria-hidden": "true",
            class: normalizeClass(["action-router__icon", [_ctx.isIconUrl ? "action-router__icon--url" : _ctx.icon]]),
            style: normalizeStyle({ backgroundImage: _ctx.isIconUrl ? `url(${_ctx.icon})` : null })
          }, null, 6)
        ], true),
        _ctx.name ? (openBlock(), createElementBlock("span", _hoisted_2, [
          createBaseVNode("strong", _hoisted_3, toDisplayString(_ctx.name), 1),
          _cache[0] || (_cache[0] = createBaseVNode("br", null, null, -1)),
          createBaseVNode("span", {
            class: "action-router__longtext",
            textContent: toDisplayString(_ctx.text)
          }, null, 8, _hoisted_4)
        ])) : _ctx.isLongText ? (openBlock(), createElementBlock("span", {
          key: 1,
          class: "action-router__longtext",
          textContent: toDisplayString(_ctx.text)
        }, null, 8, _hoisted_5)) : (openBlock(), createElementBlock("span", _hoisted_6, toDisplayString(_ctx.text), 1)),
        createCommentVNode("", true)
      ]),
      _: 3
    }, 8, ["aria-label", "role", "title", "to", "onClick"])
  ], 8, _hoisted_1$1);
}
const NcActionRouter = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render], ["__scopeId", "data-v-87267750"]]);
const _hoisted_1 = ["data-timestamp", "title", "textContent"];
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcDateTime",
  props: {
    timestamp: {},
    format: { default: () => ({ timeStyle: "medium", dateStyle: "short" }) },
    relativeTime: { type: [Boolean, String], default: "long" },
    ignoreSeconds: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const timeOptions = computed(() => ({ format: props.format }));
    const relativeTimeOptions = computed(() => ({
      ignoreSeconds: props.ignoreSeconds,
      relativeTime: props.relativeTime || "long",
      update: props.relativeTime !== false
    }));
    const title = useFormatTime(toRef(() => props.timestamp), timeOptions);
    const relativeTime = useFormatRelativeTime(toRef(() => props.timestamp), relativeTimeOptions);
    const formattedTime = computed(() => props.relativeTime ? relativeTime.value : title.value);
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("span", {
        class: "nc-datetime",
        dir: "auto",
        "data-timestamp": _ctx.timestamp,
        title: unref(title),
        textContent: toDisplayString(formattedTime.value)
      }, null, 8, _hoisted_1);
    };
  }
});
export {
  NcActionLink as N,
  _sfc_main as _,
  NcActionRouter as a
};
//# sourceMappingURL=NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs.map
