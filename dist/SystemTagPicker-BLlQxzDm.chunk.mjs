const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { f as emit, a as getCurrentUser, d as debounce } from "./index-rAufP352.chunk.mjs";
import { a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { t as translate, g as getLanguage, e as escapeHTML, p as purify, a as translatePlural } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps, b as defineComponent, r as resolveComponent, c as createBlock, w as withCtx, F as Fragment, x as createVNode, C as renderList, N as normalizeStyle, j as createTextVNode, E as withDirectives, G as vShow, v as normalizeClass } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { P as PlusIcon, N as NcChip } from "./Plus-DuSPdibD.chunk.mjs";
import NcColorPicker from "./index-DD39fp6M.chunk.mjs";
import { N as NcDialog } from "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import { N as NcEmptyContent } from "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import { N as NcLoadingIcon } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { N as NcNoteCard } from "./mdi-BGU2G5q5.chunk.mjs";
import { _ as _sfc_main$5 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { P as PencilIcon } from "./PencilOutline-BMYBdzdS.chunk.mjs";
import { l as logger, g as getTagObjects, s as setTagObjects, e as getNodeSystemTags, h as setNodeSystemTags, c as createTag, i as fetchTag, f as fetchTags, u as updateTag } from "./api-Bqdmju2E.chunk.mjs";
import { e as elementColor, i as invertTextColor, a as isDarkModeEnabled } from "./systemtags-init.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./index-Dl6U1WCt.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./dav-DGipjjQH.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
import "./public-CKeAb98h.chunk.mjs";
import "./index-DCPyCjGS.chunk.mjs";
const _sfc_main$4 = {
  name: "CheckCircleIcon",
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
const _hoisted_3$4 = { d: "M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" };
const _hoisted_4$4 = { key: 0 };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon check-circle-icon",
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
          _hoisted_4$4,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$4))
  ], 16, _hoisted_1$4);
}
const CheckIcon = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/CheckCircle.vue"]]);
const _sfc_main$3 = {
  name: "CircleIcon",
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
const _hoisted_1$3 = ["aria-hidden", "aria-label"];
const _hoisted_2$3 = ["fill", "width", "height"];
const _hoisted_3$3 = { d: "M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" };
const _hoisted_4$3 = { key: 0 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon circle-icon",
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
    ], 8, _hoisted_2$3))
  ], 16, _hoisted_1$3);
}
const CircleIcon = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Circle.vue"]]);
const _sfc_main$2 = {
  name: "CircleOutlineIcon",
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
const _hoisted_1$2 = ["aria-hidden", "aria-label"];
const _hoisted_2$2 = ["fill", "width", "height"];
const _hoisted_3$2 = { d: "M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" };
const _hoisted_4$2 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon circle-outline-icon",
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
    ], 8, _hoisted_2$2))
  ], 16, _hoisted_1$2);
}
const CircleOutlineIcon = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/CircleOutline.vue"]]);
const _sfc_main$1 = {
  name: "TagOutlineIcon",
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
const _hoisted_3$1 = { d: "M21.41 11.58L12.41 2.58A2 2 0 0 0 11 2H4A2 2 0 0 0 2 4V11A2 2 0 0 0 2.59 12.42L11.59 21.42A2 2 0 0 0 13 22A2 2 0 0 0 14.41 21.41L21.41 14.41A2 2 0 0 0 22 13A2 2 0 0 0 21.41 11.58M13 20L4 11V4H11L20 13M6.5 5A1.5 1.5 0 1 1 5 6.5A1.5 1.5 0 0 1 6.5 5Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon tag-outline-icon",
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
const TagIcon = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/TagOutline.vue"]]);
const debounceUpdateTag = debounce(updateTag, 500);
const mainBackgroundColor = getComputedStyle(document.body).getPropertyValue("--color-main-background").replace("#", "") || (isDarkModeEnabled() ? "000000" : "ffffff");
var Status = /* @__PURE__ */ ((Status2) => {
  Status2["BASE"] = "base";
  Status2["LOADING"] = "loading";
  Status2["CREATING_TAG"] = "creating-tag";
  Status2["DONE"] = "done";
  return Status2;
})(Status || {});
const restrictSystemTagsCreationToAdmin = loadState("systemtags", "restrictSystemTagsCreationToAdmin", false);
const _sfc_main = defineComponent({
  name: "SystemTagPicker",
  components: {
    CheckIcon,
    CircleIcon,
    CircleOutlineIcon,
    NcButton,
    NcCheckboxRadioSwitch,
    NcChip,
    NcColorPicker,
    NcDialog,
    NcEmptyContent,
    NcLoadingIcon,
    NcNoteCard,
    NcTextField: _sfc_main$5,
    PencilIcon,
    PlusIcon,
    TagIcon
  },
  props: {
    nodes: {
      type: Array,
      required: true
    }
  },
  emits: {
    close(status) {
      return status === null || typeof status === "boolean";
    }
  },
  setup() {
    return {
      emit,
      Status,
      t: translate,
      // Either tag creation is not restricted to admins or the current user is an admin
      canEditOrCreateTag: !restrictSystemTagsCreationToAdmin || getCurrentUser()?.isAdmin
    };
  },
  data() {
    return {
      status: "base",
      opened: true,
      openedPicker: false,
      input: "",
      tags: [],
      tagList: {},
      toAdd: [],
      toRemove: []
    };
  },
  computed: {
    sortedTags() {
      return [...this.tags].sort((a, b) => a.displayName.localeCompare(b.displayName, getLanguage(), { ignorePunctuation: true }));
    },
    filteredTags() {
      if (this.input.trim() === "") {
        return this.sortedTags;
      }
      return this.sortedTags.filter((tag) => tag.displayName.normalize().toLowerCase().includes(this.input.normalize().toLowerCase()));
    },
    hasChanges() {
      return this.toAdd.length > 0 || this.toRemove.length > 0;
    },
    canCreateTag() {
      return this.input.trim() !== "" && !this.tags.some((tag) => tag.displayName.trim().toLocaleLowerCase() === this.input.trim().toLocaleLowerCase());
    },
    statusMessage() {
      if (this.toAdd.length === 0 && this.toRemove.length === 0) {
        return "";
      }
      if (this.toAdd.length === 1 && this.toRemove.length === 1) {
        return translatePlural(
          "systemtags",
          "{tag1} will be set and {tag2} will be removed from 1 file.",
          "{tag1} will be set and {tag2} will be removed from {count} files.",
          this.nodes.length,
          {
            tag1: this.formatTagChip(this.toAdd[0]),
            tag2: this.formatTagChip(this.toRemove[0]),
            count: this.nodes.length
          },
          { escape: false }
        );
      }
      const tagsAdd = this.toAdd.map(this.formatTagChip);
      const lastTagAdd = tagsAdd.pop();
      const tagsRemove = this.toRemove.map(this.formatTagChip);
      const lastTagRemove = tagsRemove.pop();
      const addStringSingular = translatePlural(
        "systemtags",
        "{tag} will be set to 1 file.",
        "{tag} will be set to {count} files.",
        this.nodes.length,
        {
          tag: lastTagAdd,
          count: this.nodes.length
        },
        { escape: false }
      );
      const removeStringSingular = translatePlural(
        "systemtags",
        "{tag} will be removed from 1 file.",
        "{tag} will be removed from {count} files.",
        this.nodes.length,
        {
          tag: lastTagRemove,
          count: this.nodes.length
        },
        { escape: false }
      );
      const addStringPlural = translatePlural(
        "systemtags",
        "{tags} and {lastTag} will be set to 1 file.",
        "{tags} and {lastTag} will be set to {count} files.",
        this.nodes.length,
        {
          tags: tagsAdd.join(", "),
          lastTag: lastTagAdd,
          count: this.nodes.length
        },
        { escape: false }
      );
      const removeStringPlural = translatePlural(
        "systemtags",
        "{tags} and {lastTag} will be removed from 1 file.",
        "{tags} and {lastTag} will be removed from {count} files.",
        this.nodes.length,
        {
          tags: tagsRemove.join(", "),
          lastTag: lastTagRemove,
          count: this.nodes.length
        },
        { escape: false }
      );
      if (this.toAdd.length === 1 && this.toRemove.length === 0) {
        return addStringSingular;
      }
      if (this.toAdd.length === 0 && this.toRemove.length === 1) {
        return removeStringSingular;
      }
      if (this.toAdd.length > 1 && this.toRemove.length === 0) {
        return addStringPlural;
      }
      if (this.toAdd.length === 0 && this.toRemove.length > 1) {
        return removeStringPlural;
      }
      if (this.toAdd.length > 1 && this.toRemove.length === 1) {
        return `${addStringPlural} ${removeStringSingular}`;
      }
      if (this.toAdd.length === 1 && this.toRemove.length > 1) {
        return `${addStringSingular} ${removeStringPlural}`;
      }
      return `${addStringPlural} ${removeStringPlural}`;
    }
  },
  beforeMount() {
    fetchTags().then((tags) => {
      this.tags = tags;
    });
    this.tagList = this.nodes.reduce((acc, node) => {
      const tags = getNodeSystemTags(node) || [];
      tags.forEach((tag) => {
        acc[tag] = (acc[tag] || 0) + 1;
      });
      return acc;
    }, {});
    if (!this.canEditOrCreateTag) {
      logger.debug("System tag creation is restricted to admins and the current user is not an admin");
    }
  },
  methods: {
    // Format & sanitize a tag chip for v-html tag rendering
    formatTagChip(tag) {
      const chip = this.$refs.chip;
      const chipCloneEl = chip.$el.cloneNode(true);
      if (tag.color) {
        const style = this.tagListStyle(tag);
        Object.entries(style).forEach(([key, value]) => {
          chipCloneEl.style.setProperty(key, value);
        });
      }
      const chipHtml = chipCloneEl.outerHTML;
      return chipHtml.replace("%s", escapeHTML(purify.sanitize(tag.displayName)));
    },
    formatTagName(tag) {
      if (!tag.userVisible) {
        return translate("systemtags", "{displayName} (hidden)", { displayName: tag.displayName });
      }
      if (!tag.userAssignable) {
        return translate("systemtags", "{displayName} (restricted)", { displayName: tag.displayName });
      }
      return tag.displayName;
    },
    onColorChange(tag, color) {
      tag.color = color.replace("#", "");
      debounceUpdateTag(tag);
    },
    isChecked(tag) {
      return tag.displayName in this.tagList && this.tagList[tag.displayName] === this.nodes.length;
    },
    isIndeterminate(tag) {
      return tag.displayName in this.tagList && this.tagList[tag.displayName] !== 0 && this.tagList[tag.displayName] !== this.nodes.length;
    },
    onCheckUpdate(tag, checked) {
      if (checked) {
        this.toAdd.push(tag);
        this.toRemove = this.toRemove.filter((search) => search.id !== tag.id);
        this.tagList[tag.displayName] = this.nodes.length;
      } else {
        this.toRemove.push(tag);
        this.toAdd = this.toAdd.filter((search) => search.id !== tag.id);
        this.tagList[tag.displayName] = 0;
      }
    },
    async onNewTag() {
      if (!this.canEditOrCreateTag) {
        showError(translate("systemtags", "Only admins can create new tags"));
        return;
      }
      this.status = "creating-tag";
      try {
        const payload = {
          displayName: this.input.trim(),
          userAssignable: true,
          userVisible: true,
          canAssign: true
        };
        const id = await createTag(payload);
        const tag = await fetchTag(id);
        this.tags.push(tag);
        this.input = "";
        this.onCheckUpdate(tag, true);
        await this.$nextTick();
        if (Array.isArray(this.$refs.tags)) {
          const newTagEl = this.$refs.tags.find((el) => el.dataset.cySystemtagsPickerTag === id.toString());
          newTagEl?.scrollIntoView({
            behavior: "instant",
            block: "center",
            inline: "center"
          });
        }
      } catch (error) {
        showError(error?.message || translate("systemtags", "Failed to create tag"));
      } finally {
        this.status = "base";
      }
    },
    async onSubmit() {
      this.status = "loading";
      logger.debug("Applying tags", {
        toAdd: this.toAdd,
        toRemove: this.toRemove
      });
      try {
        for (const tag of this.toAdd) {
          const { etag, objects } = await getTagObjects(tag, "files");
          const ids = [.../* @__PURE__ */ new Set([
            ...objects.map((obj) => obj.id).filter(Boolean),
            ...this.nodes.map((node) => node.fileid).filter(Boolean)
          ])];
          await setTagObjects(tag, "files", ids.map((id) => ({ id, type: "files" })), etag);
        }
        for (const tag of this.toRemove) {
          const { etag, objects } = await getTagObjects(tag, "files");
          const nodeFileIds = new Set(this.nodes.map((node) => node.fileid));
          const ids = objects.map((obj) => obj.id).filter((id, index, self) => !nodeFileIds.has(id) && self.indexOf(id) === index);
          await setTagObjects(tag, "files", ids.map((id) => ({ id, type: "files" })), etag);
        }
      } catch (error) {
        logger.error("Failed to apply tags", { error });
        showError(translate("systemtags", "Failed to apply tags changes"));
        this.status = "base";
        return;
      }
      const nodes = [];
      this.toAdd.forEach((tag) => {
        this.nodes.forEach((node) => {
          const tags = [...getNodeSystemTags(node) || [], tag.displayName].sort((a, b) => a.localeCompare(b, getLanguage(), { ignorePunctuation: true }));
          setNodeSystemTags(node, tags);
          nodes.push(node);
        });
      });
      this.toRemove.forEach((tag) => {
        this.nodes.forEach((node) => {
          const tags = [...getNodeSystemTags(node) || []].filter((t2) => t2 !== tag.displayName).sort((a, b) => a.localeCompare(b, getLanguage(), { ignorePunctuation: true }));
          setNodeSystemTags(node, tags);
          nodes.push(node);
        });
      });
      nodes.forEach((node) => emit("systemtags:node:updated", node));
      this.status = "done";
      setTimeout(() => {
        this.opened = false;
        this.$emit("close", true);
      }, 2e3);
    },
    onCancel() {
      this.opened = false;
      this.$emit("close", null);
    },
    tagListStyle(tag) {
      if (!tag.color) {
        return {
          // See inline system tag color
          "--color-circle-icon": "var(--color-text-maxcontrast)"
        };
      }
      const primaryElement = elementColor(`#${tag.color}`, `#${mainBackgroundColor}`);
      const textColor = invertTextColor(primaryElement) ? "#000000" : "#ffffff";
      return {
        "--color-circle-icon": "var(--color-primary-element)",
        "--color-primary": primaryElement,
        "--color-primary-text": textColor,
        "--color-primary-element": primaryElement,
        "--color-primary-element-text": textColor
      };
    }
  }
});
const _hoisted_1 = { class: "systemtags-picker__input" };
const _hoisted_2 = {
  class: "systemtags-picker__tags",
  "data-cy-systemtags-picker-tags": ""
};
const _hoisted_3 = ["data-cy-systemtags-picker-tag"];
const _hoisted_4 = { class: "systemtags-picker__tag-create-subline" };
const _hoisted_5 = { class: "systemtags-picker__note" };
const _hoisted_6 = ["innerHTML"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcLoadingIcon = resolveComponent("NcLoadingIcon");
  const _component_CheckIcon = resolveComponent("CheckIcon");
  const _component_NcEmptyContent = resolveComponent("NcEmptyContent");
  const _component_TagIcon = resolveComponent("TagIcon");
  const _component_NcTextField = resolveComponent("NcTextField");
  const _component_NcCheckboxRadioSwitch = resolveComponent("NcCheckboxRadioSwitch");
  const _component_CircleIcon = resolveComponent("CircleIcon");
  const _component_CircleOutlineIcon = resolveComponent("CircleOutlineIcon");
  const _component_PencilIcon = resolveComponent("PencilIcon");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_NcColorPicker = resolveComponent("NcColorPicker");
  const _component_PlusIcon = resolveComponent("PlusIcon");
  const _component_NcNoteCard = resolveComponent("NcNoteCard");
  const _component_NcChip = resolveComponent("NcChip");
  const _component_NcDialog = resolveComponent("NcDialog");
  return openBlock(), createBlock(_component_NcDialog, {
    "data-cy-systemtags-picker": "",
    noClose: _ctx.status === _ctx.Status.LOADING,
    name: _ctx.t("systemtags", "Manage tags"),
    open: _ctx.opened,
    class: normalizeClass(["systemtags-picker--" + _ctx.status, "systemtags-picker"]),
    closeOnClickOutside: "",
    outTransition: "",
    "onUpdate:open": _ctx.onCancel
  }, {
    actions: withCtx(() => [
      createVNode(_component_NcButton, {
        disabled: _ctx.status !== _ctx.Status.BASE,
        variant: "tertiary",
        "data-cy-systemtags-picker-button-cancel": "",
        onClick: _ctx.onCancel
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString(_ctx.t("systemtags", "Cancel")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled", "onClick"]),
      createVNode(_component_NcButton, {
        disabled: !_ctx.hasChanges || _ctx.status !== _ctx.Status.BASE,
        "data-cy-systemtags-picker-button-submit": "",
        onClick: _ctx.onSubmit
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString(_ctx.t("systemtags", "Apply")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled", "onClick"])
    ]),
    default: withCtx(() => [
      _ctx.status === _ctx.Status.LOADING || _ctx.status === _ctx.Status.DONE ? (openBlock(), createBlock(_component_NcEmptyContent, {
        key: 0,
        name: _ctx.t("systemtags", "Applying tags changes…")
      }, {
        icon: withCtx(() => [
          _ctx.status === _ctx.Status.LOADING ? (openBlock(), createBlock(_component_NcLoadingIcon, { key: 0 })) : (openBlock(), createBlock(_component_CheckIcon, {
            key: 1,
            fillColor: "var(--color-border-success)"
          }))
        ]),
        _: 1
        /* STABLE */
      }, 8, ["name"])) : (openBlock(), createElementBlock(
        Fragment,
        { key: 1 },
        [
          createCommentVNode(" Search or create input "),
          createBaseVNode("div", _hoisted_1, [
            createVNode(_component_NcTextField, {
              modelValue: _ctx.input,
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => _ctx.input = $event),
              label: _ctx.canEditOrCreateTag ? _ctx.t("systemtags", "Search or create tag") : _ctx.t("systemtags", "Search tag"),
              "data-cy-systemtags-picker-input": ""
            }, {
              default: withCtx(() => [
                createVNode(_component_TagIcon, { size: 20 })
              ]),
              _: 1
              /* STABLE */
            }, 8, ["modelValue", "label"])
          ]),
          createCommentVNode(" Tags list "),
          createBaseVNode("ul", _hoisted_2, [
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList(_ctx.filteredTags, (tag) => {
                return openBlock(), createElementBlock("li", {
                  key: tag.id,
                  ref_for: true,
                  ref: "tags",
                  "data-cy-systemtags-picker-tag": tag.id,
                  style: normalizeStyle(_ctx.tagListStyle(tag)),
                  class: "systemtags-picker__tag"
                }, [
                  createVNode(_component_NcCheckboxRadioSwitch, {
                    modelValue: _ctx.isChecked(tag),
                    disabled: !tag.canAssign,
                    indeterminate: _ctx.isIndeterminate(tag),
                    label: tag.displayName,
                    class: "systemtags-picker__tag-checkbox",
                    "onUpdate:modelValue": ($event) => _ctx.onCheckUpdate(tag, $event)
                  }, {
                    default: withCtx(() => [
                      createTextVNode(
                        toDisplayString(_ctx.formatTagName(tag)),
                        1
                        /* TEXT */
                      )
                    ]),
                    _: 2
                    /* DYNAMIC */
                  }, 1032, ["modelValue", "disabled", "indeterminate", "label", "onUpdate:modelValue"]),
                  createCommentVNode(" Color picker "),
                  _ctx.canEditOrCreateTag ? (openBlock(), createBlock(_component_NcColorPicker, {
                    key: 0,
                    "data-cy-systemtags-picker-tag-color": tag.id,
                    modelValue: `#${tag.color || "000000"}`,
                    shown: _ctx.openedPicker === tag.id,
                    class: "systemtags-picker__tag-color",
                    "onUpdate:shown": ($event) => _ctx.openedPicker = $event ? tag.id : false,
                    onSubmit: ($event) => _ctx.onColorChange(tag, $event)
                  }, {
                    default: withCtx(() => [
                      createVNode(_component_NcButton, {
                        "aria-label": _ctx.t("systemtags", "Change tag color"),
                        variant: "tertiary"
                      }, {
                        icon: withCtx(() => [
                          tag.color ? (openBlock(), createBlock(_component_CircleIcon, {
                            key: 0,
                            size: 24,
                            fillColor: "var(--color-circle-icon)",
                            class: "button-color-circle"
                          })) : (openBlock(), createBlock(_component_CircleOutlineIcon, {
                            key: 1,
                            size: 24,
                            fillColor: "var(--color-circle-icon)",
                            class: "button-color-empty"
                          })),
                          createVNode(_component_PencilIcon, { class: "button-color-pencil" })
                        ]),
                        _: 2
                        /* DYNAMIC */
                      }, 1032, ["aria-label"])
                    ]),
                    _: 2
                    /* DYNAMIC */
                  }, 1032, ["data-cy-systemtags-picker-tag-color", "modelValue", "shown", "onUpdate:shown", "onSubmit"])) : createCommentVNode("v-if", true)
                ], 12, _hoisted_3);
              }),
              128
              /* KEYED_FRAGMENT */
            )),
            createCommentVNode(" Create new tag "),
            createBaseVNode("li", null, [
              _ctx.canEditOrCreateTag && _ctx.canCreateTag ? (openBlock(), createBlock(_component_NcButton, {
                key: 0,
                disabled: _ctx.status === _ctx.Status.CREATING_TAG,
                alignment: "start",
                class: "systemtags-picker__tag-create",
                type: "submit",
                variant: "tertiary",
                "data-cy-systemtags-picker-button-create": "",
                onClick: _ctx.onNewTag
              }, {
                icon: withCtx(() => [
                  createVNode(_component_PlusIcon)
                ]),
                default: withCtx(() => [
                  createTextVNode(
                    toDisplayString(_ctx.input.trim()),
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
                  createBaseVNode(
                    "span",
                    _hoisted_4,
                    toDisplayString(_ctx.t("systemtags", "Create new tag")),
                    1
                    /* TEXT */
                  )
                ]),
                _: 1
                /* STABLE */
              }, 8, ["disabled", "onClick"])) : createCommentVNode("v-if", true)
            ])
          ]),
          createCommentVNode(" Note "),
          createBaseVNode("div", _hoisted_5, [
            !_ctx.hasChanges ? (openBlock(), createBlock(_component_NcNoteCard, {
              key: 0,
              type: "info"
            }, {
              default: withCtx(() => [
                createTextVNode(
                  toDisplayString(_ctx.t("systemtags", "Choose tags for the selected files")),
                  1
                  /* TEXT */
                )
              ]),
              _: 1
              /* STABLE */
            })) : (openBlock(), createBlock(_component_NcNoteCard, {
              key: 1,
              type: "info"
            }, {
              default: withCtx(() => [
                createCommentVNode(" eslint-disable-next-line vue/no-v-html -- we use this to format the message with chips "),
                createBaseVNode("span", { innerHTML: _ctx.statusMessage }, null, 8, _hoisted_6)
              ]),
              _: 1
              /* STABLE */
            }))
          ])
        ],
        64
        /* STABLE_FRAGMENT */
      )),
      withDirectives(createBaseVNode(
        "div",
        null,
        [
          createVNode(
            _component_NcChip,
            {
              ref: "chip",
              text: "%s",
              noClose: "",
              variant: "primary"
            },
            null,
            512
            /* NEED_PATCH */
          )
        ],
        512
        /* NEED_PATCH */
      ), [
        [vShow, false]
      ])
    ]),
    _: 1
    /* STABLE */
  }, 8, ["noClose", "name", "open", "class", "onUpdate:open"]);
}
const SystemTagPicker = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-1a0e0634"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/systemtags/src/components/SystemTagPicker.vue"]]);
export {
  SystemTagPicker as default
};
//# sourceMappingURL=SystemTagPicker-BLlQxzDm.chunk.mjs.map
