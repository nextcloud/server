/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./core/src/OC/admin.js"
/*!******************************!*\
  !*** ./core/src/OC/admin.js ***!
  \******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isUserAdmin: () => (/* binding */ isUserAdmin)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const isAdmin = !!window._oc_isadmin;

/**
 * Returns whether the current user is an administrator
 *
 * @return {boolean} true if the user is an admin, false otherwise
 * @since 9.0.0
 */
const isUserAdmin = () => isAdmin;

/***/ },

/***/ "./core/src/OC/appconfig.js"
/*!**********************************!*\
  !*** ./core/src/OC/appconfig.js ***!
  \**********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   appConfig: () => (/* binding */ appConfig)
/* harmony export */ });
/*!
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @deprecated 16.0.0 Use OCP.AppConfig instead
 */
const appConfig = window.oc_appconfig || {};

/***/ },

/***/ "./core/src/OC/appswebroots.js"
/*!*************************************!*\
  !*** ./core/src/OC/appswebroots.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const appswebroots = window._oc_appswebroots !== undefined ? window._oc_appswebroots : false;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (appswebroots);

/***/ },

/***/ "./core/src/OC/capabilities.js"
/*!*************************************!*\
  !*** ./core/src/OC/capabilities.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getCapabilities: () => (/* binding */ getCapabilities)
/* harmony export */ });
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../logger.js */ "./core/src/logger.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/**
 * Returns the capabilities
 *
 * @return {Array} capabilities
 *
 * @since 14.0.0
 */
function getCapabilities() {
  if (OC.debug) {
    _logger_js__WEBPACK_IMPORTED_MODULE_1__["default"].warn('OC.getCapabilities is deprecated and will be removed in Nextcloud 21. See @nextcloud/capabilities');
  }
  return (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__.getCapabilities)();
}

/***/ },

/***/ "./core/src/OC/config.js"
/*!*******************************!*\
  !*** ./core/src/OC/config.js ***!
  \*******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const config = window._oc_config || {};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (config);

/***/ },

/***/ "./core/src/OC/constants.js"
/*!**********************************!*\
  !*** ./core/src/OC/constants.js ***!
  \**********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   PERMISSION_ALL: () => (/* binding */ PERMISSION_ALL),
/* harmony export */   PERMISSION_CREATE: () => (/* binding */ PERMISSION_CREATE),
/* harmony export */   PERMISSION_DELETE: () => (/* binding */ PERMISSION_DELETE),
/* harmony export */   PERMISSION_NONE: () => (/* binding */ PERMISSION_NONE),
/* harmony export */   PERMISSION_READ: () => (/* binding */ PERMISSION_READ),
/* harmony export */   PERMISSION_SHARE: () => (/* binding */ PERMISSION_SHARE),
/* harmony export */   PERMISSION_UPDATE: () => (/* binding */ PERMISSION_UPDATE),
/* harmony export */   TAG_FAVORITE: () => (/* binding */ TAG_FAVORITE),
/* harmony export */   coreApps: () => (/* binding */ coreApps),
/* harmony export */   menuSpeed: () => (/* binding */ menuSpeed)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const coreApps = ['', 'admin', 'log', 'core/search', 'core', '3rdparty'];
const menuSpeed = 50;
const PERMISSION_NONE = 0;
const PERMISSION_CREATE = 4;
const PERMISSION_READ = 1;
const PERMISSION_UPDATE = 2;
const PERMISSION_DELETE = 8;
const PERMISSION_SHARE = 16;
const PERMISSION_ALL = 31;
const TAG_FAVORITE = '_$!<Favorite>!$_';

/***/ },

/***/ "./core/src/OC/currentuser.js"
/*!************************************!*\
  !*** ./core/src/OC/currentuser.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   currentUser: () => (/* binding */ currentUser),
/* harmony export */   getCurrentUser: () => (/* binding */ getCurrentUser)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const rawUid = document.getElementsByTagName('head')[0].getAttribute('data-user');
const displayName = document.getElementsByTagName('head')[0].getAttribute('data-user-displayname');
const currentUser = rawUid !== undefined ? rawUid : false;

/**
 *
 */
function getCurrentUser() {
  return {
    uid: currentUser,
    displayName
  };
}

/***/ },

/***/ "./core/src/OC/debug.js"
/*!******************************!*\
  !*** ./core/src/OC/debug.js ***!
  \******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   debug: () => (/* binding */ debug)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const base = window._oc_debug;
const debug = base;

/***/ },

/***/ "./core/src/OC/dialogs.js"
/*!********************************!*\
  !*** ./core/src/OC/dialogs.js ***!
  \********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_folder_move_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/folder-move.svg?raw */ "./node_modules/@mdi/svg/svg/folder-move.svg?raw");
/* harmony import */ var _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/folder-multiple-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-multiple-outline.svg?raw");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_functions_dialog__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/functions/dialog */ "./node_modules/@nextcloud/vue/dist/Functions/dialog.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../logger.js */ "./core/src/logger.js");
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */










/**
 * this class to ease the usage of dialogs
 */
const Dialogs = {
  // dialog button types
  /** @deprecated use `@nextcloud/dialogs` */
  YES_NO_BUTTONS: 70,
  /** @deprecated use `@nextcloud/dialogs` */
  OK_BUTTONS: 71,
  /** @deprecated use FilePickerType from `@nextcloud/dialogs` */
  FILEPICKER_TYPE_CHOOSE: 1,
  /** @deprecated use FilePickerType from `@nextcloud/dialogs` */
  FILEPICKER_TYPE_MOVE: 2,
  /** @deprecated use FilePickerType from `@nextcloud/dialogs` */
  FILEPICKER_TYPE_COPY: 3,
  /** @deprecated use FilePickerType from `@nextcloud/dialogs` */
  FILEPICKER_TYPE_COPY_MOVE: 4,
  /** @deprecated use FilePickerType from `@nextcloud/dialogs` */
  FILEPICKER_TYPE_CUSTOM: 5,
  /**
   * displays alert dialog
   *
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {Function} callback which will be triggered when user presses OK
   * @param {boolean} [modal] make the dialog modal
   *
   * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
   */
  alert: function (text, title, callback, modal) {
    this.message(text, title, 'alert', Dialogs.OK_BUTTON, callback, modal);
  },
  /**
   * displays info dialog
   *
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {Function} callback which will be triggered when user presses OK
   * @param {boolean} [modal] make the dialog modal
   *
   * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
   */
  info: function (text, title, callback, modal) {
    this.message(text, title, 'info', Dialogs.OK_BUTTON, callback, modal);
  },
  /**
   * displays confirmation dialog
   *
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {Function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
   * @param {boolean} [modal] make the dialog modal
   * @return {Promise}
   *
   * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
   */
  confirm: function (text, title, callback, modal) {
    return this.message(text, title, 'notice', Dialogs.YES_NO_BUTTONS, callback, modal);
  },
  /**
   * displays confirmation dialog
   *
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {(number|{type: number, confirm: string, cancel: string, confirmClasses: string})} buttons text content of buttons
   * @param {Function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
   * @return {Promise}
   *
   * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
   */
  confirmDestructive: function (text, title, buttons = Dialogs.OK_BUTTONS, callback = () => {}) {
    return new _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.DialogBuilder().setName(title).setText(text).setButtons(buttons === Dialogs.OK_BUTTONS ? [{
      label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Yes'),
      variant: 'error',
      callback: () => {
        callback.clicked = true;
        callback(true);
      }
    }] : Dialogs._getLegacyButtons(buttons, callback)).build().show().then(() => {
      if (!callback.clicked) {
        callback(false);
      }
    });
  },
  /**
   * displays confirmation dialog
   *
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {Function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
   * @return {Promise}
   *
   * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
   */
  confirmHtml: function (text, title, callback) {
    return new _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.DialogBuilder().setName(title).setText('').setButtons([{
      label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'No'),
      callback: () => {}
    }, {
      label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Yes'),
      variant: 'primary',
      callback: () => {
        callback.clicked = true;
        callback(true);
      }
    }]).build().setHTML(text).show().then(() => {
      if (!callback.clicked) {
        callback(false);
      }
    });
  },
  /**
   * displays prompt dialog
   *
   * @param {string} text content of dialog
   * @param {string} title dialog title
   * @param {Function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
   * @param {boolean} [modal] make the dialog modal
   * @param {string} name name of the input field
   * @param {boolean} password whether the input should be a password input
   * @return {Promise}
   *
   * @deprecated Use NcDialog from `@nextcloud/vue` instead
   */
  prompt: function (text, title, callback, modal, name, password) {
    return new Promise(resolve => {
      (0,_nextcloud_vue_functions_dialog__WEBPACK_IMPORTED_MODULE_4__.spawnDialog)((0,vue__WEBPACK_IMPORTED_MODULE_6__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("core_src_components_LegacyDialogPrompt_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ../components/LegacyDialogPrompt.vue */ "./core/src/components/LegacyDialogPrompt.vue"))), {
        text,
        name: title,
        callback,
        inputName: name,
        isPassword: !!password
      }, (...args) => {
        callback(...args);
        resolve();
      });
    });
  },
  /**
   * Legacy wrapper to the new Vue based filepicker from `@nextcloud/dialogs`
   *
   * Prefer to use the Vue filepicker directly instead.
   *
   * In order to pick several types of mime types they need to be passed as an
   * array of strings.
   *
   * When no mime type filter is given only files can be selected. In order to
   * be able to select both files and folders "['*', 'httpd/unix-directory']"
   * should be used instead.
   *
   * @param {string} title dialog title
   * @param {Function} callback which will be triggered when user presses Choose
   * @param {boolean} [multiselect] whether it should be possible to select multiple files
   * @param {string[]} [mimetype] mimetype to filter by - directories will always be included
   * @param {boolean} [_modal] do not use
   * @param {string} [type] Type of file picker : Choose, copy, move, copy and move
   * @param {string} [path] path to the folder that the the file can be picket from
   * @param {object} [options] additonal options that need to be set
   * @param {Function} [options.filter] filter function for advanced filtering
   * @param {boolean} [options.allowDirectoryChooser] Allow to select directories
   * @deprecated since 27.1.0 use the filepicker from `@nextcloud/dialogs` instead
   */
  // eslint-disable-next-line no-unused-vars
  filepicker(title, callback, multiselect = false, mimetype = undefined, _modal = undefined, type = _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.Choose, path = undefined, options = undefined) {
    /**
     * Create legacy callback wrapper to support old filepicker syntax
     *
     * @param fn The original callback
     * @param type The file picker type which was used to pick the file(s)
     */
    const legacyCallback = (fn, type) => {
      const getPath = node => {
        const root = node?.root || '';
        let path = node?.path || '';
        // TODO: Fix this in @nextcloud/files
        if (path.startsWith(root)) {
          path = path.slice(root.length) || '/';
        }
        return path;
      };
      if (multiselect) {
        return nodes => fn(nodes.map(getPath), type);
      } else {
        return nodes => fn(getPath(nodes[0]), type);
      }
    };

    /**
     * Coverting a Node into a legacy file info to support the OC.dialogs.filepicker filter function
     *
     * @param node The node to convert
     */
    const nodeToLegacyFile = node => ({
      id: node.fileid || null,
      path: node.path,
      mimetype: node.mime || null,
      mtime: node.mtime?.getTime() || null,
      permissions: node.permissions,
      name: node.attributes?.displayName || node.basename,
      etag: node.attributes?.etag || null,
      hasPreview: node.attributes?.hasPreview || null,
      mountType: node.attributes?.mountType || null,
      quotaAvailableBytes: node.attributes?.quotaAvailableBytes || null,
      icon: null,
      sharePermissions: null
    });
    const builder = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.getFilePickerBuilder)(title);

    // Setup buttons
    if (type === this.FILEPICKER_TYPE_CUSTOM) {
      (options.buttons || []).forEach(button => {
        builder.addButton({
          callback: legacyCallback(callback, button.type),
          label: button.text,
          variant: button.defaultButton ? 'primary' : 'secondary'
        });
      });
    } else {
      builder.setButtonFactory((nodes, path) => {
        const buttons = [];
        const [node] = nodes;
        const target = node?.displayname || node?.basename || (0,path__WEBPACK_IMPORTED_MODULE_5__.basename)(path);
        if (type === _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.Choose) {
          buttons.push({
            callback: legacyCallback(callback, _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.Choose),
            label: node && !this.multiSelect ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Choose {file}', {
              file: target
            }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Choose'),
            variant: 'primary'
          });
        }
        if (type === _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.CopyMove || type === _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.Copy) {
          buttons.push({
            callback: legacyCallback(callback, _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.Copy),
            label: target ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Copy to {target}', {
              target
            }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Copy'),
            variant: 'primary',
            icon: _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__
          });
        }
        if (type === _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.Move || type === _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.CopyMove) {
          buttons.push({
            callback: legacyCallback(callback, _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.Move),
            label: target ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Move to {target}', {
              target
            }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Move'),
            variant: type === _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.FilePickerType.Move ? 'primary' : 'secondary',
            icon: _mdi_svg_svg_folder_move_svg_raw__WEBPACK_IMPORTED_MODULE_0__
          });
        }
        return buttons;
      });
    }
    if (mimetype) {
      builder.setMimeTypeFilter(typeof mimetype === 'string' ? [mimetype] : mimetype || []);
    }
    if (typeof options?.filter === 'function') {
      builder.setFilter(node => options.filter(nodeToLegacyFile(node)));
    }
    builder.allowDirectories(options?.allowDirectoryChooser === true || mimetype?.includes('httpd/unix-directory') || false).setMultiSelect(multiselect).startAt(path).build().pick();
  },
  /**
   * Displays raw dialog
   * You better use a wrapper instead ...
   *
   * @param content
   * @param title
   * @param dialogType
   * @param buttons
   * @param callback
   * @param modal
   * @param allowHtml
   * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
   */
  message: function (content, title, dialogType, buttons, callback = () => {}, modal, allowHtml) {
    const builder = new _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.DialogBuilder().setName(title).setText(allowHtml ? '' : content).setButtons(Dialogs._getLegacyButtons(buttons, callback));
    switch (dialogType) {
      case 'alert':
        builder.setSeverity('warning');
        break;
      case 'notice':
        builder.setSeverity('info');
        break;
      default:
        break;
    }
    const dialog = builder.build();
    if (allowHtml) {
      dialog.setHTML(content);
    }
    return dialog.show().then(() => {
      if (!callback._clicked) {
        callback(false);
      }
    });
  },
  /**
   * Helper for legacy API
   *
   * @param buttons
   * @param callback
   * @deprecated
   */
  _getLegacyButtons(buttons, callback) {
    const buttonList = [];
    switch (typeof buttons === 'object' ? buttons.type : buttons) {
      case Dialogs.YES_NO_BUTTONS:
        buttonList.push({
          label: buttons?.cancel ?? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'No'),
          callback: () => {
            callback._clicked = true;
            callback(false);
          }
        });
        buttonList.push({
          label: buttons?.confirm ?? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'Yes'),
          variant: 'primary',
          callback: () => {
            callback._clicked = true;
            callback(true);
          }
        });
        break;
      case Dialogs.OK_BUTTONS:
        buttonList.push({
          label: buttons?.confirm ?? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('core', 'OK'),
          variant: 'primary',
          callback: () => {
            callback._clicked = true;
            callback(true);
          }
        });
        break;
      default:
        _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].error('Invalid call to OC.dialogs');
        break;
    }
    return buttonList;
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Dialogs);

/***/ },

/***/ "./core/src/OC/eventsource.js"
/*!************************************!*\
  !*** ./core/src/OC/eventsource.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _requesttoken_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./requesttoken.ts */ "./core/src/OC/requesttoken.ts");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/**
 * Create a new event source
 *
 * @param {string} src
 * @param {object} [data] to be send as GET
 *
 * @constructs OCEventSource
 */
function OCEventSource(src, data) {
  let dataStr = '';
  let name;
  let joinChar;
  this.typelessListeners = [];
  this.closed = false;
  if (data) {
    for (name in data) {
      dataStr += name + '=' + encodeURIComponent(data[name]) + '&';
    }
  }
  dataStr += 'requesttoken=' + encodeURIComponent((0,_requesttoken_ts__WEBPACK_IMPORTED_MODULE_0__.getRequestToken)());
  joinChar = '&';
  if (src.indexOf('?') === -1) {
    joinChar = '?';
  }
  this.source = new EventSource(src + joinChar + dataStr);
  this.source.onmessage = function (e) {
    for (let i = 0; i < this.typelessListeners.length; i++) {
      this.typelessListeners[i](JSON.parse(e.data));
    }
  }.bind(this);
  // add close listener
  this.listen('__internal__', function (data) {
    if (data === 'close') {
      this.close();
    }
  }.bind(this));
}
OCEventSource.prototype = {
  typelessListeners: [],
  /**
   * Listen to a given type of events.
   *
   * @param {string} type event type
   * @param {Function} callback event callback
   */
  listen: function (type, callback) {
    if (callback && callback.call) {
      if (type) {
        this.source.addEventListener(type, function (e) {
          if (typeof e.data !== 'undefined') {
            callback(JSON.parse(e.data));
          } else {
            callback('');
          }
        }, false);
      } else {
        this.typelessListeners.push(callback);
      }
    }
  },
  /**
   * Closes this event source.
   */
  close: function () {
    this.closed = true;
    this.source.close();
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (OCEventSource);

/***/ },

/***/ "./core/src/OC/index.js"
/*!******************************!*\
  !*** ./core/src/OC/index.js ***!
  \******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../logger.js */ "./core/src/logger.js");
/* harmony import */ var _admin_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./admin.js */ "./core/src/OC/admin.js");
/* harmony import */ var _appconfig_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./appconfig.js */ "./core/src/OC/appconfig.js");
/* harmony import */ var _appswebroots_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./appswebroots.js */ "./core/src/OC/appswebroots.js");
/* harmony import */ var _capabilities_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./capabilities.js */ "./core/src/OC/capabilities.js");
/* harmony import */ var _config_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./config.js */ "./core/src/OC/config.js");
/* harmony import */ var _constants_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./constants.js */ "./core/src/OC/constants.js");
/* harmony import */ var _currentuser_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./currentuser.js */ "./core/src/OC/currentuser.js");
/* harmony import */ var _debug_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./debug.js */ "./core/src/OC/debug.js");
/* harmony import */ var _dialogs_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./dialogs.js */ "./core/src/OC/dialogs.js");
/* harmony import */ var _eventsource_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./eventsource.js */ "./core/src/OC/eventsource.js");
/* harmony import */ var _l10n_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./l10n.js */ "./core/src/OC/l10n.js");
/* harmony import */ var _mimeType_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./mimeType.js */ "./core/src/OC/mimeType.js");
/* harmony import */ var _msg_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./msg.js */ "./core/src/OC/msg.ts");
/* harmony import */ var _password_confirmation_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./password-confirmation.js */ "./core/src/OC/password-confirmation.js");
/* harmony import */ var _plugins_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./plugins.js */ "./core/src/OC/plugins.js");
/* harmony import */ var _query_string_ts__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./query-string.ts */ "./core/src/OC/query-string.ts");
/* harmony import */ var _requesttoken_ts__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./requesttoken.ts */ "./core/src/OC/requesttoken.ts");
/* harmony import */ var _routing_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./routing.js */ "./core/src/OC/routing.js");
/* harmony import */ var _theme_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./theme.js */ "./core/src/OC/theme.js");
/* harmony import */ var _util_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./util.js */ "./core/src/OC/util.js");
/* harmony import */ var _webroot_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./webroot.js */ "./core/src/OC/webroot.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




























/** @namespace OC */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  /*
   * Constants
   */
  coreApps: _constants_js__WEBPACK_IMPORTED_MODULE_10__.coreApps,
  menuSpeed: _constants_js__WEBPACK_IMPORTED_MODULE_10__.menuSpeed,
  PERMISSION_ALL: _constants_js__WEBPACK_IMPORTED_MODULE_10__.PERMISSION_ALL,
  PERMISSION_CREATE: _constants_js__WEBPACK_IMPORTED_MODULE_10__.PERMISSION_CREATE,
  PERMISSION_DELETE: _constants_js__WEBPACK_IMPORTED_MODULE_10__.PERMISSION_DELETE,
  PERMISSION_NONE: _constants_js__WEBPACK_IMPORTED_MODULE_10__.PERMISSION_NONE,
  PERMISSION_READ: _constants_js__WEBPACK_IMPORTED_MODULE_10__.PERMISSION_READ,
  PERMISSION_SHARE: _constants_js__WEBPACK_IMPORTED_MODULE_10__.PERMISSION_SHARE,
  PERMISSION_UPDATE: _constants_js__WEBPACK_IMPORTED_MODULE_10__.PERMISSION_UPDATE,
  TAG_FAVORITE: _constants_js__WEBPACK_IMPORTED_MODULE_10__.TAG_FAVORITE,
  /*
   * Deprecated helpers to be removed
   */
  appConfig: _appconfig_js__WEBPACK_IMPORTED_MODULE_6__.appConfig,
  appswebroots: _appswebroots_js__WEBPACK_IMPORTED_MODULE_7__["default"],
  config: _config_js__WEBPACK_IMPORTED_MODULE_9__["default"],
  /**
   * Currently logged in user or null if none
   *
   * @type {string}
   * @deprecated use `getCurrentUser` from https://www.npmjs.com/package/@nextcloud/auth
   */
  currentUser: _currentuser_js__WEBPACK_IMPORTED_MODULE_11__.currentUser,
  dialogs: _dialogs_js__WEBPACK_IMPORTED_MODULE_13__["default"],
  EventSource: _eventsource_js__WEBPACK_IMPORTED_MODULE_14__["default"],
  MimeType: _mimeType_js__WEBPACK_IMPORTED_MODULE_16__,
  /**
   * Returns the currently logged in user or null if there is no logged in
   * user (public page mode)
   *
   * @since 9.0.0
   * @deprecated 19.0.0 use `getCurrentUser` from https://www.npmjs.com/package/@nextcloud/auth
   */
  getCurrentUser: _currentuser_js__WEBPACK_IMPORTED_MODULE_11__.getCurrentUser,
  isUserAdmin: _admin_js__WEBPACK_IMPORTED_MODULE_5__.isUserAdmin,
  L10N: _l10n_js__WEBPACK_IMPORTED_MODULE_15__["default"],
  /**
   * This is already handled by `interceptRequests` in `core/src/init.js`.
   *
   * @deprecated 33.0.0 - unused by Nextcloud and only a stub remains. Just remove usage.
   */
  registerXHRForErrorProcessing: () => {},
  /**
   * Capabilities
   *
   * @type {Array}
   * @deprecated 20.0.0 use @nextcloud/capabilities instead
   */
  getCapabilities: _capabilities_js__WEBPACK_IMPORTED_MODULE_8__.getCapabilities,
  /*
   * Path helpers
   */
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  basename: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__.basename,
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  encodePath: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__.encodePath,
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  dirname: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__.dirname,
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  isSamePath: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__.isSamePath,
  /**
   * @deprecated 18.0.0 use https://www.npmjs.com/package/@nextcloud/paths
   */
  joinPaths: _nextcloud_paths__WEBPACK_IMPORTED_MODULE_2__.join,
  /**
   * @deprecated 20.0.0 use `getCanonicalLocale` from https://www.npmjs.com/package/@nextcloud/l10n
   */
  getCanonicalLocale: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.getCanonicalLocale,
  /**
   * @deprecated 26.0.0 use `getLocale` from https://www.npmjs.com/package/@nextcloud/l10n
   */
  getLocale: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.getLocale,
  /**
   * @deprecated 26.0.0 use `getLanguage` from https://www.npmjs.com/package/@nextcloud/l10n
   */
  getLanguage: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.getLanguage,
  // Query string helpers
  buildQueryString: _query_string_ts__WEBPACK_IMPORTED_MODULE_20__.build,
  parseQueryString: _query_string_ts__WEBPACK_IMPORTED_MODULE_20__.parse,
  msg: _msg_js__WEBPACK_IMPORTED_MODULE_17__["default"],
  /**
   * @deprecated 28.0.0 use methods from '@nextcloud/password-confirmation'
   */
  PasswordConfirmation: _password_confirmation_js__WEBPACK_IMPORTED_MODULE_18__["default"],
  Plugins: _plugins_js__WEBPACK_IMPORTED_MODULE_19__["default"],
  theme: _theme_js__WEBPACK_IMPORTED_MODULE_23__.theme,
  Util: _util_js__WEBPACK_IMPORTED_MODULE_24__["default"],
  debug: _debug_js__WEBPACK_IMPORTED_MODULE_12__.debug,
  /**
   * @deprecated 19.0.0 use `generateFilePath` from https://www.npmjs.com/package/@nextcloud/router
   */
  filePath: _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateFilePath,
  /**
   * @deprecated 19.0.0 use `WgenerateUrl` from https://www.npmjs.com/package/@nextcloud/router
   */
  generateUrl: _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl,
  /**
   * @deprecated 19.0.0 use `getRootUrl` from https://www.npmjs.com/package/@nextcloud/router
   */
  getRootPath: _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.getRootUrl,
  /**
   * @deprecated 19.0.0 use `imagePath` from https://www.npmjs.com/package/@nextcloud/router
   */
  imagePath: _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.imagePath,
  requestToken: (0,_requesttoken_ts__WEBPACK_IMPORTED_MODULE_21__.getRequestToken)(),
  /**
   * @deprecated 19.0.0 use `linkTo` from https://www.npmjs.com/package/@nextcloud/router
   */
  linkTo: _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.linkTo,
  /**
   * @param {string} service service name
   * @param {number} version OCS API version
   * @return {string} OCS API base path
   * @deprecated 19.0.0 use `generateOcsUrl` from https://www.npmjs.com/package/@nextcloud/router
   */
  linkToOCS: (service, version) => {
    return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateOcsUrl)(service, {}, {
      ocsVersion: version || 1
    }) + '/';
  },
  /**
   * @deprecated 19.0.0 use `generateRemoteUrl` from https://www.npmjs.com/package/@nextcloud/router
   */
  linkToRemote: _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateRemoteUrl,
  linkToRemoteBase: _routing_js__WEBPACK_IMPORTED_MODULE_22__.linkToRemoteBase,
  /**
   * Relative path to Nextcloud root.
   * For example: "/nextcloud"
   *
   * @type {string}
   *
   * @deprecated 19.0.0 use `getRootUrl` from https://www.npmjs.com/package/@nextcloud/router
   * @see OC#getRootPath
   */
  webroot: _webroot_js__WEBPACK_IMPORTED_MODULE_25__["default"]
});

// Keep the request token prop in sync
(0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('csrf-token-update', e => {
  OC.requestToken = e.token;

  // Logging might help debug (Sentry) issues
  _logger_js__WEBPACK_IMPORTED_MODULE_4__["default"].info('OC.requestToken changed', {
    token: e.token
  });
});

/***/ },

/***/ "./core/src/OC/l10n.js"
/*!*****************************!*\
  !*** ./core/src/OC/l10n.js ***!
  \*****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/**
 * L10N namespace with localization functions.
 *
 * @namespace OC.L10n
 * @deprecated 26.0.0 use https://www.npmjs.com/package/@nextcloud/l10n
 */
const L10n = {
  /**
   * Load an app's translation bundle if not loaded already.
   *
   * @deprecated 26.0.0 use `loadTranslations` from https://www.npmjs.com/package/@nextcloud/l10n
   *
   * @param {string} appName name of the app
   * @param {Function} callback callback to be called when
   * the translations are loaded
   * @return {Promise} promise
   */
  load: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.loadTranslations,
  /**
   * Register an app's translation bundle.
   *
   * @deprecated 26.0.0 use `register` from https://www.npmjs.com/package/@nextcloud/l10
   *
   * @param {string} appName name of the app
   * @param {Record<string, string>} bundle bundle
   */
  register: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.register,
  /**
   * @private
   * @deprecated 26.0.0 use `unregister` from https://www.npmjs.com/package/@nextcloud/l10n
   */
  _unregister: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.unregister,
  /**
   * Translate a string
   *
   * @deprecated 26.0.0 use `translate` from https://www.npmjs.com/package/@nextcloud/l10n
   *
   * @param {string} app the id of the app for which to translate the string
   * @param {string} text the string to translate
   * @param {object} [vars] map of placeholder key to value
   * @param {number} [count] number to replace %n with
   * @param {Array} [options] options array
   * @param {boolean} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
   * @param {boolean} [options.sanitize=true] enable/disable sanitization (by default enabled)
   * @return {string}
   */
  translate: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate,
  /**
   * Translate a plural string
   *
   * @deprecated 26.0.0 use `translatePlural` from https://www.npmjs.com/package/@nextcloud/l10n
   *
   * @param {string} app the id of the app for which to translate the string
   * @param {string} textSingular the string to translate for exactly one object
   * @param {string} textPlural the string to translate for n objects
   * @param {number} count number to determine whether to use singular or plural
   * @param {object} [vars] map of placeholder key to value
   * @param {Array} [options] options array
   * @param {boolean} [options.escape=true] enable/disable auto escape of placeholders (by default enabled)
   * @return {string} Translated string
   */
  translatePlural: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translatePlural
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (L10n);

/***/ },

/***/ "./core/src/OC/mimeType.js"
/*!*********************************!*\
  !*** ./core/src/OC/mimeType.js ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   clearIconCache: () => (/* binding */ clearIconCache),
/* harmony export */   getIconUrl: () => (/* binding */ getIconUrl)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const iconCache = new Map();

/**
 * Return the url to icon of the given mimeType
 *
 * @param {string} mimeType The mimeType to get the icon for
 * @return {string} Url to the icon for mimeType
 */
function getIconUrl(mimeType) {
  if (typeof mimeType === 'undefined') {
    return undefined;
  }
  while (mimeType in window.OC.MimeTypeList.aliases) {
    mimeType = window.OC.MimeTypeList.aliases[mimeType];
  }
  if (!iconCache.has(mimeType)) {
    let gotIcon = false;
    let path = '';
    // First try to get the correct icon from the current legacy-theme
    if (OC.theme.folder !== '' && Array.isArray(OC.MimeTypeList.themes[OC.theme.folder])) {
      path = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.getRootUrl)() + '/themes/' + window.OC.theme.folder + '/core/img/filetypes/';
      const icon = getMimeTypeIcon(mimeType, window.OC.MimeTypeList.themes[OC.theme.folder]);
      if (icon) {
        gotIcon = true;
        path += icon + '.svg';
      }
    }

    // theming is always enabled since Nextcloud 20 so we get it from that
    if (!gotIcon) {
      path = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/apps/theming/img/core/filetypes/' + getMimeTypeIcon(mimeType, window.OC.MimeTypeList.files) + '.svg');
    }
    path += '?v=' + window.OCA.Theming.cacheBuster;
    // Cache the result
    iconCache.set(mimeType, path);
  }
  return iconCache.get(mimeType);
}

/**
 * Return the file icon we want to use for the given mimeType.
 * The file needs to be present in the supplied file list
 *
 * @param {string} mimeType The mimeType we want an icon for
 * @param {string[]} files The available icons in this theme
 * @return {string | null} The icon to use or null if there is no match
 */
function getMimeTypeIcon(mimeType, files) {
  const icon = mimeType.replace(new RegExp('/', 'g'), '-');

  // Generate path
  if (mimeType === 'dir' && files.includes('folder')) {
    return 'folder';
  } else if (mimeType === 'dir-encrypted' && files.includes('folder-encrypted')) {
    return 'folder-encrypted';
  } else if (mimeType === 'dir-shared' && files.includes('folder-shared')) {
    return 'folder-shared';
  } else if (mimeType === 'dir-public' && files.includes('folder-public')) {
    return 'folder-public';
  } else if ((mimeType === 'dir-external' || mimeType === 'dir-external-root') && files.includes('folder-external')) {
    return 'folder-external';
  } else if (files.includes(icon)) {
    return icon;
  } else if (files.includes(icon.split('-')[0])) {
    return icon.split('-')[0];
  } else if (files.includes('file')) {
    return 'file';
  }
  return null;
}

/**
 * Clear the icon cache
 */
function clearIconCache() {
  iconCache.clear();
}

/***/ },

/***/ "./core/src/OC/password-confirmation.js"
/*!**********************************************!*\
  !*** ./core/src/OC/password-confirmation.js ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/**
 * @namespace OC.PasswordConfirmation
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  /**
   * @deprecated 28.0.0 use methods from '@nextcloud/password-confirmation'
   */
  requiresPasswordConfirmation() {
    return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_0__.isPasswordConfirmationRequired)();
  },
  /**
   * @param {Function} callback success callback function
   * @param {object} options options currently not used by confirmPassword
   * @param {Function} rejectCallback error callback function
   *
   * @deprecated 28.0.0 use methods from '@nextcloud/password-confirmation'
   */
  requirePasswordConfirmation(callback, options, rejectCallback) {
    (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_0__.confirmPassword)().then(callback, rejectCallback);
  }
});

/***/ },

/***/ "./core/src/OC/plugins.js"
/*!********************************!*\
  !*** ./core/src/OC/plugins.js ***!
  \********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  /**
   * @type {Array.<OC.Plugin>}
   */
  _plugins: {},
  /**
   * Register plugin
   *
   * @param {string} targetName app name / class name to hook into
   * @param {OC.Plugin} plugin plugin
   */
  register(targetName, plugin) {
    let plugins = this._plugins[targetName];
    if (!plugins) {
      plugins = this._plugins[targetName] = [];
    }
    plugins.push(plugin);
  },
  /**
   * Returns all plugin registered to the given target
   * name / app name / class name.
   *
   * @param {string} targetName app name / class name to hook into
   * @return {Array.<OC.Plugin>} array of plugins
   */
  getPlugins(targetName) {
    return this._plugins[targetName] || [];
  },
  /**
   * Call attach() on all plugins registered to the given target name.
   *
   * @param {string} targetName app name / class name
   * @param {object} targetObject to be extended
   * @param {object} [options] options
   */
  attach(targetName, targetObject, options) {
    const plugins = this.getPlugins(targetName);
    for (let i = 0; i < plugins.length; i++) {
      if (plugins[i].attach) {
        plugins[i].attach(targetObject, options);
      }
    }
  },
  /**
   * Call detach() on all plugins registered to the given target name.
   *
   * @param {string} targetName app name / class name
   * @param {object} targetObject to be extended
   * @param {object} [options] options
   */
  detach(targetName, targetObject, options) {
    const plugins = this.getPlugins(targetName);
    for (let i = 0; i < plugins.length; i++) {
      if (plugins[i].detach) {
        plugins[i].detach(targetObject, options);
      }
    }
  }
});

/***/ },

/***/ "./core/src/OC/routing.js"
/*!********************************!*\
  !*** ./core/src/OC/routing.js ***!
  \********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   linkToRemoteBase: () => (/* binding */ linkToRemoteBase)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/**
 * Creates a relative url for remote use
 *
 * @param {string} service id
 * @return {string} the url
 */
function linkToRemoteBase(service) {
  return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.getRootUrl)() + '/remote.php/' + service;
}

/***/ },

/***/ "./core/src/OC/theme.js"
/*!******************************!*\
  !*** ./core/src/OC/theme.js ***!
  \******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   theme: () => (/* binding */ theme)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const theme = window._theme || {};

/***/ },

/***/ "./core/src/OC/util-history.js"
/*!*************************************!*\
  !*** ./core/src/OC/util-history.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var underscore__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! underscore */ "./node_modules/underscore/modules/index-all.js");
/* harmony import */ var _index_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./index.js */ "./core/src/OC/index.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/**
 * Utility class for the history API,
 * includes fallback to using the URL hash when
 * the browser doesn't support the history API.
 *
 * @namespace OC.Util.History
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  _handlers: [],
  /**
   * Push the current URL parameters to the history stack
   * and change the visible URL.
   * Note: this includes a workaround for IE8/IE9 that uses
   * the hash part instead of the search part.
   *
   * @param {object | string} params to append to the URL, can be either a string
   * or a map
   * @param {string} [url] URL to be used, otherwise the current URL will be used,
   * using the params as query string
   * @param {boolean} [replace] whether to replace instead of pushing
   */
  _pushState(params, url, replace) {
    let strParams;
    if (typeof params === 'string') {
      strParams = params;
    } else {
      strParams = _index_js__WEBPACK_IMPORTED_MODULE_1__["default"].buildQueryString(params);
    }
    if (window.history.pushState) {
      url = url || location.pathname + '?' + strParams;
      // Workaround for bug with SVG and window.history.pushState on Firefox < 51
      // https://bugzilla.mozilla.org/show_bug.cgi?id=652991
      const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
      if (isFirefox && parseInt(navigator.userAgent.split('/').pop()) < 51) {
        const patterns = document.querySelectorAll('[fill^="url(#"], [stroke^="url(#"], [filter^="url(#invert"]');
        for (let i = 0, ii = patterns.length, pattern; i < ii; i++) {
          pattern = patterns[i];
          // eslint-disable-next-line no-self-assign
          pattern.style.fill = pattern.style.fill;
          // eslint-disable-next-line no-self-assign
          pattern.style.stroke = pattern.style.stroke;
          pattern.removeAttribute('filter');
          pattern.setAttribute('filter', 'url(#invert)');
        }
      }
      if (replace) {
        window.history.replaceState(params, '', url);
      } else {
        window.history.pushState(params, '', url);
      }
    } else {
      // use URL hash for IE8
      window.location.hash = '?' + strParams;
      // inhibit next onhashchange that just added itself
      // to the event queue
      this._cancelPop = true;
    }
  },
  /**
   * Push the current URL parameters to the history stack
   * and change the visible URL.
   * Note: this includes a workaround for IE8/IE9 that uses
   * the hash part instead of the search part.
   *
   * @param {object | string} params to append to the URL, can be either a string or a map
   * @param {string} [url] URL to be used, otherwise the current URL will be used, using the params as query string
   */
  pushState(params, url) {
    this._pushState(params, url, false);
  },
  /**
   * Push the current URL parameters to the history stack
   * and change the visible URL.
   * Note: this includes a workaround for IE8/IE9 that uses
   * the hash part instead of the search part.
   *
   * @param {object | string} params to append to the URL, can be either a string
   * or a map
   * @param {string} [url] URL to be used, otherwise the current URL will be used,
   * using the params as query string
   */
  replaceState(params, url) {
    this._pushState(params, url, true);
  },
  /**
   * Add a popstate handler
   *
   * @param {Function} handler handler
   */
  addOnPopStateHandler(handler) {
    this._handlers.push(handler);
  },
  /**
   * Parse a query string from the hash part of the URL.
   * (workaround for IE8 / IE9)
   *
   * @return {string}
   */
  _parseHashQuery() {
    const hash = window.location.hash;
    const pos = hash.indexOf('?');
    if (pos >= 0) {
      return hash.substr(pos + 1);
    }
    if (hash.length) {
      // remove hash sign
      return hash.substr(1);
    }
    return '';
  },
  _decodeQuery(query) {
    return query.replace(/\+/g, ' ');
  },
  /**
   * Parse the query/search part of the URL.
   * Also try and parse it from the URL hash (for IE8)
   *
   * @return {object} map of parameters
   */
  parseUrlQuery() {
    const query = this._parseHashQuery();
    let params;
    // try and parse from URL hash first
    if (query) {
      params = _index_js__WEBPACK_IMPORTED_MODULE_1__["default"].parseQueryString(this._decodeQuery(query));
    }
    // else read from query attributes
    params = underscore__WEBPACK_IMPORTED_MODULE_0__["default"].extend(params || {}, _index_js__WEBPACK_IMPORTED_MODULE_1__["default"].parseQueryString(this._decodeQuery(location.search)));
    return params || {};
  },
  _onPopState(e) {
    if (this._cancelPop) {
      this._cancelPop = false;
      return;
    }
    let params;
    if (!this._handlers.length) {
      return;
    }
    params = e && e.state;
    if (underscore__WEBPACK_IMPORTED_MODULE_0__["default"].isString(params)) {
      params = _index_js__WEBPACK_IMPORTED_MODULE_1__["default"].parseQueryString(params);
    } else if (!params) {
      params = this.parseUrlQuery() || {};
    }
    for (let i = 0; i < this._handlers.length; i++) {
      this._handlers[i](params);
    }
  }
});

