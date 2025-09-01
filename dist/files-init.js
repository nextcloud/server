/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files/src/actions/convertAction.ts":
/*!*************************************************!*\
  !*** ./apps/files/src/actions/convertAction.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_CONVERT: () => (/* binding */ ACTION_CONVERT),
/* harmony export */   generateIconSvg: () => (/* binding */ generateIconSvg),
/* harmony export */   registerConvertActions: () => (/* binding */ registerConvertActions)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_autorenew_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/autorenew.svg?raw */ "./node_modules/@mdi/svg/svg/autorenew.svg?raw");
/* harmony import */ var _convertUtils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./convertUtils */ "./apps/files/src/actions/convertUtils.ts");






const ACTION_CONVERT = 'convert';
const registerConvertActions = () => {
  // Generate sub actions
  const convertProviders = (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)()?.files?.file_conversions ?? [];
  const actions = convertProviders.map(_ref => {
    let {
      to,
      from,
      displayName
    } = _ref;
    return new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
      id: `convert-${from}-${to}`,
      displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Save as {displayName}', {
        displayName
      }),
      iconSvgInline: () => generateIconSvg(to),
      enabled: nodes => {
        // Check that all nodes have the same mime type
        return nodes.every(node => from === node.mime);
      },
      async exec(node) {
        // If we're here, we know that the node has a fileid
        (0,_convertUtils__WEBPACK_IMPORTED_MODULE_5__.convertFile)(node.fileid, to);
        // Silently terminate, we'll handle the UI in the background
        return null;
      },
      async execBatch(nodes) {
        const fileIds = nodes.map(node => node.fileid).filter(Boolean);
        (0,_convertUtils__WEBPACK_IMPORTED_MODULE_5__.convertFiles)(fileIds, to);
        // Silently terminate, we'll handle the UI in the background
        return Array(nodes.length).fill(null);
      },
      parent: ACTION_CONVERT
    });
  });
  // Register main action
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
    id: ACTION_CONVERT,
    displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Save as …'),
    iconSvgInline: () => _mdi_svg_svg_autorenew_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
    enabled: (nodes, view) => {
      return actions.some(action => action.enabled(nodes, view));
    },
    async exec() {
      return null;
    },
    order: 25
  }));
  // Register sub actions
  actions.forEach(_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction);
};
const generateIconSvg = mime => {
  // Generate icon based on mime type
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/core/mimeicon?mime=' + encodeURIComponent(mime));
  return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg">
		<image href="${url}" height="32" width="32" />
	</svg>`;
};

/***/ }),

/***/ "./apps/files/src/actions/convertUtils.ts":
/*!************************************************!*\
  !*** ./apps/files/src/actions/convertUtils.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   convertFile: () => (/* binding */ convertFile),
/* harmony export */   convertFiles: () => (/* binding */ convertFiles)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../services/WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../logger */ "./apps/files/src/logger.ts");








const queue = new p_queue__WEBPACK_IMPORTED_MODULE_5__["default"]({
  concurrency: 5
});
const requestConversion = function (fileId, targetMimeType) {
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/apps/files/api/v1/convert'), {
    fileId,
    targetMimeType
  });
};
const convertFiles = async function (fileIds, targetMimeType) {
  const conversions = fileIds.map(fileId => queue.add(() => requestConversion(fileId, targetMimeType)));
  // Start conversion
  const toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showLoading)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Converting files …'));
  // Handle results
  try {
    const results = await Promise.allSettled(conversions);
    const failed = results.filter(result => result.status === 'rejected');
    if (failed.length > 0) {
      const messages = failed.map(result => result.reason?.response?.data?.ocs?.meta?.message);
      _logger__WEBPACK_IMPORTED_MODULE_7__["default"].error('Failed to convert files', {
        fileIds,
        targetMimeType,
        messages
      });
      // If all failed files have the same error message, show it
      if (new Set(messages).size === 1 && typeof messages[0] === 'string') {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Failed to convert files: {message}', {
          message: messages[0]
        }));
        return;
      }
      if (failed.length === fileIds.length) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'All files failed to be converted'));
        return;
      }
      // A single file failed and if we have a message for the failed file, show it
      if (failed.length === 1 && messages[0]) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'One file could not be converted: {message}', {
          message: messages[0]
        }));
        return;
      }
      // We already check above when all files failed
      // if we're here, we have a mix of failed and successful files
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files', 'One file could not be converted', '%n files could not be converted', failed.length));
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files', 'One file successfully converted', '%n files successfully converted', fileIds.length - failed.length));
      return;
    }
    // All files converted
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Files successfully converted'));
    // Extract files that are within the current directory
    // in batch mode, you might have files from different directories
    // ⚠️, let's get the actual current dir, as the one from the action
    // might have changed as the user navigated away
    const currentDir = window.OCP.Files.Router.query.dir;
    const newPaths = results.filter(result => result.status === 'fulfilled').map(result => result.value.data.ocs.data.path).filter(path => path.startsWith(currentDir));
    // Fetch the new files
    _logger__WEBPACK_IMPORTED_MODULE_7__["default"].debug('Files to fetch', {
      newPaths
    });
    const newFiles = await Promise.all(newPaths.map(path => (0,_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__.fetchNode)(path)));
    // Inform the file list about the new files
    newFiles.forEach(file => (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:created', file));
    // Switch to the new files
    const firstSuccess = results[0];
    const newFileId = firstSuccess.value.data.ocs.data.fileId;
    window.OCP.Files.Router.goToRoute(null, {
      ...window.OCP.Files.Router.params,
      fileid: newFileId.toString()
    }, window.OCP.Files.Router.query);
  } catch (error) {
    // Should not happen as we use allSettled and handle errors above
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Failed to convert files'));
    _logger__WEBPACK_IMPORTED_MODULE_7__["default"].error('Failed to convert files', {
      fileIds,
      targetMimeType,
      error
    });
  } finally {
    // Hide loading toast
    toast.hideToast();
  }
};
const convertFile = async function (fileId, targetMimeType) {
  const toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showLoading)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Converting file …'));
  try {
    const result = await queue.add(() => requestConversion(fileId, targetMimeType));
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'File successfully converted'));
    // Inform the file list about the new file
    const newFile = await (0,_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__.fetchNode)(result.data.ocs.data.path);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:created', newFile);
    // Switch to the new file
    const newFileId = result.data.ocs.data.fileId;
    window.OCP.Files.Router.goToRoute(null, {
      ...window.OCP.Files.Router.params,
      fileid: newFileId.toString()
    }, window.OCP.Files.Router.query);
  } catch (error) {
    // If the server returned an error message, show it
    if ((0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__.isAxiosError)(error) && error.response?.data?.ocs?.meta?.message) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Failed to convert file: {message}', {
        message: error.response.data.ocs.meta.message
      }));
      return;
    }
    _logger__WEBPACK_IMPORTED_MODULE_7__["default"].error('Failed to convert file', {
      fileId,
      targetMimeType,
      error
    });
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Failed to convert file'));
  } finally {
    // Hide loading toast
    toast.hideToast();
  }
};

/***/ }),

/***/ "./apps/files/src/actions/deleteAction.ts":
/*!************************************************!*\
  !*** ./apps/files/src/actions/deleteAction.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_DELETE: () => (/* binding */ ACTION_DELETE),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _mdi_svg_svg_close_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/close.svg?raw */ "./node_modules/@mdi/svg/svg/close.svg?raw");
/* harmony import */ var _mdi_svg_svg_network_off_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/network-off.svg?raw */ "./node_modules/@mdi/svg/svg/network-off.svg?raw");
/* harmony import */ var _mdi_svg_svg_trash_can_outline_svg_raw__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/svg/svg/trash-can-outline.svg?raw */ "./node_modules/@mdi/svg/svg/trash-can-outline.svg?raw");
/* harmony import */ var _files_trashbin_src_files_views_trashbinView_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../files_trashbin/src/files_views/trashbinView.ts */ "./apps/files_trashbin/src/files_views/trashbinView.ts");
/* harmony import */ var _deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./deleteUtils.ts */ "./apps/files/src/actions/deleteUtils.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */









const queue = new p_queue__WEBPACK_IMPORTED_MODULE_2__["default"]({
  concurrency: 5
});
const ACTION_DELETE = 'delete';
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: ACTION_DELETE,
  displayName: _deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.displayName,
  iconSvgInline: nodes => {
    if ((0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.canUnshareOnly)(nodes)) {
      return _mdi_svg_svg_close_svg_raw__WEBPACK_IMPORTED_MODULE_3__;
    }
    if ((0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.canDisconnectOnly)(nodes)) {
      return _mdi_svg_svg_network_off_svg_raw__WEBPACK_IMPORTED_MODULE_4__;
    }
    return _mdi_svg_svg_trash_can_outline_svg_raw__WEBPACK_IMPORTED_MODULE_5__;
  },
  enabled(nodes, view) {
    if (view.id === _files_trashbin_src_files_views_trashbinView_ts__WEBPACK_IMPORTED_MODULE_6__.TRASHBIN_VIEW_ID) {
      const config = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files_trashbin', 'config', {
        allow_delete: true
      });
      if (config.allow_delete === false) {
        return false;
      }
    }
    return nodes.length > 0 && nodes.map(node => node.permissions).every(permission => (permission & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.DELETE) !== 0);
  },
  async exec(node, view) {
    try {
      let confirm = true;
      // Trick to detect if the action was called from a keyboard event
      // we need to make sure the method calling have its named containing 'keydown'
      // here we use `onKeydown` method from the FileEntryActions component
      const callStack = new Error().stack || '';
      const isCalledFromEventListener = callStack.toLocaleLowerCase().includes('keydown');
      if ((0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.shouldAskForConfirmation)() || isCalledFromEventListener) {
        confirm = await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.askConfirmation)([node], view);
      }
      // If the user cancels the deletion, we don't want to do anything
      if (confirm === false) {
        return null;
      }
      await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.deleteNode)(node);
      return true;
    } catch (error) {
      _logger_ts__WEBPACK_IMPORTED_MODULE_8__["default"].error('Error while deleting a file', {
        error,
        source: node.source,
        node
      });
      return false;
    }
  },
  async execBatch(nodes, view) {
    let confirm = true;
    if ((0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.shouldAskForConfirmation)()) {
      confirm = await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.askConfirmation)(nodes, view);
    } else if (nodes.length >= 5 && !(0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.canUnshareOnly)(nodes) && !(0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.canDisconnectOnly)(nodes)) {
      confirm = await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.askConfirmation)(nodes, view);
    }
    // If the user cancels the deletion, we don't want to do anything
    if (confirm === false) {
      return Promise.all(nodes.map(() => null));
    }
    // Map each node to a promise that resolves with the result of exec(node)
    const promises = nodes.map(node => {
      // Create a promise that resolves with the result of exec(node)
      const promise = new Promise(resolve => {
        queue.add(async () => {
          try {
            await (0,_deleteUtils_ts__WEBPACK_IMPORTED_MODULE_7__.deleteNode)(node);
            resolve(true);
          } catch (error) {
            _logger_ts__WEBPACK_IMPORTED_MODULE_8__["default"].error('Error while deleting a file', {
              error,
              source: node.source,
              node
            });
            resolve(false);
          }
        });
      });
      return promise;
    });
    return Promise.all(promises);
  },
  destructive: true,
  order: 100
});

/***/ }),

/***/ "./apps/files/src/actions/deleteUtils.ts":
/*!***********************************************!*\
  !*** ./apps/files/src/actions/deleteUtils.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   askConfirmation: () => (/* binding */ askConfirmation),
/* harmony export */   canDisconnectOnly: () => (/* binding */ canDisconnectOnly),
/* harmony export */   canUnshareOnly: () => (/* binding */ canUnshareOnly),
/* harmony export */   deleteNode: () => (/* binding */ deleteNode),
/* harmony export */   displayName: () => (/* binding */ displayName),
/* harmony export */   isAllFiles: () => (/* binding */ isAllFiles),
/* harmony export */   isAllFolders: () => (/* binding */ isAllFolders),
/* harmony export */   isMixedUnshareAndDelete: () => (/* binding */ isMixedUnshareAndDelete),
/* harmony export */   isTrashbinEnabled: () => (/* binding */ isTrashbinEnabled),
/* harmony export */   shouldAskForConfirmation: () => (/* binding */ shouldAskForConfirmation)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _store_userconfig__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/userconfig */ "./apps/files/src/store/userconfig.ts");
/* harmony import */ var _store__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../store */ "./apps/files/src/store/index.ts");







const isTrashbinEnabled = () => (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)()?.files?.undelete === true;
const canUnshareOnly = nodes => {
  return nodes.every(node => node.attributes['is-mount-root'] === true && node.attributes['mount-type'] === 'shared');
};
const canDisconnectOnly = nodes => {
  return nodes.every(node => node.attributes['is-mount-root'] === true && node.attributes['mount-type'] === 'external');
};
const isMixedUnshareAndDelete = nodes => {
  if (nodes.length === 1) {
    return false;
  }
  const hasSharedItems = nodes.some(node => canUnshareOnly([node]));
  const hasDeleteItems = nodes.some(node => !canUnshareOnly([node]));
  return hasSharedItems && hasDeleteItems;
};
const isAllFiles = nodes => {
  return !nodes.some(node => node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.File);
};
const isAllFolders = nodes => {
  return !nodes.some(node => node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder);
};
const displayName = (nodes, view) => {
  /**
   * If those nodes are all the root node of a
   * share, we can only unshare them.
   */
  if (canUnshareOnly(nodes)) {
    if (nodes.length === 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Leave this share');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Leave these shares');
  }
  /**
   * If those nodes are all the root node of an
   * external storage, we can only disconnect it.
   */
  if (canDisconnectOnly(nodes)) {
    if (nodes.length === 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Disconnect storage');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Disconnect storages');
  }
  /**
   * If we're in the trashbin, we can only delete permanently
   */
  if (view.id === 'trashbin' || !isTrashbinEnabled()) {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Delete permanently');
  }
  /**
   * If we're in the sharing view, we can only unshare
   */
  if (isMixedUnshareAndDelete(nodes)) {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Delete and unshare');
  }
  /**
   * If we're only selecting files, use proper wording
   */
  if (isAllFiles(nodes)) {
    if (nodes.length === 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Delete file');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Delete files');
  }
  /**
   * If we're only selecting folders, use proper wording
   */
  if (isAllFolders(nodes)) {
    if (nodes.length === 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Delete folder');
    }
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Delete folders');
  }
  return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Delete');
};
const shouldAskForConfirmation = () => {
  const userConfig = (0,_store_userconfig__WEBPACK_IMPORTED_MODULE_5__.useUserConfigStore)((0,_store__WEBPACK_IMPORTED_MODULE_6__.getPinia)());
  return userConfig.userConfig.show_dialog_deletion !== false;
};
const askConfirmation = async (nodes, view) => {
  const message = view.id === 'trashbin' || !isTrashbinEnabled() ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files', 'You are about to permanently delete {count} item', 'You are about to permanently delete {count} items', nodes.length, {
    count: nodes.length
  }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files', 'You are about to delete {count} item', 'You are about to delete {count} items', nodes.length, {
    count: nodes.length
  });
  return new Promise(resolve => {
    // TODO: Use the new dialog API
    window.OC.dialogs.confirmDestructive(message, (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Confirm deletion'), {
      type: window.OC.dialogs.YES_NO_BUTTONS,
      confirm: displayName(nodes, view),
      confirmClasses: 'error',
      cancel: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files', 'Cancel')
    }, decision => {
      resolve(decision);
    });
  });
};
const deleteNode = async node => {
  await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__["default"].delete(node.encodedSource);
  // Let's delete even if it's moved to the trashbin
  // since it has been removed from the current view
  // and changing the view will trigger a reload anyway.
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:deleted', node);
};

/***/ }),

/***/ "./apps/files/src/actions/downloadAction.ts":
/*!**************************************************!*\
  !*** ./apps/files/src/actions/downloadAction.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_arrow_down_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/arrow-down.svg?raw */ "./node_modules/@mdi/svg/svg/arrow-down.svg?raw");
/* harmony import */ var _utils_permissions__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/permissions */ "./apps/files/src/utils/permissions.ts");
/* harmony import */ var _store_paths__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../store/paths */ "./apps/files/src/store/paths.ts");
/* harmony import */ var _store__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../store */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_files__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../store/files */ "./apps/files/src/store/files.ts");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");










/**
 * Trigger downloading a file.
 *
 * @param url The url of the asset to download
 * @param name Optionally the recommended name of the download (browsers might ignore it)
 */
async function triggerDownload(url, name) {
  // try to see if the resource is still available
  await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].head(url);
  const hiddenElement = document.createElement('a');
  hiddenElement.download = name ?? '';
  hiddenElement.href = url;
  hiddenElement.click();
}
/**
 * Find the longest common path prefix of both input paths
 * @param first The first path
 * @param second The second path
 */
function longestCommonPath(first, second) {
  const firstSegments = first.split('/').filter(Boolean);
  const secondSegments = second.split('/').filter(Boolean);
  let base = '';
  for (const [index, segment] of firstSegments.entries()) {
    if (index >= second.length) {
      break;
    }
    if (segment !== secondSegments[index]) {
      break;
    }
    const sep = base === '' ? '' : '/';
    base = `${base}${sep}${segment}`;
  }
  return base;
}
/**
 * Download the given nodes.
 *
 * If only one node is given, it will be downloaded directly.
 * If multiple nodes are given, they will be zipped and downloaded.
 *
 * @param nodes The node(s) to download
 */
async function downloadNodes(nodes) {
  let url;
  if (nodes.length === 1) {
    if (nodes[0].type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.File) {
      await triggerDownload(nodes[0].encodedSource, nodes[0].displayname);
      return;
    } else {
      url = new URL(nodes[0].encodedSource);
      url.searchParams.append('accept', 'zip');
    }
  } else {
    url = new URL(nodes[0].encodedSource);
    let base = url.pathname;
    for (const node of nodes.slice(1)) {
      base = longestCommonPath(base, new URL(node.encodedSource).pathname);
    }
    url.pathname = base;
    // The URL contains the path encoded so we need to decode as the query.append will re-encode it
    const filenames = nodes.map(node => decodeURIComponent(node.encodedSource.slice(url.href.length + 1)));
    url.searchParams.append('accept', 'zip');
    url.searchParams.append('files', JSON.stringify(filenames));
  }
  if (url.pathname.at(-1) !== '/') {
    url.pathname = `${url.pathname}/`;
  }
  await triggerDownload(url.href);
}
/**
 * Get the current directory node for the given view and path.
 * TODO: ideally the folder would directly be passed as exec params
 *
 * @param view The current view
 * @param directory The directory path
 * @return The current directory node or null if not found
 */
function getCurrentDirectory(view, directory) {
  const filesStore = (0,_store_files__WEBPACK_IMPORTED_MODULE_8__.useFilesStore)((0,_store__WEBPACK_IMPORTED_MODULE_7__.getPinia)());
  const pathsStore = (0,_store_paths__WEBPACK_IMPORTED_MODULE_6__.usePathsStore)((0,_store__WEBPACK_IMPORTED_MODULE_7__.getPinia)());
  if (!view?.id) {
    return null;
  }
  if (directory === '/') {
    return filesStore.getRoot(view.id) || null;
  }
  const fileId = pathsStore.getPath(view.id, directory);
  return filesStore.getNode(fileId) || null;
}
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: 'download',
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.DefaultType.DEFAULT,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Download'),
  iconSvgInline: () => _mdi_svg_svg_arrow_down_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
  enabled(nodes, view) {
    if (nodes.length === 0) {
      return false;
    }
    // We can only download dav files and folders.
    if (nodes.some(node => !node.isDavResource)) {
      return false;
    }
    // Trashbin does not allow batch download
    if (nodes.length > 1 && view.id === 'trashbin') {
      return false;
    }
    return nodes.every(_utils_permissions__WEBPACK_IMPORTED_MODULE_5__.isDownloadable);
  },
  async exec(node) {
    try {
      await downloadNodes([node]);
    } catch (e) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'The requested file is not available.'));
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_9__.emit)('files:node:deleted', node);
    }
    return null;
  },
  async execBatch(nodes, view, dir) {
    try {
      await downloadNodes(nodes);
    } catch (e) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'The requested files are not available.'));
      // Try to reload the current directory to update the view
      const directory = getCurrentDirectory(view, dir);
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_9__.emit)('files:node:updated', directory);
    }
    return new Array(nodes.length).fill(null);
  },
  order: 30
});

/***/ }),

/***/ "./apps/files/src/actions/favoriteAction.ts":
/*!**************************************************!*\
  !*** ./apps/files/src/actions/favoriteAction.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_FAVORITE: () => (/* binding */ ACTION_FAVORITE),
/* harmony export */   action: () => (/* binding */ action),
/* harmony export */   favoriteNode: () => (/* binding */ favoriteNode)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @mdi/svg/svg/star-outline.svg?raw */ "./node_modules/@mdi/svg/svg/star-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_star_svg_raw__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @mdi/svg/svg/star.svg?raw */ "./node_modules/@mdi/svg/svg/star.svg?raw");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");












const ACTION_FAVORITE = 'favorite';
const queue = new p_queue__WEBPACK_IMPORTED_MODULE_7__["default"]({
  concurrency: 5
});
// If any of the nodes is not favorited, we display the favorite action.
const shouldFavorite = nodes => {
  return nodes.some(node => node.attributes.favorite !== 1);
};
const favoriteNode = async (node, view, willFavorite) => {
  try {
    // TODO: migrate to webdav tags plugin
    const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateUrl)('/apps/files/api/v1/files') + (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_3__.encodePath)(node.path);
    await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].post(url, {
      tags: willFavorite ? [window.OC.TAG_FAVORITE] : []
    });
    // Let's delete if we are in the favourites view
    // AND if it is removed from the user favorites
    // AND it's in the root of the favorites view
    if (view.id === 'favorites' && !willFavorite && node.dirname === '/') {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:deleted', node);
    }
    // Update the node webdav attribute
    vue__WEBPACK_IMPORTED_MODULE_8__["default"].set(node.attributes, 'favorite', willFavorite ? 1 : 0);
    // Dispatch event to whoever is interested
    if (willFavorite) {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:favorites:added', node);
    } else {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:favorites:removed', node);
    }
    return true;
  } catch (error) {
    const action = willFavorite ? 'adding a file to favourites' : 'removing a file from favourites';
    _logger_ts__WEBPACK_IMPORTED_MODULE_11__["default"].error('Error while ' + action, {
      error,
      source: node.source,
      node
    });
    return false;
  }
};
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
  id: ACTION_FAVORITE,
  displayName(nodes) {
    return shouldFavorite(nodes) ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files', 'Add to favorites') : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files', 'Remove from favorites');
  },
  iconSvgInline: nodes => {
    return shouldFavorite(nodes) ? _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_9__ : _mdi_svg_svg_star_svg_raw__WEBPACK_IMPORTED_MODULE_10__;
  },
  enabled(nodes) {
    // Not enabled for public shares
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_5__.isPublicShare)()) {
      return false;
    }
    // We can only favorite nodes if they are located in files
    return nodes.every(node => node.root?.startsWith?.('/files'))
    // and we have permissions
    && nodes.every(node => node.permissions !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.NONE);
  },
  async exec(node, view) {
    const willFavorite = shouldFavorite([node]);
    return await favoriteNode(node, view, willFavorite);
  },
  async execBatch(nodes, view) {
    const willFavorite = shouldFavorite(nodes);
    // Map each node to a promise that resolves with the result of exec(node)
    const promises = nodes.map(node => {
      // Create a promise that resolves with the result of exec(node)
      const promise = new Promise(resolve => {
        queue.add(async () => {
          try {
            await favoriteNode(node, view, willFavorite);
            resolve(true);
          } catch (error) {
            _logger_ts__WEBPACK_IMPORTED_MODULE_11__["default"].error('Error while adding file to favorite', {
              error,
              source: node.source,
              node
            });
            resolve(false);
          }
        });
      });
      return promise;
    });
    return Promise.all(promises);
  },
  order: -50
});

/***/ }),

/***/ "./apps/files/src/actions/moveOrCopyAction.ts":
/*!****************************************************!*\
  !*** ./apps/files/src/actions/moveOrCopyAction.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_COPY_MOVE: () => (/* binding */ ACTION_COPY_MOVE),
/* harmony export */   action: () => (/* binding */ action),
/* harmony export */   handleCopyMoveNodeTo: () => (/* binding */ handleCopyMoveNodeTo)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_upload__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/upload */ "./node_modules/@nextcloud/upload/dist/index.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @mdi/svg/svg/folder-multiple-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-multiple-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_folder_move_outline_svg_raw__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @mdi/svg/svg/folder-move-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-move-outline.svg?raw");
/* harmony import */ var _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./moveOrCopyActionUtils */ "./apps/files/src/actions/moveOrCopyActionUtils.ts");
/* harmony import */ var _services_Files__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../services/Files */ "./apps/files/src/services/Files.ts");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../logger */ "./apps/files/src/logger.ts");













/**
 * Return the action that is possible for the given nodes
 * @param {Node[]} nodes The nodes to check against
 * @return {MoveCopyAction} The action that is possible for the given nodes
 */
const getActionForNodes = nodes => {
  if ((0,_moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.canMove)(nodes)) {
    if ((0,_moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.canCopy)(nodes)) {
      return _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE_OR_COPY;
    }
    return _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE;
  }
  // Assuming we can copy as the enabled checks for copy permissions
  return _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.COPY;
};
/**
 * Create a loading notification toast
 * @param mode The move or copy mode
 * @param source Name of the node that is copied / moved
 * @param destination Destination path
 * @return {() => void} Function to hide the notification
 */
function createLoadingNotification(mode, source, destination) {
  const text = mode === _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Moving "{source}" to "{destination}" …', {
    source,
    destination
  }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Copying "{source}" to "{destination}" …', {
    source,
    destination
  });
  let toast;
  toast = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showInfo)(`<span class="icon icon-loading-small toast-loading-icon"></span> ${text}`, {
    isHTML: true,
    timeout: _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.TOAST_PERMANENT_TIMEOUT,
    onRemove: () => {
      toast?.hideToast();
      toast = undefined;
    }
  });
  return () => toast && toast.hideToast();
}
/**
 * Handle the copy/move of a node to a destination
 * This can be imported and used by other scripts/components on server
 * @param {Node} node The node to copy/move
 * @param {Folder} destination The destination to copy/move the node to
 * @param {MoveCopyAction} method The method to use for the copy/move
 * @param {boolean} overwrite Whether to overwrite the destination if it exists
 * @return {Promise<void>} A promise that resolves when the copy/move is done
 */
const handleCopyMoveNodeTo = async function (node, destination, method) {
  let overwrite = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  if (!destination) {
    return;
  }
  if (destination.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder) {
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Destination is not a folder'));
  }
  // Do not allow to MOVE a node to the same folder it is already located
  if (method === _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE && node.dirname === destination.path) {
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'This file/folder is already in that directory'));
  }
  /**
   * Example:
   * - node: /foo/bar/file.txt -> path = /foo/bar/file.txt, destination: /foo
   *   Allow move of /foo does not start with /foo/bar/file.txt so allow
   * - node: /foo , destination: /foo/bar
   *   Do not allow as it would copy foo within itself
   * - node: /foo/bar.txt, destination: /foo
   *   Allow copy a file to the same directory
   * - node: "/foo/bar", destination: "/foo/bar 1"
   *   Allow to move or copy but we need to check with trailing / otherwise it would report false positive
   */
  if (`${destination.path}/`.startsWith(`${node.path}/`)) {
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'You cannot move a file/folder onto itself or into a subfolder of itself'));
  }
  // Set loading state
  vue__WEBPACK_IMPORTED_MODULE_7__["default"].set(node, 'status', _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.NodeStatus.LOADING);
  const actionFinished = createLoadingNotification(method, node.basename, destination.path);
  const queue = (0,_moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.getQueue)();
  return await queue.add(async () => {
    const copySuffix = index => {
      if (index === 1) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', '(copy)'); // TRANSLATORS: Mark a file as a copy of another file
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', '(copy %n)', undefined, index); // TRANSLATORS: Meaning it is the n'th copy of a file
    };
    try {
      const client = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.davGetClient)();
      const currentPath = (0,path__WEBPACK_IMPORTED_MODULE_6__.join)(_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.davRootPath, node.path);
      const destinationPath = (0,path__WEBPACK_IMPORTED_MODULE_6__.join)(_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.davRootPath, destination.path);
      if (method === _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.COPY) {
        let target = node.basename;
        // If we do not allow overwriting then find an unique name
        if (!overwrite) {
          const otherNodes = await client.getDirectoryContents(destinationPath);
          target = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.getUniqueName)(node.basename, otherNodes.map(n => n.basename), {
            suffix: copySuffix,
            ignoreFileExtension: node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileType.Folder
          });
        }
        await client.copyFile(currentPath, (0,path__WEBPACK_IMPORTED_MODULE_6__.join)(destinationPath, target));
        // If the node is copied into current directory the view needs to be updated
        if (node.dirname === destination.path) {
          const {
            data
          } = await client.stat((0,path__WEBPACK_IMPORTED_MODULE_6__.join)(destinationPath, target), {
            details: true,
            data: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.davGetDefaultPropfind)()
          });
          (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:created', (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.davResultToNode)(data));
        }
      } else {
        // show conflict file popup if we do not allow overwriting
        if (!overwrite) {
          const otherNodes = await (0,_services_Files__WEBPACK_IMPORTED_MODULE_11__.getContents)(destination.path);
          if ((0,_nextcloud_upload__WEBPACK_IMPORTED_MODULE_5__.hasConflict)([node], otherNodes.contents)) {
            try {
              // Let the user choose what to do with the conflicting files
              const {
                selected,
                renamed
              } = await (0,_nextcloud_upload__WEBPACK_IMPORTED_MODULE_5__.openConflictPicker)(destination.path, [node], otherNodes.contents);
              // two empty arrays: either only old files or conflict skipped -> no action required
              if (!selected.length && !renamed.length) {
                return;
              }
            } catch (error) {
              // User cancelled
              return;
            }
          }
        }
        // getting here means either no conflict, file was renamed to keep both files
        // in a conflict, or the selected file was chosen to be kept during the conflict
        try {
          await client.moveFile(currentPath, (0,path__WEBPACK_IMPORTED_MODULE_6__.join)(destinationPath, node.basename));
        } catch (error) {
          const parser = new DOMParser();
          const text = await error.response?.text();
          const message = parser.parseFromString(text ?? '', 'text/xml').querySelector('message')?.textContent;
          if (message) {
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(message);
          }
          throw error;
        }
        // Delete the node as it will be fetched again
        // when navigating to the destination folder
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:deleted', node);
      }
    } catch (error) {
      if ((0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__.isAxiosError)(error)) {
        if (error.response?.status === 412) {
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'A file or folder with that name already exists in this folder'));
        } else if (error.response?.status === 423) {
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'The files are locked'));
        } else if (error.response?.status === 404) {
          throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'The file does not exist anymore'));
        } else if (error.message) {
          throw new Error(error.message);
        }
      }
      _logger__WEBPACK_IMPORTED_MODULE_12__["default"].debug(error);
      throw new Error();
    } finally {
      vue__WEBPACK_IMPORTED_MODULE_7__["default"].set(node, 'status', '');
      actionFinished();
    }
  });
};
/**
 * Open a file picker for the given action
 * @param action The action to open the file picker for
 * @param dir The directory to start the file picker in
 * @param nodes The nodes to move/copy
 * @return The picked destination or false if cancelled by user
 */
async function openFilePickerForAction(action) {
  let dir = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '/';
  let nodes = arguments.length > 2 ? arguments[2] : undefined;
  const {
    resolve,
    reject,
    promise
  } = Promise.withResolvers();
  const fileIDs = nodes.map(node => node.fileid).filter(Boolean);
  const filePicker = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.getFilePickerBuilder)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Choose destination')).allowDirectories(true).setFilter(n => {
    // We don't want to show the current nodes in the file picker
    return !fileIDs.includes(n.fileid);
  }).setMimeTypeFilter([]).setMultiSelect(false).startAt(dir).setButtonFactory((selection, path) => {
    const buttons = [];
    const target = (0,path__WEBPACK_IMPORTED_MODULE_6__.basename)(path);
    const dirnames = nodes.map(node => node.dirname);
    const paths = nodes.map(node => node.path);
    if (action === _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.COPY || action === _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE_OR_COPY) {
      buttons.push({
        label: target ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Copy to {target}', {
          target
        }, undefined, {
          escape: false,
          sanitize: false
        }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Copy'),
        type: 'primary',
        icon: _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_8__,
        disabled: selection.some(node => (node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Permission.CREATE) === 0),
        async callback(destination) {
          resolve({
            destination: destination[0],
            action: _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.COPY
          });
        }
      });
    }
    // Invalid MOVE targets (but valid copy targets)
    if (dirnames.includes(path)) {
      // This file/folder is already in that directory
      return buttons;
    }
    if (paths.includes(path)) {
      // You cannot move a file/folder onto itself
      return buttons;
    }
    if (selection.some(node => (node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Permission.CREATE) === 0)) {
      // Missing 'CREATE' permissions for selected destination
      return buttons;
    }
    if (action === _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE || action === _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE_OR_COPY) {
      buttons.push({
        label: target ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Move to {target}', {
          target
        }, undefined, {
          escape: false,
          sanitize: false
        }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Move'),
        type: action === _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE ? 'primary' : 'secondary',
        icon: _mdi_svg_svg_folder_move_outline_svg_raw__WEBPACK_IMPORTED_MODULE_9__,
        async callback(destination) {
          resolve({
            destination: destination[0],
            action: _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE
          });
        }
      });
    }
    return buttons;
  }).build();
  filePicker.pick().catch(error => {
    _logger__WEBPACK_IMPORTED_MODULE_12__["default"].debug(error);
    if (error instanceof _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.FilePickerClosed) {
      resolve(false);
    } else {
      reject(new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Move or copy operation failed')));
    }
  });
  return promise;
}
const ACTION_COPY_MOVE = 'move-copy';
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileAction({
  id: ACTION_COPY_MOVE,
  displayName(nodes) {
    switch (getActionForNodes(nodes)) {
      case _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Move');
      case _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.COPY:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Copy');
      case _moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.MoveCopyAction.MOVE_OR_COPY:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Move or copy');
    }
  },
  iconSvgInline: () => _mdi_svg_svg_folder_move_outline_svg_raw__WEBPACK_IMPORTED_MODULE_9__,
  enabled(nodes, view) {
    // We can not copy or move in single file shares
    if (view.id === 'public-file-share') {
      return false;
    }
    // We only support moving/copying files within the user folder
    if (!nodes.every(node => node.root?.startsWith('/files/'))) {
      return false;
    }
    return nodes.length > 0 && ((0,_moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.canMove)(nodes) || (0,_moveOrCopyActionUtils__WEBPACK_IMPORTED_MODULE_10__.canCopy)(nodes));
  },
  async exec(node, view, dir) {
    const action = getActionForNodes([node]);
    let result;
    try {
      result = await openFilePickerForAction(action, dir, [node]);
    } catch (e) {
      _logger__WEBPACK_IMPORTED_MODULE_12__["default"].error(e);
      return false;
    }
    if (result === false) {
      return null;
    }
    try {
      await handleCopyMoveNodeTo(node, result.destination, result.action);
      return true;
    } catch (error) {
      if (error instanceof Error && !!error.message) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(error.message);
        // Silent action as we handle the toast
        return null;
      }
      return false;
    }
  },
  async execBatch(nodes, view, dir) {
    const action = getActionForNodes(nodes);
    const result = await openFilePickerForAction(action, dir, nodes);
    // Handle cancellation silently
    if (result === false) {
      return nodes.map(() => null);
    }
    const promises = nodes.map(async node => {
      try {
        await handleCopyMoveNodeTo(node, result.destination, result.action);
        return true;
      } catch (error) {
        _logger__WEBPACK_IMPORTED_MODULE_12__["default"].error(`Failed to ${result.action} node`, {
          node,
          error
        });
        return false;
      }
    });
    // We need to keep the selection on error!
    // So we do not return null, and for batch action
    // we let the front handle the error.
    return await Promise.all(promises);
  },
  order: 15
});

/***/ }),

/***/ "./apps/files/src/actions/moveOrCopyActionUtils.ts":
/*!*********************************************************!*\
  !*** ./apps/files/src/actions/moveOrCopyActionUtils.ts ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   MoveCopyAction: () => (/* binding */ MoveCopyAction),
/* harmony export */   canCopy: () => (/* binding */ canCopy),
/* harmony export */   canDownload: () => (/* binding */ canDownload),
/* harmony export */   canMove: () => (/* binding */ canMove),
/* harmony export */   getQueue: () => (/* binding */ getQueue)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




const sharePermissions = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__.loadState)('files_sharing', 'sharePermissions', _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.NONE);
// This is the processing queue. We only want to allow 3 concurrent requests
let queue;
// Maximum number of concurrent operations
const MAX_CONCURRENCY = 5;
/**
 * Get the processing queue
 */
const getQueue = () => {
  if (!queue) {
    queue = new p_queue__WEBPACK_IMPORTED_MODULE_2__["default"]({
      concurrency: MAX_CONCURRENCY
    });
  }
  return queue;
};
var MoveCopyAction;
(function (MoveCopyAction) {
  MoveCopyAction["MOVE"] = "Move";
  MoveCopyAction["COPY"] = "Copy";
  MoveCopyAction["MOVE_OR_COPY"] = "move-or-copy";
})(MoveCopyAction || (MoveCopyAction = {}));
const canMove = nodes => {
  const minPermission = nodes.reduce((min, node) => Math.min(min, node.permissions), _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.ALL);
  return Boolean(minPermission & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.DELETE);
};
const canDownload = nodes => {
  return nodes.every(node => {
    const shareAttributes = JSON.parse(node.attributes?.['share-attributes'] ?? '[]');
    return !shareAttributes.some(attribute => attribute.scope === 'permissions' && attribute.value === false && attribute.key === 'download');
  });
};
const canCopy = nodes => {
  // a shared file cannot be copied if the download is disabled
  if (!canDownload(nodes)) {
    return false;
  }
  // it cannot be copied if the user has only view permissions
  if (nodes.some(node => node.permissions === _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.NONE)) {
    return false;
  }
  // on public shares all files have the same permission so copy is only possible if write permission is granted
  if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_1__.isPublicShare)()) {
    return Boolean(sharePermissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.CREATE);
  }
  // otherwise permission is granted
  return true;
};

/***/ }),

/***/ "./apps/files/src/actions/openFolderAction.ts":
/*!****************************************************!*\
  !*** ./apps/files/src/actions/openFolderAction.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_folder_svg_raw__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/svg/svg/folder.svg?raw */ "./node_modules/@mdi/svg/svg/folder.svg?raw");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: 'open-folder',
  displayName(files) {
    // Only works on single node
    const displayName = files[0].displayname;
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'Open folder {displayName}', {
      displayName
    });
  },
  iconSvgInline: () => _mdi_svg_svg_folder_svg_raw__WEBPACK_IMPORTED_MODULE_2__,
  enabled(nodes) {
    // Only works on single node
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes[0];
    if (!node.isDavRessource) {
      return false;
    }
    return node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.Folder && (node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.READ) !== 0;
  },
  async exec(node, view) {
    if (!node || node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.Folder) {
      return false;
    }
    window.OCP.Files.Router.goToRoute(null, {
      view: view.id,
      fileid: String(node.fileid)
    }, {
      dir: node.path
    });
    return null;
  },
  // Main action if enabled, meaning folders only
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.DefaultType.HIDDEN,
  order: -100
});

/***/ }),

/***/ "./apps/files/src/actions/openInFilesAction.ts":
/*!*****************************************************!*\
  !*** ./apps/files/src/actions/openInFilesAction.ts ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _views_search__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../views/search */ "./apps/files/src/views/search.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
  id: 'open-in-files',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files', 'Open in Files'),
  iconSvgInline: () => '',
  enabled(nodes, view) {
    return view.id === 'recent' || view.id === _views_search__WEBPACK_IMPORTED_MODULE_2__.VIEW_ID;
  },
  async exec(node) {
    let dir = node.dirname;
    if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
      dir = dir + '/' + node.basename;
    }
    window.OCP.Files.Router.goToRoute(null,
    // use default route
    {
      view: 'files',
      fileid: String(node.fileid)
    }, {
      dir,
      openfile: 'true'
    });
    return null;
  },
  // Before openFolderAction
  order: -1000,
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.DefaultType.HIDDEN
});

/***/ }),

/***/ "./apps/files/src/actions/openLocallyAction.ts":
/*!*****************************************************!*\
  !*** ./apps/files/src/actions/openLocallyAction.ts ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_laptop_svg_raw__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/svg/svg/laptop.svg?raw */ "./node_modules/@mdi/svg/svg/laptop.svg?raw");
/* harmony import */ var _mdi_svg_svg_web_svg_raw__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @mdi/svg/svg/web.svg?raw */ "./node_modules/@mdi/svg/svg/web.svg?raw");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */










const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.FileAction({
  id: 'edit-locally',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Open locally'),
  iconSvgInline: () => _mdi_svg_svg_laptop_svg_raw__WEBPACK_IMPORTED_MODULE_7__,
  // Only works on single files
  enabled(nodes) {
    // Only works on single node
    if (nodes.length !== 1) {
      return false;
    }
    // does not work with shares
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_9__.isPublicShare)()) {
      return false;
    }
    return (nodes[0].permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Permission.UPDATE) !== 0;
  },
  async exec(node) {
    await attemptOpenLocalClient(node.path);
    return null;
  },
  order: 25
});
/**
 * Try to open the path in the Nextcloud client.
 *
 * If this fails a dialog is shown with 3 options:
 * 1. Retry: If it fails no further dialog is shown.
 * 2. Open online: The viewer is used to open the file.
 * 3. Close the dialog and nothing happens (abort).
 *
 * @param path - The path to open
 */
async function attemptOpenLocalClient(path) {
  await openLocalClient(path);
  const result = await confirmLocalEditDialog();
  if (result === 'local') {
    await openLocalClient(path);
  } else if (result === 'online') {
    window.OCA.Viewer.open({
      path
    });
  }
}
/**
 * Try to open a file in the Nextcloud client.
 * There is no way to get notified if this action was successfull.
 *
 * @param path - Path to open
 */
async function openLocalClient(path) {
  const link = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files/api/v1') + '/openlocaleditor?format=json';
  try {
    const result = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].post(link, {
      path
    });
    const uid = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getCurrentUser)()?.uid;
    let url = `nc://open/${uid}@` + window.location.host + (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_0__.encodePath)(path);
    url += '?token=' + result.data.ocs.data.token;
    window.open(url, '_self');
  } catch (error) {
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Failed to redirect to client'));
  }
}
/**
 * Open the confirmation dialog.
 */
