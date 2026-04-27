const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { c as cancelableClient, i as isAxiosError } from "./index-D5H5XMHa.chunk.mjs";
import { _ as _export_sfc, g as getCapabilities, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { P as PwdConfirmationMode, a as addPasswordConfirmationInterceptors } from "./index-Dl6U1WCt.chunk.mjs";
import { b as defineComponent, o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, v as normalizeClass, c as createBlock, x as createVNode, w as withCtx, k as useModel, y as ref, F as Fragment, C as renderList, h as createCommentVNode, j as createTextVNode, M as withModifiers, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { b as generateUrl } from "./index-rAufP352.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcNoteCard } from "./mdi-BGU2G5q5.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { _ as _sfc_main$2 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { N as NcPasswordField } from "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import { D as Delete } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./modulepreload-polyfill-mMY-eDcw.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "OAuthItem",
  props: {
    client: { type: Object, required: true }
  },
  emits: ["delete"],
  setup(__props, { expose: __expose }) {
    __expose();
    const __returned__ = { get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcPasswordField() {
      return NcPasswordField;
    }, IconTrashCanOutline: Delete };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const oAuthItem__code = "_oAuthItem__code_3r02l_2";
const oAuthItem__clientSecret = "_oAuthItem__clientSecret_3r02l_11";
const style0$1 = {
  oAuthItem__code,
  oAuthItem__clientSecret
};
const _hoisted_1$1 = { key: 1 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("tr", null, [
    createBaseVNode(
      "td",
      null,
      toDisplayString($props.client.name),
      1
      /* TEXT */
    ),
    createBaseVNode("td", null, [
      createBaseVNode(
        "code",
        {
          class: normalizeClass(_ctx.$style.oAuthItem__code)
        },
        toDisplayString($props.client.redirectUri),
        3
        /* TEXT, CLASS */
      )
    ]),
    createBaseVNode("td", null, [
      createBaseVNode(
        "code",
        {
          class: normalizeClass(_ctx.$style.oAuthItem__code)
        },
        toDisplayString($props.client.clientId),
        3
        /* TEXT, CLASS */
      )
    ]),
    createBaseVNode("td", null, [
      $props.client.clientSecret ? (openBlock(), createBlock($setup["NcPasswordField"], {
        key: 0,
        class: normalizeClass(_ctx.$style.oAuthItem__clientSecret),
        "aria-label": $setup.t("oauth2", "Secret key"),
        asText: "",
        modelValue: $props.client.clientSecret,
        showTrailingButton: ""
      }, null, 8, ["class", "aria-label", "modelValue"])) : (openBlock(), createElementBlock("span", _hoisted_1$1, "*****"))
    ]),
    createBaseVNode("td", null, [
      createVNode($setup["NcButton"], {
        "aria-label": $setup.t("oauth2", "Delete"),
        title: $setup.t("oauth2", "Delete"),
        variant: "error",
        onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("delete"))
      }, {
        icon: withCtx(() => [
          createVNode($setup["IconTrashCanOutline"], { size: 20 })
        ]),
        _: 1
        /* STABLE */
      }, 8, ["aria-label", "title"])
    ])
  ]);
}
const cssModules$1 = {
  "$style": style0$1
};
const OAuthItem = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__cssModules", cssModules$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/oauth2/src/components/OAuthItem.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "AdminSettings",
  props: {
    "modelValue": { type: Array, ...{ required: true } },
    "modelModifiers": {}
  },
  emits: ["update:modelValue"],
  setup(__props, { expose: __expose }) {
    __expose();
    const clients2 = useModel(__props, "modelValue");
    const instanceName = getCapabilities().theming.name;
    const oauthDocLink = loadState("oauth2", "oauth2-doc-link");
    const showSecretWarning = ref(false);
    const newClient = ref({
      name: "",
      redirectUri: "",
      errorMsg: "",
      error: false
    });
    async function deleteClient(id) {
      await cancelableClient.delete(generateUrl("apps/oauth2/clients/{id}", { id }));
      clients2.value = clients2.value.filter((client) => client.id !== id);
    }
    async function addClient() {
      newClient.value.error = false;
      try {
        const { data } = await cancelableClient.post(generateUrl("apps/oauth2/clients"), {
          name: newClient.value.name,
          redirectUri: newClient.value.redirectUri
        }, { confirmPassword: PwdConfirmationMode.Strict });
        clients2.value.push(data);
        showSecretWarning.value = true;
        newClient.value.name = "";
        newClient.value.redirectUri = "";
      } catch (error) {
        newClient.value.error = true;
        if (isAxiosError(error) && error.response) {
          newClient.value.errorMsg = error.response.data.message;
        } else {
          newClient.value.errorMsg = translate("oauth2", "An unknown error occurred.");
        }
      }
    }
    const __returned__ = { clients: clients2, instanceName, oauthDocLink, showSecretWarning, newClient, deleteClient, addClient, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcNoteCard() {
      return NcNoteCard;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, get NcTextField() {
      return _sfc_main$2;
    }, OAuthItem };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const oauthApp__form = "_oauthApp__form_10pnp_1";
const oauthApp__form__input = "_oauthApp__form__input_10pnp_6";
const oauthApp__table = "_oauthApp__table_10pnp_11";
const oauthApp__table_withSecret = "_oauthApp__table_withSecret_10pnp_38";
const style0 = {
  oauthApp__form,
  oauthApp__form__input,
  oauthApp__table,
  oauthApp__table_withSecret
};
const _hoisted_1 = { class: "hidden-visually" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("oauth2", "OAuth 2.0 clients"),
    description: $setup.t("oauth2", "OAuth 2.0 allows external services to request access to {instanceName}.", { instanceName: $setup.instanceName }),
    docUrl: $setup.oauthDocLink
  }, {
    default: withCtx(() => [
      $setup.clients.length > 0 ? (openBlock(), createElementBlock(
        "table",
        {
          key: 0,
          class: normalizeClass([_ctx.$style.oauthApp__table, { [_ctx.$style.oauthApp__table_withSecret]: $setup.showSecretWarning }])
        },
        [
          createBaseVNode("thead", null, [
            createBaseVNode("tr", null, [
              createBaseVNode(
                "th",
                null,
                toDisplayString($setup.t("oauth2", "Name")),
                1
                /* TEXT */
              ),
              createBaseVNode(
                "th",
                null,
                toDisplayString($setup.t("oauth2", "Redirection URI")),
                1
                /* TEXT */
              ),
              createBaseVNode(
                "th",
                null,
                toDisplayString($setup.t("oauth2", "Client identifier")),
                1
                /* TEXT */
              ),
              createBaseVNode(
                "th",
                null,
                toDisplayString($setup.t("oauth2", "Secret key")),
                1
                /* TEXT */
              ),
              createBaseVNode("th", null, [
                createBaseVNode(
                  "span",
                  _hoisted_1,
                  toDisplayString($setup.t("oauth2", "Delete client")),
                  1
                  /* TEXT */
                )
              ])
            ])
          ]),
          createBaseVNode("tbody", null, [
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($setup.clients, (client) => {
                return openBlock(), createBlock($setup["OAuthItem"], {
                  key: client.id,
                  client,
                  onDelete: ($event) => $setup.deleteClient(client.id)
                }, null, 8, ["client", "onDelete"]);
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ])
        ],
        2
        /* CLASS */
      )) : createCommentVNode("v-if", true),
      $setup.showSecretWarning ? (openBlock(), createBlock($setup["NcNoteCard"], {
        key: 1,
        type: "warning"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("oauth2", "Make sure you store the secret key, it cannot be recovered.")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      _cache[2] || (_cache[2] = createBaseVNode(
        "br",
        null,
        null,
        -1
        /* CACHED */
      )),
      createBaseVNode(
        "h3",
        null,
        toDisplayString($setup.t("oauth2", "Add client")),
        1
        /* TEXT */
      ),
      $setup.newClient.error ? (openBlock(), createBlock($setup["NcNoteCard"], {
        key: 2,
        type: "error"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.newClient.errorMsg),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      createBaseVNode(
        "form",
        {
          class: normalizeClass(_ctx.$style.oauthApp__form),
          onSubmit: withModifiers($setup.addClient, ["prevent"])
        },
        [
          createVNode($setup["NcTextField"], {
            id: "name",
            modelValue: $setup.newClient.name,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.newClient.name = $event),
            class: normalizeClass(_ctx.$style.oauthApp__form__input),
            name: "name",
            label: $setup.t("oauth2", "Name"),
            placeholder: $setup.t("oauth2", "Name")
          }, null, 8, ["modelValue", "class", "label", "placeholder"]),
          createVNode($setup["NcTextField"], {
            id: "redirectUri",
            modelValue: $setup.newClient.redirectUri,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.newClient.redirectUri = $event),
            type: "url",
            class: normalizeClass(_ctx.$style.oauthApp__form__input),
            name: "redirectUri",
            label: $setup.t("oauth2", "Redirection URI"),
            placeholder: $setup.t("oauth2", "Redirection URI")
          }, null, 8, ["modelValue", "class", "label", "placeholder"]),
          createVNode($setup["NcButton"], {
            type: "submit",
            class: normalizeClass(_ctx.$style.oauthApp__submitButton)
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("oauth2", "Add")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["class"])
        ],
        34
        /* CLASS, NEED_HYDRATION */
      )
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description", "docUrl"]);
}
const cssModules = {
  "$style": style0
};
const AdminSettings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/oauth2/src/views/AdminSettings.vue"]]);
addPasswordConfirmationInterceptors(cancelableClient);
const clients = loadState("oauth2", "clients");
const app = createApp(AdminSettings, {
  modelValue: clients
});
app.mount("#oauth2");
//# sourceMappingURL=oauth2-settings-admin.mjs.map
