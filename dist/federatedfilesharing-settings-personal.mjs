const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps, b as defineComponent, n as computed, y as ref, c as createBlock, w as withCtx, x as createVNode, j as createTextVNode, F as Fragment, N as normalizeStyle, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { c as showSuccess } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { k as imagePath } from "./index-rAufP352.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcInputField } from "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import { I as Information, N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { W as Web } from "./Web-BOM4en5n.chunk.mjs";
import "./modulepreload-polyfill-mMY-eDcw.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
const _sfc_main$1 = {
  name: "CheckIcon",
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
const _hoisted_1$1 = ["aria-hidden", "aria-label"];
const _hoisted_2$1 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon check-icon",
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
      createBaseVNode("path", _hoisted_3$1, [
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$1,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$1))
  ], 16, _hoisted_1$1);
}
const IconCheck = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Check.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "PersonalSettings",
  setup(__props, { expose: __expose }) {
    __expose();
    const productName = window.OC.theme.productName;
    const color = loadState("federatedfilesharing", "color");
    const textColor = loadState("federatedfilesharing", "textColor");
    const cloudId = loadState("federatedfilesharing", "cloudId");
    const docUrlFederated = loadState("federatedfilesharing", "docUrlFederated");
    const logoPath = loadState("federatedfilesharing", "logoPath");
    const reference = loadState("federatedfilesharing", "reference");
    const urlFacebookIcon = imagePath("core", "facebook");
    const urlMastodonIcon = imagePath("core", "mastodon");
    const urlBlueSkyIcon = imagePath("core", "bluesky");
    const messageWithURL = translate("federatedfilesharing", "Share with me through my #Nextcloud Federated Cloud ID, see {url}", { url: reference });
    const messageWithoutURL = translate("federatedfilesharing", "Share with me through my #Nextcloud Federated Cloud ID");
    const shareMastodonUrl = `https://mastodon.social/?text=${encodeURIComponent(messageWithoutURL)}&url=${encodeURIComponent(reference)}`;
    const shareFacebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(reference)}`;
    const shareBlueSkyUrl = `https://bsky.app/intent/compose?text=${encodeURIComponent(messageWithURL)}`;
    const logoPathAbsolute = new URL(logoPath, location.origin);
    const showHtml = ref(false);
    const isCopied = ref(false);
    const backgroundStyle = computed(() => `
	padding:10px;
	background-color:${color};
	color:${textColor};
	border-radius:3px;
	padding-inline-start:4px;`);
    const linkStyle = `background-image:url(${logoPathAbsolute});width:50px;height:30px;position:relative;top:8px;background-size:contain;display:inline-block;background-repeat:no-repeat; background-position: center center;`;
    const htmlCode = computed(() => `<a target="_blank" rel="noreferrer noopener" href="${reference}" style="${backgroundStyle.value}">
	<span style="${linkStyle}"></span>
	${translate("federatedfilesharing", "Share with me via Nextcloud")}
</a>`);
    const copyLinkTooltip = computed(() => isCopied.value ? translate("federatedfilesharing", "Cloud ID copied") : translate("federatedfilesharing", "Copy"));
    async function copyCloudId() {
      try {
        await navigator.clipboard.writeText(cloudId);
        showSuccess(translate("federatedfilesharing", "Cloud ID copied"));
      } catch {
        window.prompt(translate("federatedfilesharing", "Clipboard not available. Please copy the cloud ID manually."), cloudId);
      }
      isCopied.value = true;
      showSuccess(translate("federatedfilesharing", "Copied!"));
      setTimeout(() => {
        isCopied.value = false;
      }, 2e3);
    }
    const __returned__ = { productName, color, textColor, cloudId, docUrlFederated, logoPath, reference, urlFacebookIcon, urlMastodonIcon, urlBlueSkyIcon, messageWithURL, messageWithoutURL, shareMastodonUrl, shareFacebookUrl, shareBlueSkyUrl, logoPathAbsolute, showHtml, isCopied, backgroundStyle, linkStyle, htmlCode, copyLinkTooltip, copyCloudId, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcInputField() {
      return NcInputField;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, IconCheck, IconClipboard: Information, IconWeb: Web };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1 = { class: "social-button" };
const _hoisted_2 = ["src"];
const _hoisted_3 = ["src"];
const _hoisted_4 = ["src"];
const _hoisted_5 = { style: { "margin": "10px 0" } };
const _hoisted_6 = ["href"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("federatedfilesharing", "Federated Cloud"),
    description: $setup.t("federatedfilesharing", "You can share with anyone who uses a {productName} server or other Open Cloud Mesh (OCM) compatible servers and services! Just put their Federated Cloud ID in the share dialog. It looks like person@cloud.example.com", { productName: $setup.productName }),
    docUrl: $setup.docUrlFederated
  }, {
    default: withCtx(() => [
      createVNode($setup["NcInputField"], {
        class: "federated-cloud__cloud-id",
        readonly: "",
        label: $setup.t("federatedfilesharing", "Your Federated Cloud ID"),
        modelValue: $setup.cloudId,
        success: $setup.isCopied,
        showTrailingButton: "",
        trailingButtonLabel: $setup.copyLinkTooltip,
        onTrailingButtonClick: $setup.copyCloudId
      }, {
        "trailing-button-icon": withCtx(() => [
          $setup.isCopied ? (openBlock(), createBlock($setup["IconCheck"], {
            key: 0,
            size: 20,
            fillColor: "var(--color-border-success)"
          })) : (openBlock(), createBlock($setup["IconClipboard"], {
            key: 1,
            size: 20
          }))
        ]),
        _: 1
        /* STABLE */
      }, 8, ["label", "modelValue", "success", "trailingButtonLabel"]),
      createBaseVNode("p", _hoisted_1, [
        createTextVNode(
          toDisplayString($setup.t("federatedfilesharing", "Share it so your friends can share files with you:")),
          1
          /* TEXT */
        ),
        _cache[1] || (_cache[1] = createBaseVNode(
          "br",
          null,
          null,
          -1
          /* CACHED */
        )),
        createVNode($setup["NcButton"], { href: $setup.shareBlueSkyUrl }, {
          icon: withCtx(() => [
            createBaseVNode("img", {
              class: "social-button__icon",
              src: $setup.urlBlueSkyIcon
            }, null, 8, _hoisted_2)
          ]),
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("federatedfilesharing", "Bluesky")) + " ",
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }),
        createVNode($setup["NcButton"], { href: $setup.shareFacebookUrl }, {
          icon: withCtx(() => [
            createBaseVNode("img", {
              class: "social-button__icon social-button__icon--bright",
              src: $setup.urlFacebookIcon
            }, null, 8, _hoisted_3)
          ]),
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("federatedfilesharing", "Facebook")) + " ",
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }),
        createVNode($setup["NcButton"], { href: $setup.shareMastodonUrl }, {
          icon: withCtx(() => [
            createBaseVNode("img", {
              class: "social-button__icon",
              src: $setup.urlMastodonIcon
            }, null, 8, _hoisted_4)
          ]),
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("federatedfilesharing", "Mastodon")) + " ",
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }),
        createVNode($setup["NcButton"], {
          class: "social-button__website-button",
          onClick: _cache[0] || (_cache[0] = ($event) => $setup.showHtml = !$setup.showHtml)
        }, {
          icon: withCtx(() => [
            createVNode($setup["IconWeb"], { size: 20 })
          ]),
          default: withCtx(() => [
            createTextVNode(
              " " + toDisplayString($setup.t("federatedfilesharing", "Add to your website")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        })
      ]),
      $setup.showHtml ? (openBlock(), createElementBlock(
        Fragment,
        { key: 0 },
        [
          createBaseVNode("p", _hoisted_5, [
            createBaseVNode("a", {
              target: "_blank",
              rel: "noreferrer noopener",
              href: $setup.reference,
              style: normalizeStyle($setup.backgroundStyle)
            }, [
              createBaseVNode("span", { style: $setup.linkStyle }),
              createTextVNode(
                " " + toDisplayString($setup.t("federatedfilesharing", "Share with me via {productName}", { productName: $setup.productName })),
                1
                /* TEXT */
              )
            ], 12, _hoisted_6)
          ]),
          createBaseVNode("div", null, [
            createBaseVNode(
              "p",
              null,
              toDisplayString($setup.t("federatedfilesharing", "HTML Code:")),
              1
              /* TEXT */
            ),
            _cache[2] || (_cache[2] = createBaseVNode(
              "br",
              null,
              null,
              -1
              /* CACHED */
            )),
            createBaseVNode("pre", null, [
              createBaseVNode(
                "code",
                null,
                toDisplayString($setup.htmlCode),
                1
                /* TEXT */
              )
            ])
          ])
        ],
        64
        /* STABLE_FRAGMENT */
      )) : createCommentVNode("v-if", true)
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description", "docUrl"]);
}
const PersonalSettings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-dcd0b4c8"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/federatedfilesharing/src/components/PersonalSettings.vue"]]);
const app = createApp(PersonalSettings);
app.mount("#vue-personal-federated");
//# sourceMappingURL=federatedfilesharing-settings-personal.mjs.map