async function confirmLocalEditDialog() {
  let result = false;
  const dialog = new _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.DialogBuilder().setName((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Open file locally')).setText((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'The file should now open on your device. If it doesn\'t, please check that you have the desktop app installed.')).setButtons([{
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Retry and close'),
    type: 'secondary',
    callback: () => {
      result = 'local';
    }
  }, {
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Open online'),
    icon: _mdi_svg_svg_web_svg_raw__WEBPACK_IMPORTED_MODULE_8__,
    type: 'primary',
    callback: () => {
      result = 'online';
    }
  }]).build();
  await dialog.show();
  return result;
}

/***/ }),

/***/ "./apps/files/src/actions/renameAction.ts":
/*!************************************************!*\
  !*** ./apps/files/src/actions/renameAction.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_RENAME: () => (/* binding */ ACTION_RENAME),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_pencil_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/pencil-outline.svg?raw */ "./node_modules/@mdi/svg/svg/pencil-outline.svg?raw");
/* harmony import */ var _store__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../store */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_files__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/files */ "./apps/files/src/store/files.ts");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_6__);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const ACTION_RENAME = 'rename';
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
  id: ACTION_RENAME,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files', 'Rename'),
  iconSvgInline: () => _mdi_svg_svg_pencil_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
  enabled: (nodes, view) => {
    if (nodes.length === 0) {
      return false;
    }
    // Disable for single file shares
    if (view.id === 'public-file-share') {
      return false;
    }
    const node = nodes[0];
    const filesStore = (0,_store_files__WEBPACK_IMPORTED_MODULE_5__.useFilesStore)((0,_store__WEBPACK_IMPORTED_MODULE_4__.getPinia)());
    const parentNode = node.dirname === '/' ? filesStore.getRoot(view.id) : filesStore.getNode((0,path__WEBPACK_IMPORTED_MODULE_6__.dirname)(node.source));
    const parentPermissions = parentNode?.permissions || _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.NONE;
    // Only enable if the node have the delete permission
    // and if the parent folder allows creating files
    return Boolean(node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.DELETE) && Boolean(parentPermissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.CREATE);
  },
  async exec(node) {
    // Renaming is a built-in feature of the files app
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:rename', node);
    return null;
  },
  order: 10
});