/***/ },

/***/ "./core/src/OC/util.js"
/*!*****************************!*\
  !*** ./core/src/OC/util.js ***!
  \*****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! moment */ "./node_modules/moment/moment.js");
/* harmony import */ var moment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(moment__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../logger.js */ "./core/src/logger.js");
/* harmony import */ var _index_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./index.js */ "./core/src/OC/index.js");
/* harmony import */ var _util_history_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./util-history.js */ "./core/src/OC/util-history.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







/**
 * @param {any} t -
 */
function chunkify(t) {
  // Adapted from http://my.opera.com/GreyWyvern/blog/show.dml/1671288
  const tz = [];
  let x = 0;
  let y = -1;
  let n = 0;
  let c;
  while (x < t.length) {
    c = t.charAt(x);
    // only include the dot in strings
    const m = !n && c === '.' || c >= '0' && c <= '9';
    if (m !== n) {
      // next chunk
      y++;
      tz[y] = '';
      n = m;
    }
    tz[y] += c;
    x++;
  }
  return tz;
}

/**
 * Utility functions
 *
 * @namespace OC.Util
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  History: _util_history_js__WEBPACK_IMPORTED_MODULE_4__["default"],
  /**
   * @deprecated use https://nextcloud.github.io/nextcloud-files/functions/formatFileSize.html
   */
  humanFileSize: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize,
  /**
   * Returns a file size in bytes from a humanly readable string
   * Makes 2kB to 2048.
   * Inspired by computerFileSize in helper.php
   *
   * @param  {string} string file size in human-readable format
   * @return {number} or null if string could not be parsed
   */
  computerFileSize(string) {
    if (typeof string !== 'string') {
      return null;
    }
    const s = string.toLowerCase().trim();
    const bytesArray = {
      b: 1,
      k: 1024,
      kb: 1024,
      mb: 1024 * 1024,
      m: 1024 * 1024,
      gb: 1024 * 1024 * 1024,
      g: 1024 * 1024 * 1024,
      tb: 1024 * 1024 * 1024 * 1024,
      t: 1024 * 1024 * 1024 * 1024,
      pb: 1024 * 1024 * 1024 * 1024 * 1024,
      p: 1024 * 1024 * 1024 * 1024 * 1024
    };
    let bytes;
    const matches = s.match(/^[\s+]?([0-9]*)(\.([0-9]+))?( +)?([kmgtp]?b?)$/i);
    if (matches !== null) {
      bytes = parseFloat(s);
      if (!isFinite(bytes)) {
        return null;
      }
    } else {
      return null;
    }
    if (matches[5]) {
      bytes = bytes * bytesArray[matches[5]];
    }
    bytes = Math.round(bytes);
    return bytes;
  },
  /**
   * @param {string|number} timestamp timestamp
   * @param {string} format date format, see momentjs docs
   * @return {string} timestamp formatted as requested
   */
  formatDate(timestamp, format) {
    if (window.TESTING === undefined && _index_js__WEBPACK_IMPORTED_MODULE_3__["default"].debug) {
      _logger_js__WEBPACK_IMPORTED_MODULE_2__["default"].warn('OC.Util.formatDate is deprecated and will be removed in Nextcloud 21. See @nextcloud/moment');
    }
    format = format || 'LLL';
    return moment__WEBPACK_IMPORTED_MODULE_1___default()(timestamp).format(format);
  },
  /**
   * @param {string|number} timestamp timestamp
   * @return {string} human readable difference from now
   */
  relativeModifiedDate(timestamp) {
    if (window.TESTING === undefined && _index_js__WEBPACK_IMPORTED_MODULE_3__["default"].debug) {
      _logger_js__WEBPACK_IMPORTED_MODULE_2__["default"].warn('OC.Util.relativeModifiedDate is deprecated and will be removed in Nextcloud 21. See @nextcloud/moment');
    }
    const diff = moment__WEBPACK_IMPORTED_MODULE_1___default()().diff(moment__WEBPACK_IMPORTED_MODULE_1___default()(timestamp));
    if (diff >= 0 && diff < 45000) {
      return t('core', 'seconds ago');
    }
    return moment__WEBPACK_IMPORTED_MODULE_1___default()(timestamp).fromNow();
  },
  /**
   * Returns the width of a generic browser scrollbar
   *
   * @return {number} width of scrollbar
   */
  getScrollBarWidth() {
    if (this._scrollBarWidth) {
      return this._scrollBarWidth;
    }
    const inner = document.createElement('p');
    inner.style.width = '100%';
    inner.style.height = '200px';
    const outer = document.createElement('div');
    outer.style.position = 'absolute';
    outer.style.top = '0px';
    outer.style.left = '0px';
    outer.style.visibility = 'hidden';
    outer.style.width = '200px';
    outer.style.height = '150px';
    outer.style.overflow = 'hidden';
    outer.appendChild(inner);
    document.body.appendChild(outer);
    const w1 = inner.offsetWidth;
    outer.style.overflow = 'scroll';
    let w2 = inner.offsetWidth;
    if (w1 === w2) {
      w2 = outer.clientWidth;
    }
    document.body.removeChild(outer);
    this._scrollBarWidth = w1 - w2;
    return this._scrollBarWidth;
  },
  /**
   * Remove the time component from a given date
   *
   * @param {Date} date date
   * @return {Date} date with stripped time
   */
  stripTime(date) {
    // FIXME: likely to break when crossing DST
    // would be better to use a library like momentJS
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  },
  /**
   * Compare two strings to provide a natural sort
   *
   * @param {string} a first string to compare
   * @param {string} b second string to compare
   * @return {number} -1 if b comes before a, 1 if a comes before b
   * or 0 if the strings are identical
   */
  naturalSortCompare(a, b) {
    let x;
    const aa = chunkify(a);
    const bb = chunkify(b);
    for (x = 0; aa[x] && bb[x]; x++) {
      if (aa[x] !== bb[x]) {
        const aNum = Number(aa[x]);
        const bNum = Number(bb[x]);
        // note: == is correct here to include null == undefined
        // eslint-disable-next-line eqeqeq
        if (aNum == aa[x] && bNum == bb[x]) {
          return aNum - bNum;
        } else {
          // Note: This locale setting isn't supported by all browsers but for the ones
          // that do there will be more consistency between client-server sorting
          return aa[x].localeCompare(bb[x], _index_js__WEBPACK_IMPORTED_MODULE_3__["default"].getLanguage());
        }
      }
    }
    return aa.length - bb.length;
  },
  /**
   * Calls the callback in a given interval until it returns true
   *
   * @param {Function} callback function to call on success
   * @param {number} interval in milliseconds
   */
  waitFor(callback, interval) {
    const internalCallback = function () {
      if (callback() !== true) {
        setTimeout(internalCallback, interval);
      }
    };
    internalCallback();
  },
  /**
   * Checks if a cookie with the given name is present and is set to the provided value.
   *
   * @param {string} name name of the cookie
   * @param {string} value value of the cookie
   * @return {boolean} true if the cookie with the given name has the given value
   */
  isCookieSetToValue(name, value) {
    const cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
      const cookie = cookies[i].split('=');
      if (cookie[0].trim() === name && cookie[1].trim() === value) {
        return true;
      }
    }
    return false;
  }
});

/***/ },

/***/ "./core/src/OC/webroot.js"
/*!********************************!*\
  !*** ./core/src/OC/webroot.js ***!
  \********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

let webroot = window._oc_webroot;
if (typeof webroot === 'undefined') {
  webroot = location.pathname;
  const pos = webroot.indexOf('/index.php/');
  if (pos !== -1) {
    webroot = webroot.substr(0, pos);
  } else {
    webroot = webroot.substr(0, webroot.lastIndexOf('/'));
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (webroot);

/***/ },

/***/ "./core/src/logger.js"
/*!****************************!*\
  !*** ./core/src/logger.js ***!
  \****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   unifiedSearchLogger: () => (/* binding */ unifiedSearchLogger)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/**
 *
 * @param user
 */
function getLogger(user) {
  if (user === null) {
    return (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_1__.getLoggerBuilder)().setApp('core').build();
  }
  return (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_1__.getLoggerBuilder)().setApp('core').setUid(user.uid).build();
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (getLogger((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()));
const unifiedSearchLogger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_1__.getLoggerBuilder)().setApp('unified-search').detectUser().build();

/***/ },

/***/ "./core/src/login.js"
/*!***************************!*\
  !*** ./core/src/login.js ***!
  \***************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _views_Login_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./views/Login.vue */ "./core/src/views/Login.vue");
/* harmony import */ var _mixins_Nextcloud_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./mixins/Nextcloud.js */ "./core/src/mixins/Nextcloud.js");
/* harmony import */ var _OC_index_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./OC/index.js */ "./core/src/OC/index.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




// eslint-disable-next-line no-unused-vars
 // TODO: Not needed but L10n breaks if removed

vue__WEBPACK_IMPORTED_MODULE_0__["default"].mixin(_mixins_Nextcloud_js__WEBPACK_IMPORTED_MODULE_2__["default"]);
const View = vue__WEBPACK_IMPORTED_MODULE_0__["default"].extend(_views_Login_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
new View().$mount('#login');

/***/ },

/***/ "./core/src/mixins/Nextcloud.js"
/*!**************************************!*\
  !*** ./core/src/mixins/Nextcloud.js ***!
  \**************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _OC_index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../OC/index.js */ "./core/src/OC/index.js");
/* harmony import */ var _OC_l10n_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../OC/l10n.js */ "./core/src/OC/l10n.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  data() {
    return {
      OC: _OC_index_js__WEBPACK_IMPORTED_MODULE_0__["default"]
    };
  },
  methods: {
    t: _OC_l10n_js__WEBPACK_IMPORTED_MODULE_1__["default"].translate.bind(_OC_l10n_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
    n: _OC_l10n_js__WEBPACK_IMPORTED_MODULE_1__["default"].translatePlural.bind(_OC_l10n_js__WEBPACK_IMPORTED_MODULE_1__["default"])
  }
});

/***/ },

/***/ "./core/src/mixins/auth.js"
/*!*********************************!*\
  !*** ./core/src/mixins/auth.js ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  computed: {
    userNameInputLengthIs255() {
      return this.user.length >= 255;
    },
    userInputHelperText() {
      if (this.userNameInputLengthIs255) {
        return t('core', 'Email length is at max (255)');
      }
      return undefined;
    }
  }
});

/***/ },

/***/ "./core/src/utils/xhr-request.js"
/*!***************************************!*\
  !*** ./core/src/utils/xhr-request.js ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   interceptRequests: () => (/* binding */ interceptRequests),
/* harmony export */   wipeBrowserStorages: () => (/* binding */ wipeBrowserStorages)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../logger.js */ "./core/src/logger.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





/**
 *
 * @param {string} url the URL to check
 * @return {boolean}
 */
function isRelativeUrl(url) {
  return !url.startsWith('https://') && !url.startsWith('http://');
}

/**
 * @param {string} url The URL to check
 * @return {boolean} true if the URL points to this nextcloud instance
 */
function isNextcloudUrl(url) {
  const nextcloudBaseUrl = window.location.protocol + '//' + window.location.host + (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.getRootUrl)();
  // if the URL is absolute and starts with the baseUrl+rootUrl
  // OR if the URL is relative and starts with rootUrl
  return url.startsWith(nextcloudBaseUrl) || isRelativeUrl(url) && url.startsWith((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.getRootUrl)());
}

/**
 * Check if a user was logged in but is now logged-out.
 * If this is the case then the user will be forwarded to the login page.
 *
 * @return {Promise<void>}
 */
async function checkLoginStatus() {
  // skip if no logged in user
  if ((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)() === null) {
    return;
  }

  // skip if already running
  if (checkLoginStatus.running === true) {
    return;
  }

  // only run one request in parallel
  checkLoginStatus.running = true;
  try {
    // We need to check this as a 401 in the first place could also come from other reasons
    const {
      status
    } = await window.fetch((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/files'));
    if (status === 401) {
      _logger_js__WEBPACK_IMPORTED_MODULE_2__["default"].warn('User session was terminated, forwarding to login page.');
      await wipeBrowserStorages();
      window.location = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/login?redirect_url={url}', {
        url: window.location.pathname + window.location.search + window.location.hash
      });
    }
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_2__["default"].warn('Could not check login-state', {
      error
    });
  } finally {
    delete checkLoginStatus.running;
  }
}

/**
 * Clear all Browser storages connected to current origin.
 *
 * @return {Promise<void>}
 */
async function wipeBrowserStorages() {
  try {
    window.localStorage.clear();
    window.sessionStorage.clear();
    const indexedDBList = await window.indexedDB.databases();
    for (const indexedDB of indexedDBList) {
      await window.indexedDB.deleteDatabase(indexedDB.name);
    }
    _logger_js__WEBPACK_IMPORTED_MODULE_2__["default"].debug('Browser storages cleared');
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_2__["default"].error('Could not clear browser storages', {
      error
    });
  }
}

/**
 * Intercept XMLHttpRequest and fetch API calls to add X-Requested-With header
 *
 * This is also done in @nextcloud/axios but not all requests pass through that
 */
function interceptRequests() {
  XMLHttpRequest.prototype.open = function (open) {
    return function (method, url) {
      open.apply(this, arguments);
      if (isNextcloudUrl(url)) {
        if (!this.getResponseHeader('X-Requested-With')) {
          this.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        }
        this.addEventListener('loadend', function () {
          if (this.status === 401) {
            checkLoginStatus();
          }
        });
      }
    };
  }(XMLHttpRequest.prototype.open);
  window.fetch = function (fetch) {
    return async (resource, options) => {
      // fetch allows the `input` to be either a Request object or any stringifyable value
      if (!isNextcloudUrl(resource.url ?? resource.toString())) {
        return await fetch(resource, options);
      }
      if (!options) {
        options = {};
      }
      if (!options.headers) {
        options.headers = new Headers();
      }
      if (options.headers instanceof Headers && !options.headers.has('X-Requested-With')) {
        options.headers.append('X-Requested-With', 'XMLHttpRequest');
      } else if (options.headers instanceof Object && !options.headers['X-Requested-With']) {
        options.headers['X-Requested-With'] = 'XMLHttpRequest';
      }
      const response = await fetch(resource, options);
      if (response.status === 401) {
        checkLoginStatus();
      }
      return response;
    };
  }(window.fetch);
}

/***/ },

/***/ "./core/src/OC/msg.ts"
/*!****************************!*\
  !*** ./core/src/OC/msg.ts ***!
  \****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * A little class to manage a status field for a "saving" process.
 * It can be used to display a starting message (e.g. "Saving...") and then
 * replace it with a green success message or a red error message.
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  /**
   * Displayes a "Saving..." message in the given message placeholder
   *
   * @param selector - Query selectior for the element to display the message in
   */
  startSaving(selector) {
    this.startAction(selector, (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('core', 'Saving …'));
  },
  /**
   * Displayes a custom message in the given message placeholder
   *
   * @param selector - Query selectior for the element to display the message in
   * @param message - Plain text message to display (no HTML allowed)
   */
  startAction(selector, message) {
    const el = document.querySelector(selector);
    if (!el || !(el instanceof HTMLElement)) {
      return;
    }
    el.textContent = message;
    el.classList.remove('success');
    el.classList.remove('error');
    el.getAnimations?.().forEach(animation => animation.cancel());
    el.style.display = 'block';
  },
  /**
   * Displayes an success/error message in the given selector
   *
   * @param selector - Query selectior for the element to display the message in
   * @param response - Response of the server
   * @param response.data - Data of the servers response
   * @param response.data.message - Plain text message to display (no HTML allowed)
   * @param response.status - is being used to decide whether the message is displayed as an error/success
   */
  finishedSaving(selector, response) {
    this.finishedAction(selector, response);
  },
  /**
   * Displayes an success/error message in the given selector
   *
   * @param selector - Query selector for the element to display the message in
   * @param response - Response of the server
   * @param response.data - Data of the servers response
   * @param response.data.message - Plain text message to display (no HTML allowed)
   * @param response.status . Is being used to decide whether the message is displayed as an error/success
   */
  finishedAction(selector, response) {
    if (response.status === 'success') {
      this.finishedSuccess(selector, response.data.message);
    } else {
      this.finishedError(selector, response.data.message);
    }
  },
  /**
   * Displayes an success message in the given selector
   *
   * @param selector - Query selector for the element to display the message in
   * @param message - Plain text success message to display (no HTML allowed)
   */
  finishedSuccess(selector, message) {
    const el = document.querySelector(selector);
    if (!el || !(el instanceof HTMLElement)) {
      return;
    }
    el.textContent = message;
    el.classList.remove('error');
    el.classList.add('success');
    el.getAnimations?.().forEach(animation => animation.cancel());
    window.setTimeout(fadeOut, 3000);
    el.style.display = 'block';
    /**
     * Fades out the message element
     */
    function fadeOut() {
      if (!el || !(el instanceof HTMLElement)) {
        return;
      }
      // eslint-disable-next-line @stylistic/exp-list-style
      const animation = el.animate?.([{
        opacity: 1
      }, {
        opacity: 0
      }], {
        duration: 900,
        fill: 'forwards'
      });
      if (animation) {
        animation.addEventListener('finish', () => {
          el.style.display = 'none';
        });
      } else {
        window.setTimeout(() => {
          el.style.display = 'none';
        }, 900);
      }
    }
  },
  /**
   * Displayes an error message in the given selector
   *
   * @param selector - Query selector for the element to display the message in
   * @param message - Plain text error message to display (no HTML allowed)
   */
  finishedError(selector, message) {
    const el = document.querySelector(selector);
    if (!el || !(el instanceof HTMLElement)) {
      return;
    }
    el.textContent = message;
    el.classList.remove('success');
    el.classList.add('error');
    el.style.display = 'block';
  }
});

/***/ },

/***/ "./core/src/OC/query-string.ts"
/*!*************************************!*\
  !*** ./core/src/OC/query-string.ts ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   build: () => (/* binding */ build),
/* harmony export */   parse: () => (/* binding */ parse)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/**
 * Parses a URL query string into a JS map
 *
 * @param queryString - Query string in the format param1=1234&param2=abcde&param3=xyz
 * @return Object containing key/values matching the URL parameters
 * @deprecated 33.0.0 - Use `URLSearchParams` instead
 */
function parse(queryString) {
  const params = new URLSearchParams(queryString);
  return Object.fromEntries(params.entries());
}
/**
 * Builds a URL query from a JS map.
 *
 * @param params - Object containing key/values matching the URL parameters
 * @return String containing a URL query (without question) mark
 * @deprecated 33.0.0 - Use `URLSearchParams` instead
 */
function build(params) {
  if (!params) {
    return '';
  }
  const search = new URLSearchParams(params);
  return search.toString();
}

/***/ },

/***/ "./core/src/OC/requesttoken.ts"
/*!*************************************!*\
  !*** ./core/src/OC/requesttoken.ts ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   fetchRequestToken: () => (/* binding */ fetchRequestToken),
/* harmony export */   getRequestToken: () => (/* binding */ getRequestToken),
/* harmony export */   setRequestToken: () => (/* binding */ setRequestToken)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/**
 * Get the current CSRF token.
 */
function getRequestToken() {
  return document.head.dataset.requesttoken;
}
/**
 * Set a new CSRF token (e.g. because of session refresh).
 * This also emits an event bus event for the updated token.
 *
 * @param token - The new token
 * @fires Error - If the passed token is not a potential valid token
 */
function setRequestToken(token) {
  if (!token || typeof token !== 'string') {
    throw new Error('Invalid CSRF token given', {
      cause: {
        token
      }
    });
  }
  document.head.dataset.requesttoken = token;
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('csrf-token-update', {
    token
  });
}
/**
 * Fetch the request token from the API.
 * This does also set it on the current context, see `setRequestToken`.
 *
 * @fires Error - If the request failed
 */
async function fetchRequestToken() {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/csrftoken');
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error('Could not fetch CSRF token from API', {
      cause: response
    });
  }
  const {
    token
  } = await response.json();
  setRequestToken(token);
  return token;
}

/***/ },

/***/ "./core/src/services/WebAuthnAuthenticationService.ts"
/*!************************************************************!*\
  !*** ./core/src/services/WebAuthnAuthenticationService.ts ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   NoValidCredentials: () => (/* binding */ NoValidCredentials),
/* harmony export */   finishAuthentication: () => (/* binding */ finishAuthentication),
/* harmony export */   startAuthentication: () => (/* binding */ startAuthentication)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _simplewebauthn_browser__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @simplewebauthn/browser */ "./node_modules/@simplewebauthn/browser/esm/index.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../logger.js */ "./core/src/logger.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




class NoValidCredentials extends Error {}
/**
 * Start webautn authentication
 * This loads the challenge, connects to the authenticator and returns the repose that needs to be sent to the server.
 *
 * @param loginName Name to login
 */
async function startAuthentication(loginName) {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/login/webauthn/start');
  const {
    data
  } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
    loginName
  });
  if (!data.allowCredentials || data.allowCredentials.length === 0) {
    _logger_js__WEBPACK_IMPORTED_MODULE_3__["default"].error('No valid credentials returned for webauthn');
    throw new NoValidCredentials();
  }
  return await (0,_simplewebauthn_browser__WEBPACK_IMPORTED_MODULE_2__.startAuthentication)({
    optionsJSON: data
  });
}
/**
 * Verify webauthn authentication
 *
 * @param authData The authentication data to sent to the server
 */
async function finishAuthentication(authData) {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/login/webauthn/finish');
  const {
    data
  } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
    data: JSON.stringify(authData)
  });
  return data;
}

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=script&lang=ts"
/*!*******************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=script&lang=ts ***!
  \*******************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _LoginButton_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./LoginButton.vue */ "./core/src/components/login/LoginButton.vue");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../logger.js */ "./core/src/logger.js");
/* harmony import */ var _mixins_auth_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../mixins/auth.js */ "./core/src/mixins/auth.js");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_2__.defineComponent)({
  name: 'ResetPassword',
  components: {
    LoginButton: _LoginButton_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  mixins: [_mixins_auth_js__WEBPACK_IMPORTED_MODULE_8__["default"]],
  props: {
    username: {
      type: String,
      required: true
    },
    resetPasswordLink: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      error: false,
      loading: false,
      message: '',
      user: this.username
    };
  },
  watch: {
    username(value) {
      this.user = value;
    }
  },
  methods: {
    updateUsername() {
      this.$emit('update:username', this.user);
    },
    async submit() {
      this.loading = true;
      this.error = false;
      this.message = '';
      const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/lostpassword/email');
      try {
        const {
          data
        } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
          user: this.user
        });
        if (data.status !== 'success') {
          throw new Error(`got status ${data.status}`);
        }
        this.message = 'send-success';
      } catch (error) {
        _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].error('could not send reset email request', {
          error
        });
        this.error = true;
        this.message = 'send-error';
      } finally {
        this.loading = false;
      }
    }
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/ArrowRight.vue */ "./node_modules/vue-material-design-icons/ArrowRight.vue");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'LoginButton',
  components: {
    ArrowRight: vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  props: {
    value: {
      type: String,
      default: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('core', 'Log in')
    },
    valueLoading: {
      type: String,
      default: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('core', 'Logging in …')
    },
    loading: {
      type: Boolean,
      required: true
    },
    invertedColors: {
      type: Boolean,
      default: false
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcCheckboxRadioSwitch */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _nextcloud_vue_components_NcPasswordField__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcPasswordField */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _LoginButton_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./LoginButton.vue */ "./core/src/components/login/LoginButton.vue");
/* harmony import */ var _mixins_auth_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../mixins/auth.js */ "./core/src/mixins/auth.js");










/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'LoginForm',
  components: {
    LoginButton: _LoginButton_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcCheckboxRadioSwitch: _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcPasswordField: _nextcloud_vue_components_NcPasswordField__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  mixins: [_mixins_auth_js__WEBPACK_IMPORTED_MODULE_9__["default"]],
  props: {
    username: {
      type: String,
      default: ''
    },
    redirectUrl: {
      type: [Boolean, String],
      default: false
    },
    errors: {
      type: Array,
      default: () => []
    },
    messages: {
      type: Array,
      default: () => []
    },
    throttleDelay: {
      type: Number,
      default: 0
    },
    autoCompleteAllowed: {
      type: Boolean,
      default: true
    },
    remembermeAllowed: {
      type: Boolean,
      default: true
    },
    directLogin: {
      type: Boolean,
      default: false
    },
    emailStates: {
      type: Array,
      default() {
        return [];
      }
    }
  },
  setup() {
    // non reactive props
    return {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
      // Disable escape and sanitize to prevent special characters to be html escaped
      // For example "J's cloud" would be escaped to "J&#39; cloud". But we do not need escaping as Vue does this in `v-text` automatically
      headlineText: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('core', 'Log in to {productName}', {
        productName: OC.theme.name
      }, undefined, {
        sanitize: false,
        escape: false
      }),
      loginTimeout: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginTimeout', 300),
      requestToken: window.OC.requestToken,
      timezone: new Intl.DateTimeFormat()?.resolvedOptions()?.timeZone,
      timezoneOffset: -new Date().getTimezoneOffset() / 60
    };
  },
  data(props) {
    return {
      loading: false,
      user: props.username,
      password: '',
      rememberme: ['1'],
      visible: false
    };
  },
  computed: {
    /**
     * Reset the login form after a long idle time (debounced)
     */
    resetFormTimeout() {
      // Infinite timeout, do nothing
      if (this.loginTimeout <= 0) {
        return () => {};
      }
      // Debounce for given timeout (in seconds so convert to milli seconds)
      return (0,debounce__WEBPACK_IMPORTED_MODULE_3__["default"])(this.handleResetForm, this.loginTimeout * 1000);
    },
    isError() {
      return this.invalidPassword || this.userDisabled || this.throttleDelay > 5000;
    },
    errorLabel() {
      if (this.invalidPassword) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('core', 'Wrong login or password.');
      }
      if (this.userDisabled) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('core', 'This account is disabled');
      }
      if (this.throttleDelay > 5000) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('core', 'Too many incorrect login attempts. Please try again in 30 seconds.');
      }
      return undefined;
    },
    apacheAuthFailed() {
      return this.errors.indexOf('apacheAuthFailed') !== -1;
    },
    csrfCheckFailed() {
      return this.errors.indexOf('csrfCheckFailed') !== -1;
    },
    internalException() {
      return this.errors.indexOf('internalexception') !== -1;
    },
    invalidPassword() {
      return this.errors.indexOf('invalidpassword') !== -1;
    },
    userDisabled() {
      return this.errors.indexOf('userdisabled') !== -1;
    },
    loadingIcon() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.imagePath)('core', 'loading-dark.gif');
    },
    loginActionUrl() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('login');
    },
    emailEnabled() {
      return this.emailStates.every(state => state === '1');
    },
    loginText() {
      if (this.emailEnabled) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('core', 'Account name or email');
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('core', 'Account name');
    }
  },
  watch: {
    /**
     * Reset form reset after the password was changed
     */
    password() {
      this.resetFormTimeout();
    }
  },
  mounted() {
    if (this.username === '') {
      this.$refs.user.$refs.inputField.$refs.input.focus();
    } else {
      this.$refs.password.$refs.inputField.$refs.input.focus();
    }
  },
  methods: {
    /**
     * Handle reset of the login form after a long IDLE time
     * This is recommended security behavior to prevent password leak on public devices
     */
    handleResetForm() {
      this.password = '';
    },
    updateUsername() {
      this.$emit('update:username', this.user);
    },
    submit(event) {
      this.visible = false;
      if (this.loading) {
        // Prevent the form from being submitted twice
        event.preventDefault();
        return;
      }
      this.loading = true;
      this.$emit('submit');
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _simplewebauthn_browser__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @simplewebauthn/browser */ "./node_modules/@simplewebauthn/browser/esm/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcEmptyContent */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/InformationOutline.vue */ "./node_modules/vue-material-design-icons/InformationOutline.vue");
/* harmony import */ var vue_material_design_icons_LockOpen_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/LockOpen.vue */ "./node_modules/vue-material-design-icons/LockOpen.vue");
/* harmony import */ var _LoginButton_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./LoginButton.vue */ "./core/src/components/login/LoginButton.vue");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../logger.js */ "./core/src/logger.js");
/* harmony import */ var _services_WebAuthnAuthenticationService_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../services/WebAuthnAuthenticationService.ts */ "./core/src/services/WebAuthnAuthenticationService.ts");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_1__.defineComponent)({
  name: 'PasswordLessLoginForm',
  components: {
    LoginButton: _LoginButton_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    InformationIcon: vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    LockOpenIcon: vue_material_design_icons_LockOpen_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcEmptyContent: _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  props: {
    username: {
      type: String,
      default: ''
    },
    redirectUrl: {
      type: [Boolean, String],
      default: false
    },
    autoCompleteAllowed: {
      type: Boolean,
      default: true
    },
    isHttps: {
      type: Boolean,
      default: false
    },
    isLocalhost: {
      type: Boolean,
      default: false
    }
  },
  setup() {
    return {
      supportsWebauthn: (0,_simplewebauthn_browser__WEBPACK_IMPORTED_MODULE_0__.browserSupportsWebAuthn)()
    };
  },
  data() {
    return {
      user: this.username,
      loading: false,
      validCredentials: true
    };
  },
  methods: {
    async authenticate() {
      // check required fields
      if (!this.$refs.loginForm.checkValidity()) {
        return;
      }
      _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].debug('passwordless login initiated');
      try {
        const params = await (0,_services_WebAuthnAuthenticationService_ts__WEBPACK_IMPORTED_MODULE_8__.startAuthentication)(this.user);
        await this.completeAuthentication(params);
      } catch (error) {
        if (error instanceof _services_WebAuthnAuthenticationService_ts__WEBPACK_IMPORTED_MODULE_8__.NoValidCredentials) {
          this.validCredentials = false;
          return;
        }
        _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].debug(error);
      }
    },
    changeUsername(username) {
      this.user = username;
      this.$emit('update:username', this.user);
    },
    completeAuthentication(challenge) {
      const redirectUrl = this.redirectUrl;
      return (0,_services_WebAuthnAuthenticationService_ts__WEBPACK_IMPORTED_MODULE_8__.finishAuthentication)(challenge).then(({
        defaultRedirectUrl
      }) => {
        _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].debug('Logged in redirecting'); // Redirect url might be false so || should be used instead of ??.
        window.location.href = redirectUrl || defaultRedirectUrl;
      }).catch(error => {
        _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].debug('GOT AN ERROR WHILE SUBMITTING CHALLENGE!', {
          error
        }); // Example: timeout, interaction refused...
      });
    },
    submit() {
      // noop
    }
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js"
/*!**********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _LoginButton_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LoginButton.vue */ "./core/src/components/login/LoginButton.vue");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UpdatePassword',
  components: {
    LoginButton: _LoginButton_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  props: {
    username: {
      type: String,
      required: true
    },
    resetPasswordTarget: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      error: false,
      loading: false,
      message: undefined,
      user: this.username,
      password: '',
      encrypted: false,
      proceed: false
    };
  },
  watch: {
    username(value) {
      this.user = value;
    }
  },
  methods: {
    async submit() {
      this.loading = true;
      this.error = false;
      this.message = '';
      try {
        const {
          data
        } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(this.resetPasswordTarget, {
          password: this.password,
          proceed: this.proceed
        });
        if (data && data.status === 'success') {
          this.message = 'send-success';
          this.$emit('update:username', this.user);
          this.$emit('done');
        } else if (data && data.encryption) {
          this.encrypted = true;
        } else if (data && data.msg) {
          throw new Error(data.msg);
        } else {
          throw new Error();
        }
      } catch (e) {
        this.error = true;
        this.message = e.message ? e.message : t('core', 'Password cannot be changed. Please contact your administrator.');
      } finally {
        this.loading = false;
      }
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=script&lang=js"
/*!**************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var query_string__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! query-string */ "./node_modules/query-string/index.js");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _components_login_LoginForm_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/login/LoginForm.vue */ "./core/src/components/login/LoginForm.vue");
/* harmony import */ var _components_login_PasswordLessLoginForm_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../components/login/PasswordLessLoginForm.vue */ "./core/src/components/login/PasswordLessLoginForm.vue");
/* harmony import */ var _components_login_ResetPassword_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../components/login/ResetPassword.vue */ "./core/src/components/login/ResetPassword.vue");
/* harmony import */ var _components_login_UpdatePassword_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../components/login/UpdatePassword.vue */ "./core/src/components/login/UpdatePassword.vue");
/* harmony import */ var _utils_xhr_request_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/xhr-request.js */ "./core/src/utils/xhr-request.js");










