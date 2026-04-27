const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, y as ref, o as openBlock, c as createBlock, w as withCtx, h as createCommentVNode, x as createVNode } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { N as NcDialog } from "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import { N as NcNoteCard } from "./mdi-BGU2G5q5.chunk.mjs";
import { N as NcPasswordField } from "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import { _ as _sfc_main$1 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "CredentialsDialog",
  emits: ["close"],
  setup(__props, { expose: __expose }) {
    __expose();
    const login = ref("");
    const password = ref("");
    const dialogButtons = [{
      label: translate("files_external", "Confirm"),
      type: "submit",
      variant: "primary"
    }];
    const __returned__ = { login, password, dialogButtons, get t() {
      return translate;
    }, get NcDialog() {
      return NcDialog;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcPasswordField() {
      return NcPasswordField;
    }, get NcTextField() {
      return _sfc_main$1;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcDialog"], {
    buttons: $setup.dialogButtons,
    class: "external-storage-auth",
    closeOnClickOutside: "",
    "data-cy-external-storage-auth": "",
    isForm: "",
    name: $setup.t("files_external", "Storage credentials"),
    outTransition: "",
    onSubmit: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("close", { login: $setup.login, password: $setup.password })),
    "onUpdate:open": _cache[3] || (_cache[3] = ($event) => _ctx.$emit("close"))
  }, {
    default: withCtx(() => [
      createCommentVNode(" Header "),
      createVNode($setup["NcNoteCard"], {
        class: "external-storage-auth__header",
        text: $setup.t("files_external", "To access the storage, you need to provide the authentication credentials."),
        type: "info"
      }, null, 8, ["text"]),
      createCommentVNode(" Login "),
      createVNode($setup["NcTextField"], {
        modelValue: $setup.login,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.login = $event),
        autofocus: "",
        class: "external-storage-auth__login",
        "data-cy-external-storage-auth-dialog-login": "",
        label: $setup.t("files_external", "Login"),
        placeholder: $setup.t("files_external", "Enter the storage login"),
        minlength: "2",
        name: "login",
        required: ""
      }, null, 8, ["modelValue", "label", "placeholder"]),
      createCommentVNode(" Password "),
      createVNode($setup["NcPasswordField"], {
        modelValue: $setup.password,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.password = $event),
        class: "external-storage-auth__password",
        "data-cy-external-storage-auth-dialog-password": "",
        label: $setup.t("files_external", "Password"),
        placeholder: $setup.t("files_external", "Enter the storage password"),
        name: "password",
        required: ""
      }, null, 8, ["modelValue", "label", "placeholder"])
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name"]);
}
const CredentialsDialog = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_external/src/views/CredentialsDialog.vue"]]);
export {
  CredentialsDialog as default
};
//# sourceMappingURL=CredentialsDialog-D19G-PMj.chunk.mjs.map
