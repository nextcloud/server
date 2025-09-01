/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files_reminders/src/actions/clearReminderAction.ts":
/*!*****************************************************************!*\
  !*** ./apps/files_reminders/src/actions/clearReminderAction.ts ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_alarm_off_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/alarm-off.svg?raw */ "./node_modules/@mdi/svg/svg/alarm-off.svg?raw");
/* harmony import */ var _services_reminderService_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/reminderService.ts */ "./apps/files_reminders/src/services/reminderService.ts");
/* harmony import */ var _shared_utils_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../shared/utils.ts */ "./apps/files_reminders/src/shared/utils.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
  id: 'clear-reminder',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Clear reminder'),
  title: nodes => {
    const node = nodes.at(0);
    const dueDate = new Date(node.attributes['reminder-due-date']);
    return `${(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Clear reminder')} – ${(0,_shared_utils_ts__WEBPACK_IMPORTED_MODULE_6__.getVerboseDateString)(dueDate)}`;
  },
  iconSvgInline: () => _mdi_svg_svg_alarm_off_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
  enabled: nodes => {
    // Only allow on a single node
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes.at(0);
    const dueDate = node.attributes['reminder-due-date'];
    return Boolean(dueDate);
  },
  async exec(node) {
    if (node.fileid) {
      try {
        await (0,_services_reminderService_ts__WEBPACK_IMPORTED_MODULE_5__.clearReminder)(node.fileid);
        vue__WEBPACK_IMPORTED_MODULE_0__["default"].set(node.attributes, 'reminder-due-date', '');
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:updated', node);
        return true;
      } catch (error) {
        return false;
      }
    }
    return null;
  },
  order: 19
});

/***/ }),

/***/ "./apps/files_reminders/src/actions/reminderStatusAction.ts":
/*!******************************************************************!*\
  !*** ./apps/files_reminders/src/actions/reminderStatusAction.ts ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_alarm_svg_raw__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/svg/svg/alarm.svg?raw */ "./node_modules/@mdi/svg/svg/alarm.svg?raw");
/* harmony import */ var _services_customPicker_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/customPicker.ts */ "./apps/files_reminders/src/services/customPicker.ts");
/* harmony import */ var _shared_utils_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../shared/utils.ts */ "./apps/files_reminders/src/shared/utils.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: 'reminder-status',
  inline: () => true,
  displayName: () => '',
  title: nodes => {
    const node = nodes.at(0);
    const dueDate = new Date(node.attributes['reminder-due-date']);
    return `${(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_reminders', 'Reminder set')} – ${(0,_shared_utils_ts__WEBPACK_IMPORTED_MODULE_4__.getVerboseDateString)(dueDate)}`;
  },
  iconSvgInline: () => _mdi_svg_svg_alarm_svg_raw__WEBPACK_IMPORTED_MODULE_2__,
  enabled: nodes => {
    // Only allow on a single node
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes.at(0);
    const dueDate = node.attributes['reminder-due-date'];
    return Boolean(dueDate);
  },
  async exec(node) {
    (0,_services_customPicker_ts__WEBPACK_IMPORTED_MODULE_3__.pickCustomDate)(node);
    return null;
  },
  order: -15
});

/***/ }),

/***/ "./apps/files_reminders/src/actions/setReminderCustomAction.ts":
/*!*********************************************************************!*\
  !*** ./apps/files_reminders/src/actions/setReminderCustomAction.ts ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_calendar_clock_svg_raw__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/svg/svg/calendar-clock.svg?raw */ "./node_modules/@mdi/svg/svg/calendar-clock.svg?raw");
/* harmony import */ var _setReminderMenuAction__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./setReminderMenuAction */ "./apps/files_reminders/src/actions/setReminderMenuAction.ts");
/* harmony import */ var _services_customPicker__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../services/customPicker */ "./apps/files_reminders/src/services/customPicker.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: 'set-reminder-custom',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_reminders', 'Custom reminder'),
  title: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_reminders', 'Reminder at custom date & time'),
  iconSvgInline: () => _mdi_svg_svg_calendar_clock_svg_raw__WEBPACK_IMPORTED_MODULE_2__,
  enabled: (nodes, view) => {
    if (view.id === 'trashbin') {
      return false;
    }
    // Only allow on a single node
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes.at(0);
    const dueDate = node.attributes['reminder-due-date'];
    return dueDate !== undefined;
  },
  parent: _setReminderMenuAction__WEBPACK_IMPORTED_MODULE_3__.SET_REMINDER_MENU_ID,
  async exec(file) {
    (0,_services_customPicker__WEBPACK_IMPORTED_MODULE_4__.pickCustomDate)(file);
    return null;
  },
  // After presets
  order: 22
});

/***/ }),