/***/ }),

/***/ "./apps/files/src/actions/sidebarAction.ts":
/*!*************************************************!*\
  !*** ./apps/files/src/actions/sidebarAction.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_DETAILS: () => (/* binding */ ACTION_DETAILS),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/* harmony import */ var _mdi_svg_svg_information_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/information-outline.svg?raw */ "./node_modules/@mdi/svg/svg/information-outline.svg?raw");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");





const ACTION_DETAILS = 'details';
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: ACTION_DETAILS,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'Details'),
  iconSvgInline: () => _mdi_svg_svg_information_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
  // Sidebar currently supports user folder only, /files/USER
  enabled: nodes => {
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_2__.isPublicShare)()) {
      return false;
    }
    // Only works on single node
    if (nodes.length !== 1) {
      return false;
    }
    if (!nodes[0]) {
      return false;
    }
    // Only work if the sidebar is available
    if (!window?.OCA?.Files?.Sidebar) {
      return false;
    }
    return (nodes[0].root?.startsWith('/files/') && nodes[0].permissions !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.NONE) ?? false;
  },
  async exec(node, view, dir) {
    try {
      // If the sidebar is already open for the current file, do nothing
      if (window.OCA.Files.Sidebar.file === node.path) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].debug('Sidebar already open for this file', {
          node
        });
        return null;
      }
      // Open sidebar and set active tab to sharing by default
      window.OCA.Files.Sidebar.setActiveTab('sharing');
      // TODO: migrate Sidebar to use a Node instead
      await window.OCA.Files.Sidebar.open(node.path);
      // Silently update current fileid
      window.OCP?.Files?.Router?.goToRoute(null, {
        view: view.id,
        fileid: String(node.fileid)
      }, {
        ...window.OCP.Files.Router.query,
        dir,
        opendetails: 'true'
      }, true);
      return null;
    } catch (error) {
      _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].error('Error while opening sidebar', {
        error
      });
      return false;
    }
  },
  order: -50
});

/***/ }),

/***/ "./apps/files/src/actions/viewInFolderAction.ts":
/*!******************************************************!*\
  !*** ./apps/files/src/actions/viewInFolderAction.ts ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_folder_move_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/folder-move-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-move-outline.svg?raw");




const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
  id: 'view-in-folder',
  displayName() {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'View in folder');
  },
  iconSvgInline: () => _mdi_svg_svg_folder_move_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
  enabled(nodes, view) {
    // Not enabled for public shares
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_0__.isPublicShare)()) {
      return false;
    }
    // Only works outside of the main files view
    if (view.id === 'files') {
      return false;
    }
    // Only works on single node
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes[0];
    if (!node.isDavRessource) {
      return false;
    }
    // Can only view files that are in the user root folder
    if (!node.root?.startsWith('/files')) {
      return false;
    }
    if (node.permissions === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.NONE) {
      return false;
    }
    return node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.File;
  },
  async exec(node) {
    if (!node || node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.File) {
      return false;
    }
    window.OCP.Files.Router.goToRoute(null, {
      view: 'files',
      fileid: String(node.fileid)
    }, {
      dir: node.dirname
    });
    return null;
  },
  order: 80
});

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilter.vue":
/*!*********************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilter.vue ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileListFilter.vue?vue&type=template&id=5c291778&scoped=true */ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true");
/* harmony import */ var _FileListFilter_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileListFilter.vue?vue&type=script&setup=true&lang=ts */ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css */ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FileListFilter_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "5c291778",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/FileListFilter/FileListFilter.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts":
/*!********************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts ***!
  \********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilter.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css":
/*!*****************************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css ***!
  \*****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css");


/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true":
/*!***************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilter.vue?vue&type=template&id=5c291778&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true");


/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue":
/*!*****************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterModified.vue ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true */ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true");
/* harmony import */ var _FileListFilterModified_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileListFilterModified.vue?vue&type=script&lang=ts */ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&lang=ts");
/* harmony import */ var _FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss */ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FileListFilterModified_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "f47dfc3e",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/FileListFilter/FileListFilterModified.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&lang=ts":
/*!*****************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterModified.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss":
/*!**************************************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss ***!
  \**************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true":
/*!***********************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true ***!
  \***********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_template_id_f47dfc3e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true");


/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue":
/*!*****************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue ***!
  \*****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileListFilterToSearch_vue_vue_type_template_id_032b2a1b__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileListFilterToSearch.vue?vue&type=template&id=032b2a1b */ "./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=template&id=032b2a1b");
/* harmony import */ var _FileListFilterToSearch_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileListFilterToSearch.vue?vue&type=script&setup=true&lang=ts */ "./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _FileListFilterToSearch_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileListFilterToSearch_vue_vue_type_template_id_032b2a1b__WEBPACK_IMPORTED_MODULE_0__.render,
  _FileListFilterToSearch_vue_vue_type_template_id_032b2a1b__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/FileListFilter/FileListFilterToSearch.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=script&setup=true&lang=ts":
/*!****************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=script&setup=true&lang=ts ***!
  \****************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterToSearch_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterToSearch.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterToSearch_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=template&id=032b2a1b":
/*!***********************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=template&id=032b2a1b ***!
  \***********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterToSearch_vue_vue_type_template_id_032b2a1b__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterToSearch_vue_vue_type_template_id_032b2a1b__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterToSearch_vue_vue_type_template_id_032b2a1b__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterToSearch.vue?vue&type=template&id=032b2a1b */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=template&id=032b2a1b");


/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterType.vue":
/*!*************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterType.vue ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileListFilterType.vue?vue&type=template&id=6c0e6dd2 */ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2");
/* harmony import */ var _FileListFilterType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileListFilterType.vue?vue&type=script&lang=ts */ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&lang=ts");
/* harmony import */ var _FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css */ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FileListFilterType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__.render,
  _FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/FileListFilter/FileListFilterType.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&lang=ts":
/*!*************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&lang=ts ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterType.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css":
/*!*********************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css ***!
  \*********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css");


/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2":
/*!*******************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2 ***!
  \*******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_template_id_6c0e6dd2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterType.vue?vue&type=template&id=6c0e6dd2 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2");


/***/ }),

/***/ "./apps/files/src/components/NewNodeDialog.vue":
/*!*****************************************************!*\
  !*** ./apps/files/src/components/NewNodeDialog.vue ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true */ "./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true");
/* harmony import */ var _NewNodeDialog_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NewNodeDialog.vue?vue&type=script&setup=true&lang=ts */ "./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css */ "./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _NewNodeDialog_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "e6b9c05a",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/NewNodeDialog.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts":
/*!****************************************************************************************!*\
  !*** ./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewNodeDialog.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css":
/*!*************************************************************************************************************!*\
  !*** ./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css ***!
  \*************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css");


/***/ }),

/***/ "./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true":
/*!***********************************************************************************************!*\
  !*** ./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true ***!
  \***********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_template_id_e6b9c05a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true");


/***/ }),

/***/ "./apps/files/src/filters/FilenameFilter.ts":
/*!**************************************************!*\
  !*** ./apps/files/src/filters/FilenameFilter.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerFilenameFilter: () => (/* binding */ registerFilenameFilter)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_search_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../store/search.ts */ "./apps/files/src/store/search.ts");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/**
 * Register the filename filter
 */
function registerFilenameFilter() {
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileListFilter)(new FilenameFilter());
}
/**
 * Simple file list filter controlled by the Navigation search box
 */
class FilenameFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileListFilter {
  constructor() {
    super('files:filename', 5);
    _defineProperty(this, "searchQuery", '');
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:search:updated', _ref => {
      let {
        query,
        scope
      } = _ref;
      if (scope === 'filter') {
        this.updateQuery(query);
      }
    });
  }
  filter(nodes) {
    const queryParts = this.searchQuery.toLocaleLowerCase().split(' ').filter(Boolean);
    return nodes.filter(node => {
      const displayname = node.displayname.toLocaleLowerCase();
      return queryParts.every(part => displayname.includes(part));
    });
  }
  reset() {
    this.updateQuery('');
  }
  updateQuery(query) {
    query = (query || '').trim();
    // Only if the query is different we update the filter to prevent re-computing all nodes
    if (query !== this.searchQuery) {
      this.searchQuery = query;
      this.filterUpdated();
      const chips = [];
      if (query !== '') {
        chips.push({
          text: query,
          onclick: () => {
            this.updateQuery('');
          }
        });
      } else {
        // make sure to also reset the search store when pressing the "X" on the filter chip
        const store = (0,_store_search_ts__WEBPACK_IMPORTED_MODULE_3__.useSearchStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_2__.getPinia)());
        if (store.scope === 'filter') {
          store.query = '';
        }
      }
      this.updateChips(chips);
    }
  }
}

/***/ }),

/***/ "./apps/files/src/filters/HiddenFilesFilter.ts":
/*!*****************************************************!*\
  !*** ./apps/files/src/filters/HiddenFilesFilter.ts ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerHiddenFilesFilter: () => (/* binding */ registerHiddenFilesFilter)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



class HiddenFilesFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileListFilter {
  constructor() {
    super('files:hidden', 0);
    _defineProperty(this, "showHidden", void 0);
    this.showHidden = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files', 'config', {
      show_hidden: false
    }).show_hidden;
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:config:updated', _ref => {
      let {
        key,
        value
      } = _ref;
      if (key === 'show_hidden') {
        this.showHidden = Boolean(value);
        this.filterUpdated();
      }
    });
  }
  filter(nodes) {
    if (this.showHidden) {
      return nodes;
    }
    return nodes.filter(node => node.attributes.hidden !== true && !node.basename.startsWith('.'));
  }
}
/**
 * Register a file list filter to only show hidden files if enabled by user config
 */
function registerHiddenFilesFilter() {
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileListFilter)(new HiddenFilesFilter());
}

/***/ }),

/***/ "./apps/files/src/filters/ModifiedFilter.ts":
/*!**************************************************!*\
  !*** ./apps/files/src/filters/ModifiedFilter.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerModifiedFilter: () => (/* binding */ registerModifiedFilter)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_FileListFilter_FileListFilterModified_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/FileListFilter/FileListFilterModified.vue */ "./apps/files/src/components/FileListFilter/FileListFilterModified.vue");
/* harmony import */ var _mdi_svg_svg_calendar_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/calendar.svg?raw */ "./node_modules/@mdi/svg/svg/calendar.svg?raw");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }





const startOfToday = () => new Date().setHours(0, 0, 0, 0);
/**
 * Available presets
 */
const timePresets = [{
  id: 'today',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Today'),
  filter: time => time > startOfToday()
}, {
  id: 'last-7',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Last 7 days'),
  filter: time => time > startOfToday() - 7 * 24 * 60 * 60 * 1000
}, {
  id: 'last-30',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Last 30 days'),
  filter: time => time > startOfToday() - 30 * 24 * 60 * 60 * 1000
}, {
  id: 'this-year',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'This year ({year})', {
    year: new Date().getFullYear()
  }),
  filter: time => time > new Date(startOfToday()).setMonth(0, 1)
}, {
  id: 'last-year',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Last year ({year})', {
    year: new Date().getFullYear() - 1
  }),
  filter: time => time > new Date(startOfToday()).setFullYear(new Date().getFullYear() - 1, 0, 1) && time < new Date(startOfToday()).setMonth(0, 1)
}];
class ModifiedFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileListFilter {
  constructor() {
    super('files:modified', 50);
    _defineProperty(this, "currentInstance", void 0);
    _defineProperty(this, "currentPreset", void 0);
  }
  mount(el) {
    if (this.currentInstance) {
      this.currentInstance.$destroy();
    }
    const View = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_components_FileListFilter_FileListFilterModified_vue__WEBPACK_IMPORTED_MODULE_3__["default"]);
    this.currentInstance = new View({
      propsData: {
        timePresets
      },
      el
    }).$on('update:preset', this.setPreset.bind(this)).$mount();
  }
  filter(nodes) {
    if (!this.currentPreset) {
      return nodes;
    }
    return nodes.filter(node => node.mtime === undefined || this.currentPreset.filter(node.mtime.getTime()));
  }
  reset() {
    this.setPreset();
  }
  setPreset(preset) {
    this.currentPreset = preset;
    this.filterUpdated();
    const chips = [];
    if (preset) {
      chips.push({
        icon: _mdi_svg_svg_calendar_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
        text: preset.label,
        onclick: () => this.setPreset()
      });
    } else {
      this.currentInstance?.resetFilter();
    }
    this.updateChips(chips);
  }
}
/**
 * Register the file list filter by modification date
 */
function registerModifiedFilter() {
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileListFilter)(new ModifiedFilter());
}

/***/ }),

/***/ "./apps/files/src/filters/SearchFilter.ts":
/*!************************************************!*\
  !*** ./apps/files/src/filters/SearchFilter.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerFilterToSearchToggle: () => (/* binding */ registerFilterToSearchToggle)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_FileListFilter_FileListFilterToSearch_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/FileListFilter/FileListFilterToSearch.vue */ "./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




class SearchFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileListFilter {
  constructor() {
    super('files:filter-to-search', 999);
    _defineProperty(this, "currentInstance", void 0);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:search:updated', _ref => {
      let {
        query,
        scope
      } = _ref;
      if (query && scope === 'filter') {
        this.currentInstance?.showButton();
      } else {
        this.currentInstance?.hideButton();
      }
    });
  }
  mount(el) {
    if (this.currentInstance) {
      this.currentInstance.$destroy();
    }
    const View = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_components_FileListFilter_FileListFilterToSearch_vue__WEBPACK_IMPORTED_MODULE_3__["default"]);
    this.currentInstance = new View().$mount(el);
  }
  filter(nodes) {
    return nodes;
  }
}
/**
 * Register a file list filter to only show hidden files if enabled by user config
 */
function registerFilterToSearchToggle() {
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileListFilter)(new SearchFilter());
}

/***/ }),

