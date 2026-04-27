const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, l as useTemplateRef, y as ref, o as openBlock, f as createElementBlock, x as createVNode, w as withCtx, c as createBlock, j as createTextVNode, t as toDisplayString, h as createCommentVNode, M as withModifiers, k as useModel, z as watch, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { c as cancelableClient, i as isAxiosError } from "./index-D5H5XMHa.chunk.mjs";
import { a as showError, e as showLoading, b as showInfo } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { b as generateUrl } from "./index-rAufP352.chunk.mjs";
import { N as NcNoteCard } from "./mdi-BGU2G5q5.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./NcTextArea-CWA3KOiC-Cpgesyiv.chunk.mjs";
import "./index-CZV8rpGu.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import { w as watchDebounced } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
/* empty css                                           */
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import "./Plus-DuSPdibD.chunk.mjs";
import "./index-DD39fp6M.chunk.mjs";
import "./TrayArrowDown-DVjUGg6-.chunk.mjs";
import "./index-BcMnKoRR.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import "./NcEmojiPicker-Djc9a0gw-F1kmncT2.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import "./index-D5BR15En.chunk.mjs";
import { N as NcFormGroup, l as logger, I as InitStatus } from "./types-D9UTgwfU.chunk.mjs";
/* empty css                                        */
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import { N as NcPasswordField } from "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./index-gwTr8m4i.chunk.mjs";
import "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import "./NcSelectTags-CTHyuMcq-2HejGZhj.chunk.mjs";
import "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./emoji-BY_D0V5K-BlCul1cD.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "SettingsPersonalChangePrivateKey",
  props: {
    recoveryEnabledForUser: { type: Boolean, required: true }
  },
  emits: ["updated"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const emit = __emit;
    const formElement = useTemplateRef("form");
    const isLoading = ref(false);
    const hasError = ref(false);
    const oldPrivateKeyPassword = ref("");
    const newPrivateKeyPassword = ref("");
    async function onSubmit() {
      if (isLoading.value) {
        return;
      }
      isLoading.value = true;
      hasError.value = false;
      try {
        await cancelableClient.post(
          generateUrl("/apps/encryption/ajax/updatePrivateKeyPassword"),
          {
            oldPassword: oldPrivateKeyPassword.value,
            newPassword: newPrivateKeyPassword.value
          }
        );
        oldPrivateKeyPassword.value = newPrivateKeyPassword.value = "";
        formElement.value?.reset();
        emit("updated");
      } catch (error) {
        if (isAxiosError(error) && error.response && error.response.data?.data?.message) {
          showError(error.response.data.data.message);
        }
        hasError.value = true;
      } finally {
        isLoading.value = false;
      }
    }
    const __returned__ = { emit, formElement, isLoading, hasError, oldPrivateKeyPassword, newPrivateKeyPassword, onSubmit, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcFormGroup() {
      return NcFormGroup;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcPasswordField() {
      return NcPasswordField;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "form",
    {
      ref: "form",
      onSubmit: withModifiers($setup.onSubmit, ["prevent"])
    },
    [
      createVNode($setup["NcFormGroup"], {
        label: $setup.t("encryption", "Update private key password"),
        description: $setup.t("encryption", "Your private key password no longer matches your log-in password. Set your old private key password to your current log-in password.")
      }, {
        default: withCtx(() => [
          $props.recoveryEnabledForUser ? (openBlock(), createBlock($setup["NcNoteCard"], { key: 0 }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("encryption", "If you do not remember your old password you can ask your administrator to recover your files.")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          })) : createCommentVNode("v-if", true),
          createVNode($setup["NcPasswordField"], {
            label: $setup.t("encryption", "Old log-in password")
          }, null, 8, ["label"]),
          createVNode($setup["NcPasswordField"], {
            label: $setup.t("encryption", "Current log-in password")
          }, null, 8, ["label"]),
          createVNode($setup["NcButton"], {
            type: "submit",
            variant: "primary"
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("encryption", "Update")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          })
        ]),
        _: 1
        /* STABLE */
      }, 8, ["label", "description"])
    ],
    544
    /* NEED_HYDRATION, NEED_PATCH */
  );
}
const SettingsPersonalChangePrivateKey = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/encryption/src/components/SettingsPersonalChangePrivateKey.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "SettingsPersonalEnableRecovery",
  props: {
    "modelValue": { type: Boolean, ...{ required: true } },
    "modelModifiers": {}
  },
  emits: ["update:modelValue"],
  setup(__props, { expose: __expose }) {
    __expose();
    const userEnableRecovery = useModel(__props, "modelValue");
    const isLoading = ref(false);
    watch(userEnableRecovery, () => {
      isLoading.value = true;
    });
    watchDebounced([userEnableRecovery], async ([newValue], [oldValue]) => {
      if (newValue === oldValue) {
        isLoading.value = false;
        return;
      }
      const toast = showLoading(translate("encryption", "Updating recovery keys. This can take some time…"));
      try {
        await cancelableClient.post(
          generateUrl("/apps/encryption/ajax/userSetRecovery"),
          { userEnableRecovery: userEnableRecovery.value }
        );
      } catch (error) {
        userEnableRecovery.value = oldValue;
        if (isAxiosError(error) && error.response && error.response.data?.data?.message) {
          showError(error.response.data.data.message);
        }
      } finally {
        toast.hideToast();
        isLoading.value = false;
      }
    }, { debounce: 800 });
    const __returned__ = { userEnableRecovery, isLoading, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcCheckboxRadioSwitch"], {
    modelValue: $setup.userEnableRecovery,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.userEnableRecovery = $event),
    type: "switch",
    loading: $setup.isLoading,
    description: $setup.t("encryption", "Enabling this option will allow you to reobtain access to your encrypted files in case of password loss")
  }, {
    default: withCtx(() => [
      createTextVNode(
        toDisplayString($setup.t("encryption", "Enable password recovery")),
        1
        /* TEXT */
      )
    ]),
    _: 1
    /* STABLE */
  }, 8, ["modelValue", "loading", "description"]);
}
const SettingsPersonalEnableRecovery = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/encryption/src/components/SettingsPersonalEnableRecovery.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "SettingsPersonal",
  setup(__props, { expose: __expose }) {
    __expose();
    const personalSettings = loadState("encryption", "personalSettings");
    const initialized = ref(personalSettings.initialized);
    const recoveryEnabledForUser = ref(personalSettings.recoveryEnabledForUser);
    async function reloadStatus() {
      try {
        const { data } = await cancelableClient.get(generateUrl("/apps/encryption/ajax/getStatus"));
        initialized.value = data.initStatus;
        if (data.data.message) {
          showInfo(data.data.message);
        }
      } catch (error) {
        logger.error("Failed to fetch current encryption status", { error });
      }
    }
    const __returned__ = { personalSettings, initialized, recoveryEnabledForUser, reloadStatus, get t() {
      return translate;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, SettingsPersonalChangePrivateKey, SettingsPersonalEnableRecovery, get InitStatus() {
      return InitStatus;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("encryption", "Basic encryption module")
  }, {
    default: withCtx(() => [
      $setup.initialized === $setup.InitStatus.NotInitialized ? (openBlock(), createBlock($setup["NcNoteCard"], {
        key: 0,
        type: "warning"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("encryption", "Encryption app is enabled but your keys are not initialized, please log-out and log-in again")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : $setup.initialized === $setup.InitStatus.InitExecuted ? (openBlock(), createBlock($setup["SettingsPersonalChangePrivateKey"], {
        key: 1,
        recoveryEnabledForUser: $setup.recoveryEnabledForUser,
        onUpdated: $setup.reloadStatus
      }, null, 8, ["recoveryEnabledForUser"])) : $setup.personalSettings.recoveryEnabled && $setup.personalSettings.privateKeySet ? (openBlock(), createBlock($setup["SettingsPersonalEnableRecovery"], {
        key: 2,
        modelValue: $setup.recoveryEnabledForUser,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.recoveryEnabledForUser = $event)
      }, null, 8, ["modelValue"])) : createCommentVNode("v-if", true)
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name"]);
}
const SettingsPersonal = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/encryption/src/views/SettingsPersonal.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const app = createApp(SettingsPersonal);
app.mount("#encryption-settings-section");
//# sourceMappingURL=encryption-settings_personal.mjs.map
