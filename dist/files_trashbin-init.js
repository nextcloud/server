/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files/src/logger.ts":
/*!**********************************!*\
  !*** ./apps/files/src/logger.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files').detectUser().build());

/***/ }),

/***/ "./apps/files_trashbin/src/files-init.ts":
/*!***********************************************!*\
  !*** ./apps/files_trashbin/src/files-init.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _files_actions_restoreAction_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./files_actions/restoreAction.ts */ "./apps/files_trashbin/src/files_actions/restoreAction.ts");
/* harmony import */ var _files_listActions_emptyTrashAction_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./files_listActions/emptyTrashAction.ts */ "./apps/files_trashbin/src/files_listActions/emptyTrashAction.ts");
/* harmony import */ var _files_views_trashbinView_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./files_views/trashbinView.ts */ "./apps/files_trashbin/src/files_views/trashbinView.ts");
/* harmony import */ var _trashbin_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./trashbin.scss */ "./apps/files_trashbin/src/trashbin.scss");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getNavigation)();
Navigation.register(_files_views_trashbinView_ts__WEBPACK_IMPORTED_MODULE_3__.trashbinView);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileListAction)(_files_listActions_emptyTrashAction_ts__WEBPACK_IMPORTED_MODULE_2__.emptyTrashAction);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_files_actions_restoreAction_ts__WEBPACK_IMPORTED_MODULE_1__.restoreAction);

/***/ }),

/***/ "./apps/files_trashbin/src/files_actions/restoreAction.ts":
/*!****************************************************************!*\
  !*** ./apps/files_trashbin/src/files_actions/restoreAction.ts ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   restoreAction: () => (/* binding */ restoreAction)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_history_svg_raw__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @mdi/svg/svg/history.svg?raw */ "./node_modules/@mdi/svg/svg/history.svg?raw");
/* harmony import */ var _files_views_trashbinView_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../files_views/trashbinView.ts */ "./apps/files_trashbin/src/files_views/trashbinView.ts");
/* harmony import */ var _files_src_logger_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../../files/src/logger.ts */ "./apps/files/src/logger.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */











const restoreAction = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileAction({
  id: 'restore',
  displayName() {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_trashbin', 'Restore');
  },
  iconSvgInline: () => _mdi_svg_svg_history_svg_raw__WEBPACK_IMPORTED_MODULE_8__,
  enabled(nodes, view) {
    // Only available in the trashbin view
    if (view.id !== _files_views_trashbinView_ts__WEBPACK_IMPORTED_MODULE_9__.TRASHBIN_VIEW_ID) {
      return false;
    }
    // Only available if all nodes have read permission
    return nodes.length > 0 && nodes.map(node => node.permissions).every(permission => Boolean(permission & _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Permission.READ));
  },
  async exec(node) {
    try {
      const destination = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateRemoteUrl)((0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_5__.encodePath)(`dav/trashbin/${(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid}/restore/${node.basename}`));
      await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_7__["default"].request({
        method: 'MOVE',
        url: node.encodedSource,
        headers: {
          destination
        }
      });
      // Let's pretend the file is deleted since
      // we don't know the restored location
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:deleted', node);
      return true;
    } catch (error) {
      if (error.response?.status === 507) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_trashbin', 'Not enough free space to restore the file/folder'));
      }
      _files_src_logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error('Failed to restore node', {
        error,
        node
      });
      return false;
    }
  },
  async execBatch(nodes, view, dir) {
    return Promise.all(nodes.map(node => this.exec(node, view, dir)));
  },
  order: 1,
  inline: () => true
});

/***/ }),

/***/ "./apps/files_trashbin/src/files_listActions/emptyTrashAction.ts":
/*!***********************************************************************!*\
  !*** ./apps/files_trashbin/src/files_listActions/emptyTrashAction.ts ***!
  \***********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   emptyTrashAction: () => (/* binding */ emptyTrashAction)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _services_api_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/api.ts */ "./apps/files_trashbin/src/services/api.ts");
/* harmony import */ var _files_views_trashbinView_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../files_views/trashbinView.ts */ "./apps/files_trashbin/src/files_views/trashbinView.ts");







