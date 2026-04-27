const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { c as cancelableClient, i as isAxiosError } from "./index-D5H5XMHa.chunk.mjs";
import { b as showInfo, a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { s as subscribe, b as generateUrl } from "./index-rAufP352.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { N as NcDialog, s as spawnDialog } from "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import { b as defineComponent, n as computed, y as ref, o as openBlock, c as createBlock, w as withCtx, g as createBaseVNode, t as toDisplayString, v as normalizeClass, h as createCommentVNode } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { N as NcPasswordField } from "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import { l as logger } from "./logger-CImeZ7_x.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "RemoteShareDialog",
  props: {
    name: { type: String, required: true },
    owner: { type: String, required: true },
    remote: { type: String, required: true },
    passwordRequired: { type: Boolean, required: true }
  },
  emits: ["close"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const props = __props;
    const emit = __emit;
    const password = ref("");
    const buttons = computed(() => [
      {
        label: translate("federatedfilesharing", "Cancel"),
        callback: () => emit("close", false)
      },
      {
        label: translate("federatedfilesharing", "Add remote share"),
        type: props.passwordRequired ? "submit" : void 0,
        variant: "primary",
        callback: () => emit("close", true, password.value)
      }
    ]);
    const __returned__ = { props, emit, password, buttons, get t() {
      return translate;
    }, get NcDialog() {
      return NcDialog;
    }, get NcPasswordField() {
      return NcPasswordField;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const remoteShareDialog__password = "_remoteShareDialog__password_1ccpy_2";
const style0 = {
  remoteShareDialog__password
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcDialog"], {
    buttons: $setup.buttons,
    isForm: $props.passwordRequired,
    name: $setup.t("federatedfilesharing", "Remote share"),
    onSubmit: _cache[1] || (_cache[1] = ($event) => $setup.emit("close", true, $setup.password))
  }, {
    default: withCtx(() => [
      createBaseVNode(
        "p",
        null,
        toDisplayString($setup.t("federatedfilesharing", "Do you want to add the remote share {name} from {owner}@{remote}?", { name: $props.name, owner: $props.owner, remote: $props.remote })),
        1
        /* TEXT */
      ),
      $props.passwordRequired ? (openBlock(), createBlock($setup["NcPasswordField"], {
        key: 0,
        modelValue: $setup.password,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.password = $event),
        class: normalizeClass(_ctx.$style.remoteShareDialog__password),
        label: $setup.t("federatedfilesharing", "Remote share password")
      }, null, 8, ["modelValue", "class", "label"])) : createCommentVNode("v-if", true)
    ]),
    _: 1
    /* STABLE */
  }, 8, ["buttons", "isForm", "name"]);
}
const cssModules = {
  "$style": style0
};
const RemoteShareDialog = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/federatedfilesharing/src/components/RemoteShareDialog.vue"]]);
async function showRemoteShareDialog(name, owner, remote, passwordRequired = false) {
  const [status, password] = await spawnDialog(RemoteShareDialog, {
    name,
    owner,
    remote,
    passwordRequired
  });
  if (passwordRequired && status) {
    return password;
  } else if (status) {
    return;
  } else {
    throw new Error("Dialog was cancelled");
  }
}
window.addEventListener("DOMContentLoaded", () => {
  processIncomingShareFromUrl();
  if (loadState("federatedfilesharing", "notificationsEnabled", true) !== true) {
    processSharesToConfirm();
  }
  subscribe("notifications:action:executed", ({ action, notification }) => {
    if (notification.app === "files_sharing" && notification.object_type === "remote_share" && action.type === "POST") {
      reloadFilesList();
    }
  });
});
function reloadFilesList() {
  if (!window?.OCP?.Files?.Router?.goToRoute) {
    window.location.reload();
    return;
  }
  window.OCP.Files.Router.goToRoute(
    null,
    { ...window.OCP.Files.Router.params, fileid: void 0 },
    { ...window.OCP.Files.Router.query, dir: "/", openfile: void 0 }
  );
}
function processIncomingShareFromUrl() {
  const params = window.OC.Util.History.parseUrlQuery();
  if (params.remote && params.token && params.name) {
    const callbackAddShare = (result, share) => {
      if (result === false) {
        return;
      }
      cancelableClient.post(
        generateUrl("apps/federatedfilesharing/askForFederatedShare"),
        {
          remote: share.remote,
          token: share.token,
          owner: share.owner,
          ownerDisplayName: share.ownerDisplayName || share.owner,
          name: share.name,
          password: share.password || ""
        }
      ).then(({ data }) => {
        if (Object.hasOwn(data, "legacyMount")) {
          reloadFilesList();
        } else {
          showInfo(data.message);
        }
      }).catch((error) => {
        logger.error("Error while processing incoming share", { error });
        if (isAxiosError(error) && error.response.data.message) {
          showError(error.response.data.message);
        } else {
          showError(translate("federatedfilesharing", "Incoming share could not be processed"));
        }
      });
    };
    location.hash = "";
    params.passwordProtected = parseInt(params.protected, 10) === 1;
    showAddExternalDialog(
      params,
      params.passwordProtected,
      callbackAddShare
    );
  }
}
async function processSharesToConfirm() {
  const { data: shares } = await cancelableClient.get(generateUrl("/apps/files_sharing/api/externalShares"));
  for (let index = 0; index < shares.length; ++index) {
    showAddExternalDialog(
      shares[index],
      false,
      function(result, share) {
        if (result === false) {
          cancelableClient.delete(generateUrl("/apps/files_sharing/api/externalShares/" + share.id));
        } else {
          cancelableClient.post(generateUrl("/apps/files_sharing/api/externalShares"), { id: share.id }).then(() => reloadFilesList());
        }
      }
    );
  }
}
function showAddExternalDialog(share, passwordProtected, callback) {
  const owner = share.ownerDisplayName || share.owner;
  const name = share.name;
  const remote = share.remote.replace(/^https?:\/\//, "").replace(/\/$/, "");
  showRemoteShareDialog(name, owner, remote, passwordProtected).then((password) => callback(true, { ...share, password })).catch(() => callback(false, share));
}
//# sourceMappingURL=federatedfilesharing-init-files.mjs.map
