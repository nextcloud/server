const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, l as useTemplateRef, z as watch, n as computed, y as ref, o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, x as createVNode, w as withCtx, j as createTextVNode, c as createBlock, F as Fragment, h as createCommentVNode, M as withModifiers, Q as onBeforeMount, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { c as showSuccess, a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import { N as NcSelectTags } from "./NcSelectTags-CTHyuMcq-2HejGZhj.chunk.mjs";
import { _ as _sfc_main$3 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { c as createTag, d as defaultBaseTag, u as updateTag, a as deleteTag, b as updateSystemTagsAdminRestriction, l as logger, f as fetchTags } from "./api-Bqdmju2E.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./index-Dl6U1WCt.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./dav-DGipjjQH.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
import "./public-CKeAb98h.chunk.mjs";
var TagLevel = /* @__PURE__ */ ((TagLevel2) => {
  TagLevel2["Public"] = "Public";
  TagLevel2["Restricted"] = "Restricted";
  TagLevel2["Invisible"] = "Invisible";
  return TagLevel2;
})(TagLevel || {});
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "SystemTagForm",
  props: {
    tags: { type: Array, required: true }
  },
  emits: ["tag:created", "tag:updated", "tag:deleted"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const props = __props;
    const emit = __emit;
    const tagLevelOptions = [
      {
        id: "Public",
        label: translate("systemtags", "Public")
      },
      {
        id: "Restricted",
        label: translate("systemtags", "Restricted")
      },
      {
        id: "Invisible",
        label: translate("systemtags", "Invisible")
      }
    ];
    const tagNameInputElement = useTemplateRef("tagNameInput");
    const loading = ref(false);
    const errorMessage = ref("");
    const tagName = ref("");
    const tagLevel = ref(
      "Public"
      /* Public */
    );
    const selectedTag = ref(null);
    watch(selectedTag, (tag) => {
      tagName.value = tag ? tag.displayName : "";
      tagLevel.value = tag ? getTagLevel(tag.userVisible, tag.userAssignable) : "Public";
    });
    const isCreating = computed(() => selectedTag.value === null);
    const isCreateDisabled = computed(() => tagName.value === "");
    const isUpdateDisabled = computed(() => tagName.value === "" || selectedTag.value?.displayName === tagName.value && getTagLevel(selectedTag.value?.userVisible, selectedTag.value?.userAssignable) === tagLevel.value);
    const isResetDisabled = computed(() => {
      if (isCreating.value) {
        return tagName.value === "" && tagLevel.value === "Public";
      }
      return selectedTag.value === null;
    });
    const userVisible = computed(() => {
      const matchLevel = {
        [
          "Public"
          /* Public */
        ]: true,
        [
          "Restricted"
          /* Restricted */
        ]: true,
        [
          "Invisible"
          /* Invisible */
        ]: false
      };
      return matchLevel[tagLevel.value];
    });
    const userAssignable = computed(() => {
      const matchLevel = {
        [
          "Public"
          /* Public */
        ]: true,
        [
          "Restricted"
          /* Restricted */
        ]: false,
        [
          "Invisible"
          /* Invisible */
        ]: false
      };
      return matchLevel[tagLevel.value];
    });
    const tagProperties = computed(() => {
      return {
        displayName: tagName.value,
        userVisible: userVisible.value,
        userAssignable: userAssignable.value
      };
    });
    function onSelectTag(tagId) {
      const tag = props.tags.find((search) => search.id === tagId) || null;
      selectedTag.value = tag;
    }
    async function handleSubmit() {
      if (isCreating.value) {
        await create();
        return;
      }
      await update();
    }
    async function create() {
      const tag = { ...defaultBaseTag, ...tagProperties.value };
      loading.value = true;
      try {
        const id = await createTag(tag);
        const createdTag = { ...tag, id };
        emit("tag:created", createdTag);
        showSuccess(translate("systemtags", "Created tag"));
        reset();
      } catch {
        errorMessage.value = translate("systemtags", "Failed to create tag");
      }
      loading.value = false;
    }
    async function update() {
      if (selectedTag.value === null) {
        return;
      }
      const tag = { ...selectedTag.value, ...tagProperties.value };
      loading.value = true;
      try {
        await updateTag(tag);
        selectedTag.value = tag;
        emit("tag:updated", tag);
        showSuccess(translate("systemtags", "Updated tag"));
        tagNameInputElement.value?.focus();
      } catch {
        errorMessage.value = translate("systemtags", "Failed to update tag");
      }
      loading.value = false;
    }
    async function handleDelete() {
      if (selectedTag.value === null) {
        return;
      }
      loading.value = true;
      try {
        await deleteTag(selectedTag.value);
        emit("tag:deleted", selectedTag.value);
        showSuccess(translate("systemtags", "Deleted tag"));
        reset();
      } catch {
        errorMessage.value = translate("systemtags", "Failed to delete tag");
      }
      loading.value = false;
    }
    function reset() {
      selectedTag.value = null;
      errorMessage.value = "";
      tagName.value = "";
      tagLevel.value = "Public";
      tagNameInputElement.value?.focus();
    }
    function getTagLevel(userVisible2, userAssignable2) {
      const matchLevel = {
        [[true, true].join(",")]: "Public",
        [[true, false].join(",")]: "Restricted",
        [[false, false].join(",")]: "Invisible"
        /* Invisible */
      };
      return matchLevel[[userVisible2, userAssignable2].join(",")];
    }
    const __returned__ = { props, emit, TagLevel, tagLevelOptions, tagNameInputElement, loading, errorMessage, tagName, tagLevel, selectedTag, isCreating, isCreateDisabled, isUpdateDisabled, isResetDisabled, userVisible, userAssignable, tagProperties, onSelectTag, handleSubmit, create, update, handleDelete, reset, getTagLevel, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, get NcSelect() {
      return NcSelect;
    }, get NcSelectTags() {
      return NcSelectTags;
    }, get NcTextField() {
      return _sfc_main$3;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1$1 = ["disabled"];
const _hoisted_2$1 = { id: "system-tag-form-heading" };
const _hoisted_3$1 = { class: "system-tag-form__group" };
const _hoisted_4 = { for: "system-tags-input" };
const _hoisted_5 = { class: "system-tag-form__group" };
const _hoisted_6 = { for: "system-tag-name" };
const _hoisted_7 = { class: "system-tag-form__group" };
const _hoisted_8 = { for: "system-tag-level" };
const _hoisted_9 = { class: "system-tag-form__row" };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("form", {
    class: "system-tag-form",
    disabled: $setup.loading,
    "aria-labelledby": "system-tag-form-heading",
    onSubmit: withModifiers($setup.handleSubmit, ["prevent"]),
    onReset: $setup.reset
  }, [
    createBaseVNode(
      "h4",
      _hoisted_2$1,
      toDisplayString($setup.t("systemtags", "Create or edit tags")),
      1
      /* TEXT */
    ),
    createBaseVNode("div", _hoisted_3$1, [
      createBaseVNode(
        "label",
        _hoisted_4,
        toDisplayString($setup.t("systemtags", "Search for a tag to edit")),
        1
        /* TEXT */
      ),
      createVNode($setup["NcSelectTags"], {
        modelValue: $setup.selectedTag,
        inputId: "system-tags-input",
        placeholder: $setup.t("systemtags", "Collaborative tags …"),
        fetchTags: false,
        options: $props.tags,
        multiple: false,
        labelOutside: "",
        "onUpdate:modelValue": $setup.onSelectTag
      }, {
        "no-options": withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("systemtags", "No tags to select")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["modelValue", "placeholder", "options"])
    ]),
    createBaseVNode("div", _hoisted_5, [
      createBaseVNode(
        "label",
        _hoisted_6,
        toDisplayString($setup.t("systemtags", "Tag name")),
        1
        /* TEXT */
      ),
      createVNode($setup["NcTextField"], {
        id: "system-tag-name",
        ref: "tagNameInput",
        modelValue: $setup.tagName,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.tagName = $event),
        error: Boolean($setup.errorMessage),
        helperText: $setup.errorMessage,
        labelOutside: ""
      }, null, 8, ["modelValue", "error", "helperText"])
    ]),
    createBaseVNode("div", _hoisted_7, [
      createBaseVNode(
        "label",
        _hoisted_8,
        toDisplayString($setup.t("systemtags", "Tag level")),
        1
        /* TEXT */
      ),
      createVNode($setup["NcSelect"], {
        modelValue: $setup.tagLevel,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.tagLevel = $event),
        inputId: "system-tag-level",
        options: $setup.tagLevelOptions,
        reduce: (level) => level.id,
        clearable: false,
        disabled: $setup.loading,
        labelOutside: ""
      }, null, 8, ["modelValue", "reduce", "disabled"])
    ]),
    createBaseVNode("div", _hoisted_9, [
      $setup.isCreating ? (openBlock(), createBlock($setup["NcButton"], {
        key: 0,
        type: "submit",
        disabled: $setup.isCreateDisabled || $setup.loading
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("systemtags", "Create")),
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
          createVNode($setup["NcButton"], {
            type: "submit",
            disabled: $setup.isUpdateDisabled || $setup.loading
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("systemtags", "Update")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["disabled"]),
          createVNode($setup["NcButton"], {
            disabled: $setup.loading,
            onClick: $setup.handleDelete
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("systemtags", "Delete")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }, 8, ["disabled"])
        ],
        64
        /* STABLE_FRAGMENT */
      )),
      createVNode($setup["NcButton"], {
        type: "reset",
        disabled: $setup.isResetDisabled || $setup.loading
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("systemtags", "Reset")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"]),
      $setup.loading ? (openBlock(), createBlock($setup["NcLoadingIcon"], {
        key: 2,
        name: $setup.t("systemtags", "Loading …"),
        size: 32
      }, null, 8, ["name"])) : createCommentVNode("v-if", true)
    ])
  ], 40, _hoisted_1$1);
}
const SystemTagForm = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-c66aab93"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/systemtags/src/components/SystemTagForm.vue"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "SystemTagsCreationControl",
  setup(__props, { expose: __expose }) {
    __expose();
    const systemTagsCreationRestrictedToAdmin = ref(loadState("systemtags", "restrictSystemTagsCreationToAdmin", false));
    async function updateSystemTagsDefault(isRestricted) {
      try {
        const responseData = await updateSystemTagsAdminRestriction(isRestricted);
        logger.debug("updateSystemTagsDefault", { responseData });
        handleResponse({
          isRestricted,
          status: responseData.ocs?.meta?.status
        });
      } catch (e) {
        handleResponse({
          errorMessage: translate("systemtags", "Unable to update setting"),
          error: e
        });
      }
    }
    function handleResponse({ isRestricted, status, errorMessage, error }) {
      if (status === "ok") {
        systemTagsCreationRestrictedToAdmin.value = !!isRestricted;
        showSuccess(isRestricted ? translate("systemtags", "System tag creation is now restricted to administrators") : translate("systemtags", "System tag creation is now allowed for everybody"));
        return;
      }
      if (errorMessage) {
        showError(errorMessage);
        logger.error(errorMessage, { error });
      }
    }
    const __returned__ = { systemTagsCreationRestrictedToAdmin, updateSystemTagsDefault, handleResponse, get t() {
      return translate;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
const _hoisted_1 = { id: "system-tags-creation-control" };
const _hoisted_2 = { class: "inlineblock" };
const _hoisted_3 = { class: "settings-hint" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode(
      "h4",
      _hoisted_2,
      toDisplayString($setup.t("systemtags", "System tag management")),
      1
      /* TEXT */
    ),
    createBaseVNode(
      "p",
      _hoisted_3,
      toDisplayString($setup.t("systemtags", "If enabled, only administrators can create and edit tags. Accounts can still assign and remove them from files.")),
      1
      /* TEXT */
    ),
    createVNode($setup["NcCheckboxRadioSwitch"], {
      modelValue: $setup.systemTagsCreationRestrictedToAdmin,
      "onUpdate:modelValue": [
        _cache[0] || (_cache[0] = ($event) => $setup.systemTagsCreationRestrictedToAdmin = $event),
        $setup.updateSystemTagsDefault
      ],
      type: "switch"
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("systemtags", "Restrict tag creation and editing to administrators")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["modelValue"])
  ]);
}
const SystemTagsCreationControl = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/systemtags/src/components/SystemTagsCreationControl.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "SystemTagsSection",
  setup(__props, { expose: __expose }) {
    __expose();
    const loadingTags = ref(false);
    const tags = ref([]);
    onBeforeMount(async () => {
      loadingTags.value = true;
      try {
        tags.value = await fetchTags();
      } catch (error) {
        showError(translate("systemtags", "Failed to load tags"));
        logger.error("Failed to load tags", { error });
      }
      loadingTags.value = false;
    });
    function handleCreate(tag) {
      tags.value.unshift(tag);
    }
    function handleUpdate(tag) {
      const tagIndex = tags.value.findIndex((currTag) => currTag.id === tag.id);
      tags.value.splice(tagIndex, 1);
      tags.value.unshift(tag);
    }
    function handleDelete(tag) {
      const tagIndex = tags.value.findIndex((currTag) => currTag.id === tag.id);
      tags.value.splice(tagIndex, 1);
    }
    const __returned__ = { loadingTags, tags, handleCreate, handleUpdate, handleDelete, get t() {
      return translate;
    }, get NcLoadingIcon() {
      return NcLoadingIcon;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, SystemTagForm, SystemTagsCreationControl };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createBlock($setup["NcSettingsSection"], {
    name: $setup.t("systemtags", "Collaborative tags"),
    description: $setup.t("systemtags", "Collaborative tags are available for all users. Restricted tags are visible to users but cannot be assigned by them. Invisible tags are for internal use, since users cannot see or assign them.")
  }, {
    default: withCtx(() => [
      createVNode($setup["SystemTagsCreationControl"]),
      $setup.loadingTags ? (openBlock(), createBlock($setup["NcLoadingIcon"], {
        key: 0,
        name: $setup.t("systemtags", "Loading collaborative tags …"),
        size: 32
      }, null, 8, ["name"])) : (openBlock(), createBlock($setup["SystemTagForm"], {
        key: 1,
        tags: $setup.tags,
        "onTag:created": $setup.handleCreate,
        "onTag:updated": $setup.handleUpdate,
        "onTag:deleted": $setup.handleDelete
      }, null, 8, ["tags"]))
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name", "description"]);
}
const SystemTagsSection = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/systemtags/src/views/SystemTagsSection.vue"]]);
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const app = createApp(SystemTagsSection);
app.mount("#vue-admin-systemtags");
//# sourceMappingURL=systemtags-admin.mjs.map