/***/ "./apps/files_reminders/src/actions/setReminderMenuAction.ts":
/*!*******************************************************************!*\
  !*** ./apps/files_reminders/src/actions/setReminderMenuAction.ts ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SET_REMINDER_MENU_ID: () => (/* binding */ SET_REMINDER_MENU_ID),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_alarm_svg_raw__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/svg/svg/alarm.svg?raw */ "./node_modules/@mdi/svg/svg/alarm.svg?raw");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



const SET_REMINDER_MENU_ID = 'set-reminder-menu';
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: SET_REMINDER_MENU_ID,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_reminders', 'Set reminder'),
  iconSvgInline: () => _mdi_svg_svg_alarm_svg_raw__WEBPACK_IMPORTED_MODULE_2__,
  enabled: (nodes, view) => {
    if (view.id === 'trashbin') {
      return false;
    }
    // Only allow on a single node
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes.at(0);
    const dueDate = node.attributes['reminder-due-date'];
    return dueDate !== undefined;
  },
  async exec() {
    return null;
  },
  order: 20
});

/***/ }),

/***/ "./apps/files_reminders/src/actions/setReminderSuggestionActions.scss":
/*!****************************************************************************!*\
  !*** ./apps/files_reminders/src/actions/setReminderSuggestionActions.scss ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_setReminderSuggestionActions_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./setReminderSuggestionActions.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_reminders/src/actions/setReminderSuggestionActions.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_setReminderSuggestionActions_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_setReminderSuggestionActions_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_setReminderSuggestionActions_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_setReminderSuggestionActions_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/files_reminders/src/actions/setReminderSuggestionActions.ts":
/*!**************************************************************************!*\
  !*** ./apps/files_reminders/src/actions/setReminderSuggestionActions.ts ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   actions: () => (/* binding */ actions)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _shared_utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../shared/utils */ "./apps/files_reminders/src/shared/utils.ts");
/* harmony import */ var _shared_logger__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../shared/logger */ "./apps/files_reminders/src/shared/logger.ts");
/* harmony import */ var _setReminderMenuAction__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./setReminderMenuAction */ "./apps/files_reminders/src/actions/setReminderMenuAction.ts");
/* harmony import */ var _services_reminderService__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../services/reminderService */ "./apps/files_reminders/src/services/reminderService.ts");
/* harmony import */ var _setReminderSuggestionActions_scss__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./setReminderSuggestionActions.scss */ "./apps/files_reminders/src/actions/setReminderSuggestionActions.scss");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */










const laterToday = {
  dateTimePreset: _shared_utils__WEBPACK_IMPORTED_MODULE_5__.DateTimePreset.LaterToday,
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Later today'),
  ariaLabel: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Set reminder for later today'),
  dateString: '',
  verboseDateString: ''
};
const tomorrow = {
  dateTimePreset: _shared_utils__WEBPACK_IMPORTED_MODULE_5__.DateTimePreset.Tomorrow,
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Tomorrow'),
  ariaLabel: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Set reminder for tomorrow'),
  dateString: '',
  verboseDateString: ''
};
const thisWeekend = {
  dateTimePreset: _shared_utils__WEBPACK_IMPORTED_MODULE_5__.DateTimePreset.ThisWeekend,
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'This weekend'),
  ariaLabel: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Set reminder for this weekend'),
  dateString: '',
  verboseDateString: ''
};
const nextWeek = {
  dateTimePreset: _shared_utils__WEBPACK_IMPORTED_MODULE_5__.DateTimePreset.NextWeek,
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Next week'),
  ariaLabel: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Set reminder for next week'),
  dateString: '',
  verboseDateString: ''
};
/**
 * Generate a file action for the given option
 *
 * @param option The option to generate the action for
 * @return The file action or null if the option should not be shown
 */