/***/ "./apps/files/src/filters/TypeFilter.ts":
/*!**********************************************!*\
  !*** ./apps/files/src/filters/TypeFilter.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerTypeFilter: () => (/* binding */ registerTypeFilter)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_FileListFilter_FileListFilterType_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/FileListFilter/FileListFilterType.vue */ "./apps/files/src/components/FileListFilter/FileListFilterType.vue");
/* harmony import */ var _mdi_svg_svg_file_document_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/file-document.svg?raw */ "./node_modules/@mdi/svg/svg/file-document.svg?raw");
/* harmony import */ var _mdi_svg_svg_file_table_box_svg_raw__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/svg/svg/file-table-box.svg?raw */ "./node_modules/@mdi/svg/svg/file-table-box.svg?raw");
/* harmony import */ var _mdi_svg_svg_file_presentation_box_svg_raw__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @mdi/svg/svg/file-presentation-box.svg?raw */ "./node_modules/@mdi/svg/svg/file-presentation-box.svg?raw");
/* harmony import */ var _mdi_svg_svg_file_pdf_box_svg_raw__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/svg/svg/file-pdf-box.svg?raw */ "./node_modules/@mdi/svg/svg/file-pdf-box.svg?raw");
/* harmony import */ var _mdi_svg_svg_folder_svg_raw__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @mdi/svg/svg/folder.svg?raw */ "./node_modules/@mdi/svg/svg/folder.svg?raw");
/* harmony import */ var _mdi_svg_svg_music_svg_raw__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @mdi/svg/svg/music.svg?raw */ "./node_modules/@mdi/svg/svg/music.svg?raw");
/* harmony import */ var _mdi_svg_svg_image_svg_raw__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @mdi/svg/svg/image.svg?raw */ "./node_modules/@mdi/svg/svg/image.svg?raw");
/* harmony import */ var _mdi_svg_svg_movie_svg_raw__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @mdi/svg/svg/movie.svg?raw */ "./node_modules/@mdi/svg/svg/movie.svg?raw");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }




// TODO: Create a modern replacement for OC.MimeType...








const colorize = (svg, color) => {
  return svg.replace('<path ', `<path fill="${color}" `);
};
/**
 * Available presets
 */
const getTypePresets = async () => [{
  id: 'document',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Documents'),
  icon: colorize(_mdi_svg_svg_file_document_svg_raw__WEBPACK_IMPORTED_MODULE_4__, '#49abea'),
  mime: ['x-office/document']
}, {
  id: 'spreadsheet',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Spreadsheets'),
  icon: colorize(_mdi_svg_svg_file_table_box_svg_raw__WEBPACK_IMPORTED_MODULE_5__, '#9abd4e'),
  mime: ['x-office/spreadsheet']
}, {
  id: 'presentation',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Presentations'),
  icon: colorize(_mdi_svg_svg_file_presentation_box_svg_raw__WEBPACK_IMPORTED_MODULE_6__, '#f0965f'),
  mime: ['x-office/presentation']
}, {
  id: 'pdf',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'PDFs'),
  icon: colorize(_mdi_svg_svg_file_pdf_box_svg_raw__WEBPACK_IMPORTED_MODULE_7__, '#dc5047'),
  mime: ['application/pdf']
}, {
  id: 'folder',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Folders'),
  icon: colorize(_mdi_svg_svg_folder_svg_raw__WEBPACK_IMPORTED_MODULE_8__, window.getComputedStyle(document.body).getPropertyValue('--color-primary-element')),
  mime: ['httpd/unix-directory']
}, {
  id: 'audio',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Audio'),
  icon: _mdi_svg_svg_music_svg_raw__WEBPACK_IMPORTED_MODULE_9__,
  mime: ['audio']
}, {
  id: 'image',
  // TRANSLATORS: This is for filtering files, e.g. PNG or JPEG, so photos, drawings, or images in general
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Images'),
  icon: _mdi_svg_svg_image_svg_raw__WEBPACK_IMPORTED_MODULE_10__,
  mime: ['image']
}, {
  id: 'video',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Videos'),
  icon: _mdi_svg_svg_movie_svg_raw__WEBPACK_IMPORTED_MODULE_11__,
  mime: ['video']
}];
class TypeFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileListFilter {
  constructor() {
    super('files:type', 10);
    _defineProperty(this, "currentInstance", void 0);
    _defineProperty(this, "currentPresets", void 0);
    _defineProperty(this, "allPresets", void 0);
    this.currentPresets = [];
  }
  async mount(el) {
    // We need to defer this as on init script this is not available:
    if (this.allPresets === undefined) {
      this.allPresets = await getTypePresets();
    }
    // Already mounted
    if (this.currentInstance) {
      this.currentInstance.$destroy();
      delete this.currentInstance;
    }
    const View = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_components_FileListFilter_FileListFilterType_vue__WEBPACK_IMPORTED_MODULE_3__["default"]);
    this.currentInstance = new View({
      propsData: {
        presets: this.currentPresets,
        typePresets: this.allPresets
      },
      el
    }).$on('update:presets', this.setPresets.bind(this)).$mount();
  }
  filter(nodes) {
    if (!this.currentPresets || this.currentPresets.length === 0) {
      return nodes;
    }
    const mimeList = this.currentPresets.reduce((previous, current) => [...previous, ...current.mime], []);
    return nodes.filter(node => {
      if (!node.mime) {
        return false;
      }
      const mime = node.mime.toLowerCase();
      if (mimeList.includes(mime)) {
        return true;
      } else if (mimeList.includes(window.OC.MimeTypeList.aliases[mime])) {
        return true;
      } else if (mimeList.includes(mime.split('/')[0])) {
        return true;
      }
      return false;
    });
  }
  reset() {
    this.setPresets();
  }
  setPresets(presets) {
    this.currentPresets = presets ?? [];
    if (this.currentInstance !== undefined) {
      // could be called before the instance was created
      // (meaning the files list is not mounted yet)
      this.currentInstance.$props.presets = presets;
    }
    this.filterUpdated();
    const chips = [];
    if (presets && presets.length > 0) {
      for (const preset of presets) {
        chips.push({
          icon: preset.icon,
          text: preset.label,
          onclick: () => this.removeFilterPreset(preset.id)
        });
      }
    } else {
      this.currentInstance?.resetFilter();
    }
    this.updateChips(chips);
  }
  /**
   * Helper callback that removed a preset from selected.
   * This is used when clicking on "remove" on a filter-chip.
   * @param presetId Id of preset to remove
   */
  removeFilterPreset(presetId) {
    const filtered = this.currentPresets.filter(_ref => {
      let {
        id
      } = _ref;
      return id !== presetId;
    });
    this.setPresets(filtered);
  }
}
/**
 * Register the file list filter by file type
 */
function registerTypeFilter() {
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileListFilter)(new TypeFilter());
}

/***/ }),

/***/ "./apps/files/src/init.ts":
/*!********************************!*\
  !*** ./apps/files/src/init.ts ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _actions_deleteAction__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./actions/deleteAction */ "./apps/files/src/actions/deleteAction.ts");
/* harmony import */ var _actions_downloadAction__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./actions/downloadAction */ "./apps/files/src/actions/downloadAction.ts");
/* harmony import */ var _actions_openLocallyAction_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./actions/openLocallyAction.ts */ "./apps/files/src/actions/openLocallyAction.ts");
/* harmony import */ var _actions_favoriteAction__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./actions/favoriteAction */ "./apps/files/src/actions/favoriteAction.ts");
/* harmony import */ var _actions_moveOrCopyAction__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./actions/moveOrCopyAction */ "./apps/files/src/actions/moveOrCopyAction.ts");
/* harmony import */ var _actions_openFolderAction__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./actions/openFolderAction */ "./apps/files/src/actions/openFolderAction.ts");
/* harmony import */ var _actions_openInFilesAction__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./actions/openInFilesAction */ "./apps/files/src/actions/openInFilesAction.ts");
/* harmony import */ var _actions_renameAction__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./actions/renameAction */ "./apps/files/src/actions/renameAction.ts");
/* harmony import */ var _actions_sidebarAction__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./actions/sidebarAction */ "./apps/files/src/actions/sidebarAction.ts");
/* harmony import */ var _actions_viewInFolderAction__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./actions/viewInFolderAction */ "./apps/files/src/actions/viewInFolderAction.ts");
/* harmony import */ var _filters_HiddenFilesFilter_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./filters/HiddenFilesFilter.ts */ "./apps/files/src/filters/HiddenFilesFilter.ts");
/* harmony import */ var _filters_TypeFilter_ts__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./filters/TypeFilter.ts */ "./apps/files/src/filters/TypeFilter.ts");
/* harmony import */ var _filters_ModifiedFilter_ts__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./filters/ModifiedFilter.ts */ "./apps/files/src/filters/ModifiedFilter.ts");
/* harmony import */ var _newMenu_newFolder_ts__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./newMenu/newFolder.ts */ "./apps/files/src/newMenu/newFolder.ts");
/* harmony import */ var _newMenu_newTemplatesFolder_ts__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./newMenu/newTemplatesFolder.ts */ "./apps/files/src/newMenu/newTemplatesFolder.ts");
/* harmony import */ var _newMenu_newFromTemplate_ts__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./newMenu/newFromTemplate.ts */ "./apps/files/src/newMenu/newFromTemplate.ts");
/* harmony import */ var _views_favorites_ts__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./views/favorites.ts */ "./apps/files/src/views/favorites.ts");
/* harmony import */ var _views_recent__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./views/recent */ "./apps/files/src/views/recent.ts");
/* harmony import */ var _views_personal_files__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./views/personal-files */ "./apps/files/src/views/personal-files.ts");
/* harmony import */ var _views_files__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./views/files */ "./apps/files/src/views/files.ts");
/* harmony import */ var _views_folderTree_ts__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./views/folderTree.ts */ "./apps/files/src/views/folderTree.ts");
/* harmony import */ var _views_search_ts__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./views/search.ts */ "./apps/files/src/views/search.ts");
/* harmony import */ var _services_ServiceWorker_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./services/ServiceWorker.js */ "./apps/files/src/services/ServiceWorker.js");
/* harmony import */ var _services_LivePhotos__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./services/LivePhotos */ "./apps/files/src/services/LivePhotos.ts");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/* harmony import */ var _actions_convertAction_ts__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./actions/convertAction.ts */ "./apps/files/src/actions/convertAction.ts");
/* harmony import */ var _filters_FilenameFilter_ts__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./filters/FilenameFilter.ts */ "./apps/files/src/filters/FilenameFilter.ts");
/* harmony import */ var _filters_SearchFilter_ts__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./filters/SearchFilter.ts */ "./apps/files/src/filters/SearchFilter.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





























// Register file actions
(0,_actions_convertAction_ts__WEBPACK_IMPORTED_MODULE_26__.registerConvertActions)();
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_deleteAction__WEBPACK_IMPORTED_MODULE_1__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_downloadAction__WEBPACK_IMPORTED_MODULE_2__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_openLocallyAction_ts__WEBPACK_IMPORTED_MODULE_3__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_favoriteAction__WEBPACK_IMPORTED_MODULE_4__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_moveOrCopyAction__WEBPACK_IMPORTED_MODULE_5__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_openFolderAction__WEBPACK_IMPORTED_MODULE_6__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_openInFilesAction__WEBPACK_IMPORTED_MODULE_7__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_renameAction__WEBPACK_IMPORTED_MODULE_8__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_sidebarAction__WEBPACK_IMPORTED_MODULE_9__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_actions_viewInFolderAction__WEBPACK_IMPORTED_MODULE_10__.action);
// Register new menu entry
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.addNewFileMenuEntry)(_newMenu_newFolder_ts__WEBPACK_IMPORTED_MODULE_14__.entry);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.addNewFileMenuEntry)(_newMenu_newTemplatesFolder_ts__WEBPACK_IMPORTED_MODULE_15__.entry);
(0,_newMenu_newFromTemplate_ts__WEBPACK_IMPORTED_MODULE_16__.registerTemplateEntries)();
// Register files views when not on public share
if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_25__.isPublicShare)() === false) {
  (0,_views_favorites_ts__WEBPACK_IMPORTED_MODULE_17__.registerFavoritesView)();
  (0,_views_files__WEBPACK_IMPORTED_MODULE_20__.registerFilesView)();
  (0,_views_personal_files__WEBPACK_IMPORTED_MODULE_19__.registerPersonalFilesView)();
  (0,_views_recent__WEBPACK_IMPORTED_MODULE_18__["default"])();
  (0,_views_search_ts__WEBPACK_IMPORTED_MODULE_22__.registerSearchView)();
  (0,_views_folderTree_ts__WEBPACK_IMPORTED_MODULE_21__.registerFolderTreeView)();
}
// Register file list filters
(0,_filters_HiddenFilesFilter_ts__WEBPACK_IMPORTED_MODULE_11__.registerHiddenFilesFilter)();
(0,_filters_TypeFilter_ts__WEBPACK_IMPORTED_MODULE_12__.registerTypeFilter)();
(0,_filters_ModifiedFilter_ts__WEBPACK_IMPORTED_MODULE_13__.registerModifiedFilter)();
(0,_filters_FilenameFilter_ts__WEBPACK_IMPORTED_MODULE_27__.registerFilenameFilter)();
(0,_filters_SearchFilter_ts__WEBPACK_IMPORTED_MODULE_28__.registerFilterToSearchToggle)();
// Register preview service worker
(0,_services_ServiceWorker_js__WEBPACK_IMPORTED_MODULE_23__["default"])();
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerDavProperty)('nc:hidden', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerDavProperty)('nc:is-mount-root', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerDavProperty)('nc:metadata-blurhash', {
  nc: 'http://nextcloud.org/ns'
});
(0,_services_LivePhotos__WEBPACK_IMPORTED_MODULE_24__.initLivePhotos)();

/***/ }),

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

/***/ "./apps/files/src/newMenu/newFolder.ts":
/*!*********************************************!*\
  !*** ./apps/files/src/newMenu/newFolder.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   entry: () => (/* binding */ entry)
/* harmony export */ });
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_folder_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/svg/svg/folder-plus-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-plus-outline.svg?raw");
/* harmony import */ var _utils_newNodeDialog__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/newNodeDialog */ "./apps/files/src/utils/newNodeDialog.ts");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../logger */ "./apps/files/src/logger.ts");










const createNewFolder = async (root, name) => {
  const source = root.source + '/' + name;
  const encodedSource = root.encodedSource + '/' + encodeURIComponent(name);
  const response = await (0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"])({
    method: 'MKCOL',
    url: encodedSource,
    headers: {
      Overwrite: 'F'
    }
  });
  return {
    fileid: parseInt(response.headers['oc-fileid']),
    source
  };
};
const entry = {
  id: 'newFolder',
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'New folder'),
  enabled: context => Boolean(context.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Permission.CREATE) && Boolean(context.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Permission.READ),
  // Make the svg icon color match the primary element color
  iconSvgInline: _mdi_svg_svg_folder_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_7__.replace(/viewBox/gi, 'style="color: var(--color-primary-element)" viewBox'),
  order: 0,
  async handler(context, content) {
    const name = await (0,_utils_newNodeDialog__WEBPACK_IMPORTED_MODULE_8__.newNodeName)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'New folder'), content);
    if (name === null) {
      return;
    }
    try {
      const {
        fileid,
        source
      } = await createNewFolder(context, name.trim());
      // Create the folder in the store
      const folder = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Folder({
        source,
        id: fileid,
        mtime: new Date(),
        owner: context.owner,
        permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.Permission.ALL,
        root: context?.root || '/files/' + (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getCurrentUser)()?.uid,
        // Include mount-type from parent folder as this is inherited
        attributes: {
          'mount-type': context.attributes?.['mount-type'],
          'owner-id': context.attributes?.['owner-id'],
          'owner-display-name': context.attributes?.['owner-display-name']
        }
      });
      // Show success
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('files:node:created', folder);
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('files', 'Created new folder "{name}"', {
        name: (0,path__WEBPACK_IMPORTED_MODULE_0__.basename)(source)
      }));
      _logger__WEBPACK_IMPORTED_MODULE_9__["default"].debug('Created new folder', {
        folder,
        source
      });
      // Navigate to the new folder
      window.OCP.Files.Router.goToRoute(null,
      // use default route
      {
        view: 'files',
        fileid: String(fileid)
      }, {
        dir: context.path
      });
    } catch (error) {
      _logger__WEBPACK_IMPORTED_MODULE_9__["default"].error('Creating new folder failed', {
        error
      });
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)('Creating new folder failed');
    }
  }
};

/***/ }),

/***/ "./apps/files/src/newMenu/newFromTemplate.ts":
/*!***************************************************!*\
  !*** ./apps/files/src/newMenu/newFromTemplate.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerTemplateEntries: () => (/* binding */ registerTemplateEntries)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _utils_newNodeDialog__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/newNodeDialog */ "./apps/files/src/utils/newNodeDialog.ts");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





// async to reduce bundle size
const TemplatePickerVue = (0,vue__WEBPACK_IMPORTED_MODULE_4__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("apps_files_src_views_TemplatePicker_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/TemplatePicker.vue */ "./apps/files/src/views/TemplatePicker.vue")));
let TemplatePicker = null;
const getTemplatePicker = async context => {
  if (TemplatePicker === null) {
    // Create document root
    const mountingPoint = document.createElement('div');
    mountingPoint.id = 'template-picker';
    document.body.appendChild(mountingPoint);
    // Init vue app
    TemplatePicker = new vue__WEBPACK_IMPORTED_MODULE_4__["default"]({
      render: h => h(TemplatePickerVue, {
        ref: 'picker',
        props: {
          parent: context
        }
      }),
      methods: {
        open() {
          this.$refs.picker.open(...arguments);
        }
      },
      el: mountingPoint
    });
  }
  return TemplatePicker;
};
/**
 * Register all new-file-menu entries for all template providers
 */
function registerTemplateEntries() {
  const templates = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files', 'templates', []);
  // Init template files menu
  templates.forEach((provider, index) => {
    (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.addNewFileMenuEntry)({
      id: `template-new-${provider.app}-${index}`,
      displayName: provider.label,
      iconClass: provider.iconClass || 'icon-file',
      iconSvgInline: provider.iconSvgInline,
      enabled(context) {
        return (context.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.CREATE) !== 0;
      },
      order: 11,
      async handler(context, content) {
        const templatePicker = getTemplatePicker(context);
        const name = await (0,_utils_newNodeDialog__WEBPACK_IMPORTED_MODULE_2__.newNodeName)(`${provider.label}${provider.extension}`, content, {
          label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files', 'Filename'),
          name: provider.label
        });
        if (name !== null) {
          // Create the file
          const picker = await templatePicker;
          picker.open(name.trim(), provider);
        }
      }
    });
  });
}

/***/ }),

/***/ "./apps/files/src/newMenu/newTemplatesFolder.ts":
/*!******************************************************!*\
  !*** ./apps/files/src/newMenu/newTemplatesFolder.ts ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   entry: () => (/* binding */ entry)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _utils_newNodeDialog__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/newNodeDialog */ "./apps/files/src/utils/newNodeDialog.ts");
/* harmony import */ var _mdi_svg_svg_plus_svg_raw__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @mdi/svg/svg/plus.svg?raw */ "./node_modules/@mdi/svg/svg/plus.svg?raw");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");











const templatesEnabled = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__.loadState)('files', 'templates_enabled', true);
let templatesPath = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_3__.loadState)('files', 'templates_path', false);
_logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].debug('Templates folder enabled', {
  templatesEnabled
});
_logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].debug('Initial templates folder', {
  templatesPath
});
/**
 * Init template folder
 * @param directory Folder where to create the templates folder
 * @param name Name to use or the templates folder
 */