const query = query_string__WEBPACK_IMPORTED_MODULE_2__["default"].parse(location.search);
if (query.clear === '1') {
  (0,_utils_xhr_request_js__WEBPACK_IMPORTED_MODULE_9__.wipeBrowserStorages)();
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Login',
  components: {
    LoginForm: _components_login_LoginForm_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    PasswordLessLoginForm: _components_login_PasswordLessLoginForm_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    ResetPassword: _components_login_ResetPassword_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    UpdatePassword: _components_login_UpdatePassword_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  data() {
    return {
      loading: false,
      user: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginUsername', ''),
      passwordlessLogin: false,
      resetPassword: false,
      // Initial data
      errors: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginErrors', []),
      messages: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginMessages', []),
      redirectUrl: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginRedirectUrl', false),
      throttleDelay: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginThrottleDelay', 0),
      canResetPassword: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginCanResetPassword', false),
      resetPasswordLink: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginResetPasswordLink', ''),
      autoCompleteAllowed: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginAutocomplete', true),
      remembermeAllowed: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'loginCanRememberme', true),
      resetPasswordTarget: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'resetPasswordTarget', ''),
      resetPasswordUser: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'resetPasswordUser', ''),
      directLogin: query.direct === '1',
      hasPasswordless: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'webauthn-available', false),
      countAlternativeLogins: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'countAlternativeLogins', false),
      alternativeLogins: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'alternativeLogins', []),
      isHttps: window.location.protocol === 'https:',
      isLocalhost: window.location.hostname === 'localhost',
      hideLoginForm: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'hideLoginForm', false),
      emailStates: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'emailStates', [])
    };
  },
  methods: {
    passwordResetFinished() {
      window.location.href = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('login') + '?direct=1';
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcButton", {
    attrs: {
      variant: "primary",
      type: "submit",
      wide: true,
      disabled: _vm.loading
    },
    on: {
      click: function ($event) {
        return _vm.$emit("click");
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm.loading ? _c("div", {
          staticClass: "submit-wrapper__icon icon-loading-small-dark"
        }) : _c("ArrowRight", {
          staticClass: "submit-wrapper__icon"
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t" + _vm._s(!_vm.loading ? _vm.value : _vm.valueLoading) + "\n\t")]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("form", {
    ref: "loginForm",
    staticClass: "login-form",
    attrs: {
      method: "post",
      name: "login",
      action: _vm.loginActionUrl
    },
    on: {
      submit: _vm.submit
    }
  }, [_c("fieldset", {
    staticClass: "login-form__fieldset",
    attrs: {
      "data-login-form": ""
    }
  }, [_vm.apacheAuthFailed ? _c("NcNoteCard", {
    attrs: {
      title: _vm.t("core", "Server side authentication failed!"),
      type: "warning"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Please contact your administrator.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.csrfCheckFailed ? _c("NcNoteCard", {
    attrs: {
      heading: _vm.t("core", "Session error"),
      type: "error"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "It appears your session token has expired, please refresh the page and try again.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.messages.length > 0 ? _c("NcNoteCard", _vm._l(_vm.messages, function (message, index) {
    return _c("div", {
      key: index
    }, [_vm._v("\n\t\t\t\t" + _vm._s(message)), _c("br")]);
  }), 0) : _vm._e(), _vm._v(" "), _vm.internalException ? _c("NcNoteCard", {
    class: _vm.t("core", "An internal error occurred."),
    attrs: {
      type: "warning"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Please try again or contact your administrator.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "hidden",
    attrs: {
      id: "message"
    }
  }, [_c("img", {
    staticClass: "float-spinner",
    attrs: {
      alt: "",
      src: _vm.loadingIcon
    }
  }), _vm._v(" "), _c("span", {
    attrs: {
      id: "messageText"
    }
  }), _vm._v(" "), _c("div", {
    staticStyle: {
      clear: "both"
    }
  })]), _vm._v(" "), _c("h2", {
    staticClass: "login-form__headline",
    attrs: {
      "data-login-form-headline": ""
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.headlineText) + "\n\t\t")]), _vm._v(" "), _c("NcTextField", {
    ref: "user",
    class: {
      shake: _vm.invalidPassword
    },
    attrs: {
      id: "user",
      label: _vm.loginText,
      name: "user",
      maxlength: 255,
      autocapitalize: "none",
      spellchecking: false,
      autocomplete: _vm.autoCompleteAllowed ? "username" : "off",
      required: "",
      error: _vm.userNameInputLengthIs255,
      "helper-text": _vm.userInputHelperText,
      "data-login-form-input-user": ""
    },
    on: {
      change: _vm.updateUsername
    },
    model: {
      value: _vm.user,
      callback: function ($$v) {
        _vm.user = $$v;
      },
      expression: "user"
    }
  }), _vm._v(" "), _c("NcPasswordField", {
    ref: "password",
    class: {
      shake: _vm.invalidPassword
    },
    attrs: {
      id: "password",
      name: "password",
      spellchecking: false,
      autocapitalize: "none",
      autocomplete: _vm.autoCompleteAllowed ? "current-password" : "off",
      label: _vm.t("core", "Password"),
      "helper-text": _vm.errorLabel,
      error: _vm.isError,
      visible: _vm.visible,
      "data-login-form-input-password": "",
      required: ""
    },
    model: {
      value: _vm.password,
      callback: function ($$v) {
        _vm.password = $$v;
      },
      expression: "password"
    }
  }), _vm._v(" "), _vm.remembermeAllowed ? _c("NcCheckboxRadioSwitch", {
    ref: "rememberme",
    attrs: {
      id: "rememberme",
      name: "rememberme",
      value: "1",
      "data-login-form-input-rememberme": ""
    },
    model: {
      value: _vm.rememberme,
      callback: function ($$v) {
        _vm.rememberme = $$v;
      },
      expression: "rememberme"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("core", "Remember me")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("LoginButton", {
    attrs: {
      "data-login-form-submit": "",
      loading: _vm.loading
    }
  }), _vm._v(" "), _vm.redirectUrl ? _c("input", {
    attrs: {
      type: "hidden",
      name: "redirect_url"
    },
    domProps: {
      value: _vm.redirectUrl
    }
  }) : _vm._e(), _vm._v(" "), _c("input", {
    attrs: {
      type: "hidden",
      name: "timezone"
    },
    domProps: {
      value: _vm.timezone
    }
  }), _vm._v(" "), _c("input", {
    attrs: {
      type: "hidden",
      name: "timezone_offset"
    },
    domProps: {
      value: _vm.timezoneOffset
    }
  }), _vm._v(" "), _c("input", {
    attrs: {
      type: "hidden",
      name: "requesttoken"
    },
    domProps: {
      value: _vm.requestToken
    }
  }), _vm._v(" "), _vm.directLogin ? _c("input", {
    attrs: {
      type: "hidden",
      name: "direct",
      value: "1"
    }
  }) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return (_vm.isHttps || _vm.isLocalhost) && _vm.supportsWebauthn ? _c("form", {
    ref: "loginForm",
    staticClass: "password-less-login-form",
    attrs: {
      "aria-labelledby": "password-less-login-form-title",
      method: "post",
      name: "login"
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.submit.apply(null, arguments);
      }
    }
  }, [_c("h2", {
    attrs: {
      id: "password-less-login-form-title"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("core", "Log in with a device")) + "\n\t")]), _vm._v(" "), _c("NcTextField", {
    attrs: {
      required: "",
      "model-value": _vm.user,
      autocomplete: _vm.autoCompleteAllowed ? "on" : "off",
      error: !_vm.validCredentials,
      label: _vm.t("core", "Login or email"),
      placeholder: _vm.t("core", "Login or email"),
      "helper-text": !_vm.validCredentials ? _vm.t("core", "Your account is not setup for passwordless login.") : ""
    },
    on: {
      "update:value": _vm.changeUsername
    }
  }), _vm._v(" "), _vm.validCredentials ? _c("LoginButton", {
    attrs: {
      loading: _vm.loading
    },
    on: {
      click: _vm.authenticate
    }
  }) : _vm._e()], 1) : !_vm.isHttps && !_vm.isLocalhost ? _c("NcEmptyContent", {
    attrs: {
      name: _vm.t("core", "Your connection is not secure"),
      description: _vm.t("core", "Passwordless authentication is only available over a secure connection.")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("LockOpenIcon")];
      },
      proxy: true
    }])
  }) : _c("NcEmptyContent", {
    attrs: {
      name: _vm.t("core", "Browser not supported"),
      description: _vm.t("core", "Passwordless authentication is not supported in your browser.")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("InformationIcon")];
      },
      proxy: true
    }])
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true"
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("form", {
    staticClass: "reset-password-form",
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.submit.apply(null, arguments);
      }
    }
  }, [_c("h2", [_vm._v(_vm._s(_vm.t("core", "Reset password")))]), _vm._v(" "), _c("NcTextField", {
    attrs: {
      id: "user",
      name: "user",
      maxlength: 255,
      autocapitalize: "off",
      label: _vm.t("core", "Login or email"),
      error: _vm.userNameInputLengthIs255,
      "helper-text": _vm.userInputHelperText,
      required: ""
    },
    on: {
      change: _vm.updateUsername
    },
    model: {
      value: _vm.user,
      callback: function ($$v) {
        _vm.user = $$v;
      },
      expression: "user"
    }
  }), _vm._v(" "), _c("LoginButton", {
    attrs: {
      loading: _vm.loading,
      value: _vm.t("core", "Reset password")
    }
  }), _vm._v(" "), _c("NcButton", {
    attrs: {
      variant: "tertiary",
      wide: ""
    },
    on: {
      click: function ($event) {
        return _vm.$emit("abort");
      }
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("core", "Back to login")) + "\n\t")]), _vm._v(" "), _vm.message === "send-success" ? _c("NcNoteCard", {
    attrs: {
      type: "success"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("core", "If this account exists, a password reset message has been sent to its email address. If you do not receive it, verify your email address and/or Login, check your spam/junk folders or ask your local administration for help.")) + "\n\t")]) : _vm.message === "send-error" ? _c("NcNoteCard", {
    attrs: {
      type: "error"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("core", "Couldn't send reset email. Please contact your administrator.")) + "\n\t")]) : _vm.message === "reset-error" ? _c("NcNoteCard", {
    attrs: {
      type: "error"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("core", "Password cannot be changed. Please contact your administrator.")) + "\n\t")]) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true"
/*!*********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("form", {
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.submit.apply(null, arguments);
      }
    }
  }, [_c("fieldset", [_c("p", [_c("label", {
    staticClass: "infield",
    attrs: {
      for: "password"
    }
  }, [_vm._v(_vm._s(_vm.t("core", "New password")))]), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.password,
      expression: "password"
    }],
    attrs: {
      id: "password",
      type: "password",
      name: "password",
      autocomplete: "new-password",
      autocapitalize: "none",
      spellcheck: "false",
      required: "",
      placeholder: _vm.t("core", "New password")
    },
    domProps: {
      value: _vm.password
    },
    on: {
      input: function ($event) {
        if ($event.target.composing) return;
        _vm.password = $event.target.value;
      }
    }
  })]), _vm._v(" "), _vm.encrypted ? _c("div", {
    staticClass: "update"
  }, [_c("p", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("core", "Your files are encrypted. There will be no way to get your data back after your password is reset. If you are not sure what to do, please contact your administrator before you continue. Do you really want to continue?")) + "\n\t\t\t")]), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.proceed,
      expression: "proceed"
    }],
    staticClass: "checkbox",
    attrs: {
      id: "encrypted-continue",
      type: "checkbox"
    },
    domProps: {
      checked: Array.isArray(_vm.proceed) ? _vm._i(_vm.proceed, null) > -1 : _vm.proceed
    },
    on: {
      change: function ($event) {
        var $$a = _vm.proceed,
          $$el = $event.target,
          $$c = $$el.checked ? true : false;
        if (Array.isArray($$a)) {
          var $$v = null,
            $$i = _vm._i($$a, $$v);
          if ($$el.checked) {
            $$i < 0 && (_vm.proceed = $$a.concat([$$v]));
          } else {
            $$i > -1 && (_vm.proceed = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
          }
        } else {
          _vm.proceed = $$c;
        }
      }
    }
  }), _vm._v(" "), _c("label", {
    attrs: {
      for: "encrypted-continue"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("core", "I know what I'm doing")) + "\n\t\t\t")])]) : _vm._e(), _vm._v(" "), _c("LoginButton", {
    attrs: {
      loading: _vm.loading,
      value: _vm.t("core", "Reset password"),
      "value-loading": _vm.t("core", "Resetting password")
    }
  }), _vm._v(" "), _vm.error && _vm.message ? _c("p", {
    class: {
      warning: _vm.error
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.message) + "\n\t\t")]) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=template&id=020fd45b&scoped=true"
/*!*************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=template&id=020fd45b&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "guest-box login-box"
  }, [!_vm.hideLoginForm || _vm.directLogin ? [_c("transition", {
    attrs: {
      name: "fade",
      mode: "out-in"
    }
  }, [!_vm.passwordlessLogin && !_vm.resetPassword && _vm.resetPasswordTarget === "" ? _c("div", {
    staticClass: "login-box__wrapper"
  }, [_c("LoginForm", {
    attrs: {
      username: _vm.user,
      "redirect-url": _vm.redirectUrl,
      "direct-login": _vm.directLogin,
      messages: _vm.messages,
      errors: _vm.errors,
      "throttle-delay": _vm.throttleDelay,
      "auto-complete-allowed": _vm.autoCompleteAllowed,
      "rememberme-allowed": _vm.remembermeAllowed,
      "email-states": _vm.emailStates
    },
    on: {
      "update:username": function ($event) {
        _vm.user = $event;
      },
      submit: function ($event) {
        _vm.loading = true;
      }
    }
  }), _vm._v(" "), _vm.hasPasswordless ? _c("NcButton", {
    attrs: {
      variant: "tertiary",
      wide: ""
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        _vm.passwordlessLogin = true;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Log in with a device")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.canResetPassword && _vm.resetPasswordLink !== "" ? _c("NcButton", {
    attrs: {
      id: "lost-password",
      href: _vm.resetPasswordLink,
      variant: "tertiary-no-background",
      wide: ""
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Forgot password?")) + "\n\t\t\t\t")]) : _vm.canResetPassword && !_vm.resetPassword ? _c("NcButton", {
    attrs: {
      id: "lost-password",
      variant: "tertiary",
      wide: ""
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        _vm.resetPassword = true;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Forgot password?")) + "\n\t\t\t\t")]) : _vm._e()], 1) : !_vm.loading && _vm.passwordlessLogin ? _c("div", {
    key: "reset-pw-less",
    staticClass: "login-additional login-box__wrapper"
  }, [_c("PasswordLessLoginForm", {
    attrs: {
      username: _vm.user,
      "redirect-url": _vm.redirectUrl,
      "auto-complete-allowed": _vm.autoCompleteAllowed,
      "is-https": _vm.isHttps,
      "is-localhost": _vm.isLocalhost
    },
    on: {
      "update:username": function ($event) {
        _vm.user = $event;
      },
      submit: function ($event) {
        _vm.loading = true;
      }
    }
  }), _vm._v(" "), _c("NcButton", {
    attrs: {
      variant: "tertiary",
      "aria-label": _vm.t("core", "Back to login form"),
      wide: true
    },
    on: {
      click: function ($event) {
        _vm.passwordlessLogin = false;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("core", "Back")) + "\n\t\t\t\t")])], 1) : !_vm.loading && _vm.canResetPassword ? _c("div", {
    key: "reset-can-reset",
    staticClass: "login-additional"
  }, [_c("div", {
    staticClass: "lost-password-container"
  }, [_vm.resetPassword ? _c("ResetPassword", {
    attrs: {
      username: _vm.user,
      "reset-password-link": _vm.resetPasswordLink
    },
    on: {
      "update:username": function ($event) {
        _vm.user = $event;
      },
      abort: function ($event) {
        _vm.resetPassword = false;
      }
    }
  }) : _vm._e()], 1)]) : _vm.resetPasswordTarget !== "" ? _c("div", [_c("UpdatePassword", {
    attrs: {
      username: _vm.user,
      "reset-password-target": _vm.resetPasswordTarget
    },
    on: {
      "update:username": function ($event) {
        _vm.user = $event;
      },
      done: _vm.passwordResetFinished
    }
  })], 1) : _vm._e()])] : [_c("transition", {
    attrs: {
      name: "fade",
      mode: "out-in"
    }
  }, [_c("NcNoteCard", {
    attrs: {
      type: "info",
      title: _vm.t("core", "Login form is disabled.")
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("core", "The Nextcloud login form is disabled. Use another login option if available or contact your administration.")) + "\n\t\t\t")])], 1)], _vm._v(" "), _c("div", {
    staticClass: "login-box__alternative-logins",
    attrs: {
      id: "alternative-logins"
    }
  }, _vm._l(_vm.alternativeLogins, function (alternativeLogin, index) {
    return _c("NcButton", {
      key: index,
      class: [alternativeLogin.class],
      attrs: {
        variant: "secondary",
        wide: true,
        role: "link",
        href: alternativeLogin.href
      }
    }, [_vm._v("\n\t\t\t" + _vm._s(alternativeLogin.name) + "\n\t\t")]);
  }), 1)], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true"
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
___CSS_LOADER_EXPORT___.push([module.id, `.button-vue[data-v-2b0f9fce] {
  margin-top: 0.5rem;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
___CSS_LOADER_EXPORT___.push([module.id, `.login-form[data-v-722a846b] {
  text-align: start;
  font-size: 1rem;
  margin: 0;
}
.login-form__fieldset[data-v-722a846b] {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.login-form__headline[data-v-722a846b] {
  text-align: center;
  overflow-wrap: anywhere;
}
.login-form[data-v-722a846b] input:invalid:not(:user-invalid) {
  border-color: var(--color-border-maxcontrast) !important;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
___CSS_LOADER_EXPORT___.push([module.id, `.password-less-login-form[data-v-34bf48f7] {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
___CSS_LOADER_EXPORT___.push([module.id, `.reset-password-form[data-v-a10057b0] {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss"
/*!********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.login-box[data-v-020fd45b] {
  width: 320px;
  box-sizing: border-box;
}
.login-box__wrapper[data-v-020fd45b] {
  display: flex;
  flex-direction: column;
  gap: calc(2 * var(--default-grid-baseline));
}
.login-box__alternative-logins[data-v-020fd45b] {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.fade-enter-active[data-v-020fd45b], .fade-leave-active[data-v-020fd45b] {
  transition: opacity 0.3s;
}
.fade-enter[data-v-020fd45b], .fade-leave-to[data-v-020fd45b] {
  opacity: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
___CSS_LOADER_EXPORT___.push([module.id, `
fieldset[data-v-66634656] {
	text-align: center;
}
input[type=submit][data-v-66634656] {
	margin-top: 20px;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/moment/locale sync recursive ^\\.\\/.*$"
/*!***************************************************!*\
  !*** ./node_modules/moment/locale/ sync ^\.\/.*$ ***!
  \***************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./af": "./node_modules/moment/locale/af.js",
	"./af.js": "./node_modules/moment/locale/af.js",
	"./ar": "./node_modules/moment/locale/ar.js",
	"./ar-dz": "./node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "./node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "./node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "./node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "./node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "./node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "./node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "./node_modules/moment/locale/ar-ma.js",
	"./ar-ps": "./node_modules/moment/locale/ar-ps.js",
	"./ar-ps.js": "./node_modules/moment/locale/ar-ps.js",
	"./ar-sa": "./node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "./node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "./node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "./node_modules/moment/locale/ar-tn.js",
	"./ar.js": "./node_modules/moment/locale/ar.js",
	"./az": "./node_modules/moment/locale/az.js",
	"./az.js": "./node_modules/moment/locale/az.js",
	"./be": "./node_modules/moment/locale/be.js",
	"./be.js": "./node_modules/moment/locale/be.js",
	"./bg": "./node_modules/moment/locale/bg.js",
	"./bg.js": "./node_modules/moment/locale/bg.js",
	"./bm": "./node_modules/moment/locale/bm.js",
	"./bm.js": "./node_modules/moment/locale/bm.js",
	"./bn": "./node_modules/moment/locale/bn.js",
	"./bn-bd": "./node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "./node_modules/moment/locale/bn-bd.js",
	"./bn.js": "./node_modules/moment/locale/bn.js",
	"./bo": "./node_modules/moment/locale/bo.js",
	"./bo.js": "./node_modules/moment/locale/bo.js",
	"./br": "./node_modules/moment/locale/br.js",
	"./br.js": "./node_modules/moment/locale/br.js",
	"./bs": "./node_modules/moment/locale/bs.js",
	"./bs.js": "./node_modules/moment/locale/bs.js",
	"./ca": "./node_modules/moment/locale/ca.js",
	"./ca.js": "./node_modules/moment/locale/ca.js",
	"./cs": "./node_modules/moment/locale/cs.js",
	"./cs.js": "./node_modules/moment/locale/cs.js",
	"./cv": "./node_modules/moment/locale/cv.js",
	"./cv.js": "./node_modules/moment/locale/cv.js",
	"./cy": "./node_modules/moment/locale/cy.js",
	"./cy.js": "./node_modules/moment/locale/cy.js",
	"./da": "./node_modules/moment/locale/da.js",
	"./da.js": "./node_modules/moment/locale/da.js",
	"./de": "./node_modules/moment/locale/de.js",
	"./de-at": "./node_modules/moment/locale/de-at.js",
	"./de-at.js": "./node_modules/moment/locale/de-at.js",
	"./de-ch": "./node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "./node_modules/moment/locale/de-ch.js",
	"./de.js": "./node_modules/moment/locale/de.js",
	"./dv": "./node_modules/moment/locale/dv.js",
	"./dv.js": "./node_modules/moment/locale/dv.js",
	"./el": "./node_modules/moment/locale/el.js",
	"./el.js": "./node_modules/moment/locale/el.js",
	"./en-au": "./node_modules/moment/locale/en-au.js",
	"./en-au.js": "./node_modules/moment/locale/en-au.js",
	"./en-ca": "./node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "./node_modules/moment/locale/en-ca.js",
	"./en-gb": "./node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "./node_modules/moment/locale/en-gb.js",
	"./en-ie": "./node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "./node_modules/moment/locale/en-ie.js",
	"./en-il": "./node_modules/moment/locale/en-il.js",
	"./en-il.js": "./node_modules/moment/locale/en-il.js",
	"./en-in": "./node_modules/moment/locale/en-in.js",
	"./en-in.js": "./node_modules/moment/locale/en-in.js",
	"./en-nz": "./node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "./node_modules/moment/locale/en-nz.js",
	"./en-sg": "./node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "./node_modules/moment/locale/en-sg.js",
	"./eo": "./node_modules/moment/locale/eo.js",
	"./eo.js": "./node_modules/moment/locale/eo.js",
	"./es": "./node_modules/moment/locale/es.js",
	"./es-do": "./node_modules/moment/locale/es-do.js",
	"./es-do.js": "./node_modules/moment/locale/es-do.js",
	"./es-mx": "./node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "./node_modules/moment/locale/es-mx.js",
	"./es-us": "./node_modules/moment/locale/es-us.js",
	"./es-us.js": "./node_modules/moment/locale/es-us.js",
	"./es.js": "./node_modules/moment/locale/es.js",
	"./et": "./node_modules/moment/locale/et.js",
	"./et.js": "./node_modules/moment/locale/et.js",
	"./eu": "./node_modules/moment/locale/eu.js",
	"./eu.js": "./node_modules/moment/locale/eu.js",
	"./fa": "./node_modules/moment/locale/fa.js",
	"./fa.js": "./node_modules/moment/locale/fa.js",
	"./fi": "./node_modules/moment/locale/fi.js",
	"./fi.js": "./node_modules/moment/locale/fi.js",
	"./fil": "./node_modules/moment/locale/fil.js",
	"./fil.js": "./node_modules/moment/locale/fil.js",
	"./fo": "./node_modules/moment/locale/fo.js",
	"./fo.js": "./node_modules/moment/locale/fo.js",
	"./fr": "./node_modules/moment/locale/fr.js",
	"./fr-ca": "./node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "./node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "./node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "./node_modules/moment/locale/fr-ch.js",
	"./fr.js": "./node_modules/moment/locale/fr.js",
	"./fy": "./node_modules/moment/locale/fy.js",
	"./fy.js": "./node_modules/moment/locale/fy.js",
	"./ga": "./node_modules/moment/locale/ga.js",
	"./ga.js": "./node_modules/moment/locale/ga.js",
	"./gd": "./node_modules/moment/locale/gd.js",
	"./gd.js": "./node_modules/moment/locale/gd.js",
	"./gl": "./node_modules/moment/locale/gl.js",
	"./gl.js": "./node_modules/moment/locale/gl.js",
	"./gom-deva": "./node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "./node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "./node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "./node_modules/moment/locale/gom-latn.js",
	"./gu": "./node_modules/moment/locale/gu.js",
	"./gu.js": "./node_modules/moment/locale/gu.js",
	"./he": "./node_modules/moment/locale/he.js",
	"./he.js": "./node_modules/moment/locale/he.js",
	"./hi": "./node_modules/moment/locale/hi.js",
	"./hi.js": "./node_modules/moment/locale/hi.js",
	"./hr": "./node_modules/moment/locale/hr.js",
	"./hr.js": "./node_modules/moment/locale/hr.js",
	"./hu": "./node_modules/moment/locale/hu.js",
	"./hu.js": "./node_modules/moment/locale/hu.js",
	"./hy-am": "./node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "./node_modules/moment/locale/hy-am.js",
	"./id": "./node_modules/moment/locale/id.js",
	"./id.js": "./node_modules/moment/locale/id.js",
	"./is": "./node_modules/moment/locale/is.js",
	"./is.js": "./node_modules/moment/locale/is.js",
	"./it": "./node_modules/moment/locale/it.js",
	"./it-ch": "./node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "./node_modules/moment/locale/it-ch.js",
	"./it.js": "./node_modules/moment/locale/it.js",
	"./ja": "./node_modules/moment/locale/ja.js",
	"./ja.js": "./node_modules/moment/locale/ja.js",
	"./jv": "./node_modules/moment/locale/jv.js",
	"./jv.js": "./node_modules/moment/locale/jv.js",
	"./ka": "./node_modules/moment/locale/ka.js",
	"./ka.js": "./node_modules/moment/locale/ka.js",
	"./kk": "./node_modules/moment/locale/kk.js",
	"./kk.js": "./node_modules/moment/locale/kk.js",
	"./km": "./node_modules/moment/locale/km.js",
	"./km.js": "./node_modules/moment/locale/km.js",
	"./kn": "./node_modules/moment/locale/kn.js",
	"./kn.js": "./node_modules/moment/locale/kn.js",
	"./ko": "./node_modules/moment/locale/ko.js",
	"./ko.js": "./node_modules/moment/locale/ko.js",
	"./ku": "./node_modules/moment/locale/ku.js",
	"./ku-kmr": "./node_modules/moment/locale/ku-kmr.js",
	"./ku-kmr.js": "./node_modules/moment/locale/ku-kmr.js",
	"./ku.js": "./node_modules/moment/locale/ku.js",
	"./ky": "./node_modules/moment/locale/ky.js",
	"./ky.js": "./node_modules/moment/locale/ky.js",
	"./lb": "./node_modules/moment/locale/lb.js",
	"./lb.js": "./node_modules/moment/locale/lb.js",
	"./lo": "./node_modules/moment/locale/lo.js",
	"./lo.js": "./node_modules/moment/locale/lo.js",
	"./lt": "./node_modules/moment/locale/lt.js",
	"./lt.js": "./node_modules/moment/locale/lt.js",
	"./lv": "./node_modules/moment/locale/lv.js",
	"./lv.js": "./node_modules/moment/locale/lv.js",
	"./me": "./node_modules/moment/locale/me.js",
	"./me.js": "./node_modules/moment/locale/me.js",
	"./mi": "./node_modules/moment/locale/mi.js",
	"./mi.js": "./node_modules/moment/locale/mi.js",
	"./mk": "./node_modules/moment/locale/mk.js",
	"./mk.js": "./node_modules/moment/locale/mk.js",
	"./ml": "./node_modules/moment/locale/ml.js",
	"./ml.js": "./node_modules/moment/locale/ml.js",
	"./mn": "./node_modules/moment/locale/mn.js",
	"./mn.js": "./node_modules/moment/locale/mn.js",
	"./mr": "./node_modules/moment/locale/mr.js",
	"./mr.js": "./node_modules/moment/locale/mr.js",
	"./ms": "./node_modules/moment/locale/ms.js",
	"./ms-my": "./node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "./node_modules/moment/locale/ms-my.js",
	"./ms.js": "./node_modules/moment/locale/ms.js",
	"./mt": "./node_modules/moment/locale/mt.js",
	"./mt.js": "./node_modules/moment/locale/mt.js",
	"./my": "./node_modules/moment/locale/my.js",
	"./my.js": "./node_modules/moment/locale/my.js",
	"./nb": "./node_modules/moment/locale/nb.js",
	"./nb.js": "./node_modules/moment/locale/nb.js",
	"./ne": "./node_modules/moment/locale/ne.js",
	"./ne.js": "./node_modules/moment/locale/ne.js",
	"./nl": "./node_modules/moment/locale/nl.js",
	"./nl-be": "./node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "./node_modules/moment/locale/nl-be.js",
	"./nl.js": "./node_modules/moment/locale/nl.js",
	"./nn": "./node_modules/moment/locale/nn.js",
	"./nn.js": "./node_modules/moment/locale/nn.js",
	"./oc-lnc": "./node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "./node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "./node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "./node_modules/moment/locale/pa-in.js",
	"./pl": "./node_modules/moment/locale/pl.js",
	"./pl.js": "./node_modules/moment/locale/pl.js",
	"./pt": "./node_modules/moment/locale/pt.js",
	"./pt-br": "./node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "./node_modules/moment/locale/pt-br.js",
	"./pt.js": "./node_modules/moment/locale/pt.js",
	"./ro": "./node_modules/moment/locale/ro.js",
	"./ro.js": "./node_modules/moment/locale/ro.js",
	"./ru": "./node_modules/moment/locale/ru.js",
	"./ru.js": "./node_modules/moment/locale/ru.js",
	"./sd": "./node_modules/moment/locale/sd.js",
	"./sd.js": "./node_modules/moment/locale/sd.js",
	"./se": "./node_modules/moment/locale/se.js",
	"./se.js": "./node_modules/moment/locale/se.js",
	"./si": "./node_modules/moment/locale/si.js",
	"./si.js": "./node_modules/moment/locale/si.js",
	"./sk": "./node_modules/moment/locale/sk.js",
	"./sk.js": "./node_modules/moment/locale/sk.js",
	"./sl": "./node_modules/moment/locale/sl.js",
	"./sl.js": "./node_modules/moment/locale/sl.js",
	"./sq": "./node_modules/moment/locale/sq.js",
	"./sq.js": "./node_modules/moment/locale/sq.js",
	"./sr": "./node_modules/moment/locale/sr.js",
	"./sr-cyrl": "./node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "./node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "./node_modules/moment/locale/sr.js",
	"./ss": "./node_modules/moment/locale/ss.js",
	"./ss.js": "./node_modules/moment/locale/ss.js",
	"./sv": "./node_modules/moment/locale/sv.js",
	"./sv.js": "./node_modules/moment/locale/sv.js",
	"./sw": "./node_modules/moment/locale/sw.js",
	"./sw.js": "./node_modules/moment/locale/sw.js",
	"./ta": "./node_modules/moment/locale/ta.js",
	"./ta.js": "./node_modules/moment/locale/ta.js",
	"./te": "./node_modules/moment/locale/te.js",
	"./te.js": "./node_modules/moment/locale/te.js",
	"./tet": "./node_modules/moment/locale/tet.js",
	"./tet.js": "./node_modules/moment/locale/tet.js",
	"./tg": "./node_modules/moment/locale/tg.js",
	"./tg.js": "./node_modules/moment/locale/tg.js",
	"./th": "./node_modules/moment/locale/th.js",
	"./th.js": "./node_modules/moment/locale/th.js",
	"./tk": "./node_modules/moment/locale/tk.js",
	"./tk.js": "./node_modules/moment/locale/tk.js",
	"./tl-ph": "./node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "./node_modules/moment/locale/tl-ph.js",
	"./tlh": "./node_modules/moment/locale/tlh.js",
	"./tlh.js": "./node_modules/moment/locale/tlh.js",
	"./tr": "./node_modules/moment/locale/tr.js",
	"./tr.js": "./node_modules/moment/locale/tr.js",
	"./tzl": "./node_modules/moment/locale/tzl.js",
	"./tzl.js": "./node_modules/moment/locale/tzl.js",
	"./tzm": "./node_modules/moment/locale/tzm.js",
	"./tzm-latn": "./node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "./node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "./node_modules/moment/locale/tzm.js",
	"./ug-cn": "./node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "./node_modules/moment/locale/ug-cn.js",
	"./uk": "./node_modules/moment/locale/uk.js",
	"./uk.js": "./node_modules/moment/locale/uk.js",
	"./ur": "./node_modules/moment/locale/ur.js",
	"./ur.js": "./node_modules/moment/locale/ur.js",
	"./uz": "./node_modules/moment/locale/uz.js",
	"./uz-latn": "./node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "./node_modules/moment/locale/uz-latn.js",
	"./uz.js": "./node_modules/moment/locale/uz.js",
	"./vi": "./node_modules/moment/locale/vi.js",
	"./vi.js": "./node_modules/moment/locale/vi.js",
	"./x-pseudo": "./node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "./node_modules/moment/locale/x-pseudo.js",
	"./yo": "./node_modules/moment/locale/yo.js",
	"./yo.js": "./node_modules/moment/locale/yo.js",
	"./zh-cn": "./node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "./node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "./node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "./node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "./node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "./node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "./node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "./node_modules/moment/locale/zh-tw.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss"
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./core/src/components/login/LoginButton.vue"
/*!***************************************************!*\
  !*** ./core/src/components/login/LoginButton.vue ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true */ "./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true");
/* harmony import */ var _LoginButton_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LoginButton.vue?vue&type=script&lang=js */ "./core/src/components/login/LoginButton.vue?vue&type=script&lang=js");
/* harmony import */ var _LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true */ "./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _LoginButton_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "2b0f9fce",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "core/src/components/login/LoginButton.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./core/src/components/login/LoginForm.vue"
/*!*************************************************!*\
  !*** ./core/src/components/login/LoginForm.vue ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _LoginForm_vue_vue_type_template_id_722a846b_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LoginForm.vue?vue&type=template&id=722a846b&scoped=true */ "./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true");
/* harmony import */ var _LoginForm_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LoginForm.vue?vue&type=script&lang=js */ "./core/src/components/login/LoginForm.vue?vue&type=script&lang=js");
/* harmony import */ var _LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true */ "./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _LoginForm_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _LoginForm_vue_vue_type_template_id_722a846b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _LoginForm_vue_vue_type_template_id_722a846b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "722a846b",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "core/src/components/login/LoginForm.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./core/src/components/login/PasswordLessLoginForm.vue"
/*!*************************************************************!*\
  !*** ./core/src/components/login/PasswordLessLoginForm.vue ***!
  \*************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true */ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true");
/* harmony import */ var _PasswordLessLoginForm_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PasswordLessLoginForm.vue?vue&type=script&lang=js */ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js");
/* harmony import */ var _PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true */ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _PasswordLessLoginForm_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "34bf48f7",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "core/src/components/login/PasswordLessLoginForm.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./core/src/components/login/ResetPassword.vue"
/*!*****************************************************!*\
  !*** ./core/src/components/login/ResetPassword.vue ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true */ "./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true");
/* harmony import */ var _ResetPassword_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ResetPassword.vue?vue&type=script&lang=ts */ "./core/src/components/login/ResetPassword.vue?vue&type=script&lang=ts");
/* harmony import */ var _ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true */ "./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ResetPassword_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "a10057b0",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "core/src/components/login/ResetPassword.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./core/src/components/login/UpdatePassword.vue"
/*!******************************************************!*\
  !*** ./core/src/components/login/UpdatePassword.vue ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UpdatePassword_vue_vue_type_template_id_66634656_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UpdatePassword.vue?vue&type=template&id=66634656&scoped=true */ "./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true");
/* harmony import */ var _UpdatePassword_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UpdatePassword.vue?vue&type=script&lang=js */ "./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js");
/* harmony import */ var _UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css */ "./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UpdatePassword_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UpdatePassword_vue_vue_type_template_id_66634656_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _UpdatePassword_vue_vue_type_template_id_66634656_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "66634656",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "core/src/components/login/UpdatePassword.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./core/src/views/Login.vue"
/*!**********************************!*\
  !*** ./core/src/views/Login.vue ***!
  \**********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Login_vue_vue_type_template_id_020fd45b_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Login.vue?vue&type=template&id=020fd45b&scoped=true */ "./core/src/views/Login.vue?vue&type=template&id=020fd45b&scoped=true");
/* harmony import */ var _Login_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Login.vue?vue&type=script&lang=js */ "./core/src/views/Login.vue?vue&type=script&lang=js");
/* harmony import */ var _Login_vue_vue_type_style_index_0_id_020fd45b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss */ "./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Login_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Login_vue_vue_type_template_id_020fd45b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _Login_vue_vue_type_template_id_020fd45b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "020fd45b",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "core/src/views/Login.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-material-design-icons/LockOpen.vue"
/*!*************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/LockOpen.vue ***!
  \*************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _LockOpen_vue_vue_type_template_id_2f5a5bc1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LockOpen.vue?vue&type=template&id=2f5a5bc1 */ "./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=template&id=2f5a5bc1");
/* harmony import */ var _LockOpen_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LockOpen.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _LockOpen_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _LockOpen_vue_vue_type_template_id_2f5a5bc1__WEBPACK_IMPORTED_MODULE_0__.render,
  _LockOpen_vue_vue_type_template_id_2f5a5bc1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/LockOpen.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "LockOpenIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
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
});


/***/ },

/***/ "./core/src/components/login/ResetPassword.vue?vue&type=script&lang=ts"
/*!*****************************************************************************!*\
  !*** ./core/src/components/login/ResetPassword.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ResetPassword.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./core/src/components/login/LoginButton.vue?vue&type=script&lang=js"
/*!***************************************************************************!*\
  !*** ./core/src/components/login/LoginButton.vue?vue&type=script&lang=js ***!
  \***************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginButton.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./core/src/components/login/LoginForm.vue?vue&type=script&lang=js"
/*!*************************************************************************!*\
  !*** ./core/src/components/login/LoginForm.vue?vue&type=script&lang=js ***!
  \*************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginForm.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js"
/*!*************************************************************************************!*\
  !*** ./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js ***!
  \*************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PasswordLessLoginForm.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js"
/*!******************************************************************************!*\
  !*** ./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js ***!
  \******************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdatePassword.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./core/src/views/Login.vue?vue&type=script&lang=js"
/*!**********************************************************!*\
  !*** ./core/src/views/Login.vue?vue&type=script&lang=js ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Login.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true"
/*!*********************************************************************************************!*\
  !*** ./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true ***!
  \*********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_template_id_2b0f9fce_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=template&id=2b0f9fce&scoped=true");


/***/ },

/***/ "./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true"
/*!*******************************************************************************************!*\
  !*** ./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true ***!
  \*******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_template_id_722a846b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_template_id_722a846b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_template_id_722a846b_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginForm.vue?vue&type=template&id=722a846b&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=template&id=722a846b&scoped=true");


/***/ },

/***/ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true"
/*!*******************************************************************************************************!*\
  !*** ./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true ***!
  \*******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_template_id_34bf48f7_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=template&id=34bf48f7&scoped=true");


/***/ },

/***/ "./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true"
/*!***********************************************************************************************!*\
  !*** ./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_template_id_a10057b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=template&id=a10057b0&scoped=true");


/***/ },

/***/ "./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true"
/*!************************************************************************************************!*\
  !*** ./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true ***!
  \************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_template_id_66634656_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_template_id_66634656_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_template_id_66634656_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdatePassword.vue?vue&type=template&id=66634656&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=template&id=66634656&scoped=true");


/***/ },

/***/ "./core/src/views/Login.vue?vue&type=template&id=020fd45b&scoped=true"
/*!****************************************************************************!*\
  !*** ./core/src/views/Login.vue?vue&type=template&id=020fd45b&scoped=true ***!
  \****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_template_id_020fd45b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_template_id_020fd45b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_template_id_020fd45b_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Login.vue?vue&type=template&id=020fd45b&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=template&id=020fd45b&scoped=true");


/***/ },

/***/ "./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true"
/*!************************************************************************************************************!*\
  !*** ./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true ***!
  \************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginButton_vue_vue_type_style_index_0_id_2b0f9fce_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginButton.vue?vue&type=style&index=0&id=2b0f9fce&lang=scss&scoped=true");


/***/ },

/***/ "./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true"
/*!**********************************************************************************************************!*\
  !*** ./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true ***!
  \**********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LoginForm_vue_vue_type_style_index_0_id_722a846b_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/LoginForm.vue?vue&type=style&index=0&id=722a846b&lang=scss&scoped=true");


/***/ },

/***/ "./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true"
/*!**********************************************************************************************************************!*\
  !*** ./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true ***!
  \**********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PasswordLessLoginForm_vue_vue_type_style_index_0_id_34bf48f7_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/PasswordLessLoginForm.vue?vue&type=style&index=0&id=34bf48f7&lang=scss&scoped=true");


/***/ },

/***/ "./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true"
/*!**************************************************************************************************************!*\
  !*** ./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true ***!
  \**************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ResetPassword_vue_vue_type_style_index_0_id_a10057b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/ResetPassword.vue?vue&type=style&index=0&id=a10057b0&lang=scss&scoped=true");


/***/ },

/***/ "./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss"
/*!*******************************************************************************************!*\
  !*** ./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss ***!
  \*******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Login_vue_vue_type_style_index_0_id_020fd45b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/views/Login.vue?vue&type=style&index=0&id=020fd45b&scoped=true&lang=scss");


/***/ },

/***/ "./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css"
/*!**************************************************************************************************************!*\
  !*** ./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css ***!
  \**************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdatePassword_vue_vue_type_style_index_0_id_66634656_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./core/src/components/login/UpdatePassword.vue?vue&type=style&index=0&id=66634656&scoped=true&lang=css");


/***/ },

