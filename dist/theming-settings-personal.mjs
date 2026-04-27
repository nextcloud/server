const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, k as useModel, n as computed, q as mergeModels, o as openBlock, f as createElementBlock, g as createBaseVNode, N as normalizeStyle, t as toDisplayString, h as createCommentVNode, E as withDirectives, G as vShow, x as createVNode, w as withCtx, j as createTextVNode, v as normalizeClass, m as mergeProps, y as ref, c as createBlock, S as useCssVars, F as Fragment, C as renderList, z as watch, l as useTemplateRef, P as nextTick, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { c as cancelableClient, i as isAxiosError } from "./index-D5H5XMHa.chunk.mjs";
import { a as showError, h as getFilePickerBuilder } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { p as generateFilePath, c as generateOcsUrl, b as generateUrl, d as debounce } from "./index-rAufP352.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { N as NcNoteCard, g as mdiUndo, h as mdiPaletteOutline, j as mdiImageEditOutline, k as mdiCheck } from "./mdi-BGU2G5q5.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { A as AppOrderSelector, l as logger, g as getTextColor, r as refreshStyles } from "./refreshStyles-BusgZcyi.chunk.mjs";
import NcColorPicker from "./index-DD39fp6M.chunk.mjs";
import { N as NcIconSvgWrapper } from "./Web-BOM4en5n.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./modulepreload-polyfill-mMY-eDcw.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
const _sfc_main$8 = /* @__PURE__ */ defineComponent({
  __name: "ThemePreviewItem",
  props: /* @__PURE__ */ mergeModels({
    enforced: { type: Boolean, required: false },
    theme: { type: Object, required: true },
    type: { type: String, required: true },
    unique: { type: Boolean, required: true }
  }, {
    "selected": { type: Boolean, ...{ required: true } },
    "selectedModifiers": {}
  }),
  emits: ["update:selected"],
  setup(__props, { expose: __expose }) {
    __expose();
    const selected = useModel(__props, "selected");
    const props = __props;
    const switchType = computed(() => props.unique ? "switch" : "radio");
    const name = computed(() => !props.unique ? props.type : null);
    const img = computed(() => generateFilePath("theming", "img", props.theme.id + ".jpg"));
    const checked = computed({
      get() {
        return selected.value;
      },
      set(checked2) {
        if (props.enforced) {
          return;
        }
        selected.value = props.unique ? checked2 : true;
      }
    });
    function onToggle() {
      if (props.enforced) {
        return;
      }
      if (switchType.value === "radio") {
        checked.value = true;
        return;
      }
      checked.value = !checked.value;
    }
    const __returned__ = { selected, props, switchType, name, img, checked, onToggle, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$5 = { class: "theming__preview-description" };
const _hoisted_2$5 = { class: "theming__preview-explanation" };
const _hoisted_3$5 = {
  key: 0,
  class: "theming__preview-warning",
  role: "note"
};
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass(["theming__preview--" + $props.theme.id, "theming__preview"])
    },
    [
      createBaseVNode(
        "div",
        {
          class: "theming__preview-image",
          style: normalizeStyle({ backgroundImage: "url(" + $setup.img + ")" }),
          onClick: $setup.onToggle
        },
        null,
        4
        /* STYLE */
      ),
      createBaseVNode("div", _hoisted_1$5, [
        createBaseVNode(
          "h3",
          null,
          toDisplayString($props.theme.title),
          1
          /* TEXT */
        ),
        createBaseVNode(
          "p",
          _hoisted_2$5,
          toDisplayString($props.theme.description),
          1
          /* TEXT */
        ),
        $props.enforced ? (openBlock(), createElementBlock(
          "span",
          _hoisted_3$5,
          toDisplayString($setup.t("theming", "Theme selection is enforced")),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true),
        createCommentVNode(" Only show checkbox if we can change themes "),
        withDirectives(createVNode($setup["NcCheckboxRadioSwitch"], {
          modelValue: $setup.checked,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.checked = $event),
          class: "theming__preview-toggle",
          disabled: $props.enforced,
          name: $setup.name,
          type: $setup.switchType
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($props.theme.enableLabel),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue", "disabled", "name", "type"]), [
          [vShow, !$props.enforced]
        ])
      ])
    ],
    2
    /* CLASS */
  );
}
const ThemePreviewItem = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$8], ["__scopeId", "data-v-6aa42a09"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/ThemePreviewItem.vue"]]);
const _sfc_main$7 = {
  name: "UndoIcon",
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
const _hoisted_3$4 = { d: "M12.5,8C9.85,8 7.45,9 5.6,10.6L2,7V16H11L7.38,12.38C8.77,11.22 10.54,10.5 12.5,10.5C16.04,10.5 19.05,12.81 20.1,16L22.47,15.22C21.08,11.03 17.15,8 12.5,8Z" };
const _hoisted_4$4 = { key: 0 };
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon undo-icon",
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
const IconUndo$1 = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Undo.vue"]]);
const elementIdAppOrderChanged = "theming-apporder-changed-infocard";
const elementIdEnforcedDefaultApp = "theming-apporder-changed-infocard";
const _sfc_main$6 = /* @__PURE__ */ defineComponent({
  __name: "UserSectionAppMenu",
  setup(__props, { expose: __expose }) {
    __expose();
    const {
      /** The app order currently defined by the user */
      userAppOrder,
      /** The enforced default app set by the administrator (if any) */
      enforcedDefaultApp
    } = loadState("theming", "navigationBar");
    const initialAppOrder = loadState("core", "apps").filter(({ type }) => type === "link").map((app2) => ({ ...app2, label: app2.name, default: app2.default && app2.id === enforcedDefaultApp }));
    const appOrder = ref([...initialAppOrder]);
    const hasCustomAppOrder = ref(!Array.isArray(userAppOrder) || Object.values(userAppOrder).length > 0);
    const hasAppOrderChanged = computed(() => initialAppOrder.some(({ id }, index) => id !== appOrder.value[index]?.id));
    const ariaDetailsAppOrder = computed(() => (hasAppOrderChanged.value ? `${elementIdAppOrderChanged} ` : "") + (enforcedDefaultApp ? elementIdEnforcedDefaultApp : ""));
    async function updateAppOrder(value) {
      const order = {};
      value.forEach(({ app: app2, id }, index) => {
        order[id] = { order: index, app: app2 };
      });
      try {
        await saveSetting("apporder", order);
        appOrder.value = value;
        hasCustomAppOrder.value = true;
      } catch (error) {
        logger.error("Could not set the app order", { error });
        showError(translate("theming", "Could not set the app order"));
      }
    }
    async function resetAppOrder() {
      try {
        await saveSetting("apporder", []);
        hasCustomAppOrder.value = false;
        const { data } = await cancelableClient.get(generateOcsUrl("/core/navigation/apps"), {
          headers: {
            "OCS-APIRequest": "true"
          }
        });
        appOrder.value = data.ocs.data.map((app2) => ({ ...app2, label: app2.name, default: app2.default && app2.app === enforcedDefaultApp }));
      } catch (error) {
        logger.error("Could not reset the app order", { error });
        showError(translate("theming", "Could not reset the app order"));
      }
    }
    async function saveSetting(key, value) {
      const url = generateOcsUrl("apps/provisioning_api/api/v1/config/users/{appId}/{configKey}", {
        appId: "core",
        configKey: key
      });
      return await cancelableClient.post(url, {
        configValue: JSON.stringify(value)
      });
    }
    const __returned__ = { userAppOrder, enforcedDefaultApp, initialAppOrder, appOrder, hasCustomAppOrder, hasAppOrderChanged, elementIdAppOrderChanged, elementIdEnforcedDefaultApp, ariaDetailsAppOrder, updateAppOrder, resetAppOrder, saveSetting, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, IconUndo: IconUndo$1, AppOrderSelector };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const userSectionAppMenu__selector = "_userSectionAppMenu__selector_1hkl7_2";
const style0$2 = {
  userSectionAppMenu__selector
};
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("theming", "Navigation bar settings")
  }, {
    default: withCtx(() => [
      createBaseVNode(
        "p",
        null,
        toDisplayString($setup.t("theming", "You can configure the app order used for the navigation bar. The first entry will be the default app, opened after login or when clicking on the logo.")),
        1
        /* TEXT */
      ),
      $setup.enforcedDefaultApp ? (openBlock(), createBlock($setup["NcNoteCard"], {
        key: 0,
        id: $setup.elementIdEnforcedDefaultApp,
        type: "info"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("theming", "The default app can not be changed because it was configured by the administrator.")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      $setup.hasAppOrderChanged ? (openBlock(), createBlock($setup["NcNoteCard"], {
        key: 1,
        id: $setup.elementIdAppOrderChanged,
        type: "info"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("theming", "The app order was changed, to see it in action you have to reload the page.")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      createVNode($setup["AppOrderSelector"], {
        class: normalizeClass(_ctx.$style.userSectionAppMenu__selector),
        "aria-details": $setup.ariaDetailsAppOrder,
        modelValue: $setup.appOrder,
        "onUpdate:modelValue": $setup.updateAppOrder
      }, null, 8, ["class", "aria-details", "modelValue"]),
      createVNode($setup["NcButton"], {
        "data-test-id": "btn-apporder-reset",
        disabled: !$setup.hasCustomAppOrder,
        variant: "tertiary",
        onClick: $setup.resetAppOrder
      }, {
        icon: withCtx(() => [
          createVNode($setup["IconUndo"], { size: 20 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("theming", "Reset default app order")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"])
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name"]);
}
const cssModules$2 = {
  "$style": style0$2
};
const UserSectionAppMenu = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__cssModules", cssModules$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/UserSectionAppMenu.vue"]]);
const _sfc_main$5 = /* @__PURE__ */ defineComponent({
  __name: "UserSectionBackground",
  emits: ["refreshStyles"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    useCssVars((_ctx) => ({
      "68beb2de-DEFAULT_BACKGROUND_IMAGE": DEFAULT_BACKGROUND_IMAGE
    }));
    const emit = __emit;
    const SHIPPED_BACKGROUNDS = loadState("theming", "shippedBackgrounds");
    const THEMING_DEFAULTS = loadState("theming", "themingDefaults");
    const DEFAULT_BACKGROUND_IMAGE = `url('${THEMING_DEFAULTS.backgroundImage}')`;
    const loading = ref(false);
    const currentTheming = ref(structuredClone(loadState("theming", "data")));
    const currentBackgroundImage = ref(loadState("theming", "userBackgroundImage"));
    const shippedBackgrounds = Object.keys(SHIPPED_BACKGROUNDS).filter((background) => {
      return background !== THEMING_DEFAULTS.defaultShippedBackground || THEMING_DEFAULTS.backgroundMime !== "";
    }).map((fileName) => {
      return {
        name: fileName,
        url: prefixWithBaseUrl(fileName),
        preview: prefixWithBaseUrl("preview/" + fileName),
        details: SHIPPED_BACKGROUNDS[fileName]
      };
    });
    function prefixWithBaseUrl(url) {
      return generateFilePath("theming", "", "img/background/") + url;
    }
    async function update(data) {
      currentBackgroundImage.value = data.backgroundImage;
      currentTheming.value.backgroundColor = data.backgroundColor;
      emit("refreshStyles");
      loading.value = false;
    }
    async function setDefault() {
      loading.value = "default";
      const result = await cancelableClient.post(generateUrl("/apps/theming/background/default"));
      update(result.data);
    }
    async function setShipped(shipped) {
      loading.value = shipped;
      const result = await cancelableClient.post(generateUrl("/apps/theming/background/shipped"), { value: shipped });
      update(result.data);
    }
    async function setFile(path) {
      loading.value = "custom";
      const result = await cancelableClient.post(generateUrl("/apps/theming/background/custom"), { value: path });
      update(result.data);
    }
    async function pickColor(color) {
      if (!color) {
        return;
      }
      loading.value = "color";
      const { data } = await cancelableClient.post(generateUrl("/apps/theming/background/color"), { color: color || "#0082c9" });
      update(data);
    }
    async function pickFile() {
      await getFilePickerBuilder(translate("theming", "Select a background from your files")).allowDirectories(false).setFilter((node) => node.mime.startsWith("image/")).setMultiSelect(false).addButton({
        label: translate("theming", "Select background"),
        callback: ([node]) => {
          setFile(node.path);
        },
        variant: "primary"
      }).build().pick();
    }
    const __returned__ = { emit, SHIPPED_BACKGROUNDS, THEMING_DEFAULTS, DEFAULT_BACKGROUND_IMAGE, loading, currentTheming, currentBackgroundImage, shippedBackgrounds, prefixWithBaseUrl, update, setDefault, setShipped, setFile, pickColor, pickFile, get mdiCheck() {
      return mdiCheck;
    }, get mdiImageEditOutline() {
      return mdiImageEditOutline;
    }, get mdiPaletteOutline() {
      return mdiPaletteOutline;
    }, get mdiUndo() {
      return mdiUndo;
    }, get t() {
      return translate;
    }, get NcColorPicker() {
      return NcColorPicker;
    }, get NcIconSvgWrapper() {
      return NcIconSvgWrapper;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, get getTextColor() {
      return getTextColor;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const backgroundSelect = "_backgroundSelect_l1nhm_2";
const backgroundSelect__entry = "_backgroundSelect__entry_l1nhm_7";
const backgroundSelect__entryColor = "_backgroundSelect__entryColor_l1nhm_38";
const backgroundSelect__entryFilePicker = "_backgroundSelect__entryFilePicker_l1nhm_42";
const backgroundSelect__entryDefault = "_backgroundSelect__entryDefault_l1nhm_52";
const style0$1 = {
  backgroundSelect,
  backgroundSelect__entry,
  backgroundSelect__entryColor,
  backgroundSelect__entryFilePicker,
  backgroundSelect__entryDefault
};
const _hoisted_1$3 = { class: "hidden-visually" };
const _hoisted_2$3 = ["aria-disabled", "aria-pressed", "aria-label", "title"];
const _hoisted_3$3 = ["aria-disabled", "aria-pressed", "aria-label", "title"];
const _hoisted_4$3 = ["aria-disabled", "aria-pressed", "aria-label", "title"];
const _hoisted_5 = { class: "hidden-visually" };
const _hoisted_6 = ["title", "aria-label", "aria-pressed", "onClick"];
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    class: "background",
    name: $setup.t("theming", "Background and color"),
    description: $setup.t("theming", "The background can be set to an image from the default set, a custom uploaded image, or a plain color.")
  }, {
    default: withCtx(() => [
      createBaseVNode("fieldset", null, [
        createBaseVNode(
          "legend",
          _hoisted_1$3,
          toDisplayString($setup.t("theming", "Background and color")),
          1
          /* TEXT */
        ),
        createBaseVNode(
          "div",
          {
            class: normalizeClass(_ctx.$style.backgroundSelect)
          },
          [
            createCommentVNode(" Custom background "),
            createBaseVNode("button", {
              "aria-disabled": $setup.loading === "custom",
              "aria-pressed": $setup.currentBackgroundImage === "custom",
              "aria-label": $setup.t("theming", "Custom background"),
              title: $setup.t("theming", "Custom background"),
              class: normalizeClass(["button-vue", [_ctx.$style.backgroundSelect__entry, _ctx.$style.backgroundSelect__entryFilePicker]]),
              onClick: $setup.pickFile
            }, [
              $setup.loading === "custom" ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
                key: 1,
                path: $setup.currentBackgroundImage === "custom" ? $setup.mdiCheck : $setup.mdiImageEditOutline
              }, null, 8, ["path"]))
            ], 10, _hoisted_2$3),
            createCommentVNode(" Custom color picker "),
            createVNode($setup["NcColorPicker"], {
              modelValue: $setup.currentTheming.backgroundColor,
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.currentTheming.backgroundColor = $event),
              onSubmit: $setup.pickColor
            }, {
              default: withCtx(() => [
                createBaseVNode("button", {
                  class: normalizeClass(["button-vue", [_ctx.$style.backgroundSelect__entry, _ctx.$style.backgroundSelect__entryColor]]),
                  "aria-disabled": $setup.loading === "color",
                  "aria-pressed": $setup.currentBackgroundImage === "color",
                  "aria-label": $setup.t("theming", "Plain background"),
                  title: $setup.t("theming", "Plain background"),
                  style: normalizeStyle({
                    backgroundColor: $setup.currentTheming.backgroundColor,
                    "--color-content": $setup.getTextColor($setup.currentTheming.backgroundColor)
                  })
                }, [
                  $setup.loading === "color" ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
                    key: 1,
                    path: $setup.currentBackgroundImage === "color" ? $setup.mdiCheck : $setup.mdiPaletteOutline
                  }, null, 8, ["path"]))
                ], 14, _hoisted_3$3)
              ]),
              _: 1
              /* STABLE */
            }, 8, ["modelValue"]),
            createCommentVNode(" Default background "),
            createBaseVNode("button", {
              class: normalizeClass(["button-vue", [_ctx.$style.backgroundSelect__entry, _ctx.$style.backgroundSelect__entryDefault]]),
              "aria-disabled": $setup.loading === "default",
              "aria-pressed": $setup.currentBackgroundImage === "default",
              "aria-label": $setup.t("theming", "Default background"),
              title: $setup.t("theming", "Default background"),
              style: normalizeStyle({
                "--color-content": $setup.getTextColor($setup.THEMING_DEFAULTS.backgroundColor)
              }),
              onClick: $setup.setDefault
            }, [
              $setup.loading === "default" ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
                key: 1,
                path: $setup.currentBackgroundImage === "default" ? $setup.mdiCheck : $setup.mdiUndo
              }, null, 8, ["path"]))
            ], 14, _hoisted_4$3)
          ],
          2
          /* CLASS */
        ),
        createCommentVNode(" Background set selection "),
        createBaseVNode(
          "fieldset",
          {
            class: normalizeClass(_ctx.$style.backgroundSelect)
          },
          [
            createBaseVNode(
              "label",
              _hoisted_5,
              toDisplayString($setup.t("theming", "Default shipped background images")),
              1
              /* TEXT */
            ),
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($setup.shippedBackgrounds, (shippedBackground) => {
                return openBlock(), createElementBlock("button", {
                  key: shippedBackground.name,
                  title: shippedBackground.details.attribution,
                  "aria-label": shippedBackground.details.description,
                  "aria-pressed": $setup.currentBackgroundImage === shippedBackground.name,
                  class: normalizeClass(["button-vue", _ctx.$style.backgroundSelect__entry]),
                  style: normalizeStyle({
                    backgroundImage: "url(" + shippedBackground.preview + ")"
                  }),
                  tabindex: "0",
                  onClick: ($event) => $setup.setShipped(shippedBackground.name)
                }, [
                  $setup.currentBackgroundImage === shippedBackground.name ? (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
                    key: 0,
                    class: normalizeClass(_ctx.$style.backgroundSelect__entryIcon),
                    path: $setup.mdiCheck
                  }, null, 8, ["class", "path"])) : createCommentVNode("v-if", true)
                ], 14, _hoisted_6);
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ],
          2
          /* CLASS */
        )
      ])
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description"]);
}
const cssModules$1 = {
  "$style": style0$1
};
const UserSectionBackground = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__cssModules", cssModules$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/UserSectionBackground.vue"]]);
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "UserSectionHotkeys",
  setup(__props, { expose: __expose }) {
    __expose();
    const shortcutsDisabled = ref(loadState("theming", "shortcutsDisabled", false));
    watch(shortcutsDisabled, updateHotkeyState);
    async function updateHotkeyState() {
      const url = generateOcsUrl("apps/provisioning_api/api/v1/config/users/{appId}/{configKey}", {
        appId: "theming",
        configKey: "shortcuts_disabled"
      });
      if (shortcutsDisabled.value) {
        await cancelableClient.post(url, {
          configValue: "yes"
        });
      } else {
        await cancelableClient.delete(url);
      }
    }
    const __returned__ = { shortcutsDisabled, updateHotkeyState, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("theming", "Keyboard shortcuts"),
    description: $setup.t("theming", "In some cases keyboard shortcuts can interfere with accessibility tools. In order to allow focusing on your tool correctly you can disable all keyboard shortcuts here. This will also disable all available shortcuts in apps.")
  }, {
    default: withCtx(() => [
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.shortcutsDisabled,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.shortcutsDisabled = $event),
        class: "theming__preview-toggle",
        type: "switch"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("theming", "Disable all keyboard shortcuts")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"])
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description"]);
}
const UserSectionHotkeys = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/UserSectionHotkeys.vue"]]);
var r = { grad: 0.9, turn: 360, rad: 360 / (2 * Math.PI) }, t = function(r2) {
  return "string" == typeof r2 ? r2.length > 0 : "number" == typeof r2;
}, n = function(r2, t2, n2) {
  return void 0 === t2 && (t2 = 0), void 0 === n2 && (n2 = Math.pow(10, t2)), Math.round(n2 * r2) / n2 + 0;
}, e = function(r2, t2, n2) {
  return void 0 === t2 && (t2 = 0), void 0 === n2 && (n2 = 1), r2 > n2 ? n2 : r2 > t2 ? r2 : t2;
}, u = function(r2) {
  return (r2 = isFinite(r2) ? r2 % 360 : 0) > 0 ? r2 : r2 + 360;
}, a = function(r2) {
  return { r: e(r2.r, 0, 255), g: e(r2.g, 0, 255), b: e(r2.b, 0, 255), a: e(r2.a) };
}, o = function(r2) {
  return { r: n(r2.r), g: n(r2.g), b: n(r2.b), a: n(r2.a, 3) };
}, i = /^#([0-9a-f]{3,8})$/i, s = function(r2) {
  var t2 = r2.toString(16);
  return t2.length < 2 ? "0" + t2 : t2;
}, h = function(r2) {
  var t2 = r2.r, n2 = r2.g, e2 = r2.b, u2 = r2.a, a2 = Math.max(t2, n2, e2), o2 = a2 - Math.min(t2, n2, e2), i2 = o2 ? a2 === t2 ? (n2 - e2) / o2 : a2 === n2 ? 2 + (e2 - t2) / o2 : 4 + (t2 - n2) / o2 : 0;
  return { h: 60 * (i2 < 0 ? i2 + 6 : i2), s: a2 ? o2 / a2 * 100 : 0, v: a2 / 255 * 100, a: u2 };
}, b = function(r2) {
  var t2 = r2.h, n2 = r2.s, e2 = r2.v, u2 = r2.a;
  t2 = t2 / 360 * 6, n2 /= 100, e2 /= 100;
  var a2 = Math.floor(t2), o2 = e2 * (1 - n2), i2 = e2 * (1 - (t2 - a2) * n2), s2 = e2 * (1 - (1 - t2 + a2) * n2), h2 = a2 % 6;
  return { r: 255 * [e2, i2, o2, o2, s2, e2][h2], g: 255 * [s2, e2, e2, i2, o2, o2][h2], b: 255 * [o2, o2, s2, e2, e2, i2][h2], a: u2 };
}, g = function(r2) {
  return { h: u(r2.h), s: e(r2.s, 0, 100), l: e(r2.l, 0, 100), a: e(r2.a) };
}, d = function(r2) {
  return { h: n(r2.h), s: n(r2.s), l: n(r2.l), a: n(r2.a, 3) };
}, f = function(r2) {
  return b((n2 = (t2 = r2).s, { h: t2.h, s: (n2 *= ((e2 = t2.l) < 50 ? e2 : 100 - e2) / 100) > 0 ? 2 * n2 / (e2 + n2) * 100 : 0, v: e2 + n2, a: t2.a }));
  var t2, n2, e2;
}, c = function(r2) {
  return { h: (t2 = h(r2)).h, s: (u2 = (200 - (n2 = t2.s)) * (e2 = t2.v) / 100) > 0 && u2 < 200 ? n2 * e2 / 100 / (u2 <= 100 ? u2 : 200 - u2) * 100 : 0, l: u2 / 2, a: t2.a };
  var t2, n2, e2, u2;
}, l = /^hsla?\(\s*([+-]?\d*\.?\d+)(deg|rad|grad|turn)?\s*,\s*([+-]?\d*\.?\d+)%\s*,\s*([+-]?\d*\.?\d+)%\s*(?:,\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i, p = /^hsla?\(\s*([+-]?\d*\.?\d+)(deg|rad|grad|turn)?\s+([+-]?\d*\.?\d+)%\s+([+-]?\d*\.?\d+)%\s*(?:\/\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i, v = /^rgba?\(\s*([+-]?\d*\.?\d+)(%)?\s*,\s*([+-]?\d*\.?\d+)(%)?\s*,\s*([+-]?\d*\.?\d+)(%)?\s*(?:,\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i, m = /^rgba?\(\s*([+-]?\d*\.?\d+)(%)?\s+([+-]?\d*\.?\d+)(%)?\s+([+-]?\d*\.?\d+)(%)?\s*(?:\/\s*([+-]?\d*\.?\d+)(%)?\s*)?\)$/i, y = { string: [[function(r2) {
  var t2 = i.exec(r2);
  return t2 ? (r2 = t2[1]).length <= 4 ? { r: parseInt(r2[0] + r2[0], 16), g: parseInt(r2[1] + r2[1], 16), b: parseInt(r2[2] + r2[2], 16), a: 4 === r2.length ? n(parseInt(r2[3] + r2[3], 16) / 255, 2) : 1 } : 6 === r2.length || 8 === r2.length ? { r: parseInt(r2.substr(0, 2), 16), g: parseInt(r2.substr(2, 2), 16), b: parseInt(r2.substr(4, 2), 16), a: 8 === r2.length ? n(parseInt(r2.substr(6, 2), 16) / 255, 2) : 1 } : null : null;
}, "hex"], [function(r2) {
  var t2 = v.exec(r2) || m.exec(r2);
  return t2 ? t2[2] !== t2[4] || t2[4] !== t2[6] ? null : a({ r: Number(t2[1]) / (t2[2] ? 100 / 255 : 1), g: Number(t2[3]) / (t2[4] ? 100 / 255 : 1), b: Number(t2[5]) / (t2[6] ? 100 / 255 : 1), a: void 0 === t2[7] ? 1 : Number(t2[7]) / (t2[8] ? 100 : 1) }) : null;
}, "rgb"], [function(t2) {
  var n2 = l.exec(t2) || p.exec(t2);
  if (!n2) return null;
  var e2, u2, a2 = g({ h: (e2 = n2[1], u2 = n2[2], void 0 === u2 && (u2 = "deg"), Number(e2) * (r[u2] || 1)), s: Number(n2[3]), l: Number(n2[4]), a: void 0 === n2[5] ? 1 : Number(n2[5]) / (n2[6] ? 100 : 1) });
  return f(a2);
}, "hsl"]], object: [[function(r2) {
  var n2 = r2.r, e2 = r2.g, u2 = r2.b, o2 = r2.a, i2 = void 0 === o2 ? 1 : o2;
  return t(n2) && t(e2) && t(u2) ? a({ r: Number(n2), g: Number(e2), b: Number(u2), a: Number(i2) }) : null;
}, "rgb"], [function(r2) {
  var n2 = r2.h, e2 = r2.s, u2 = r2.l, a2 = r2.a, o2 = void 0 === a2 ? 1 : a2;
  if (!t(n2) || !t(e2) || !t(u2)) return null;
  var i2 = g({ h: Number(n2), s: Number(e2), l: Number(u2), a: Number(o2) });
  return f(i2);
}, "hsl"], [function(r2) {
  var n2 = r2.h, a2 = r2.s, o2 = r2.v, i2 = r2.a, s2 = void 0 === i2 ? 1 : i2;
  if (!t(n2) || !t(a2) || !t(o2)) return null;
  var h2 = (function(r3) {
    return { h: u(r3.h), s: e(r3.s, 0, 100), v: e(r3.v, 0, 100), a: e(r3.a) };
  })({ h: Number(n2), s: Number(a2), v: Number(o2), a: Number(s2) });
  return b(h2);
}, "hsv"]] }, N = function(r2, t2) {
  for (var n2 = 0; n2 < t2.length; n2++) {
    var e2 = t2[n2][0](r2);
    if (e2) return [e2, t2[n2][1]];
  }
  return [null, void 0];
}, x = function(r2) {
  return "string" == typeof r2 ? N(r2.trim(), y.string) : "object" == typeof r2 && null !== r2 ? N(r2, y.object) : [null, void 0];
}, M = function(r2, t2) {
  var n2 = c(r2);
  return { h: n2.h, s: e(n2.s + 100 * t2, 0, 100), l: n2.l, a: n2.a };
}, H = function(r2) {
  return (299 * r2.r + 587 * r2.g + 114 * r2.b) / 1e3 / 255;
}, $ = function(r2, t2) {
  var n2 = c(r2);
  return { h: n2.h, s: n2.s, l: e(n2.l + 100 * t2, 0, 100), a: n2.a };
}, j = (function() {
  function r2(r3) {
    this.parsed = x(r3)[0], this.rgba = this.parsed || { r: 0, g: 0, b: 0, a: 1 };
  }
  return r2.prototype.isValid = function() {
    return null !== this.parsed;
  }, r2.prototype.brightness = function() {
    return n(H(this.rgba), 2);
  }, r2.prototype.isDark = function() {
    return H(this.rgba) < 0.5;
  }, r2.prototype.isLight = function() {
    return H(this.rgba) >= 0.5;
  }, r2.prototype.toHex = function() {
    return r3 = o(this.rgba), t2 = r3.r, e2 = r3.g, u2 = r3.b, i2 = (a2 = r3.a) < 1 ? s(n(255 * a2)) : "", "#" + s(t2) + s(e2) + s(u2) + i2;
    var r3, t2, e2, u2, a2, i2;
  }, r2.prototype.toRgb = function() {
    return o(this.rgba);
  }, r2.prototype.toRgbString = function() {
    return r3 = o(this.rgba), t2 = r3.r, n2 = r3.g, e2 = r3.b, (u2 = r3.a) < 1 ? "rgba(" + t2 + ", " + n2 + ", " + e2 + ", " + u2 + ")" : "rgb(" + t2 + ", " + n2 + ", " + e2 + ")";
    var r3, t2, n2, e2, u2;
  }, r2.prototype.toHsl = function() {
    return d(c(this.rgba));
  }, r2.prototype.toHslString = function() {
    return r3 = d(c(this.rgba)), t2 = r3.h, n2 = r3.s, e2 = r3.l, (u2 = r3.a) < 1 ? "hsla(" + t2 + ", " + n2 + "%, " + e2 + "%, " + u2 + ")" : "hsl(" + t2 + ", " + n2 + "%, " + e2 + "%)";
    var r3, t2, n2, e2, u2;
  }, r2.prototype.toHsv = function() {
    return r3 = h(this.rgba), { h: n(r3.h), s: n(r3.s), v: n(r3.v), a: n(r3.a, 3) };
    var r3;
  }, r2.prototype.invert = function() {
    return w({ r: 255 - (r3 = this.rgba).r, g: 255 - r3.g, b: 255 - r3.b, a: r3.a });
    var r3;
  }, r2.prototype.saturate = function(r3) {
    return void 0 === r3 && (r3 = 0.1), w(M(this.rgba, r3));
  }, r2.prototype.desaturate = function(r3) {
    return void 0 === r3 && (r3 = 0.1), w(M(this.rgba, -r3));
  }, r2.prototype.grayscale = function() {
    return w(M(this.rgba, -1));
  }, r2.prototype.lighten = function(r3) {
    return void 0 === r3 && (r3 = 0.1), w($(this.rgba, r3));
  }, r2.prototype.darken = function(r3) {
    return void 0 === r3 && (r3 = 0.1), w($(this.rgba, -r3));
  }, r2.prototype.rotate = function(r3) {
    return void 0 === r3 && (r3 = 15), this.hue(this.hue() + r3);
  }, r2.prototype.alpha = function(r3) {
    return "number" == typeof r3 ? w({ r: (t2 = this.rgba).r, g: t2.g, b: t2.b, a: r3 }) : n(this.rgba.a, 3);
    var t2;
  }, r2.prototype.hue = function(r3) {
    var t2 = c(this.rgba);
    return "number" == typeof r3 ? w({ h: r3, s: t2.s, l: t2.l, a: t2.a }) : n(t2.h);
  }, r2.prototype.isEqual = function(r3) {
    return this.toHex() === w(r3).toHex();
  }, r2;
})(), w = function(r2) {
  return r2 instanceof j ? r2 : new j(r2);
};
const _sfc_main$3 = {
  name: "PaletteOutlineIcon",
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
const _hoisted_3$2 = { d: "M12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2C17.5,2 22,6 22,11A6,6 0 0,1 16,17H14.2C13.9,17 13.7,17.2 13.7,17.5C13.7,17.6 13.8,17.7 13.8,17.8C14.2,18.3 14.4,18.9 14.4,19.5C14.5,20.9 13.4,22 12,22M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C12.3,20 12.5,19.8 12.5,19.5C12.5,19.3 12.4,19.2 12.4,19.1C12,18.6 11.8,18.1 11.8,17.5C11.8,16.1 12.9,15 14.3,15H16A4,4 0 0,0 20,11C20,7.1 16.4,4 12,4M6.5,10C7.3,10 8,10.7 8,11.5C8,12.3 7.3,13 6.5,13C5.7,13 5,12.3 5,11.5C5,10.7 5.7,10 6.5,10M9.5,6C10.3,6 11,6.7 11,7.5C11,8.3 10.3,9 9.5,9C8.7,9 8,8.3 8,7.5C8,6.7 8.7,6 9.5,6M14.5,6C15.3,6 16,6.7 16,7.5C16,8.3 15.3,9 14.5,9C13.7,9 13,8.3 13,7.5C13,6.7 13.7,6 14.5,6M17.5,10C18.3,10 19,10.7 19,11.5C19,12.3 18.3,13 17.5,13C16.7,13 16,12.3 16,11.5C16,10.7 16.7,10 17.5,10Z" };
const _hoisted_4$2 = { key: 0 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon palette-outline-icon",
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
const IconColorPalette = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/PaletteOutline.vue"]]);
const _sfc_main$2 = {
  name: "UndoVariantIcon",
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
const _hoisted_3$1 = { d: "M13.5,7A6.5,6.5 0 0,1 20,13.5A6.5,6.5 0 0,1 13.5,20H10V18H13.5C16,18 18,16 18,13.5C18,11 16,9 13.5,9H7.83L10.91,12.09L9.5,13.5L4,8L9.5,2.5L10.92,3.91L7.83,7H13.5M6,18H8V20H6V18Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon undo-variant-icon",
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
const IconUndo = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/UndoVariant.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "UserSectionPrimaryColor",
  emits: ["refreshStyles"],
  setup(__props, { expose: __expose, emit: __emit }) {
    const emit = __emit;
    __expose({ reload });
    const { primaryColor: initialPrimaryColor, defaultPrimaryColor } = loadState("theming", "data", { primaryColor: "#0082c9", defaultPrimaryColor: "#0082c9" });
    const triggerElement = useTemplateRef("trigger");
    const loading = ref(false);
    const primaryColor = ref(initialPrimaryColor);
    watch(primaryColor, debounce((newColor) => {
      onUpdate(newColor);
    }, 1e3));
    const isDefaultPrimaryColor = computed(() => w(primaryColor.value).isEqual(w(defaultPrimaryColor)));
    function reload() {
      let newColor = window.getComputedStyle(triggerElement.value).backgroundColor;
      const rgbMatch = newColor.replaceAll(/\s/g, "").match(/^rgba?\((\d+),(\d+),(\d+)/);
      if (rgbMatch) {
        newColor = `#${numberToHex(rgbMatch[1])}${numberToHex(rgbMatch[2])}${numberToHex(rgbMatch[3])}`;
      }
      if (newColor.toLowerCase() !== primaryColor.value.toLowerCase()) {
        primaryColor.value = newColor;
      }
    }
    function onReset() {
      primaryColor.value = defaultPrimaryColor;
      onUpdate(null);
    }
    async function onUpdate(value) {
      loading.value = true;
      const url = generateOcsUrl("apps/provisioning_api/api/v1/config/users/{appId}/{configKey}", {
        appId: "theming",
        configKey: "primary_color"
      });
      try {
        if (value) {
          await cancelableClient.post(url, {
            configValue: value
          });
        } else {
          await cancelableClient.delete(url);
        }
        emit("refreshStyles");
      } catch (error) {
        logger.error("Could not update primary color", { error });
        showError(translate("theming", "Could not set primary color"));
      }
      loading.value = false;
    }
    function numberToHex(numeric) {
      const parsed = Number.parseInt(numeric);
      return parsed.toString(16).padStart(2, "0");
    }
    const __returned__ = { emit, initialPrimaryColor, defaultPrimaryColor, triggerElement, loading, primaryColor, isDefaultPrimaryColor, reload, onReset, onUpdate, numberToHex, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcColorPicker() {
      return NcColorPicker;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, IconColorPalette, IconUndo };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const userPrimaryColor = "_userPrimaryColor_3oh6f_1";
const userPrimaryColor__trigger = "_userPrimaryColor__trigger_3oh6f_8";
const style0 = {
  userPrimaryColor,
  userPrimaryColor__trigger
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("theming", "Primary color"),
    description: $setup.t("theming", "Set a primary color to highlight important elements. The color used for elements such as primary buttons might differ a bit as it gets adjusted to fulfill accessibility requirements.")
  }, {
    default: withCtx(() => [
      createBaseVNode(
        "div",
        {
          class: normalizeClass(_ctx.$style.userPrimaryColor)
        },
        [
          createVNode($setup["NcColorPicker"], {
            modelValue: $setup.primaryColor,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.primaryColor = $event),
            "data-user-theming-primary-color": ""
          }, {
            default: withCtx(() => [
              createBaseVNode(
                "button",
                {
                  ref: "trigger",
                  class: normalizeClass(_ctx.$style.userPrimaryColor__trigger),
                  style: normalizeStyle({ "background-color": $setup.primaryColor }),
                  "data-user-theming-primary-color-trigger": ""
                },
                [
                  createTextVNode(
                    toDisplayString($setup.t("theming", "Primary color")) + " ",
                    1
                    /* TEXT */
                  ),
                  $setup.loading ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : (openBlock(), createBlock($setup["IconColorPalette"], {
                    key: 1,
                    size: 20
                  }))
                ],
                6
                /* CLASS, STYLE */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["modelValue"]),
          createVNode($setup["NcButton"], {
            variant: "tertiary",
            disabled: $setup.isDefaultPrimaryColor,
            onClick: $setup.onReset
          }, {
            icon: withCtx(() => [
              createVNode($setup["IconUndo"], { size: 20 })
            ]),
            default: withCtx(() => [
              createTextVNode(
                " " + toDisplayString($setup.t("theming", "Reset primary color")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["disabled"])
        ],
        2
        /* CLASS */
      )
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description"]);
}
const cssModules = {
  "$style": style0
};
const UserSectionPrimaryColor = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/UserSectionPrimaryColor.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "UserTheming",
  setup(__props, { expose: __expose }) {
    __expose();
    const enforceTheme = loadState("theming", "enforceTheme", "");
    const isUserThemingDisabled = loadState("theming", "isUserThemingDisabled");
    const enableBlurFilter = ref(loadState("theming", "enableBlurFilter", ""));
    const availableThemes = loadState("theming", "themes", []);
    const themes = ref(availableThemes.filter((theme) => theme.type === 1));
    const fonts = ref(availableThemes.filter((theme) => theme.type === 2));
    const selectedTheme = computed(() => themes.value.find((theme) => theme.enabled) || themes.value[0]);
    const primaryColorSection = useTemplateRef("primaryColor");
    const description = translate(
      "theming",
      "Universal access is very important to us. We follow web standards and check to make everything usable also without mouse, and assistive software such as screenreaders. We aim to be compliant with the {linkstart}Web Content Accessibility Guidelines{linkend} 2.1 on AA level, with the high contrast theme even on AAA level.",
      {
        linkstart: '<a target="_blank" href="https://www.w3.org/WAI/standards-guidelines/wcag/" rel="noreferrer nofollow">',
        linkend: "</a>"
      },
      { escape: false }
    );
    const descriptionDetail = translate(
      "theming",
      "If you find any issues, do not hesitate to report them on {issuetracker}our issue tracker{linkend}. And if you want to get involved, come join {designteam}our design team{linkend}!",
      {
        issuetracker: '<a target="_blank" href="https://github.com/nextcloud/server/issues/" rel="noreferrer nofollow">',
        designteam: '<a target="_blank" href="https://nextcloud.com/design" rel="noreferrer nofollow">',
        linkend: "</a>"
      },
      { escape: false }
    );
    async function refreshGlobalStyles() {
      await refreshStyles();
      nextTick(() => primaryColorSection.value?.reload());
    }
    function changeTheme(id, enabled) {
      themes.value.forEach((theme) => {
        if (theme.id === id && enabled) {
          theme.enabled = true;
          return;
        }
        theme.enabled = false;
      });
      updateBodyAttributes();
      selectItem(enabled, id);
    }
    function changeFont(id, enabled) {
      fonts.value.forEach((font) => {
        if (font.id === id && enabled) {
          font.enabled = true;
          return;
        }
        font.enabled = false;
      });
      updateBodyAttributes();
      selectItem(enabled, id);
    }
    async function changeEnableBlurFilter() {
      enableBlurFilter.value = enableBlurFilter.value === "no" ? "yes" : "no";
      await cancelableClient({
        url: generateOcsUrl("apps/provisioning_api/api/v1/config/users/{appId}/{configKey}", {
          appId: "theming",
          configKey: "force_enable_blur_filter"
        }),
        data: {
          configValue: enableBlurFilter.value
        },
        method: "POST"
      });
      refreshStyles();
    }
    function updateBodyAttributes() {
      const enabledThemesIDs = themes.value.filter((theme) => theme.enabled === true).map((theme) => theme.id);
      const enabledFontsIDs = fonts.value.filter((font) => font.enabled === true).map((font) => font.id);
      themes.value.forEach((theme) => {
        document.body.toggleAttribute(`data-theme-${theme.id}`, theme.enabled);
      });
      fonts.value.forEach((font) => {
        document.body.toggleAttribute(`data-theme-${font.id}`, font.enabled);
      });
      document.body.setAttribute("data-themes", [...enabledThemesIDs, ...enabledFontsIDs].join(","));
    }
    async function selectItem(enabled, themeId) {
      try {
        if (enabled) {
          await cancelableClient({
            url: generateOcsUrl("apps/theming/api/v1/theme/{themeId}/enable", { themeId }),
            method: "PUT"
          });
        } else {
          await cancelableClient({
            url: generateOcsUrl("apps/theming/api/v1/theme/{themeId}", { themeId }),
            method: "DELETE"
          });
        }
      } catch (error) {
        logger.error("theming: Unable to apply setting.", { error });
        let message = translate("theming", "Unable to apply the setting.");
        if (isAxiosError(error) && error.response?.data.ocs?.meta?.message) {
          message = `${error.response.data.ocs.meta.message}. ${message}`;
        }
        showError(message);
      }
    }
    const __returned__ = { enforceTheme, isUserThemingDisabled, enableBlurFilter, availableThemes, themes, fonts, selectedTheme, primaryColorSection, description, descriptionDetail, refreshGlobalStyles, changeTheme, changeFont, changeEnableBlurFilter, updateBodyAttributes, selectItem, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, ThemePreviewItem, UserSectionAppMenu, UserSectionBackground, UserSectionHotkeys, UserSectionPrimaryColor };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1 = ["innerHTML"];
const _hoisted_2 = ["innerHTML"];
const _hoisted_3 = { class: "theming__preview-list" };
const _hoisted_4 = { class: "theming__preview-list" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    Fragment,
    null,
    [
      createVNode($setup["NcSettingsSection"], {
        name: $setup.t("theming", "Appearance and accessibility settings"),
        class: "theming"
      }, {
        default: withCtx(() => [
          createCommentVNode(" eslint-disable-next-line vue/no-v-html "),
          createBaseVNode("p", { innerHTML: $setup.description }, null, 8, _hoisted_1),
          createCommentVNode(" eslint-disable-next-line vue/no-v-html "),
          createBaseVNode("p", { innerHTML: $setup.descriptionDetail }, null, 8, _hoisted_2),
          createBaseVNode("div", _hoisted_3, [
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($setup.themes, (theme) => {
                return openBlock(), createBlock($setup["ThemePreviewItem"], {
                  key: theme.id,
                  enforced: theme.id === $setup.enforceTheme,
                  selected: $setup.selectedTheme.id === theme.id,
                  theme,
                  unique: $setup.themes.length === 1,
                  type: "theme",
                  "onUpdate:selected": ($event) => $setup.changeTheme(theme.id, $event)
                }, null, 8, ["enforced", "selected", "theme", "unique", "onUpdate:selected"]);
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ]),
          createBaseVNode("div", _hoisted_4, [
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($setup.fonts, (theme) => {
                return openBlock(), createBlock($setup["ThemePreviewItem"], {
                  key: theme.id,
                  selected: theme.enabled,
                  theme,
                  unique: $setup.fonts.length === 1,
                  type: "font",
                  "onUpdate:selected": ($event) => $setup.changeFont(theme.id, $event)
                }, null, 8, ["selected", "theme", "unique", "onUpdate:selected"]);
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ]),
          createBaseVNode(
            "h3",
            null,
            toDisplayString($setup.t("theming", "Misc accessibility options")),
            1
            /* TEXT */
          ),
          createVNode($setup["NcCheckboxRadioSwitch"], {
            type: "checkbox",
            modelValue: $setup.enableBlurFilter === "yes",
            indeterminate: $setup.enableBlurFilter === "",
            "onUpdate:modelValue": $setup.changeEnableBlurFilter
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("theming", "Enable blur background filter (may increase GPU load)")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["modelValue", "indeterminate"])
        ]),
        _: 1
        /* STABLE */
      }, 8, ["name"]),
      $setup.isUserThemingDisabled ? (openBlock(), createBlock($setup["NcNoteCard"], {
        key: 0,
        type: "info"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("theming", "Customization has been disabled by your administrator")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : (openBlock(), createElementBlock(
        Fragment,
        { key: 1 },
        [
          createVNode(
            $setup["UserSectionPrimaryColor"],
            {
              ref: "primaryColor",
              onRefreshStyles: $setup.refreshGlobalStyles
            },
            null,
            512
            /* NEED_PATCH */
          ),
          createVNode($setup["UserSectionBackground"], { onRefreshStyles: $setup.refreshGlobalStyles })
        ],
        64
        /* STABLE_FRAGMENT */
      )),
      createVNode($setup["UserSectionHotkeys"]),
      createVNode($setup["UserSectionAppMenu"])
    ],
    64
    /* STABLE_FRAGMENT */
  );
}
const UserTheming = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-fb0d7f48"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/views/UserTheming.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const app = createApp(UserTheming);
app.config.idPrefix = "settings";
app.mount("#settings-personal-theming");
//# sourceMappingURL=theming-settings-personal.mjs.map