const initTemplatesFolder = async function (directory, name) {
  const templatePath = (0,path__WEBPACK_IMPORTED_MODULE_6__.join)(directory.path, name);
  try {
    _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].debug('Initializing the templates directory', {
      templatePath
    });
    const {
      data
    } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_9__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateOcsUrl)('apps/files/api/v1/templates/path'), {
      templatePath,
      copySystemTemplates: true
    });
    // Go to template directory
    window.OCP.Files.Router.goToRoute(null,
    // use default route
    {
      view: 'files',
      fileid: undefined
    }, {
      dir: templatePath
    });
    _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].info('Created new templates folder', {
      ...data.ocs.data
    });
    templatesPath = data.ocs.data.templates_path;
  } catch (error) {
    _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error('Unable to initialize the templates directory');
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Unable to initialize the templates directory'));
  }
};
const entry = {
  id: 'template-picker',
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Create templates folder'),
  iconSvgInline: _mdi_svg_svg_plus_svg_raw__WEBPACK_IMPORTED_MODULE_8__,
  order: 30,
  enabled(context) {
    // Templates disabled or templates folder already initialized
    if (!templatesEnabled || templatesPath) {
      return false;
    }
    // Allow creation on your own folders only
    if (context.owner !== (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid) {
      return false;
    }
    return (context.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.CREATE) !== 0;
  },
  async handler(context, content) {
    const name = await (0,_utils_newNodeDialog__WEBPACK_IMPORTED_MODULE_7__.newNodeName)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'Templates'), content, {
      name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.translate)('files', 'New template folder')
    });
    if (name !== null) {
      // Create the template folder
      initTemplatesFolder(context, name);
      // Remove the menu entry
      (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.removeNewFileMenuEntry)('template-picker');
    }
  }
};

/***/ }),

/***/ "./apps/files/src/services/Favorites.ts":
/*!**********************************************!*\
  !*** ./apps/files/src/services/Favorites.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! cancelable-promise */ "./node_modules/cancelable-promise/umd/CancelablePromise.js");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(cancelable_promise__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Files_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Files.ts */ "./apps/files/src/services/Files.ts");
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");





const getContents = function () {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  // We only filter root files for favorites, for subfolders we can simply reuse the files contents
  if (path !== '/') {
    return (0,_Files_ts__WEBPACK_IMPORTED_MODULE_3__.getContents)(path);
  }
  return new cancelable_promise__WEBPACK_IMPORTED_MODULE_2__.CancelablePromise((resolve, reject, cancel) => {
    const promise = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getFavoriteNodes)(_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__.client).catch(reject).then(contents => {
      if (!contents) {
        reject();
        return;
      }
      resolve({
        contents,
        folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
          id: 0,
          source: `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRemoteURL}${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath}`,
          root: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath,
          owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || null,
          permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ
        })
      });
    });
    cancel(() => promise.cancel());
  });
};

/***/ }),

/***/ "./apps/files/src/services/Files.ts":
/*!******************************************!*\
  !*** ./apps/files/src/services/Files.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultGetContents: () => (/* binding */ defaultGetContents),
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   resultToNode: () => (/* binding */ resultToNode)
/* harmony export */ });
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! cancelable-promise */ "./node_modules/cancelable-promise/umd/CancelablePromise.js");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(cancelable_promise__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _WebDavSearch_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./WebDavSearch.ts */ "./apps/files/src/services/WebDavSearch.ts");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_files_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../store/files.ts */ "./apps/files/src/store/files.ts");
/* harmony import */ var _store_search_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../store/search.ts */ "./apps/files/src/store/search.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");









/**
 * Slim wrapper over `@nextcloud/files` `davResultToNode` to allow using the function with `Array.map`
 * @param stat The result returned by the webdav library
 */
const resultToNode = stat => (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.resultToNode)(stat);
/**
 * Get contents implementation for the files view.
 * This also allows to fetch local search results when the user is currently filtering.
 *
 * @param path - The path to query
 */
function getContents() {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  const controller = new AbortController();
  const searchStore = (0,_store_search_ts__WEBPACK_IMPORTED_MODULE_7__.useSearchStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_5__.getPinia)());
  if (searchStore.query.length >= 3) {
    return new cancelable_promise__WEBPACK_IMPORTED_MODULE_1__.CancelablePromise((resolve, reject, cancel) => {
      cancel(() => controller.abort());
      getLocalSearch(path, searchStore.query, controller.signal).then(resolve).catch(reject);
    });
  } else {
    return defaultGetContents(path);
  }
}
/**
 * Generic `getContents` implementation for the users files.
 *
 * @param path - The path to get the contents
 */
function defaultGetContents(path) {
  path = (0,path__WEBPACK_IMPORTED_MODULE_2__.join)(_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.defaultRootPath, path);
  const controller = new AbortController();
  const propfindPayload = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getDefaultPropfind)();
  return new cancelable_promise__WEBPACK_IMPORTED_MODULE_1__.CancelablePromise(async (resolve, reject, onCancel) => {
    onCancel(() => controller.abort());
    try {
      const contentsResponse = await _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__.client.getDirectoryContents(path, {
        details: true,
        data: propfindPayload,
        includeSelf: true,
        signal: controller.signal
      });
      const root = contentsResponse.data[0];
      const contents = contentsResponse.data.slice(1);
      if (root.filename !== path && `${root.filename}/` !== path) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_8__["default"].debug(`Exepected "${path}" but got filename "${root.filename}" instead.`);
        throw new Error('Root node does not match requested path');
      }
      resolve({
        folder: resultToNode(root),
        contents: contents.map(result => {
          try {
            return resultToNode(result);
          } catch (error) {
            _logger_ts__WEBPACK_IMPORTED_MODULE_8__["default"].error(`Invalid node detected '${result.basename}'`, {
              error
            });
            return null;
          }
        }).filter(Boolean)
      });
    } catch (error) {
      reject(error);
    }
  });
}
/**
 * Get the local search results for the current folder.
 *
 * @param path - The path
 * @param query - The current search query
 * @param signal - The aboort signal
 */
async function getLocalSearch(path, query, signal) {
  const filesStore = (0,_store_files_ts__WEBPACK_IMPORTED_MODULE_6__.useFilesStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_5__.getPinia)());
  let folder = filesStore.getDirectoryByPath('files', path);
  if (!folder) {
    const rootPath = (0,path__WEBPACK_IMPORTED_MODULE_2__.join)(_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.defaultRootPath, path);
    const stat = await _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__.client.stat(rootPath, {
      details: true
    });
    folder = resultToNode(stat.data);
  }
  const contents = await (0,_WebDavSearch_ts__WEBPACK_IMPORTED_MODULE_4__.searchNodes)(query, {
    dir: path,
    signal
  });
  return {
    folder,
    contents
  };
}

/***/ }),

/***/ "./apps/files/src/services/FolderTree.ts":
/*!***********************************************!*\
  !*** ./apps/files/src/services/FolderTree.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   encodeSource: () => (/* binding */ encodeSource),
/* harmony export */   folderTreeId: () => (/* binding */ folderTreeId),
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   getFolderTreeNodes: () => (/* binding */ getFolderTreeNodes),
/* harmony export */   getSourceParent: () => (/* binding */ getSourceParent),
/* harmony export */   sourceRoot: () => (/* binding */ sourceRoot)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _Files_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Files.ts */ "./apps/files/src/services/Files.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */








const folderTreeId = 'folders';
const sourceRoot = `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.davRemoteURL}/files/${(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__.getCurrentUser)()?.uid}`;
const collator = Intl.Collator([(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.getLanguage)(), (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.getCanonicalLocale)()], {
  numeric: true,
  usage: 'sort'
});
const compareNodes = (a, b) => collator.compare(a.displayName ?? a.basename, b.displayName ?? b.basename);
const getTreeNodes = function (tree) {
  let currentPath = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '/';
  let nodes = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
  const sortedTree = tree.toSorted(compareNodes);
  for (const {
    id,
    basename,
    displayName,
    children
  } of sortedTree) {
    const path = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__.joinPaths)(currentPath, basename);
    const source = `${sourceRoot}${path}`;
    const node = {
      source,
      encodedSource: encodeSource(source),
      path,
      fileid: id,
      basename
    };
    if (displayName) {
      node.displayName = displayName;
    }
    nodes.push(node);
    if (children.length > 0) {
      getTreeNodes(children, path, nodes);
    }
  }
  return nodes;
};
const getFolderTreeNodes = async function () {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  let depth = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
  const {
    data: tree
  } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('/apps/files/api/v1/folder-tree'), {
    params: new URLSearchParams({
      path,
      depth: String(depth)
    })
  });
  const nodes = getTreeNodes(tree, path);
  return nodes;
};
const getContents = path => (0,_Files_ts__WEBPACK_IMPORTED_MODULE_6__.getContents)(path);
const encodeSource = source => {
  const {
    origin
  } = new URL(source);
  return origin + (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__.encodePath)(source.slice(origin.length));
};
const getSourceParent = source => {
  const parent = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__.dirname)(source);
  if (parent === sourceRoot) {
    return folderTreeId;
  }
  return encodeSource(parent);
};

/***/ }),

/***/ "./apps/files/src/services/LivePhotos.ts":
/*!***********************************************!*\
  !*** ./apps/files/src/services/LivePhotos.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   initLivePhotos: () => (/* binding */ initLivePhotos),
/* harmony export */   isLivePhoto: () => (/* binding */ isLivePhoto)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 *
 */
function initLivePhotos() {
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerDavProperty)('nc:metadata-files-live-photo', {
    nc: 'http://nextcloud.org/ns'
  });
}
/**
 * @param {Node} node - The node
 */
function isLivePhoto(node) {
  return node.attributes['metadata-files-live-photo'] !== undefined;
}

/***/ }),

/***/ "./apps/files/src/services/PersonalFiles.ts":
/*!**************************************************!*\
  !*** ./apps/files/src/services/PersonalFiles.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   isPersonalFile: () => (/* binding */ isPersonalFile)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _Files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Files */ "./apps/files/src/services/Files.ts");


const currentUserId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid;
/**
 * Filters each file/folder on its shared status
 *
 * A personal file is considered a file that has all of the following properties:
 * 1. the current user owns
 * 2. the file is not shared with anyone
 * 3. the file is not a group folder
 * @todo Move to `@nextcloud/files`
 * @param node The node to check
 */
const isPersonalFile = function (node) {
  // the type of mounts that determine whether the file is shared
  const sharedMountTypes = ['group', 'shared'];
  const mountType = node.attributes['mount-type'];
  return currentUserId === node.owner && !sharedMountTypes.includes(mountType);
};
const getContents = function () {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  // get all the files from the current path as a cancellable promise
  // then filter the files that the user does not own, or has shared / is a group folder
  return (0,_Files__WEBPACK_IMPORTED_MODULE_1__.getContents)(path).then(content => {
    content.contents = content.contents.filter(isPersonalFile);
    return content;
  });
};

/***/ }),

/***/ "./apps/files/src/services/Recent.ts":
/*!*******************************************!*\
  !*** ./apps/files/src/services/Recent.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! cancelable-promise */ "./node_modules/cancelable-promise/umd/CancelablePromise.js");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(cancelable_promise__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _store_userconfig_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../store/userconfig.ts */ "./apps/files/src/store/userconfig.ts");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");







const lastTwoWeeksTimestamp = Math.round(Date.now() / 1000 - 60 * 60 * 24 * 14);
/**
 * Helper to map a WebDAV result to a Nextcloud node
 * The search endpoint already includes the dav remote URL so we must not include it in the source
 *
 * @param stat the WebDAV result
 */
const resultToNode = stat => (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davResultToNode)(stat, _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath, (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.getBaseUrl)());
/**
 * Get recently changed nodes
 *
 * This takes the users preference about hidden files into account.
 * If hidden files are not shown, then also recently changed files *in* hidden directories are filtered.
 *
 * @param path Path to search for recent changes
 */
const getContents = function () {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  const store = (0,_store_userconfig_ts__WEBPACK_IMPORTED_MODULE_3__.useUserConfigStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_4__.getPinia)());
  /**
   * Filter function that returns only the visible nodes - or hidden if explicitly configured
   * @param node The node to check
   */
  const filterHidden = node => path !== '/' // We need to hide files from hidden directories in the root if not configured to show
  || store.userConfig.show_hidden // If configured to show hidden files we can early return
  || !node.dirname.split('/').some(dir => dir.startsWith('.')); // otherwise only include the file if non of the parent directories is hidden
  const controller = new AbortController();
  const handler = async () => {
    const contentsResponse = await _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_5__.client.search('/', {
      signal: controller.signal,
      details: true,
      data: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetRecentSearch)(lastTwoWeeksTimestamp)
    });
    const contents = contentsResponse.data.results.map(resultToNode).filter(filterHidden);
    return {
      folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
        id: 0,
        source: `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRemoteURL}${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath}`,
        root: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath,
        owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || null,
        permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ
      }),
      contents
    };
  };
  return new cancelable_promise__WEBPACK_IMPORTED_MODULE_2__.CancelablePromise(async (resolve, reject, cancel) => {
    cancel(() => controller.abort());
    resolve(handler());
  });
};

/***/ }),

/***/ "./apps/files/src/services/Search.ts":
/*!*******************************************!*\
  !*** ./apps/files/src/services/Search.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! cancelable-promise */ "./node_modules/cancelable-promise/umd/CancelablePromise.js");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(cancelable_promise__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _WebDavSearch_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./WebDavSearch.ts */ "./apps/files/src/services/WebDavSearch.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");
/* harmony import */ var _store_search_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../store/search.ts */ "./apps/files/src/store/search.ts");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../store/index.ts */ "./apps/files/src/store/index.ts");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */








/**
 * Get the contents for a search view
 */
function getContents() {
  const controller = new AbortController();
  const searchStore = (0,_store_search_ts__WEBPACK_IMPORTED_MODULE_6__.useSearchStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_7__.getPinia)());
  return new cancelable_promise__WEBPACK_IMPORTED_MODULE_3__.CancelablePromise(async (resolve, reject, cancel) => {
    cancel(() => controller.abort());
    try {
      const contents = await (0,_WebDavSearch_ts__WEBPACK_IMPORTED_MODULE_4__.searchNodes)(searchStore.query, {
        signal: controller.signal
      });
      resolve({
        contents,
        folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
          id: 0,
          source: `${_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.defaultRemoteURL}#search`,
          owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid,
          permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ
        })
      });
    } catch (error) {
      _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error('Failed to fetch search results', {
        error
      });
      reject(error);
    }
  });
}

/***/ }),

/***/ "./apps/files/src/services/ServiceWorker.js":
/*!**************************************************!*\
  !*** ./apps/files/src/services/ServiceWorker.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  if ('serviceWorker' in navigator) {
    // Use the window load event to keep the page load performant
    window.addEventListener('load', async () => {
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/apps/files/preview-service-worker.js', {}, {
          noRewrite: true
        });
        let scope = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.getRootUrl)();
        // If the instance is not in a subfolder an empty string will be returned.
        // The service worker registration will use the current path if it receives an empty string,
        // which will result in a service worker registration for every single path the user visits.
        if (scope === '') {
          scope = '/';
        }
        const registration = await navigator.serviceWorker.register(url, {
          scope
        });
        _logger_ts__WEBPACK_IMPORTED_MODULE_1__["default"].debug('SW registered: ', {
          registration
        });
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_1__["default"].error('SW registration failed: ', {
          error
        });
      }
    });
  } else {
    _logger_ts__WEBPACK_IMPORTED_MODULE_1__["default"].debug('Service Worker is not enabled on this browser.');
  }
});

/***/ }),

/***/ "./apps/files/src/services/WebDavSearch.ts":
/*!*************************************************!*\
  !*** ./apps/files/src/services/WebDavSearch.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   searchNodes: () => (/* binding */ searchNodes)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





/**
 * Search for nodes matching the given query.
 *
 * @param query - Search query
 * @param options - Options
 * @param options.dir - The base directory to scope the search to
 * @param options.signal - Abort signal for the request
 */
async function searchNodes(query, _ref) {
  let {
    dir,
    signal
  } = _ref;
  const user = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)();
  if (!user) {
    // the search plugin only works for user roots
    return [];
  }
  query = query.trim();
  if (query.length < 3) {
    // the search plugin only works with queries of at least 3 characters
    return [];
  }
  if (dir && !dir.startsWith('/')) {
    dir = `/${dir}`;
  }
  _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].debug('Searching for nodes', {
    query,
    dir
  });
  const {
    data
  } = await _WebdavClient_ts__WEBPACK_IMPORTED_MODULE_3__.client.search('/', {
    details: true,
    signal,
    data: `
<d:searchrequest ${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.getDavNameSpaces)()}>
	 <d:basicsearch>
		 <d:select>
			 <d:prop>
			 ${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.getDavProperties)()}
			 </d:prop>
		 </d:select>
		 <d:from>
			 <d:scope>
				 <d:href>/files/${user.uid}${dir || ''}</d:href>
				 <d:depth>infinity</d:depth>
			 </d:scope>
		 </d:from>
		 <d:where>
			 <d:like>
				 <d:prop>
					 <d:displayname/>
				 </d:prop>
				 <d:literal>%${query.replace('%', '')}%</d:literal>
			 </d:like>
		 </d:where>
		 <d:orderby/>
	</d:basicsearch>
</d:searchrequest>`
  });
  // check if the request was aborted
  if (signal?.aborted) {
    return [];
  }
  // otherwise return the result mapped to Nextcloud nodes
  return data.results.map(result => (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.resultToNode)(result, _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.defaultRootPath, (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.getBaseUrl)()));
}

/***/ }),

/***/ "./apps/files/src/services/WebdavClient.ts":
/*!*************************************************!*\
  !*** ./apps/files/src/services/WebdavClient.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   client: () => (/* binding */ client),
/* harmony export */   fetchNode: () => (/* binding */ fetchNode)
/* harmony export */ });
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");

const client = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getClient)();
const fetchNode = async path => {
  const propfindPayload = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getDefaultPropfind)();
  const result = await client.stat(`${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getRootPath)()}${path}`, {
    details: true,
    data: propfindPayload
  });
  return (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.resultToNode)(result.data);
};

/***/ }),

/***/ "./apps/files/src/store/active.ts":
/*!****************************************!*\
  !*** ./apps/files/src/store/active.ts ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useActiveStore: () => (/* binding */ useActiveStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





const useActiveStore = (0,pinia__WEBPACK_IMPORTED_MODULE_2__.defineStore)('active', () => {
  /**
   * The currently active action
   */
  const activeAction = (0,vue__WEBPACK_IMPORTED_MODULE_3__.ref)();
  /**
   * The currently active folder
   */
  const activeFolder = (0,vue__WEBPACK_IMPORTED_MODULE_3__.ref)();
  /**
   * The current active node within the folder
   */
  const activeNode = (0,vue__WEBPACK_IMPORTED_MODULE_3__.ref)();
  /**
   * The current active view
   */
  const activeView = (0,vue__WEBPACK_IMPORTED_MODULE_3__.ref)();
  initialize();
  /**
   * Unset the active node if deleted
   *
   * @param node - The node thats deleted
   * @private
   */
  function onDeletedNode(node) {
    if (activeNode.value && activeNode.value.source === node.source) {
      activeNode.value = undefined;
    }
  }
  /**
   * Callback to update the current active view
   *
   * @param view - The new active view
   * @private
   */
  function onChangedView() {
    let view = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
    _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].debug('Setting active view', {
      view
    });
    activeView.value = view ?? undefined;
    activeNode.value = undefined;
  }
  /**
   * Initalize the store - connect all event listeners.
   * @private
   */
  function initialize() {
    const navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
    // Make sure we only register the listeners once
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:node:deleted', onDeletedNode);
    onChangedView(navigation.active);
    // Or you can react to changes of the current active view
    navigation.addEventListener('updateActive', event => {
      onChangedView(event.detail);
    });
  }
  return {
    activeAction,
    activeFolder,
    activeNode,
    activeView
  };
});