/***/ "./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=script&lang=js"
/*!*************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=script&lang=js ***!
  \*************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_LockOpen_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./LockOpen.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_LockOpen_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=template&id=2f5a5bc1"
/*!*******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=template&id=2f5a5bc1 ***!
  \*******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_LockOpen_vue_vue_type_template_id_2f5a5bc1__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_LockOpen_vue_vue_type_template_id_2f5a5bc1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_LockOpen_vue_vue_type_template_id_2f5a5bc1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./LockOpen.vue?vue&type=template&id=2f5a5bc1 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=template&id=2f5a5bc1");


/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=template&id=2f5a5bc1"
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOpen.vue?vue&type=template&id=2f5a5bc1 ***!
  \***********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon lock-open-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6C4.89,22 4,21.1 4,20V10A2,2 0 0,1 6,8H15V6A3,3 0 0,0 12,3A3,3 0 0,0 9,6H7A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,17A2,2 0 0,0 14,15A2,2 0 0,0 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/@mdi/svg/svg/folder-move.svg?raw"
/*!*******************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder-move.svg?raw ***!
  \*******************************************************/
(module) {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder-move\" viewBox=\"0 0 24 24\"><path d=\"M14,18V15H10V11H14V8L19,13M20,6H12L10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6Z\" /></svg>";

/***/ },

/***/ "?3e83"
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
() {

/* (ignored) */

/***/ },

/***/ "?19e6"
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
() {

/* (ignored) */

/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js"
/*!*************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js ***!
  \*************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   base64URLStringToBuffer: () => (/* binding */ base64URLStringToBuffer)
/* harmony export */ });
/**
 * Convert from a Base64URL-encoded string to an Array Buffer. Best used when converting a
 * credential ID from a JSON string to an ArrayBuffer, like in allowCredentials or
 * excludeCredentials
 *
 * Helper method to compliment `bufferToBase64URLString`
 */
function base64URLStringToBuffer(base64URLString) {
    // Convert from Base64URL to Base64
    const base64 = base64URLString.replace(/-/g, '+').replace(/_/g, '/');
    /**
     * Pad with '=' until it's a multiple of four
     * (4 - (85 % 4 = 1) = 3) % 4 = 3 padding
     * (4 - (86 % 4 = 2) = 2) % 4 = 2 padding
     * (4 - (87 % 4 = 3) = 1) % 4 = 1 padding
     * (4 - (88 % 4 = 0) = 4) % 4 = 0 padding
     */
    const padLength = (4 - (base64.length % 4)) % 4;
    const padded = base64.padEnd(base64.length + padLength, '=');
    // Convert to a binary string
    const binary = atob(padded);
    // Convert binary string to buffer
    const buffer = new ArrayBuffer(binary.length);
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return buffer;
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js"
/*!*************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js ***!
  \*************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _browserSupportsWebAuthnInternals: () => (/* binding */ _browserSupportsWebAuthnInternals),
/* harmony export */   browserSupportsWebAuthn: () => (/* binding */ browserSupportsWebAuthn)
/* harmony export */ });
/**
 * Determine if the browser is capable of Webauthn
 */
function browserSupportsWebAuthn() {
    return _browserSupportsWebAuthnInternals.stubThis(globalThis?.PublicKeyCredential !== undefined &&
        typeof globalThis.PublicKeyCredential === 'function');
}
/**
 * Make it possible to stub the return value during testing
 * @ignore Don't include this in docs output
 */
const _browserSupportsWebAuthnInternals = {
    stubThis: (value) => value,
};


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js"
/*!*********************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js ***!
  \*********************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _browserSupportsWebAuthnAutofillInternals: () => (/* binding */ _browserSupportsWebAuthnAutofillInternals),
/* harmony export */   browserSupportsWebAuthnAutofill: () => (/* binding */ browserSupportsWebAuthnAutofill)
/* harmony export */ });
/* harmony import */ var _browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");

/**
 * Determine if the browser supports conditional UI, so that WebAuthn credentials can
 * be shown to the user in the browser's typical password autofill popup.
 */
function browserSupportsWebAuthnAutofill() {
    if (!(0,_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_0__.browserSupportsWebAuthn)()) {
        return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
    }
    /**
     * I don't like the `as unknown` here but there's a `declare var PublicKeyCredential` in
     * TS' DOM lib that's making it difficult for me to just go `as PublicKeyCredentialFuture` as I
     * want. I think I'm fine with this for now since it's _supposed_ to be temporary, until TS types
     * have a chance to catch up.
     */
    const globalPublicKeyCredential = globalThis
        .PublicKeyCredential;
    if (globalPublicKeyCredential?.isConditionalMediationAvailable === undefined) {
        return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
    }
    return _browserSupportsWebAuthnAutofillInternals.stubThis(globalPublicKeyCredential.isConditionalMediationAvailable());
}
// Make it possible to stub the return value during testing
const _browserSupportsWebAuthnAutofillInternals = {
    stubThis: (value) => value,
};


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js"
/*!*************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js ***!
  \*************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   bufferToBase64URLString: () => (/* binding */ bufferToBase64URLString)
/* harmony export */ });
/**
 * Convert the given array buffer into a Base64URL-encoded string. Ideal for converting various
 * credential response ArrayBuffers to string for sending back to the server as JSON.
 *
 * Helper method to compliment `base64URLStringToBuffer`
 */
function bufferToBase64URLString(buffer) {
    const bytes = new Uint8Array(buffer);
    let str = '';
    for (const charCode of bytes) {
        str += String.fromCharCode(charCode);
    }
    const base64String = btoa(str);
    return base64String.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js"
/*!*****************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js ***!
  \*****************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   identifyAuthenticationError: () => (/* binding */ identifyAuthenticationError)
/* harmony export */ });
/* harmony import */ var _isValidDomain_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isValidDomain.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js");
/* harmony import */ var _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./webAuthnError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js");


/**
 * Attempt to intuit _why_ an error was raised after calling `navigator.credentials.get()`
 */
function identifyAuthenticationError({ error, options, }) {
    const { publicKey } = options;
    if (!publicKey) {
        throw Error('options was missing required publicKey property');
    }
    if (error.name === 'AbortError') {
        if (options.signal instanceof AbortSignal) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 16)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'Authentication ceremony was sent an abort signal',
                code: 'ERROR_CEREMONY_ABORTED',
                cause: error,
            });
        }
    }
    else if (error.name === 'NotAllowedError') {
        /**
         * Pass the error directly through. Platforms are overloading this error beyond what the spec
         * defines and we don't want to overwrite potentially useful error messages.
         */
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: error.message,
            code: 'ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY',
            cause: error,
        });
    }
    else if (error.name === 'SecurityError') {
        const effectiveDomain = globalThis.location.hostname;
        if (!(0,_isValidDomain_js__WEBPACK_IMPORTED_MODULE_0__.isValidDomain)(effectiveDomain)) {
            // https://www.w3.org/TR/webauthn-2/#sctn-discover-from-external-source (Step 5)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: `${globalThis.location.hostname} is an invalid domain`,
                code: 'ERROR_INVALID_DOMAIN',
                cause: error,
            });
        }
        else if (publicKey.rpId !== effectiveDomain) {
            // https://www.w3.org/TR/webauthn-2/#sctn-discover-from-external-source (Step 6)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: `The RP ID "${publicKey.rpId}" is invalid for this domain`,
                code: 'ERROR_INVALID_RP_ID',
                cause: error,
            });
        }
    }
    else if (error.name === 'UnknownError') {
        // https://www.w3.org/TR/webauthn-2/#sctn-op-get-assertion (Step 1)
        // https://www.w3.org/TR/webauthn-2/#sctn-op-get-assertion (Step 12)
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: 'The authenticator was unable to process the specified options, or could not create a new assertion signature',
            code: 'ERROR_AUTHENTICATOR_GENERAL_ERROR',
            cause: error,
        });
    }
    return error;
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js"
/*!***************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js ***!
  \***************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   identifyRegistrationError: () => (/* binding */ identifyRegistrationError)
/* harmony export */ });
/* harmony import */ var _isValidDomain_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isValidDomain.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js");
/* harmony import */ var _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./webAuthnError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js");


/**
 * Attempt to intuit _why_ an error was raised after calling `navigator.credentials.create()`
 */
function identifyRegistrationError({ error, options, }) {
    const { publicKey } = options;
    if (!publicKey) {
        throw Error('options was missing required publicKey property');
    }
    if (error.name === 'AbortError') {
        if (options.signal instanceof AbortSignal) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 16)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'Registration ceremony was sent an abort signal',
                code: 'ERROR_CEREMONY_ABORTED',
                cause: error,
            });
        }
    }
    else if (error.name === 'ConstraintError') {
        if (publicKey.authenticatorSelection?.requireResidentKey === true) {
            // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 4)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'Discoverable credentials were required but no available authenticator supported it',
                code: 'ERROR_AUTHENTICATOR_MISSING_DISCOVERABLE_CREDENTIAL_SUPPORT',
                cause: error,
            });
        }
        else if (
        // @ts-ignore: `mediation` doesn't yet exist on CredentialCreationOptions but it's possible as of Sept 2024
        options.mediation === 'conditional' &&
            publicKey.authenticatorSelection?.userVerification === 'required') {
            // https://w3c.github.io/webauthn/#sctn-createCredential (Step 22.4)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'User verification was required during automatic registration but it could not be performed',
                code: 'ERROR_AUTO_REGISTER_USER_VERIFICATION_FAILURE',
                cause: error,
            });
        }
        else if (publicKey.authenticatorSelection?.userVerification === 'required') {
            // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 5)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'User verification was required but no available authenticator supported it',
                code: 'ERROR_AUTHENTICATOR_MISSING_USER_VERIFICATION_SUPPORT',
                cause: error,
            });
        }
    }
    else if (error.name === 'InvalidStateError') {
        // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 20)
        // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 3)
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: 'The authenticator was previously registered',
            code: 'ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED',
            cause: error,
        });
    }
    else if (error.name === 'NotAllowedError') {
        /**
         * Pass the error directly through. Platforms are overloading this error beyond what the spec
         * defines and we don't want to overwrite potentially useful error messages.
         */
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: error.message,
            code: 'ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY',
            cause: error,
        });
    }
    else if (error.name === 'NotSupportedError') {
        const validPubKeyCredParams = publicKey.pubKeyCredParams.filter((param) => param.type === 'public-key');
        if (validPubKeyCredParams.length === 0) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 10)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'No entry in pubKeyCredParams was of type "public-key"',
                code: 'ERROR_MALFORMED_PUBKEYCREDPARAMS',
                cause: error,
            });
        }
        // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 2)
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: 'No available authenticator supported any of the specified pubKeyCredParams algorithms',
            code: 'ERROR_AUTHENTICATOR_NO_SUPPORTED_PUBKEYCREDPARAMS_ALG',
            cause: error,
        });
    }
    else if (error.name === 'SecurityError') {
        const effectiveDomain = globalThis.location.hostname;
        if (!(0,_isValidDomain_js__WEBPACK_IMPORTED_MODULE_0__.isValidDomain)(effectiveDomain)) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 7)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: `${globalThis.location.hostname} is an invalid domain`,
                code: 'ERROR_INVALID_DOMAIN',
                cause: error,
            });
        }
        else if (publicKey.rp.id !== effectiveDomain) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 8)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: `The RP ID "${publicKey.rp.id}" is invalid for this domain`,
                code: 'ERROR_INVALID_RP_ID',
                cause: error,
            });
        }
    }
    else if (error.name === 'TypeError') {
        if (publicKey.user.id.byteLength < 1 || publicKey.user.id.byteLength > 64) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 5)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'User ID was not between 1 and 64 characters',
                code: 'ERROR_INVALID_USER_ID_LENGTH',
                cause: error,
            });
        }
    }
    else if (error.name === 'UnknownError') {
        // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 1)
        // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 8)
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: 'The authenticator was unable to process the specified options, or could not create a new credential',
            code: 'ERROR_AUTHENTICATOR_GENERAL_ERROR',
            cause: error,
        });
    }
    return error;
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js"
/*!***************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js ***!
  \***************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isValidDomain: () => (/* binding */ isValidDomain)
/* harmony export */ });
/**
 * A simple test to determine if a hostname is a properly-formatted domain name
 *
 * A "valid domain" is defined here: https://url.spec.whatwg.org/#valid-domain
 *
 * Regex sourced from here:
 * https://www.oreilly.com/library/view/regular-expressions-cookbook/9781449327453/ch08s15.html
 */
function isValidDomain(hostname) {
    return (
    // Consider localhost valid as well since it's okay wrt Secure Contexts
    hostname === 'localhost' ||
        /^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i.test(hostname));
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/platformAuthenticatorIsAvailable.js"
/*!**********************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/platformAuthenticatorIsAvailable.js ***!
  \**********************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   platformAuthenticatorIsAvailable: () => (/* binding */ platformAuthenticatorIsAvailable)
/* harmony export */ });
/* harmony import */ var _browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");

/**
 * Determine whether the browser can communicate with a built-in authenticator, like
 * Touch ID, Android fingerprint scanner, or Windows Hello.
 *
 * This method will _not_ be able to tell you the name of the platform authenticator.
 */
function platformAuthenticatorIsAvailable() {
    if (!(0,_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_0__.browserSupportsWebAuthn)()) {
        return new Promise((resolve) => resolve(false));
    }
    return PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js"
/*!***************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js ***!
  \***************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   toAuthenticatorAttachment: () => (/* binding */ toAuthenticatorAttachment)
/* harmony export */ });
const attachments = ['cross-platform', 'platform'];
/**
 * If possible coerce a `string` value into a known `AuthenticatorAttachment`
 */
function toAuthenticatorAttachment(attachment) {
    if (!attachment) {
        return;
    }
    if (attachments.indexOf(attachment) < 0) {
        return;
    }
    return attachment;
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js"
/*!*********************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js ***!
  \*********************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   toPublicKeyCredentialDescriptor: () => (/* binding */ toPublicKeyCredentialDescriptor)
/* harmony export */ });
/* harmony import */ var _base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./base64URLStringToBuffer.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js");

function toPublicKeyCredentialDescriptor(descriptor) {
    const { id } = descriptor;
    return {
        ...descriptor,
        id: (0,_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_0__.base64URLStringToBuffer)(id),
        /**
         * `descriptor.transports` is an array of our `AuthenticatorTransportFuture` that includes newer
         * transports that TypeScript's DOM lib is ignorant of. Convince TS that our list of transports
         * are fine to pass to WebAuthn since browsers will recognize the new value.
         */
        transports: descriptor.transports,
    };
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js"
/*!**********************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js ***!
  \**********************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WebAuthnAbortService: () => (/* binding */ WebAuthnAbortService)
/* harmony export */ });
class BaseWebAuthnAbortService {
    constructor() {
        Object.defineProperty(this, "controller", {
            enumerable: true,
            configurable: true,
            writable: true,
            value: void 0
        });
    }
    createNewAbortSignal() {
        // Abort any existing calls to navigator.credentials.create() or navigator.credentials.get()
        if (this.controller) {
            const abortError = new Error('Cancelling existing WebAuthn API call for new one');
            abortError.name = 'AbortError';
            this.controller.abort(abortError);
        }
        const newController = new AbortController();
        this.controller = newController;
        return newController.signal;
    }
    cancelCeremony() {
        if (this.controller) {
            const abortError = new Error('Manually cancelling existing WebAuthn API call');
            abortError.name = 'AbortError';
            this.controller.abort(abortError);
            this.controller = undefined;
        }
    }
}
/**
 * A service singleton to help ensure that only a single WebAuthn ceremony is active at a time.
 *
 * Users of **@simplewebauthn/browser** shouldn't typically need to use this, but it can help e.g.
 * developers building projects that use client-side routing to better control the behavior of
 * their UX in response to router navigation events.
 */
const WebAuthnAbortService = new BaseWebAuthnAbortService();


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js"
/*!***************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js ***!
  \***************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WebAuthnError: () => (/* binding */ WebAuthnError)
/* harmony export */ });
/**
 * A custom Error used to return a more nuanced error detailing _why_ one of the eight documented
 * errors in the spec was raised after calling `navigator.credentials.create()` or
 * `navigator.credentials.get()`:
 *
 * - `AbortError`
 * - `ConstraintError`
 * - `InvalidStateError`
 * - `NotAllowedError`
 * - `NotSupportedError`
 * - `SecurityError`
 * - `TypeError`
 * - `UnknownError`
 *
 * Error messages were determined through investigation of the spec to determine under which
 * scenarios a given error would be raised.
 */
class WebAuthnError extends Error {
    constructor({ message, code, cause, name, }) {
        // @ts-ignore: help Rollup understand that `cause` is okay to set
        super(message, { cause });
        Object.defineProperty(this, "code", {
            enumerable: true,
            configurable: true,
            writable: true,
            value: void 0
        });
        this.name = name ?? cause.name;
        this.code = code;
    }
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/index.js"
/*!***********************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/index.js ***!
  \***********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WebAuthnAbortService: () => (/* reexport safe */ _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_7__.WebAuthnAbortService),
/* harmony export */   WebAuthnError: () => (/* reexport safe */ _helpers_webAuthnError_js__WEBPACK_IMPORTED_MODULE_8__.WebAuthnError),
/* harmony export */   _browserSupportsWebAuthnAutofillInternals: () => (/* reexport safe */ _helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_4__._browserSupportsWebAuthnAutofillInternals),
/* harmony export */   _browserSupportsWebAuthnInternals: () => (/* reexport safe */ _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__._browserSupportsWebAuthnInternals),
/* harmony export */   base64URLStringToBuffer: () => (/* reexport safe */ _helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_5__.base64URLStringToBuffer),
/* harmony export */   browserSupportsWebAuthn: () => (/* reexport safe */ _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__.browserSupportsWebAuthn),
/* harmony export */   browserSupportsWebAuthnAutofill: () => (/* reexport safe */ _helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_4__.browserSupportsWebAuthnAutofill),
/* harmony export */   bufferToBase64URLString: () => (/* reexport safe */ _helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_6__.bufferToBase64URLString),
/* harmony export */   platformAuthenticatorIsAvailable: () => (/* reexport safe */ _helpers_platformAuthenticatorIsAvailable_js__WEBPACK_IMPORTED_MODULE_3__.platformAuthenticatorIsAvailable),
/* harmony export */   startAuthentication: () => (/* reexport safe */ _methods_startAuthentication_js__WEBPACK_IMPORTED_MODULE_1__.startAuthentication),
/* harmony export */   startRegistration: () => (/* reexport safe */ _methods_startRegistration_js__WEBPACK_IMPORTED_MODULE_0__.startRegistration)
/* harmony export */ });
/* harmony import */ var _methods_startRegistration_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./methods/startRegistration.js */ "./node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js");
/* harmony import */ var _methods_startAuthentication_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./methods/startAuthentication.js */ "./node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js");
/* harmony import */ var _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./helpers/browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");
/* harmony import */ var _helpers_platformAuthenticatorIsAvailable_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./helpers/platformAuthenticatorIsAvailable.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/platformAuthenticatorIsAvailable.js");
/* harmony import */ var _helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./helpers/browserSupportsWebAuthnAutofill.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js");
/* harmony import */ var _helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./helpers/base64URLStringToBuffer.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js");
/* harmony import */ var _helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./helpers/bufferToBase64URLString.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js");
/* harmony import */ var _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./helpers/webAuthnAbortService.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js");
/* harmony import */ var _helpers_webAuthnError_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./helpers/webAuthnError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js");
/* harmony import */ var _types_index_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./types/index.js */ "./node_modules/@simplewebauthn/browser/esm/types/index.js");












/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js"
/*!*********************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js ***!
  \*********************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   startAuthentication: () => (/* binding */ startAuthentication)
/* harmony export */ });
/* harmony import */ var _helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../helpers/bufferToBase64URLString.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js");
/* harmony import */ var _helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../helpers/base64URLStringToBuffer.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js");
/* harmony import */ var _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../helpers/browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");
/* harmony import */ var _helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../helpers/browserSupportsWebAuthnAutofill.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js");
/* harmony import */ var _helpers_toPublicKeyCredentialDescriptor_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/toPublicKeyCredentialDescriptor.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js");
/* harmony import */ var _helpers_identifyAuthenticationError_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/identifyAuthenticationError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js");
/* harmony import */ var _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../helpers/webAuthnAbortService.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js");
/* harmony import */ var _helpers_toAuthenticatorAttachment_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../helpers/toAuthenticatorAttachment.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js");








/**
 * Begin authenticator "login" via WebAuthn assertion
 *
 * @param optionsJSON Output from **@simplewebauthn/server**'s `generateAuthenticationOptions()`
 * @param useBrowserAutofill (Optional) Initialize conditional UI to enable logging in via browser autofill prompts. Defaults to `false`.
 * @param verifyBrowserAutofillInput (Optional) Ensure a suitable `<input>` element is present when `useBrowserAutofill` is `true`. Defaults to `true`.
 */
async function startAuthentication(options) {
    // @ts-ignore: Intentionally check for old call structure to warn about improper API call
    if (!options.optionsJSON && options.challenge) {
        console.warn('startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.');
        // @ts-ignore: Reassign the options, passed in as a positional argument, to the expected variable
        options = { optionsJSON: options };
    }
    const { optionsJSON, useBrowserAutofill = false, verifyBrowserAutofillInput = true, } = options;
    if (!(0,_helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__.browserSupportsWebAuthn)()) {
        throw new Error('WebAuthn is not supported in this browser');
    }
    // We need to avoid passing empty array to avoid blocking retrieval
    // of public key
    let allowCredentials;
    if (optionsJSON.allowCredentials?.length !== 0) {
        allowCredentials = optionsJSON.allowCredentials?.map(_helpers_toPublicKeyCredentialDescriptor_js__WEBPACK_IMPORTED_MODULE_4__.toPublicKeyCredentialDescriptor);
    }
    // We need to convert some values to Uint8Arrays before passing the credentials to the navigator
    const publicKey = {
        ...optionsJSON,
        challenge: (0,_helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__.base64URLStringToBuffer)(optionsJSON.challenge),
        allowCredentials,
    };
    // Prepare options for `.get()`
    const getOptions = {};
    /**
     * Set up the page to prompt the user to select a credential for authentication via the browser's
     * input autofill mechanism.
     */
    if (useBrowserAutofill) {
        if (!(await (0,_helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_3__.browserSupportsWebAuthnAutofill)())) {
            throw Error('Browser does not support WebAuthn autofill');
        }
        // Check for an <input> with "webauthn" in its `autocomplete` attribute
        const eligibleInputs = document.querySelectorAll("input[autocomplete$='webauthn']");
        // WebAuthn autofill requires at least one valid input
        if (eligibleInputs.length < 1 && verifyBrowserAutofillInput) {
            throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');
        }
        // `CredentialMediationRequirement` doesn't know about "conditional" yet as of
        // typescript@4.6.3
        getOptions.mediation = 'conditional';
        // Conditional UI requires an empty allow list
        publicKey.allowCredentials = [];
    }
    // Finalize options
    getOptions.publicKey = publicKey;
    // Set up the ability to cancel this request if the user attempts another
    getOptions.signal = _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_6__.WebAuthnAbortService.createNewAbortSignal();
    // Wait for the user to complete assertion
    let credential;
    try {
        credential = (await navigator.credentials.get(getOptions));
    }
    catch (err) {
        throw (0,_helpers_identifyAuthenticationError_js__WEBPACK_IMPORTED_MODULE_5__.identifyAuthenticationError)({ error: err, options: getOptions });
    }
    if (!credential) {
        throw new Error('Authentication was not completed');
    }
    const { id, rawId, response, type } = credential;
    let userHandle = undefined;
    if (response.userHandle) {
        userHandle = (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.userHandle);
    }
    // Convert values to base64 to make it easier to send back to the server
    return {
        id,
        rawId: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(rawId),
        response: {
            authenticatorData: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.authenticatorData),
            clientDataJSON: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.clientDataJSON),
            signature: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.signature),
            userHandle,
        },
        type,
        clientExtensionResults: credential.getClientExtensionResults(),
        authenticatorAttachment: (0,_helpers_toAuthenticatorAttachment_js__WEBPACK_IMPORTED_MODULE_7__.toAuthenticatorAttachment)(credential.authenticatorAttachment),
    };
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js"
/*!*******************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js ***!
  \*******************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   startRegistration: () => (/* binding */ startRegistration)
/* harmony export */ });
/* harmony import */ var _helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../helpers/bufferToBase64URLString.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js");
/* harmony import */ var _helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../helpers/base64URLStringToBuffer.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js");
/* harmony import */ var _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../helpers/browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");
/* harmony import */ var _helpers_toPublicKeyCredentialDescriptor_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../helpers/toPublicKeyCredentialDescriptor.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js");
/* harmony import */ var _helpers_identifyRegistrationError_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/identifyRegistrationError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js");
/* harmony import */ var _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/webAuthnAbortService.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js");
/* harmony import */ var _helpers_toAuthenticatorAttachment_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../helpers/toAuthenticatorAttachment.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js");







/**
 * Begin authenticator "registration" via WebAuthn attestation
 *
 * @param optionsJSON Output from **@simplewebauthn/server**'s `generateRegistrationOptions()`
 * @param useAutoRegister (Optional) Try to silently create a passkey with the password manager that the user just signed in with. Defaults to `false`.
 */
async function startRegistration(options) {
    // @ts-ignore: Intentionally check for old call structure to warn about improper API call
    if (!options.optionsJSON && options.challenge) {
        console.warn('startRegistration() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.');
        // @ts-ignore: Reassign the options, passed in as a positional argument, to the expected variable
        options = { optionsJSON: options };
    }
    const { optionsJSON, useAutoRegister = false } = options;
    if (!(0,_helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__.browserSupportsWebAuthn)()) {
        throw new Error('WebAuthn is not supported in this browser');
    }
    // We need to convert some values to Uint8Arrays before passing the credentials to the navigator
    const publicKey = {
        ...optionsJSON,
        challenge: (0,_helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__.base64URLStringToBuffer)(optionsJSON.challenge),
        user: {
            ...optionsJSON.user,
            id: (0,_helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__.base64URLStringToBuffer)(optionsJSON.user.id),
        },
        excludeCredentials: optionsJSON.excludeCredentials?.map(_helpers_toPublicKeyCredentialDescriptor_js__WEBPACK_IMPORTED_MODULE_3__.toPublicKeyCredentialDescriptor),
    };
    // Prepare options for `.create()`
    const createOptions = {};
    /**
     * Try to use conditional create to register a passkey for the user with the password manager
     * the user just used to authenticate with. The user won't be shown any prominent UI by the
     * browser.
     */
    if (useAutoRegister) {
        // @ts-ignore: `mediation` doesn't yet exist on CredentialCreationOptions but it's possible as of Sept 2024
        createOptions.mediation = 'conditional';
    }
    // Finalize options
    createOptions.publicKey = publicKey;
    // Set up the ability to cancel this request if the user attempts another
    createOptions.signal = _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_5__.WebAuthnAbortService.createNewAbortSignal();
    // Wait for the user to complete attestation
    let credential;
    try {
        credential = (await navigator.credentials.create(createOptions));
    }
    catch (err) {
        throw (0,_helpers_identifyRegistrationError_js__WEBPACK_IMPORTED_MODULE_4__.identifyRegistrationError)({ error: err, options: createOptions });
    }
    if (!credential) {
        throw new Error('Registration was not completed');
    }
    const { id, rawId, response, type } = credential;
    // Continue to play it safe with `getTransports()` for now, even when L3 types say it's required
    let transports = undefined;
    if (typeof response.getTransports === 'function') {
        transports = response.getTransports();
    }
    // L3 says this is required, but browser and webview support are still not guaranteed.
    let responsePublicKeyAlgorithm = undefined;
    if (typeof response.getPublicKeyAlgorithm === 'function') {
        try {
            responsePublicKeyAlgorithm = response.getPublicKeyAlgorithm();
        }
        catch (error) {
            warnOnBrokenImplementation('getPublicKeyAlgorithm()', error);
        }
    }
    let responsePublicKey = undefined;
    if (typeof response.getPublicKey === 'function') {
        try {
            const _publicKey = response.getPublicKey();
            if (_publicKey !== null) {
                responsePublicKey = (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(_publicKey);
            }
        }
        catch (error) {
            warnOnBrokenImplementation('getPublicKey()', error);
        }
    }
    // L3 says this is required, but browser and webview support are still not guaranteed.
    let responseAuthenticatorData;
    if (typeof response.getAuthenticatorData === 'function') {
        try {
            responseAuthenticatorData = (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.getAuthenticatorData());
        }
        catch (error) {
            warnOnBrokenImplementation('getAuthenticatorData()', error);
        }
    }
    return {
        id,
        rawId: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(rawId),
        response: {
            attestationObject: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.attestationObject),
            clientDataJSON: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.clientDataJSON),
            transports,
            publicKeyAlgorithm: responsePublicKeyAlgorithm,
            publicKey: responsePublicKey,
            authenticatorData: responseAuthenticatorData,
        },
        type,
        clientExtensionResults: credential.getClientExtensionResults(),
        authenticatorAttachment: (0,_helpers_toAuthenticatorAttachment_js__WEBPACK_IMPORTED_MODULE_6__.toAuthenticatorAttachment)(credential.authenticatorAttachment),
    };
}
/**
 * Visibly warn when we detect an issue related to a passkey provider intercepting WebAuthn API
 * calls
 */
function warnOnBrokenImplementation(methodName, cause) {
    console.warn(`The browser extension that intercepted this WebAuthn API call incorrectly implemented ${methodName}. You should report this error to them.\n`, cause);
}


/***/ },

/***/ "./node_modules/@simplewebauthn/browser/esm/types/index.js"
/*!*****************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/types/index.js ***!
  \*****************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);



/***/ },

/***/ "./node_modules/underscore/modules/_baseCreate.js"
/*!********************************************************!*\
  !*** ./node_modules/underscore/modules/_baseCreate.js ***!
  \********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ baseCreate)
/* harmony export */ });
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/underscore/modules/isObject.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");



// Create a naked function reference for surrogate-prototype-swapping.
function ctor() {
  return function(){};
}

// An internal function for creating a new object that inherits from another.
function baseCreate(prototype) {
  if (!(0,_isObject_js__WEBPACK_IMPORTED_MODULE_0__["default"])(prototype)) return {};
  if (_setup_js__WEBPACK_IMPORTED_MODULE_1__.nativeCreate) return (0,_setup_js__WEBPACK_IMPORTED_MODULE_1__.nativeCreate)(prototype);
  var Ctor = ctor();
  Ctor.prototype = prototype;
  var result = new Ctor;
  Ctor.prototype = null;
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/_baseIteratee.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/_baseIteratee.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ baseIteratee)
/* harmony export */ });
/* harmony import */ var _identity_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./identity.js */ "./node_modules/underscore/modules/identity.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/underscore/modules/isObject.js");
/* harmony import */ var _isArray_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isArray.js */ "./node_modules/underscore/modules/isArray.js");
/* harmony import */ var _matcher_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./matcher.js */ "./node_modules/underscore/modules/matcher.js");
/* harmony import */ var _property_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./property.js */ "./node_modules/underscore/modules/property.js");
/* harmony import */ var _optimizeCb_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./_optimizeCb.js */ "./node_modules/underscore/modules/_optimizeCb.js");








// An internal function to generate callbacks that can be applied to each
// element in a collection, returning the desired result — either `_.identity`,
// an arbitrary callback, a property matcher, or a property accessor.
function baseIteratee(value, context, argCount) {
  if (value == null) return _identity_js__WEBPACK_IMPORTED_MODULE_0__["default"];
  if ((0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(value)) return (0,_optimizeCb_js__WEBPACK_IMPORTED_MODULE_6__["default"])(value, context, argCount);
  if ((0,_isObject_js__WEBPACK_IMPORTED_MODULE_2__["default"])(value) && !(0,_isArray_js__WEBPACK_IMPORTED_MODULE_3__["default"])(value)) return (0,_matcher_js__WEBPACK_IMPORTED_MODULE_4__["default"])(value);
  return (0,_property_js__WEBPACK_IMPORTED_MODULE_5__["default"])(value);
}


/***/ },

/***/ "./node_modules/underscore/modules/_cb.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/_cb.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ cb)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");
/* harmony import */ var _baseIteratee_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_baseIteratee.js */ "./node_modules/underscore/modules/_baseIteratee.js");
/* harmony import */ var _iteratee_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./iteratee.js */ "./node_modules/underscore/modules/iteratee.js");




// The function we call internally to generate a callback. It invokes
// `_.iteratee` if overridden, otherwise `baseIteratee`.
function cb(value, context, argCount) {
  if (_underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].iteratee !== _iteratee_js__WEBPACK_IMPORTED_MODULE_2__["default"]) return _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].iteratee(value, context);
  return (0,_baseIteratee_js__WEBPACK_IMPORTED_MODULE_1__["default"])(value, context, argCount);
}


/***/ },

/***/ "./node_modules/underscore/modules/_chainResult.js"
/*!*********************************************************!*\
  !*** ./node_modules/underscore/modules/_chainResult.js ***!
  \*********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ chainResult)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");


// Helper function to continue chaining intermediate results.
function chainResult(instance, obj) {
  return instance._chain ? (0,_underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj).chain() : obj;
}


/***/ },

/***/ "./node_modules/underscore/modules/_collectNonEnumProps.js"
/*!*****************************************************************!*\
  !*** ./node_modules/underscore/modules/_collectNonEnumProps.js ***!
  \*****************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ collectNonEnumProps)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_has.js */ "./node_modules/underscore/modules/_has.js");




// Internal helper to create a simple lookup structure.
// `collectNonEnumProps` used to depend on `_.contains`, but this led to
// circular imports. `emulatedSet` is a one-off solution that only works for
// arrays of strings.
function emulatedSet(keys) {
  var hash = {};
  for (var l = keys.length, i = 0; i < l; ++i) hash[keys[i]] = true;
  return {
    contains: function(key) { return hash[key] === true; },
    push: function(key) {
      hash[key] = true;
      return keys.push(key);
    }
  };
}

// Internal helper. Checks `keys` for the presence of keys in IE < 9 that won't
// be iterated by `for key in ...` and thus missed. Extends `keys` in place if
// needed.
function collectNonEnumProps(obj, keys) {
  keys = emulatedSet(keys);
  var nonEnumIdx = _setup_js__WEBPACK_IMPORTED_MODULE_0__.nonEnumerableProps.length;
  var constructor = obj.constructor;
  var proto = ((0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(constructor) && constructor.prototype) || _setup_js__WEBPACK_IMPORTED_MODULE_0__.ObjProto;

  // Constructor is a special case.
  var prop = 'constructor';
  if ((0,_has_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj, prop) && !keys.contains(prop)) keys.push(prop);

  while (nonEnumIdx--) {
    prop = _setup_js__WEBPACK_IMPORTED_MODULE_0__.nonEnumerableProps[nonEnumIdx];
    if (prop in obj && obj[prop] !== proto[prop] && !keys.contains(prop)) {
      keys.push(prop);
    }
  }
}


/***/ },

/***/ "./node_modules/underscore/modules/_createAssigner.js"
/*!************************************************************!*\
  !*** ./node_modules/underscore/modules/_createAssigner.js ***!
  \************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ createAssigner)
/* harmony export */ });
// An internal function for creating assigner functions.
function createAssigner(keysFunc, defaults) {
  return function(obj) {
    var length = arguments.length;
    if (defaults) obj = Object(obj);
    if (length < 2 || obj == null) return obj;
    for (var index = 1; index < length; index++) {
      var source = arguments[index],
          keys = keysFunc(source),
          l = keys.length;
      for (var i = 0; i < l; i++) {
        var key = keys[i];
        if (!defaults || obj[key] === void 0) obj[key] = source[key];
      }
    }
    return obj;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_createEscaper.js"
/*!***********************************************************!*\
  !*** ./node_modules/underscore/modules/_createEscaper.js ***!
  \***********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ createEscaper)
/* harmony export */ });
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");


// Internal helper to generate functions for escaping and unescaping strings
// to/from HTML interpolation.
function createEscaper(map) {
  var escaper = function(match) {
    return map[match];
  };
  // Regexes for identifying a key that needs to be escaped.
  var source = '(?:' + (0,_keys_js__WEBPACK_IMPORTED_MODULE_0__["default"])(map).join('|') + ')';
  var testRegexp = RegExp(source);
  var replaceRegexp = RegExp(source, 'g');
  return function(string) {
    string = string == null ? '' : '' + string;
    return testRegexp.test(string) ? string.replace(replaceRegexp, escaper) : string;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_createIndexFinder.js"
/*!***************************************************************!*\
  !*** ./node_modules/underscore/modules/_createIndexFinder.js ***!
  \***************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ createIndexFinder)
/* harmony export */ });
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _isNaN_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./isNaN.js */ "./node_modules/underscore/modules/isNaN.js");




// Internal function to generate the `_.indexOf` and `_.lastIndexOf` functions.
function createIndexFinder(dir, predicateFind, sortedIndex) {
  return function(array, item, idx) {
    var i = 0, length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(array);
    if (typeof idx == 'number') {
      if (dir > 0) {
        i = idx >= 0 ? idx : Math.max(idx + length, i);
      } else {
        length = idx >= 0 ? Math.min(idx + 1, length) : idx + length + 1;
      }
    } else if (sortedIndex && idx && length) {
      idx = sortedIndex(array, item);
      return array[idx] === item ? idx : -1;
    }
    if (item !== item) {
      idx = predicateFind(_setup_js__WEBPACK_IMPORTED_MODULE_1__.slice.call(array, i, length), _isNaN_js__WEBPACK_IMPORTED_MODULE_2__["default"]);
      return idx >= 0 ? idx + i : -1;
    }
    for (idx = dir > 0 ? i : length - 1; idx >= 0 && idx < length; idx += dir) {
      if (array[idx] === item) return idx;
    }
    return -1;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_createPredicateIndexFinder.js"
/*!************************************************************************!*\
  !*** ./node_modules/underscore/modules/_createPredicateIndexFinder.js ***!
  \************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ createPredicateIndexFinder)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");



// Internal function to generate `_.findIndex` and `_.findLastIndex`.
function createPredicateIndexFinder(dir) {
  return function(array, predicate, context) {
    predicate = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(predicate, context);
    var length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_1__["default"])(array);
    var index = dir > 0 ? 0 : length - 1;
    for (; index >= 0 && index < length; index += dir) {
      if (predicate(array[index], index, array)) return index;
    }
    return -1;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_createReduce.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/_createReduce.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ createReduce)
/* harmony export */ });
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");
/* harmony import */ var _optimizeCb_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_optimizeCb.js */ "./node_modules/underscore/modules/_optimizeCb.js");




