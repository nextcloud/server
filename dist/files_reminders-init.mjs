const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { a as registerFileAction } from "./index-DCPyCjGS.chunk.mjs";
import { c as registerDavProperty } from "./dav-DGipjjQH.chunk.mjs";
import { c as generateOcsUrl, f as emit, g as getLoggerBuilder } from "./index-rAufP352.chunk.mjs";
import { b as getCanonicalLocale, t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import { b as defineComponent, Q as onBeforeMount, A as onMounted, y as ref, o as openBlock, c as createBlock, w as withCtx, g as createBaseVNode, M as withModifiers, x as createVNode, j as createTextVNode, t as toDisplayString, h as createCommentVNode } from "./preload-helper-xAe3EUYB.chunk.mjs";
import "./NcTextArea-CWA3KOiC-Cpgesyiv.chunk.mjs";
import { N as NcDateTimePickerNative } from "./index-CZV8rpGu.chunk.mjs";
import { _ as _sfc_main$1 } from "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
/* empty css                                           */
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import "./Plus-DuSPdibD.chunk.mjs";
import "./index-DD39fp6M.chunk.mjs";
import "./TrayArrowDown-DVjUGg6-.chunk.mjs";
import "./index-BcMnKoRR.chunk.mjs";
import { N as NcDialog, s as spawnDialog } from "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import "./NcEmojiPicker-Djc9a0gw-F1kmncT2.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import "./index-D5BR15En.chunk.mjs";
/* empty css                                        */
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import { N as NcNoteCard } from "./mdi-BGU2G5q5.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./index-gwTr8m4i.chunk.mjs";
import "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import "./NcSelectTags-CTHyuMcq-2HejGZhj.chunk.mjs";
import "./ContentCopy-C2t0ZTwU.chunk.mjs";
import "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./emoji-BY_D0V5K-BlCul1cD.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import { a as showError, c as showSuccess } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import "./public-CKeAb98h.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
const AlarmOffSvg = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-alarm-off" viewBox="0 0 24 24"><path d="M8,3.28L6.6,1.86L5.74,2.57L7.16,4M16.47,18.39C15.26,19.39 13.7,20 12,20A7,7 0 0,1 5,13C5,11.3 5.61,9.74 6.61,8.53M2.92,2.29L1.65,3.57L3,4.9L1.87,5.83L3.29,7.25L4.4,6.31L5.2,7.11C3.83,8.69 3,10.75 3,13A9,9 0 0,0 12,22C14.25,22 16.31,21.17 17.89,19.8L20.09,22L21.36,20.73L3.89,3.27L2.92,2.29M22,5.72L17.4,1.86L16.11,3.39L20.71,7.25L22,5.72M12,6A7,7 0 0,1 19,13C19,13.84 18.84,14.65 18.57,15.4L20.09,16.92C20.67,15.73 21,14.41 21,13A9,9 0 0,0 12,4C10.59,4 9.27,4.33 8.08,4.91L9.6,6.43C10.35,6.16 11.16,6 12,6Z" /></svg>';
async function setReminder(fileId, dueDate) {
  const url = generateOcsUrl("/apps/files_reminders/api/v1/{fileId}", { fileId });
  const response = await cancelableClient.put(url, {
    dueDate: dueDate.toISOString()
    // timezone of string is always UTC
  });
  return response.data.ocs.data;
}
async function clearReminder(fileId) {
  const url = generateOcsUrl("/apps/files_reminders/api/v1/{fileId}", { fileId });
  const response = await cancelableClient.delete(url);
  return response.data.ocs.data;
}
var DateTimePreset = /* @__PURE__ */ ((DateTimePreset2) => {
  DateTimePreset2["LaterToday"] = "later-today";
  DateTimePreset2["Tomorrow"] = "tomorrow";
  DateTimePreset2["ThisWeekend"] = "this-weekend";
  DateTimePreset2["NextWeek"] = "next-week";
  return DateTimePreset2;
})(DateTimePreset || {});
function getFirstWorkdayOfWeek() {
  const now = /* @__PURE__ */ new Date();
  now.setHours(0, 0, 0, 0);
  now.setDate(now.getDate() - now.getDay() + 1);
  return new Date(now);
}
function getWeek(date) {
  const dateClone = new Date(date);
  dateClone.setHours(0, 0, 0, 0);
  const firstDayOfYear = new Date(date.getFullYear(), 0, 1, 0, 0, 0, 0);
  const daysFromFirstDay = (date.getTime() - firstDayOfYear.getTime()) / 864e5;
  return Math.ceil((daysFromFirstDay + firstDayOfYear.getDay() + 1) / 7);
}
function isSameWeek(a, b) {
  return getWeek(a) === getWeek(b) && a.getFullYear() === b.getFullYear();
}
function isSameDate(a, b) {
  return a.getDate() === b.getDate() && a.getMonth() === b.getMonth() && a.getFullYear() === b.getFullYear();
}
function getDateTime(dateTime) {
  const matchPreset = {
    [
      "later-today"
      /* LaterToday */
    ]: () => {
      const now = /* @__PURE__ */ new Date();
      const evening = /* @__PURE__ */ new Date();
      evening.setHours(18, 0, 0, 0);
      const cutoff = /* @__PURE__ */ new Date();
      cutoff.setHours(17, 0, 0, 0);
      if (now >= cutoff) {
        return null;
      }
      return evening;
    },
    [
      "tomorrow"
      /* Tomorrow */
    ]: () => {
      const now = /* @__PURE__ */ new Date();
      const day = /* @__PURE__ */ new Date();
      day.setDate(now.getDate() + 1);
      day.setHours(8, 0, 0, 0);
      return day;
    },
    [
      "this-weekend"
      /* ThisWeekend */
    ]: () => {
      const today = /* @__PURE__ */ new Date();
      if ([
        5,
        // Friday
        6,
        // Saturday
        0
        // Sunday
      ].includes(today.getDay())) {
        return null;
      }
      const saturday = /* @__PURE__ */ new Date();
      const firstWorkdayOfWeek = getFirstWorkdayOfWeek();
      saturday.setDate(firstWorkdayOfWeek.getDate() + 5);
      saturday.setHours(8, 0, 0, 0);
      return saturday;
    },
    [
      "next-week"
      /* NextWeek */
    ]: () => {
      const today = /* @__PURE__ */ new Date();
      if (today.getDay() === 0) {
        return null;
      }
      const workday = /* @__PURE__ */ new Date();
      const firstWorkdayOfWeek = getFirstWorkdayOfWeek();
      workday.setDate(firstWorkdayOfWeek.getDate() + 7);
      workday.setHours(8, 0, 0, 0);
      return workday;
    }
  };
  return matchPreset[dateTime]();
}
function getInitialCustomDueDate() {
  const now = /* @__PURE__ */ new Date();
  const dueDate = /* @__PURE__ */ new Date();
  dueDate.setHours(now.getHours() + 2, 0, 0, 0);
  return dueDate;
}
function getDateString(dueDate) {
  let formatOptions = {
    hour: "numeric",
    minute: "2-digit"
  };
  const today = /* @__PURE__ */ new Date();
  if (!isSameDate(dueDate, today)) {
    formatOptions = {
      ...formatOptions,
      weekday: "short"
    };
  }
  if (!isSameWeek(dueDate, today)) {
    formatOptions = {
      ...formatOptions,
      month: "short",
      day: "numeric"
    };
  }
  if (dueDate.getFullYear() !== today.getFullYear()) {
    formatOptions = {
      ...formatOptions,
      year: "numeric"
    };
  }
  return dueDate.toLocaleString(
    getCanonicalLocale(),
    formatOptions
  );
}
function getVerboseDateString(dueDate) {
  let formatOptions = {
    month: "long",
    day: "numeric",
    weekday: "long",
    hour: "numeric",
    minute: "2-digit"
  };
  const today = /* @__PURE__ */ new Date();
  if (dueDate.getFullYear() !== today.getFullYear()) {
    formatOptions = {
      ...formatOptions,
      year: "numeric"
    };
  }
  return dueDate.toLocaleString(
    getCanonicalLocale(),
    formatOptions
  );
}
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const action$3 = {
  id: "clear-reminder",
  displayName: () => translate("files_reminders", "Clear reminder"),
  title: ({ nodes }) => {
    const node = nodes.at(0);
    const dueDate = new Date(node.attributes["reminder-due-date"]);
    return `${translate("files_reminders", "Clear reminder")} – ${getVerboseDateString(dueDate)}`;
  },
  iconSvgInline: () => AlarmOffSvg,
  enabled: ({ nodes }) => {
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes.at(0);
    const dueDate = node.attributes["reminder-due-date"];
    return Boolean(dueDate);
  },
  async exec({ nodes }) {
    const node = nodes.at(0);
    if (node.fileid) {
      try {
        await clearReminder(node.fileid);
        node.attributes["reminder-due-date"] = "";
        emit("files:node:updated", node);
        return true;
      } catch {
        return false;
      }
    }
    return null;
  },
  order: 19
};
const AlarmSvg = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-alarm" viewBox="0 0 24 24"><path d="M12,20A7,7 0 0,1 5,13A7,7 0 0,1 12,6A7,7 0 0,1 19,13A7,7 0 0,1 12,20M12,4A9,9 0 0,0 3,13A9,9 0 0,0 12,22A9,9 0 0,0 21,13A9,9 0 0,0 12,4M12.5,8H11V14L15.75,16.85L16.5,15.62L12.5,13.25V8M7.88,3.39L6.6,1.86L2,5.71L3.29,7.24L7.88,3.39M22,5.72L17.4,1.86L16.11,3.39L20.71,7.25L22,5.72Z" /></svg>';
const logger = getLoggerBuilder().setApp("files_reminders").detectUser().build();
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "SetCustomReminderModal",
  props: {
    node: { type: null, required: true }
  },
  emits: ["close"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const props = __props;
    const emit$1 = __emit;
    const hasDueDate = ref(false);
    const opened = ref(false);
    const isValid = ref(true);
    const customDueDate = ref();
    const nowDate = ref(/* @__PURE__ */ new Date());
    onBeforeMount(() => {
      const dueDate = props.node.attributes["reminder-due-date"] ? new Date(props.node.attributes["reminder-due-date"]) : void 0;
      hasDueDate.value = Boolean(dueDate);
      isValid.value = true;
      opened.value = true;
      customDueDate.value = dueDate ?? getInitialCustomDueDate();
      nowDate.value = /* @__PURE__ */ new Date();
    });
    onMounted(() => {
      const input = document.getElementById("set-custom-reminder");
      input.focus();
      if (!hasDueDate.value) {
        input.showPicker();
      }
    });
    async function setCustom() {
      if (!(customDueDate.value instanceof Date) || isNaN(customDueDate.value.getTime())) {
        showError(translate("files_reminders", "Please choose a valid date & time"));
        return;
      }
      try {
        await setReminder(props.node.fileid, customDueDate.value);
        const node = props.node.clone();
        node.attributes["reminder-due-date"] = customDueDate.value.toISOString();
        emit("files:node:updated", node);
        showSuccess(translate("files_reminders", 'Reminder set for "{fileName}"', { fileName: props.node.displayname }));
        onClose();
      } catch (error) {
        logger.error("Failed to set reminder", { error });
        showError(translate("files_reminders", "Failed to set reminder"));
      }
    }
    async function clear() {
      try {
        await clearReminder(props.node.fileid);
        const node = props.node.clone();
        node.attributes["reminder-due-date"] = "";
        emit("files:node:updated", node);
        showSuccess(translate("files_reminders", 'Reminder cleared for "{fileName}"', { fileName: props.node.displayname }));
        onClose();
      } catch (error) {
        logger.error("Failed to clear reminder", { error });
        showError(translate("files_reminders", "Failed to clear reminder"));
      }
    }
    function onClose() {
      opened.value = false;
      emit$1("close");
    }
    function onInput() {
      const input = document.getElementById("set-custom-reminder");
      isValid.value = input.checkValidity();
    }
    const __returned__ = { props, emit: emit$1, hasDueDate, opened, isValid, customDueDate, nowDate, setCustom, clear, onClose, onInput, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcDateTime() {
      return _sfc_main$1;
    }, get NcDateTimePickerNative() {
      return NcDateTimePickerNative;
    }, get NcDialog() {
      return NcDialog;
    }, get NcNoteCard() {
      return NcNoteCard;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return $setup.opened ? (openBlock(), createBlock($setup["NcDialog"], {
    key: 0,
    name: $setup.t("files_reminders", `Set reminder for '{fileName}'`, { fileName: $props.node.displayname }),
    outTransition: "",
    size: "small",
    closeOnClickOutside: "",
    onClosing: $setup.onClose
  }, {
    actions: withCtx(() => [
      createCommentVNode(" Cancel pick "),
      createVNode($setup["NcButton"], {
        variant: "tertiary",
        onClick: $setup.onClose
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("files_reminders", "Cancel")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }),
      createCommentVNode(" Clear reminder "),
      $setup.hasDueDate ? (openBlock(), createBlock($setup["NcButton"], {
        key: 0,
        onClick: $setup.clear
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("files_reminders", "Clear reminder")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      })) : createCommentVNode("v-if", true),
      createCommentVNode(" Set reminder "),
      createVNode($setup["NcButton"], {
        disabled: !$setup.isValid,
        variant: "primary",
        form: "set-custom-reminder-form",
        type: "submit"
      }, {
        default: withCtx(() => [
          createTextVNode(
            toDisplayString($setup.t("files_reminders", "Set reminder")),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["disabled"])
    ]),
    default: withCtx(() => [
      createBaseVNode(
        "form",
        {
          id: "set-custom-reminder-form",
          class: "custom-reminder-modal",
          onSubmit: withModifiers($setup.setCustom, ["prevent"])
        },
        [
          createVNode($setup["NcDateTimePickerNative"], {
            id: "set-custom-reminder",
            modelValue: $setup.customDueDate,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $setup.customDueDate = $event),
            label: $setup.t("files_reminders", "Reminder at custom date & time"),
            min: $setup.nowDate,
            required: true,
            type: "datetime-local",
            onInput: $setup.onInput
          }, null, 8, ["modelValue", "label", "min"]),
          $setup.isValid && $setup.customDueDate ? (openBlock(), createBlock($setup["NcNoteCard"], {
            key: 0,
            type: "info"
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("files_reminders", "We will remind you of this file")) + " ",
                1
                /* TEXT */
              ),
              createVNode($setup["NcDateTime"], { timestamp: $setup.customDueDate }, null, 8, ["timestamp"])
            ]),
            _: 1
            /* STABLE */
          })) : (openBlock(), createBlock($setup["NcNoteCard"], {
            key: 1,
            type: "error"
          }, {
            default: withCtx(() => [
              createTextVNode(
                toDisplayString($setup.t("files_reminders", "Please choose a valid date & time")),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          }))
        ],
        32
        /* NEED_HYDRATION */
      )
    ]),
    _: 1
    /* STABLE */
  }, 8, ["name"])) : createCommentVNode("v-if", true);
}
const SetCustomReminderModal = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-f712c8b1"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/files_reminders/src/components/SetCustomReminderModal.vue"]]);
async function pickCustomDate(node) {
  await spawnDialog(SetCustomReminderModal, {
    node
  });
}
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const action$2 = {
  id: "reminder-status",
  inline: () => true,
  displayName: () => "",
  title: ({ nodes }) => {
    const node = nodes.at(0);
    const dueDate = new Date(node.attributes["reminder-due-date"]);
    return `${translate("files_reminders", "Reminder set")} – ${getVerboseDateString(dueDate)}`;
  },
  iconSvgInline: () => AlarmSvg,
  enabled: ({ nodes }) => {
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes.at(0);
    const dueDate = node.attributes["reminder-due-date"];
    return Boolean(dueDate);
  },
  async exec({ nodes }) {
    const node = nodes.at(0);
    await pickCustomDate(node);
    return null;
  },
  order: -15
};
const CalendarClockSvg = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-calendar-clock" viewBox="0 0 24 24"><path d="M15,13H16.5V15.82L18.94,17.23L18.19,18.53L15,16.69V13M19,8H5V19H9.67C9.24,18.09 9,17.07 9,16A7,7 0 0,1 16,9C17.07,9 18.09,9.24 19,9.67V8M5,21C3.89,21 3,20.1 3,19V5C3,3.89 3.89,3 5,3H6V1H8V3H16V1H18V3H19A2,2 0 0,1 21,5V11.1C22.24,12.36 23,14.09 23,16A7,7 0 0,1 16,23C14.09,23 12.36,22.24 11.1,21H5M16,11.15A4.85,4.85 0 0,0 11.15,16C11.15,18.68 13.32,20.85 16,20.85A4.85,4.85 0 0,0 20.85,16C20.85,13.32 18.68,11.15 16,11.15Z" /></svg>';
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const SET_REMINDER_MENU_ID = "set-reminder-menu";
const action$1 = {
  id: SET_REMINDER_MENU_ID,
  displayName: () => translate("files_reminders", "Set reminder"),
  iconSvgInline: () => AlarmSvg,
  enabled: ({ nodes, view }) => {
    if (view.id === "trashbin") {
      return false;
    }
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes.at(0);
    const dueDate = node.attributes["reminder-due-date"];
    return dueDate !== void 0;
  },
  async exec() {
    return null;
  },
  order: 20
};
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const action = {
  id: "set-reminder-custom",
  displayName: () => translate("files_reminders", "Custom reminder"),
  title: () => translate("files_reminders", "Reminder at custom date & time"),
  iconSvgInline: () => CalendarClockSvg,
  enabled: ({ nodes, view }) => {
    if (view.id === "trashbin") {
      return false;
    }
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes.at(0);
    const dueDate = node.attributes["reminder-due-date"];
    return dueDate !== void 0;
  },
  parent: SET_REMINDER_MENU_ID,
  async exec({ nodes }) {
    const node = nodes.at(0);
    pickCustomDate(node);
    return null;
  },
  // After presets
  order: 22
};
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const laterToday = {
  dateTimePreset: DateTimePreset.LaterToday,
  label: translate("files_reminders", "Later today"),
  ariaLabel: translate("files_reminders", "Set reminder for later today"),
  dateString: "",
  verboseDateString: ""
};
const tomorrow = {
  dateTimePreset: DateTimePreset.Tomorrow,
  label: translate("files_reminders", "Tomorrow"),
  ariaLabel: translate("files_reminders", "Set reminder for tomorrow"),
  dateString: "",
  verboseDateString: ""
};
const thisWeekend = {
  dateTimePreset: DateTimePreset.ThisWeekend,
  label: translate("files_reminders", "This weekend"),
  ariaLabel: translate("files_reminders", "Set reminder for this weekend"),
  dateString: "",
  verboseDateString: ""
};
const nextWeek = {
  dateTimePreset: DateTimePreset.NextWeek,
  label: translate("files_reminders", "Next week"),
  ariaLabel: translate("files_reminders", "Set reminder for next week"),
  dateString: "",
  verboseDateString: ""
};
function getSetReminderSuggestionActions() {
  [laterToday, tomorrow, thisWeekend, nextWeek].forEach((option) => {
    const dateTime = getDateTime(option.dateTimePreset);
    if (!dateTime) {
      return;
    }
    option.dateString = getDateString(dateTime);
    option.verboseDateString = getVerboseDateString(dateTime);
    setInterval(() => {
      const dateTime2 = getDateTime(option.dateTimePreset);
      if (!dateTime2) {
        return;
      }
      option.dateString = getDateString(dateTime2);
      option.verboseDateString = getVerboseDateString(dateTime2);
    }, 1e3 * 30 * 60);
  });
  return [laterToday, tomorrow, thisWeekend, nextWeek].map(generateFileAction);
}
function generateFileAction(option) {
  return {
    id: `set-reminder-${option.dateTimePreset}`,
    displayName: () => `${option.label} – ${option.dateString}`,
    title: () => `${option.ariaLabel} – ${option.verboseDateString}`,
    // Empty svg to hide the icon
    iconSvgInline: () => "<svg></svg>",
    enabled: ({ nodes, view }) => {
      if (view.id === "trashbin") {
        return false;
      }
      if (nodes.length !== 1) {
        return false;
      }
      const node = nodes.at(0);
      const dueDate = node.attributes["reminder-due-date"];
      return dueDate !== void 0 && Boolean(getDateTime(option.dateTimePreset));
    },
    parent: SET_REMINDER_MENU_ID,
    async exec({ nodes }) {
      const node = nodes.at(0);
      if (!node.fileid) {
        logger.error("Failed to set reminder, missing file id");
        showError(translate("files_reminders", "Failed to set reminder"));
        return null;
      }
      try {
        const dateTime = getDateTime(option.dateTimePreset);
        await setReminder(node.fileid, dateTime);
        node.attributes["reminder-due-date"] = dateTime.toISOString();
        emit("files:node:updated", node);
        showSuccess(translate("files_reminders", 'Reminder set for "{fileName}"', { fileName: node.basename }));
      } catch (error) {
        logger.error("Failed to set reminder", { error });
        showError(translate("files_reminders", "Failed to set reminder"));
      }
      return null;
    },
    order: 21
  };
}
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
registerDavProperty("nc:reminder-due-date", { nc: "http://nextcloud.org/ns" });
registerFileAction(action$2);
registerFileAction(action$3);
registerFileAction(action$1);
registerFileAction(action);
getSetReminderSuggestionActions().forEach(registerFileAction);
//# sourceMappingURL=files_reminders-init.mjs.map