/***/ }),

/***/ "./apps/files/src/store/files.ts":
/*!***************************************!*\
  !*** ./apps/files/src/store/files.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useFilesStore: () => (/* binding */ useFilesStore)
/* harmony export */ });
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../logger */ "./apps/files/src/logger.ts");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../services/WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _paths_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./paths.ts */ "./apps/files/src/store/paths.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






const useFilesStore = function () {
  const store = (0,pinia__WEBPACK_IMPORTED_MODULE_0__.defineStore)('files', {
    state: () => ({
      files: {},
      roots: {}
    }),
    getters: {
      /**
       * Get a file or folder by its source
       * @param state
       */
      getNode: state => source => state.files[source],
      /**
       * Get a list of files or folders by their IDs
       * Note: does not return undefined values
       * @param state
       */
      getNodes: state => sources => sources.map(source => state.files[source]).filter(Boolean),
      /**
       * Get files or folders by their file ID
       * Multiple nodes can have the same file ID but different sources
       * (e.g. in a shared context)
       * @param state
       */
      getNodesById: state => fileId => Object.values(state.files).filter(node => node.fileid === fileId),
      /**
       * Get the root folder of a service
       * @param state
       */
      getRoot: state => service => state.roots[service]
    },
    actions: {
      /**
       * Get cached directory matching a given path
       *
       * @param service - The service (files view)
       * @param path - The path relative within the service
       * @return The folder if found
       */
      getDirectoryByPath(service, path) {
        const pathsStore = (0,_paths_ts__WEBPACK_IMPORTED_MODULE_5__.usePathsStore)();
        let folder;
        // Get the containing folder from path store
        if (!path || path === '/') {
          folder = this.getRoot(service);
        } else {
          const source = pathsStore.getPath(service, path);
          if (source) {
            folder = this.getNode(source);
          }
        }
        return folder;
      },
      /**
       * Get cached child nodes within a given path
       *
       * @param service - The service (files view)
       * @param path - The path relative within the service
       * @return Array of cached nodes within the path
       */
      getNodesByPath(service, path) {
        const folder = this.getDirectoryByPath(service, path);
        // If we found a cache entry and the cache entry was already loaded (has children) then use it
        return (folder?._children ?? []).map(source => this.getNode(source)).filter(Boolean);
      },
      updateNodes(nodes) {
        // Update the store all at once
        const files = nodes.reduce((acc, node) => {
          if (!node.fileid) {
            _logger__WEBPACK_IMPORTED_MODULE_2__["default"].error('Trying to update/set a node without fileid', {
              node
            });
            return acc;
          }
          acc[node.source] = node;
          return acc;
        }, {});
        vue__WEBPACK_IMPORTED_MODULE_3__["default"].set(this, 'files', {
          ...this.files,
          ...files
        });
      },
      deleteNodes(nodes) {
        nodes.forEach(node => {
          if (node.source) {
            vue__WEBPACK_IMPORTED_MODULE_3__["default"].delete(this.files, node.source);
          }
        });
      },
      setRoot(_ref) {
        let {
          service,
          root
        } = _ref;
        vue__WEBPACK_IMPORTED_MODULE_3__["default"].set(this.roots, service, root);
      },
      onDeletedNode(node) {
        this.deleteNodes([node]);
      },
      onCreatedNode(node) {
        this.updateNodes([node]);
      },
      onMovedNode(_ref2) {
        let {
          node,
          oldSource
        } = _ref2;
        if (!node.fileid) {
          _logger__WEBPACK_IMPORTED_MODULE_2__["default"].error('Trying to update/set a node without fileid', {
            node
          });
          return;
        }
        // Update the path of the node
        vue__WEBPACK_IMPORTED_MODULE_3__["default"].delete(this.files, oldSource);
        this.updateNodes([node]);
      },
      async onUpdatedNode(node) {
        if (!node.fileid) {
          _logger__WEBPACK_IMPORTED_MODULE_2__["default"].error('Trying to update/set a node without fileid', {
            node
          });
          return;
        }
        // If we have multiple nodes with the same file ID, we need to update all of them
        const nodes = this.getNodesById(node.fileid);
        if (nodes.length > 1) {
          await Promise.all(nodes.map(node => (0,_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__.fetchNode)(node.path))).then(this.updateNodes);
          _logger__WEBPACK_IMPORTED_MODULE_2__["default"].debug(nodes.length + ' nodes updated in store', {
            fileid: node.fileid
          });
          return;
        }
        // If we have only one node with the file ID, we can update it directly
        if (nodes.length === 1 && node.source === nodes[0].source) {
          this.updateNodes([node]);
          return;
        }
        // Otherwise, it means we receive an event for a node that is not in the store
        (0,_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_4__.fetchNode)(node.path).then(n => this.updateNodes([n]));
      },
      // Handlers for legacy sidebar (no real nodes support)
      onAddFavorite(node) {
        const ourNode = this.getNode(node.source);
        if (ourNode) {
          vue__WEBPACK_IMPORTED_MODULE_3__["default"].set(ourNode.attributes, 'favorite', 1);
        }
      },
      onRemoveFavorite(node) {
        const ourNode = this.getNode(node.source);
        if (ourNode) {
          vue__WEBPACK_IMPORTED_MODULE_3__["default"].set(ourNode.attributes, 'favorite', 0);
        }
      }
    }
  });
  const fileStore = store(...arguments);
  // Make sure we only register the listeners once
  if (!fileStore._initialized) {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:node:created', fileStore.onCreatedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:node:deleted', fileStore.onDeletedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:node:updated', fileStore.onUpdatedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:node:moved', fileStore.onMovedNode);
    // legacy sidebar
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:favorites:added', fileStore.onAddFavorite);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:favorites:removed', fileStore.onRemoveFavorite);
    fileStore._initialized = true;
  }
  return fileStore;
};

/***/ }),

/***/ "./apps/files/src/store/index.ts":
/*!***************************************!*\
  !*** ./apps/files/src/store/index.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getPinia: () => (/* binding */ getPinia)
/* harmony export */ });
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const getPinia = () => {
  if (window._nc_files_pinia) {
    return window._nc_files_pinia;
  }
  window._nc_files_pinia = (0,pinia__WEBPACK_IMPORTED_MODULE_0__.createPinia)();
  return window._nc_files_pinia;
};

/***/ }),

/***/ "./apps/files/src/store/paths.ts":
/*!***************************************!*\
  !*** ./apps/files/src/store/paths.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   usePathsStore: () => (/* binding */ usePathsStore)
/* harmony export */ });
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger */ "./apps/files/src/logger.ts");
/* harmony import */ var _files__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./files */ "./apps/files/src/store/files.ts");







const usePathsStore = function () {
  const files = (0,_files__WEBPACK_IMPORTED_MODULE_6__.useFilesStore)(...arguments);
  const store = (0,pinia__WEBPACK_IMPORTED_MODULE_0__.defineStore)('paths', {
    state: () => ({
      paths: {}
    }),
    getters: {
      getPath: state => {
        return (service, path) => {
          if (!state.paths[service]) {
            return undefined;
          }
          return state.paths[service][path];
        };
      }
    },
    actions: {
      addPath(payload) {
        // If it doesn't exists, init the service state
        if (!this.paths[payload.service]) {
          vue__WEBPACK_IMPORTED_MODULE_4__["default"].set(this.paths, payload.service, {});
        }
        // Now we can set the provided path
        vue__WEBPACK_IMPORTED_MODULE_4__["default"].set(this.paths[payload.service], payload.path, payload.source);
      },
      deletePath(service, path) {
        // skip if service does not exist
        if (!this.paths[service]) {
          return;
        }
        vue__WEBPACK_IMPORTED_MODULE_4__["default"].delete(this.paths[service], path);
      },
      onCreatedNode(node) {
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.getNavigation)()?.active?.id || 'files';
        if (!node.fileid) {
          _logger__WEBPACK_IMPORTED_MODULE_5__["default"].error('Node has no fileid', {
            node
          });
          return;
        }
        // Only add path if it's a folder
        if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.FileType.Folder) {
          this.addPath({
            service,
            path: node.path,
            source: node.source
          });
        }
        // Update parent folder children if exists
        // If the folder is the root, get it and update it
        this.addNodeToParentChildren(node);
      },
      onDeletedNode(node) {
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.getNavigation)()?.active?.id || 'files';
        if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.FileType.Folder) {
          // Delete the path
          this.deletePath(service, node.path);
        }
        this.deleteNodeFromParentChildren(node);
      },
      onMovedNode(_ref) {
        let {
          node,
          oldSource
        } = _ref;
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.getNavigation)()?.active?.id || 'files';
        // Update the path of the node
        if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.FileType.Folder) {
          // Delete the old path if it exists
          const oldPath = Object.entries(this.paths[service]).find(_ref2 => {
            let [, source] = _ref2;
            return source === oldSource;
          });
          if (oldPath?.[0]) {
            this.deletePath(service, oldPath[0]);
          }
          // Add the new path
          this.addPath({
            service,
            path: node.path,
            source: node.source
          });
        }
        // Dummy simple clone of the renamed node from a previous state
        const oldNode = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.File({
          source: oldSource,
          owner: node.owner,
          mime: node.mime
        });
        this.deleteNodeFromParentChildren(oldNode);
        this.addNodeToParentChildren(node);
      },
      deleteNodeFromParentChildren(node) {
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.getNavigation)()?.active?.id || 'files';
        // Update children of a root folder
        const parentSource = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__.dirname)(node.source);
        const folder = node.dirname === '/' ? files.getRoot(service) : files.getNode(parentSource);
        if (folder) {
          // ensure sources are unique
          const children = new Set(folder._children ?? []);
          children.delete(node.source);
          vue__WEBPACK_IMPORTED_MODULE_4__["default"].set(folder, '_children', [...children.values()]);
          _logger__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Children updated', {
            parent: folder,
            node,
            children: folder._children
          });
          return;
        }
        _logger__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Parent path does not exists, skipping children update', {
          node
        });
      },
      addNodeToParentChildren(node) {
        const service = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.getNavigation)()?.active?.id || 'files';
        // Update children of a root folder
        const parentSource = (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__.dirname)(node.source);
        const folder = node.dirname === '/' ? files.getRoot(service) : files.getNode(parentSource);
        if (folder) {
          // ensure sources are unique
          const children = new Set(folder._children ?? []);
          children.add(node.source);
          vue__WEBPACK_IMPORTED_MODULE_4__["default"].set(folder, '_children', [...children.values()]);
          _logger__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Children updated', {
            parent: folder,
            node,
            children: folder._children
          });
          return;
        }
        _logger__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Parent path does not exists, skipping children update', {
          node
        });
      }
    }
  });
  const pathsStore = store(...arguments);
  // Make sure we only register the listeners once
  if (!pathsStore._initialized) {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:node:created', pathsStore.onCreatedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:node:deleted', pathsStore.onDeletedNode);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:node:moved', pathsStore.onMovedNode);
    pathsStore._initialized = true;
  }
  return pathsStore;
};

/***/ }),

/***/ "./apps/files/src/store/search.ts":
/*!****************************************!*\
  !*** ./apps/files/src/store/search.ts ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useSearchStore: () => (/* binding */ useSearchStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _views_search_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../views/search.ts */ "./apps/files/src/views/search.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






const useSearchStore = (0,pinia__WEBPACK_IMPORTED_MODULE_2__.defineStore)('search', () => {
  /**
   * The current search query
   */
  const query = (0,vue__WEBPACK_IMPORTED_MODULE_3__.ref)('');
  /**
   * Scope of the search.
   * Scopes:
   * - filter: only filter current file list
   * - globally: search everywhere
   */
  const scope = (0,vue__WEBPACK_IMPORTED_MODULE_3__.ref)('filter');
  // reset the base if query is cleared
  (0,vue__WEBPACK_IMPORTED_MODULE_3__.watch)(scope, updateSearch);
  (0,vue__WEBPACK_IMPORTED_MODULE_3__.watch)(query, (old, current) => {
    // skip if only whitespaces changed
    if (old.trim() === current.trim()) {
      return;
    }
    updateSearch();
  });
  // initialize the search store
  initialize();
  /**
   * Debounced update of the current route
   * @private
   */
  const updateRouter = debounce__WEBPACK_IMPORTED_MODULE_1___default()(isSearch => {
    const router = window.OCP.Files.Router;
    router.goToRoute(undefined, {
      view: _views_search_ts__WEBPACK_IMPORTED_MODULE_4__.VIEW_ID
    }, {
      query: query.value
    }, isSearch);
  });
  /**
   * Handle updating the filter if needed.
   * Also update the search view by updating the current route if needed.
   *
   * @private
   */
  function updateSearch() {
    // emit the search event to update the filter
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:search:updated', {
      query: query.value,
      scope: scope.value
    });
    const router = window.OCP.Files.Router;
    // if we are on the search view and the query was unset or scope was set to 'filter' we need to move back to the files view
    if (router.params.view === _views_search_ts__WEBPACK_IMPORTED_MODULE_4__.VIEW_ID && (query.value === '' || scope.value === 'filter')) {
      scope.value = 'filter';
      return router.goToRoute(undefined, {
        view: 'files'
      }, {
        ...router.query,
        query: undefined
      });
    }
    // for the filter scope we do not need to adjust the current route anymore
    // also if the query is empty we do not need to do anything
    if (scope.value === 'filter' || !query.value) {
      return;
    }
    const isSearch = router.params.view === _views_search_ts__WEBPACK_IMPORTED_MODULE_4__.VIEW_ID;
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Update route for updated search query', {
      query: query.value,
      isSearch
    });
    updateRouter(isSearch);
  }
  /**
   * Event handler that resets the store if the file list view was changed.
   *
   * @param view - The new view that is active
   * @private
   */
  function onViewChanged(view) {
    if (view.id !== _views_search_ts__WEBPACK_IMPORTED_MODULE_4__.VIEW_ID) {
      query.value = '';
      scope.value = 'filter';
    }
  }
  /**
   * Initialize the store from the router if needed
   */
  function initialize() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:navigation:changed', onViewChanged);
    const router = window.OCP.Files.Router;
    // if we initially load the search view (e.g. hard page refresh)
    // then we need to initialize the store from the router
    if (router.params.view === _views_search_ts__WEBPACK_IMPORTED_MODULE_4__.VIEW_ID) {
      query.value = [router.query.query].flat()[0] ?? '';
      if (query.value) {
        scope.value = 'globally';
        _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Directly navigated to search view', {
          query: query.value
        });
      } else {
        // we do not have any query so we need to move to the files list
        _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].info('Directly navigated to search view without any query, redirect to files view.');
        router.goToRoute(undefined, {
          ...router.params,
          view: 'files'
        }, {
          ...router.query,
          query: undefined
        }, true);
      }
    }
  }
  return {
    query,
    scope
  };
});

/***/ }),

/***/ "./apps/files/src/store/userconfig.ts":
/*!********************************************!*\
  !*** ./apps/files/src/store/userconfig.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useUserConfigStore: () => (/* binding */ useUserConfigStore)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");







const initialUserConfig = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('files', 'config', {
  crop_image_previews: true,
  default_view: 'files',
  grid_view: false,
  show_files_extensions: true,
  show_hidden: false,
  show_mime_column: true,
  sort_favorites_first: true,
  sort_folders_first: true,
  show_dialog_deletion: false,
  show_dialog_file_extension: true
});
const useUserConfigStore = (0,pinia__WEBPACK_IMPORTED_MODULE_4__.defineStore)('userconfig', () => {
  const userConfig = (0,vue__WEBPACK_IMPORTED_MODULE_5__.ref)({
    ...initialUserConfig
  });
  /**
   * Update the user config local store
   * @param key The config key
   * @param value The new value
   */
  function onUpdate(key, value) {
    (0,vue__WEBPACK_IMPORTED_MODULE_5__.set)(userConfig.value, key, value);
  }
  /**
   * Update the user config local store AND on server side
   * @param key The config key
   * @param value The new value
   */
  async function update(key, value) {
    // only update if a user is logged in (not the case for public shares)
    if ((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)() !== null) {
      await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].put((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('/apps/files/api/v1/config/{key}', {
        key
      }), {
        value
      });
    }
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('files:config:updated', {
      key,
      value
    });
  }
  // Register the event listener
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('files:config:updated', _ref => {
    let {
      key,
      value
    } = _ref;
    return onUpdate(key, value);
  });
  return {
    userConfig,
    update
  };
});

/***/ }),

/***/ "./apps/files/src/utils/filenameValidity.ts":
/*!**************************************************!*\
  !*** ./apps/files/src/utils/filenameValidity.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getFilenameValidity: () => (/* binding */ getFilenameValidity)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/**
 * Get the validity of a filename (empty if valid).
 * This can be used for `setCustomValidity` on input elements
 * @param name The filename
 * @param escape Escape the matched string in the error (only set when used in HTML)
 */
function getFilenameValidity(name) {
  let escape = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  if (name.trim() === '') {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Filename must not be empty.');
  }
  try {
    (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.validateFilename)(name);
    return '';
  } catch (error) {
    if (!(error instanceof _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.InvalidFilenameError)) {
      throw error;
    }
    switch (error.reason) {
      case _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.InvalidFilenameErrorReason.Character:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', '"{char}" is not allowed inside a filename.', {
          char: error.segment
        }, undefined, {
          escape
        });
      case _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.InvalidFilenameErrorReason.ReservedName:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', '"{segment}" is a reserved name and not allowed for filenames.', {
          segment: error.segment
        }, undefined, {
          escape: false
        });
      case _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.InvalidFilenameErrorReason.Extension:
        if (error.segment.match(/\.[a-z]/i)) {
          return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', '"{extension}" is not an allowed filetype.', {
            extension: error.segment
          }, undefined, {
            escape: false
          });
        }
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Filenames must not end with "{extension}".', {
          extension: error.segment
        }, undefined, {
          escape: false
        });
      default:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Invalid filename.');
    }
  }
}

/***/ }),

/***/ "./apps/files/src/utils/filesViews.ts":
/*!********************************************!*\
  !*** ./apps/files/src/utils/filesViews.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultView: () => (/* binding */ defaultView),
/* harmony export */   hasPersonalFilesView: () => (/* binding */ hasPersonalFilesView)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Check whether the personal files view can be shown
 */
function hasPersonalFilesView() {
  const storageStats = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files', 'storageStats', {
    quota: -1
  });
  // Don't show this view if the user has no storage quota
  return storageStats.quota !== 0;
}
/**
 * Get the default files view
 */
function defaultView() {
  const {
    default_view: defaultView
  } = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files', 'config', {
    default_view: 'files'
  });
  // the default view - only use the personal one if it is enabled
  if (defaultView !== 'personal' || hasPersonalFilesView()) {
    return defaultView;
  }
  return 'files';
}

/***/ }),

/***/ "./apps/files/src/utils/hashUtils.ts":
/*!*******************************************!*\
  !*** ./apps/files/src/utils/hashUtils.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   hashCode: () => (/* binding */ hashCode)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/**
 * Simple non-secure hashing function similar to Java's `hashCode`
 * @param str The string to hash
 * @return {number} a non secure hash of the string
 */
const hashCode = function (str) {
  let hash = 0;
  for (let i = 0; i < str.length; i++) {
    hash = (hash << 5) - hash + str.charCodeAt(i) | 0;
  }
  return hash >>> 0;
};

/***/ }),

/***/ "./apps/files/src/utils/newNodeDialog.ts":
/*!***********************************************!*\
  !*** ./apps/files/src/utils/newNodeDialog.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   newNodeName: () => (/* binding */ newNodeName)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _components_NewNodeDialog_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../components/NewNodeDialog.vue */ "./apps/files/src/components/NewNodeDialog.vue");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/**
 * Ask user for file or folder name
 * @param defaultName Default name to use
 * @param folderContent Nodes with in the current folder to check for unique name
 * @param labels Labels to set on the dialog
 * @return string if successful otherwise null if aborted
 */