const generateFileAction = option => {
  return new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
    id: `set-reminder-${option.dateTimePreset}`,
    displayName: () => `${option.label} – ${option.dateString}`,
    title: () => `${option.ariaLabel} – ${option.verboseDateString}`,
    // Empty svg to hide the icon
    iconSvgInline: () => '<svg></svg>',
    enabled: (nodes, view) => {
      if (view.id === 'trashbin') {
        return false;
      }
      // Only allow on a single node
      if (nodes.length !== 1) {
        return false;
      }
      const node = nodes.at(0);
      const dueDate = node.attributes['reminder-due-date'];
      return dueDate !== undefined && Boolean((0,_shared_utils__WEBPACK_IMPORTED_MODULE_5__.getDateTime)(option.dateTimePreset));
    },
    parent: _setReminderMenuAction__WEBPACK_IMPORTED_MODULE_7__.SET_REMINDER_MENU_ID,
    async exec(node) {
      // Can't really happen, but just in case™
      if (!node.fileid) {
        _shared_logger__WEBPACK_IMPORTED_MODULE_6__.logger.error('Failed to set reminder, missing file id');
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Failed to set reminder'));
        return null;
      }
      // Set the reminder
      try {
        const dateTime = (0,_shared_utils__WEBPACK_IMPORTED_MODULE_5__.getDateTime)(option.dateTimePreset);
        await (0,_services_reminderService__WEBPACK_IMPORTED_MODULE_8__.setReminder)(node.fileid, dateTime);
        vue__WEBPACK_IMPORTED_MODULE_0__["default"].set(node.attributes, 'reminder-due-date', dateTime.toISOString());
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:updated', node);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Reminder set for "{fileName}"', {
          fileName: node.basename
        }));
      } catch (error) {
        _shared_logger__WEBPACK_IMPORTED_MODULE_6__.logger.error('Failed to set reminder', {
          error
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files_reminders', 'Failed to set reminder'));
      }
      // Silent success as we display our own notification
      return null;
    },
    order: 21
  });
};
[laterToday, tomorrow, thisWeekend, nextWeek].forEach(option => {
  // Generate the initial date string
  const dateTime = (0,_shared_utils__WEBPACK_IMPORTED_MODULE_5__.getDateTime)(option.dateTimePreset);
  if (!dateTime) {
    return;
  }
  option.dateString = (0,_shared_utils__WEBPACK_IMPORTED_MODULE_5__.getDateString)(dateTime);
  option.verboseDateString = (0,_shared_utils__WEBPACK_IMPORTED_MODULE_5__.getVerboseDateString)(dateTime);
  // Update the date string every 30 minutes
  setInterval(() => {
    const dateTime = (0,_shared_utils__WEBPACK_IMPORTED_MODULE_5__.getDateTime)(option.dateTimePreset);
    if (!dateTime) {
      return;
    }
    // update the submenu remind options strings
    option.dateString = (0,_shared_utils__WEBPACK_IMPORTED_MODULE_5__.getDateString)(dateTime);
    option.verboseDateString = (0,_shared_utils__WEBPACK_IMPORTED_MODULE_5__.getVerboseDateString)(dateTime);
  }, 1000 * 30 * 60);
});
// Generate the default preset actions
const actions = [laterToday, tomorrow, thisWeekend, nextWeek].map(generateFileAction);

/***/ }),

/***/ "./apps/files_reminders/src/components/SetCustomReminderModal.vue":
/*!************************************************************************!*\
  !*** ./apps/files_reminders/src/components/SetCustomReminderModal.vue ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SetCustomReminderModal_vue_vue_type_template_id_553ca1de_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SetCustomReminderModal.vue?vue&type=template&id=553ca1de&scoped=true */ "./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=template&id=553ca1de&scoped=true");
/* harmony import */ var _SetCustomReminderModal_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SetCustomReminderModal.vue?vue&type=script&lang=ts */ "./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=script&lang=ts");
/* harmony import */ var _SetCustomReminderModal_vue_vue_type_style_index_0_id_553ca1de_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true */ "./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SetCustomReminderModal_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SetCustomReminderModal_vue_vue_type_template_id_553ca1de_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SetCustomReminderModal_vue_vue_type_template_id_553ca1de_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "553ca1de",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_reminders/src/components/SetCustomReminderModal.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=script&lang=ts":
/*!************************************************************************************************!*\
  !*** ./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=script&lang=ts ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SetCustomReminderModal.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true":
/*!*********************************************************************************************************************************!*\
  !*** ./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true ***!
  \*********************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_style_index_0_id_553ca1de_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=template&id=553ca1de&scoped=true":
/*!******************************************************************************************************************!*\
  !*** ./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=template&id=553ca1de&scoped=true ***!
  \******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_template_id_553ca1de_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_template_id_553ca1de_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_template_id_553ca1de_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SetCustomReminderModal.vue?vue&type=template&id=553ca1de&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=template&id=553ca1de&scoped=true");


/***/ }),

/***/ "./apps/files_reminders/src/init.ts":
/*!******************************************!*\
  !*** ./apps/files_reminders/src/init.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _actions_reminderStatusAction__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./actions/reminderStatusAction */ "./apps/files_reminders/src/actions/reminderStatusAction.ts");
