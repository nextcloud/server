const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { y as ref, n as computed, b as defineComponent, o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, x as createVNode, w as withCtx, j as createTextVNode, c as createBlock, h as createCommentVNode, Q as onBeforeMount, m as mergeProps, z as watch, F as Fragment, v as normalizeClass, C as renderList, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { l as loadState, g as getCapabilities, _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import { t as translate, a as translatePlural } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import { a as NcTextArea } from "./NcTextArea-CWA3KOiC-Cpgesyiv.chunk.mjs";
import "./index-CZV8rpGu.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
/* empty css                                           */
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { g as getLoggerBuilder, c as generateOcsUrl } from "./index-rAufP352.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { P as PlusIcon } from "./Plus-DuSPdibD.chunk.mjs";
import "./index-DD39fp6M.chunk.mjs";
import "./TrayArrowDown-DVjUGg6-.chunk.mjs";
import "./index-BcMnKoRR.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import { N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import "./NcEmojiPicker-Djc9a0gw-F1kmncT2.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import "./index-D5BR15En.chunk.mjs";
/* empty css                                        */
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import { N as NcNoteCard } from "./mdi-BGU2G5q5.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./index-gwTr8m4i.chunk.mjs";
import { c as cancelableClient, i as isAxiosError } from "./index-D5H5XMHa.chunk.mjs";
import { _ as _sfc_main$9 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import "./NcSelectTags-CTHyuMcq-2HejGZhj.chunk.mjs";
import { I as Information } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./emoji-BY_D0V5K-BlCul1cD.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import { d as defineStore, s as storeToRefs, c as createPinia } from "./pinia-0yhe0wHh.chunk.mjs";
import { a as showError, c as showSuccess, g as getDialogBuilder, s as showWarning, b as showInfo } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
const logger = getLoggerBuilder().setApp("LDAP").detectUser().build();
async function createConfig() {
  const response = await cancelableClient.post(generateOcsUrl("apps/user_ldap/api/v1/config"));
  logger.debug("Created configuration", { configId: response.data.ocs.data.configID });
  return response.data.ocs.data.configID;
}
async function copyConfig(configId) {
  const params = new FormData();
  params.set("copyConfig", configId);
  const response = await cancelableClient.post(
    generateOcsUrl("apps/user_ldap/api/v1/config/{configId}/copy", { configId }),
    params
  );
  logger.debug("Created configuration", { configId: response.data.ocs.data.configID });
  return response.data.ocs.data.configID;
}
async function getConfig(configId) {
  const response = await cancelableClient.get(generateOcsUrl("apps/user_ldap/api/v1/config/{configId}", { configId }));
  logger.debug("Fetched configuration", { configId, config: response.data.ocs.data });
  return response.data.ocs.data;
}
async function updateConfig(configId, config) {
  const response = await cancelableClient.put(
    generateOcsUrl("apps/user_ldap/api/v1/config/{configId}", { configId }),
    { configData: config }
  );
  logger.debug("Updated configuration", { configId, config });
  return response.data.ocs.data;
}
async function deleteConfig(configId) {
  try {
    const isConfirmed = await confirmOperation(
      translate("user_ldap", "Confirm action"),
      translate("user_ldap", "Are you sure you want to permanently delete this LDAP configuration? This cannot be undone.")
    );
    if (!isConfirmed) {
      return false;
    }
    await cancelableClient.delete(generateOcsUrl("apps/user_ldap/api/v1/config/{configId}", { configId }));
    logger.debug("Deleted configuration", { configId });
  } catch (error) {
    const errorResponse = error.response;
    showError(errorResponse?.data.ocs.meta.message || translate("user_ldap", "Failed to delete config"));
  }
  return true;
}
async function testConfiguration(configId) {
  const params = new FormData();
  const response = await cancelableClient.post(generateOcsUrl("apps/user_ldap/api/v1/config/{configId}/test", { configId }));
  logger.debug(`Configuration is ${response.data.ocs.data.success ? "valide" : "invalide"}`, { configId, params, response });
  return response.data.ocs.data;
}
async function clearMapping(subject) {
  const isConfirmed = await confirmOperation(
    translate("user_ldap", "Confirm action"),
    translate("user_ldap", "Are you sure you want to permanently clear the LDAP mapping? This cannot be undone.")
  );
  if (!isConfirmed) {
    return false;
  }
  try {
    const response = await cancelableClient.post(
      generateOcsUrl("apps/user_ldap/api/v1/wizard/clearMappings"),
      { subject }
    );
    logger.debug("Cleared mapping", { subject, response });
    showSuccess(translate("user_ldap", "Mapping cleared"));
    return true;
  } catch (error) {
    const errorResponse = error.response;
    showError(errorResponse?.data.ocs.meta.message || translate("user_ldap", "Failed to clear mapping"));
  }
}
async function callWizard(action, configId, extraParams = {}) {
  const params = new FormData();
  Object.entries(extraParams).forEach(([key, value]) => {
    params.set(key, value);
  });
  try {
    const response = await cancelableClient.post(
      generateOcsUrl("apps/user_ldap/api/v1/wizard/{configId}/{action}", { configId, action }),
      params
    );
    logger.debug(`Called wizard action: ${action}`, { configId, params, response });
    return response.data.ocs.data;
  } catch (error) {
    let message = translate("user_ldap", "An error occurred");
    if (isAxiosError(error) && error.response?.data.ocs.meta.status === "failure") {
      if (error.response.data.ocs.meta.message !== "" && error.response.data.ocs.meta.message !== void 0) {
        message = error.response.data.ocs.meta.message;
      }
    }
    showError(message);
    throw error;
  }
}
async function showEnableAutomaticFilterInfo() {
  return await confirmOperation(
    translate("user_ldap", "Mode switch"),
    translate("user_ldap", "Switching the mode will enable automatic LDAP queries. Depending on your LDAP size they may take a while. Do you still want to switch the mode?")
  );
}
async function confirmOperation(name, text) {
  let result = false;
  const dialog = getDialogBuilder(name).setText(text).setSeverity("warning").addButton({
    label: translate("user_ldap", "Cancel"),
    callback() {
    }
  }).addButton({
    label: translate("user_ldap", "Confirm"),
    variant: "error",
    callback() {
      result = true;
    }
  }).build();
  await dialog.show();
  return result;
}
const useLDAPConfigsStore = defineStore("ldap-configs", () => {
  const ldapConfigs = ref(loadState("user_ldap", "ldapConfigs"));
  const selectedConfigId = ref(Object.keys(ldapConfigs.value)[0]);
  const selectedConfig = computed(() => selectedConfigId.value === void 0 ? void 0 : ldapConfigs.value[selectedConfigId.value]);
  const updatingConfig = ref(0);
  function getConfigProxy(configId, postSetHooks = {}) {
    if (ldapConfigs.value[configId] === void 0) {
      throw new Error(`Config with id ${configId} does not exist`);
    }
    return new Proxy(ldapConfigs.value[configId], {
      get(target, property) {
        return target[property];
      },
      set(target, property, newValue) {
        target[property] = newValue;
        (async () => {
          updatingConfig.value++;
          await updateConfig(configId, { [property]: newValue });
          updatingConfig.value--;
          if (postSetHooks[property] !== void 0) {
            postSetHooks[property](target[property]);
          }
        })();
        return true;
      }
    });
  }
  async function create() {
    const configId = await createConfig();
    ldapConfigs.value[configId] = await getConfig(configId);
    selectedConfigId.value = configId;
    return configId;
  }
  async function _copyConfig(fromConfigId) {
    if (ldapConfigs.value[fromConfigId] === void 0) {
      throw new Error(`Config with id ${fromConfigId} does not exist`);
    }
    const configId = await copyConfig(fromConfigId);
    ldapConfigs.value[configId] = { ...ldapConfigs.value[fromConfigId] };
    selectedConfigId.value = configId;
    return configId;
  }
  async function removeConfig(configId) {
    const result = await deleteConfig(configId);
    if (result === true) {
      if (Object.keys(ldapConfigs.value).length === 1) {
        selectedConfigId.value = await create();
        if (selectedConfigId.value !== configId) {
          delete ldapConfigs.value[configId];
        }
      } else {
        selectedConfigId.value = Object.keys(ldapConfigs.value).filter((_configId) => configId !== _configId)[0];
        delete ldapConfigs.value[configId];
      }
    }
  }
  return {
    ldapConfigs,
    selectedConfigId,
    selectedConfig,
    updatingConfig,
    getConfigProxy,
    create,
    copyConfig: _copyConfig,
    removeConfig
  };
});
const _sfc_main$8 = /* @__PURE__ */ defineComponent({
  __name: "AdvancedTab",
  props: {
    configId: { type: String, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const ldapConfigsStore = useLDAPConfigsStore();
    const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId));
    const instanceName = getCapabilities().theming.name;
    const groupMemberAssociation = {
      uniqueMember: "uniqueMember",
      memberUid: "memberUid",
      member: "member (AD)",
      gidNumber: "gidNumber",
      zimbraMailForwardingAddress: "zimbraMailForwardingAddress"
    };
    const __returned__ = { props, ldapConfigsStore, ldapConfigProxy, instanceName, groupMemberAssociation, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcSelect() {
      return NcSelect;
    }, get NcTextArea() {
      return NcTextArea;
    }, get NcTextField() {
      return _sfc_main$9;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$8 = { class: "ldap-wizard__advanced" };
const _hoisted_2$8 = {
  open: "",
  name: "ldap-wizard__advanced__section",
  class: "ldap-wizard__advanced__section"
};
const _hoisted_3$7 = {
  name: "ldap-wizard__advanced__section",
  class: "ldap-wizard__advanced__section"
};
const _hoisted_4$7 = { class: "tablecell" };
const _hoisted_5$6 = {
  name: "ldap-wizard__advanced__section",
  class: "ldap-wizard__advanced__section"
};
const _hoisted_6$4 = {
  name: "ldap-wizard__advanced__section",
  class: "ldap-wizard__advanced__section"
};
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("fieldset", _hoisted_1$8, [
    createBaseVNode("details", _hoisted_2$8, [
      createBaseVNode("summary", null, [
        createBaseVNode(
          "h3",
          null,
          toDisplayString($setup.t("user_ldap", "Connection Settings")),
          1
          /* TEXT */
        )
      ]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Backup (Replica) Host"),
        modelValue: $setup.ldapConfigProxy.ldapBackupHost,
        helperText: $setup.t("user_ldap", "Give an optional backup host. It must be a replica of the main LDAP/AD server."),
        onChange: _cache[0] || (_cache[0] = (event) => $setup.ldapConfigProxy.ldapBackupHost = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        type: "number",
        modelValue: $setup.ldapConfigProxy.ldapBackupPort,
        label: $setup.t("user_ldap", "Backup (Replica) Port"),
        onChange: _cache[1] || (_cache[1] = (event) => $setup.ldapConfigProxy.ldapBackupPort = event.target.value)
      }, null, 8, ["modelValue", "label"]),
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapConfigProxy.ldapOverrideMainServer === "1",
        type: "switch",
        "aria-label": $setup.t("user_ldap", "Only connect to the replica server."),
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $setup.ldapConfigProxy.ldapOverrideMainServer = $event ? "1" : "0")
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Disable Main Server")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue", "aria-label"]),
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapConfigProxy.turnOffCertCheck === "1",
        "aria-label": $setup.t("user_ldap", "Not recommended, use it for testing only! If connection only works with this option, import the LDAP server's SSL certificate in your {instanceName} server.", { instanceName: $setup.instanceName }),
        "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $setup.ldapConfigProxy.turnOffCertCheck = $event ? "1" : "0")
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Turn off SSL certificate validation.")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue", "aria-label"]),
      createVNode($setup["NcTextField"], {
        type: "number",
        label: $setup.t("user_ldap", "Cache Time-To-Live"),
        modelValue: $setup.ldapConfigProxy.ldapCacheTTL,
        helperText: $setup.t("user_ldap", "in seconds. A change empties the cache."),
        onChange: _cache[4] || (_cache[4] = (event) => $setup.ldapConfigProxy.ldapCacheTTL = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"])
    ]),
    createBaseVNode("details", _hoisted_3$7, [
      createBaseVNode("summary", null, [
        createBaseVNode(
          "h3",
          null,
          toDisplayString($setup.t("user_ldap", "Directory Settings")),
          1
          /* TEXT */
        )
      ]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        modelValue: $setup.ldapConfigProxy.ldapUserDisplayName,
        label: $setup.t("user_ldap", "User Display Name Field"),
        helperText: $setup.t("user_ldap", "The LDAP attribute to use to generate the user's display name."),
        onChange: _cache[5] || (_cache[5] = (event) => $setup.ldapConfigProxy.ldapUserDisplayName = event.target.value)
      }, null, 8, ["modelValue", "label", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        modelValue: $setup.ldapConfigProxy.ldapUserDisplayName2,
        label: $setup.t("user_ldap", "2nd User Display Name Field"),
        helperText: $setup.t("user_ldap", "Optional. An LDAP attribute to be added to the display name in brackets. Results in e.g. »John Doe (john.doe@example.org)«."),
        onChange: _cache[6] || (_cache[6] = (event) => $setup.ldapConfigProxy.ldapUserDisplayName2 = event.target.value)
      }, null, 8, ["modelValue", "label", "helperText"]),
      createVNode($setup["NcTextArea"], {
        modelValue: $setup.ldapConfigProxy.ldapBaseUsers,
        placeholder: $setup.t("user_ldap", "One User Base DN per line"),
        label: $setup.t("user_ldap", "Base User Tree"),
        onChange: _cache[7] || (_cache[7] = (event) => $setup.ldapConfigProxy.ldapBaseUsers = event.target.value)
      }, null, 8, ["modelValue", "placeholder", "label"]),
      createVNode($setup["NcTextArea"], {
        modelValue: $setup.ldapConfigProxy.ldapAttributesForUserSearch,
        placeholder: $setup.t("user_ldap", "Optional; one attribute per line"),
        label: $setup.t("user_ldap", "User Search Attributes"),
        onChange: _cache[8] || (_cache[8] = (event) => $setup.ldapConfigProxy.ldapAttributesForUserSearch = event.target.value)
      }, null, 8, ["modelValue", "placeholder", "label"]),
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapConfigProxy.markRemnantsAsDisabled === "1",
        "aria-label": $setup.t("user_ldap", "When switched on, users imported from LDAP which are then missing will be disabled"),
        "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => $setup.ldapConfigProxy.markRemnantsAsDisabled = $event ? "1" : "0")
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Disable users missing from LDAP")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue", "aria-label"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        modelValue: $setup.ldapConfigProxy.ldapGroupDisplayName,
        label: $setup.t("user_ldap", "Group Display Name Field"),
        title: $setup.t("user_ldap", "The LDAP attribute to use to generate the groups's display name."),
        onChange: _cache[10] || (_cache[10] = (event) => $setup.ldapConfigProxy.ldapGroupDisplayName = event.target.value)
      }, null, 8, ["modelValue", "label", "title"]),
      createVNode($setup["NcTextArea"], {
        modelValue: $setup.ldapConfigProxy.ldapBaseGroups,
        placeholder: $setup.t("user_ldap", "One Group Base DN per line"),
        label: $setup.t("user_ldap", "Base Group Tree"),
        onChange: _cache[11] || (_cache[11] = (event) => $setup.ldapConfigProxy.ldapBaseGroups = event.target.value)
      }, null, 8, ["modelValue", "placeholder", "label"]),
      createVNode($setup["NcTextArea"], {
        modelValue: $setup.ldapConfigProxy.ldapAttributesForGroupSearch,
        placeholder: $setup.t("user_ldap", "Optional; one attribute per line"),
        label: $setup.t("user_ldap", "Group Search Attributes"),
        onChange: _cache[12] || (_cache[12] = (event) => $setup.ldapConfigProxy.ldapAttributesForGroupSearch = event.target.value)
      }, null, 8, ["modelValue", "placeholder", "label"]),
      createVNode($setup["NcSelect"], {
        modelValue: $setup.ldapConfigProxy.ldapGroupMemberAssocAttr,
        "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => $setup.ldapConfigProxy.ldapGroupMemberAssocAttr = $event),
        options: Object.keys($setup.groupMemberAssociation),
        inputLabel: $setup.t("user_ldap", "Group-Member association")
      }, {
        option: withCtx(({ label }) => [
          createTextVNode(
            toDisplayString($setup.groupMemberAssociation[label]),
            1
            /* TEXT */
          )
        ]),
        "selected-option": withCtx(({ label }) => [
          createTextVNode(
            toDisplayString($setup.groupMemberAssociation[label]),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue", "options", "inputLabel"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Dynamic Group Member URL"),
        modelValue: $setup.ldapConfigProxy.ldapDynamicGroupMemberURL,
        helperText: $setup.t("user_ldap", "The LDAP attribute that on group objects contains an LDAP search URL that determines what objects belong to the group. (An empty setting disables dynamic group membership functionality.)"),
        onChange: _cache[14] || (_cache[14] = (event) => $setup.ldapConfigProxy.ldapDynamicGroupMemberURL = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapConfigProxy.ldapNestedGroups === "1",
        "aria-label": $setup.t("user_ldap", "When switched on, groups that contain groups are supported. (Only works if the group member attribute contains DNs.)"),
        "onUpdate:modelValue": _cache[15] || (_cache[15] = ($event) => $setup.ldapConfigProxy.ldapNestedGroups = $event ? "1" : "0")
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Nested Groups")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue", "aria-label"]),
      createVNode($setup["NcTextField"], {
        type: "number",
        label: $setup.t("user_ldap", "Paging chunksize"),
        modelValue: $setup.ldapConfigProxy.ldapPagingSize,
        helperText: $setup.t("user_ldap", "Chunksize used for paged LDAP searches that may return bulky results like user or group enumeration. (Setting it 0 disables paged LDAP searches in those situations.)"),
        onChange: _cache[16] || (_cache[16] = (event) => $setup.ldapConfigProxy.ldapPagingSize = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapConfigProxy.turnOnPasswordChange === "1",
        "aria-label": $setup.t("user_ldap", "Allow LDAP users to change their password and allow Super Administrators and Group Administrators to change the password of their LDAP users. Only works when access control policies are configured accordingly on the LDAP server. As passwords are sent in plaintext to the LDAP server, transport encryption must be used and password hashing should be configured on the LDAP server."),
        "onUpdate:modelValue": _cache[17] || (_cache[17] = ($event) => $setup.ldapConfigProxy.turnOnPasswordChange = $event ? "1" : "0")
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Enable LDAP password changes per user")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue", "aria-label"]),
      createBaseVNode(
        "span",
        _hoisted_4$7,
        toDisplayString($setup.t("user_ldap", "(New password is sent as plain text to LDAP)")),
        1
        /* TEXT */
      ),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Default password policy DN"),
        modelValue: $setup.ldapConfigProxy.ldapDefaultPPolicyDN,
        helperText: $setup.t("user_ldap", "The DN of a default password policy that will be used for password expiry handling. Works only when LDAP password changes per user are enabled and is only supported by OpenLDAP. Leave empty to disable password expiry handling."),
        onChange: _cache[18] || (_cache[18] = (event) => $setup.ldapConfigProxy.ldapDefaultPPolicyDN = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"])
    ]),
    createBaseVNode("details", _hoisted_5$6, [
      createBaseVNode("summary", null, [
        createBaseVNode(
          "h3",
          null,
          toDisplayString($setup.t("user_ldap", "Special Attributes")),
          1
          /* TEXT */
        )
      ]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        modelValue: $setup.ldapConfigProxy.ldapQuotaAttribute,
        label: $setup.t("user_ldap", "Quota Field"),
        helperText: $setup.t("user_ldap", "Leave empty for user's default quota. Otherwise, specify an LDAP/AD attribute."),
        onChange: _cache[19] || (_cache[19] = (event) => $setup.ldapConfigProxy.ldapQuotaAttribute = event.target.value)
      }, null, 8, ["modelValue", "label", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        modelValue: $setup.ldapConfigProxy.ldapQuotaDefault,
        label: $setup.t("user_ldap", "Quota Default"),
        helperText: $setup.t("user_ldap", "Override default quota for LDAP users who do not have a quota set in the Quota Field."),
        onChange: _cache[20] || (_cache[20] = (event) => $setup.ldapConfigProxy.ldapQuotaDefault = event.target.value)
      }, null, 8, ["modelValue", "label", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        modelValue: $setup.ldapConfigProxy.ldapEmailAttribute,
        label: $setup.t("user_ldap", "Email Field"),
        helperText: $setup.t("user_ldap", "Set the user's email from their LDAP attribute. Leave it empty for default behaviour."),
        onChange: _cache[21] || (_cache[21] = (event) => $setup.ldapConfigProxy.ldapEmailAttribute = event.target.value)
      }, null, 8, ["modelValue", "label", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "User Home Folder Naming Rule"),
        modelValue: $setup.ldapConfigProxy.homeFolderNamingRule,
        helperText: $setup.t("user_ldap", "Leave empty for username (default). Otherwise, specify an LDAP/AD attribute."),
        onChange: _cache[22] || (_cache[22] = (event) => $setup.ldapConfigProxy.homeFolderNamingRule = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "`$home` Placeholder Field"),
        modelValue: $setup.ldapConfigProxy.ldapExtStorageHomeAttribute,
        helperText: $setup.t("user_ldap", "$home in an external storage configuration will be replaced with the value of the specified attribute"),
        onChange: _cache[23] || (_cache[23] = (event) => $setup.ldapConfigProxy.ldapExtStorageHomeAttribute = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"])
    ]),
    createBaseVNode("details", _hoisted_6$4, [
      createBaseVNode("summary", null, [
        createBaseVNode(
          "h3",
          null,
          toDisplayString($setup.t("user_ldap", "User Profile Attributes")),
          1
          /* TEXT */
        )
      ]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Phone Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributePhone,
        helperText: $setup.t("user_ldap", "User profile Phone will be set from the specified attribute"),
        onChange: _cache[24] || (_cache[24] = (event) => $setup.ldapConfigProxy.ldapAttributePhone = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Website Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeWebsite,
        helperText: $setup.t("user_ldap", "User profile Website will be set from the specified attribute"),
        onChange: _cache[25] || (_cache[25] = (event) => $setup.ldapConfigProxy.ldapAttributeWebsite = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Address Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeAddress,
        helperText: $setup.t("user_ldap", "User profile Address will be set from the specified attribute"),
        onChange: _cache[26] || (_cache[26] = (event) => $setup.ldapConfigProxy.ldapAttributeAddress = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Twitter Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeTwitter,
        helperText: $setup.t("user_ldap", "User profile Twitter will be set from the specified attribute"),
        onChange: _cache[27] || (_cache[27] = (event) => $setup.ldapConfigProxy.ldapAttributeTwitter = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Fediverse Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeFediverse,
        helperText: $setup.t("user_ldap", "User profile Fediverse will be set from the specified attribute"),
        onChange: _cache[28] || (_cache[28] = (event) => $setup.ldapConfigProxy.ldapAttributeFediverse = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Organisation Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeOrganisation,
        helperText: $setup.t("user_ldap", "User profile Organisation will be set from the specified attribute"),
        onChange: _cache[29] || (_cache[29] = (event) => $setup.ldapConfigProxy.ldapAttributeOrganisation = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Role Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeRole,
        helperText: $setup.t("user_ldap", "User profile Role will be set from the specified attribute"),
        onChange: _cache[30] || (_cache[30] = (event) => $setup.ldapConfigProxy.ldapAttributeRole = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Headline Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeHeadline,
        helperText: $setup.t("user_ldap", "User profile Headline will be set from the specified attribute"),
        onChange: _cache[31] || (_cache[31] = (event) => $setup.ldapConfigProxy.ldapAttributeHeadline = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Biography Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeBiography,
        helperText: $setup.t("user_ldap", "User profile Biography will be set from the specified attribute"),
        onChange: _cache[32] || (_cache[32] = (event) => $setup.ldapConfigProxy.ldapAttributeBiography = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "Birthdate Field"),
        modelValue: $setup.ldapConfigProxy.ldapAttributeBirthDate,
        helperText: $setup.t("user_ldap", "User profile Date of birth will be set from the specified attribute"),
        onChange: _cache[33] || (_cache[33] = (event) => $setup.ldapConfigProxy.ldapAttributeBirthDate = event.target.value)
      }, null, 8, ["label", "modelValue", "helperText"])
    ])
  ]);
}
const AdvancedTab = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$8], ["__scopeId", "data-v-4a53d9ec"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_ldap/src/components/SettingsTabs/AdvancedTab.vue"]]);
const _sfc_main$7 = /* @__PURE__ */ defineComponent({
  __name: "ExpertTab",
  props: {
    configId: { type: String, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const ldapConfigsStore = useLDAPConfigsStore();
    const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId));
    const __returned__ = { props, ldapConfigsStore, ldapConfigProxy, get t() {
      return translate;
    }, get NcTextField() {
      return _sfc_main$9;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$7 = { class: "ldap-wizard__expert" };
const _hoisted_2$7 = { class: "ldap-wizard__expert__line" };
const _hoisted_3$6 = { id: "ldap_expert_username_attr" };
const _hoisted_4$6 = { class: "ldap-wizard__expert__line" };
const _hoisted_5$5 = { id: "ldap_expert_uuid_user_attr" };
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("fieldset", _hoisted_1$7, [
    createBaseVNode("div", _hoisted_2$7, [
      createBaseVNode(
        "strong",
        null,
        toDisplayString($setup.t("user_ldap", "Internal Username")),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "p",
        _hoisted_3$6,
        toDisplayString($setup.t("user_ldap", "By default the internal username will be created from the UUID attribute. It makes sure that the username is unique and characters do not need to be converted. The internal username has the restriction that only these characters are allowed: [a-zA-Z0-9_.@-]. Other characters are replaced with their ASCII correspondence or simply omitted. On collisions a number will be added/increased. The internal username is used to identify a user internally. It is also the default name for the user home folder. It is also a part of remote URLs, for instance for all DAV services. With this setting, the default behavior can be overridden. Changes will have effect only on newly mapped (added) LDAP users. Leave it empty for default behavior.")),
        1
        /* TEXT */
      ),
      createVNode($setup["NcTextField"], {
        "aria-describedby": "ldap_expert_username_attr",
        autocomplete: "off",
        label: $setup.t("user_ldap", "Internal Username Attribute:"),
        modelValue: $setup.ldapConfigProxy.ldapExpertUsernameAttr,
        onChange: _cache[0] || (_cache[0] = (event) => $setup.ldapConfigProxy.ldapExpertUsernameAttr = event.target.value)
      }, null, 8, ["label", "modelValue"])
    ]),
    createBaseVNode("div", _hoisted_4$6, [
      createBaseVNode(
        "strong",
        null,
        toDisplayString($setup.t("user_ldap", "Override UUID detection")),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "p",
        _hoisted_5$5,
        toDisplayString($setup.t("user_ldap", "By default, the UUID attribute is automatically detected. The UUID attribute is used to doubtlessly identify LDAP users and groups. Also, the internal username will be created based on the UUID, if not specified otherwise above. You can override the setting and pass an attribute of your choice. You must make sure that the attribute of your choice can be fetched for both users and groups and it is unique. Leave it empty for default behavior. Changes will have effect only on newly mapped (added) LDAP users and groups.")),
        1
        /* TEXT */
      ),
      createVNode($setup["NcTextField"], {
        "aria-describedby": "ldap_expert_uuid_user_attr",
        autocomplete: "off",
        label: $setup.t("user_ldap", "UUID Attribute for Users"),
        modelValue: $setup.ldapConfigProxy.ldapExpertUUIDUserAttr,
        onChange: _cache[1] || (_cache[1] = (event) => $setup.ldapConfigProxy.ldapExpertUUIDUserAttr = event.target.value)
      }, null, 8, ["label", "modelValue"]),
      createVNode($setup["NcTextField"], {
        autocomplete: "off",
        label: $setup.t("user_ldap", "UUID Attribute for Groups"),
        modelValue: $setup.ldapConfigProxy.ldapExpertUUIDGroupAttr,
        onChange: _cache[2] || (_cache[2] = (event) => $setup.ldapConfigProxy.ldapExpertUUIDGroupAttr = event.target.value)
      }, null, 8, ["label", "modelValue"])
    ])
  ]);
}
const ExpertTab = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7], ["__scopeId", "data-v-c0cb0436"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_ldap/src/components/SettingsTabs/ExpertTab.vue"]]);
const _sfc_main$6 = /* @__PURE__ */ defineComponent({
  __name: "GroupsTab",
  props: {
    configId: { type: String, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const ldapConfigsStore = useLDAPConfigsStore();
    const { ldapConfigs } = storeToRefs(ldapConfigsStore);
    const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId, {
      ldapGroupFilterObjectclass: getGroupFilter,
      ldapGroupFilterGroups: getGroupFilter
    }));
    const instanceName = getCapabilities().theming.name;
    const groupsCountLabel = ref(void 0);
    const groupObjectClasses = ref([]);
    const groupGroups = ref([]);
    const loadingGroupCount = ref(false);
    const ldapGroupFilterObjectclass = computed({
      get() {
        return ldapConfigProxy.value.ldapGroupFilterObjectclass.split(";").filter((item) => item !== "");
      },
      set(value) {
        ldapConfigProxy.value.ldapGroupFilterObjectclass = value.join(";");
      }
    });
    const ldapGroupFilterGroups = computed({
      get() {
        return ldapConfigProxy.value.ldapGroupFilterGroups.split(";").filter((item) => item !== "");
      },
      set(value) {
        ldapConfigProxy.value.ldapGroupFilterGroups = value.join(";");
      }
    });
    async function init() {
      const response1 = await callWizard("determineGroupObjectClasses", props.configId);
      groupObjectClasses.value = response1.options?.ldap_groupfilter_objectclass ?? [];
      const response2 = await callWizard("determineGroupsForGroups", props.configId);
      groupGroups.value = response2.options?.ldap_groupfilter_groups ?? [];
    }
    init();
    async function getGroupFilter() {
      const response = await callWizard("getGroupFilter", props.configId);
      ldapConfigs.value[props.configId].ldapGroupFilter = response.changes?.ldap_group_filter ?? "";
    }
    async function countGroups() {
      try {
        loadingGroupCount.value = true;
        const response = await callWizard("countGroups", props.configId);
        groupsCountLabel.value = response.changes.ldap_group_count;
      } finally {
        loadingGroupCount.value = false;
      }
    }
    async function toggleFilterMode(value) {
      if (value) {
        ldapConfigProxy.value.ldapGroupFilterMode = "1";
      } else {
        ldapConfigProxy.value.ldapGroupFilterMode = await showEnableAutomaticFilterInfo() ? "0" : "1";
      }
    }
    const __returned__ = { props, ldapConfigsStore, ldapConfigs, ldapConfigProxy, instanceName, groupsCountLabel, groupObjectClasses, groupGroups, loadingGroupCount, ldapGroupFilterObjectclass, ldapGroupFilterGroups, init, getGroupFilter, countGroups, toggleFilterMode, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, get NcSelect() {
      return NcSelect;
    }, get NcTextArea() {
      return NcTextArea;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$6 = { class: "ldap-wizard__groups" };
const _hoisted_2$6 = { class: "ldap-wizard__groups__line ldap-wizard__groups__filter-selection" };
const _hoisted_3$5 = { class: "ldap-wizard__groups__line ldap-wizard__groups__groups-filter" };
const _hoisted_4$5 = { key: 0 };
const _hoisted_5$4 = { key: 1 };
const _hoisted_6$3 = { class: "ldap-wizard__groups__line ldap-wizard__groups__groups-count-check" };
const _hoisted_7$3 = { key: 1 };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("fieldset", _hoisted_1$6, [
    createBaseVNode(
      "legend",
      null,
      toDisplayString($setup.t("user_ldap", "Groups meeting these criteria are available in {instanceName}:", { instanceName: $setup.instanceName })),
      1
      /* TEXT */
    ),
    createBaseVNode("div", _hoisted_2$6, [
      createVNode($setup["NcSelect"], {
        modelValue: $setup.ldapGroupFilterObjectclass,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.ldapGroupFilterObjectclass = $event),
        class: "ldap-wizard__groups__group-filter-groups__select",
        options: $setup.groupObjectClasses,
        disabled: $setup.ldapConfigProxy.ldapGroupFilterMode === "1",
        inputLabel: $setup.t("user_ldap", "Only these object classes:"),
        multiple: true
      }, null, 8, ["modelValue", "options", "disabled", "inputLabel"]),
      createVNode($setup["NcSelect"], {
        modelValue: $setup.ldapGroupFilterGroups,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.ldapGroupFilterGroups = $event),
        class: "ldap-wizard__groups__group-filter-groups__select",
        options: $setup.groupGroups,
        disabled: $setup.ldapConfigProxy.ldapGroupFilterMode === "1",
        inputLabel: $setup.t("user_ldap", "Only from these groups:"),
        multiple: true
      }, null, 8, ["modelValue", "options", "disabled", "inputLabel"])
    ]),
    createBaseVNode("div", _hoisted_3$5, [
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapConfigProxy.ldapGroupFilterMode === "1",
        "onUpdate:modelValue": $setup.toggleFilterMode
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Edit LDAP Query")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"]),
      $setup.ldapConfigProxy.ldapGroupFilterMode === "1" ? (openBlock(), createElementBlock("div", _hoisted_4$5, [
        createVNode($setup["NcTextArea"], {
          modelValue: $setup.ldapConfigProxy.ldapGroupFilter,
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $setup.ldapConfigProxy.ldapGroupFilter = $event),
          placeholder: $setup.t("user_ldap", "Edit LDAP Query"),
          helperText: $setup.t("user_ldap", "The filter specifies which LDAP groups shall have access to the {instanceName} instance.", { instanceName: $setup.instanceName })
        }, null, 8, ["modelValue", "placeholder", "helperText"])
      ])) : (openBlock(), createElementBlock("div", _hoisted_5$4, [
        createBaseVNode(
          "span",
          null,
          toDisplayString($setup.t("user_ldap", "LDAP Filter:")),
          1
          /* TEXT */
        ),
        createBaseVNode(
          "code",
          null,
          toDisplayString($setup.ldapConfigProxy.ldapGroupFilter),
          1
          /* TEXT */
        )
      ]))
    ]),
    createBaseVNode("div", _hoisted_6$3, [
      createVNode($setup["NcButton"], {
        disabled: $setup.loadingGroupCount,
        onClick: $setup.countGroups
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Verify settings and count the groups")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"]),
      $setup.loadingGroupCount ? (openBlock(), createBlock($setup["NcLoadingIcon"], {
        key: 0,
        size: 20
      })) : createCommentVNode("v-if", true),
      $setup.groupsCountLabel !== void 0 && !$setup.loadingGroupCount ? (openBlock(), createElementBlock(
        "span",
        _hoisted_7$3,
        toDisplayString($setup.groupsCountLabel),
        1
        /* TEXT */
      )) : createCommentVNode("v-if", true)
    ])
  ]);
}
const GroupsTab = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__scopeId", "data-v-93645fee"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_ldap/src/components/SettingsTabs/GroupsTab.vue"]]);
const _sfc_main$5 = /* @__PURE__ */ defineComponent({
  __name: "LoginTab",
  props: {
    configId: { type: String, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const ldapConfigsStore = useLDAPConfigsStore();
    const { ldapConfigs } = storeToRefs(ldapConfigsStore);
    const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId, {
      ldapLoginFilterAttributes: getUserLoginFilter,
      ldapLoginFilterUsername: getUserLoginFilter,
      ldapLoginFilterEmail: getUserLoginFilter
    }));
    const instanceName = getCapabilities().theming.name;
    const testUsername = ref("");
    const loginFilterOptions = ref([]);
    const ldapLoginFilterAttributes = computed({
      get() {
        return ldapConfigProxy.value.ldapLoginFilterAttributes.split(";").filter((item) => item !== "");
      },
      set(value) {
        ldapConfigProxy.value.ldapLoginFilterAttributes = value.join(";");
      }
    });
    const ldapLoginFilterMode = computed(() => ldapConfigProxy.value.ldapLoginFilterMode === "1");
    const filteredLoginFilterOptions = computed(() => loginFilterOptions.value.filter((option) => !ldapLoginFilterAttributes.value.includes(option)));
    onBeforeMount(init);
    async function init() {
      const response = await callWizard("determineAttributes", props.configId);
      loginFilterOptions.value = response.options?.ldap_loginfilter_attributes ?? [];
    }
    async function getUserLoginFilter() {
      if (ldapConfigProxy.value.ldapLoginFilterMode === "0") {
        const response = await callWizard("getUserLoginFilter", props.configId);
        ldapConfigs.value[props.configId].ldapLoginFilter = response.changes?.ldap_login_filter ?? "";
      }
    }
    async function verifyLoginName() {
      try {
        const response = await callWizard("testLoginName", props.configId, { loginName: testUsername.value });
        const testLoginName = response.changes.ldap_test_loginname;
        const testEffectiveFilter = response.changes.ldap_test_effective_filter;
        if (testLoginName < 1) {
          showError(translate("user_ldap", "User not found. Please check your login attributes and username. Effective filter (to copy-and-paste for command-line validation): {filter}", { filter: testEffectiveFilter }));
        } else if (testLoginName === 1) {
          showSuccess(translate("user_ldap", "User found and settings verified."));
        } else if (testLoginName > 1) {
          showWarning(translate("user_ldap", "Consider narrowing your search, as it encompassed many users, only the first one of whom will be able to log in."));
        }
      } catch (error) {
        const message = error ?? translate("user_ldap", "An unspecified error occurred. Please check log and settings.");
        switch (message) {
          case "Bad search filter":
            showError(translate("user_ldap", "The search filter is invalid, probably due to syntax issues like uneven number of opened and closed brackets. Please revise."));
            break;
          case "connection error":
            showError(translate("user_ldap", "A connection error to LDAP/AD occurred. Please check host, port and credentials."));
            break;
          case "missing placeholder":
            showError(translate("user_ldap", 'The "%uid" placeholder is missing. It will be replaced with the login name when querying LDAP/AD.'));
            break;
        }
      }
    }
    async function toggleFilterMode(value) {
      if (value) {
        ldapConfigProxy.value.ldapLoginFilterMode = "1";
      } else {
        ldapConfigProxy.value.ldapLoginFilterMode = await showEnableAutomaticFilterInfo() ? "0" : "1";
      }
    }
    const __returned__ = { props, ldapConfigsStore, ldapConfigs, ldapConfigProxy, instanceName, testUsername, loginFilterOptions, ldapLoginFilterAttributes, ldapLoginFilterMode, filteredLoginFilterOptions, init, getUserLoginFilter, verifyLoginName, toggleFilterMode, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcSelect() {
      return NcSelect;
    }, get NcTextArea() {
      return NcTextArea;
    }, get NcTextField() {
      return _sfc_main$9;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$5 = { class: "ldap-wizard__login" };
const _hoisted_2$5 = { class: "ldap-wizard__login__line ldap-wizard__login__login-attributes" };
const _hoisted_3$4 = { class: "ldap-wizard__login__line ldap-wizard__login__user-login-filter" };
const _hoisted_4$4 = { key: 1 };
const _hoisted_5$3 = { class: "ldap-wizard__login__line" };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("fieldset", _hoisted_1$5, [
    createBaseVNode(
      "legend",
      null,
      toDisplayString($setup.t("user_ldap", "When logging in, {instanceName} will find the user based on the following attributes:", { instanceName: $setup.instanceName })),
      1
      /* TEXT */
    ),
    createVNode($setup["NcCheckboxRadioSwitch"], {
      modelValue: $setup.ldapConfigProxy.ldapLoginFilterUsername === "1",
      description: $setup.t("user_ldap", "Allows login against the LDAP/AD username, which is either 'uid' or 'sAMAccountName' and will be detected."),
      "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.ldapConfigProxy.ldapLoginFilterUsername = $event ? "1" : "0")
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("user_ldap", "LDAP/AD Username:")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["modelValue", "description"]),
    createVNode($setup["NcCheckboxRadioSwitch"], {
      modelValue: $setup.ldapConfigProxy.ldapLoginFilterEmail === "1",
      description: $setup.t("user_ldap", "Allows login against an email attribute. 'mail' and 'mailPrimaryAddress' allowed."),
      "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.ldapConfigProxy.ldapLoginFilterEmail = $event ? "1" : "0")
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("user_ldap", "LDAP/AD Email Address:")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["modelValue", "description"]),
    createBaseVNode("div", _hoisted_2$5, [
      createVNode($setup["NcSelect"], {
        modelValue: $setup.ldapLoginFilterAttributes,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $setup.ldapLoginFilterAttributes = $event),
        keepOpen: "",
        disabled: $setup.ldapLoginFilterMode,
        options: $setup.filteredLoginFilterOptions,
        inputLabel: $setup.t("user_ldap", "Other Attributes:"),
        multiple: true
      }, null, 8, ["modelValue", "disabled", "options", "inputLabel"])
    ]),
    createBaseVNode("div", _hoisted_3$4, [
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapLoginFilterMode,
        "onUpdate:modelValue": $setup.toggleFilterMode
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Edit LDAP Query")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"]),
      $setup.ldapLoginFilterMode ? (openBlock(), createBlock($setup["NcTextArea"], {
        key: 0,
        modelValue: $setup.ldapConfigProxy.ldapLoginFilter,
        placeholder: $setup.t("user_ldap", "Edit LDAP Query"),
        helperText: $setup.t("user_ldap", "Defines the filter to apply, when login is attempted. `%%uid` replaces the username in the login action. Example: `uid=%%uid`"),
        onChange: _cache[3] || (_cache[3] = (event) => $setup.ldapConfigProxy.ldapLoginFilter = event.target.value)
      }, null, 8, ["modelValue", "placeholder", "helperText"])) : (openBlock(), createElementBlock("div", _hoisted_4$4, [
        createBaseVNode(
          "span",
          null,
          toDisplayString($setup.t("user_ldap", "LDAP Filter:")),
          1
          /* TEXT */
        ),
        createBaseVNode(
          "code",
          null,
          toDisplayString($setup.ldapConfigProxy.ldapLoginFilter),
          1
          /* TEXT */
        )
      ]))
    ]),
    createBaseVNode("div", _hoisted_5$3, [
      createVNode($setup["NcTextField"], {
        modelValue: $setup.testUsername,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $setup.testUsername = $event),
        helperText: $setup.t("user_ldap", "Attempts to receive a DN for the given login name and the current login filter"),
        label: $setup.t("user_ldap", "Test Login name"),
        autocomplete: "off"
      }, null, 8, ["modelValue", "helperText", "label"]),
      createVNode($setup["NcButton"], {
        disabled: $setup.testUsername.length === 0,
        onClick: $setup.verifyLoginName
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Verify settings")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"])
    ])
  ]);
}
const LoginTab = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__scopeId", "data-v-82cefc64"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_ldap/src/components/SettingsTabs/LoginTab.vue"]]);
const _sfc_main$4 = {
  name: "DeleteIcon",
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
const _hoisted_3$3 = { d: "M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z" };
const _hoisted_4$3 = { key: 0 };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon delete-icon",
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
    ], 8, _hoisted_2$4))
  ], 16, _hoisted_1$4);
}
const Delete = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Delete.vue"]]);
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "ServerTab",
  props: {
    configId: { type: String, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const ldapConfigsStore = useLDAPConfigsStore();
    const { ldapConfigs } = storeToRefs(ldapConfigsStore);
    const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId));
    const loadingGuessPortAndTLS = ref(false);
    const loadingCountInBaseDN = ref(false);
    const loadingGuessBaseDN = ref(false);
    const localLdapAgentName = ref(ldapConfigProxy.value.ldapAgentName);
    const localLdapAgentPassword = ref(ldapConfigProxy.value.ldapAgentPassword);
    const needsToSaveCredentials = computed(() => {
      return ldapConfigProxy.value.ldapAgentName !== localLdapAgentName.value || ldapConfigProxy.value.ldapAgentPassword !== localLdapAgentPassword.value;
    });
    watch(
      ldapConfigProxy,
      (newVal) => {
        localLdapAgentName.value = newVal.ldapAgentName;
        if (newVal.ldapAgentPassword === "***") {
          localLdapAgentPassword.value = "";
        } else {
          localLdapAgentPassword.value = newVal.ldapAgentPassword;
        }
      }
    );
    function updateCredentials() {
      ldapConfigProxy.value.ldapAgentName = localLdapAgentName.value;
      ldapConfigProxy.value.ldapAgentPassword = localLdapAgentPassword.value;
    }
    async function guessPortAndTLS() {
      try {
        loadingGuessPortAndTLS.value = true;
        const { changes } = await callWizard("guessPortAndTLS", props.configId);
        ldapConfigs.value[props.configId].ldapPort = changes.ldap_port ?? "";
      } finally {
        loadingGuessPortAndTLS.value = false;
      }
    }
    async function guessBaseDN() {
      try {
        loadingGuessBaseDN.value = true;
        const { changes } = await callWizard("guessBaseDN", props.configId);
        ldapConfigProxy.value.ldapBase = changes.ldap_base ?? "";
      } finally {
        loadingGuessBaseDN.value = false;
      }
    }
    async function countInBaseDN() {
      try {
        loadingCountInBaseDN.value = true;
        const { changes } = await callWizard("countInBaseDN", props.configId);
        const ldapTestBase = changes.ldap_test_base;
        if (ldapTestBase < 1) {
          showInfo(translate("user_ldap", "No object found in the given Base DN. Please revise."));
        } else if (ldapTestBase > 1e3) {
          showInfo(translate("user_ldap", "More than 1,000 directory entries available."));
        } else {
          showInfo(translatePlural(
            "user_ldap",
            "{ldapTestBase} entry available within the provided Base DN",
            "{ldapTestBase} entries available within the provided Base DN",
            ldapTestBase,
            { ldapTestBase }
          ));
        }
      } finally {
        loadingCountInBaseDN.value = false;
      }
    }
    const __returned__ = { props, ldapConfigsStore, ldapConfigs, ldapConfigProxy, loadingGuessPortAndTLS, loadingCountInBaseDN, loadingGuessBaseDN, localLdapAgentName, localLdapAgentPassword, needsToSaveCredentials, updateCredentials, guessPortAndTLS, guessBaseDN, countInBaseDN, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcTextArea() {
      return NcTextArea;
    }, get NcTextField() {
      return _sfc_main$9;
    }, ContentCopy: Information, Delete };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$3 = { class: "ldap-wizard__server" };
const _hoisted_2$3 = { class: "ldap-wizard__server__line" };
const _hoisted_3$2 = { class: "ldap-wizard__server__line" };
const _hoisted_4$2 = { class: "ldap-wizard__server__host__port" };
const _hoisted_5$2 = { class: "ldap-wizard__server__line" };
const _hoisted_6$2 = { class: "ldap-wizard__server__line" };
const _hoisted_7$2 = { class: "ldap-wizard__server__line" };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("fieldset", _hoisted_1$3, [
    createBaseVNode("div", _hoisted_2$3, [
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapConfigProxy.ldapConfigurationActive === "1",
        type: "switch",
        "aria-label": $setup.t("user_ldap", "When unchecked, this configuration will be skipped."),
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.ldapConfigProxy.ldapConfigurationActive = $event ? "1" : "0")
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Configuration active")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue", "aria-label"]),
      createVNode($setup["NcButton"], {
        title: $setup.t("user_ldap", "Copy current configuration into new directory binding"),
        onClick: _cache[1] || (_cache[1] = ($event) => $setup.ldapConfigsStore.copyConfig($props.configId))
      }, {
        icon: withCtx(() => [
          createVNode($setup["ContentCopy"], { size: 20 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("user_ldap", "Copy configuration")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["title"]),
      createVNode($setup["NcButton"], {
        variant: "error",
        onClick: _cache[2] || (_cache[2] = ($event) => $setup.ldapConfigsStore.removeConfig($props.configId))
      }, {
        icon: withCtx(() => [
          createVNode($setup["Delete"], { size: 20 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("user_ldap", "Delete configuration")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })
    ]),
    createBaseVNode("div", _hoisted_3$2, [
      createVNode($setup["NcTextField"], {
        modelValue: $setup.ldapConfigProxy.ldapHost,
        helperText: $setup.t("user_ldap", "You can omit the protocol, unless you require SSL. If so, start with ldaps://"),
        label: $setup.t("user_ldap", "Host"),
        placeholder: "ldaps://localhost",
        autocomplete: "off",
        onChange: _cache[3] || (_cache[3] = (event) => $setup.ldapConfigProxy.ldapHost = event.target.value)
      }, null, 8, ["modelValue", "helperText", "label"]),
      createBaseVNode("div", _hoisted_4$2, [
        createVNode($setup["NcTextField"], {
          modelValue: $setup.ldapConfigProxy.ldapPort,
          label: $setup.t("user_ldap", "Port"),
          placeholder: "389",
          type: "number",
          autocomplete: "off",
          onChange: _cache[4] || (_cache[4] = (event) => $setup.ldapConfigProxy.ldapPort = event.target.value)
        }, null, 8, ["modelValue", "label"]),
        createVNode($setup["NcButton"], {
          disabled: $setup.loadingGuessPortAndTLS,
          onClick: $setup.guessPortAndTLS
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("user_ldap", "Detect Port")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["disabled"])
      ])
    ]),
    createBaseVNode("div", _hoisted_5$2, [
      createVNode($setup["NcTextField"], {
        modelValue: $setup.localLdapAgentName,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $setup.localLdapAgentName = $event),
        helperText: $setup.t("user_ldap", "The DN of the client user with which the bind shall be done. For anonymous access, leave DN and Password empty."),
        label: $setup.t("user_ldap", "User DN"),
        placeholder: "uid=agent,dc=example,dc=com",
        autocomplete: "off"
      }, null, 8, ["modelValue", "helperText", "label"])
    ]),
    createBaseVNode("div", _hoisted_6$2, [
      createVNode($setup["NcTextField"], {
        modelValue: $setup.localLdapAgentPassword,
        "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $setup.localLdapAgentPassword = $event),
        type: "password",
        helperText: $setup.t("user_ldap", "For anonymous access, leave DN and Password empty."),
        label: $setup.t("user_ldap", "Password"),
        autocomplete: "off"
      }, null, 8, ["modelValue", "helperText", "label"]),
      createVNode($setup["NcButton"], {
        disabled: !$setup.needsToSaveCredentials,
        onClick: $setup.updateCredentials
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Save credentials")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"])
    ]),
    createBaseVNode("div", _hoisted_7$2, [
      createVNode($setup["NcTextArea"], {
        label: $setup.t("user_ldap", "Base DN"),
        modelValue: $setup.ldapConfigProxy.ldapBase,
        placeholder: $setup.t("user_ldap", "One Base DN per line"),
        helperText: $setup.t("user_ldap", "You can specify Base DN for users and groups in the Advanced tab"),
        onChange: _cache[7] || (_cache[7] = (event) => $setup.ldapConfigProxy.ldapBase = event.target.value)
      }, null, 8, ["label", "modelValue", "placeholder", "helperText"]),
      createVNode($setup["NcButton"], {
        disabled: $setup.loadingGuessBaseDN || $setup.needsToSaveCredentials,
        onClick: $setup.guessBaseDN
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Detect Base DN")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"]),
      createVNode($setup["NcButton"], {
        disabled: $setup.loadingCountInBaseDN || $setup.ldapConfigProxy.ldapBase === "",
        onClick: $setup.countInBaseDN
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Test Base DN")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"])
    ])
  ]);
}
const ServerTab = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-952fdbd6"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_ldap/src/components/SettingsTabs/ServerTab.vue"]]);
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "UsersTab",
  props: {
    configId: { type: String, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const ldapConfigsStore = useLDAPConfigsStore();
    const { ldapConfigs } = storeToRefs(ldapConfigsStore);
    const ldapConfigProxy = computed(() => ldapConfigsStore.getConfigProxy(props.configId, {
      ldapUserFilterObjectclass: reloadFilters,
      ldapUserFilterGroups: reloadFilters
    }));
    const usersCount = ref(void 0);
    const loadingUserCount = ref(false);
    const instanceName = getCapabilities().theming.name;
    const userObjectClasses = ref([]);
    const userGroups = ref([]);
    const ldapUserFilterObjectclass = computed({
      get() {
        return ldapConfigProxy.value.ldapUserFilterObjectclass?.split(";").filter((item) => item !== "") ?? [];
      },
      set(value) {
        ldapConfigProxy.value.ldapUserFilterObjectclass = value.join(";");
      }
    });
    const ldapUserFilterGroups = computed({
      get() {
        return ldapConfigProxy.value.ldapUserFilterGroups.split(";").filter((item) => item !== "");
      },
      set(value) {
        ldapConfigProxy.value.ldapUserFilterGroups = value.join(";");
      }
    });
    onBeforeMount(init);
    async function init() {
      const response1 = await callWizard("determineUserObjectClasses", props.configId);
      userObjectClasses.value = response1.options?.ldap_userfilter_objectclass ?? [];
      ldapConfigs.value[props.configId].ldapUserFilterObjectclass = response1.changes?.ldap_userfilter_objectclass?.join(";") ?? "";
      const response2 = await callWizard("determineGroupsForUsers", props.configId);
      userGroups.value = response2.options?.ldap_userfilter_groups ?? [];
      ldapConfigs.value[props.configId].ldapUserFilterGroups = response2.changes?.ldap_userfilter_groups?.join(";") ?? "";
    }
    async function reloadFilters() {
      if (ldapConfigProxy.value.ldapUserFilterMode === "0") {
        const response1 = await callWizard("getUserListFilter", props.configId);
        ldapConfigs.value[props.configId].ldapUserFilter = response1.changes?.ldap_userlist_filter ?? "";
        const response2 = await callWizard("getUserLoginFilter", props.configId);
        ldapConfigs.value[props.configId].ldapLoginFilter = response2.changes?.ldap_login_filter ?? "";
      }
    }
    async function countUsers() {
      try {
        loadingUserCount.value = true;
        const response = await callWizard("countUsers", props.configId);
        usersCount.value = response.changes.ldap_user_count;
      } finally {
        loadingUserCount.value = false;
      }
    }
    async function toggleFilterMode(value) {
      if (value) {
        ldapConfigProxy.value.ldapUserFilterMode = "1";
      } else {
        ldapConfigProxy.value.ldapUserFilterMode = await showEnableAutomaticFilterInfo() ? "0" : "1";
      }
    }
    const __returned__ = { props, ldapConfigsStore, ldapConfigs, ldapConfigProxy, usersCount, loadingUserCount, instanceName, userObjectClasses, userGroups, ldapUserFilterObjectclass, ldapUserFilterGroups, init, reloadFilters, countUsers, toggleFilterMode, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, get NcSelect() {
      return NcSelect;
    }, get NcTextArea() {
      return NcTextArea;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$2 = { class: "ldap-wizard__users" };
const _hoisted_2$2 = { class: "ldap-wizard__users__line ldap-wizard__users__user-filter-object-class" };
const _hoisted_3$1 = { class: "ldap-wizard__users__line ldap-wizard__users__user-filter-groups" };
const _hoisted_4$1 = { class: "ldap-wizard__users__line ldap-wizard__users__user-filter" };
const _hoisted_5$1 = { key: 0 };
const _hoisted_6$1 = { key: 1 };
const _hoisted_7$1 = { class: "ldap-wizard__users__line ldap-wizard__users__user-count-check" };
const _hoisted_8 = { key: 1 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("fieldset", _hoisted_1$2, [
    createTextVNode(
      toDisplayString($setup.t("user_ldap", "Listing and searching for users is constrained by these criteria:")) + " ",
      1
      /* TEXT */
    ),
    createBaseVNode("div", _hoisted_2$2, [
      createVNode($setup["NcSelect"], {
        modelValue: $setup.ldapUserFilterObjectclass,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.ldapUserFilterObjectclass = $event),
        disabled: $setup.ldapConfigProxy.ldapUserFilterMode === "1",
        class: "ldap-wizard__users__user-filter-object-class__select",
        options: $setup.userObjectClasses,
        inputLabel: $setup.t("user_ldap", "Only these object classes:"),
        multiple: true
      }, null, 8, ["modelValue", "disabled", "options", "inputLabel"]),
      createTextVNode(
        " " + toDisplayString($setup.t("user_ldap", "The most common object classes for users are organizationalPerson, person, user, and inetOrgPerson. If you are not sure which object class to select, please consult your directory admin.")),
        1
        /* TEXT */
      )
    ]),
    createBaseVNode("div", _hoisted_3$1, [
      createVNode($setup["NcSelect"], {
        modelValue: $setup.ldapUserFilterGroups,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.ldapUserFilterGroups = $event),
        class: "ldap-wizard__users__user-filter-groups__select",
        disabled: $setup.ldapConfigProxy.ldapUserFilterMode === "1",
        options: $setup.userGroups,
        inputLabel: $setup.t("user_ldap", "Only from these groups:"),
        multiple: true
      }, null, 8, ["modelValue", "disabled", "options", "inputLabel"])
    ]),
    createBaseVNode("div", _hoisted_4$1, [
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.ldapConfigProxy.ldapUserFilterMode === "1",
        "onUpdate:modelValue": $setup.toggleFilterMode
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Edit LDAP Query")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"]),
      $setup.ldapConfigProxy.ldapUserFilterMode === "1" ? (openBlock(), createElementBlock("div", _hoisted_5$1, [
        createVNode($setup["NcTextArea"], {
          modelValue: $setup.ldapConfigProxy.ldapUserFilter,
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $setup.ldapConfigProxy.ldapUserFilter = $event),
          placeholder: $setup.t("user_ldap", "Edit LDAP Query"),
          helperText: $setup.t("user_ldap", "The filter specifies which LDAP users shall have access to the {instanceName} instance.", { instanceName: $setup.instanceName })
        }, null, 8, ["modelValue", "placeholder", "helperText"])
      ])) : (openBlock(), createElementBlock("div", _hoisted_6$1, [
        createBaseVNode(
          "label",
          null,
          toDisplayString($setup.t("user_ldap", "LDAP Filter:")),
          1
          /* TEXT */
        ),
        createBaseVNode(
          "code",
          null,
          toDisplayString($setup.ldapConfigProxy.ldapUserFilter),
          1
          /* TEXT */
        )
      ]))
    ]),
    createBaseVNode("div", _hoisted_7$1, [
      createVNode($setup["NcButton"], {
        disabled: $setup.loadingUserCount,
        onClick: $setup.countUsers
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("user_ldap", "Verify settings and count users")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"]),
      $setup.loadingUserCount ? (openBlock(), createBlock($setup["NcLoadingIcon"], {
        key: 0,
        size: 16
      })) : createCommentVNode("v-if", true),
      $setup.usersCount !== void 0 && !$setup.loadingUserCount ? (openBlock(), createElementBlock(
        "span",
        _hoisted_8,
        toDisplayString($setup.t("user_ldap", "User count: {usersCount}", { usersCount: $setup.usersCount }, { escape: false })),
        1
        /* TEXT */
      )) : createCommentVNode("v-if", true)
    ])
  ]);
}
const UsersTab = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-65337498"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_ldap/src/components/SettingsTabs/UsersTab.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "WizardControls",
  props: {
    configId: { type: String, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const ldapConfigsStore = useLDAPConfigsStore();
    const { updatingConfig } = storeToRefs(ldapConfigsStore);
    const loading = ref(false);
    const result = ref(null);
    const isValide = computed(() => result.value?.success);
    watch(updatingConfig, () => {
      result.value = null;
    });
    async function testSelectedConfig() {
      try {
        loading.value = true;
        result.value = await testConfiguration(props.configId);
      } finally {
        loading.value = false;
      }
    }
    const __returned__ = { props, ldapConfigsStore, updatingConfig, loading, result, isValide, testSelectedConfig, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, Information };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$1 = { class: "ldap-wizard__controls" };
const _hoisted_2$1 = { class: "ldap-wizard__controls__state_message" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createVNode($setup["NcButton"], {
      variant: "primary",
      disabled: $setup.loading,
      onClick: $setup.testSelectedConfig
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("user_ldap", "Test Configuration")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["disabled"]),
    createVNode($setup["NcButton"], {
      variant: "tertiary",
      href: "https://docs.nextcloud.com/server/stable/go.php?to=admin-ldap",
      target: "_blank",
      rel: "noreferrer noopener"
    }, {
      icon: withCtx(() => [
        createVNode($setup["Information"], { size: 20 })
      ]),
      default: withCtx(() => [
        createBaseVNode(
          "span",
          null,
          toDisplayString($setup.t("user_ldap", "Help")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }),
    $setup.result !== null && !$setup.loading ? (openBlock(), createElementBlock(
      Fragment,
      { key: 0 },
      [
        createBaseVNode(
          "span",
          {
            class: normalizeClass(["ldap-wizard__controls__state_indicator", { "ldap-wizard__controls__state_indicator--valid": $setup.isValide }])
          },
          null,
          2
          /* CLASS */
        ),
        createBaseVNode(
          "span",
          _hoisted_2$1,
          toDisplayString($setup.result.message),
          1
          /* TEXT */
        )
      ],
      64
      /* STABLE_FRAGMENT */
    )) : createCommentVNode("v-if", true),
    $setup.loading ? (openBlock(), createBlock($setup["NcLoadingIcon"], {
      key: 1,
      size: 16
    })) : createCommentVNode("v-if", true)
  ]);
}
const WizardControls = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-c4fe8901"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_ldap/src/components/WizardControls.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "LDAPSettingsApp",
  setup(__props, { expose: __expose }) {
    __expose();
    const ldapModuleInstalled = loadState("user_ldap", "ldapModuleInstalled");
    const tabs = {
      server: translate("user_ldap", "Server"),
      users: translate("user_ldap", "Users"),
      login: translate("user_ldap", "Login Attributes"),
      groups: translate("user_ldap", "Groups"),
      advanced: translate("user_ldap", "Advanced"),
      expert: translate("user_ldap", "Expert")
    };
    const ldapConfigsStore = useLDAPConfigsStore();
    const { ldapConfigs, selectedConfigId, selectedConfig } = storeToRefs(ldapConfigsStore);
    const selectedTab = ref("server");
    const clearMappingLoading = ref(false);
    const selectedConfigHasServerInfo = computed(() => {
      return selectedConfig.value !== void 0 && selectedConfig.value.ldapHost !== "" && selectedConfig.value.ldapPort !== "" && selectedConfig.value.ldapBase !== "";
    });
    async function requestClearMapping(subject) {
      try {
        clearMappingLoading.value = true;
        await clearMapping(subject);
      } finally {
        clearMappingLoading.value = false;
      }
    }
    const __returned__ = { ldapModuleInstalled, tabs, ldapConfigsStore, ldapConfigs, selectedConfigId, selectedConfig, selectedTab, clearMappingLoading, selectedConfigHasServerInfo, requestClearMapping, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcSelect() {
      return NcSelect;
    }, Plus: PlusIcon, AdvancedTab, ExpertTab, GroupsTab, LoginTab, ServerTab, UsersTab, WizardControls };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1 = { class: "ldap-wizard" };
const _hoisted_2 = { class: "ldap-wizard__config-selection" };
const _hoisted_3 = {
  key: 0,
  class: "ldap-wizard__tab-container"
};
const _hoisted_4 = { class: "ldap-wizard__tab-selection-container" };
const _hoisted_5 = { class: "ldap-wizard__tab-selection" };
const _hoisted_6 = { class: "ldap-wizard__clear-mapping" };
const _hoisted_7 = { class: "ldap-wizard__clear-mapping__buttons" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("form", _hoisted_1, [
    createBaseVNode(
      "h2",
      null,
      toDisplayString($setup.t("user_ldap", "LDAP/AD integration")),
      1
      /* TEXT */
    ),
    !$setup.ldapModuleInstalled ? (openBlock(), createBlock($setup["NcNoteCard"], {
      key: 0,
      type: "warning",
      text: $setup.t("user_ldap", "The PHP LDAP module is not installed, the backend will not work. Please ask your system administrator to install it.")
    }, null, 8, ["text"])) : createCommentVNode("v-if", true),
    $setup.ldapModuleInstalled ? (openBlock(), createElementBlock(
      Fragment,
      { key: 1 },
      [
        createBaseVNode("div", _hoisted_2, [
          $setup.selectedConfigId !== void 0 ? (openBlock(), createBlock($setup["NcSelect"], {
            key: 0,
            modelValue: $setup.selectedConfigId,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.selectedConfigId = $event),
            options: Object.keys($setup.ldapConfigs),
            inputLabel: $setup.t("user_ldap", "Select LDAP Config")
          }, {
            option: withCtx(({ label: configId }) => [
              createTextVNode(
                toDisplayString(`${configId}: ${$setup.ldapConfigs[configId]?.ldapHost ?? ""}`),
                1
                /* TEXT */
              )
            ]),
            "selected-option": withCtx(({ label: configId }) => [
              createTextVNode(
                toDisplayString(`${configId}: ${$setup.ldapConfigs[configId]?.ldapHost ?? ""}`),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["modelValue", "options", "inputLabel"])) : createCommentVNode("v-if", true),
          createVNode($setup["NcButton"], {
            label: $setup.t("user_ldap", "Create New Config"),
            class: "ldap-wizard__config-selection__create-button",
            onClick: $setup.ldapConfigsStore.create
          }, {
            icon: withCtx(() => [
              createVNode($setup["Plus"], { size: 20 })
            ]),
            default: withCtx(() => [
              createTextVNode(
                " " + toDisplayString($setup.t("user_ldap", "Create configuration")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["label", "onClick"])
        ]),
        $setup.selectedConfigId !== void 0 ? (openBlock(), createElementBlock("div", _hoisted_3, [
          createBaseVNode("div", _hoisted_4, [
            createBaseVNode("div", _hoisted_5, [
              (openBlock(), createElementBlock(
                Fragment,
                null,
                renderList($setup.tabs, (tabLabel, tabId) => {
                  return createVNode($setup["NcCheckboxRadioSwitch"], {
                    key: tabId,
                    modelValue: $setup.selectedTab,
                    "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.selectedTab = $event),
                    buttonVariant: true,
                    value: tabId,
                    type: "radio",
                    disabled: tabId !== "server" && !$setup.selectedConfigHasServerInfo,
                    buttonVariantGrouped: "horizontal"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(
                        toDisplayString(tabLabel),
                        1
                        /* TEXT */
                      )
                    ]),
                    _: 2
                    /* DYNAMIC */
                  }, 1032, ["modelValue", "value", "disabled"]);
                }),
                64
                /* STABLE_FRAGMENT */
              ))
            ])
          ]),
          $setup.selectedTab === "server" ? (openBlock(), createBlock($setup["ServerTab"], {
            key: 0,
            configId: $setup.selectedConfigId
          }, null, 8, ["configId"])) : $setup.selectedTab === "users" ? (openBlock(), createBlock($setup["UsersTab"], {
            key: 1,
            configId: $setup.selectedConfigId
          }, null, 8, ["configId"])) : $setup.selectedTab === "login" ? (openBlock(), createBlock($setup["LoginTab"], {
            key: 2,
            configId: $setup.selectedConfigId
          }, null, 8, ["configId"])) : $setup.selectedTab === "groups" ? (openBlock(), createBlock($setup["GroupsTab"], {
            key: 3,
            configId: $setup.selectedConfigId
          }, null, 8, ["configId"])) : $setup.selectedTab === "expert" ? (openBlock(), createBlock($setup["ExpertTab"], {
            key: 4,
            configId: $setup.selectedConfigId
          }, null, 8, ["configId"])) : $setup.selectedTab === "advanced" ? (openBlock(), createBlock($setup["AdvancedTab"], {
            key: 5,
            configId: $setup.selectedConfigId
          }, null, 8, ["configId"])) : createCommentVNode("v-if", true),
          createVNode($setup["WizardControls"], {
            class: "ldap-wizard__controls",
            configId: $setup.selectedConfigId
          }, null, 8, ["configId"])
        ])) : createCommentVNode("v-if", true),
        createBaseVNode("div", _hoisted_6, [
          createBaseVNode(
            "strong",
            null,
            toDisplayString($setup.t("user_ldap", "Username-LDAP User Mapping")),
            1
            /* TEXT */
          ),
          createTextVNode(
            " " + toDisplayString($setup.t("user_ldap", "Usernames are used to store and assign metadata. In order to precisely identify and recognize users, each LDAP user will have an internal username. This requires a mapping from username to LDAP user. The created username is mapped to the UUID of the LDAP user. Additionally the DN is cached as well to reduce LDAP interaction, but it is not used for identification. If the DN changes, the changes will be found. The internal username is used all over. Clearing the mappings will have leftovers everywhere. Clearing the mappings is not configuration sensitive, it affects all LDAP configurations! Never clear the mappings in a production environment, only in a testing or experimental stage.")) + " ",
            1
            /* TEXT */
          ),
          createBaseVNode("div", _hoisted_7, [
            createVNode($setup["NcButton"], {
              variant: "error",
              disabled: $setup.clearMappingLoading,
              onClick: _cache[2] || (_cache[2] = ($event) => $setup.requestClearMapping("user"))
            }, {
              default: withCtx(() => [
                createTextVNode(
                  toDisplayString($setup.t("user_ldap", "Clear Username-LDAP User Mapping")),
                  1
                  /* TEXT */
                )
              ]),
              _: 1
              /* STABLE */
            }, 8, ["disabled"]),
            createVNode($setup["NcButton"], {
              variant: "error",
              disabled: $setup.clearMappingLoading,
              onClick: _cache[3] || (_cache[3] = ($event) => $setup.requestClearMapping("group"))
            }, {
              default: withCtx(() => [
                createTextVNode(
                  toDisplayString($setup.t("user_ldap", "Clear Groupname-LDAP Group Mapping")),
                  1
                  /* TEXT */
                )
              ]),
              _: 1
              /* STABLE */
            }, 8, ["disabled"])
          ])
        ])
      ],
      64
      /* STABLE_FRAGMENT */
    )) : createCommentVNode("v-if", true)
  ]);
}
const LDAPSettingsApp = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-607c4115"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_ldap/src/views/LDAPSettingsApp.vue"]]);
const pinia = createPinia();
const app = createApp(LDAPSettingsApp);
app.use(pinia);
app.mount("#content-ldap-settings");
//# sourceMappingURL=user_ldap-settings-admin.mjs.map
