const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { l as loadState, _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import { b as defineComponent, O as reactive, o as openBlock, c as createBlock, w as withCtx, x as createVNode, j as createTextVNode, t as toDisplayString, h as createCommentVNode, g as createBaseVNode, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { a as showError, d as showConfirmation } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as confirmPassword } from "./index-Dl6U1WCt.chunk.mjs";
import { c as generateOcsUrl } from "./index-rAufP352.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { l as logger } from "./logger-CImeZ7_x.chunk.mjs";
import "./modulepreload-polyfill-mMY-eDcw.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "AdminSettings",
  setup(__props, { expose: __expose }) {
    __expose();
    const sharingFederatedDocUrl = loadState("federatedfilesharing", "sharingFederatedDocUrl");
    const internalState = new Proxy({
      outgoingServer2serverShareEnabled: [
        loadState("federatedfilesharing", "outgoingServer2serverShareEnabled"),
        "outgoing_server2server_share_enabled"
      ],
      incomingServer2serverShareEnabled: [
        loadState("federatedfilesharing", "incomingServer2serverShareEnabled"),
        "incoming_server2server_share_enabled"
      ],
      outgoingServer2serverGroupShareEnabled: [
        loadState("federatedfilesharing", "outgoingServer2serverGroupShareEnabled"),
        "outgoing_server2server_group_share_enabled"
      ],
      incomingServer2serverGroupShareEnabled: [
        loadState("federatedfilesharing", "incomingServer2serverGroupShareEnabled"),
        "incoming_server2server_group_share_enabled"
      ],
      federatedGroupSharingSupported: [
        loadState("federatedfilesharing", "federatedGroupSharingSupported"),
        "federated_group_sharing_supported"
      ],
      federatedTrustedShareAutoAccept: [
        loadState("federatedfilesharing", "federatedTrustedShareAutoAccept"),
        "federatedTrustedShareAutoAccept"
      ],
      lookupServerEnabled: [
        loadState("federatedfilesharing", "lookupServerEnabled"),
        "lookupServerEnabled"
      ],
      lookupServerUploadEnabled: [
        loadState("federatedfilesharing", "lookupServerUploadEnabled"),
        "lookupServerUploadEnabled"
      ]
    }, {
      get(target, prop) {
        return target[prop]?.[0];
      },
      set(target, prop, value) {
        if (prop in target) {
          target[prop][0] = value;
          updateAppConfig(target[prop][1], value);
          return true;
        }
        return false;
      }
    });
    const state = reactive(internalState);
    async function showLookupServerUploadConfirmation(value) {
      if (value === false) {
        return state.lookupServerUploadEnabled = false;
      }
      await showConfirmation({
        name: translate("federatedfilesharing", "Confirm data upload to lookup server"),
        text: translate("federatedfilesharing", 'When enabled, all account properties (e.g. email address) with scope visibility set to "published", will be automatically synced and transmitted to an external system and made available in a public, global address book.'),
        labelConfirm: translate("federatedfilesharing", "Enable data upload"),
        labelReject: translate("federatedfilesharing", "Disable upload"),
        severity: "warning"
      }).then(() => {
        state.lookupServerUploadEnabled = true;
      }).catch(() => {
        state.lookupServerUploadEnabled = false;
      });
    }
    async function showLookupServerConfirmation(value) {
      if (value === false) {
        return state.lookupServerEnabled = false;
      }
      await showConfirmation({
        name: translate("federatedfilesharing", "Confirm querying lookup server"),
        text: translate("federatedfilesharing", "When enabled, the search input when creating shares will be sent to an external system that provides a public and global address book.") + translate("federatedfilesharing", "This is used to retrieve the federated cloud ID to make federated sharing easier.") + translate("federatedfilesharing", "Moreover, email addresses of users might be sent to that system in order to verify them."),
        labelConfirm: translate("federatedfilesharing", "Enable querying"),
        labelReject: translate("federatedfilesharing", "Disable querying"),
        severity: "warning"
      }).then(() => {
        state.lookupServerEnabled = true;
      }).catch(() => {
        state.lookupServerEnabled = false;
      });
    }
    async function updateAppConfig(key, value) {
      await confirmPassword();
      const url = generateOcsUrl("/apps/provisioning_api/api/v1/config/apps/{appId}/{key}", {
        appId: "files_sharing",
        key
      });
      const stringValue = value ? "yes" : "no";
      try {
        const { data } = await cancelableClient.post(url, {
          value: stringValue
        });
        if (data.ocs.meta.status !== "ok") {
          if (data.ocs.meta.message) {
            showError(data.ocs.meta.message);
            logger.error("Error updating federated files sharing config", { error: data.ocs });
          } else {
            throw new Error(`Failed to update federatedfilesharing config, ${data.ocs.meta.statuscode}`);
          }
        }
      } catch (error) {
        logger.error("Error updating federated files sharing config", { error });
        showError(translate("federatedfilesharing", "Unable to update federated files sharing config"));
      }
    }
    const __returned__ = { sharingFederatedDocUrl, internalState, state, showLookupServerUploadConfirmation, showLookupServerConfirmation, updateAppConfig, get t() {
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
const _hoisted_1 = { class: "settings-subsection" };
const _hoisted_2 = { class: "settings-subsection__name" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("federatedfilesharing", "Federated Cloud Sharing"),
    description: $setup.t("federatedfilesharing", "Adjust how people can share between servers. This includes shares between people on this server as well if they are using federated sharing."),
    docUrl: $setup.sharingFederatedDocUrl
  }, {
    default: withCtx(() => [
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.state.outgoingServer2serverShareEnabled,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.state.outgoingServer2serverShareEnabled = $event),
        type: "switch"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("federatedfilesharing", "Allow people on this server to send shares to other servers (this option also allows WebDAV access to public shares)")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"]),
      createVNode($setup["NcCheckboxRadioSwitch"], {
        modelValue: $setup.state.incomingServer2serverShareEnabled,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.state.incomingServer2serverShareEnabled = $event),
        type: "switch"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("federatedfilesharing", "Allow people on this server to receive shares from other servers")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"]),
      $setup.state.federatedGroupSharingSupported ? (openBlock(), createBlock($setup["NcCheckboxRadioSwitch"], {
        key: 0,
        modelValue: $setup.state.outgoingServer2serverGroupShareEnabled,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $setup.state.outgoingServer2serverGroupShareEnabled = $event),
        type: "switch"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("federatedfilesharing", "Allow people on this server to send shares to groups on other servers")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"])) : createCommentVNode("v-if", true),
      $setup.state.federatedGroupSharingSupported ? (openBlock(), createBlock($setup["NcCheckboxRadioSwitch"], {
        key: 1,
        modelValue: $setup.state.incomingServer2serverGroupShareEnabled,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $setup.state.incomingServer2serverGroupShareEnabled = $event),
        type: "switch"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("federatedfilesharing", "Allow people on this server to receive group shares from other servers")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"])) : createCommentVNode("v-if", true),
      createBaseVNode("fieldset", null, [
        createBaseVNode(
          "legend",
          null,
          toDisplayString($setup.t("federatedfilesharing", "The lookup server is only available for global scale.")),
          1
          /* TEXT */
        ),
        createVNode($setup["NcCheckboxRadioSwitch"], {
          type: "switch",
          modelValue: $setup.state.lookupServerEnabled,
          disabled: "",
          "onUpdate:modelValue": $setup.showLookupServerConfirmation
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("federatedfilesharing", "Search global and public address book for people")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue"]),
        createVNode($setup["NcCheckboxRadioSwitch"], {
          type: "switch",
          modelValue: $setup.state.lookupServerUploadEnabled,
          disabled: "",
          "onUpdate:modelValue": $setup.showLookupServerUploadConfirmation
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("federatedfilesharing", "Allow people to publish their data to a global and public address book")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue"])
      ]),
      createCommentVNode(" Trusted server handling "),
      createBaseVNode("div", _hoisted_1, [
        createBaseVNode(
          "h3",
          _hoisted_2,
          toDisplayString($setup.t("federatedfilesharing", "Trusted federation")),
          1
          /* TEXT */
        ),
        createVNode($setup["NcCheckboxRadioSwitch"], {
          modelValue: $setup.state.federatedTrustedShareAutoAccept,
          "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $setup.state.federatedTrustedShareAutoAccept = $event),
          type: "switch"
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("federatedfilesharing", "Automatically accept shares from trusted federated accounts and groups by default")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue"])
      ])
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description", "docUrl"]);
}
const AdminSettings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-26065554"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/federatedfilesharing/src/components/AdminSettings.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const internalOnly = loadState("federatedfilesharing", "internalOnly", false);
if (!internalOnly) {
  const app = createApp(AdminSettings);
  app.mount("#vue-admin-federated");
}
//# sourceMappingURL=federatedfilesharing-settings-admin.mjs.map
