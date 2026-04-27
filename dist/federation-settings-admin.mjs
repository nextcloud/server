const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, l as useTemplateRef, y as ref, P as nextTick, o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, v as normalizeClass, x as createVNode, w as withCtx, M as withModifiers, n as computed, c as createBlock, h as createCommentVNode, T as TransitionGroup, F as Fragment, C as renderList, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { e as mdiPlus, f as mdiTrashCanOutline, a as mdiCloseNetworkOutline, b as mdiHelpNetworkOutline, d as mdiCheckNetworkOutline, N as NcNoteCard } from "./mdi-BGU2G5q5.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { c as showSuccess, a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcIconSvgWrapper } from "./Web-BOM4en5n.chunk.mjs";
import { _ as _sfc_main$3 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { c as cancelableClient, i as isAxiosError } from "./index-D5H5XMHa.chunk.mjs";
import { c as generateOcsUrl, g as getLoggerBuilder } from "./index-rAufP352.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const TrustedServerStatus = Object.freeze({
  /** after a user list was exchanged at least once successfully */
  STATUS_OK: 1,
  /** waiting for shared secret or initial user list exchange */
  STATUS_PENDING: 2,
  /** something went wrong, misconfigured server, software bug,... user interaction needed */
  STATUS_FAILURE: 3,
  /** remote server revoked access */
  STATUS_ACCESS_REVOKED: 4
});
class ApiError extends Error {
}
async function addServer(url) {
  try {
    const { data } = await cancelableClient.post(
      generateOcsUrl("apps/federation/trusted-servers"),
      { url }
    );
    const serverData = data.ocs.data;
    return {
      id: serverData.id,
      url: serverData.url,
      status: TrustedServerStatus.STATUS_PENDING
    };
  } catch (error) {
    throw mapError(error);
  }
}
async function deleteServer(id) {
  try {
    await cancelableClient.delete(generateOcsUrl(`apps/federation/trusted-servers/${id}`));
  } catch (error) {
    throw mapError(error);
  }
}
function mapError(error) {
  if (isAxiosError(error) && error.response?.data?.ocs) {
    return new ApiError(error.response.data.ocs.meta.message, { cause: error });
  }
  return error;
}
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const logger = getLoggerBuilder().setApp("federation").build();
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "AddTrustedServerForm",
  emits: ["add"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const emit = __emit;
    const formElement = useTemplateRef("form");
    const newServerUrl = ref("");
    async function onAdd() {
      try {
        const server = await addServer(newServerUrl.value);
        newServerUrl.value = "";
        emit("add", server);
        nextTick(() => formElement.value?.reset());
        showSuccess(translate("federation", "Added to the list of trusted servers"));
      } catch (error) {
        logger.error("Failed to add trusted server", { error });
        if (error instanceof ApiError) {
          showError(error.message);
        } else {
          showError(translate("federation", "Could not add trusted server. Please try again later."));
        }
      }
    }
    const __returned__ = { emit, formElement, newServerUrl, onAdd, get mdiPlus() {
      return mdiPlus;
    }, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcIconSvgWrapper() {
      return NcIconSvgWrapper;
    }, get NcTextField() {
      return _sfc_main$3;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const addTrustedServerForm__heading = "_addTrustedServerForm__heading_14ngv_2";
const addTrustedServerForm__wrapper = "_addTrustedServerForm__wrapper_14ngv_7";
const addTrustedServerForm__submitButton = "_addTrustedServerForm__submitButton_14ngv_14";
const style0$2 = {
  addTrustedServerForm__heading,
  addTrustedServerForm__wrapper,
  addTrustedServerForm__submitButton
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "form",
    {
      ref: "form",
      onSubmit: withModifiers($setup.onAdd, ["prevent"])
    },
    [
      createBaseVNode(
        "h3",
        {
          class: normalizeClass(_ctx.$style.addTrustedServerForm__heading)
        },
        toDisplayString($setup.t("federation", "Add trusted server")),
        3
        /* TEXT, CLASS */
      ),
      createBaseVNode(
        "div",
        {
          class: normalizeClass(_ctx.$style.addTrustedServerForm__wrapper)
        },
        [
          createVNode($setup["NcTextField"], {
            modelValue: $setup.newServerUrl,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.newServerUrl = $event),
            label: $setup.t("federation", "Server url"),
            placeholder: "https://…",
            required: "",
            type: "url"
          }, null, 8, ["modelValue", "label"]),
          createVNode($setup["NcButton"], {
            class: normalizeClass(_ctx.$style.addTrustedServerForm__submitButton),
            "aria-label": $setup.t("federation", "Add"),
            title: $setup.t("federation", "Add"),
            type: "submit",
            variant: "primary"
          }, {
            icon: withCtx(() => [
              createVNode($setup["NcIconSvgWrapper"], { path: $setup.mdiPlus }, null, 8, ["path"])
            ]),
            _: 1
            /* STABLE */
          }, 8, ["class", "aria-label", "title"])
        ],
        2
        /* CLASS */
      )
    ],
    544
    /* NEED_HYDRATION, NEED_PATCH */
  );
}
const cssModules$2 = {
  "$style": style0$2
};
const AddTrustedServerForm = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__cssModules", cssModules$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/federation/src/components/AddTrustedServerForm.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "TrustedServer",
  props: {
    server: { type: Object, required: true }
  },
  emits: ["delete"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const props = __props;
    const emit = __emit;
    const isLoading = ref(false);
    const hasError = computed(() => props.server.status === TrustedServerStatus.STATUS_FAILURE);
    const serverIcon = computed(() => {
      switch (props.server.status) {
        case TrustedServerStatus.STATUS_OK:
          return mdiCheckNetworkOutline;
        case TrustedServerStatus.STATUS_PENDING:
        case TrustedServerStatus.STATUS_ACCESS_REVOKED:
          return mdiHelpNetworkOutline;
        case TrustedServerStatus.STATUS_FAILURE:
        default:
          return mdiCloseNetworkOutline;
      }
    });
    const serverStatus = computed(() => {
      switch (props.server.status) {
        case TrustedServerStatus.STATUS_OK:
          return [translate("federation", "Server ok"), translate("federation", "User list was exchanged at least once successfully with the remote server.")];
        case TrustedServerStatus.STATUS_PENDING:
          return [translate("federation", "Server pending"), translate("federation", "Waiting for shared secret or initial user list exchange.")];
        case TrustedServerStatus.STATUS_ACCESS_REVOKED:
          return [translate("federation", "Server access revoked"), translate("federation", "Server access revoked")];
        case TrustedServerStatus.STATUS_FAILURE:
        default:
          return [translate("federation", "Server failure"), translate("federation", "Connection to the remote server failed or the remote server is misconfigured.")];
      }
    });
    async function onDelete() {
      try {
        isLoading.value = true;
        await deleteServer(props.server.id);
        emit("delete", props.server);
      } catch (error) {
        isLoading.value = false;
        logger.error("Failed to delete trusted server", { error });
        showError(translate("federation", "Failed to delete trusted server. Please try again later."));
      }
    }
    const __returned__ = { props, emit, isLoading, hasError, serverIcon, serverStatus, onDelete, get mdiTrashCanOutline() {
      return mdiTrashCanOutline;
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
const trustedServer = "_trustedServer_1wqey_2";
const trustedServer__icon_error = "_trustedServer__icon_error_1wqey_15";
const trustedServer__url = "_trustedServer__url_1wqey_19";
const style0$1 = {
  trustedServer,
  trustedServer__icon_error,
  trustedServer__url
};
const _hoisted_1 = ["textContent"];
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "li",
    {
      class: normalizeClass(_ctx.$style.trustedServer)
    },
    [
      createVNode($setup["NcIconSvgWrapper"], {
        class: normalizeClass({
          [_ctx.$style.trustedServer__icon_error]: $setup.hasError
        }),
        path: $setup.serverIcon,
        name: $setup.serverStatus[0],
        title: $setup.serverStatus[1]
      }, null, 8, ["class", "path", "name", "title"]),
      createBaseVNode("code", {
        class: normalizeClass(_ctx.$style.trustedServer__url),
        textContent: toDisplayString($props.server.url)
      }, null, 10, _hoisted_1),
      createVNode($setup["NcButton"], {
        "aria-label": $setup.t("federation", "Delete"),
        title: $setup.t("federation", "Delete"),
        disabled: $setup.isLoading,
        onClick: $setup.onDelete
      }, {
        icon: withCtx(() => [
          $setup.isLoading ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : (openBlock(), createBlock($setup["NcIconSvgWrapper"], {
            key: 1,
            path: $setup.mdiTrashCanOutline
          }, null, 8, ["path"]))
        ]),
        _: 1
        /* STABLE */
      }, 8, ["aria-label", "title", "disabled"])
    ],
    2
    /* CLASS */
  );
}
const cssModules$1 = {
  "$style": style0$1
};
const TrustedServer = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__cssModules", cssModules$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/federation/src/components/TrustedServer.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "AdminSettings",
  setup(__props, { expose: __expose }) {
    __expose();
    const adminSettings = loadState("federation", "adminSettings");
    const trustedServers = ref(adminSettings.trustedServers);
    const showPendingServerInfo = computed(() => trustedServers.value.some((server) => server.status === TrustedServerStatus.STATUS_PENDING));
    async function onAdd(server) {
      trustedServers.value.unshift(server);
    }
    function onDelete(server) {
      trustedServers.value = trustedServers.value.filter((s) => s.id !== server.id);
    }
    const __returned__ = { adminSettings, trustedServers, showPendingServerInfo, onAdd, onDelete, get t() {
      return translate;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, AddTrustedServerForm, TrustedServer };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const federationAdminSettings__trustedServersList = "_federationAdminSettings__trustedServersList_z3uvu_2";
const federationAdminSettings__trustedServersListItem = "_federationAdminSettings__trustedServersListItem_z3uvu_9";
const transition_active = "_transition_active_z3uvu_13";
const transition_hidden = "_transition_hidden_z3uvu_17";
const style0 = {
  federationAdminSettings__trustedServersList,
  federationAdminSettings__trustedServersListItem,
  transition_active,
  transition_hidden
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("federation", "Trusted servers"),
    docUrl: $setup.adminSettings.docUrl,
    description: $setup.t("federation", "Federation allows you to connect with other trusted servers to exchange the account directory. For example this will be used to auto-complete external accounts for federated sharing. It is not necessary to add a server as trusted server in order to create a federated share.")
  }, {
    default: withCtx(() => [
      $setup.showPendingServerInfo ? (openBlock(), createBlock($setup["NcNoteCard"], {
        key: 0,
        type: "info",
        text: $setup.t("federation", "Each server must validate the other. This process may require a few cron cycles.")
      }, null, 8, ["text"])) : createCommentVNode("v-if", true),
      createVNode(TransitionGroup, {
        class: normalizeClass(_ctx.$style.federationAdminSettings__trustedServersList),
        "aria-label": $setup.t("federation", "Trusted servers"),
        tag: "ul",
        enterFromClass: _ctx.$style.transition_hidden,
        enterActiveClass: _ctx.$style.transition_active,
        leaveActiveClass: _ctx.$style.transition_active,
        leaveToClass: _ctx.$style.transition_hidden
      }, {
        default: withCtx(() => [
          (openBlock(true), createElementBlock(
            Fragment,
            null,
            renderList($setup.trustedServers, (server) => {
              return openBlock(), createBlock($setup["TrustedServer"], {
                key: server.id,
                class: normalizeClass(_ctx.$style.federationAdminSettings__trustedServersListItem),
                server,
                onDelete: $setup.onDelete
              }, null, 8, ["class", "server"]);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ]),
        _: 1
        /* STABLE */
      }, 8, ["class", "aria-label", "enterFromClass", "enterActiveClass", "leaveActiveClass", "leaveToClass"]),
      createVNode($setup["AddTrustedServerForm"], { onAdd: $setup.onAdd })
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "docUrl", "description"]);
}
const cssModules = {
  "$style": style0
};
const AdminSettings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/federation/src/views/AdminSettings.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const app = createApp(AdminSettings);
app.mount("#federation-admin-settings");
//# sourceMappingURL=federation-settings-admin.mjs.map