const emptyTrashAction = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileListAction({
  id: 'empty-trash',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_trashbin', 'Empty deleted files'),
  order: 0,
  enabled(view, nodes, folder) {
    if (view.id !== _files_views_trashbinView_ts__WEBPACK_IMPORTED_MODULE_6__.TRASHBIN_VIEW_ID) {
      return false;
    }
    const config = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files_trashbin', 'config');
    if (!config.allow_delete) {
      return false;
    }
    return nodes.length > 0 && folder.path === '/';
  },
  async exec(view, nodes) {
    const askConfirmation = new Promise(resolve => {
      const dialog = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.getDialogBuilder)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_trashbin', 'Confirm permanent deletion')).setSeverity(_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.DialogSeverity.Warning)
      // TODO Add note for groupfolders
      .setText((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_trashbin', 'Are you sure you want to permanently delete all files and folders in the trash? This cannot be undone.')).setButtons([{
        label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_trashbin', 'Cancel'),
        type: 'secondary',
        callback: () => resolve(false)
      }, {
        label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_trashbin', 'Empty deleted files'),
        type: 'error',
        callback: () => resolve(true)
      }]).build();
      dialog.show().then(() => {
        resolve(false);
      });
    });
    const result = await askConfirmation;
    if (result === true) {
      if (await (0,_services_api_ts__WEBPACK_IMPORTED_MODULE_5__.emptyTrash)()) {
        nodes.forEach(node => (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:deleted', node));
      }
      return null;
    }
    return null;
  }
});

/***/ }),

/***/ "./apps/files_trashbin/src/files_views/columns.ts":
/*!********************************************************!*\
  !*** ./apps/files_trashbin/src/files_views/columns.ts ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   deleted: () => (/* binding */ deleted),
/* harmony export */   deletedBy: () => (/* binding */ deletedBy),
/* harmony export */   originalLocation: () => (/* binding */ originalLocation)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcUserBubble__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcUserBubble */ "./node_modules/@nextcloud/vue/dist/Components/NcUserBubble.mjs");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






const originalLocation = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Column({
  id: 'files_trashbin--original-location',
  title: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files_trashbin', 'Original location'),
  render(node) {
    const originalLocation = parseOriginalLocation(node);
    const span = document.createElement('span');
    span.title = originalLocation;
    span.textContent = originalLocation;
    return span;
  },
  sort(nodeA, nodeB) {
    const locationA = parseOriginalLocation(nodeA);
    const locationB = parseOriginalLocation(nodeB);
    return locationA.localeCompare(locationB, [(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.getLanguage)(), (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.getCanonicalLocale)()], {
      numeric: true,
      usage: 'sort'
    });
  }
});
const deletedBy = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Column({
  id: 'files_trashbin--deleted-by',
  title: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files_trashbin', 'Deleted by'),
  render(node) {
    const {
      userId,
      displayName,
      label
    } = parseDeletedBy(node);
    if (label) {
      const span = document.createElement('span');
      span.textContent = label;
      return span;
    }
    const UserBubble = vue__WEBPACK_IMPORTED_MODULE_4__["default"].extend(_nextcloud_vue_components_NcUserBubble__WEBPACK_IMPORTED_MODULE_5__["default"]);
    const propsData = {
      size: 32,
      user: userId ?? undefined,
      displayName: displayName ?? userId
    };
    const userBubble = new UserBubble({
      propsData
    }).$mount().$el;
    return userBubble;
  },
  sort(nodeA, nodeB) {
    const deletedByA = parseDeletedBy(nodeA);
    const deletedbyALabel = deletedByA.label ?? deletedByA.displayName ?? deletedByA.userId;
    const deletedByB = parseDeletedBy(nodeB);
    const deletedByBLabel = deletedByB.label ?? deletedByB.displayName ?? deletedByB.userId;
    // label is set if uid and display name are unset - if label is unset at least uid or display name is set.
    return deletedbyALabel.localeCompare(deletedByBLabel, [(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.getLanguage)(), (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.getCanonicalLocale)()], {
      numeric: true,
      usage: 'sort'
    });
  }
});
const deleted = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Column({
  id: 'files_trashbin--deleted',
  title: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files_trashbin', 'Deleted'),
  render(node) {
    const deletionTime = node.attributes?.['trashbin-deletion-time'] || (node?.mtime?.getTime() ?? 0) / 1000;
    const span = document.createElement('span');
    if (deletionTime) {
      const formatter = Intl.DateTimeFormat([(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.getCanonicalLocale)()], {
        dateStyle: 'long',
        timeStyle: 'short'
      });
      const timestamp = new Date(deletionTime * 1000);
      span.title = formatter.format(timestamp);
      span.textContent = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.formatRelativeTime)(timestamp, {
        ignoreSeconds: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'few seconds ago')
      });
      return span;
    }
    // Unknown deletion time
    span.textContent = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files_trashbin', 'A long time ago');
    return span;
  },
  sort(nodeA, nodeB) {
    // deletion time is a unix timestamp while mtime is a JS Date -> we need to align the numbers (seconds vs milliseconds)
    const deletionTimeA = nodeA.attributes?.['trashbin-deletion-time'] || (nodeA?.mtime?.getTime() ?? 0) / 1000;
    const deletionTimeB = nodeB.attributes?.['trashbin-deletion-time'] || (nodeB?.mtime?.getTime() ?? 0) / 1000;
    return deletionTimeB - deletionTimeA;
  }
});
/**
 * Get the original file location of a trashbin file.
 *
 * @param node The node to parse
 */
