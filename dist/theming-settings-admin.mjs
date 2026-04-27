const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, W as useId, y as ref, z as watch, o as openBlock, c as createBlock, w as withCtx, g as createBaseVNode, t as toDisplayString, x as createVNode, j as createTextVNode, f as createElementBlock, h as createCommentVNode, X as toValue, Y as readonly, Z as isRef, $ as isReadonly, a0 as toRef, p as createSlots, v as normalizeClass, n as computed, N as normalizeStyle, i as renderSlot, S as useCssVars, l as useTemplateRef, F as Fragment, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { l as loadState, _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import { g as mdiUndo, h as mdiPaletteOutline, i as mdiImageOutline, N as NcNoteCard } from "./mdi-BGU2G5q5.chunk.mjs";
import { c as cancelableClient, i as isAxiosError } from "./index-D5H5XMHa.chunk.mjs";
import { a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { b as generateUrl } from "./index-rAufP352.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { l as logger, A as AppOrderSelector, g as getTextColor, r as refreshStyles } from "./refreshStyles-BusgZcyi.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { _ as _sfc_main$7 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { w as watchDebounced } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import NcColorPicker from "./index-DD39fp6M.chunk.mjs";
import { N as NcIconSvgWrapper } from "./Web-BOM4en5n.chunk.mjs";
import "./modulepreload-polyfill-mMY-eDcw.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
const _sfc_main$6 = /* @__PURE__ */ defineComponent({
  __name: "AdminSectionAppMenu",
  setup(__props, { expose: __expose }) {
    __expose();
    const idGlobalDefaultApp = useId();
    const { defaultApps } = loadState("theming", "adminThemingParameters");
    const allApps = loadState("core", "apps").map(({ id, name, icon }) => ({ label: name, id, icon }));
    const selectedApps = ref(defaultApps.map((id) => allApps.find((app2) => app2.id === id)).filter(Boolean));
    watch(selectedApps, async (value) => {
      try {
        await saveSetting("defaultApps", value.map((app2) => app2.id));
      } catch (error) {
        logger.error("Could not set global default apps", { error });
        showError(translate("theming", "Could not set global default apps"));
      }
    });
    const hasCustomDefaultApp = ref(defaultApps.length > 0);
    watch(hasCustomDefaultApp, (checked) => {
      selectedApps.value = checked ? allApps.filter((app2) => ["dashboard", "files"].includes(app2.id)) : [];
    });
    async function saveSetting(key, value) {
      const url = generateUrl("/apps/theming/ajax/updateAppMenu");
      return await cancelableClient.put(url, {
        setting: key,
        value
      });
    }
    const __returned__ = { idGlobalDefaultApp, defaultApps, allApps, selectedApps, hasCustomDefaultApp, saveSetting, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcSelect() {
      return NcSelect;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, AppOrderSelector };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$1 = { class: "info-note" };
const _hoisted_2$1 = ["aria-labelledby"];
const _hoisted_3 = ["id"];
const _hoisted_4 = { class: "info-note" };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("theming", "Navigation bar settings")
  }, {
    default: withCtx(() => [
      createBaseVNode(
        "h3",
        null,
        toDisplayString($setup.t("theming", "Default app")),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "p",
        _hoisted_1$1,
        toDisplayString($setup.t("theming", "The default app is the app that is e.g. opened after login or when the logo in the menu is clicked.")),
        1
        /* TEXT */
      ),
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.hasCustomDefaultApp,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.hasCustomDefaultApp = $event),
        type: "switch"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("theming", "Use custom default app")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"]),
      $setup.hasCustomDefaultApp ? (openBlock(), createElementBlock("section", {
        key: 0,
        "aria-labelledby": $setup.idGlobalDefaultApp
      }, [
        createBaseVNode("h4", { id: $setup.idGlobalDefaultApp }, toDisplayString($setup.t("theming", "Global default app")), 9, _hoisted_3),
        createVNode($setup["NcSelect"], {
          modelValue: $setup.selectedApps,
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.selectedApps = $event),
          keepOpen: "",
          multiple: "",
          placeholder: $setup.t("theming", "Global default apps"),
          options: $setup.allApps
        }, null, 8, ["modelValue", "placeholder", "options"]),
        createBaseVNode(
          "h5",
          null,
          toDisplayString($setup.t("theming", "Default app priority")),
          1
          /* TEXT */
        ),
        createBaseVNode(
          "p",
          _hoisted_4,
          toDisplayString($setup.t("theming", "If an app is not enabled for a user, the next app with lower priority is used.")),
          1
          /* TEXT */
        ),
        createVNode($setup["AppOrderSelector"], {
          modelValue: $setup.selectedApps,
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $setup.selectedApps = $event)
        }, null, 8, ["modelValue"])
      ], 8, _hoisted_2$1)) : createCommentVNode("v-if", true)
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name"]);
}
const AdminSectionAppMenu = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__scopeId", "data-v-030d9b5a"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/AdminSectionAppMenu.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
function useAdminThemingValue(name, modelValue, defaultValue) {
  let resetted = false;
  const isSaving = ref(false);
  const isSaved = ref(false);
  watchDebounced(modelValue, async () => {
    if (isSaving.value) {
      return;
    }
    if (resetted) {
      resetted = false;
      return;
    }
    isSaving.value = true;
    isSaved.value = false;
    try {
      await setValue(toValue(name), toValue(modelValue));
      isSaved.value = true;
      window.setTimeout(() => {
        isSaved.value = false;
      }, 2e3);
    } finally {
      isSaving.value = false;
    }
  }, { debounce: 800, flush: "sync" });
  async function reset() {
    isSaving.value = true;
    isSaved.value = false;
    try {
      const result = await resetValue(toValue(name));
      if (result && isRef(defaultValue) && !isReadonly(defaultValue)) {
        defaultValue.value = result;
      }
      resetted = true;
      modelValue.value = toValue(defaultValue);
    } finally {
      isSaving.value = false;
    }
  }
  return {
    isSaving: readonly(isSaving),
    isSaved: readonly(isSaved),
    reset
  };
}
async function setValue(setting, value) {
  const url = generateUrl("/apps/theming/ajax/updateStylesheet");
  try {
    await cancelableClient.post(url, {
      setting,
      value: String(value)
    });
  } catch (error) {
    logger.error("Failed to save changes", { error, setting, value });
    if (isAxiosError(error) && error.response?.data?.data?.message) {
      showError(error.response.data.data.message);
    }
    throw error;
  }
}
async function resetValue(setting) {
  const url = generateUrl("/apps/theming/ajax/undoChanges");
  try {
    const { data } = await cancelableClient.post(url, { setting });
    return data.data.value;
  } catch (error) {
    logger.error("Failed to reset theming value", { error, setting });
    if (isAxiosError(error) && error.response?.data?.data?.message) {
      showError(error.response.data.data.message);
      return false;
    }
    throw error;
  }
}
const _sfc_main$5 = /* @__PURE__ */ defineComponent({
  __name: "TextField",
  props: {
    name: { type: String, required: true },
    label: { type: String, required: true },
    defaultValue: { type: String, required: true },
    type: { type: String, required: false, default: "text" }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const modelValue = ref(loadState("theming", "adminThemingParameters")[props.name].toString());
    const {
      isSaving,
      isSaved,
      reset
    } = useAdminThemingValue(toRef(() => props.name), modelValue, toRef(() => props.defaultValue));
    watchDebounced(modelValue, (value) => {
      if (props.type === "url" && value.includes('"')) {
        try {
          const url = new URL(value);
          url.pathname = url.pathname.replaceAll(/"/g, "%22");
          modelValue.value = url.href;
        } catch {
          return;
        }
      }
    }, { debounce: 600 });
    const __returned__ = { props, modelValue, isSaving, isSaved, reset, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, get NcTextField() {
      return _sfc_main$7;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcTextField"], {
    modelValue: $setup.modelValue,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.modelValue = $event),
    label: $props.label,
    readonly: $setup.isSaving,
    success: $setup.isSaved,
    type: $props.type,
    showTrailingButton: $setup.modelValue !== $props.defaultValue,
    trailingButtonIcon: $props.defaultValue ? "undo" : "close",
    onTrailingButtonClick: $setup.reset
  }, createSlots({
    _: 2
    /* DYNAMIC */
  }, [
    $setup.isSaving ? {
      name: "icon",
      fn: withCtx(() => [
        createVNode($setup["NcLoadingIcon"])
      ]),
      key: "0"
    } : void 0
  ]), 1032, ["modelValue", "label", "readonly", "success", "type", "showTrailingButton", "trailingButtonIcon", "onTrailingButtonClick"]);
}
const TextField = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/admin/TextField.vue"]]);
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "AdminSectionTheming",
  setup(__props, { expose: __expose }) {
    __expose();
    const ADMIN_INFO = loadState("theming", "adminThemingInfo");
    const __returned__ = { ADMIN_INFO, get t() {
      return translate;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, TextField };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const adminSectionTheming = "_adminSectionTheming_1p8pd_2";
const style0$3 = {
  adminSectionTheming
};
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("theming", "Theming"),
    description: $setup.t("theming", "Theming makes it possible to easily customize the look and feel of your instance and supported clients. This will be visible for all users."),
    docUrl: $setup.ADMIN_INFO.docUrl,
    "data-admin-theming-settings": ""
  }, {
    default: withCtx(() => [
      createBaseVNode(
        "div",
        {
          class: normalizeClass(_ctx.$style.adminSectionTheming)
        },
        [
          createCommentVNode(" Name, web link, slogan... fields "),
          createVNode($setup["TextField"], {
            name: "name",
            label: $setup.t("theming", "Name"),
            defaultValue: "Nextcloud",
            maxlength: "250"
          }, null, 8, ["label"]),
          createVNode($setup["TextField"], {
            name: "url",
            label: $setup.t("theming", "Web link"),
            defaultValue: "https://nextcloud.com",
            placeholder: "https://…",
            type: "url",
            maxlength: "500"
          }, null, 8, ["label"]),
          createVNode($setup["TextField"], {
            name: "slogan",
            label: $setup.t("theming", "Slogan"),
            defaultValue: $setup.t("settings", "a safe home for all your data"),
            maxlength: "500"
          }, null, 8, ["label", "defaultValue"]),
          _cache[0] || (_cache[0] = createBaseVNode(
            "hr",
            null,
            null,
            -1
            /* CACHED */
          )),
          createVNode($setup["TextField"], {
            name: "legalNoticeUrl",
            label: $setup.t("theming", "Legal notice link"),
            defaultValue: "",
            type: "url",
            placeholder: "https://…",
            maxlength: "500"
          }, null, 8, ["label"]),
          createVNode($setup["TextField"], {
            name: "privacyPolicyUrl",
            label: $setup.t("theming", "Privacy policy link"),
            defaultValue: "",
            type: "url",
            placeholder: "https://…",
            maxlength: "500"
          }, null, 8, ["label"])
        ],
        2
        /* CLASS */
      )
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description", "docUrl"]);
}
const cssModules$3 = {
  "$style": style0$3
};
const AdminSectionTheming = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__cssModules", cssModules$3], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/AdminSectionTheming.vue"]]);
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "ColorPickerField",
  props: {
    name: { type: String, required: true },
    label: { type: String, required: true },
    defaultValue: { type: String, required: true }
  },
  emits: ["updated"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const props = __props;
    const emit = __emit;
    const id = useId();
    const modelValue = ref(loadState("theming", "adminThemingParameters")[props.name]);
    const previewColor = ref(modelValue.value);
    watch(modelValue, (v) => {
      previewColor.value = v;
    });
    const {
      isSaving,
      reset
    } = useAdminThemingValue(() => props.name, modelValue, toRef(props, "defaultValue"));
    watch(isSaving, (v) => !v && emit("updated"));
    const textColor = computed(() => getTextColor(previewColor.value));
    const __returned__ = { props, emit, id, modelValue, previewColor, isSaving, reset, textColor, get mdiPaletteOutline() {
      return mdiPaletteOutline;
    }, get mdiUndo() {
      return mdiUndo;
    }, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcColorPicker() {
      return NcColorPicker;
    }, get NcIconSvgWrapper() {
      return NcIconSvgWrapper;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const colorPickerField = "_colorPickerField_o0yey_2";
const colorPickerField__row = "_colorPickerField__row_o0yey_7";
const colorPickerField__button = "_colorPickerField__button_o0yey_14";
const colorPickerField__description = "_colorPickerField__description_o0yey_18";
const style0$2 = {
  colorPickerField,
  colorPickerField__row,
  colorPickerField__button,
  colorPickerField__description
};
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass(_ctx.$style.colorPickerField)
    },
    [
      createBaseVNode(
        "div",
        {
          class: normalizeClass(_ctx.$style.colorPickerField__row)
        },
        [
          createVNode($setup["NcColorPicker"], {
            id: $setup.id,
            modelValue: $setup.previewColor,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.previewColor = $event),
            advancedFields: "",
            onSubmit: _cache[1] || (_cache[1] = ($event) => $setup.modelValue = $event)
          }, {
            default: withCtx(() => [
              createVNode($setup["NcButton"], {
                class: normalizeClass(_ctx.$style.colorPickerField__button),
                size: "large",
                variant: "primary",
                style: normalizeStyle({
                  "--color-primary-element": $setup.previewColor,
                  "--color-primary-element-text": $setup.textColor,
                  "--color-primary-element-hover": "color-mix(in srgb, var(--color-primary-element) 70%, var(--color-primary-element-text))"
                })
              }, {
                icon: withCtx(() => [
                  $setup.isSaving ? (openBlock(), createBlock($setup["NcLoadingIcon"], {
                    key: 0,
                    appearance: $setup.textColor === "#ffffff" ? "light" : "dark"
                  }, null, 8, ["appearance"])) : (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
                    key: 1,
                    path: $setup.mdiPaletteOutline
                  }, null, 8, ["path"]))
                ]),
                default: withCtx(() => [
                  createTextVNode(
                    " " + toDisplayString($props.label),
                    1
                    /* TEXT */
                  )
                ]),
                _: 1
                /* STABLE */
              }, 8, ["class", "style"])
            ]),
            _: 1
            /* STABLE */
          }, 8, ["id", "modelValue"]),
          $setup.modelValue !== $props.defaultValue ? (openBlock(), createBlock($setup["NcButton"], {
            key: 0,
            variant: "tertiary",
            "aria-label": $setup.t("theming", "Reset to default"),
            title: $setup.t("theming", "Reset to default"),
            onClick: $setup.reset
          }, {
            icon: withCtx(() => [
              createVNode($setup["NcIconSvgWrapper"], { path: $setup.mdiUndo }, null, 8, ["path"])
            ]),
            _: 1
            /* STABLE */
          }, 8, ["aria-label", "title", "onClick"])) : createCommentVNode("v-if", true)
        ],
        2
        /* CLASS */
      ),
      createBaseVNode(
        "p",
        {
          class: normalizeClass(_ctx.$style.colorPickerField__description)
        },
        [
          renderSlot(_ctx.$slots, "description")
        ],
        2
        /* CLASS */
      )
    ],
    2
    /* CLASS */
  );
}
const cssModules$2 = {
  "$style": style0$2
};
const ColorPickerField = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__cssModules", cssModules$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/admin/ColorPickerField.vue"]]);
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "FileInputField",
  props: {
    name: { type: String, required: true },
    label: { type: String, required: true },
    disabled: { type: Boolean, required: false }
  },
  emits: ["updated"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    useCssVars((_ctx) => ({
      "bd0e75b8-background": background.value
    }));
    const props = __props;
    const emit = __emit;
    const isSaving = ref(false);
    const mime = ref(loadState("theming", "adminThemingParameters")[props.name + "Mime"]);
    const inputElement = useTemplateRef("input");
    const background = computed(() => {
      const baseUrl = generateUrl("/apps/theming/image/{key}", { key: props.name });
      return `url(${baseUrl}?v=${Date.now()}&m=${encodeURIComponent(mime.value)})`;
    });
    function pickFile() {
      if (isSaving.value) {
        return;
      }
      inputElement.value.files = null;
      inputElement.value.click();
    }
    async function onChange() {
      if (!inputElement.value.files?.[0]) {
        return;
      }
      const file = inputElement.value.files[0];
      if (file.type && !file.type.startsWith("image/")) {
        showError(translate("theming", "Non image file selected"));
        return;
      }
      isSaving.value = true;
      const formData = new FormData();
      formData.append("image", file);
      formData.append("key", props.name);
      try {
        await cancelableClient.post(generateUrl("/apps/theming/ajax/uploadImage"), formData, {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        });
        mime.value = file.type;
        emit("updated");
      } catch (error) {
        if (isAxiosError(error) && error.response?.status === 422) {
          const serverMessage = error.response.data?.data?.message;
          showError(serverMessage || translate("theming", "Failed to upload image"));
        } else {
          showError(translate("theming", "Failed to upload image"));
        }
      } finally {
        isSaving.value = false;
        inputElement.value.value = "";
      }
    }
    async function resetToDefault() {
      if (isSaving.value) {
        return;
      }
      isSaving.value = true;
      try {
        await cancelableClient.post(generateUrl("/apps/theming/ajax/undoChanges"), {
          setting: props.name
        });
        mime.value = "";
        emit("updated");
      } finally {
        isSaving.value = false;
      }
    }
    const __returned__ = { props, emit, isSaving, mime, inputElement, background, pickFile, onChange, resetToDefault, get mdiImageOutline() {
      return mdiImageOutline;
    }, get mdiUndo() {
      return mdiUndo;
    }, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcIconSvgWrapper() {
      return NcIconSvgWrapper;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const fileInputField = "_fileInputField_1u7zs_2";
const fileInputField__button = "_fileInputField__button_1u7zs_10";
const fileInputField__preview = "_fileInputField__preview_1u7zs_14";
const style0$1 = {
  fileInputField,
  fileInputField__button,
  fileInputField__preview
};
const _hoisted_1 = ["aria-label"];
const _hoisted_2 = ["disabled", "name"];
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass(_ctx.$style.fileInputField)
    },
    [
      createVNode($setup["NcButton"], {
        class: normalizeClass(_ctx.$style.fileInputField__button),
        alignment: "start",
        disabled: $props.disabled,
        size: "large",
        onClick: $setup.pickFile
      }, {
        icon: withCtx(() => [
          $setup.isSaving ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
            key: 1,
            path: $setup.mdiImageOutline
          }, null, 8, ["path"]))
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($props.label),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["class", "disabled"]),
      $setup.mime.startsWith("image/") ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: normalizeClass(_ctx.$style.fileInputField__preview),
        role: "img",
        "aria-label": $setup.t("theming", "Preview of the selected image")
      }, null, 10, _hoisted_1)) : createCommentVNode("v-if", true),
      $setup.mime && !$props.disabled ? (openBlock(), createBlock($setup["NcButton"], {
        key: 1,
        "aria-label": $setup.t("theming", "Reset to default"),
        title: $setup.t("theming", "Reset to default"),
        size: "large",
        variant: "tertiary",
        onClick: $setup.resetToDefault
      }, {
        icon: withCtx(() => [
          createVNode($setup["NcIconSvgWrapper"], { path: $setup.mdiUndo }, null, 8, ["path"])
        ]),
        _: 1
        /* STABLE */
      }, 8, ["aria-label", "title"])) : createCommentVNode("v-if", true),
      createBaseVNode("input", {
        ref: "input",
        class: "hidden-visually",
        "aria-hidden": "true",
        disabled: $props.disabled,
        type: "file",
        accept: "image/*",
        name: $props.name,
        onChange: $setup.onChange
      }, null, 40, _hoisted_2)
    ],
    2
    /* CLASS */
  );
}
const cssModules$1 = {
  "$style": style0$1
};
const FileInputField = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__cssModules", cssModules$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/admin/FileInputField.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "AdminSectionThemingAdvanced",
  setup(__props, { expose: __expose }) {
    __expose();
    const { defaultBackgroundColor } = loadState("theming", "adminThemingInfo");
    const adminThemingParameters = loadState("theming", "adminThemingParameters");
    const userThemingDisabled = ref(adminThemingParameters.disableUserTheming);
    const { isSaving } = useAdminThemingValue("disableUserTheming", userThemingDisabled, false);
    const isRemovingBackgroundImage = ref(false);
    const removeBackgroundImage = ref(adminThemingParameters.backgroundMime === "backgroundColor");
    watch(removeBackgroundImage, toggleBackground);
    async function toggleBackground(value) {
      isRemovingBackgroundImage.value = true;
      try {
        if (value) {
          await cancelableClient.post(generateUrl("/apps/theming/ajax/undoChanges"), {
            setting: "background"
          });
          await cancelableClient.post(generateUrl("/apps/theming/ajax/updateStylesheet"), {
            setting: "backgroundMime",
            value: "backgroundColor"
          });
        } else {
          await cancelableClient.post(generateUrl("/apps/theming/ajax/undoChanges"), {
            setting: "backgroundMime"
          });
        }
        await refreshStyles();
      } catch (error) {
        logger.error("Failed to remove background image", { error });
        if (isAxiosError(error) && error.response?.data?.data?.message) {
          showError(error.response.data.data.message);
          return;
        }
        throw error;
      } finally {
        isRemovingBackgroundImage.value = false;
      }
    }
    const __returned__ = { defaultBackgroundColor, adminThemingParameters, userThemingDisabled, isSaving, isRemovingBackgroundImage, removeBackgroundImage, toggleBackground, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, ColorPickerField, FileInputField, get refreshStyles() {
      return refreshStyles;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const adminSectionThemingAdvanced = "_adminSectionThemingAdvanced_dyaj4_2";
const style0 = {
  adminSectionThemingAdvanced
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("theming", "Background and color")
  }, {
    default: withCtx(() => [
      createBaseVNode(
        "div",
        {
          class: normalizeClass(_ctx.$style.adminSectionThemingAdvanced)
        },
        [
          createCommentVNode(" primary color "),
          createVNode($setup["ColorPickerField"], {
            name: "primaryColor",
            label: $setup.t("theming", "Primary color"),
            defaultValue: "#00679e",
            onUpdated: $setup.refreshStyles
          }, {
            description: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("theming", "Set the default primary color, used to highlight important elements.")) + " " + toDisplayString($setup.t("theming", "The color used for elements such as primary buttons might differ a bit as it gets adjusted to fulfill accessibility requirements.")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["label", "onUpdated"]),
          createCommentVNode(" background color "),
          createVNode($setup["ColorPickerField"], {
            name: "backgroundColor",
            label: $setup.t("theming", "Background color"),
            defaultValue: $setup.defaultBackgroundColor,
            onUpdated: $setup.refreshStyles
          }, {
            description: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("theming", "When no background image is set the background color will be used.")) + " " + toDisplayString($setup.t("theming", "Otherwise the background color is by default generated from the background image, but can be adjusted to fine tune the color of the navigation icons.")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["label", "defaultValue", "onUpdated"]),
          createCommentVNode(" background and logo "),
          createVNode($setup["NcCheckboxRadioSwitch"], {
            modelValue: $setup.removeBackgroundImage,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.removeBackgroundImage = $event),
            type: "switch",
            loading: $setup.isRemovingBackgroundImage,
            description: $setup.t("theming", "Use a plain background color instead of a background image.")
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("theming", "Remove background image")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["modelValue", "loading", "description"]),
          createVNode($setup["FileInputField"], {
            name: "background",
            disabled: $setup.removeBackgroundImage,
            label: $setup.t("theming", "Background image"),
            onUpdated: $setup.refreshStyles
          }, null, 8, ["disabled", "label", "onUpdated"]),
          createVNode($setup["FileInputField"], {
            name: "favicon",
            label: $setup.t("theming", "Favicon")
          }, null, 8, ["label"]),
          createVNode($setup["FileInputField"], {
            name: "logo",
            label: $setup.t("theming", "Logo"),
            onUpdated: $setup.refreshStyles
          }, null, 8, ["label", "onUpdated"]),
          createVNode($setup["FileInputField"], {
            name: "logoheader",
            label: $setup.t("theming", "Navigation bar logo"),
            onUpdated: $setup.refreshStyles
          }, null, 8, ["label", "onUpdated"]),
          _cache[2] || (_cache[2] = createBaseVNode(
            "hr",
            null,
            null,
            -1
            /* CACHED */
          )),
          createVNode($setup["NcCheckboxRadioSwitch"], {
            modelValue: $setup.userThemingDisabled,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.userThemingDisabled = $event),
            type: "switch",
            loading: $setup.isSaving,
            description: $setup.t("theming", "Although you can select and customize your instance, users can change their background and colors. If you want to enforce your customization, you can toggle this on.")
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("theming", "Disable user theming")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["modelValue", "loading", "description"])
        ],
        2
        /* CLASS */
      )
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name"]);
}
const cssModules = {
  "$style": style0
};
const AdminSectionThemingAdvanced = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/components/AdminSectionThemingAdvanced.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "AdminTheming",
  setup(__props, { expose: __expose }) {
    __expose();
    const { isThemeable, notThemeableErrorMessage } = loadState("theming", "adminThemingInfo");
    const __returned__ = { isThemeable, notThemeableErrorMessage, get NcNoteCard() {
      return NcNoteCard;
    }, AdminSectionAppMenu, AdminSectionTheming, AdminSectionThemingAdvanced };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    Fragment,
    null,
    [
      !$setup.isThemeable ? (openBlock(), createBlock($setup["NcNoteCard"], {
        key: 0,
        text: $setup.notThemeableErrorMessage,
        showAlert: "",
        type: "error"
      }, null, 8, ["text"])) : (openBlock(), createElementBlock(
        Fragment,
        { key: 1 },
        [
          createVNode($setup["AdminSectionTheming"]),
          createVNode($setup["AdminSectionThemingAdvanced"])
        ],
        64
        /* STABLE_FRAGMENT */
      )),
      createVNode($setup["AdminSectionAppMenu"])
    ],
    64
    /* STABLE_FRAGMENT */
  );
}
const AdminTheming = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/theming/src/views/AdminTheming.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const app = createApp(AdminTheming);
app.config.idPrefix = "settings";
app.mount("#settings-admin-theming");
//# sourceMappingURL=theming-settings-admin.mjs.map
