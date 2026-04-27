const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { d as defineStore, c as createPinia } from "./pinia-0yhe0wHh.chunk.mjs";
import { y as ref, b as defineComponent, n as computed, o as openBlock, f as createElementBlock, c as createBlock, w as withCtx, j as createTextVNode, t as toDisplayString, h as createCommentVNode, F as Fragment, g as createBaseVNode, C as renderList, v as normalizeClass, x as createVNode, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { g as getCapabilities, l as loadState, _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import { a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as confirmPassword } from "./index-Dl6U1WCt.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { g as getLoggerBuilder, b as generateUrl } from "./index-rAufP352.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const logger = getLoggerBuilder().detectLogLevel().setApp("twofactor_backupcodes").build();
/*!
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
function print(data) {
  const name = getCapabilities().theming.name || "Nextcloud";
  const newTab = window.open("", translate("twofactor_backupcodes", "{name} backup codes", { name }));
  if (!newTab) {
    showError(translate("twofactor_backupcodes", "Unable to open a new tab for printing"));
    throw new Error("Unable to open a new tab for printing");
  }
  const heading = newTab.document.createElement("h1");
  heading.textContent = translate("twofactor_backupcodes", "{name} backup codes", { name });
  const pre = newTab.document.createElement("pre");
  for (const code of data) {
    const codeLine = newTab.document.createTextNode(code);
    pre.appendChild(codeLine);
    pre.appendChild(newTab.document.createElement("br"));
  }
  newTab.document.body.innerHTML = "";
  newTab.document.body.appendChild(heading);
  newTab.document.body.appendChild(pre);
  newTab.print();
  newTab.close();
}
async function generateCodes() {
  const url = generateUrl("/apps/twofactor_backupcodes/settings/create");
  const { data } = await cancelableClient.post(url);
  return data;
}
const initialState = loadState("twofactor_backupcodes", "state");
const useStore = defineStore("twofactor_backupcodes", () => {
  const enabled = ref(initialState.enabled);
  const total = ref(initialState.total);
  const used = ref(initialState.used);
  const codes = ref([]);
  async function generate() {
    enabled.value = false;
    const { codes: newCodes, state } = await generateCodes();
    enabled.value = state.enabled;
    total.value = state.total;
    used.value = state.used;
    codes.value = newCodes;
  }
  return {
    enabled,
    total,
    used,
    codes,
    generate
  };
});
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "PersonalSettings",
  setup(__props, { expose: __expose }) {
    __expose();
    const instanceName = getCapabilities().theming.name ?? "Nextcloud";
    const store = useStore();
    const generatingCodes = ref(false);
    const hasCodes = computed(() => {
      return store.codes && store.codes.length > 0;
    });
    const downloadFilename = instanceName + "-backup-codes.txt";
    const downloadUrl = computed(() => {
      if (!hasCodes.value) {
        return "";
      }
      return "data:text/plain," + encodeURIComponent(store.codes.reduce((prev, code) => {
        return prev + code + "\n";
      }, ""));
    });
    async function generateBackupCodes() {
      await confirmPassword();
      generatingCodes.value = true;
      try {
        await store.generate();
      } catch (error) {
        logger.error("Error generating backup codes", { error });
        showError(translate("twofactor_backupcodes", "An error occurred while generating your backup codes"));
      } finally {
        generatingCodes.value = false;
      }
    }
    function printCodes() {
      print(!store.codes || store.codes.length === 0 ? [] : store.codes);
    }
    const __returned__ = { instanceName, store, generatingCodes, hasCodes, downloadFilename, downloadUrl, generateBackupCodes, printCodes, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const backupcodesSettings = "_backupcodesSettings_bnkw8_2";
const backupcodesSettings__code = "_backupcodesSettings__code_bnkw8_7";
const backupcodesSettings__actions = "_backupcodesSettings__actions_bnkw8_13";
const style0 = {
  backupcodesSettings,
  backupcodesSettings__code,
  backupcodesSettings__actions
};
const _hoisted_1 = ["aria-label"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass(_ctx.$style.backupcodesSettings)
    },
    [
      !$setup.store.enabled ? (openBlock(), createBlock($setup["NcButton"], {
        key: 0,
        disabled: $setup.generatingCodes,
        variant: "primary",
        onClick: $setup.generateBackupCodes
      }, {
        icon: withCtx(() => [
          $setup.generatingCodes ? (openBlock(), createBlock($setup["NcLoadingIcon"], { key: 0 })) : createCommentVNode("v-if", true)
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("twofactor_backupcodes", "Generate backup codes")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"])) : (openBlock(), createElementBlock(
        Fragment,
        { key: 1 },
        [
          createBaseVNode("p", null, [
            !$setup.hasCodes ? (openBlock(), createElementBlock(
              Fragment,
              { key: 0 },
              [
                createTextVNode(
                  toDisplayString($setup.t("twofactor_backupcodes", "Backup codes have been generated. {used} of {total} codes have been used.", { used: $setup.store.used, total: $setup.store.total })),
                  1
                  /* TEXT */
                )
              ],
              64
              /* STABLE_FRAGMENT */
            )) : (openBlock(), createElementBlock(
              Fragment,
              { key: 1 },
              [
                createTextVNode(
                  toDisplayString($setup.t("twofactor_backupcodes", "These are your backup codes. Please save and/or print them as you will not be able to read the codes again later.")) + " ",
                  1
                  /* TEXT */
                ),
                createBaseVNode("ul", {
                  "aria-label": $setup.t("twofactor_backupcodes", "List of backup codes")
                }, [
                  (openBlock(true), createElementBlock(
                    Fragment,
                    null,
                    renderList($setup.store.codes, (code) => {
                      return openBlock(), createElementBlock(
                        "li",
                        {
                          key: code,
                          class: normalizeClass(_ctx.$style.backupcodesSettings__code)
                        },
                        toDisplayString(code),
                        3
                        /* TEXT, CLASS */
                      );
                    }),
                    128
                    /* KEYED_FRAGMENT */
                  ))
                ], 8, _hoisted_1)
              ],
              64
              /* STABLE_FRAGMENT */
            ))
          ]),
          createBaseVNode(
            "p",
            {
              class: normalizeClass(_ctx.$style.backupcodesSettings__actions)
            },
            [
              createVNode($setup["NcButton"], {
                id: "generate-backup-codes",
                variant: "error",
                onClick: $setup.generateBackupCodes
              }, {
                default: withCtx(() => [
                  createTextVNode(
                    toDisplayString($setup.t("twofactor_backupcodes", "Regenerate backup codes")),
                    1
                    /* TEXT */
                  )
                ]),
                _: 1
                /* STABLE */
              }),
              $setup.hasCodes ? (openBlock(), createElementBlock(
                Fragment,
                { key: 0 },
                [
                  createVNode($setup["NcButton"], { onClick: $setup.printCodes }, {
                    default: withCtx(() => [
                      createTextVNode(
                        toDisplayString($setup.t("twofactor_backupcodes", "Print backup codes")),
                        1
                        /* TEXT */
                      )
                    ]),
                    _: 1
                    /* STABLE */
                  }),
                  createVNode($setup["NcButton"], {
                    href: $setup.downloadUrl,
                    download: $setup.downloadFilename,
                    variant: "primary"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(
                        toDisplayString($setup.t("twofactor_backupcodes", "Save backup codes")),
                        1
                        /* TEXT */
                      )
                    ]),
                    _: 1
                    /* STABLE */
                  }, 8, ["href"])
                ],
                64
                /* STABLE_FRAGMENT */
              )) : createCommentVNode("v-if", true)
            ],
            2
            /* CLASS */
          ),
          createBaseVNode("p", null, [
            createBaseVNode(
              "em",
              null,
              toDisplayString($setup.t("twofactor_backupcodes", "If you regenerate backup codes, you automatically invalidate old codes.")),
              1
              /* TEXT */
            )
          ])
        ],
        64
        /* STABLE_FRAGMENT */
      ))
    ],
    2
    /* CLASS */
  );
}
const cssModules = {
  "$style": style0
};
const PersonalSettings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/twofactor_backupcodes/src/views/PersonalSettings.vue"]]);
const pinia = createPinia();
const app = createApp(PersonalSettings);
app.use(pinia);
app.mount("#twofactor-backupcodes-settings");
//# sourceMappingURL=twofactor_backupcodes-settings-personal.mjs.map