function newNodeName(defaultName, folderContent) {
  let labels = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  const contentNames = folderContent.map(node => node.basename);
  return new Promise(resolve => {
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.spawnDialog)(_components_NewNodeDialog_vue__WEBPACK_IMPORTED_MODULE_1__["default"], {
      ...labels,
      defaultName,
      otherNames: contentNames
    }, folderName => {
      resolve(folderName);
    });
  });
}

/***/ }),

/***/ "./apps/files/src/utils/permissions.ts":
/*!*********************************************!*\
  !*** ./apps/files/src/utils/permissions.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isDownloadable: () => (/* binding */ isDownloadable)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");

/**
 * Check permissions on the node if it can be downloaded
 * @param node The node to check
 * @return True if downloadable, false otherwise
 */
function isDownloadable(node) {
  if ((node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.READ) === 0) {
    return false;
  }
  // check hide-download property of shares
  if (node.attributes['hide-download'] === true || node.attributes['hide-download'] === 'true') {
    return false;
  }
  // If the mount type is a share, ensure it got download permissions.
  if (node.attributes['share-attributes']) {
    const shareAttributes = JSON.parse(node.attributes['share-attributes'] || '[]');
    const downloadAttribute = shareAttributes.find(_ref => {
      let {
        scope,
        key
      } = _ref;
      return scope === 'permissions' && key === 'download';
    });
    if (downloadAttribute !== undefined) {
      return downloadAttribute.value === true;
    }
  }
  return true;
}

/***/ }),

/***/ "./apps/files/src/views/favorites.ts":
/*!*******************************************!*\
  !*** ./apps/files/src/views/favorites.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerFavoritesView: () => (/* binding */ registerFavoritesView)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/folder-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/svg/svg/star-outline.svg?raw */ "./node_modules/@mdi/svg/svg/star-outline.svg?raw");
/* harmony import */ var _services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../services/WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _services_Favorites__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../services/Favorites */ "./apps/files/src/services/Favorites.ts");
/* harmony import */ var _utils_hashUtils__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/hashUtils */ "./apps/files/src/utils/hashUtils.ts");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../logger */ "./apps/files/src/logger.ts");










const generateFavoriteFolderView = function (folder) {
  let index = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  return new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.View({
    id: generateIdFromPath(folder.path),
    name: folder.displayname,
    icon: _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
    order: index,
    params: {
      dir: folder.path,
      fileid: String(folder.fileid),
      view: 'favorites'
    },
    parent: 'favorites',
    columns: [],
    getContents: _services_Favorites__WEBPACK_IMPORTED_MODULE_7__.getContents
  });
};
const generateIdFromPath = function (path) {
  return `favorite-${(0,_utils_hashUtils__WEBPACK_IMPORTED_MODULE_8__.hashCode)(path)}`;
};
const registerFavoritesView = async () => {
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.View({
    id: 'favorites',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Favorites'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'List of favorite files and folders.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'No favorites yet'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Files and folders you mark as favorite will show up here'),
    icon: _mdi_svg_svg_star_outline_svg_raw__WEBPACK_IMPORTED_MODULE_5__,
    order: 15,
    columns: [],
    getContents: _services_Favorites__WEBPACK_IMPORTED_MODULE_7__.getContents
  }));
  const favoriteFolders = (await (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_2__.getFavoriteNodes)(_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__.client)).filter(node => node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.Folder);
  const favoriteFoldersViews = favoriteFolders.map((folder, index) => generateFavoriteFolderView(folder, index));
  _logger__WEBPACK_IMPORTED_MODULE_9__["default"].debug('Generating favorites view', {
    favoriteFolders
  });
  favoriteFoldersViews.forEach(view => Navigation.register(view));
  /**
   * Update favorites navigation when a new folder is added
   */
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:favorites:added', node => {
    if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.Folder) {
      return;
    }
    // Sanity check
    if (node.path === null || !node.root?.startsWith('/files')) {
      _logger__WEBPACK_IMPORTED_MODULE_9__["default"].error('Favorite folder is not within user files root', {
        node
      });
      return;
    }
    addToFavorites(node);
  });
  /**
   * Remove favorites navigation when a folder is removed
   */
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:favorites:removed', node => {
    if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.Folder) {
      return;
    }
    // Sanity check
    if (node.path === null || !node.root?.startsWith('/files')) {
      _logger__WEBPACK_IMPORTED_MODULE_9__["default"].error('Favorite folder is not within user files root', {
        node
      });
      return;
    }
    removePathFromFavorites(node.path);
  });
  /**
   * Update favorites navigation when a folder is renamed
   */
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:node:renamed', node => {
    if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.Folder) {
      return;
    }
    if (node.attributes.favorite !== 1) {
      return;
    }
    updateNodeFromFavorites(node);
  });
  /**
   * Sort the favorites paths array and
   * update the order property of the existing views
   */
  const updateAndSortViews = function () {
    favoriteFolders.sort((a, b) => a.basename.localeCompare(b.basename, [(0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.getLanguage)(), (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.getCanonicalLocale)()], {
      ignorePunctuation: true,
      numeric: true,
      usage: 'sort'
    }));
    favoriteFolders.forEach((folder, index) => {
      const view = favoriteFoldersViews.find(view => view.id === generateIdFromPath(folder.path));
      if (view) {
        view.order = index;
      }
    });
  };
  // Add a folder to the favorites paths array and update the views
  const addToFavorites = function (node) {
    const view = generateFavoriteFolderView(node);
    // Skip if already exists
    if (favoriteFolders.find(folder => folder.path === node.path)) {
      return;
    }
    // Update arrays
    favoriteFolders.push(node);
    favoriteFoldersViews.push(view);
    // Update and sort views
    updateAndSortViews();
    Navigation.register(view);
  };
  // Remove a folder from the favorites paths array and update the views
  const removePathFromFavorites = function (path) {
    const id = generateIdFromPath(path);
    const index = favoriteFolders.findIndex(folder => folder.path === path);
    // Skip if not exists
    if (index === -1) {
      return;
    }
    // Update arrays
    favoriteFolders.splice(index, 1);
    favoriteFoldersViews.splice(index, 1);
    // Update and sort views
    Navigation.remove(id);
    updateAndSortViews();
  };
  // Update a folder from the favorites paths array and update the views
  const updateNodeFromFavorites = function (node) {
    const favoriteFolder = favoriteFolders.find(folder => folder.fileid === node.fileid);
    // Skip if it does not exists
    if (favoriteFolder === undefined) {
      return;
    }
    removePathFromFavorites(favoriteFolder.path);
    addToFavorites(node);
  };
  updateAndSortViews();
};

/***/ }),

/***/ "./apps/files/src/views/files.ts":
/*!***************************************!*\
  !*** ./apps/files/src/views/files.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VIEW_ID: () => (/* binding */ VIEW_ID),
/* harmony export */   registerFilesView: () => (/* binding */ registerFilesView)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _services_Files_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/Files.ts */ "./apps/files/src/services/Files.ts");
/* harmony import */ var _store_active_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../store/active.ts */ "./apps/files/src/store/active.ts");
/* harmony import */ var _utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/filesViews.ts */ "./apps/files/src/utils/filesViews.ts");
/* harmony import */ var _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @mdi/svg/svg/folder-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-outline.svg?raw");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const VIEW_ID = 'files';
/**
 * Register the files view to the navigation
 */
function registerFilesView() {
  // we cache the query to allow more performant search (see below in event listener)
  let oldQuery = '';
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: VIEW_ID,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'All files'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'List of your files and folders.'),
    icon: _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__,
    // if this is the default view we set it at the top of the list - otherwise below it
    order: (0,_utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_5__.defaultView)() === VIEW_ID ? 0 : 5,
    getContents: _services_Files_ts__WEBPACK_IMPORTED_MODULE_3__.getContents
  }));
  // when the search is updated
  // and we are in the files view
  // and there is already a folder fetched
  // then we "update" it to trigger a new `getContents` call to search for the query while the filelist is filtered
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:search:updated', _ref => {
    let {
      scope,
      query
    } = _ref;
    if (scope === 'globally') {
      return;
    }
    if (Navigation.active?.id !== VIEW_ID) {
      return;
    }
    // If neither the old query nor the new query is longer than the search minimum
    // then we do not need to trigger a new PROPFIND / SEARCH
    // so we skip unneccessary requests here
    if (oldQuery.length < 3 && query.length < 3) {
      return;
    }
    const store = (0,_store_active_ts__WEBPACK_IMPORTED_MODULE_4__.useActiveStore)();
    if (!store.activeFolder) {
      return;
    }
    oldQuery = query;
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:updated', store.activeFolder);
  });
}

/***/ }),

/***/ "./apps/files/src/views/folderTree.ts":
/*!********************************************!*\
  !*** ./apps/files/src/views/folderTree.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerFolderTreeView: () => (/* binding */ registerFolderTreeView)
/* harmony export */ });
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @mdi/svg/svg/folder-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/svg/svg/folder-multiple-outline.svg?raw */ "./node_modules/@mdi/svg/svg/folder-multiple-outline.svg?raw");
/* harmony import */ var _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../services/FolderTree.ts */ "./apps/files/src/services/FolderTree.ts");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */









const isFolderTreeEnabled = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__.loadState)('files', 'config', {
  folder_tree: true
}).folder_tree;
let showHiddenFiles = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_5__.loadState)('files', 'config', {
  show_hidden: false
}).show_hidden;
const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
const queue = new p_queue__WEBPACK_IMPORTED_MODULE_0__["default"]({
  concurrency: 5,
  intervalCap: 5,
  interval: 200
});
const registerQueue = new p_queue__WEBPACK_IMPORTED_MODULE_0__["default"]({
  concurrency: 5,
  intervalCap: 5,
  interval: 200
});
const registerTreeChildren = async function () {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  await queue.add(async () => {
    const nodes = await (0,_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getFolderTreeNodes)(path);
    const promises = nodes.map(node => registerQueue.add(() => registerNodeView(node)));
    await Promise.allSettled(promises);
  });
};
const getLoadChildViews = node => {
  return async view => {
    // @ts-expect-error Custom property on View instance
    if (view.loading || view.loaded) {
      return;
    }
    // @ts-expect-error Custom property
    view.loading = true;
    await registerTreeChildren(node.path);
    // @ts-expect-error Custom property
    view.loading = false;
    // @ts-expect-error Custom property
    view.loaded = true;
    // @ts-expect-error No payload
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:navigation:updated');
    // @ts-expect-error No payload
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:folder-tree:expanded');
  };
};
const registerNodeView = node => {
  const registeredView = Navigation.views.find(view => view.id === node.encodedSource);
  if (registeredView) {
    Navigation.remove(registeredView.id);
  }
  if (!showHiddenFiles && node.basename.startsWith('.')) {
    return;
  }
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: node.encodedSource,
    parent: (0,_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getSourceParent)(node.source),
    // @ts-expect-error Casing differences
    name: node.displayName ?? node.displayname ?? node.basename,
    icon: _mdi_svg_svg_folder_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__,
    getContents: _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getContents,
    loadChildViews: getLoadChildViews(node),
    params: {
      view: _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.folderTreeId,
      fileid: String(node.fileid),
      // Needed for matching exact routes
      dir: node.path
    }
  }));
};
const removeFolderView = folder => {
  const viewId = folder.encodedSource;
  Navigation.remove(viewId);
};
const removeFolderViewSource = source => {
  Navigation.remove(source);
};
const onCreateNode = node => {
  if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
    return;
  }
  registerNodeView(node);
};
const onDeleteNode = node => {
  if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
    return;
  }
  removeFolderView(node);
};
const onMoveNode = _ref => {
  let {
    node,
    oldSource
  } = _ref;
  if (node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
    return;
  }
  removeFolderViewSource(oldSource);
  registerNodeView(node);
  const newPath = node.source.replace(_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.sourceRoot, '');
  const oldPath = oldSource.replace(_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.sourceRoot, '');
  const childViews = Navigation.views.filter(view => {
    if (!view.params?.dir) {
      return false;
    }
    if ((0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_4__.isSamePath)(view.params.dir, oldPath)) {
      return false;
    }
    return view.params.dir.startsWith(oldPath);
  });
  for (const view of childViews) {
    // @ts-expect-error FIXME Allow setting parent
    view.parent = (0,_services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getSourceParent)(node.source);
    // @ts-expect-error dir param is defined
    view.params.dir = view.params.dir.replace(oldPath, newPath);
  }
};
const onUserConfigUpdated = async _ref2 => {
  let {
    key,
    value
  } = _ref2;
  if (key === 'show_hidden') {
    showHiddenFiles = value;
    await registerTreeChildren();
    // @ts-expect-error No payload
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:folder-tree:initialized');
  }
};
const registerTreeRoot = () => {
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.folderTreeId,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files', 'Folder tree'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files', 'List of your files and folders.'),
    icon: _mdi_svg_svg_folder_multiple_outline_svg_raw__WEBPACK_IMPORTED_MODULE_7__,
    order: 50,
    // Below all other views
    getContents: _services_FolderTree_ts__WEBPACK_IMPORTED_MODULE_8__.getContents
  }));
};
const registerFolderTreeView = async () => {
  if (!isFolderTreeEnabled) {
    return;
  }
  registerTreeRoot();
  await registerTreeChildren();
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:node:created', onCreateNode);
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:node:deleted', onDeleteNode);
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:node:moved', onMoveNode);
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:config:updated', onUserConfigUpdated);
  // @ts-expect-error No payload
  (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:folder-tree:initialized');
};

/***/ }),

/***/ "./apps/files/src/views/personal-files.ts":
/*!************************************************!*\
  !*** ./apps/files/src/views/personal-files.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VIEW_ID: () => (/* binding */ VIEW_ID),
/* harmony export */   registerPersonalFilesView: () => (/* binding */ registerPersonalFilesView)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _services_PersonalFiles_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/PersonalFiles.ts */ "./apps/files/src/services/PersonalFiles.ts");
/* harmony import */ var _utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/filesViews.ts */ "./apps/files/src/utils/filesViews.ts");
/* harmony import */ var _mdi_svg_svg_account_outline_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/account-outline.svg?raw */ "./node_modules/@mdi/svg/svg/account-outline.svg?raw");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





const VIEW_ID = 'personal';
/**
 * Register the personal files view if allowed
 */
function registerPersonalFilesView() {
  if (!(0,_utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_3__.hasPersonalFilesView)()) {
    return;
  }
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: VIEW_ID,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files', 'Personal files'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files', 'List of your files and folders that are not shared.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files', 'No personal files found'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files', 'Files that are not shared will show up here.'),
    icon: _mdi_svg_svg_account_outline_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
    // if this is the default view we set it at the top of the list - otherwise default position of fifth
    order: (0,_utils_filesViews_ts__WEBPACK_IMPORTED_MODULE_3__.defaultView)() === VIEW_ID ? 0 : 5,
    getContents: _services_PersonalFiles_ts__WEBPACK_IMPORTED_MODULE_2__.getContents
  }));
}

/***/ }),

/***/ "./apps/files/src/views/recent.ts":
/*!****************************************!*\
  !*** ./apps/files/src/views/recent.ts ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_history_svg_raw__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/svg/svg/history.svg?raw */ "./node_modules/@mdi/svg/svg/history.svg?raw");
/* harmony import */ var _services_Recent__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/Recent */ "./apps/files/src/services/Recent.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.View({
    id: 'recent',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'Recent'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'List of recently modified files and folders.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'No recently modified files'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'Files and folders you recently modified will show up here.'),
    icon: _mdi_svg_svg_history_svg_raw__WEBPACK_IMPORTED_MODULE_2__,
    order: 10,
    defaultSortKey: 'mtime',
    getContents: _services_Recent__WEBPACK_IMPORTED_MODULE_3__.getContents
  }));
});

/***/ }),

/***/ "./apps/files/src/views/search.ts":
/*!****************************************!*\
  !*** ./apps/files/src/views/search.ts ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VIEW_ID: () => (/* binding */ VIEW_ID),
/* harmony export */   registerSearchView: () => (/* binding */ registerSearchView)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _services_Search_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/Search.ts */ "./apps/files/src/services/Search.ts");
/* harmony import */ var _files_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./files.ts */ "./apps/files/src/views/files.ts");
/* harmony import */ var _mdi_svg_svg_magnify_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/magnify.svg?raw */ "./node_modules/@mdi/svg/svg/magnify.svg?raw");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






const VIEW_ID = 'search';
/**
 * Register the search-in-files view
 */
function registerSearchView() {
  let instance;
  let view;
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.View({
    id: VIEW_ID,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Search'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files', 'Search results within your files.'),
    async emptyView(el) {
      if (!view) {
        view = (await Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("apps_files_src_views_SearchEmptyView_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./SearchEmptyView.vue */ "./apps/files/src/views/SearchEmptyView.vue"))).default;
      } else {
        instance.$destroy();
      }
      instance = new vue__WEBPACK_IMPORTED_MODULE_5__["default"](view);
      instance.$mount(el);
    },
    icon: _mdi_svg_svg_magnify_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
    order: 10,
    parent: _files_ts__WEBPACK_IMPORTED_MODULE_3__.VIEW_ID,
    // it should be shown expanded
    expanded: true,
    // this view is hidden by default and only shown when active
    hidden: true,
    getContents: _services_Search_ts__WEBPACK_IMPORTED_MODULE_2__.getContents
  }));
}

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