// Internal helper to create a reducing function, iterating left or right.
function createReduce(dir) {
  // Wrap code that reassigns argument variables in a separate function than
  // the one that accesses `arguments.length` to avoid a perf hit. (#1991)
  var reducer = function(obj, iteratee, memo, initial) {
    var _keys = !(0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj) && (0,_keys_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj),
        length = (_keys || obj).length,
        index = dir > 0 ? 0 : length - 1;
    if (!initial) {
      memo = obj[_keys ? _keys[index] : index];
      index += dir;
    }
    for (; index >= 0 && index < length; index += dir) {
      var currentKey = _keys ? _keys[index] : index;
      memo = iteratee(memo, obj[currentKey], currentKey, obj);
    }
    return memo;
  };

  return function(obj, iteratee, memo, context) {
    var initial = arguments.length >= 3;
    return reducer(obj, (0,_optimizeCb_js__WEBPACK_IMPORTED_MODULE_2__["default"])(iteratee, context, 4), memo, initial);
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_createSizePropertyCheck.js"
/*!*********************************************************************!*\
  !*** ./node_modules/underscore/modules/_createSizePropertyCheck.js ***!
  \*********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ createSizePropertyCheck)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");


// Common internal logic for `isArrayLike` and `isBufferLike`.
function createSizePropertyCheck(getSizeProperty) {
  return function(collection) {
    var sizeProperty = getSizeProperty(collection);
    return typeof sizeProperty == 'number' && sizeProperty >= 0 && sizeProperty <= _setup_js__WEBPACK_IMPORTED_MODULE_0__.MAX_ARRAY_INDEX;
  }
}


/***/ },

/***/ "./node_modules/underscore/modules/_deepGet.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/_deepGet.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ deepGet)
/* harmony export */ });
// Internal function to obtain a nested property in `obj` along `path`.
function deepGet(obj, path) {
  var length = path.length;
  for (var i = 0; i < length; i++) {
    if (obj == null) return void 0;
    obj = obj[path[i]];
  }
  return length ? obj : void 0;
}


/***/ },

/***/ "./node_modules/underscore/modules/_escapeMap.js"
/*!*******************************************************!*\
  !*** ./node_modules/underscore/modules/_escapeMap.js ***!
  \*******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
// Internal list of HTML entities for escaping.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#x27;',
  '`': '&#x60;'
});


/***/ },

/***/ "./node_modules/underscore/modules/_executeBound.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/_executeBound.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ executeBound)
/* harmony export */ });
/* harmony import */ var _baseCreate_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_baseCreate.js */ "./node_modules/underscore/modules/_baseCreate.js");
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/underscore/modules/isObject.js");



// Internal function to execute `sourceFunc` bound to `context` with optional
// `args`. Determines whether to execute a function as a constructor or as a
// normal function.
function executeBound(sourceFunc, boundFunc, context, callingContext, args) {
  if (!(callingContext instanceof boundFunc)) return sourceFunc.apply(context, args);
  var self = (0,_baseCreate_js__WEBPACK_IMPORTED_MODULE_0__["default"])(sourceFunc.prototype);
  var result = sourceFunc.apply(self, args);
  if ((0,_isObject_js__WEBPACK_IMPORTED_MODULE_1__["default"])(result)) return result;
  return self;
}


/***/ },

/***/ "./node_modules/underscore/modules/_flatten.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/_flatten.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ flatten)
/* harmony export */ });
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _isArray_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./isArray.js */ "./node_modules/underscore/modules/isArray.js");
/* harmony import */ var _isArguments_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isArguments.js */ "./node_modules/underscore/modules/isArguments.js");





// Internal implementation of a recursive `flatten` function.
function flatten(input, depth, strict) {
  if (!depth && depth !== 0) depth = Infinity;
  var output = [], idx = 0, i = 0, length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(input) || 0, stack = [];
  while (true) {
    if (i >= length) {
      if (!stack.length) break;
      var frame = stack.pop();
      i = frame.i;
      input = frame.v;
      length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(input);
      continue;
    }
    var value = input[i++];
    if (stack.length >= depth) {
      output[idx++] = value;
    } else if ((0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__["default"])(value) && ((0,_isArray_js__WEBPACK_IMPORTED_MODULE_2__["default"])(value) || (0,_isArguments_js__WEBPACK_IMPORTED_MODULE_3__["default"])(value))) {
      // Flatten current level of array or arguments object.
      stack.push({i: i, v: input});
      i = 0;
      input = value;
      length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(input);
    } else if (!strict) {
      output[idx++] = value;
    }
  }
  return output;
}


/***/ },

/***/ "./node_modules/underscore/modules/_getByteLength.js"
/*!***********************************************************!*\
  !*** ./node_modules/underscore/modules/_getByteLength.js ***!
  \***********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _shallowProperty_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_shallowProperty.js */ "./node_modules/underscore/modules/_shallowProperty.js");


// Internal helper to obtain the `byteLength` property of an object.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_shallowProperty_js__WEBPACK_IMPORTED_MODULE_0__["default"])('byteLength'));


/***/ },

/***/ "./node_modules/underscore/modules/_getLength.js"
/*!*******************************************************!*\
  !*** ./node_modules/underscore/modules/_getLength.js ***!
  \*******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _shallowProperty_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_shallowProperty.js */ "./node_modules/underscore/modules/_shallowProperty.js");


// Internal helper to obtain the `length` property of an object.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_shallowProperty_js__WEBPACK_IMPORTED_MODULE_0__["default"])('length'));


/***/ },

/***/ "./node_modules/underscore/modules/_group.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/_group.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ group)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _each_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./each.js */ "./node_modules/underscore/modules/each.js");



// An internal function used for aggregate "group by" operations.
function group(behavior, partition) {
  return function(obj, iteratee, context) {
    var result = partition ? [[], []] : {};
    iteratee = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(iteratee, context);
    (0,_each_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj, function(value, index) {
      var key = iteratee(value, index, obj);
      behavior(result, value, key);
    });
    return result;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_has.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/_has.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ has)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");


// Internal function to check whether `key` is an own property name of `obj`.
function has(obj, key) {
  return obj != null && _setup_js__WEBPACK_IMPORTED_MODULE_0__.hasOwnProperty.call(obj, key);
}


/***/ },

/***/ "./node_modules/underscore/modules/_hasObjectTag.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/_hasObjectTag.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Object'));


/***/ },

/***/ "./node_modules/underscore/modules/_isArrayLike.js"
/*!*********************************************************!*\
  !*** ./node_modules/underscore/modules/_isArrayLike.js ***!
  \*********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createSizePropertyCheck_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createSizePropertyCheck.js */ "./node_modules/underscore/modules/_createSizePropertyCheck.js");
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");



// Internal helper for collection methods to determine whether a collection
// should be iterated as an array or as an object.
// Related: https://people.mozilla.org/~jorendorff/es6-draft.html#sec-tolength
// Avoids a very nasty iOS 8 JIT bug on ARM-64. #2094
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createSizePropertyCheck_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_getLength_js__WEBPACK_IMPORTED_MODULE_1__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/_isBufferLike.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/_isBufferLike.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createSizePropertyCheck_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createSizePropertyCheck.js */ "./node_modules/underscore/modules/_createSizePropertyCheck.js");
/* harmony import */ var _getByteLength_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_getByteLength.js */ "./node_modules/underscore/modules/_getByteLength.js");



// Internal helper to determine whether we should spend extensive checks against
// `ArrayBuffer` et al.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createSizePropertyCheck_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_getByteLength_js__WEBPACK_IMPORTED_MODULE_1__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/_keyInObj.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/_keyInObj.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ keyInObj)
/* harmony export */ });
// Internal `_.pick` helper function to determine whether `key` is an enumerable
// property name of `obj`.
function keyInObj(value, key, obj) {
  return key in obj;
}


/***/ },

/***/ "./node_modules/underscore/modules/_methodFingerprint.js"
/*!***************************************************************!*\
  !*** ./node_modules/underscore/modules/_methodFingerprint.js ***!
  \***************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ie11fingerprint: () => (/* binding */ ie11fingerprint),
/* harmony export */   mapMethods: () => (/* binding */ mapMethods),
/* harmony export */   setMethods: () => (/* binding */ setMethods),
/* harmony export */   weakMapMethods: () => (/* binding */ weakMapMethods)
/* harmony export */ });
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _allKeys_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./allKeys.js */ "./node_modules/underscore/modules/allKeys.js");




// Since the regular `Object.prototype.toString` type tests don't work for
// some types in IE 11, we use a fingerprinting heuristic instead, based
// on the methods. It's not great, but it's the best we got.
// The fingerprint method lists are defined below.
function ie11fingerprint(methods) {
  var length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(methods);
  return function(obj) {
    if (obj == null) return false;
    // `Map`, `WeakMap` and `Set` have no enumerable keys.
    var keys = (0,_allKeys_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj);
    if ((0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(keys)) return false;
    for (var i = 0; i < length; i++) {
      if (!(0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj[methods[i]])) return false;
    }
    // If we are testing against `WeakMap`, we need to ensure that
    // `obj` doesn't have a `forEach` method in order to distinguish
    // it from a regular `Map`.
    return methods !== weakMapMethods || !(0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj[forEachName]);
  };
}

// In the interest of compact minification, we write
// each string in the fingerprints only once.
var forEachName = 'forEach',
    hasName = 'has',
    commonInit = ['clear', 'delete'],
    mapTail = ['get', hasName, 'set'];

// `Map`, `WeakMap` and `Set` each have slightly different
// combinations of the above sublists.
var mapMethods = commonInit.concat(forEachName, mapTail),
    weakMapMethods = commonInit.concat(mapTail),
    setMethods = ['add'].concat(commonInit, forEachName, hasName);


/***/ },

/***/ "./node_modules/underscore/modules/_optimizeCb.js"
/*!********************************************************!*\
  !*** ./node_modules/underscore/modules/_optimizeCb.js ***!
  \********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ optimizeCb)
/* harmony export */ });
// Internal function that returns an efficient (for current engines) version
// of the passed-in callback, to be repeatedly applied in other Underscore
// functions.
function optimizeCb(func, context, argCount) {
  if (context === void 0) return func;
  switch (argCount == null ? 3 : argCount) {
    case 1: return function(value) {
      return func.call(context, value);
    };
    // The 2-argument case is omitted because we’re not using it.
    case 3: return function(value, index, collection) {
      return func.call(context, value, index, collection);
    };
    case 4: return function(accumulator, value, index, collection) {
      return func.call(context, accumulator, value, index, collection);
    };
  }
  return function() {
    return func.apply(context, arguments);
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_setup.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/_setup.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ArrayProto: () => (/* binding */ ArrayProto),
/* harmony export */   MAX_ARRAY_INDEX: () => (/* binding */ MAX_ARRAY_INDEX),
/* harmony export */   ObjProto: () => (/* binding */ ObjProto),
/* harmony export */   SymbolProto: () => (/* binding */ SymbolProto),
/* harmony export */   VERSION: () => (/* binding */ VERSION),
/* harmony export */   _isFinite: () => (/* binding */ _isFinite),
/* harmony export */   _isNaN: () => (/* binding */ _isNaN),
/* harmony export */   hasEnumBug: () => (/* binding */ hasEnumBug),
/* harmony export */   hasOwnProperty: () => (/* binding */ hasOwnProperty),
/* harmony export */   nativeCreate: () => (/* binding */ nativeCreate),
/* harmony export */   nativeIsArray: () => (/* binding */ nativeIsArray),
/* harmony export */   nativeIsView: () => (/* binding */ nativeIsView),
/* harmony export */   nativeKeys: () => (/* binding */ nativeKeys),
/* harmony export */   nonEnumerableProps: () => (/* binding */ nonEnumerableProps),
/* harmony export */   push: () => (/* binding */ push),
/* harmony export */   root: () => (/* binding */ root),
/* harmony export */   slice: () => (/* binding */ slice),
/* harmony export */   supportsArrayBuffer: () => (/* binding */ supportsArrayBuffer),
/* harmony export */   supportsDataView: () => (/* binding */ supportsDataView),
/* harmony export */   toString: () => (/* binding */ toString)
/* harmony export */ });
// Current version.
var VERSION = '1.13.8';

// Establish the root object, `window` (`self`) in the browser, `global`
// on the server, or `this` in some virtual machines. We use `self`
// instead of `window` for `WebWorker` support.
var root = (typeof self == 'object' && self.self === self && self) ||
          (typeof globalThis == 'object' && globalThis.global === globalThis && globalThis) ||
          Function('return this')() ||
          {};

// Save bytes in the minified (but not gzipped) version:
var ArrayProto = Array.prototype, ObjProto = Object.prototype;
var SymbolProto = typeof Symbol !== 'undefined' ? Symbol.prototype : null;

// Create quick reference variables for speed access to core prototypes.
var push = ArrayProto.push,
    slice = ArrayProto.slice,
    toString = ObjProto.toString,
    hasOwnProperty = ObjProto.hasOwnProperty;

// Modern feature detection.
var supportsArrayBuffer = typeof ArrayBuffer !== 'undefined',
    supportsDataView = typeof DataView !== 'undefined';

// All **ECMAScript 5+** native function implementations that we hope to use
// are declared here.
var nativeIsArray = Array.isArray,
    nativeKeys = Object.keys,
    nativeCreate = Object.create,
    nativeIsView = supportsArrayBuffer && ArrayBuffer.isView;

// Create references to these builtin functions because we override them.
var _isNaN = isNaN,
    _isFinite = isFinite;

// Keys in IE < 9 that won't be iterated by `for key in ...` and thus missed.
var hasEnumBug = !{toString: null}.propertyIsEnumerable('toString');
var nonEnumerableProps = ['valueOf', 'isPrototypeOf', 'toString',
  'propertyIsEnumerable', 'hasOwnProperty', 'toLocaleString'];

// The largest integer that can be represented exactly.
var MAX_ARRAY_INDEX = Math.pow(2, 53) - 1;


/***/ },

/***/ "./node_modules/underscore/modules/_shallowProperty.js"
/*!*************************************************************!*\
  !*** ./node_modules/underscore/modules/_shallowProperty.js ***!
  \*************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ shallowProperty)
/* harmony export */ });
// Internal helper to generate a function to obtain property `key` from `obj`.
function shallowProperty(key) {
  return function(obj) {
    return obj == null ? void 0 : obj[key];
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_stringTagBug.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/_stringTagBug.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   hasDataViewBug: () => (/* binding */ hasDataViewBug),
/* harmony export */   isIE11: () => (/* binding */ isIE11)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _hasObjectTag_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_hasObjectTag.js */ "./node_modules/underscore/modules/_hasObjectTag.js");



// In IE 10 - Edge 13, `DataView` has string tag `'[object Object]'`.
// In IE 11, the most common among them, this problem also applies to
// `Map`, `WeakMap` and `Set`.
// Also, there are cases where an application can override the native
// `DataView` object, in cases like that we can't use the constructor
// safely and should just rely on alternate `DataView` checks
var hasDataViewBug = (
      _setup_js__WEBPACK_IMPORTED_MODULE_0__.supportsDataView && (!/\[native code\]/.test(String(DataView)) || (0,_hasObjectTag_js__WEBPACK_IMPORTED_MODULE_1__["default"])(new DataView(new ArrayBuffer(8))))
    ),
    isIE11 = (typeof Map !== 'undefined' && (0,_hasObjectTag_js__WEBPACK_IMPORTED_MODULE_1__["default"])(new Map));


/***/ },

/***/ "./node_modules/underscore/modules/_tagTester.js"
/*!*******************************************************!*\
  !*** ./node_modules/underscore/modules/_tagTester.js ***!
  \*******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ tagTester)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");


// Internal function for creating a `toString`-based type tester.
function tagTester(name) {
  var tag = '[object ' + name + ']';
  return function(obj) {
    return _setup_js__WEBPACK_IMPORTED_MODULE_0__.toString.call(obj) === tag;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/_toBufferView.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/_toBufferView.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toBufferView)
/* harmony export */ });
/* harmony import */ var _getByteLength_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_getByteLength.js */ "./node_modules/underscore/modules/_getByteLength.js");


// Internal function to wrap or shallow-copy an ArrayBuffer,
// typed array or DataView to a new view, reusing the buffer.
function toBufferView(bufferSource) {
  return new Uint8Array(
    bufferSource.buffer || bufferSource,
    bufferSource.byteOffset || 0,
    (0,_getByteLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(bufferSource)
  );
}


/***/ },

/***/ "./node_modules/underscore/modules/_toPath.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/_toPath.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPath)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");
/* harmony import */ var _toPath_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./toPath.js */ "./node_modules/underscore/modules/toPath.js");



// Internal wrapper for `_.toPath` to enable minification.
// Similar to `cb` for `_.iteratee`.
function toPath(path) {
  return _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].toPath(path);
}


/***/ },

/***/ "./node_modules/underscore/modules/_unescapeMap.js"
/*!*********************************************************!*\
  !*** ./node_modules/underscore/modules/_unescapeMap.js ***!
  \*********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _invert_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./invert.js */ "./node_modules/underscore/modules/invert.js");
/* harmony import */ var _escapeMap_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_escapeMap.js */ "./node_modules/underscore/modules/_escapeMap.js");



// Internal list of HTML entities for unescaping.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_invert_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_escapeMap_js__WEBPACK_IMPORTED_MODULE_1__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/after.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/after.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ after)
/* harmony export */ });
// Returns a function that will only be executed on and after the Nth call.
function after(times, func) {
  return function() {
    if (--times < 1) {
      return func.apply(this, arguments);
    }
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/allKeys.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/allKeys.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ allKeys)
/* harmony export */ });
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/underscore/modules/isObject.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _collectNonEnumProps_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_collectNonEnumProps.js */ "./node_modules/underscore/modules/_collectNonEnumProps.js");




// Retrieve all the enumerable property names of an object.
function allKeys(obj) {
  if (!(0,_isObject_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj)) return [];
  var keys = [];
  for (var key in obj) keys.push(key);
  // Ahem, IE < 9.
  if (_setup_js__WEBPACK_IMPORTED_MODULE_1__.hasEnumBug) (0,_collectNonEnumProps_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj, keys);
  return keys;
}


/***/ },

/***/ "./node_modules/underscore/modules/before.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/before.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ before)
/* harmony export */ });
// Returns a function that will only be executed up to (but not including) the
// Nth call.
function before(times, func) {
  var memo;
  return function() {
    if (--times > 0) {
      memo = func.apply(this, arguments);
    }
    if (times <= 1) func = null;
    return memo;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/bind.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/bind.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _executeBound_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_executeBound.js */ "./node_modules/underscore/modules/_executeBound.js");




// Create a function bound to a given object (assigning `this`, and arguments,
// optionally).
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(func, context, args) {
  if (!(0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(func)) throw new TypeError('Bind must be called on a function');
  var bound = (0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(callArgs) {
    return (0,_executeBound_js__WEBPACK_IMPORTED_MODULE_2__["default"])(func, bound, context, this, args.concat(callArgs));
  });
  return bound;
}));


/***/ },

/***/ "./node_modules/underscore/modules/bindAll.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/bindAll.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _flatten_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_flatten.js */ "./node_modules/underscore/modules/_flatten.js");
/* harmony import */ var _bind_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./bind.js */ "./node_modules/underscore/modules/bind.js");




// Bind a number of an object's methods to that object. Remaining arguments
// are the method names to be bound. Useful for ensuring that all callbacks
// defined on an object belong to it.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(obj, keys) {
  keys = (0,_flatten_js__WEBPACK_IMPORTED_MODULE_1__["default"])(keys, false, false);
  var index = keys.length;
  if (index < 1) throw new Error('bindAll must be passed function names');
  while (index--) {
    var key = keys[index];
    obj[key] = (0,_bind_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj[key], obj);
  }
  return obj;
}));


/***/ },

/***/ "./node_modules/underscore/modules/chain.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/chain.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ chain)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");


// Start chaining a wrapped Underscore object.
function chain(obj) {
  var instance = (0,_underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj);
  instance._chain = true;
  return instance;
}


/***/ },

/***/ "./node_modules/underscore/modules/chunk.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/chunk.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ chunk)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");


// Chunk a single array into multiple arrays, each containing `count` or fewer
// items.
function chunk(array, count) {
  if (count == null || count < 1) return [];
  var result = [];
  var i = 0, length = array.length;
  while (i < length) {
    result.push(_setup_js__WEBPACK_IMPORTED_MODULE_0__.slice.call(array, i, i += count));
  }
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/clone.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/clone.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ clone)
/* harmony export */ });
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/underscore/modules/isObject.js");
/* harmony import */ var _isArray_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isArray.js */ "./node_modules/underscore/modules/isArray.js");
/* harmony import */ var _extend_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./extend.js */ "./node_modules/underscore/modules/extend.js");




// Create a (shallow-cloned) duplicate of an object.
function clone(obj) {
  if (!(0,_isObject_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj)) return obj;
  return (0,_isArray_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj) ? obj.slice() : (0,_extend_js__WEBPACK_IMPORTED_MODULE_2__["default"])({}, obj);
}


/***/ },

/***/ "./node_modules/underscore/modules/compact.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/compact.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ compact)
/* harmony export */ });
/* harmony import */ var _filter_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./filter.js */ "./node_modules/underscore/modules/filter.js");


// Trim out all falsy values from an array.
function compact(array) {
  return (0,_filter_js__WEBPACK_IMPORTED_MODULE_0__["default"])(array, Boolean);
}


/***/ },

/***/ "./node_modules/underscore/modules/compose.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/compose.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ compose)
/* harmony export */ });
// Returns a function that is the composition of a list of functions, each
// consuming the return value of the function that follows.
function compose() {
  var args = arguments;
  var start = args.length - 1;
  return function() {
    var i = start;
    var result = args[start].apply(this, arguments);
    while (i--) result = args[i].call(this, result);
    return result;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/constant.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/constant.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ constant)
/* harmony export */ });
// Predicate-generating function. Often useful outside of Underscore.
function constant(value) {
  return function() {
    return value;
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/contains.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/contains.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ contains)
/* harmony export */ });
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _values_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./values.js */ "./node_modules/underscore/modules/values.js");
/* harmony import */ var _indexOf_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./indexOf.js */ "./node_modules/underscore/modules/indexOf.js");




// Determine if the array or object contains a given item (using `===`).
function contains(obj, item, fromIndex, guard) {
  if (!(0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj)) obj = (0,_values_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj);
  if (typeof fromIndex != 'number' || guard) fromIndex = 0;
  return (0,_indexOf_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj, item, fromIndex) >= 0;
}


/***/ },

/***/ "./node_modules/underscore/modules/countBy.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/countBy.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _group_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_group.js */ "./node_modules/underscore/modules/_group.js");
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_has.js */ "./node_modules/underscore/modules/_has.js");



// Counts instances of an object that group by a certain criterion. Pass
// either a string attribute to count by, or a function that returns the
// criterion.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_group_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(result, value, key) {
  if ((0,_has_js__WEBPACK_IMPORTED_MODULE_1__["default"])(result, key)) result[key]++; else result[key] = 1;
}));


/***/ },

/***/ "./node_modules/underscore/modules/create.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/create.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ create)
/* harmony export */ });
/* harmony import */ var _baseCreate_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_baseCreate.js */ "./node_modules/underscore/modules/_baseCreate.js");
/* harmony import */ var _extendOwn_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./extendOwn.js */ "./node_modules/underscore/modules/extendOwn.js");



// Creates an object that inherits from the given prototype object.
// If additional properties are provided then they will be added to the
// created object.
function create(prototype, props) {
  var result = (0,_baseCreate_js__WEBPACK_IMPORTED_MODULE_0__["default"])(prototype);
  if (props) (0,_extendOwn_js__WEBPACK_IMPORTED_MODULE_1__["default"])(result, props);
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/debounce.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/debounce.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ debounce)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _now_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./now.js */ "./node_modules/underscore/modules/now.js");



// When a sequence of calls of the returned function ends, the argument
// function is triggered. The end of a sequence is defined by the `wait`
// parameter. If `immediate` is passed, the argument function will be
// triggered at the beginning of the sequence instead of at the end.
function debounce(func, wait, immediate) {
  var timeout, previous, args, result, context;

  var later = function() {
    var passed = (0,_now_js__WEBPACK_IMPORTED_MODULE_1__["default"])() - previous;
    if (wait > passed) {
      timeout = setTimeout(later, wait - passed);
    } else {
      timeout = null;
      if (!immediate) result = func.apply(context, args);
      // This check is needed because `func` can recursively invoke `debounced`.
      if (!timeout) args = context = null;
    }
  };

  var debounced = (0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(_args) {
    context = this;
    args = _args;
    previous = (0,_now_js__WEBPACK_IMPORTED_MODULE_1__["default"])();
    if (!timeout) {
      timeout = setTimeout(later, wait);
      if (immediate) result = func.apply(context, args);
    }
    return result;
  });

  debounced.cancel = function() {
    clearTimeout(timeout);
    timeout = args = context = null;
  };

  return debounced;
}


/***/ },

/***/ "./node_modules/underscore/modules/defaults.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/defaults.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createAssigner_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createAssigner.js */ "./node_modules/underscore/modules/_createAssigner.js");
/* harmony import */ var _allKeys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./allKeys.js */ "./node_modules/underscore/modules/allKeys.js");



// Fill in a given object with default properties.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createAssigner_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_allKeys_js__WEBPACK_IMPORTED_MODULE_1__["default"], true));


/***/ },

/***/ "./node_modules/underscore/modules/defer.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/defer.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _partial_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./partial.js */ "./node_modules/underscore/modules/partial.js");
/* harmony import */ var _delay_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./delay.js */ "./node_modules/underscore/modules/delay.js");
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");




// Defers a function, scheduling it to run after the current call stack has
// cleared.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_partial_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_delay_js__WEBPACK_IMPORTED_MODULE_1__["default"], _underscore_js__WEBPACK_IMPORTED_MODULE_2__["default"], 1));


/***/ },

/***/ "./node_modules/underscore/modules/delay.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/delay.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");


// Delays a function for the given number of milliseconds, and then calls
// it with the arguments supplied.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(func, wait, args) {
  return setTimeout(function() {
    return func.apply(null, args);
  }, wait);
}));


/***/ },

/***/ "./node_modules/underscore/modules/difference.js"
/*!*******************************************************!*\
  !*** ./node_modules/underscore/modules/difference.js ***!
  \*******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _flatten_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_flatten.js */ "./node_modules/underscore/modules/_flatten.js");
/* harmony import */ var _filter_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./filter.js */ "./node_modules/underscore/modules/filter.js");
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./contains.js */ "./node_modules/underscore/modules/contains.js");





// Take the difference between one array and a number of other arrays.
// Only the elements present in just the first array will remain.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(array, rest) {
  rest = (0,_flatten_js__WEBPACK_IMPORTED_MODULE_1__["default"])(rest, true, true);
  return (0,_filter_js__WEBPACK_IMPORTED_MODULE_2__["default"])(array, function(value){
    return !(0,_contains_js__WEBPACK_IMPORTED_MODULE_3__["default"])(rest, value);
  });
}));


/***/ },

/***/ "./node_modules/underscore/modules/each.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/each.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ each)
/* harmony export */ });
/* harmony import */ var _optimizeCb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_optimizeCb.js */ "./node_modules/underscore/modules/_optimizeCb.js");
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");




// The cornerstone for collection functions, an `each`
// implementation, aka `forEach`.
// Handles raw objects in addition to array-likes. Treats all
// sparse array-likes as if they were dense.
function each(obj, iteratee, context) {
  iteratee = (0,_optimizeCb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(iteratee, context);
  var i, length;
  if ((0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj)) {
    for (i = 0, length = obj.length; i < length; i++) {
      iteratee(obj[i], i, obj);
    }
  } else {
    var _keys = (0,_keys_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj);
    for (i = 0, length = _keys.length; i < length; i++) {
      iteratee(obj[_keys[i]], _keys[i], obj);
    }
  }
  return obj;
}


/***/ },

/***/ "./node_modules/underscore/modules/escape.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/escape.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createEscaper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createEscaper.js */ "./node_modules/underscore/modules/_createEscaper.js");
/* harmony import */ var _escapeMap_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_escapeMap.js */ "./node_modules/underscore/modules/_escapeMap.js");



// Function for escaping strings to HTML interpolation.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createEscaper_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_escapeMap_js__WEBPACK_IMPORTED_MODULE_1__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/every.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/every.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ every)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");




// Determine whether all of the elements pass a truth test.
function every(obj, predicate, context) {
  predicate = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(predicate, context);
  var _keys = !(0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj) && (0,_keys_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj),
      length = (_keys || obj).length;
  for (var index = 0; index < length; index++) {
    var currentKey = _keys ? _keys[index] : index;
    if (!predicate(obj[currentKey], currentKey, obj)) return false;
  }
  return true;
}


/***/ },

/***/ "./node_modules/underscore/modules/extend.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/extend.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createAssigner_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createAssigner.js */ "./node_modules/underscore/modules/_createAssigner.js");
/* harmony import */ var _allKeys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./allKeys.js */ "./node_modules/underscore/modules/allKeys.js");



// Extend a given object with all the properties in passed-in object(s).
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createAssigner_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_allKeys_js__WEBPACK_IMPORTED_MODULE_1__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/extendOwn.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/extendOwn.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createAssigner_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createAssigner.js */ "./node_modules/underscore/modules/_createAssigner.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");



// Assigns a given object with all the own properties in the passed-in
// object(s).
// (https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/Object/assign)
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createAssigner_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_keys_js__WEBPACK_IMPORTED_MODULE_1__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/filter.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/filter.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ filter)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _each_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./each.js */ "./node_modules/underscore/modules/each.js");



// Return all the elements that pass a truth test.
function filter(obj, predicate, context) {
  var results = [];
  predicate = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(predicate, context);
  (0,_each_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj, function(value, index, list) {
    if (predicate(value, index, list)) results.push(value);
  });
  return results;
}


/***/ },

/***/ "./node_modules/underscore/modules/find.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/find.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ find)
/* harmony export */ });
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _findIndex_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./findIndex.js */ "./node_modules/underscore/modules/findIndex.js");
/* harmony import */ var _findKey_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./findKey.js */ "./node_modules/underscore/modules/findKey.js");




// Return the first value which passes a truth test.
function find(obj, predicate, context) {
  var keyFinder = (0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj) ? _findIndex_js__WEBPACK_IMPORTED_MODULE_1__["default"] : _findKey_js__WEBPACK_IMPORTED_MODULE_2__["default"];
  var key = keyFinder(obj, predicate, context);
  if (key !== void 0 && key !== -1) return obj[key];
}


/***/ },

/***/ "./node_modules/underscore/modules/findIndex.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/findIndex.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createPredicateIndexFinder_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createPredicateIndexFinder.js */ "./node_modules/underscore/modules/_createPredicateIndexFinder.js");


// Returns the first index on an array-like that passes a truth test.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createPredicateIndexFinder_js__WEBPACK_IMPORTED_MODULE_0__["default"])(1));


/***/ },

/***/ "./node_modules/underscore/modules/findKey.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/findKey.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ findKey)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");



// Returns the first key on an object that passes a truth test.
function findKey(obj, predicate, context) {
  predicate = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(predicate, context);
  var _keys = (0,_keys_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj), key;
  for (var i = 0, length = _keys.length; i < length; i++) {
    key = _keys[i];
    if (predicate(obj[key], key, obj)) return key;
  }
}


/***/ },

/***/ "./node_modules/underscore/modules/findLastIndex.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/findLastIndex.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createPredicateIndexFinder_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createPredicateIndexFinder.js */ "./node_modules/underscore/modules/_createPredicateIndexFinder.js");


// Returns the last index on an array-like that passes a truth test.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createPredicateIndexFinder_js__WEBPACK_IMPORTED_MODULE_0__["default"])(-1));


/***/ },

/***/ "./node_modules/underscore/modules/findWhere.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/findWhere.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ findWhere)
/* harmony export */ });
/* harmony import */ var _find_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./find.js */ "./node_modules/underscore/modules/find.js");
/* harmony import */ var _matcher_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./matcher.js */ "./node_modules/underscore/modules/matcher.js");



// Convenience version of a common use case of `_.find`: getting the first
// object containing specific `key:value` pairs.
function findWhere(obj, attrs) {
  return (0,_find_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj, (0,_matcher_js__WEBPACK_IMPORTED_MODULE_1__["default"])(attrs));
}


/***/ },

/***/ "./node_modules/underscore/modules/first.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/first.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ first)
/* harmony export */ });
/* harmony import */ var _initial_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./initial.js */ "./node_modules/underscore/modules/initial.js");


// Get the first element of an array. Passing **n** will return the first N
// values in the array. The **guard** check allows it to work with `_.map`.
function first(array, n, guard) {
  if (array == null || array.length < 1) return n == null || guard ? void 0 : [];
  if (n == null || guard) return array[0];
  return (0,_initial_js__WEBPACK_IMPORTED_MODULE_0__["default"])(array, array.length - n);
}


/***/ },

/***/ "./node_modules/underscore/modules/flatten.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/flatten.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ flatten)
/* harmony export */ });
/* harmony import */ var _flatten_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_flatten.js */ "./node_modules/underscore/modules/_flatten.js");


// Flatten out an array, either recursively (by default), or up to `depth`.
// Passing `true` or `false` as `depth` means `1` or `Infinity`, respectively.
function flatten(array, depth) {
  return (0,_flatten_js__WEBPACK_IMPORTED_MODULE_0__["default"])(array, depth, false);
}


/***/ },

/***/ "./node_modules/underscore/modules/functions.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/functions.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ functions)
/* harmony export */ });
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");


// Return a sorted list of the function names available on the object.
function functions(obj) {
  var names = [];
  for (var key in obj) {
    if ((0,_isFunction_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj[key])) names.push(key);
  }
  return names.sort();
}


/***/ },

/***/ "./node_modules/underscore/modules/get.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/get.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ get)
/* harmony export */ });
/* harmony import */ var _toPath_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_toPath.js */ "./node_modules/underscore/modules/_toPath.js");
/* harmony import */ var _deepGet_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_deepGet.js */ "./node_modules/underscore/modules/_deepGet.js");
/* harmony import */ var _isUndefined_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./isUndefined.js */ "./node_modules/underscore/modules/isUndefined.js");




// Get the value of the (deep) property on `path` from `object`.
// If any property in `path` does not exist or if the value is
// `undefined`, return `defaultValue` instead.
// The `path` is normalized through `_.toPath`.
function get(object, path, defaultValue) {
  var value = (0,_deepGet_js__WEBPACK_IMPORTED_MODULE_1__["default"])(object, (0,_toPath_js__WEBPACK_IMPORTED_MODULE_0__["default"])(path));
  return (0,_isUndefined_js__WEBPACK_IMPORTED_MODULE_2__["default"])(value) ? defaultValue : value;
}


/***/ },

/***/ "./node_modules/underscore/modules/groupBy.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/groupBy.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _group_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_group.js */ "./node_modules/underscore/modules/_group.js");
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_has.js */ "./node_modules/underscore/modules/_has.js");



// Groups the object's values by a criterion. Pass either a string attribute
// to group by, or a function that returns the criterion.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_group_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(result, value, key) {
  if ((0,_has_js__WEBPACK_IMPORTED_MODULE_1__["default"])(result, key)) result[key].push(value); else result[key] = [value];
}));


/***/ },

/***/ "./node_modules/underscore/modules/has.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/has.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ has)
/* harmony export */ });
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_has.js */ "./node_modules/underscore/modules/_has.js");
/* harmony import */ var _toPath_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_toPath.js */ "./node_modules/underscore/modules/_toPath.js");



// Shortcut function for checking if an object has a given property directly on
// itself (in other words, not on a prototype). Unlike the internal `has`
// function, this public version can also traverse nested properties.
function has(obj, path) {
  path = (0,_toPath_js__WEBPACK_IMPORTED_MODULE_1__["default"])(path);
  var length = path.length;
  for (var i = 0; i < length; i++) {
    var key = path[i];
    if (!(0,_has_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj, key)) return false;
    obj = obj[key];
  }
  return !!length;
}


/***/ },

/***/ "./node_modules/underscore/modules/identity.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/identity.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ identity)
/* harmony export */ });
// Keep the identity function around for default iteratees.
function identity(value) {
  return value;
}


/***/ },

