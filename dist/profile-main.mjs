const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps, b as defineComponent, c as createBlock, K as resolveDynamicComponent, v as normalizeClass, y as ref, n as computed, Q as onBeforeMount, A as onMounted, R as onBeforeUnmount, w as withCtx, x as createVNode, j as createTextVNode, M as withModifiers, F as Fragment, C as renderList, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { a as getCurrentUser, b as generateUrl, s as subscribe, u as unsubscribe } from "./index-rAufP352.chunk.mjs";
import { a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { N as NcActionLink } from "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import { a as NcActions } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { N as NcContent, a as NcAppContent } from "./NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs";
import { N as NcAvatar } from "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcEmptyContent } from "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import { b as NcRichText } from "./index-D5BR15En.chunk.mjs";
import { A as AccountOutline, l as logger } from "./logger-BQwTrq8j.chunk.mjs";
import { P as PencilIcon } from "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./modulepreload-polyfill-mMY-eDcw.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
const _sfc_main$2 = {
  name: "MapMarkerIcon",
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
const _hoisted_3$1 = { d: "M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon map-marker-icon",
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
const MapMarkerIcon = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/MapMarker.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "ProfileSection",
  props: {
    section: { type: Object, required: true },
    userId: { type: null, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const __returned__ = {};
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const profileSection = "_profileSection_1o17g_2";
const style0 = {
  profileSection
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass(_ctx.$style.profileSection)
    },
    [
      (openBlock(), createBlock(resolveDynamicComponent($props.section.tagName), mergeProps($props.section.params, { ".user": $props.userId }), null, 48, [".user"]))
    ],
    2
    /* CLASS */
  );
}
const cssModules = {
  "$style": style0
};
const ProfileSection = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/profile/src/components/ProfileSection.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "ProfileApp",
  setup(__props, { expose: __expose }) {
    __expose();
    const profileParameters = loadState("profile", "profileParameters", {
      userId: void 0,
      displayname: void 0,
      address: void 0,
      organisation: void 0,
      role: void 0,
      headline: void 0,
      biography: void 0,
      actions: [],
      isUserAvatarVisible: false,
      pronouns: void 0
    });
    const userStatus = ref(loadState("profile", "status", {}));
    const sections = ref([]);
    const sortedSections = computed(() => [...sections.value].sort((a, b) => b.order - a.order));
    onBeforeMount(() => {
      sections.value = window.OCA.Profile.ProfileSections.getSections();
    });
    const isCurrentUser = getCurrentUser()?.uid === profileParameters.userId;
    const primaryAction = profileParameters.actions[0];
    const otherActions = profileParameters.actions.slice(1);
    const settingsUrl = generateUrl("/settings/user");
    const emptyProfileMessage = isCurrentUser ? translate("profile", "You have not added any info yet") : translate("profile", "{user} has not added any info yet", { user: profileParameters.displayname || profileParameters.userId || "" });
    onMounted(() => {
      document.title = `${profileParameters.displayname || profileParameters.userId} - ${document.title}`;
      subscribe("user_status:status.updated", handleStatusUpdate);
    });
    onBeforeUnmount(() => {
      unsubscribe("user_status:status.updated", handleStatusUpdate);
    });
    function handleStatusUpdate(status) {
      if (isCurrentUser && status.userId === profileParameters.userId) {
        userStatus.value = status;
      }
    }
    function openStatusModal() {
      if (!isCurrentUser) {
        return;
      }
      const statusMenuItem = document.querySelector(".user-status-menu-item");
      if (statusMenuItem) {
        statusMenuItem.click();
      } else {
        showError(translate("profile", "Error opening the user status modal, try hard refreshing the page"));
      }
    }
    const __returned__ = { profileParameters, userStatus, sections, sortedSections, isCurrentUser, primaryAction, otherActions, settingsUrl, emptyProfileMessage, handleStatusUpdate, openStatusModal, get t() {
      return translate;
    }, get NcActionLink() {
      return NcActionLink;
    }, get NcActions() {
      return NcActions;
    }, get NcAppContent() {
      return NcAppContent;
    }, get NcAvatar() {
      return NcAvatar;
    }, get NcButton() {
      return NcButton;
    }, get NcContent() {
      return NcContent;
    }, get NcEmptyContent() {
      return NcEmptyContent;
    }, get NcRichText() {
      return NcRichText;
    }, AccountIcon: AccountOutline, MapMarkerIcon, PencilIcon, ProfileSection };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1 = { class: "profile__header" };
const _hoisted_2 = { class: "profile__header__container" };
const _hoisted_3 = { class: "profile__header__container__displayname" };
const _hoisted_4 = { key: 0 };
const _hoisted_5 = {
  key: 1,
  class: "profile__header__container__pronouns"
};
const _hoisted_6 = { class: "profile__wrapper" };
const _hoisted_7 = { class: "profile__content" };
const _hoisted_8 = { class: "profile__sidebar" };
const _hoisted_9 = { class: "user-actions" };
const _hoisted_10 = ["src"];
const _hoisted_11 = ["src"];
const _hoisted_12 = { class: "profile__blocks" };
const _hoisted_13 = {
  key: 0,
  class: "profile__blocks-details"
};
const _hoisted_14 = {
  key: 0,
  class: "detail"
};
const _hoisted_15 = { key: 0 };
const _hoisted_16 = {
  key: 1,
  class: "detail"
};
const _hoisted_17 = {
  key: 0,
  class: "profile__blocks-headline"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcContent"], { appName: "profile" }, {
    default: withCtx(() => [
      createVNode($setup["NcAppContent"], null, {
        default: withCtx(() => [
          createBaseVNode("div", _hoisted_1, [
            createBaseVNode("div", _hoisted_2, [
              _cache[0] || (_cache[0] = createBaseVNode(
                "div",
                { class: "profile__header__container__placeholder" },
                null,
                -1
                /* CACHED */
              )),
              createBaseVNode("div", _hoisted_3, [
                createBaseVNode(
                  "h2",
                  null,
                  toDisplayString($setup.profileParameters.displayname || $setup.profileParameters.userId),
                  1
                  /* TEXT */
                ),
                $setup.profileParameters.pronouns ? (openBlock(), createElementBlock("span", _hoisted_4, "·")) : createCommentVNode("v-if", true),
                $setup.profileParameters.pronouns ? (openBlock(), createElementBlock(
                  "span",
                  _hoisted_5,
                  toDisplayString($setup.profileParameters.pronouns),
                  1
                  /* TEXT */
                )) : createCommentVNode("v-if", true),
                $setup.isCurrentUser ? (openBlock(), createBlock($setup["NcButton"], {
                  key: 2,
                  variant: "primary",
                  href: $setup.settingsUrl
                }, {
                  icon: withCtx(() => [
                    createVNode($setup["PencilIcon"], { size: 20 })
                  ]),
                  default: withCtx(() => [
                    createTextVNode(
                      " " + toDisplayString($setup.t("profile", "Edit Profile")),
                      1
                      /* TEXT */
                    )
                  ]),
                  _: 1
                  /* STABLE */
                }, 8, ["href"])) : createCommentVNode("v-if", true)
              ]),
              $setup.userStatus.icon || $setup.userStatus.message ? (openBlock(), createBlock($setup["NcButton"], {
                key: 0,
                disabled: !$setup.isCurrentUser,
                variant: $setup.isCurrentUser ? "tertiary" : "tertiary-no-background",
                onClick: $setup.openStatusModal
              }, {
                default: withCtx(() => [
                  createTextVNode(
                    toDisplayString($setup.userStatus.icon) + " " + toDisplayString($setup.userStatus.message),
                    1
                    /* TEXT */
                  )
                ]),
                _: 1
                /* STABLE */
              }, 8, ["disabled", "variant"])) : createCommentVNode("v-if", true)
            ])
          ]),
          createBaseVNode("div", _hoisted_6, [
            createBaseVNode("div", _hoisted_7, [
              createBaseVNode("div", _hoisted_8, [
                createVNode($setup["NcAvatar"], {
                  class: normalizeClass(["avatar", { interactive: $setup.isCurrentUser }]),
                  user: $setup.profileParameters.userId,
                  size: 180,
                  disableMenu: true,
                  disableTooltip: true,
                  isNoUser: !$setup.profileParameters.isUserAvatarVisible,
                  onClick: withModifiers($setup.openStatusModal, ["prevent", "stop"])
                }, null, 8, ["class", "user", "isNoUser"]),
                createBaseVNode("div", _hoisted_9, [
                  createCommentVNode(' When a tel: URL is opened with target="_blank", a blank new tab is opened which is inconsistent with the handling of other URLs so we set target="_self" for the phone action '),
                  $setup.primaryAction ? (openBlock(), createBlock($setup["NcButton"], {
                    key: 0,
                    variant: "primary",
                    class: "user-actions__primary",
                    href: $setup.primaryAction.target,
                    icon: $setup.primaryAction.icon,
                    target: $setup.primaryAction.id === "phone" ? "_self" : "_blank"
                  }, {
                    icon: withCtx(() => [
                      createCommentVNode(" Fix for https://github.com/nextcloud-libraries/nextcloud-vue/issues/2315 "),
                      createBaseVNode("img", {
                        src: $setup.primaryAction.icon,
                        alt: "",
                        class: "user-actions__primary__icon"
                      }, null, 8, _hoisted_10)
                    ]),
                    default: withCtx(() => [
                      createTextVNode(
                        " " + toDisplayString($setup.primaryAction.title),
                        1
                        /* TEXT */
                      )
                    ]),
                    _: 1
                    /* STABLE */
                  }, 8, ["href", "icon", "target"])) : createCommentVNode("v-if", true),
                  createVNode($setup["NcActions"], {
                    class: "user-actions__other",
                    inline: 4
                  }, {
                    default: withCtx(() => [
                      (openBlock(true), createElementBlock(
                        Fragment,
                        null,
                        renderList($setup.otherActions, (action) => {
                          return openBlock(), createBlock($setup["NcActionLink"], {
                            key: action.id,
                            closeAfterClick: true,
                            href: action.target,
                            target: action.id === "phone" ? "_self" : "_blank"
                          }, {
                            icon: withCtx(() => [
                              createCommentVNode(" Fix for https://github.com/nextcloud-libraries/nextcloud-vue/issues/2315 "),
                              createBaseVNode("img", {
                                src: action.icon,
                                alt: "",
                                class: "user-actions__other__icon"
                              }, null, 8, _hoisted_11)
                            ]),
                            default: withCtx(() => [
                              createTextVNode(
                                " " + toDisplayString(action.title),
                                1
                                /* TEXT */
                              )
                            ]),
                            _: 2
                            /* DYNAMIC */
                          }, 1032, ["href", "target"]);
                        }),
                        128
                        /* KEYED_FRAGMENT */
                      ))
                    ]),
                    _: 1
                    /* STABLE */
                  })
                ])
              ]),
              createBaseVNode("div", _hoisted_12, [
                $setup.profileParameters.organisation || $setup.profileParameters.role || $setup.profileParameters.address ? (openBlock(), createElementBlock("div", _hoisted_13, [
                  $setup.profileParameters.organisation || $setup.profileParameters.role ? (openBlock(), createElementBlock("div", _hoisted_14, [
                    createBaseVNode("p", null, [
                      createTextVNode(
                        toDisplayString($setup.profileParameters.organisation) + " ",
                        1
                        /* TEXT */
                      ),
                      $setup.profileParameters.organisation && $setup.profileParameters.role ? (openBlock(), createElementBlock("span", _hoisted_15, "•")) : createCommentVNode("v-if", true),
                      createTextVNode(
                        " " + toDisplayString($setup.profileParameters.role),
                        1
                        /* TEXT */
                      )
                    ])
                  ])) : createCommentVNode("v-if", true),
                  $setup.profileParameters.address ? (openBlock(), createElementBlock("div", _hoisted_16, [
                    createBaseVNode("p", null, [
                      createVNode($setup["MapMarkerIcon"], {
                        class: "map-icon",
                        size: 16
                      }),
                      createTextVNode(
                        " " + toDisplayString($setup.profileParameters.address),
                        1
                        /* TEXT */
                      )
                    ])
                  ])) : createCommentVNode("v-if", true)
                ])) : createCommentVNode("v-if", true),
                $setup.profileParameters.headline || $setup.profileParameters.biography || $setup.sections.length > 0 ? (openBlock(), createElementBlock(
                  Fragment,
                  { key: 1 },
                  [
                    $setup.profileParameters.headline ? (openBlock(), createElementBlock(
                      "h3",
                      _hoisted_17,
                      toDisplayString($setup.profileParameters.headline),
                      1
                      /* TEXT */
                    )) : createCommentVNode("v-if", true),
                    $setup.profileParameters.biography ? (openBlock(), createBlock($setup["NcRichText"], {
                      key: 1,
                      text: $setup.profileParameters.biography,
                      useExtendedMarkdown: ""
                    }, null, 8, ["text"])) : createCommentVNode("v-if", true),
                    createCommentVNode(" additional entries, use it with cautious "),
                    (openBlock(true), createElementBlock(
                      Fragment,
                      null,
                      renderList($setup.sortedSections, (section) => {
                        return openBlock(), createBlock($setup["ProfileSection"], {
                          key: section.id,
                          section,
                          userId: $setup.profileParameters.userId
                        }, null, 8, ["section", "userId"]);
                      }),
                      128
                      /* KEYED_FRAGMENT */
                    ))
                  ],
                  64
                  /* STABLE_FRAGMENT */
                )) : (openBlock(), createBlock($setup["NcEmptyContent"], {
                  key: 2,
                  class: "profile__blocks-empty-info",
                  name: $setup.emptyProfileMessage,
                  description: $setup.t("profile", "The headline and about sections will show up here")
                }, {
                  icon: withCtx(() => [
                    createVNode($setup["AccountIcon"], { size: 60 })
                  ]),
                  _: 1
                  /* STABLE */
                }, 8, ["name", "description"]))
              ])
            ])
          ])
        ]),
        _: 1
        /* STABLE */
      })
    ]),
    _: 1
    /* STABLE */
  });
}
const ProfileApp = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-a8276e49"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/profile/src/views/ProfileApp.vue"]]);
class ProfileSections {
  #sections;
  constructor() {
    this.#sections = /* @__PURE__ */ new Map();
  }
  /**
   * @param section To be called to mount the section to the profile page
   */
  registerSection(section) {
    if (this.#sections.has(section.id)) {
      logger.warn(`Profile section with id '${section.id}' is already registered.`);
    }
    this.#sections.set(section.id, section);
  }
  getSections() {
    return [...this.#sections.values()];
  }
}
window.OCA.Profile ??= {};
window.OCA.Profile.ProfileSections = new ProfileSections();
const app = createApp(ProfileApp);
app.mount("#content");
//# sourceMappingURL=profile-main.mjs.map
