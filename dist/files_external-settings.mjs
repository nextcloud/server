const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { d as defineStore, c as createPinia } from "./pinia-0yhe0wHh.chunk.mjs";
import { n as computed, a5 as watchEffect, X as toValue, O as reactive, b as defineComponent, k as useModel, y as ref, o as openBlock, c as createBlock, z as watch, q as mergeModels, f as createElementBlock, g as createBaseVNode, t as toDisplayString, F as Fragment, h as createCommentVNode, K as resolveDynamicComponent, C as renderList, E as withDirectives, G as vShow, v as normalizeClass, ab as toRaw, W as useId, x as createVNode, w as withCtx, j as createTextVNode, M as withModifiers, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { l as mdiChevronRight, n as mdiChevronDown, f as mdiTrashCanOutline, o as mdiPencilOutline, p as mdiInformationOutline, q as mdiAccountGroupOutline, N as NcNoteCard, e as mdiPlus } from "./mdi-BGU2G5q5.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate, a as translatePlural } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcEmptyContent } from "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import { N as NcIconSvgWrapper } from "./Web-BOM4en5n.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { N as NcDialog, s as spawnDialog } from "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import { N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import { _ as _sfc_main$b } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { b as generateUrl } from "./index-rAufP352.chunk.mjs";
import { N as NcSelectUsers } from "./NcTextArea-CWA3KOiC-Cpgesyiv.chunk.mjs";
import { l as useDebounceFn } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./index-CZV8rpGu.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
/* empty css                                           */
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { N as NcChip } from "./Plus-DuSPdibD.chunk.mjs";
import "./index-DD39fp6M.chunk.mjs";
import "./TrayArrowDown-DVjUGg6-.chunk.mjs";
import "./index-BcMnKoRR.chunk.mjs";
import "./NcEmojiPicker-Djc9a0gw-F1kmncT2.chunk.mjs";
import "./index-D5BR15En.chunk.mjs";
/* empty css                                        */
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import { N as NcPasswordField } from "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./index-gwTr8m4i.chunk.mjs";
import "./NcSelectTags-CTHyuMcq-2HejGZhj.chunk.mjs";
import { N as NcUserBubble } from "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./emoji-BY_D0V5K-BlCul1cD.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import { C as ConfigurationEntry } from "./AuthMechanismRsa-DKz8YPt-.chunk.mjs";
import { C as ConfigurationType, a as ConfigurationFlag, M as MountOptionsCheckFilesystem, S as StorageStatus, b as StorageStatusMessage, c as StorageStatusIcons } from "./types-B1VCwyqH.chunk.mjs";
import { a as addPasswordConfirmationInterceptors, P as PwdConfirmationMode } from "./index-Dl6U1WCt.chunk.mjs";
import { a as showError, c as showSuccess } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { l as logger } from "./logger-CE4VDfGL.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
const svgAccountGroupOutline = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-account-group-outline" viewBox="0 0 24 24"><path d="M12,5A3.5,3.5 0 0,0 8.5,8.5A3.5,3.5 0 0,0 12,12A3.5,3.5 0 0,0 15.5,8.5A3.5,3.5 0 0,0 12,5M12,7A1.5,1.5 0 0,1 13.5,8.5A1.5,1.5 0 0,1 12,10A1.5,1.5 0 0,1 10.5,8.5A1.5,1.5 0 0,1 12,7M5.5,8A2.5,2.5 0 0,0 3,10.5C3,11.44 3.53,12.25 4.29,12.68C4.65,12.88 5.06,13 5.5,13C5.94,13 6.35,12.88 6.71,12.68C7.08,12.47 7.39,12.17 7.62,11.81C6.89,10.86 6.5,9.7 6.5,8.5C6.5,8.41 6.5,8.31 6.5,8.22C6.2,8.08 5.86,8 5.5,8M18.5,8C18.14,8 17.8,8.08 17.5,8.22C17.5,8.31 17.5,8.41 17.5,8.5C17.5,9.7 17.11,10.86 16.38,11.81C16.5,12 16.63,12.15 16.78,12.3C16.94,12.45 17.1,12.58 17.29,12.68C17.65,12.88 18.06,13 18.5,13C18.94,13 19.35,12.88 19.71,12.68C20.47,12.25 21,11.44 21,10.5A2.5,2.5 0 0,0 18.5,8M12,14C9.66,14 5,15.17 5,17.5V19H19V17.5C19,15.17 14.34,14 12,14M4.71,14.55C2.78,14.78 0,15.76 0,17.5V19H3V17.07C3,16.06 3.69,15.22 4.71,14.55M19.29,14.55C20.31,15.22 21,16.06 21,17.07V19H24V17.5C24,15.76 21.22,14.78 19.29,14.55M12,16C13.53,16 15.24,16.5 16.23,17H7.77C8.76,16.5 10.47,16 12,16Z" /></svg>';
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const displayNames = reactive(/* @__PURE__ */ new Map());
function useUsers(uids) {
  const users = computed(() => toValue(uids).map((uid) => ({
    id: `user:${uid}`,
    user: uid,
    displayName: displayNames.get(uid) || uid
  })));
  watchEffect(async () => {
    const missingUsers = toValue(uids).filter((uid) => !displayNames.has(uid));
    if (missingUsers.length > 0) {
      const { data } = await cancelableClient.post(generateUrl("/displaynames"), {
        users: missingUsers
      });
      for (const [uid, displayName] of Object.entries(data.users)) {
        displayNames.set(uid, displayName);
      }
    }
  });
  return users;
}
function useGroups(gids) {
  return computed(() => toValue(gids).map(mapGroupToUserData));
}
function mapGroupToUserData(gid) {
  return {
    id: gid,
    isNoUser: true,
    displayName: gid,
    iconSvg: svgAccountGroupOutline
  };
}
const _sfc_main$a = /* @__PURE__ */ defineComponent({
  __name: "ApplicableEntities",
  props: {
    "groups": { type: Array, ...{ default: () => [] } },
    "groupsModifiers": {},
    "users": { type: Array, ...{ default: () => [] } },
    "usersModifiers": {}
  },
  emits: ["update:groups", "update:users"],
  setup(__props, { expose: __expose }) {
    __expose();
    const groups = useModel(__props, "groups");
    const users = useModel(__props, "users");
    const entities = ref([]);
    const selectedUsers = useUsers(users);
    const selectedGroups = useGroups(groups);
    const model = computed({
      get() {
        return [...selectedGroups.value, ...selectedUsers.value];
      },
      set(value) {
        users.value = value.filter((u) => u.user).map((u) => u.user);
        groups.value = value.filter((g) => g.isNoUser).map((g) => g.id);
      }
    });
    const debouncedSearch = useDebounceFn(onSearch, 500);
    async function onSearch(pattern) {
      const { data } = await cancelableClient.get(
        generateUrl("apps/files_external/ajax/applicable"),
        { params: { pattern, limit: 20 } }
      );
      const newEntries = [
        ...entities.value.map((e) => [e.id, e]),
        ...Object.entries(data.groups).map(([id, displayName]) => [id, { ...mapGroupToUserData(id), displayName }]),
        ...Object.entries(data.users).map(([id, displayName]) => [`user:${id}`, { id: `user:${id}`, user: id, displayName }])
      ];
      entities.value = [...new Map(newEntries).values()];
    }
    const __returned__ = { groups, users, entities, selectedUsers, selectedGroups, model, debouncedSearch, onSearch, get t() {
      return translate;
    }, get NcSelectUsers() {
      return NcSelectUsers;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render$a(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSelectUsers"], {
    modelValue: $setup.model,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.model = $event),
    keepOpen: "",
    multiple: "",
    options: $setup.entities,
    inputLabel: $setup.t("files_external", "Restrict to"),
    onSearch: $setup.debouncedSearch
  }, null, 8, ["modelValue", "options", "inputLabel", "onSearch"]);
}
const ApplicableEntities = /* @__PURE__ */ _export_sfc(_sfc_main$a, [["render", _sfc_render$a], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/components/AddExternalStorageDialog/ApplicableEntities.vue"]]);
const _sfc_main$9 = /* @__PURE__ */ defineComponent({
  __name: "AuthMechanismConfiguration",
  props: /* @__PURE__ */ mergeModels({
    authMechanism: { type: Object, required: true }
  }, {
    "modelValue": { type: Object, ...{ required: true } },
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props, { expose: __expose }) {
    __expose();
    const modelValue = useModel(__props, "modelValue");
    const props = __props;
    const configuration = computed(() => {
      if (!props.authMechanism.configuration) {
        return void 0;
      }
      const entries = Object.entries(props.authMechanism.configuration).filter(([, option]) => !(option.flags & ConfigurationFlag.UserProvided));
      return Object.fromEntries(entries);
    });
    const customComponent = computed(() => window.OCA.FilesExternal.AuthMechanism.getHandler(props.authMechanism));
    const hasConfiguration = computed(() => {
      if (!configuration.value) {
        return false;
      }
      for (const option of Object.values(configuration.value)) {
        if (option.flags & ConfigurationFlag.Hidden || option.flags & ConfigurationFlag.UserProvided) {
          continue;
        }
        return true;
      }
      return false;
    });
    const isLoadingCustomComponent = ref(false);
    watchEffect(async () => {
      if (customComponent.value) {
        isLoadingCustomComponent.value = true;
        await window.customElements.whenDefined(customComponent.value.tagName);
        isLoadingCustomComponent.value = false;
      }
    });
    watch(configuration, () => {
      for (const key in configuration.value) {
        if (!(key in modelValue.value)) {
          modelValue.value[key] = configuration.value[key]?.type === ConfigurationType.Boolean ? false : "";
        }
      }
    });
    function onUpdateModelValue(event) {
      const config = [event.detail].flat()[0];
      modelValue.value = { ...modelValue.value, ...config };
    }
    const __returned__ = { modelValue, props, configuration, customComponent, hasConfiguration, isLoadingCustomComponent, onUpdateModelValue, get t() {
      return translate;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, ConfigurationEntry, get ConfigurationFlag() {
      return ConfigurationFlag;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const authMechanismConfiguration = "_authMechanismConfiguration_kpcpy_2";
const style0$8 = {
  authMechanismConfiguration
};
function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
  return $setup.hasConfiguration ? (openBlock(), createElementBlock(
    "fieldset",
    {
      key: 0,
      class: normalizeClass(_ctx.$style.authMechanismConfiguration)
    },
    [
      createBaseVNode(
        "legend",
        null,
        toDisplayString($setup.t("files_external", "Authentication")),
        1
        /* TEXT */
      ),
      $setup.customComponent ? (openBlock(), createElementBlock(
        Fragment,
        { key: 0 },
        [
          $setup.isLoadingCustomComponent ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : (openBlock(), createElementBlock(
            Fragment,
            { key: 1 },
            [
              createCommentVNode(" eslint-disable vue/attribute-hyphenation,vue/v-on-event-hyphenation -- for custom elements the casing is fixed! "),
              (openBlock(), createBlock(resolveDynamicComponent($setup.customComponent.tagName), {
                ".modelValue": $setup.modelValue,
                ".authMechanism": $props.authMechanism,
                "onUpdate:modelValue": $setup.onUpdateModelValue
              }, null, 40, [".modelValue", ".authMechanism"]))
            ],
            2112
            /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */
          ))
        ],
        64
        /* STABLE_FRAGMENT */
      )) : (openBlock(true), createElementBlock(
        Fragment,
        { key: 1 },
        renderList($setup.configuration, (configOption, configKey) => {
          return withDirectives((openBlock(), createBlock($setup["ConfigurationEntry"], {
            key: configOption.value,
            modelValue: $setup.modelValue[configKey],
            "onUpdate:modelValue": ($event) => $setup.modelValue[configKey] = $event,
            "config-key": configKey,
            "config-option": configOption
          }, null, 8, ["modelValue", "onUpdate:modelValue", "config-key", "config-option"])), [
            [vShow, !(configOption.flags & $setup.ConfigurationFlag.Hidden)]
          ]);
        }),
        128
        /* KEYED_FRAGMENT */
      ))
    ],
    2
    /* CLASS */
  )) : createCommentVNode("v-if", true);
}
const cssModules$8 = {
  "$style": style0$8
};
const AuthMechanismConfiguration = /* @__PURE__ */ _export_sfc(_sfc_main$9, [["render", _sfc_render$9], ["__cssModules", cssModules$8], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/components/AddExternalStorageDialog/AuthMechanismConfiguration.vue"]]);
const _sfc_main$8 = /* @__PURE__ */ defineComponent({
  __name: "BackendConfiguration",
  props: /* @__PURE__ */ mergeModels({
    configuration: { type: Object, required: true }
  }, {
    "modelValue": { type: Object, ...{ required: true } },
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props, { expose: __expose }) {
    __expose();
    const modelValue = useModel(__props, "modelValue");
    const props = __props;
    watch(() => props.configuration, () => {
      for (const key in props.configuration) {
        if (!(key in modelValue.value)) {
          modelValue.value[key] = props.configuration[key]?.defaultValue ?? (props.configuration[key]?.type === ConfigurationType.Boolean ? false : "");
        }
      }
    }, { immediate: true });
    const __returned__ = { modelValue, props, get t() {
      return translate;
    }, ConfigurationEntry, get ConfigurationFlag() {
      return ConfigurationFlag;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const backendConfiguration = "_backendConfiguration_1sf6y_2";
const style0$7 = {
  backendConfiguration
};
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "fieldset",
    {
      class: normalizeClass(_ctx.$style.backendConfiguration)
    },
    [
      createBaseVNode(
        "legend",
        null,
        toDisplayString($setup.t("files_external", "Storage configuration")),
        1
        /* TEXT */
      ),
      (openBlock(true), createElementBlock(
        Fragment,
        null,
        renderList($props.configuration, (configOption, configKey) => {
          return withDirectives((openBlock(), createBlock($setup["ConfigurationEntry"], {
            key: configOption.value,
            modelValue: $setup.modelValue[configKey],
            "onUpdate:modelValue": ($event) => $setup.modelValue[configKey] = $event,
            configKey,
            configOption
          }, null, 8, ["modelValue", "onUpdate:modelValue", "configKey", "configOption"])), [
            [vShow, !(configOption.flags & $setup.ConfigurationFlag.Hidden)]
          ]);
        }),
        128
        /* KEYED_FRAGMENT */
      ))
    ],
    2
    /* CLASS */
  );
}
const cssModules$7 = {
  "$style": style0$7
};
const BackendConfiguration = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$8], ["__cssModules", cssModules$7], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/components/AddExternalStorageDialog/BackendConfiguration.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const { isAdmin: isAdmin$1 } = loadState("files_external", "settings");
const useStorages = defineStore("files_external--storages", () => {
  const globalStorages = ref([]);
  const userStorages = ref([]);
  async function createGlobalStorage(storage) {
    const url = generateUrl("apps/files_external/globalstorages");
    const { data } = await cancelableClient.post(
      url,
      toRaw(storage),
      { confirmPassword: PwdConfirmationMode.Strict }
    );
    globalStorages.value.push(parseStorage(data));
  }
  async function createUserStorage(storage) {
    const url = generateUrl("apps/files_external/userstorages");
    const { data } = await cancelableClient.post(
      url,
      toRaw(storage),
      { confirmPassword: PwdConfirmationMode.Strict }
    );
    userStorages.value.push(parseStorage(data));
  }
  async function deleteStorage(storage) {
    await cancelableClient.delete(getUrl(storage), {
      confirmPassword: PwdConfirmationMode.Strict
    });
    if (storage.type === "personal") {
      userStorages.value = userStorages.value.filter((s) => s.id !== storage.id);
    } else {
      globalStorages.value = globalStorages.value.filter((s) => s.id !== storage.id);
    }
  }
  async function updateStorage(storage) {
    const { data } = await cancelableClient.put(
      getUrl(storage),
      toRaw(storage),
      { confirmPassword: PwdConfirmationMode.Strict }
    );
    overrideStorage(parseStorage(data));
  }
  async function reloadStorage(storage) {
    const { data } = await cancelableClient.get(getUrl(storage));
    overrideStorage(parseStorage(data));
  }
  initialize();
  return {
    globalStorages,
    userStorages,
    createGlobalStorage,
    createUserStorage,
    deleteStorage,
    reloadStorage,
    updateStorage
  };
  async function loadStorages(type) {
    const url = `apps/files_external/${type}`;
    const { data } = await cancelableClient.get(generateUrl(url));
    return Object.values(data).map(parseStorage);
  }
  async function initialize() {
    addPasswordConfirmationInterceptors(cancelableClient);
    if (isAdmin$1) {
      globalStorages.value = await loadStorages("globalstorages");
    } else {
      userStorages.value = await loadStorages("userstorages");
      globalStorages.value = await loadStorages("userglobalstorages");
    }
  }
  function getUrl(storage) {
    const type = storage.type === "personal" ? "userstorages" : "globalstorages";
    return generateUrl(`apps/files_external/${type}/${storage.id}`);
  }
  function overrideStorage(storage) {
    if (storage.type === "personal") {
      const index = userStorages.value.findIndex((s) => s.id === storage.id);
      userStorages.value.splice(index, 1, storage);
    } else {
      const index = globalStorages.value.findIndex((s) => s.id === storage.id);
      globalStorages.value.splice(index, 1, storage);
    }
  }
});
function parseStorage(storage) {
  return {
    ...storage,
    mountOptions: parseMountOptions(storage.mountOptions)
  };
}
function parseMountOptions(options) {
  const mountOptions2 = { ...options };
  mountOptions2.encrypt = convertBooleanOptions(mountOptions2.encrypt, true);
  mountOptions2.previews = convertBooleanOptions(mountOptions2.previews, true);
  mountOptions2.enable_sharing = convertBooleanOptions(mountOptions2.enable_sharing, false);
  mountOptions2.filesystem_check_changes = typeof mountOptions2.filesystem_check_changes === "string" ? Number.parseInt(mountOptions2.filesystem_check_changes) : mountOptions2.filesystem_check_changes ?? MountOptionsCheckFilesystem.OncePerRequest;
  mountOptions2.encoding_compatibility = convertBooleanOptions(mountOptions2.encoding_compatibility, false);
  mountOptions2.readonly = convertBooleanOptions(mountOptions2.readonly, false);
  return mountOptions2;
}
function convertBooleanOptions(option, fallback = false) {
  if (option === void 0) {
    return fallback;
  }
  return option === true || option === "true" || option === "1";
}
const _sfc_main$7 = /* @__PURE__ */ defineComponent({
  __name: "MountOptions",
  props: {
    "modelValue": { type: Object, ...{ required: true } },
    "modelModifiers": {}
  },
  emits: ["update:modelValue"],
  setup(__props, { expose: __expose }) {
    __expose();
    const mountOptions2 = useModel(__props, "modelValue");
    watchEffect(() => {
      if (Object.keys(mountOptions2.value).length === 0) {
        mountOptions2.value = parseMountOptions(mountOptions2.value);
      }
    });
    const { hasEncryption } = loadState("files_external", "settings");
    const idButton = useId();
    const idFieldset = useId();
    const isExpanded = ref(false);
    const checkFilesystemOptions = [
      {
        label: translate("files_external", "Never"),
        value: MountOptionsCheckFilesystem.Never
      },
      {
        label: translate("files_external", "Once every direct access"),
        value: MountOptionsCheckFilesystem.OncePerRequest
      },
      {
        label: translate("files_external", "Always"),
        value: MountOptionsCheckFilesystem.Always
      }
    ];
    const checkFilesystem = computed({
      get() {
        return checkFilesystemOptions.find((option) => option.value === mountOptions2.value.filesystem_check_changes);
      },
      set(value) {
        mountOptions2.value.filesystem_check_changes = value?.value ?? MountOptionsCheckFilesystem.OncePerRequest;
      }
    });
    const __returned__ = { mountOptions: mountOptions2, hasEncryption, idButton, idFieldset, isExpanded, checkFilesystemOptions, checkFilesystem, get mdiChevronDown() {
      return mdiChevronDown;
    }, get mdiChevronRight() {
      return mdiChevronRight;
    }, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcIconSvgWrapper() {
      return NcIconSvgWrapper;
    }, get NcSelect() {
      return NcSelect;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const mountOptions = "_mountOptions_1fqyb_2";
const mountOptions__fieldset = "_mountOptions__fieldset_1fqyb_12";
const style0$6 = {
  mountOptions,
  mountOptions__fieldset
};
const _hoisted_1$3 = ["id", "aria-labelledby"];
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass(_ctx.$style.mountOptions)
    },
    [
      createVNode($setup["NcButton"], {
        id: $setup.idButton,
        "aria-controls": $setup.idFieldset,
        "aria-expanded": $setup.isExpanded,
        variant: "tertiary-no-background",
        onClick: _cache[0] || (_cache[0] = ($event) => $setup.isExpanded = !$setup.isExpanded)
      }, {
        icon: withCtx(() => [
          createVNode($setup["NcIconSvgWrapper"], {
            directional: "",
            path: $setup.isExpanded ? $setup.mdiChevronDown : $setup.mdiChevronRight
          }, null, 8, ["path"])
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("files_external", "Mount options")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["id", "aria-controls", "aria-expanded"]),
      withDirectives(createBaseVNode("fieldset", {
        id: $setup.idFieldset,
        class: normalizeClass(_ctx.$style.mountOptions__fieldset),
        "aria-labelledby": $setup.idButton
      }, [
        createVNode($setup["NcSelect"], {
          modelValue: $setup.checkFilesystem,
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.checkFilesystem = $event),
          inputLabel: $setup.t("files_external", "Check filesystem changes"),
          options: $setup.checkFilesystemOptions
        }, null, 8, ["modelValue", "inputLabel"]),
        createVNode($setup["NcCheckboxRadioSwitch"], {
          modelValue: $props.modelValue.readonly,
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $props.modelValue.readonly = $event),
          type: "switch"
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("files_external", "Read only")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue"]),
        createVNode($setup["NcCheckboxRadioSwitch"], {
          modelValue: $props.modelValue.previews,
          "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $props.modelValue.previews = $event),
          type: "switch"
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("files_external", "Enable previews")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue"]),
        createVNode($setup["NcCheckboxRadioSwitch"], {
          modelValue: $props.modelValue.enable_sharing,
          "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $props.modelValue.enable_sharing = $event),
          type: "switch"
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("files_external", "Enable sharing")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue"]),
        $setup.hasEncryption ? (openBlock(), createBlock($setup["NcCheckboxRadioSwitch"], {
          key: 0,
          modelValue: $props.modelValue.encrypt,
          "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $props.modelValue.encrypt = $event),
          type: "switch"
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("files_external", "Enable encryption")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue"])) : createCommentVNode("v-if", true),
        createVNode($setup["NcCheckboxRadioSwitch"], {
          modelValue: $props.modelValue.encoding_compatibility,
          "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $props.modelValue.encoding_compatibility = $event),
          type: "switch"
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("files_external", "Compatibility with Mac NFD encoding (slow)")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue"])
      ], 10, _hoisted_1$3), [
        [vShow, $setup.isExpanded]
      ])
    ],
    2
    /* CLASS */
  );
}
const cssModules$6 = {
  "$style": style0$6
};
const MountOptions = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7], ["__cssModules", cssModules$6], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/components/AddExternalStorageDialog/MountOptions.vue"]]);
const { isAdmin } = loadState("files_external", "settings");
const allowedBackendIds = loadState("files_external", "allowedBackends");
const backends = loadState("files_external", "backends").filter((b) => allowedBackendIds.includes(b.identifier));
const allAuthMechanisms = loadState("files_external", "authMechanisms");
const _sfc_main$6 = /* @__PURE__ */ defineComponent({
  __name: "AddExternalStorageDialog",
  props: /* @__PURE__ */ mergeModels({
    storage: { type: Object, required: false, default: () => ({ backendOptions: {}, mountOptions: {}, type: isAdmin ? "system" : "personal" }) }
  }, {
    "open": { type: Boolean, ...{ default: true } },
    "openModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["close"], ["update:open"]),
  setup(__props, { expose: __expose }) {
    __expose();
    const open = useModel(__props, "open");
    const internalStorage = ref(structuredClone(toRaw(__props.storage)));
    watchEffect(() => {
      if (open.value) {
        internalStorage.value = structuredClone(toRaw(__props.storage));
      }
    });
    const backend = computed({
      get() {
        return backends.find((b) => b.identifier === internalStorage.value.backend);
      },
      set(value) {
        internalStorage.value.backend = value?.identifier;
      }
    });
    const authMechanisms = computed(() => allAuthMechanisms.filter(({ scheme }) => backend.value?.authSchemes[scheme]));
    const authMechanism = computed({
      get() {
        return authMechanisms.value.find((a) => a.identifier === internalStorage.value.authMechanism);
      },
      set(value) {
        internalStorage.value.authMechanism = value?.identifier;
      }
    });
    watch(authMechanisms, () => {
      if (authMechanisms.value.length === 1) {
        internalStorage.value.authMechanism = authMechanisms.value[0].identifier;
      }
    });
    const __returned__ = { isAdmin, allowedBackendIds, backends, allAuthMechanisms, open, internalStorage, backend, authMechanisms, authMechanism, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcDialog() {
      return NcDialog;
    }, get NcSelect() {
      return NcSelect;
    }, get NcTextField() {
      return _sfc_main$b;
    }, ApplicableEntities, AuthMechanismConfiguration, BackendConfiguration, MountOptions };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const externalStorageDialog = "_externalStorageDialog_9wew8_2";
const externalStorageDialog__configuration = "_externalStorageDialog__configuration_9wew8_9";
const style0$5 = {
  externalStorageDialog,
  externalStorageDialog__configuration
};
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcDialog"], {
    open: $setup.open,
    "onUpdate:open": [
      _cache[9] || (_cache[9] = ($event) => $setup.open = $event),
      _cache[11] || (_cache[11] = ($event) => $event || _ctx.$emit("close"))
    ],
    isForm: "",
    contentClasses: _ctx.$style.externalStorageDialog,
    name: $setup.internalStorage.id ? $setup.t("files_external", "Edit storage") : $setup.t("files_external", "Add storage"),
    onSubmit: _cache[10] || (_cache[10] = ($event) => _ctx.$emit("close", $setup.internalStorage))
  }, {
    actions: withCtx(() => [
      $props.storage.id ? (openBlock(), createBlock($setup["NcButton"], {
        key: 0,
        onClick: _cache[8] || (_cache[8] = ($event) => _ctx.$emit("close"))
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("files_external", "Cancel")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      createVNode($setup["NcButton"], {
        variant: "primary",
        type: "submit"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($props.storage.id ? $setup.t("files_external", "Edit") : $setup.t("files_external", "Create")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })
    ]),
    default: withCtx(() => [
      createVNode($setup["NcTextField"], {
        modelValue: $setup.internalStorage.mountPoint,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.internalStorage.mountPoint = $event),
        label: $setup.t("files_external", "Folder name"),
        required: ""
      }, null, 8, ["modelValue", "label"]),
      createVNode($setup["MountOptions"], {
        modelValue: $setup.internalStorage.mountOptions,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.internalStorage.mountOptions = $event)
      }, null, 8, ["modelValue"]),
      $setup.isAdmin ? (openBlock(), createBlock($setup["ApplicableEntities"], {
        key: 0,
        groups: $setup.internalStorage.applicableGroups,
        "onUpdate:groups": _cache[2] || (_cache[2] = ($event) => $setup.internalStorage.applicableGroups = $event),
        users: $setup.internalStorage.applicableUsers,
        "onUpdate:users": _cache[3] || (_cache[3] = ($event) => $setup.internalStorage.applicableUsers = $event)
      }, null, 8, ["groups", "users"])) : createCommentVNode("v-if", true),
      createVNode($setup["NcSelect"], {
        modelValue: $setup.backend,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $setup.backend = $event),
        options: $setup.backends,
        disabled: !!($setup.internalStorage.id && $setup.internalStorage.backend),
        inputLabel: $setup.t("files_external", "External storage"),
        label: "name",
        required: ""
      }, null, 8, ["modelValue", "options", "disabled", "inputLabel"]),
      createVNode($setup["NcSelect"], {
        modelValue: $setup.authMechanism,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $setup.authMechanism = $event),
        options: $setup.authMechanisms,
        disabled: !$setup.internalStorage.backend || $setup.authMechanisms.length <= 1 || !!($setup.internalStorage.id && $setup.internalStorage.authMechanism),
        inputLabel: $setup.t("files_external", "Authentication"),
        label: "name",
        required: ""
      }, null, 8, ["modelValue", "options", "disabled", "inputLabel"]),
      $setup.backend && $setup.internalStorage.backendOptions ? (openBlock(), createBlock($setup["BackendConfiguration"], {
        key: 1,
        modelValue: $setup.internalStorage.backendOptions,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $setup.internalStorage.backendOptions = $event),
        class: normalizeClass(_ctx.$style.externalStorageDialog__configuration),
        configuration: $setup.backend.configuration
      }, null, 8, ["modelValue", "class", "configuration"])) : createCommentVNode("v-if", true),
      $setup.authMechanism && $setup.internalStorage.backendOptions ? (openBlock(), createBlock($setup["AuthMechanismConfiguration"], {
        key: 2,
        modelValue: $setup.internalStorage.backendOptions,
        "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $setup.internalStorage.backendOptions = $event),
        class: normalizeClass(_ctx.$style.externalStorageDialog__configuration),
        authMechanism: $setup.authMechanism
      }, null, 8, ["modelValue", "class", "authMechanism"])) : createCommentVNode("v-if", true)
    ]),
    _: 1
    /* STABLE */
  }, 8, ["open", "contentClasses", "name"]);
}
const cssModules$5 = {
  "$style": style0$5
};
const AddExternalStorageDialog = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__cssModules", cssModules$5], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/components/AddExternalStorageDialog/AddExternalStorageDialog.vue"]]);
const _sfc_main$5 = /* @__PURE__ */ defineComponent({
  __name: "ExternalStorageTableRow",
  props: {
    storage: { type: Object, required: true },
    isAdmin: { type: Boolean, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const store = useStorages();
    const backends2 = loadState("files_external", "backends");
    const backendName = computed(() => backends2.find((b) => b.identifier === props.storage.backend).name);
    const authMechanisms = loadState("files_external", "authMechanisms");
    const authMechanismName = computed(() => authMechanisms.find((a) => a.identifier === props.storage.authMechanism).name);
    const checkingStatus = ref(false);
    const status = computed(() => {
      if (checkingStatus.value) {
        return {
          icon: "loading",
          label: translate("files_external", "Checking …")
        };
      }
      const status2 = props.storage.status ?? StorageStatus.Indeterminate;
      const label = props.storage.statusMessage || StorageStatusMessage[status2];
      const icon = StorageStatusIcons[status2];
      const isWarning = status2 === StorageStatus.NetworkError || status2 === StorageStatus.Timeout;
      const isError = !isWarning && status2 !== StorageStatus.Success && status2 !== StorageStatus.Indeterminate;
      return { icon, label, isWarning, isError };
    });
    const users = useUsers(() => props.storage.applicableUsers || []);
    async function onDelete() {
      await store.deleteStorage(props.storage);
    }
    async function onEdit() {
      const storage = await spawnDialog(AddExternalStorageDialog, {
        storage: props.storage
      });
      if (!storage) {
        return;
      }
      await store.updateStorage(storage);
    }
    async function reloadStatus() {
      checkingStatus.value = true;
      try {
        await store.reloadStorage(props.storage);
      } finally {
        checkingStatus.value = false;
      }
    }
    const __returned__ = { props, store, backends: backends2, backendName, authMechanisms, authMechanismName, checkingStatus, status, users, onDelete, onEdit, reloadStatus, get mdiAccountGroupOutline() {
      return mdiAccountGroupOutline;
    }, get mdiInformationOutline() {
      return mdiInformationOutline;
    }, get mdiPencilOutline() {
      return mdiPencilOutline;
    }, get mdiTrashCanOutline() {
      return mdiTrashCanOutline;
    }, get t() {
      return translate;
    }, get NcChip() {
      return NcChip;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, get NcUserBubble() {
      return NcUserBubble;
    }, get NcButton() {
      return NcButton;
    }, get NcIconSvgWrapper() {
      return NcIconSvgWrapper;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const storageTableRow__cellActions = "_storageTableRow__cellActions_1k62p_2";
const storageTableRow__cellApplicable = "_storageTableRow__cellApplicable_1k62p_7";
const storageTableRow__status_warning = "_storageTableRow__status_warning_1k62p_17";
const storageTableRow__status_error = "_storageTableRow__status_error_1k62p_21";
const style0$4 = {
  storageTableRow__cellActions,
  storageTableRow__cellApplicable,
  storageTableRow__status_warning,
  storageTableRow__status_error
};
const _hoisted_1$2 = { class: "hidden-visually" };
const _hoisted_2$1 = { key: 0 };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "tr",
    {
      class: normalizeClass(_ctx.$style.storageTableRow)
    },
    [
      createBaseVNode("td", null, [
        createBaseVNode(
          "span",
          _hoisted_1$2,
          toDisplayString($setup.status.label),
          1
          /* TEXT */
        ),
        createVNode($setup["NcButton"], {
          "aria-label": $setup.t("files_external", "Recheck status"),
          title: $setup.status.label,
          variant: "tertiary-no-background",
          onClick: $setup.reloadStatus
        }, {
          icon: withCtx(() => [
            $setup.status.icon === "loading" ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
              key: 1,
              class: normalizeClass({
                [_ctx.$style.storageTableRow__status_error]: $setup.status.isError,
                [_ctx.$style.storageTableRow__status_warning]: $setup.status.isWarning
              }),
              path: $setup.status.icon
            }, null, 8, ["class", "path"]))
          ]),
          _: 1
          /* STABLE */
        }, 8, ["aria-label", "title"])
      ]),
      createBaseVNode(
        "td",
        null,
        toDisplayString($props.storage.mountPoint),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "td",
        null,
        toDisplayString($setup.backendName),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "td",
        null,
        toDisplayString($setup.authMechanismName),
        1
        /* TEXT */
      ),
      $props.isAdmin ? (openBlock(), createElementBlock("td", _hoisted_2$1, [
        createBaseVNode(
          "div",
          {
            class: normalizeClass(_ctx.$style.storageTableRow__cellApplicable)
          },
          [
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($props.storage.applicableGroups, (group) => {
                return openBlock(), createBlock($setup["NcChip"], {
                  key: group,
                  iconPath: $setup.mdiAccountGroupOutline,
                  noClose: "",
                  text: group
                }, null, 8, ["iconPath", "text"]);
              }),
              128
              /* KEYED_FRAGMENT */
            )),
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($setup.users, (user) => {
                return openBlock(), createBlock($setup["NcUserBubble"], {
                  key: user.user,
                  displayName: user.displayName,
                  size: 24,
                  user: user.user
                }, null, 8, ["displayName", "user"]);
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ],
          2
          /* CLASS */
        )
      ])) : createCommentVNode("v-if", true),
      createBaseVNode("td", null, [
        $props.isAdmin || $props.storage.type === "personal" ? (openBlock(), createElementBlock(
          "div",
          {
            key: 0,
            class: normalizeClass(_ctx.$style.storageTableRow__cellActions)
          },
          [
            createVNode($setup["NcButton"], {
              "aria-label": $setup.t("files_external", "Edit"),
              title: $setup.t("files_external", "Edit"),
              onClick: $setup.onEdit
            }, {
              icon: withCtx(() => [
                createVNode($setup["NcIconSvgWrapper"], { path: $setup.mdiPencilOutline }, null, 8, ["path"])
              ]),
              _: 1
              /* STABLE */
            }, 8, ["aria-label", "title"]),
            createVNode($setup["NcButton"], {
              "aria-label": $setup.t("files_external", "Delete"),
              title: $setup.t("files_external", "Delete"),
              variant: "error",
              onClick: $setup.onDelete
            }, {
              icon: withCtx(() => [
                createVNode($setup["NcIconSvgWrapper"], { path: $setup.mdiTrashCanOutline }, null, 8, ["path"])
              ]),
              _: 1
              /* STABLE */
            }, 8, ["aria-label", "title"])
          ],
          2
          /* CLASS */
        )) : (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
          key: 1,
          inline: "",
          path: $setup.mdiInformationOutline,
          name: $setup.t("files_external", "System provided storage"),
          title: $setup.t("files_external", "System provided storage")
        }, null, 8, ["path", "name", "title"]))
      ])
    ],
    2
    /* CLASS */
  );
}
const cssModules$4 = {
  "$style": style0$4
};
const ExternalStorageTableRow = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__cssModules", cssModules$4], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/components/ExternalStorageTableRow.vue"]]);
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "ExternalStorageTable",
  setup(__props, { expose: __expose }) {
    __expose();
    const store = useStorages();
    const { isAdmin: isAdmin2 } = loadState("files_external", "settings");
    const storages = computed(() => {
      if (isAdmin2) {
        return store.globalStorages;
      } else {
        return [
          ...store.userStorages,
          ...store.globalStorages
        ];
      }
    });
    const __returned__ = { store, isAdmin: isAdmin2, storages, get t() {
      return translate;
    }, ExternalStorageTableRow };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const storageTable = "_storageTable_1dqte_2";
const storageTable__header = "_storageTable__header_1dqte_11";
const storageTable__headerStatus = "_storageTable__headerStatus_1dqte_16";
const storageTable__headerFolder = "_storageTable__headerFolder_1dqte_20";
const storageTable__headerBackend = "_storageTable__headerBackend_1dqte_24";
const storageTable__headerFAuthentication = "_storageTable__headerFAuthentication_1dqte_28";
const storageTable__headerActions = "_storageTable__headerActions_1dqte_32";
const style0$3 = {
  storageTable,
  storageTable__header,
  storageTable__headerStatus,
  storageTable__headerFolder,
  storageTable__headerBackend,
  storageTable__headerFAuthentication,
  storageTable__headerActions
};
const _hoisted_1$1 = ["aria-label"];
const _hoisted_2 = { class: "hidden-visually" };
const _hoisted_3 = { key: 0 };
const _hoisted_4 = { class: "hidden-visually" };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("table", {
    class: normalizeClass(_ctx.$style.storageTable),
    "aria-label": $setup.t("files_external", "External storages")
  }, [
    createBaseVNode(
      "thead",
      {
        class: normalizeClass(_ctx.$style.storageTable__header)
      },
      [
        createBaseVNode("tr", null, [
          createBaseVNode(
            "th",
            {
              class: normalizeClass(_ctx.$style.storageTable__headerStatus)
            },
            [
              createBaseVNode(
                "span",
                _hoisted_2,
                toDisplayString($setup.t("files_external", "Status")),
                1
                /* TEXT */
              )
            ],
            2
            /* CLASS */
          ),
          createBaseVNode(
            "th",
            {
              class: normalizeClass(_ctx.$style.storageTable__headerFolder)
            },
            toDisplayString($setup.t("files_external", "Folder name")),
            3
            /* TEXT, CLASS */
          ),
          createBaseVNode(
            "th",
            {
              class: normalizeClass(_ctx.$style.storageTable__headerBackend)
            },
            toDisplayString($setup.t("files_external", "External storage")),
            3
            /* TEXT, CLASS */
          ),
          createBaseVNode(
            "th",
            {
              class: normalizeClass(_ctx.$style.storageTable__headerAuthentication)
            },
            toDisplayString($setup.t("files_external", "Authentication")),
            3
            /* TEXT, CLASS */
          ),
          $setup.isAdmin ? (openBlock(), createElementBlock(
            "th",
            _hoisted_3,
            toDisplayString($setup.t("files_external", "Restricted to")),
            1
            /* TEXT */
          )) : createCommentVNode("v-if", true),
          createBaseVNode(
            "th",
            {
              class: normalizeClass(_ctx.$style.storageTable__headerActions)
            },
            [
              createBaseVNode(
                "span",
                _hoisted_4,
                toDisplayString($setup.t("files_external", "Actions")),
                1
                /* TEXT */
              )
            ],
            2
            /* CLASS */
          )
        ])
      ],
      2
      /* CLASS */
    ),
    createBaseVNode("tbody", null, [
      (openBlock(true), createElementBlock(
        Fragment,
        null,
        renderList($setup.storages, (storage) => {
          return openBlock(), createBlock($setup["ExternalStorageTableRow"], {
            key: storage.id,
            isAdmin: $setup.isAdmin,
            storage
          }, null, 8, ["isAdmin", "storage"]);
        }),
        128
        /* KEYED_FRAGMENT */
      ))
    ])
  ], 10, _hoisted_1$1);
}
const cssModules$3 = {
  "$style": style0$3
};
const ExternalStorageTable = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__cssModules", cssModules$3], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/components/ExternalStorageTable.vue"]]);
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "UserMountSettings",
  setup(__props, { expose: __expose }) {
    __expose();
    const userMounting = loadState("files_external", "user-mounting");
    const availableBackends = loadState("files_external", "backends").filter((backend) => backend.identifier !== "local");
    const allowUserMounting = ref(userMounting.allowUserMounting);
    const allowedBackends = ref(userMounting.allowedBackends);
    watch(allowUserMounting, () => {
      const backupValue = !allowUserMounting.value;
      window.OCP.AppConfig.setValue(
        "files_external",
        "allow_user_mounting",
        allowUserMounting.value ? "yes" : "no",
        {
          success: () => showSuccess(translate("files_external", "Saved")),
          error: () => {
            allowUserMounting.value = backupValue;
            showError(translate("files_external", "Error while saving"));
          }
        }
      );
    });
    watch(allowedBackends, (newValue, oldValue) => {
      window.OCP.AppConfig.setValue(
        "files_external",
        "user_mounting_backends",
        newValue.join(","),
        {
          success: () => showSuccess(translate("files_external", "Saved allowed backends")),
          error: () => {
            showError(translate("files_external", "Failed to save allowed backends"));
            allowedBackends.value = oldValue;
          }
        }
      );
    });
    const __returned__ = { userMounting, availableBackends, allowUserMounting, allowedBackends, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const userMountSettings__heading = "_userMountSettings__heading_59moz_2";
const userMountSettings__backends = "_userMountSettings__backends_59moz_9";
const style0$2 = {
  userMountSettings__heading,
  userMountSettings__backends
};
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("form", null, [
    createBaseVNode(
      "h3",
      {
        class: normalizeClass(_ctx.$style.userMountSettings__heading)
      },
      toDisplayString($setup.t("files_external", "Advanced options for external storage mounts")),
      3
      /* TEXT, CLASS */
    ),
    createVNode($setup["NcCheckboxRadioSwitch"], {
      modelValue: $setup.allowUserMounting,
      "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.allowUserMounting = $event),
      type: "switch"
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("files_external", "Allow people to mount external storage")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["modelValue"]),
    withDirectives(createBaseVNode(
      "fieldset",
      {
        class: normalizeClass(_ctx.$style.userMountSettings__backends)
      },
      [
        createBaseVNode(
          "legend",
          null,
          toDisplayString($setup.t("files_external", "External storage backends people are allowed to mount")),
          1
          /* TEXT */
        ),
        (openBlock(true), createElementBlock(
          Fragment,
          null,
          renderList($setup.availableBackends, (backend) => {
            return openBlock(), createBlock($setup["NcCheckboxRadioSwitch"], {
              key: backend.identifier,
              modelValue: $setup.allowedBackends,
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.allowedBackends = $event),
              value: backend.identifier,
              name: "allowUserMountingBackends[]"
            }, {
              default: withCtx(() => [
                createTextVNode(
                  toDisplayString(backend.name),
                  1
                  /* TEXT */
                )
              ]),
              _: 2
              /* DYNAMIC */
            }, 1032, ["modelValue", "value"]);
          }),
          128
          /* KEYED_FRAGMENT */
        ))
      ],
      2
      /* CLASS */
    ), [
      [vShow, $setup.allowUserMounting]
    ])
  ]);
}
const cssModules$2 = {
  "$style": style0$2
};
const UserMountSettings = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__cssModules", cssModules$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/components/UserMountSettings.vue"]]);
const filesExternalSvg = '<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px"><path d="M456-432h156.48q24.96 0 42.24-17.39Q672-466.77 672-491.89q0-25.11-17.42-42.61T612-552h-1q-4.83-30.72-27.99-51.36Q559.85-624 528-624q-26 0-45.98 12.96-19.99 12.96-30.46 35.04-28.54 1.92-48.05 22.56Q384-532.8 384-504q0 28.8 21 50.4 21 21.6 51 21.6ZM120-144q-29.7 0-50.85-21.15Q48-186.3 48-216v-504h72v504h633v72H120Zm144-144q-29.7 0-50.85-21.15Q192-330.3 192-360v-432q0-29.7 21.15-50.85Q234.3-864 264-864h168l96 96h264q30-1 51 20.44 21 21.45 21 51.56v336q0 29.7-21.15 50.85Q821.7-288 792-288H264Z"/></svg>\n';
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "ExternalStoragesSection",
  setup(__props, { expose: __expose }) {
    __expose();
    const settings = loadState("files_external", "settings", {
      docUrl: "",
      dependencyIssues: {
        messages: null,
        modules: null
      },
      isAdmin: false
    });
    const store = useStorages();
    const dependencyIssues = settings.dependencyIssues?.messages ?? [];
    const missingModules = settings.dependencyIssues?.modules ?? {};
    const showDialog = ref(false);
    const newStorage = ref();
    async function addStorage(storage) {
      showDialog.value = false;
      if (!storage) {
        return;
      }
      try {
        if (settings.isAdmin) {
          await store.createGlobalStorage(storage);
        } else {
          await store.createUserStorage(storage);
        }
        newStorage.value = void 0;
      } catch (error) {
        logger.error("Failed to add external storage", { error, storage });
        newStorage.value = { ...storage };
        showDialog.value = true;
      }
    }
    const __returned__ = { settings, store, dependencyIssues, missingModules, showDialog, newStorage, addStorage, get mdiPlus() {
      return mdiPlus;
    }, get n() {
      return translatePlural;
    }, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcEmptyContent() {
      return NcEmptyContent;
    }, get NcIconSvgWrapper() {
      return NcIconSvgWrapper;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, AddExternalStorageDialog, ExternalStorageTable, UserMountSettings, get filesExternalSvg() {
      return filesExternalSvg;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const externalStoragesSection__dependantList = "_externalStoragesSection__dependantList_atsmn_2";
const externalStoragesSection__newStorageButton = "_externalStoragesSection__newStorageButton_atsmn_7";
const style0$1 = {
  externalStoragesSection__dependantList,
  externalStoragesSection__newStorageButton
};
const _hoisted_1 = ["aria-label"];
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    docUrl: $setup.settings.docUrl,
    name: $setup.t("files_external", "External storage"),
    description: $setup.t("files_external", "External storage enables you to mount external storage services and devices as secondary Nextcloud storage devices.") + ($setup.settings.isAdmin ? " " + $setup.t("files_external", "You may also allow people to mount their own external storage services.") : "")
  }, {
    default: withCtx(() => [
      createCommentVNode(" Dependency error messages "),
      (openBlock(true), createElementBlock(
        Fragment,
        null,
        renderList($setup.dependencyIssues, (message, index) => {
          return openBlock(), createBlock(
            $setup["NcNoteCard"],
            {
              key: index,
              type: "error"
            },
            {
              default: withCtx(() => [
                createTextVNode(
                  toDisplayString(message),
                  1
                  /* TEXT */
                )
              ]),
              _: 2
              /* DYNAMIC */
            },
            1024
            /* DYNAMIC_SLOTS */
          );
        }),
        128
        /* KEYED_FRAGMENT */
      )),
      createCommentVNode(" Missing modules for backends "),
      (openBlock(true), createElementBlock(
        Fragment,
        null,
        renderList($setup.missingModules, (dependants, module) => {
          return openBlock(), createBlock(
            $setup["NcNoteCard"],
            {
              key: module,
              type: "warning"
            },
            {
              default: withCtx(() => [
                createBaseVNode("p", null, [
                  module === "curl" ? (openBlock(), createElementBlock(
                    Fragment,
                    { key: 0 },
                    [
                      createTextVNode(
                        toDisplayString($setup.t("files_external", "The cURL support in PHP is not enabled or installed.")),
                        1
                        /* TEXT */
                      )
                    ],
                    64
                    /* STABLE_FRAGMENT */
                  )) : module === "ftp" ? (openBlock(), createElementBlock(
                    Fragment,
                    { key: 1 },
                    [
                      createTextVNode(
                        toDisplayString($setup.t("files_external", "The FTP support in PHP is not enabled or installed.")),
                        1
                        /* TEXT */
                      )
                    ],
                    64
                    /* STABLE_FRAGMENT */
                  )) : (openBlock(), createElementBlock(
                    Fragment,
                    { key: 2 },
                    [
                      createTextVNode(
                        toDisplayString($setup.t("files_external", "{module} is not installed.", { module })),
                        1
                        /* TEXT */
                      )
                    ],
                    64
                    /* STABLE_FRAGMENT */
                  )),
                  createTextVNode(
                    " " + toDisplayString($setup.n(
                      "files_external",
                      "Please ask your system administrator to install it as otherwise mounting the following backend is not possible:",
                      "Please ask your system administrator to install it as otherwise mounting the following backends is not possible:",
                      dependants.length
                    )),
                    1
                    /* TEXT */
                  )
                ]),
                createBaseVNode("ul", {
                  class: normalizeClass(_ctx.$style.externalStoragesSection__dependantList),
                  "aria-label": $setup.t("files_external", "Dependant backends")
                }, [
                  (openBlock(true), createElementBlock(
                    Fragment,
                    null,
                    renderList(dependants, (backend) => {
                      return openBlock(), createElementBlock(
                        "li",
                        { key: backend },
                        toDisplayString(backend),
                        1
                        /* TEXT */
                      );
                    }),
                    128
                    /* KEYED_FRAGMENT */
                  ))
                ], 10, _hoisted_1)
              ]),
              _: 2
              /* DYNAMIC */
            },
            1024
            /* DYNAMIC_SLOTS */
          );
        }),
        128
        /* KEYED_FRAGMENT */
      )),
      createCommentVNode(" For user settings if the user has no permission or for user and admin settings if no storage was configured "),
      false ? (openBlock(), createBlock($setup["NcEmptyContent"], {
        key: 0,
        description: $setup.t("files_external", "No external storage configured or you do not have the permission to configure them")
      }, {
        icon: withCtx(() => [
          createVNode($setup["NcIconSvgWrapper"], {
            svg: $setup.filesExternalSvg,
            size: 64
          }, null, 8, ["svg"])
        ]),
        _: 1
        /* STABLE */
      }, 8, ["description"])) : createCommentVNode("v-if", true),
      createVNode($setup["ExternalStorageTable"]),
      createVNode($setup["NcButton"], {
        class: normalizeClass(_ctx.$style.externalStoragesSection__newStorageButton),
        variant: "primary",
        onClick: _cache[0] || (_cache[0] = ($event) => $setup.showDialog = !$setup.showDialog)
      }, {
        icon: withCtx(() => [
          createVNode($setup["NcIconSvgWrapper"], { path: $setup.mdiPlus }, null, 8, ["path"])
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("files_external", "Add external storage")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["class"]),
      createVNode($setup["AddExternalStorageDialog"], {
        open: $setup.showDialog,
        "onUpdate:open": _cache[1] || (_cache[1] = ($event) => $setup.showDialog = $event),
        storage: $setup.newStorage,
        onClose: $setup.addStorage
      }, null, 8, ["open", "storage"]),
      $setup.settings.isAdmin ? (openBlock(), createBlock($setup["UserMountSettings"], { key: 1 })) : createCommentVNode("v-if", true)
    ]),
    _: 1
    /* STABLE */
  }, 8, ["docUrl", "name", "description"]);
}
const cssModules$1 = {
  "$style": style0$1
};
const ExternalStoragesSection = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__cssModules", cssModules$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/views/ExternalStoragesSection.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "GlobalCredentialsSection",
  setup(__props, { expose: __expose }) {
    __expose();
    const globalCredentials = loadState("files_external", "global-credentials");
    const loading = ref(false);
    const username = ref(globalCredentials.user);
    const password = ref(globalCredentials.password);
    addPasswordConfirmationInterceptors(cancelableClient);
    async function onSubmit() {
      try {
        loading.value = true;
        const { data } = await cancelableClient.post(generateUrl("apps/files_external/globalcredentials"), {
          // This is the UID of the user to save the credentials (admins can set that also for other users)
          uid: globalCredentials.uid,
          user: username.value,
          password: password.value
        }, { confirmPassword: PwdConfirmationMode.Strict });
        if (data) {
          showSuccess(translate("files_external", "Global credentials saved"));
          return;
        }
      } catch (e) {
        logger.error(e);
      } finally {
        loading.value = false;
      }
      showError(translate("files_external", "Could not save global credentials"));
      username.value = globalCredentials.user;
      password.value = globalCredentials.password;
    }
    const __returned__ = { globalCredentials, loading, username, password, onSubmit, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcPasswordField() {
      return NcPasswordField;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, get NcTextField() {
      return _sfc_main$b;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const globalCredentialsSectionForm = "_globalCredentialsSectionForm_bgjv2_2";
const globalCredentialsSectionForm__submit = "_globalCredentialsSectionForm__submit_bgjv2_10";
const style0 = {
  globalCredentialsSectionForm,
  globalCredentialsSectionForm__submit
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("files_external", "Global credentials"),
    description: $setup.t("files_external", "Global credentials can be used to authenticate with multiple external storages that have the same credentials.")
  }, {
    default: withCtx(() => [
      createBaseVNode(
        "form",
        {
          id: "global_credentials",
          class: normalizeClass(_ctx.$style.globalCredentialsSectionForm),
          autocomplete: "false",
          onSubmit: withModifiers($setup.onSubmit, ["prevent"])
        },
        [
          createVNode($setup["NcTextField"], {
            modelValue: $setup.username,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.username = $event),
            name: "username",
            autocomplete: "false",
            label: $setup.t("files_external", "Login")
          }, null, 8, ["modelValue", "label"]),
          createVNode($setup["NcPasswordField"], {
            modelValue: $setup.password,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.password = $event),
            name: "password",
            autocomplete: "false",
            label: $setup.t("files_external", "Password")
          }, null, 8, ["modelValue", "label"]),
          createVNode($setup["NcButton"], {
            class: normalizeClass(_ctx.$style.globalCredentialsSectionForm__submit),
            disabled: $setup.loading,
            variant: "primary",
            type: "submit"
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.loading ? $setup.t("files_external", "Saving …") : $setup.t("files_external", "Save")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["class", "disabled"])
        ],
        34
        /* CLASS, NEED_HYDRATION */
      )
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description"]);
}
const cssModules = {
  "$style": style0
};
const GlobalCredentialsSection = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/views/GlobalCredentialsSection.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "FilesExternalSettings",
  setup(__props, { expose: __expose }) {
    __expose();
    const __returned__ = { ExternalStoragesSection, GlobalCredentialsSection };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    Fragment,
    null,
    [
      createVNode($setup["ExternalStoragesSection"]),
      createVNode($setup["GlobalCredentialsSection"])
    ],
    64
    /* STABLE_FRAGMENT */
  );
}
const FilesExternalApp = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/views/FilesExternalSettings.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const pinia = createPinia();
const app = createApp(FilesExternalApp);
app.config.idPrefix = "files-external";
app.use(pinia);
app.mount("#files-external");
//# sourceMappingURL=files_external-settings.mjs.map