/***/ "./node_modules/underscore/modules/index-all.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/index-all.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VERSION: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.VERSION),
/* harmony export */   after: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.after),
/* harmony export */   all: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.all),
/* harmony export */   allKeys: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.allKeys),
/* harmony export */   any: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.any),
/* harmony export */   assign: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.assign),
/* harmony export */   before: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.before),
/* harmony export */   bind: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.bind),
/* harmony export */   bindAll: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.bindAll),
/* harmony export */   chain: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.chain),
/* harmony export */   chunk: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.chunk),
/* harmony export */   clone: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.clone),
/* harmony export */   collect: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.collect),
/* harmony export */   compact: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.compact),
/* harmony export */   compose: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.compose),
/* harmony export */   constant: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.constant),
/* harmony export */   contains: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.contains),
/* harmony export */   countBy: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.countBy),
/* harmony export */   create: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.create),
/* harmony export */   debounce: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.debounce),
/* harmony export */   "default": () => (/* reexport safe */ _index_default_js__WEBPACK_IMPORTED_MODULE_0__["default"]),
/* harmony export */   defaults: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.defaults),
/* harmony export */   defer: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.defer),
/* harmony export */   delay: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.delay),
/* harmony export */   detect: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.detect),
/* harmony export */   difference: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.difference),
/* harmony export */   drop: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.drop),
/* harmony export */   each: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.each),
/* harmony export */   escape: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.escape),
/* harmony export */   every: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.every),
/* harmony export */   extend: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.extend),
/* harmony export */   extendOwn: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.extendOwn),
/* harmony export */   filter: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.filter),
/* harmony export */   find: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.find),
/* harmony export */   findIndex: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.findIndex),
/* harmony export */   findKey: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.findKey),
/* harmony export */   findLastIndex: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.findLastIndex),
/* harmony export */   findWhere: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.findWhere),
/* harmony export */   first: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.first),
/* harmony export */   flatten: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.flatten),
/* harmony export */   foldl: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.foldl),
/* harmony export */   foldr: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.foldr),
/* harmony export */   forEach: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.forEach),
/* harmony export */   functions: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.functions),
/* harmony export */   get: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.get),
/* harmony export */   groupBy: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.groupBy),
/* harmony export */   has: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.has),
/* harmony export */   head: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.head),
/* harmony export */   identity: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.identity),
/* harmony export */   include: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.include),
/* harmony export */   includes: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.includes),
/* harmony export */   indexBy: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.indexBy),
/* harmony export */   indexOf: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.indexOf),
/* harmony export */   initial: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.initial),
/* harmony export */   inject: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.inject),
/* harmony export */   intersection: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.intersection),
/* harmony export */   invert: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.invert),
/* harmony export */   invoke: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.invoke),
/* harmony export */   isArguments: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isArguments),
/* harmony export */   isArray: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isArray),
/* harmony export */   isArrayBuffer: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isArrayBuffer),
/* harmony export */   isBoolean: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isBoolean),
/* harmony export */   isDataView: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isDataView),
/* harmony export */   isDate: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isDate),
/* harmony export */   isElement: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isElement),
/* harmony export */   isEmpty: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isEmpty),
/* harmony export */   isEqual: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isEqual),
/* harmony export */   isError: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isError),
/* harmony export */   isFinite: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isFinite),
/* harmony export */   isFunction: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isFunction),
/* harmony export */   isMap: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isMap),
/* harmony export */   isMatch: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isMatch),
/* harmony export */   isNaN: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isNaN),
/* harmony export */   isNull: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isNull),
/* harmony export */   isNumber: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isNumber),
/* harmony export */   isObject: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isObject),
/* harmony export */   isRegExp: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isRegExp),
/* harmony export */   isSet: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isSet),
/* harmony export */   isString: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isString),
/* harmony export */   isSymbol: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isSymbol),
/* harmony export */   isTypedArray: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isTypedArray),
/* harmony export */   isUndefined: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isUndefined),
/* harmony export */   isWeakMap: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isWeakMap),
/* harmony export */   isWeakSet: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.isWeakSet),
/* harmony export */   iteratee: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.iteratee),
/* harmony export */   keys: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.keys),
/* harmony export */   last: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.last),
/* harmony export */   lastIndexOf: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.lastIndexOf),
/* harmony export */   map: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.map),
/* harmony export */   mapObject: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.mapObject),
/* harmony export */   matcher: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.matcher),
/* harmony export */   matches: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.matches),
/* harmony export */   max: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.max),
/* harmony export */   memoize: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.memoize),
/* harmony export */   methods: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.methods),
/* harmony export */   min: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.min),
/* harmony export */   mixin: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.mixin),
/* harmony export */   negate: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.negate),
/* harmony export */   noop: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.noop),
/* harmony export */   now: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.now),
/* harmony export */   object: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.object),
/* harmony export */   omit: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.omit),
/* harmony export */   once: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.once),
/* harmony export */   pairs: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.pairs),
/* harmony export */   partial: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.partial),
/* harmony export */   partition: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.partition),
/* harmony export */   pick: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.pick),
/* harmony export */   pluck: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.pluck),
/* harmony export */   property: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.property),
/* harmony export */   propertyOf: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.propertyOf),
/* harmony export */   random: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.random),
/* harmony export */   range: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.range),
/* harmony export */   reduce: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.reduce),
/* harmony export */   reduceRight: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.reduceRight),
/* harmony export */   reject: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.reject),
/* harmony export */   rest: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.rest),
/* harmony export */   restArguments: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.restArguments),
/* harmony export */   result: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.result),
/* harmony export */   sample: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.sample),
/* harmony export */   select: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.select),
/* harmony export */   shuffle: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.shuffle),
/* harmony export */   size: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.size),
/* harmony export */   some: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.some),
/* harmony export */   sortBy: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.sortBy),
/* harmony export */   sortedIndex: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.sortedIndex),
/* harmony export */   tail: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.tail),
/* harmony export */   take: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.take),
/* harmony export */   tap: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.tap),
/* harmony export */   template: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.template),
/* harmony export */   templateSettings: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.templateSettings),
/* harmony export */   throttle: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.throttle),
/* harmony export */   times: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.times),
/* harmony export */   toArray: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.toArray),
/* harmony export */   toPath: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.toPath),
/* harmony export */   transpose: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.transpose),
/* harmony export */   unescape: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.unescape),
/* harmony export */   union: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.union),
/* harmony export */   uniq: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.uniq),
/* harmony export */   unique: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.unique),
/* harmony export */   uniqueId: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.uniqueId),
/* harmony export */   unzip: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.unzip),
/* harmony export */   values: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.values),
/* harmony export */   where: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.where),
/* harmony export */   without: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.without),
/* harmony export */   wrap: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.wrap),
/* harmony export */   zip: () => (/* reexport safe */ _index_js__WEBPACK_IMPORTED_MODULE_1__.zip)
/* harmony export */ });
/* harmony import */ var _index_default_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./index-default.js */ "./node_modules/underscore/modules/index-default.js");
/* harmony import */ var _index_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./index.js */ "./node_modules/underscore/modules/index.js");
// ESM Exports
// ===========
// This module is the package entry point for ES module users. In other words,
// it is the module they are interfacing with when they import from the whole
// package instead of from a submodule, like this:
//
// ```js
// import { map } from 'underscore';
// ```
//
// The difference with `./index-default`, which is the package entry point for
// CommonJS, AMD and UMD users, is purely technical. In ES modules, named and
// default exports are considered to be siblings, so when you have a default
// export, its properties are not automatically available as named exports. For
// this reason, we re-export the named exports in addition to providing the same
// default export as in `./index-default`.




/***/ },

/***/ "./node_modules/underscore/modules/index-default.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/index-default.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./index.js */ "./node_modules/underscore/modules/index.js");
// Default Export
// ==============
// In this module, we mix our bundled exports into the `_` object and export
// the result. This is analogous to setting `module.exports = _` in CommonJS.
// Hence, this module is also the entry point of our UMD bundle and the package
// entry point for CommonJS and AMD users. In other words, this is (the source
// of) the module you are interfacing with when you do any of the following:
//
// ```js
// // CommonJS
// var _ = require('underscore');
//
// // AMD
// define(['underscore'], function(_) {...});
//
// // UMD in the browser
// // _ is available as a global variable
// ```



// Add all of the Underscore functions to the wrapper object.
var _ = (0,_index_js__WEBPACK_IMPORTED_MODULE_0__.mixin)(_index_js__WEBPACK_IMPORTED_MODULE_0__);
// Legacy Node.js API.
_._ = _;
// Export the Underscore API.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_);


/***/ },

/***/ "./node_modules/underscore/modules/index.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/index.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VERSION: () => (/* reexport safe */ _setup_js__WEBPACK_IMPORTED_MODULE_0__.VERSION),
/* harmony export */   after: () => (/* reexport safe */ _after_js__WEBPACK_IMPORTED_MODULE_72__["default"]),
/* harmony export */   all: () => (/* reexport safe */ _every_js__WEBPACK_IMPORTED_MODULE_89__["default"]),
/* harmony export */   allKeys: () => (/* reexport safe */ _allKeys_js__WEBPACK_IMPORTED_MODULE_29__["default"]),
/* harmony export */   any: () => (/* reexport safe */ _some_js__WEBPACK_IMPORTED_MODULE_90__["default"]),
/* harmony export */   assign: () => (/* reexport safe */ _extendOwn_js__WEBPACK_IMPORTED_MODULE_35__["default"]),
/* harmony export */   before: () => (/* reexport safe */ _before_js__WEBPACK_IMPORTED_MODULE_73__["default"]),
/* harmony export */   bind: () => (/* reexport safe */ _bind_js__WEBPACK_IMPORTED_MODULE_62__["default"]),
/* harmony export */   bindAll: () => (/* reexport safe */ _bindAll_js__WEBPACK_IMPORTED_MODULE_63__["default"]),
/* harmony export */   chain: () => (/* reexport safe */ _chain_js__WEBPACK_IMPORTED_MODULE_59__["default"]),
/* harmony export */   chunk: () => (/* reexport safe */ _chunk_js__WEBPACK_IMPORTED_MODULE_123__["default"]),
/* harmony export */   clone: () => (/* reexport safe */ _clone_js__WEBPACK_IMPORTED_MODULE_38__["default"]),
/* harmony export */   collect: () => (/* reexport safe */ _map_js__WEBPACK_IMPORTED_MODULE_84__["default"]),
/* harmony export */   compact: () => (/* reexport safe */ _compact_js__WEBPACK_IMPORTED_MODULE_112__["default"]),
/* harmony export */   compose: () => (/* reexport safe */ _compose_js__WEBPACK_IMPORTED_MODULE_71__["default"]),
/* harmony export */   constant: () => (/* reexport safe */ _constant_js__WEBPACK_IMPORTED_MODULE_44__["default"]),
/* harmony export */   contains: () => (/* reexport safe */ _contains_js__WEBPACK_IMPORTED_MODULE_91__["default"]),
/* harmony export */   countBy: () => (/* reexport safe */ _countBy_js__WEBPACK_IMPORTED_MODULE_102__["default"]),
/* harmony export */   create: () => (/* reexport safe */ _create_js__WEBPACK_IMPORTED_MODULE_37__["default"]),
/* harmony export */   debounce: () => (/* reexport safe */ _debounce_js__WEBPACK_IMPORTED_MODULE_68__["default"]),
/* harmony export */   "default": () => (/* reexport safe */ _underscore_array_methods_js__WEBPACK_IMPORTED_MODULE_125__["default"]),
/* harmony export */   defaults: () => (/* reexport safe */ _defaults_js__WEBPACK_IMPORTED_MODULE_36__["default"]),
/* harmony export */   defer: () => (/* reexport safe */ _defer_js__WEBPACK_IMPORTED_MODULE_66__["default"]),
/* harmony export */   delay: () => (/* reexport safe */ _delay_js__WEBPACK_IMPORTED_MODULE_65__["default"]),
/* harmony export */   detect: () => (/* reexport safe */ _find_js__WEBPACK_IMPORTED_MODULE_81__["default"]),
/* harmony export */   difference: () => (/* reexport safe */ _difference_js__WEBPACK_IMPORTED_MODULE_118__["default"]),
/* harmony export */   drop: () => (/* reexport safe */ _rest_js__WEBPACK_IMPORTED_MODULE_111__["default"]),
/* harmony export */   each: () => (/* reexport safe */ _each_js__WEBPACK_IMPORTED_MODULE_83__["default"]),
/* harmony export */   escape: () => (/* reexport safe */ _escape_js__WEBPACK_IMPORTED_MODULE_53__["default"]),
/* harmony export */   every: () => (/* reexport safe */ _every_js__WEBPACK_IMPORTED_MODULE_89__["default"]),
/* harmony export */   extend: () => (/* reexport safe */ _extend_js__WEBPACK_IMPORTED_MODULE_34__["default"]),
/* harmony export */   extendOwn: () => (/* reexport safe */ _extendOwn_js__WEBPACK_IMPORTED_MODULE_35__["default"]),
/* harmony export */   filter: () => (/* reexport safe */ _filter_js__WEBPACK_IMPORTED_MODULE_87__["default"]),
/* harmony export */   find: () => (/* reexport safe */ _find_js__WEBPACK_IMPORTED_MODULE_81__["default"]),
/* harmony export */   findIndex: () => (/* reexport safe */ _findIndex_js__WEBPACK_IMPORTED_MODULE_76__["default"]),
/* harmony export */   findKey: () => (/* reexport safe */ _findKey_js__WEBPACK_IMPORTED_MODULE_75__["default"]),
/* harmony export */   findLastIndex: () => (/* reexport safe */ _findLastIndex_js__WEBPACK_IMPORTED_MODULE_77__["default"]),
/* harmony export */   findWhere: () => (/* reexport safe */ _findWhere_js__WEBPACK_IMPORTED_MODULE_82__["default"]),
/* harmony export */   first: () => (/* reexport safe */ _first_js__WEBPACK_IMPORTED_MODULE_108__["default"]),
/* harmony export */   flatten: () => (/* reexport safe */ _flatten_js__WEBPACK_IMPORTED_MODULE_113__["default"]),
/* harmony export */   foldl: () => (/* reexport safe */ _reduce_js__WEBPACK_IMPORTED_MODULE_85__["default"]),
/* harmony export */   foldr: () => (/* reexport safe */ _reduceRight_js__WEBPACK_IMPORTED_MODULE_86__["default"]),
/* harmony export */   forEach: () => (/* reexport safe */ _each_js__WEBPACK_IMPORTED_MODULE_83__["default"]),
/* harmony export */   functions: () => (/* reexport safe */ _functions_js__WEBPACK_IMPORTED_MODULE_33__["default"]),
/* harmony export */   get: () => (/* reexport safe */ _get_js__WEBPACK_IMPORTED_MODULE_40__["default"]),
/* harmony export */   groupBy: () => (/* reexport safe */ _groupBy_js__WEBPACK_IMPORTED_MODULE_100__["default"]),
/* harmony export */   has: () => (/* reexport safe */ _has_js__WEBPACK_IMPORTED_MODULE_41__["default"]),
/* harmony export */   head: () => (/* reexport safe */ _first_js__WEBPACK_IMPORTED_MODULE_108__["default"]),
/* harmony export */   identity: () => (/* reexport safe */ _identity_js__WEBPACK_IMPORTED_MODULE_43__["default"]),
/* harmony export */   include: () => (/* reexport safe */ _contains_js__WEBPACK_IMPORTED_MODULE_91__["default"]),
/* harmony export */   includes: () => (/* reexport safe */ _contains_js__WEBPACK_IMPORTED_MODULE_91__["default"]),
/* harmony export */   indexBy: () => (/* reexport safe */ _indexBy_js__WEBPACK_IMPORTED_MODULE_101__["default"]),
/* harmony export */   indexOf: () => (/* reexport safe */ _indexOf_js__WEBPACK_IMPORTED_MODULE_79__["default"]),
/* harmony export */   initial: () => (/* reexport safe */ _initial_js__WEBPACK_IMPORTED_MODULE_109__["default"]),
/* harmony export */   inject: () => (/* reexport safe */ _reduce_js__WEBPACK_IMPORTED_MODULE_85__["default"]),
/* harmony export */   intersection: () => (/* reexport safe */ _intersection_js__WEBPACK_IMPORTED_MODULE_117__["default"]),
/* harmony export */   invert: () => (/* reexport safe */ _invert_js__WEBPACK_IMPORTED_MODULE_32__["default"]),
/* harmony export */   invoke: () => (/* reexport safe */ _invoke_js__WEBPACK_IMPORTED_MODULE_92__["default"]),
/* harmony export */   isArguments: () => (/* reexport safe */ _isArguments_js__WEBPACK_IMPORTED_MODULE_17__["default"]),
/* harmony export */   isArray: () => (/* reexport safe */ _isArray_js__WEBPACK_IMPORTED_MODULE_15__["default"]),
/* harmony export */   isArrayBuffer: () => (/* reexport safe */ _isArrayBuffer_js__WEBPACK_IMPORTED_MODULE_13__["default"]),
/* harmony export */   isBoolean: () => (/* reexport safe */ _isBoolean_js__WEBPACK_IMPORTED_MODULE_5__["default"]),
/* harmony export */   isDataView: () => (/* reexport safe */ _isDataView_js__WEBPACK_IMPORTED_MODULE_14__["default"]),
/* harmony export */   isDate: () => (/* reexport safe */ _isDate_js__WEBPACK_IMPORTED_MODULE_9__["default"]),
/* harmony export */   isElement: () => (/* reexport safe */ _isElement_js__WEBPACK_IMPORTED_MODULE_6__["default"]),
/* harmony export */   isEmpty: () => (/* reexport safe */ _isEmpty_js__WEBPACK_IMPORTED_MODULE_21__["default"]),
/* harmony export */   isEqual: () => (/* reexport safe */ _isEqual_js__WEBPACK_IMPORTED_MODULE_23__["default"]),
/* harmony export */   isError: () => (/* reexport safe */ _isError_js__WEBPACK_IMPORTED_MODULE_11__["default"]),
/* harmony export */   isFinite: () => (/* reexport safe */ _isFinite_js__WEBPACK_IMPORTED_MODULE_18__["default"]),
/* harmony export */   isFunction: () => (/* reexport safe */ _isFunction_js__WEBPACK_IMPORTED_MODULE_16__["default"]),
/* harmony export */   isMap: () => (/* reexport safe */ _isMap_js__WEBPACK_IMPORTED_MODULE_24__["default"]),
/* harmony export */   isMatch: () => (/* reexport safe */ _isMatch_js__WEBPACK_IMPORTED_MODULE_22__["default"]),
/* harmony export */   isNaN: () => (/* reexport safe */ _isNaN_js__WEBPACK_IMPORTED_MODULE_19__["default"]),
/* harmony export */   isNull: () => (/* reexport safe */ _isNull_js__WEBPACK_IMPORTED_MODULE_3__["default"]),
/* harmony export */   isNumber: () => (/* reexport safe */ _isNumber_js__WEBPACK_IMPORTED_MODULE_8__["default"]),
/* harmony export */   isObject: () => (/* reexport safe */ _isObject_js__WEBPACK_IMPORTED_MODULE_2__["default"]),
/* harmony export */   isRegExp: () => (/* reexport safe */ _isRegExp_js__WEBPACK_IMPORTED_MODULE_10__["default"]),
/* harmony export */   isSet: () => (/* reexport safe */ _isSet_js__WEBPACK_IMPORTED_MODULE_26__["default"]),
/* harmony export */   isString: () => (/* reexport safe */ _isString_js__WEBPACK_IMPORTED_MODULE_7__["default"]),
/* harmony export */   isSymbol: () => (/* reexport safe */ _isSymbol_js__WEBPACK_IMPORTED_MODULE_12__["default"]),
/* harmony export */   isTypedArray: () => (/* reexport safe */ _isTypedArray_js__WEBPACK_IMPORTED_MODULE_20__["default"]),
/* harmony export */   isUndefined: () => (/* reexport safe */ _isUndefined_js__WEBPACK_IMPORTED_MODULE_4__["default"]),
/* harmony export */   isWeakMap: () => (/* reexport safe */ _isWeakMap_js__WEBPACK_IMPORTED_MODULE_25__["default"]),
/* harmony export */   isWeakSet: () => (/* reexport safe */ _isWeakSet_js__WEBPACK_IMPORTED_MODULE_27__["default"]),
/* harmony export */   iteratee: () => (/* reexport safe */ _iteratee_js__WEBPACK_IMPORTED_MODULE_60__["default"]),
/* harmony export */   keys: () => (/* reexport safe */ _keys_js__WEBPACK_IMPORTED_MODULE_28__["default"]),
/* harmony export */   last: () => (/* reexport safe */ _last_js__WEBPACK_IMPORTED_MODULE_110__["default"]),
/* harmony export */   lastIndexOf: () => (/* reexport safe */ _lastIndexOf_js__WEBPACK_IMPORTED_MODULE_80__["default"]),
/* harmony export */   map: () => (/* reexport safe */ _map_js__WEBPACK_IMPORTED_MODULE_84__["default"]),
/* harmony export */   mapObject: () => (/* reexport safe */ _mapObject_js__WEBPACK_IMPORTED_MODULE_42__["default"]),
/* harmony export */   matcher: () => (/* reexport safe */ _matcher_js__WEBPACK_IMPORTED_MODULE_49__["default"]),
/* harmony export */   matches: () => (/* reexport safe */ _matcher_js__WEBPACK_IMPORTED_MODULE_49__["default"]),
/* harmony export */   max: () => (/* reexport safe */ _max_js__WEBPACK_IMPORTED_MODULE_95__["default"]),
/* harmony export */   memoize: () => (/* reexport safe */ _memoize_js__WEBPACK_IMPORTED_MODULE_64__["default"]),
/* harmony export */   methods: () => (/* reexport safe */ _functions_js__WEBPACK_IMPORTED_MODULE_33__["default"]),
/* harmony export */   min: () => (/* reexport safe */ _min_js__WEBPACK_IMPORTED_MODULE_96__["default"]),
/* harmony export */   mixin: () => (/* reexport safe */ _mixin_js__WEBPACK_IMPORTED_MODULE_124__["default"]),
/* harmony export */   negate: () => (/* reexport safe */ _negate_js__WEBPACK_IMPORTED_MODULE_70__["default"]),
/* harmony export */   noop: () => (/* reexport safe */ _noop_js__WEBPACK_IMPORTED_MODULE_45__["default"]),
/* harmony export */   now: () => (/* reexport safe */ _now_js__WEBPACK_IMPORTED_MODULE_52__["default"]),
/* harmony export */   object: () => (/* reexport safe */ _object_js__WEBPACK_IMPORTED_MODULE_121__["default"]),
/* harmony export */   omit: () => (/* reexport safe */ _omit_js__WEBPACK_IMPORTED_MODULE_107__["default"]),
/* harmony export */   once: () => (/* reexport safe */ _once_js__WEBPACK_IMPORTED_MODULE_74__["default"]),
/* harmony export */   pairs: () => (/* reexport safe */ _pairs_js__WEBPACK_IMPORTED_MODULE_31__["default"]),
/* harmony export */   partial: () => (/* reexport safe */ _partial_js__WEBPACK_IMPORTED_MODULE_61__["default"]),
/* harmony export */   partition: () => (/* reexport safe */ _partition_js__WEBPACK_IMPORTED_MODULE_103__["default"]),
/* harmony export */   pick: () => (/* reexport safe */ _pick_js__WEBPACK_IMPORTED_MODULE_106__["default"]),
/* harmony export */   pluck: () => (/* reexport safe */ _pluck_js__WEBPACK_IMPORTED_MODULE_93__["default"]),
/* harmony export */   property: () => (/* reexport safe */ _property_js__WEBPACK_IMPORTED_MODULE_47__["default"]),
/* harmony export */   propertyOf: () => (/* reexport safe */ _propertyOf_js__WEBPACK_IMPORTED_MODULE_48__["default"]),
/* harmony export */   random: () => (/* reexport safe */ _random_js__WEBPACK_IMPORTED_MODULE_51__["default"]),
/* harmony export */   range: () => (/* reexport safe */ _range_js__WEBPACK_IMPORTED_MODULE_122__["default"]),
/* harmony export */   reduce: () => (/* reexport safe */ _reduce_js__WEBPACK_IMPORTED_MODULE_85__["default"]),
/* harmony export */   reduceRight: () => (/* reexport safe */ _reduceRight_js__WEBPACK_IMPORTED_MODULE_86__["default"]),
/* harmony export */   reject: () => (/* reexport safe */ _reject_js__WEBPACK_IMPORTED_MODULE_88__["default"]),
/* harmony export */   rest: () => (/* reexport safe */ _rest_js__WEBPACK_IMPORTED_MODULE_111__["default"]),
/* harmony export */   restArguments: () => (/* reexport safe */ _restArguments_js__WEBPACK_IMPORTED_MODULE_1__["default"]),
/* harmony export */   result: () => (/* reexport safe */ _result_js__WEBPACK_IMPORTED_MODULE_57__["default"]),
/* harmony export */   sample: () => (/* reexport safe */ _sample_js__WEBPACK_IMPORTED_MODULE_98__["default"]),
/* harmony export */   select: () => (/* reexport safe */ _filter_js__WEBPACK_IMPORTED_MODULE_87__["default"]),
/* harmony export */   shuffle: () => (/* reexport safe */ _shuffle_js__WEBPACK_IMPORTED_MODULE_97__["default"]),
/* harmony export */   size: () => (/* reexport safe */ _size_js__WEBPACK_IMPORTED_MODULE_105__["default"]),
/* harmony export */   some: () => (/* reexport safe */ _some_js__WEBPACK_IMPORTED_MODULE_90__["default"]),
/* harmony export */   sortBy: () => (/* reexport safe */ _sortBy_js__WEBPACK_IMPORTED_MODULE_99__["default"]),
/* harmony export */   sortedIndex: () => (/* reexport safe */ _sortedIndex_js__WEBPACK_IMPORTED_MODULE_78__["default"]),
/* harmony export */   tail: () => (/* reexport safe */ _rest_js__WEBPACK_IMPORTED_MODULE_111__["default"]),
/* harmony export */   take: () => (/* reexport safe */ _first_js__WEBPACK_IMPORTED_MODULE_108__["default"]),
/* harmony export */   tap: () => (/* reexport safe */ _tap_js__WEBPACK_IMPORTED_MODULE_39__["default"]),
/* harmony export */   template: () => (/* reexport safe */ _template_js__WEBPACK_IMPORTED_MODULE_56__["default"]),
/* harmony export */   templateSettings: () => (/* reexport safe */ _templateSettings_js__WEBPACK_IMPORTED_MODULE_55__["default"]),
/* harmony export */   throttle: () => (/* reexport safe */ _throttle_js__WEBPACK_IMPORTED_MODULE_67__["default"]),
/* harmony export */   times: () => (/* reexport safe */ _times_js__WEBPACK_IMPORTED_MODULE_50__["default"]),
/* harmony export */   toArray: () => (/* reexport safe */ _toArray_js__WEBPACK_IMPORTED_MODULE_104__["default"]),
/* harmony export */   toPath: () => (/* reexport safe */ _toPath_js__WEBPACK_IMPORTED_MODULE_46__["default"]),
/* harmony export */   transpose: () => (/* reexport safe */ _unzip_js__WEBPACK_IMPORTED_MODULE_119__["default"]),
/* harmony export */   unescape: () => (/* reexport safe */ _unescape_js__WEBPACK_IMPORTED_MODULE_54__["default"]),
/* harmony export */   union: () => (/* reexport safe */ _union_js__WEBPACK_IMPORTED_MODULE_116__["default"]),
/* harmony export */   uniq: () => (/* reexport safe */ _uniq_js__WEBPACK_IMPORTED_MODULE_115__["default"]),
/* harmony export */   unique: () => (/* reexport safe */ _uniq_js__WEBPACK_IMPORTED_MODULE_115__["default"]),
/* harmony export */   uniqueId: () => (/* reexport safe */ _uniqueId_js__WEBPACK_IMPORTED_MODULE_58__["default"]),
/* harmony export */   unzip: () => (/* reexport safe */ _unzip_js__WEBPACK_IMPORTED_MODULE_119__["default"]),
/* harmony export */   values: () => (/* reexport safe */ _values_js__WEBPACK_IMPORTED_MODULE_30__["default"]),
/* harmony export */   where: () => (/* reexport safe */ _where_js__WEBPACK_IMPORTED_MODULE_94__["default"]),
/* harmony export */   without: () => (/* reexport safe */ _without_js__WEBPACK_IMPORTED_MODULE_114__["default"]),
/* harmony export */   wrap: () => (/* reexport safe */ _wrap_js__WEBPACK_IMPORTED_MODULE_69__["default"]),
/* harmony export */   zip: () => (/* reexport safe */ _zip_js__WEBPACK_IMPORTED_MODULE_120__["default"])
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/underscore/modules/isObject.js");
/* harmony import */ var _isNull_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isNull.js */ "./node_modules/underscore/modules/isNull.js");
/* harmony import */ var _isUndefined_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./isUndefined.js */ "./node_modules/underscore/modules/isUndefined.js");
/* harmony import */ var _isBoolean_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./isBoolean.js */ "./node_modules/underscore/modules/isBoolean.js");
/* harmony import */ var _isElement_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./isElement.js */ "./node_modules/underscore/modules/isElement.js");
/* harmony import */ var _isString_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./isString.js */ "./node_modules/underscore/modules/isString.js");
/* harmony import */ var _isNumber_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./isNumber.js */ "./node_modules/underscore/modules/isNumber.js");
/* harmony import */ var _isDate_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./isDate.js */ "./node_modules/underscore/modules/isDate.js");
/* harmony import */ var _isRegExp_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./isRegExp.js */ "./node_modules/underscore/modules/isRegExp.js");
/* harmony import */ var _isError_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./isError.js */ "./node_modules/underscore/modules/isError.js");
/* harmony import */ var _isSymbol_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./isSymbol.js */ "./node_modules/underscore/modules/isSymbol.js");
/* harmony import */ var _isArrayBuffer_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./isArrayBuffer.js */ "./node_modules/underscore/modules/isArrayBuffer.js");
/* harmony import */ var _isDataView_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./isDataView.js */ "./node_modules/underscore/modules/isDataView.js");
/* harmony import */ var _isArray_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./isArray.js */ "./node_modules/underscore/modules/isArray.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _isArguments_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./isArguments.js */ "./node_modules/underscore/modules/isArguments.js");
/* harmony import */ var _isFinite_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./isFinite.js */ "./node_modules/underscore/modules/isFinite.js");
/* harmony import */ var _isNaN_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./isNaN.js */ "./node_modules/underscore/modules/isNaN.js");
/* harmony import */ var _isTypedArray_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./isTypedArray.js */ "./node_modules/underscore/modules/isTypedArray.js");
/* harmony import */ var _isEmpty_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./isEmpty.js */ "./node_modules/underscore/modules/isEmpty.js");
/* harmony import */ var _isMatch_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./isMatch.js */ "./node_modules/underscore/modules/isMatch.js");
/* harmony import */ var _isEqual_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./isEqual.js */ "./node_modules/underscore/modules/isEqual.js");
/* harmony import */ var _isMap_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./isMap.js */ "./node_modules/underscore/modules/isMap.js");
/* harmony import */ var _isWeakMap_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./isWeakMap.js */ "./node_modules/underscore/modules/isWeakMap.js");
/* harmony import */ var _isSet_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./isSet.js */ "./node_modules/underscore/modules/isSet.js");
/* harmony import */ var _isWeakSet_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./isWeakSet.js */ "./node_modules/underscore/modules/isWeakSet.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");
/* harmony import */ var _allKeys_js__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./allKeys.js */ "./node_modules/underscore/modules/allKeys.js");
/* harmony import */ var _values_js__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ./values.js */ "./node_modules/underscore/modules/values.js");
/* harmony import */ var _pairs_js__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ./pairs.js */ "./node_modules/underscore/modules/pairs.js");
/* harmony import */ var _invert_js__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! ./invert.js */ "./node_modules/underscore/modules/invert.js");
/* harmony import */ var _functions_js__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ./functions.js */ "./node_modules/underscore/modules/functions.js");
/* harmony import */ var _extend_js__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ./extend.js */ "./node_modules/underscore/modules/extend.js");
/* harmony import */ var _extendOwn_js__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! ./extendOwn.js */ "./node_modules/underscore/modules/extendOwn.js");
/* harmony import */ var _defaults_js__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! ./defaults.js */ "./node_modules/underscore/modules/defaults.js");
/* harmony import */ var _create_js__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! ./create.js */ "./node_modules/underscore/modules/create.js");
/* harmony import */ var _clone_js__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! ./clone.js */ "./node_modules/underscore/modules/clone.js");
/* harmony import */ var _tap_js__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__(/*! ./tap.js */ "./node_modules/underscore/modules/tap.js");
/* harmony import */ var _get_js__WEBPACK_IMPORTED_MODULE_40__ = __webpack_require__(/*! ./get.js */ "./node_modules/underscore/modules/get.js");
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_41__ = __webpack_require__(/*! ./has.js */ "./node_modules/underscore/modules/has.js");
/* harmony import */ var _mapObject_js__WEBPACK_IMPORTED_MODULE_42__ = __webpack_require__(/*! ./mapObject.js */ "./node_modules/underscore/modules/mapObject.js");
/* harmony import */ var _identity_js__WEBPACK_IMPORTED_MODULE_43__ = __webpack_require__(/*! ./identity.js */ "./node_modules/underscore/modules/identity.js");
/* harmony import */ var _constant_js__WEBPACK_IMPORTED_MODULE_44__ = __webpack_require__(/*! ./constant.js */ "./node_modules/underscore/modules/constant.js");
/* harmony import */ var _noop_js__WEBPACK_IMPORTED_MODULE_45__ = __webpack_require__(/*! ./noop.js */ "./node_modules/underscore/modules/noop.js");
/* harmony import */ var _toPath_js__WEBPACK_IMPORTED_MODULE_46__ = __webpack_require__(/*! ./toPath.js */ "./node_modules/underscore/modules/toPath.js");
/* harmony import */ var _property_js__WEBPACK_IMPORTED_MODULE_47__ = __webpack_require__(/*! ./property.js */ "./node_modules/underscore/modules/property.js");
/* harmony import */ var _propertyOf_js__WEBPACK_IMPORTED_MODULE_48__ = __webpack_require__(/*! ./propertyOf.js */ "./node_modules/underscore/modules/propertyOf.js");
/* harmony import */ var _matcher_js__WEBPACK_IMPORTED_MODULE_49__ = __webpack_require__(/*! ./matcher.js */ "./node_modules/underscore/modules/matcher.js");
/* harmony import */ var _times_js__WEBPACK_IMPORTED_MODULE_50__ = __webpack_require__(/*! ./times.js */ "./node_modules/underscore/modules/times.js");
/* harmony import */ var _random_js__WEBPACK_IMPORTED_MODULE_51__ = __webpack_require__(/*! ./random.js */ "./node_modules/underscore/modules/random.js");
/* harmony import */ var _now_js__WEBPACK_IMPORTED_MODULE_52__ = __webpack_require__(/*! ./now.js */ "./node_modules/underscore/modules/now.js");
/* harmony import */ var _escape_js__WEBPACK_IMPORTED_MODULE_53__ = __webpack_require__(/*! ./escape.js */ "./node_modules/underscore/modules/escape.js");
/* harmony import */ var _unescape_js__WEBPACK_IMPORTED_MODULE_54__ = __webpack_require__(/*! ./unescape.js */ "./node_modules/underscore/modules/unescape.js");
/* harmony import */ var _templateSettings_js__WEBPACK_IMPORTED_MODULE_55__ = __webpack_require__(/*! ./templateSettings.js */ "./node_modules/underscore/modules/templateSettings.js");
/* harmony import */ var _template_js__WEBPACK_IMPORTED_MODULE_56__ = __webpack_require__(/*! ./template.js */ "./node_modules/underscore/modules/template.js");
/* harmony import */ var _result_js__WEBPACK_IMPORTED_MODULE_57__ = __webpack_require__(/*! ./result.js */ "./node_modules/underscore/modules/result.js");
/* harmony import */ var _uniqueId_js__WEBPACK_IMPORTED_MODULE_58__ = __webpack_require__(/*! ./uniqueId.js */ "./node_modules/underscore/modules/uniqueId.js");
/* harmony import */ var _chain_js__WEBPACK_IMPORTED_MODULE_59__ = __webpack_require__(/*! ./chain.js */ "./node_modules/underscore/modules/chain.js");
/* harmony import */ var _iteratee_js__WEBPACK_IMPORTED_MODULE_60__ = __webpack_require__(/*! ./iteratee.js */ "./node_modules/underscore/modules/iteratee.js");
/* harmony import */ var _partial_js__WEBPACK_IMPORTED_MODULE_61__ = __webpack_require__(/*! ./partial.js */ "./node_modules/underscore/modules/partial.js");
/* harmony import */ var _bind_js__WEBPACK_IMPORTED_MODULE_62__ = __webpack_require__(/*! ./bind.js */ "./node_modules/underscore/modules/bind.js");
/* harmony import */ var _bindAll_js__WEBPACK_IMPORTED_MODULE_63__ = __webpack_require__(/*! ./bindAll.js */ "./node_modules/underscore/modules/bindAll.js");
/* harmony import */ var _memoize_js__WEBPACK_IMPORTED_MODULE_64__ = __webpack_require__(/*! ./memoize.js */ "./node_modules/underscore/modules/memoize.js");
/* harmony import */ var _delay_js__WEBPACK_IMPORTED_MODULE_65__ = __webpack_require__(/*! ./delay.js */ "./node_modules/underscore/modules/delay.js");
/* harmony import */ var _defer_js__WEBPACK_IMPORTED_MODULE_66__ = __webpack_require__(/*! ./defer.js */ "./node_modules/underscore/modules/defer.js");
/* harmony import */ var _throttle_js__WEBPACK_IMPORTED_MODULE_67__ = __webpack_require__(/*! ./throttle.js */ "./node_modules/underscore/modules/throttle.js");
/* harmony import */ var _debounce_js__WEBPACK_IMPORTED_MODULE_68__ = __webpack_require__(/*! ./debounce.js */ "./node_modules/underscore/modules/debounce.js");
/* harmony import */ var _wrap_js__WEBPACK_IMPORTED_MODULE_69__ = __webpack_require__(/*! ./wrap.js */ "./node_modules/underscore/modules/wrap.js");
/* harmony import */ var _negate_js__WEBPACK_IMPORTED_MODULE_70__ = __webpack_require__(/*! ./negate.js */ "./node_modules/underscore/modules/negate.js");
/* harmony import */ var _compose_js__WEBPACK_IMPORTED_MODULE_71__ = __webpack_require__(/*! ./compose.js */ "./node_modules/underscore/modules/compose.js");
/* harmony import */ var _after_js__WEBPACK_IMPORTED_MODULE_72__ = __webpack_require__(/*! ./after.js */ "./node_modules/underscore/modules/after.js");
/* harmony import */ var _before_js__WEBPACK_IMPORTED_MODULE_73__ = __webpack_require__(/*! ./before.js */ "./node_modules/underscore/modules/before.js");
/* harmony import */ var _once_js__WEBPACK_IMPORTED_MODULE_74__ = __webpack_require__(/*! ./once.js */ "./node_modules/underscore/modules/once.js");
/* harmony import */ var _findKey_js__WEBPACK_IMPORTED_MODULE_75__ = __webpack_require__(/*! ./findKey.js */ "./node_modules/underscore/modules/findKey.js");
/* harmony import */ var _findIndex_js__WEBPACK_IMPORTED_MODULE_76__ = __webpack_require__(/*! ./findIndex.js */ "./node_modules/underscore/modules/findIndex.js");
/* harmony import */ var _findLastIndex_js__WEBPACK_IMPORTED_MODULE_77__ = __webpack_require__(/*! ./findLastIndex.js */ "./node_modules/underscore/modules/findLastIndex.js");
/* harmony import */ var _sortedIndex_js__WEBPACK_IMPORTED_MODULE_78__ = __webpack_require__(/*! ./sortedIndex.js */ "./node_modules/underscore/modules/sortedIndex.js");
/* harmony import */ var _indexOf_js__WEBPACK_IMPORTED_MODULE_79__ = __webpack_require__(/*! ./indexOf.js */ "./node_modules/underscore/modules/indexOf.js");
/* harmony import */ var _lastIndexOf_js__WEBPACK_IMPORTED_MODULE_80__ = __webpack_require__(/*! ./lastIndexOf.js */ "./node_modules/underscore/modules/lastIndexOf.js");
/* harmony import */ var _find_js__WEBPACK_IMPORTED_MODULE_81__ = __webpack_require__(/*! ./find.js */ "./node_modules/underscore/modules/find.js");
/* harmony import */ var _findWhere_js__WEBPACK_IMPORTED_MODULE_82__ = __webpack_require__(/*! ./findWhere.js */ "./node_modules/underscore/modules/findWhere.js");
/* harmony import */ var _each_js__WEBPACK_IMPORTED_MODULE_83__ = __webpack_require__(/*! ./each.js */ "./node_modules/underscore/modules/each.js");
/* harmony import */ var _map_js__WEBPACK_IMPORTED_MODULE_84__ = __webpack_require__(/*! ./map.js */ "./node_modules/underscore/modules/map.js");
/* harmony import */ var _reduce_js__WEBPACK_IMPORTED_MODULE_85__ = __webpack_require__(/*! ./reduce.js */ "./node_modules/underscore/modules/reduce.js");
/* harmony import */ var _reduceRight_js__WEBPACK_IMPORTED_MODULE_86__ = __webpack_require__(/*! ./reduceRight.js */ "./node_modules/underscore/modules/reduceRight.js");
/* harmony import */ var _filter_js__WEBPACK_IMPORTED_MODULE_87__ = __webpack_require__(/*! ./filter.js */ "./node_modules/underscore/modules/filter.js");
/* harmony import */ var _reject_js__WEBPACK_IMPORTED_MODULE_88__ = __webpack_require__(/*! ./reject.js */ "./node_modules/underscore/modules/reject.js");
/* harmony import */ var _every_js__WEBPACK_IMPORTED_MODULE_89__ = __webpack_require__(/*! ./every.js */ "./node_modules/underscore/modules/every.js");
/* harmony import */ var _some_js__WEBPACK_IMPORTED_MODULE_90__ = __webpack_require__(/*! ./some.js */ "./node_modules/underscore/modules/some.js");
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_91__ = __webpack_require__(/*! ./contains.js */ "./node_modules/underscore/modules/contains.js");
/* harmony import */ var _invoke_js__WEBPACK_IMPORTED_MODULE_92__ = __webpack_require__(/*! ./invoke.js */ "./node_modules/underscore/modules/invoke.js");
/* harmony import */ var _pluck_js__WEBPACK_IMPORTED_MODULE_93__ = __webpack_require__(/*! ./pluck.js */ "./node_modules/underscore/modules/pluck.js");
/* harmony import */ var _where_js__WEBPACK_IMPORTED_MODULE_94__ = __webpack_require__(/*! ./where.js */ "./node_modules/underscore/modules/where.js");
/* harmony import */ var _max_js__WEBPACK_IMPORTED_MODULE_95__ = __webpack_require__(/*! ./max.js */ "./node_modules/underscore/modules/max.js");
/* harmony import */ var _min_js__WEBPACK_IMPORTED_MODULE_96__ = __webpack_require__(/*! ./min.js */ "./node_modules/underscore/modules/min.js");
/* harmony import */ var _shuffle_js__WEBPACK_IMPORTED_MODULE_97__ = __webpack_require__(/*! ./shuffle.js */ "./node_modules/underscore/modules/shuffle.js");
/* harmony import */ var _sample_js__WEBPACK_IMPORTED_MODULE_98__ = __webpack_require__(/*! ./sample.js */ "./node_modules/underscore/modules/sample.js");
/* harmony import */ var _sortBy_js__WEBPACK_IMPORTED_MODULE_99__ = __webpack_require__(/*! ./sortBy.js */ "./node_modules/underscore/modules/sortBy.js");
/* harmony import */ var _groupBy_js__WEBPACK_IMPORTED_MODULE_100__ = __webpack_require__(/*! ./groupBy.js */ "./node_modules/underscore/modules/groupBy.js");
/* harmony import */ var _indexBy_js__WEBPACK_IMPORTED_MODULE_101__ = __webpack_require__(/*! ./indexBy.js */ "./node_modules/underscore/modules/indexBy.js");
/* harmony import */ var _countBy_js__WEBPACK_IMPORTED_MODULE_102__ = __webpack_require__(/*! ./countBy.js */ "./node_modules/underscore/modules/countBy.js");
/* harmony import */ var _partition_js__WEBPACK_IMPORTED_MODULE_103__ = __webpack_require__(/*! ./partition.js */ "./node_modules/underscore/modules/partition.js");
/* harmony import */ var _toArray_js__WEBPACK_IMPORTED_MODULE_104__ = __webpack_require__(/*! ./toArray.js */ "./node_modules/underscore/modules/toArray.js");
/* harmony import */ var _size_js__WEBPACK_IMPORTED_MODULE_105__ = __webpack_require__(/*! ./size.js */ "./node_modules/underscore/modules/size.js");
/* harmony import */ var _pick_js__WEBPACK_IMPORTED_MODULE_106__ = __webpack_require__(/*! ./pick.js */ "./node_modules/underscore/modules/pick.js");
/* harmony import */ var _omit_js__WEBPACK_IMPORTED_MODULE_107__ = __webpack_require__(/*! ./omit.js */ "./node_modules/underscore/modules/omit.js");
/* harmony import */ var _first_js__WEBPACK_IMPORTED_MODULE_108__ = __webpack_require__(/*! ./first.js */ "./node_modules/underscore/modules/first.js");
/* harmony import */ var _initial_js__WEBPACK_IMPORTED_MODULE_109__ = __webpack_require__(/*! ./initial.js */ "./node_modules/underscore/modules/initial.js");
/* harmony import */ var _last_js__WEBPACK_IMPORTED_MODULE_110__ = __webpack_require__(/*! ./last.js */ "./node_modules/underscore/modules/last.js");
/* harmony import */ var _rest_js__WEBPACK_IMPORTED_MODULE_111__ = __webpack_require__(/*! ./rest.js */ "./node_modules/underscore/modules/rest.js");
/* harmony import */ var _compact_js__WEBPACK_IMPORTED_MODULE_112__ = __webpack_require__(/*! ./compact.js */ "./node_modules/underscore/modules/compact.js");
/* harmony import */ var _flatten_js__WEBPACK_IMPORTED_MODULE_113__ = __webpack_require__(/*! ./flatten.js */ "./node_modules/underscore/modules/flatten.js");
/* harmony import */ var _without_js__WEBPACK_IMPORTED_MODULE_114__ = __webpack_require__(/*! ./without.js */ "./node_modules/underscore/modules/without.js");
/* harmony import */ var _uniq_js__WEBPACK_IMPORTED_MODULE_115__ = __webpack_require__(/*! ./uniq.js */ "./node_modules/underscore/modules/uniq.js");
/* harmony import */ var _union_js__WEBPACK_IMPORTED_MODULE_116__ = __webpack_require__(/*! ./union.js */ "./node_modules/underscore/modules/union.js");
/* harmony import */ var _intersection_js__WEBPACK_IMPORTED_MODULE_117__ = __webpack_require__(/*! ./intersection.js */ "./node_modules/underscore/modules/intersection.js");
/* harmony import */ var _difference_js__WEBPACK_IMPORTED_MODULE_118__ = __webpack_require__(/*! ./difference.js */ "./node_modules/underscore/modules/difference.js");
/* harmony import */ var _unzip_js__WEBPACK_IMPORTED_MODULE_119__ = __webpack_require__(/*! ./unzip.js */ "./node_modules/underscore/modules/unzip.js");
/* harmony import */ var _zip_js__WEBPACK_IMPORTED_MODULE_120__ = __webpack_require__(/*! ./zip.js */ "./node_modules/underscore/modules/zip.js");
/* harmony import */ var _object_js__WEBPACK_IMPORTED_MODULE_121__ = __webpack_require__(/*! ./object.js */ "./node_modules/underscore/modules/object.js");
/* harmony import */ var _range_js__WEBPACK_IMPORTED_MODULE_122__ = __webpack_require__(/*! ./range.js */ "./node_modules/underscore/modules/range.js");
/* harmony import */ var _chunk_js__WEBPACK_IMPORTED_MODULE_123__ = __webpack_require__(/*! ./chunk.js */ "./node_modules/underscore/modules/chunk.js");
/* harmony import */ var _mixin_js__WEBPACK_IMPORTED_MODULE_124__ = __webpack_require__(/*! ./mixin.js */ "./node_modules/underscore/modules/mixin.js");
/* harmony import */ var _underscore_array_methods_js__WEBPACK_IMPORTED_MODULE_125__ = __webpack_require__(/*! ./underscore-array-methods.js */ "./node_modules/underscore/modules/underscore-array-methods.js");
// Named Exports
// =============