/* harmony import */ var _actions_clearReminderAction__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./actions/clearReminderAction */ "./apps/files_reminders/src/actions/clearReminderAction.ts");
/* harmony import */ var _actions_setReminderMenuAction__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./actions/setReminderMenuAction */ "./apps/files_reminders/src/actions/setReminderMenuAction.ts");
/* harmony import */ var _actions_setReminderSuggestionActions__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./actions/setReminderSuggestionActions */ "./apps/files_reminders/src/actions/setReminderSuggestionActions.ts");
/* harmony import */ var _actions_setReminderCustomAction__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./actions/setReminderCustomAction */ "./apps/files_reminders/src/actions/setReminderCustomAction.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerDavProperty)('nc:reminder-due-date', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_reminderStatusAction__WEBPACK_IMPORTED_MODULE_1__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_clearReminderAction__WEBPACK_IMPORTED_MODULE_2__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_setReminderMenuAction__WEBPACK_IMPORTED_MODULE_3__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_setReminderCustomAction__WEBPACK_IMPORTED_MODULE_5__.action);
_actions_setReminderSuggestionActions__WEBPACK_IMPORTED_MODULE_4__.actions.forEach(action => (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(action));

/***/ }),

/***/ "./apps/files_reminders/src/services/customPicker.ts":
/*!***********************************************************!*\
  !*** ./apps/files_reminders/src/services/customPicker.ts ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   pickCustomDate: () => (/* binding */ pickCustomDate)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_SetCustomReminderModal_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../components/SetCustomReminderModal.vue */ "./apps/files_reminders/src/components/SetCustomReminderModal.vue");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const View = vue__WEBPACK_IMPORTED_MODULE_0__["default"].extend(_components_SetCustomReminderModal_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
const mount = document.createElement('div');
mount.id = 'set-custom-reminder-modal';
document.body.appendChild(mount);
// Create a new Vue instance and mount it to our modal container
const CustomReminderModal = new View({
  name: 'SetCustomReminderModal',
  el: mount
});
const pickCustomDate = node => {
  CustomReminderModal.open(node);
  // Wait for the modal to close
  return new Promise(resolve => {
    CustomReminderModal.$once('close', resolve);
  });
};

/***/ }),

/***/ "./apps/files_reminders/src/services/reminderService.ts":
/*!**************************************************************!*\
  !*** ./apps/files_reminders/src/services/reminderService.ts ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   clearReminder: () => (/* binding */ clearReminder),
/* harmony export */   getReminder: () => (/* binding */ getReminder),
/* harmony export */   setReminder: () => (/* binding */ setReminder)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const getReminder = async fileId => {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/apps/files_reminders/api/v1/{fileId}', {
    fileId
  });
  const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
  const dueDate = response.data.ocs.data.dueDate ? new Date(response.data.ocs.data.dueDate) : null;
  return {
    dueDate
  };
};
const setReminder = async (fileId, dueDate) => {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/apps/files_reminders/api/v1/{fileId}', {
    fileId
  });
  const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
    dueDate: dueDate.toISOString() // timezone of string is always UTC
  });
  return response.data.ocs.data;
};
const clearReminder = async fileId => {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/apps/files_reminders/api/v1/{fileId}', {
    fileId
  });
  const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(url);
  return response.data.ocs.data;
};

/***/ }),

/***/ "./apps/files_reminders/src/shared/logger.ts":
/*!***************************************************!*\
  !*** ./apps/files_reminders/src/shared/logger.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   logger: () => (/* binding */ logger)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files_reminders').detectUser().build();

/***/ }),

/***/ "./apps/files_reminders/src/shared/utils.ts":
/*!**************************************************!*\
  !*** ./apps/files_reminders/src/shared/utils.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DateTimePreset: () => (/* binding */ DateTimePreset),
/* harmony export */   getDateString: () => (/* binding */ getDateString),
/* harmony export */   getDateTime: () => (/* binding */ getDateTime),
/* harmony export */   getInitialCustomDueDate: () => (/* binding */ getInitialCustomDueDate),
/* harmony export */   getVerboseDateString: () => (/* binding */ getVerboseDateString)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

