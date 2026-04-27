const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps, b as defineComponent, y as ref, n as computed, P as nextTick, c as createBlock, w as withCtx, j as createTextVNode, x as createVNode, l as useTemplateRef, a5 as watchEffect, i as renderSlot, N as normalizeStyle, z as watch, a0 as toRef, F as Fragment, C as renderList } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { c as showSuccess, a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { a as getCurrentUser, m as getRootUrl, g as getLoggerBuilder, b as generateUrl, h as generateRemoteUrl, j as encodePath, n as join, f as emit } from "./index-rAufP352.chunk.mjs";
import { t as translate, b as getCanonicalLocale } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as useIsMobile } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { D as Delete, N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { f as formatFileSize } from "./index-DCPyCjGS.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { P as PencilIcon, N as NcActionButton } from "./PencilOutline-BMYBdzdS.chunk.mjs";
import { _ as _sfc_main$7, N as NcActionLink } from "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import { N as NcAvatar } from "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import { D as Download, N as NcListItem, s as svgCheck } from "./TrayArrowDown-DVjUGg6-.chunk.mjs";
import { P as Permission } from "./public-CKeAb98h.chunk.mjs";
import { N as NcDialog } from "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import { _ as _sfc_main$8 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { g as getClient } from "./dav-DGipjjQH.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
const _sfc_main$6 = {
  name: "BackupRestoreIcon",
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
const _hoisted_2$4 = ["fill", "width", "height"];
const _hoisted_3$3 = { d: "M12,3A9,9 0 0,0 3,12H0L4,16L8,12H5A7,7 0 0,1 12,5A7,7 0 0,1 19,12A7,7 0 0,1 12,19C10.5,19 9.09,18.5 7.94,17.7L6.5,19.14C8.04,20.3 9.94,21 12,21A9,9 0 0,0 21,12A9,9 0 0,0 12,3M14,12A2,2 0 0,0 12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12Z" };
const _hoisted_4$3 = { key: 0 };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon backup-restore-icon",
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
  ], 16, _hoisted_1$6);
}
const BackupRestore = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/BackupRestore.vue"]]);
const _sfc_main$5 = {
  name: "FileCompareIcon",
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
const _hoisted_2$3 = ["fill", "width", "height"];
const _hoisted_3$2 = { d: "M10,18H6V16H10V18M10,14H6V12H10V14M10,1V2H6C4.89,2 4,2.89 4,4V20A2,2 0 0,0 6,22H10V23H12V1H10M20,8V20C20,21.11 19.11,22 18,22H14V20H18V11H14V9H18.5L14,4.5V2L20,8M16,14H14V12H16V14M16,18H14V16H16V18Z" };
const _hoisted_4$2 = { key: 0 };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon file-compare-icon",
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
      createBaseVNode("path", _hoisted_3$2, [
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$2,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$3))
  ], 16, _hoisted_1$5);
}
const FileCompare = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/FileCompare.vue"]]);
const _sfc_main$4 = {
  name: "ImageOffOutlineIcon",
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
const _hoisted_2$2 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M22 20.7L3.3 2L2 3.3L3 4.3V19C3 20.1 3.9 21 5 21H19.7L20.7 22L22 20.7M5 19V6.3L12.6 13.9L11.1 15.8L9 13.1L6 17H15.7L17.7 19H5M8.8 5L6.8 3H19C20.1 3 21 3.9 21 5V17.2L19 15.2V5H8.8" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon image-off-outline-icon",
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
    ], 8, _hoisted_2$2))
  ], 16, _hoisted_1$4);
}
const ImageOffOutline = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/ImageOffOutline.vue"]]);
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "VersionEntry",
  props: {
    version: { type: Object, required: true },
    node: { type: null, required: true },
    isCurrent: { type: Boolean, required: true },
    isFirstVersion: { type: Boolean, required: true },
    loadPreview: { type: Boolean, required: true },
    canView: { type: Boolean, required: true },
    canCompare: { type: Boolean, required: true }
  },
  emits: ["click", "compare", "restore", "delete", "labelUpdateRequest"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const props = __props;
    const emit2 = __emit;
    const previewLoaded = ref(false);
    const previewErrored = ref(false);
    const capabilities = ref(loadState("core", "capabilities", { files: { version_labeling: false, version_deletion: false } }));
    const humanReadableSize = computed(() => {
      return formatFileSize(props.version.size);
    });
    const versionLabel = computed(() => {
      const label = props.version.label ?? "";
      if (props.isCurrent) {
        if (label === "") {
          return translate("files_versions", "Current version");
        } else {
          return `${label} (${translate("files_versions", "Current version")})`;
        }
      }
      if (props.isFirstVersion && label === "") {
        return translate("files_versions", "Initial version");
      }
      return label;
    });
    const versionAuthor = computed(() => {
      if (!props.version.author || !props.version.authorName) {
        return "";
      }
      if (props.version.author === getCurrentUser()?.uid) {
        return translate("files_versions", "You");
      }
      return props.version.authorName ?? props.version.author;
    });
    const versionHumanExplicitDate = computed(() => {
      return new Date(props.version.mtime).toLocaleString(
        [getCanonicalLocale(), getCanonicalLocale().split("-")[0]],
        {
          timeStyle: "long",
          dateStyle: "long"
        }
      );
    });
    const downloadURL = computed(() => {
      if (props.isCurrent) {
        return props.node.source;
      } else {
        return getRootUrl() + props.version.url;
      }
    });
    const enableLabeling = computed(() => {
      return capabilities.value.files.version_labeling === true;
    });
    const enableDeletion = computed(() => {
      return capabilities.value.files.version_deletion === true;
    });
    const hasDeletePermissions = computed(() => {
      return hasPermission(props.node, Permission.DELETE);
    });
    const hasUpdatePermissions = computed(() => {
      return hasPermission(props.node, Permission.UPDATE);
    });
    const isDownloadable = computed(() => {
      if ((props.node.permissions & Permission.READ) === 0) {
        return false;
      }
      if (props.node.attributes["mount-type"] === "shared" && props.node.attributes["share-attributes"]) {
        const downloadAttribute = JSON.parse(props.node.attributes["share-attributes"]).find((attribute) => attribute.scope === "permissions" && attribute.key === "download") || {};
        if (downloadAttribute?.value === false) {
          return false;
        }
      }
      return true;
    });
    function labelUpdate() {
      emit2("labelUpdateRequest");
    }
    function restoreVersion2() {
      emit2("restore", props.version);
    }
    async function deleteVersion2() {
      await nextTick();
      await nextTick();
      emit2("delete", props.version);
    }
    function click(event) {
      if (props.canView) {
        event.preventDefault();
      }
      emit2("click", props.version);
    }
    function compareVersion() {
      if (!props.canView) {
        throw new Error("Cannot compare version of this file");
      }
      emit2("compare", props.version);
    }
    function hasPermission(node, permission) {
      return (node.permissions & permission) !== 0;
    }
    const __returned__ = { props, emit: emit2, previewLoaded, previewErrored, capabilities, humanReadableSize, versionLabel, versionAuthor, versionHumanExplicitDate, downloadURL, enableLabeling, enableDeletion, hasDeletePermissions, hasUpdatePermissions, isDownloadable, labelUpdate, restoreVersion: restoreVersion2, deleteVersion: deleteVersion2, click, compareVersion, hasPermission, get t() {
      return translate;
    }, get NcActionButton() {
      return NcActionButton;
    }, get NcActionLink() {
      return NcActionLink;
    }, get NcAvatar() {
      return NcAvatar;
    }, get NcDateTime() {
      return _sfc_main$7;
    }, get NcListItem() {
      return NcListItem;
    }, BackupRestore, FileCompare, ImageOffOutline, Pencil: PencilIcon, Delete, Download };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$3 = {
  key: 0,
  class: "version__image"
};
const _hoisted_2$1 = ["src"];
const _hoisted_3 = {
  key: 2,
  class: "version__image"
};
const _hoisted_4 = { class: "version__info" };
const _hoisted_5 = ["title"];
const _hoisted_6 = {
  key: 1,
  class: "version__info",
  "data-cy-files-version-author-name": ""
};
const _hoisted_7 = { key: 0 };
const _hoisted_8 = ["title"];
const _hoisted_9 = { class: "version__info version__info__subline" };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcListItem"], {
    class: "version",
    forceDisplayActions: true,
    "actions-aria-label": $setup.t("files_versions", "Actions for version from {versionHumanExplicitDate}", { versionHumanExplicitDate: $setup.versionHumanExplicitDate }),
    "data-files-versions-version": $props.version.fileVersion,
    href: $setup.downloadURL,
    onClick: $setup.click
  }, {
    icon: withCtx(() => [
      !($props.loadPreview || $setup.previewLoaded) ? (openBlock(), createElementBlock("div", _hoisted_1$3)) : $props.version.previewUrl && !$setup.previewErrored ? (openBlock(), createElementBlock("img", {
        key: 1,
        src: $props.version.previewUrl,
        alt: "",
        decoding: "async",
        fetchpriority: "low",
        loading: "lazy",
        class: "version__image",
        onLoad: _cache[0] || (_cache[0] = ($event) => $setup.previewLoaded = true),
        onError: _cache[1] || (_cache[1] = ($event) => $setup.previewErrored = true)
      }, null, 40, _hoisted_2$1)) : (openBlock(), createElementBlock("div", _hoisted_3, [
        createVNode($setup["ImageOffOutline"], { size: 20 })
      ]))
    ]),
    name: withCtx(() => [
      createBaseVNode("div", _hoisted_4, [
        $setup.versionLabel ? (openBlock(), createElementBlock("div", {
          key: 0,
          class: "version__info__label",
          "data-cy-files-version-label": "",
          title: $setup.versionLabel
        }, toDisplayString($setup.versionLabel), 9, _hoisted_5)) : createCommentVNode("v-if", true),
        $setup.versionAuthor ? (openBlock(), createElementBlock("div", _hoisted_6, [
          $setup.versionLabel ? (openBlock(), createElementBlock("span", _hoisted_7, "•")) : createCommentVNode("v-if", true),
          createVNode($setup["NcAvatar"], {
            class: "avatar",
            user: $props.version.author ?? void 0,
            size: 20,
            disableMenu: "",
            disableTooltip: "",
            hideStatus: ""
          }, null, 8, ["user"]),
          createBaseVNode("div", {
            class: "version__info__author_name",
            title: $setup.versionAuthor
          }, toDisplayString($setup.versionAuthor), 9, _hoisted_8)
        ])) : createCommentVNode("v-if", true)
      ])
    ]),
    subname: withCtx(() => [
      createBaseVNode("div", _hoisted_9, [
        createVNode($setup["NcDateTime"], {
          class: "version__info__date",
          relativeTime: "short",
          timestamp: $props.version.mtime
        }, null, 8, ["timestamp"]),
        createCommentVNode(" Separate dot to improve alignment "),
        _cache[2] || (_cache[2] = createBaseVNode(
          "span",
          null,
          "•",
          -1
          /* CACHED */
        )),
        createBaseVNode(
          "span",
          null,
          toDisplayString($setup.humanReadableSize),
          1
          /* TEXT */
        )
      ])
    ]),
    actions: withCtx(() => [
      $setup.enableLabeling && $setup.hasUpdatePermissions ? (openBlock(), createBlock($setup["NcActionButton"], {
        key: 0,
        "data-cy-files-versions-version-action": "label",
        closeAfterClick: true,
        onClick: $setup.labelUpdate
      }, {
        icon: withCtx(() => [
          createVNode($setup["Pencil"], { size: 22 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($props.version.label === "" ? $setup.t("files_versions", "Name this version") : $setup.t("files_versions", "Edit version name")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      !$props.isCurrent && $props.canView && $props.canCompare ? (openBlock(), createBlock($setup["NcActionButton"], {
        key: 1,
        "data-cy-files-versions-version-action": "compare",
        closeAfterClick: true,
        onClick: $setup.compareVersion
      }, {
        icon: withCtx(() => [
          createVNode($setup["FileCompare"], { size: 22 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("files_versions", "Compare to current version")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      !$props.isCurrent && $setup.hasUpdatePermissions ? (openBlock(), createBlock($setup["NcActionButton"], {
        key: 2,
        "data-cy-files-versions-version-action": "restore",
        closeAfterClick: true,
        onClick: $setup.restoreVersion
      }, {
        icon: withCtx(() => [
          createVNode($setup["BackupRestore"], { size: 22 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("files_versions", "Restore version")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      $setup.isDownloadable ? (openBlock(), createBlock($setup["NcActionLink"], {
        key: 3,
        "data-cy-files-versions-version-action": "download",
        href: $setup.downloadURL,
        closeAfterClick: true,
        download: $setup.downloadURL
      }, {
        icon: withCtx(() => [
          createVNode($setup["Download"], { size: 22 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("files_versions", "Download version")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["href", "download"])) : createCommentVNode("v-if", true),
      !$props.isCurrent && $setup.enableDeletion && $setup.hasDeletePermissions ? (openBlock(), createBlock($setup["NcActionButton"], {
        key: 4,
        "data-cy-files-versions-version-action": "delete",
        closeAfterClick: true,
        onClick: $setup.deleteVersion
      }, {
        icon: withCtx(() => [
          createVNode($setup["Delete"], { size: 22 })
        ]),
        default: withCtx(() => [
          createTextVNode(
            " " + toDisplayString($setup.t("files_versions", "Delete version")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true)
    ]),
    _: 1
    /* STABLE */
  }, 8, ["actions-aria-label", "data-files-versions-version", "href"]);
}
const VersionEntry = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-069aa31a"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_versions/src/components/VersionEntry.vue"]]);
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "VersionLabelDialog",
  props: {
    open: {
      type: Boolean,
      default: false
    },
    label: {
      type: String,
      default: ""
    }
  },
  emits: ["update:open", "update:label"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const props = __props;
    const emit2 = __emit;
    const labelInput = useTemplateRef("labelInput");
    const internalLabel = ref("");
    const dialogButtons = computed(() => {
      const buttons = [];
      if (props.label.trim() === "") {
        buttons.push({
          label: translate("files_versions", "Cancel")
        });
      } else {
        buttons.push({
          label: translate("files_versions", "Remove version name"),
          type: "reset",
          variant: "error",
          callback: () => {
            setVersionLabel2("");
          }
        });
      }
      return [
        ...buttons,
        {
          label: translate("files_versions", "Save version name"),
          icon: svgCheck,
          type: "submit",
          variant: "primary"
        }
      ];
    });
    watchEffect(() => {
      internalLabel.value = props.label ?? "";
    });
    watchEffect(() => {
      if (props.open) {
        nextTick(() => labelInput.value?.focus());
      }
      internalLabel.value = props.label;
    });
    function setVersionLabel2(label) {
      emit2("update:label", label);
    }
    const __returned__ = { props, emit: emit2, labelInput, internalLabel, dialogButtons, setVersionLabel: setVersionLabel2, get t() {
      return translate;
    }, get NcDialog() {
      return NcDialog;
    }, get NcTextField() {
      return _sfc_main$8;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$2 = { class: "version-label-modal__info" };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcDialog"], {
    buttons: $setup.dialogButtons,
    contentClasses: "version-label-modal",
    isForm: "",
    open: $props.open,
    size: "normal",
    name: $setup.t("files_versions", "Name this version"),
    "onUpdate:open": _cache[1] || (_cache[1] = ($event) => _ctx.$emit("update:open", $event)),
    onSubmit: _cache[2] || (_cache[2] = ($event) => $setup.setVersionLabel($setup.internalLabel))
  }, {
    default: withCtx(() => [
      createVNode($setup["NcTextField"], {
        ref: "labelInput",
        modelValue: $setup.internalLabel,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.internalLabel = $event),
        class: "version-label-modal__input",
        label: $setup.t("files_versions", "Version name"),
        placeholder: $setup.t("files_versions", "Version name")
      }, null, 8, ["modelValue", "label", "placeholder"]),
      createBaseVNode(
        "p",
        _hoisted_1$2,
        toDisplayString($setup.t("files_versions", "Named versions are persisted, and excluded from automatic cleanups when your storage quota is full.")),
        1
        /* TEXT */
      )
    ]),
    _: 1
    /* STABLE */
  }, 8, ["buttons", "open", "name"]);
}
const VersionLabelDialog = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-dcaf1b63"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_versions/src/components/VersionLabelDialog.vue"]]);
const logger = getLoggerBuilder().setApp("files_version").detectUser().build();
const _sfc_main$1 = defineComponent({
  name: "VirtualScrolling",
  props: {
    sections: {
      type: Array,
      required: true
    },
    containerElement: {
      type: HTMLElement,
      default: null
    },
    useWindow: {
      type: Boolean,
      default: false
    },
    headerHeight: {
      type: Number,
      default: 75
    },
    renderDistance: {
      type: Number,
      default: 0.5
    },
    bottomBufferRatio: {
      type: Number,
      default: 2
    },
    scrollToKey: {
      type: String,
      default: ""
    }
  },
  emits: ["needContent"],
  data() {
    return {
      scrollPosition: 0,
      containerHeight: 0,
      rowsContainerHeight: 0,
      resizeObserver: null
    };
  },
  computed: {
    visibleSections() {
      logger.debug("[VirtualScrolling] Computing visible section", { sections: this.sections });
      const containerHeight = this.containerHeight;
      const containerTop = this.scrollPosition;
      const containerBottom = containerTop + containerHeight;
      let currentRowTop = 0;
      let currentRowBottom = 0;
      const visibleSections = this.sections.map((section) => {
        currentRowBottom += this.headerHeight;
        return {
          ...section,
          rows: section.rows.reduce((visibleRows, row) => {
            currentRowTop = currentRowBottom;
            currentRowBottom += row.height;
            let distance = 0;
            if (currentRowBottom < containerTop) {
              distance = (containerTop - currentRowBottom) / containerHeight;
            } else if (currentRowTop > containerBottom) {
              distance = (currentRowTop - containerBottom) / containerHeight;
            }
            if (distance > this.renderDistance) {
              return visibleRows;
            }
            return [
              ...visibleRows,
              {
                ...row,
                distance
              }
            ];
          }, [])
        };
      }).filter((section) => section.rows.length > 0);
      const visibleItems = visibleSections.flatMap(({ rows }) => rows).flatMap(({ items }) => items);
      const rowIdToKeyMap = this._rowIdToKeyMap;
      visibleItems.forEach((item) => item.key = rowIdToKeyMap[item.id]);
      const usedTokens = visibleItems.map(({ key }) => key).filter((key) => key !== void 0);
      const unusedTokens = Object.values(rowIdToKeyMap).filter((key) => !usedTokens.includes(key));
      visibleItems.filter(({ key }) => key === void 0).forEach((item) => item.key = unusedTokens.pop() ?? Math.random().toString(36).substr(2));
      this._rowIdToKeyMap = visibleItems.reduce((finalMapping, { id, key }) => ({ ...finalMapping, [`${id}`]: key }), {});
      return visibleSections;
    },
    /**
     * Total height of all the rows + some room for the loader.
     */
    totalHeight() {
      const loaderHeight = 0;
      return this.sections.map((section) => this.headerHeight + section.height).reduce((totalHeight, sectionHeight) => totalHeight + sectionHeight, 0) + loaderHeight;
    },
    paddingTop() {
      if (this.visibleSections.length === 0) {
        return 0;
      }
      let paddingTop = 0;
      for (const section of this.sections) {
        if (section.key !== this.visibleSections[0].rows[0].sectionKey) {
          paddingTop += this.headerHeight + section.height;
          continue;
        }
        for (const row of section.rows) {
          if (row.key === this.visibleSections[0].rows[0].key) {
            return paddingTop;
          }
          paddingTop += row.height;
        }
        paddingTop += this.headerHeight;
      }
      return paddingTop;
    },
    /**
     * padding-top is used to replace not included item in the container.
     */
    rowsContainerStyle() {
      return {
        height: `${this.totalHeight}px`,
        paddingTop: `${this.paddingTop}px`
      };
    },
    /**
     * Whether the user is near the bottom.
     * If true, then the needContent event will be emitted.
     */
    isNearBottom() {
      const buffer = this.containerHeight * this.bottomBufferRatio;
      return this.scrollPosition + this.containerHeight >= this.totalHeight - buffer;
    },
    container() {
      logger.debug("[VirtualScrolling] Computing container");
      if (this.containerElement !== null) {
        return this.containerElement;
      } else if (this.useWindow) {
        return window;
      } else {
        return this.$refs.container;
      }
    }
  },
  watch: {
    isNearBottom(value) {
      logger.debug("[VirtualScrolling] isNearBottom changed", { value });
      if (value) {
        this.$emit("needContent");
      }
    },
    visibleSections() {
      if (this.isNearBottom) {
        this.$emit("needContent");
      }
    },
    scrollToKey(key) {
      let currentRowTopDistanceFromTop = 0;
      for (const section of this.sections) {
        if (section.key !== key) {
          currentRowTopDistanceFromTop += this.headerHeight + section.height;
          continue;
        }
        break;
      }
      logger.debug("[VirtualScrolling] Scrolling to", { currentRowTopDistanceFromTop });
      this.container.scrollTo({ top: currentRowTopDistanceFromTop, behavior: "smooth" });
    }
  },
  beforeCreate() {
    this._rowIdToKeyMap = {};
  },
  mounted() {
    this.resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        const cr = entry.contentRect;
        if (entry.target === this.container) {
          this.containerHeight = cr.height;
        }
        if (entry.target.classList.contains("vs-rows-container")) {
          this.rowsContainerHeight = cr.height;
        }
      }
    });
    if (this.useWindow) {
      window.addEventListener("resize", this.updateContainerSize, { passive: true });
      this.containerHeight = window.innerHeight;
    } else {
      this.resizeObserver.observe(this.container);
    }
    this.resizeObserver.observe(this.$refs.rowsContainer);
    this.container.addEventListener("scroll", this.updateScrollPosition, { passive: true });
  },
  beforeUnmount() {
    if (this.useWindow) {
      window.removeEventListener("resize", this.updateContainerSize);
    }
    this.resizeObserver?.disconnect();
    this.container.removeEventListener("scroll", this.updateScrollPosition);
  },
  methods: {
    updateScrollPosition() {
      this._onScrollHandle ??= requestAnimationFrame(() => {
        this._onScrollHandle = null;
        if (this.useWindow) {
          this.scrollPosition = this.container.scrollY;
        } else {
          this.scrollPosition = this.container.scrollTop;
        }
      });
    },
    updateContainerSize() {
      this.containerHeight = window.innerHeight;
    }
  }
});
const _hoisted_1$1 = {
  key: 0,
  ref: "container",
  class: "vs-container"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return !_ctx.useWindow && _ctx.containerElement === null ? (openBlock(), createElementBlock(
    "div",
    _hoisted_1$1,
    [
      createBaseVNode(
        "div",
        {
          ref: "rowsContainer",
          class: "vs-rows-container",
          style: normalizeStyle(_ctx.rowsContainerStyle)
        },
        [
          renderSlot(_ctx.$slots, "default", { visibleSections: _ctx.visibleSections }, void 0, true),
          renderSlot(_ctx.$slots, "loader", {}, void 0, true)
        ],
        4
        /* STYLE */
      )
    ],
    512
    /* NEED_PATCH */
  )) : (openBlock(), createElementBlock(
    "div",
    {
      key: 1,
      ref: "rowsContainer",
      class: "vs-rows-container",
      style: normalizeStyle(_ctx.rowsContainerStyle)
    },
    [
      renderSlot(_ctx.$slots, "default", { visibleSections: _ctx.visibleSections }, void 0, true),
      renderSlot(_ctx.$slots, "loader", {}, void 0, true)
    ],
    4
    /* STYLE */
  ));
}
const VirtualScrolling = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-35fd1375"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_versions/src/components/VirtualScrolling.vue"]]);
const davRequest = `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:"
	xmlns:oc="http://owncloud.org/ns"
	xmlns:nc="http://nextcloud.org/ns"
	xmlns:ocs="http://open-collaboration-services.org/ns">
	<d:prop>
		<d:getcontentlength />
		<d:getcontenttype />
		<d:getlastmodified />
		<d:getetag />
		<nc:version-label />
		<nc:version-author />
		<nc:has-preview />
	</d:prop>
</d:propfind>`;
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const client = getClient();
async function fetchVersions(node) {
  const path = `/versions/${getCurrentUser()?.uid}/versions/${node.fileid}`;
  try {
    const response = await client.getDirectoryContents(path, {
      data: davRequest,
      details: true
    });
    const versions = response.data.filter(({ mime }) => mime !== "").map((version) => formatVersion(version, node));
    const authorIds = new Set(versions.map((version) => String(version.author)));
    const authors = await cancelableClient.post(generateUrl("/displaynames"), { users: [...authorIds] });
    for (const version of versions) {
      const author = authors.data.users[version.author ?? ""];
      if (author) {
        version.authorName = author;
      }
    }
    return versions;
  } catch (exception) {
    logger.error("Could not fetch version", { exception });
    throw exception;
  }
}
async function restoreVersion(version) {
  try {
    logger.debug("Restoring version", { url: version.url });
    await client.moveFile(
      `/versions/${getCurrentUser()?.uid}/versions/${version.fileId}/${version.fileVersion}`,
      `/versions/${getCurrentUser()?.uid}/restore/target`
    );
  } catch (exception) {
    logger.error("Could not restore version", { exception });
    throw exception;
  }
}
function formatVersion(version, node) {
  const mtime = Date.parse(version.lastmod);
  let previewUrl;
  if (mtime === node.mtime?.getTime()) {
    previewUrl = generateUrl("/core/preview?fileId={fileId}&c={fileEtag}&x=250&y=250&forceIcon=0&a=0&forceIcon=1&mimeFallback=1", {
      fileId: node.id,
      fileEtag: node.attributes.etag
    });
  } else {
    previewUrl = generateUrl("/apps/files_versions/preview?file={file}&version={fileVersion}&mimeFallback=1", {
      file: node.path,
      fileVersion: version.basename
    });
  }
  return {
    fileId: node.id,
    // If version-label is defined make sure it is a string (prevent issue if the label is a number an PHP returns a number then)
    label: version.props["version-label"] ? String(version.props["version-label"]) : "",
    author: version.props["version-author"] ? String(version.props["version-author"]) : null,
    authorName: null,
    filename: version.filename,
    basename: new Date(mtime).toLocaleString(
      [getCanonicalLocale(), getCanonicalLocale().split("-")[0]],
      {
        timeStyle: "long",
        dateStyle: "medium"
      }
    ),
    mime: version.mime,
    etag: `${version.props.getetag}`,
    size: version.size,
    type: version.type,
    mtime,
    permissions: "R",
    previewUrl,
    url: join("/remote.php/dav", version.filename),
    source: generateRemoteUrl("dav") + encodePath(version.filename),
    fileVersion: version.basename
  };
}
async function setVersionLabel(version, newLabel) {
  return await client.customRequest(
    version.filename,
    {
      method: "PROPPATCH",
      data: `<?xml version="1.0"?>
					<d:propertyupdate xmlns:d="DAV:"
						xmlns:oc="http://owncloud.org/ns"
						xmlns:nc="http://nextcloud.org/ns"
						xmlns:ocs="http://open-collaboration-services.org/ns">
					<d:set>
						<d:prop>
							<nc:version-label>${newLabel}</nc:version-label>
						</d:prop>
					</d:set>
					</d:propertyupdate>`
    }
  );
}
async function deleteVersion(version) {
  await client.deleteFile(version.filename);
}
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "FilesVersionsSidebarTab",
  props: {
    active: { type: Boolean, required: true },
    node: { type: null, required: true },
    folder: { type: null, required: true },
    view: { type: null, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const isMobile = useIsMobile();
    const versions = ref([]);
    const loading = ref(false);
    const showVersionLabelForm = ref(false);
    const editedVersion = ref(null);
    watch(toRef(() => props.node), async () => {
      if (!props.node) {
        return;
      }
      try {
        loading.value = true;
        versions.value = await fetchVersions(props.node);
      } finally {
        loading.value = false;
      }
    }, { immediate: true });
    const currentVersionMtime = computed(() => props.node?.mtime?.getTime() ?? 0);
    const orderedVersions = computed(() => {
      return [...versions.value].sort((a, b) => {
        if (!props.node) {
          return 0;
        }
        if (a.mtime === props.node.mtime?.getTime()) {
          return -1;
        } else if (b.mtime === props.node.mtime?.getTime()) {
          return 1;
        } else {
          return b.mtime - a.mtime;
        }
      });
    });
    const sections = computed(() => {
      const rows = orderedVersions.value.map((version) => ({
        key: version.mtime.toString(),
        height: 68,
        sectionKey: "versions",
        items: [{ id: version.mtime.toString(), version }]
      }));
      return [{ key: "versions", rows, height: 68 * orderedVersions.value.length }];
    });
    const initialVersionMtime = computed(() => {
      return versions.value.map((version) => version.mtime).reduce((a, b) => Math.min(a, b));
    });
    const canView = computed(() => {
      if (!props.node) {
        return false;
      }
      return window.OCA.Viewer?.mimetypes?.includes(props.node?.mime);
    });
    const canCompare = computed(() => {
      return !isMobile.value && window.OCA.Viewer?.mimetypesCompare?.includes(props.node?.mime);
    });
    async function handleRestore(version) {
      if (!props.node) {
        return;
      }
      const restoredNode = props.node.clone();
      restoredNode.attributes.etag = version.etag;
      restoredNode.size = version.size;
      restoredNode.mtime = new Date(version.mtime);
      const restoreStartedEventState = {
        preventDefault: false,
        node: restoredNode,
        version
      };
      emit("files_versions:restore:requested", restoreStartedEventState);
      if (restoreStartedEventState.preventDefault) {
        return;
      }
      try {
        await restoreVersion(version);
        if (version.label) {
          showSuccess(translate("files_versions", `${version.label} restored`));
        } else if (version.mtime === initialVersionMtime.value) {
          showSuccess(translate("files_versions", "Initial version restored"));
        } else {
          showSuccess(translate("files_versions", "Version restored"));
        }
        emit("files:node:updated", restoredNode);
        emit("files_versions:restore:restored", { node: restoredNode, version });
      } catch {
        showError(translate("files_versions", "Could not restore version"));
        emit("files_versions:restore:failed", version);
      }
    }
    function handleLabelUpdateRequest(version) {
      showVersionLabelForm.value = true;
      editedVersion.value = version;
    }
    async function handleLabelUpdate(newLabel) {
      if (editedVersion.value === null) {
        throw new Error("editedVersion should be set at that point");
      }
      const oldLabel = editedVersion.value.label;
      editedVersion.value.label = newLabel;
      showVersionLabelForm.value = false;
      try {
        await setVersionLabel(editedVersion.value, newLabel);
        editedVersion.value = null;
      } catch (exception) {
        editedVersion.value.label = oldLabel;
        showError(translate("files_versions", "Could not set version label"));
        logger.error("Could not set version label", { exception });
      }
    }
    async function handleDelete(version) {
      const index = versions.value.indexOf(version);
      versions.value.splice(index, 1);
      try {
        await deleteVersion(version);
      } catch {
        versions.value.push(version);
        showError(translate("files_versions", "Could not delete version"));
      }
    }
    function openVersion(version) {
      if (props.node === null) {
        return;
      }
      if (version.mtime === props.node?.mtime?.getTime()) {
        window.OCA.Viewer.open({ path: props.node.path });
        return;
      }
      window.OCA.Viewer.open({
        fileInfo: {
          ...version,
          // Versions previews are too small for our use case, so we override previewUrl
          // to either point to the original file or original version.
          filename: version.filename,
          previewUrl: void 0
        },
        enableSidebar: false
      });
    }
    function compareVersion(version) {
      const _versions = versions.value.map((version2) => ({ ...version2, previewUrl: void 0 }));
      window.OCA.Viewer.compare(
        { path: props.node.path },
        _versions.find((v) => v.source === version.source)
      );
    }
    const __returned__ = { props, isMobile, versions, loading, showVersionLabelForm, editedVersion, currentVersionMtime, orderedVersions, sections, initialVersionMtime, canView, canCompare, handleRestore, handleLabelUpdateRequest, handleLabelUpdate, handleDelete, openVersion, compareVersion, get t() {
      return translate;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, VersionEntry, VersionLabelDialog, VirtualScrolling };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1 = {
  key: 0,
  class: "versions-tab__container"
};
const _hoisted_2 = ["aria-label"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return $props.node ? (openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode($setup["VirtualScrolling"], {
      sections: $setup.sections,
      headerHeight: 0
    }, {
      default: withCtx(({ visibleSections }) => [
        createBaseVNode("ul", {
          "aria-label": $setup.t("files_versions", "File versions"),
          "data-files-versions-versions-list": ""
        }, [
          visibleSections.length === 1 ? (openBlock(true), createElementBlock(
            Fragment,
            { key: 0 },
            renderList(visibleSections[0].rows, (row) => {
              return openBlock(), createBlock($setup["VersionEntry"], {
                key: row.items[0].version.mtime,
                canView: $setup.canView,
                canCompare: $setup.canCompare,
                loadPreview: $props.active,
                version: row.items[0].version,
                node: $props.node,
                isCurrent: row.items[0].version.mtime === $setup.currentVersionMtime,
                isFirstVersion: row.items[0].version.mtime === $setup.initialVersionMtime,
                onClick: $setup.openVersion,
                onCompare: $setup.compareVersion,
                onRestore: $setup.handleRestore,
                onLabelUpdateRequest: ($event) => $setup.handleLabelUpdateRequest(row.items[0].version),
                onDelete: $setup.handleDelete
              }, null, 8, ["canView", "canCompare", "loadPreview", "version", "node", "isCurrent", "isFirstVersion", "onLabelUpdateRequest"]);
            }),
            128
            /* KEYED_FRAGMENT */
          )) : createCommentVNode("v-if", true)
        ], 8, _hoisted_2)
      ]),
      loader: withCtx(() => [
        $setup.loading ? (openBlock(), createBlock($setup["NcLoadingIcon"], {
          key: 0,
          class: "files-list-viewer__loader"
        })) : createCommentVNode("v-if", true)
      ]),
      _: 1
      /* STABLE */
    }, 8, ["sections"]),
    $setup.editedVersion ? (openBlock(), createBlock($setup["VersionLabelDialog"], {
      key: 0,
      open: $setup.showVersionLabelForm,
      "onUpdate:open": _cache[0] || (_cache[0] = ($event) => $setup.showVersionLabelForm = $event),
      label: $setup.editedVersion.label,
      "onUpdate:label": $setup.handleLabelUpdate
    }, null, 8, ["open", "label"])) : createCommentVNode("v-if", true)
  ])) : createCommentVNode("v-if", true);
}
const FilesVersionsSidebarTab = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_versions/src/views/FilesVersionsSidebarTab.vue"]]);
export {
  FilesVersionsSidebarTab as default
};
//# sourceMappingURL=FilesVersionsSidebarTab-B6rMR4ZT.chunk.mjs.map