//     Underscore.js 1.13.8
//     https://underscorejs.org
//     (c) 2009-2026 Jeremy Ashkenas, Julian Gonggrijp, and DocumentCloud and Investigative Reporters & Editors
//     Underscore may be freely distributed under the MIT license.

// Baseline setup.



// Object Functions
// ----------------
// Our most fundamental functions operate on any JavaScript object.
// Most functions in Underscore depend on at least one function in this section.

// A group of functions that check the types of core JavaScript values.
// These are often informally referred to as the "isType" functions.



























// Functions that treat an object as a dictionary of key-value pairs.
















// Utility Functions
// -----------------
// A bit of a grab bag: Predicate-generating functions for use with filters and
// loops, string escaping and templating, create random numbers and unique ids,
// and functions that facilitate Underscore's chaining and iteration conventions.



















// Function (ahem) Functions
// -------------------------
// These functions take a function as an argument and return a new function
// as the result. Also known as higher-order functions.















// Finders
// -------
// Functions that extract (the position of) a single element from an object
// or array based on some criterion.









// Collection Functions
// --------------------
// Functions that work on any collection of elements: either an array, or
// an object of key-value pairs.
























// `_.pick` and `_.omit` are actually object functions, but we put
// them here in order to create a more natural reading order in the
// monolithic build as they depend on `_.contains`.



// Array Functions
// ---------------
// Functions that operate on arrays (and array-likes) only, because they’re
// expressed in terms of operations on an ordered list of values.

















// OOP
// ---
// These modules support the "object-oriented" calling style. See also
// `underscore.js` and `index-default.js`.




/***/ },

/***/ "./node_modules/underscore/modules/indexBy.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/indexBy.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _group_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_group.js */ "./node_modules/underscore/modules/_group.js");


// Indexes the object's values by a criterion, similar to `_.groupBy`, but for
// when you know that your index values will be unique.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_group_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(result, value, key) {
  result[key] = value;
}));


/***/ },

/***/ "./node_modules/underscore/modules/indexOf.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/indexOf.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _sortedIndex_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./sortedIndex.js */ "./node_modules/underscore/modules/sortedIndex.js");
/* harmony import */ var _findIndex_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./findIndex.js */ "./node_modules/underscore/modules/findIndex.js");
/* harmony import */ var _createIndexFinder_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_createIndexFinder.js */ "./node_modules/underscore/modules/_createIndexFinder.js");




// Return the position of the first occurrence of an item in an array,
// or -1 if the item is not included in the array.
// If the array is large and already in sort order, pass `true`
// for **isSorted** to use binary search.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createIndexFinder_js__WEBPACK_IMPORTED_MODULE_2__["default"])(1, _findIndex_js__WEBPACK_IMPORTED_MODULE_1__["default"], _sortedIndex_js__WEBPACK_IMPORTED_MODULE_0__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/initial.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/initial.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ initial)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");


// Returns everything but the last entry of the array. Especially useful on
// the arguments object. Passing **n** will return all the values in
// the array, excluding the last N.
function initial(array, n, guard) {
  return _setup_js__WEBPACK_IMPORTED_MODULE_0__.slice.call(array, 0, Math.max(0, array.length - (n == null || guard ? 1 : n)));
}


/***/ },

/***/ "./node_modules/underscore/modules/intersection.js"
/*!*********************************************************!*\
  !*** ./node_modules/underscore/modules/intersection.js ***!
  \*********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ intersection)
/* harmony export */ });
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./contains.js */ "./node_modules/underscore/modules/contains.js");



// Produce an array that contains every item shared between all the
// passed-in arrays.
function intersection(array) {
  var result = [];
  var argsLength = arguments.length;
  for (var i = 0, length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(array); i < length; i++) {
    var item = array[i];
    if ((0,_contains_js__WEBPACK_IMPORTED_MODULE_1__["default"])(result, item)) continue;
    var j;
    for (j = 1; j < argsLength; j++) {
      if (!(0,_contains_js__WEBPACK_IMPORTED_MODULE_1__["default"])(arguments[j], item)) break;
    }
    if (j === argsLength) result.push(item);
  }
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/invert.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/invert.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ invert)
/* harmony export */ });
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");


// Invert the keys and values of an object. The values must be serializable.
function invert(obj) {
  var result = {};
  var _keys = (0,_keys_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj);
  for (var i = 0, length = _keys.length; i < length; i++) {
    result[obj[_keys[i]]] = _keys[i];
  }
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/invoke.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/invoke.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _map_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./map.js */ "./node_modules/underscore/modules/map.js");
/* harmony import */ var _deepGet_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./_deepGet.js */ "./node_modules/underscore/modules/_deepGet.js");
/* harmony import */ var _toPath_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./_toPath.js */ "./node_modules/underscore/modules/_toPath.js");






// Invoke a method (with arguments) on every item in a collection.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(obj, path, args) {
  var contextPath, func;
  if ((0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(path)) {
    func = path;
  } else {
    path = (0,_toPath_js__WEBPACK_IMPORTED_MODULE_4__["default"])(path);
    contextPath = path.slice(0, -1);
    path = path[path.length - 1];
  }
  return (0,_map_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj, function(context) {
    var method = func;
    if (!method) {
      if (contextPath && contextPath.length) {
        context = (0,_deepGet_js__WEBPACK_IMPORTED_MODULE_3__["default"])(context, contextPath);
      }
      if (context == null) return void 0;
      method = context[path];
    }
    return method == null ? method : method.apply(context, args);
  });
}));


/***/ },

/***/ "./node_modules/underscore/modules/isArguments.js"
/*!********************************************************!*\
  !*** ./node_modules/underscore/modules/isArguments.js ***!
  \********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_has.js */ "./node_modules/underscore/modules/_has.js");



var isArguments = (0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Arguments');

// Define a fallback version of the method in browsers (ahem, IE < 9), where
// there isn't any inspectable "Arguments" type.
(function() {
  if (!isArguments(arguments)) {
    isArguments = function(obj) {
      return (0,_has_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj, 'callee');
    };
  }
}());

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (isArguments);


/***/ },

/***/ "./node_modules/underscore/modules/isArray.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/isArray.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");



// Is a given value an array?
// Delegates to ECMA5's native `Array.isArray`.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_setup_js__WEBPACK_IMPORTED_MODULE_0__.nativeIsArray || (0,_tagTester_js__WEBPACK_IMPORTED_MODULE_1__["default"])('Array'));


/***/ },

/***/ "./node_modules/underscore/modules/isArrayBuffer.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/isArrayBuffer.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('ArrayBuffer'));


/***/ },

/***/ "./node_modules/underscore/modules/isBoolean.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/isBoolean.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isBoolean)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");


// Is a given value a boolean?
function isBoolean(obj) {
  return obj === true || obj === false || _setup_js__WEBPACK_IMPORTED_MODULE_0__.toString.call(obj) === '[object Boolean]';
}


/***/ },

/***/ "./node_modules/underscore/modules/isDataView.js"
/*!*******************************************************!*\
  !*** ./node_modules/underscore/modules/isDataView.js ***!
  \*******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _isArrayBuffer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./isArrayBuffer.js */ "./node_modules/underscore/modules/isArrayBuffer.js");
/* harmony import */ var _stringTagBug_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./_stringTagBug.js */ "./node_modules/underscore/modules/_stringTagBug.js");





var isDataView = (0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('DataView');

// In IE 10 - Edge 13, we need a different heuristic
// to determine whether an object is a `DataView`.
// Also, in cases where the native `DataView` is
// overridden we can't rely on the tag itself.
function alternateIsDataView(obj) {
  return obj != null && (0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj.getInt8) && (0,_isArrayBuffer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj.buffer);
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_stringTagBug_js__WEBPACK_IMPORTED_MODULE_3__.hasDataViewBug ? alternateIsDataView : isDataView);


/***/ },

/***/ "./node_modules/underscore/modules/isDate.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/isDate.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Date'));


/***/ },

/***/ "./node_modules/underscore/modules/isElement.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/isElement.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isElement)
/* harmony export */ });
// Is a given value a DOM element?
function isElement(obj) {
  return !!(obj && obj.nodeType === 1);
}


/***/ },

/***/ "./node_modules/underscore/modules/isEmpty.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/isEmpty.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isEmpty)
/* harmony export */ });
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");
/* harmony import */ var _isArray_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isArray.js */ "./node_modules/underscore/modules/isArray.js");
/* harmony import */ var _isString_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./isString.js */ "./node_modules/underscore/modules/isString.js");
/* harmony import */ var _isArguments_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isArguments.js */ "./node_modules/underscore/modules/isArguments.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");






// Is a given array, string, or object empty?
// An "empty" object has no enumerable own-properties.
function isEmpty(obj) {
  if (obj == null) return true;
  // Skip the more expensive `toString`-based type checks if `obj` has no
  // `.length`.
  var length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj);
  if (typeof length == 'number' && (
    (0,_isArray_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj) || (0,_isString_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj) || (0,_isArguments_js__WEBPACK_IMPORTED_MODULE_3__["default"])(obj)
  )) return length === 0;
  return (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])((0,_keys_js__WEBPACK_IMPORTED_MODULE_4__["default"])(obj)) === 0;
}


/***/ },

/***/ "./node_modules/underscore/modules/isEqual.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/isEqual.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isEqual)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _getByteLength_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_getByteLength.js */ "./node_modules/underscore/modules/_getByteLength.js");
/* harmony import */ var _isTypedArray_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isTypedArray.js */ "./node_modules/underscore/modules/isTypedArray.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _stringTagBug_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./_stringTagBug.js */ "./node_modules/underscore/modules/_stringTagBug.js");
/* harmony import */ var _isDataView_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./isDataView.js */ "./node_modules/underscore/modules/isDataView.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./_has.js */ "./node_modules/underscore/modules/_has.js");
/* harmony import */ var _toBufferView_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./_toBufferView.js */ "./node_modules/underscore/modules/_toBufferView.js");











// We use this string twice, so give it a name for minification.
var tagDataView = '[object DataView]';

// Perform a deep comparison to check if two objects are equal.
function isEqual(a, b) {
  var todo = [{a: a, b: b}];
  // Initializing stacks of traversed objects for cycle detection.
  var aStack = [], bStack = [];

  while (todo.length) {
    var frame = todo.pop();
    if (frame === true) {
      // Remove the first object from the stack of traversed objects.
      aStack.pop();
      bStack.pop();
      continue;
    }
    a = frame.a;
    b = frame.b;

    // Identical objects are equal. `0 === -0`, but they aren't identical.
    // See the [Harmony `egal` proposal](https://wiki.ecmascript.org/doku.php?id=harmony:egal).
    if (a === b) {
      if (a !== 0 || 1 / a === 1 / b) continue;
      return false;
    }
    // `null` or `undefined` only equal to itself (strict comparison).
    if (a == null || b == null) return false;
    // `NaN`s are equivalent, but non-reflexive.
    if (a !== a) {
      if (b !== b) continue;
      return false;
    }
    // Exhaust primitive checks
    var type = typeof a;
    if (type !== 'function' && type !== 'object' && typeof b != 'object') return false;

// Internal recursive comparison function for `_.isEqual`.
    // Unwrap any wrapped objects.
    if (a instanceof _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"]) a = a._wrapped;
    if (b instanceof _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"]) b = b._wrapped;
    // Compare `[[Class]]` names.
    var className = _setup_js__WEBPACK_IMPORTED_MODULE_1__.toString.call(a);
    if (className !== _setup_js__WEBPACK_IMPORTED_MODULE_1__.toString.call(b)) return false;
    // Work around a bug in IE 10 - Edge 13.
    if (_stringTagBug_js__WEBPACK_IMPORTED_MODULE_5__.hasDataViewBug && className == '[object Object]' && (0,_isDataView_js__WEBPACK_IMPORTED_MODULE_6__["default"])(a)) {
      if (!(0,_isDataView_js__WEBPACK_IMPORTED_MODULE_6__["default"])(b)) return false;
      className = tagDataView;
    }
    switch (className) {
      // These types are compared by value.
    case '[object RegExp]':
      // RegExps are coerced to strings for comparison (Note: '' + /a/i === '/a/i')
    case '[object String]':
      // Primitives and their corresponding object wrappers are equivalent; thus, `"5"` is
      // equivalent to `new String("5")`.
      if ('' + a === '' + b) continue;
      return false;
    case '[object Number]':
      todo.push({a: +a, b: +b});
      continue;
    case '[object Date]':
    case '[object Boolean]':
      // Coerce dates and booleans to numeric primitive values. Dates are compared by their
      // millisecond representations. Note that invalid dates with millisecond representations
      // of `NaN` are not equivalent.
      if (+a === +b) continue;
      return false;
    case '[object Symbol]':
      if (_setup_js__WEBPACK_IMPORTED_MODULE_1__.SymbolProto.valueOf.call(a) === _setup_js__WEBPACK_IMPORTED_MODULE_1__.SymbolProto.valueOf.call(b)) continue;
      return false;
    case '[object ArrayBuffer]':
    case tagDataView:
      // Coerce to typed array so we can fall through.
      todo.push({a: (0,_toBufferView_js__WEBPACK_IMPORTED_MODULE_9__["default"])(a), b: (0,_toBufferView_js__WEBPACK_IMPORTED_MODULE_9__["default"])(b)});
      continue;
    }

    var areArrays = className === '[object Array]';
    if (!areArrays && (0,_isTypedArray_js__WEBPACK_IMPORTED_MODULE_3__["default"])(a)) {
      var byteLength = (0,_getByteLength_js__WEBPACK_IMPORTED_MODULE_2__["default"])(a);
      if (byteLength !== (0,_getByteLength_js__WEBPACK_IMPORTED_MODULE_2__["default"])(b)) return false;
      if (a.buffer === b.buffer && a.byteOffset === b.byteOffset) continue;
      areArrays = true;
    }
    if (!areArrays) {
      if (typeof a != 'object' || typeof b != 'object') return false;

      // Objects with different constructors are not equivalent, but `Object`s or `Array`s
      // from different frames are.
      var aCtor = a.constructor, bCtor = b.constructor;
      if (aCtor !== bCtor && !((0,_isFunction_js__WEBPACK_IMPORTED_MODULE_4__["default"])(aCtor) && aCtor instanceof aCtor &&
                               (0,_isFunction_js__WEBPACK_IMPORTED_MODULE_4__["default"])(bCtor) && bCtor instanceof bCtor)
          && ('constructor' in a && 'constructor' in b)) {
        return false;
      }
    }

    // Assume equality for cyclic structures. The algorithm for detecting cyclic
    // structures is adapted from ES 5.1 section 15.12.3, abstract operation `JO`.

    var length = aStack.length;
    while (length--) {
      // Linear search. Performance is inversely proportional to the number of
      // unique nested structures.
      if (aStack[length] === a) {
        if (bStack[length] === b) break;
        return false;
      }
    }
    if (length >= 0) continue;

    // Add the first object to the stack of traversed objects.
    aStack.push(a);
    bStack.push(b);
    todo.push(true);

    // Recursively compare objects and arrays.
    if (areArrays) {
      // Compare array lengths to determine if a deep comparison is necessary.
      length = a.length;
      if (length !== b.length) return false;
      // Deep compare the contents, ignoring non-numeric properties.
      while (length--) {
        todo.push({a: a[length], b: b[length]});
      }
    } else {
      // Deep compare objects.
      var _keys = (0,_keys_js__WEBPACK_IMPORTED_MODULE_7__["default"])(a), key;
      length = _keys.length;
      // Ensure that both objects contain the same number of properties before comparing deep equality.
      if ((0,_keys_js__WEBPACK_IMPORTED_MODULE_7__["default"])(b).length !== length) return false;
      while (length--) {
        // Deep compare each member
        key = _keys[length];
        if (!(0,_has_js__WEBPACK_IMPORTED_MODULE_8__["default"])(b, key)) return false;
        todo.push({a: a[key], b: b[key]});
      }
    }
  }
  return true;
}


/***/ },

/***/ "./node_modules/underscore/modules/isError.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/isError.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Error'));


/***/ },

/***/ "./node_modules/underscore/modules/isFinite.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/isFinite.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isFinite)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _isSymbol_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isSymbol.js */ "./node_modules/underscore/modules/isSymbol.js");



// Is a given object a finite number?
function isFinite(obj) {
  return !(0,_isSymbol_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj) && (0,_setup_js__WEBPACK_IMPORTED_MODULE_0__._isFinite)(obj) && !isNaN(parseFloat(obj));
}


/***/ },

/***/ "./node_modules/underscore/modules/isFunction.js"
/*!*******************************************************!*\
  !*** ./node_modules/underscore/modules/isFunction.js ***!
  \*******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");



var isFunction = (0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Function');

// Optimize `isFunction` if appropriate. Work around some `typeof` bugs in old
// v8, IE 11 (#1621), Safari 8 (#1929), and PhantomJS (#2236).
var nodelist = _setup_js__WEBPACK_IMPORTED_MODULE_1__.root.document && _setup_js__WEBPACK_IMPORTED_MODULE_1__.root.document.childNodes;
if ( true && typeof Int8Array != 'object' && typeof nodelist != 'function') {
  isFunction = function(obj) {
    return typeof obj == 'function' || false;
  };
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (isFunction);


/***/ },

/***/ "./node_modules/underscore/modules/isMap.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/isMap.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");
/* harmony import */ var _stringTagBug_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_stringTagBug.js */ "./node_modules/underscore/modules/_stringTagBug.js");
/* harmony import */ var _methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_methodFingerprint.js */ "./node_modules/underscore/modules/_methodFingerprint.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_stringTagBug_js__WEBPACK_IMPORTED_MODULE_1__.isIE11 ? (0,_methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__.ie11fingerprint)(_methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__.mapMethods) : (0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Map'));


/***/ },

/***/ "./node_modules/underscore/modules/isMatch.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/isMatch.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isMatch)
/* harmony export */ });
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");


// Returns whether an object has a given set of `key:value` pairs.
function isMatch(object, attrs) {
  var _keys = (0,_keys_js__WEBPACK_IMPORTED_MODULE_0__["default"])(attrs), length = _keys.length;
  if (object == null) return !length;
  var obj = Object(object);
  for (var i = 0; i < length; i++) {
    var key = _keys[i];
    if (attrs[key] !== obj[key] || !(key in obj)) return false;
  }
  return true;
}


/***/ },

/***/ "./node_modules/underscore/modules/isNaN.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/isNaN.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isNaN)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _isNumber_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isNumber.js */ "./node_modules/underscore/modules/isNumber.js");



// Is the given value `NaN`?
function isNaN(obj) {
  return (0,_isNumber_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj) && (0,_setup_js__WEBPACK_IMPORTED_MODULE_0__._isNaN)(obj);
}


/***/ },

/***/ "./node_modules/underscore/modules/isNull.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/isNull.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isNull)
/* harmony export */ });
// Is a given value equal to null?
function isNull(obj) {
  return obj === null;
}


/***/ },

/***/ "./node_modules/underscore/modules/isNumber.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/isNumber.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Number'));


/***/ },

/***/ "./node_modules/underscore/modules/isObject.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/isObject.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isObject)
/* harmony export */ });
// Is a given variable an object?
function isObject(obj) {
  var type = typeof obj;
  return type === 'function' || (type === 'object' && !!obj);
}


/***/ },

/***/ "./node_modules/underscore/modules/isRegExp.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/isRegExp.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('RegExp'));


/***/ },

/***/ "./node_modules/underscore/modules/isSet.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/isSet.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");
/* harmony import */ var _stringTagBug_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_stringTagBug.js */ "./node_modules/underscore/modules/_stringTagBug.js");
/* harmony import */ var _methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_methodFingerprint.js */ "./node_modules/underscore/modules/_methodFingerprint.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_stringTagBug_js__WEBPACK_IMPORTED_MODULE_1__.isIE11 ? (0,_methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__.ie11fingerprint)(_methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__.setMethods) : (0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Set'));


/***/ },

/***/ "./node_modules/underscore/modules/isString.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/isString.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('String'));


/***/ },

/***/ "./node_modules/underscore/modules/isSymbol.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/isSymbol.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('Symbol'));


/***/ },

/***/ "./node_modules/underscore/modules/isTypedArray.js"
/*!*********************************************************!*\
  !*** ./node_modules/underscore/modules/isTypedArray.js ***!
  \*********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _isDataView_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isDataView.js */ "./node_modules/underscore/modules/isDataView.js");
/* harmony import */ var _constant_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./constant.js */ "./node_modules/underscore/modules/constant.js");
/* harmony import */ var _isBufferLike_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./_isBufferLike.js */ "./node_modules/underscore/modules/_isBufferLike.js");





// Is a given value a typed array?
var typedArrayPattern = /\[object ((I|Ui)nt(8|16|32)|Float(32|64)|Uint8Clamped|Big(I|Ui)nt64)Array\]/;
function isTypedArray(obj) {
  // `ArrayBuffer.isView` is the most future-proof, so use it when available.
  // Otherwise, fall back on the above regular expression.
  return _setup_js__WEBPACK_IMPORTED_MODULE_0__.nativeIsView ? ((0,_setup_js__WEBPACK_IMPORTED_MODULE_0__.nativeIsView)(obj) && !(0,_isDataView_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj)) :
                (0,_isBufferLike_js__WEBPACK_IMPORTED_MODULE_3__["default"])(obj) && typedArrayPattern.test(_setup_js__WEBPACK_IMPORTED_MODULE_0__.toString.call(obj));
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_setup_js__WEBPACK_IMPORTED_MODULE_0__.supportsArrayBuffer ? isTypedArray : (0,_constant_js__WEBPACK_IMPORTED_MODULE_2__["default"])(false));


/***/ },

/***/ "./node_modules/underscore/modules/isUndefined.js"
/*!********************************************************!*\
  !*** ./node_modules/underscore/modules/isUndefined.js ***!
  \********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ isUndefined)
/* harmony export */ });
// Is a given variable undefined?
function isUndefined(obj) {
  return obj === void 0;
}


/***/ },

/***/ "./node_modules/underscore/modules/isWeakMap.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/isWeakMap.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");
/* harmony import */ var _stringTagBug_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_stringTagBug.js */ "./node_modules/underscore/modules/_stringTagBug.js");
/* harmony import */ var _methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_methodFingerprint.js */ "./node_modules/underscore/modules/_methodFingerprint.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_stringTagBug_js__WEBPACK_IMPORTED_MODULE_1__.isIE11 ? (0,_methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__.ie11fingerprint)(_methodFingerprint_js__WEBPACK_IMPORTED_MODULE_2__.weakMapMethods) : (0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('WeakMap'));


/***/ },

/***/ "./node_modules/underscore/modules/isWeakSet.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/isWeakSet.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _tagTester_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_tagTester.js */ "./node_modules/underscore/modules/_tagTester.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_tagTester_js__WEBPACK_IMPORTED_MODULE_0__["default"])('WeakSet'));


/***/ },

/***/ "./node_modules/underscore/modules/iteratee.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/iteratee.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ iteratee)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");
/* harmony import */ var _baseIteratee_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_baseIteratee.js */ "./node_modules/underscore/modules/_baseIteratee.js");



// External wrapper for our callback generator. Users may customize
// `_.iteratee` if they want additional predicate/iteratee shorthand styles.
// This abstraction hides the internal-only `argCount` argument.
function iteratee(value, context) {
  return (0,_baseIteratee_js__WEBPACK_IMPORTED_MODULE_1__["default"])(value, context, Infinity);
}
_underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].iteratee = iteratee;


/***/ },

/***/ "./node_modules/underscore/modules/keys.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/keys.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ keys)
/* harmony export */ });
/* harmony import */ var _isObject_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isObject.js */ "./node_modules/underscore/modules/isObject.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_has.js */ "./node_modules/underscore/modules/_has.js");
/* harmony import */ var _collectNonEnumProps_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./_collectNonEnumProps.js */ "./node_modules/underscore/modules/_collectNonEnumProps.js");





// Retrieve the names of an object's own properties.
// Delegates to **ECMAScript 5**'s native `Object.keys`.
function keys(obj) {
  if (!(0,_isObject_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj)) return [];
  if (_setup_js__WEBPACK_IMPORTED_MODULE_1__.nativeKeys) return (0,_setup_js__WEBPACK_IMPORTED_MODULE_1__.nativeKeys)(obj);
  var keys = [];
  for (var key in obj) if ((0,_has_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj, key)) keys.push(key);
  // Ahem, IE < 9.
  if (_setup_js__WEBPACK_IMPORTED_MODULE_1__.hasEnumBug) (0,_collectNonEnumProps_js__WEBPACK_IMPORTED_MODULE_3__["default"])(obj, keys);
  return keys;
}


/***/ },

/***/ "./node_modules/underscore/modules/last.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/last.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ last)
/* harmony export */ });
/* harmony import */ var _rest_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./rest.js */ "./node_modules/underscore/modules/rest.js");


// Get the last element of an array. Passing **n** will return the last N
// values in the array.
function last(array, n, guard) {
  if (array == null || array.length < 1) return n == null || guard ? void 0 : [];
  if (n == null || guard) return array[array.length - 1];
  return (0,_rest_js__WEBPACK_IMPORTED_MODULE_0__["default"])(array, Math.max(0, array.length - n));
}


/***/ },

/***/ "./node_modules/underscore/modules/lastIndexOf.js"
/*!********************************************************!*\
  !*** ./node_modules/underscore/modules/lastIndexOf.js ***!
  \********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _findLastIndex_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./findLastIndex.js */ "./node_modules/underscore/modules/findLastIndex.js");
/* harmony import */ var _createIndexFinder_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_createIndexFinder.js */ "./node_modules/underscore/modules/_createIndexFinder.js");



// Return the position of the last occurrence of an item in an array,
// or -1 if the item is not included in the array.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createIndexFinder_js__WEBPACK_IMPORTED_MODULE_1__["default"])(-1, _findLastIndex_js__WEBPACK_IMPORTED_MODULE_0__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/map.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/map.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ map)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");




// Return the results of applying the iteratee to each element.
function map(obj, iteratee, context) {
  iteratee = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(iteratee, context);
  var _keys = !(0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj) && (0,_keys_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj),
      length = (_keys || obj).length,
      results = Array(length);
  for (var index = 0; index < length; index++) {
    var currentKey = _keys ? _keys[index] : index;
    results[index] = iteratee(obj[currentKey], currentKey, obj);
  }
  return results;
}


/***/ },

/***/ "./node_modules/underscore/modules/mapObject.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/mapObject.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ mapObject)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");



// Returns the results of applying the `iteratee` to each element of `obj`.
// In contrast to `_.map` it returns an object.
function mapObject(obj, iteratee, context) {
  iteratee = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(iteratee, context);
  var _keys = (0,_keys_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj),
      length = _keys.length,
      results = {};
  for (var index = 0; index < length; index++) {
    var currentKey = _keys[index];
    results[currentKey] = iteratee(obj[currentKey], currentKey, obj);
  }
  return results;
}


/***/ },

/***/ "./node_modules/underscore/modules/matcher.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/matcher.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ matcher)
/* harmony export */ });
/* harmony import */ var _extendOwn_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./extendOwn.js */ "./node_modules/underscore/modules/extendOwn.js");
/* harmony import */ var _isMatch_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isMatch.js */ "./node_modules/underscore/modules/isMatch.js");



// Returns a predicate for checking whether an object has a given set of
// `key:value` pairs.
function matcher(attrs) {
  attrs = (0,_extendOwn_js__WEBPACK_IMPORTED_MODULE_0__["default"])({}, attrs);
  return function(obj) {
    return (0,_isMatch_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj, attrs);
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/max.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/max.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ max)
/* harmony export */ });
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _values_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./values.js */ "./node_modules/underscore/modules/values.js");
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _each_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./each.js */ "./node_modules/underscore/modules/each.js");





// Return the maximum element (or element-based computation).
function max(obj, iteratee, context) {
  var result = -Infinity, lastComputed = -Infinity,
      value, computed;
  if (iteratee == null || (typeof iteratee == 'number' && typeof obj[0] != 'object' && obj != null)) {
    obj = (0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj) ? obj : (0,_values_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj);
    for (var i = 0, length = obj.length; i < length; i++) {
      value = obj[i];
      if (value != null && value > result) {
        result = value;
      }
    }
  } else {
    iteratee = (0,_cb_js__WEBPACK_IMPORTED_MODULE_2__["default"])(iteratee, context);
    (0,_each_js__WEBPACK_IMPORTED_MODULE_3__["default"])(obj, function(v, index, list) {
      computed = iteratee(v, index, list);
      if (computed > lastComputed || (computed === -Infinity && result === -Infinity)) {
        result = v;
        lastComputed = computed;
      }
    });
  }
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/memoize.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/memoize.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ memoize)
/* harmony export */ });
/* harmony import */ var _has_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_has.js */ "./node_modules/underscore/modules/_has.js");


// Memoize an expensive function by storing its results.
function memoize(func, hasher) {
  var memoize = function(key) {
    var cache = memoize.cache;
    var address = '' + (hasher ? hasher.apply(this, arguments) : key);
    if (!(0,_has_js__WEBPACK_IMPORTED_MODULE_0__["default"])(cache, address)) cache[address] = func.apply(this, arguments);
    return cache[address];
  };
  memoize.cache = {};
  return memoize;
}


/***/ },

/***/ "./node_modules/underscore/modules/min.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/min.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ min)
/* harmony export */ });
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _values_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./values.js */ "./node_modules/underscore/modules/values.js");
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _each_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./each.js */ "./node_modules/underscore/modules/each.js");





// Return the minimum element (or element-based computation).
function min(obj, iteratee, context) {
  var result = Infinity, lastComputed = Infinity,
      value, computed;
  if (iteratee == null || (typeof iteratee == 'number' && typeof obj[0] != 'object' && obj != null)) {
    obj = (0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj) ? obj : (0,_values_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj);
    for (var i = 0, length = obj.length; i < length; i++) {
      value = obj[i];
      if (value != null && value < result) {
        result = value;
      }
    }
  } else {
    iteratee = (0,_cb_js__WEBPACK_IMPORTED_MODULE_2__["default"])(iteratee, context);
    (0,_each_js__WEBPACK_IMPORTED_MODULE_3__["default"])(obj, function(v, index, list) {
      computed = iteratee(v, index, list);
      if (computed < lastComputed || (computed === Infinity && result === Infinity)) {
        result = v;
        lastComputed = computed;
      }
    });
  }
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/mixin.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/mixin.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ mixin)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");
/* harmony import */ var _each_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./each.js */ "./node_modules/underscore/modules/each.js");
/* harmony import */ var _functions_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./functions.js */ "./node_modules/underscore/modules/functions.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _chainResult_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./_chainResult.js */ "./node_modules/underscore/modules/_chainResult.js");






