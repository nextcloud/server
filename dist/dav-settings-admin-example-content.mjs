const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps, b as defineComponent, c as createBlock, w as withCtx, i as renderSlot, x as createVNode, r as resolveComponent, j as createTextVNode, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./NcTextArea-CWA3KOiC-Cpgesyiv.chunk.mjs";
import "./index-CZV8rpGu.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
/* empty css                                           */
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { b as generateUrl } from "./index-rAufP352.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import "./Plus-DuSPdibD.chunk.mjs";
import "./index-DD39fp6M.chunk.mjs";
import { D as Download, s as svgCheck } from "./TrayArrowDown-DVjUGg6-.chunk.mjs";
import "./index-BcMnKoRR.chunk.mjs";
import { N as NcDialog } from "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import "./NcEmojiPicker-Djc9a0gw-F1kmncT2.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import "./index-D5BR15En.chunk.mjs";
/* empty css                                        */
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./index-gwTr8m4i.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import "./NcSelectTags-CTHyuMcq-2HejGZhj.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./emoji-BY_D0V5K-BlCul1cD.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import { c as showSuccess, a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { l as logger } from "./logger-DbM6EgbB.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
const IconCancel = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-cancel" viewBox="0 0 24 24"><path d="M12 2C17.5 2 22 6.5 22 12S17.5 22 12 22 2 17.5 2 12 6.5 2 12 2M12 4C10.1 4 8.4 4.6 7.1 5.7L18.3 16.9C19.3 15.5 20 13.8 20 12C20 7.6 16.4 4 12 4M16.9 18.3L5.7 7.1C4.6 8.4 4 10.1 4 12C4 16.4 7.6 20 12 20C13.9 20 15.6 19.4 16.9 18.3Z" /></svg>';
const _sfc_main$7 = {
  name: "AccountIcon",
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
const _hoisted_1$6 = ["aria-hidden", "aria-label"];
const _hoisted_2$6 = ["fill", "width", "height"];
const _hoisted_3$5 = { d: "M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" };
const _hoisted_4$4 = { key: 0 };
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon account-icon",
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
      createBaseVNode("path", _hoisted_3$5, [
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$4,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$6))
  ], 16, _hoisted_1$6);
}
const IconAccount = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Account.vue"]]);
const _sfc_main$6 = {
  name: "RestoreIcon",
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
const _hoisted_1$5 = ["aria-hidden", "aria-label"];
const _hoisted_2$5 = ["fill", "width", "height"];
const _hoisted_3$4 = { d: "M13,3A9,9 0 0,0 4,12H1L4.89,15.89L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3Z" };
const _hoisted_4$3 = { key: 0 };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon restore-icon",
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
      createBaseVNode("path", _hoisted_3$4, [
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$3,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$5))
  ], 16, _hoisted_1$5);
}
const IconRestore = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Restore.vue"]]);
const _sfc_main$5 = {
  name: "TrayArrowUpIcon",
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
const _hoisted_3$3 = { d: "M2 12H4V17H20V12H22V17C22 18.11 21.11 19 20 19H4C2.9 19 2 18.11 2 17V12M12 2L6.46 7.46L7.88 8.88L11 5.75V15H13V5.75L16.13 8.88L17.55 7.45L12 2Z" };
const _hoisted_4$2 = { key: 0 };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon tray-arrow-up-icon",
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
          _hoisted_4$2,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$4))
  ], 16, _hoisted_1$4);
}
const IconUpload = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/TrayArrowUp.vue"]]);
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "ExampleContentDownloadButton",
  props: {
    href: { type: String, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const __returned__ = { get NcButton() {
      return NcButton;
    }, IconDownload: Download };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$3 = { class: "download-button" };
const _hoisted_2$3 = { class: "download-button__label" };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcButton"], {
    variant: "tertiary",
    href: $props.href
  }, {
    icon: withCtx(() => [
      renderSlot(_ctx.$slots, "icon", {}, void 0, true)
    ]),
    default: withCtx(() => [
      createBaseVNode("div", _hoisted_1$3, [
        createBaseVNode("span", _hoisted_2$3, [
          renderSlot(_ctx.$slots, "default", {}, void 0, true)
        ]),
        createVNode($setup["IconDownload"], {
          class: "download-button__icon",
          size: 20
        })
      ])
    ]),
    _: 3
    /* FORWARDED */
  }, 8, ["href"]);
}
const ExampleContentDownloadButton = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__scopeId", "data-v-1a393c56"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/dav/src/components/ExampleContentDownloadButton.vue"]]);
const enableDefaultContact = loadState("dav", "enableDefaultContact", false);
const hasCustomDefaultContact = loadState("dav", "hasCustomDefaultContact", false);
const _sfc_main$3 = {
  name: "ExampleContactSettings",
  components: {
    NcDialog,
    NcButton,
    NcCheckboxRadioSwitch,
    IconUpload,
    IconRestore,
    IconAccount,
    ExampleContentDownloadButton
  },
  setup() {
    return { t: translate };
  },
  data() {
    return {
      enableDefaultContact,
      hasCustomDefaultContact,
      isModalOpen: false,
      loading: false,
      buttons: [
        {
          label: translate("dav", "Cancel"),
          icon: IconCancel,
          callback: () => {
            this.isModalOpen = false;
          }
        },
        {
          label: translate("dav", "Import"),
          icon: svgCheck,
          variant: "primary",
          callback: () => {
            this.clickImportInput();
          }
        }
      ]
    };
  },
  computed: {
    downloadUrl() {
      return generateUrl("/apps/dav/api/defaultcontact/contact");
    }
  },
  methods: {
    updateEnableDefaultContact() {
      cancelableClient.put(generateUrl("apps/dav/api/defaultcontact/config"), {
        allow: !this.enableDefaultContact
      }).then(() => {
        this.enableDefaultContact = !this.enableDefaultContact;
      }).catch(() => {
        showError(translate("dav", "Error while saving settings"));
      });
    },
    toggleModal() {
      this.isModalOpen = !this.isModalOpen;
    },
    clickImportInput() {
      this.$refs.exampleContactImportInput.click();
    },
    resetContact() {
      this.loading = true;
      cancelableClient.put(generateUrl("/apps/dav/api/defaultcontact/contact")).then(() => {
        this.hasCustomDefaultContact = false;
        showSuccess(translate("dav", "Contact reset successfully"));
      }).catch((error) => {
        logger.error("Error importing contact:", { error });
        showError(translate("dav", "Error while resetting contact"));
      }).finally(() => {
        this.loading = false;
      });
    },
    processFile(event) {
      this.loading = true;
      const file = event.target.files[0];
      const reader = new FileReader();
      reader.onload = async () => {
        this.isModalOpen = false;
        try {
          await cancelableClient.put(generateUrl("/apps/dav/api/defaultcontact/contact"), { contactData: reader.result });
          this.hasCustomDefaultContact = true;
          showSuccess(translate("dav", "Contact imported successfully"));
        } catch (error) {
          logger.error("Error importing contact:", { error });
          showError(translate("dav", "Error while importing contact"));
        } finally {
          this.loading = false;
          event.target.value = "";
        }
      };
      reader.readAsText(file);
    }
  }
};
const _hoisted_1$2 = { class: "example-contact-settings" };
const _hoisted_2$2 = {
  key: 0,
  class: "example-contact-settings__buttons"
};
const _hoisted_3$2 = ["disabled"];
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcCheckboxRadioSwitch = resolveComponent("NcCheckboxRadioSwitch");
  const _component_IconAccount = resolveComponent("IconAccount");
  const _component_ExampleContentDownloadButton = resolveComponent("ExampleContentDownloadButton");
  const _component_IconUpload = resolveComponent("IconUpload");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_IconRestore = resolveComponent("IconRestore");
  const _component_NcDialog = resolveComponent("NcDialog");
  return openBlock(), createElementBlock("div", _hoisted_1$2, [
    createVNode(_component_NcCheckboxRadioSwitch, {
      modelValue: $data.enableDefaultContact,
      type: "switch",
      "onUpdate:modelValue": $options.updateEnableDefaultContact
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("dav", "Add example contact to user's address book when they first log in")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["modelValue", "onUpdate:modelValue"]),
    $data.enableDefaultContact ? (openBlock(), createElementBlock("div", _hoisted_2$2, [
      createVNode(_component_ExampleContentDownloadButton, { href: $options.downloadUrl }, {
        icon: withCtx(() => [
          createVNode(_component_IconAccount, { size: 20 })
        ]),
        default: withCtx(() => [
          _cache[2] || (_cache[2] = createTextVNode(
            " example_contact.vcf ",
            -1
            /* CACHED */
          ))
        ]),
        _: 1
        /* STABLE */
      }, 8, ["href"]),
      createVNode(_component_NcButton, {
        variant: "secondary",
        onClick: $options.toggleModal
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconUpload, { size: 20 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("dav", "Import contact")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["onClick"]),
      $data.hasCustomDefaultContact ? (openBlock(), createBlock(_component_NcButton, {
        key: 0,
        variant: "tertiary",
        onClick: $options.resetContact
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconRestore, { size: 20 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("dav", "Reset to default")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["onClick"])) : createCommentVNode("v-if", true)
    ])) : createCommentVNode("v-if", true),
    createVNode(_component_NcDialog, {
      open: $data.isModalOpen,
      "onUpdate:open": _cache[0] || (_cache[0] = ($event) => $data.isModalOpen = $event),
      name: $setup.t("dav", "Import contacts"),
      buttons: $data.buttons
    }, {
      default: withCtx(() => [
        createBaseVNode("div", null, [
          createBaseVNode(
            "p",
            null,
            toDisplayString($setup.t("dav", "Importing a new .vcf file will delete the existing default contact and replace it with the new one. Do you want to continue?")),
            1
            /* TEXT */
          )
        ])
      ]),
      _: 1
      /* STABLE */
    }, 8, ["open", "name", "buttons"]),
    createBaseVNode("input", {
      id: "example-contact-import",
      ref: "exampleContactImportInput",
      disabled: $data.loading,
      type: "file",
      accept: ".vcf",
      class: "hidden-visually",
      onChange: _cache[1] || (_cache[1] = (...args) => $options.processFile && $options.processFile(...args))
    }, null, 40, _hoisted_3$2)
  ]);
}
const ExampleContactSettings = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-1c516d62"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/dav/src/components/ExampleContactSettings.vue"]]);
const _sfc_main$2 = {
  name: "CalendarBlankIcon",
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
const _hoisted_3$1 = { d: "M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon calendar-blank-icon",
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
const IconCalendarBlank = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/CalendarBlank.vue"]]);
async function setCreateExampleEvent(enable) {
  const url = generateUrl("/apps/dav/api/exampleEvent/enable");
  await cancelableClient.post(url, {
    enable
  });
}
async function uploadExampleEvent(ics) {
  const url = generateUrl("/apps/dav/api/exampleEvent/event");
  await cancelableClient.post(url, {
    ics
  });
}
async function deleteExampleEvent() {
  const url = generateUrl("/apps/dav/api/exampleEvent/event");
  await cancelableClient.delete(url);
}
const _sfc_main$1 = {
  name: "ExampleEventSettings",
  components: {
    NcButton,
    NcCheckboxRadioSwitch,
    NcDialog,
    IconCalendarBlank,
    IconUpload,
    IconRestore,
    ExampleContentDownloadButton
  },
  setup() {
    return { t: translate };
  },
  data() {
    return {
      createExampleEvent: loadState("dav", "create_example_event", false),
      hasCustomEvent: loadState("dav", "has_custom_example_event", false),
      showImportModal: false,
      uploading: false,
      deleting: false,
      savingConfig: false,
      selectedFile: void 0
    };
  },
  computed: {
    downloadUrl() {
      return generateUrl("/apps/dav/api/exampleEvent/event");
    }
  },
  methods: {
    selectFile() {
      this.selectedFile = this.$refs["event-file"]?.files[0];
    },
    async updateCreateExampleEvent() {
      this.savingConfig = true;
      const enable = !this.createExampleEvent;
      try {
        await setCreateExampleEvent(enable);
      } catch (error) {
        showError(translate("dav", "Failed to save example event creation setting"));
        logger.error("Failed to save example event creation setting", {
          error,
          enable
        });
      } finally {
        this.savingConfig = false;
      }
      this.createExampleEvent = enable;
    },
    uploadCustomEvent() {
      if (!this.selectedFile) {
        return;
      }
      this.uploading = true;
      const reader = new FileReader();
      reader.addEventListener("load", async () => {
        const ics = reader.result;
        try {
          await uploadExampleEvent(ics);
        } catch (error) {
          showError(translate("dav", "Failed to upload the example event"));
          logger.error("Failed to upload example ICS", {
            error,
            ics
          });
          return;
        } finally {
          this.uploading = false;
        }
        showSuccess(translate("dav", "Custom example event was saved successfully"));
        this.showImportModal = false;
        this.hasCustomEvent = true;
      });
      reader.readAsText(this.selectedFile);
    },
    async deleteCustomEvent() {
      this.deleting = true;
      try {
        await deleteExampleEvent();
      } catch (error) {
        showError(translate("dav", "Failed to delete the custom example event"));
        logger.error("Failed to delete the custom example event", {
          error
        });
        return;
      } finally {
        this.deleting = false;
      }
      showSuccess(translate("dav", "Custom example event was deleted successfully"));
      this.hasCustomEvent = false;
    }
  }
};
const _hoisted_1 = { class: "example-event-settings" };
const _hoisted_2 = {
  key: 0,
  class: "example-event-settings__buttons"
};
const _hoisted_3 = { class: "import-event-modal" };
const _hoisted_4 = ["disabled"];
const _hoisted_5 = { class: "import-event-modal__buttons" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcCheckboxRadioSwitch = resolveComponent("NcCheckboxRadioSwitch");
  const _component_IconCalendarBlank = resolveComponent("IconCalendarBlank");
  const _component_ExampleContentDownloadButton = resolveComponent("ExampleContentDownloadButton");
  const _component_IconUpload = resolveComponent("IconUpload");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_IconRestore = resolveComponent("IconRestore");
  const _component_NcDialog = resolveComponent("NcDialog");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_NcCheckboxRadioSwitch, {
      modelValue: $data.createExampleEvent,
      disabled: $data.savingConfig,
      type: "switch",
      "onUpdate:modelValue": $options.updateCreateExampleEvent
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("dav", "Add example event to user's calendar when they first log in")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["modelValue", "disabled", "onUpdate:modelValue"]),
    $data.createExampleEvent ? (openBlock(), createElementBlock("div", _hoisted_2, [
      createVNode(_component_ExampleContentDownloadButton, { href: $options.downloadUrl }, {
        icon: withCtx(() => [
          createVNode(_component_IconCalendarBlank, { size: 20 })
        ]),
        default: withCtx(() => [
          _cache[4] || (_cache[4] = createTextVNode(
            " example_event.ics ",
            -1
            /* CACHED */
          ))
        ]),
        _: 1
        /* STABLE */
      }, 8, ["href"]),
      createVNode(_component_NcButton, {
        variant: "secondary",
        onClick: _cache[0] || (_cache[0] = ($event) => $data.showImportModal = true)
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconUpload, { size: 20 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("dav", "Import calendar event")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }),
      $data.hasCustomEvent ? (openBlock(), createBlock(_component_NcButton, {
        key: 0,
        variant: "tertiary",
        disabled: $data.deleting,
        onClick: $options.deleteCustomEvent
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconRestore, { size: 20 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("dav", "Reset to default")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled", "onClick"])) : createCommentVNode("v-if", true)
    ])) : createCommentVNode("v-if", true),
    createVNode(_component_NcDialog, {
      open: $data.showImportModal,
      "onUpdate:open": _cache[3] || (_cache[3] = ($event) => $data.showImportModal = $event),
      name: $setup.t("dav", "Import calendar event")
    }, {
      default: withCtx(() => [
        createBaseVNode("div", _hoisted_3, [
          createBaseVNode(
            "p",
            null,
            toDisplayString($setup.t("dav", "Uploading a new event will overwrite the existing one.")),
            1
            /* TEXT */
          ),
          createBaseVNode("input", {
            ref: "event-file",
            disabled: $data.uploading,
            type: "file",
            accept: ".ics,text/calendar",
            class: "import-event-modal__file-picker",
            onChange: _cache[1] || (_cache[1] = (...args) => $options.selectFile && $options.selectFile(...args))
          }, null, 40, _hoisted_4),
          createBaseVNode("div", _hoisted_5, [
            createVNode(_component_NcButton, {
              disabled: $data.uploading || !$data.selectedFile,
              variant: "primary",
              onClick: _cache[2] || (_cache[2] = ($event) => $options.uploadCustomEvent())
            }, {
              icon: withCtx(() => [
                createVNode(_component_IconUpload, { size: 20 })
              ]),
              default: withCtx(() => [
                createTextVNode(
                  " " + toDisplayString($setup.t("dav", "Upload event")),
                  1
                  /* TEXT */
                )
              ]),
              _: 1
              /* STABLE */
            }, 8, ["disabled"])
          ])
        ])
      ]),
      _: 1
      /* STABLE */
    }, 8, ["open", "name"])
  ]);
}
const ExampleEventSettings = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-9048222d"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/dav/src/components/ExampleEventSettings.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "ExampleContentSettingsSection",
  setup(__props, { expose: __expose }) {
    __expose();
    const hasContactsApp = loadState("dav", "contactsEnabled");
    const hasCalendarApp = loadState("dav", "calendarEnabled");
    const __returned__ = { hasContactsApp, hasCalendarApp, get t() {
      return translate;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, ExampleContactSettings, ExampleEventSettings };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    id: "example-content",
    name: $setup.t("dav", "Example content"),
    description: $setup.t("dav", "Example content serves to showcase the features of Nextcloud. Default content is shipped with Nextcloud, and can be replaced by custom content.")
  }, {
    default: withCtx(() => [
      $setup.hasContactsApp ? (openBlock(), createBlock($setup["ExampleContactSettings"], { key: 0 })) : createCommentVNode("v-if", true),
      $setup.hasCalendarApp ? (openBlock(), createBlock($setup["ExampleEventSettings"], { key: 1 })) : createCommentVNode("v-if", true)
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description"]);
}
const ExampleContentSettingsSection = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/dav/src/views/ExampleContentSettingsSection.vue"]]);
const app = createApp(ExampleContentSettingsSection);
app.mount("#settings-example-content");
//# sourceMappingURL=dav-settings-admin-example-content.mjs.map
