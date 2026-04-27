const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { N as NcAvatar } from "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import { A as AccountOutline, l as logger } from "./logger-BQwTrq8j.chunk.mjs";
import { o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps, r as resolveComponent, x as createVNode, F as Fragment, j as createTextVNode } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import { W as Web } from "./Web-BOM4en5n.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
const _sfc_main$4 = {
  name: "DomainIcon",
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
const _hoisted_2$4 = ["fill", "width", "height"];
const _hoisted_3$4 = { d: "M18,15H16V17H18M18,11H16V13H18M20,19H12V17H14V15H12V13H14V11H12V9H20M10,7H8V5H10M10,11H8V9H10M10,15H8V13H10M10,19H8V17H10M6,7H4V5H6M6,11H4V9H6M6,15H4V13H6M6,19H4V17H6M12,7V3H2V21H22V7H12Z" };
const _hoisted_4$4 = { key: 0 };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon domain-icon",
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
      createBaseVNode("path", _hoisted_3$4, [
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$4,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$4))
  ], 16, _hoisted_1$4);
}
const Domain = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Domain.vue"]]);
const _sfc_main$3 = {
  name: "HandshakeOutlineIcon",
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
const _hoisted_2$3 = ["fill", "width", "height"];
const _hoisted_3$3 = { d: "M21.71 8.71C22.96 7.46 22.39 6 21.71 5.29L18.71 2.29C17.45 1.04 16 1.61 15.29 2.29L13.59 4H11C9.1 4 8 5 7.44 6.15L3 10.59V14.59L2.29 15.29C1.04 16.55 1.61 18 2.29 18.71L5.29 21.71C5.83 22.25 6.41 22.45 6.96 22.45C7.67 22.45 8.32 22.1 8.71 21.71L11.41 19H15C16.7 19 17.56 17.94 17.87 16.9C19 16.6 19.62 15.74 19.87 14.9C21.42 14.5 22 13.03 22 12V9H21.41L21.71 8.71M20 12C20 12.45 19.81 13 19 13L18 13L18 14C18 14.45 17.81 15 17 15L16 15L16 16C16 16.45 15.81 17 15 17H10.59L7.31 20.28C7 20.57 6.82 20.4 6.71 20.29L3.72 17.31C3.43 17 3.6 16.82 3.71 16.71L5 15.41V11.41L7 9.41V11C7 12.21 7.8 14 10 14S13 12.21 13 11H20V12M20.29 7.29L18.59 9H11V11C11 11.45 10.81 12 10 12S9 11.45 9 11V8C9 7.54 9.17 6 11 6H14.41L16.69 3.72C17 3.43 17.18 3.6 17.29 3.71L20.28 6.69C20.57 7 20.4 7.18 20.29 7.29Z" };
const _hoisted_4$3 = { key: 0 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon handshake-outline-icon",
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
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$3,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$3))
  ], 16, _hoisted_1$3);
}
const HandshakeOutline = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/HandshakeOutline.vue"]]);
const _sfc_main$2 = {
  name: "MapMarkerOutlineIcon",
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
const _hoisted_2$2 = ["fill", "width", "height"];
const _hoisted_3$2 = { d: "M12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5M12,2A7,7 0 0,1 19,9C19,14.25 12,22 12,22C12,22 5,14.25 5,9A7,7 0 0,1 12,2M12,4A5,5 0 0,0 7,9C7,10 7,12 12,18.71C17,12 17,10 17,9A5,5 0 0,0 12,4Z" };
const _hoisted_4$2 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon map-marker-outline-icon",
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
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$2,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$2))
  ], 16, _hoisted_1$2);
}
const MapMarkerOutline = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/MapMarkerOutline.vue"]]);
const _sfc_main$1 = {
  name: "TextAccountIcon",
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
const _hoisted_1$1 = ["aria-hidden", "aria-label"];
const _hoisted_2$1 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M21 5V7H3V5H21M3 17H12V15H3V17M3 12H21V10H3V12M18 14C19.11 14 20 14.9 20 16S19.11 18 18 18 16 17.11 16 16 16.9 14 18 14M14 22V21C14 19.9 15.79 19 18 19S22 19.9 22 21V22H14Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon text-account-icon",
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
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$1,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$1))
  ], 16, _hoisted_1$1);
}
const TextAccount = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/TextAccount.vue"]]);
const _sfc_main = {
  name: "ProfilePickerReferenceWidget",
  components: {
    NcAvatar,
    AccountOutline,
    MapMarkerOutline,
    Web,
    Domain,
    HandshakeOutline,
    TextAccount
  },
  props: {
    richObjectType: {
      type: String,
      default: ""
    },
    richObject: {
      type: Object,
      default: null
    },
    accessible: {
      type: Boolean,
      default: true
    }
  },
  beforeMount() {
    logger.debug("ProfilePickerReferenceWidget", this.richObject);
  }
};
const _hoisted_1 = { class: "profile-reference" };
const _hoisted_2 = { class: "profile-reference__wrapper" };
const _hoisted_3 = { class: "profile-reference__wrapper__header" };
const _hoisted_4 = { class: "profile-card__title" };
const _hoisted_5 = ["href"];
const _hoisted_6 = { class: "profile-content" };
const _hoisted_7 = { class: "profile-content__subline" };
const _hoisted_8 = {
  key: 0,
  class: "headline"
};
const _hoisted_9 = {
  key: 1,
  class: "location"
};
const _hoisted_10 = ["href"];
const _hoisted_11 = {
  key: 2,
  class: "website"
};
const _hoisted_12 = ["href"];
const _hoisted_13 = {
  key: 3,
  class: "organisation"
};
const _hoisted_14 = {
  key: 4,
  class: "role"
};
const _hoisted_15 = ["title"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcAvatar = resolveComponent("NcAvatar");
  const _component_AccountOutline = resolveComponent("AccountOutline");
  const _component_MapMarkerOutline = resolveComponent("MapMarkerOutline");
  const _component_Web = resolveComponent("Web");
  const _component_Domain = resolveComponent("Domain");
  const _component_HandshakeOutline = resolveComponent("HandshakeOutline");
  const _component_TextAccount = resolveComponent("TextAccount");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createVNode(_component_NcAvatar, {
          user: $props.richObject.user_id,
          size: 48,
          class: "profile-card__avatar"
        }, null, 8, ["user"]),
        createBaseVNode("div", _hoisted_4, [
          createBaseVNode("a", {
            href: $props.richObject.url,
            target: "_blank"
          }, [
            createVNode(_component_AccountOutline, { size: 20 }),
            createBaseVNode(
              "strong",
              null,
              toDisplayString($props.richObject.email !== null ? $props.richObject.title + " - " + $props.richObject.email : $props.richObject.title),
              1
              /* TEXT */
            )
          ], 8, _hoisted_5)
        ])
      ]),
      createBaseVNode("div", _hoisted_6, [
        createBaseVNode("p", _hoisted_7, [
          $props.richObject.headline ? (openBlock(), createElementBlock(
            "span",
            _hoisted_8,
            toDisplayString($props.richObject.headline),
            1
            /* TEXT */
          )) : createCommentVNode("v-if", true),
          $props.richObject.location ? (openBlock(), createElementBlock("span", _hoisted_9, [
            createVNode(_component_MapMarkerOutline, { size: 20 }),
            $props.richObject.location_url ? (openBlock(), createElementBlock("a", {
              key: 0,
              href: $props.richObject.location_url,
              class: "external",
              target: "_blank"
            }, toDisplayString($props.richObject.location), 9, _hoisted_10)) : (openBlock(), createElementBlock(
              Fragment,
              { key: 1 },
              [
                createTextVNode(
                  toDisplayString($props.richObject.location),
                  1
                  /* TEXT */
                )
              ],
              64
              /* STABLE_FRAGMENT */
            ))
          ])) : createCommentVNode("v-if", true),
          $props.richObject.website ? (openBlock(), createElementBlock("span", _hoisted_11, [
            createVNode(_component_Web, { size: 20 }),
            createBaseVNode("a", {
              href: $props.richObject.website,
              class: "external",
              target: "_blank"
            }, toDisplayString($props.richObject.website), 9, _hoisted_12)
          ])) : createCommentVNode("v-if", true),
          $props.richObject.organisation ? (openBlock(), createElementBlock("span", _hoisted_13, [
            createVNode(_component_Domain, { size: 20 }),
            createTextVNode(
              " " + toDisplayString($props.richObject.organisation),
              1
              /* TEXT */
            )
          ])) : createCommentVNode("v-if", true),
          $props.richObject.role ? (openBlock(), createElementBlock("span", _hoisted_14, [
            createVNode(_component_HandshakeOutline, { size: 20 }),
            createTextVNode(
              " " + toDisplayString($props.richObject.role),
              1
              /* TEXT */
            )
          ])) : createCommentVNode("v-if", true),
          $props.richObject.bio ? (openBlock(), createElementBlock("span", {
            key: 5,
            class: "bio",
            title: $props.richObject.full_bio
          }, [
            createVNode(_component_TextAccount, { size: 20 }),
            createTextVNode(
              " " + toDisplayString($props.richObject.bio),
              1
              /* TEXT */
            )
          ], 8, _hoisted_15)) : createCommentVNode("v-if", true)
        ])
      ])
    ])
  ]);
}
const ProfilePickerReferenceWidget = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-6cfbf971"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/profile/src/views/ProfilePickerReferenceWidget.vue"]]);
export {
  ProfilePickerReferenceWidget as default
};
//# sourceMappingURL=ProfilePickerReferenceWidget-D97y66SP.chunk.mjs.map