function parseOriginalLocation(node) {
  const path = stringOrNull(node.attributes?.['trashbin-original-location']);
  if (!path) {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files_trashbin', 'Unknown');
  }
  const dir = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_3__.dirname)(path);
  if (dir === path) {
    // Node is in root folder
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files_trashbin', 'All files');
  }
  return dir.replace(/^\//, '');
}
/**
 * Parse a trashbin file to get information about the user that deleted the file.
 *
 * @param node The node to parse
 */
function parseDeletedBy(node) {
  const userId = stringOrNull(node.attributes?.['trashbin-deleted-by-id']);
  const displayName = stringOrNull(node.attributes?.['trashbin-deleted-by-display-name']);
  let label;
  const currentUserId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid;
  if (userId === currentUserId) {
    label = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files_trashbin', 'You');
  }
  if (!userId && !displayName) {
    label = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files_trashbin', 'Unknown');
  }
  return {
    userId,
    displayName,
    label
  };
}
/**
 * If the attribute is given it will be stringified and returned - otherwise null is returned.
 *
 * @param attribute The attribute to check
 */
function stringOrNull(attribute) {
  if (attribute) {
    return String(attribute);
  }
  return null;
}

/***/ }),

/***/ "./apps/files_trashbin/src/files_views/trashbinView.ts":
/*!*************************************************************!*\
  !*** ./apps/files_trashbin/src/files_views/trashbinView.ts ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   TRASHBIN_VIEW_ID: () => (/* binding */ TRASHBIN_VIEW_ID),
/* harmony export */   trashbinView: () => (/* binding */ trashbinView)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _columns_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./columns.ts */ "./apps/files_trashbin/src/files_views/columns.ts");
/* harmony import */ var _services_trashbin_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/trashbin.ts */ "./apps/files_trashbin/src/services/trashbin.ts");
/* harmony import */ var _mdi_svg_svg_trash_can_outline_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/trash-can-outline.svg?raw */ "./node_modules/@mdi/svg/svg/trash-can-outline.svg?raw");
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





const TRASHBIN_VIEW_ID = 'trashbin';
const trashbinView = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.View({
  id: TRASHBIN_VIEW_ID,
  name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files_trashbin', 'Deleted files'),
  caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files_trashbin', 'List of files that have been deleted.'),
  emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files_trashbin', 'No deleted files'),
  emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files_trashbin', 'Files and folders you have deleted will show up here'),
  icon: _mdi_svg_svg_trash_can_outline_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
  order: 50,
  sticky: true,
  defaultSortKey: 'deleted',
  columns: [_columns_ts__WEBPACK_IMPORTED_MODULE_2__.originalLocation, _columns_ts__WEBPACK_IMPORTED_MODULE_2__.deletedBy, _columns_ts__WEBPACK_IMPORTED_MODULE_2__.deleted],
  getContents: _services_trashbin_ts__WEBPACK_IMPORTED_MODULE_3__.getContents
});