// Add your own custom functions to the Underscore object.
function mixin(obj) {
  (0,_each_js__WEBPACK_IMPORTED_MODULE_1__["default"])((0,_functions_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj), function(name) {
    var func = _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"][name] = obj[name];
    _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].prototype[name] = function() {
      var args = [this._wrapped];
      _setup_js__WEBPACK_IMPORTED_MODULE_3__.push.apply(args, arguments);
      return (0,_chainResult_js__WEBPACK_IMPORTED_MODULE_4__["default"])(this, func.apply(_underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"], args));
    };
  });
  return _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"];
}


/***/ },

/***/ "./node_modules/underscore/modules/negate.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/negate.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ negate)
/* harmony export */ });
// Returns a negated version of the passed-in predicate.
function negate(predicate) {
  return function() {
    return !predicate.apply(this, arguments);
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/noop.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/noop.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ noop)
/* harmony export */ });
// Predicate-generating function. Often useful outside of Underscore.
function noop(){}


/***/ },

/***/ "./node_modules/underscore/modules/now.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/now.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
// A (possibly faster) way to get the current timestamp as an integer.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Date.now || function() {
  return new Date().getTime();
});


/***/ },

/***/ "./node_modules/underscore/modules/object.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/object.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ object)
/* harmony export */ });
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");


// Converts lists into objects. Pass either a single array of `[key, value]`
// pairs, or two parallel arrays of the same length -- one of keys, and one of
// the corresponding values. Passing by pairs is the reverse of `_.pairs`.
function object(list, values) {
  var result = {};
  for (var i = 0, length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_0__["default"])(list); i < length; i++) {
    if (values) {
      result[list[i]] = values[i];
    } else {
      result[list[i][0]] = list[i][1];
    }
  }
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/omit.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/omit.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _negate_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./negate.js */ "./node_modules/underscore/modules/negate.js");
/* harmony import */ var _map_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./map.js */ "./node_modules/underscore/modules/map.js");
/* harmony import */ var _flatten_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./_flatten.js */ "./node_modules/underscore/modules/_flatten.js");
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./contains.js */ "./node_modules/underscore/modules/contains.js");
/* harmony import */ var _pick_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./pick.js */ "./node_modules/underscore/modules/pick.js");








// Return a copy of the object without the disallowed properties.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(obj, keys) {
  var iteratee = keys[0], context;
  if ((0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(iteratee)) {
    iteratee = (0,_negate_js__WEBPACK_IMPORTED_MODULE_2__["default"])(iteratee);
    if (keys.length > 1) context = keys[1];
  } else {
    keys = (0,_map_js__WEBPACK_IMPORTED_MODULE_3__["default"])((0,_flatten_js__WEBPACK_IMPORTED_MODULE_4__["default"])(keys, false, false), String);
    iteratee = function(value, key) {
      return !(0,_contains_js__WEBPACK_IMPORTED_MODULE_5__["default"])(keys, key);
    };
  }
  return (0,_pick_js__WEBPACK_IMPORTED_MODULE_6__["default"])(obj, iteratee, context);
}));


/***/ },

/***/ "./node_modules/underscore/modules/once.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/once.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _partial_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./partial.js */ "./node_modules/underscore/modules/partial.js");
/* harmony import */ var _before_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./before.js */ "./node_modules/underscore/modules/before.js");



// Returns a function that will be executed at most one time, no matter how
// often you call it. Useful for lazy initialization.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_partial_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_before_js__WEBPACK_IMPORTED_MODULE_1__["default"], 2));


/***/ },

/***/ "./node_modules/underscore/modules/pairs.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/pairs.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ pairs)
/* harmony export */ });
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");


// Convert an object into a list of `[key, value]` pairs.
// The opposite of `_.object` with one argument.
function pairs(obj) {
  var _keys = (0,_keys_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj);
  var length = _keys.length;
  var pairs = Array(length);
  for (var i = 0; i < length; i++) {
    pairs[i] = [_keys[i], obj[_keys[i]]];
  }
  return pairs;
}


/***/ },

/***/ "./node_modules/underscore/modules/partial.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/partial.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _executeBound_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_executeBound.js */ "./node_modules/underscore/modules/_executeBound.js");
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");




// Partially apply a function by creating a version that has had some of its
// arguments pre-filled, without changing its dynamic `this` context. `_` acts
// as a placeholder by default, allowing any combination of arguments to be
// pre-filled. Set `_.partial.placeholder` for a custom placeholder argument.
var partial = (0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(func, boundArgs) {
  var placeholder = partial.placeholder;
  var bound = function() {
    var position = 0, length = boundArgs.length;
    var args = Array(length);
    for (var i = 0; i < length; i++) {
      args[i] = boundArgs[i] === placeholder ? arguments[position++] : boundArgs[i];
    }
    while (position < arguments.length) args.push(arguments[position++]);
    return (0,_executeBound_js__WEBPACK_IMPORTED_MODULE_1__["default"])(func, bound, this, this, args);
  };
  return bound;
});

partial.placeholder = _underscore_js__WEBPACK_IMPORTED_MODULE_2__["default"];
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (partial);


/***/ },

/***/ "./node_modules/underscore/modules/partition.js"
/*!******************************************************!*\
  !*** ./node_modules/underscore/modules/partition.js ***!
  \******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _group_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_group.js */ "./node_modules/underscore/modules/_group.js");


// Split a collection into two arrays: one whose elements all pass the given
// truth test, and one whose elements all do not pass the truth test.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_group_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(result, value, pass) {
  result[pass ? 0 : 1].push(value);
}, true));


/***/ },

/***/ "./node_modules/underscore/modules/pick.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/pick.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _optimizeCb_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_optimizeCb.js */ "./node_modules/underscore/modules/_optimizeCb.js");
/* harmony import */ var _allKeys_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./allKeys.js */ "./node_modules/underscore/modules/allKeys.js");
/* harmony import */ var _keyInObj_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./_keyInObj.js */ "./node_modules/underscore/modules/_keyInObj.js");
/* harmony import */ var _flatten_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./_flatten.js */ "./node_modules/underscore/modules/_flatten.js");







// Return a copy of the object only containing the allowed properties.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(obj, keys) {
  var result = {}, iteratee = keys[0];
  if (obj == null) return result;
  if ((0,_isFunction_js__WEBPACK_IMPORTED_MODULE_1__["default"])(iteratee)) {
    if (keys.length > 1) iteratee = (0,_optimizeCb_js__WEBPACK_IMPORTED_MODULE_2__["default"])(iteratee, keys[1]);
    keys = (0,_allKeys_js__WEBPACK_IMPORTED_MODULE_3__["default"])(obj);
  } else {
    iteratee = _keyInObj_js__WEBPACK_IMPORTED_MODULE_4__["default"];
    keys = (0,_flatten_js__WEBPACK_IMPORTED_MODULE_5__["default"])(keys, false, false);
    obj = Object(obj);
  }
  for (var i = 0, length = keys.length; i < length; i++) {
    var key = keys[i];
    var value = obj[key];
    if (iteratee(value, key, obj)) result[key] = value;
  }
  return result;
}));


/***/ },

/***/ "./node_modules/underscore/modules/pluck.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/pluck.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ pluck)
/* harmony export */ });
/* harmony import */ var _map_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./map.js */ "./node_modules/underscore/modules/map.js");
/* harmony import */ var _property_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./property.js */ "./node_modules/underscore/modules/property.js");



// Convenience version of a common use case of `_.map`: fetching a property.
function pluck(obj, key) {
  return (0,_map_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj, (0,_property_js__WEBPACK_IMPORTED_MODULE_1__["default"])(key));
}


/***/ },

/***/ "./node_modules/underscore/modules/property.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/property.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ property)
/* harmony export */ });
/* harmony import */ var _deepGet_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_deepGet.js */ "./node_modules/underscore/modules/_deepGet.js");
/* harmony import */ var _toPath_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_toPath.js */ "./node_modules/underscore/modules/_toPath.js");



// Creates a function that, when passed an object, will traverse that object’s
// properties down the given `path`, specified as an array of keys or indices.
function property(path) {
  path = (0,_toPath_js__WEBPACK_IMPORTED_MODULE_1__["default"])(path);
  return function(obj) {
    return (0,_deepGet_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj, path);
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/propertyOf.js"
/*!*******************************************************!*\
  !*** ./node_modules/underscore/modules/propertyOf.js ***!
  \*******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ propertyOf)
/* harmony export */ });
/* harmony import */ var _noop_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./noop.js */ "./node_modules/underscore/modules/noop.js");
/* harmony import */ var _get_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./get.js */ "./node_modules/underscore/modules/get.js");



// Generates a function for a given object that returns a given property.
function propertyOf(obj) {
  if (obj == null) return _noop_js__WEBPACK_IMPORTED_MODULE_0__["default"];
  return function(path) {
    return (0,_get_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj, path);
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/random.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/random.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ random)
/* harmony export */ });
// Return a random integer between `min` and `max` (inclusive).
function random(min, max) {
  if (max == null) {
    max = min;
    min = 0;
  }
  return min + Math.floor(Math.random() * (max - min + 1));
}


/***/ },

/***/ "./node_modules/underscore/modules/range.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/range.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ range)
/* harmony export */ });
// Generate an integer Array containing an arithmetic progression. A port of
// the native Python `range()` function. See
// [the Python documentation](https://docs.python.org/library/functions.html#range).
function range(start, stop, step) {
  if (stop == null) {
    stop = start || 0;
    start = 0;
  }
  if (!step) {
    step = stop < start ? -1 : 1;
  }

  var length = Math.max(Math.ceil((stop - start) / step), 0);
  var range = Array(length);

  for (var idx = 0; idx < length; idx++, start += step) {
    range[idx] = start;
  }

  return range;
}


/***/ },

/***/ "./node_modules/underscore/modules/reduce.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/reduce.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createReduce_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createReduce.js */ "./node_modules/underscore/modules/_createReduce.js");


// **Reduce** builds up a single result from a list of values, aka `inject`,
// or `foldl`.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createReduce_js__WEBPACK_IMPORTED_MODULE_0__["default"])(1));


/***/ },

/***/ "./node_modules/underscore/modules/reduceRight.js"
/*!********************************************************!*\
  !*** ./node_modules/underscore/modules/reduceRight.js ***!
  \********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createReduce_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createReduce.js */ "./node_modules/underscore/modules/_createReduce.js");


// The right-associative version of reduce, also known as `foldr`.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createReduce_js__WEBPACK_IMPORTED_MODULE_0__["default"])(-1));


/***/ },

/***/ "./node_modules/underscore/modules/reject.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/reject.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ reject)
/* harmony export */ });
/* harmony import */ var _filter_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./filter.js */ "./node_modules/underscore/modules/filter.js");
/* harmony import */ var _negate_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./negate.js */ "./node_modules/underscore/modules/negate.js");
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");




// Return all the elements for which a truth test fails.
function reject(obj, predicate, context) {
  return (0,_filter_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj, (0,_negate_js__WEBPACK_IMPORTED_MODULE_1__["default"])((0,_cb_js__WEBPACK_IMPORTED_MODULE_2__["default"])(predicate)), context);
}


/***/ },

/***/ "./node_modules/underscore/modules/rest.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/rest.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ rest)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");


// Returns everything but the first entry of the `array`. Especially useful on
// the `arguments` object. Passing an **n** will return the rest N values in the
// `array`.
function rest(array, n, guard) {
  return _setup_js__WEBPACK_IMPORTED_MODULE_0__.slice.call(array, n == null || guard ? 1 : n);
}


/***/ },

/***/ "./node_modules/underscore/modules/restArguments.js"
/*!**********************************************************!*\
  !*** ./node_modules/underscore/modules/restArguments.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ restArguments)
/* harmony export */ });
// Some functions take a variable number of arguments, or a few expected
// arguments at the beginning and then a variable number of values to operate
// on. This helper accumulates all remaining arguments past the function’s
// argument length (or an explicit `startIndex`), into an array that becomes
// the last argument. Similar to ES6’s "rest parameter".
function restArguments(func, startIndex) {
  startIndex = startIndex == null ? func.length - 1 : +startIndex;
  return function() {
    var length = Math.max(arguments.length - startIndex, 0),
        rest = Array(length),
        index = 0;
    for (; index < length; index++) {
      rest[index] = arguments[index + startIndex];
    }
    switch (startIndex) {
      case 0: return func.call(this, rest);
      case 1: return func.call(this, arguments[0], rest);
      case 2: return func.call(this, arguments[0], arguments[1], rest);
    }
    var args = Array(startIndex + 1);
    for (index = 0; index < startIndex; index++) {
      args[index] = arguments[index];
    }
    args[startIndex] = rest;
    return func.apply(this, args);
  };
}


/***/ },

/***/ "./node_modules/underscore/modules/result.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/result.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ result)
/* harmony export */ });
/* harmony import */ var _isFunction_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isFunction.js */ "./node_modules/underscore/modules/isFunction.js");
/* harmony import */ var _toPath_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_toPath.js */ "./node_modules/underscore/modules/_toPath.js");



// Traverses the children of `obj` along `path`. If a child is a function, it
// is invoked with its parent as context. Returns the value of the final
// child, or `fallback` if any child is undefined.
function result(obj, path, fallback) {
  path = (0,_toPath_js__WEBPACK_IMPORTED_MODULE_1__["default"])(path);
  var length = path.length;
  if (!length) {
    return (0,_isFunction_js__WEBPACK_IMPORTED_MODULE_0__["default"])(fallback) ? fallback.call(obj) : fallback;
  }
  for (var i = 0; i < length; i++) {
    var prop = obj == null ? void 0 : obj[path[i]];
    if (prop === void 0) {
      prop = fallback;
      i = length; // Ensure we don't continue iterating.
    }
    obj = (0,_isFunction_js__WEBPACK_IMPORTED_MODULE_0__["default"])(prop) ? prop.call(obj) : prop;
  }
  return obj;
}


/***/ },

/***/ "./node_modules/underscore/modules/sample.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/sample.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ sample)
/* harmony export */ });
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _values_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./values.js */ "./node_modules/underscore/modules/values.js");
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");
/* harmony import */ var _random_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./random.js */ "./node_modules/underscore/modules/random.js");
/* harmony import */ var _toArray_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./toArray.js */ "./node_modules/underscore/modules/toArray.js");






// Sample **n** random values from a collection using the modern version of the
// [Fisher-Yates shuffle](https://en.wikipedia.org/wiki/Fisher–Yates_shuffle).
// If **n** is not specified, returns a single random element.
// The internal `guard` argument allows it to work with `_.map`.
function sample(obj, n, guard) {
  if (n == null || guard) {
    if (!(0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj)) obj = (0,_values_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj);
    return obj[(0,_random_js__WEBPACK_IMPORTED_MODULE_3__["default"])(obj.length - 1)];
  }
  var sample = (0,_toArray_js__WEBPACK_IMPORTED_MODULE_4__["default"])(obj);
  var length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_2__["default"])(sample);
  n = Math.max(Math.min(n, length), 0);
  var last = length - 1;
  for (var index = 0; index < n; index++) {
    var rand = (0,_random_js__WEBPACK_IMPORTED_MODULE_3__["default"])(index, last);
    var temp = sample[index];
    sample[index] = sample[rand];
    sample[rand] = temp;
  }
  return sample.slice(0, n);
}


/***/ },

/***/ "./node_modules/underscore/modules/shuffle.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/shuffle.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ shuffle)
/* harmony export */ });
/* harmony import */ var _sample_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./sample.js */ "./node_modules/underscore/modules/sample.js");


// Shuffle a collection.
function shuffle(obj) {
  return (0,_sample_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj, Infinity);
}


/***/ },

/***/ "./node_modules/underscore/modules/size.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/size.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ size)
/* harmony export */ });
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");



// Return the number of elements in a collection.
function size(obj) {
  if (obj == null) return 0;
  return (0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj) ? obj.length : (0,_keys_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj).length;
}


/***/ },

/***/ "./node_modules/underscore/modules/some.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/some.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ some)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");




// Determine if at least one element in the object passes a truth test.
function some(obj, predicate, context) {
  predicate = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(predicate, context);
  var _keys = !(0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_1__["default"])(obj) && (0,_keys_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj),
      length = (_keys || obj).length;
  for (var index = 0; index < length; index++) {
    var currentKey = _keys ? _keys[index] : index;
    if (predicate(obj[currentKey], currentKey, obj)) return true;
  }
  return false;
}


/***/ },

/***/ "./node_modules/underscore/modules/sortBy.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/sortBy.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ sortBy)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _pluck_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./pluck.js */ "./node_modules/underscore/modules/pluck.js");
/* harmony import */ var _map_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./map.js */ "./node_modules/underscore/modules/map.js");




// Sort the object's values by a criterion produced by an iteratee.
function sortBy(obj, iteratee, context) {
  var index = 0;
  iteratee = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(iteratee, context);
  return (0,_pluck_js__WEBPACK_IMPORTED_MODULE_1__["default"])((0,_map_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj, function(value, key, list) {
    return {
      value: value,
      index: index++,
      criteria: iteratee(value, key, list)
    };
  }).sort(function(left, right) {
    var a = left.criteria;
    var b = right.criteria;
    if (a !== b) {
      if (a > b || a === void 0) return 1;
      if (a < b || b === void 0) return -1;
    }
    return left.index - right.index;
  }), 'value');
}


/***/ },

/***/ "./node_modules/underscore/modules/sortedIndex.js"
/*!********************************************************!*\
  !*** ./node_modules/underscore/modules/sortedIndex.js ***!
  \********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ sortedIndex)
/* harmony export */ });
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");



// Use a comparator function to figure out the smallest index at which
// an object should be inserted so as to maintain order. Uses binary search.
function sortedIndex(array, obj, iteratee, context) {
  iteratee = (0,_cb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(iteratee, context, 1);
  var value = iteratee(obj);
  var low = 0, high = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_1__["default"])(array);
  while (low < high) {
    var mid = Math.floor((low + high) / 2);
    if (iteratee(array[mid]) < value) low = mid + 1; else high = mid;
  }
  return low;
}


/***/ },

/***/ "./node_modules/underscore/modules/tap.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/tap.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ tap)
/* harmony export */ });
// Invokes `interceptor` with the `obj` and then returns `obj`.
// The primary purpose of this method is to "tap into" a method chain, in
// order to perform operations on intermediate results within the chain.
function tap(obj, interceptor) {
  interceptor(obj);
  return obj;
}


/***/ },

/***/ "./node_modules/underscore/modules/template.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/template.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ template)
/* harmony export */ });
/* harmony import */ var _defaults_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./defaults.js */ "./node_modules/underscore/modules/defaults.js");
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");
/* harmony import */ var _templateSettings_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./templateSettings.js */ "./node_modules/underscore/modules/templateSettings.js");




// When customizing `_.templateSettings`, if you don't want to define an
// interpolation, evaluation or escaping regex, we need one that is
// guaranteed not to match.
var noMatch = /(.)^/;

// Certain characters need to be escaped so that they can be put into a
// string literal.
var escapes = {
  "'": "'",
  '\\': '\\',
  '\r': 'r',
  '\n': 'n',
  '\u2028': 'u2028',
  '\u2029': 'u2029'
};

var escapeRegExp = /\\|'|\r|\n|\u2028|\u2029/g;

function escapeChar(match) {
  return '\\' + escapes[match];
}

// In order to prevent third-party code injection through
// `_.templateSettings.variable`, we test it against the following regular
// expression. It is intentionally a bit more liberal than just matching valid
// identifiers, but still prevents possible loopholes through defaults or
// destructuring assignment.
var bareIdentifier = /^\s*(\w|\$)+\s*$/;

// JavaScript micro-templating, similar to John Resig's implementation.
// Underscore templating handles arbitrary delimiters, preserves whitespace,
// and correctly escapes quotes within interpolated code.
// NB: `oldSettings` only exists for backwards compatibility.
function template(text, settings, oldSettings) {
  if (!settings && oldSettings) settings = oldSettings;
  settings = (0,_defaults_js__WEBPACK_IMPORTED_MODULE_0__["default"])({}, settings, _underscore_js__WEBPACK_IMPORTED_MODULE_1__["default"].templateSettings);

  // Combine delimiters into one regular expression via alternation.
  var matcher = RegExp([
    (settings.escape || noMatch).source,
    (settings.interpolate || noMatch).source,
    (settings.evaluate || noMatch).source
  ].join('|') + '|$', 'g');

  // Compile the template source, escaping string literals appropriately.
  var index = 0;
  var source = "__p+='";
  text.replace(matcher, function(match, escape, interpolate, evaluate, offset) {
    source += text.slice(index, offset).replace(escapeRegExp, escapeChar);
    index = offset + match.length;

    if (escape) {
      source += "'+\n((__t=(" + escape + "))==null?'':_.escape(__t))+\n'";
    } else if (interpolate) {
      source += "'+\n((__t=(" + interpolate + "))==null?'':__t)+\n'";
    } else if (evaluate) {
      source += "';\n" + evaluate + "\n__p+='";
    }

    // Adobe VMs need the match returned to produce the correct offset.
    return match;
  });
  source += "';\n";

  var argument = settings.variable;
  if (argument) {
    // Insure against third-party code injection. (CVE-2021-23358)
    if (!bareIdentifier.test(argument)) throw new Error(
      'variable is not a bare identifier: ' + argument
    );
  } else {
    // If a variable is not specified, place data values in local scope.
    source = 'with(obj||{}){\n' + source + '}\n';
    argument = 'obj';
  }

  source = "var __t,__p='',__j=Array.prototype.join," +
    "print=function(){__p+=__j.call(arguments,'');};\n" +
    source + 'return __p;\n';

  var render;
  try {
    render = new Function(argument, '_', source);
  } catch (e) {
    e.source = source;
    throw e;
  }

  var template = function(data) {
    return render.call(this, data, _underscore_js__WEBPACK_IMPORTED_MODULE_1__["default"]);
  };

  // Provide the compiled source as a convenience for precompilation.
  template.source = 'function(' + argument + '){\n' + source + '}';

  return template;
}


/***/ },

/***/ "./node_modules/underscore/modules/templateSettings.js"
/*!*************************************************************!*\
  !*** ./node_modules/underscore/modules/templateSettings.js ***!
  \*************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");


// By default, Underscore uses ERB-style template delimiters. Change the
// following template settings to use alternative delimiters.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].templateSettings = {
  evaluate: /<%([\s\S]+?)%>/g,
  interpolate: /<%=([\s\S]+?)%>/g,
  escape: /<%-([\s\S]+?)%>/g
});


/***/ },

/***/ "./node_modules/underscore/modules/throttle.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/throttle.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ throttle)
/* harmony export */ });
/* harmony import */ var _now_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./now.js */ "./node_modules/underscore/modules/now.js");


// Returns a function, that, when invoked, will only be triggered at most once
// during a given window of time. Normally, the throttled function will run
// as much as it can, without ever going more than once per `wait` duration;
// but if you'd like to disable the execution on the leading edge, pass
// `{leading: false}`. To disable execution on the trailing edge, ditto.
function throttle(func, wait, options) {
  var timeout, context, args, result;
  var previous = 0;
  if (!options) options = {};

  var later = function() {
    previous = options.leading === false ? 0 : (0,_now_js__WEBPACK_IMPORTED_MODULE_0__["default"])();
    timeout = null;
    result = func.apply(context, args);
    if (!timeout) context = args = null;
  };

  var throttled = function() {
    var _now = (0,_now_js__WEBPACK_IMPORTED_MODULE_0__["default"])();
    if (!previous && options.leading === false) previous = _now;
    var remaining = wait - (_now - previous);
    context = this;
    args = arguments;
    if (remaining <= 0 || remaining > wait) {
      if (timeout) {
        clearTimeout(timeout);
        timeout = null;
      }
      previous = _now;
      result = func.apply(context, args);
      if (!timeout) context = args = null;
    } else if (!timeout && options.trailing !== false) {
      timeout = setTimeout(later, remaining);
    }
    return result;
  };

  throttled.cancel = function() {
    clearTimeout(timeout);
    previous = 0;
    timeout = context = args = null;
  };

  return throttled;
}


/***/ },

/***/ "./node_modules/underscore/modules/times.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/times.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ times)
/* harmony export */ });
/* harmony import */ var _optimizeCb_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_optimizeCb.js */ "./node_modules/underscore/modules/_optimizeCb.js");


// Run a function **n** times.
function times(n, iteratee, context) {
  var accum = Array(Math.max(0, n));
  iteratee = (0,_optimizeCb_js__WEBPACK_IMPORTED_MODULE_0__["default"])(iteratee, context, 1);
  for (var i = 0; i < n; i++) accum[i] = iteratee(i);
  return accum;
}


/***/ },

/***/ "./node_modules/underscore/modules/toArray.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/toArray.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toArray)
/* harmony export */ });
/* harmony import */ var _isArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isArray.js */ "./node_modules/underscore/modules/isArray.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _isString_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./isString.js */ "./node_modules/underscore/modules/isString.js");
/* harmony import */ var _isArrayLike_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./_isArrayLike.js */ "./node_modules/underscore/modules/_isArrayLike.js");
/* harmony import */ var _map_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./map.js */ "./node_modules/underscore/modules/map.js");
/* harmony import */ var _identity_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./identity.js */ "./node_modules/underscore/modules/identity.js");
/* harmony import */ var _values_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./values.js */ "./node_modules/underscore/modules/values.js");








// Safely create a real, live array from anything iterable.
var reStrSymbol = /[^\ud800-\udfff]|[\ud800-\udbff][\udc00-\udfff]|[\ud800-\udfff]/g;
function toArray(obj) {
  if (!obj) return [];
  if ((0,_isArray_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj)) return _setup_js__WEBPACK_IMPORTED_MODULE_1__.slice.call(obj);
  if ((0,_isString_js__WEBPACK_IMPORTED_MODULE_2__["default"])(obj)) {
    // Keep surrogate pair characters together.
    return obj.match(reStrSymbol);
  }
  if ((0,_isArrayLike_js__WEBPACK_IMPORTED_MODULE_3__["default"])(obj)) return (0,_map_js__WEBPACK_IMPORTED_MODULE_4__["default"])(obj, _identity_js__WEBPACK_IMPORTED_MODULE_5__["default"]);
  return (0,_values_js__WEBPACK_IMPORTED_MODULE_6__["default"])(obj);
}


/***/ },

/***/ "./node_modules/underscore/modules/toPath.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/toPath.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPath)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");
/* harmony import */ var _isArray_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isArray.js */ "./node_modules/underscore/modules/isArray.js");



// Normalize a (deep) property `path` to array.
// Like `_.iteratee`, this function can be customized.
function toPath(path) {
  return (0,_isArray_js__WEBPACK_IMPORTED_MODULE_1__["default"])(path) ? path : [path];
}
_underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].toPath = toPath;


/***/ },

/***/ "./node_modules/underscore/modules/underscore-array-methods.js"
/*!*********************************************************************!*\
  !*** ./node_modules/underscore/modules/underscore-array-methods.js ***!
  \*********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./underscore.js */ "./node_modules/underscore/modules/underscore.js");
/* harmony import */ var _each_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./each.js */ "./node_modules/underscore/modules/each.js");
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");
/* harmony import */ var _chainResult_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./_chainResult.js */ "./node_modules/underscore/modules/_chainResult.js");





// Add all mutator `Array` functions to the wrapper.
(0,_each_js__WEBPACK_IMPORTED_MODULE_1__["default"])(['pop', 'push', 'reverse', 'shift', 'sort', 'splice', 'unshift'], function(name) {
  var method = _setup_js__WEBPACK_IMPORTED_MODULE_2__.ArrayProto[name];
  _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].prototype[name] = function() {
    var obj = this._wrapped;
    if (obj != null) {
      method.apply(obj, arguments);
      if ((name === 'shift' || name === 'splice') && obj.length === 0) {
        delete obj[0];
      }
    }
    return (0,_chainResult_js__WEBPACK_IMPORTED_MODULE_3__["default"])(this, obj);
  };
});

// Add all accessor `Array` functions to the wrapper.
(0,_each_js__WEBPACK_IMPORTED_MODULE_1__["default"])(['concat', 'join', 'slice'], function(name) {
  var method = _setup_js__WEBPACK_IMPORTED_MODULE_2__.ArrayProto[name];
  _underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"].prototype[name] = function() {
    var obj = this._wrapped;
    if (obj != null) obj = method.apply(obj, arguments);
    return (0,_chainResult_js__WEBPACK_IMPORTED_MODULE_3__["default"])(this, obj);
  };
});

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_underscore_js__WEBPACK_IMPORTED_MODULE_0__["default"]);


/***/ },

/***/ "./node_modules/underscore/modules/underscore.js"
/*!*******************************************************!*\
  !*** ./node_modules/underscore/modules/underscore.js ***!
  \*******************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _)
/* harmony export */ });
/* harmony import */ var _setup_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_setup.js */ "./node_modules/underscore/modules/_setup.js");


// If Underscore is called as a function, it returns a wrapped object that can
// be used OO-style. This wrapper holds altered versions of all functions added
// through `_.mixin`. Wrapped objects may be chained.
function _(obj) {
  if (obj instanceof _) return obj;
  if (!(this instanceof _)) return new _(obj);
  this._wrapped = obj;
}

_.VERSION = _setup_js__WEBPACK_IMPORTED_MODULE_0__.VERSION;

// Extracts the result from a wrapped and chained object.
_.prototype.value = function() {
  return this._wrapped;
};

// Provide unwrapping proxies for some methods used in engine operations
// such as arithmetic and JSON stringification.
_.prototype.valueOf = _.prototype.toJSON = _.prototype.value;

_.prototype.toString = function() {
  return String(this._wrapped);
};


/***/ },

/***/ "./node_modules/underscore/modules/unescape.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/unescape.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _createEscaper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_createEscaper.js */ "./node_modules/underscore/modules/_createEscaper.js");
/* harmony import */ var _unescapeMap_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_unescapeMap.js */ "./node_modules/underscore/modules/_unescapeMap.js");



// Function for unescaping strings from HTML interpolation.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_createEscaper_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_unescapeMap_js__WEBPACK_IMPORTED_MODULE_1__["default"]));


/***/ },

/***/ "./node_modules/underscore/modules/union.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/union.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _uniq_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./uniq.js */ "./node_modules/underscore/modules/uniq.js");
/* harmony import */ var _flatten_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_flatten.js */ "./node_modules/underscore/modules/_flatten.js");




// Produce an array that contains the union: each distinct element from all of
// the passed-in arrays.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(arrays) {
  return (0,_uniq_js__WEBPACK_IMPORTED_MODULE_1__["default"])((0,_flatten_js__WEBPACK_IMPORTED_MODULE_2__["default"])(arrays, true, true));
}));


/***/ },

/***/ "./node_modules/underscore/modules/uniq.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/uniq.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ uniq)
/* harmony export */ });
/* harmony import */ var _isBoolean_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isBoolean.js */ "./node_modules/underscore/modules/isBoolean.js");
/* harmony import */ var _cb_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_cb.js */ "./node_modules/underscore/modules/_cb.js");
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./contains.js */ "./node_modules/underscore/modules/contains.js");





// Produce a duplicate-free version of the array. If the array has already
// been sorted, you have the option of using a faster algorithm.
// The faster algorithm will not work with an iteratee if the iteratee
// is not a one-to-one function, so providing an iteratee will disable
// the faster algorithm.
function uniq(array, isSorted, iteratee, context) {
  if (!(0,_isBoolean_js__WEBPACK_IMPORTED_MODULE_0__["default"])(isSorted)) {
    context = iteratee;
    iteratee = isSorted;
    isSorted = false;
  }
  if (iteratee != null) iteratee = (0,_cb_js__WEBPACK_IMPORTED_MODULE_1__["default"])(iteratee, context);
  var result = [];
  var seen = [];
  for (var i = 0, length = (0,_getLength_js__WEBPACK_IMPORTED_MODULE_2__["default"])(array); i < length; i++) {
    var value = array[i],
        computed = iteratee ? iteratee(value, i, array) : value;
    if (isSorted && !iteratee) {
      if (!i || seen !== computed) result.push(value);
      seen = computed;
    } else if (iteratee) {
      if (!(0,_contains_js__WEBPACK_IMPORTED_MODULE_3__["default"])(seen, computed)) {
        seen.push(computed);
        result.push(value);
      }
    } else if (!(0,_contains_js__WEBPACK_IMPORTED_MODULE_3__["default"])(result, value)) {
      result.push(value);
    }
  }
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/uniqueId.js"
/*!*****************************************************!*\
  !*** ./node_modules/underscore/modules/uniqueId.js ***!
  \*****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ uniqueId)
/* harmony export */ });
// Generate a unique integer id (unique within the entire client session).
// Useful for temporary DOM ids.
var idCounter = 0;
function uniqueId(prefix) {
  var id = ++idCounter + '';
  return prefix ? prefix + id : id;
}


/***/ },

/***/ "./node_modules/underscore/modules/unzip.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/unzip.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ unzip)
/* harmony export */ });
/* harmony import */ var _max_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./max.js */ "./node_modules/underscore/modules/max.js");
/* harmony import */ var _getLength_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./_getLength.js */ "./node_modules/underscore/modules/_getLength.js");
/* harmony import */ var _pluck_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./pluck.js */ "./node_modules/underscore/modules/pluck.js");




// Complement of zip. Unzip accepts an array of arrays and groups
// each array's elements on shared indices.
function unzip(array) {
  var length = (array && (0,_max_js__WEBPACK_IMPORTED_MODULE_0__["default"])(array, _getLength_js__WEBPACK_IMPORTED_MODULE_1__["default"]).length) || 0;
  var result = Array(length);

  for (var index = 0; index < length; index++) {
    result[index] = (0,_pluck_js__WEBPACK_IMPORTED_MODULE_2__["default"])(array, index);
  }
  return result;
}


/***/ },

/***/ "./node_modules/underscore/modules/values.js"
/*!***************************************************!*\
  !*** ./node_modules/underscore/modules/values.js ***!
  \***************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ values)
/* harmony export */ });
/* harmony import */ var _keys_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./keys.js */ "./node_modules/underscore/modules/keys.js");


// Retrieve the values of an object's properties.
function values(obj) {
  var _keys = (0,_keys_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj);
  var length = _keys.length;
  var values = Array(length);
  for (var i = 0; i < length; i++) {
    values[i] = obj[_keys[i]];
  }
  return values;
}


/***/ },

/***/ "./node_modules/underscore/modules/where.js"
/*!**************************************************!*\
  !*** ./node_modules/underscore/modules/where.js ***!
  \**************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ where)
/* harmony export */ });
/* harmony import */ var _filter_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./filter.js */ "./node_modules/underscore/modules/filter.js");
/* harmony import */ var _matcher_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./matcher.js */ "./node_modules/underscore/modules/matcher.js");



// Convenience version of a common use case of `_.filter`: selecting only
// objects containing specific `key:value` pairs.
function where(obj, attrs) {
  return (0,_filter_js__WEBPACK_IMPORTED_MODULE_0__["default"])(obj, (0,_matcher_js__WEBPACK_IMPORTED_MODULE_1__["default"])(attrs));
}


/***/ },

/***/ "./node_modules/underscore/modules/without.js"
/*!****************************************************!*\
  !*** ./node_modules/underscore/modules/without.js ***!
  \****************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _difference_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./difference.js */ "./node_modules/underscore/modules/difference.js");



// Return a version of the array that does not contain the specified value(s).
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(function(array, otherArrays) {
  return (0,_difference_js__WEBPACK_IMPORTED_MODULE_1__["default"])(array, otherArrays);
}));


/***/ },

/***/ "./node_modules/underscore/modules/wrap.js"
/*!*************************************************!*\
  !*** ./node_modules/underscore/modules/wrap.js ***!
  \*************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ wrap)
/* harmony export */ });
/* harmony import */ var _partial_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./partial.js */ "./node_modules/underscore/modules/partial.js");


// Returns the first function passed as an argument to the second,
// allowing you to adjust arguments, run code before and after, and
// conditionally execute the original function.
function wrap(func, wrapper) {
  return (0,_partial_js__WEBPACK_IMPORTED_MODULE_0__["default"])(wrapper, func);
}


/***/ },

/***/ "./node_modules/underscore/modules/zip.js"
/*!************************************************!*\
  !*** ./node_modules/underscore/modules/zip.js ***!
  \************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _restArguments_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./restArguments.js */ "./node_modules/underscore/modules/restArguments.js");
/* harmony import */ var _unzip_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./unzip.js */ "./node_modules/underscore/modules/unzip.js");



// Zip together multiple lists into a single array -- elements that share
// an index go together.
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_restArguments_js__WEBPACK_IMPORTED_MODULE_0__["default"])(_unzip_js__WEBPACK_IMPORTED_MODULE_1__["default"]));


/***/ }

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
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
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
/******/ 				var [chunkIds, fn, priority] = deferred[i];
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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"core_src_components_LegacyDialogPrompt_vue":"4fee177b202a7d3a61a6","node_modules_nextcloud_dialogs_dist_chunks_preview-BIbJGxXF_mjs-node_modules_nextcloud_dialog-7546cc":"dce7060a8340cecf2ca5","node_modules_nextcloud_dialogs_dist_chunks_ConflictPicker-CWBf0soh_mjs":"01a2e7bc2c49db839239","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcTextField_index_mjs":"50270bc67122ae47dfd0","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-C1yRZfLt_mjs":"a3985d66705012167d75","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-7_GNN76e_mjs":"b2479474dfc9749ea9d0","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcColorPicker_index_mjs":"63766ea64d27a6d8d6cc","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcDateTimePicker_in-952ddb":"bd2fc411cc830fe12f0b"}[chunkId] + "";
/******/ 		};
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
/******/ 		var dataWebpackPrefix = "nextcloud-ui-legacy:";
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
/******/ 		if (globalThis.importScripts) scriptUrl = globalThis.location + "";
/******/ 		var document = globalThis.document;
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
/******/ 		__webpack_require__.b = (typeof document !== 'undefined' && document.baseURI) || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"core-login": 0
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
/******/ 			var [chunkIds, moreModules, runtime] = data;
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
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunknextcloud_ui_legacy"] = globalThis["webpackChunknextcloud_ui_legacy"] || [];
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./core/src/login.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=core-login.js.map?v=f9eae71e526ea2dd04b8