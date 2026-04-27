const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { r as resolveComponent, o as openBlock, c as createBlock, w as withCtx, x as createVNode, j as createTextVNode, t as toDisplayString, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as confirmPassword } from "./index-Dl6U1WCt.chunk.mjs";
import { g as getLoggerBuilder, c as generateOcsUrl } from "./index-rAufP352.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
const logger = getLoggerBuilder().detectLogLevel().setApp("sharebymail").build();
const _sfc_main = {
  name: "AdminSettings",
  components: {
    NcCheckboxRadioSwitch,
    NcSettingsSection
  },
  setup() {
    return { t: translate };
  },
  data() {
    return {
      sendPasswordMail: loadState("sharebymail", "sendPasswordMail"),
      replyToInitiator: loadState("sharebymail", "replyToInitiator")
    };
  },
  watch: {
    sendPasswordMail(newValue) {
      this.update("sendpasswordmail", newValue);
    },
    replyToInitiator(newValue) {
      this.update("replyToInitiator", newValue);
    }
  },
  methods: {
    async update(key, value) {
      await confirmPassword();
      const url = generateOcsUrl("/apps/provisioning_api/api/v1/config/apps/{appId}/{key}", {
        appId: "sharebymail",
        key
      });
      const stringValue = value ? "yes" : "no";
      try {
        const { data } = await cancelableClient.post(url, {
          value: stringValue
        });
        this.handleResponse({
          status: data.ocs?.meta?.status
        });
      } catch (e) {
        this.handleResponse({
          errorMessage: translate("sharebymail", "Unable to update share by mail config"),
          error: e
        });
      }
    },
    async handleResponse({ status, errorMessage, error }) {
      if (status !== "ok") {
        showError(errorMessage);
        logger.error(errorMessage, { error });
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcCheckboxRadioSwitch = resolveComponent("NcCheckboxRadioSwitch");
  const _component_NcSettingsSection = resolveComponent("NcSettingsSection");
  return openBlock(), createBlock(_component_NcSettingsSection, {
    name: $setup.t("sharebymail", "Share by mail"),
    description: $setup.t("sharebymail", "Allows people to share a personalized link to a file or folder by putting in an email address.")
  }, {
    default: withCtx(() => [
      createVNode(_component_NcCheckboxRadioSwitch, {
        modelValue: $data.sendPasswordMail,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.sendPasswordMail = $event),
        type: "switch"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("sharebymail", "Send password by mail")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue"]),
      createVNode(_component_NcCheckboxRadioSwitch, {
        modelValue: $data.replyToInitiator,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.replyToInitiator = $event),
        type: "switch"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("sharebymail", "Reply to initiator")),
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
const AdminSettings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/sharebymail/src/components/AdminSettings.vue"]]);
const app = createApp(AdminSettings);
app.mount("#vue-admin-sharebymail");
//# sourceMappingURL=sharebymail-admin-settings.mjs.map