/***/ "./node_modules/@mdi/svg/svg/account-outline.svg?raw":
/*!***********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/account-outline.svg?raw ***!
  \***********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-account-outline\" viewBox=\"0 0 24 24\"><path d=\"M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,6A2,2 0 0,0 10,8A2,2 0 0,0 12,10A2,2 0 0,0 14,8A2,2 0 0,0 12,6M12,13C14.67,13 20,14.33 20,17V20H4V17C4,14.33 9.33,13 12,13M12,14.9C9.03,14.9 5.9,16.36 5.9,17V18.1H18.1V17C18.1,16.36 14.97,14.9 12,14.9Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/arrow-down.svg?raw":
/*!******************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/arrow-down.svg?raw ***!
  \******************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-arrow-down\" viewBox=\"0 0 24 24\"><path d=\"M11,4H13V16L18.5,10.5L19.92,11.92L12,19.84L4.08,11.92L5.5,10.5L11,16V4Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/autorenew.svg?raw":
/*!*****************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/autorenew.svg?raw ***!
  \*****************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-autorenew\" viewBox=\"0 0 24 24\"><path d=\"M12,6V9L16,5L12,1V4A8,8 0 0,0 4,12C4,13.57 4.46,15.03 5.24,16.26L6.7,14.8C6.25,13.97 6,13 6,12A6,6 0 0,1 12,6M18.76,7.74L17.3,9.2C17.74,10.04 18,11 18,12A6,6 0 0,1 12,18V15L8,19L12,23V20A8,8 0 0,0 20,12C20,10.43 19.54,8.97 18.76,7.74Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/calendar.svg?raw":
/*!****************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/calendar.svg?raw ***!
  \****************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-calendar\" viewBox=\"0 0 24 24\"><path d=\"M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/file-document.svg?raw":
/*!*********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-document.svg?raw ***!
  \*********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-document\" viewBox=\"0 0 24 24\"><path d=\"M13,9H18.5L13,3.5V9M6,2H14L20,8V20A2,2 0 0,1 18,22H6C4.89,22 4,21.1 4,20V4C4,2.89 4.89,2 6,2M15,18V16H6V18H15M18,14V12H6V14H18Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/file-pdf-box.svg?raw":
/*!********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-pdf-box.svg?raw ***!
  \********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-pdf-box\" viewBox=\"0 0 24 24\"><path d=\"M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3M9.5 11.5C9.5 12.3 8.8 13 8 13H7V15H5.5V9H8C8.8 9 9.5 9.7 9.5 10.5V11.5M14.5 13.5C14.5 14.3 13.8 15 13 15H10.5V9H13C13.8 9 14.5 9.7 14.5 10.5V13.5M18.5 10.5H17V11.5H18.5V13H17V15H15.5V9H18.5V10.5M12 10.5H13V13.5H12V10.5M7 10.5H8V11.5H7V10.5Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/file-presentation-box.svg?raw":
/*!*****************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-presentation-box.svg?raw ***!
  \*****************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-presentation-box\" viewBox=\"0 0 24 24\"><path d=\"M19,16H5V8H19M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/file-table-box.svg?raw":
/*!**********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-table-box.svg?raw ***!
  \**********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-table-box\" viewBox=\"0 0 24 24\"><path d=\"M19 3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3M9 18H6V16H9V18M9 15H6V13H9V15M9 12H6V10H9V12M13 18H10V16H13V18M13 15H10V13H13V15M13 12H10V10H13V12Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/folder-move-outline.svg?raw":
/*!***************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder-move-outline.svg?raw ***!
  \***************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder-move-outline\" viewBox=\"0 0 24 24\"><path d=\"M20 18H4V8H20V18M12 6L10 4H4C2.9 4 2 4.89 2 6V18C2 19.11 2.9 20 4 20H20C21.11 20 22 19.11 22 18V8C22 6.9 21.11 6 20 6H12M11 14V12H15V9L19 13L15 17V14H11Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/folder-plus-outline.svg?raw":
/*!***************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder-plus-outline.svg?raw ***!
  \***************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder-plus-outline\" viewBox=\"0 0 24 24\"><path d=\"M13 19C13 19.34 13.04 19.67 13.09 20H4C2.9 20 2 19.11 2 18V6C2 4.89 2.89 4 4 4H10L12 6H20C21.1 6 22 6.89 22 8V13.81C21.39 13.46 20.72 13.22 20 13.09V8H4V18H13.09C13.04 18.33 13 18.66 13 19M20 18V15H18V18H15V20H18V23H20V20H23V18H20Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/folder.svg?raw":
/*!**************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/folder.svg?raw ***!
  \**************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-folder\" viewBox=\"0 0 24 24\"><path d=\"M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/history.svg?raw":
/*!***************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/history.svg?raw ***!
  \***************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-history\" viewBox=\"0 0 24 24\"><path d=\"M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/image.svg?raw":
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/image.svg?raw ***!
  \*************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-image\" viewBox=\"0 0 24 24\"><path d=\"M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/laptop.svg?raw":
/*!**************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/laptop.svg?raw ***!
  \**************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-laptop\" viewBox=\"0 0 24 24\"><path d=\"M4,6H20V16H4M20,18A2,2 0 0,0 22,16V6C22,4.89 21.1,4 20,4H4C2.89,4 2,4.89 2,6V16A2,2 0 0,0 4,18H0V20H24V18H20Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/movie.svg?raw":
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/movie.svg?raw ***!
  \*************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-movie\" viewBox=\"0 0 24 24\"><path d=\"M18,4L20,8H17L15,4H13L15,8H12L10,4H8L10,8H7L5,4H4A2,2 0 0,0 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V4H18Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/music.svg?raw":
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/music.svg?raw ***!
  \*************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-music\" viewBox=\"0 0 24 24\"><path d=\"M21,3V15.5A3.5,3.5 0 0,1 17.5,19A3.5,3.5 0 0,1 14,15.5A3.5,3.5 0 0,1 17.5,12C18.04,12 18.55,12.12 19,12.34V6.47L9,8.6V17.5A3.5,3.5 0 0,1 5.5,21A3.5,3.5 0 0,1 2,17.5A3.5,3.5 0 0,1 5.5,14C6.04,14 6.55,14.12 7,14.34V6L21,3Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/network-off.svg?raw":
/*!*******************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/network-off.svg?raw ***!
  \*******************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-network-off\" viewBox=\"0 0 24 24\"><path d=\"M1,5.27L5,9.27V15A2,2 0 0,0 7,17H11V19H10A1,1 0 0,0 9,20H2V22H9A1,1 0 0,0 10,23H14A1,1 0 0,0 15,22H17.73L19.73,24L21,22.72L2.28,4L1,5.27M15,20A1,1 0 0,0 14,19H13V17.27L15.73,20H15M17.69,16.87L5.13,4.31C5.41,3.55 6.14,3 7,3H17A2,2 0 0,1 19,5V15C19,15.86 18.45,16.59 17.69,16.87M22,20V21.18L20.82,20H22Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/plus.svg?raw":
/*!************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/plus.svg?raw ***!
  \************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-plus\" viewBox=\"0 0 24 24\"><path d=\"M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/web.svg?raw":
/*!***********************************************!*\
  !*** ./node_modules/@mdi/svg/svg/web.svg?raw ***!
  \***********************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-web\" viewBox=\"0 0 24 24\"><path d=\"M16.36,14C16.44,13.34 16.5,12.68 16.5,12C16.5,11.32 16.44,10.66 16.36,10H19.74C19.9,10.64 20,11.31 20,12C20,12.69 19.9,13.36 19.74,14M14.59,19.56C15.19,18.45 15.65,17.25 15.97,16H18.92C17.96,17.65 16.43,18.93 14.59,19.56M14.34,14H9.66C9.56,13.34 9.5,12.68 9.5,12C9.5,11.32 9.56,10.65 9.66,10H14.34C14.43,10.65 14.5,11.32 14.5,12C14.5,12.68 14.43,13.34 14.34,14M12,19.96C11.17,18.76 10.5,17.43 10.09,16H13.91C13.5,17.43 12.83,18.76 12,19.96M8,8H5.08C6.03,6.34 7.57,5.06 9.4,4.44C8.8,5.55 8.35,6.75 8,8M5.08,16H8C8.35,17.25 8.8,18.45 9.4,19.56C7.57,18.93 6.03,17.65 5.08,16M4.26,14C4.1,13.36 4,12.69 4,12C4,11.31 4.1,10.64 4.26,10H7.64C7.56,10.66 7.5,11.32 7.5,12C7.5,12.68 7.56,13.34 7.64,14M12,4.03C12.83,5.23 13.5,6.57 13.91,8H10.09C10.5,6.57 11.17,5.23 12,4.03M18.92,8H15.97C15.65,6.75 15.19,5.55 14.59,4.44C16.43,5.07 17.96,6.34 18.92,8M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z\" /></svg>";

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionSeparator */ "./node_modules/@nextcloud/vue/dist/Components/NcActionSeparator.mjs");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'FileListFilter',
  props: {
    isActive: {
      type: Boolean,
      required: true
    },
    filterName: {
      type: String,
      required: true
    }
  },
  emits: ["reset-filter"],
  setup(__props) {
    return {
      __sfc: true,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t,
      NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_2__["default"],
      NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__["default"],
      NcActionSeparator: _nextcloud_vue_components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_4__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&lang=ts":
/*!*******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=script&lang=ts ***!
  \*******************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _FileListFilter_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FileListFilter.vue */ "./apps/files/src/components/FileListFilter/FileListFilter.vue");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_2__.defineComponent)({
  components: {
    FileListFilter: _FileListFilter_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    timePresets: {
      type: Array,
      required: true
    }
  },
  setup() {
    return {
      // icons used in template
      mdiCalendarRangeOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiCalendarRangeOutline
    };
  },
  data() {
    return {
      selectedOption: null,
      timeRangeEnd: null,
      timeRangeStart: null
    };
  },
  computed: {
    /**
     * Is the filter currently active
     */
    isActive() {
      return this.selectedOption !== null;
    },
    currentPreset() {
      return this.timePresets.find(_ref => {
        let {
          id
        } = _ref;
        return id === this.selectedOption;
      }) ?? null;
    }
  },
  watch: {
    selectedOption() {
      if (this.selectedOption === null) {
        this.$emit('update:preset');
      } else {
        const preset = this.currentPreset;
        this.$emit('update:preset', preset);
      }
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
    resetFilter() {
      this.selectedOption = null;
      this.timeRangeEnd = null;
      this.timeRangeStart = null;
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=script&setup=true&lang=ts":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=script&setup=true&lang=ts ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _store_index_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../store/index.ts */ "./apps/files/src/store/index.ts");
/* harmony import */ var _store_search_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../store/search.ts */ "./apps/files/src/store/search.ts");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'FileListFilterToSearch',
  setup(__props, _ref) {
    let {
      expose
    } = _ref;
    const isVisible = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    expose({
      hideButton,
      showButton
    });
    /**
     * Hide the button - called by the filter class
     */
    function hideButton() {
      isVisible.value = false;
    }
    /**
     * Show the button - called by the filter class
     */
    function showButton() {
      isVisible.value = true;
    }
    /**
     * Button click handler to make the filtering a global search.
     */
    function onClick() {
      const searchStore = (0,_store_search_ts__WEBPACK_IMPORTED_MODULE_4__.useSearchStore)((0,_store_index_ts__WEBPACK_IMPORTED_MODULE_3__.getPinia)());
      searchStore.scope = 'globally';
    }
    return {
      __sfc: true,
      isVisible,
      hideButton,
      showButton,
      onClick,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t,
      NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_2__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&lang=ts":
/*!***************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=script&lang=ts ***!
  \***************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _FileListFilter_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FileListFilter.vue */ "./apps/files/src/components/FileListFilter/FileListFilter.vue");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_2__.defineComponent)({
  name: 'FileListFilterType',
  components: {
    FileListFilter: _FileListFilter_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    presets: {
      type: Array,
      default: () => []
    },
    typePresets: {
      type: Array,
      required: true
    }
  },
  setup() {
    return {
      mdiFileOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiFileOutline,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate
    };
  },
  data() {
    return {
      selectedOptions: []
    };
  },
  computed: {
    isActive() {
      return this.selectedOptions.length > 0;
    }
  },
  watch: {
    /** Reset selected options if property is changed */
    presets() {
      this.selectedOptions = this.presets ?? [];
    },
    selectedOptions(newValue, oldValue) {
      if (this.selectedOptions.length === 0) {
        if (oldValue.length !== 0) {
          this.$emit('update:presets');
        }
      } else {
        this.$emit('update:presets', this.selectedOptions);
      }
    }
  },
  mounted() {
    this.selectedOptions = this.presets ?? [];
  },
  methods: {
    resetFilter() {
      this.selectedOptions = [];
    },
    /**
     * Toggle option from selected option
     * @param option The option to toggle
     */
    toggleOption(option) {
      const idx = this.selectedOptions.indexOf(option);
      if (idx !== -1) {
        this.selectedOptions.splice(idx, 1);
      } else {
        this.selectedOptions.push(option);
      }
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts":
/*!******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=script&setup=true&lang=ts ***!
  \******************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(path__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utils_filenameValidity_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/filenameValidity.ts */ "./apps/files/src/utils/filenameValidity.ts");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcDialog */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");










/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'NewNodeDialog',
  props: {
    /**
     * The name to be used by default
     */
    defaultName: {
      type: String,
      default: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'New folder')
    },
    /**
     * Other files that are in the current directory
     */
    otherNames: {
      type: Array,
      default: () => []
    },
    /**
     * Open state of the dialog
     */
    open: {
      type: Boolean,
      default: true
    },
    /**
     * Dialog name
     */
    name: {
      type: String,
      default: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Create new folder')
    },
    /**
     * Input label
     */
    label: {
      type: String,
      default: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'Folder name')
    }
  },
  emits: ["close"],
  setup(__props, _ref) {
    let {
      emit
    } = _ref;
    const props = __props;
    const localDefaultName = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(props.defaultName);
    const nameInput = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    const formElement = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    const validity = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)('');
    const isHiddenFileName = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
      // Check if the name starts with a dot, which indicates a hidden file
      return localDefaultName.value.trim().startsWith('.');
    });
    /**
     * Focus the filename input field
     */
    function focusInput() {
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.nextTick)(() => {
        // get the input element
        const input = nameInput.value?.$el.querySelector('input');
        if (!props.open || !input) {
          return;
        }
        // length of the basename
        const length = localDefaultName.value.length - (0,path__WEBPACK_IMPORTED_MODULE_3__.extname)(localDefaultName.value).length;
        // focus the input
        input.focus();
        // and set the selection to the basename (name without extension)
        input.setSelectionRange(0, length);
      });
    }
    /**
     * Trigger submit on the form
     */
    function submit() {
      formElement.value?.requestSubmit();
    }
    // Reset local name on props change
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(() => [props.defaultName, props.otherNames], () => {
      localDefaultName.value = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getUniqueName)(props.defaultName, props.otherNames).trim();
    });
    // Validate the local name
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watchEffect)(() => {
      if (props.otherNames.includes(localDefaultName.value.trim())) {
        validity.value = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('files', 'This name is already in use.');
      } else {
        validity.value = (0,_utils_filenameValidity_ts__WEBPACK_IMPORTED_MODULE_4__.getFilenameValidity)(localDefaultName.value.trim());
      }
      const input = nameInput.value?.$el.querySelector('input');
      if (input) {
        input.setCustomValidity(validity.value);
        input.reportValidity();
      }
    });
    // Ensure the input is focussed even if the dialog is already mounted but not open
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(() => props.open, () => {
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.nextTick)(() => {
        focusInput();
      });
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onMounted)(() => {
      // on mounted lets use the unique name
      localDefaultName.value = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getUniqueName)(localDefaultName.value, props.otherNames).trim();
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.nextTick)(() => focusInput());
    });
    return {
      __sfc: true,
      props,
      emit,
      localDefaultName,
      nameInput,
      formElement,
      validity,
      isHiddenFileName,
      focusInput,
      submit,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t,
      NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_5__["default"],
      NcDialog: _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_6__["default"],
      NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_7__["default"],
      NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_8__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c(_setup.NcActions, {
    attrs: {
      "force-menu": "",
      type: _vm.isActive ? "secondary" : "tertiary",
      "menu-name": _vm.filterName
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm._t("icon")];
      },
      proxy: true
    }], null, true)
  }, [_vm._v(" "), _vm._t("default"), _vm._v(" "), _vm.isActive ? [_c(_setup.NcActionSeparator), _vm._v(" "), _c(_setup.NcActionButton, {
    staticClass: "files-list-filter__clear-button",
    attrs: {
      "close-after-click": ""
    },
    on: {
      click: function ($event) {
        return _vm.$emit("reset-filter");
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_setup.t("files", "Clear filter")) + "\n\t\t")])] : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=template&id=f47dfc3e&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("FileListFilter", {
    attrs: {
      "is-active": _vm.isActive,
      "filter-name": _vm.t("files", "Modified")
    },
    on: {
      "reset-filter": _vm.resetFilter
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiCalendarRangeOutline
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _vm._l(_vm.timePresets, function (preset) {
    return _c("NcActionButton", {
      key: preset.id,
      attrs: {
        type: "radio",
        "close-after-click": "",
        "model-value": _vm.selectedOption,
        value: preset.id
      },
      on: {
        "update:modelValue": function ($event) {
          _vm.selectedOption = $event;
        },
        "update:model-value": function ($event) {
          _vm.selectedOption = $event;
        }
      }
    }, [_vm._v("\n\t\t" + _vm._s(preset.label) + "\n\t")]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=template&id=032b2a1b":
/*!********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterToSearch.vue?vue&type=template&id=032b2a1b ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c(_setup.NcButton, {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _setup.isVisible,
      expression: "isVisible"
    }],
    on: {
      click: _setup.onClick
    }
  }, [_vm._v("\n\t" + _vm._s(_setup.t("files", "Search everywhere")) + "\n")]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=template&id=6c0e6dd2 ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("FileListFilter", {
    staticClass: "file-list-filter-type",
    attrs: {
      "is-active": _vm.isActive,
      "filter-name": _vm.t("files", "Type")
    },
    on: {
      "reset-filter": _vm.resetFilter
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiFileOutline
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _vm._l(_vm.typePresets, function (fileType) {
    return _c("NcActionButton", {
      key: fileType.id,
      attrs: {
        type: "checkbox",
        "model-value": _vm.selectedOptions.includes(fileType)
      },
      on: {
        click: function ($event) {
          return _vm.toggleOption(fileType);
        }
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c("NcIconSvgWrapper", {
            attrs: {
              svg: fileType.icon
            }
          })];
        },
        proxy: true
      }], null, true)
    }, [_vm._v("\n\t\t" + _vm._s(fileType.label) + "\n\t")]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=template&id=e6b9c05a&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c(_setup.NcDialog, {
    attrs: {
      "data-cy-files-new-node-dialog": "",
      name: _vm.name,
      open: _vm.open,
      "close-on-click-outside": "",
      "out-transition": ""
    },
    on: {
      "update:open": function ($event) {
        return _setup.emit("close", null);
      }
    },
    scopedSlots: _vm._u([{
      key: "actions",
      fn: function () {
        return [_c(_setup.NcButton, {
          attrs: {
            "data-cy-files-new-node-dialog-submit": "",
            type: "primary",
            disabled: _setup.validity !== ""
          },
          on: {
            click: _setup.submit
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_setup.t("files", "Create")) + "\n\t\t")])];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("form", {
    ref: "formElement",
    staticClass: "new-node-dialog__form",
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _setup.emit("close", _setup.localDefaultName);
      }
    }
  }, [_c(_setup.NcTextField, {
    ref: "nameInput",
    attrs: {
      "data-cy-files-new-node-dialog-input": "",
      error: _setup.validity !== "",
      "helper-text": _setup.validity,
      label: _vm.label,
      value: _setup.localDefaultName
    },
    on: {
      "update:value": function ($event) {
        _setup.localDefaultName = $event;
      }
    }
  }), _vm._v(" "), _setup.isHiddenFileName ? _c(_setup.NcNoteCard, {
    attrs: {
      type: "warning",
      text: _setup.t("files", "Files starting with a dot are hidden by default")
    }
  }) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.files-list-filter-time__clear-button[data-v-f47dfc3e] .action-button__text {
  color: var(--color-error-text);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.files-list-filter__clear-button[data-v-5c291778] .action-button__text {
	color: var(--color-error-text);
}
[data-v-5c291778] .button-vue {
	font-weight: normal !important;
*[data-v-5c291778] {
		font-weight: normal !important;
}
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.file-list-filter-type {
	max-width: 220px;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css":
/*!***********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `
.new-node-dialog__form[data-v-e6b9c05a] {
	/* Ensure the dialog does not jump when there is a validity error */
	min-height: calc(2 * var(--default-clickable-area));
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterModified.vue?vue&type=style&index=0&id=f47dfc3e&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterModified_vue_vue_type_style_index_0_id_f47dfc3e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilterType.vue?vue&type=style&index=0&id=6c0e6dd2&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterType_vue_vue_type_style_index_0_id_6c0e6dd2_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/NewNodeDialog.vue?vue&type=style&index=0&id=e6b9c05a&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewNodeDialog_vue_vue_type_style_index_0_id_e6b9c05a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"2fcef36253529e5f48bc","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"f3a3966faa81f9b81fa8","apps_files_src_views_SearchEmptyView_vue":"f4a1d1b5018f4999b20a","node_modules_nextcloud_upload_dist_chunks_InvalidFilenameDialog-BYpqWa7P_mjs":"b5048b7dd1c81ffc8fe6","node_modules_nextcloud_upload_dist_chunks_ConflictPicker-BvM7ZujP_mjs":"f457ef4faf127a8a345a","apps_files_src_views_TemplatePicker_vue":"8034a0e7f970992f72fb","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-CsU6FfAP_mjs":"8bce3ebf3ef868f175e5","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"9fa10a9863e5b78deec8"}[chunkId] + "";
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
/******/ 			"files-init": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files/src/init.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files-init.js.map?v=5c50880652f4d555270b