const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { v as NcSelect, x as NcEmojiPicker, N as NcButton, _ as _sfc_main$7, y as NcUserStatusIcon, z as NcModal, s as showError } from "./Plus-CFgExibL.chunk.mjs";
import { f as translate, _ as _export_sfc, r as resolveComponent, s as createElementBlock, o as openBlock, k as createBaseVNode, b as createVNode, t as toDisplayString, w as withCtx, d as createTextVNode, F as Fragment, u as renderList, c as createBlock, Z as withKeys, j as createCommentVNode, Q as mergeProps, Y as withModifiers, i as generateUrl } from "./TrashCanOutline-CLxw5nIJ.chunk.mjs";
import { c as clearAtFormat, m as mapGetters, a as mapState, O as OnlineStatusMixin, l as logger } from "./user_status-menu.mjs";
import "./index-MBGsF8CJ.chunk.mjs";
function getAllClearAtOptions() {
  return [{
    label: translate("user_status", "Don't clear"),
    clearAt: null
  }, {
    label: translate("user_status", "30 minutes"),
    clearAt: {
      type: "period",
      time: 1800
    }
  }, {
    label: translate("user_status", "1 hour"),
    clearAt: {
      type: "period",
      time: 3600
    }
  }, {
    label: translate("user_status", "4 hours"),
    clearAt: {
      type: "period",
      time: 14400
    }
  }, {
    label: translate("user_status", "Today"),
    clearAt: {
      type: "end-of",
      time: "day"
    }
  }, {
    label: translate("user_status", "This week"),
    clearAt: {
      type: "end-of",
      time: "week"
    }
  }];
}
const _sfc_main$6 = {
  name: "ClearAtSelect",
  components: {
    NcSelect
  },
  props: {
    clearAt: {
      type: Object,
      default: null
    }
  },
  emits: ["selectClearAt"],
  data() {
    return {
      options: getAllClearAtOptions()
    };
  },
  computed: {
    /**
     * Returns an object of the currently selected option
     *
     * @return {object}
     */
    option() {
      return {
        clearAt: this.clearAt,
        label: clearAtFormat(this.clearAt)
      };
    }
  },
  methods: {
    t: translate,
    /**
     * Triggered when the user selects a new option.
     *
     * @param {object=} option The new selected option
     */
    select(option) {
      if (!option) {
        return;
      }
      this.$emit("selectClearAt", option.clearAt);
    }
  }
};
const _hoisted_1$6 = { class: "clear-at-select" };
const _hoisted_2$6 = {
  class: "clear-at-select__label",
  for: "clearStatus"
};
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcSelect = resolveComponent("NcSelect");
  return openBlock(), createElementBlock("div", _hoisted_1$6, [
    createBaseVNode(
      "label",
      _hoisted_2$6,
      toDisplayString($options.t("user_status", "Clear status after")),
      1
      /* TEXT */
    ),
    createVNode(_component_NcSelect, {
      "input-id": "clearStatus",
      class: "clear-at-select__select",
      options: $data.options,
      "model-value": $options.option,
      clearable: false,
      placement: "top",
      "label-outside": "",
      "onOption:selected": $options.select
    }, null, 8, ["options", "model-value", "onOption:selected"])
  ]);
}
const ClearAtSelect = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__scopeId", "data-v-4b76bb61"], ["__file", "/home/admin/Docker/workspace/server/build/frontend/apps/user_status/src/components/ClearAtSelect.vue"]]);
const _sfc_main$5 = {
  name: "CustomMessageInput",
  components: {
    NcTextField: _sfc_main$7,
    NcButton,
    NcEmojiPicker
  },
  props: {
    icon: {
      type: String,
      default: "😀"
    },
    message: {
      type: String,
      default: ""
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: [
    "change",
    "selectIcon"
  ],
  computed: {
    /**
     * Returns the user-set icon or a smiley in case no icon is set
     *
     * @return {string}
     */
    visibleIcon() {
      return this.icon || "😀";
    }
  },
  methods: {
    t: translate,
    focus() {
      this.$refs.input.focus();
    },
    /**
     * Notifies the parent component about a changed input
     *
     * @param {string} value The new input value
     */
    onChange(value) {
      this.$emit("change", value);
    },
    setIcon(icon) {
      this.$emit("selectIcon", icon);
    }
  }
};
const _hoisted_1$5 = {
  class: "custom-input",
  role: "group"
};
const _hoisted_2$5 = { class: "custom-input__container" };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcButton = resolveComponent("NcButton");
  const _component_NcEmojiPicker = resolveComponent("NcEmojiPicker");
  const _component_NcTextField = resolveComponent("NcTextField");
  return openBlock(), createElementBlock("div", _hoisted_1$5, [
    createVNode(_component_NcEmojiPicker, {
      container: ".custom-input",
      onSelect: $options.setIcon
    }, {
      default: withCtx(() => [
        createVNode(_component_NcButton, {
          variant: "tertiary",
          "aria-label": $options.t("user_status", "Emoji for your status message")
        }, {
          icon: withCtx(() => [
            createTextVNode(
              toDisplayString($options.visibleIcon),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["aria-label"])
      ]),
      _: 1
      /* STABLE */
    }, 8, ["onSelect"]),
    createBaseVNode("div", _hoisted_2$5, [
      createVNode(_component_NcTextField, {
        ref: "input",
        maxlength: "80",
        disabled: $props.disabled,
        placeholder: $options.t("user_status", "What is your status?"),
        "model-value": $props.message,
        type: "text",
        label: $options.t("user_status", "What is your status?"),
        "onUpdate:modelValue": $options.onChange
      }, null, 8, ["disabled", "placeholder", "model-value", "label", "onUpdate:modelValue"])
    ])
  ]);
}
const CustomMessageInput = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__scopeId", "data-v-0ef173e0"], ["__file", "/home/admin/Docker/workspace/server/build/frontend/apps/user_status/src/components/CustomMessageInput.vue"]]);
const _sfc_main$4 = {
  name: "OnlineStatusSelect",
  components: {
    NcUserStatusIcon
  },
  props: {
    checked: {
      type: Boolean,
      default: false
    },
    type: {
      type: String,
      required: true
    },
    label: {
      type: String,
      required: true
    },
    subline: {
      type: String,
      default: null
    }
  },
  emits: ["select"],
  computed: {
    id() {
      return `user-status-online-status-${this.type}`;
    }
  },
  methods: {
    onChange() {
      this.$emit("select", this.type);
    }
  }
};
const _hoisted_1$4 = { class: "user-status-online-select" };
const _hoisted_2$4 = ["id", "checked"];
const _hoisted_3$3 = ["for"];
const _hoisted_4$3 = { class: "user-status-online-select__icon-wrapper" };
const _hoisted_5$2 = { class: "user-status-online-select__subline" };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcUserStatusIcon = resolveComponent("NcUserStatusIcon");
  return openBlock(), createElementBlock("div", _hoisted_1$4, [
    createBaseVNode("input", {
      id: $options.id,
      checked: $props.checked,
      class: "hidden-visually user-status-online-select__input",
      type: "radio",
      name: "user-status-online",
      onChange: _cache[0] || (_cache[0] = (...args) => $options.onChange && $options.onChange(...args))
    }, null, 40, _hoisted_2$4),
    createBaseVNode("label", {
      for: $options.id,
      class: "user-status-online-select__label"
    }, [
      createBaseVNode("span", _hoisted_4$3, [
        createVNode(_component_NcUserStatusIcon, {
          status: $props.type,
          class: "user-status-online-select__icon",
          "aria-hidden": "true"
        }, null, 8, ["status"])
      ]),
      createTextVNode(
        " " + toDisplayString($props.label) + " ",
        1
        /* TEXT */
      ),
      createBaseVNode(
        "em",
        _hoisted_5$2,
        toDisplayString($props.subline),
        1
        /* TEXT */
      )
    ], 8, _hoisted_3$3)
  ]);
}
const OnlineStatusSelect = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__scopeId", "data-v-a592c307"], ["__file", "/home/admin/Docker/workspace/server/build/frontend/apps/user_status/src/components/OnlineStatusSelect.vue"]]);
const _sfc_main$3 = {
  name: "PredefinedStatus",
  props: {
    messageId: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      required: true
    },
    message: {
      type: String,
      required: true
    },
    clearAt: {
      type: Object,
      required: false,
      default: null
    },
    selected: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  emits: ["select"],
  computed: {
    id() {
      return `user-status-predefined-status-${this.messageId}`;
    },
    formattedClearAt() {
      return clearAtFormat(this.clearAt);
    }
  },
  methods: {
    /**
     * Emits an event when the user clicks the row
     */
    select() {
      this.$emit("select");
    }
  }
};
const _hoisted_1$3 = { class: "predefined-status" };
const _hoisted_2$3 = ["id", "checked"];
const _hoisted_3$2 = ["for"];
const _hoisted_4$2 = {
  "aria-hidden": "true",
  class: "predefined-status__label--icon"
};
const _hoisted_5$1 = { class: "predefined-status__label--message" };
const _hoisted_6$1 = { class: "predefined-status__label--clear-at" };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("li", _hoisted_1$3, [
    createBaseVNode("input", {
      id: $options.id,
      class: "hidden-visually predefined-status__input",
      type: "radio",
      name: "predefined-status",
      checked: $props.selected,
      onChange: _cache[0] || (_cache[0] = (...args) => $options.select && $options.select(...args))
    }, null, 40, _hoisted_2$3),
    createBaseVNode("label", {
      class: "predefined-status__label",
      for: $options.id
    }, [
      createBaseVNode(
        "span",
        _hoisted_4$2,
        toDisplayString($props.icon),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "span",
        _hoisted_5$1,
        toDisplayString($props.message),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "span",
        _hoisted_6$1,
        toDisplayString($options.formattedClearAt),
        1
        /* TEXT */
      )
    ], 8, _hoisted_3$2)
  ]);
}
const PredefinedStatus = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-c2f9bb60"], ["__file", "/home/admin/Docker/workspace/server/build/frontend/apps/user_status/src/components/PredefinedStatus.vue"]]);
const _sfc_main$2 = {
  name: "PredefinedStatusesList",
  components: {
    PredefinedStatus
  },
  emits: ["selectStatus"],
  data() {
    return {
      lastSelected: null
    };
  },
  computed: {
    ...mapState({
      predefinedStatuses: (state) => state.predefinedStatuses.predefinedStatuses,
      messageId: (state) => state.userStatus.messageId
    }),
    ...mapGetters(["statusesHaveLoaded"])
  },
  watch: {
    messageId: {
      immediate: true,
      handler() {
        this.lastSelected = this.messageId;
      }
    }
  },
  /**
   * Loads all predefined statuses from the server
   * when this component is mounted
   */
  created() {
    this.$store.dispatch("loadAllPredefinedStatuses");
  },
  methods: {
    t: translate,
    /**
     * Emits an event when the user selects a status
     *
     * @param {object} status The selected status
     */
    selectStatus(status) {
      this.lastSelected = status.id;
      this.$emit("selectStatus", status);
    }
  }
};
const _hoisted_1$2 = ["aria-label"];
const _hoisted_2$2 = {
  key: 1,
  class: "predefined-statuses-list"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_PredefinedStatus = resolveComponent("PredefinedStatus");
  return _ctx.statusesHaveLoaded ? (openBlock(), createElementBlock("ul", {
    key: 0,
    class: "predefined-statuses-list",
    "aria-label": $options.t("user_status", "Predefined statuses")
  }, [
    (openBlock(true), createElementBlock(
      Fragment,
      null,
      renderList(_ctx.predefinedStatuses, (status) => {
        return openBlock(), createBlock(_component_PredefinedStatus, {
          key: status.id,
          "message-id": status.id,
          icon: status.icon,
          message: status.message,
          "clear-at": status.clearAt,
          selected: $data.lastSelected === status.id,
          onSelect: ($event) => $options.selectStatus(status)
        }, null, 8, ["message-id", "icon", "message", "clear-at", "selected", "onSelect"]);
      }),
      128
      /* KEYED_FRAGMENT */
    ))
  ], 8, _hoisted_1$2)) : (openBlock(), createElementBlock("div", _hoisted_2$2, [..._cache[0] || (_cache[0] = [
    createBaseVNode(
      "div",
      { class: "icon icon-loading-small" },
      null,
      -1
      /* CACHED */
    )
  ])]));
}
const PredefinedStatusesList = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-b3aa73e0"], ["__file", "/home/admin/Docker/workspace/server/build/frontend/apps/user_status/src/components/PredefinedStatusesList.vue"]]);
const _sfc_main$1 = {
  name: "PreviousStatus",
  components: {
    NcButton
  },
  props: {
    icon: {
      type: [String, null],
      required: true
    },
    message: {
      type: String,
      required: true
    }
  },
  emits: ["select"],
  methods: {
    t: translate,
    /**
     * Emits an event when the user clicks the row
     */
    select() {
      this.$emit("select");
    }
  }
};
const _hoisted_1$1 = { class: "predefined-status__icon" };
const _hoisted_2$1 = { class: "predefined-status__message" };
const _hoisted_3$1 = { class: "predefined-status__clear-at" };
const _hoisted_4$1 = { class: "backup-status__reset-button" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcButton = resolveComponent("NcButton");
  return openBlock(), createElementBlock(
    "div",
    {
      class: "predefined-status backup-status",
      tabindex: "0",
      onKeyup: [
        _cache[0] || (_cache[0] = withKeys((...args) => $options.select && $options.select(...args), ["enter"])),
        _cache[1] || (_cache[1] = withKeys((...args) => $options.select && $options.select(...args), ["space"]))
      ],
      onClick: _cache[2] || (_cache[2] = (...args) => $options.select && $options.select(...args))
    },
    [
      createBaseVNode(
        "span",
        _hoisted_1$1,
        toDisplayString($props.icon),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "span",
        _hoisted_2$1,
        toDisplayString($props.message),
        1
        /* TEXT */
      ),
      createBaseVNode(
        "span",
        _hoisted_3$1,
        toDisplayString($options.t("user_status", "Previously set")),
        1
        /* TEXT */
      ),
      createBaseVNode("div", _hoisted_4$1, [
        createVNode(_component_NcButton, { onClick: $options.select }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($options.t("user_status", "Reset status")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["onClick"])
      ])
    ],
    32
    /* NEED_HYDRATION */
  );
}
const PreviousStatus = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-ee7ae222"], ["__file", "/home/admin/Docker/workspace/server/build/frontend/apps/user_status/src/components/PreviousStatus.vue"]]);
function getAllStatusOptions() {
  return [{
    type: "online",
    label: translate("user_status", "Online")
  }, {
    type: "away",
    label: translate("user_status", "Away")
  }, {
    type: "busy",
    label: translate("user_status", "Busy")
  }, {
    type: "dnd",
    label: translate("user_status", "Do not disturb"),
    subline: translate("user_status", "Mute all notifications")
  }, {
    type: "invisible",
    label: translate("user_status", "Invisible"),
    subline: translate("user_status", "Appear offline")
  }];
}
const _sfc_main = {
  name: "SetStatusModal",
  components: {
    ClearAtSelect,
    CustomMessageInput,
    NcModal,
    OnlineStatusSelect,
    PredefinedStatusesList,
    PreviousStatus,
    NcButton
  },
  mixins: [OnlineStatusMixin],
  props: {
    /**
     * Whether the component should be rendered as a Dashboard Status or a User Menu Entries
     * true = Dashboard Status
     * false = User Menu Entries
     */
    inline: {
      type: Boolean,
      default: false
    }
  },
  emits: ["close"],
  data() {
    return {
      clearAt: null,
      editedMessage: "",
      predefinedMessageId: null,
      isSavingStatus: false,
      statuses: getAllStatusOptions()
    };
  },
  computed: {
    messageId() {
      return this.$store.state.userStatus.messageId;
    },
    icon() {
      return this.$store.state.userStatus.icon;
    },
    message() {
      return this.$store.state.userStatus.message || "";
    },
    hasBackupStatus() {
      return this.messageId && (this.backupIcon || this.backupMessage);
    },
    backupIcon() {
      return this.$store.state.userBackupStatus.icon || "";
    },
    backupMessage() {
      return this.$store.state.userBackupStatus.message || "";
    },
    absencePageUrl() {
      return generateUrl("settings/user/availability#absence");
    },
    resetButtonText() {
      if (this.backupIcon && this.backupMessage) {
        return translate("user_status", 'Reset status to "{icon} {message}"', {
          icon: this.backupIcon,
          message: this.backupMessage
        });
      } else if (this.backupMessage) {
        return translate("user_status", 'Reset status to "{message}"', {
          message: this.backupMessage
        });
      } else if (this.backupIcon) {
        return translate("user_status", 'Reset status to "{icon}"', {
          icon: this.backupIcon
        });
      }
      return translate("user_status", "Reset status");
    },
    setReturnFocus() {
      if (this.inline) {
        return void 0;
      }
      return document.querySelector('[aria-controls="header-menu-user-menu"]') ?? void 0;
    }
  },
  watch: {
    message: {
      immediate: true,
      handler(newValue) {
        this.editedMessage = newValue;
      }
    }
  },
  /**
   * Loads the current status when a user opens dialog
   */
  mounted() {
    this.$store.dispatch("fetchBackupFromServer");
    this.predefinedMessageId = this.$store.state.userStatus.messageId;
    if (this.$store.state.userStatus.clearAt !== null) {
      this.clearAt = {
        type: "_time",
        time: this.$store.state.userStatus.clearAt
      };
    }
  },
  methods: {
    t: translate,
    /**
     * Closes the Set Status modal
     */
    closeModal() {
      this.$emit("close");
    },
    /**
     * Sets a new icon
     *
     * @param {string} icon The new icon
     */
    setIcon(icon) {
      this.predefinedMessageId = null;
      this.$store.dispatch("setCustomMessage", {
        message: this.message,
        icon,
        clearAt: this.clearAt
      });
      this.$nextTick(() => {
        this.$refs.customMessageInput.focus();
      });
    },
    /**
     * Sets a new message
     *
     * @param {string} message The new message
     */
    setMessage(message) {
      this.predefinedMessageId = null;
      this.editedMessage = message;
    },
    /**
     * Sets a new clearAt value
     *
     * @param {object} clearAt The new clearAt object
     */
    setClearAt(clearAt) {
      this.clearAt = clearAt;
    },
    /**
     * Sets new icon/message/clearAt based on a predefined message
     *
     * @param {object} status The predefined status object
     */
    selectPredefinedMessage(status) {
      this.predefinedMessageId = status.id;
      this.clearAt = status.clearAt;
      this.$store.dispatch("setPredefinedMessage", {
        messageId: status.id,
        clearAt: status.clearAt
      });
    },
    /**
     * Saves the status and closes the
     *
     * @return {Promise<void>}
     */
    async saveStatus() {
      if (this.isSavingStatus) {
        return;
      }
      try {
        this.isSavingStatus = true;
        if (this.predefinedMessageId === null) {
          await this.$store.dispatch("setCustomMessage", {
            message: this.editedMessage,
            icon: this.icon,
            clearAt: this.clearAt
          });
        } else {
          this.$store.dispatch("setPredefinedMessage", {
            messageId: this.predefinedMessageId,
            clearAt: this.clearAt
          });
        }
      } catch (err) {
        showError(translate("user_status", "There was an error saving the status"));
        logger.debug(err);
        this.isSavingStatus = false;
        return;
      }
      this.isSavingStatus = false;
      this.closeModal();
    },
    /**
     *
     * @return {Promise<void>}
     */
    async clearStatus() {
      try {
        this.isSavingStatus = true;
        await this.$store.dispatch("clearMessage");
      } catch (err) {
        showError(translate("user_status", "There was an error clearing the status"));
        logger.debug(err);
        this.isSavingStatus = false;
        return;
      }
      this.isSavingStatus = false;
      this.predefinedMessageId = null;
      this.closeModal();
    },
    /**
     *
     * @return {Promise<void>}
     */
    async revertBackupFromServer() {
      try {
        this.isSavingStatus = true;
        await this.$store.dispatch("revertBackupFromServer", {
          messageId: this.messageId
        });
      } catch (err) {
        showError(translate("user_status", "There was an error reverting the status"));
        logger.debug(err);
        this.isSavingStatus = false;
        return;
      }
      this.isSavingStatus = false;
      this.predefinedMessageId = this.$store.state.userStatus?.messageId;
    }
  }
};
const _hoisted_1 = { class: "set-status-modal" };
const _hoisted_2 = {
  id: "user_status-set-dialog",
  class: "set-status-modal__header"
};
const _hoisted_3 = ["aria-label"];
const _hoisted_4 = { class: "set-status-modal__header" };
const _hoisted_5 = { class: "set-status-modal__custom-input" };
const _hoisted_6 = {
  key: 0,
  class: "set-status-modal__automation-hint"
};
const _hoisted_7 = { class: "status-buttons" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_OnlineStatusSelect = resolveComponent("OnlineStatusSelect");
  const _component_CustomMessageInput = resolveComponent("CustomMessageInput");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_PreviousStatus = resolveComponent("PreviousStatus");
  const _component_PredefinedStatusesList = resolveComponent("PredefinedStatusesList");
  const _component_ClearAtSelect = resolveComponent("ClearAtSelect");
  const _component_NcModal = resolveComponent("NcModal");
  return openBlock(), createBlock(_component_NcModal, {
    size: "normal",
    "label-id": "user_status-set-dialog",
    dark: "",
    "set-return-focus": $options.setReturnFocus,
    onClose: $options.closeModal
  }, {
    default: withCtx(() => [
      createBaseVNode("div", _hoisted_1, [
        createCommentVNode(" Status selector "),
        createBaseVNode(
          "h2",
          _hoisted_2,
          toDisplayString($options.t("user_status", "Online status")),
          1
          /* TEXT */
        ),
        createBaseVNode("div", {
          class: "set-status-modal__online-status",
          role: "radiogroup",
          "aria-label": $options.t("user_status", "Online status")
        }, [
          (openBlock(true), createElementBlock(
            Fragment,
            null,
            renderList($data.statuses, (status) => {
              return openBlock(), createBlock(_component_OnlineStatusSelect, mergeProps({
                key: status.type
              }, { ref_for: true }, status, {
                checked: status.type === _ctx.statusType,
                onSelect: _ctx.changeStatus
              }), null, 16, ["checked", "onSelect"]);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ], 8, _hoisted_3),
        createCommentVNode(" Status message form "),
        createBaseVNode(
          "form",
          {
            onSubmit: _cache[0] || (_cache[0] = withModifiers((...args) => $options.saveStatus && $options.saveStatus(...args), ["prevent"])),
            onReset: _cache[1] || (_cache[1] = (...args) => $options.clearStatus && $options.clearStatus(...args))
          },
          [
            createBaseVNode(
              "h3",
              _hoisted_4,
              toDisplayString($options.t("user_status", "Status message")),
              1
              /* TEXT */
            ),
            createBaseVNode("div", _hoisted_5, [
              createVNode(_component_CustomMessageInput, {
                ref: "customMessageInput",
                icon: $options.icon,
                message: $data.editedMessage,
                onChange: $options.setMessage,
                onSelectIcon: $options.setIcon
              }, null, 8, ["icon", "message", "onChange", "onSelectIcon"]),
              $options.messageId === "vacationing" ? (openBlock(), createBlock(_component_NcButton, {
                key: 0,
                href: $options.absencePageUrl,
                target: "_blank",
                variant: "secondary",
                "aria-label": $options.t("user_status", "Set absence period")
              }, {
                default: withCtx(() => [
                  createTextVNode(
                    toDisplayString($options.t("user_status", "Set absence period and replacement") + " ↗"),
                    1
                    /* TEXT */
                  )
                ]),
                _: 1
                /* STABLE */
              }, 8, ["href", "aria-label"])) : createCommentVNode("v-if", true)
            ]),
            $options.hasBackupStatus ? (openBlock(), createElementBlock(
              "div",
              _hoisted_6,
              toDisplayString($options.t("user_status", "Your status was set automatically")),
              1
              /* TEXT */
            )) : createCommentVNode("v-if", true),
            $options.hasBackupStatus ? (openBlock(), createBlock(_component_PreviousStatus, {
              key: 1,
              icon: $options.backupIcon,
              message: $options.backupMessage,
              onSelect: $options.revertBackupFromServer
            }, null, 8, ["icon", "message", "onSelect"])) : createCommentVNode("v-if", true),
            createVNode(_component_PredefinedStatusesList, { onSelectStatus: $options.selectPredefinedMessage }, null, 8, ["onSelectStatus"]),
            createVNode(_component_ClearAtSelect, {
              "clear-at": $data.clearAt,
              onSelectClearAt: $options.setClearAt
            }, null, 8, ["clear-at", "onSelectClearAt"]),
            createBaseVNode("div", _hoisted_7, [
              createVNode(_component_NcButton, {
                wide: true,
                variant: "tertiary",
                type: "reset",
                "aria-label": $options.t("user_status", "Clear status message"),
                disabled: $data.isSavingStatus
              }, {
                default: withCtx(() => [
                  createTextVNode(
                    toDisplayString($options.t("user_status", "Clear status message")),
                    1
                    /* TEXT */
                  )
                ]),
                _: 1
                /* STABLE */
              }, 8, ["aria-label", "disabled"]),
              createVNode(_component_NcButton, {
                wide: true,
                variant: "primary",
                type: "submit",
                "aria-label": $options.t("user_status", "Set status message"),
                disabled: $data.isSavingStatus
              }, {
                default: withCtx(() => [
                  createTextVNode(
                    toDisplayString($options.t("user_status", "Set status message")),
                    1
                    /* TEXT */
                  )
                ]),
                _: 1
                /* STABLE */
              }, 8, ["aria-label", "disabled"])
            ])
          ],
          32
          /* NEED_HYDRATION */
        )
      ])
    ]),
    _: 1
    /* STABLE */
  }, 8, ["set-return-focus", "onClose"]);
}
const SetStatusModal = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-aaeb0cdc"], ["__file", "/home/admin/Docker/workspace/server/build/frontend/apps/user_status/src/components/SetStatusModal.vue"]]);
export {
  SetStatusModal as default
};
//# sourceMappingURL=SetStatusModal-VT77gtjx.chunk.mjs.map