var DateTimePreset;
(function (DateTimePreset) {
  DateTimePreset["LaterToday"] = "later-today";
  DateTimePreset["Tomorrow"] = "tomorrow";
  DateTimePreset["ThisWeekend"] = "this-weekend";
  DateTimePreset["NextWeek"] = "next-week";
})(DateTimePreset || (DateTimePreset = {}));
const getFirstWorkdayOfWeek = () => {
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  now.setDate(now.getDate() - now.getDay() + 1);
  return new Date(now);
};
const getWeek = date => {
  const dateClone = new Date(date);
  dateClone.setHours(0, 0, 0, 0);
  const firstDayOfYear = new Date(date.getFullYear(), 0, 1, 0, 0, 0, 0);
  const daysFromFirstDay = (date.getTime() - firstDayOfYear.getTime()) / 86400000;
  return Math.ceil((daysFromFirstDay + firstDayOfYear.getDay() + 1) / 7);
};
const isSameWeek = (a, b) => {
  return getWeek(a) === getWeek(b) && a.getFullYear() === b.getFullYear();
};
const isSameDate = (a, b) => {
  return a.getDate() === b.getDate() && a.getMonth() === b.getMonth() && a.getFullYear() === b.getFullYear();
};
const getDateTime = dateTime => {
  const matchPreset = {
    [DateTimePreset.LaterToday]: () => {
      const now = new Date();
      const evening = new Date();
      evening.setHours(18, 0, 0, 0);
      const cutoff = new Date();
      cutoff.setHours(17, 0, 0, 0);
      if (now >= cutoff) {
        return null;
      }
      return evening;
    },
    [DateTimePreset.Tomorrow]: () => {
      const now = new Date();
      const day = new Date();
      day.setDate(now.getDate() + 1);
      day.setHours(8, 0, 0, 0);
      return day;
    },
    [DateTimePreset.ThisWeekend]: () => {
      const today = new Date();
      if ([5,
      // Friday
      6,
      // Saturday
      0 // Sunday
      ].includes(today.getDay())) {
        return null;
      }
      const saturday = new Date();
      const firstWorkdayOfWeek = getFirstWorkdayOfWeek();
      saturday.setDate(firstWorkdayOfWeek.getDate() + 5);
      saturday.setHours(8, 0, 0, 0);
      return saturday;
    },
    [DateTimePreset.NextWeek]: () => {
      const today = new Date();
      if (today.getDay() === 0) {
        // Sunday
        return null;
      }
      const workday = new Date();
      const firstWorkdayOfWeek = getFirstWorkdayOfWeek();
      workday.setDate(firstWorkdayOfWeek.getDate() + 7);
      workday.setHours(8, 0, 0, 0);
      return workday;
    }
  };
  return matchPreset[dateTime]();
};
const getInitialCustomDueDate = () => {
  const now = new Date();
  const dueDate = new Date();
  dueDate.setHours(now.getHours() + 2, 0, 0, 0);
  return dueDate;
};
const getDateString = dueDate => {
  let formatOptions = {
    hour: 'numeric',
    minute: '2-digit'
  };
  const today = new Date();
  if (!isSameDate(dueDate, today)) {
    formatOptions = {
      ...formatOptions,
      weekday: 'short'
    };
  }
  if (!isSameWeek(dueDate, today)) {
    formatOptions = {
      ...formatOptions,
      month: 'short',
      day: 'numeric'
    };
  }
  if (dueDate.getFullYear() !== today.getFullYear()) {
    formatOptions = {
      ...formatOptions,
      year: 'numeric'
    };
  }
  return dueDate.toLocaleString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.getCanonicalLocale)(), formatOptions);
};
const getVerboseDateString = dueDate => {
  let formatOptions = {
    month: 'long',
    day: 'numeric',
    weekday: 'long',
    hour: 'numeric',
    minute: '2-digit'
  };
  const today = new Date();
  if (dueDate.getFullYear() !== today.getFullYear()) {
    formatOptions = {
      ...formatOptions,
      year: 'numeric'
    };
  }
  return dueDate.toLocaleString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.getCanonicalLocale)(), formatOptions);
};

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/alarm-off.svg?raw":
/*!*****************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/alarm-off.svg?raw ***!
  \*****************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-alarm-off\" viewBox=\"0 0 24 24\"><path d=\"M8,3.28L6.6,1.86L5.74,2.57L7.16,4M16.47,18.39C15.26,19.39 13.7,20 12,20A7,7 0 0,1 5,13C5,11.3 5.61,9.74 6.61,8.53M2.92,2.29L1.65,3.57L3,4.9L1.87,5.83L3.29,7.25L4.4,6.31L5.2,7.11C3.83,8.69 3,10.75 3,13A9,9 0 0,0 12,22C14.25,22 16.31,21.17 17.89,19.8L20.09,22L21.36,20.73L3.89,3.27L2.92,2.29M22,5.72L17.4,1.86L16.11,3.39L20.71,7.25L22,5.72M12,6A7,7 0 0,1 19,13C19,13.84 18.84,14.65 18.57,15.4L20.09,16.92C20.67,15.73 21,14.41 21,13A9,9 0 0,0 12,4C10.59,4 9.27,4.33 8.08,4.91L9.6,6.43C10.35,6.16 11.16,6 12,6Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/alarm.svg?raw":
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/alarm.svg?raw ***!
  \*************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-alarm\" viewBox=\"0 0 24 24\"><path d=\"M12,20A7,7 0 0,1 5,13A7,7 0 0,1 12,6A7,7 0 0,1 19,13A7,7 0 0,1 12,20M12,4A9,9 0 0,0 3,13A9,9 0 0,0 12,22A9,9 0 0,0 21,13A9,9 0 0,0 12,4M12.5,8H11V14L15.75,16.85L16.5,15.62L12.5,13.25V8M7.88,3.39L6.6,1.86L2,5.71L3.29,7.24L7.88,3.39M22,5.72L17.4,1.86L16.11,3.39L20.71,7.25L22,5.72Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/calendar-clock.svg?raw":
/*!**********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/calendar-clock.svg?raw ***!
  \**********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-calendar-clock\" viewBox=\"0 0 24 24\"><path d=\"M15,13H16.5V15.82L18.94,17.23L18.19,18.53L15,16.69V13M19,8H5V19H9.67C9.24,18.09 9,17.07 9,16A7,7 0 0,1 16,9C17.07,9 18.09,9.24 19,9.67V8M5,21C3.89,21 3,20.1 3,19V5C3,3.89 3.89,3 5,3H6V1H8V3H16V1H18V3H19A2,2 0 0,1 21,5V11.1C22.24,12.36 23,14.09 23,16A7,7 0 0,1 16,23C14.09,23 12.36,22.24 11.1,21H5M16,11.15A4.85,4.85 0 0,0 11.15,16C11.15,18.68 13.32,20.85 16,20.85A4.85,4.85 0 0,0 20.85,16C20.85,13.32 18.68,11.15 16,11.15Z\" /></svg>";

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=script&lang=ts":
/*!**************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=script&lang=ts ***!
  \**************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDateTime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcDateTime */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTime.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDateTimePickerNative__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcDateTimePickerNative */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTimePickerNative.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcDialog */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _shared_utils_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../shared/utils.ts */ "./apps/files_reminders/src/shared/utils.ts");