/***/ }),

/***/ "./apps/files_trashbin/src/logger.ts":
/*!*******************************************!*\
  !*** ./apps/files_trashbin/src/logger.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   logger: () => (/* binding */ logger)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files_trashbin').detectUser().build();

/***/ }),

/***/ "./apps/files_trashbin/src/services/api.ts":
/*!*************************************************!*\
  !*** ./apps/files_trashbin/src/services/api.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   emptyTrash: () => (/* binding */ emptyTrash)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger.ts */ "./apps/files_trashbin/src/logger.ts");
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






/**
 * Send API request to empty the trashbin.
 * Returns true if request succeeded - otherwise false is returned.
 */
async function emptyTrash() {
  try {
    await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__["default"].delete(`${_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.defaultRemoteURL}/trashbin/${(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid}/trash`);
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_trashbin', 'All files have been permanently deleted'));
    return true;
  } catch (error) {
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_trashbin', 'Failed to empty deleted files'));
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__.logger.error('Failed to empty deleted files', {
      error
    });
    return false;
  }
}

/***/ }),

/***/ "./apps/files_trashbin/src/services/client.ts":
/*!****************************************************!*\
  !*** ./apps/files_trashbin/src/services/client.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   client: () => (/* binding */ client),
/* harmony export */   rootPath: () => (/* binding */ rootPath)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


// init webdav client
const rootPath = `/trashbin/${(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid}/trash`;
const client = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetClient)();

/***/ }),

/***/ "./apps/files_trashbin/src/services/trashbin.ts":
/*!******************************************************!*\
  !*** ./apps/files_trashbin/src/services/trashbin.ts ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _client__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./client */ "./apps/files_trashbin/src/services/client.ts");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");



const data = `<?xml version="1.0"?>
<d:propfind ${(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getDavNameSpaces)()}>
	<d:prop>
		<nc:trashbin-deletion-time />
		<nc:trashbin-original-location />
		<nc:trashbin-title />
		<nc:trashbin-deleted-by-id />
		<nc:trashbin-deleted-by-display-name />
		${(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getDavProperties)()}
	</d:prop>
</d:propfind>`;
const resultToNode = stat => {
  const node = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davResultToNode)(stat, _client__WEBPACK_IMPORTED_MODULE_1__.rootPath);
  node.attributes.previewUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/files_trashbin/preview?fileId={fileid}&x=32&y=32', {
    fileid: node.fileid
  });
  return node;
};
const getContents = async function () {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  const contentsResponse = await _client__WEBPACK_IMPORTED_MODULE_1__.client.getDirectoryContents(`${_client__WEBPACK_IMPORTED_MODULE_1__.rootPath}${path}`, {
    details: true,
    data,
    includeSelf: true
  });
  const contents = contentsResponse.data.map(resultToNode);
  const [folder] = contents.splice(contents.findIndex(node => node.path === path), 1);
  return {
    folder: folder,
    contents
  };
};

/***/ }),

/***/ "./apps/files_trashbin/src/trashbin.scss":
/*!***********************************************!*\
  !*** ./apps/files_trashbin/src/trashbin.scss ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trashbin_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/sass-loader/dist/cjs.js!./trashbin.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_trashbin/src/trashbin.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trashbin_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trashbin_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trashbin_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trashbin_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/@mdi/svg/svg/history.svg?raw":
/*!***************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/history.svg?raw ***!
  \***************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-history\" viewBox=\"0 0 24 24\"><path d=\"M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3\" /></svg>";

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_trashbin/src/trashbin.scss":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_trashbin/src/trashbin.scss ***!
  \****************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

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
___CSS_LOADER_EXPORT___.push([module.id, `/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
.files-list__row-trashbin-original-location {
  width: 150px !important;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


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
/******/ 			"files_trashbin-init": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files_trashbin/src/files-init.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files_trashbin-init.js.map?v=058c80fd2c8a3a4baaf2