/* harmony import */ var _shared_logger_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../shared/logger.ts */ "./apps/files_reminders/src/shared/logger.ts");
/* harmony import */ var _services_reminderService_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../services/reminderService.ts */ "./apps/files_reminders/src/services/reminderService.ts");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_0__["default"].extend({
  name: 'SetCustomReminderModal',
  components: {
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcDateTime: _nextcloud_vue_components_NcDateTime__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcDateTimePickerNative: _nextcloud_vue_components_NcDateTimePickerNative__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcDialog: _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_8__["default"]
  },
  data() {
    return {
      node: undefined,
      hasDueDate: false,
      opened: false,
      isValid: true,
      customDueDate: null,
      nowDate: new Date()
    };
  },
  computed: {
    fileId() {
      return this.node?.fileid;
    },
    fileName() {
      return this.node?.basename;
    },
    name() {
      return this.fileName ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Set reminder for "{fileName}"', {
        fileName: this.fileName
      }) : '';
    },
    label() {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Reminder at custom date & time');
    },
    clearAriaLabel() {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Clear reminder');
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate,
    getDateString: _shared_utils_ts__WEBPACK_IMPORTED_MODULE_9__.getDateString,
    /**
     * Open the modal to set a custom reminder
     * and reset the state.
     * @param node The node to set a reminder for
     */
    open(node) {
      const dueDate = node.attributes['reminder-due-date'] ? new Date(node.attributes['reminder-due-date']) : null;
      this.node = node;
      this.hasDueDate = Boolean(dueDate);
      this.isValid = true;
      this.opened = true;
      this.customDueDate = dueDate ?? (0,_shared_utils_ts__WEBPACK_IMPORTED_MODULE_9__.getInitialCustomDueDate)();
      this.nowDate = new Date();
      // Focus the input and show the picker after the animation
      setTimeout(() => {
        const input = document.getElementById('set-custom-reminder');
        input.focus();
        if (!this.hasDueDate) {
          input.showPicker();
        }
      }, 300);
    },
    async setCustom() {
      // Handle input cleared or invalid date
      if (!(this.customDueDate instanceof Date) || isNaN(this.customDueDate)) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Please choose a valid date & time'));
        return;
      }
      try {
        await (0,_services_reminderService_ts__WEBPACK_IMPORTED_MODULE_11__.setReminder)(this.fileId, this.customDueDate);
        vue__WEBPACK_IMPORTED_MODULE_0__["default"].set(this.node.attributes, 'reminder-due-date', this.customDueDate.toISOString());
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('files:node:updated', this.node);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Reminder set for "{fileName}"', {
          fileName: this.fileName
        }));
        this.onClose();
      } catch (error) {
        _shared_logger_ts__WEBPACK_IMPORTED_MODULE_10__.logger.error('Failed to set reminder', {
          error
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Failed to set reminder'));
      }
    },
    async clear() {
      try {
        await (0,_services_reminderService_ts__WEBPACK_IMPORTED_MODULE_11__.clearReminder)(this.fileId);
        vue__WEBPACK_IMPORTED_MODULE_0__["default"].set(this.node.attributes, 'reminder-due-date', '');
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('files:node:updated', this.node);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Reminder cleared for "{fileName}"', {
          fileName: this.fileName
        }));
        this.onClose();
      } catch (error) {
        _shared_logger_ts__WEBPACK_IMPORTED_MODULE_10__.logger.error('Failed to clear reminder', {
          error
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_reminders', 'Failed to clear reminder'));
      }
    },
    onClose() {
      this.opened = false;
      this.$emit('close');
    },
    onInput() {
      const input = document.getElementById('set-custom-reminder');
      this.isValid = input.checkValidity();
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=template&id=553ca1de&scoped=true":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=template&id=553ca1de&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _vm.opened ? _c("NcDialog", {
    attrs: {
      name: _vm.name,
      "out-transition": true,
      size: "small",
      "close-on-click-outside": ""
    },
    on: {
      closing: _vm.onClose
    },
    scopedSlots: _vm._u([{
      key: "actions",
      fn: function () {
        return [_c("NcButton", {
          attrs: {
            type: "tertiary"
          },
          on: {
            click: _vm.onClose
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_reminders", "Cancel")) + "\n\t\t")]), _vm._v(" "), _vm.hasDueDate ? _c("NcButton", {
          on: {
            click: _vm.clear
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_reminders", "Clear reminder")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("NcButton", {
          attrs: {
            disabled: !_vm.isValid,
            type: "primary",
            form: "set-custom-reminder-form",
            "native-type": "submit"
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_reminders", "Set reminder")) + "\n\t\t")])];
      },
      proxy: true
    }], null, false, 2766788902)
  }, [_c("form", {
    staticClass: "custom-reminder-modal",
    attrs: {
      id: "set-custom-reminder-form"
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.setCustom.apply(null, arguments);
      }
    }
  }, [_c("NcDateTimePickerNative", {
    attrs: {
      id: "set-custom-reminder",
      label: _vm.label,
      min: _vm.nowDate,
      required: true,
      type: "datetime-local"
    },
    on: {
      input: _vm.onInput
    },
    model: {
      value: _vm.customDueDate,
      callback: function ($$v) {
        _vm.customDueDate = $$v;
      },
      expression: "customDueDate"
    }
  }), _vm._v(" "), _vm.isValid ? _c("NcNoteCard", {
    attrs: {
      type: "info"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_reminders", "We will remind you of this file")) + "\n\t\t\t"), _c("NcDateTime", {
    attrs: {
      timestamp: _vm.customDueDate
    }
  })], 1) : _c("NcNoteCard", {
    attrs: {
      type: "error"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_reminders", "Please choose a valid date & time")) + "\n\t\t")])], 1)]) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_reminders/src/actions/setReminderSuggestionActions.scss":
/*!*********************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_reminders/src/actions/setReminderSuggestionActions.scss ***!
  \*********************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
.files-list__row-action-set-reminder-custom {
  margin-top: 13px;
  position: relative;
}
.files-list__row-action-set-reminder-custom::before {
  content: "";
  margin-block: 3px;
  margin-inline: 15px 10px;
  border-bottom: 1px solid var(--color-border-dark);
  cursor: default;
  display: flex;
  height: 0;
  position: absolute;
  inset-inline: 0;
  top: -10px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.custom-reminder-modal[data-v-553ca1de] {
  margin: 0 12px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_style_index_0_id_553ca1de_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_reminders/src/components/SetCustomReminderModal.vue?vue&type=style&index=0&id=553ca1de&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_style_index_0_id_553ca1de_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_style_index_0_id_553ca1de_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_style_index_0_id_553ca1de_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetCustomReminderModal_vue_vue_type_style_index_0_id_553ca1de_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		__webpack_require__.f = {};
/******/ 		// This file contains only the entry chunk.
/******/ 		// The chunk loading function for additional chunks
/******/ 		__webpack_require__.e = (chunkId) => {
/******/ 			return Promise.all(Object.keys(__webpack_require__.f).reduce((promises, key) => {
/******/ 				__webpack_require__.f[key](chunkId, promises);
/******/ 				return promises;
/******/ 			}, []));
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get javascript chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.u = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"2fcef36253529e5f48bc","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"f3a3966faa81f9b81fa8","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-CsU6FfAP_mjs":"8bce3ebf3ef868f175e5","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"9fa10a9863e5b78deec8"}[chunkId] + "";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/load script */
/******/ 	(() => {
/******/ 		var inProgress = {};
/******/ 		var dataWebpackPrefix = "nextcloud:";
/******/ 		// loadScript function to load a script via script tag
/******/ 		__webpack_require__.l = (url, done, key, chunkId) => {
/******/ 			if(inProgress[url]) { inProgress[url].push(done); return; }
/******/ 			var script, needAttach;
/******/ 			if(key !== undefined) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				for(var i = 0; i < scripts.length; i++) {
/******/ 					var s = scripts[i];
/******/ 					if(s.getAttribute("src") == url || s.getAttribute("data-webpack") == dataWebpackPrefix + key) { script = s; break; }
/******/ 				}
/******/ 			}
/******/ 			if(!script) {
/******/ 				needAttach = true;
/******/ 				script = document.createElement('script');
/******/ 		
/******/ 				script.charset = 'utf-8';
/******/ 				script.timeout = 120;
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.setAttribute("data-webpack", dataWebpackPrefix + key);
/******/ 		
/******/ 				script.src = url;
/******/ 			}
/******/ 			inProgress[url] = [done];
/******/ 			var onScriptComplete = (prev, event) => {
/******/ 				// avoid mem leaks in IE.
/******/ 				script.onerror = script.onload = null;
/******/ 				clearTimeout(timeout);
/******/ 				var doneFns = inProgress[url];
/******/ 				delete inProgress[url];
/******/ 				script.parentNode && script.parentNode.removeChild(script);
/******/ 				doneFns && doneFns.forEach((fn) => (fn(event)));
/******/ 				if(prev) return prev(event);
/******/ 			}
/******/ 			var timeout = setTimeout(onScriptComplete.bind(null, undefined, { type: 'timeout', target: script }), 120000);
/******/ 			script.onerror = onScriptComplete.bind(null, script.onerror);
/******/ 			script.onload = onScriptComplete.bind(null, script.onload);
/******/ 			needAttach && document.head.appendChild(script);
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	(() => {
/******/ 		__webpack_require__.nmd = (module) => {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		var scriptUrl;
/******/ 		if (__webpack_require__.g.importScripts) scriptUrl = __webpack_require__.g.location + "";
/******/ 		var document = __webpack_require__.g.document;
/******/ 		if (!scriptUrl && document) {
/******/ 			if (document.currentScript && document.currentScript.tagName.toUpperCase() === 'SCRIPT')
/******/ 				scriptUrl = document.currentScript.src;
/******/ 			if (!scriptUrl) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				if(scripts.length) {
/******/ 					var i = scripts.length - 1;
/******/ 					while (i > -1 && (!scriptUrl || !/^http(s?):/.test(scriptUrl))) scriptUrl = scripts[i--].src;
/******/ 				}
/******/ 			}
/******/ 		}
/******/ 		// When supporting browsers where an automatic publicPath is not supported you must specify an output.publicPath manually via configuration
/******/ 		// or pass an empty string ("") and set the __webpack_public_path__ variable from your code to use your own logic.
/******/ 		if (!scriptUrl) throw new Error("Automatic publicPath is not supported in this browser");
/******/ 		scriptUrl = scriptUrl.replace(/^blob:/, "").replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
/******/ 		__webpack_require__.p = scriptUrl;
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"files_reminders-init": 0
/******/ 		};
/******/ 		
/******/ 		__webpack_require__.f.j = (chunkId, promises) => {
/******/ 				// JSONP chunk loading for javascript
/******/ 				var installedChunkData = __webpack_require__.o(installedChunks, chunkId) ? installedChunks[chunkId] : undefined;
/******/ 				if(installedChunkData !== 0) { // 0 means "already installed".
/******/ 		
/******/ 					// a Promise means "currently loading".
/******/ 					if(installedChunkData) {
/******/ 						promises.push(installedChunkData[2]);
/******/ 					} else {
/******/ 						if(true) { // all chunks have JS
/******/ 							// setup Promise in chunk cache
/******/ 							var promise = new Promise((resolve, reject) => (installedChunkData = installedChunks[chunkId] = [resolve, reject]));
/******/ 							promises.push(installedChunkData[2] = promise);
/******/ 		
/******/ 							// start chunk loading
/******/ 							var url = __webpack_require__.p + __webpack_require__.u(chunkId);
/******/ 							// create error before stack unwound to get useful stacktrace later
/******/ 							var error = new Error();
/******/ 							var loadingEnded = (event) => {
/******/ 								if(__webpack_require__.o(installedChunks, chunkId)) {
/******/ 									installedChunkData = installedChunks[chunkId];
/******/ 									if(installedChunkData !== 0) installedChunks[chunkId] = undefined;
/******/ 									if(installedChunkData) {
/******/ 										var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 										var realSrc = event && event.target && event.target.src;
/******/ 										error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 										error.name = 'ChunkLoadError';
/******/ 										error.type = errorType;
/******/ 										error.request = realSrc;
/******/ 										installedChunkData[1](error);
/******/ 									}
/******/ 								}
/******/ 							};
/******/ 							__webpack_require__.l(url, loadingEnded, "chunk-" + chunkId, chunkId);
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 		};
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	(() => {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files_reminders/src/init.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files_reminders-init.js.map?v=0b3687b14064abfaf67b